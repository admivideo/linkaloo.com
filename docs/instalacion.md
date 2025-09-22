# Guía de instalación

Esta guía explica cómo preparar un entorno local de desarrollo o pruebas para **linkaloo**.

## Requisitos

- **PHP 8.1 o superior** con las extensiones PDO (MySQL), cURL, mbstring, DOM, JSON y GD habilitadas.
- **MySQL 8** (o MariaDB compatible) para persistir la información.
- **Node.js 18** y **npm** para ejecutar Stylelint sobre los estilos.
- Acceso a Internet para resolver dependencias y, opcionalmente, para que cURL obtenga metadatos y favicons.

## Preparación del proyecto

1. Clona el repositorio en tu máquina local.
2. Entra en el directorio raíz (`cd linkaloo.com`).
3. Instala las dependencias de desarrollo ejecutando `npm install`.
4. Comprueba que `node_modules/` contiene Stylelint y su configuración (`stylelint.config.js`).

## Configuración de la base de datos

1. Crea una base de datos vacía en MySQL (por ejemplo, `linkaloo_dev`).
2. Ejecuta el script `database.sql` sobre esa base para crear tablas e índices iniciales. Puedes usar `mysql`:
   ```bash
   mysql -u <usuario> -p linkaloo_dev < database.sql
   ```
3. Edita `config.php` para apuntar a tu servidor local. Ajusta `host`, `dbname`, `username` y `password`. En
   entornos de producción se recomienda extraer estas credenciales a variables de entorno o a un archivo no versionado.

## Claves externas y variables de entorno

- **Google OAuth:** define `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` en el entorno si no
  quieres usar los valores de respaldo incluidos en `config.php`.
- **reCAPTCHA v3:** establece `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY` con tus claves. Si no lo haces, se
  utilizarán las llaves de ejemplo del archivo de configuración.
- Exporta las variables antes de iniciar el servidor, por ejemplo:
  ```bash
  export GOOGLE_CLIENT_ID="tu-client-id"
  export GOOGLE_CLIENT_SECRET="tu-client-secret"
  export RECAPTCHA_SITE_KEY="tu-site-key"
  export RECAPTCHA_SECRET_KEY="tu-secret-key"
  ```

## Servidor local

1. Arranca un servidor PHP embebido desde la raíz del proyecto:
   ```bash
   php -S localhost:8000
   ```
2. Abre `http://localhost:8000` en tu navegador y crea una cuenta de prueba.
3. Si vas a probar el flujo de recuperación de contraseña, configura previamente el envío de correo en tu
   entorno (`sendmail`, `mailhog`, etc.) o adapta `recuperar_password.php` para utilizar un proveedor SMTP.

## Comprobaciones recomendadas

Antes de subir cambios o desplegar, ejecuta las verificaciones básicas:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Todas deben finalizar sin errores. Si modificas la base de datos, vuelve a ejecutar `database.sql` en un entorno
limpio o documenta las migraciones necesarias.
