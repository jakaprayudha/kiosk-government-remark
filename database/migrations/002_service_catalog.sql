CREATE TABLE service_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL CHECK (length(trim(name)) > 0),
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    icon TEXT NOT NULL DEFAULT 'document',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL REFERENCES service_categories (id) ON DELETE RESTRICT,
    name TEXT NOT NULL CHECK (length(trim(name)) > 0),
    slug TEXT NOT NULL UNIQUE,
    summary TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    eligibility TEXT NOT NULL DEFAULT '',
    fee_text TEXT NOT NULL DEFAULT '',
    estimated_time TEXT NOT NULL DEFAULT '',
    office_unit TEXT NOT NULL DEFAULT '',
    service_location TEXT NOT NULL DEFAULT '',
    official_url TEXT NOT NULL DEFAULT '',
    is_published INTEGER NOT NULL DEFAULT 0 CHECK (is_published IN (0, 1)),
    valid_from TEXT CHECK (valid_from IS NULL OR (length(valid_from) = 10 AND date(valid_from, '+0 days') IS NOT NULL AND date(valid_from, '+0 days') = valid_from)),
    valid_until TEXT CHECK (valid_until IS NULL OR (length(valid_until) = 10 AND date(valid_until, '+0 days') IS NOT NULL AND date(valid_until, '+0 days') = valid_until)),
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (valid_from IS NULL OR valid_until IS NULL OR valid_until >= valid_from)
);

CREATE TABLE service_requirements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
    name TEXT NOT NULL CHECK (length(trim(name)) > 0),
    description TEXT NOT NULL DEFAULT '',
    is_required INTEGER NOT NULL DEFAULT 1 CHECK (is_required IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX service_categories_public_idx ON service_categories (is_active, sort_order, name);
CREATE INDEX services_public_idx ON services (is_published, category_id, valid_from, valid_until, sort_order);
CREATE INDEX service_requirements_order_idx ON service_requirements (service_id, sort_order, id);
