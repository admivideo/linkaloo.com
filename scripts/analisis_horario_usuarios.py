#!/usr/bin/env python3
"""Analiza franjas horarias de conexión y actividad de usuarios en MySQL.

Conexión (aproximada): distribución por hora de `usuarios.ultimo_acceso`.
Actividad: media diaria de usuarios activos por hora basada en `links.creado_en`.

Genera:
- CSV con métricas por hora.
- Gráfico de barras verticales (hora vs media de usuarios activos).
"""
from __future__ import annotations

import argparse
import os
from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd
import pymysql


def get_connection(args: argparse.Namespace):
    return pymysql.connect(
        host=args.host,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )


def query_hourly_metrics(conn) -> pd.DataFrame:
    sql = """
    WITH RECURSIVE horas AS (
        SELECT 0 AS hora
        UNION ALL
        SELECT hora + 1 FROM horas WHERE hora < 23
    ),
    dias AS (
        SELECT COUNT(DISTINCT DATE(creado_en)) AS total_dias
        FROM links
        WHERE creado_en IS NOT NULL
    ),
    actividad AS (
        SELECT
            HOUR(creado_en) AS hora,
            DATE(creado_en) AS fecha,
            COUNT(DISTINCT usuario_id) AS usuarios_activos
        FROM links
        WHERE creado_en IS NOT NULL
        GROUP BY HOUR(creado_en), DATE(creado_en)
    ),
    actividad_promedio AS (
        SELECT
            a.hora,
            AVG(a.usuarios_activos) AS media_usuarios_activos_hora,
            SUM(a.usuarios_activos) AS total_usuarios_activos_hora
        FROM actividad a
        GROUP BY a.hora
    ),
    conexiones_aprox AS (
        SELECT
            HOUR(ultimo_acceso) AS hora,
            COUNT(*) AS usuarios_ultimo_acceso
        FROM usuarios
        WHERE ultimo_acceso IS NOT NULL
        GROUP BY HOUR(ultimo_acceso)
    )
    SELECT
        h.hora,
        COALESCE(ap.media_usuarios_activos_hora, 0) AS media_usuarios_activos_hora,
        COALESCE(ap.total_usuarios_activos_hora, 0) AS total_usuarios_activos_hora,
        COALESCE(ca.usuarios_ultimo_acceso, 0) AS usuarios_ultimo_acceso,
        d.total_dias
    FROM horas h
    CROSS JOIN dias d
    LEFT JOIN actividad_promedio ap ON ap.hora = h.hora
    LEFT JOIN conexiones_aprox ca ON ca.hora = h.hora
    ORDER BY h.hora ASC;
    """

    return pd.read_sql(sql, conn)


def save_chart(df: pd.DataFrame, output_png: Path) -> None:
    plt.figure(figsize=(14, 6))
    plt.bar(df["hora"], df["media_usuarios_activos_hora"], color="#2563eb")
    plt.xticks(range(24), [f"{h:02d}:00" for h in range(24)], rotation=45)
    plt.xlabel("Hora del día")
    plt.ylabel("Media de usuarios activos por hora")
    plt.title("Actividad media por franja horaria (1 hora)")
    plt.tight_layout()
    output_png.parent.mkdir(parents=True, exist_ok=True)
    plt.savefig(output_png, dpi=160)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default=os.getenv("LINKALOO_DB_HOST", "82.223.84.165"))
    parser.add_argument("--user", default=os.getenv("LINKALOO_DB_USER", "smartuserIOn0s"))
    parser.add_argument("--password", default=os.getenv("LINKALOO_DB_PASS", ""))
    parser.add_argument("--database", default=os.getenv("LINKALOO_DB_NAME", "smartlinks"))
    parser.add_argument("--output-csv", default="docs/analysis/actividad_horaria.csv")
    parser.add_argument("--output-png", default="docs/analysis/actividad_horaria_barras.png")
    args = parser.parse_args()

    if not args.password:
        print("ERROR: Falta contraseña de BD. Usa --password o LINKALOO_DB_PASS.")
        return 2

    try:
        conn = get_connection(args)
        with conn:
            df = query_hourly_metrics(conn)
    except Exception as exc:
        print(f"ERROR: No se pudo obtener datos de MySQL: {exc}")
        return 1

    out_csv = Path(args.output_csv)
    out_csv.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(out_csv, index=False)

    out_png = Path(args.output_png)
    save_chart(df, out_png)

    top = df.sort_values("media_usuarios_activos_hora", ascending=False).head(3)
    print("Top 3 horas por media de usuarios activos:")
    for _, row in top.iterrows():
        print(f"- {int(row['hora']):02d}:00 -> {row['media_usuarios_activos_hora']:.2f}")

    print(f"CSV: {out_csv}")
    print(f"PNG: {out_png}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
