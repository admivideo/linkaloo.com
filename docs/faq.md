# FAQ de linkaloo

## ¿Qué es linkaloo y para qué sirve?

linkaloo es una aplicación web para guardar enlaces en tableros temáticos, buscarlos rápidamente y
compartir tableros o enlaces individuales con otras personas.

## ¿Qué necesito para ejecutarlo en local?

- PHP 8.1+
- MySQL 8+
- Extensiones de PHP: PDO (MySQL), cURL, mbstring, DOM, GD y JSON
- Node.js 18+ (solo para herramientas de lint en desarrollo)

Consulta la guía completa en [instalacion.md](instalacion.md).

## ¿Dónde configuro la conexión a la base de datos?

En [`config.php`](../config.php). También puedes revisar recomendaciones de variables y despliegue en
[configuracion.md](configuracion.md).

## ¿Cómo creo el esquema de base de datos?

Importa [`database.sql`](../database.sql) en una base vacía:

```bash
mysql -u TU_USUARIO -p TU_BD < database.sql
```

## ¿Cómo verifico rápidamente que todo está bien?

Desde la raíz del repositorio:

```bash
php -l config.php panel.php move_link.php load_links.php
node --check assets/main.js
npm run lint:css
```

## ¿Qué flujo mínimo debo probar tras instalar?

1. Registro o login.
2. Creación de tablero.
3. Alta de enlace en el tablero.
4. Filtrado/búsqueda en panel.
5. Compartición de enlace o tablero.

## ¿Qué endpoints se usan desde el front-end para acciones dinámicas?

Los más frecuentes son:

- `load_links.php`
- `move_link.php`
- `delete_link.php`
- `load_public_links.php`

La referencia funcional de parámetros y respuestas está en [endpoints.md](endpoints.md).

## ¿Cómo funciona la compartición?

El sistema combina:

- **Web Share API** (cuando el navegador la soporta)
- **AddToAny** como alternativa
- Tableros públicos con token mediante `tablero_publico.php`

## ¿Dónde se guardan favicons e imágenes de fichas?

- Favicons locales: `local_favicons/`
- Imágenes/fichas de enlaces: `fichas/`

Estas rutas deben tener permisos de escritura para el usuario del servidor web.

## ¿Cómo restablezco una contraseña?

Usando el flujo de:

1. `recuperar_password.php`
2. `restablecer_password.php`
3. `cambiar_password.php`

El correo de recuperación debe estar bien configurado en `config.php`.

## ¿Cuál es el punto de entrada principal de la interfaz autenticada?

`panel.php`, desde donde se accede a gestión de tableros, enlaces y compartición.

## ¿Hay soporte para OAuth?

Sí. El proyecto incluye flujo OAuth con Google (`oauth.php` y `oauth2callback.php`).

## ¿Qué documento leer primero si voy a tocar código?

- Primero: [guia_rapida.md](guia_rapida.md)
- Después: [manual_tecnico.md](manual_tecnico.md)
- Finalmente: [arquitectura.md](arquitectura.md) y [estructura.md](estructura.md)
