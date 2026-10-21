CREATE TABLE queue_agencies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    kind TEXT NOT NULL CHECK(kind IN ('dukcapil','health','hospital','government')),
    description TEXT NOT NULL DEFAULT '',
    address TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1))
);
CREATE TABLE queue_services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    agency_id INTEGER NOT NULL REFERENCES queue_agencies(id),
    name TEXT NOT NULL,
    instructions TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    UNIQUE(agency_id,name)
);
CREATE TABLE queue_slots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL REFERENCES queue_services(id),
    service_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    capacity INTEGER NOT NULL CHECK(capacity BETWEEN 1 AND 500),
    occupied INTEGER NOT NULL DEFAULT 0 CHECK(occupied>=0 AND occupied<=capacity),
    last_number INTEGER NOT NULL DEFAULT 0,
    is_open INTEGER NOT NULL DEFAULT 1 CHECK(is_open IN (0,1)),
    version INTEGER NOT NULL DEFAULT 1,
    UNIQUE(service_id,service_date,start_time)
);
CREATE INDEX queue_slots_date_idx ON queue_slots(service_date);
CREATE TABLE queue_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slot_id INTEGER NOT NULL REFERENCES queue_slots(id),
    request_key TEXT NOT NULL UNIQUE,
    public_code TEXT NOT NULL UNIQUE,
    pin_hash TEXT NOT NULL,
    number INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'waiting' CHECK(status IN ('waiting','called','completed','cancelled','missed')),
    version INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT NOT NULL,
    UNIQUE(slot_id,number)
);
CREATE UNIQUE INDEX queue_one_called_idx ON queue_bookings(slot_id) WHERE status='called';
CREATE INDEX queue_bookings_expiry_idx ON queue_bookings(expires_at);
CREATE TABLE queue_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL REFERENCES queue_bookings(id) ON DELETE CASCADE,
    status TEXT NOT NULL,
    changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
