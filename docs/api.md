# Referencia de API

Este documento describe los endpoints de backend usados por la aplicación.
Todas las rutas requieren una sesión activa del usuario.

## `GET load_links.php`

Devuelve enlaces del usuario en formato JSON.

**Parámetros de consulta**

- `offset` (opcional, entero): desplazamiento para paginación. Valor por defecto 0.
- `cat` (opcional, entero o `all`): identificador del tablero a filtrar. Por defecto `all`.

**Respuesta**

Arreglo JSON con objetos que incluyen `id`, `categoria_id`, `url`, `titulo`, `descripcion`, `imagen` y `favicon`.

## `POST move_link.php`

Mueve un enlace a otro tablero.

**Campos del cuerpo**

- `id` (entero): identificador del enlace.
- `categoria_id` (entero): identificador del tablero destino.

**Respuesta**

Objeto JSON `{ "success": true|false }`.

## `POST delete_link.php`

Elimina un enlace del usuario.

**Campos del cuerpo**

- `id` (entero): identificador del enlace.

**Respuesta**

Objeto JSON `{ "success": true|false }`.

