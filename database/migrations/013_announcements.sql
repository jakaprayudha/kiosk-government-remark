CREATE TABLE announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL CHECK(length(trim(title)) > 0),
    body TEXT NOT NULL CHECK(length(trim(body)) > 0),
    starts_at TEXT,
    ends_at TEXT,
    priority INTEGER NOT NULL DEFAULT 0 CHECK(priority BETWEEN 0 AND 9999),
    is_published INTEGER NOT NULL DEFAULT 0 CHECK(is_published IN (0,1)),
    version INTEGER NOT NULL DEFAULT 1,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK(starts_at IS NULL OR ends_at IS NULL OR ends_at >= starts_at)
);
CREATE INDEX announcements_public_idx ON announcements(is_published, priority, starts_at, ends_at);
