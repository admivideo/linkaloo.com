# Guía de uso

Esta guía describe los flujos principales de **linkaloo** desde la perspectiva de un usuario final.

## Acceso y registro

1. Abre `login.php`. Si ya tienes una sesión activa, `index.php` te redirigirá automáticamente al panel.【F:index.php†L1-L10】
2. Para registrarte, completa nombre, email y contraseña en `register.php`. Ambos formularios integran reCAPTCHA v3 y admiten un parámetro `shared` que prellena el modal de añadir enlace tras iniciar sesión.【F:register.php†L1-L69】【F:login.php†L1-L71】【F:assets/main.js†L199-L228】
3. También puedes autenticarte con Google. `oauth.php` inicia el flujo OAuth y `oauth2callback.php` crea o reutiliza el usuario local antes de redirigirte al panel o al asistente de tableros.【F:oauth.php†L1-L32】【F:oauth2callback.php†L1-L76】

## Selección inicial de tableros

Después de registrarte o de iniciar sesión por primera vez con Google se muestra `seleccion_tableros.php`, donde puedes marcar intereses predefinidos que se convertirán en tableros propios. Puedes omitir este paso y crear tableros más tarde.【F:seleccion_tableros.php†L1-L68】

## Panel principal (`panel.php`)

`panel.php` es el centro de trabajo diario:

- La barra superior permite cambiar de tablero, abrir el buscador y lanzar el modal para añadir enlaces.【F:header.php†L22-L83】【F:assets/main.js†L1-L96】
- El formulario del modal guarda una URL, título opcional y tablero destino (existente o nuevo). El servidor descarga metadatos (Open Graph), genera una imagen local y evita duplicados por usuario.【F:panel.php†L81-L150】
- Cada tarjeta ofrece botones para compartir (Web Share API/AddToAny) y un selector para moverla rápidamente a otro tablero mediante `move_link.php`.【F:assets/main.js†L92-L168】【F:move_link.php†L1-L25】
- El buscador filtra en vivo por texto y la vista recuerda el desplazamiento horizontal de la lista de tableros en `sessionStorage` para mantener el contexto tras recargar.【F:assets/main.js†L17-L90】
- Si llegas desde un enlace compartido con el parámetro `shared`, el modal se abre automáticamente con la URL prellenada.【F:assets/main.js†L199-L228】
- Al pulsar el icono de la papelera desde la vista de detalle (`editar_link.php`) se elimina la ficha vía AJAX (`delete_link.php`).【F:editar_link.php†L1-L55】【F:assets/main.js†L146-L185】【F:delete_link.php†L1-L24】

## Gestión de tableros

- `tableros.php` lista todos los tableros con conteo de enlaces, miniatura y accesos rápidos para compartir o editar. Desde aquí también puedes crear nuevos tableros por nombre.【F:tableros.php†L1-L71】
- `tablero.php` permite renombrar un tablero, añadir notas, activar su modo público (genera `share_token`), regenerar imágenes mediante scraping y eliminarlo. Muestra estadísticas de creación/modificación y un mosaico de imágenes recientes.【F:tablero.php†L71-L143】
- Cuando un tablero es público, el botón de compartir abre la URL `tablero_publico.php?token=...`, lista de solo lectura ideal para enviar a otros usuarios.【F:tablero.php†L104-L140】【F:tablero_publico.php†L1-L74】

## Edición de enlaces

- Al hacer clic en el icono de editar desde `tableros.php` se abre `editar_link.php`, que muestra título, URL, descripción, fecha de creación y permite modificar título/nota del enlace.【F:editar_link.php†L1-L55】
- El favicon se obtiene del dominio asociado a la URL mediante `getLocalFavicon` para mantener coherencia visual.【F:editar_link.php†L34-L40】【F:favicon_utils.php†L1-L33】

## Cuenta y seguridad

- `cpanel.php` permite actualizar nombre y correo guardados en `usuarios`. Desde ahí puedes acceder a `cambiar_password.php` para modificar la contraseña actual.【F:cpanel.php†L1-L45】【F:cambiar_password.php†L1-L39】
- Si olvidas la contraseña, `recuperar_password.php` genera un correo con un enlace temporal (`restablecer_password.php`). Tras definir una nueva contraseña, el token se invalida.【F:recuperar_password.php†L1-L31】【F:restablecer_password.php†L1-L44】
- `logout.php` cierra la sesión, borra cookies y tokens persistentes antes de redirigir al login.【F:logout.php†L1-L17】

## Tableros públicos

Los tableros marcados como públicos son visibles en `tablero_publico.php`. Las tarjetas muestran favicon, título truncado según dispositivo, descripción y botón para compartir el enlace original. No es necesario iniciar sesión para consultar un tablero público.【F:tablero_publico.php†L1-L74】【F:assets/main.js†L92-L149】

## Buenas prácticas para usuarios

- Usa tableros descriptivos y notas para agrupar enlaces y recordar el contexto.【F:tablero.php†L71-L140】
- Aprovecha el botón de «Actualizar imágenes» en `tablero.php` si los metadatos de tus enlaces han cambiado; el sistema volverá a intentar descargar `og:image` y fallback a favicons locales si es necesario.【F:tablero.php†L42-L101】
- Comparte tableros públicos solo con contenido que quieras divulgar; puedes revocar el acceso desmarcando la casilla de «Compartir tablero públicamente», lo que elimina el `share_token`.【F:tablero.php†L83-L140】
