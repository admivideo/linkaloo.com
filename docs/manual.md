# Manual del proyecto

Este manual describe los pasos necesarios para instalar y ejecutar **linkaloo**, una aplicación web simple para guardar enlaces en tableros personales.

## Requisitos

- PHP 8
- MySQL
- Node.js (opcional, para verificar estilos con Stylelint)

## Instalación

1. Clona este repositorio.
2. Crea una base de datos MySQL y ejecuta `database.sql` para generar las tablas.
3. Ajusta las credenciales de la base de datos en `config.php`.
4. (Opcional) Define las variables de entorno `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` si deseas habilitar el inicio de sesión con Google.
5. Coloca los recursos gráficos en el directorio `img/` (logotipo y favicons).

## Ejecución

Inicia un servidor PHP apuntando a la raíz del proyecto:

```bash
php -S localhost:8000
```

Accede en el navegador a `http://localhost:8000` para utilizar la aplicación.

## Verificación de código

Para comprobar que el código del proyecto no contiene errores básicos:

- `php -l config.php panel.php move_link.php load_links.php`
- `node --check assets/main.js`
- `npm run lint:css`

## Estructura y base de datos

Consulta [docs/estructura.md](estructura.md) para obtener más detalles sobre la arquitectura del proyecto y el esquema de la base de datos.

