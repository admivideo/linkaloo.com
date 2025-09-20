# Documentación de linkaloo

Este directorio reúne la información necesaria para entender, instalar y
mantener el proyecto **linkaloo**, una aplicación web para guardar enlaces en
colecciones personales llamadas *tableros*.

## Índice rápido

- [Visión técnica](estructura.md) – arquitectura de carpetas, flujo de datos y
  dependencias principales.
- [Guía de instalación](instalacion.md) – requisitos, preparación de la base de
  datos y ejecución en local.
- [Guía de uso](uso.md) – recorrido por la interfaz web y las acciones más
  frecuentes.
- [Referencia de endpoints](endpoints.md) – descripción de las rutas que sirven
  como API o manejan formularios clave.
- [Guía de desarrollo](desarrollo.md) – consejos para trabajar con el código,
  configurar integraciones externas y ejecutar comprobaciones.

## Cómo orientarse

La documentación está organizada para que puedas avanzar desde una visión
panorámica hasta detalles específicos:

1. Empieza por **estructura.md** para conocer qué hace cada carpeta, cómo se
   generan los metadatos de las fichas y qué tablas componen la base de datos.
2. Sigue con **instalacion.md** si necesitas levantar el proyecto en un entorno
   nuevo.
3. Consulta **uso.md** cuando quieras repasar el flujo de usuario, desde el
   registro hasta la compartición de tableros.
4. Acude a **endpoints.md** para integrar la aplicación con otros clientes o
   automatizar tareas sobre los enlaces.
5. Para contribuir código, revisa **desarrollo.md**: resume dependencias,
   comandos de comprobación y convenciones.

## Comprobaciones rápidas

Desde la raíz del repositorio puedes ejecutar las siguientes órdenes para
verificar la instalación y el estilo del código:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Estas comprobaciones deben finalizar sin errores antes de contribuir cambios.
