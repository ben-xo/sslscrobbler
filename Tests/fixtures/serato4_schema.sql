-- Minimal subset of Serato DJ 4.x master.sqlite schema used by
-- SSLHistoryDatabaseMonitor. Dumped and trimmed from a real installation
-- (Serato DJ Pro 4.0.6). Triggers that call custom SQLite extension
-- functions (serato_str_norm, serato_raw_key_string_to_key_type) have
-- been removed — they're loaded from the Serato app binary at runtime
-- and aren't available to an independent reader. SSLHistoryDatabaseMonitor
-- doesn't read the normalized / pre-computed columns those triggers populate.

CREATE TABLE history_session
(
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    start_time      INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    end_time        INTEGER,
    notes           TEXT,
    composer        TEXT,
    label           TEXT,
    comment         TEXT,
    grouping        TEXT,
    key             TEXT,
    year            TEXT,
    name            TEXT
);

CREATE TABLE location
(
    id                      INTEGER PRIMARY KEY,
    path                    TEXT,
    uuid                    BLOB,
    revision                INT NOT NULL DEFAULT 0,
    show_when_disconnected  INT NOT NULL DEFAULT 0,
    last_sync_time          INTEGER NOT NULL DEFAULT 0,
    last_sync_secret        INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE history_entry
(
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    location_id         INTEGER,
    portable_id         TEXT NOT NULL DEFAULT '',
    file_name           TEXT DEFAULT NULL,
    file_size           INTEGER,
    file_bit_rate       REAL,
    file_sample_rate    REAL,
    type                TEXT NOT NULL DEFAULT '',
    format              TEXT NOT NULL DEFAULT '',
    artist              TEXT NOT NULL DEFAULT '',
    color               INTEGER,
    comments            TEXT NOT NULL DEFAULT '',
    comment_language    TEXT NOT NULL DEFAULT '',
    comment_descriptor  TEXT NOT NULL DEFAULT '',
    grouping            TEXT NOT NULL DEFAULT '',
    remixer             TEXT NOT NULL DEFAULT '',
    name                TEXT NOT NULL DEFAULT '',
    album               TEXT NOT NULL DEFAULT '',
    composer            TEXT NOT NULL DEFAULT '',
    year                TEXT NOT NULL DEFAULT '',
    genre               TEXT NOT NULL DEFAULT '',
    key                 TEXT NOT NULL DEFAULT '',
    label               TEXT NOT NULL DEFAULT '',
    rating              REAL CHECK (rating IS NULL OR (rating BETWEEN 0 AND 1)),
    emoji               TEXT NOT NULL DEFAULT '',
    bpm                 REAL,
    length_sec          INTEGER,
    length_ms           INTEGER,
    time_added          INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    time_modified       INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    part_of_set         TEXT NOT NULL DEFAULT '',
    track_number        TEXT NOT NULL DEFAULT '',
    video_portable_id   TEXT,
    is_corrupt          INTEGER NOT NULL DEFAULT 0,
    corrupt_description TEXT,
    is_missing          INTEGER NOT NULL DEFAULT 0,
    is_readonly         INTEGER NOT NULL DEFAULT 0,
    is_beatgrid_locked  INTEGER NOT NULL DEFAULT 0,
    third_party_type    INTEGER CHECK (third_party_type IS NOT NULL AND third_party_type >= 0) DEFAULT 0,
    is_stale            INTEGER NOT NULL DEFAULT 0,
    is_whitelabel       INTEGER NOT NULL DEFAULT 0,
    is_karaoke          INTEGER NOT NULL DEFAULT 0,
    dj_play_count       INTEGER,
    dj_recently_played  INTEGER NOT NULL DEFAULT 0,
    analysis_flags      INTEGER NOT NULL DEFAULT 0,
    architectures       INTEGER NOT NULL DEFAULT 0,
    stems_analyze_state INTEGER NOT NULL DEFAULT 0,
    type_specific_data  TEXT,
    file_name_norm      TEXT,
    name_norm           TEXT,
    artist_norm         TEXT,
    album_norm          TEXT,
    genre_norm          TEXT,
    comments_norm       TEXT,
    label_norm          TEXT,
    remixer_norm        TEXT,
    grouping_norm       TEXT,
    composer_norm       TEXT,
    year_norm           TEXT,
    key_norm            TEXT,
    key_value           INTEGER NOT NULL DEFAULT -1,
    session_id          INTEGER NOT NULL DEFAULT -1,
    asset_id            INTEGER DEFAULT -1,
    start_time          INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    end_time            INTEGER DEFAULT -1,
    played              INTEGER NOT NULL DEFAULT 0,
    deck                TEXT NOT NULL DEFAULT '',
    notes               TEXT NOT NULL DEFAULT '',
    device              TEXT NOT NULL DEFAULT '',
    app_name            TEXT NOT NULL DEFAULT '',
    app_version         TEXT NOT NULL DEFAULT '',
    needs_processing    INTEGER NOT NULL DEFAULT 0,

    FOREIGN KEY (session_id) REFERENCES history_session(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES location(id) ON DELETE SET NULL
);

CREATE INDEX history_entry__asset_id           ON history_entry (asset_id);
CREATE INDEX history_entry__history_session_id ON history_entry (session_id);
CREATE INDEX history_entry__location_id        ON history_entry (location_id);
