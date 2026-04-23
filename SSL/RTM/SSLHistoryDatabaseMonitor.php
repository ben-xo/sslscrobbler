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
class SSLHistoryDatabaseMonitor implements TickObserver, SSLDiffObservable
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

    public function notifyTick($seconds)
    {
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
     * Pick the currently-active session. Prefers a session with no end_time
     * (Serato is still adding to it). Falls back to the most recent session
     * (used by --post-process / --immediate against a closed session).
     *
     * @return int|null
     */
    protected function findCurrentSessionId()
    {
        // Serato writes end_time = NULL while a session is in progress, and a
        // real unix timestamp when the session closes cleanly. We've also
        // observed end_time = 0 after an unclean shutdown (e.g. Serato was
        // force-quit) — treat that as "not cleanly closed" too.
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
            'SELECT he.*, loc.path AS location_path
             FROM history_entry he
             LEFT JOIN location loc ON loc.id = he.location_id
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
        return sha1(implode("\x1f", $bits));
    }

    /**
     * Map a history_entry DB row (plus the joined location.path) onto an
     * SSLTrack via the XOUP field-name vocabulary. Only fields actually
     * consumed by downstream models/plugins are populated; unknown XOUP
     * fields remain unset as SSLTrack's getters already tolerate that.
     */
    protected function rowToTrack(array $row)
    {
        $start = isset($row['start_time']) ? (int) $row['start_time'] : null;
        $end_raw = isset($row['end_time']) ? $row['end_time'] : null;
        $end = ($end_raw === null || $end_raw === '' || (int) $end_raw <= 0)
            ? null : (int) $end_raw;
        $playtime = ($end !== null && $start !== null) ? max(0, $end - $start) : 0;

        $length_sec = isset($row['length_sec']) && $row['length_sec'] !== null
            ? (int) $row['length_sec'] : null;
        $length_str = ($length_sec !== null && $length_sec > 0)
            ? sprintf('%d:%02d.00', intdiv($length_sec, 60), $length_sec % 60)
            : null;

        $file_name = isset($row['file_name']) ? $row['file_name'] : null;
        $path = isset($row['location_path']) ? $row['location_path'] : null;
        $fullpath = ($path !== null && $file_name !== null && $path !== '' && $file_name !== '')
            ? rtrim($path, '/') . '/' . $file_name : null;

        $deck_raw = isset($row['deck']) ? $row['deck'] : '';
        $deck = ($deck_raw !== '' && $deck_raw !== null && is_numeric($deck_raw))
            ? (int) $deck_raw : null;

        $fields = array(
            'row'       => (int) $row['id'],
            'artist'    => isset($row['artist']) ? (string) $row['artist'] : '',
            'title'     => isset($row['name']) ? (string) $row['name'] : '',
            'album'     => isset($row['album']) ? (string) $row['album'] : '',
            'genre'     => isset($row['genre']) ? (string) $row['genre'] : '',
            'length'    => $length_str,
            'bpm'       => isset($row['bpm']) && $row['bpm'] !== null ? (int) $row['bpm'] : null,
            'deck'      => $deck,
            'played'    => isset($row['played']) ? (int) $row['played'] : 0,
            'playtime'  => $playtime,
            // NB: the XOUP field names 'starttime' and 'endtime' are lowercase,
            // while SSLTrack::getStartTime() and getEndTime() would look up
            // 'startTime' / 'endTime' (camelCase) via __call. That mismatch is
            // a long-standing latent bug in the legacy path — those two getters
            // return NULL on XOUP-parsed tracks too. We faithfully replicate
            // the legacy keying here; fixing the getters is a separate concern.
            'starttime' => $start,
            'endtime'   => $end,
            'filename'  => $file_name,
            'fullpath'  => $fullpath,
            'key'       => isset($row['key']) ? (string) $row['key'] : '',
            'sessionId' => isset($row['session_id']) ? (int) $row['session_id'] : 0,
            // time_modified is the asset file's mtime, not the DB row's; start_time
            // is the best monotonic-within-session proxy for updatedAt.
            'updatedAt' => $start !== null ? $start : 0,
        );

        $factory = Inject::the(new SSLTrackFactory());
        $track = $factory->newTrack();
        $track->populateFrom($fields);
        return $track;
    }

    protected function notifyObservers(SSLDom $changes)
    {
        foreach ($this->diff_observers as $observer) {
            /* @var $observer SSLDiffObserver */
            $observer->notifyDiff($changes);
        }
    }
}
