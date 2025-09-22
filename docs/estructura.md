# Estructura y arquitectura del proyecto

Este documento ofrece una panorámica rápida de cómo se organiza el repositorio y dónde se encuentran los
componentes principales del backend, front-end y documentación.

## Raíz del repositorio

| Elemento | Descripción |
| --- | --- |
| `index.php` | Punto de entrada que redirige al panel o al login según exista una sesión activa. |
| `login.php`, `register.php` | Formularios de autenticación con reCAPTCHA y soporte del parámetro `shared`. |
| `panel.php` | Vista principal que lista tableros y enlaces del usuario autenticado. |
| `agregar_favolink.php` | Formulario para crear enlaces nuevos, con scraping de metadatos e inserción automática de tableros. |
| `tableros.php`, `tablero.php`, `tablero_publico.php` | Administración de tableros privados y vista pública asociada a `share_token`. |
| `editar_link.php`, `move_link.php`, `delete_link.php`, `load_links.php` | Edición puntual y endpoints JSON para mover, borrar o paginar enlaces. |
| `cpanel.php`, `cambiar_password.php` | Gestión de la cuenta y de la contraseña. |
| `recuperar_password.php`, `restablecer_password.php` | Flujo de recuperación de contraseña mediante tokens temporales. |
| `oauth.php`, `oauth2callback.php`, `logout.php` | Inicio de sesión con Google, callback y cierre de sesión. |
| `config.php`, `session.php`, `device.php` | Configuración de base de datos y OAuth, gestión de sesiones y detección de dispositivo. |
| `favicon_utils.php`, `image_utils.php` | Utilidades para descargar favicons y normalizar imágenes. |
| `database.sql` | Script SQL con la definición inicial de tablas e índices. |
| `ShareReceiverActivity.kt`, `AndroidManifest.xml` | Cliente Android que permite compartir enlaces hacia la aplicación web. |

## Directorios relevantes

| Carpeta | Contenido |
| --- | --- |
| `assets/` | `main.js` (comportamiento del panel y vistas) y `style.css` (estilos principales). |
| `docs/` | Guías de instalación, uso, referencia de endpoints y este mismo documento. |
| `fichas/` | Imágenes descargadas automáticamente desde los enlaces guardados. |
| `img/` | Recursos estáticos (logos, iconos) utilizados en la interfaz. |
| `local_favicons/` | Favicons en caché generados por `favicon_utils.php`. |
| `node_modules/` | Dependencias de desarrollo de Node para Stylelint. |

## Recursos legales y páginas estáticas

Los archivos `cookies.php`, `politica_cookies.php`, `politica_privacidad.php`, `condiciones_servicio.php` y
`quienes_somos.php` contienen textos legales listos para personalizar. Están enlazados desde el menú de ajustes
de `header.php` y se sirven como páginas PHP simples sin lógica adicional.

## Modelo de datos

La base de datos se crea ejecutando `database.sql` y utiliza la codificación `utf8mb4`. Las tablas principales
son:

- **usuarios:** credenciales y datos básicos de cada persona registrada.
- **categorias:** tableros asociados a un usuario, con campos para notas, token público e imagen de portada.
- **links:** enlaces individuales con URL original y canónica, título, descripción, imagen, notas y `hash_url`.
- **password_resets:** tokens temporales para restablecer contraseñas.
- **usuario_tokens:** tokens persistentes empleados para la funcionalidad "remember me".

Cada tabla incluye marcas de auditoría (`creado_en`, `modificado_en`) para facilitar informes o limpieza de datos.

## Flujo de archivos estáticos

`header.php` incorpora `assets/style.css` y `assets/main.js` con query strings basados en `filemtime` para forzar la
actualización en los navegadores. Los favicons se almacenan en `local_favicons/` y las imágenes completas en
`fichas/`, reutilizándose en paneles y tableros públicos para optimizar tiempos de carga.
