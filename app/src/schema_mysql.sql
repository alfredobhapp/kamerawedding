CREATE TABLE events (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(60)  NOT NULL UNIQUE,
  title            VARCHAR(80)  NOT NULL,
  event_date       DATE         NOT NULL,
  welcome_text     VARCHAR(200) NULL,
  access_token     CHAR(22)     NOT NULL,
  access_pin_hash  VARCHAR(255) NULL,
  upload_enabled   TINYINT(1)   NOT NULL DEFAULT 1,
  gallery_enabled  TINYINT(1)   NOT NULL DEFAULT 1,
  upload_open_at   DATETIME     NULL,
  upload_close_at  DATETIME     NULL,
  retention_days   SMALLINT UNSIGNED NOT NULL DEFAULT 90,
  storage_quota_mb INT UNSIGNED NOT NULL DEFAULT 4096,
  photo_count      INT UNSIGNED NOT NULL DEFAULT 0,
  total_bytes      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at       DATETIME NOT NULL,
  updated_at       DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE photos (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id         INT UNSIGNED NOT NULL,
  guest_id         CHAR(36)     NOT NULL,
  client_uuid      CHAR(36)     NOT NULL,
  sha256           CHAR(64)     NOT NULL,
  file_key         CHAR(32)     NOT NULL,
  ext              ENUM('jpg','gif') NOT NULL,
  width            SMALLINT UNSIGNED NOT NULL,
  height           SMALLINT UNSIGNED NOT NULL,
  size_bytes       INT UNSIGNED NOT NULL,
  thumb_bytes      INT UNSIGNED NOT NULL,
  src_format       VARCHAR(10)  NOT NULL,
  status           ENUM('active','hidden','pending','deleted') NOT NULL DEFAULT 'active',
  guest_note       VARCHAR(500) NULL,
  delete_token_hash CHAR(64)    NULL,
  created_at       DATETIME NOT NULL,
  deleted_at       DATETIME NULL,
  UNIQUE KEY uq_client (event_id, client_uuid),
  KEY idx_list  (event_id, status, id),
  KEY idx_hash  (event_id, sha256),
  KEY idx_guest (guest_id, id),
  CONSTRAINT fk_photo_event FOREIGN KEY (event_id) REFERENCES events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE guests (
  guest_id      CHAR(36) PRIMARY KEY,
  first_seen_at DATETIME NOT NULL,
  last_seen_at  DATETIME NOT NULL,
  upload_count  INT UNSIGNED NOT NULL DEFAULT 0,
  device_class  ENUM('ios','android','desktop','other') NOT NULL DEFAULT 'other',
  ip_hash       CHAR(16) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(40)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rate_limits (
  bucket       VARCHAR(80) PRIMARY KEY,
  window_start INT UNSIGNED NOT NULL,
  hits         INT UNSIGNED NOT NULL,
  KEY idx_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_log (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id   INT UNSIGNED NOT NULL,
  action     VARCHAR(40)  NOT NULL,
  target     VARCHAR(80)  NULL,
  created_at DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
