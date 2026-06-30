ELYCTIS client workstation install
==================================

Run this on EACH Windows PC that has the Elyctis card reader connected.
Open PowerShell as Administrator in this extracted folder, then run:

  Set-ExecutionPolicy -Scope Process Bypass -Force
  .\install-elyctis-client.ps1

If the scanner COM port or ELY TRAVEL DOC path is not detected automatically:

  .\install-elyctis-client.ps1 -ScannerPortName COM7 -ScannerAssemblyPath "C:\Users\ANAM1429\Desktop\Application - ELY TRAVEL DOC v4.9.3\x86\Release\ELY TRAVEL DOC.exe"

To find the COM port:

  Get-CimInstance Win32_PnPEntity | Where-Object { $_.Name -match 'ELYCTIS|COM' } | Select-Object Name

The installer:
- installs NSSM service ElyctisCardMiddleware;
- listens only on this PC at http://127.0.0.1:8765;
- forces ReaderNameContains=ELYCTIS so it does not pick another smart-card reader;
- auto-detects ELY TRAVEL DOC.exe, scanner COM port, and unblocks vendor files;
- starts Windows Smart Card service.

The SIGAM/VisiLog page /guests/add calls this localhost endpoint from the browser.
Do not install only on the server if the reader is connected to user PCs.
