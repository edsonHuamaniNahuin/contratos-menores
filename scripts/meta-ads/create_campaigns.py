#!/usr/bin/env python3
"""
Lanzador de campañas de Facebook/Instagram (Meta Marketing API) para Vigilante SEACE.

Crea, para cada campaña definida en campaigns_config.json:
    campaña -> conjunto de anuncios (targeting + presupuesto) -> anuncio (copy + imagen).

Requisitos (una sola vez):
  - Access token de un System User con permisos: ads_management, ads_read,
    pages_read_engagement, business_management.
  - Ad Account ID (formato: act_XXXXXXXXX), Page ID y Pixel ID.
  - Las imágenes deben ser URLs públicas (https) accesibles para Meta.

Uso:
  python create_campaigns.py --token META_TOKEN --ad-account act_XXXX --page-id 123 --pixel-id 456
  python create_campaigns.py --token META_TOKEN --ad-account act_XXXX --page-id 123 --pixel-id 456 \
      --campaign "MA-Alertas" --activate

Sin --activate las campañas quedan en PAUSED (revisión previa).
"""

import argparse
import json
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

CONFIG_PATH = Path(__file__).parent / "campaigns_config.json"
GRAPH = "https://graph.facebook.com"
API_VERSION = "v26.0"


def api(method: str, path: str, token: str, data: dict | None = None) -> dict:
    """Llama a la Graph API de Meta. Devuelve el JSON de respuesta."""
    payload = dict(data or {})
    payload["access_token"] = token
    if method == "GET":
        url = f"{GRAPH}/{API_VERSION}/{path}"
        sep = "&" if "?" in url else "?"
        url += sep + urllib.parse.urlencode(payload)
        req = urllib.request.Request(url, method="GET")
    else:
        url = f"{GRAPH}/{API_VERSION}/{path}"
        req = urllib.request.Request(url, data=urllib.parse.urlencode(payload).encode(), method=method)
    try:
        with urllib.request.urlopen(req, timeout=60) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        detail = e.read().decode()
        print(f"  [API ERROR {e.code}] {path}\n  {detail}", file=sys.stderr)
        raise


def search_interest_id(token: str, name: str) -> str | None:
    """Busca el ID numérico de un interés de audiencia por nombre."""
    path = "search?type=adinterest&q=" + urllib.parse.quote(name) + "&limit=5"
    data = api("GET", path, token)
    items = data.get("data", [])
    name_l = name.lower()
    for item in items:
        if item.get("name", "").lower() == name_l:
            return str(item["id"])
    for item in items:  # fallback: que contenga el término
        if name_l in item.get("name", "").lower():
            return str(item["id"])
    if items:
        return str(items[0]["id"])  # último fallback: primer match
    return None


def upload_image(token: str, ad_account: str, image_url: str) -> str:
    """Sube una imagen (URL remota o archivo local) al Ad Account y devuelve su hash."""
    # Meta rechaza el upload por URL con apps en modo desarrollo; descargamos
    # la imagen y la subimos como multipart desde el archivo local si es local,
    # o vía URL remota si no hay archivo.
    import mimetypes

    if image_url.startswith("http"):
        # Intentar primero por URL (rápido); si falla con code 3, se descarga y
        # se sube como archivo.
        try:
            result = api("POST", f"{ad_account}/adimages", token, {"url": image_url})
            hashes = result.get("images", {})
            if hashes:
                return list(hashes.values())[0]["hash"]
        except Exception:
            pass
        # Descargar a un temporal y subir como archivo
        tmp_path = Path(image_url.split("/")[-1].split("?")[0])
        urllib.request.urlretrieve(image_url, tmp_path)
        try:
            return _upload_image_file(token, ad_account, tmp_path)
        finally:
            tmp_path.unlink(missing_ok=True)

    return _upload_image_file(token, ad_account, Path(image_url))


def _upload_image_file(token: str, ad_account: str, path: Path) -> str:
    """Sube un archivo de imagen local vía multipart/form-data (método que sí
    funciona con apps en modo desarrollo / access tier limited)."""
    import uuid

    boundary = uuid.uuid4().hex
    file_bytes = path.read_bytes()
    mime = "image/jpeg"
    if path.suffix.lower() == ".png":
        mime = "image/png"
    elif path.suffix.lower() in (".webp",):
        mime = "image/webp"

    def field(name, value):
        return (
            f"--{boundary}\r\n"
            f'Content-Disposition: form-data; name="{name}"\r\n\r\n'
            f"{value}\r\n"
        ).encode()

    def file_field(name, filename):
        return (
            f"--{boundary}\r\n"
            f'Content-Disposition: form-data; name="{name}"; filename="{filename}"\r\n'
            f"Content-Type: {mime}\r\n\r\n"
        ).encode()

    parts = []
    parts.append(field("access_token", token))
    parts.append(file_field("source", path.name))
    parts.append(file_bytes)
    parts.append(f"\r\n--{boundary}--\r\n".encode())

    body = b"".join(parts)
    url = f"{GRAPH}/{API_VERSION}/{ad_account}/adimages"
    req = urllib.request.Request(url, data=body, method="POST")
    req.add_header("Content-Type", f"multipart/form-data; boundary={boundary}")
    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            result = json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        print(f"  [API ERROR {e.code}] adimages (multipart)\n  {e.read().decode()}", file=sys.stderr)
        raise
    hashes = result.get("images", {})
    if not hashes:
        raise RuntimeError("No se pudo subir la imagen: " + json.dumps(result))
    return list(hashes.values())[0]["hash"]


def create_campaign(token, ad_account, cfg, activate: bool):
    name = cfg["name"]
    print(f"\n=== {name} ===")

    status = "ACTIVE" if activate else "PAUSED"

    # 1) Campaña
    camp_params = {
        "name": name,
        "objective": cfg["objective"],
        "status": status,
        "is_adset_budget_sharing_enabled": "false",
        "special_ad_categories": json.dumps(cfg.get("special_ad_categories", [])),
    }
    camp = api("POST", f"{ad_account}/campaigns", token, camp_params)
    campaign_id = camp.get("id")
    print(f"  Campaña creada: {campaign_id} ({status})")

    # 2) Resolver intereses
    interests = []
    for interest_name in cfg["interests"]:
        iid = search_interest_id(token, interest_name)
        if iid:
            interests.append({"id": iid})
            print(f"  Interés OK: {interest_name} -> {iid}")
        else:
            print(f"  Interés NO encontrado (se omite): {interest_name}")

    targeting = {
        "age_min": cfg.get("age_min", 25),
        "age_max": cfg.get("age_max", 60),
        "geo_locations": cfg.get("geo_locations", {"countries": ["PE"]}),
        "targeting_automation": {
            "advantage_audience": cfg.get("advantage_audience", 0),
        },
    }
    if interests:
        targeting["flexible_spec"] = [{"interests": interests}]

    # 3) Conjunto de anuncios
    adset_params = {
        "name": name + " - Conjunto 1",
        "campaign_id": campaign_id,
        "daily_budget": str(cfg.get("daily_budget_cents", 1650)),  # en centavos
        "billing_event": cfg.get("billing_event", "IMPRESSIONS"),
        "optimization_goal": cfg.get("optimization_goal", "LINK_CLICKS"),
        "bid_strategy": cfg.get("bid_strategy", "LOWEST_COST_WITHOUT_CAP"),
        "targeting": json.dumps(targeting),
        "status": status,
    }
    start = cfg.get("start_time")
    if start:
        adset_params["start_time"] = start
    adset = api("POST", f"{ad_account}/adsets", token, adset_params)
    adset_id = adset.get("id")
    print(f"  Conjunto creado: {adset_id}")

    # 4) Subir imagen y crear creative
    image_hash = upload_image(token, ad_account, cfg["creative_image"])
    print(f"  Imagen subida: {cfg['creative_image']}")

    story_spec = {
        "page_id": args.page_id,
        "link_data": {
            "link": cfg["landing"],
            "message": cfg["primary_text"],
            "name": cfg["headline"],
            "description": "Vigilante SEACE",
            "call_to_action": {"type": cfg.get("cta_button", "MESSAGE_PAGE")},
            "image_hash": image_hash,
        },
    }
    creative_params = {
        "name": name + " - Creativo 1",
        "object_story_spec": json.dumps(story_spec),
    }
    creative = api("POST", f"{ad_account}/adcreatives", token, creative_params)
    creative_id = creative.get("id")
    print(f"  Creativo creado: {creative_id}")

    # 5) Anuncio
    ad_params = {
        "name": name + " - Anuncio 1",
        "adset_id": adset_id,
        "creative": json.dumps({"creative_id": creative_id}),
        "status": status,
    }
    ad = api("POST", f"{ad_account}/ads", token, ad_params)
    print(f"  Anuncio creado: {ad.get('id')} ({status})")

    # 6) Vincular conversión del pixel (si está disponible en la cuenta)
    try:
        conv_params = {
            "name": name + " - " + cfg.get("pixel_event", "Lead"),
            "event_source": "pixel",
            "pixel_id": args.pixel_id,
            "event_type": cfg.get("pixel_event", "Lead"),
        }
        conv = api("POST", f"{ad_account}/customconversions", token, conv_params)
        print(f"  Conversión registrada: {conv.get('id')} (usar como evento en el Ad Set si se desea)")
    except Exception:
        print("  Nota: no se pudo registrar conversión automática (se puede configurar en Ads Manager).")

    return {
        "campaign": name,
        "campaign_id": campaign_id,
        "adset_id": adset_id,
        "creative_id": creative_id,
        "ad_id": ad.get("id"),
        "status": status,
    }


def main():
    global args
    parser = argparse.ArgumentParser(description="Lanza campañas Meta Ads para Vigilante SEACE")
    parser.add_argument("--token", required=True, help="Access token del System User de Meta")
    parser.add_argument("--ad-account", required=True, help="Ad Account ID (formato act_XXXX)")
    parser.add_argument("--page-id", required=True, help="Page ID de Facebook")
    parser.add_argument("--pixel-id", required=True, help="Pixel ID de Meta")
    parser.add_argument("--campaign", default=None, help="Lanzar solo una campaña por nombre")
    parser.add_argument("--activate", action="store_true", help="Dejar las campañas ACTIVE (default: PAUSED)")
    parser.add_argument("--config", default=str(CONFIG_PATH), help="Ruta al JSON de configuración")
    args = parser.parse_args()

    cfg_all = json.loads(Path(args.config).read_text(encoding="utf-8"))
    campaigns = cfg_all["campaigns"]
    if args.campaign:
        campaigns = [c for c in campaigns if c["name"] == args.campaign]
        if not campaigns:
            print(f"Campaña '{args.campaign}' no encontrada en el config.", file=sys.stderr)
            sys.exit(1)

    results = []
    for c in campaigns:
        try:
            results.append(create_campaign(args.token, args.ad_account, c, args.activate))
        except Exception as e:
            print(f"  !! {c['name']} falló: {e}", file=sys.stderr)

    print("\n=== RESUMEN ===")
    for r in results:
        print(f"  {r['campaign']}: campaign={r['campaign_id']} adset={r['adset_id']} ad={r['ad_id']} ({r['status']})")
    print("Revisa en Meta Ads Manager antes de activar. Con --activate las campañas quedan activas.")


if __name__ == "__main__":
    main()
