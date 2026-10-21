CREATE TABLE kiosk_settings (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    region_name TEXT NOT NULL DEFAULT '',
    kiosk_title TEXT NOT NULL DEFAULT 'KIOSK',
    tagline TEXT NOT NULL DEFAULT 'Sentuh menu di bawah untuk menemukan informasi yang Anda butuhkan.',
    footer_text TEXT NOT NULL DEFAULT 'Jaga kerahasiaan data pribadi Anda.',
    logo_data TEXT NOT NULL DEFAULT '',
    version INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO kiosk_settings (id) VALUES (1);
