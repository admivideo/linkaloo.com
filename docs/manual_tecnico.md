# Manual técnico de linkaloo

## Resumen

linkaloo es una aplicación web que permite a cada persona organizar enlaces en tableros privados y,
si lo desea, compartirlos públicamente. El backend está escrito en PHP 8 con MySQL como base de datos,
y el front-end se apoya en HTML generado por los propios scripts PHP, estilos CSS y JavaScript vanilla
para mejorar la experiencia de usuario. El repositorio también incluye un receptor Android que facilita
compartir URL del sistema hacia la aplicación web.

## Tecnologías y dependencias

- **Lenguajes:** PHP 8, JavaScript (ES2020) y HTML/CSS.
- **Base de datos:** MySQL 8 con codificación `utf8mb4` (ver `database.sql`).
- **Dependencias de PHP:** extensiones PDO (con driver MySQL), cURL, mbstring, DOM, GD y JSON.
- **Dependencias de Node:** Stylelint y `stylelint-config-standard` se instalan mediante `npm install` y
  se utilizan únicamente para validar la hoja de estilos (`npm run lint:css`).
- **Servicios externos:** Google OAuth 2.0, reCAPTCHA v3, Web Share API/AddToAny para compartir enlaces y
  el correo saliente que usa `mail()` para recuperar contraseñas.

## Organización del proyecto

### Directorios y archivos clave

| Ruta | Descripción |
| --- | --- |
| `assets/` | Front-end: `main.js` controla interacción y `style.css` define estilos globales. |
| `docs/` | Documentación técnica y guías de uso. |
| `fichas/` | Imágenes asociadas a los enlaces guardados (se generan con `image_utils.php`). |
| `img/`, `local_favicons/` | Recursos estáticos y favicons cacheados localmente. |
| `tableros.php`, `panel.php`, `tablero.php` | Vistas principales para listar, administrar y detallar tableros. |
| `agregar_favolink.php` | Formulario para crear enlaces, con scraping opcional de metadatos. |
| `move_link.php`, `delete_link.php`, `load_links.php` | Endpoints JSON que soportan acciones asíncronas. |
| `oauth.php`, `oauth2callback.php` | Flujo de autenticación con Google. |
| `session.php` | Configura sesiones, cookies persistentes y validación de URL compartidas. |
| `ShareReceiverActivity.kt`, `AndroidManifest.xml` | Cliente Android que comparte enlaces hacia la aplicación web. |
| `cookies.php`, `politica_cookies.php`, `condiciones_servicio.php`, etc. | Páginas legales enlazadas desde el menú de ajustes. |

### Scripts PHP principales

| Script | Rol dentro del sistema |
| --- | --- |
| `index.php` | Redirige a `panel.php` si la sesión está iniciada o a `login.php` en caso contrario conservando la query string original. |
| `login.php` | Formulario de autenticación con reCAPTCHA v3, emisión de token "remember me" y soporte del parámetro `shared` para continuar el flujo de alta rápida. |
| `register.php` | Registro de usuarios con verificación reCAPTCHA, inserción del usuario y redirección a `seleccion_tableros.php`. |
| `seleccion_tableros.php` | Permite crear tableros iniciales a partir de una lista predefinida y mantiene el parámetro `shared`. |
| `panel.php` | Panel principal: obtiene tableros y enlaces del usuario activo, prepara métricas para publicidad y pinta las tarjetas. |
| `agregar_favolink.php` | Gestiona el alta de enlaces: normaliza URL, obtiene metadatos con cURL/DOM, descarga imágenes y evita duplicados mediante `hash_url`. |
| `tableros.php` | Listado de tableros con recuentos de enlaces, accesos a edición y botón de compartición pública. |
| `tablero.php` | Detalle de un tablero: edición de nombre/nota, activación del token público, refresco de imágenes y eliminación. |
| `tablero_publico.php` | Vista de solo lectura accesible por token (`share_token`) para compartir tableros sin iniciar sesión. |
| `editar_link.php` | Edición individual de un enlace (título y nota) y eliminación directa. |
| `cpanel.php`, `cambiar_password.php` | Gestión de perfil y actualización de contraseña. |
| `recuperar_password.php`, `restablecer_password.php` | Flujo de recuperación de contraseña mediante token temporal enviado por correo. |
| `logout.php` | Revoca la cookie persistente, destruye la sesión y redirige al login. |

## Front-end y experiencia de usuario

El archivo `header.php` incluye de forma consistente los recursos comunes, controla la caché de assets y
muestra el menú principal con enlaces a la legalidad y a las acciones del usuario. `assets/main.js`
inyecta interactividad al panel y a las vistas de tableros:

- Gestiona el menú responsive y el carrusel de tableros, recordando la posición en `sessionStorage`.
- Aplica filtros por tablero y búsqueda en vivo sobre las tarjetas renderizadas en servidor.
- Sincroniza el desplegable para mover enlaces (`move_link.php`) y la acción de borrado (`delete_link.php`).
- Implementa la experiencia de compartición con la Web Share API y, cuando no está disponible, abre AddToAny.
- Realiza pequeños ajustes de accesibilidad: foco automático en `agregar_favolink.php` cuando se llega con el
  parámetro `shared`, truncado dinámico de descripciones y cierre de avisos.

Aunque existe un endpoint `load_links.php` para paginar contenido, el front-end actual carga todos los enlaces
desde PHP y utiliza el endpoint como recurso disponible para futuras mejoras.

## Gestión de sesiones y autenticación

- `session.php` define la duración (365 días) y las propiedades de la sesión, genera cookies persistentes,
  valida URL externas (`isValidSharedUrl`) y expone utilidades para el ciclo "remember me".
- En `login.php` y `register.php` se invocan reCAPTCHA v3 (`action` "login" o "register") antes de validar
  credenciales. Las contraseñas se guardan con `password_hash` y se regeneran los identificadores de sesión.
- `oauth.php` inicia el flujo de OAuth con Google, guarda el estado y el posible parámetro `shared`; 
  `oauth2callback.php` intercambia el `code` por tokens, consulta `userinfo`, crea o actualiza al usuario y
  finaliza emitiendo una nueva cookie persistente. 
- La recuperación de contraseñas se apoya en `password_resets`: `recuperar_password.php` genera un token y
  envía un correo, mientras que `restablecer_password.php` comprueba la vigencia del token antes de aceptar la
  nueva contraseña.

## Gestión de tableros y enlaces

- `tableros.php` permite crear nuevos tableros, muestra la cantidad de enlaces y, si el tablero tiene token
  público, ofrece un botón de compartición. `tablero.php` amplía esa información y habilita la edición de nombre,
  nota, compartición pública y la actualización masiva de imágenes (descargando metadatos de cada URL).
- `panel.php` lista todas las tarjetas del usuario, adjunta favicons locales mediante `getLocalFavicon()` y
  controla la frecuencia con la que se muestran espacios publicitarios por tablero.
- `agregar_favolink.php` normaliza y completa los datos del enlace (metadatos, favicon, imagen) y evita
  duplicados calculando un `hash_url` canónico por usuario. Si se añade el nombre de un tablero nuevo, se crea
  automáticamente antes de insertar el enlace.
- `move_link.php` y `delete_link.php` actualizan la marca `modificado_en` del tablero afectado para mantener la
  información consistente. `editar_link.php` proporciona una vista centrada en un enlace concreto para ajustes
  puntuales.
- `tablero_publico.php` expone la versión de solo lectura asociada a `share_token`, reutilizando el mismo
  truncado de títulos y descripciones que el panel privado.

## Endpoints y procesos asíncronos

Los endpoints que devuelven JSON se encuentran en la raíz del proyecto y requieren una sesión activa; en caso
contrario responden con `401` o con `{ "success": false }`.

| Endpoint | Método | Descripción y parámetros |
| --- | --- | --- |
| `load_links.php` | `GET` | Devuelve lotes de 18 enlaces. Admite `offset` y `cat` (`all` o un identificador numérico) y adjunta el favicon resuelto. |
| `move_link.php` | `POST` | Recibe `id` y `categoria_id` (`application/x-www-form-urlencoded`). Mueve el enlace y devuelve `{ "success": true }` si el usuario es propietario. |
| `delete_link.php` | `POST` | Recibe `id` y elimina el enlace del usuario autenticado. La respuesta indica si la operación tuvo éxito. |

Además, `tablero_publico.php` actúa como endpoint público de solo lectura identificado por el parámetro
`token`. Aunque no requiere sesión, únicamente muestra tableros a los que se haya asignado un `share_token`.

## Base de datos

El archivo `database.sql` crea las tablas necesarias, todas con codificación `utf8mb4` y claves primarias
enteras. A nivel funcional se utilizan cinco tablas principales:

| Tabla | Propósito | Campos destacados |
| --- | --- | --- |
| `usuarios` | Personas registradas. | `id`, `nombre`, `email` (único), `pass_hash`, marcas de creación/modificación. |
| `categorias` | Tableros personales. | `usuario_id`, `nombre`, `color`, `nota`, `share_token`, `imagen`, `creado_en`, `modificado_en`. |
| `links` | Enlaces guardados. | `categoria_id`, `url`, `url_canonica`, `titulo`, `descripcion`, `imagen`, `nota`, `hash_url`, `creado_en`. |
| `password_resets` | Tokens temporales de recuperación. | `usuario_id`, `token`, `expiracion`. |
| `usuario_tokens` | Tokens persistentes para "remember me". | `usuario_id`, `selector`, `token_hash`, `expires_at`.

Se recomienda ejecutar `database.sql` en una base de datos vacía durante la instalación inicial y añadir los
índices necesarios si se amplía el modelo (por ejemplo, índices sobre `links.hash_url` y `links.categoria_id`).

## Procesamiento de medios y utilidades de soporte

- `favicon_utils.php` descarga favicons desde Google, los redimensiona a 25×25 píxeles y los almacena en
  `local_favicons/` para reutilizarlos. Usa la extensión GD para manipular imágenes.
- `image_utils.php` descarga imágenes remotas referenciadas en metadatos OpenGraph, las normaliza a un ancho
  máximo de 300 píxeles y las guarda en `fichas/` agrupadas por usuario.
- `device.php` expone `isMobile()` para ajustar la longitud máxima de descripciones en PHP y JavaScript.
- `session.php` incluye utilidades comunes: validación estricta de URL externas (`isValidSharedUrl`) y helpers
  para gestionar cookies persistentes (`linkalooIssueRememberMeToken`, `linkalooClearRememberMeToken`, etc.).

## Integraciones externas

- **Google OAuth:** configurable mediante `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` (por
  defecto apuntan a `https://linkaloo.com/oauth2callback.php`). Permite autenticación en un paso.
- **reCAPTCHA v3:** `login.php` y `register.php` incluyen los tokens de Google antes de procesar formularios.
  Las claves se leen de `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY` con valores de respaldo en `config.php`.
- **Web Share API y AddToAny:** la primera opción intenta compartir directamente desde el navegador; si no está
  disponible, se construye una URL hacia AddToAny.
- **Aplicación Android:** `ShareReceiverActivity.kt` y `AndroidManifest.xml` registran un intent filter
  (`https://linkaloo.com/agregar_favolink.php`) para que el usuario pueda compartir enlaces desde otras apps y
  completar automáticamente el formulario de alta.

## Configuración y despliegue

- `config.php` contiene las credenciales de base de datos y las claves por defecto de OAuth/reCAPTCHA. Para entornos
  reales es recomendable sobreescribir estos valores con variables de entorno o ajustar el archivo antes de desplegar.
- El servidor puede ejecutarse en local con `php -S localhost:8000` desde la raíz del proyecto. Si se usa HTTPS en
  producción, las cookies de sesión se envían con la marca `secure` activada automáticamente.
- El correo saliente usa `mail()`; en producción conviene configurar un MTA o adaptar el envío a un proveedor externo.

## Comprobaciones y mantenimiento

Antes de subir cambios ejecuta las verificaciones básicas descritas en [docs/README.md](README.md):

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

También es aconsejable revisar que `database.sql` refleje cualquier modificación del modelo y que las rutas
públicas sigan sirviendo correctamente después de un despliegue.

## Buenas prácticas y seguridad

- Todas las consultas a la base de datos usan sentencias preparadas con PDO para prevenir inyecciones SQL.
- Las contraseñas se almacenan mediante `password_hash` (BCRYPT) y los tokens persistentes se invalidan tras
  su uso o expiración.
- El token `share_token` genera URLs impredecibles para los tableros públicos; al desactivar la opción se elimina.
- Los formularios y los scripts de scraping convierten texto a UTF-8 y limitan longitud de campos para evitar
  problemas de encoding y mejorar la presentación.
- `isValidSharedUrl` valida de forma estricta el parámetro `shared` antes de redirigir al formulario de alta,
  mitigando riesgos derivados de URLs maliciosas.
