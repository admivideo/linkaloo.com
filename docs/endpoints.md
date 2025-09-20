# Endpoints de la API y formularios

Este documento describe los scripts PHP que sirven como puntos de entrada para
operaciones asíncronas del cliente o procesan formularios críticos. Salvo que se
indique lo contrario, todos requieren que el usuario tenga una sesión activa; de
lo contrario devuelven un código 401 o redirigen a `login.php`.

## Endpoints JSON del panel

### `load_links.php`

Obtiene un lote de enlaces del usuario.

- **Método:** `GET`
- **Parámetros:**
  - `offset` (entero, opcional): número de enlaces ya cargados. Por defecto `0`.
  - `cat` (entero o `"all"`, opcional): identificador del tablero. Si se omite o
    vale `all`, devuelve enlaces de todos los tableros.
- **Respuesta:** matriz JSON con objetos que incluyen `id`, `categoria_id`, `url`,
  `titulo`, `descripcion`, `imagen` y un `favicon` calculado en el servidor según
  el dominio.

### `move_link.php`

Mueve un enlace a otro tablero y actualiza la fecha de modificación en ambas
categorías.

- **Método:** `POST`
- **Parámetros:**
  - `id` (entero, obligatorio): identificador del enlace.
  - `categoria_id` (entero, obligatorio): identificador del tablero destino.
- **Respuesta:** objeto JSON `{ "success": true }` si la operación se realiza
  correctamente, en caso contrario `{ "success": false }`.

### `delete_link.php`

Elimina un enlace existente.

- **Método:** `POST`
- **Parámetros:**
  - `id` (entero, obligatorio): identificador del enlace a borrar.
- **Respuesta:** objeto JSON con `success: true` si se borra el enlace; en caso
  contrario `success: false`.

## Formularios y rutas relevantes

### `panel.php`

Procesa el formulario "Guardar link". Admite los campos `link_url`, `link_title`
(opcional), `categoria_id` y `categoria_nombre` (para crear un tablero nuevo).
Normaliza la URL, descarga metadatos y evita duplicados calculando `hash_url`.

### `seleccion_tableros.php`

Permite seleccionar varios tableros predefinidos tras el registro u OAuth.
Acepta un arreglo `boards[]` con nombres de tablero y un campo oculto `shared`
que conserva la URL que originó el flujo de autenticación.

### `tablero_publico.php`

Exposición de tableros de solo lectura. No requiere sesión, pero exige el
parámetro `token` con el valor almacenado en `categorias.share_token`.

### `login.php` / `register.php`

Procesan formularios de acceso y registro. Ambos validan el campo
`g-recaptcha-response` cuando existen claves de reCAPTCHA y soportan el parámetro
`shared` para redirigir a `panel.php?shared=...` tras la autenticación.

### `oauth.php` y `oauth2callback.php`

Implementan el inicio de sesión con Google. `oauth.php` redirige al endpoint de
Google con el parámetro `state`; `oauth2callback.php` valida dicho `state`,
intercambia el `code` por tokens y crea la sesión local.

### `recuperar_password.php` y `restablecer_password.php`

Gestionan el flujo de recuperación de contraseña. El primer script genera un
`token` temporal en `password_resets` y envía un correo con el enlace; el segundo
verifica el token y permite establecer una nueva contraseña.

## Consideraciones de seguridad

- Los scripts JSON envían `Content-Type: application/json` y retornan estructuras
  simples (`success`, `message`...). Si añades nuevos endpoints sigue el mismo
  patrón.
- Cualquier parámetro `shared` se valida con `isValidSharedUrl()` para evitar
  redirecciones maliciosas.
- `session.php` configura cookies con `HttpOnly` y `SameSite=Lax` y proporciona
  funciones para expedir y revocar tokens persistentes.
