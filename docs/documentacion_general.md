# Documentación general de linkaloo

Este documento resume el funcionamiento de la aplicación hasta la fecha, de forma que cualquier persona
pueda entender qué hace, cómo está organizada y qué dependencias necesita para ejecutarse. Complementa
el resto de guías del directorio `docs/` con una visión transversal del producto.

## Panorama funcional

- **Objetivo:** guardar enlaces en tableros personales, organizarlos por tema y compartirlos con otras
  personas.
- **Back-end:** PHP 8.1+ sin framework, con MySQL como base de datos y PDO para el acceso a datos.
- **Front-end:** HTML generado por PHP, hoja de estilos `assets/style.css` y JavaScript vanilla en
  `assets/main.js` para filtros, búsqueda, formularios modales y compartición.
- **Integraciones externas:** autenticación opcional con Google OAuth 2.0, validación reCAPTCHA v3 en
  formularios de autenticación, envío de correos para recuperación de contraseña y compatibilidad con la
  Web Share API / AddToAny. Incluye un receptor Android (`ShareReceiverActivity.kt`) para compartir URLs
  desde el sistema operativo.

## Vistas y flujo de usuario

1. **Inicio de sesión y registro** (`login.php`, `register.php`): formularios con validación reCAPTCHA v3.
   Al autenticarse se genera una sesión PHP y, si se marca la opción, una cookie persistente de 365 días.
2. **Selección inicial de tableros** (`seleccion_tableros.php`): se ofrecen tableros sugeridos tras el
   registro para rellenar rápidamente la cuenta.
3. **Panel principal** (`panel.php`): muestra todas las tarjetas de enlaces con carrusel de tableros,
   buscador en vivo, botones de compartir, mover o eliminar, y atajo al formulario de alta.
4. **Gestión de tableros** (`tableros.php`, `tablero.php`): creación, renombrado, notas internas, borrado y
   activación de compartición pública mediante `share_token`. Incluye botón para regenerar imágenes.
5. **Alta de enlaces** (`agregar_favolink.php`): permite pegar una URL, descargar metadatos (título,
   descripción, imagen y favicon), elegir tablero existente o crear uno nuevo sobre la marcha.
6. **Recuperación de contraseña** (`recuperar_password.php`, `restablecer_password.php`): generación y
   validación de tokens temporales enviados por correo.
7. **Vistas públicas y legales** (`tablero_publico.php`, `politica_privacidad.php`,
   `politica_cookies.php`, `condiciones_servicio.php`, `quienes_somos.php`): acceso en solo lectura y
   cumplimiento normativo.

## Endpoints y lógica asíncrona

- `load_links.php` devuelve enlaces paginados para el usuario autenticado y admite filtro por tablero.
- `move_link.php` cambia un enlace de tablero y actualiza la fecha de modificación.
- `delete_link.php` elimina enlaces del usuario y sincroniza el estado del tablero.
- `tablero_publico.php` sirve tableros compartidos mediante `share_token` sin requerir autenticación.
- Las peticiones se realizan con `fetch()` desde `assets/main.js`, que también implementa Web Share API y
  el fallback AddToAny cuando el navegador no soporta la API nativa.

## Persistencia y archivos relevantes

- **Esquema:** definido en `database.sql` con tablas para usuarios, tableros (`categorias`), enlaces
  (`favoritos`), tokens persistentes (`usuario_tokens`) y solicitudes de reseteo (`password_resets`).
- **Archivos utilitarios:** `session.php` centraliza el manejo de sesiones y cookies; `favicon_utils.php`
  y `image_utils.php` descargan, redimensionan y cachean recursos en `local_favicons/` y `fichas/`.
- **Activos estáticos:** `assets/style.css` contiene el estilo principal; `assets/main.js` maneja la
  interacción en el panel y los modales; `img/` almacena recursos de interfaz.

## Seguridad y cumplimiento

- Cookies de sesión configuradas como `HttpOnly`, `SameSite=Lax` y `secure` automático bajo HTTPS.
- Contraseñas almacenadas con `password_hash()`; tokens persistentes guardados como hash.
- Validación de URLs compartidas para evitar redirecciones abiertas mediante `isValidSharedUrl()`.
- Páginas legales separadas para privacidad, cookies, términos y quiénes somos, editables según la
  normativa del despliegue.

## Requisitos y operación

- **Dependencias mínimas:** PHP 8.1+, extensiones PDO (MySQL), cURL, mbstring, DOM, GD y JSON; MySQL 8 o
  compatible. Node.js 18+ solo si se ejecuta Stylelint en desarrollo.
- **Preparación rápida:** clonar el repositorio, ejecutar `database.sql`, completar `config.php` o las
  variables de entorno correspondientes, instalar dependencias opcionales con `npm install` y levantar un
  servidor con `php -S localhost:8000`.
- **Comprobaciones recomendadas:**

  ```bash
  php -l config.php panel.php move_link.php load_links.php
  node --check assets/main.js
  npm run lint:css
  ```

- **Mantenimiento sugerido:** limpieza periódica de imágenes o tokens caducados, documentación de scripts
  en `bots/` y monitorización básica de logs/métricas en despliegues productivos.

## Historial resumido

- **MVP:** autenticación propia, creación de tableros y guardado de enlaces.
- **Iteraciones posteriores:** integración con Google OAuth y reCAPTCHA v3, compartición pública de
  tableros, descarga avanzada de metadatos con cacheo de imágenes/favicons y documentación completa en
  `docs/`.

Para obtener detalles pormenorizados sobre arquitectura, configuración o flujos operativos, consulta el
[manual técnico](manual_tecnico.md), la [referencia de endpoints](endpoints.md) y la [guía de uso](uso.md).
