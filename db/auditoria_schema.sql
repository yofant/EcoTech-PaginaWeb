-- Add audit snapshots to existing audit events.
-- Run once after ECOTECH_KTOR_SCHEMA.sql and before the audit trigger scripts.
USE `ecotech`;

ALTER TABLE `Auditoria`
  MODIFY COLUMN `detalle` TEXT NULL,
  ADD COLUMN `valores_anteriores` JSON NULL AFTER `detalle`,
  ADD COLUMN `valores_nuevos` JSON NULL AFTER `valores_anteriores`;
