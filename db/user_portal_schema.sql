-- Additive schema for the EcoTech user/vendor portal.
-- Run this once after importing ECOTECH_KTOR_SCHEMA.sql.
USE `ecotech`;

ALTER TABLE `Equipos`
  ADD COLUMN `publicado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `usuario_id`,
  ADD KEY `idx_equipos_publicado` (`publicado`, `estado_actual`);

CREATE TABLE `Conversaciones` (
  `conversacion_id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `interlocutor_id` INT NOT NULL,
  `tipo` VARCHAR(20) NOT NULL,
  `equipo_id` INT NULL,
  `equipo_contexto` INT GENERATED ALWAYS AS (COALESCE(`equipo_id`, 0)) STORED,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversacion_id`),
  UNIQUE KEY `uk_conversacion_contexto` (`usuario_id`, `interlocutor_id`, `tipo`, `equipo_contexto`),
  KEY `idx_conversacion_interlocutor` (`interlocutor_id`, `tipo`, `fecha_actualizacion`),
  CONSTRAINT `fk_conversacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversacion_interlocutor` FOREIGN KEY (`interlocutor_id`) REFERENCES `Usuarios` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversacion_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `Equipos` (`equipo_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Mensajes` (
  `mensaje_id` BIGINT NOT NULL AUTO_INCREMENT,
  `conversacion_id` INT NOT NULL,
  `emisor_id` INT NOT NULL,
  `contenido` TEXT NOT NULL,
  `leido` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_envio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mensaje_id`),
  KEY `idx_mensaje_conversacion_fecha` (`conversacion_id`, `fecha_envio`, `mensaje_id`),
  CONSTRAINT `fk_mensaje_conversacion` FOREIGN KEY (`conversacion_id`) REFERENCES `Conversaciones` (`conversacion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mensaje_emisor` FOREIGN KEY (`emisor_id`) REFERENCES `Usuarios` (`usuario_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `PuntosRecoleccion` (
  `punto_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `ciudad_id` INT NULL,
  `direccion` VARCHAR(250) NOT NULL,
  `horario` VARCHAR(150) NOT NULL,
  `instrucciones` VARCHAR(250) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`punto_id`),
  KEY `idx_punto_activo_ciudad` (`activo`, `ciudad_id`),
  CONSTRAINT `fk_punto_ciudad` FOREIGN KEY (`ciudad_id`) REFERENCES `Ciudades` (`ciudad_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
