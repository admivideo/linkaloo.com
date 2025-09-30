# linkaloo

linkaloo es una aplicación web para guardar enlaces en tableros personales, organizarlos por temas y
compartirlos con otras personas. El backend está escrito en PHP 8 con MySQL y el front-end combina
HTML, CSS y JavaScript sin frameworks.

## Tabla rápida de contenidos

- [Características principales](#características-principales)
- [Requisitos previos](#requisitos-previos)
- [Instalación rápida](#instalación-rápida)
- [Configuración](#configuración)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Flujo básico de uso](#flujo-básico-de-uso)
- [Tareas comunes de desarrollo](#tareas-comunes-de-desarrollo)
- [Documentación ampliada](#documentación-ampliada)

## Características principales

- Autenticación de usuarios mediante formulario propio u OAuth con Google.
- Gestión completa de tableros privados: creación, renombrado, notas, eliminación y token de
  compartición pública.
- Guardado de enlaces con metadatos: título, descripción truncada automáticamente, favicon e imagen
  descargada cuando está disponible.
- Filtros y búsqueda rápida en el panel, con acciones de mover o eliminar cada enlace sin recargar la
  página.
- Compartición de enlaces o tableros con la Web Share API y AddToAny como alternativa.
- Páginas legales preconfiguradas (cookies, privacidad, condiciones y quiénes somos).
- Base de datos `utf8mb4` y utilidades para gestionar favicons e imágenes de forma local.

## Requisitos previos

- PHP 8.1 o superior con extensiones PDO (MySQL), cURL, mbstring, DOM, GD y JSON.
- MySQL 8 o compatible.
- Node.js 18+ (solo para ejecutar Stylelint en desarrollo).
- Servidor web o `php -S` para servir la aplicación.

## Instalación rápida

1. Clona el repositorio: `git clone https://github.com/…/linkaloo.com.git`.
2. Crea una base de datos vacía y ejecuta [database.sql](database.sql).
3. Copia `.env.example` si existe o edita [config.php](config.php) con tus credenciales (ver
   [Configuración](#configuración)).
4. Instala las dependencias opcionales de desarrollo: `npm install`.
5. Inicia un servidor PHP en la raíz del proyecto: `php -S localhost:8000`.

## Configuración

Los valores necesarios para conectar con la base de datos, Google OAuth, reCAPTCHA v3 y el correo de
recuperación se documentan en detalle en [docs/configuracion.md](docs/configuracion.md). El archivo
[config.php](config.php) incluye valores por defecto pensados para desarrollo, pero se recomienda
sobrescribirlos mediante variables de entorno antes de desplegar en producción.

## Estructura del proyecto

| Ubicación | Descripción |
| --- | --- |
| `assets/` | JavaScript (`main.js`) y hoja de estilos principal (`style.css`). |
| `docs/` | Documentación técnica, guías de uso e índice general. |
| `fichas/` | Imágenes descargadas de los enlaces guardados. |
| `img/` | Recursos gráficos estáticos. |
| `local_favicons/` | Favicons cacheados por `favicon_utils.php`. |
| `*.php` | Scripts PHP que renderizan vistas o actúan como endpoints JSON. |
| `database.sql` | Definición del esquema inicial de la base de datos. |
| `ShareReceiverActivity.kt` | Receptor Android que permite compartir URLs del sistema a linkaloo. |

Para más detalles consulta [docs/estructura.md](docs/estructura.md) y
[docs/manual_tecnico.md](docs/manual_tecnico.md).

## Flujo básico de uso

1. Regístrate (`register.php`) o inicia sesión (`login.php`).
2. Crea tableros desde el panel (`panel.php` / `tableros.php`).
3. Guarda enlaces con el formulario “+” (`agregar_favolink.php`).
4. Utiliza los filtros y el buscador para encontrar enlaces, moverlos entre tableros o eliminarlos.
5. Comparte un enlace o tablero con otras personas mediante los botones de compartición.

## Tareas comunes de desarrollo

Ejecuta estas comprobaciones antes de publicar cambios:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

Los estilos y patrones de arquitectura principales se describen en el
[manual técnico](docs/manual_tecnico.md).

## Documentación ampliada

- [docs/README.md](docs/README.md) – índice general de la documentación.
- [docs/instalacion.md](docs/instalacion.md) – instalación paso a paso con capturas y comandos.
- [docs/uso.md](docs/uso.md) – guía práctica para personas usuarias finales.
- [docs/estructura.md](docs/estructura.md) – descripción detallada del repositorio y la base de datos.
- [docs/endpoints.md](docs/endpoints.md) – referencia de endpoints JSON y vistas públicas.
- [docs/manual_tecnico.md](docs/manual_tecnico.md) – visión completa de arquitectura, flujos y buenas prácticas.
- [docs/arquitectura.md](docs/arquitectura.md) – descripción de capas, flujos de datos e integraciones externas.
- [docs/configuracion.md](docs/configuracion.md) – variables de entorno, ajustes avanzados y consejos de despliegue.

