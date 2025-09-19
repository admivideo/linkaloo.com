# Guía de instalación

Este documento describe el proceso para preparar un entorno local de desarrollo para **linkaloo**.
Sigue los pasos en orden y consulta [configuracion.md](configuracion.md) si necesitas más contexto sobre credenciales externas.

## Requisitos previos

- PHP 8.0 o superior con extensiones `pdo_mysql`, `curl`, `gd` y `mbstring` habilitadas.
- Servidor MySQL 8 (o compatible) accesible desde tu entorno.
- Node.js 18 y npm (solo para ejecutar el lint de CSS).
- Extensión `openssl` habilitada para usar funciones criptográficas (`random_bytes`).

## Preparación del código

1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/linkaloo.git
   cd linkaloo.com
   ```
2. Instala dependencias de desarrollo:
   ```bash
   npm install
   ```
3. Crea las carpetas de almacenamiento si no existen y otorga permisos de escritura al servidor web:
   ```bash
   mkdir -p fichas local_favicons
   chmod 775 fichas local_favicons
   ```

## Configuración de la base de datos

1. Crea una base de datos vacía y un usuario con permisos de lectura/escritura.
2. Ejecuta el script de esquema:
   ```bash
   mysql -u linkaloo_user -p linkaloo < database.sql
   ```
3. Edita `config.php` y actualiza las credenciales de conexión.
4. Opcionalmente define las variables de entorno descritas en [configuracion.md](configuracion.md) (`GOOGLE_CLIENT_ID`, `RECAPTCHA_SITE_KEY`, etc.).

## Configuración de servicios externos

- Para iniciar sesión con Google, registra un OAuth Client ID y ajusta `GOOGLE_REDIRECT_URI` a `http://localhost:8000/oauth2callback.php` durante el desarrollo.
- Para reCAPTCHA v3, crea un sitio en la consola de Google y usa las claves en `login.php` y `register.php` mediante las variables de entorno correspondientes.
- Si quieres probar el flujo de recuperación de contraseña, configura `mail()` en tu entorno (por ejemplo con [MailHog](https://github.com/mailhog/MailHog)).

## Puesta en marcha

1. Inicia un servidor PHP en la raíz del proyecto:
   ```bash
   php -S localhost:8000
   ```
2. Abre `http://localhost:8000` en tu navegador y completa el registro o inicia sesión.
3. Tras el primer login se mostrará `seleccion_tableros.php` para crear tableros sugeridos (puedes omitirlo con el botón **Omitir**).

## Verificación de la instalación

Ejecuta las comprobaciones básicas desde la raíz del repositorio:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Todos los comandos deben finalizar sin errores. Si `php -S` no refleja tus cambios, borra la caché de opcodes (`php -r 'opcache_reset();'`) si la extensión está habilitada.

## Siguientes pasos

- Revisa [uso.md](uso.md) para comprender el flujo funcional de la aplicación.
- Consulta [endpoints.md](endpoints.md) si necesitas interactuar con los scripts desde herramientas externas o desarrollar nuevas características.
