-- Migration 0002 — OTA web-automation support.
-- Adds connection mode + automation config to ota_channels so channels without
-- an official API (e.g. MakeMyTrip, Goibibo) can be driven via cURL session or
-- a real headless browser. Safe to run once on an existing install.

ALTER TABLE ota_channels
    ADD COLUMN connection_mode ENUM('api','web_session','browser') NOT NULL DEFAULT 'api' AFTER credentials,
    ADD COLUMN automation_config TEXT NULL AFTER connection_mode;
