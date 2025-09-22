# Manual técnico de linkaloo

## Resumen

Linkaloo es una aplicación web que permite guardar enlaces en tableros temáticos privados o públicos. Está construida con PHP 8, MySQL y una capa ligera de JavaScript para mejorar la experiencia de usuario. El código se organiza en scripts PHP que sirven la interfaz, gestionan la autenticación, operan sobre la base de datos y exponen endpoints usados por AJAX, además de utilidades para procesar imágenes y favicon.

## Arquitectura general

- **Presentación:** las vistas HTML se generan desde scripts PHP como `panel.php`, `tableros.php`, `tablero.php`, `login.php` y `register.php`, que incluyen `header.php` para compartir el encabezado y los recursos estáticos; el formulario de alta se ofrece en la página dedicada `agregar_favolink.php`, reutilizando la misma cabecera.【F:panel.php†L1-L40】【F:header.php†L10-L36】【F:agregar_favolink.php†L1-L112】
- **Capa de aplicación:** la lógica de negocio vive en los mismos scripts PHP e incorpora funciones auxiliares para normalizar URL, extraer metadatos y evitar duplicados. Otros scripts dedicados (`move_link.php`, `delete_link.php`, `load_links.php`) actúan como endpoints JSON que reciben peticiones asíncronas desde el cliente.【F:panel.php†L24-L123】【F:move_link.php†L1-L24】【F:delete_link.php†L1-L24】【F:load_links.php†L1-L33】
- **Persistencia:** todos los datos se almacenan en una base MySQL definida en `database.sql`. El acceso se realiza mediante PDO con consultas preparadas centralizadas en `config.php`, donde también se inicializa la conexión y se cargan claves de OAuth y reCAPTCHA desde variables de entorno.【F:config.php†L1-L35】【F:database.sql†L1-L44】
- **Clientes externos:** el repositorio incluye un receptor Android (`ShareReceiverActivity.kt`) que permite compartir URLs del sistema operativo hacia la aplicación web inyectando el parámetro `shared` en `agregar_favolink.php` al abrirse.【F:ShareReceiverActivity.kt†L1-L58】

## Directorios principales

| Ruta | Contenido |
| --- | --- |
| `assets/` | Código front-end (`main.js`) y estilos (`style.css`) cargados por todas las vistas a través de `header.php`.【F:header.php†L10-L29】 |
| `docs/` | Documentación modular del proyecto; este manual se complementa con guías de instalación, uso y referencia de endpoints. |
| `fichas/` | Almacena las imágenes descargadas para cada enlace cuando se guarda contenido remoto mediante `image_utils.php`.【F:image_utils.php†L1-L47】 |
| `img/` y `local_favicons/` | Activos estáticos usados en la interfaz; los favicons se generan bajo demanda con `favicon_utils.php`.【F:favicon_utils.php†L1-L32】 |
| `tableros.php`, `tablero.php`, `panel.php` | Páginas principales para administrar tableros, editar uno en detalle y listar enlaces respectivamente. |
| `oauth*.php`, `session.php` | Flujos de autenticación, gestión de sesiones, “recuérdame” y login con Google.【F:session.php†L1-L143】【F:oauth.php†L1-L32】【F:oauth2callback.php†L1-L75】 |

## Flujo de autenticación y cuentas

1. **Registro y login manual:** `register.php` y `login.php` verifican Google reCAPTCHA v3 antes de crear o validar credenciales. Las contraseñas se almacenan con `password_hash` y las sesiones se regeneran tras autenticarse.【F:register.php†L16-L52】【F:login.php†L22-L47】
2. **Recordar sesión:** `session.php` configura una vida útil de 365 días y emite cookies de “remember me” basadas en selectores y validadores almacenados en `usuario_tokens`. Las funciones `linkalooIssueRememberMeToken`, `linkalooAttemptAutoLogin` y `linkalooClearRememberMeToken` encapsulan este ciclo.【F:session.php†L1-L143】
3. **OAuth con Google:** `oauth.php` construye la URL de autorización con un token `state`, mientras que `oauth2callback.php` intercambia el `code` por tokens, obtiene el perfil y crea o actualiza al usuario antes de iniciar sesión. El parámetro opcional `shared` se conserva para precargar la vista `agregar_favolink.php` tras el login.【F:oauth.php†L1-L32】【F:oauth2callback.php†L1-L85】【F:agregar_favolink.php†L1-L112】
4. **Gestión de cuenta:** `cpanel.php` permite editar nombre y correo; `cambiar_password.php` valida la contraseña actual antes de actualizarla; `recuperar_password.php` y `restablecer_password.php` gestionan tokens temporales de recuperación almacenados en `password_resets`; `logout.php` destruye la sesión y revoca el token persistente.【F:cpanel.php†L1-L45】【F:cambiar_password.php†L1-L36】【F:recuperar_password.php†L1-L26】【F:restablecer_password.php†L1-L40】【F:logout.php†L1-L16】

## Gestión de tableros y enlaces

- **Creación y listado:** `panel.php` carga las categorías del usuario, filtra por tablero y muestra las tarjetas de enlaces, mientras que `agregar_favolink.php` gestiona la inserción normalizando la URL (`canonicalizeUrl`), extrayendo metadatos (`scrapeMetadata`), truncando textos según dispositivo y evitando duplicados con `hash_url`; si el usuario indica un tablero nuevo, se crea automáticamente.【F:panel.php†L1-L120】【F:agregar_favolink.php†L1-L112】
- **Movimientos y borrado:** las tarjetas incluyen un desplegable para mover enlaces entre tableros. Esta acción dispara `move_link.php`, que valida la autoría y actualiza la marca `modificado_en`. Los borrados usan `delete_link.php` con lógica similar.【F:move_link.php†L1-L24】【F:delete_link.php†L1-L24】
- **Administración de tableros:** `tableros.php` lista tableros con recuento de enlaces y botones para compartirlos. `tablero.php` permite renombrar, añadir notas, activar un token público (`share_token`), regenerar imágenes automáticamente y eliminar tableros completos. También muestra métricas de creación y modificación.【F:tableros.php†L1-L67】【F:tablero.php†L1-L128】
- **Edición detallada:** `editar_link.php` abre una ficha individual para actualizar título y nota, además de permitir su eliminación directa desde la vista detallada.【F:editar_link.php†L1-L53】
- **Selección inicial:** tras registrarse, `seleccion_tableros.php` ofrece una serie de tableros predeterminados que se insertan en lote si el usuario los marca, y respeta el parámetro `shared` para completar el flujo de alta rápida de enlaces compartidos.【F:seleccion_tableros.php†L1-L50】
- **Tableros públicos:** `tablero_publico.php` muestra la versión de solo lectura asociada a `share_token`, incluyendo el botón de compartir y truncado adaptativo según dispositivo.【F:tablero_publico.php†L1-L74】

## Interfaz y comportamiento del cliente

El archivo `assets/main.js` enriquece la navegación con JavaScript progresivo:

- Inicializa iconos Feather y gestiona el menú responsive. Controla el carrusel de tableros superior, guardando el desplazamiento horizontal en `sessionStorage` y filtrando tarjetas según la categoría activa.【F:assets/main.js†L1-L66】
- Implementa búsqueda en vivo, comparte enlaces usando la Web Share API (con fallback a AddToAny) y sincroniza el desplegable de movimiento con la vista cuando el servidor confirma el cambio.【F:assets/main.js†L67-L127】
- Observa las tarjetas para animarlas al entrar en pantalla, limita la longitud de las descripciones según el ancho del dispositivo y limpia el parámetro `shared` enfocando el formulario de `agregar_favolink.php` cuando está presente.【F:assets/main.js†L129-L214】
- Maneja los botones de borrado en la vista detallada y cierra avisos de error con delegación de eventos.【F:assets/main.js†L129-L207】

## Endpoints y utilidades de soporte

- **Carga paginada:** `load_links.php` sigue disponible para recuperar lotes paginados de enlaces (18 por solicitud) aplicando el mismo truncado condicional y adjuntando favicons locales. Puede reutilizarse si se reintroduce el scroll infinito.【F:load_links.php†L1-L33】
- **Procesamiento de medios:** `image_utils.php` descarga imágenes remotas, las normaliza (máximo 300 px de ancho) y las guarda por usuario. `favicon_utils.php` obtiene favicons desde Google, los redimensiona a 25×25 px y los almacena en caché local.【F:image_utils.php†L1-L47】【F:favicon_utils.php†L1-L32】
- **Detección de dispositivo:** `device.php` detecta navegadores móviles para ajustar los límites de texto tanto en PHP como en JavaScript.【F:device.php†L1-L6】

## Base de datos

La estructura definida en `database.sql` contempla cinco tablas principales:【F:database.sql†L1-L44】

- `usuarios`: identificador, nombre, correo único y `pass_hash` para autenticación.
- `categorias`: tableros por usuario con campos para color, imagen destacada, nota, token público y marcas de auditoría.
- `links`: enlaces asociados a un tablero, con URL original y canónica, metadatos, favicon, nota, etiquetas opcionales y `hash_url` único por usuario.
- `password_resets`: tokens de recuperación con expiración de una hora.
- `usuario_tokens`: tokens persistentes para el inicio de sesión prolongado, con índices por usuario y expiración.

## Integraciones externas

- **Google OAuth y reCAPTCHA:** se configuran mediante las variables de entorno `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY`, con valores de respaldo en `config.php`.【F:config.php†L21-L34】
- **Web Share API y AddToAny:** la interfaz usa la API nativa cuando está disponible; en su defecto abre AddToAny con los parámetros adecuados.【F:assets/main.js†L67-L126】
- **Favicons de Google y redimensionado GD:** `favicon_utils.php` y `image_utils.php` dependen de cURL y la extensión GD para descargar y ajustar imágenes a tamaños consistentes.【F:image_utils.php†L16-L46】【F:favicon_utils.php†L16-L31】
- **Aplicación Android:** `ShareReceiverActivity` y `AndroidManifest.xml` habilitan compartir texto/URL desde Android y abren Linkaloo con la URL enviada, añadiendo el parámetro `shared` cuando procede.【F:ShareReceiverActivity.kt†L10-L58】【F:AndroidManifest.xml†L1-L25】

## Configuración y comprobaciones

- Ajusta las credenciales de base de datos en `config.php` o por variables de entorno antes de desplegar.【F:config.php†L1-L24】
- Ejecuta las verificaciones rápidas documentadas (`php -l …`, `node --check assets/main.js`, `npm run lint:css`) para detectar errores comunes durante el desarrollo.【F:docs/README.md†L9-L18】【F:package.json†L5-L15】

## Consideraciones de seguridad y buenas prácticas

- Todas las consultas SQL usan sentencias preparadas con PDO, reduciendo el riesgo de inyección.【F:panel.php†L57-L87】【F:tablero.php†L41-L78】
- Las contraseñas se almacenan con algoritmos de hashing robustos (`password_hash` con BCRYPT) y se verifican con `password_verify`.【F:register.php†L33-L46】【F:cambiar_password.php†L14-L30】
- Los tokens de sesión persistente y de recuperación se invalidan tras su uso o expiración, evitando reutilización.【F:session.php†L83-L143】【F:restablecer_password.php†L16-L34】
- La sanitización y truncado de contenido (título, descripción) se aplican en servidor y cliente para asegurar consistencia y evitar problemas de encoding.【F:panel.php†L24-L96】【F:assets/main.js†L139-L168】
