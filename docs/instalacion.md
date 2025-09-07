# Guía de instalación

Este documento describe el proceso para preparar un entorno local de desarrollo para **linkaloo**.

## Requisitos

- PHP 8
- MySQL 8
- Node.js 18
- npm

## Pasos

1. Clona el repositorio en tu máquina.
2. Crea una base de datos MySQL y ejecuta `database.sql` para generar las tablas.
3. Ajusta las credenciales de acceso en `config.php`.
4. Instala las dependencias de desarrollo con `npm install`.
5. Inicia un servidor PHP en la raíz del proyecto: `php -S localhost:8000`.
6. Abre `http://localhost:8000` en tu navegador.

## Verificación

Desde la raíz del proyecto puedes ejecutar las comprobaciones básicas:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Si todas las órdenes finalizan sin errores, la instalación se ha realizado correctamente.
