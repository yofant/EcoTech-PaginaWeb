# Validacion y estado tecnico

## Resumen

Esta revision se hizo comparando la documentacion existente contra los archivos reales del proyecto en `html/`, `php/`, `Js/`, `css/` e `images/`.

El objetivo de este documento es dejar claro que ya funciona, que esta incompleto y que puntos tecnicos conviene corregir antes de una entrega o despliegue.

## Hallazgos confirmados

### En buen estado

- `php/registro.php` registra usuarios usando `password_hash`.
- `php/login.php` valida credenciales con `password_verify`.
- `Js/alertas.js` centraliza estados de login y registro con SweetAlert2.
- `Js/Valid_checkbox.js` valida el checkbox de terminos en el formulario de contacto.
- La estructura general del frontend esta organizada por pagina y por hoja de estilos.
- Panel `html/admin_panel.php` con sesion y rol `Administrador`, modulos PHP para usuarios, donantes, estados actuales, equipos, diagnosticos y reparaciones.
- Grafico de ubicaciones en el resumen admin: el dataset de Chart.js incluye la propiedad `data` con los totales por etiqueta.
- Grafico de estados en el resumen admin: la consulta agrupa por el campo `Equipos.estado_actual`.
- Modulo de donantes en el admin: usa la tabla `Donantes` y los campos de contacto del esquema nuevo.

### Inconsistencias funcionales

- `html/contacto.html` envia el formulario a `../php/registro.php`.
- `php/registro.php` espera `nombre`, `primer_apellido`, `correo`, `telefono` y `contrasena`; asigna el rol `Operador`.
- `html/contacto.html` solo envia `nombre`, `primer_apellido` y `correo`.
- Resultado: el flujo de contacto no coincide con el backend y no representa una funcionalidad terminada.

### Inconsistencias de rutas

- La entrada real del proyecto es `html/index.php`, pero varias paginas siguen enlazando a `index.html`.
- `html/registro_user.php` enlaza a `login.html`, pero el archivo valido es `login_user.php`.
- `html/productos.html` enlaza a `login.html`, que no existe.
- `html/herramientas.html` tiene un boton de retorno a `index.html` en lugar de `index.php`.

### Inconsistencias de recursos

- `html/productos.html` intenta cargar `.. /css/productos.css`.
- No existe `css/productos.css` en el proyecto.
- La ruta contiene un espacio entre `..` y `/css`, por lo que aun existiendo el archivo la referencia seguiria mal formada.

### Inconsistencias de idioma y metadatos

- `index.php` y `servicios.html` usan `lang="es"`.
- `contacto.html`, `herramientas.html`, `login_user.php`, `nosotros.html`, `productos.html`, `registro_user.php` y `terminos_condiciones.html` usan `lang="en"` con contenido en espanol.

### Dependencias frontend

- `index.php`, `servicios.html`, `nosotros.html` y `herramientas.html` usan Bootstrap `5.3.8`.
- `contacto.html`, `login_user.php`, `registro_user.php` y `terminos_condiciones.html` usan Bootstrap `5.3.2`.
- El proyecto mezcla Bootstrap Icons y Font Awesome segun la pagina.

## Riesgos actuales

### Riesgo alto

- El formulario de contacto no tiene backend propio.
- Las rutas rotas (`index.html`, `login.html`) pueden cortar la navegacion.
- `productos.html` da imagen de pagina incompleta si se expone en menu.

### Riesgo medio

- Las credenciales de base de datos estan hardcodeadas en `php/conexion.php`.
- No existe en el repositorio un archivo SQL con el esquema de la base de datos.
- Hay dependencias CDN externas, por lo que el proyecto depende de conexion a internet para estilos, iconos y alertas.

### Riesgo bajo

- El formulario de comentarios dentro de `index.php` es solo visual.
- Algunas paginas no comparten el mismo navbar ni el mismo orden de enlaces.

## Recomendaciones prioritarias

1. Crear un `php/contacto.php` o ajustar el `action` del formulario de contacto segun el alcance real.
2. Corregir todas las referencias a `index.html` y `login.html`.
3. Definir si `productos.html` se completara o se sacara temporalmente del menu.
4. Unificar `lang="es"` en las vistas con contenido en espanol.
5. Estandarizar la version de Bootstrap usada en todas las paginas.
6. Mover la configuracion sensible de `php/conexion.php` a una estrategia mas segura para despliegue.
7. Mantener documentada la estructura minima de base de datos en `docs/BASE_DE_DATOS.md`.

## Checklist rapido

- [x] Registro con password hash
- [x] Login con password verify
- [x] Validacion cliente para terminos
- [ ] Flujo de contacto funcional
- [ ] Navegacion sin rutas rotas
- [ ] Pagina de productos terminada
- [ ] Idioma unificado en todas las vistas
- [ ] Esquema SQL incluido en el repositorio

## Ultima revision

- Fecha: 1 de mayo de 2026
- Estado: incorporadas notas del panel de administracion, graficos y empresas.
