-- Scheme of Work Database
CREATE DATABASE IF NOT EXISTS scheme_of_work CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE scheme_of_work;

-- ── Grades ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grades (
    id               TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level_group      VARCHAR(60)  NOT NULL,          -- e.g. "Primary — Lower"
    name             VARCHAR(50)  NOT NULL,           -- e.g. "Grade 1"
    lesson_duration  SMALLINT UNSIGNED DEFAULT 35,   -- minutes per lesson
    sort_order       TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed grades (CBC Kenya)
INSERT IGNORE INTO grades (id, level_group, name, lesson_duration, sort_order) VALUES
(1,  'Pre-Primary',       'PP 1',    30, 1),
(2,  'Pre-Primary',       'PP 2',    30, 2),
(3,  'Primary — Lower',   'Grade 1', 35, 3),
(4,  'Primary — Lower',   'Grade 2', 35, 4),
(5,  'Primary — Lower',   'Grade 3', 35, 5),
(6,  'Primary — Upper',   'Grade 4', 35, 6),
(7,  'Primary — Upper',   'Grade 5', 35, 7),
(8,  'Primary — Upper',   'Grade 6', 35, 8),
(9,  'Junior Secondary',  'Grade 7', 40, 9),
(10, 'Junior Secondary',  'Grade 8', 40, 10),
(11, 'Junior Secondary',  'Grade 9', 40, 11);

-- ── Learning Areas ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS learning_areas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grade_id         TINYINT UNSIGNED NOT NULL,
    name             VARCHAR(255) NOT NULL,
    short_code       VARCHAR(20)  DEFAULT NULL,
    lessons_per_week TINYINT UNSIGNED NOT NULL DEFAULT 5,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_grade (grade_id),
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Scheme of Work ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS scheme_of_work (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learning_area_id INT UNSIGNED DEFAULT NULL,
    week             TINYINT UNSIGNED NOT NULL,
    lesson           TINYINT UNSIGNED NOT NULL,
    strand           VARCHAR(255) NOT NULL,
    sub_strand       VARCHAR(255) NOT NULL,
    slo_cd           TEXT,
    slo_sow          TEXT,
    le_cd            TEXT,
    le_sow           TEXT,
    key_inquiry      TEXT,
    resources        TEXT,
    assessment       TEXT,
    remarks          TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_week_lesson (week, lesson),
    INDEX idx_learning_area (learning_area_id),
    FOREIGN KEY (learning_area_id) REFERENCES learning_areas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
