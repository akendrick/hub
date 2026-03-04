-- ============================================================
--  Darkroom Process Journal — knotworkdb
--  Knotwork · Scientific Photography Records
--
--  Run once against knotworkdb to create all tables.
--  mysql -u cerrick_dbhub -p -h mysql.knotwork.ca knotworkdb < schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Lookup / dropdown tables ─────────────────────────────────

CREATE TABLE IF NOT EXISTS chemistry_types (
  id   INT          AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO chemistry_types (name) VALUES
  ('Gelatin'),
  ('Sensitizer'),
  ('Developer'),
  ('Fixer'),
  ('Stop Bath'),
  ('Sizing'),
  ('Coating');

CREATE TABLE IF NOT EXISTS negative_types (
  id   INT          AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO negative_types (name) VALUES
  ('Inkjet'),
  ('Laser'),
  ('Waxed Paper'),
  ('Glass');

-- ── Chemistry ─────────────────────────────────────────────────
--  Core chemical solutions — can be linked to the chemistry
--  they were derived from (lineage) and to papers they treated.

CREATE TABLE IF NOT EXISTS chemistry (
  id               INT            AUTO_INCREMENT PRIMARY KEY,
  date_created     DATE           NOT NULL,
  type_id          INT,
  percent_solution DECIMAL(6,3),
  notes            TEXT,
  created_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES chemistry_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Parent → Child relationship (e.g. stock solution → working solution)
CREATE TABLE IF NOT EXISTS chemistry_lineage (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NOT NULL  COMMENT 'chemistry that was used to create child',
  child_id  INT NOT NULL  COMMENT 'chemistry that was created',
  UNIQUE KEY uq_lineage (parent_id, child_id),
  FOREIGN KEY (parent_id) REFERENCES chemistry(id) ON DELETE CASCADE,
  FOREIGN KEY (child_id)  REFERENCES chemistry(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Paper ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS paper (
  id                     INT            AUTO_INCREMENT PRIMARY KEY,
  manufacturer           VARCHAR(150),
  label                  VARCHAR(150),
  weight                 DECIMAL(7,2)   COMMENT 'gsm',
  hot_press              TINYINT(1)     NOT NULL DEFAULT 0,
  treatment_chemistry_id INT            COMMENT 'FK → chemistry used to treat/size this paper',
  notes                  TEXT,
  created_at             TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (treatment_chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chemistry applied to paper (many-to-many, additional to treatment FK above)
CREATE TABLE IF NOT EXISTS chemistry_paper_use (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  chemistry_id INT NOT NULL,
  paper_id     INT NOT NULL,
  UNIQUE KEY uq_chem_paper (chemistry_id, paper_id),
  FOREIGN KEY (chemistry_id) REFERENCES chemistry(id) ON DELETE CASCADE,
  FOREIGN KEY (paper_id)     REFERENCES paper(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Negatives ─────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS negative (
  id             INT       AUTO_INCREMENT PRIMARY KEY,
  date_created   DATE      NOT NULL,
  type_id        INT,
  settings_notes TEXT      COMMENT 'print settings, profile, etc.',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES negative_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Exposures ─────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS exposure (
  id                INT          AUTO_INCREMENT PRIMARY KEY,
  date_exposed      DATE         NOT NULL,
  test_strip        TINYINT(1)   NOT NULL DEFAULT 0,
  paper_soak_time   INT          COMMENT 'minutes',
  paper_soak_temp   DECIMAL(5,1) COMMENT 'celsius',
  hot_develop_time  INT          COMMENT 'seconds',
  hot_develop_temp  DECIMAL(5,1) COMMENT 'celsius',
  cool_develop_time INT          COMMENT 'seconds',
  cool_develop_temp DECIMAL(5,1) COMMENT 'celsius',
  negative_id       INT,
  notes             TEXT,
  created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (negative_id) REFERENCES negative(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Multiple exposure durations per exposure (e.g. 10min, 10min, 15min)
CREATE TABLE IF NOT EXISTS exposure_times (
  id               INT          AUTO_INCREMENT PRIMARY KEY,
  exposure_id      INT          NOT NULL,
  duration_minutes DECIMAL(6,2) NOT NULL,
  sort_order       INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (exposure_id) REFERENCES exposure(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Photos ────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS photo (
  id                   INT          AUTO_INCREMENT PRIMARY KEY,
  gelatin_chemistry_id INT          COMMENT 'FK → chemistry used as gelatin layer',
  paper_id             INT,
  amount_used          VARCHAR(100) COMMENT 'gelatin amount',
  photo_size           VARCHAR(100) COMMENT 'e.g. 8x10, 4x5',
  date_sensitized      DATE,
  date_exposed         DATE,
  exposure_id          INT,
  notes                TEXT,
  created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (gelatin_chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL,
  FOREIGN KEY (paper_id)             REFERENCES paper(id)     ON DELETE SET NULL,
  FOREIGN KEY (exposure_id)          REFERENCES exposure(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Multiple sensitizer layers per photo (chemistry + amount, ordered)
CREATE TABLE IF NOT EXISTS photo_sensitizer (
  id           INT          AUTO_INCREMENT PRIMARY KEY,
  photo_id     INT          NOT NULL,
  chemistry_id INT          NOT NULL,
  amount_used  VARCHAR(100),
  sort_order   INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id)     REFERENCES photo(id)     ON DELETE CASCADE,
  FOREIGN KEY (chemistry_id) REFERENCES chemistry(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
