# Guía de uso

Esta guía describe el flujo básico para trabajar con **linkaloo** desde la interfaz web. Todas las vistas
comparten la cabecera (`header.php`), desde la que puedes acceder al panel, a tus tableros, a la cuenta y a
las páginas legales.

## Registro e inicio de sesión

1. Abre `http://localhost:8000` (o la URL desplegada) para acceder al formulario de inicio (`login.php`).
2. Inicia sesión con tu correo y contraseña o usa el botón **Google** para autenticarte mediante OAuth.
3. Si aún no tienes cuenta, sigue el enlace **Registrarse** y completa el formulario (`register.php`). Se
   validará el reCAPTCHA v3 antes de crear el usuario. Al finalizar se abrirá `seleccion_tableros.php` para que
   elijas tableros iniciales.
4. Al iniciar sesión se genera una cookie "remember me" válida durante 365 días, de modo que la siguiente vez
   accederás directamente al panel.

## Gestionar tableros

- En `tableros.php` verás todos tus tableros con el recuento de enlaces y accesos directos para compartirlos o
  editarlos.
- Pulsa **Crear** para añadir un nuevo tablero. También puedes crear uno desde `agregar_favolink.php` escribiendo
  un nombre en el campo "o crea un nuevo".
- Abre un tablero concreto desde `tablero.php` para editar su nombre, añadir una nota, activar la compartición
  pública o eliminarlo por completo. El botón **Actualizar imágenes** vuelve a descargar las portadas de los
  enlaces guardados.

## Añadir y organizar enlaces

1. Usa el botón **+** del menú o el acceso directo en `panel.php` para abrir `agregar_favolink.php`.
2. Pega la URL en "Pega aquí el link". El sistema intentará recuperar título, descripción e imagen mediante
   scraping y se encargará de generar un favicon si no hay imagen.
3. Selecciona un tablero existente o crea uno nuevo. Al guardar, se comprueba automáticamente si la URL ya
   existe para el usuario (a través de `hash_url`).
4. Las tarjetas aparecerán en el panel con botones para editar, compartir, mover o eliminar cada enlace.
5. Cambia un enlace de tablero con el desplegable; la acción es inmediata y se sincroniza con el servidor sin
   recargar la página.

## Búsqueda y navegación

- El carrusel superior del panel permite filtrar por tablero. El desplazamiento lateral se recuerda durante la
  sesión.
- Pulsa el icono de búsqueda para mostrar el campo de filtro. Escribe palabras clave y las tarjetas se filtrarán
  en tiempo real.
- Las tarjetas se animan al entrar en pantalla y muestran descripciones truncadas según el ancho del dispositivo.

## Tableros públicos y compartición

- Activa la casilla **Compartir tablero públicamente** en `tablero.php` para generar un `share_token`. Obtendrás
  un enlace directo a `tablero_publico.php?token=...` con las fichas en modo lectura.
- El icono de compartir utiliza la Web Share API cuando está disponible. Si no, abre una ventana de AddToAny con
  la URL y el título del tablero.
- En cada tarjeta también encontrarás un botón de compartir que aplica el mismo comportamiento para enlaces
  individuales.

## Cuenta y seguridad

- Desde `cpanel.php` puedes actualizar tu nombre y correo. Los cambios se aplican inmediatamente.
- En `cambiar_password.php` introduce tu contraseña actual y la nueva para actualizar el hash almacenado.
- Usa `logout.php` para cerrar sesión y revocar la cookie persistente del navegador.

## Recuperar acceso

Si olvidaste la contraseña, visita `recuperar_password.php` y escribe tu correo. Se generará un enlace con
caducidad de una hora; al abrirlo (`restablecer_password.php`) podrás introducir una contraseña nueva.

## Compartir desde Android

El repositorio incluye `ShareReceiverActivity.kt`, que registra un intent filter para recibir enlaces desde
otras aplicaciones Android. Al compartir hacia Linkaloo se abrirá `agregar_favolink.php` con el campo URL
rellenado automáticamente gracias al parámetro `shared`.
