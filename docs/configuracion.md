# Configuración

Este documento reúne los ajustes necesarios para ejecutar **linkaloo** tanto en local como en producción.
Incluye credenciales de base de datos, integración con Google OAuth, reCAPTCHA v3 y parámetros opcionales de sesión.

## Base de datos

1. Copia o edita `config.php` y reemplaza las credenciales por las de tu servidor MySQL:
   ```php
   $host     = 'localhost';
   $dbname   = 'linkaloo';
   $username = 'linkaloo_user';
   $password = 'cambia-esto';
   ```
2. Ejecuta [`database.sql`](../database.sql) con un usuario que tenga permisos de creación de tablas.
3. Verifica que la conexión utiliza `utf8mb4`; el script ya establece `SET NAMES 'utf8mb4'` al abrir la conexión.
4. No publiques credenciales reales en el repositorio. Para despliegues automatizados, exporta las variables en tu entorno y lee su valor antes de incluir `config.php`.

> **Nota:** `config.php` inicializa un objeto `PDO` accesible como `$pdo`. Cualquier script que necesite conexión debe incluirlo antes de ejecutar consultas.

## Variables de entorno soportadas

`config.php` admite valores desde variables de entorno para las integraciones externas. Si no están definidas, se emplean los ejemplos incluidos en el archivo (conviene reemplazarlos en producción).

| Variable                 | Uso en la aplicación                                               | Fallback en `config.php` |
|--------------------------|--------------------------------------------------------------------|--------------------------|
| `GOOGLE_CLIENT_ID`       | Identificador OAuth 2.0 usado en `oauth.php` y `oauth2callback.php`. | Valor de ejemplo.        |
| `GOOGLE_CLIENT_SECRET`   | Secreto del cliente OAuth.                                         | Valor de ejemplo.        |
| `GOOGLE_REDIRECT_URI`    | URL registrada en Google Cloud Console para el callback.           | `https://linkaloo.com/oauth2callback.php` |
| `RECAPTCHA_SITE_KEY`     | Clave pública de reCAPTCHA v3 usada en los formularios de login y registro. | Valor de ejemplo. |
| `RECAPTCHA_SECRET_KEY`   | Clave secreta para verificar el token de reCAPTCHA en el backend.  | Valor de ejemplo. |

Define las variables antes de iniciar el servidor PHP:

```bash
export GOOGLE_CLIENT_ID="tu-id"
export GOOGLE_CLIENT_SECRET="tu-secreto"
export GOOGLE_REDIRECT_URI="http://localhost:8000/oauth2callback.php"
export RECAPTCHA_SITE_KEY="tu-site-key"
export RECAPTCHA_SECRET_KEY="tu-secret-key"
```

## OAuth de Google

1. Crea un proyecto en [Google Cloud Console](https://console.cloud.google.com/).
2. Configura la pantalla de consentimiento e incluye los ámbitos `openid email profile`.
3. Registra un **OAuth client ID** de tipo *Web application*.
4. Añade `http://localhost:8000/oauth2callback.php` y la URL pública del despliegue en los *Authorized redirect URIs*.
5. Define las variables de entorno anteriores y prueba el flujo usando el enlace «Google» en `login.php`.

## reCAPTCHA v3

- Se ejecuta en `login.php` y `register.php`. Debes habilitar la acción `login` y `register` en el panel de reCAPTCHA.
- Ajusta el umbral de score en `register.php` y `login.php` si recibes falsos positivos (actualmente `>= 0.5`).
- Si dejas las claves de ejemplo, la verificación fallará y el formulario devolverá «Verificación humana fallida».

## Parámetros de sesión y cookies

`session.php` define dos constantes opcionales que puedes sobrescribir antes de incluir el archivo:

```php
define('LINKALOO_SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 días
define('LINKALOO_REMEMBER_COOKIE_NAME', 'mi_cookie_personalizada');
require_once __DIR__ . '/session.php';
```

- `LINKALOO_SESSION_LIFETIME` controla tanto la duración de la sesión como la expiración del token «Recordarme».
- `LINKALOO_REMEMBER_COOKIE_NAME` permite cambiar el nombre de la cookie persistente.

La cookie se marca como `Secure` cuando el sitio se sirve bajo HTTPS y `SameSite=Lax` para reducir ataques CSRF.

## Almacenamiento de imágenes

- Los favicons descargados se guardan en `local_favicons/`. Asegúrate de que el servidor tenga permisos de escritura.
- Las imágenes de las fichas se almacenan en `fichas/<id_usuario>/`. Cada archivo se redimensiona a 300 px de ancho como máximo.
- Limpia periódicamente estas carpetas si el espacio en disco es limitado; no existe un recolector automático.

## Correo saliente

`recuperar_password.php` envía correos mediante `mail()`. Configura tu servidor PHP para que la función disponga de un `sendmail_path` válido o reemplázala por un proveedor SMTP si necesitas mayor fiabilidad.
