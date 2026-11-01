param([ValidatePattern('^([01]\d|2[0-3]):[0-5]\d$')][string]$Hora = '20:00')
$ErrorActionPreference = 'Stop'
$taskName = 'TiendaProd-RespaldoDiario'
if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) { throw 'La tarea ya existe. Revisala antes de modificarla.' }
$script = Join-Path $PSScriptRoot 'Respaldo-Programado.ps1'
$powershell = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$arguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $script + '" -Conservar 7'
$action = New-ScheduledTaskAction -Execute $powershell -Argument $arguments -WorkingDirectory ([IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..')))
$trigger = New-ScheduledTaskTrigger -Daily -At ([DateTime]::ParseExact($Hora, 'HH:mm', [Globalization.CultureInfo]::InvariantCulture))
$principal = New-ScheduledTaskPrincipal -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 30) -RestartCount 2 -RestartInterval (New-TimeSpan -Minutes 10)
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Respaldo diario de tienda-prod, cifrado DPAPI, retencion de 7 copias completas. Requiere sesion del usuario, Docker y Minikube disponibles.' | Out-Null
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\respaldos'))
New-Item -ItemType Directory -Force $evidencePath | Out-Null
Export-ScheduledTask -TaskName $taskName | Set-Content -LiteralPath (Join-Path $evidencePath 'tarea-respaldo.xml') -Encoding Unicode
Write-Host ('Tarea creada: ' + $taskName + ', diariamente a las ' + $Hora + ' (hora local de Windows).')
Write-Host 'Retencion: ultimas 7 copias completas. Ventanas de ejecucion ocultas.'
Write-Host 'Requiere sesion iniciada y Docker/Minikube disponibles. Un equipo apagado no garantiza RPO de 24 horas.'
