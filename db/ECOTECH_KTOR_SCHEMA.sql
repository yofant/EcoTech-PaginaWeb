-- =====================================================================
-- ECOTECH - Script Oficial de Base de Datos para el Servidor Ktor
-- Compatible con MySQL 5.7+, MySQL 8.0+ y MariaDB 10.3+
-- Corresponde al modelo ORM (Exposed) definido en DatabaseFactory.kt
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `ecotech` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `ecotech`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Tabla: Ciudades
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Ciudades`;
CREATE TABLE `Ciudades` (
  `ciudad_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `departamento` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`ciudad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Tabla: TiposEquipo
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `TiposEquipo`;
CREATE TABLE `TiposEquipo` (
  `tipo_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(250) NULL,
  PRIMARY KEY (`tipo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Tabla: Donantes
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Donantes`;
CREATE TABLE `Donantes` (
  `donante_id` INT NOT NULL AUTO_INCREMENT,
  `tipo` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `ciudad_id` INT NULL,
  `direccion` VARCHAR(250) NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`donante_id`),
  CONSTRAINT `fk_donantes_ciudad` FOREIGN KEY (`ciudad_id`) REFERENCES `Ciudades` (`ciudad_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Tabla: Usuarios
-- Roles base: Administrador, Tecnico, Operador, Auditor, Usuario y Vendedor
-- password_hash admite hashes de PHP; los usuarios seed conservan SHA-256 legado.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Usuarios`;
CREATE TABLE `Usuarios` (
  `usuario_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `rol` VARCHAR(50) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `password_hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`usuario_id`),
  UNIQUE KEY `uk_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Tabla: Beneficiarios
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Beneficiarios`;
CREATE TABLE `Beneficiarios` (
  `beneficiario_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `documento` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `ciudad_id` INT NULL,
  `direccion` VARCHAR(250) NOT NULL,
  `estrato` INT NULL,
  `necesidad` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`beneficiario_id`),
  UNIQUE KEY `uk_beneficiarios_documento` (`documento`),
  CONSTRAINT `fk_beneficiarios_ciudad` FOREIGN KEY (`ciudad_id`) REFERENCES `Ciudades` (`ciudad_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Tabla: Equipos
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Equipos`;
CREATE TABLE `Equipos` (
  `equipo_id` INT NOT NULL AUTO_INCREMENT,
  `tipo_id` INT NOT NULL,
  `donante_id` INT NULL,
  `marca` VARCHAR(100) NULL,
  `modelo` VARCHAR(100) NULL,
  `serial` VARCHAR(100) NULL,
  `estado_ingreso` VARCHAR(50) NULL,
  `estado_actual` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(200) NULL,
  `fecha_recepcion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` INT NULL,
  PRIMARY KEY (`equipo_id`),
  UNIQUE KEY `uk_equipos_serial` (`serial`),
  CONSTRAINT `fk_equipos_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `TiposEquipo` (`tipo_id`),
  CONSTRAINT `fk_equipos_donante` FOREIGN KEY (`donante_id`) REFERENCES `Donantes` (`donante_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`usuario_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Tabla: Diagnosticos
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Diagnosticos`;
CREATE TABLE `Diagnosticos` (
  `diagnostico_id` INT NOT NULL AUTO_INCREMENT,
  `equipo_id` INT NOT NULL,
  `tecnico_id` INT NOT NULL,
  `descripcion` VARCHAR(250) NULL,
  `requiere_repara` TINYINT(1) NOT NULL DEFAULT 1,
  `costo_estimado` DECIMAL(18,2) NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`diagnostico_id`),
  CONSTRAINT `fk_diagnosticos_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `Equipos` (`equipo_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_diagnosticos_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `Usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Tabla: Reparaciones
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Reparaciones`;
CREATE TABLE `Reparaciones` (
  `reparacion_id` INT NOT NULL AUTO_INCREMENT,
  `equipo_id` INT NOT NULL,
  `tecnico_id` INT NOT NULL,
  `descripcion` VARCHAR(250) NULL,
  `repuestos_usados` VARCHAR(100) NULL,
  `costo_real` DECIMAL(18,2) NULL,
  `estado` VARCHAR(50) NULL,
  `fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` DATETIME NULL,
  PRIMARY KEY (`reparacion_id`),
  CONSTRAINT `fk_reparaciones_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `Equipos` (`equipo_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reparaciones_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `Usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. Tabla: Entregas
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Entregas`;
CREATE TABLE `Entregas` (
  `entrega_id` INT NOT NULL AUTO_INCREMENT,
  `equipo_id` INT NOT NULL,
  `beneficiario_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `fecha_entrega` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `condiciones` VARCHAR(100) NULL,
  `observaciones` VARCHAR(100) NULL,
  PRIMARY KEY (`entrega_id`),
  CONSTRAINT `fk_entregas_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `Equipos` (`equipo_id`),
  CONSTRAINT `fk_entregas_beneficiario` FOREIGN KEY (`beneficiario_id`) REFERENCES `Beneficiarios` (`beneficiario_id`),
  CONSTRAINT `fk_entregas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. Tabla: Auditoria
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `Auditoria`;
CREATE TABLE `Auditoria` (
  `auditoria_id` INT NOT NULL AUTO_INCREMENT,
  `tabla_afectada` VARCHAR(100) NOT NULL,
  `operacion` VARCHAR(15) NOT NULL,
  `registro_id` INT NULL,
  `usuario_sql` VARCHAR(100) NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `detalle` VARCHAR(100) NULL,
  PRIMARY KEY (`auditoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DATOS INICIALES (SEEDS)
-- =====================================================================

-- Ciudades
INSERT INTO `Ciudades` (`ciudad_id`, `nombre`, `departamento`) VALUES
(1, 'Bogotá', 'Cundinamarca'),
(2, 'Medellín', 'Antioquia'),
(3, 'Cali', 'Valle del Cauca'),
(4, 'Barranquilla', 'Atlántico'),
(5, 'Cartagena', 'Bolívar')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Tipos de Equipo
INSERT INTO `TiposEquipo` (`tipo_id`, `nombre`, `descripcion`) VALUES
(1, 'Computador de escritorio', 'Equipos de escritorio'),
(2, 'Portátil', 'Equipos móviles'),
(3, 'Celular', 'Smartphones'),
(4, 'Tablet', 'Tabletas'),
(5, 'Impresora', 'Periféricos de impresión'),
(6, 'Monitor', 'Pantallas'),
(7, 'Periférico', 'Teclados, ratones, etc.')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Usuarios Iniciales
-- Contraseña para todos: 'EcoTech2026!'
-- Hash SHA-256: 61a66994d1c3805ccdee514aa7a9cc55936dd1e97203b76932b74832287a7702
INSERT INTO `Usuarios` (`usuario_id`, `nombre`, `apellido`, `email`, `telefono`, `rol`, `activo`, `fecha_registro`, `password_hash`) VALUES
(1, 'Yofan', 'Tellez', 'yojantellez8@gmail.com', '3001234567', 'Administrador', 1, NOW(), '61a66994d1c3805ccdee514aa7a9cc55936dd1e97203b76932b74832287a7702'),
(2, 'Cristian', 'Munca', 'cristian.munca@gmail.com', '3109876543', 'Administrador', 1, NOW(), '61a66994d1c3805ccdee514aa7a9cc55936dd1e97203b76932b74832287a7702'),
(3, 'Karoline', 'Miranda', 'karoline.sanchez@gmail.com', '3155551234', 'Tecnico', 1, NOW(), '61a66994d1c3805ccdee514aa7a9cc55936dd1e97203b76932b74832287a7702'),
(4, 'Yaneth', 'Mendez', 'yaneth.mendez@gmail.com', '3204449876', 'Operador', 1, NOW(), '61a66994d1c3805ccdee514aa7a9cc55936dd1e97203b76932b74832287a7702'),
(5, 'Auditor', 'General', 'auditor@ecotech.com', '3009990000', 'Auditor', 1, NOW(), '61a66994d1c3805ccdee514aa7a9cc55936dd1e97203b76932b74832287a7702')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Donante de prueba
INSERT INTO `Donantes` (`donante_id`, `tipo`, `nombre`, `email`, `telefono`, `ciudad_id`, `direccion`, `fecha_registro`) VALUES
(1, 'Empresa', 'Fundación Verde Tech', 'contacto@verdetech.org', '6012345678', 1, 'Calle 100 # 15-20', NOW())
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Beneficiario de prueba
INSERT INTO `Beneficiarios` (`beneficiario_id`, `nombre`, `apellido`, `documento`, `email`, `telefono`, `ciudad_id`, `direccion`, `estrato`, `necesidad`, `fecha_registro`) VALUES
(1, 'Escuela Rural', 'Esperanza', '900123456-1', 'esperanza@educacion.gov.co', '3123456789', 2, 'Vereda Las Palmas', 1, 'Equipos para aula de computación', NOW())
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Equipo de prueba
INSERT INTO `Equipos` (`equipo_id`, `tipo_id`, `donante_id`, `marca`, `modelo`, `serial`, `estado_ingreso`, `estado_actual`, `descripcion`, `fecha_recepcion`, `usuario_id`) VALUES
(1, 2, 1, 'Lenovo', 'ThinkPad T480', 'PF123456XYZ', 'Usado - Buen Estado', 'En Diagnóstico', 'Portátil donado para aula comunitaria', NOW(), 1)
ON DUPLICATE KEY UPDATE `serial` = VALUES(`serial`);

-- Registro de Auditoría inicial
INSERT INTO `Auditoria` (`tabla_afectada`, `operacion`, `registro_id`, `usuario_sql`, `fecha`, `detalle`) VALUES
('Database', 'INIT', 1, 'SYSTEM', NOW(), 'Inicialización oficial del esquema EcoTech Ktor');
