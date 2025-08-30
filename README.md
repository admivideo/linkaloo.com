# Linkadoo

Aplicación web simple para guardar enlaces en tableros personales. Requiere PHP 8 y MySQL.

## Funcionalidades

- Autenticación de usuarios (`login.php`, `register.php`, `logout.php`).
- Tableros para organizar enlaces y moverlos entre tableros.
- Cada ficha muestra título (máximo 50 caracteres), descripción (máximo 250 con `...`), favicon y dominio.
- Botón de compartir por ficha con la Web Share API o copia al portapapeles.
- Menú desplegable en la parte inferior izquierda de cada ficha para moverla a otro tablero.
- Grid responsivo: en móviles las fichas se distribuyen en dos columnas.
- La altura de cada ficha se adapta a su contenido y no hay separación vertical entre fichas de una misma columna.
- Base de datos en `utf8mb4` para almacenar caracteres especiales correctamente.

## Instalación

1. Crea una base de datos MySQL y ejecuta `database.sql`.
2. Ajusta las credenciales en `config.php`.
3. Coloca el logo `img/linkaloo_white.png`.
4. Ejecuta un servidor PHP en la raíz del proyecto.

## Desarrollo

- JavaScript principal en `assets/main.js`.
- Estilos en `assets/style.css`.
- Para comprobar el código:
  - `php -l config.php panel.php move_link.php`
  - `node --check assets/main.js`
  - `npx --yes stylelint assets/style.css` *(requiere configuración)*
