-- ============================================================
--  Darkroom schema additions — run AFTER schema.sql
--  mysql -u cerrick_dbhub -p -h mysql.knotwork.ca knotworkdb < schema-additions.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Support Paper ─────────────────────────────────────────────
-- A specific prepared/sized sheet of base paper, identified
-- with a hand-written letter-number mark (e.g. "A1", "B3").

CREATE TABLE IF NOT EXISTS support_paper (
  id         INT          AUTO_INCREMENT PRIMARY KEY,
  paper_id   INT          NOT NULL COMMENT 'FK → paper (the base stock)',
  mark       VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'field mark, e.g. A1, B3',
  notes      TEXT,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (paper_id) REFERENCES paper(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Carbon Tissue ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS carbon_tissue (
  id            INT          AUTO_INCREMENT PRIMARY KEY,
  size          VARCHAR(100) COMMENT 'e.g. 8x10, 4x5',
  chemistry_id  INT          COMMENT 'FK → chemistry (gelatin/sensitizer used)',
  amount_poured VARCHAR(100) COMMENT 'e.g. 45ml',
  date_poured   DATE         NOT NULL,
  notes         TEXT,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Photo Layers ──────────────────────────────────────────────
-- Each layer of a photo print: one support paper + one negative
-- + one carbon tissue, in order. All FKs are nullable so partial
-- layer entries are allowed.

CREATE TABLE IF NOT EXISTS photo_layer (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  photo_id         INT NOT NULL,
  support_paper_id INT  COMMENT 'FK → support_paper',
  negative_id      INT  COMMENT 'FK → negative',
  carbon_tissue_id INT  COMMENT 'FK → carbon_tissue',
  sort_order       INT  NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id)         REFERENCES photo(id)         ON DELETE CASCADE,
  FOREIGN KEY (support_paper_id) REFERENCES support_paper(id) ON DELETE SET NULL,
  FOREIGN KEY (negative_id)      REFERENCES negative(id)      ON DELETE SET NULL,
  FOREIGN KEY (carbon_tissue_id) REFERENCES carbon_tissue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
