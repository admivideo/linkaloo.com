# Guía de uso

Este documento describe el flujo básico para interactuar con **linkaloo** desde
la interfaz web.

## Primer acceso

1. Abre la página `index.php` y accede al enlace de registro o inicio de sesión.
2. Regístrate con correo y contraseña o autentícate con Google. Si compartiste un
   enlace desde otra aplicación, el parámetro `shared` se conservará y accederás
   a `nuevo_link.php` para completar el guardado.
3. Tras el registro se ofrece un asistente (`seleccion_tableros.php`) para crear
   tableros iniciales a partir de una lista de intereses; puedes omitirlo si
   prefieres comenzar con un lienzo en blanco.

## Panel principal

- La parte superior muestra un carrusel con tus tableros. Usa las flechas laterales
  o el desplazamiento horizontal para navegar cuando tengas muchos tableros.
- El botón "Todo" muestra enlaces de todas las categorías. Al cambiar de tablero
  la URL del navegador se actualiza con `?cat=<id>` para facilitar el acceso
  directo.
- Pulsa el icono de lupa para mostrar u ocultar el cuadro de búsqueda. El filtrado
  se aplica en cliente y respeta el tablero seleccionado.

## Guardar y editar enlaces

1. Haz clic en el botón `+` para abrir la vista "Guardar link".
2. Introduce la URL y, opcionalmente, un título personalizado. Puedes elegir un
   tablero existente o crear uno nuevo escribiendo su nombre.
3. Al guardar, el sistema descarga metadatos, genera una miniatura y evita
   duplicados automáticos.
4. Desde el menú contextual de cada tarjeta puedes mover el enlace, abrirlo en
   una nueva pestaña, compartirlo o eliminarlo.
5. Si necesitas editar la nota o el título manualmente, abre la tarjeta y utiliza
   el formulario de `editar_link.php`.

## Compartir tableros

- Cada tablero privado puede generar un enlace público mediante el menú de
  compartir del panel.
- El destinatario verá `tablero_publico.php?token=...`, una vista de solo lectura
  con las mismas tarjetas y botones de compartir individuales.
- Los botones de compartir detectan si el navegador soporta la Web Share API; si
  no, se recurre a AddToAny para abrir la ventana de selección de red social.

## Experiencia móvil

- La interfaz es responsiva y ajusta la altura de las tarjetas para mostrar dos
  columnas en pantallas pequeñas.
- `assets/main.js` detecta automáticamente móviles para recortar descripciones y
  optimizar la lectura.
- En Android, la actividad `ShareReceiverActivity` permite enviar un enlace desde
  otra aplicación y abre directamente `nuevo_link.php` con el formulario
  prellenado.
