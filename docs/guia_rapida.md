# Guía rápida del proyecto

Esta guía está pensada para personas que necesitan entender **qué hace linkaloo**, cómo arrancarlo en
local y qué archivos revisar primero antes de tocar código.

## ¿Qué es linkaloo?

linkaloo es una aplicación web para guardar enlaces en tableros personales. Cada persona usuaria puede:

- crear tableros temáticos,
- añadir enlaces con metadatos,
- mover y eliminar enlaces,
- compartir enlaces concretos o tableros públicos.

Está construida con PHP + MySQL en el servidor y JavaScript/CSS nativos en el cliente.

## Arranque en 5 minutos

1. Importa el esquema de base de datos:

   ```bash
   mysql -u <usuario> -p <base_de_datos> < database.sql
   ```

2. Configura `config.php` con credenciales locales de MySQL y, si aplica, OAuth/recaptcha.
3. Instala utilidades de desarrollo (opcional pero recomendado):

   ```bash
   npm install
   ```

4. Arranca un servidor local:

   ```bash
   php -S localhost:8000
   ```

5. Abre `http://localhost:8000` y valida registro/login.

## Flujo funcional mínimo

Para comprobar que la app está operativa, verifica este recorrido:

1. Crear cuenta (`register.php`) e iniciar sesión (`login.php`).
2. Crear un tablero (`tableros.php`).
3. Añadir un enlace (`agregar_favolink.php`).
4. Verlo en panel (`panel.php`) y moverlo/eliminarlo con acciones rápidas.
5. Probar compartición desde botón de share (Web Share API o AddToAny).

## Mapa de archivos clave

| Archivo/directorio | Rol principal |
| --- | --- |
| `config.php` | Configuración global: DB, OAuth, reCAPTCHA y ajustes de entorno. |
| `session.php` | Gestión de sesión y autenticación persistente. |
| `panel.php` | Vista principal tras login; lista tableros y enlaces. |
| `tableros.php` / `tablero.php` | Gestión de tableros personales. |
| `agregar_favolink.php` | Alta de enlaces con metadatos. |
| `load_links.php` | Carga incremental de enlaces para UI dinámica. |
| `move_link.php` / `delete_link.php` | Endpoints para mover y borrar enlaces. |
| `assets/main.js` | Lógica front-end: filtros, búsqueda, eventos y acciones asíncronas. |
| `assets/style.css` | Estilos globales de la aplicación. |
| `database.sql` | Esquema de base de datos inicial. |

## Checks recomendados antes de publicar cambios

Desde la raíz del repositorio:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

## ¿Dónde seguir leyendo?

- `docs/instalacion.md`: instalación detallada paso a paso.
- `docs/configuracion.md`: variables de entorno y ajustes finos.
- `docs/uso.md`: guía orientada a personas usuarias.
- `docs/manual_tecnico.md`: arquitectura, decisiones técnicas y mantenimiento.
- `docs/endpoints.md`: referencia de endpoints JSON/públicos.
