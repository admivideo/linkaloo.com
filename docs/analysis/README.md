# Análisis horario de usuarios

Script disponible: `scripts/analisis_horario_usuarios.py`.

## Qué calcula

- **Conexión por hora (aprox.)**: distribución de `usuarios.ultimo_acceso` por hora.
- **Actividad por hora**: media diaria de usuarios activos por hora usando `links.creado_en`.

## Ejecutar

```bash
python scripts/analisis_horario_usuarios.py \
  --password 'TU_PASSWORD' \
  --output-csv docs/analysis/actividad_horaria.csv \
  --output-png docs/analysis/actividad_horaria_barras.png
```

## Salidas

- `docs/analysis/actividad_horaria.csv`
- `docs/analysis/actividad_horaria_barras.png`

> Nota: en este entorno de ejecución no hubo conectividad hacia el host MySQL remoto, por lo que no se pudieron generar resultados reales automáticamente.
