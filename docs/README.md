# Documentación de linkaloo

Este directorio reúne la información necesaria para comprender, desplegar y operar la aplicación.
Cada documento se centra en una perspectiva distinta: desde la visión técnica completa hasta las
instrucciones para usuarios finales y la referencia de los endpoints disponibles.

## Cómo navegar por la documentación

1. **¿Vas a preparar un entorno local?** Empieza por la [guía de instalación](instalacion.md) y, una vez
   configurado todo, repasa la [guía de uso](uso.md) para validar que el flujo funciona.
2. **¿Necesitas entender la arquitectura o planificas realizar cambios?** Lee el
   [manual técnico](manual_tecnico.md), que detalla la organización del código, el modelo de datos y los
   procesos principales.
3. **¿Buscas un mapa rápido del repositorio o de la base de datos?** Consulta
   [estructura.md](estructura.md) para conocer cómo se agrupan los archivos y tablas.
4. **¿Necesitas preparar credenciales o ajustar el servidor?** Consulta
   [configuracion.md](configuracion.md) para conocer las variables de entorno y consejos de despliegue.
5. **¿Vas a integrar el front-end con llamadas asíncronas?** Revisa la referencia de
   [endpoints.md](endpoints.md) para conocer parámetros, respuestas y requisitos de autenticación.

## Tabla de contenidos

| Documento | Descripción |
| --- | --- |
| [manual_tecnico.md](manual_tecnico.md) | Panorama completo de arquitectura, flujos de negocio, servicios externos y prácticas recomendadas. |
| [arquitectura.md](arquitectura.md) | Visión por capas del sistema, flujos de datos e integraciones clave. |
| [estructura.md](estructura.md) | Resumen de directorios, archivos clave y esquema de la base de datos. |
| [instalacion.md](instalacion.md) | Pasos detallados para preparar un entorno local de desarrollo o pruebas. |
| [uso.md](uso.md) | Guía paso a paso para operar la aplicación desde la interfaz web. |
| [documentacion_general.md](documentacion_general.md) | Visión transversal y resumida de la aplicación y sus dependencias. |
| [resumen_ejecutivo.md](resumen_ejecutivo.md) | Resumen rápido para stakeholders: propósito, flujo principal y tareas de operación. |
| [configuracion.md](configuracion.md) | Variables de entorno recomendadas, requisitos de OAuth/reCAPTCHA y tareas de mantenimiento. |
| [endpoints.md](endpoints.md) | Referencia de los scripts PHP que exponen respuestas JSON o sirven contenido público. |
| [progreso.md](progreso.md) | Resumen del estado actual del proyecto, funcionalidades cubiertas y próximos pasos sugeridos. |

## Flujo general del proyecto

1. Una persona se registra o inicia sesión (`register.php`, `login.php`) y el sistema emite un token de
   sesión persistente gestionado por `session.php`.
2. Desde el panel (`panel.php`) crea tableros (`tableros.php`, `tablero.php`) y guarda enlaces en
   `agregar_favolink.php`. El front-end (`assets/main.js`) controla filtros, búsqueda y compartición.
3. Las acciones de mantenimiento (mover, eliminar o cargar enlaces de forma incremental) se realizan
   mediante los endpoints JSON `move_link.php`, `delete_link.php` y `load_links.php`.
4. Opcionalmente se generan tableros públicos (`tablero_publico.php`) y se pueden compartir enlaces
   individuales o completos usando la Web Share API o AddToAny.

## Comprobaciones rápidas

Desde la raíz del repositorio puedes ejecutar las siguientes órdenes para verificar la instalación y el
estilo del código antes de publicar cambios:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Todas deben finalizar sin errores. El comando de PHP valida la sintaxis de los scripts críticos, `node
--check` confirma que el JavaScript del front-end es válido y `npm run lint:css` aplica la configuración de
Stylelint sobre `assets/style.css`.
