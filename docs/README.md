# Documentación de linkaloo

La carpeta `docs/` reúne la información necesaria para comprender, instalar y utilizar la aplicación **linkaloo**, un gestor de enlaces organizado en tableros personales escrito en PHP.

## Índice de documentos

- [Estructura y arquitectura](estructura.md): describe cómo se organiza el código, los servicios auxiliares y la base de datos.
- [Instalación y configuración](instalacion.md): pasos para preparar un entorno local y credenciales externas.
- [Guía de uso](uso.md): explica los principales flujos de la interfaz, incluido el uso compartido de tableros.
- [Referencia de endpoints](endpoints.md): detalla los formularios y scripts PHP que actúan como API.

## Resumen del proyecto

### Objetivo funcional

linkaloo permite que cada usuario cree tableros temáticos y guarde fichas con enlaces, títulos, descripciones e imágenes. Desde `panel.php` se gestionan todas las fichas (alta, edición, movimiento y borrado) y desde `tableros.php`/`tablero.php` se controlan los tableros y su publicación pública.【F:panel.php†L1-L154】【F:tableros.php†L1-L71】【F:tablero.php†L1-L143】

### Tecnologías principales

- **Backend:** PHP 8 con PDO, cURL y extensiones DOM para scraping y recorte de imágenes. La conexión y opciones se declaran en `config.php`.【F:config.php†L1-L38】【F:panel.php†L37-L79】
- **Base de datos:** MySQL 8 con tablas `usuarios`, `categorias`, `links`, `password_resets` y `usuario_tokens` definidas en `database.sql` usando `utf8mb4` y restricciones de integridad.【F:database.sql†L1-L45】
- **Frontend:** JavaScript vanilla (`assets/main.js`) para interacción (filtros, compartir, modales) y CSS (`assets/style.css`). Feather Icons se carga desde CDN en `header.php`.【F:assets/main.js†L1-L199】【F:header.php†L8-L52】
- **Herramientas de desarrollo:** `npm` con `stylelint` para revisar estilos (`npm run lint:css`).【F:package.json†L1-L17】

### Flujo funcional clave

1. El usuario se autentica mediante formulario (`login.php`/`register.php`) con reCAPTCHA v3 y, opcionalmente, inicio de sesión con Google (OAuth 2).【F:login.php†L1-L72】【F:register.php†L1-L69】【F:oauth.php†L1-L32】【F:oauth2callback.php†L1-L76】
2. Tras iniciar sesión se redirige al `panel.php`, donde puede crear un enlace. El script descarga metadatos, normaliza la URL, guarda la imagen y asegura unicidad por `hash_url` antes de insertar en `links`.【F:panel.php†L81-L150】
3. Los usuarios administran tableros en `tableros.php` y ajustan detalles individuales en `tablero.php`, donde se puede generar un enlace público (`tablero_publico.php`) para compartir contenido de solo lectura.【F:tableros.php†L1-L71】【F:tablero.php†L71-L140】【F:tablero_publico.php†L1-L76】
4. Acciones auxiliares como mover (`move_link.php`), borrar (`delete_link.php`) o cargar más enlaces (`load_links.php`) se realizan mediante peticiones AJAX que devuelven JSON.【F:move_link.php†L1-L25】【F:delete_link.php†L1-L24】【F:load_links.php†L1-L36】

### Directorios relevantes

- Raíz: scripts PHP para páginas y endpoints.
- `assets/`: JavaScript y estilos.
- `img/`, `fichas/`, `local_favicons/`: recursos estáticos y generados (favicons e imágenes descargadas).
- `docs/`: documentación.
- `database.sql`: esquema relacional.

## Comprobaciones rápidas

Desde la raíz del repositorio puedes ejecutar estas órdenes para validar que el entorno está listo:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Todas las comprobaciones deben finalizar sin errores antes de subir cambios.
