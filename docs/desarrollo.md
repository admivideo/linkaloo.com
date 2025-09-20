# Guía de desarrollo

Este documento resume la información necesaria para colaborar con el código de
**linkaloo** y entender cómo se conectan las piezas internas.

## Stack y dependencias

- **PHP 8** con las extensiones `PDO`, `curl`, `mbstring` y `gd` habilitadas.
- **MySQL 8** como base de datos relacional.
- **Node.js 18** y `npm` para ejecutar tareas de linting de CSS.
- Las librerías del navegador utilizadas en producción (Feather Icons, AddToAny
  y la Web Share API) se cargan desde CDN en los archivos PHP; no hay un proceso
  de build para JavaScript.

## Configuración local

1. Clona el repositorio y ejecuta `npm install` para disponer de Stylelint.
2. Crea una base de datos y ejecuta `database.sql` para poblar las tablas
   (`usuarios`, `categorias`, `links`, `password_resets` y `usuario_tokens`).
3. Duplica `config.php` o edítalo para apuntar a tu servidor MySQL. En entornos
   reales es preferible sobrescribir los valores mediante variables de entorno o
   un archivo fuera del control de versiones.
4. Exporta las variables de entorno opcionales:
   - `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` para el
     inicio de sesión con Google.
   - `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY` si quieres habilitar
     reCAPTCHA v3 en los formularios de login y registro.
5. Ejecuta `php -S localhost:8000` desde la raíz del proyecto para servir los
   scripts PHP en local.

Los directorios `fichas/` y `local_favicons/` se generan automáticamente para
almacenar imágenes descargadas y favicons procesados. Añádelos al `.gitignore`
si trabajas con un fork o clonas el repositorio.

## Flujo de autenticación

- `login.php` y `register.php` manejan el acceso clásico con email y contraseña.
  Usan reCAPTCHA v3 cuando hay claves configuradas y delegan la persistencia a
  `session.php`, que también implementa los tokens "Recuérdame".
- `oauth.php` y `oauth2callback.php` gestionan el inicio de sesión con Google.
  Almacenan temporalmente un `state` en sesión para validar la respuesta de
  Google y, tras la autenticación, redirigen al panel o al asistente de creación
  de tableros (`seleccion_tableros.php`).
- `recuperar_password.php` y `restablecer_password.php` implementan el flujo de
  restablecimiento mediante correos con token de un solo uso.

## Gestión de enlaces

El panel (`panel.php`) es el punto central de la aplicación. Desde ahí se
realizan las siguientes acciones:

- Alta de enlaces mediante un formulario que admite URL y título opcional.
  `panel.php` descarga metadatos y favicons (a través de `image_utils.php` y
  `favicon_utils.php`), normaliza la URL y evita duplicados gracias a un hash.
- Movimiento de tarjetas entre tableros (`move_link.php`) y eliminación
  (`delete_link.php`). Ambos scripts responden en JSON y actualizan la marca de
  modificación del tablero.
- Carga progresiva de enlaces (`load_links.php`) para implementar scroll
  infinito en el cliente (`assets/main.js`).

Los tableros públicos (`tablero_publico.php`) exponen un listado de sólo lectura
a través de un token de compartición almacenado en `categorias.share_token`.

## Herramientas y comprobaciones

Antes de subir cambios ejecuta las comprobaciones básicas:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Para depurar peticiones AJAX puedes usar `php -S` junto con la consola del
navegador. El código de JavaScript se encuentra en `assets/main.js` y está
escrito en ES2019, por lo que funciona sin transpilación adicional.

## Buenas prácticas

- Escapa siempre la salida HTML con `htmlspecialchars` como hace el proyecto en
  las plantillas existentes.
- Prefiere `prepare` + `execute` al construir consultas SQL para beneficiarte de
  parámetros y evitar inyecciones.
- Mantén los textos en UTF-8; `panel.php` incluye utilidades para convertir
  entradas detectadas en otras codificaciones.
- Si añades nuevos scripts PHP con respuestas JSON, recuerda fijar el header
  `Content-Type: application/json` y devolver estructuras simples (`success`,
  `message`, etc.).

## Integraciones adicionales

- El directorio raíz contiene `ShareReceiverActivity.kt` y `AndroidManifest.xml`
  con un *intent filter* para recibir enlaces desde Android y abrir el panel web
  con el parámetro `shared`. Esto permite compartir desde otras apps hacia la
  versión web.
- El envío de correos de recuperación usa la función `mail()` de PHP. Sustitúyela
  por un proveedor transaccional si necesitas un flujo más robusto.
