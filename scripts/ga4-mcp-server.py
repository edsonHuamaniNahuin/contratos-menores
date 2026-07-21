#!/usr/bin/env python3
"""
MCP Server para Google Analytics 4 (GA4)
Permite consultar métricas de Vigilante SEACE directamente desde opencode.

Uso: python ga4-mcp-server.py
Configuración: storage/ga4-service-account.json + GA4_PROPERTY_ID=G-4PRW1QCW48
"""

import json
import os
import sys
from datetime import datetime, timedelta, date

from google.oauth2 import service_account
from googleapiclient.discovery import build

# ─── Configuración ─────────────────────────────────────
PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CREDENTIALS_PATH = os.path.join(PROJECT_ROOT, "storage", "ga4-service-account.json")
GA4_PROPERTY_ID = os.environ.get("GA4_PROPERTY_ID", "properties/404642926")  # G-4PRW1QCW48

# ─── Inicialización ────────────────────────────────────
if os.path.exists(CREDENTIALS_PATH):
    credentials = service_account.Credentials.from_service_account_file(
        CREDENTIALS_PATH,
        scopes=["https://www.googleapis.com/auth/analytics.readonly"]
    )
elif os.environ.get("GA4_CREDENTIALS_JSON"):
    import io
    credentials = service_account.Credentials.from_service_account_info(
        json.loads(os.environ["GA4_CREDENTIALS_JSON"]),
        scopes=["https://www.googleapis.com/auth/analytics.readonly"]
    )
else:
    print(json.dumps({"error": "GA4 credentials not found"}))
    sys.exit(1)

analytics = build("analyticsdata", "v1beta", credentials=credentials)

# ─── Helpers ───────────────────────────────────────────

def run_report(metrics: list, dimensions: list = None, date_ranges: list = None,
               order_by: list = None, limit: int = None, metric_filter: dict = None):
    """Ejecuta un reporte contra GA4."""
    request_body = {
        "metrics": [{"name": m} for m in metrics],
        "dateRanges": date_ranges or [{"startDate": "7daysAgo", "endDate": "today"}],
    }
    if dimensions:
        request_body["dimensions"] = [{"name": d} for d in dimensions]
    if order_by:
        request_body["orderBys"] = order_by
    if limit:
        request_body["limit"] = str(limit)
    if metric_filter:
        request_body["metricFilter"] = metric_filter

    response = analytics.properties().runReport(
        property=GA4_PROPERTY_ID, body=request_body
    ).execute()

    return _format_response(response)

def _format_response(response):
    """Formatea la respuesta de GA4 a un dict legible."""
    dimension_headers = [h["name"] for h in response.get("dimensionHeaders", [])]
    metric_headers = [h["name"] for h in response.get("metricHeaders", [])]

    rows = []
    for row in response.get("rows", []):
        dims = dict(zip(dimension_headers, [v.get("value", "") for v in row.get("dimensionValues", [])]))
        metrics = dict(zip(metric_headers, [v.get("value", "") for v in row.get("metricValues", [])]))
        rows.append({**dims, **metrics})

    return {
        "rowCount": response.get("rowCount", 0),
        "rows": rows,
    }

def date_range(days_ago: int):
    """Helper para crear dateRange."""
    start = (datetime.now() - timedelta(days=days_ago)).strftime("%Y-%m-%d")
    end = date.today().strftime("%Y-%m-%d")
    return [{"startDate": start, "endDate": end}]

# ─── Funciones del MCP ─────────────────────────────────

def get_totals(days: int = 7):
    """Visitantes, sesiones, páginas vistas totales."""
    return run_report(
        metrics=["activeUsers", "sessions", "screenPageViews", "averageSessionDuration", "bounceRate"],
        date_ranges=date_range(days),
    )

def get_daily_trend(days: int = 30):
    """Tendencia diaria de visitas."""
    return run_report(
        metrics=["activeUsers", "sessions", "screenPageViews"],
        dimensions=["date"],
        date_ranges=date_range(days),
        order_by=[{"dimension": {"dimensionName": "date"}}],
    )

def get_top_pages(days: int = 7, limit: int = 10):
    """Páginas más visitadas."""
    return run_report(
        metrics=["screenPageViews", "activeUsers", "averageSessionDuration"],
        dimensions=["pagePath", "pageTitle"],
        date_ranges=date_range(days),
        order_by=[{"metric": {"metricName": "screenPageViews"}, "desc": True}],
        limit=limit,
    )

def get_traffic_sources(days: int = 7):
    """Fuentes de tráfico."""
    return run_report(
        metrics=["activeUsers", "sessions"],
        dimensions=["sessionSource", "sessionMedium"],
        date_ranges=date_range(days),
        order_by=[{"metric": {"metricName": "activeUsers"}, "desc": True}],
        limit=10,
    )

def get_devices(days: int = 7):
    """Dispositivos usados."""
    return run_report(
        metrics=["activeUsers"],
        dimensions=["deviceCategory"],
        date_ranges=date_range(days),
    )

def get_event_count(event_name: str, days: int = 7):
    """Contar cuántas veces se disparó un evento específico."""
    return run_report(
        metrics=["eventCount"],
        dimensions=["eventName"],
        date_ranges=date_range(days),
        metric_filter={
            "filter": {
                "fieldName": "eventName",
                "stringFilter": {"matchType": "EXACT", "value": event_name}
            }
        },
    )

def get_all_events(days: int = 7, limit: int = 20):
    """Listar todos los eventos registrados."""
    return run_report(
        metrics=["eventCount", "totalUsers"],
        dimensions=["eventName"],
        date_ranges=date_range(days),
        order_by=[{"metric": {"metricName": "eventCount"}, "desc": True}],
        limit=limit,
    )

# ─── CLI para MCP tools ────────────────────────────────

TOOLS = {
    "ga4_totals": {"fn": get_totals, "desc": "Visitantes, sesiones, páginas vistas totales", "args": ["days"]},
    "ga4_daily_trend": {"fn": get_daily_trend, "desc": "Tendencia diaria de visitas", "args": ["days"]},
    "ga4_top_pages": {"fn": get_top_pages, "desc": "Páginas más visitadas", "args": ["days", "limit"]},
    "ga4_traffic_sources": {"fn": get_traffic_sources, "desc": "Fuentes de tráfico", "args": ["days"]},
    "ga4_devices": {"fn": get_devices, "desc": "Dispositivos usados", "args": ["days"]},
    "ga4_all_events": {"fn": get_all_events, "desc": "Todos los eventos registrados", "args": ["days", "limit"]},
    "ga4_event_count": {"fn": get_event_count, "desc": "Contar evento específico", "args": ["event_name", "days"]},
}

def main():
    if len(sys.argv) < 2:
        print(json.dumps(list(TOOLS.keys())))
        return

    tool = sys.argv[1]
    if tool not in TOOLS:
        print(json.dumps({"error": f"Tool not found: {tool}"}))
        return

    kwargs = {}
    for i, arg_name in enumerate(TOOLS[tool]["args"]):
        if i + 2 < len(sys.argv):
            val = sys.argv[i + 2]
            if val.isdigit():
                val = int(val)
            kwargs[arg_name] = val

    try:
        result = TOOLS[tool]["fn"](**kwargs)
        print(json.dumps(result, indent=2, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()
