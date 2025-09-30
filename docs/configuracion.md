# Configuración de linkaloo

Este documento describe las opciones de configuración necesarias para ejecutar linkaloo en entornos de
desarrollo y producción. La mayoría de valores se leen desde variables de entorno; en su ausencia se
utilizan los valores definidos en [`config.php`](../config.php), pensados únicamente para desarrollo.

## Variables de entorno principales

| Variable | Descripción | Valor por defecto |
| --- | --- | --- |
| `DB_HOST` | Host o IP del servidor MySQL. | `127.0.0.1` |
| `DB_NAME` | Nombre de la base de datos utilizada por la aplicación. | `linkaloo` |
| `DB_USER` | Usuario con permisos de lectura/escritura sobre la base de datos. | `root` |
| `DB_PASS` | Contraseña del usuario de base de datos. | Cadena vacía |
| `DB_PORT` | Puerto TCP de MySQL. | `3306` |
| `GOOGLE_CLIENT_ID` | Client ID de la credencial OAuth 2.0. | `''` (autenticación deshabilitada) |
| `GOOGLE_CLIENT_SECRET` | Client secret asociado. | `''` |
| `GOOGLE_REDIRECT_URI` | URI de callback para OAuth. | `https://linkaloo.com/oauth2callback.php` |
| `RECAPTCHA_SITE_KEY` | Clave pública de reCAPTCHA v3. | Valor de demostración definido en `config.php` |
| `RECAPTCHA_SECRET_KEY` | Clave secreta de reCAPTCHA v3. | Valor de demostración definido en `config.php` |
| `MAIL_FROM` | Dirección de correo usada para los mensajes de recuperación de contraseña. | `no-reply@linkaloo.com` |
| `APP_URL` | URL pública base usada en correos y enlaces compartidos. | `https://linkaloo.com` |

> ℹ️  Si despliegas en un entorno compartido evita versionar `config.php` con credenciales reales. Utiliza
> un archivo `.env`, variables del servidor web o un gestor de secretos.

## Configuración de OAuth con Google

1. Accede a [Google Cloud Console](https://console.cloud.google.com/) y crea un proyecto.
2. En **APIs & Services → OAuth consent screen** configura la pantalla de consentimiento.
3. En **Credentials** crea un **OAuth client ID** de tipo *Web application*.
4. Añade las URLs de callback a la lista **Authorized redirect URIs** (por ejemplo
   `http://localhost:8000/oauth2callback.php` en local y la URL pública en producción).
5. Copia el `Client ID` y el `Client Secret` y asígnalos a `GOOGLE_CLIENT_ID` y
   `GOOGLE_CLIENT_SECRET` respectivamente.

Si no configuras estas variables, el botón de "Continuar con Google" no se mostrará en `login.php`.

## Configuración de reCAPTCHA v3

- Registra un nuevo sitio en [https://www.google.com/recaptcha/admin/](https://www.google.com/recaptcha/admin/).
- Selecciona **reCAPTCHA v3** y añade tus dominios.
- Copia las claves generadas en `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY`.

Los formularios de login y registro mostrarán un mensaje de error si Google rechaza el token.
Durante el desarrollo puedes reutilizar las claves de ejemplo, pero se recomienda usar claves
propias en producción para evitar abusos.

## Envío de correo electrónico

El flujo de recuperación de contraseña (`recuperar_password.php` y `restablecer_password.php`) utiliza
la función nativa `mail()`. Para entornos de producción:

- Configura un MTA en el servidor (Postfix, Exim, etc.) o adapta el código para usar un proveedor SMTP.
- Ajusta `MAIL_FROM` con un remitente válido y asegúrate de que el dominio tiene SPF/DKIM configurados.
- En entornos de desarrollo puedes habilitar un buzón de pruebas como [MailHog](https://github.com/mailhog/MailHog).

## Archivos y directorios de almacenamiento

| Ruta | Contenido | Consideraciones |
| --- | --- | --- |
| `fichas/` | Imágenes descargadas desde los metadatos OpenGraph. | Asegura permisos de escritura para el usuario del servidor. |
| `local_favicons/` | Favicons cacheados de los enlaces guardados. | Se pueden limpiar periódicamente si el almacenamiento es limitado. |
| `img/` | Recursos estáticos (logos, iconos). | Personaliza los logos antes de un despliegue público. |

En instalaciones nuevas crea manualmente `fichas/` y `local_favicons/` (vacíos) y
concede permisos de escritura (`chmod 775` o similares) al proceso que ejecuta PHP.

## Cookies y sesiones

- `session.php` establece una duración de 365 días para la sesión y define el comportamiento de la cookie
  persistente "remember me". Para forzar cookies seguras activa HTTPS; la bandera `secure` se ajusta
  automáticamente cuando la variable `$_SERVER['HTTPS']` está presente.
- Si se despliega tras un proxy inverso, asegúrate de reenviar los encabezados `X-Forwarded-Proto` para que
  PHP detecte el uso de HTTPS y marque las cookies como seguras.

## Parámetro `shared`

La aplicación acepta un parámetro opcional `shared` en varias rutas para precargar el formulario de alta.
Por seguridad se valida con `isValidSharedUrl()` en `session.php`. Si quieres permitir dominios
adicionales, edita dicha función o abastece una lista configurable mediante variables de entorno.

## Limpieza y mantenimiento

- Programa una tarea recurrente para eliminar tokens de recuperación expirados (`password_resets`).
- Considera limpiar periódicamente las imágenes en `fichas/` y los favicons si los enlaces se eliminan.
- Revisa los índices de la base de datos si la aplicación crece (por ejemplo, añade índices sobre
  `links.hash_url` y `links.categoria_id`).

## Resumen

1. Define las variables de entorno antes de desplegar.
2. Configura Google OAuth y reCAPTCHA con tus claves.
3. Asegura permisos de escritura en `fichas/` y `local_favicons/`.
4. Configura el envío de correo para recuperar contraseñas.
5. Supervisa tokens e imágenes para mantener el almacenamiento bajo control.
