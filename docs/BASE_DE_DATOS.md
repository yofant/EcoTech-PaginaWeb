# Base de datos EcoTech

El esquema que consume el backend se encuentra en [`db/ECOTECH_KTOR_SCHEMA.sql`](../db/ECOTECH_KTOR_SCHEMA.sql). Crea la base `ecotech` y las tablas `Usuarios`, `Donantes`, `Ciudades`, `TiposEquipo`, `Equipos`, `Diagnosticos`, `Reparaciones`, `Beneficiarios`, `Entregas` y `Auditoria`.

Importa ese archivo en MySQL/MariaDB antes de usar la aplicación. El script recrea sus tablas (usa `DROP TABLE`), por lo que no debe ejecutarse sobre una base con datos que se deban conservar sin antes respaldarla.

Para habilitar chats, publicaciones y puntos de entrega, importa una sola vez [`db/user_portal_schema.sql`](../db/user_portal_schema.sql) después del esquema principal. Es una migración aditiva: añade el indicador de publicación a `Equipos` y crea las tablas `Conversaciones`, `Mensajes` y `PuntosRecoleccion`. No contiene direcciones ficticias; un administrador debe cargar ubicaciones verificadas desde el panel.

Para habilitar el historial completo, ejecuta una sola vez `db/auditoria_schema.sql` y luego `db/auditoria_triggers.sql`. Si instalaste el portal de usuarios, ejecuta también `db/auditoria_portal_triggers.sql` después de los triggers principales. Importa los triggers después de las migraciones de tablas. Estos triggers registran inserciones, cambios y eliminaciones con los valores anteriores/nuevos y el actor de la sesión; los hashes de contraseña y el contenido de los mensajes se excluyen deliberadamente. Los snapshots pueden contener datos personales y quedan disponibles para el rol Auditor. Las modificaciones en cascada por claves foráneas se reflejan en el evento de eliminación del registro padre, ya que MySQL no ejecuta triggers para las filas eliminadas por cascada.

El registro de inicio/cierre de sesión, intentos de autenticación y solicitudes de contacto se escribe directamente en `Auditoria`. Los cambios de datos quedan garantizados por triggers: si falla la escritura del evento de auditoría, también falla la modificación que lo originó.

## Conexión

[`php/conexion.php`](../php/conexion.php) se conecta a `localhost`, usuario `root`, contraseña vacía y base `ecotech`; luego configura `utf8mb4`. Cambia estos valores si tu instalación local utiliza otras credenciales.

## Rutas PHP y tablas

| Archivo o módulo | Tablas |
|---|---|
| `php/registro.php`, `php/login.php` | `Usuarios` |
| `php/admin_usuarios.php` | `Usuarios` |
| `php/admin_empresas.php` (módulo Donantes) | `Donantes` |
| `php/admin_estados.php` | `Equipos.estado_actual` |
| `php/admin_puntos.php` | `PuntosRecoleccion`, `Ciudades` |
| `php/admin_acciones.php` | `Equipos`, `TiposEquipo`, `Donantes`, `Ciudades`, `Diagnosticos`, `Reparaciones`, `Usuarios` |
| `php/admin_chart_*.php` | `Usuarios`, `Equipos`, `TiposEquipo`, `Donantes`, `Ciudades` |
| `php/user_panel_api.php` | `Usuarios`, `Equipos`, `TiposEquipo`, `PuntosRecoleccion`, `Conversaciones`, `Mensajes` |
| `html/tecnico.php` | `Usuarios`, `Equipos`, `TiposEquipo`, `Diagnosticos`, `Reparaciones` |
| `html/auditor_panel.php` | `Usuarios`, `Auditoria` |

El registro público permite crear cuentas `Usuario` o `Vendedor`; las opciones privilegiadas (`Administrador`, `Tecnico`, `Operador`, `Auditor`) solo se asignan desde el panel administrativo. `Tecnico` accede a `html/tecnico.php` para consultar el inventario, registrar diagnósticos y documentar reparaciones. `Vendedor` puede publicar equipos y responder consultas; `Usuario` puede explorar equipos y conversar con vendedores u operadores. `Operador` atiende solicitudes de recogida desde `html/operator_panel.php`. El panel presenta `Donantes` en la sección que anteriormente mostraba empresas y permite mantener los puntos de entrega.

Los hashes SHA-256 de cuentas iniciales se reemplazan por hashes seguros de `password_hash` después del primer inicio de sesión exitoso. Las nuevas cuentas siempre usan `password_hash`.
