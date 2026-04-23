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
 */

use PHPUnit\Framework\TestCase;

/**
 * In-process observer that captures every notifyDiff payload so tests can
 * assert against them without weaving PHPUnit mocks through the observer
 * interface.
 */
class DatabaseMonitorTest_CapturingObserver implements SSLDiffObserver
{
    /** @var SSLDom[] */
    public $notifications = array();

    public function notifyDiff(SSLDom $changes)
    {
        $this->notifications[] = $changes;
    }

    /** Return the most recent diff payload, or null if none. */
    public function last()
    {
        return empty($this->notifications) ? null : end($this->notifications);
    }
}

class DatabaseMonitorTest_ExitCapture implements ExitObserver
{
    public $calls = 0;
    public function notifyExit()
    {
        $this->calls++;
    }
}

class SSLHistoryDatabaseMonitorTest extends TestCase
{
    /** @var PDO */
    protected $pdo;

    /** @var SSLHistoryDatabaseMonitor */
    protected $monitor;

    /** @var DatabaseMonitorTest_CapturingObserver */
    protected $obs;

    public function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $schema = file_get_contents(__DIR__ . '/../fixtures/serato4_schema.sql');
        $this->pdo->exec($schema);

        $this->monitor = new SSLHistoryDatabaseMonitor($this->pdo);
        $this->obs = new DatabaseMonitorTest_CapturingObserver();
        $this->monitor->addDiffObserver($this->obs);
    }

    public function tearDown(): void
    {
        Inject::reset();
    }

    // ------------------------------------------------------------------
    // Session selection
    // ------------------------------------------------------------------

    public function test_no_sessions_emits_no_diff()
    {
        $this->monitor->notifyTick(2);
        $this->assertCount(0, $this->obs->notifications,
            'Monitor should stay silent when the DB has no sessions');
    }

    public function test_prefers_active_session_over_closed_one()
    {
        // Closed earlier session with 1 entry
        $this->insertSession(100, 1700000000, 1700003600);
        $this->insertEntry(1, 100, array('artist' => 'A', 'name' => 'Closed track'));

        // Active (end_time NULL) later session with 1 entry
        $this->insertSession(101, 1700010000, null);
        $this->insertEntry(2, 101, array('artist' => 'B', 'name' => 'Live track'));

        $this->monitor->notifyTick(2);

        $tracks = $this->getLastDiffTracks();
        $this->assertCount(1, $tracks, 'Only the active session should be reported');
        $this->assertSame('B', $tracks[2]->getArtist());
        $this->assertSame('Live track', $tracks[2]->getTitle());
    }

    public function test_falls_back_to_most_recent_when_no_active_session()
    {
        // Simulates --post-process mode: Serato isn't running, last session is
        // closed, but we still want to replay it.
        $this->insertSession(100, 1700000000, 1700003600);
        $this->insertEntry(1, 100, array('artist' => 'A', 'name' => 'T1'));

        $this->monitor->notifyTick(2);

        $tracks = $this->getLastDiffTracks();
        $this->assertCount(1, $tracks);
        $this->assertSame('A', $tracks[1]->getArtist());
    }

    // ------------------------------------------------------------------
    // Core event shapes — insert, update, delete
    // ------------------------------------------------------------------

    public function test_new_entry_emits_sslTrack_diff()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(42, 1, array(
            'artist'     => 'Fred V',
            'name'       => 'Mistakes',
            'album'      => 'VIPs',
            'genre'      => 'Drum & Bass',
            'bpm'        => 174.0,
            'length_sec' => 215,
            'deck'       => '2',
            'played'     => 1,
            'start_time' => 1700000100,
            'end_time'   => null,
            'file_name'  => 'mistakes.mp3',
        ));

        $this->monitor->notifyTick(2);

        $tracks = $this->getLastDiffTracks();
        $this->assertCount(1, $tracks);

        $t = $tracks[42];
        $this->assertInstanceOf('SSLTrack', $t);
        $this->assertSame(42, $t->getRow());
        $this->assertSame('Fred V', $t->getArtist());
        $this->assertSame('Mistakes', $t->getTitle());
        $this->assertSame('Fred V - Mistakes', $t->getFullTitle());
        $this->assertSame('VIPs', $t->getAlbum());
        $this->assertSame('Drum & Bass', $t->getGenre());
        $this->assertSame(174, $t->getBpm());
        $this->assertSame(2, $t->getDeck());
        $this->assertSame(1, $t->getPlayed());
        $this->assertTrue($t->isPlayed());
        $this->assertSame('3:35.00', $t->getLength());
        $this->assertSame(215, $t->getLengthInSeconds());
    }

    public function test_idempotent_tick_emits_no_further_diff()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'B'));

        $this->monitor->notifyTick(2);
        $this->monitor->notifyTick(2);
        $this->monitor->notifyTick(2);

        $this->assertCount(1, $this->obs->notifications,
            'Unchanged rows must not be re-emitted');
    }

    public function test_play_state_transition_emits_update()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'B', 'played' => 0));

        $this->monitor->notifyTick(2);
        $this->assertSame(0, $this->getLastDiffTracks()[1]->getPlayed());

        // Serato promotes from NEW to PLAYING by flipping `played` 0 -> 1
        $this->pdo->exec('UPDATE history_entry SET played = 1 WHERE id = 1');
        $this->monitor->notifyTick(2);

        $this->assertCount(2, $this->obs->notifications);
        $this->assertSame(1, $this->getLastDiffTracks()[1]->getPlayed());
    }

    public function test_end_time_stamping_emits_update_and_computes_playtime()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array(
            'artist' => 'A', 'name' => 'B',
            'start_time' => 1700000100, 'end_time' => null, 'played' => 1,
        ));

        $this->monitor->notifyTick(2);
        // end_time still null -> playtime is 0 (track still on deck)
        $this->assertSame(0, $this->getLastDiffTracks()[1]->getPlaytime());

        // Track ejected 180s later
        $this->pdo->exec('UPDATE history_entry SET end_time = 1700000280 WHERE id = 1');
        $this->monitor->notifyTick(2);

        $this->assertCount(2, $this->obs->notifications);
        $this->assertSame(180, $this->getLastDiffTracks()[1]->getPlaytime());
    }

    public function test_metadata_edit_emits_update()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'Old', 'name' => 'Old Title'));

        $this->monitor->notifyTick(2);
        $this->pdo->exec("UPDATE history_entry SET name = 'New Title' WHERE id = 1");
        $this->monitor->notifyTick(2);

        $this->assertCount(2, $this->obs->notifications);
        $this->assertSame('New Title', $this->getLastDiffTracks()[1]->getTitle());
    }

    public function test_time_modified_churn_is_ignored()
    {
        // Serato's background sync silently rewrites time_modified on many rows
        // (it mirrors the audio file's mtime, not the DB row's). Our
        // fingerprint must not see that as a real change.
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'B'));

        $this->monitor->notifyTick(2);
        $this->pdo->exec('UPDATE history_entry SET time_modified = time_modified + 1000 WHERE id = 1');
        $this->monitor->notifyTick(2);

        $this->assertCount(1, $this->obs->notifications,
            'time_modified changes must not trigger a diff');
    }

    public function test_row_deletion_emits_SSLTrackDelete()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'B'));

        $this->monitor->notifyTick(2);
        $this->pdo->exec('DELETE FROM history_entry WHERE id = 1');
        $this->monitor->notifyTick(2);

        $this->assertCount(2, $this->obs->notifications);
        // SSLHistoryDiffDom's getTracks() returns the raw payload which can
        // include SSLTrackDelete instances as well as SSLTracks.
        $entries = $this->obs->last()->getTracks();
        $this->assertCount(1, $entries);
        $entry = array_pop($entries);
        $this->assertInstanceOf('SSLTrackDelete', $entry);
        $this->assertSame(1, $entry->getRow());
    }

    // ------------------------------------------------------------------
    // Session rollover
    // ------------------------------------------------------------------

    public function test_session_rollover_resets_snapshot()
    {
        // Session 1 starts and has a track.
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'Session1 track'));
        $this->monitor->notifyTick(2);

        // User closes session 1 and starts session 2 with its own first track.
        $this->pdo->exec('UPDATE history_session SET end_time = 1700001000 WHERE id = 1');
        $this->insertSession(2, 1700001100, null);
        $this->insertEntry(2, 2, array('artist' => 'B', 'name' => 'Session2 track'));
        $this->monitor->notifyTick(2);

        // The session-2 diff should report *only* row 2 — not any residue of
        // session 1. (In particular, row 1 must NOT appear as a delete, since
        // it belongs to a different session.)
        $tracks = $this->getLastDiffTracks();
        $this->assertArrayHasKey(2, $tracks);
        $this->assertArrayNotHasKey(1, $tracks);
        $this->assertSame('Session2 track', $tracks[2]->getTitle());
    }

    // ------------------------------------------------------------------
    // runOnce / post-process mode
    // ------------------------------------------------------------------

    public function test_runOnce_emits_entire_session_as_single_diff()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'T1', 'played' => 1));
        $this->insertEntry(2, 1, array('artist' => 'B', 'name' => 'T2', 'played' => 1));
        $this->insertEntry(3, 1, array('artist' => 'C', 'name' => 'T3', 'played' => 0));

        $count = $this->monitor->runOnce();

        $this->assertSame(3, $count);
        $this->assertCount(1, $this->obs->notifications,
            'runOnce should emit a single diff containing every entry');
        $tracks = $this->getLastDiffTracks();
        $this->assertCount(3, $tracks);
        $this->assertSame('T1', $tracks[1]->getTitle());
        $this->assertSame('T2', $tracks[2]->getTitle());
        $this->assertSame('T3', $tracks[3]->getTitle());
    }

    public function test_runOnce_on_empty_db_returns_zero_and_emits_nothing()
    {
        $count = $this->monitor->runOnce();
        $this->assertSame(0, $count);
        $this->assertCount(0, $this->obs->notifications);
    }

    public function test_runOnce_falls_back_to_most_recent_closed_session()
    {
        $this->insertSession(1, 1700000000, 1700003600);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'Old track'));

        $count = $this->monitor->runOnce();

        $this->assertSame(1, $count);
        $this->assertSame('Old track', $this->getLastDiffTracks()[1]->getTitle());
    }

    // ------------------------------------------------------------------
    // Stepped replay (--post-process --manual)
    // ------------------------------------------------------------------

    public function test_prepareSteppedReplay_queues_rows_without_emitting()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'T1'));
        $this->insertEntry(2, 1, array('artist' => 'B', 'name' => 'T2'));

        $count = $this->monitor->prepareSteppedReplay();

        $this->assertSame(2, $count);
        $this->assertCount(0, $this->obs->notifications,
            'prepareSteppedReplay only queues; emission happens on tick');
    }

    public function test_stepped_replay_emits_one_row_per_tick_then_exits()
    {
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array('artist' => 'A', 'name' => 'T1'));
        $this->insertEntry(2, 1, array('artist' => 'B', 'name' => 'T2'));
        $this->insertEntry(3, 1, array('artist' => 'C', 'name' => 'T3'));

        $this->monitor->prepareSteppedReplay();

        // Each tick should emit exactly one SSLTrack, in id order.
        $this->monitor->notifyTick(0);
        $this->assertCount(1, $this->obs->notifications);
        $this->assertSame('T1', $this->onlyTrack($this->obs->last())->getTitle());

        $this->monitor->notifyTick(0);
        $this->assertCount(2, $this->obs->notifications);
        $this->assertSame('T2', $this->onlyTrack($this->obs->last())->getTitle());

        $this->monitor->notifyTick(0);
        $this->assertCount(3, $this->obs->notifications);
        $this->assertSame('T3', $this->onlyTrack($this->obs->last())->getTitle());

        // Queue is drained — next tick should notify exit observers, not emit.
        $exit = new DatabaseMonitorTest_ExitCapture();
        $this->monitor->addExitObserver($exit);
        $this->monitor->notifyTick(0);

        $this->assertCount(3, $this->obs->notifications,
            'No further diffs once the queue is empty');
        $this->assertSame(1, $exit->calls, 'Exit observer should fire exactly once on drain');
    }

    public function test_stepped_replay_on_empty_session_exits_immediately()
    {
        $this->insertSession(1, 1700000000, null);  // no entries

        $count = $this->monitor->prepareSteppedReplay();
        $this->assertSame(0, $count);

        $exit = new DatabaseMonitorTest_ExitCapture();
        $this->monitor->addExitObserver($exit);
        $this->monitor->notifyTick(0);

        $this->assertCount(0, $this->obs->notifications);
        $this->assertSame(1, $exit->calls);
    }

    protected function onlyTrack(SSLHistoryDiffDom $dom)
    {
        $tracks = $dom->getTracks();
        $this->assertCount(1, $tracks);
        return array_shift($tracks);
    }

    // ------------------------------------------------------------------
    // Fullpath join
    // ------------------------------------------------------------------

    public function test_fullpath_is_joined_from_location_table()
    {
        $this->pdo->exec("INSERT INTO location (id, path) VALUES (7, '/Users/dj/Music/Tunes')");
        $this->insertSession(1, 1700000000, null);
        $this->insertEntry(1, 1, array(
            'artist' => 'A', 'name' => 'B',
            'file_name' => 'mixdown.mp3',
            'location_id' => 7,
        ));

        $this->monitor->notifyTick(2);

        $t = $this->getLastDiffTracks()[1];
        $this->assertSame('mixdown.mp3', $t->getFilename());
        $this->assertSame('/Users/dj/Music/Tunes/mixdown.mp3', $t->getFullpath());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function insertSession($id, $start_time, $end_time)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO history_session (id, start_time, end_time) VALUES (:id, :s, :e)'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':s', $start_time, PDO::PARAM_INT);
        $stmt->bindValue(':e', $end_time, $end_time === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
    }

    protected function insertEntry($id, $session_id, array $overrides)
    {
        $defaults = array(
            'id' => $id,
            'session_id' => $session_id,
            'artist' => '',
            'name' => '',
            'album' => '',
            'genre' => '',
            'bpm' => null,
            'length_sec' => null,
            'deck' => '',
            'played' => 0,
            'start_time' => 1700000000,
            'end_time' => null,
            'file_name' => null,
            'location_id' => null,
        );
        $fields = array_merge($defaults, $overrides);

        $cols = array_keys($fields);
        $placeholders = array_map(function ($c) { return ':' . $c; }, $cols);
        $sql = 'INSERT INTO history_entry (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        foreach ($fields as $k => $v) {
            $stmt->bindValue(':' . $k, $v, $v === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        $stmt->execute();
    }

    /**
     * @return array<int, SSLTrack> keyed by row id
     */
    protected function getLastDiffTracks()
    {
        $last = $this->obs->last();
        $this->assertNotNull($last, 'Expected at least one diff notification');
        $tracks = array();
        foreach ($last->getTracks() as $t) {
            $tracks[$t->getRow()] = $t;
        }
        return $tracks;
    }
}
