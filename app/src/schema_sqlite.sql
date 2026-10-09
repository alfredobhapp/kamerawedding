CREATE TABLE events (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  slug             TEXT NOT NULL UNIQUE,
  title            TEXT NOT NULL,
  event_date       TEXT NOT NULL,
  welcome_text     TEXT,
  access_token     TEXT NOT NULL,
  access_pin_hash  TEXT,
  upload_enabled   INTEGER NOT NULL DEFAULT 1,
  gallery_enabled  INTEGER NOT NULL DEFAULT 1,
  upload_open_at   TEXT,
  upload_close_at  TEXT,
  retention_days   INTEGER NOT NULL DEFAULT 90,
  storage_quota_mb INTEGER NOT NULL DEFAULT 4096,
  photo_count      INTEGER NOT NULL DEFAULT 0,
  total_bytes      INTEGER NOT NULL DEFAULT 0,
  created_at       TEXT NOT NULL,
  updated_at       TEXT NOT NULL
);

CREATE TABLE photos (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id         INTEGER NOT NULL,
  guest_id         TEXT NOT NULL,
  client_uuid      TEXT NOT NULL,
  sha256           TEXT NOT NULL,
  file_key         TEXT NOT NULL,
  ext              TEXT NOT NULL,
  width            INTEGER NOT NULL,
  height           INTEGER NOT NULL,
  size_bytes       INTEGER NOT NULL,
  thumb_bytes      INTEGER NOT NULL,
  src_format       TEXT NOT NULL,
  status           TEXT NOT NULL DEFAULT 'active',
  guest_note       TEXT,
  delete_token_hash TEXT,
  created_at       TEXT NOT NULL,
  deleted_at       TEXT,
  FOREIGN KEY (event_id) REFERENCES events(id),
  UNIQUE (event_id, client_uuid)
);
CREATE INDEX idx_photo_list ON photos(event_id, status, id);
CREATE INDEX idx_photo_hash ON photos(event_id, sha256);
CREATE INDEX idx_photo_guest ON photos(guest_id, id);

CREATE TABLE guests (
  guest_id      TEXT PRIMARY KEY,
  first_seen_at TEXT NOT NULL,
  last_seen_at  TEXT NOT NULL,
  upload_count  INTEGER NOT NULL DEFAULT 0,
  device_class  TEXT NOT NULL DEFAULT 'other',
  ip_hash       TEXT
);

CREATE TABLE admins (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  username      TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  last_login_at TEXT,
  created_at    TEXT NOT NULL
);

CREATE TABLE rate_limits (
  bucket       TEXT PRIMARY KEY,
  window_start INTEGER NOT NULL,
  hits         INTEGER NOT NULL
);
CREATE INDEX idx_rate_window ON rate_limits(window_start);

CREATE TABLE audit_log (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  admin_id   INTEGER NOT NULL,
  action     TEXT NOT NULL,
  target     TEXT,
  created_at TEXT NOT NULL
);
