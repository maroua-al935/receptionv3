param(
    [string]$InstallDir = "$env:ProgramFiles\ElyctisCardMiddleware",
    [string]$ServiceName = "ElyctisCardMiddleware",
    [string]$ScannerPortName = "",
    [string]$ScannerAssemblyPath = "",
    [int]$ScannerMrzTimeoutMs = 20000
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

function Install-ElyctisWatchdog {
    param([string]$InstallDir, [string]$ServiceName)

    $watchdogDir = Join-Path $env:ProgramData "ElyctisCardMiddleware"
    New-Item -ItemType Directory -Force -Path $watchdogDir | Out-Null
    $watchdogPath = Join-Path $watchdogDir "elyctis-watchdog.ps1"
    $watchdogScript = @'
$ErrorActionPreference = "SilentlyContinue"
$serviceName = "ElyctisCardMiddleware"
$healthUrl = "http://127.0.0.1:8765/health?token=change-this-local-token"

$svc = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if (-not $svc) { exit 2 }

if ($svc.Status -ne "Running") {
    Start-Service -Name $serviceName -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
}

try {
    $health = Invoke-RestMethod -Uri $healthUrl -TimeoutSec 4
    if ($health.success -ne $true) {
        Restart-Service -Name $serviceName -Force -ErrorAction SilentlyContinue
    }
} catch {
    Restart-Service -Name $serviceName -Force -ErrorAction SilentlyContinue
}
'@

    $watchdogScript | Set-Content -LiteralPath $watchdogPath -Encoding UTF8
    $taskName = "ElyctisCardMiddlewareWatchdog"
    $powershellPath = Join-Path $env:SystemRoot "System32\WindowsPowerShell\v1.0\powershell.exe"
    $taskCommand = "`"$powershellPath`" -NoProfile -ExecutionPolicy Bypass -File `"$watchdogPath`""
    & schtasks.exe /Create /TN $taskName /SC MINUTE /MO 1 /TR $taskCommand /RU SYSTEM /RL HIGHEST /F | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Step "Installed watchdog scheduled task $taskName"
    } else {
        Write-Warning "Could not install watchdog scheduled task $taskName"
    }
}

function Wait-ElyctisHealth {
    param([string]$HealthUrl)

    for ($i = 1; $i -le 15; $i++) {
        try {
            $health = Invoke-RestMethod -Uri $HealthUrl -TimeoutSec 5
            if ($health.success -eq $true) {
                return $true
            }
        } catch {
            Start-Sleep -Seconds 1
        }
    }

    return $false
}

function Find-ElyctisScannerPort {
    if (-not [string]::IsNullOrWhiteSpace($ScannerPortName)) {
        return $ScannerPortName
    }

    try {
        $devices = Get-CimInstance Win32_PnPEntity -ErrorAction SilentlyContinue | Where-Object {
            $identity = "$($_.Name) $($_.DeviceID) $($_.PNPDeviceID) $($_.Manufacturer)"
            $identity -match '\(COM\d+\)' -or $identity -match 'ELYCTIS|Elyctis|VID_2B78|PID_0005|Virtual Com'
        }

        $preferred = $devices | Where-Object {
            $identity = "$($_.Name) $($_.DeviceID) $($_.PNPDeviceID) $($_.Manufacturer)"
            $identity -match '\(COM\d+\)' -and $identity -match 'ELYCTIS|Elyctis|VID_2B78|PID_0005|Virtual Com'
        } | Select-Object -First 1

        if ($preferred -and $preferred.Name -match '\((COM\d+)\)') {
            Write-Step "Auto-detected Elyctis scanner device: $($preferred.Name) [$($preferred.DeviceID)]"
            return $Matches[1]
        }

        $genericComDevices = @($devices | Where-Object {
            $_.Name -match '\(COM\d+\)' -and $_.Name -notmatch 'Intel|AMT|Bluetooth|Modem|Management'
        })
        if ($genericComDevices.Count -eq 1 -and $genericComDevices[0].Name -match '\((COM\d+)\)') {
            Write-Warning "Only one non-system COM device was found. Using $($genericComDevices[0].Name)."
            return $Matches[1]
        }
    } catch {
        Write-Warning "Could not auto-detect scanner COM port: $($_.Exception.Message)"
    }

    Write-Warning "No Elyctis/Virtual COM scanner device was auto-detected. Falling back to COM6; pass -ScannerPortName COMx if needed."
    return "COM6"
}

function Find-ElyTravelDoc {
    if (-not [string]::IsNullOrWhiteSpace($ScannerAssemblyPath) -and (Test-Path $ScannerAssemblyPath)) {
        return $ScannerAssemblyPath
    }

    $packagedScanner = Join-Path $root "ELY_TRAVEL_DOC_x86\ELY TRAVEL DOC.exe"
    if (Test-Path $packagedScanner) {
        return $packagedScanner
    }

    $candidatePaths = @(
        "$env:USERPROFILE\Desktop\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe",
        "$env:USERPROFILE\Desktop\Application - ELY TRAVEL DOC v4.9.3\x64\Release\ELY TRAVEL DOC.exe",
        "$env:USERPROFILE\Downloads\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe",
        "$env:USERPROFILE\Downloads\Application - ELY TRAVEL DOC v4.9.3\x64\Release\ELY TRAVEL DOC.exe",
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
$config.ReadTimeoutMs = 120000
$config.ReadDG2Photo = $true
$config.EnableVendorLogs = $false
$config.ScannerPortName = Find-ElyctisScannerPort
$config.ScannerMrzTimeoutMs = $ScannerMrzTimeoutMs

$scannerPath = Find-ElyTravelDoc
if ($scannerPath) {
    if ($scannerPath -like (Join-Path $root "ELY_TRAVEL_DOC_x86\*")) {
        $scannerInstallDir = Join-Path $InstallDir "Scanner\x86"
        Write-Step "Installing packaged ELY TRAVEL DOC scanner files to $scannerInstallDir"
        New-Item -ItemType Directory -Force -Path $scannerInstallDir | Out-Null
        Copy-Item -Path (Join-Path $root "ELY_TRAVEL_DOC_x86\*") -Destination $scannerInstallDir -Recurse -Force
        New-Item -ItemType Directory -Force -Path (Join-Path $scannerInstallDir "logs") | Out-Null
        $config.ScannerAssemblyPath = Join-Path $scannerInstallDir "ELY TRAVEL DOC.exe"
    } else {
        $config.ScannerAssemblyPath = $scannerPath
    }
    New-Item -ItemType Directory -Force -Path (Join-Path (Split-Path -Parent $config.ScannerAssemblyPath) "logs") | Out-Null
    Get-ChildItem -Path (Split-Path -Parent $config.ScannerAssemblyPath) -Recurse -File | Unblock-File -ErrorAction SilentlyContinue
} else {
    Write-Warning "ELY TRAVEL DOC.exe was not found. Card reading may require MRZ/CAN input until it is installed."
}

$config | ConvertTo-Json -Depth 10 | Set-Content -Path $configPath -Encoding UTF8
Write-Step "Scanner COM port: $($config.ScannerPortName)"
Write-Step "Scanner path: $($config.ScannerAssemblyPath)"
Write-Step "Scanner MRZ timeout: $($config.ScannerMrzTimeoutMs) ms"

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
& $nssm set $ServiceName AppThrottle 1500 | Out-Null
& $nssm set $ServiceName AppRestartDelay 1000 | Out-Null
& $nssm set $ServiceName DisplayName "Elyctis Card Middleware" | Out-Null

try {
    & sc.exe failure $ServiceName reset= 60 actions= restart/1000/restart/3000/restart/5000 | Out-Null
    & sc.exe failureflag $ServiceName 1 | Out-Null
    Write-Step "Configured Windows service recovery."
} catch {
    Write-Warning "Could not configure service recovery: $($_.Exception.Message)"
}

Install-ElyctisWatchdog -InstallDir $InstallDir -ServiceName $ServiceName

Write-Step "Starting service..."
Start-Service $ServiceName

$healthUrl = "http://127.0.0.1:8765/health?token=change-this-local-token"
if (-not (Wait-ElyctisHealth -HealthUrl $healthUrl)) { throw "Health check failed." }

Write-Step "Installed $ServiceName and verified http://127.0.0.1:8765/health"
Write-Step "Reader filter: ELYCTIS"
Write-Step "If the app reports 'MRZ scanner non lue', run: .\diagnose-elyctis-client.ps1 -ReadCard"
Write-Step "Done."
