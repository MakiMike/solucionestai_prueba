$ErrorActionPreference = 'Stop'
$block = Get-NetFirewallRule -Name 'TiendaProd-Bloquear-LAN'
if ($block.Group -ne 'TiendaLab') { throw 'Grupo de regla inesperado.' }
$ports = @($block | Get-NetFirewallPortFilter | Select-Object -ExpandProperty LocalPort)
& 'C:\laragon\www\sistema\k8s\minikube\Configurar-Firewall.ps1' -Puertos ((@($ports) + @('9443') | Sort-Object -Unique) -join ',')
$allow = Get-NetFirewallRule -Name 'TiendaProd-Https-LAN' -ErrorAction SilentlyContinue
if ($allow) {
    if ($allow.Group -ne 'TiendaLab') { throw 'Grupo de regla inesperado.' }
    $allow | Remove-NetFirewallRule
}
'Acceso LAN cerrado; produccion y desarrollo conservan el acceso local.' | Set-Content -LiteralPath 'C:\laragon\www\sistema\output\evidencias\acceso-lan\acceso-retirado.txt'
