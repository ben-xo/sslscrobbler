<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

/**
 * Serato DJ 4.x stores history in a SQLite database at
 *   ~/Library/Application Support/Serato/Library/master.sqlite
 * instead of the binary chunked .session files used by prior versions.
 *
 * This monitor is the DB-mode equivalent of SSLHistoryFileMonitor + DiffMonitor:
 * on each tick it polls the active session's rows and emits an
 * SSLHistoryDiffDom of populated SSLTracks for anything that changed since
 * the previous tick.
 *
 * Change detection strategy: the `history_entry.time_modified` column turns
 * out to mirror the underlying audio file's mtime, not the DB row's mtime,
 * so we can't use it as a watermark. Instead we keep an in-memory snapshot
 * of the active session's rows and emit a diff when any fingerprinted column
 * changes. The snapshot is bounded by the size of a single DJ session
 * (typically tens to low hundreds of rows), so re-reading every tick is
 * cheap.
 */
class SSLHistoryDatabaseMonitor implements TickObserver, SSLDiffObservable, ExitObservable
{
    /**
     * Columns whose changes warrant emitting a diff event. Covers play
     * transitions, deck eject (end_time NULL -> value), metadata edits,
     * and the file-missing flag. Deliberately excludes time_modified
     * (unreliable; see class docblock), time_added, and pre-computed /
     * normalized columns that churn without user-meaningful change.
     */
    private static $FINGERPRINT_COLUMNS = array(
        'played', 'start_time', 'end_time', 'deck',
        'artist', 'name', 'album', 'length_sec', 'bpm',
        'genre', 'key', 'remixer', 'year', 'label',
        'composer', 'grouping', 'comments', 'is_missing',
    );

    /**
     * @var PDO
     */
    protected $pdo;

    protected $diff_observers = array();
    protected $exit_observers = array();

    /**
     * When non-null, notifyTick() is in stepped-replay mode: each tick shifts
     * one row off the queue and emits it as a single-track diff. When the
     * queue empties, exit observers are notified so the tick source can stop.
     * @var array<int, array<string, mixed>>|null
     */
    protected $replay_queue = null;

    /**
     * Snapshot of the active session's rows from the previous tick,
     * keyed by history_entry.id. Each entry is [fingerprint, SSLTrack].
     * @var array<int, array{0:string,1:SSLTrack}>
     */
    protected $prev = array();

    /**
     * @var int|null
     */
    protected $current_session_id = null;

    public function __construct(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
    }

    /**
     * Open Serato's master.sqlite read-only in a way safe to use while
     * Serato has it open for writing (WAL mode).
     */
    public static function openReadOnly($db_path)
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException(
                'The pdo_sqlite PHP extension is required for Serato DJ 4.x support. '
                . 'On macOS Homebrew and most Linux distros it is bundled with PHP. '
                . 'On Windows, enable it by uncommenting "extension=pdo_sqlite" in php.ini. '
                . 'Alternatively, pass --legacy to use the .session-file tail-monitoring path.'
            );
        }

        // The 'file:' URI form lets us request read-only mode without the
        // pdo_sqlite driver version caring about the PDO::SQLITE_* flags,
        // which aren't available on all PHP builds.
        $uri = 'sqlite:file:' . $db_path . '?mode=ro';
        return new PDO($uri, null, null, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));
    }

    public function addDiffObserver(SSLDiffObserver $observer)
    {
        $this->diff_observers[] = $observer;
    }

    public function addExitObserver(ExitObserver $observer)
    {
        $this->exit_observers[] = $observer;
    }

    public function notifyTick($seconds)
    {
        if ($this->replay_queue !== null) {
            $this->notifyTickStepped();
            return;
        }

        $session_id = $this->findCurrentSessionId();
        if ($session_id === null) {
            return;
        }

        if ($session_id !== $this->current_session_id) {
            L::level(L::INFO, __CLASS__) &&
                L::log(L::INFO, __CLASS__, 'Switched to session %d', array($session_id));
            $this->prev = array();
            $this->current_session_id = $session_id;
        }

        $rows = $this->fetchSessionEntries($session_id);
        $changed = $this->computeDiff($rows);

        if (!empty($changed)) {
            $this->notifyObservers(new SSLHistoryDiffDom($changed));
        }
    }

    /**
     * Tick handler used when stepped replay is armed. Emits one track per
     * tick (ordered by history_entry.id — the same order Serato wrote the
     * rows), then signals the tick source to stop when the queue empties.
     * This is the DB analogue of SSLHistoryFileReplayer driven by a
     * CrankHandle — press enter, see one event, repeat.
     *
     * Unlike the legacy replayer's group-by-updatedAt batching, the DB has
     * no equivalent grouping key (edits update rows in place, so there's
     * never "multiple rows written in the same write"), so one row per tick
     * is the natural unit.
     */
    protected function notifyTickStepped()
    {
        if (empty($this->replay_queue)) {
            foreach ($this->exit_observers as $observer) {
                /* @var $observer ExitObserver */
                $observer->notifyExit();
            }
            return;
        }

        $row = array_shift($this->replay_queue);
        $track = $this->rowToTrack($row);
        $this->notifyObservers(new SSLHistoryDiffDom(array($track->getRow() => $track)));
    }

    /**
     * Arm stepped-replay mode: pre-fetch the current session's rows (in id
     * order) so subsequent notifyTick() calls each emit one row as a
     * single-track diff, and signal exit once the queue empties. Intended
     * for --post-process combined with --manual.
     *
     * @return int number of rows queued
     */
    public function prepareSteppedReplay()
    {
        $session_id = $this->findCurrentSessionId();
        if ($session_id === null) {
            $this->replay_queue = array();
            return 0;
        }

        L::level(L::INFO, __CLASS__) &&
            L::log(L::INFO, __CLASS__, 'Stepped replay of session %d armed', array($session_id));

        $this->replay_queue = $this->fetchSessionEntries($session_id);
        return count($this->replay_queue);
    }

    /**
     * One-shot post-process mode: emit every entry in the current session as
     * a single SSLHistoryDiffDom. Intended for --post-process, where we want
     * to run the whole session once through ImmediateScrobbleModel /
     * ImmediateNowPlayingModel and then exit. Ignores the snapshot cache
     * since each runOnce() is an independent session replay.
     *
     * @return int number of entries emitted
     */
    public function runOnce()
    {
        $session_id = $this->findCurrentSessionId();
        if ($session_id === null) {
            return 0;
        }

        L::level(L::INFO, __CLASS__) &&
            L::log(L::INFO, __CLASS__, 'Post-processing session %d', array($session_id));

        $rows = $this->fetchSessionEntries($session_id);
        if (empty($rows)) {
            return 0;
        }

        $tracks = array();
        foreach ($rows as $row) {
            $track = $this->rowToTrack($row);
            $tracks[$track->getRow()] = $track;
        }
        $this->notifyObservers(new SSLHistoryDiffDom($tracks));
        return count($tracks);
    }

    /**
     * Pick the currently-active session. Prefers a session with no end_time
     * (Serato is still adding to it). Falls back to the most recent session
     * (used by --post-process / --immediate against a closed session).
     *
     * @return int|null
     */
    protected function findCurrentSessionId()
    {
        // Serato writes end_time = 0 on an active session (that's the value
        // the "Start Session" UI button commits) and a real unix timestamp
        // when the session is closed — either by the user pressing "End
        // Session", or implicitly when a subsequent "Start Session" rolls
        // it over. The NULL case is a belt-and-braces defensive fallback:
        // the schema allows it (no default), but in practice we've only ever
        // observed 0 or a real timestamp. Either way, treat "<= 0 or NULL"
        // as "not closed yet" so the live-mode polling picks up the right
        // session.
        $stmt = $this->pdo->query(
            'SELECT id FROM history_session
             WHERE end_time IS NULL OR end_time <= 0
             ORDER BY id DESC LIMIT 1'
        );
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $this->pdo->query(
            'SELECT id FROM history_session ORDER BY id DESC LIMIT 1'
        );
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchSessionEntries($session_id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT he.*,
                    loc.path AS location_path,
                    conn.database_uri AS connection_uri
             FROM history_entry he
             LEFT JOIN location loc ON loc.id = he.location_id
             LEFT JOIN connection conn ON conn.location_id = he.location_id
             WHERE he.session_id = :sid
             ORDER BY he.id'
        );
        $stmt->execute(array(':sid' => $session_id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compare the new row set to the previous snapshot, emit SSLTracks for
     * new or changed rows, and SSLTrackDeletes for rows that have vanished
     * (corresponds to OREN chunks in the legacy format).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, SSLTrack|SSLTrackDelete> keyed by row id
     */
    protected function computeDiff(array $rows)
    {
        $next_prev = array();
        $changed = array();

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $fp = $this->fingerprint($row);

            if (!isset($this->prev[$id]) || $this->prev[$id][0] !== $fp) {
                $track = $this->rowToTrack($row);
                $changed[$id] = $track;
                $next_prev[$id] = array($fp, $track);
            } else {
                // unchanged — keep previous SSLTrack instance
                $next_prev[$id] = $this->prev[$id];
            }
        }

        // Detect deletions (present in prev, absent now)
        foreach ($this->prev as $id => $_) {
            if (!isset($next_prev[$id])) {
                $delete = new SSLTrackDelete();
                $delete->populateFrom(array('row' => $id));
                $changed[$id] = $delete;
            }
        }

        $this->prev = $next_prev;
        return $changed;
    }

    protected function fingerprint(array $row)
    {
        $bits = array();
        foreach (self::$FINGERPRINT_COLUMNS as $col) {
            $bits[] = isset($row[$col]) && $row[$col] !== null ? (string) $row[$col] : '';
        }
        // crc32 is plenty: we only compare a row's fingerprint against its
        // OWN previous fingerprint, so collisions are bounded by a row's
        // lifetime (a handful of state transitions). No cryptographic
        // requirement — cheapest PHP-stdlib hash wins.
        return crc32(implode("\x1f", $bits));
    }

    /**
     * Map a history_entry DB row (plus the joined location.path) onto an
     * SSLTrack via the XOUP field-name vocabulary. Only the fields that
     * downstream models/plugins actually consume are populated; unmapped
     * XOUP fields remain unset, which SSLTrack's getters already tolerate.
     */
    protected function rowToTrack(array $row)
    {
        // ---- Normalise the tricky columns up front. ---------------------
        //
        // Serato uses a small family of "unset" sentinels: NULL, empty
        // string, -1 (the schema default for history_entry.end_time) and
        // 0 (observed on end_time for freshly-loaded tracks). normalise()
        // collapses all of them to a single PHP null.
        $start = $this->normaliseTimestamp($row, 'start_time');
        $end   = $this->normaliseTimestamp($row, 'end_time');

        // Playtime is derived: seconds between start and end if the track
        // has been ejected, else 0 (= "still on deck").
        $playtime = ($start !== null && $end !== null) ? max(0, $end - $start) : 0;

        // Length: SSLTrack expects the legacy string form "MM:SS.cc"
        // because SSLTrack::getLengthInSeconds() parses it with a regex.
        // Serato 4 stores it as length_ms (millisecond precision); some
        // older or partially-analysed rows expose only the integer
        // length_sec. Prefer ms so the row's value is used and the
        // expensive guess-from-file fallback can stay dormant.
        $length_ms = $this->nullableInt($row, 'length_ms');
        if ($length_ms === null) {
            $length_sec = $this->nullableInt($row, 'length_sec');
            $length_ms = $length_sec !== null ? $length_sec * 1000 : null;
        }
        $length_str = $this->formatLegacyLengthString($length_ms);

        // Build the on-disk path to the audio file.
        //
        // Real Serato 4 installs leave location.path empty and instead
        // store the volume root in connection.database_uri (e.g.
        // "/Volumes/MUSIC/_Serato_/Library/location.sqlite"). The
        // per-track relative path lives in history_entry.portable_id
        // ("DNB/foo/bar.aif"), with file_name only the basename.
        //
        // Falling back to location.path / file_name keeps tests and
        // hypothetical future schema variants working.
        $location_root = $this->resolveLocationRoot(
            isset($row['location_path']) ? $row['location_path'] : null,
            isset($row['connection_uri']) ? $row['connection_uri'] : null
        );
        $portable_id = isset($row['portable_id']) ? $row['portable_id'] : '';
        $file_name   = isset($row['file_name']) ? $row['file_name'] : null;
        $relative    = $portable_id !== '' ? $portable_id : $file_name;
        $fullpath    = $this->joinFullpath($location_root, $relative);

        // Deck: stored as TEXT in Serato 4 ("1", "2", ...). Downstream
        // code wants an int; non-numeric (including empty) -> null.
        $deck = $this->parseDeck(isset($row['deck']) ? $row['deck'] : '');

        // ---- XOUP-keyed fields that SSLTrack's getters look up. --------
        //
        // Field naming follows SSL/Structs/SSLTrackAdat.xoup — mostly
        // all-lowercase single words. Two subtleties are called out below.
        $fields = array(
            'row'       => (int) $row['id'],
            'artist'    => (string) (isset($row['artist']) ? $row['artist'] : ''),
            'title'     => (string) (isset($row['name'])   ? $row['name']   : ''),  // DB 'name' -> XOUP 'title'
            'album'     => (string) (isset($row['album'])  ? $row['album']  : ''),
            'genre'     => (string) (isset($row['genre'])  ? $row['genre']  : ''),
            'length'    => $length_str,
            'bpm'       => $this->nullableInt($row, 'bpm'),
            'deck'      => $deck,
            'played'    => (int) (isset($row['played']) ? $row['played'] : 0),
            'playtime'  => $playtime,

            // The XOUP field names are 'starttime' and 'endtime' (fully
            // lowercase). SSLTrack has explicit getStartTime() and
            // getEndTime() accessors (added in the same PR that introduced
            // this monitor) that read these keys directly, side-stepping
            // the camelCase mismatch that GetterSetter::__call would have.
            'starttime' => $start,
            'endtime'   => $end,

            'filename'  => $file_name,
            'fullpath'  => $fullpath,
            'key'       => (string) (isset($row['key']) ? $row['key'] : ''),
            'sessionId' => (int) (isset($row['session_id']) ? $row['session_id'] : 0),

            // history_entry.time_modified mirrors the underlying audio
            // file's mtime (empirically verified), not the DB row's
            // modification time — so it's useless as an "updated at". We
            // fall back to start_time as a monotonic-within-session proxy.
            // getUpdatedAt() is only read by paths we don't exercise from
            // the DB monitor (SSLHistoryDom::getNewOrUpdatedTracksSince
            // and the legacy replayer's group-by-timestamp batching), so
            // strict fidelity here doesn't matter.
            'updatedAt' => $start !== null ? $start : 0,
        );

        $factory = Inject::the(new SSLTrackFactory());
        $track = $factory->newTrack();
        $track->populateFrom($fields);
        return $track;
    }

    /**
     * Read an integer timestamp from $row[$col], collapsing all of
     * Serato's "unset" sentinels (NULL, '', 0, -1) to PHP null.
     *
     * @return int|null
     */
    protected function normaliseTimestamp(array $row, $col)
    {
        if (!isset($row[$col]) || $row[$col] === null || $row[$col] === '') {
            return null;
        }
        $value = (int) $row[$col];
        return $value > 0 ? $value : null;
    }

    /**
     * @return int|null
     */
    protected function nullableInt(array $row, $col)
    {
        if (!isset($row[$col]) || $row[$col] === null || $row[$col] === '') {
            return null;
        }
        return (int) $row[$col];
    }

    /**
     * Rebuild the legacy "MM:SS.cc" string form that SSLTrack's getters
     * expect from a millisecond duration. A null or non-positive value
     * returns null, matching how XOUP-parsed tracks behave when Serato
     * hasn't analysed the file.
     *
     * @return string|null
     */
    protected function formatLegacyLengthString($length_ms)
    {
        if ($length_ms === null || $length_ms <= 0) {
            return null;
        }
        $minutes = intdiv($length_ms, 60000);
        $seconds = intdiv($length_ms % 60000, 1000);
        $centis  = intdiv($length_ms % 1000, 10);
        return sprintf('%d:%02d.%02d', $minutes, $seconds, $centis);
    }

    /**
     * Resolve the on-disk root for a history entry. Prefer a populated
     * location.path; otherwise infer the root from connection.database_uri
     * by stripping Serato's well-known suffixes:
     *
     *   - "<volume>/_Serato_/Library/location.sqlite" -> "<volume>"
     *   - "<anywhere>/Library/root.sqlite"            -> "/" (boot drive,
     *     where portable_ids like "Users/dj/Music/..." are relative-to-root)
     *
     * Backslashes in the URI (Windows installs) are normalised to forward
     * slashes for matching; PHP's filesystem functions accept either.
     *
     * @return string|null
     */
    protected function resolveLocationRoot($location_path, $connection_uri)
    {
        if ($location_path !== null && $location_path !== '') {
            return $location_path;
        }
        if ($connection_uri === null || $connection_uri === '') {
            return null;
        }
        $uri = str_replace('\\', '/', $connection_uri);
        if (preg_match('#^(.*?)/_Serato_/Library/location\.sqlite$#i', $uri, $m)) {
            return $m[1] !== '' ? $m[1] : '/';
        }
        if (preg_match('#/Library/root\.sqlite$#i', $uri)) {
            return '/';
        }
        return null;
    }

    /**
     * @return string|null
     */
    protected function joinFullpath($location_path, $relative)
    {
        if ($location_path === null
            || $relative === null || $relative === '') {
            return null;
        }
        $relative = ltrim($relative, '/');
        if ($location_path === '/') {
            return '/' . $relative;
        }
        return rtrim($location_path, '/') . '/' . $relative;
    }

    /**
     * Serato 4 stores history_entry.deck as TEXT ("1", "2", ...). Downstream
     * code expects an int; non-numeric values (including empty) collapse to
     * null.
     *
     * @return int|null
     */
    protected function parseDeck($deck_raw)
    {
        if ($deck_raw === null || $deck_raw === '' || !is_numeric($deck_raw)) {
            return null;
        }
        return (int) $deck_raw;
    }

    protected function notifyObservers(SSLDom $changes)
    {
        foreach ($this->diff_observers as $observer) {
            /* @var $observer SSLDiffObserver */
            $observer->notifyDiff($changes);
        }
    }
}
