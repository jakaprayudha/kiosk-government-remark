ALTER TABLE services ADD COLUMN accepts_submissions INTEGER NOT NULL DEFAULT 0 CHECK (accepts_submissions IN (0, 1));
ALTER TABLE services ADD COLUMN requires_name INTEGER NOT NULL DEFAULT 0 CHECK (requires_name IN (0, 1));
ALTER TABLE services ADD COLUMN requires_contact INTEGER NOT NULL DEFAULT 0 CHECK (requires_contact IN (0, 1));
ALTER TABLE services ADD COLUMN camera_enabled INTEGER NOT NULL DEFAULT 0 CHECK (camera_enabled IN (0, 1));
ALTER TABLE services ADD COLUMN retention_days INTEGER NOT NULL DEFAULT 30 CHECK (retention_days BETWEEN 1 AND 365);
ALTER TABLE services ADD COLUMN is_demo INTEGER NOT NULL DEFAULT 0 CHECK (is_demo IN (0, 1));

UPDATE services SET accepts_submissions = 1, camera_enabled = 1, is_demo = 1
WHERE slug IN ('demo-legalisasi-dokumen', 'demo-rekomendasi-kegiatan', 'demo-konsultasi-administrasi',
               'demo-konsultasi-kanal-instansi', 'demo-permintaan-informasi', 'demo-informasi-fasilitas')
  AND name LIKE 'DEMO - %';

CREATE TABLE submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_code TEXT NOT NULL UNIQUE,
    pin_hash TEXT NOT NULL,
    request_key TEXT NOT NULL UNIQUE,
    service_id INTEGER NOT NULL REFERENCES services (id) ON DELETE RESTRICT,
    service_name TEXT NOT NULL,
    is_demo INTEGER NOT NULL CHECK (is_demo IN (0, 1)),
    name TEXT NOT NULL DEFAULT '',
    contact TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new', 'reviewing', 'needs_info', 'in_progress', 'completed', 'rejected')),
    public_note TEXT NOT NULL DEFAULT '',
    internal_note TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at TEXT,
    expires_at TEXT NOT NULL
);
CREATE INDEX submissions_status_created_idx ON submissions (status, created_at);
CREATE INDEX submissions_expiry_idx ON submissions (expires_at);

CREATE TABLE submission_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL REFERENCES submissions (id) ON DELETE CASCADE,
    old_status TEXT,
    new_status TEXT NOT NULL,
    public_message TEXT NOT NULL DEFAULT '',
    internal_note TEXT NOT NULL DEFAULT '',
    changed_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX submission_events_history_idx ON submission_events (submission_id, id);

CREATE TABLE submission_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_id INTEGER NOT NULL REFERENCES submissions (id) ON DELETE CASCADE,
    requirement_id INTEGER REFERENCES service_requirements (id) ON DELETE SET NULL,
    document_name TEXT NOT NULL,
    stored_name TEXT NOT NULL UNIQUE,
    mime_type TEXT NOT NULL CHECK (mime_type = 'image/jpeg'),
    size_bytes INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX submission_attachments_parent_idx ON submission_attachments (submission_id);

CREATE TABLE public_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identity_hash TEXT NOT NULL,
    action TEXT NOT NULL CHECK (action IN ('create', 'lookup')),
    attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX public_attempts_lookup_idx ON public_attempts (action, identity_hash, attempted_at);
