# `linkaloo_stats.php`: documentación funcional y técnica

Esta pantalla interna centraliza métricas de uso de Linkaloo por usuario y expone exportaciones CSV orientadas a acciones de onboarding/reactivación.

> URL objetivo: `https://linkaloo.com/linkaloo_stats.php`.

## Objetivo de la página

`linkaloo_stats.php` permite:

- Ver el total de usuarios, links guardados y distribución de links por segmentos.
- Revisar una tabla detallada por usuario (registro, último acceso, categorías, favolinks, primer/último favolink).
- Clasificar visualmente usuarios por “recencia del último link” con un sistema de colores.
- Exportar listas CSV de usuarios sin links para campañas por cohortes temporales (`D0`, `D1`, `D3`, `D7`, `D14`, `REACTIVAR`).

## Seguridad y acceso

La página está protegida por **HTTP Basic Auth** con validación usando `hash_equals`:

- Usuario: constante `STATS_USER`.
- Contraseña: constante `STATS_PASS`.

Si las credenciales no son válidas, responde `401 Unauthorized` y envía `WWW-Authenticate`.

### Recomendación de seguridad

Actualmente las credenciales viven en el código fuente. Se recomienda migrarlas a variables de entorno o a `config.php` con secretos fuera del repositorio para evitar exposición accidental.

## Compatibilidad con esquemas de base de datos

El script intenta adaptarse a variaciones de nombres de columnas en producción mediante `pickColumn()` consultando `INFORMATION_SCHEMA.COLUMNS`.

Columnas detectadas dinámicamente:

- En `usuarios`: fecha de creación, email y último acceso.
- En `links`: fecha de creación (para calcular primer/último favolink).

Esto reduce roturas cuando cambian nombres como `created_at`, `creado_en`, `fecha_creacion`, etc.

## Cálculo de métricas

## Segmentos de usuarios por volumen de links

La función `statsSegments()` define 7 segmentos:

1. `0 links`
2. `1-3`
3. `4-10`
4. `11-25`
5. `26-50`
6. `51-100`
7. `+100`

Para cada segmento se acumulan:

- número de usuarios,
- total de links,
- porcentaje sobre el total de links (usado en tarjetas y gráfico circular).

## Estado del último link (semáforo)

`lastSavedLinkStatus()` clasifica cada usuario según días desde `fecha_ultimo_favolink`:

- **Verde**: `0-3 días`.
- **Naranja**: `4-7 días`.
- **Rojo**: `+8 días`.
- **Azul**: sin links guardados o fecha inválida.

Además calcula un valor `sort` para permitir ordenación de la columna en la tabla.

## Consulta principal

La vista obtiene datos con una consulta consolidada:

- Base: `usuarios u`.
- Join agregado con `categorias` para `COUNT(*)` por usuario.
- Join agregado con `links` para:
  - total de favolinks,
  - fecha del primer favolink,
  - fecha del último favolink.

La tabla final se pagina en memoria con `500` filas por página.

## Exportaciones CSV

La pantalla admite exportación mediante query params:

- `?export_welcome_csv=1` (D0 / hoy)
- `?export_d1_csv=1`
- `?export_d3_csv=1`
- `?export_d7_csv=1`
- `?export_d14_csv=1`
- `?export_reactivate_csv=1`

Todos los exports filtran usuarios con:

- fecha de registro dentro del rango de cohorte,
- `COUNT(links) = 0` (sin favolinks aún).

Detalles de salida:

- Content-Type `text/csv; charset=UTF-8`.
- BOM UTF-8 para compatibilidad con Excel.
- Cabecera fija: `id,email`.
- Archivo sin encapsulado por comillas (funciones `sanitizePlainExportValue()` y `writePlainCsvRow()`).

### Cohortes usadas

- **D0**: registrados hoy.
- **D1**: registrados hace 1 día.
- **D3**: registrados hace 3 días.
- **D7**: registrados entre hace 7 y 3 días.
- **D14**: registrados entre hace 14 y 8 días.
- **REACTIVAR**: registrados hace más de 14 días.

## Interfaz y UX

La pantalla contiene:

- Cabecera con acciones de exportación.
- Tarjetas de resumen (usuarios/links/% por segmento).
- Tabla con ordenación cliente (botones por columna).
- Paginación visible si hay más de 500 filas.
- Sidebar con:
  - gráfico circular (`conic-gradient`) de distribución de links,
  - resumen de estados de color del último link.

## Dependencias y puntos de fallo conocidos

- Requiere `$pdo` de `config.php` y conectividad a MySQL.
- Si no se detecta columna de fecha de creación de usuario, los exports devuelven `500`.
- El formato CSV asume que las comas dentro de datos no son críticas (al no usar encapsulado); si aparecen, pueden desalinear columnas en algunos consumidores.

## Operación recomendada

1. Proteger el endpoint por red (VPN/IP allowlist) además de Basic Auth.
2. Rotar credenciales periódicamente.
3. Revisar semanalmente cohortes D3/D7/D14 y reactivación.
4. Verificar que los nombres de columnas detectados siguen vigentes tras migraciones.
