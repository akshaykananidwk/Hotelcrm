-- ============================================================================
--  HotelCRM ERP — Full MySQL Schema
--  Engine: InnoDB / utf8mb4. Compatible with MySQL 5.7+ and MariaDB 10.3+.
--  Multi-tenant: most business tables carry a hotel_id for SaaS isolation.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Access control
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(120) NOT NULL,
    slug     VARCHAR(120) NOT NULL UNIQUE,
    module   VARCHAR(60)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Hotels (tenants) & structure
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hotels (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id    INT UNSIGNED NULL,                 -- for branches
    name         VARCHAR(160) NOT NULL,
    slug         VARCHAR(160) NOT NULL UNIQUE,
    legal_name   VARCHAR(200) NULL,
    email        VARCHAR(160) NULL,
    phone        VARCHAR(40)  NULL,
    address      VARCHAR(255) NULL,
    city         VARCHAR(120) NULL,
    state        VARCHAR(120) NULL,
    country      VARCHAR(120) NULL DEFAULT 'India',
    pincode      VARCHAR(20)  NULL,
    gst_number   VARCHAR(30)  NULL,
    pan_number   VARCHAR(20)  NULL,
    currency     VARCHAR(8)   NOT NULL DEFAULT 'INR',
    currency_symbol VARCHAR(8) NOT NULL DEFAULT '₹',
    timezone     VARCHAR(60)  NOT NULL DEFAULT 'Asia/Kolkata',
    logo         VARCHAR(255) NULL,
    brand_color  VARCHAR(16)  NULL DEFAULT '#0d6efd',
    check_in_time  TIME NOT NULL DEFAULT '14:00:00',
    check_out_time TIME NOT NULL DEFAULT '11:00:00',
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES hotels(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS floors (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    name     VARCHAR(60)  NOT NULL,
    number   INT NOT NULL DEFAULT 0,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS amenities (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    name     VARCHAR(120) NOT NULL,
    icon     VARCHAR(60)  NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_types (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT UNSIGNED NOT NULL,
    name         VARCHAR(120) NOT NULL,
    code         VARCHAR(40)  NULL,
    description  TEXT NULL,
    base_price   DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_adults   TINYINT UNSIGNED NOT NULL DEFAULT 2,
    max_children TINYINT UNSIGNED NOT NULL DEFAULT 1,
    tax_rate     DECIMAL(5,2) NOT NULL DEFAULT 12.00,   -- default GST %
    amenities    VARCHAR(500) NULL,                     -- CSV of amenity ids
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id      INT UNSIGNED NOT NULL,
    room_type_id  INT UNSIGNED NOT NULL,
    floor_id      INT UNSIGNED NULL,
    number        VARCHAR(20)  NOT NULL,
    status        ENUM('available','occupied','blocked','maintenance') NOT NULL DEFAULT 'available',
    housekeeping  ENUM('clean','dirty','inspected','maintenance') NOT NULL DEFAULT 'clean',
    notes         VARCHAR(255) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_room (hotel_id, number),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-date inventory / rate overrides (channel manager + dynamic pricing).
CREATE TABLE IF NOT EXISTS room_inventory (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id      INT UNSIGNED NOT NULL,
    room_type_id  INT UNSIGNED NOT NULL,
    date          DATE NOT NULL,
    available      INT NOT NULL DEFAULT 0,
    price         DECIMAL(10,2) NULL,
    min_stay      INT NOT NULL DEFAULT 1,
    max_stay      INT NULL,
    stop_sell     TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_inv (room_type_id, date),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id      INT UNSIGNED NULL,                 -- NULL = platform super admin
    role_id       INT UNSIGNED NOT NULL,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(160) NOT NULL UNIQUE,
    phone         VARCHAR(40)  NULL,
    password      VARCHAR(255) NOT NULL,
    avatar        VARCHAR(255) NULL,
    two_factor_secret VARCHAR(120) NULL,
    status        ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,            -- sha256 of the plaintext
    abilities   VARCHAR(255) NULL,
    last_used_at DATETIME NULL,
    expires_at  DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(160) NOT NULL,
    user_id    INT UNSIGNED NULL,
    success    TINYINT(1) NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Guests / CRM
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS guests (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT UNSIGNED NOT NULL,
    first_name   VARCHAR(80)  NOT NULL,
    last_name    VARCHAR(80)  NULL,
    email        VARCHAR(160) NULL,
    phone        VARCHAR(40)  NULL,
    gender       ENUM('male','female','other') NULL,
    dob          DATE NULL,
    address      VARCHAR(255) NULL,
    city         VARCHAR(120) NULL,
    country      VARCHAR(120) NULL,
    id_type      ENUM('aadhaar','passport','pan','driving_license','voter_id','other') NULL,
    id_number    VARCHAR(80)  NULL,
    id_document  VARCHAR(255) NULL,
    nationality  VARCHAR(80)  NULL,
    company      VARCHAR(160) NULL,
    gst_number   VARCHAR(30)  NULL,
    preferences  TEXT NULL,
    notes        TEXT NULL,
    loyalty_points INT NOT NULL DEFAULT 0,
    loyalty_tier ENUM('none','silver','gold','platinum') NOT NULL DEFAULT 'none',
    is_blacklisted TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guest_phone (phone),
    INDEX idx_guest_email (email),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guest_feedback (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT UNSIGNED NOT NULL,
    guest_id     INT UNSIGNED NULL,
    reservation_id INT UNSIGNED NULL,
    rating       TINYINT UNSIGNED NOT NULL DEFAULT 5,
    comment      TEXT NULL,
    source       VARCHAR(60) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Reservations
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id       INT UNSIGNED NOT NULL,
    code           VARCHAR(30) NOT NULL UNIQUE,        -- e.g. RSV-2026-000123
    guest_id       INT UNSIGNED NOT NULL,
    source         ENUM('walk_in','website','ota','phone','corporate','travel_agent','group') NOT NULL DEFAULT 'walk_in',
    channel        VARCHAR(60) NULL,                   -- e.g. booking_com
    ota_reference  VARCHAR(120) NULL,
    check_in       DATE NOT NULL,
    check_out      DATE NOT NULL,
    adults         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    children       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status         ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show') NOT NULL DEFAULT 'pending',
    total_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    special_requests TEXT NULL,
    company        VARCHAR(160) NULL,
    group_id       INT UNSIGNED NULL,
    checked_in_at  DATETIME NULL,
    checked_out_at DATETIME NULL,
    cancelled_at   DATETIME NULL,
    created_by     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_res_dates (check_in, check_out),
    INDEX idx_res_status (status),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES guests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A reservation can span multiple rooms (group / multi-room bookings).
CREATE TABLE IF NOT EXISTS reservation_rooms (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    room_id        INT UNSIGNED NULL,
    room_type_id   INT UNSIGNED NOT NULL,
    rate           DECIMAL(10,2) NOT NULL DEFAULT 0,
    nights         INT NOT NULL DEFAULT 1,
    tax_rate       DECIMAL(5,2) NOT NULL DEFAULT 12.00,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Billing
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS invoices (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id       INT UNSIGNED NOT NULL,
    reservation_id INT UNSIGNED NULL,
    guest_id       INT UNSIGNED NULL,
    number         VARCHAR(40) NOT NULL UNIQUE,        -- INV-2026-000045
    subtotal       DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_total      DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount       DECIMAL(12,2) NOT NULL DEFAULT 0,
    total          DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid           DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance        DECIMAL(12,2) NOT NULL DEFAULT 0,
    status         ENUM('unpaid','partial','paid','refunded','void') NOT NULL DEFAULT 'unpaid',
    gst_number     VARCHAR(30) NULL,
    place_of_supply VARCHAR(120) NULL,
    notes          TEXT NULL,
    issued_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by     INT UNSIGNED NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id  INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    category    ENUM('room','restaurant','minibar','laundry','service','tax','other') NOT NULL DEFAULT 'room',
    quantity    DECIMAL(8,2) NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_rate    DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
    total       DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT UNSIGNED NOT NULL,
    invoice_id   INT UNSIGNED NULL,
    reservation_id INT UNSIGNED NULL,
    guest_id     INT UNSIGNED NULL,
    amount       DECIMAL(12,2) NOT NULL,
    method       ENUM('cash','card','upi','bank_transfer','razorpay','phonepe','payu','other') NOT NULL DEFAULT 'cash',
    gateway      VARCHAR(60) NULL,
    gateway_ref  VARCHAR(160) NULL,
    type         ENUM('payment','refund') NOT NULL DEFAULT 'payment',
    status       ENUM('pending','success','failed') NOT NULL DEFAULT 'success',
    notes        VARCHAR(255) NULL,
    created_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Housekeeping
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS housekeeping_tasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT UNSIGNED NOT NULL,
    room_id     INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    type        ENUM('cleaning','maintenance','inspection','laundry') NOT NULL DEFAULT 'cleaning',
    status      ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    priority    ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    notes       VARCHAR(255) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lost_and_found (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT UNSIGNED NOT NULL,
    room_id     INT UNSIGNED NULL,
    item        VARCHAR(160) NOT NULL,
    description TEXT NULL,
    status      ENUM('found','claimed','disposed') NOT NULL DEFAULT 'found',
    found_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Channel manager (OTA)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ota_channels (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT UNSIGNED NOT NULL,
    channel      VARCHAR(60) NOT NULL,                 -- booking_com, agoda...
    display_name VARCHAR(120) NOT NULL,
    credentials  TEXT NULL,                            -- JSON (encrypted at app layer)
    is_enabled   TINYINT(1) NOT NULL DEFAULT 0,
    last_sync_at DATETIME NULL,
    last_status  ENUM('idle','success','failed','running') NOT NULL DEFAULT 'idle',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_channel (hotel_id, channel),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_jobs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT UNSIGNED NOT NULL,
    channel     VARCHAR(60) NOT NULL,
    job_type    ENUM('inventory','rates','restrictions','bookings','full') NOT NULL,
    payload     TEXT NULL,
    status      ENUM('queued','running','success','failed') NOT NULL DEFAULT 'queued',
    attempts    INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 5,
    error       TEXT NULL,
    run_after   DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sync_status (status, run_after),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Booking engine: promos
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_codes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id      INT UNSIGNED NOT NULL,
    code          VARCHAR(40) NOT NULL,
    type          ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value         DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_uses      INT NULL,
    used_count    INT NOT NULL DEFAULT 0,
    valid_from    DATE NULL,
    valid_to      DATE NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_promo (hotel_id, code),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Notifications & messaging
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT UNSIGNED NULL,
    user_id     INT UNSIGNED NULL,
    channel     ENUM('in_app','email','whatsapp') NOT NULL DEFAULT 'in_app',
    title       VARCHAR(160) NOT NULL,
    body        TEXT NULL,
    recipient   VARCHAR(160) NULL,                     -- email / phone
    status      ENUM('queued','sent','failed','read') NOT NULL DEFAULT 'queued',
    error       VARCHAR(255) NULL,
    read_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_templates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id    INT UNSIGNED NULL,
    channel     ENUM('email','whatsapp') NOT NULL,
    event_key   VARCHAR(80) NOT NULL,                  -- booking_confirmation...
    subject     VARCHAR(200) NULL,
    body        TEXT NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_tpl (hotel_id, channel, event_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Settings (key/value per hotel, grouped)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id   INT UNSIGNED NULL,
    group_key  VARCHAR(60) NOT NULL,                   -- smtp, whatsapp, mikrotik...
    key_name   VARCHAR(80) NOT NULL,
    value      TEXT NULL,
    is_secret  TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_setting (hotel_id, group_key, key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- MikroTik guest Wi-Fi vouchers
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wifi_vouchers (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT UNSIGNED NOT NULL,
    reservation_id INT UNSIGNED NULL,
    username     VARCHAR(80) NOT NULL,
    password     VARCHAR(80) NOT NULL,
    profile      VARCHAR(80) NULL,
    bandwidth    VARCHAR(40) NULL,
    valid_until  DATETIME NULL,
    status       ENUM('active','expired','disabled') NOT NULL DEFAULT 'active',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Audit / activity logging
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    hotel_id   INT UNSIGNED NULL,
    action     VARCHAR(120) NOT NULL,
    entity     VARCHAR(80)  NULL,
    entity_id  VARCHAR(40)  NULL,
    meta       TEXT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity, entity_id),
    INDEX idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
