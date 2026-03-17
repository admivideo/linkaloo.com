# Análisis horario de usuarios

El análisis por franjas de 1 hora se muestra directamente en:

- `linkaloo_stats.php#resumen`

## Qué se muestra

En la sección **Resumen** se imprime una tabla por hora (`00:00` a `23:00`) con:

- cantidad de usuarios,
- porcentaje por franja,
- columna base detectada en `usuarios` (`actualizado_en`, `updated_at`, `modificado_en` o `ultimo_acceso`).

## Nota

No se genera exportación `.csv` para este análisis.
