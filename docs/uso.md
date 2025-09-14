# Guía de uso

Este documento describe el flujo básico para interactuar con **linkaloo** desde la interfaz web.

## Registro e inicio de sesión

1. Abre la página `index.php`.
2. Regístrate con un correo y contraseña o accede con tus credenciales existentes.
3. Opcionalmente puedes autenticarte mediante Google desde `login.php`.

## Gestión de tableros

1. En el panel principal crea tableros para organizar tus enlaces.
2. Usa el menú de cada tablero para renombrarlo, añadir notas o eliminarlo.

## Guardar y organizar enlaces

1. Pulsa el botón “+” para agregar un nuevo enlace.
2. Completa el título y la descripción opcional; el favicon se obtiene automáticamente.
3. Cada tarjeta permite mover el enlace a otro tablero, buscarlo, compartirlo o eliminarlo.

## Tableros públicos

Cada tablero genera un enlace de compartición que muestra sus fichas en `tablero_publico.php`. Comparte ese enlace para dar acceso de solo lectura.

## Compartir

El icono de compartir utiliza la Web Share API cuando está disponible; si no, copia la URL al portapapeles.
