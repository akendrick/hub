-- ============================================================
--  Darkroom Process Journal  —  schema v3  (MySQL 8)
--  Drops everything and rebuilds clean.
--
--  mysql -u cerrick_dbhub -p -h mysql.knotwork.ca knotworkdb < schema-v3.sql
-- ============================================================
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Drop all tables (children first) ─────────────────────────

DROP TABLE IF EXISTS photo_finishing;
DROP TABLE IF EXISTS photo_layer;
DROP TABLE IF EXISTS photo_times;
DROP TABLE IF EXISTS photo;
DROP TABLE IF EXISTS photo_types;
DROP TABLE IF EXISTS carbon_tissue;
DROP TABLE IF EXISTS support_paper;
DROP TABLE IF EXISTS paper;
DROP TABLE IF EXISTS chemistry_lineage;
DROP TABLE IF EXISTS chemistry;
DROP TABLE IF EXISTS negative;
DROP TABLE IF EXISTS negative_types;
DROP TABLE IF EXISTS chemistry_types;

-- Also drop any old tables from pre-v2 if they exist
DROP TABLE IF EXISTS photo_sensitizer;
DROP TABLE IF EXISTS exposure_times;
DROP TABLE IF EXISTS exposure;
DROP TABLE IF EXISTS chemistry_paper_use;

-- ── Lookup tables ─────────────────────────────────────────────

CREATE TABLE chemistry_types (
  id   INT          AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO chemistry_types (name) VALUES
  ('Gelatin'), ('Sensitizer'), ('Developer'),
  ('Fixer'), ('Stop Bath'), ('Sizing'), ('Coating');

CREATE TABLE negative_types (
  id   INT          AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO negative_types (name) VALUES
  ('Inkjet'), ('Laser'), ('Waxed Paper'), ('Glass');

-- ── Photo Types ───────────────────────────────────────────────
-- Controls which form sections are shown for a given process.
--   has_layers  TRUE  → show the Layers section
--   dev_mode    carbon  → paper soak + hot develop + cool develop
--               simple  → single time / temp / notes field

CREATE TABLE photo_types (
  id         INT          AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL UNIQUE,
  has_layers BOOLEAN      NOT NULL DEFAULT FALSE,
  dev_mode   VARCHAR(20)  NOT NULL DEFAULT 'simple'
             COMMENT 'carbon | simple'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO photo_types (name, has_layers, dev_mode) VALUES
  ('Carbon Transfer', TRUE,  'carbon'),
  ('Cyanotype',       FALSE, 'simple'),
  ('Ambrotype',       FALSE, 'simple'),
  ('Tintype',         FALSE, 'simple'),
  ('Salt Print',      FALSE, 'simple'),
  ('Albumen',         FALSE, 'simple');

-- ── Chemistry ─────────────────────────────────────────────────

CREATE TABLE chemistry (
  id               INT           AUTO_INCREMENT PRIMARY KEY,
  date_created     DATE          NOT NULL,
  type_id          INT           NULL,
  percent_solution DECIMAL(6,3)  NULL,
  notes            TEXT          NULL,
  created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES chemistry_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chemistry_lineage (
  id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NOT NULL,
  child_id  INT NOT NULL,
  UNIQUE KEY uq_lineage (parent_id, child_id),
  FOREIGN KEY (parent_id) REFERENCES chemistry(id) ON DELETE CASCADE,
  FOREIGN KEY (child_id)  REFERENCES chemistry(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Paper ─────────────────────────────────────────────────────

CREATE TABLE paper (
  id                     INT          AUTO_INCREMENT PRIMARY KEY,
  manufacturer           VARCHAR(150) NULL,
  label                  VARCHAR(150) NULL,
  weight                 DECIMAL(7,2) NULL COMMENT 'gsm',
  hot_press              BOOLEAN      NOT NULL DEFAULT FALSE,
  notes                  TEXT         NULL,
  created_at             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE support_paper (
  id                     INT          AUTO_INCREMENT PRIMARY KEY,
  paper_id               INT          NOT NULL,
  mark                   VARCHAR(20)  NOT NULL DEFAULT '',
  treatment_chemistry_id INT          NULL,
  notes                  TEXT         NULL,
  created_at             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (paper_id)               REFERENCES paper(id)     ON DELETE CASCADE,
  FOREIGN KEY (treatment_chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Negative ──────────────────────────────────────────────────

CREATE TABLE negative (
  id             INT          AUTO_INCREMENT PRIMARY KEY,
  title_id       VARCHAR(100) NULL COMMENT 'menu label — shown as TITLE.YY.MM',
  date_created   DATE         NOT NULL,
  type_id        INT          NULL,
  settings_notes TEXT         NULL,
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES negative_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Carbon Tissue ─────────────────────────────────────────────

CREATE TABLE carbon_tissue (
  id            INT          AUTO_INCREMENT PRIMARY KEY,
  title_id      VARCHAR(100) NULL COMMENT 'menu label — shown as TITLE.YY.MM',
  size          VARCHAR(100) NULL,
  chemistry_id  INT          NULL,
  amount_poured VARCHAR(100) NULL,
  date_poured   DATE         NOT NULL,
  notes         TEXT         NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Photo ─────────────────────────────────────────────────────
-- All exposure and development data lives here (no separate exposure table).
--
-- Exposure:
--   test_strip        — toggle that enables time intervals
--   exposure_duration — primary/single duration in minutes
--   photo_times       — child table of intervals (used when test_strip is on)
--
-- Development — carbon mode (dev_mode = 'carbon'):
--   paper_soak_time / paper_soak_temp
--   hot_develop_time  / hot_develop_temp
--   cool_develop_time / cool_develop_temp
--
-- Development — simple mode (dev_mode = 'simple'):
--   develop_time / develop_temp / develop_notes

CREATE TABLE photo (
  id                INT          AUTO_INCREMENT PRIMARY KEY,
  title             VARCHAR(255) NULL,
  photo_type_id     INT          NULL,
  paper_id          INT          NULL,
  photo_size        VARCHAR(100) NULL,
  date_sensitized   DATE         NULL,
  date_exposed      DATE         NULL,
  -- Exposure
  test_strip        BOOLEAN      NOT NULL DEFAULT FALSE,
  exposure_duration DECIMAL(6,2) NULL COMMENT 'primary exposure in minutes',
  -- Development: carbon mode
  paper_soak_time   INT          NULL COMMENT 'seconds',
  paper_soak_temp   DECIMAL(5,1) NULL COMMENT 'celsius',
  hot_develop_time  INT          NULL COMMENT 'seconds',
  hot_develop_temp  DECIMAL(5,1) NULL COMMENT 'celsius',
  cool_develop_time INT          NULL COMMENT 'seconds',
  cool_develop_temp DECIMAL(5,1) NULL COMMENT 'celsius',
  -- Development: simple mode
  develop_time      INT          NULL COMMENT 'seconds',
  develop_temp      DECIMAL(5,1) NULL COMMENT 'celsius',
  develop_notes     TEXT         NULL,
  -- Image & notes
  image_path        VARCHAR(500) NULL,
  notes             TEXT         NULL,
  created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (photo_type_id) REFERENCES photo_types(id) ON DELETE SET NULL,
  FOREIGN KEY (paper_id)      REFERENCES paper(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Photo Times (exposure intervals for test strips) ──────────

CREATE TABLE photo_times (
  id               INT          AUTO_INCREMENT PRIMARY KEY,
  photo_id         INT          NOT NULL,
  duration_minutes DECIMAL(6,2) NOT NULL,
  sort_order       INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id) REFERENCES photo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Photo Layers (carbon transfer only) ───────────────────────

CREATE TABLE photo_layer (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  photo_id         INT NOT NULL,
  support_paper_id INT NULL,
  negative_id      INT NULL,
  carbon_tissue_id INT NULL,
  sort_order       INT NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id)         REFERENCES photo(id)         ON DELETE CASCADE,
  FOREIGN KEY (support_paper_id) REFERENCES support_paper(id) ON DELETE SET NULL,
  FOREIGN KEY (negative_id)      REFERENCES negative(id)      ON DELETE SET NULL,
  FOREIGN KEY (carbon_tissue_id) REFERENCES carbon_tissue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Photo Finishing ───────────────────────────────────────────
-- One or more finishing steps per photo (clearing bath, fixative, toner, etc.)
-- chemistry_id is optional — can use free-text label alone.

CREATE TABLE photo_finishing (
  id           INT          AUTO_INCREMENT PRIMARY KEY,
  photo_id     INT          NOT NULL,
  label        VARCHAR(150) NOT NULL DEFAULT '',
  chemistry_id INT          NULL,
  step_time    INT          NULL COMMENT 'seconds',
  step_temp    DECIMAL(5,1) NULL COMMENT 'celsius',
  notes        TEXT         NULL,
  sort_order   INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id)     REFERENCES photo(id)     ON DELETE CASCADE,
  FOREIGN KEY (chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
