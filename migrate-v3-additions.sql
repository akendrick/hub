-- Migration: non-destructive additions to existing v3 database
-- Safe to run on live data — existing rows get NULL for new columns

-- 1. Add label to chemistry (for named chemistry batches e.g. "IndiaInkSample")
ALTER TABLE chemistry ADD COLUMN IF NOT EXISTS label VARCHAR(255) NULL DEFAULT NULL AFTER id;

-- 2. Add thumb_path to photo (400×400 square JPEG generated on upload)
ALTER TABLE photo ADD COLUMN IF NOT EXISTS thumb_path VARCHAR(500) NULL DEFAULT NULL AFTER image_path;

-- 3. Create finishing_types lookup table if not already done
CREATE TABLE IF NOT EXISTS finishing_types (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO finishing_types (name) VALUES
  ('Clearing Bath'), ('Fixative'), ('Toner'), ('Bleach'),
  ('Stop Bath'), ('Wash'), ('Hypo Clear'), ('Selenium');
