# Arquitectura de linkaloo

Este documento describe cómo se organiza el sistema a nivel de capas, flujos de datos y dependencias.
Su objetivo es servir como mapa mental para personas desarrolladoras que necesiten extender o mantener
la aplicación.

## Vista por capas

```
+-------------------------------+
|        Navegador (UI)         |
|  HTML renderizado en PHP,     |
|  CSS (assets/style.css) y     |
|  JavaScript (assets/main.js)  |
+---------------+---------------+
                |
                v
+---------------+---------------+
|        Servidor PHP           |
|  Scripts *.php en la raíz     |
|  con lógica de negocio y      |
|  endpoints JSON               |
+---------------+---------------+
                |
                v
+---------------+---------------+
|        Base de datos          |
|      MySQL (database.sql)     |
+-------------------------------+
```

- **Presentación:** el HTML se genera en el servidor, pero `assets/main.js` añade interactividad para
  filtrar, buscar, mover enlaces y controlar la compartición. Los estilos globales viven en
  `assets/style.css` y se complementan con utilidades en línea en los propios scripts PHP.
- **Lógica de negocio:** cada script PHP encapsula un caso de uso. Las vistas combinan obtención de
  datos con plantillas embebidas; los endpoints (`move_link.php`, `delete_link.php`, `load_links.php`)
  devuelven JSON para peticiones asíncronas. Utilidades comunes (sesiones, favicons, descarga de
  imágenes) se extraen a archivos especializados.
- **Persistencia:** todas las operaciones utilizan PDO con sentencias preparadas. El esquema está
  definido en `database.sql` y contiene tablas para usuarios, tableros, enlaces y tokens de seguridad.

## Flujos principales

### Alta y autenticación

1. `login.php` y `register.php` presentan formularios con validación reCAPTCHA v3.
2. Tras validar credenciales o completar el registro, `session.php` genera la sesión y opcionalmente
   una cookie persistente (`usuario_tokens`).
3. Si el usuario llega con una URL compartida (`shared`), el flujo lo redirige a `agregar_favolink.php`
   para prellenar el formulario con la URL original.
4. La autenticación con Google se inicia en `oauth.php`, que redirige a Google con un `state`
   firmado. `oauth2callback.php` intercambia el `code` por tokens, obtiene el perfil y crea o
   actualiza al usuario local antes de finalizar la sesión.

### Gestión de tableros y enlaces

1. `panel.php` obtiene todos los tableros y sus enlaces asociados para renderizarlos en tarjetas.
2. El JavaScript del panel permite filtrar enlaces por tablero, realizar búsquedas en vivo y abrir
   el formulario modal de alta rápida (`agregar_favolink.php`).
3. Cuando la persona mueve o elimina un enlace se envía una petición `fetch()` a `move_link.php` o
   `delete_link.php`. Estas rutas validan la sesión, actualizan la base de datos y devuelven una
   respuesta JSON que el front-end interpreta para actualizar la interfaz sin recargar.
4. `tablero.php` centraliza la edición avanzada (nombre, nota, token público) y la regeneración de
   imágenes. Si se activa la compartición, `tablero_publico.php` ofrece una vista de solo lectura a
   la que se accede mediante `share_token`.

### Descarga de metadatos

- `agregar_favolink.php` normaliza la URL y, si el usuario lo solicita, descarga metadatos OpenGraph
  mediante cURL y DOM. Las imágenes se procesan en `image_utils.php`, que guarda versiones reducidas
  en `fichas/` agrupadas por usuario.
- `favicon_utils.php` consulta primero el favicon en caché (`local_favicons/`); si no existe, lo
  descarga, redimensiona con GD y lo guarda para reutilizarlo en futuras visitas.

## Integraciones externas

| Servicio | Archivo/s implicados | Propósito |
| --- | --- | --- |
| Google OAuth 2.0 | `oauth.php`, `oauth2callback.php` | Inicio de sesión con cuentas de Google. |
| reCAPTCHA v3 | `login.php`, `register.php` | Mitigar bots en los formularios de autenticación. |
| Web Share API / AddToAny | `assets/main.js`, `panel.php` | Compartición de enlaces/tableros desde navegadores compatibles. |
| Correo saliente (`mail()`) | `recuperar_password.php`, `restablecer_password.php` | Recuperación de contraseñas mediante token temporal. |
| Aplicación Android | `ShareReceiverActivity.kt`, `AndroidManifest.xml` | Compartir enlaces desde el sistema operativo al formulario web. |

## Seguridad y cumplimiento

- Las sesiones se configuran con cookies `HttpOnly`, `SameSite=Lax` y `secure` automático si el sitio
  se sirve por HTTPS.
- Los tokens persistentes se almacenan con un hash (`usuario_tokens.token_hash`) y se invalidan tras
  cada uso.
- Las URLs externas que llegan mediante el parámetro `shared` pasan por `isValidSharedUrl()` antes de
  permitir redirecciones, evitando ataques de open redirect.
- Las páginas legales (cookies, privacidad, condiciones y quiénes somos) se mantienen en archivos
  independientes (`politica_privacidad.php`, `condiciones_servicio.php`, etc.) para facilitar su
  actualización.

## Despliegue recomendado

1. Ajusta `config.php` o define variables de entorno para credenciales de base de datos, claves de
   OAuth y reCAPTCHA, y parámetros de correo.
2. Ejecuta `database.sql` en la base de datos de destino y crea una cuenta administrativa mediante
   `register.php`.
3. Sube el código a un hosting con soporte PHP 8.1+, configura HTTPS y habilita las extensiones
   necesarias (PDO MySQL, cURL, mbstring, DOM, GD, JSON).
4. Instala dependencias de desarrollo (`npm install`) solo si vas a ejecutar `npm run lint:css` en el
   pipeline. En producción no se requieren dependencias Node.
5. Configura tareas programadas opcionales para limpiar imágenes órfanas o tokens caducados según las
   políticas de la plataforma de alojamiento.

## Próximos pasos sugeridos

- Definir un proceso automatizado para eliminar entradas antiguas de `usuario_tokens` y
  `password_resets`.
- Considerar la incorporación de un sistema de colas si en el futuro se desean tareas en segundo
  plano (por ejemplo, reintentar descargas de metadatos de forma asíncrona).
- Añadir pruebas automatizadas para los endpoints críticos y para el flujo de autenticación.

