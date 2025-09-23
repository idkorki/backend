PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS events (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  title      TEXT NOT NULL,
  description TEXT,
  date       TEXT,          -- ISO: YYYY-MM-DD
  startTime  TEXT,          -- HH:MM (24h)
  endTime    TEXT,          -- HH:MM (24h)
  status     TEXT NOT NULL DEFAULT 'draft'
);

CREATE TABLE IF NOT EXISTS stages (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id    INTEGER NOT NULL,
  title       TEXT NOT NULL DEFAULT '',
  description TEXT,
  startTime   TEXT,
  endTime     TEXT,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_stages_event_id ON stages(event_id);

