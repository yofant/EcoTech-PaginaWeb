-- Row-level audit triggers for the core EcoTech schema.
-- Run after ECOTECH_KTOR_SCHEMA.sql and auditoria_schema.sql.
-- Re-running this file replaces the triggers without clearing audit history.
USE `ecotech`;

DROP TRIGGER IF EXISTS `ecotech_ciudades_ai`;
CREATE TRIGGER `ecotech_ciudades_ai` AFTER INSERT ON `Ciudades` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Ciudades', 'INSERT', NEW.ciudad_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('ciudad_id', NEW.ciudad_id, 'nombre', NEW.nombre, 'departamento', NEW.departamento));
DROP TRIGGER IF EXISTS `ecotech_ciudades_au`;
CREATE TRIGGER `ecotech_ciudades_au` AFTER UPDATE ON `Ciudades` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Ciudades', 'UPDATE', NEW.ciudad_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('ciudad_id', OLD.ciudad_id, 'nombre', OLD.nombre, 'departamento', OLD.departamento), JSON_OBJECT('ciudad_id', NEW.ciudad_id, 'nombre', NEW.nombre, 'departamento', NEW.departamento));
DROP TRIGGER IF EXISTS `ecotech_ciudades_ad`;
CREATE TRIGGER `ecotech_ciudades_ad` AFTER DELETE ON `Ciudades` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Ciudades', 'DELETE', OLD.ciudad_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('ciudad_id', OLD.ciudad_id, 'nombre', OLD.nombre, 'departamento', OLD.departamento));

DROP TRIGGER IF EXISTS `ecotech_tipos_equipo_ai`;
CREATE TRIGGER `ecotech_tipos_equipo_ai` AFTER INSERT ON `TiposEquipo` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('TiposEquipo', 'INSERT', NEW.tipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('tipo_id', NEW.tipo_id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion));
DROP TRIGGER IF EXISTS `ecotech_tipos_equipo_au`;
CREATE TRIGGER `ecotech_tipos_equipo_au` AFTER UPDATE ON `TiposEquipo` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('TiposEquipo', 'UPDATE', NEW.tipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('tipo_id', OLD.tipo_id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion), JSON_OBJECT('tipo_id', NEW.tipo_id, 'nombre', NEW.nombre, 'descripcion', NEW.descripcion));
DROP TRIGGER IF EXISTS `ecotech_tipos_equipo_ad`;
CREATE TRIGGER `ecotech_tipos_equipo_ad` AFTER DELETE ON `TiposEquipo` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('TiposEquipo', 'DELETE', OLD.tipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('tipo_id', OLD.tipo_id, 'nombre', OLD.nombre, 'descripcion', OLD.descripcion));

DROP TRIGGER IF EXISTS `ecotech_donantes_ai`;
CREATE TRIGGER `ecotech_donantes_ai` AFTER INSERT ON `Donantes` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Donantes', 'INSERT', NEW.donante_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('donante_id', NEW.donante_id, 'tipo', NEW.tipo, 'nombre', NEW.nombre, 'email', NEW.email, 'telefono', NEW.telefono, 'ciudad_id', NEW.ciudad_id, 'direccion', NEW.direccion, 'fecha_registro', NEW.fecha_registro));
DROP TRIGGER IF EXISTS `ecotech_donantes_au`;
CREATE TRIGGER `ecotech_donantes_au` AFTER UPDATE ON `Donantes` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Donantes', 'UPDATE', NEW.donante_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('donante_id', OLD.donante_id, 'tipo', OLD.tipo, 'nombre', OLD.nombre, 'email', OLD.email, 'telefono', OLD.telefono, 'ciudad_id', OLD.ciudad_id, 'direccion', OLD.direccion, 'fecha_registro', OLD.fecha_registro), JSON_OBJECT('donante_id', NEW.donante_id, 'tipo', NEW.tipo, 'nombre', NEW.nombre, 'email', NEW.email, 'telefono', NEW.telefono, 'ciudad_id', NEW.ciudad_id, 'direccion', NEW.direccion, 'fecha_registro', NEW.fecha_registro));
DROP TRIGGER IF EXISTS `ecotech_donantes_ad`;
CREATE TRIGGER `ecotech_donantes_ad` AFTER DELETE ON `Donantes` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Donantes', 'DELETE', OLD.donante_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('donante_id', OLD.donante_id, 'tipo', OLD.tipo, 'nombre', OLD.nombre, 'email', OLD.email, 'telefono', OLD.telefono, 'ciudad_id', OLD.ciudad_id, 'direccion', OLD.direccion, 'fecha_registro', OLD.fecha_registro));

DROP TRIGGER IF EXISTS `ecotech_usuarios_ai`;
CREATE TRIGGER `ecotech_usuarios_ai` AFTER INSERT ON `Usuarios` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Usuarios', 'INSERT', NEW.usuario_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado; contraseña excluida', JSON_OBJECT('usuario_id', NEW.usuario_id, 'nombre', NEW.nombre, 'apellido', NEW.apellido, 'email', NEW.email, 'telefono', NEW.telefono, 'rol', NEW.rol, 'activo', NEW.activo, 'fecha_registro', NEW.fecha_registro));
DROP TRIGGER IF EXISTS `ecotech_usuarios_au`;
CREATE TRIGGER `ecotech_usuarios_au` AFTER UPDATE ON `Usuarios` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Usuarios', 'UPDATE', NEW.usuario_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado; contraseña excluida', JSON_OBJECT('usuario_id', OLD.usuario_id, 'nombre', OLD.nombre, 'apellido', OLD.apellido, 'email', OLD.email, 'telefono', OLD.telefono, 'rol', OLD.rol, 'activo', OLD.activo, 'fecha_registro', OLD.fecha_registro), JSON_OBJECT('usuario_id', NEW.usuario_id, 'nombre', NEW.nombre, 'apellido', NEW.apellido, 'email', NEW.email, 'telefono', NEW.telefono, 'rol', NEW.rol, 'activo', NEW.activo, 'fecha_registro', NEW.fecha_registro));
DROP TRIGGER IF EXISTS `ecotech_usuarios_ad`;
CREATE TRIGGER `ecotech_usuarios_ad` AFTER DELETE ON `Usuarios` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Usuarios', 'DELETE', OLD.usuario_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado; contraseña excluida', JSON_OBJECT('usuario_id', OLD.usuario_id, 'nombre', OLD.nombre, 'apellido', OLD.apellido, 'email', OLD.email, 'telefono', OLD.telefono, 'rol', OLD.rol, 'activo', OLD.activo, 'fecha_registro', OLD.fecha_registro));

DROP TRIGGER IF EXISTS `ecotech_beneficiarios_ai`;
CREATE TRIGGER `ecotech_beneficiarios_ai` AFTER INSERT ON `Beneficiarios` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Beneficiarios', 'INSERT', NEW.beneficiario_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('beneficiario_id', NEW.beneficiario_id, 'nombre', NEW.nombre, 'apellido', NEW.apellido, 'documento', NEW.documento, 'email', NEW.email, 'telefono', NEW.telefono, 'ciudad_id', NEW.ciudad_id, 'direccion', NEW.direccion, 'estrato', NEW.estrato, 'necesidad', NEW.necesidad, 'fecha_registro', NEW.fecha_registro));
DROP TRIGGER IF EXISTS `ecotech_beneficiarios_au`;
CREATE TRIGGER `ecotech_beneficiarios_au` AFTER UPDATE ON `Beneficiarios` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Beneficiarios', 'UPDATE', NEW.beneficiario_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('beneficiario_id', OLD.beneficiario_id, 'nombre', OLD.nombre, 'apellido', OLD.apellido, 'documento', OLD.documento, 'email', OLD.email, 'telefono', OLD.telefono, 'ciudad_id', OLD.ciudad_id, 'direccion', OLD.direccion, 'estrato', OLD.estrato, 'necesidad', OLD.necesidad, 'fecha_registro', OLD.fecha_registro), JSON_OBJECT('beneficiario_id', NEW.beneficiario_id, 'nombre', NEW.nombre, 'apellido', NEW.apellido, 'documento', NEW.documento, 'email', NEW.email, 'telefono', NEW.telefono, 'ciudad_id', NEW.ciudad_id, 'direccion', NEW.direccion, 'estrato', NEW.estrato, 'necesidad', NEW.necesidad, 'fecha_registro', NEW.fecha_registro));
DROP TRIGGER IF EXISTS `ecotech_beneficiarios_ad`;
CREATE TRIGGER `ecotech_beneficiarios_ad` AFTER DELETE ON `Beneficiarios` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Beneficiarios', 'DELETE', OLD.beneficiario_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('beneficiario_id', OLD.beneficiario_id, 'nombre', OLD.nombre, 'apellido', OLD.apellido, 'documento', OLD.documento, 'email', OLD.email, 'telefono', OLD.telefono, 'ciudad_id', OLD.ciudad_id, 'direccion', OLD.direccion, 'estrato', OLD.estrato, 'necesidad', OLD.necesidad, 'fecha_registro', OLD.fecha_registro));

DROP TRIGGER IF EXISTS `ecotech_equipos_ai`;
CREATE TRIGGER `ecotech_equipos_ai` AFTER INSERT ON `Equipos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Equipos', 'INSERT', NEW.equipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('equipo_id', NEW.equipo_id, 'tipo_id', NEW.tipo_id, 'donante_id', NEW.donante_id, 'marca', NEW.marca, 'modelo', NEW.modelo, 'serial', NEW.serial, 'estado_ingreso', NEW.estado_ingreso, 'estado_actual', NEW.estado_actual, 'descripcion', NEW.descripcion, 'fecha_recepcion', NEW.fecha_recepcion, 'usuario_id', NEW.usuario_id));
DROP TRIGGER IF EXISTS `ecotech_equipos_au`;
CREATE TRIGGER `ecotech_equipos_au` AFTER UPDATE ON `Equipos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Equipos', 'UPDATE', NEW.equipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('equipo_id', OLD.equipo_id, 'tipo_id', OLD.tipo_id, 'donante_id', OLD.donante_id, 'marca', OLD.marca, 'modelo', OLD.modelo, 'serial', OLD.serial, 'estado_ingreso', OLD.estado_ingreso, 'estado_actual', OLD.estado_actual, 'descripcion', OLD.descripcion, 'fecha_recepcion', OLD.fecha_recepcion, 'usuario_id', OLD.usuario_id), JSON_OBJECT('equipo_id', NEW.equipo_id, 'tipo_id', NEW.tipo_id, 'donante_id', NEW.donante_id, 'marca', NEW.marca, 'modelo', NEW.modelo, 'serial', NEW.serial, 'estado_ingreso', NEW.estado_ingreso, 'estado_actual', NEW.estado_actual, 'descripcion', NEW.descripcion, 'fecha_recepcion', NEW.fecha_recepcion, 'usuario_id', NEW.usuario_id));
DROP TRIGGER IF EXISTS `ecotech_equipos_ad`;
CREATE TRIGGER `ecotech_equipos_ad` AFTER DELETE ON `Equipos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Equipos', 'DELETE', OLD.equipo_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('equipo_id', OLD.equipo_id, 'tipo_id', OLD.tipo_id, 'donante_id', OLD.donante_id, 'marca', OLD.marca, 'modelo', OLD.modelo, 'serial', OLD.serial, 'estado_ingreso', OLD.estado_ingreso, 'estado_actual', OLD.estado_actual, 'descripcion', OLD.descripcion, 'fecha_recepcion', OLD.fecha_recepcion, 'usuario_id', OLD.usuario_id));

DROP TRIGGER IF EXISTS `ecotech_diagnosticos_ai`;
CREATE TRIGGER `ecotech_diagnosticos_ai` AFTER INSERT ON `Diagnosticos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Diagnosticos', 'INSERT', NEW.diagnostico_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('diagnostico_id', NEW.diagnostico_id, 'equipo_id', NEW.equipo_id, 'tecnico_id', NEW.tecnico_id, 'descripcion', NEW.descripcion, 'requiere_repara', NEW.requiere_repara, 'costo_estimado', NEW.costo_estimado, 'fecha', NEW.fecha));
DROP TRIGGER IF EXISTS `ecotech_diagnosticos_au`;
CREATE TRIGGER `ecotech_diagnosticos_au` AFTER UPDATE ON `Diagnosticos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Diagnosticos', 'UPDATE', NEW.diagnostico_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('diagnostico_id', OLD.diagnostico_id, 'equipo_id', OLD.equipo_id, 'tecnico_id', OLD.tecnico_id, 'descripcion', OLD.descripcion, 'requiere_repara', OLD.requiere_repara, 'costo_estimado', OLD.costo_estimado, 'fecha', OLD.fecha), JSON_OBJECT('diagnostico_id', NEW.diagnostico_id, 'equipo_id', NEW.equipo_id, 'tecnico_id', NEW.tecnico_id, 'descripcion', NEW.descripcion, 'requiere_repara', NEW.requiere_repara, 'costo_estimado', NEW.costo_estimado, 'fecha', NEW.fecha));
DROP TRIGGER IF EXISTS `ecotech_diagnosticos_ad`;
CREATE TRIGGER `ecotech_diagnosticos_ad` AFTER DELETE ON `Diagnosticos` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Diagnosticos', 'DELETE', OLD.diagnostico_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('diagnostico_id', OLD.diagnostico_id, 'equipo_id', OLD.equipo_id, 'tecnico_id', OLD.tecnico_id, 'descripcion', OLD.descripcion, 'requiere_repara', OLD.requiere_repara, 'costo_estimado', OLD.costo_estimado, 'fecha', OLD.fecha));

DROP TRIGGER IF EXISTS `ecotech_reparaciones_ai`;
CREATE TRIGGER `ecotech_reparaciones_ai` AFTER INSERT ON `Reparaciones` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Reparaciones', 'INSERT', NEW.reparacion_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('reparacion_id', NEW.reparacion_id, 'equipo_id', NEW.equipo_id, 'tecnico_id', NEW.tecnico_id, 'descripcion', NEW.descripcion, 'repuestos_usados', NEW.repuestos_usados, 'costo_real', NEW.costo_real, 'estado', NEW.estado, 'fecha_inicio', NEW.fecha_inicio, 'fecha_fin', NEW.fecha_fin));
DROP TRIGGER IF EXISTS `ecotech_reparaciones_au`;
CREATE TRIGGER `ecotech_reparaciones_au` AFTER UPDATE ON `Reparaciones` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Reparaciones', 'UPDATE', NEW.reparacion_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('reparacion_id', OLD.reparacion_id, 'equipo_id', OLD.equipo_id, 'tecnico_id', OLD.tecnico_id, 'descripcion', OLD.descripcion, 'repuestos_usados', OLD.repuestos_usados, 'costo_real', OLD.costo_real, 'estado', OLD.estado, 'fecha_inicio', OLD.fecha_inicio, 'fecha_fin', OLD.fecha_fin), JSON_OBJECT('reparacion_id', NEW.reparacion_id, 'equipo_id', NEW.equipo_id, 'tecnico_id', NEW.tecnico_id, 'descripcion', NEW.descripcion, 'repuestos_usados', NEW.repuestos_usados, 'costo_real', NEW.costo_real, 'estado', NEW.estado, 'fecha_inicio', NEW.fecha_inicio, 'fecha_fin', NEW.fecha_fin));
DROP TRIGGER IF EXISTS `ecotech_reparaciones_ad`;
CREATE TRIGGER `ecotech_reparaciones_ad` AFTER DELETE ON `Reparaciones` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Reparaciones', 'DELETE', OLD.reparacion_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('reparacion_id', OLD.reparacion_id, 'equipo_id', OLD.equipo_id, 'tecnico_id', OLD.tecnico_id, 'descripcion', OLD.descripcion, 'repuestos_usados', OLD.repuestos_usados, 'costo_real', OLD.costo_real, 'estado', OLD.estado, 'fecha_inicio', OLD.fecha_inicio, 'fecha_fin', OLD.fecha_fin));

DROP TRIGGER IF EXISTS `ecotech_entregas_ai`;
CREATE TRIGGER `ecotech_entregas_ai` AFTER INSERT ON `Entregas` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_nuevos)
  VALUES ('Entregas', 'INSERT', NEW.entrega_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro creado', JSON_OBJECT('entrega_id', NEW.entrega_id, 'equipo_id', NEW.equipo_id, 'beneficiario_id', NEW.beneficiario_id, 'usuario_id', NEW.usuario_id, 'fecha_entrega', NEW.fecha_entrega, 'condiciones', NEW.condiciones, 'observaciones', NEW.observaciones));
DROP TRIGGER IF EXISTS `ecotech_entregas_au`;
CREATE TRIGGER `ecotech_entregas_au` AFTER UPDATE ON `Entregas` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores, valores_nuevos)
  VALUES ('Entregas', 'UPDATE', NEW.entrega_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro actualizado', JSON_OBJECT('entrega_id', OLD.entrega_id, 'equipo_id', OLD.equipo_id, 'beneficiario_id', OLD.beneficiario_id, 'usuario_id', OLD.usuario_id, 'fecha_entrega', OLD.fecha_entrega, 'condiciones', OLD.condiciones, 'observaciones', OLD.observaciones), JSON_OBJECT('entrega_id', NEW.entrega_id, 'equipo_id', NEW.equipo_id, 'beneficiario_id', NEW.beneficiario_id, 'usuario_id', NEW.usuario_id, 'fecha_entrega', NEW.fecha_entrega, 'condiciones', NEW.condiciones, 'observaciones', NEW.observaciones));
DROP TRIGGER IF EXISTS `ecotech_entregas_ad`;
CREATE TRIGGER `ecotech_entregas_ad` AFTER DELETE ON `Entregas` FOR EACH ROW
  INSERT INTO `Auditoria` (tabla_afectada, operacion, registro_id, usuario_sql, detalle, valores_anteriores)
  VALUES ('Entregas', 'DELETE', OLD.entrega_id, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), 'Registro eliminado', JSON_OBJECT('entrega_id', OLD.entrega_id, 'equipo_id', OLD.equipo_id, 'beneficiario_id', OLD.beneficiario_id, 'usuario_id', OLD.usuario_id, 'fecha_entrega', OLD.fecha_entrega, 'condiciones', OLD.condiciones, 'observaciones', OLD.observaciones));
