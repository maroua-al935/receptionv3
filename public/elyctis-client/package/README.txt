ELYCTIS client workstation install
==================================

Run this on EACH Windows PC that has the Elyctis card reader connected.
Simple method:

  Right-click install-elyctis-client-as-admin.bat
  Run as administrator

If the scanner is known to be COM3:

  Right-click install-elyctis-client-com3-as-admin.bat
  Run as administrator

Manual method:
Open PowerShell as Administrator in this extracted folder, then run:

  Set-ExecutionPolicy -Scope Process Bypass -Force
  .\install-elyctis-client.ps1

If the scanner COM port or ELY TRAVEL DOC path is not detected automatically:

  .\install-elyctis-client.ps1 -ScannerPortName COM7 -ScannerAssemblyPath "C:\Users\ANAM1429\Desktop\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe"

For a PC where Windows detects the scanner on COM3:

  .\install-elyctis-client.ps1 -ScannerPortName COM3 -ScannerMrzTimeoutMs 20000

To find the COM port:

  Get-CimInstance Win32_PnPEntity | Where-Object { $_.Name -match 'ELYCTIS|COM' } | Select-Object Name

If the web app shows "Lecture impossible: MRZ scanner non lue":

  .\diagnose-elyctis-client.ps1 -ReadCard

To test only the MRZ scanner port, without changing the service config:

  C:\Windows\SysWOW64\WindowsPowerShell\v1.0\powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\test-mrz-scanner-port.ps1 -PortName COM3
  C:\Windows\SysWOW64\WindowsPowerShell\v1.0\powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\test-mrz-scanner-port.ps1 -PortName COM6

Useful repair commands:

  .\diagnose-elyctis-client.ps1 -SetScannerPortName COM3 -ReadCard
  .\diagnose-elyctis-client.ps1 -SetScannerMrzTimeoutMs 20000 -ReadCard

The installer:
- installs NSSM service ElyctisCardMiddleware;
- configures Windows/NSSM restart recovery if the service crashes;
- installs a SYSTEM watchdog scheduled task that checks health every minute;
- listens only on this PC at http://127.0.0.1:8765;
- forces ReaderNameContains=ELYCTIS so it does not pick another smart-card reader;
- installs the packaged ELY TRAVEL DOC scanner files when no local scanner app is found;
- auto-detects scanner COM port and unblocks vendor files;
- if the configured COM port does not return MRZ, probes other likely COM ports;
- allows a longer MRZ scanner timeout for slower workstations;
- starts Windows Smart Card service.

The SIGAM/VisiLog page /guests/add calls this localhost endpoint from the browser.
Do not install only on the server if the reader is connected to user PCs.
