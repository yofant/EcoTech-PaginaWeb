-- Row-level audit triggers for optional user-portal tables.
-- Run after user_portal_schema.sql and auditoria_schema.sql.
USE `ecotech`;

DROP TRIGGER IF EXISTS `ecotech_puntos_ai`;
CREATE TRIGGER `ecotech_puntos_ai` AFTER INSERT ON `PuntosRecoleccion` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('PuntosRecoleccion', 'INSERT', NEW.punto_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('punto_id', NEW.punto_id, 'nombre', NEW.nombre, 'ciudad_id', NEW.ciudad_id, 'direccion', NEW.direccion, 'horario', NEW.horario, 'instrucciones', NEW.instrucciones, 'activo', NEW.activo, 'fecha_creacion', NEW.fecha_creacion));
DROP TRIGGER IF EXISTS `ecotech_puntos_au`;
CREATE TRIGGER `ecotech_puntos_au` AFTER UPDATE ON `PuntosRecoleccion` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('PuntosRecoleccion', 'UPDATE', NEW.punto_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('punto_id', OLD.punto_id, 'nombre', OLD.nombre, 'ciudad_id', OLD.ciudad_id, 'direccion', OLD.direccion, 'horario', OLD.horario, 'instrucciones', OLD.instrucciones, 'activo', OLD.activo, 'fecha_creacion', OLD.fecha_creacion), JSON_OBJECT('punto_id', NEW.punto_id, 'nombre', NEW.nombre, 'ciudad_id', NEW.ciudad_id, 'direccion', NEW.direccion, 'horario', NEW.horario, 'instrucciones', NEW.instrucciones, 'activo', NEW.activo, 'fecha_creacion', NEW.fecha_creacion));
DROP TRIGGER IF EXISTS `ecotech_puntos_ad`;
CREATE TRIGGER `ecotech_puntos_ad` AFTER DELETE ON `PuntosRecoleccion` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('PuntosRecoleccion', 'DELETE', OLD.punto_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('punto_id', OLD.punto_id, 'nombre', OLD.nombre, 'ciudad_id', OLD.ciudad_id, 'direccion', OLD.direccion, 'horario', OLD.horario, 'instrucciones', OLD.instrucciones, 'activo', OLD.activo, 'fecha_creacion', OLD.fecha_creacion));

DROP TRIGGER IF EXISTS `ecotech_conversaciones_ai`;
CREATE TRIGGER `ecotech_conversaciones_ai` AFTER INSERT ON `Conversaciones` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Conversaciones', 'INSERT', NEW.conversacion_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Conversación iniciada', JSON_OBJECT('conversacion_id', NEW.conversacion_id, 'usuario_id', NEW.usuario_id, 'interlocutor_id', NEW.interlocutor_id, 'tipo', NEW.tipo, 'equipo_id', NEW.equipo_id, 'fecha_creacion', NEW.fecha_creacion));
DROP TRIGGER IF EXISTS `ecotech_conversaciones_au`;
CREATE TRIGGER `ecotech_conversaciones_au` AFTER UPDATE ON `Conversaciones` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Conversaciones', 'UPDATE', NEW.conversacion_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Conversación actualizada', JSON_OBJECT('conversacion_id', OLD.conversacion_id, 'usuario_id', OLD.usuario_id, 'interlocutor_id', OLD.interlocutor_id, 'tipo', OLD.tipo, 'equipo_id', OLD.equipo_id, 'fecha_creacion', OLD.fecha_creacion, 'fecha_actualizacion', OLD.fecha_actualizacion), JSON_OBJECT('conversacion_id', NEW.conversacion_id, 'usuario_id', NEW.usuario_id, 'interlocutor_id', NEW.interlocutor_id, 'tipo', NEW.tipo, 'equipo_id', NEW.equipo_id, 'fecha_creacion', NEW.fecha_creacion, 'fecha_actualizacion', NEW.fecha_actualizacion));
DROP TRIGGER IF EXISTS `ecotech_conversaciones_ad`;
CREATE TRIGGER `ecotech_conversaciones_ad` AFTER DELETE ON `Conversaciones` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Conversaciones', 'DELETE', OLD.conversacion_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Conversación eliminada', JSON_OBJECT('conversacion_id', OLD.conversacion_id, 'usuario_id', OLD.usuario_id, 'interlocutor_id', OLD.interlocutor_id, 'tipo', OLD.tipo, 'equipo_id', OLD.equipo_id, 'fecha_creacion', OLD.fecha_creacion, 'fecha_actualizacion', OLD.fecha_actualizacion));

DROP TRIGGER IF EXISTS `ecotech_mensajes_ai`;
CREATE TRIGGER `ecotech_mensajes_ai` AFTER INSERT ON `Mensajes` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Mensajes', 'INSERT', NEW.mensaje_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Mensaje enviado; contenido excluido por privacidad', JSON_OBJECT('mensaje_id', NEW.mensaje_id, 'conversacion_id', NEW.conversacion_id, 'emisor_id', NEW.emisor_id, 'leido', NEW.leido, 'fecha_envio', NEW.fecha_envio));
DROP TRIGGER IF EXISTS `ecotech_mensajes_au`;
CREATE TRIGGER `ecotech_mensajes_au` AFTER UPDATE ON `Mensajes` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Mensajes', 'UPDATE', NEW.mensaje_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Mensaje actualizado; contenido excluido por privacidad', JSON_OBJECT('mensaje_id', OLD.mensaje_id, 'conversacion_id', OLD.conversacion_id, 'emisor_id', OLD.emisor_id, 'leido', OLD.leido, 'fecha_envio', OLD.fecha_envio), JSON_OBJECT('mensaje_id', NEW.mensaje_id, 'conversacion_id', NEW.conversacion_id, 'emisor_id', NEW.emisor_id, 'leido', NEW.leido, 'fecha_envio', NEW.fecha_envio));
DROP TRIGGER IF EXISTS `ecotech_mensajes_ad`;
CREATE TRIGGER `ecotech_mensajes_ad` AFTER DELETE ON `Mensajes` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Mensajes', 'DELETE', OLD.mensaje_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Mensaje eliminado; contenido excluido por privacidad', JSON_OBJECT('mensaje_id', OLD.mensaje_id, 'conversacion_id', OLD.conversacion_id, 'emisor_id', OLD.emisor_id, 'leido', OLD.leido, 'fecha_envio', OLD.fecha_envio));

-- Replace the core equipment snapshots to include the portal publication flag.
DROP TRIGGER IF EXISTS `ecotech_equipos_ai`;
CREATE TRIGGER `ecotech_equipos_ai` AFTER INSERT ON `Equipos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Equipos', 'INSERT', NEW.equipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('equipo_id', NEW.equipo_id, 'tipo_id', NEW.tipo_id, 'donante_id', NEW.donante_id, 'marca', NEW.marca, 'modelo', NEW.modelo, 'serial', NEW.serial, 'estado_ingreso', NEW.estado_ingreso, 'estado_actual', NEW.estado_actual, 'descripcion', NEW.descripcion, 'fecha_recepcion', NEW.fecha_recepcion, 'usuario_id', NEW.usuario_id, 'publicado', NEW.publicado));
DROP TRIGGER IF EXISTS `ecotech_equipos_au`;
CREATE TRIGGER `ecotech_equipos_au` AFTER UPDATE ON `Equipos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Equipos', 'UPDATE', NEW.equipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('equipo_id', OLD.equipo_id, 'tipo_id', OLD.tipo_id, 'donante_id', OLD.donante_id, 'marca', OLD.marca, 'modelo', OLD.modelo, 'serial', OLD.serial, 'estado_ingreso', OLD.estado_ingreso, 'estado_actual', OLD.estado_actual, 'descripcion', OLD.descripcion, 'fecha_recepcion', OLD.fecha_recepcion, 'usuario_id', OLD.usuario_id, 'publicado', OLD.publicado), JSON_OBJECT('equipo_id', NEW.equipo_id, 'tipo_id', NEW.tipo_id, 'donante_id', NEW.donante_id, 'marca', NEW.marca, 'modelo', NEW.modelo, 'serial', NEW.serial, 'estado_ingreso', NEW.estado_ingreso, 'estado_actual', NEW.estado_actual, 'descripcion', NEW.descripcion, 'fecha_recepcion', NEW.fecha_recepcion, 'usuario_id', NEW.usuario_id, 'publicado', NEW.publicado));
DROP TRIGGER IF EXISTS `ecotech_equipos_ad`;
CREATE TRIGGER `ecotech_equipos_ad` AFTER DELETE ON `Equipos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Equipos', 'DELETE', OLD.equipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('equipo_id', OLD.equipo_id, 'tipo_id', OLD.tipo_id, 'donante_id', OLD.donante_id, 'marca', OLD.marca, 'modelo', OLD.modelo, 'serial', OLD.serial, 'estado_ingreso', OLD.estado_ingreso, 'estado_actual', OLD.estado_actual, 'descripcion', OLD.descripcion, 'fecha_recepcion', OLD.fecha_recepcion, 'usuario_id', OLD.usuario_id, 'publicado', OLD.publicado));
