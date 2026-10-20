CREATE TABLE document_guides (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    region TEXT NOT NULL DEFAULT 'Kabupaten Serdang Bedagai',
    requirements_text TEXT NOT NULL,
    fee_amount INTEGER CHECK (fee_amount IS NULL OR fee_amount BETWEEN 0 AND 1000000000),
    fee_note TEXT NOT NULL DEFAULT '',
    legal_basis TEXT NOT NULL DEFAULT '',
    source_url TEXT NOT NULL DEFAULT '',
    fee_verified INTEGER NOT NULL DEFAULT 0 CHECK (fee_verified IN (0, 1)),
    is_demo INTEGER NOT NULL DEFAULT 1 CHECK (is_demo IN (0, 1)),
    is_published INTEGER NOT NULL DEFAULT 0 CHECK (is_published IN (0, 1)),
    sort_order INTEGER NOT NULL DEFAULT 0 CHECK (sort_order BETWEEN 0 AND 9999),
    version INTEGER NOT NULL DEFAULT 1,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (is_demo = 0 OR fee_verified = 0),
    CHECK (is_published = 0 OR is_demo = 1 OR
        (fee_verified = 1 AND fee_amount IS NOT NULL AND length(trim(legal_basis)) > 0 AND length(trim(source_url)) > 0))
);
