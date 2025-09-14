# Endpoints de la API

Este documento describe los scripts PHP que actúan como puntos de entrada para las operaciones asíncronas del cliente.
Todos requieren que el usuario tenga una sesión activa; de lo contrario devuelven un código 401 o `success: false`.

## `load_links.php`

Obtiene un lote de enlaces del usuario.

- **Método:** `GET`
- **Parámetros:**
  - `offset` (entero, opcional): número de enlaces ya cargados. Por defecto `0`.
  - `cat` (entero o `"all"`, opcional): identificador del tablero. Si se omite o vale `all`, devuelve enlaces de todos los tableros.
- **Respuesta:** matriz JSON con objetos que incluyen `id`, `categoria_id`, `url`, `titulo`, `descripcion`, `imagen` y `favicon`.

## `move_link.php`

Mueve un enlace a otro tablero.

- **Método:** `POST`
- **Parámetros:**
  - `id` (entero, obligatorio): identificador del enlace.
  - `categoria_id` (entero, obligatorio): identificador del tablero destino.
- **Respuesta:** objeto JSON `{ "success": true }` si la operación se realiza correctamente, en caso contrario `{ "success": false }`.

## `delete_link.php`

Elimina un enlace existente.

- **Método:** `POST`
- **Parámetros:**
  - `id` (entero, obligatorio): identificador del enlace a borrar.
- **Respuesta:** objeto JSON con `success: true` si se borra el enlace; en caso contrario `success: false`.

Estos endpoints actualizan la marca de modificación del tablero afectado para mantener la información sincronizada.
