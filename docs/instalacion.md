# Guía de instalación

Este documento describe el proceso para preparar un entorno local de desarrollo
para **linkaloo**.

## Requisitos

- PHP 8 con extensiones `PDO`, `curl`, `mbstring` y `gd` activas.
- MySQL 8.
- Node.js 18 y `npm`.
- Servidor SMTP o cuenta de correo si deseas probar el flujo de recuperación de
  contraseña.

## Pasos

1. Clona el repositorio en tu máquina.
2. Ejecuta `npm install` en la raíz para instalar Stylelint.
3. Crea una base de datos MySQL y ejecuta `database.sql` para generar las tablas.
4. Ajusta las credenciales de acceso en `config.php` o exporta las variables de
   entorno equivalentes.
5. (Opcional) Configura las integraciones externas:
   - Google OAuth: define `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y
     `GOOGLE_REDIRECT_URI`.
   - reCAPTCHA v3: define `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY`.
6. Inicia un servidor PHP en la raíz del proyecto: `php -S localhost:8000`.
7. Abre `http://localhost:8000` en tu navegador.

## Directorios generados

Durante la ejecución se crean dos carpetas adicionales:

- `fichas/<usuario>/` almacena las imágenes de vista previa descargadas al
  guardar enlaces.
- `local_favicons/` guarda los favicons reducidos a 25×25 px.

Puedes eliminarlas con seguridad al limpiar el entorno; se regenerarán según se
necesiten.

## Verificación

Desde la raíz del proyecto puedes ejecutar las comprobaciones básicas:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Si todas las órdenes finalizan sin errores, la instalación se ha realizado
correctamente.
