#!/usr/bin/env python3
"""List GA4 properties accessible by the service account."""
import json, os, sys
sys.path.insert(0, os.path.dirname(__file__))

from google.oauth2 import service_account
from googleapiclient.discovery import build

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CREDS_PATH = os.path.join(PROJECT_ROOT, "storage", "ga4-service-account.json")

creds = service_account.Credentials.from_service_account_file(
    CREDS_PATH,
    scopes=["https://www.googleapis.com/auth/analytics.readonly"]
)

admin = build("analyticsadmin", "v1beta", credentials=creds)
resp = admin.accountSummaries().list().execute()

for acc in resp.get("accountSummaries", []):
    name = acc.get("account", "unknown")
    for prop in acc.get("propertySummaries", []):
        prop_id = prop.get("property", "").replace("properties/", "")
        display = prop.get("displayName", "N/A")
        print(f"{display} -> properties/{prop_id}")
