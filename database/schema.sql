-- =============================================================
--  Generator Fuel Tracker - MySQL schema
--  Import this file via phpMyAdmin after creating the database.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
--  Users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            CHAR(36)        NOT NULL,
    `name`          VARCHAR(150)    NOT NULL,
    `email`         VARCHAR(190)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `role`          ENUM('ADMIN','OPERATOR') NOT NULL DEFAULT 'OPERATOR',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Generators
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `generators` (
    `id`         CHAR(36)      NOT NULL,
    `code`       VARCHAR(100)  NOT NULL,
    `location`   VARCHAR(255)  NOT NULL,
    `image_url`  VARCHAR(255)  DEFAULT NULL,
    `notes`      TEXT          DEFAULT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `generators_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Fuel logs
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fuel_logs` (
    `id`                   CHAR(36)      NOT NULL,
    `generator_id`         CHAR(36)      NOT NULL,
    `date`                 DATE          NOT NULL,
    `time`                 VARCHAR(5)    NOT NULL,
    `hour_reading`         DOUBLE        NOT NULL,
    `liters`               DOUBLE        NOT NULL,
    `price_per_liter`      DOUBLE        NOT NULL,
    `fuel_type`            ENUM('PETROL','DIESEL','KEROSENE') NOT NULL,
    `is_tank_full`         TINYINT(1)    NOT NULL DEFAULT 0,
    `hours_worked`         DOUBLE        DEFAULT NULL,
    `consumption_per_hour` DOUBLE        DEFAULT NULL,
    `cost`                 DOUBLE        NOT NULL,
    `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fuel_logs_generator_hour_idx` (`generator_id`, `hour_reading`),
    CONSTRAINT `fuel_logs_generator_fk`
        FOREIGN KEY (`generator_id`) REFERENCES `generators` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
--  Default administrator
--  Email:    admin@example.com
--  Password: admin123   (CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN)
-- -------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`)
VALUES (
    '00000000-0000-0000-0000-000000000001',
    'System Administrator',
    'admin@example.com',
    '$2b$10$vY8CPXMxjlSB5XoZn694fOjx6BDx4DMt8v7jsXFMQYM3iUil7lYFO',
    'ADMIN'
)
ON DUPLICATE KEY UPDATE `email` = `email`;
