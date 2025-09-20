# linkaloo

Aplicación web simple para guardar enlaces en tableros personales. Requiere PHP 8 y MySQL.

## Documentación

Consulta el índice [docs/README.md](docs/README.md) para una visión general de la arquitectura, una guía de instalación paso a paso, una guía de uso y otras referencias.

## Características

- Autenticación de usuarios (`login.php`, `register.php`, `logout.php`).
- Gestión completa de tableros: creación, renombrado, notas y eliminación.
- Cada ficha muestra título (máx. 50 caracteres), descripción (máx. 250 con `...`) y favicon.
- Buscador de enlaces y filtros por tablero.
- Menú inferior para mover rápidamente un enlace a otro tablero.
- Botón para compartir por ficha que usa la Web Share API o copia al portapapeles.
- Recepción de enlaces desde otros navegadores mediante Web Share Target (`share_target.php`).
- Icono de configuración con acceso a Cookies, Política de cookies, Condiciones de servicio, Política de privacidad y Quiénes somos, todas con contenido estándar.
- Diseño responsivo: dos columnas en móvil y altura adaptable sin separación vertical.
- Carga progresiva de enlaces (scroll infinito) a partir de la ficha 18.
- Todas las tablas y la conexión MySQL usan `utf8mb4` para soportar caracteres especiales.

## Instalación

1. Clona el repositorio.
2. Crea una base de datos MySQL y ejecuta `database.sql`.
3. Configura las variables de entorno descritas en la sección "Variables de entorno".
4. Coloca los recursos gráficos (`img/linkaloo_white.png`, `img/logo_linkaloo_blue.png`, `img/favicon.png`, `img/icon-192.png`, `img/icon-512.png` y `favicon.ico`) en el servidor.
5. Inicia un servidor PHP en la raíz del proyecto (`php -S localhost:8000`).

## Uso

1. Regístrate y accede al panel.
2. Crea uno o más tableros.
3. Guarda enlaces mediante el formulario “+”.
4. Busca, filtra, mueve, comparte o elimina cada enlace desde su tarjeta.

## Páginas legales

El proyecto incluye contenido legal listo para usar y enlazado desde el icono de configuración:

- `cookies.php` y `politica_cookies.php`
- `politica_privacidad.php`
- `condiciones_servicio.php`
- `quienes_somos.php`

Cualquier texto puede adaptarse editando los archivos correspondientes.

## Desarrollo

- JavaScript principal en `assets/main.js`.
- Estilos en `assets/style.css`.
- Para comprobar el código:
  - `php -l config.php panel.php move_link.php load_links.php`
  - `node --check assets/main.js`
  - `npm run lint:css`


## Variables de entorno

El proyecto lee la configuración exclusivamente desde variables de entorno. Asegúrate de definirlas antes de iniciar PHP (por ejemplo, en tu panel de hosting o mediante un fichero `.env` si usas un cargador de variables).

| Variable                 | Descripción |
| ------------------------ | ----------- |
| `DB_HOST`                | Host del servidor MySQL. |
| `DB_NAME`                | Nombre de la base de datos. |
| `DB_USERNAME`            | Usuario con permisos sobre la base de datos. |
| `DB_PASSWORD`            | Contraseña del usuario de base de datos. |
| `DB_CHARSET`             | Conjunto de caracteres usado en la conexión (por ejemplo, `utf8mb4`). |
| `GOOGLE_CLIENT_ID`       | Client ID de OAuth 2.0 para Google. |
| `GOOGLE_CLIENT_SECRET`   | Client Secret de OAuth 2.0 para Google. |
| `GOOGLE_REDIRECT_URI`    | URL de callback configurada en Google Cloud. |
| `RECAPTCHA_SITE_KEY`     | Clave pública de reCAPTCHA v3. |
| `RECAPTCHA_SECRET_KEY`   | Clave privada de reCAPTCHA v3. |

## Login con Google

1. Ve a [Google Cloud Console](https://console.cloud.google.com/) y crea un proyecto.
2. Configura la pantalla de consentimiento en **APIs & Services → OAuth consent screen**.
3. En **Credentials** crea un **OAuth client ID** de tipo "Web application".
4. Añade `http://localhost:8000/oauth2callback.php` (el endpoint de backend que maneja el callback OAuth) y `https://linkaloo.com/oauth2callback.php` en **Authorized redirect URIs**.
5. Copia el *Client ID* y el *Client Secret*.
6. Define `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` como variables de entorno.
7. Usa el enlace "Google" en `login.php` para autenticarte.

