# Modelo de datos

El esquema de **linkaloo** se define en [`database.sql`](../database.sql) y utiliza codificación `utf8mb4` para admitir emojis y caracteres especiales.
Las tablas siguen un patrón relacional sencillo: un usuario posee múltiples tableros (*categorías*) y cada tablero contiene enlaces.

## Relación general

```
usuarios 1 ──── * categorias 1 ──── * links
          \
           \──── * password_resets
            \─── * usuario_tokens
```

- Cada enlace pertenece a un usuario y a un tablero.
- Los tableros se eliminan en cascada si el usuario se borra, y los enlaces se eliminan en cascada si se borra su tablero.
- Los tokens de recuperación y de «recuérdame» dependen del usuario y se limpian automáticamente mediante restricciones `ON DELETE CASCADE`.

## Tablas y columnas

### `usuarios`

| Columna     | Tipo            | Descripción |
|-------------|-----------------|-------------|
| `id`        | `INT` PK        | Identificador interno autoincremental. |
| `nombre`    | `VARCHAR(100)`  | Nombre mostrado en la interfaz. |
| `email`     | `VARCHAR(255)`  | Correo único usado para autenticación y como clave natural. |
| `pass_hash` | `VARCHAR(255)`  | Hash `bcrypt` de la contraseña o hash aleatorio creado para cuentas OAuth. |

### `categorias`

| Columna        | Tipo              | Descripción |
|----------------|-------------------|-------------|
| `id`           | `INT` PK          | Identificador del tablero. |
| `usuario_id`   | `INT` FK → `usuarios.id` | Usuario propietario. |
| `nombre`       | `VARCHAR(100)`    | Título del tablero mostrado en la UI. |
| `color`        | `VARCHAR(20)`     | Color opcional (no utilizado actualmente en la interfaz). |
| `imagen`       | `TEXT`            | Imagen destacada calculada a partir del primer enlace. |
| `share_token`  | `VARCHAR(100)`    | Token público para generar `tablero_publico.php?token=<token>`. Nulo si el tablero es privado. |
| `nota`         | `TEXT`            | Texto libre asociado al tablero. |
| `creado_en`    | `DATETIME`        | Fecha de creación (se inicializa con `CURRENT_TIMESTAMP`). |
| `modificado_en`| `DATETIME`        | Fecha de última modificación; se actualiza automáticamente en operaciones sobre el tablero. |

### `links`

| Columna        | Tipo              | Descripción |
|----------------|-------------------|-------------|
| `id`           | `INT` PK          | Identificador del enlace. |
| `usuario_id`   | `INT` FK → `usuarios.id` | Usuario propietario (refuerza la pertenencia aunque el enlace ya apunta a un tablero de ese usuario). |
| `categoria_id` | `INT` FK → `categorias.id` | Tablero al que pertenece el enlace. |
| `url`          | `TEXT`            | URL original introducida por el usuario. |
| `url_canonica` | `TEXT`            | URL normalizada (esquema + host + ruta) usada para evitar duplicados. |
| `titulo`       | `VARCHAR(50)`     | Título visible (se trunca a 50 caracteres al guardar). |
| `descripcion`  | `TEXT`            | Descripción resumida, recortada según el dispositivo. |
| `imagen`       | `TEXT`            | Imagen descargada o favicon almacenado en `fichas/<usuario>/`. |
| `favicon`      | `TEXT`            | Ruta del favicon asociado (no siempre poblada). |
| `dominio`      | `VARCHAR(255)`    | Dominio extraído de la URL (para búsquedas futuras). |
| `nota_link`    | `TEXT`            | Nota personalizada editable desde `editar_link.php`. |
| `etiquetas`    | `TEXT`            | Etiquetas libres (no usadas actualmente). |
| `hash_url`     | `VARCHAR(255)`    | Hash `SHA-1` de la URL canónica. Garantiza unicidad con `UNIQUE (usuario_id, hash_url)`. |
| `creado_en`    | `TIMESTAMP`       | Marca de creación automática. |

### `password_resets`

| Columna      | Tipo              | Descripción |
|--------------|-------------------|-------------|
| `id`         | `INT` PK          | Identificador del registro. |
| `usuario_id` | `INT` FK → `usuarios.id` | Usuario que solicitó el restablecimiento. |
| `token`      | `VARCHAR(255)`    | Token aleatorio enviado por correo. |
| `expiracion` | `DATETIME`        | Fecha límite de validez (1 hora tras la solicitud). |

Los registros se eliminan cuando el token se consume o expira; la lógica se implementa en `restablecer_password.php`.

### `usuario_tokens`

| Columna      | Tipo              | Descripción |
|--------------|-------------------|-------------|
| `id`         | `INT` PK          | Identificador interno. |
| `usuario_id` | `INT` FK → `usuarios.id` | Usuario autenticado que activó «Recordarme». |
| `selector`   | `CHAR(32)` UNIQUE | Identificador público guardado en la cookie. |
| `token_hash` | `CHAR(64)`        | Hash SHA-256 del validador privado. |
| `expires_at` | `DATETIME`        | Fecha de expiración (365 días desde la creación). |
| `creado_en`  | `DATETIME`        | Fecha de emisión del token. |

El par `selector:validator` se almacena en la cookie `linkaloo_remember` y se valida en `session.php`. Si la verificación falla o expira, el token se elimina.

## Consideraciones adicionales

- Todas las tablas usan `InnoDB` para soportar claves foráneas y transacciones.
- Las columnas de texto permiten contenido UTF-8 extendido; no es necesario realizar conversiones manuales antes de insertar.
- Para migraciones, modifica `database.sql` y documenta los cambios en este archivo para mantener la sincronización entre esquema y documentación.
