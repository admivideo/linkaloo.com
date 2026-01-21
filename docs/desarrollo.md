# Guía de desarrollo

Este documento reúne las prácticas recomendadas para contribuir al proyecto, ejecutar la aplicación en
local y mantener la calidad del código. Complementa la documentación general con tareas enfocadas a
quienes trabajan en el repositorio.

## Preparación del entorno

1. Instala PHP 8.1+ con extensiones PDO, cURL, mbstring, DOM, GD y JSON.
2. Instala MySQL 8 o compatible y crea una base de datos vacía.
3. Revisa las credenciales en `config.php` o define variables de entorno según
   [configuracion.md](configuracion.md).
4. Carga el esquema inicial con `database.sql`.
5. Instala dependencias de desarrollo con `npm install` para ejecutar Stylelint.

Para pasos detallados con capturas consulta [instalacion.md](instalacion.md).

## Servir la aplicación en local

Desde la raíz del repositorio:

```bash
php -S localhost:8000
```

Luego abre `http://localhost:8000` en el navegador y registra una cuenta de prueba.

## Comprobaciones recomendadas

Antes de publicar cambios ejecuta:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Estas comprobaciones validan la sintaxis PHP, el JavaScript del front-end y el estilo CSS.

## Flujo de trabajo sugerido

1. Crea una rama por cambio.
2. Actualiza o añade documentación si se toca lógica de negocio.
3. Verifica los endpoints JSON descritos en [endpoints.md](endpoints.md) si hay cambios en las APIs.
4. Comprueba las páginas legales cuando se modifique contenido visible al usuario.

## Convenciones de código

- Mantén la lógica de acceso a base de datos en `config.php` y funciones reutilizables en helpers
  dedicados (`favicon_utils.php`, `image_utils.php`).
- Usa `assets/main.js` para el comportamiento del panel y evita dependencias externas.
- Las rutas públicas deben validar la sesión o el token de tablero compartido.

## Estructura de datos y pruebas rápidas

- Ejecuta `database.sql` cada vez que se necesite un entorno limpio.
- Para probar tableros públicos revisa `tablero_publico.php` y genera un token de compartición desde
  `tableros.php`.
- Las imágenes de fichas se almacenan en `fichas/` y los favicons en `local_favicons/`.

## Despliegue

- Usa variables de entorno para credenciales y claves externas.
- Revisa permisos de escritura en `fichas/` y `local_favicons/`.
- Configura HTTPS para OAuth y Web Share cuando estés en producción.

