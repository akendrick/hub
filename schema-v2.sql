-- ============================================================
--  Darkroom Process Journal v2 — knotworkdb
--  DROPS all existing tables and rebuilds from scratch.
--  Run: mysql -u cerrick_dbhub -p -h mysql.knotwork.ca knotworkdb < schema-v2.sql
-- ============================================================
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

DROP TABLE IF EXISTS photo_layer;
DROP TABLE IF EXISTS photo_times;
DROP TABLE IF EXISTS photo_sensitizer;
DROP TABLE IF EXISTS exposure_times;
DROP TABLE IF EXISTS photo;
DROP TABLE IF EXISTS exposure;
DROP TABLE IF EXISTS carbon_tissue;
DROP TABLE IF EXISTS support_paper;
DROP TABLE IF EXISTS chemistry_paper_use;
DROP TABLE IF EXISTS paper;
DROP TABLE IF EXISTS chemistry_lineage;
DROP TABLE IF EXISTS chemistry;
DROP TABLE IF EXISTS negative;
DROP TABLE IF EXISTS negative_types;
DROP TABLE IF EXISTS chemistry_types;

CREATE TABLE chemistry_types (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO chemistry_types (name) VALUES ('Gelatin'),('Sensitizer'),('Developer'),('Fixer'),('Stop Bath'),('Sizing'),('Coating');

CREATE TABLE negative_types (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO negative_types (name) VALUES ('Inkjet'),('Laser'),('Waxed Paper'),('Glass');

CREATE TABLE chemistry (
  id INT AUTO_INCREMENT PRIMARY KEY, date_created DATE NOT NULL,
  type_id INT, percent_solution DECIMAL(6,3), notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES chemistry_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chemistry_lineage (
  id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT NOT NULL, child_id INT NOT NULL,
  UNIQUE KEY uq_lineage (parent_id, child_id),
  FOREIGN KEY (parent_id) REFERENCES chemistry(id) ON DELETE CASCADE,
  FOREIGN KEY (child_id)  REFERENCES chemistry(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE paper (
  id INT AUTO_INCREMENT PRIMARY KEY, manufacturer VARCHAR(150), label VARCHAR(150),
  weight DECIMAL(7,2) COMMENT 'gsm', hot_press TINYINT(1) NOT NULL DEFAULT 0,
  treatment_chemistry_id INT, notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (treatment_chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE support_paper (
  id INT AUTO_INCREMENT PRIMARY KEY, paper_id INT NOT NULL,
  mark VARCHAR(20) NOT NULL DEFAULT '', notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (paper_id) REFERENCES paper(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- title_id: user label, shown in menus as "LABEL.YY.MM"
CREATE TABLE negative (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_id VARCHAR(100) DEFAULT NULL COMMENT 'menu display label',
  date_created DATE NOT NULL, type_id INT, settings_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES negative_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carbon_tissue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_id VARCHAR(100) DEFAULT NULL COMMENT 'menu display label',
  size VARCHAR(100), chemistry_id INT, amount_poured VARCHAR(100),
  date_poured DATE NOT NULL, notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (chemistry_id) REFERENCES chemistry(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Photo absorbs all exposure/development data
CREATE TABLE photo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  paper_id INT, photo_size VARCHAR(100),
  date_sensitized DATE, date_exposed DATE,
  test_strip TINYINT(1) NOT NULL DEFAULT 0,
  paper_soak_time INT COMMENT 'minutes', paper_soak_temp DECIMAL(5,1) COMMENT 'celsius',
  hot_develop_time INT COMMENT 'seconds', hot_develop_temp DECIMAL(5,1) COMMENT 'celsius',
  cool_develop_time INT COMMENT 'seconds', cool_develop_temp DECIMAL(5,1) COMMENT 'celsius',
  image_path VARCHAR(500),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (paper_id) REFERENCES paper(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE photo_times (
  id INT AUTO_INCREMENT PRIMARY KEY, photo_id INT NOT NULL,
  duration_minutes DECIMAL(6,2) NOT NULL, sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id) REFERENCES photo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE photo_layer (
  id INT AUTO_INCREMENT PRIMARY KEY, photo_id INT NOT NULL,
  support_paper_id INT, negative_id INT, carbon_tissue_id INT,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (photo_id)         REFERENCES photo(id)         ON DELETE CASCADE,
  FOREIGN KEY (support_paper_id) REFERENCES support_paper(id) ON DELETE SET NULL,
  FOREIGN KEY (negative_id)      REFERENCES negative(id)      ON DELETE SET NULL,
  FOREIGN KEY (carbon_tissue_id) REFERENCES carbon_tissue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
