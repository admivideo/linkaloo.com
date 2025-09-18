# Estructura y arquitectura del proyecto

Este documento amplía la visión general de la aplicación **linkaloo** y describe cómo se conectan sus piezas principales.

## Tecnologías y dependencias

- **PHP 8** con extensiones PDO, cURL, GD y DOM: se utilizan para conectarse a MySQL, descargar metadatos, procesar imágenes y analizar HTML. Las credenciales y opciones se inicializan en `config.php`.【F:config.php†L1-L38】【F:panel.php†L37-L140】
- **MySQL 8** como base de datos relacional. Todas las tablas se declaran en `database.sql` con codificación `utf8mb4` y claves foráneas para mantener la integridad de usuario, tablero y enlace.【F:database.sql†L1-L45】
- **Node.js 18 + npm** únicamente para tareas de linting (Stylelint).【F:package.json†L1-L17】
- **Feather Icons** y Google Fonts se cargan desde CDN en `header.php`.【F:header.php†L14-L21】

## Distribución de archivos relevantes

- **Raíz del proyecto**: contiene la mayoría de los scripts PHP que actúan como puntos de entrada web (`login.php`, `panel.php`, `tableros.php`, etc.).
- **`assets/`**: JavaScript (`main.js`) y hoja de estilos (`style.css`). El JS se encarga de filtros, formularios modales, compartir y animaciones.【F:assets/main.js†L1-L199】
- **`img/`**: logotipos y favicons cargados en la interfaz.【F:header.php†L22-L48】
- **`fichas/`**: directorio donde se almacenan las imágenes descargadas de cada enlace, organizado por usuario.【F:image_utils.php†L1-L46】
- **`local_favicons/`**: caché local de favicons generado automáticamente.【F:favicon_utils.php†L1-L33】
- **`docs/`**: documentación funcional y técnica.

## Backend PHP

### Controladores principales

- `index.php` redirige al usuario al panel o al login según la sesión.【F:index.php†L1-L10】
- `login.php` y `register.php` gestionan autenticación local con reCAPTCHA v3, manejan el parámetro `shared` y emiten tokens de «recuérdame».【F:login.php†L1-L71】【F:register.php†L1-L69】
- `oauth.php` y `oauth2callback.php` implementan el flujo OAuth 2 con Google (estado, intercambio de código y creación automática de usuarios).【F:oauth.php†L1-L32】【F:oauth2callback.php†L1-L76】
- `panel.php` permite crear nuevas fichas; descarga metadatos, normaliza la URL (`canonicalizeUrl`), guarda favicons/imágenes y evita duplicados con `hash_url`. También prepara los tableros y enlaces mostrados en la vista.【F:panel.php†L25-L154】
- `tableros.php` lista y crea tableros; `tablero.php` permite renombrarlos, añadir notas, compartirlos públicamente y regenerar imágenes.【F:tableros.php†L1-L71】【F:tablero.php†L71-L140】
- `tablero_publico.php` expone un tablero compartido con fichas de solo lectura.【F:tablero_publico.php†L1-L74】
- `editar_link.php` edita título y nota de una ficha existente y ofrece un borrado directo.【F:editar_link.php†L1-L55】
- `seleccion_tableros.php` crea tableros iniciales a partir de una lista predefinida para usuarios recién registrados.【F:seleccion_tableros.php†L1-L68】
- `cpanel.php`, `cambiar_password.php`, `recuperar_password.php` y `restablecer_password.php` cubren la gestión de cuenta y recuperación de credenciales.【F:cpanel.php†L1-L45】【F:cambiar_password.php†L1-L39】【F:recuperar_password.php†L1-L31】【F:restablecer_password.php†L1-L44】

### Gestión de sesiones y seguridad

- `session.php` centraliza la configuración de sesiones: establece una vida útil de 365 días, fuerza cookies `HttpOnly`, `Secure` (si hay HTTPS) y `SameSite=Lax`, y ofrece utilidades para tokens «recuérdame». También valida URLs compartidas antes de reutilizarlas.【F:session.php†L1-L139】
- `linkalooIssueRememberMeToken`, `linkalooAttemptAutoLogin` y funciones auxiliares emiten y validan tokens persistentes guardados en `usuario_tokens`.【F:session.php†L52-L138】【F:database.sql†L29-L45】
- `header.php` incluye cabeceras `Cache-Control` y `Pragma` para evitar contenidos en caché tras cerrar sesión.【F:header.php†L2-L11】

### Servicios auxiliares

- `device.php` detecta navegadores móviles para ajustar límites de texto.【F:device.php†L1-L6】
- `favicon_utils.php` descarga y redimensiona favicons desde Google S2, guardándolos en `local_favicons/` y devolviendo rutas relativas.【F:favicon_utils.php†L1-L33】
- `image_utils.php` descarga imágenes `og:image`, las redimensiona a un ancho máximo de 300 píxeles y las almacena por usuario en `fichas/`.【F:image_utils.php†L1-L46】

## Frontend

### Plantilla base

`header.php` genera la cabecera, carga assets con *cache busting* mediante la marca de tiempo del archivo y construye el menú, incluyendo modales para añadir enlaces cuando el usuario ha iniciado sesión.【F:header.php†L2-L52】【F:header.php†L53-L83】

### Interacciones JavaScript

`assets/main.js` coordina la experiencia del panel: reemplaza iconos Feather, controla el carrusel de tableros, filtra fichas por tablero/búsqueda, abre modales, lanza peticiones `fetch` para mover o borrar enlaces, gestiona el parámetro `shared` y aplica truncado de descripciones. También habilita el botón de compartir (Web Share API o AddToAny como *fallback*).【F:assets/main.js†L1-L199】【F:assets/main.js†L199-L246】

### Hojas de estilo

`assets/style.css` define el diseño responsivo con un grid de tarjetas y estilos para formularios, alertas y menús (no se detalla aquí; revísalo junto con `stylelint`).

## Flujo de datos principal

1. El formulario de `panel.php` envía la URL y el tablero destino; el servidor crea el tablero si se proporcionó un nombre nuevo y obtiene metadatos del enlace (título, descripción, imagen).【F:panel.php†L81-L140】
2. La URL se normaliza (`canonicalizeUrl`), se calcula `hash_url` y se evita duplicar enlaces dentro del mismo usuario.【F:panel.php†L25-L150】
3. Tras insertar el enlace, se actualiza `modificado_en` del tablero para mantener el orden por actividad.【F:panel.php†L122-L154】
4. `load_links.php` y `move_link.php` sirven datos en JSON a `main.js` para refrescar la interfaz sin recargar toda la página.【F:load_links.php†L1-L36】【F:assets/main.js†L79-L168】

## Persistencia de datos

- `usuarios`: credenciales (`nombre`, `email`, `pass_hash`).
- `categorias`: tableros con campos opcionales (`color`, `imagen`, `nota`, `share_token`) y marcas de tiempo de creación/modificación.【F:database.sql†L9-L23】
- `links`: fichas con URL original y canónica, título (50 caracteres), descripción, imagen/favicon, nota y hash único por usuario.【F:database.sql†L25-L38】
- `password_resets`: tokens temporales para recuperación de contraseñas.【F:database.sql†L40-L45】
- `usuario_tokens`: almacena tokens persistentes de «recuérdame» para sesiones prolongadas.【F:database.sql†L29-L45】

## Integraciones externas

- **Google OAuth 2**: configurado mediante `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` para autenticación social.【F:config.php†L24-L28】【F:oauth.php†L1-L32】
- **reCAPTCHA v3**: uso opcional para formularios de login/registro con las variables `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY`.【F:config.php†L31-L34】【F:login.php†L19-L71】
- **Google S2 Favicons** y **AddToAny**: enriquecen la presentación y el compartido de enlaces.【F:favicon_utils.php†L16-L33】【F:assets/main.js†L92-L149】

## Recursos generados y almacenamiento

- `fichas/<usuario>/img_xxx.ext`: imágenes descargadas desde metadatos o favicons almacenadas para cada usuario.【F:image_utils.php†L1-L46】
- `local_favicons/*.png`: favicons recortados a 25×25 px para mostrar junto al título del enlace.【F:favicon_utils.php†L1-L33】

## Consideraciones adicionales

- Las cookies de sesión y tokens persistentes se borran explícitamente en `logout.php` para cerrar todas las sesiones abiertas.【F:logout.php†L1-L17】
- `tablero.php` permite marcar un tablero como público generando `share_token`, que se utiliza como parámetro de lectura en `tablero_publico.php`.【F:tablero.php†L71-L140】【F:tablero_publico.php†L1-L74】
- `assets/main.js` guarda el desplazamiento horizontal del carrusel en `sessionStorage` para mejorar la UX entre recargas.【F:assets/main.js†L17-L48】
