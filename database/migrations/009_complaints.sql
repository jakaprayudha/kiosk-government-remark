CREATE TABLE complaints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_code TEXT NOT NULL UNIQUE,
    pin_hash TEXT NOT NULL,
    request_key TEXT NOT NULL UNIQUE,
    kind TEXT NOT NULL CHECK (kind IN ('complaint', 'aspiration')),
    category TEXT NOT NULL CHECK (category IN ('service', 'infrastructure', 'environment', 'social', 'other')),
    title TEXT NOT NULL,
    location TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    contact TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'reviewing', 'in_progress', 'completed', 'rejected')),
    version INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT NOT NULL
);
CREATE INDEX complaints_status_idx ON complaints(status, id);
CREATE INDEX complaints_expiry_idx ON complaints(expires_at);

CREATE TABLE complaint_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_id INTEGER NOT NULL REFERENCES complaints(id) ON DELETE CASCADE,
    new_status TEXT NOT NULL,
    public_message TEXT NOT NULL DEFAULT '',
    internal_note TEXT NOT NULL DEFAULT '',
    changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX complaint_events_history_idx ON complaint_events(complaint_id, id);
