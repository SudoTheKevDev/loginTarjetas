-- Base de datos del proyecto (importar en phpMyAdmin o con: mysql -u root < config/sp2_2.sql)
CREATE DATABASE IF NOT EXISTS `sp2_2` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sp2_2`;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_completo`   VARCHAR(150) NOT NULL,
  `fecha_nacimiento`  DATE NULL,
  `nivel_educativo`   VARCHAR(30) NULL,
  `telefono`          VARCHAR(20) NULL,
  `horas_programando` DECIMAL(4,1) NULL,
  `email`             VARCHAR(180) NOT NULL,
  `nickname`          VARCHAR(50)  NOT NULL,
  `password_hash`     VARCHAR(255) NOT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_nickname` (`nickname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
