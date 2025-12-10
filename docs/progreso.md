# Estado actual del proyecto

Este documento resume las funcionalidades implementadas hasta la fecha, la cobertura de la
aplicación web y los aspectos pendientes identificados durante el desarrollo.

## Resumen funcional

- **Autenticación y cuentas**
  - Registro y acceso mediante formularios propios con verificación reCAPTCHA v3 (`register.php`, `login.php`).
  - Inicio de sesión social con Google OAuth 2.0 (`oauth.php`, `oauth2callback.php`).
  - Recuperación de contraseñas a través de tokens temporales enviados por correo (`recuperar_password.php`, `restablecer_password.php`).
  - Gestión de sesiones persistentes y tokens recuerdame (`session.php`).
- **Gestión de tableros y enlaces**
  - Creación, edición, renombrado y eliminación de tableros desde el panel principal (`panel.php`, `tableros.php`, `tablero.php`).
  - Alta de enlaces con descarga automática de metadatos y procesamiento de imágenes (`agregar_favolink.php`, `image_utils.php`).
  - Reordenamiento y movimiento de enlaces entre tableros mediante peticiones asíncronas (`move_link.php`).
  - Eliminación de enlaces con confirmación inmediata en la interfaz (`delete_link.php`).
  - Listados paginados/cargados bajo demanda para optimizar el renderizado de colecciones grandes (`load_links.php`).
- **Compartición y vistas públicas**
  - Generación de tokens de compartición para tableros públicos (`tablero.php`, `tablero_publico.php`).
  - Integración con Web Share API y AddToAny para distribuir enlaces (`assets/main.js`, `panel.php`).
  - Compatibilidad con dispositivos Android mediante `ShareReceiverActivity.kt`.
- **Aspectos legales y cumplimiento**
  - Páginas dedicadas a privacidad, cookies, términos de servicio y quiénes somos.
  - Gestión de cookies en conformidad con la normativa local (`politica_cookies.php`).

## Estado del backend

- **Lenguaje y dependencias:** PHP 8.1+ con PDO, cURL, GD, DOM y mbstring. No se utiliza framework.
- **Persistencia:** MySQL 8 con esquema definido en `database.sql`, que incluye tablas para usuarios,
  tableros, enlaces, tokens de sesión persistentes y peticiones de reseteo de contraseña.
- **Seguridad:**
  - Uso de cookies `HttpOnly` y `SameSite=Lax`, activando `secure` en HTTPS.
  - Hash de contraseñas con `password_hash()` y tokens persistentes almacenados como hash.
  - Validación de URLs compartidas para evitar redirecciones abiertas (`isValidSharedUrl`).
- **Servicios externos:** Google OAuth, reCAPTCHA v3 y correo SMTP mediante `mail()` o integración de hosting.

## Estado del front-end

- **Tecnologías:** HTML renderizado desde PHP, CSS modular en `assets/style.css` y JavaScript vanilla
  en `assets/main.js`.
- **Funcionalidades clave:**
  - Búsqueda instantánea y filtros por tablero en el panel principal.
  - Formularios modales para creación rápida de enlaces y tableros.
  - Soporte para Web Share API con alternativa AddToAny en navegadores no compatibles.
  - Uso de `fetch()` para sincronizar acciones sin recargar la página.
- **Calidad:** Se validan los estilos mediante Stylelint (`npm run lint:css`) y la sintaxis JS con
  `node --check`.

## Automatización y utilidades

- Scripts utilitarios para favicon (`favicon_utils.php`) e imágenes (`image_utils.php`), que almacenan
  recursos en `local_favicons/` y `fichas/` respectivamente.
- `device.php` detecta el dispositivo para personalizar la experiencia móvil.
- `cookies.php` centraliza la lógica de aceptación/rechazo de cookies opcionales.

## Integraciones complementarias

- **Aplicación Android:** `ShareReceiverActivity.kt` y `AndroidManifest.xml` permiten compartir URLs
  desde el sistema operativo hacia el formulario de alta web.
- **Bots y automatizaciones:** el directorio `bots/` contiene scripts auxiliares (por documentar) para
  tareas administrativas o de migración.

## Próximos pasos sugeridos

1. Cubrir con pruebas automatizadas los endpoints críticos (`load_links.php`, `move_link.php`, `delete_link.php`).
2. Documentar y robustecer los scripts en `bots/` para tareas periódicas.
3. Implementar limpieza programada de imágenes y tokens caducados.
4. Añadir monitoreo básico (logs estructurados y métricas) para despliegues en producción.
5. Evaluar la incorporación de colas o jobs diferidos para descargas de metadatos pesadas.

## Historial resumido

- **MVP inicial:** Autenticación básica, creación de tableros y guardado de enlaces.
- **Iteración actual:**
  - Se integró Google OAuth y se reforzaron los flujos de recuperación de contraseñas.
  - Se añadió la compartición pública de tableros y la descarga de metadatos avanzada.
  - Se implementó la documentación central (este directorio) con guías de instalación, uso y arquitectura.

Para detalles más profundos, consulta el [manual técnico](manual_tecnico.md) y la [referencia de endpoints](endpoints.md).
