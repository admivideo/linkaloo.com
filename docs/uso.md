# Guía de uso

Este documento describe el flujo funcional de **linkaloo** desde la perspectiva del usuario final.
Abarca desde el registro hasta la compartición pública de tableros y la gestión de la cuenta.

## 1. Registro e inicio de sesión

1. Abre `index.php` y elige entre **Registrarse** o **Iniciar sesión**.
2. Completa el formulario correspondiente:
   - **Registro:** introduce nombre, correo y contraseña. El formulario ejecuta reCAPTCHA v3; si falla mostrará un mensaje de error.
   - **Login:** escribe tu correo y contraseña. También puedes iniciar sesión con Google desde el botón «Google».
3. Si iniciaste sesión mediante un enlace compartido (`?shared=<URL>`), el parámetro se conserva y se utilizará para precargar el modal de creación de enlaces tras acceder al panel.

## 2. Selección inicial de tableros

Después del registro, `seleccion_tableros.php` propone una serie de intereses. Marca los que quieras convertir en tableros y pulsa **Siguiente**, o selecciona **Omitir** para comenzar con un tablero vacío. Puedes crear o renombrar tableros más adelante.

## 3. Panel principal (`panel.php`)

- **Navegación entre tableros:** el carrusel superior permite cambiar rápidamente entre tableros. El botón «Todo» muestra todas las fichas.
- **Búsqueda y filtros:** usa el icono de lupa para mostrar el buscador. El filtrado es instantáneo sobre las fichas cargadas.
- **Añadir enlaces:** pulsa el botón «+» para abrir el modal. Introduce la URL y opcionalmente el título o el tablero destino. El sistema extrae título, descripción, favicon e imagen automáticamente y evita duplicados. Si instalas la app como PWA en tu móvil, podrás usar el menú «Compartir» de otros navegadores: `share_target.php` recibe la URL y abre el panel con el modal precargado.
- **Acciones en una ficha:**
  - **Mover** a otro tablero mediante el desplegable.
  - **Compartir** con la Web Share API o AddToAny.
  - **Editar** abriendo la vista detallada (`editar_link.php`) para añadir notas o ajustar el título.
  - **Eliminar** desde el icono de papelera.
- **Carga progresiva:** el listado inicial muestra todas las fichas disponibles; `assets/main.js` aplica animaciones y recorta descripciones según el dispositivo.

## 4. Administración de tableros

- **Listado general (`tableros.php`):**
  - Crea nuevos tableros con el formulario superior.
  - Visualiza cuántos enlaces contiene cada tablero y accede al enlace público si está compartido.
  - Entra en el detalle mediante el icono de lápiz.
- **Detalle (`tablero.php`):**
  - Cambia el nombre o añade notas al tablero.
  - Activa «Compartir tablero públicamente» para generar un `share_token`. El botón de compartir copia/abre la URL `tablero_publico.php?token=...`.
  - Usa **Actualizar imágenes** para reintentar la obtención de metadatos de todas las fichas.
  - El botón **Eliminar tablero** borra el tablero y todos los enlaces asociados (requiere confirmación).

## 5. Tableros públicos

Cada tablero compartido dispone de una URL de solo lectura (`tablero_publico.php`). Los visitantes pueden:

- Navegar por las fichas con la misma presentación visual que el panel privado.
- Abrir los enlaces originales en una nueva pestaña.
- Compartir la URL del tablero completo o la de un enlace concreto con el icono correspondiente.

Revoca el acceso desmarcando la casilla de compartir en `tablero.php`; esto elimina el `share_token` y vuelve inaccesible la URL pública.

## 6. Gestión de la cuenta

- **Perfil (`cpanel.php`):** permite actualizar nombre y correo. Desde esta página también puedes cerrar sesión o acceder al cambio de contraseña.
- **Cambio de contraseña (`cambiar_password.php`):** introduce la contraseña actual y la nueva para actualizar `pass_hash`.
- **Cerrar sesión (`logout.php`):** elimina la sesión y la cookie «Recordarme» (`linkaloo_remember`).

## 7. Recuperación de contraseña

1. Desde el login selecciona «¿Olvidaste tu contraseña?» para ir a `recuperar_password.php`.
2. Introduce tu correo. Si existe, recibirás un email con un enlace válido durante 1 hora (`restablecer_password.php?token=...`).
3. Accede al enlace, establece una nueva contraseña y vuelve al login.

## 8. Consejos de uso

- Mantén tus tableros organizados usando notas y colores (la columna `color` está lista para personalizaciones futuras).
- El sistema detecta automáticamente URLs duplicadas por usuario; si necesitas guardar variantes de la misma página, añade parámetros distintos a la URL.
- El favicon y la imagen se almacenan en disco. Si cambias la URL original puedes reutilizar **Actualizar imágenes** para refrescar el contenido visual.
