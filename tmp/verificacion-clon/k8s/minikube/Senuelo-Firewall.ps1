param(
    [Parameter(Mandatory=$true)][string]$Direccion,
    [ValidateRange(30,600)][int]$Segundos=300
)
# Servicio temporal de prueba, sin acceso a la tienda ni a sus datos.
$ErrorActionPreference='Stop'
$probeAddress=[Net.IPAddress]::Parse($Direccion)
if ($probeAddress.AddressFamily -ne [Net.Sockets.AddressFamily]::InterNetwork -or [Net.IPAddress]::IsLoopback($probeAddress)) { throw 'Se requiere una IPv4 LAN.' }
if (-not (Get-NetIPAddress -AddressFamily IPv4 -IPAddress $Direccion -ErrorAction SilentlyContinue)) { throw 'La IP no pertenece a este equipo.' }
$probeRule=Get-NetFirewallRule -Name TiendaProd-Bloquear-LAN -PolicyStore ActiveStore
$probePorts=@($probeRule|Get-NetFirewallPortFilter|Select-Object -ExpandProperty LocalPort)
if ($probeRule.Enabled -ne 'True' -or $probeRule.Action -ne 'Block' -or $probePorts -notcontains '9444') { throw 'El puerto de prueba debe tener bloqueo activo antes de escuchar.' }
$probeListener=New-Object Net.Sockets.TcpListener($probeAddress,9444)
$probeClock=[Diagnostics.Stopwatch]::StartNew()
try {
    $probeListener.Start()
    Write-Output ('Senuelo sin datos escuchando en '+$Direccion+':9444 durante '+$Segundos+' segundos.')
    while($probeClock.Elapsed.TotalSeconds -lt $Segundos) {
        if ($probeListener.Pending()) {
            $probeClient=$probeListener.AcceptTcpClient()
            try {
                $probeStream=$probeClient.GetStream()
                $probeStream.WriteTimeout=1000
                $probeBody='SERVICIO DE PRUEBA SIN DATOS. Si aparece desde otro equipo, revisar el bloqueo de firewall.'
                $probeReply="HTTP/1.1 200 OK`r`nContent-Type: text/plain; charset=utf-8`r`nContent-Length: $([Text.Encoding]::UTF8.GetByteCount($probeBody))`r`nConnection: close`r`n`r`n"+$probeBody
                $probeBytes=[Text.Encoding]::UTF8.GetBytes($probeReply)
                $probeStream.Write($probeBytes,0,$probeBytes.Length)
            } finally { $probeClient.Dispose() }
        }
        Start-Sleep -Milliseconds 200
    }
} finally { $probeListener.Stop() }
Write-Output 'Senuelo cerrado; reglas y aplicaciones sin cambios.'
