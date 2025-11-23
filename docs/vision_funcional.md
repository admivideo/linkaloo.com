# Visión funcional de la aplicación

Este documento explica, de manera concisa y orientada a negocio, qué resuelve **linkaloo**,
cómo se usa y qué garantías ofrece en torno a la información de las personas usuarias.
Se complementa con los detalles técnicos del [manual](manual_tecnico.md) y con la
[guía de uso](uso.md).

## Propósito

- **Guardar y ordenar enlaces personales** en tableros privados ("categorías").
- **Compartir enlaces o tableros** completos de forma opcional mediante tokens públicos.
- **Acceder rápidamente a los enlaces** desde cualquier dispositivo con una interfaz
  responsiva y con atajos para búsqueda, filtrado y movimiento entre tableros.

## Usuarios y alcance

La aplicación está pensada para una única persona por cuenta. No hay roles diferenciados;
cualquier usuario autenticado puede administrar sus tableros y enlaces sin interferir con
los de otras cuentas.

## Funcionalidades principales

- **Autenticación y alta**: formularios de registro e inicio (`register.php`, `login.php`)
  con reCAPTCHA v3 y opción de acceso mediante Google OAuth.
- **Gestión de tableros**: creación, renombrado, notas, eliminación y activación de enlace
  público (`share_token`).
- **Gestión de enlaces**: captura de metadatos (título, descripción, imagen y favicon),
  edición, movimiento entre tableros, eliminación y detección de duplicados por `hash_url`.
- **Búsqueda y navegación**: filtros por tablero, búsqueda en vivo, paginación opcional
  vía `load_links.php` y optimizaciones de interfaz para móviles.
- **Compartición**: uso de Web Share API o AddToAny para compartir enlaces individuales o
  tableros públicos.
- **Recuperación de acceso**: restablecimiento de contraseña por correo, con tokens de una
  hora de vigencia.

## Flujo típico de uso

1. La persona se registra o inicia sesión y llega al `panel.php` con sus tableros y enlaces.
2. Añade un enlace desde `agregar_favolink.php`; si introduce un nombre de tablero nuevo se
   crea automáticamente.
3. Organiza los enlaces moviéndolos con el menú contextual de cada tarjeta o desde el
   desplegable del panel. Las acciones se sincronizan vía `move_link.php` y `delete_link.php`.
4. Activa la compartición pública en `tablero.php` cuando quiere mostrar sus enlaces a otras
   personas a través de un token único.
5. Opcionalmente comparte desde Android usando el receptor incluido en
   `ShareReceiverActivity.kt`, que abre el formulario con la URL pre-rellenada.

## Integraciones externas

- **Google OAuth 2.0** para autenticación en un solo paso.
- **reCAPTCHA v3** para reducir envíos automatizados en login y registro.
- **Web Share API/AddToAny** para la compartición de enlaces.
- **Correo saliente** mediante `mail()` para recuperación de contraseña.

## Privacidad y seguridad

- Los tableros y enlaces son privados por defecto; un tablero solo se hace público si la
  persona activa su `share_token`.
- Las contraseñas se guardan con `password_hash` y los tokens persistentes se invalidan al
  cerrar sesión o tras usarse.
- Se validan las URL externas del parámetro `shared` para evitar redirecciones maliciosas
  antes de abrir el formulario de alta.

## Recursos de referencia

- [manual_tecnico.md](manual_tecnico.md): arquitectura, dependencias y modelo de datos.
- [uso.md](uso.md): guía paso a paso para operar la interfaz web.
- [estructura.md](estructura.md): mapa de archivos y tablas principales.
- [endpoints.md](endpoints.md): referencia de los scripts PHP que devuelven JSON.
