# Documentación de linkaloo

Este directorio reúne toda la información necesaria para comprender, instalar y mantener la aplicación.
Úsalo como punto de partida antes de tocar el código o desplegar el proyecto.

## Índice rápido

- [Arquitectura y estructura](estructura.md): componentes del frontend y backend, flujo de datos y almacenamiento de recursos.
- [Modelo de datos](datos.md): definición detallada de tablas, columnas, índices y relaciones de la base de datos MySQL.
- [Configuración](configuracion.md): variables de entorno, credenciales externas y ajustes de seguridad.
- [Instalación paso a paso](instalacion.md): preparación de un entorno local de desarrollo.
- [Guía de uso](uso.md): recorrido funcional de la interfaz y de las operaciones disponibles para el usuario.
- [Referencia de endpoints](endpoints.md): descripción de los scripts PHP que actúan como API y de sus parámetros.

Cada documento puede consultarse de forma independiente, pero seguir el orden anterior ayuda a obtener una visión completa del sistema.

## Convenciones y herramientas

- La aplicación corre sobre **PHP 8**, **MySQL 8** y emplea **Node.js 18** únicamente para tareas de linting de CSS.
- El código JavaScript reside en `assets/main.js` y los estilos en `assets/style.css`.
- Las imágenes descargadas se almacenan en `fichas/<usuario>` y los favicons en `local_favicons/`.

## Comprobaciones rápidas

Desde la raíz del repositorio ejecuta:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

El comando `php -l` verifica la sintaxis de los scripts principales, `node --check` revisa el JavaScript y `npm run lint:css`
valida la hoja de estilos con Stylelint.

## Próximos pasos

1. Lee [estructura.md](estructura.md) para familiarizarte con los módulos y con el flujo general.
2. Consulta [datos.md](datos.md) antes de modificar el esquema de la base de datos.
3. Revisa [configuracion.md](configuracion.md) para preparar credenciales locales y servicios externos.
4. Sigue [instalacion.md](instalacion.md) para levantar el entorno.
5. Completa con [uso.md](uso.md) y [endpoints.md](endpoints.md) cuando desarrolles nuevas funcionalidades o integres el frontend.
