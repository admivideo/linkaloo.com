# Endpoints de la API

Los siguientes scripts PHP exponen respuestas JSON o sirven contenido público que puede consumirse desde
otros clientes. Todos ellos residen en la raíz del proyecto.

## Reglas generales

- Los endpoints JSON (`load_links.php`, `move_link.php`, `delete_link.php`) **requieren una sesión iniciada**. Si
  no existe, devuelven `401` o `{ "success": false }`.
- El cuerpo de las peticiones `POST` debe codificarse como `application/x-www-form-urlencoded`.
- Las respuestas JSON se envían con `Content-Type: application/json; charset=utf-8` y utilizan UTF-8.
- El tamaño de lote por defecto en `load_links.php` es de 18 elementos.

## `load_links.php`

Recupera enlaces del usuario autenticado en bloques paginados.

- **Método:** `GET`
- **Parámetros:**
  - `offset` (`int`, opcional) – Número de enlaces ya cargados. Por defecto `0`.
  - `cat` (`int` o `all`, opcional) – Identificador del tablero. Si es `all` (valor por defecto) devuelve enlaces de todos los tableros.
- **Respuesta:** matriz JSON con objetos que incluyen `id`, `categoria_id`, `url`, `titulo`, `descripcion`, `imagen` y `favicon`.
- **Ejemplo de respuesta:**
  ```json
  [
    {
      "id": 42,
      "categoria_id": 3,
      "url": "https://ejemplo.com/articulo",
      "titulo": "Artículo destacado",
      "descripcion": "Resumen corto…",
      "imagen": "/fichas/3/42.png",
      "favicon": "/local_favicons/ejemplo.com.png"
    }
  ]
  ```

Aunque el panel actual carga todos los enlaces directamente en PHP, este endpoint puede reutilizarse para
implementar scroll infinito o integraciones externas.

## `move_link.php`

Actualiza el tablero al que pertenece un enlace.

- **Método:** `POST`
- **Parámetros:**
  - `id` (`int`, obligatorio) – Identificador del enlace a mover.
  - `categoria_id` (`int`, obligatorio) – Identificador del tablero destino.
- **Respuesta:**
  - `{ "success": true }` cuando el enlace pertenece al usuario y se actualiza correctamente.
  - `{ "success": false }` en cualquier otro caso (parámetros incorrectos, enlace inexistente, sesión caducada…).

La operación actualiza también la columna `modificado_en` tanto del tablero origen como del destino.

## `delete_link.php`

Elimina un enlace del usuario autenticado.

- **Método:** `POST`
- **Parámetros:**
  - `id` (`int`, obligatorio) – Identificador del enlace.
- **Respuesta:**
  - `{ "success": true }` si el enlace se elimina y, en su caso, se actualiza la marca `modificado_en` del tablero asociado.
  - `{ "success": false }` si la operación no se realiza.

## `tablero_publico.php`

Expone la versión pública de un tablero previamente marcado como compartido.

- **Método:** `GET`
- **Parámetros:** `token` (`string`, obligatorio) – Valor almacenado en `categorias.share_token`.
- **Respuesta:** HTML con las fichas del tablero en modo lectura. Si el token no existe o está vacío, devuelve un
  mensaje `Tablero no disponible` con código de estado `404`.

Este endpoint no requiere autenticación y se usa para compartir tableros completos fuera de la aplicación.
