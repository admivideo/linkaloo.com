# Endpoints de la aplicación

Los scripts PHP de **linkaloo** actúan como endpoints HTTP. Algunos devuelven HTML (formularios/páginas) y otros responden en JSON para peticiones AJAX. Todos los endpoints autenticados comprueban la sesión mediante `session.php` y redirigen o devuelven `401` si el usuario no está identificado.【F:session.php†L1-L139】【F:load_links.php†L1-L12】

## Resumen rápido

| Script | Método(s) | Autenticación | Respuesta | Descripción |
|--------|-----------|---------------|-----------|-------------|
| `panel.php` | GET/POST | Sí | HTML/redirect | Muestra panel y crea enlaces.【F:panel.php†L1-L156】 |
| `load_links.php` | GET | Sí | JSON | Devuelve fichas paginadas.【F:load_links.php†L1-L36】 |
| `move_link.php` | POST | Sí | JSON | Cambia un enlace de tablero.【F:move_link.php†L1-L25】 |
| `delete_link.php` | POST | Sí | JSON | Borra un enlace existente.【F:delete_link.php†L1-L24】 |
| `editar_link.php` | GET/POST | Sí | HTML | Edita título y nota de una ficha.【F:editar_link.php†L1-L55】 |
| `tableros.php` | GET/POST | Sí | HTML | Lista/crea tableros.【F:tableros.php†L1-L71】 |
| `tablero.php` | GET/POST | Sí | HTML | Administra un tablero concreto.【F:tablero.php†L1-L143】 |
| `tablero_publico.php` | GET | No | HTML | Vista pública de tableros compartidos.【F:tablero_publico.php†L1-L74】 |
| `login.php` | GET/POST | No | HTML/redirect | Inicia sesión local con reCAPTCHA.【F:login.php†L1-L71】 |
| `register.php` | GET/POST | No | HTML/redirect | Registra usuarios y lanza selección de tableros.【F:register.php†L1-L69】 |
| `oauth.php` / `oauth2callback.php` | GET | No | Redirect/HTML | Flujo OAuth 2 con Google.【F:oauth.php†L1-L32】【F:oauth2callback.php†L1-L76】 |
| `logout.php` | GET | Sí | Redirect | Cierra sesión y limpia cookies.【F:logout.php†L1-L17】 |
| `recuperar_password.php` | GET/POST | No | HTML | Solicita restablecimiento de contraseña.【F:recuperar_password.php†L1-L31】 |
| `restablecer_password.php` | GET/POST | No | HTML | Define nueva contraseña con token válido.【F:restablecer_password.php†L1-L44】 |
| `seleccion_tableros.php` | GET/POST | Sí | HTML/redirect | Crea tableros a partir de intereses iniciales.【F:seleccion_tableros.php†L1-L68】 |
| `cpanel.php` | GET/POST | Sí | HTML | Actualiza nombre/email del usuario.【F:cpanel.php†L1-L45】 |
| `cambiar_password.php` | GET/POST | Sí | HTML | Cambia la contraseña autenticada.【F:cambiar_password.php†L1-L39】 |

## Operaciones con enlaces

### `panel.php`
- **GET**: Renderiza el panel con tableros y fichas del usuario.
- **POST**: Acepta `link_url`, `link_title`, `categoria_id` y opcionalmente `categoria_nombre`. Si se envía `categoria_nombre`, crea el tablero antes de guardar el enlace. Descarga metadatos, guarda imágenes, calcula `hash_url` y evita duplicados antes de insertar en `links`.
- **Respuesta**: redirige al propio `panel.php` tras procesar el formulario.【F:panel.php†L81-L154】

### `load_links.php`
- **Método**: `GET`.
- **Parámetros**:
  - `offset` (entero, opcional). Límite fijo de 18 registros.
  - `cat` (entero o `all`). Filtra por tablero.
- **Respuesta**: JSON con campos `id`, `categoria_id`, `url`, `titulo`, `descripcion`, `imagen`, `favicon`, con textos truncados según el dispositivo.【F:load_links.php†L1-L36】

### `move_link.php`
- **Método**: `POST` (`Content-Type: application/x-www-form-urlencoded`).
- **Parámetros**: `id` (entero), `categoria_id` (entero destino).
- **Respuesta**: JSON `{ "success": true|false }`. Actualiza la fecha `modificado_en` en el tablero origen y destino cuando la operación se completa.【F:move_link.php†L1-L25】

### `delete_link.php`
- **Método**: `POST`.
- **Parámetros**: `id` (entero).
- **Respuesta**: JSON `{ "success": true|false }`. Si elimina un enlace, también actualiza `modificado_en` del tablero asociado.【F:delete_link.php†L1-L24】

### `editar_link.php`
- **GET**: Muestra formulario con datos del enlace (incluido favicon y fecha).【F:editar_link.php†L1-L55】
- **POST**: Acepta `titulo` y `nota_link`, actualiza la fila de `links` y recarga la misma página con los cambios aplicados.【F:editar_link.php†L18-L34】

## Gestión de tableros

### `tableros.php`
- **GET**: Lista tableros del usuario con recuento de enlaces y accesos a compartir/editar.【F:tableros.php†L32-L71】
- **POST**: Campo `board_name`. Crea un nuevo registro en `categorias` y vuelve a renderizar la página.【F:tableros.php†L11-L28】

### `tablero.php`
- **GET**: Muestra la ficha de un tablero específico, sus estadísticas y un mosaico de imágenes recientes.【F:tablero.php†L71-L143】
- **POST**: Acepta varias acciones mutuamente excluyentes:
  - `delete_board`: elimina el tablero y sus enlaces asociados.【F:tablero.php†L58-L67】
  - `update_images`: recorre enlaces para re-scrapear `og:image` o favicons y guardarlos localmente.【F:tablero.php†L34-L57】
  - Actualización general: `nombre`, `nota`, `publico` (checkbox). Genera o elimina `share_token` según la casilla y actualiza la nota/nombre.【F:tablero.php†L83-L123】

### `seleccion_tableros.php`
- **GET**: Presenta lista de intereses iniciales.
- **POST**: Recoge `boards[]` y crea cada nombre como fila en `categorias`. Después redirige a `panel.php` conservando el parámetro `shared` si existía.【F:seleccion_tableros.php†L1-L68】

## Autenticación y gestión de cuenta

### `login.php`
- **GET**: Muestra formulario de inicio de sesión con enlace a registro y recuperación de contraseña. Inserta un campo oculto `shared` cuando se llega desde una URL compartida.【F:login.php†L1-L71】
- **POST**: Campos `email`, `password`, `g-recaptcha-response`, `shared`. Valida reCAPTCHA (si hay claves), busca al usuario por email, verifica la contraseña y emite token persistente. Redirige a `panel.php` (con `shared` si aplica).【F:login.php†L19-L52】

### `register.php`
- **GET**: Formulario de registro con enlaces a login y recuperación.【F:register.php†L1-L69】
- **POST**: Campos `nombre`, `email`, `password`, `g-recaptcha-response`, `shared`. Valida reCAPTCHA, comprueba duplicados, crea usuario, inicia sesión y redirige a `seleccion_tableros.php` conservando `shared` si existe.【F:register.php†L15-L60】

### `oauth.php` y `oauth2callback.php`
- `oauth.php` genera un `state` aleatorio, lo guarda en sesión junto con el parámetro `shared` y redirige a Google con `scope` `openid email profile`.【F:oauth.php†L1-L32】
- `oauth2callback.php` intercambia el `code` por tokens, obtiene la información del usuario, crea el registro si no existe, emite token persistente y redirige al panel o a `seleccion_tableros.php` según corresponda.【F:oauth2callback.php†L1-L76】

### `cpanel.php` y `cambiar_password.php`
- `cpanel.php` (GET) muestra un formulario con nombre y email; (POST) actualiza esos campos en `usuarios` y refleja el cambio en sesión.【F:cpanel.php†L1-L45】
- `cambiar_password.php` (POST) requiere la contraseña actual y la nueva; al validar la primera actualiza el `pass_hash`.【F:cambiar_password.php†L1-L39】

### `logout.php`
- Elimina tokens persistentes (`usuario_tokens` y cookie `linkaloo_remember`), destruye la sesión y redirige a `login.php`.【F:logout.php†L1-L17】【F:session.php†L90-L138】

## Recuperación de contraseña

### `recuperar_password.php`
- **GET**: Formulario que solicita el email del usuario.【F:recuperar_password.php†L1-L31】
- **POST**: Inserta un token en `password_resets` (válido durante 1 hora) y envía un correo con el enlace `restablecer_password.php?token=...`. Siempre muestra un mensaje genérico para evitar revelar si el correo existe.【F:recuperar_password.php†L1-L23】

### `restablecer_password.php`
- **GET**: Verifica que el token exista y no haya expirado. Si es válido, muestra un formulario para definir la nueva contraseña.【F:restablecer_password.php†L1-L28】
- **POST**: Actualiza la contraseña en `usuarios`, borra el token usado y confirma el éxito. Si el token no es válido, informa del error.【F:restablecer_password.php†L12-L44】

## Tableros públicos

### `tablero_publico.php`
- **Método**: `GET`.
- **Parámetro**: `token` (cadena generada por `tablero.php`).
- **Respuesta**: HTML con título, nota y fichas del tablero compartido. Las tarjetas incluyen botón de compartir (Web Share/AddToAny) pero no permiten editar ni eliminar contenido.【F:tablero_publico.php†L1-L74】【F:assets/main.js†L92-L149】

## Utilidades compartidas

- Todos los formularios utilizan `session.php` para configurar cookies de sesión (`SameSite=Lax`, `HttpOnly`, `Secure` si procede).【F:session.php†L1-L29】
- `device.php` se usa en endpoints que adaptan longitud de descripción (`panel.php`, `load_links.php`, `tablero_publico.php`).【F:panel.php†L14-L22】【F:load_links.php†L1-L36】【F:tablero_publico.php†L1-L44】
- `favicon_utils.php` e `image_utils.php` son reutilizados en múltiples scripts para mantener consistencia visual de las fichas.【F:favicon_utils.php†L1-L33】【F:image_utils.php†L1-L46】
