param(
    [string]$InstallDir = "$env:ProgramFiles\ElyctisCardMiddleware",
    [string]$ServiceName = "ElyctisCardMiddleware",
    [string]$Token = "change-this-local-token",
    [switch]$ReadCard,
    [string]$SetScannerPortName = "",
    [int]$SetScannerMrzTimeoutMs = 0
)

$ErrorActionPreference = "Continue"

function Write-Step {
    param([string]$Message)
    Write-Host "[Elyctis] $Message"
}

function Show-LatestLog {
    param([string]$InstallDir)

    $logDir = Join-Path $InstallDir "logs"
    if (-not (Test-Path $logDir)) {
        Write-Warning "Log folder not found: $logDir"
        return
    }

    $latest = Get-ChildItem -Path $logDir -Filter "*.log" -File -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if (-not $latest) {
        Write-Warning "No .log file found in $logDir"
        return
    }

    Write-Step "Latest service log: $($latest.FullName)"
    $tail = Get-Content -LiteralPath $latest.FullName -Tail 120
    $tail

    $joined = ($tail -join "`n")
    if ($joined -match "Scanner assembly not found") {
        Write-Warning "Diagnosis: ScannerAssemblyPath is missing or invalid."
    }
    if ($joined -match "MRZ scanner connect\(([^)]+)\) returned False") {
        Write-Warning "Diagnosis: the service could not connect to the scanner on $($Matches[1]). Recheck COM port."
    }
    if ($joined -match "MRZ scanner returned no data") {
        Write-Warning "Diagnosis: COM connection worked, but no MRZ text was received. Check card placement/orientation and try a longer timeout."
    }
    if ($joined -match "MRZ scanner read failed: (.+)") {
        Write-Warning "Diagnosis: scanner API failed: $($Matches[1])"
    }
}

$configPath = Join-Path $InstallDir "appsettings.json"
if (-not (Test-Path $configPath)) {
    throw "Config not found: $configPath. Install ElyctisCardMiddleware first."
}

$config = Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
$changed = $false

if (-not [string]::IsNullOrWhiteSpace($SetScannerPortName)) {
    $config.ScannerPortName = $SetScannerPortName
    $changed = $true
}

if ($SetScannerMrzTimeoutMs -gt 0) {
    $config.ScannerMrzTimeoutMs = $SetScannerMrzTimeoutMs
    $changed = $true
}

if ($changed) {
    $config | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $configPath -Encoding UTF8
    Write-Step "Updated appsettings.json"
    Restart-Service -Name $ServiceName -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
}

Write-Step "Service status"
Get-Service -Name $ServiceName -ErrorAction SilentlyContinue | Format-List Name,Status,StartType

Write-Step "Smart Card service"
Get-Service -Name SCardSvr -ErrorAction SilentlyContinue | Format-List Name,Status,StartType

Write-Step "Installed config"
$config = Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
$config |
    Select-Object ListenUrl,ReaderNameContains,ScannerPortName,ScannerMrzTimeoutMs,ScannerAssemblyPath,ReadTimeoutMs,ReadDG2Photo |
    Format-List

Write-Step "Detected COM devices"
Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue |
    Where-Object {
        $identity = "$($_.Name) $($_.DeviceID) $($_.PNPDeviceID) $($_.Manufacturer)"
        $identity -match 'ELYCTIS|Elyctis|Virtual Com|COM|VID_2B78|PID_0005'
    } |
    Select-Object Name,DeviceID,Manufacturer,Status |
    Format-Table -AutoSize

$escapedToken = [uri]::EscapeDataString($Token)

Write-Step "Health check"
try {
    Invoke-RestMethod -Uri "http://127.0.0.1:8765/health?token=$escapedToken" -TimeoutSec 10 |
        ConvertTo-Json -Depth 10
} catch {
    Write-Warning "Health failed: $($_.Exception.Message)"
}

Write-Step "Middleware diagnostics"
try {
    Invoke-RestMethod -Uri "http://127.0.0.1:8765/diagnostics?token=$escapedToken" -TimeoutSec 10 |
        ConvertTo-Json -Depth 10
} catch {
    Write-Warning "Diagnostics failed: $($_.Exception.Message)"
}

if ($ReadCard) {
    Write-Step "Read-card test"
    Write-Host "Place the document/card correctly in the Elyctis scanner, then wait..."
    try {
        Invoke-RestMethod -Uri "http://127.0.0.1:8765/read-card?token=$escapedToken" -TimeoutSec 60 |
            ConvertTo-Json -Depth 20
    } catch {
        Write-Warning "Read-card request failed: $($_.Exception.Message)"
    }
} else {
    Write-Step "To test the MRZ scanner and card read, run:"
    Write-Host ".\diagnose-elyctis-client.ps1 -ReadCard"
}

Show-LatestLog -InstallDir $InstallDir
