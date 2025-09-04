# Estructura y arquitectura del proyecto

Este documento ofrece una visión general de la aplicación **linkaloo** y de cómo está organizada.

## Estructura de directorios

- `index.php`: punto de entrada público con el formulario de acceso.
- `login.php`, `register.php`, `logout.php`: gestión de autenticación.
- `panel.php`: panel principal que muestra los tableros y enlaces del usuario.
- `tablero.php`, `tableros.php`: creación y administración de tableros.
- `assets/main.js`: código JavaScript que maneja la carga de enlaces, filtros y acciones en las tarjetas.
- `assets/style.css`: hoja de estilos principal.
- `load_links.php`, `move_link.php`, `editar_link.php`, `delete_link.php`: endpoints AJAX para operar sobre los enlaces.
- `config.php`: configuración de la base de datos y credenciales opcionales de OAuth.
- `img/`: logotipos y favicons.

## Esquema de la base de datos

La base de datos se define en `database.sql` y utiliza codificación `utf8mb4`.

- **usuarios**: almacena las credenciales básicas (`id`, `nombre`, `email`, `pass_hash`).
- **categorias**: representa los tableros personales del usuario. Incluye nombre, color, imagen, nota y token de compartición.
- **links**: registros individuales de cada enlace guardado. Contiene URL, título, descripción, favicon y referencias al usuario y a la categoría.

## Flujo principal

1. Un usuario se registra o inicia sesión.
2. Desde el panel puede crear tableros y agregar enlaces mediante el botón “+”.
3. Cada tarjeta de enlace permite mover, buscar, compartir o eliminar el recurso.
4. El desplazamiento infinito carga enlaces adicionales a partir de la ficha 18 para optimizar el rendimiento.

## Configuración

- Crea la base de datos ejecutando `database.sql`.
- Define las credenciales en `config.php`.
- Para el login con Google, configura las variables `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI`.
- Registra `http://localhost:8000/oauth.php?provider=google` (o su URL de producción) como URI de redirección autorizada; corresponde al endpoint de backend que procesa el callback OAuth.

