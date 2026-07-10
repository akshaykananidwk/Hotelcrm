-- ============================================================================
--  HotelCRM ERP — Seed / sample data
--  Default logins:
--    Super Admin : admin@hotelcrm.test  / Admin@123
--    Manager     : manager@hotelcrm.test / Password@123
--    Reception   : reception@hotelcrm.test / Password@123
-- ============================================================================

-- ---- Roles ----------------------------------------------------------------
INSERT INTO roles (id, name, slug, description, is_system) VALUES
    (1, 'Super Admin',   'super_admin',  'Platform owner with unrestricted access', 1),
    (2, 'Hotel Owner',   'hotel_owner',  'Owns one or more hotels', 1),
    (3, 'Manager',       'manager',      'Manages daily operations', 1),
    (4, 'Receptionist',  'receptionist', 'Front desk operations', 1),
    (5, 'Accountant',    'accountant',   'Billing and finance', 1),
    (6, 'Housekeeping',  'housekeeping', 'Housekeeping staff', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Permissions ----------------------------------------------------------
INSERT INTO permissions (name, slug, module) VALUES
    ('View Dashboard','dashboard.view','dashboard'),
    ('Manage Hotels','hotels.manage','hotels'),
    ('View Rooms','rooms.view','rooms'),
    ('Manage Rooms','rooms.manage','rooms'),
    ('View Reservations','reservations.view','reservations'),
    ('Manage Reservations','reservations.manage','reservations'),
    ('Front Office','frontoffice.manage','frontoffice'),
    ('Housekeeping','housekeeping.manage','housekeeping'),
    ('View Billing','billing.view','billing'),
    ('Manage Billing','billing.manage','billing'),
    ('View Guests','guests.view','guests'),
    ('Manage Guests','guests.manage','guests'),
    ('View Reports','reports.view','reports'),
    ('Manage Channels','channels.manage','channels'),
    ('Manage Users','users.manage','users'),
    ('Manage Settings','settings.manage','settings'),
    ('View Logs','logs.view','logs')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Role → Permission mapping -------------------------------------------
-- Hotel Owner: everything except platform-level user management is granted via '*'
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Manager: operations + reports, no settings/users
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE slug IN
 ('dashboard.view','rooms.view','rooms.manage','reservations.view','reservations.manage',
  'frontoffice.manage','housekeeping.manage','billing.view','billing.manage',
  'guests.view','guests.manage','reports.view','channels.manage')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Receptionist
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE slug IN
 ('dashboard.view','rooms.view','reservations.view','reservations.manage',
  'frontoffice.manage','billing.view','guests.view','guests.manage')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Accountant
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE slug IN
 ('dashboard.view','billing.view','billing.manage','reports.view','guests.view')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Housekeeping
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions WHERE slug IN
 ('dashboard.view','rooms.view','housekeeping.manage')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- ---- Sample hotel ---------------------------------------------------------
INSERT INTO hotels (id, name, slug, legal_name, email, phone, address, city, state, country, pincode, gst_number, currency, currency_symbol, timezone, brand_color)
VALUES (1, 'Grand Palace Hotel', 'grand-palace', 'Grand Palace Hospitality Pvt Ltd',
        'info@grandpalace.test', '+91 79 1234 5678', '12 MG Road', 'Ahmedabad', 'Gujarat',
        'India', '380001', '24ABCDE1234F1Z5', 'INR', '₹', 'Asia/Kolkata', '#0d6efd')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Users (passwords are bcrypt hashes; see header) ----------------------
INSERT INTO users (id, hotel_id, role_id, name, email, phone, password, status) VALUES
    (1, NULL, 1, 'Super Admin', 'admin@hotelcrm.test', '+919978123146',
        '$2y$12$EF5w4D96VLRTAvZBrx.MAueTxzQYw4Elsxtpn8QwUfeb1t6ycVDgy', 'active'),
    (2, 1, 3, 'Hotel Manager', 'manager@hotelcrm.test', '+919978123147',
        '$2y$12$k6vvCC01jKh4x6ysiZbiIO0SUbOnyFl63GisLn.ZS58120etrBMe2', 'active'),
    (3, 1, 4, 'Front Desk', 'reception@hotelcrm.test', '+919978123148',
        '$2y$12$k6vvCC01jKh4x6ysiZbiIO0SUbOnyFl63GisLn.ZS58120etrBMe2', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Floors & amenities ---------------------------------------------------
INSERT INTO floors (hotel_id, name, number) VALUES
    (1, 'Ground Floor', 0), (1, 'First Floor', 1), (1, 'Second Floor', 2);

INSERT INTO amenities (hotel_id, name, icon) VALUES
    (1, 'Wi-Fi', 'wifi'), (1, 'Air Conditioning', 'snow'), (1, 'TV', 'tv'),
    (1, 'Mini Bar', 'cup-straw'), (1, 'Room Service', 'bell');

-- ---- Room types -----------------------------------------------------------
INSERT INTO room_types (id, hotel_id, name, code, description, base_price, max_adults, max_children, tax_rate) VALUES
    (1, 1, 'Standard',  'STD', 'Comfortable standard room', 2500.00, 2, 1, 12.00),
    (2, 1, 'Deluxe',    'DLX', 'Spacious deluxe room with city view', 4000.00, 3, 1, 12.00),
    (3, 1, 'Suite',     'STE', 'Luxury suite with living area', 7500.00, 4, 2, 18.00)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ---- Rooms ----------------------------------------------------------------
INSERT INTO rooms (hotel_id, room_type_id, floor_id, number, status, housekeeping) VALUES
    (1, 1, 1, '101', 'available', 'clean'),
    (1, 1, 1, '102', 'available', 'clean'),
    (1, 1, 1, '103', 'available', 'dirty'),
    (1, 2, 2, '201', 'available', 'clean'),
    (1, 2, 2, '202', 'available', 'clean'),
    (1, 3, 3, '301', 'available', 'inspected');

-- ---- Sample guest ---------------------------------------------------------
INSERT INTO guests (id, hotel_id, first_name, last_name, email, phone, id_type, id_number, city, country, loyalty_tier)
VALUES (1, 1, 'Rahul', 'Sharma', 'rahul@example.test', '+919812345678', 'aadhaar', 'XXXX-XXXX-1234', 'Mumbai', 'India', 'silver')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- ---- OTA channels (disabled until credentials are entered) ----------------
INSERT INTO ota_channels (hotel_id, channel, display_name, is_enabled) VALUES
    (1, 'booking_com', 'Booking.com', 0),
    (1, 'makemytrip',  'MakeMyTrip', 0),
    (1, 'goibibo',     'Goibibo', 0),
    (1, 'agoda',       'Agoda', 0),
    (1, 'expedia',     'Expedia', 0),
    (1, 'airbnb',      'Airbnb', 0),
    (1, 'hotels_com',  'Hotels.com', 0),
    (1, 'trip_com',    'Trip.com', 0),
    (1, 'hostelworld', 'Hostelworld', 0)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- ---- Message templates ----------------------------------------------------
INSERT INTO message_templates (hotel_id, channel, event_key, subject, body) VALUES
    (1, 'whatsapp', 'booking_confirmation', NULL,
        'Hi {{guest_name}}, your booking {{code}} at {{hotel_name}} is confirmed for {{check_in}} to {{check_out}}. Total: {{total}}. See you soon!'),
    (1, 'whatsapp', 'checkin_reminder', NULL,
        'Hi {{guest_name}}, reminder: your check-in at {{hotel_name}} is on {{check_in}}. We look forward to hosting you.'),
    (1, 'whatsapp', 'checkout_reminder', NULL,
        'Hi {{guest_name}}, check-out is scheduled for {{check_out}}. Thank you for staying with {{hotel_name}}!'),
    (1, 'email', 'booking_confirmation', 'Booking Confirmed — {{code}}',
        '<p>Dear {{guest_name}},</p><p>Your booking <strong>{{code}}</strong> at {{hotel_name}} is confirmed.</p><p>Check-in: {{check_in}}<br>Check-out: {{check_out}}<br>Total: {{total}}</p><p>Thank you!</p>'),
    (1, 'email', 'invoice', 'Invoice {{number}} — {{hotel_name}}',
        '<p>Dear {{guest_name}},</p><p>Please find your invoice {{number}} attached. Amount due: {{balance}}.</p>')
ON DUPLICATE KEY UPDATE body = VALUES(body);

-- ---- Default settings groups ---------------------------------------------
INSERT INTO settings (hotel_id, group_key, key_name, value, is_secret) VALUES
    (1, 'smtp', 'host', '', 0),
    (1, 'smtp', 'port', '587', 0),
    (1, 'smtp', 'username', '', 0),
    (1, 'smtp', 'password', '', 1),
    (1, 'smtp', 'from_email', 'no-reply@grandpalace.test', 0),
    (1, 'smtp', 'from_name', 'Grand Palace Hotel', 0),
    (1, 'whatsapp', 'provider', 'akdwk', 0),
    (1, 'whatsapp', 'api_url', 'https://bulk.akdwk.in/api.php', 0),
    (1, 'whatsapp', 'session_id', '', 1),
    (1, 'whatsapp', 'api_key', '', 1),
    (1, 'payment', 'default_gateway', 'cash', 0),
    (1, 'payment', 'razorpay_key', '', 1),
    (1, 'payment', 'razorpay_secret', '', 1),
    (1, 'mikrotik', 'host', '', 0),
    (1, 'mikrotik', 'username', 'admin', 0),
    (1, 'mikrotik', 'password', '', 1),
    (1, 'mikrotik', 'default_profile', 'guest', 0)
ON DUPLICATE KEY UPDATE value = value;
