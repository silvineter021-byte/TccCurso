-- ==============================================================================
-- RODAX — BANCO DE DADOS OFICIAL (PROMPT V5)
-- Sistema de Marketplace de Veículos
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `rodax_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rodax_db`;

-- ------------------------------------------------------------------------------
-- 1. TABELA DE USUÁRIOS (users)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `vehicle_features`;
DROP TABLE IF EXISTS `vehicle_images`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `features`;
DROP TABLE IF EXISTS `vehicle_types`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `fipe_cache`;
DROP TABLE IF EXISTS `fipe_vehicle_details`;
DROP TABLE IF EXISTS `fipe_years`;
DROP TABLE IF EXISTS `fipe_models`;
DROP TABLE IF EXISTS `fipe_brands`;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `user_type` ENUM('particular', 'loja') NOT NULL DEFAULT 'particular',
  `company_name` VARCHAR(150) DEFAULT NULL,
  `cpf_cnpj` VARCHAR(20) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` CHAR(2) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `lockout_until` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_type` (`user_type`),
  INDEX `idx_users_admin` (`is_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABELA DE TIPOS DE VEÍCULOS (vehicle_types)
-- ------------------------------------------------------------------------------
CREATE TABLE `vehicle_types` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vehicle_types` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Carros', 'carros', 'car'),
(2, 'Motos', 'motos', 'motorcycle'),
(3, 'Caminhões', 'caminhoes', 'truck'),
(4, 'Vans', 'vans', 'shuttle-van'),
(5, 'Ônibus', 'onibus', 'bus'),
(6, 'Reboques e Implementos', 'implementos-rodoviarios', 'trailer');

-- ------------------------------------------------------------------------------
-- 3. TABELA DE VEÍCULOS / ANÚNCIOS (vehicles)
-- ------------------------------------------------------------------------------
CREATE TABLE `vehicles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `vehicle_type_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `version` VARCHAR(150) DEFAULT NULL,
  `year_manufacture` SMALLINT UNSIGNED NOT NULL,
  `year_model` SMALLINT UNSIGNED NOT NULL,
  `mileage` INT UNSIGNED NOT NULL DEFAULT 0,
  `price` DECIMAL(12, 2) NOT NULL,
  `fipe_code` VARCHAR(20) DEFAULT NULL,
  `fipe_reference_price` DECIMAL(12, 2) DEFAULT NULL,
  `color` VARCHAR(50) NOT NULL,
  `fuel_type` VARCHAR(50) NOT NULL,
  `engine` VARCHAR(50) DEFAULT NULL,
  `cylinders` TINYINT UNSIGNED DEFAULT NULL,
  `power_hp` INT UNSIGNED DEFAULT NULL,
  `torque_kgfm` DECIMAL(6,2) DEFAULT NULL,
  `transmission` VARCHAR(50) DEFAULT NULL,
  `steering` VARCHAR(50) DEFAULT NULL,
  `traction` VARCHAR(50) DEFAULT NULL,
  `seller_type` ENUM('particular', 'loja') NOT NULL DEFAULT 'particular',
  `state` CHAR(2) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active', 'paused', 'sold', 'deleted') NOT NULL DEFAULT 'active',
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  
  -- Campos Específicos por Categoria
  `body_style` VARCHAR(50) DEFAULT NULL,            -- Carros / Vans / Caminhões / Ônibus
  `doors_count` TINYINT UNSIGNED DEFAULT NULL,       -- Carros
  `seats_count` SMALLINT UNSIGNED DEFAULT NULL,      -- Carros / Vans / Ônibus
  `engine_capacity_cc` INT UNSIGNED DEFAULT NULL,    -- Motos
  `starter_type` VARCHAR(50) DEFAULT NULL,           -- Motos
  `motorcycle_category` VARCHAR(50) DEFAULT NULL,    -- Motos
  `brakes_type` VARCHAR(50) DEFAULT NULL,            -- Motos
  `has_abs` TINYINT(1) DEFAULT 0,                    -- Motos
  `axles_count` TINYINT UNSIGNED DEFAULT NULL,       -- Caminhões / Ônibus / Implementos
  `pbt_kg` INT UNSIGNED DEFAULT NULL,                -- Caminhões (PBT)
  `load_capacity_kg` INT UNSIGNED DEFAULT NULL,      -- Caminhões / Vans / Implementos
  `cabin_type` VARCHAR(50) DEFAULT NULL,             -- Caminhões
  `passenger_capacity` SMALLINT UNSIGNED DEFAULT NULL, -- Ônibus
  `implement_type` VARCHAR(50) DEFAULT NULL,         -- Implementos rodoviários
  `implement_length_m` DECIMAL(5,2) DEFAULT NULL,    -- Implementos rodoviários
  `implement_manufacturer` VARCHAR(100) DEFAULT NULL, -- Implementos rodoviários

  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicles_type` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE RESTRICT,
  INDEX `idx_vehicles_type` (`vehicle_type_id`),
  INDEX `idx_vehicles_status` (`status`),
  INDEX `idx_vehicles_brand_model` (`brand`, `model`),
  INDEX `idx_vehicles_price` (`price`),
  INDEX `idx_vehicles_year` (`year_model`),
  INDEX `idx_vehicles_location` (`state`, `city`),
  INDEX `idx_vehicles_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABELA DE IMAGENS DO VEÍCULO (vehicle_images)
-- ------------------------------------------------------------------------------
CREATE TABLE `vehicle_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_main` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_images_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  INDEX `idx_images_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. TABELA DE OPCIONAIS / RECURSOS (features)
-- ------------------------------------------------------------------------------
CREATE TABLE `features` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_type_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `category` VARCHAR(50) DEFAULT 'conforto',
  CONSTRAINT `fk_features_type` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opcionais iniciais
INSERT INTO `features` (`vehicle_type_id`, `name`, `slug`, `category`) VALUES
(1, 'Ar Condicionado', 'ar-condicionado', 'conforto'),
(1, 'Direção Hidráulica', 'direcao-hidraulica', 'conforto'),
(1, 'Freios ABS', 'freios-abs', 'seguranca'),
(1, 'Airbag Duplo', 'airbag-duplo', 'seguranca'),
(1, 'Vidros Elétricos', 'vidros-eletricos', 'conforto'),
(1, 'Central Multimídia', 'central-multimidia', 'tecnologia'),
(1, 'Bancos em Couro', 'bancos-em-couro', 'conforto'),
(1, 'Teto Solar', 'teto-solar', 'exterior'),
(2, 'Freios ABS (Moto)', 'freios-abs-moto', 'seguranca'),
(2, 'Painel Digital', 'painel-digital', 'tecnologia'),
(2, 'Partida Elétrica', 'partida-eletrica', 'conforto'),
(3, 'Piloto Automático', 'piloto-automatico-caminhao', 'conforto'),
(3, 'Cabine Leito', 'cabine-leito-caminhao', 'conforto'),
(3, 'Freio Motor', 'freio-motor', 'seguranca');

-- ------------------------------------------------------------------------------
-- 6. TABELA RELACIONAL DE OPCIONAIS DO ANÚNCIO (vehicle_features)
-- ------------------------------------------------------------------------------
CREATE TABLE `vehicle_features` (
  `vehicle_id` INT UNSIGNED NOT NULL,
  `feature_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`vehicle_id`, `feature_id`),
  CONSTRAINT `fk_vf_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vf_feature` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. TABELA DE FAVORITOS (favorites)
-- ------------------------------------------------------------------------------
CREATE TABLE `favorites` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_vehicle` (`user_id`, `vehicle_id`),
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorites_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. TABELA DE DENÚNCIAS (reports)
-- ------------------------------------------------------------------------------
CREATE TABLE `reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT UNSIGNED DEFAULT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
  `resolved_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reports_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reports_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_reports_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. TABELA DE MENSAGENS / CHAT (messages)
-- ------------------------------------------------------------------------------
CREATE TABLE `messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `message_text` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_messages_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_messages_conversation` (`vehicle_id`, `sender_id`, `receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. TABELA DE LOGS ADMINISTRATIVOS (admin_logs)
-- ------------------------------------------------------------------------------
CREATE TABLE `admin_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_admin_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. TABELAS DE CACHE E ESTRUTURA FIPE (fipe_cache, fipe_brands, etc.)
-- ------------------------------------------------------------------------------
CREATE TABLE `fipe_brands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_type_slug` VARCHAR(30) NOT NULL,
  `fipe_brand_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_type_brand` (`vehicle_type_slug`, `fipe_brand_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fipe_models` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_type_slug` VARCHAR(30) NOT NULL,
  `fipe_brand_code` VARCHAR(20) NOT NULL,
  `fipe_model_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_brand_model` (`vehicle_type_slug`, `fipe_brand_code`, `fipe_model_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fipe_years` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_type_slug` VARCHAR(30) NOT NULL,
  `fipe_brand_code` VARCHAR(20) NOT NULL,
  `fipe_model_code` VARCHAR(20) NOT NULL,
  `fipe_year_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_model_year` (`vehicle_type_slug`, `fipe_brand_code`, `fipe_model_code`, `fipe_year_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fipe_vehicle_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fipe_code` VARCHAR(20) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `model` VARCHAR(150) NOT NULL,
  `model_year` INT UNSIGNED NOT NULL,
  `fuel` VARCHAR(50) NOT NULL,
  `fipe_price` DECIMAL(12,2) NOT NULL,
  `reference_month` VARCHAR(50) DEFAULT NULL,
  `vehicle_type_slug` VARCHAR(30) NOT NULL,
  `raw_json` TEXT DEFAULT NULL,
  `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  INDEX `idx_fipe_details_lookup` (`fipe_code`, `model_year`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fipe_cache` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cache_key` VARCHAR(191) NOT NULL UNIQUE,
  `response_data` LONGTEXT NOT NULL,
  `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  INDEX `idx_cache_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
