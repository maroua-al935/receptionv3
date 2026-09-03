param(
    [string]$ScannerAssemblyPath = "",
    [string]$PortName = "COM6",
    [int]$TimeoutMs = 5000
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ScannerAssemblyPath)) {
    $configPath = "C:\Program Files\ElyctisCardMiddleware\appsettings.json"
    if (Test-Path -LiteralPath $configPath) {
        $config = Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
        $ScannerAssemblyPath = $config.ScannerAssemblyPath
    }
}

if (-not (Test-Path -LiteralPath $ScannerAssemblyPath)) {
    throw "Scanner assembly not found: $ScannerAssemblyPath"
}

$scannerDir = Split-Path -Parent $ScannerAssemblyPath
Set-Location -LiteralPath $scannerDir

[AppDomain]::CurrentDomain.add_AssemblyResolve({
    param($sender, $eventArgs)
    $name = New-Object System.Reflection.AssemblyName($eventArgs.Name)
    $candidate = Join-Path (Get-Location) ($name.Name + ".dll")
    if (Test-Path -LiteralPath $candidate) {
        return [System.Reflection.Assembly]::LoadFrom($candidate)
    }
    return $null
})

$source = @"
using System;
using System.Threading;

public class MrzCapture {
    public ManualResetEventSlim Received = new ManualResetEventSlim(false);
    public string Text;

    public void OnMrz(string mrz) {
        Text = mrz;
        Received.Set();
    }
}
"@

if (-not ("MrzCapture" -as [type])) {
    Add-Type -TypeDefinition $source
}

$assembly = [System.Reflection.Assembly]::LoadFrom($ScannerAssemblyPath)
$scannerType = $assembly.GetType("ELY_TRAVEL_DOC.Scanner", $true)
$delegateType = $assembly.GetType("ELY_TRAVEL_DOC.DelegateReadMrz", $true)
$capture = New-Object MrzCapture
$callback = [System.Delegate]::CreateDelegate($delegateType, $capture, "OnMrz")
$scanner = [Activator]::CreateInstance($scannerType, $callback)

try {
    $connected = $scannerType.GetMethod("Connect").Invoke($scanner, @($PortName))
    Write-Host "CONNECT $PortName = $connected"
    if (-not $connected) {
        exit 20
    }

    $scannerType.GetMethod("Inquire").Invoke($scanner, @()) | Out-Null
    $null = $capture.Received.Wait($TimeoutMs)
    $mrz = $capture.Text

    if ([string]::IsNullOrWhiteSpace($mrz)) {
        $readMrzMethod = $scannerType.GetMethod(
            "ReadMRZ",
            [System.Reflection.BindingFlags]"Instance,Public,NonPublic"
        )
        if ($readMrzMethod) {
            $mrz = $readMrzMethod.Invoke($scanner, @($TimeoutMs))
        }
    }

    if ([string]::IsNullOrWhiteSpace($mrz)) {
        Write-Host "MRZ $PortName = <empty>"
        exit 21
    }

    Write-Host "MRZ $PortName length = $($mrz.Length)"
    Write-Host $mrz
} finally {
    try {
        $scannerType.GetMethod("Disconnect").Invoke($scanner, @()) | Out-Null
    } catch {}
}
