param(
    [ValidateSet('Aplicar','Revertir')][string]$Accion = 'Aplicar',
    [string]$PuertosProtegidos = '3000,9081,9090,9444'
)
# Solo Windows/NetSecurity. No ejecuta Docker, Minikube ni cambios de certificados.
$ErrorActionPreference = 'Stop'
$lanIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
$lanPrincipal = New-Object Security.Principal.WindowsPrincipal($lanIdentity)
if (-not $lanPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Windows requiere PowerShell como administrador para cambiar estas reglas. No se ha modificado el firewall.'
}
$lanEvidence = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\output\evidencias\seguridad'))
New-Item -ItemType Directory -Force -Path $lanEvidence | Out-Null
$lanBackup = Join-Path $lanEvidence 'firewall-antes-prod-lan.json'
$lanBlockName = 'TiendaProd-Bloquear-LAN'
$lanAllowName = 'TiendaProd-Https-LAN'
foreach ($lanName in @($lanBlockName,$lanAllowName)) {
    $lanExisting = Get-NetFirewallRule -Name $lanName -ErrorAction SilentlyContinue
    if ($lanExisting -and $lanExisting.Group -ne 'TiendaLab') { throw ('Regla ajena con nombre reservado: ' + $lanName) }
}
if ($Accion -eq 'Revertir') {
    if (-not (Test-Path -LiteralPath $lanBackup)) { throw 'No existe copia anterior; no se modifica el firewall.' }
    $lanBefore = Get-Content -Raw -LiteralPath $lanBackup | ConvertFrom-Json
    if ($lanBefore.Nombre -ne $lanBlockName -or -not (@($lanBefore.Puertos) -contains '9443')) { throw 'Copia anterior inesperada.' }
    & (Join-Path $PSScriptRoot 'Configurar-Firewall.ps1') -Puertos (@($lanBefore.Puertos) -join ',')
    $lanAllow = Get-NetFirewallRule -Name $lanAllowName -ErrorAction SilentlyContinue
    if ($lanAllow) { $lanAllow | Remove-NetFirewallRule }
    Write-Host 'Acceso LAN retirado; puerto 9443 nuevamente bloqueado en interfaces fisicas.'
    exit 0
}
$lanActive = @(Get-NetIPConfiguration | Where-Object {
    $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' -and -not $_.NetAdapter.Virtual
})
if ($lanActive.Count -eq 0) { throw 'No hay una interfaz fisica activa con gateway.' }
$lanInterfaces = @($lanActive | Select-Object -ExpandProperty InterfaceAlias -Unique)
$lanAddresses = @($lanActive | ForEach-Object { $_.IPv4Address.IPAddress } | Where-Object { $_ -and $_ -notlike '169.254.*' } | Sort-Object -Unique)
if ($lanAddresses.Count -eq 0) { throw 'No se encontro una IPv4 LAN utilizable.' }
$lanExistingBlock = Get-NetFirewallRule -Name $lanBlockName -ErrorAction SilentlyContinue
if (-not $lanExistingBlock) { throw 'Falta la regla de bloqueo existente. Revisar antes de habilitar acceso LAN.' }
$lanOldPorts = @($lanExistingBlock | Get-NetFirewallPortFilter | Select-Object -ExpandProperty LocalPort)
if (-not (Test-Path -LiteralPath $lanBackup)) {
    if (-not ($lanOldPorts -contains '9443')) { throw 'El bloqueo original no contiene 9443; revisar antes de modificarlo.' }
    @{Nombre=$lanBlockName;Puertos=$lanOldPorts;FechaUTC=[DateTime]::UtcNow.ToString('o')} |
        ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $lanBackup -Encoding UTF8
}
$lanProtected = @(@($PuertosProtegidos.Split(',')) + @($lanOldPorts) + @('3000','9081','9090','9444') | ForEach-Object {
    $lanPort=0
    if (-not [int]::TryParse($_.Trim(),[ref]$lanPort) -or $lanPort -lt 1 -or $lanPort -gt 65535) { throw 'Puerto protegido invalido.' }
    if ($lanPort -ne 9443) { $lanPort }
} | Sort-Object -Unique)
$lanParameters = @{
    Name=$lanAllowName; DisplayName='TSkull-Shirt - HTTPS de produccion desde LAN'; Group='TiendaLab'
    Direction='Inbound'; Action='Allow'; Protocol='TCP'; LocalPort=9443
    LocalAddress=$lanAddresses; RemoteAddress='LocalSubnet'; InterfaceAlias=$lanInterfaces
    Profile='Any'; Enabled='True'
}
try {
    $lanAllow = Get-NetFirewallRule -Name $lanAllowName -ErrorAction SilentlyContinue
    if ($lanAllow) {
        $lanUpdate = $lanParameters.Clone()
        $lanUpdate.Remove('Group'); $lanUpdate.Remove('DisplayName')
        $lanUpdate.NewDisplayName = $lanParameters.DisplayName
        Set-NetFirewallRule @lanUpdate | Out-Null
    } else { New-NetFirewallRule @lanParameters | Out-Null }
    # La regla Block prevalece sobre Allow: 9443 se retira solo al final.
    & (Join-Path $PSScriptRoot 'Configurar-Firewall.ps1') -Puertos ($lanProtected -join ',')
    $lanApplied = Get-NetFirewallRule -Name $lanAllowName -PolicyStore ActiveStore
    $lanActualPorts = @(Get-NetFirewallRule -Name $lanBlockName -PolicyStore ActiveStore | Get-NetFirewallPortFilter | Select-Object -ExpandProperty LocalPort)
    if ($lanApplied.Enabled -ne 'True' -or $lanApplied.Action -ne 'Allow' -or $lanActualPorts -contains '9443') { throw 'La comprobacion de reglas no paso.' }
    $lanParameters | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $lanEvidence 'firewall-prod-lan.json') -Encoding UTF8
    Write-Host ('HTTPS produccion permitido desde LocalSubnet: ' + ($lanAddresses -join ', ') + ':9443')
    Write-Host 'Desarrollo 9444, Grafana 3000, Prometheus 9090 y restauracion 9081 mantienen bloqueo LAN.'
    Write-Host 'No se han cambiado perfiles, politicas generales ni otros servicios.'
    Write-Host 'Falta comprobar desde el telefono: permitir produccion y rechazar desarrollo.'
} catch {
    # Recuperar el bloqueo de 9443 si no se pudo completar el cambio.
    & (Join-Path $PSScriptRoot 'Configurar-Firewall.ps1') -Puertos ((@($lanProtected) + @(9443) | Sort-Object -Unique) -join ',')
    throw
}
