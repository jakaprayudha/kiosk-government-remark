CREATE TABLE office_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    regency TEXT NOT NULL,
    province TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    address TEXT NOT NULL DEFAULT '',
    opening_hours TEXT NOT NULL DEFAULT '',
    contact_note TEXT NOT NULL DEFAULT '',
    latitude REAL NOT NULL CHECK (latitude BETWEEN -90 AND 90),
    longitude REAL NOT NULL CHECK (longitude BETWEEN -180 AND 180),
    source_url TEXT NOT NULL DEFAULT '',
    is_demo INTEGER NOT NULL DEFAULT 1 CHECK (is_demo IN (0, 1)),
    is_published INTEGER NOT NULL DEFAULT 0 CHECK (is_published IN (0, 1)),
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE office_villages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    office_id INTEGER NOT NULL REFERENCES office_profiles (id) ON DELETE CASCADE,
    slug TEXT NOT NULL,
    name TEXT NOT NULL,
    head_name TEXT NOT NULL DEFAULT '',
    population INTEGER NOT NULL CHECK (population BETWEEN 0 AND 100000000),
    population_year INTEGER NOT NULL CHECK (population_year BETWEEN 1900 AND 2200),
    latitude REAL NOT NULL CHECK (latitude BETWEEN -90 AND 90),
    longitude REAL NOT NULL CHECK (longitude BETWEEN -180 AND 180),
    coordinate_note TEXT NOT NULL DEFAULT '',
    is_demo INTEGER NOT NULL DEFAULT 1 CHECK (is_demo IN (0, 1)),
    is_published INTEGER NOT NULL DEFAULT 0 CHECK (is_published IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (office_id, slug)
);
CREATE INDEX office_villages_public_idx ON office_villages (office_id, is_published, sort_order);
