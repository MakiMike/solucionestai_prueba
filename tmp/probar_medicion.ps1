$ErrorActionPreference='Stop'
$parseErrors=$null
$tokens=$null
$null=[Management.Automation.Language.Parser]::ParseFile((Join-Path $PSScriptRoot '..\k8s\minikube\Medir-Catalogo.ps1'),[ref]$tokens,[ref]$parseErrors)
if ($parseErrors.Count) { throw ($parseErrors | Out-String) }
$credential=New-Object PSCredential('mock@tienda.test',(ConvertTo-SecureString 'dummy' -AsPlainText -Force))
& "$PSScriptRoot\..\k8s\minikube\Medir-Catalogo.ps1" -BaseUrl http://127.0.0.1:18764 -SegundosPorFase 2 -Usuarios 2 -Credencial $credential
if (-not $?) { throw 'La medicion de prueba fallo.' }
$folder=Get-ChildItem "$PSScriptRoot\..\output\evidencias\rendimiento" -Directory | Sort-Object Name -Descending | Select-Object -First 1
$summary=@(Import-Csv (Join-Path $folder.FullName 'resumen.csv'))
if ($summary.Count -ne 2 -or @($summary | Where-Object { [int]$_.Fallos -ne 0 -or [double]$_.P95ms -le 0 }).Count) { throw 'Resumen incorrecto.' }
Write-Host 'PRUEBA LOCAL SIMULADA OK (no representa rendimiento de Kubernetes).'
