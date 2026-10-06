# EcoTech - Documentacion del proyecto

## Resumen

EcoTech es un proyecto web orientado a la gestion responsable de residuos electronicos (RAEE), el reacondicionamiento de equipos y la promocion de una economia circular. El proyecto combina una capa visual construida con HTML, CSS, Bootstrap y JavaScript, con un backend basico en PHP conectado a MySQL.

La aplicacion hoy funciona como un sitio corporativo con:

- Landing page con presentacion del proyecto, servicios, equipo y formulario visual de comentarios.
- Paginas informativas para servicios, nosotros, herramientas, contacto, terminos y productos.
- Flujo de registro y login conectado a base de datos.
- Panel de administracion (`html/admin_panel.php`) reservado a usuarios con rol `Administrador`: resumen con graficos (Chart.js), gestion de usuarios y donantes, inventario de equipos, diagnosticos, reparaciones y estados actuales.
- Portal para usuarios y vendedores con chats persistentes, publicaciones de equipos y puntos de entrega; bandeja para operadores en `html/operator_panel.php`.
- Registro de cambios de base de datos mediante triggers; el auditor puede revisar los valores antes/después desde `html/auditor_panel.php`.

## Tecnologias usadas

- PHP
- MySQL / phpMyAdmin
- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- SweetAlert2
- Bootstrap Icons
- Font Awesome
- Chart.js (panel de administracion, CDN)

## Estructura actual del proyecto

```text
Ecotech/
|-- css/
|   |-- admin.css
|   |-- Contacto.css
|   |-- herramientas.css
|   |-- Login.css
|   |-- nosotros.css
|   |-- Registro.css
|   |-- servicios.css
|   |-- style.css
|   `-- terminos_condiciones.css
|-- docs/
|   |-- BASE_DE_DATOS.md
|   |-- PAGINAS_Y_FLUJO.md
|   `-- VALIDACION.md
|-- html/
|   |-- admin_panel.php
|   |-- contacto.html
|   |-- herramientas.html
|   |-- index.php
|   |-- login_user.php
|   |-- nosotros.html
|   |-- productos.html
|   |-- registro_user.php
|   |-- servicios.html
|   `-- terminos_condiciones.html
|-- images/
|   |-- Imagen1.png
|   |-- Imagen2.png
|   |-- Imagen3.png
|   |-- Imagen4.png
|   |-- Recoleccion.png
|   |-- Reacondicionamiento.png
|   |-- Trazabilidad.png
|   |-- propuesta.png
|   |-- Impacto.png
|   |-- AWS.png
|   |-- Backend.png
|   |-- Fronted.png
|   |-- GitHub.png
|   |-- IA.png
|   |-- Despliegue.png
|   |-- Karoline.jpeg
|   |-- Daniel.jpeg
|   |-- Yofan.jpeg
|   `-- Reci.mp4
|-- Js/
|   |-- admin_panel.js
|   |-- admin_panel_cantidad_*.js
|   |-- alertas.js
|   `-- Valid_checkbox.js
|-- php/
|   |-- admin_acciones.php
|   |-- admin_chart_*.php
|   |-- admin_dashboard_data.php
|   |-- admin_empresas.php
|   |-- admin_estados.php
|   |-- admin_usuarios.php
|   |-- conexion.php
|   |-- login.php
|   `-- registro.php
`-- README.md
```

## Paginas principales

| Ruta | Rol |
|---|---|
| `html/index.php` | Landing principal del proyecto |
| `html/servicios.html` | Descripcion extendida de servicios |
| `html/nosotros.html` | Presentacion, impacto, proceso y CTA |
| `html/herramientas.html` | Stack tecnico y herramientas del equipo |
| `html/contacto.html` | Formulario de contacto con validacion de terminos |
| `html/terminos_condiciones.html` | Contenido legal para el formulario |
| `html/login_user.php` | Inicio de sesion |
| `html/registro_user.php` | Registro de usuarios |
| `html/user_panel.php` | Portal de usuarios/vendedores: catálogo, publicaciones, puntos y chat |
| `html/operator_panel.php` | Bandeja de conversaciones de recogida para operadores |
| `html/tecnico.php` | Panel de técnicos: inventario, diagnósticos y reparaciones |
| `html/auditor_panel.php` | Panel de consulta y filtrado del registro de auditoría |
| `html/productos.html` | Pagina creada pero aun incompleta |
| `html/admin_panel.php` | Panel admin: metricas, graficos, usuarios, donantes, equipos, diagnosticos, reparaciones y estados |

## Backend disponible

| Archivo | Funcion actual |
|---|---|
| `php/conexion.php` | Conexion `mysqli` a la base de datos `ecotech` |
| `php/registro.php` | Registra usuarios en `Usuarios` usando `password_hash` |
| `php/login.php` | Valida credenciales en `Usuarios`, migra hashes SHA-256 iniciales y redirige según rol |
| `php/admin_usuarios.php` | CRUD de usuarios para el panel admin |
| `php/admin_empresas.php` | CRUD de donantes para el panel admin |
| `php/admin_estados.php` | Resume equipos por el valor actual de `estado_actual` |
| `php/admin_acciones.php` | Muestra equipos, diagnosticos y reparaciones |
| `php/admin_dashboard_data.php` | Agrega datos para graficos del resumen |
| `php/admin_chart_*.php` | Consultas por grafico (equipos, ubicaciones, estados, usuarios) |
| `php/admin_puntos.php` | Administración de puntos de entrega verificados |
| `php/user_panel_api.php` | API autenticada para catálogo, chats, publicaciones y puntos |

## JavaScript disponible

| Archivo | Funcion actual |
|---|---|
| `Js/alertas.js` | Muestra alertas visuales de login y registro con SweetAlert2 |
| `Js/Valid_checkbox.js` | Obliga a aceptar terminos antes de enviar el formulario de contacto |
| `Js/admin_panel.js` | Navegacion entre secciones del panel y confirmacion de borrado |
| `Js/admin_panel_cantidad_*.js` | Inicializa graficos Chart.js del resumen admin |

## Como ejecutar el proyecto en local

1. Coloca el proyecto dentro de `htdocs` de XAMPP.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Importa `db/ECOTECH_KTOR_SCHEMA.sql` en MySQL/MariaDB para crear el esquema base de `ecotech`.
4. Importa `db/user_portal_schema.sql` una sola vez después del esquema base para habilitar chats, publicaciones y puntos de entrega.
5. Importa una sola vez `db/auditoria_schema.sql` y después `db/auditoria_triggers.sql`. Si vas a usar las tablas del portal, importa también `db/auditoria_portal_triggers.sql` después de `db/user_portal_schema.sql` y de los triggers principales.
6. Si ya existe una base con datos que debas conservar, respáldala antes de importar el esquema base: ese script elimina y vuelve a crear las tablas.
7. Registra puntos de entrega reales y confirmados desde el panel administrativo antes de mostrarlos a los usuarios.
8. Abre en el navegador la ruta `http://localhost/Ecotech/html/index.php`.

La conexion actual esta definida en `php/conexion.php` con estos valores:

- Servidor: `localhost`
- Usuario: `root`
- Contrasena: vacia
- Base de datos: `ecotech`

El mapeo de rutas PHP a tablas está documentado en [docs/BASE_DE_DATOS.md](docs/BASE_DE_DATOS.md).

## Estado actual del proyecto

Lo que ya esta funcionando:

- Landing page y paginas informativas maquetadas.
- Estilos visuales coherentes con identidad EcoTech.
- Registro de usuarios con hash seguro.
- Login con validacion de password hasheado.
- Alertas visuales para flujos de autenticacion.
- Validacion del checkbox de terminos en contacto.
- Panel de administracion con graficos de resumen, gestion de usuarios y donantes, e informacion de equipos, diagnosticos, reparaciones y estados.

Pendientes importantes detectados en el codigo actual:

- Varias paginas siguen enlazando a `index.html`, pero la entrada real del proyecto es `index.php`.
- `contacto.html` envia el formulario a `php/registro.php`, lo que no corresponde con un flujo de contacto.
- `productos.html` referencia `login.html`, que no existe.
- `productos.html` apunta a `.. /css/productos.css`, pero ese archivo no existe y la ruta tiene un espacio.
- Algunas paginas tienen `lang="en"` aunque el contenido esta en espanol.
- Se mezclan versiones de Bootstrap `5.3.2` y `5.3.8`.

## Documentacion adicional

- [Paginas y flujo](docs/PAGINAS_Y_FLUJO.md)
- [Validacion y estado tecnico](docs/VALIDACION.md)
- [Base de datos](docs/BASE_DE_DATOS.md)

## Ultima revision

- Fecha: 1 de mayo de 2026
- Estado: documentacion alineada con el panel de administracion, modulo de empresas y graficos del resumen.
