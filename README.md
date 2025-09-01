# linkaloo

Aplicación web simple para guardar enlaces en tableros personales. Requiere PHP 8 y MySQL.

## Características

- Autenticación de usuarios (`login.php`, `register.php`, `logout.php`).
- Gestión completa de tableros: creación, renombrado, notas y eliminación.
- Recuperación de contraseña mediante enlace temporal.
- Cada ficha muestra título (máx. 50 caracteres), descripción (máx. 250 con `...`), favicon y dominio del enlace.
- Buscador de enlaces y filtros por tablero.
- Menú inferior para mover rápidamente un enlace a otro tablero.
- Botón para compartir por ficha o tablero que usa la Web Share API o copia al portapapeles.
- Icono de configuración con acceso a Cookies, Política de cookies, Condiciones de servicio, Política de privacidad y Quiénes somos.
- Diseño responsivo: dos columnas en móvil y altura adaptable sin separación vertical.
- Carga progresiva de enlaces (scroll infinito) a partir de la ficha 18.
- Todas las tablas y la conexión MySQL usan `utf8mb4` para soportar caracteres especiales.

## Instalación

1. Clona el repositorio.
2. Crea una base de datos MySQL y ejecuta `database.sql`.
3. Define las credenciales de la base de datos mediante variables de entorno:

   ```bash
   export DB_HOST=localhost
   export DB_NAME=linkaloo
   export DB_USER=usuario
   export DB_PASS=secreto
   ```

   (o utiliza un fichero `.env`, que está ignorado por Git).
4. Coloca el logo `img/linkaloo_white.png` (y los favicons en el servidor).
5. Inicia un servidor PHP en la raíz del proyecto (`php -S localhost:8000`).

## Uso

1. Regístrate y accede al panel.
2. Crea uno o más tableros.
3. Guarda enlaces mediante el formulario “+”.
4. Busca, filtra, mueve, comparte o elimina cada enlace desde su tarjeta.

## Desarrollo

- JavaScript principal en `assets/main.js`.
- Estilos en `assets/style.css`.
- Para comprobar el código:
  - `npm install` (una vez) para preparar las dependencias de desarrollo.
  - `php -l config.php panel.php move_link.php load_links.php`
  - `node --check assets/main.js`
  - `npm run lint:css`

