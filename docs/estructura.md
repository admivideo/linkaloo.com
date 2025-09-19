# Estructura y arquitectura del proyecto

Este documento ofrece una visión completa de cómo está organizada la aplicación **linkaloo**, qué componentes intervienen y cómo se comunican.

## Pila tecnológica

- **Backend:** PHP 8 con `PDO` para el acceso a MySQL 8.
- **Frontend:** HTML renderizado por PHP, estilos en `assets/style.css` y JavaScript en `assets/main.js`.
- **Persistencia:** Base de datos MySQL (`database.sql`) y almacenamiento de archivos en disco (`fichas/` y `local_favicons/`).
- **Dependencias externas:** Google OAuth 2.0 y reCAPTCHA v3 para autenticación y protección contra bots.

## Estructura de directorios relevantes

| Ruta                         | Descripción |
|------------------------------|-------------|
| `index.php`                  | Página de bienvenida con acceso al formulario de login. |
| `login.php`, `register.php`  | Formularios de autenticación local con reCAPTCHA y enlaces a Google OAuth. |
| `panel.php`                  | Panel principal donde se listan tableros y enlaces, y desde donde se crean nuevas fichas. |
| `tableros.php`, `tablero.php`| Administración de tableros: creación, edición, compartir públicamente y actualización masiva de imágenes. |
| `tablero_publico.php`        | Vista de solo lectura accesible mediante `share_token`. |
| `seleccion_tableros.php`     | Asistente opcional que crea tableros sugeridos tras el registro. |
| `load_links.php`, `move_link.php`, `delete_link.php`, `editar_link.php` | Endpoints y páginas auxiliares para operaciones AJAX y edición de enlaces. |
| `session.php`                | Arranque de sesión, cookie «Recordarme» y validaciones de URL compartidas. |
| `favicon_utils.php`, `image_utils.php` | Descarga y normalización de imágenes asociadas a enlaces. |
| `assets/`                    | Recursos frontales (`main.js`, `style.css`, iconos Feather). |
| `fichas/`                    | Carpeta donde se guardan las imágenes locales de las fichas por usuario. |
| `local_favicons/`            | Favicons descargados y redimensionados para mostrarse junto a cada enlace. |
| `docs/`                      | Documentación técnica (este directorio). |

## Flujo de navegación y datos

1. **Autenticación:**
   - `index.php` redirige a `login.php` o `register.php` según la acción.
   - Ambos formularios incluyen reCAPTCHA v3 y pueden recibir el parámetro opcional `shared` para precargar un enlace tras el login.
   - Google OAuth se inicia desde `oauth.php` y concluye en `oauth2callback.php`, que crea usuarios nuevos si no existen.

2. **Panel de usuario (`panel.php`):**
   - Requiere sesión activa (`session.php` redirige al login si no existe `$_SESSION['user_id']`).
   - Permite crear un enlace enviando un `POST` que dispara `scrapeMetadata()` para recuperar título, descripción e imagen, evita duplicados con un hash SHA-1 (`hash_url`) y almacena los datos en `links`.
   - Muestra los tableros del usuario y renderiza las tarjetas iniciales. `assets/main.js` se encarga de aplicar filtros, búsqueda y compartir.

3. **Operaciones asíncronas:**
   - `load_links.php` devuelve enlaces paginados (18 por petición) en JSON.
   - `move_link.php` y `delete_link.php` actualizan o eliminan enlaces mediante `fetch` desde el frontend y responden con objetos `{ success: bool }`.
   - `editar_link.php` presenta un formulario para actualizar notas y título de una ficha concreta.

4. **Gestión de tableros:**
   - `tableros.php` lista todos los tableros con métricas básicas y permite crear nuevos mediante un formulario `POST`.
   - `tablero.php` permite renombrar el tablero, añadir notas, activar la compartición pública (`share_token`) y ejecutar una actualización masiva de imágenes (`update_images`). También ofrece la opción de borrar el tablero y todas sus fichas.
   - Los tableros marcados como públicos generan una URL estable (`tablero_publico.php?token=...`) que muestra sus enlaces con la misma estética del panel pero en modo lectura.

5. **Recuperación de contraseñas:**
   - `recuperar_password.php` genera un token temporal en `password_resets` y envía el enlace por correo.
   - `restablecer_password.php` valida el token, actualiza el `pass_hash` y elimina el registro usado.

## Sesiones y seguridad

- `session.php` ajusta la duración de la sesión (`LINKALOO_SESSION_LIFETIME`), aplica `SameSite=Lax` y fuerza `httponly`.
- Implementa un mecanismo «Recordarme»: guarda un token `selector:validator` en la cookie `linkaloo_remember`, lo valida contra la tabla `usuario_tokens` y lo renueva automáticamente.
- `isValidSharedUrl()` comprueba que el parámetro `shared` sea una URL HTTP/HTTPS válida antes de reutilizarlo tras el login o el registro.
- Los scripts que modifican datos verifican la pertenencia del recurso (`usuario_id`) antes de ejecutar `UPDATE` o `DELETE`.
- Las operaciones que exponen datos (`tablero_publico.php`) solo aceptan tokens generados mediante `bin2hex(random_bytes(16))`.

## Extracción y almacenamiento de metadatos

- `panel.php` y `tablero.php` usan cURL para descargar el HTML de las URLs, extraer metadatos Open Graph/Twitter (`scrapeMetadata()` y `scrapeImage()`), normalizar la codificación y limitar la longitud de los textos según el dispositivo (`device.php`).
- Cuando se encuentra una imagen remota, `image_utils.php` la descarga, la redimensiona (máx. 300 px de ancho) y la guarda en `fichas/<usuario>/`.
- Si no hay imagen disponible, `favicon_utils.php` solicita un favicon a Google (`https://www.google.com/s2/favicons`), lo redimensiona a 25×25 px y lo guarda en `local_favicons/` para futuras visitas.

## Frontend y experiencia de usuario

- `assets/main.js` inicializa iconos Feather, gestiona el carrusel horizontal de tableros, controla el buscador inline y maneja el modal para agregar enlaces.
- Los botones «Compartir» utilizan la Web Share API cuando está disponible; como alternativa abren AddToAny.
- El script aplica animaciones progresivas (`IntersectionObserver`) y recorta descripciones largas en función del ancho de pantalla.
- Desde el panel es posible abrir el modal de creación con un enlace compartido (`?shared=<URL>`); el JavaScript valida el parámetro y precarga el formulario. El archivo `share_target.php` permite que una PWA instalada reciba URLs desde el menú «Compartir» del sistema.

## Archivos estáticos y layout

- `header.php` contiene la cabecera HTML común (metaetiquetas, enlaces a CSS/JS) y se incluye en todas las páginas.
- Las páginas legales (`cookies.php`, `politica_privacidad.php`, etc.) son enlaces estáticos accesibles desde el menú de configuración.

Con esta información puedes localizar rápidamente el código responsable de cada función y comprender cómo fluye la información entre el frontend, PHP y la base de datos.
