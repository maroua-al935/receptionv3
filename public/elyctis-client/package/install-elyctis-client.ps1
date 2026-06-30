param(
    [string]$InstallDir = "$env:ProgramFiles\ElyctisCardMiddleware",
    [string]$ServiceName = "ElyctisCardMiddleware",
    [string]$ScannerPortName = "",
    [string]$ScannerAssemblyPath = ""
)

$ErrorActionPreference = "Stop"

function Assert-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw "Run this script as Administrator."
    }
}

function Write-Step {
    param([string]$Message)
    Write-Host "[Elyctis] $Message"
}

function Stop-OldElyctis {
    param([string]$Name, [string]$NssmPath)

    Write-Step "Stopping old Elyctis services/processes if they exist..."
    $existing = Get-Service -Name $Name -ErrorAction SilentlyContinue
    if ($existing) {
        if ($existing.Status -ne "Stopped") {
            Stop-Service -Name $Name -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 2
        }
        & $NssmPath remove $Name confirm | Out-Null
        Start-Sleep -Seconds 1
    }

    Get-Process -Name ElyCardReaderService,ElyctisCardService -ErrorAction SilentlyContinue |
        Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1
}

function Find-ElyctisScannerPort {
    if (-not [string]::IsNullOrWhiteSpace($ScannerPortName)) {
        return $ScannerPortName
    }

    try {
        $devices = Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -match 'ELYCTIS|Elyctis|Virtual Com|COM' }

        $preferred = $devices |
            Where-Object { $_.Name -match 'ELYCTIS|Elyctis' -and $_.Name -match '\(COM\d+\)' } |
            Select-Object -First 1

        if (-not $preferred) {
            $preferred = $devices |
                Where-Object { $_.Name -match '\(COM\d+\)' } |
                Select-Object -First 1
        }

        if ($preferred -and $preferred.Name -match '\((COM\d+)\)') {
            return $Matches[1]
        }
    } catch {
        Write-Warning "Could not auto-detect scanner COM port: $($_.Exception.Message)"
    }

    return "COM6"
}

function Find-ElyTravelDoc {
    if (-not [string]::IsNullOrWhiteSpace($ScannerAssemblyPath) -and (Test-Path $ScannerAssemblyPath)) {
        return $ScannerAssemblyPath
    }

    $candidatePaths = @(
        "$env:USERPROFILE\Desktop\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe",
        "$env:USERPROFILE\Desktop\Application - ELY TRAVEL DOC v4.9.3\x64\Release\ELY TRAVEL DOC.exe",
        "$env:PUBLIC\Desktop\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe",
        "$env:PUBLIC\Desktop\Application - ELY TRAVEL DOC v4.9.3\x64\Release\ELY TRAVEL DOC.exe",
        "$env:ProgramFiles\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe",
        "$env:ProgramFiles\Application - ELY TRAVEL DOC v4.9.3\x64\Release\ELY TRAVEL DOC.exe",
        "${env:ProgramFiles(x86)}\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe",
        "${env:ProgramFiles(x86)}\Application - ELY TRAVEL DOC v4.9.3\x64\Release\ELY TRAVEL DOC.exe"
    )

    foreach ($path in $candidatePaths) {
        if ($path -and (Test-Path $path)) {
            return $path
        }
    }

    return $null
}

Assert-Administrator

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$nssm = Join-Path $root "nssm.exe"
$source = Join-Path $root "ElyctisCardMiddleware"
$exe = Join-Path $InstallDir "ElyctisCardService.exe"

Write-Step "Installer folder: $root"
if (-not (Test-Path $nssm)) { throw "Missing nssm.exe beside this installer." }
if (-not (Test-Path (Join-Path $source "ElyctisCardService.exe"))) { throw "Missing ElyctisCardMiddleware files." }

Stop-OldElyctis -Name $ServiceName -NssmPath $nssm

Write-Step "Copying middleware files to $InstallDir"
New-Item -ItemType Directory -Force -Path $InstallDir | Out-Null
Copy-Item -Path (Join-Path $source "*") -Destination $InstallDir -Recurse -Force
Get-ChildItem -Path $InstallDir -Recurse -File | Unblock-File -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path (Join-Path $InstallDir "logs") | Out-Null

$configPath = Join-Path $InstallDir "appsettings.json"
$config = Get-Content $configPath -Raw | ConvertFrom-Json
$config.ListenUrl = "http://127.0.0.1:8765/"
$config.AllowedOrigin = "*"
$config.ReaderNameContains = "ELYCTIS"
$config.ScannerPortName = Find-ElyctisScannerPort

$scannerPath = Find-ElyTravelDoc
if ($scannerPath) {
    $config.ScannerAssemblyPath = $scannerPath
    Get-ChildItem -Path (Split-Path -Parent $scannerPath) -Recurse -File | Unblock-File -ErrorAction SilentlyContinue
} else {
    Write-Warning "ELY TRAVEL DOC.exe was not found. Card reading may require MRZ/CAN input until it is installed."
}

$config | ConvertTo-Json -Depth 10 | Set-Content -Path $configPath -Encoding UTF8
Write-Step "Scanner COM port: $($config.ScannerPortName)"
Write-Step "Scanner path: $($config.ScannerAssemblyPath)"

try {
    Write-Step "Starting Windows Smart Card service..."
    Set-Service SCardSvr -StartupType Automatic
    Start-Service SCardSvr -ErrorAction SilentlyContinue
} catch {
    Write-Warning "Could not start Windows Smart Card service: $($_.Exception.Message)"
}

Write-Step "Installing Windows service $ServiceName with NSSM..."
& $nssm install $ServiceName $exe | Out-Null
& $nssm set $ServiceName AppParameters "--headless" | Out-Null
& $nssm set $ServiceName AppDirectory $InstallDir | Out-Null
& $nssm set $ServiceName AppStdout (Join-Path $InstallDir "logs\elyctis-service.out.log") | Out-Null
& $nssm set $ServiceName AppStderr (Join-Path $InstallDir "logs\elyctis-service.err.log") | Out-Null
& $nssm set $ServiceName AppRotateFiles 1 | Out-Null
& $nssm set $ServiceName AppRotateOnline 1 | Out-Null
& $nssm set $ServiceName AppRotateBytes 10485760 | Out-Null
& $nssm set $ServiceName Start SERVICE_AUTO_START | Out-Null
& $nssm set $ServiceName AppExit Default Restart | Out-Null
& $nssm set $ServiceName DisplayName "Elyctis Card Middleware" | Out-Null

Write-Step "Starting service..."
Start-Service $ServiceName
Start-Sleep -Seconds 4

$health = Invoke-RestMethod -Uri "http://127.0.0.1:8765/health?token=change-this-local-token" -TimeoutSec 10
if ($health.success -ne $true) { throw "Health check failed." }

Write-Step "Installed $ServiceName and verified http://127.0.0.1:8765/health"
Write-Step "Reader filter: ELYCTIS"
Write-Step "Done."
