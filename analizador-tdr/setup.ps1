# Script de instalación y despliegue rápido
# Ejecutar en PowerShell: .\setup.ps1

Write-Host "🚀 Instalando Analizador TDR SEACE..." -ForegroundColor Cyan

# Verificar Python
Write-Host "`n1️⃣  Verificando Python..." -ForegroundColor Yellow
python --version
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Python no encontrado. Instala Python 3.10+ primero." -ForegroundColor Red
    exit 1
}

# Crear entorno virtual
Write-Host "`n2️⃣  Creando entorno virtual..." -ForegroundColor Yellow
python -m venv venv

# Activar entorno virtual
Write-Host "`n3️⃣  Activando entorno virtual..." -ForegroundColor Yellow
.\venv\Scripts\Activate.ps1

# Instalar dependencias
Write-Host "`n4️⃣  Instalando dependencias..." -ForegroundColor Yellow
pip install --upgrade pip
pip install -r requirements.txt

# Copiar .env si no existe
if (!(Test-Path ".env")) {
    Write-Host "`n5️⃣  Creando archivo .env..." -ForegroundColor Yellow
    Copy-Item .env.example .env
    Write-Host "⚠️  IMPORTANTE: Edita el archivo .env y configura tu API key" -ForegroundColor Yellow
} else {
    Write-Host "`n5️⃣  Archivo .env ya existe" -ForegroundColor Green
}

Write-Host "`n✅ Instalación completada!" -ForegroundColor Green
Write-Host "`n📝 Próximos pasos:" -ForegroundColor Cyan
Write-Host "1. Edita el archivo .env con tu API key"
Write-Host "2. Ejecuta: python main.py"
Write-Host "3. Abre: http://localhost:8001/docs"
