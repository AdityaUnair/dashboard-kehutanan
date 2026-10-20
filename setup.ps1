# =====================================================================
# Setup sekali jalan (Windows / PowerShell): database, pipeline ETL, dan website.
#   Prasyarat: XAMPP dengan MySQL menyala, Python 3.10+, PHP 8.2+, Composer, Node.js 20+.
#   Jalankan dari folder repo:  powershell -ExecutionPolicy Bypass -File setup.ps1
#   Setelah selesai:            cd web; php artisan serve   -> http://localhost:8000
# =====================================================================
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

function Langkah($teks) { Write-Host "`n==> $teks" -ForegroundColor Green }
function Gagal($teks) { Write-Host "`nGagal: $teks" -ForegroundColor Red; exit 1 }
function Jalankan($perintah) { & cmd /c $perintah; if ($LASTEXITCODE -ne 0) { Gagal "perintah gagal: $perintah" } }

foreach ($p in 'php', 'composer', 'npm') {
    if (-not (Get-Command $p -ErrorAction SilentlyContinue)) { Gagal "'$p' belum terpasang (lihat Prasyarat di README)." }
}
$python = if (Get-Command py -ErrorAction SilentlyContinue) { 'py -3' } elseif (Get-Command python -ErrorAction SilentlyContinue) { 'python' } else { Gagal "Python belum terpasang." }
$mysql = if ($env:MYSQL) { $env:MYSQL } elseif (Test-Path 'C:\xampp\mysql\bin\mysql.exe') { 'C:\xampp\mysql\bin\mysql.exe' } elseif (Get-Command mysql -ErrorAction SilentlyContinue) { 'mysql' } else { Gagal "mysql.exe tidak ditemukan. Pasang XAMPP di C:\xampp atau isi `$env:MYSQL." }

Langkah 'Menyiapkan file .env'
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path web\.env)) { Copy-Item web\.env.example web\.env }
$pass = ((Get-Content .env | Where-Object { $_ -match '^DB_PASSWORD=' }) -replace '^DB_PASSWORD=', '')
$env:MYSQL_PWD = $pass
& $mysql -h 127.0.0.1 -u root -e "SELECT 1" *> $null
if ($LASTEXITCODE -ne 0) { Gagal 'Tidak bisa terhubung ke MariaDB di 127.0.0.1:3306. Nyalakan MySQL di XAMPP dulu.' }

Langkah 'Membuat 3 database (staging, master, dashboard)'
Jalankan "`"$mysql`" -h 127.0.0.1 -u root --default-character-set=utf8mb4 < sql\schema.sql"

Langkah 'Memasang library Python (virtual env .venv)'
Jalankan "$python -m venv .venv"
Jalankan ".venv\Scripts\python.exe -m pip install -q --disable-pip-version-check -r etl\requirements.txt"

$env:PYTHONIOENCODING = "utf-8"
Langkah 'Menjalankan pipeline ETL (extract -> transform/load -> replikasi)'
Push-Location etl
Jalankan "..\.venv\Scripts\python.exe run_etl.py"
Pop-Location

Langkah 'Menyiapkan website Laravel'
Push-Location web
Jalankan 'composer install --no-interaction --quiet'
Jalankan 'npm install --silent'
Jalankan 'npm run build --silent'
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+' -Quiet)) { Jalankan 'php artisan key:generate --force' }
Jalankan 'php artisan migrate --seed --force'
Pop-Location

Write-Host "`nSelesai. Jalankan:`n`n    cd web; php artisan serve`n`nlalu buka http://localhost:8000 (akun demo ada di halaman login)." -ForegroundColor Green
