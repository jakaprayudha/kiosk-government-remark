CREATE TABLE office_facilities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    office_id INTEGER NOT NULL REFERENCES office_profiles(id) ON DELETE CASCADE,
    slug TEXT NOT NULL,
    category TEXT NOT NULL CHECK(category IN ('health', 'sd', 'smp', 'sma', 'government', 'market', 'sport')),
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    address TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    opening_hours TEXT NOT NULL DEFAULT '',
    latitude REAL NOT NULL CHECK(latitude BETWEEN -90 AND 90),
    longitude REAL NOT NULL CHECK(longitude BETWEEN -180 AND 180),
    coordinate_note TEXT NOT NULL DEFAULT '',
    is_demo INTEGER NOT NULL DEFAULT 1 CHECK(is_demo IN (0, 1)),
    is_published INTEGER NOT NULL DEFAULT 0 CHECK(is_published IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(office_id, slug)
);
CREATE INDEX office_facilities_public_idx ON office_facilities(office_id, is_published, sort_order);
