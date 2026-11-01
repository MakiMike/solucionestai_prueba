param(
    [Parameter(Mandatory=$true)]
    [string]$Puertos
)
$ErrorActionPreference = 'Stop'
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Abre PowerShell con Ejecutar como administrador y repite este comando. No se ha modificado el firewall.'
}
$ports = @($Puertos.Split(',') | ForEach-Object {
    $port = 0
    if (-not [int]::TryParse($_.Trim(), [ref]$port) -or $port -lt 1 -or $port -gt 65535) { throw 'Lista de puertos TCP invalida.' }
    $port
} | Sort-Object -Unique)
$interfaces = @(Get-NetIPConfiguration | Where-Object {
    $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' -and -not $_.NetAdapter.Virtual
} | Select-Object -ExpandProperty InterfaceAlias)
if ($interfaces.Count -eq 0) { throw 'No se encontro una interfaz fisica activa con gateway. No se modifica el firewall.' }
$ruleName = 'TiendaProd-Bloquear-LAN'
$parameters = @{
    Name=$ruleName
    DisplayName='Tienda laboratorio - bloquear acceso desde LAN'
    Group='TiendaLab'
    Direction='Inbound'
    Action='Block'
    Protocol='TCP'
    LocalPort=$ports
    InterfaceAlias=$interfaces
    RemoteAddress='Any'
    Profile='Any'
    Enabled='True'
}
$existing = Get-NetFirewallRule -Name $ruleName -ErrorAction SilentlyContinue
if ($existing) {
    if ($existing.Group -ne 'TiendaLab') { throw 'Existe una regla con ese nombre de otro grupo. No se modifica.' }
    $update = $parameters.Clone()
    $update.Remove('DisplayName')
    $update.Remove('Group')
    $update.NewDisplayName = $parameters.DisplayName
    Set-NetFirewallRule @update | Out-Null
} else {
    New-NetFirewallRule @parameters | Out-Null
}
$evidencePath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\seguridad'))
New-Item -ItemType Directory -Force -Path $evidencePath | Out-Null
$parameters | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $evidencePath 'firewall-tienda.json') -Encoding UTF8
$profiles = @(Get-NetFirewallProfile -PolicyStore ActiveStore | Select-Object Name,Enabled,DefaultInboundAction,DefaultOutboundAction)
$profiles | Export-Csv -LiteralPath (Join-Path $evidencePath 'firewall-perfiles.csv') -NoTypeInformation -Encoding UTF8
$applied = Get-NetFirewallRule -Name $ruleName -PolicyStore ActiveStore
if ($applied.Enabled -ne 'True' -or $applied.Action -ne 'Block') { throw 'La regla no aparece activa con accion Block.' }
$profiles | Format-Table -AutoSize | Out-Host
$applied | Format-Table DisplayName,Enabled,Direction,Action -AutoSize | Out-Host
Write-Host ('Interfaces fisicas: ' + ($interfaces -join ', '))
Write-Host ('Puertos TCP protegidos: ' + ($ports -join ', '))
Write-Host ('Configuracion exportada: ' + $evidencePath)
Write-Host 'REGLA DE FIREWALL APLICADA. Falta prueba de bloqueo desde otro equipo.'
