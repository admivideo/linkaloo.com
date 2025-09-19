# Endpoints de la aplicación

Los scripts PHP actúan como controladores ligeros que combinan renderizado del lado del servidor con peticiones asíncronas desde el frontend.
La tabla siguiente resume los puntos de entrada más relevantes; los detalles de cada uno aparecen en las secciones posteriores.

| Script / Ruta             | Método(s) | Propósito principal |
|---------------------------|-----------|---------------------|
| `panel.php`               | `GET`, `POST` | Panel privado; crea enlaces y muestra tableros. |
| `share_target.php`        | `GET`, `POST` | Normaliza URLs recibidas desde Web Share Target y redirige al panel. |
| `load_links.php`          | `GET`     | Devuelve enlaces paginados en JSON. |
| `move_link.php`           | `POST`    | Cambia un enlace de tablero. |
| `delete_link.php`         | `POST`    | Elimina un enlace. |
| `editar_link.php`         | `GET`, `POST` | Edita título y nota de una ficha. |
| `tableros.php`            | `GET`, `POST` | Lista tableros y permite crear nuevos. |
| `tablero.php`             | `GET`, `POST` | Gestiona un tablero concreto (renombrar, compartir, borrar). |
| `tablero_publico.php`     | `GET`     | Muestra un tablero público de solo lectura. |
| `login.php` / `register.php` | `GET`, `POST` | Autenticación local con reCAPTCHA. |
| `oauth.php` / `oauth2callback.php` | `GET` | Flujo OAuth con Google. |
| `recuperar_password.php` / `restablecer_password.php` | `GET`, `POST` | Recuperación de contraseña por correo. |

Todos los endpoints que modifican o leen datos privados incluyen `session.php` y exigen que `$_SESSION['user_id']` exista. En caso contrario redirigen al login o devuelven un error 401.

## `panel.php`

- **GET:** renderiza el panel con los tableros del usuario y una primera tanda de enlaces.
- **POST:** crea un nuevo enlace. Campos aceptados:
  - `link_url` (obligatorio): URL del recurso.
  - `link_title` (opcional): título personalizado.
  - `categoria_id` (opcional): identificador de tablero existente.
  - `categoria_nombre` (opcional): si se envía, crea un tablero nuevo y usa su `id`.

El script:
1. Normaliza la URL (`canonicalizeUrl`) y calcula `hash_url` para prevenir duplicados por usuario.
2. Extrae metadatos (`scrapeMetadata`) y descarga imágenes (`saveImageFromUrl`).
3. Limita título y descripción a 50/150 caracteres según el dispositivo (ver `device.php`).
4. Actualiza la marca `modificado_en` del tablero.

## `share_target.php`

- **Métodos:** `POST` (también acepta `GET`).
- **Parámetros:**
  - `url`, `text`, `title` — valores entregados por el navegador al compartir.
- **Comportamiento:** extrae la primera URL válida de los parámetros, comprueba que sea HTTP/HTTPS con `isValidSharedUrl()` y redirige a `panel.php?shared=...`.
- Si no encuentra una URL válida, redirige a `panel.php` sin parámetros.

## `load_links.php`

- **Método:** `GET`
- **Parámetros:**
  - `offset` (`int`, opcional) — desplazamiento para paginación, múltiplo de 18.
  - `cat` (`int` o `"all"`, opcional) — filtra por tablero.
- **Respuesta:** matriz JSON de objetos `{ id, categoria_id, url, titulo, descripcion, imagen, favicon }`.
- **Errores:** responde con `401 Unauthorized` si la sesión no está activa.

## `move_link.php`

- **Método:** `POST`
- **Parámetros:**
  - `id` (`int`, obligatorio) — enlace a mover.
  - `categoria_id` (`int`, obligatorio) — nuevo tablero.
- **Respuesta:** `{ "success": true }` en caso de éxito; `{ "success": false }` si el enlace no pertenece al usuario o la actualización falla.
- Actualiza `modificado_en` en los tableros de origen y destino.

## `delete_link.php`

- **Método:** `POST`
- **Parámetros:**
  - `id` (`int`, obligatorio) — enlace a eliminar.
- **Respuesta:** `{ "success": true }` si se borra el registro y se actualiza el tablero; `false` en caso contrario.

## `editar_link.php`

- **GET:** muestra los datos del enlace identificado por `id` y permite editarlo.
- **POST:** acepta `titulo` (máx. 50 caracteres) y `nota_link` (texto libre). Tras guardar, vuelve a mostrar el formulario con los cambios aplicados.
- Incluye un botón «Borrar» que reutiliza `delete_link.php` vía `fetch`.

## `tableros.php`

- **GET:** lista todos los tableros del usuario con el número de enlaces (`total`) y miniaturas.
- **POST:** crea un tablero nuevo con el campo `board_name`. El nombre se recorta en el servidor y se inserta en `categorias`.
- Desde esta vista se accede a `tablero.php` y al enlace de compartir (`share_token`).

## `tablero.php`

- **GET:** renderiza la información del tablero (`nombre`, `nota`, `share_token`, métricas, enlaces asociados).
- **POST:** puede ejecutar varias acciones según los campos enviados:
  - `delete_board` — borra el tablero y sus enlaces.
  - `update_images` — recorre las fichas, vuelve a extraer metadatos (`scrapeImage`) y actualiza la columna `imagen`.
  - `nombre`, `nota`, `publico` — actualiza los metadatos del tablero; si `publico` está marcado genera o elimina `share_token`.
- Retorna siempre la vista HTML actualizada.

## `tablero_publico.php`

- **Método:** `GET`
- **Parámetros:** `token` (cadena obligatoria) — identifica el tablero compartido.
- **Comportamiento:** busca el tablero por `share_token`, carga sus enlaces y renderiza tarjetas de solo lectura. Si el token no existe responde con 404.
- Incluye botones de compartir (`share-board`, `share-btn`) que usan Web Share API o AddToAny.

## Autenticación y recuperación

### `login.php`

- **GET:** muestra el formulario de acceso.
- **POST:** requiere `email`, `password` y `g-recaptcha-response` cuando reCAPTCHA está configurado.
- Valida credenciales (`password_verify`), renueva la sesión (`session_regenerate_id`) y emite un token «Recordarme» (`linkalooIssueRememberMeToken`).
- Acepta el parámetro `shared` para redirigir al panel con un enlace precargado tras iniciar sesión.

### `register.php`

- **GET:** formulario de registro.
- **POST:** campos `nombre`, `email`, `password`, `g-recaptcha-response` y opcionalmente `shared`.
- Crea el usuario con `password_hash` y redirige a `seleccion_tableros.php`.

### `oauth.php` / `oauth2callback.php`

- `oauth.php?provider=google` genera una URL de autorización y guarda en sesión `oauth_state_token` y el parámetro `shared`.
- `oauth2callback.php` intercambia el `code` por un `access_token`, obtiene datos del usuario (`https://www.googleapis.com/oauth2/v2/userinfo`), inicia sesión o crea la cuenta y redirige al panel o a `seleccion_tableros.php`.

### Recuperación de contraseña

- `recuperar_password.php` acepta `email` y, si existe, inserta un token en `password_resets` y envía un correo con el enlace `restablecer_password.php?token=...`.
- `restablecer_password.php` valida el token vigente y permite establecer una nueva contraseña (`password_hash`). Tras el cambio elimina el token usado.

## Notas adicionales

- Todos los endpoints que devuelven JSON establecen el encabezado `Content-Type: application/json; charset=utf-8`.
- Los scripts que reciben entrada de usuario aplican `trim`, `mb_substr` o validaciones específicas (por ejemplo, `isValidSharedUrl`).
- Si añades un nuevo endpoint, documenta su propósito, parámetros y respuestas en este archivo para mantener la referencia completa.
