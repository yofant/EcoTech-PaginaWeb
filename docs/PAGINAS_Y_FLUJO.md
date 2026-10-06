# Paginas y flujo de navegacion

## Entrada principal

La pagina de entrada real del proyecto es `html/index.php`.

Aunque varias vistas enlazan al logo o al menu usando `index.html`, en el repositorio actual no existe ese archivo. Para documentacion y pruebas, debe considerarse `index.php` como punto de inicio.

## Mapa de paginas en `html/`

### `index.php`

- Landing principal de EcoTech.
- Incluye navbar, carrusel, bloque "Quienes somos", seccion de servicios, equipo, formulario visual de comentarios y footer.
- Usa `../css/style.css`.
- Usa Bootstrap `5.3.8` y Bootstrap Icons.
- Enlaza a `login_user.php`, `servicios.html`, `productos.html`, `contacto.html` y `nosotros.html`.
- El formulario inferior es solo visual: su `action` actual es `#`.

### `servicios.html`

- Pagina informativa con tres bloques extensos:
- Recoleccion de residuos electronicos.
- Reacondicionamiento tecnologico.
- Trazabilidad y seguimiento.
- Usa `../css/servicios.css`.
- Navbar actual: Inicio, Nosotros, Herramientas, Contacto.
- Tiene `lang="es"`.

### `nosotros.html`

- Presenta la historia del proyecto, mision, vision, valores, propuesta, impacto, proceso y metricas.
- Incluye CTA hacia `contacto.html` y `herramientas.html`.
- Usa `../css/nosotros.css`.
- Usa Bootstrap `5.3.8` y Bootstrap Icons.
- Actualmente tiene `lang="en"` aunque el contenido esta en espanol.

### `herramientas.html`

- Describe el stack de trabajo y herramientas del equipo:
- Frontend.
- Backend.
- AWS.
- GitHub.
- IA y entorno de desarrollo.
- Herramientas de despliegue.
- Usa `../css/herramientas.css`.
- Incluye un boton de retorno hacia `index.html`.
- Actualmente tiene `lang="en"` aunque el contenido esta en espanol.

### `contacto.html`

- Muestra un formulario corto con nombre, apellido y correo.
- Incluye checkbox de terminos y enlace a `terminos_condiciones.html`.
- Carga `../Js/Valid_checkbox.js`.
- Usa `../css/Contacto.css`.
- El formulario envia a `../php/registro.php`, lo que hoy no corresponde con un flujo de contacto real.
- Actualmente tiene `lang="en"` aunque el contenido esta en espanol.

### `terminos_condiciones.html`

- Contiene el texto legal asociado al formulario de contacto.
- Boton "Volver" hacia `contacto.html`.
- Usa `../css/terminos_condiciones.css`.
- Actualmente tiene `lang="en"` aunque el contenido esta en espanol.

### `login_user.php`

- Formulario de inicio de sesion.
- Envia por `POST` a `../php/login.php`.
- Carga `../Js/alertas.js` y SweetAlert2 para mostrar estados como `success`, `error_pass`, `error_user` y `error_data`.
- Usa `../css/Login.css`.
- Enlaza a `registro_user.php`.
- Actualmente tiene `lang="en"` aunque el contenido esta en espanol.

### `registro_user.php`

- Formulario de registro de usuarios.
- Envia por `POST` a `../php/registro.php`.
- Solicita nombre, apellido, correo, telefono, contrasena y un perfil público `Usuario` o `Vendedor`.
- Los usuarios acceden a `html/user_panel.php`; los vendedores también pueden publicar equipos.
- El rol `Operador` accede a `html/operator_panel.php` para responder solicitudes de recogida.
- Carga `../Js/alertas.js` y SweetAlert2.
- Usa `../css/Registro.css`.
- El enlace inferior apunta a `login.html`, pero el archivo correcto hoy es `login_user.php`.
- Actualmente tiene `lang="en"` aunque el contenido esta en espanol.

### `productos.html`

- Existe en el arbol del proyecto, pero esta incompleta.
- Solo contiene navbar y referencias de estilos.
- Enlaza a `login.html`, archivo que no existe.
- Intenta cargar `.. /css/productos.css`, pero ese archivo no existe y la ruta contiene un espacio.
- Actualmente tiene `lang="en"`.

### `admin_panel.php`

- Panel de administracion accesible solo si la sesion existe y el rol del usuario es `admin` (comprobacion en PHP al inicio del archivo).
- Incluye `../php/conexion.php` y modulos en `../php/admin_usuarios.php`, `../php/admin_empresas.php`, `../php/admin_estados.php`, `../php/admin_acciones.php` y `../php/admin_dashboard_data.php`.
- Navegacion lateral con secciones enlazadas por hash y parametro `?panel=`: `resumen`, `usuarios`, `empresas`, `acciones`, `estado`. El script `../Js/admin_panel.js` sincroniza la URL al cambiar de seccion.
- **Resumen:** tarjetas metricas y cuadros con graficos Chart.js (equipos, ubicaciones, estados, usuarios). Los datos se exponen en `window.ADMIN_CHART_DATA` generado desde PHP.
- **Usuarios:** formulario y tabla para crear, editar y eliminar usuarios (`admin_usuarios.php`).
- **Empresas:** formulario y tabla para empresas aliadas: `nombre`, `nit`, `direccion`, `telefono`, `correo_contacto`, `fecha_registro` (alta opcional; si se omite, se usa fecha/hora actual). `id_empresa` es autonumerico. Logica en `admin_empresas.php` con acciones `empresa_crud_action=save_empresa`, `empresa_action=edit` y `empresa_action=delete`.
- **Acciones:** generacion de reportes y vistas resumidas de activos e historial (`admin_acciones.php`).
- **Estado:** CRUD de estados de equipos (`admin_estados.php`).
- Estilos en `../css/admin.css`; Chart.js se carga por CDN al final de la pagina.

### `tecnico.php`

- Accesible únicamente con una cuenta activa de rol `Tecnico`.
- Presenta el inventario, los estados de los equipos, el resumen de diagnósticos y la actividad del técnico.
- Permite registrar diagnósticos con el requisito de reparación y costo estimado; actualiza el estado del equipo.
- Permite registrar reparaciones con repuestos, costo y estado de finalización; guarda los cambios del equipo y del historial de reparación en una transacción.
- La ruta `tecnico_panel.php` redirige a `tecnico.php` por compatibilidad.

### `auditor_panel.php`

- Accesible únicamente con una cuenta activa de rol `Auditor`.
- Presenta el registro `Auditoria` en modo de consulta, con filtros por tabla, operación, rango de fechas y texto; los resultados están paginados y pueden mostrar los valores registrados antes/después.
- El panel no modifica ni crea eventos: muestra la trazabilidad que ya existe en la base de datos.

## Flujo actual de autenticacion

### Registro

1. El usuario entra a `html/registro_user.php`.
2. Completa el formulario.
3. El formulario envia `POST` a `php/registro.php`.
4. El backend genera un hash con `password_hash`.
5. Si el registro sale bien, redirige a `registro_user.php?status=user_create`.
6. Si falla, redirige con `status=failed`.

### Login

1. El usuario entra a `html/login_user.php`.
2. Completa correo y contrasena.
3. El formulario envia `POST` a `php/login.php`.
4. El backend consulta la tabla `Usuarios` por `email`.
5. Valida la contrasena con `password_verify` y actualiza los hashes SHA-256 heredados al primer inicio exitoso.
6. Redirige al panel correspondiente a administradores, usuarios/vendedores u operadores.

## Portal de usuario, vendedor y operador

1. Los usuarios consultan los equipos publicados, los puntos activos y sus conversaciones.
2. Una consulta a un vendedor queda vinculada al equipo seleccionado y solo se puede abrir si sigue publicado y pertenece a ese vendedor.
3. Los mensajes se guardan en `Mensajes` y se consultan periódicamente para actualizar el chat.
4. El usuario puede iniciar una conversación con un operador para coordinar la recogida; los operadores responden desde su bandeja.
5. Los vendedores publican equipos usando los tipos existentes en `TiposEquipo`.
6. Un administrador registra ubicaciones verificadas desde la sección Puntos de entrega.

## Flujo actual de contacto

1. El usuario abre `html/contacto.html`.
2. Marca el checkbox de terminos.
3. `Js/Valid_checkbox.js` bloquea el envio si no acepta los terminos.
4. Si el formulario se envia, hoy termina en `php/registro.php`.

Observacion: este flujo no esta terminado. El backend espera campos de registro de usuario y no un mensaje de contacto.

## Coherencia de navegacion

El sitio no usa un menu unico en todas las paginas. El contenido actual muestra estos patrones:

- `index.php` incluye Login, Servicios, Productos, Contacto y Nosotros.
- `servicios.html` incluye Inicio, Nosotros, Herramientas y Contacto.
- `nosotros.html` incluye Inicio y Servicios.
- `herramientas.html` incluye Inicio, Servicios y Contacto.
- `contacto.html` incluye Inicio, Servicios y Nosotros.
- `login_user.php` y `registro_user.php` incluyen Inicio, Servicios, Contacto y Nosotros.

## Recomendaciones de documentacion para futuras iteraciones

- Unificar todos los enlaces de retorno a `index.php`.
- Definir si `productos.html` seguira en el alcance del proyecto o si debe ocultarse.
- Separar el formulario de contacto del flujo de registro.
- Homogeneizar `lang`, navbar y version de Bootstrap.

## Ultima revision

- Fecha: 1 de mayo de 2026
- Cambios: documentado el panel de administracion y el flujo del modulo de empresas.
