# Documentación de linkaloo

Este directorio reúne los documentos principales del proyecto:

- `estructura.md`: visión general de la arquitectura y de la base de datos.
- `instalacion.md`: guía para preparar un entorno local de desarrollo.

## Comprobaciones rápidas

Desde la raíz del repositorio puedes ejecutar las siguientes órdenes para verificar la instalación y el estilo del código:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Estas comprobaciones deben finalizar sin errores antes de contribuir cambios.
