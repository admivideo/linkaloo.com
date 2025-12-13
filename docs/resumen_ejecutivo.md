# Resumen ejecutivo de linkaloo

Este documento ofrece una visión condensada del proyecto para que cualquier persona pueda entender en
minutos qué resuelve, cómo está construido y qué necesita para funcionar. Se centra en los pilares
funcionales, la arquitectura técnica y las tareas básicas de operación.

## Propósito y público objetivo

- **Problema que soluciona:** guardar enlaces en tableros personales, organizarlos por temas y compartirlos
  de forma sencilla.
- **Usuarios principales:** personas que recopilan recursos (investigación, inspiración, favoritos) y
  equipos que necesitan colecciones compartidas.
- **Principios de diseño:** simplicidad (PHP sin framework, HTML clásico), rendimiento aceptable sin
  dependencias pesadas y compatibilidad amplia en navegadores.

## Stack y componentes clave

| Área | Detalle |
| --- | --- |
| Front-end | HTML renderizado por PHP, `assets/style.css` como hoja principal y `assets/main.js` para filtros, búsqueda en vivo, modales y compartición. |
| Back-end | PHP 8.1+ con MySQL vía PDO; scripts independientes (`*.php`) que actúan como páginas o endpoints JSON. |
| Persistencia | Esquema definido en `database.sql` con tablas para usuarios, tableros (`categorias`), enlaces (`favoritos`), tokens persistentes y solicitudes de recuperación. |
| Integraciones | Google OAuth 2.0 opcional, reCAPTCHA v3 en formularios de autenticación, Web Share API y fallback AddToAny, envío de correos para restablecimiento. |
| Aplicaciones complementarias | `ShareReceiverActivity.kt` permite enviar URLs desde Android directamente al backend. |

## Flujo funcional resumido

1. **Alta y acceso:** registro (`register.php`) o login (`login.php`) con reCAPTCHA; se emiten sesiones y
   cookies persistentes gestionadas en `session.php`.
2. **Onboarding:** sugerencia de tableros iniciales en `seleccion_tableros.php` tras el registro.
3. **Operación diaria:** el panel (`panel.php`) lista enlaces con búsqueda y filtros; `agregar_favolink.php`
   crea nuevos enlaces con metadatos (título, descripción truncada, favicon e imagen descargada).
4. **Mantenimiento de tableros:** alta, renombrado, notas internas, eliminación y compartición pública desde
   `tableros.php` y `tablero.php` usando tokens `share_token`.
5. **Acceso público y legal:** tableros públicos servidos por `tablero_publico.php` y páginas legales
   (`politica_privacidad.php`, `politica_cookies.php`, `condiciones_servicio.php`, `quienes_somos.php`).

## Endpoints y lógica asíncrona

- `load_links.php`: devuelve enlaces paginados, filtrables por tablero para la sesión activa.
- `move_link.php`: cambia un enlace de tablero y actualiza la fecha de modificación.
- `delete_link.php`: elimina enlaces y sincroniza los contadores asociados.
- `load_public_links.php`: obtiene enlaces de un tablero público usando `share_token`.
- Las peticiones se realizan con `fetch()` desde `assets/main.js`, que también integra Web Share API y
  AddToAny como alternativa.

## Seguridad y cumplimiento

- Contraseñas almacenadas con `password_hash()` y tokens persistentes guardados como hash.
- Cookies de sesión con `HttpOnly`, `SameSite=Lax` y `secure` en entornos HTTPS.
- Validación de URLs compartidas para evitar redirecciones abiertas mediante `isValidSharedUrl()` en el
  backend.
- Páginas legales incluidas de serie para privacidad, cookies, términos y sección de “quiénes somos”.

## Configuración y despliegue rápido

1. Crear base de datos MySQL y ejecutar `database.sql`.
2. Completar credenciales en `config.php` o mediante variables de entorno (OAuth, reCAPTCHA, correo de
   recuperación).
3. Opcional: ejecutar `npm install` para disponer de Stylelint.
4. Levantar el servidor con `php -S 0.0.0.0:8000` o configurar el virtual host preferido.

### Comprobaciones recomendadas

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

## Mantenimiento y operaciones

- **Limpieza periódica:** eliminar imágenes caducadas de `fichas/` y favicons de `local_favicons/` si se
  acumulan.
- **Seguridad:** rotar las claves de OAuth y reCAPTCHA, revisar expiración de tokens y registros de acceso.
- **Monitoreo:** revisar errores de PHP y métricas básicas del servidor (tiempo de respuesta, uso de disco).
- **Automatización opcional:** documentar y programar tareas auxiliares en `bots/` para ingesta o depuración.

## Referencias rápidas

- Índice general: [docs/README.md](README.md)
- Guía de instalación: [docs/instalacion.md](instalacion.md)
- Manual técnico detallado: [docs/manual_tecnico.md](manual_tecnico.md)
- Referencia de endpoints: [docs/endpoints.md](endpoints.md)
- Configuración avanzada: [docs/configuracion.md](configuracion.md)
- Estado del proyecto y próximos pasos: [docs/progreso.md](progreso.md)
