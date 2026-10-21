ALTER TABLE kiosk_devices ADD COLUMN last_seen_at TEXT;
ALTER TABLE kiosk_devices ADD COLUMN page_views INTEGER NOT NULL DEFAULT 0 CHECK(page_views >= 0);
