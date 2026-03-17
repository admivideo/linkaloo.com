# Análisis horario de usuarios (PHP + JS)

Pantalla/script: `scripts/analisis_horario_usuarios.php`.

## Qué calcula

- **Conexión por hora (aprox.)**: distribución de `usuarios.ultimo_acceso` por hora.
- **Actividad por hora**: media diaria de usuarios activos por hora usando `links.creado_en`.

## Qué muestra

- Top 3 franjas horarias con mayor actividad media.
- Tabla completa de las 24 horas.
- Gráfico de barras verticales en **HTML5 Canvas + JavaScript**:
  - Eje X: horas.
  - Eje Y: media de usuarios por hora.

## Ejecutar en local

```bash
php -S 0.0.0.0:8080
```

Luego abrir:

- `http://localhost:8080/scripts/analisis_horario_usuarios.php`

> Nota: este script crea su propia conexión PDO a MySQL con variables de entorno opcionales (`LINKALOO_DB_HOST`, `LINKALOO_DB_NAME`, `LINKALOO_DB_USER`, `LINKALOO_DB_PASS`).
