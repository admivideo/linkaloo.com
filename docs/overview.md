# Documentación del proyecto

## Estructura general

- `config.php`: configuración de la base de datos y creación del objeto `PDO` para todas las consultas.
- `login.php`, `register.php`, `logout.php`: gestión de usuarios y sesiones.
- `cambiar_password.php`, `recuperar_password.php`, `restablecer_password.php`: flujo completo de recuperación y cambio de contraseña.
- `panel.php`: panel principal donde se crean tableros y se guardan enlaces. Incluye la función `scrapeMetadata` que obtiene título, descripción e imagen de una URL.
- `load_links.php`: devuelve enlaces en formato JSON para la carga progresiva (scroll infinito).
- `move_link.php` y `delete_link.php`: endpoints usados por AJAX para mover o borrar un enlace.
- `editar_link.php`: permite añadir notas o ajustar datos de un enlace existente.
- `database.sql`: define las tablas `usuarios`, `categorias` y `links`.
- `assets/main.js`: controla el filtrado por tablero, la búsqueda, el compartido de enlaces, la paginación infinita y la interacción con los endpoints.
- `assets/style.css`: estilos principales y diseño responsivo.

## Flujo de uso

1. El usuario se registra o inicia sesión.
2. Desde el panel puede crear tableros y agregar enlaces. La metadata de cada URL se detecta automáticamente.
3. La interfaz lista 18 enlaces iniciales y solicita más a `load_links.php` al llegar al final.
4. Cada tarjeta permite mover, compartir o eliminar un enlace sin recargar la página.

## Páginas informativas

Las páginas `cookies.php`, `politica_cookies.php`, `condiciones_servicio.php`, `politica_privacidad.php`, `quienes_somos.php` e `index.php` ofrecen información estática y enlaces legales.

Para instrucciones de instalación y pruebas consulte `README.md`.
