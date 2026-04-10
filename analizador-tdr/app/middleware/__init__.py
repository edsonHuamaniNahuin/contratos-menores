"""
Middleware de seguridad: JWT, Rate-Limit, IP Allowlist.

Responsabilidades:
1. Verificar JWT HMAC-SHA256 en header Authorization: Bearer <token>
2. Rate-limit por user_id (ventana deslizante en memoria)
3. Extraer user context para logging y auditoría

El /health endpoint queda exento para monitoreo sin auth.
"""
import hashlib
import hmac
import json
import time
import logging
from base64 import urlsafe_b64decode
from collections import defaultdict
from typing import Optional

from fastapi import Request, HTTPException
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials

from config import settings

logger = logging.getLogger(__name__)

# ── Esquema Bearer para OpenAPI ────────────────────────────────────────────
_bearer_scheme = HTTPBearer(auto_error=False)


# ── Rate-limiter en memoria (por user_id) ────────────────────────────────
class _RateLimiter:
    """Ventana deslizante simple por key. Thread-safe en asyncio (GIL)."""

    def __init__(self, max_requests: int = 5, window_seconds: int = 60):
        self.max_requests = max_requests
        self.window = window_seconds
        self._hits: dict[str, list[float]] = defaultdict(list)

    def check(self, key: str) -> bool:
        """True si el request está permitido, False si excede el límite."""
        now = time.time()
        cutoff = now - self.window
        hits = self._hits[key]
        # Limpiar entradas antiguas
        self._hits[key] = [t for t in hits if t > cutoff]
        if len(self._hits[key]) >= self.max_requests:
            return False
        self._hits[key].append(now)
        return True

    def remaining(self, key: str) -> int:
        now = time.time()
        cutoff = now - self.window
        return max(0, self.max_requests - sum(1 for t in self._hits[key] if t > cutoff))


_rate_limiter = _RateLimiter(
    max_requests=settings.rate_limit_per_user,
    window_seconds=60,
)


# ── JWT verification ──────────────────────────────────────────────────────

def _b64decode(data: str) -> bytes:
    """Base64url decode con padding."""
    padding = 4 - len(data) % 4
    if padding != 4:
        data += "=" * padding
    return urlsafe_b64decode(data)


def _verify_jwt(token: str) -> dict:
    """
    Verifica JWT HMAC-SHA256 y retorna payload claims.

    Raises HTTPException 401/403 si el token es inválido o expirado.
    """
    secret = settings.analizador_tdr_secret
    if not secret or len(secret) < 32:
        raise HTTPException(
            status_code=503,
            detail="Servicio no configurado: falta ANALIZADOR_TDR_SECRET"
        )

    parts = token.split(".")
    if len(parts) != 3:
        raise HTTPException(status_code=401, detail="JWT malformado")

    header_b64, payload_b64, signature_b64 = parts

    # Verificar firma HMAC-SHA256
    try:
        key = bytes.fromhex(secret)
    except ValueError:
        key = secret.encode()

    expected_sig = hmac.new(
        key,
        f"{header_b64}.{payload_b64}".encode(),
        hashlib.sha256,
    ).digest()

    try:
        received_sig = _b64decode(signature_b64)
    except Exception:
        raise HTTPException(status_code=401, detail="Firma JWT ilegible")

    if not hmac.compare_digest(expected_sig, received_sig):
        logger.warning("JWT: firma HMAC inválida")
        raise HTTPException(status_code=401, detail="Firma JWT inválida")

    # Decodificar payload
    try:
        payload = json.loads(_b64decode(payload_b64))
    except Exception:
        raise HTTPException(status_code=401, detail="Payload JWT inválido")

    # Verificar expiración (5s tolerancia para clock skew)
    exp = payload.get("exp", 0)
    if exp < (time.time() - 5):
        logger.warning("JWT expirado: exp=%s, now=%s", exp, int(time.time()))
        raise HTTPException(status_code=401, detail="Token JWT expirado")

    # Verificar issuer
    if payload.get("iss") != "vigilante-seace":
        raise HTTPException(status_code=401, detail="Issuer JWT no autorizado")

    return payload


# ── Dependency principal para inyectar en endpoints ──────────────────────

class AuthContext:
    """Contexto del usuario autenticado, disponible en cada request."""
    __slots__ = ("user_id", "action", "origin")

    def __init__(self, user_id: int, action: str, origin: str):
        self.user_id = user_id
        self.action = action
        self.origin = origin

    def __repr__(self):
        return f"AuthContext(user={self.user_id}, act={self.action}, org={self.origin})"


async def require_auth(request: Request) -> AuthContext:
    """
    FastAPI Dependency: extrae y valida JWT del header Authorization.

    Uso en endpoint:
        @app.post("/analyze-tdr")
        async def analyze_tdr(auth: AuthContext = Depends(require_auth)):
            logger.info(f"User {auth.user_id} from {auth.origin}")

    Endpoints exentos: /health, /docs, /redoc, /openapi.json, /
    """
    # Extraer token del header
    auth_header = request.headers.get("Authorization", "")
    if not auth_header.startswith("Bearer "):
        raise HTTPException(
            status_code=401,
            detail="Header Authorization: Bearer <token> requerido"
        )

    token = auth_header[7:]  # Quitar "Bearer "
    if not token:
        raise HTTPException(status_code=401, detail="Token vacío")

    # Verificar JWT
    claims = _verify_jwt(token)

    user_id = int(claims.get("sub", 0))
    action = claims.get("act", "unknown")
    origin = claims.get("org", "unknown")

    # Rate-limit por user_id
    rate_key = f"user:{user_id}"
    if not _rate_limiter.check(rate_key):
        remaining = _rate_limiter.remaining(rate_key)
        logger.warning("Rate-limit excedido: user=%s action=%s", user_id, action)
        raise HTTPException(
            status_code=429,
            detail=f"Demasiadas peticiones. Límite: {settings.rate_limit_per_user}/min.",
            headers={"Retry-After": "60"},
        )

    logger.info("✅ Auth OK: user=%s act=%s org=%s", user_id, action, origin)

    return AuthContext(user_id=user_id, action=action, origin=origin)
