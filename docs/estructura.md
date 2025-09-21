# Estructura y arquitectura del proyecto

Este documento ofrece una visión técnica de **linkaloo**, describiendo la
organización del código, los componentes que intervienen en cada flujo y la
estructura de la base de datos.

## Mapa de directorios principales

- `index.php` – página pública con el formulario de acceso.
- `login.php`, `register.php`, `logout.php` – flujo de autenticación clásico.
- `panel.php` – panel privado donde se crean tableros y se gestionan enlaces.
- `tablero.php`, `tableros.php`, `tablero_publico.php` – vistas relacionadas con
  un tablero concreto o su versión compartida.
- `assets/` – recursos estáticos (JavaScript y CSS) utilizados por el panel y las
  pantallas de login.
- `img/` – logotipos, iconos y gráficos que se sirven tal cual.
- `local_favicons/` y `fichas/` – directorios generados en tiempo de ejecución
  para almacenar favicons descargados y miniaturas de enlaces.
- `docs/` – la documentación que estás leyendo.
- `AndroidManifest.xml` y `ShareReceiverActivity.kt` – código auxiliar para la
  integración con Android (recepción de enlaces compartidos).

## Componentes del backend

| Archivo | Responsabilidad principal |
| --- | --- |
| `config.php` | Define la conexión PDO y expone las claves de Google OAuth y reCAPTCHA. |
| `session.php` | Configura parámetros de sesión, cookies "Recuérdame" y utilidades para validar URLs compartidas. |
| `panel.php` | Alta de enlaces, creación de tableros y renderizado del panel privado. |
| `load_links.php` | Devuelve un lote paginado de enlaces en formato JSON. |
| `move_link.php` | Actualiza la categoría asociada a un enlace. |
| `delete_link.php` | Elimina enlaces y sincroniza la fecha de modificación del tablero. |
| `seleccion_tableros.php` | Asistente para crear tableros iniciales tras el registro o un login por OAuth. |
| `tablero_publico.php` | Muestra un tablero de solo lectura a partir de un token de compartición. |
| `oauth.php` / `oauth2callback.php` | Negocian el login con Google y redirigen de vuelta a la aplicación. |
| `recuperar_password.php` / `restablecer_password.php` | Implementan el flujo de recuperación de contraseña. |
| `favicon_utils.php` | Descarga favicons desde Google S2 y los almacena localmente. |
| `image_utils.php` | Descarga, redimensiona y guarda las imágenes principales de los enlaces. |
| `device.php` | Detecta si la petición proviene de un dispositivo móvil (usado para limitar textos). |

Todas las vistas incluyen `header.php`, que centraliza el marcado repetitivo y
la carga de recursos externos como Feather Icons.

## Componentes del frontend

- `assets/main.js` controla la interacción del panel: selección de tableros,
  filtros de búsqueda, navegación y envío de
  formularios AJAX (`move_link.php`, `delete_link.php`), animaciones de entrada y
  funciones de compartición que recurren a la Web Share API o a AddToAny como
  alternativa.
- `assets/style.css` define el diseño responsivo: rejilla de tarjetas,
  navegación horizontal de tableros, formularios y vistas de login. El estilo se
  valida con Stylelint (`npm run lint:css`).

## Flujo de creación de enlaces

1. El usuario abre la vista `nuevo_link.php` desde el botón "+" del encabezado.
2. Al enviar el formulario se valida o crea la categoría destino.
3. `panel.php` descarga metadatos (`scrapeMetadata`) y favicons, normaliza la URL
   (`canonicalizeUrl`) y evita duplicados calculando un hash SHA-1.
4. Si hay imagen, `image_utils.php` la guarda en `fichas/<usuario>/` y ajusta su
   tamaño máximo a 300 px de ancho.
5. Se inserta el registro en `links` y se actualiza `categorias.modificado_en`.
6. El cliente vuelve al panel y `assets/main.js` refresca la vista.

## Autenticación y sesiones

- Las sesiones PHP duran 365 días (`session.php`) y establecen cookies con
  `SameSite=Lax` y `HttpOnly`.
- Los tokens "Recuérdame" se guardan en `usuario_tokens` y se eliminan en cada
  nuevo inicio de sesión.
- El inicio de sesión con Google utiliza los endpoints oficiales de OAuth 2.0 y
  almacena el `state` en sesión para mitigar ataques CSRF.
- Los formularios de login y registro admiten un parámetro `shared` que permite
  redirigir al usuario a `panel.php?shared=<url>` tras autenticarse.

## Tableros públicos y compartición

Cada tablero puede generar un `share_token`. La vista pública (`tablero_publico.php`)
utiliza dicho token para recuperar el tablero y las fichas asociadas. Las
herramientas de compartición generan un enlace permanente y, cuando es posible,
preparan metadatos enriquecidos (imagen destacada y descripción recortada según
el dispositivo).

## Integración con Android

`ShareReceiverActivity.kt` actúa como *share target*: intercepta enlaces
compartidos en Android y abre directamente `nuevo_link.php` con el parámetro
`shared=<url>` para mostrar el formulario de alta con la URL ya rellenada.

## Esquema de la base de datos

La base de datos se define en `database.sql` y utiliza codificación `utf8mb4`.

- **usuarios**: credenciales básicas (`id`, `nombre`, `email`, `pass_hash`).
- **categorias**: tableros del usuario, con atributos opcionales como `color`,
  `nota` e `imagen`. `share_token` almacena el identificador público y
  `modificado_en` permite ordenar los tableros recientes.
- **links**: enlaces guardados por cada usuario. Incluyen URL original y
  canónica, metadatos (`titulo`, `descripcion`, `imagen`, `favicon`), nota
  interna, etiquetas y un hash (`hash_url`) para evitar duplicados por usuario.
- **password_resets**: tokens temporales para restablecer contraseñas.
- **usuario_tokens**: almacena los tokens persistentes de la funcionalidad
  "Recuérdame".
