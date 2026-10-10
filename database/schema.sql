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
--  Password: ChangeMe!2026
--
--  SECURITY: This is a throwaway bootstrap credential. Log in once,
--  immediately change the password via Users > Edit, and never expose
--  this account to the public internet. The hash below is generated
--  with bcrypt cost 10 and is safe to commit (it is public knowledge).
-- -------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`)
VALUES (
    '00000000-0000-0000-0000-000000000001',
    'System Administrator',
    'admin@example.com',
    '$2y$10$KGiDb7CT7a9OvOZ7iivQd.4W9gOpu5d2GYHqaW.kZoJ7URRDCi4US',
    'ADMIN'
)
ON DUPLICATE KEY UPDATE `email` = `email`;

-- -------------------------------------------------------------
--  Remember-me tokens (used by the "Remember me for 30 days"
--  login option). Only the SHA-256 hash of each token is stored.
--  The app also creates this table automatically on first use.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `token_hash` VARCHAR(64)     NOT NULL,
    `user_id`    CHAR(36)        NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME        NOT NULL,
    PRIMARY KEY (`token_hash`),
    KEY `remember_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
