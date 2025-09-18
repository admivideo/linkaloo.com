# Guía de instalación

Esta guía explica cómo preparar un entorno de desarrollo para **linkaloo** y qué parámetros debes ajustar antes de ejecutar la aplicación.

## Requisitos

- PHP 8 con extensiones PDO, cURL, GD y DOM habilitadas (necesarias para conectarse a MySQL, descargar imágenes y analizar HTML).【F:config.php†L1-L38】【F:panel.php†L37-L140】
- Servidor MySQL 8 o compatible.【F:database.sql†L1-L45】
- Node.js 18 y npm para ejecutar Stylelint durante el desarrollo.【F:package.json†L1-L17】
- Acceso a Internet para obtener favicons, metadatos Open Graph y completar OAuth/reCAPTCHA cuando se utilicen.【F:favicon_utils.php†L16-L33】【F:oauth.php†L1-L32】【F:login.php†L19-L71】

## Preparar la base de datos

1. Crea una base de datos vacía (por ejemplo, `linkaloo`).
2. Ejecuta el script [`database.sql`](../database.sql) para generar las tablas `usuarios`, `categorias`, `links`, `password_resets` y `usuario_tokens` con codificación `utf8mb4`.
3. Opcional: crea un usuario MySQL dedicado y concédele privilegios sobre la base.

## Configurar credenciales y variables de entorno

El archivo `config.php` contiene los datos de conexión y lecturas de variables de entorno para proveedores externos.【F:config.php†L1-L34】 Ajusta lo siguiente:

- Actualiza `$host`, `$dbname`, `$username` y `$password` con tus credenciales de MySQL.
- Define en tu entorno (por ejemplo, mediante `.env` + `dotenv`, variables del sistema o configuración del servidor) las siguientes claves:
  - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` para el login con Google.【F:config.php†L24-L28】【F:oauth.php†L1-L32】
  - `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY` para habilitar reCAPTCHA v3 en login/registro.【F:config.php†L31-L34】【F:login.php†L19-L71】
- Si no defines esas variables, la aplicación usará valores de demostración incluidos en `config.php`; reemplázalos en entornos productivos.

Asegúrate de que los directorios `fichas/` y `local_favicons/` sean escribibles por el proceso web; allí se almacenan imágenes y favicons descargados.【F:image_utils.php†L1-L46】【F:favicon_utils.php†L1-L33】

## Instalación del proyecto

1. Clona el repositorio y entra en la carpeta.
2. Instala las dependencias de desarrollo de npm:
   ```bash
   npm install
   ```
3. Si quieres ejecutar la aplicación en local, lanza un servidor embebido de PHP desde la raíz:
   ```bash
   php -S localhost:8000
   ```
4. Abre `http://localhost:8000/login.php` y crea una cuenta o inicia sesión. Tras autenticarte se te redirigirá a `panel.php`.【F:index.php†L1-L10】【F:panel.php†L1-L18】

## Verificaciones recomendadas

Antes de enviar cambios o desplegar, ejecuta las comprobaciones rápidas:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Todas deben terminar sin errores. También puedes crear un usuario de prueba, agregar tableros desde `seleccion_tableros.php` y guardar varios enlaces para confirmar que `panel.php` descarga metadatos y que `tablero_publico.php` funciona correctamente.【F:seleccion_tableros.php†L1-L68】【F:panel.php†L81-L154】【F:tablero_publico.php†L1-L74】
