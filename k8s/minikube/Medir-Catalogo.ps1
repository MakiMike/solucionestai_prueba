param(
    [string]$BaseUrl = 'https://tienda-prod.test:9443',
    [ValidateRange(1,1800)][int]$SegundosPorFase = 300,
    [ValidateRange(1,32)][int]$Usuarios = 12,
    [PSCredential]$Credencial
)
$ErrorActionPreference = 'Stop'
$BaseUrl = $BaseUrl.TrimEnd('/')
if (-not $Credencial) {
    $Credencial = Get-Credential -UserName 'cliente@tienda.test' -Message 'Cuenta de prueba de la tienda (no se guarda la contrasena)'
}
if (-not $Credencial) { throw 'Se requiere una cuenta de prueba.' }
$worker = {
    param($url, $credential, $seconds, $pauseMs, $number)
    $ErrorActionPreference = 'Stop'
    Add-Type -AssemblyName System.Net.Http
    $handler = New-Object Net.Http.HttpClientHandler
    $handler.UseProxy = $false
    $handler.UseCookies = $true
    $handler.AllowAutoRedirect = $false
    $client = New-Object Net.Http.HttpClient($handler)
    $client.Timeout = [TimeSpan]::FromSeconds(10)
    try {
        $login = $client.GetAsync("$url/login").GetAwaiter().GetResult()
        try {
            $html = $login.Content.ReadAsStringAsync().GetAwaiter().GetResult()
            if ([int]$login.StatusCode -ne 200) { throw 'Login no disponible.' }
        } finally { $login.Dispose() }
        $token = [regex]::Match($html, 'const name="[^"]+",token="([a-f0-9]+)"')
        if (-not $token.Success) { throw 'No se encontro el token CSRF del login.' }
        $fields = New-Object 'Collections.Generic.Dictionary[string,string]'
        $fields.Add('email', $credential.UserName)
        $fields.Add('password', $credential.GetNetworkCredential().Password)
        $fields.Add('csrf_test_name', $token.Groups[1].Value)
        $form = New-Object Net.Http.FormUrlEncodedContent($fields)
        try {
            $response = $client.PostAsync("$url/login/submit", $form).GetAwaiter().GetResult()
            try {
                if ([int]$response.StatusCode -notin @(302,303)) { throw 'El login no fue aceptado.' }
            } finally { $response.Dispose() }
        } finally { $form.Dispose(); $fields.Clear() }
        # Cada usuario tiene cookies propias: evita serializar toda la prueba en una sesion.
        $catalog = "$url/ropa/obtener_catalogo?genero=TODOS&page=1"
        $probe = $client.GetAsync($catalog).GetAwaiter().GetResult()
        try {
            $json = $probe.Content.ReadAsStringAsync().GetAwaiter().GetResult() | ConvertFrom-Json
            if ([int]$probe.StatusCode -ne 200 -or $json.success -ne $true -or $json.total_records -lt 1) {
                throw 'No se pudo consultar el catalogo autenticado con productos.'
            }
        } finally { $probe.Dispose() }
        $rows = New-Object 'Collections.Generic.List[object]'
        $start = [DateTime]::UtcNow
        $clock = [Diagnostics.Stopwatch]::StartNew()
        while ($clock.Elapsed.TotalSeconds -lt $seconds) {
            $status = 0
            $ok = $false
            $response = $null
            $timer = [Diagnostics.Stopwatch]::StartNew()
            try {
                $response = $client.GetAsync($catalog).GetAwaiter().GetResult()
                $body = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
                $timer.Stop()
                $status = [int]$response.StatusCode
                $data = $body | ConvertFrom-Json
                $ok = $status -eq 200 -and $data.success -eq $true -and $data.total_records -gt 0
            } catch {
                $timer.Stop()
            } finally { if ($response) { $response.Dispose() } }
            $rows.Add([pscustomobject]@{
                UTC = [DateTime]::UtcNow.ToString('o')
                Usuario = $number
                HTTP = $status
                Correcta = $ok
                Milisegundos = [Math]::Round($timer.Elapsed.TotalMilliseconds,3)
            })
            if ($pauseMs -gt 0) { Start-Sleep -Milliseconds $pauseMs }
        }
        [pscustomobject]@{ Inicio=$start; Fin=[DateTime]::UtcNow; Muestras=$rows.ToArray() }
    } finally { $client.Dispose(); $handler.Dispose() }
}
$destination = Join-Path $PSScriptRoot ('..\..\output\evidencias\rendimiento\' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $destination -Force | Out-Null
$summaries = @()
foreach ($phase in @(
    @{Nombre='Base'; Usuarios=1; Pausa=1000},
    @{Nombre='Carga'; Usuarios=$Usuarios; Pausa=20}
)) {
    Write-Host ("Fase {0}: {1} usuario(s), {2} segundos. Consultando catalogo..." -f $phase.Nombre,$phase.Usuarios,$SegundosPorFase)
    $jobs = @()
    try {
        for ($i=1; $i -le $phase.Usuarios; $i++) {
            $jobs += Start-Job -ScriptBlock $worker -ArgumentList $BaseUrl,$Credencial,$SegundosPorFase,$phase.Pausa,$i
        }
        $null = $jobs | Wait-Job
        $results = @($jobs | Receive-Job -ErrorAction Stop)
        if (@($jobs | Where-Object State -ne 'Completed').Count -gt 0 -or $results.Count -ne $phase.Usuarios) {
            throw 'Un usuario no termino la prueba. No se genera un resultado parcial como valido.'
        }
        $rows = @($results | ForEach-Object { $_.Muestras })
        if ($rows.Count -eq 0) { throw 'No hubo muestras.' }
        $rows | Export-Csv (Join-Path $destination ($phase.Nombre + '.csv')) -NoTypeInformation -Encoding UTF8
        $begin = ($results | ForEach-Object { [DateTime]$_.Inicio } | Sort-Object | Select-Object -First 1)
        $end = ($results | ForEach-Object { [DateTime]$_.Fin } | Sort-Object | Select-Object -Last 1)
        $elapsed = ($end - $begin).TotalSeconds
        $successful = @($rows | Where-Object Correcta -eq $true)
        $sorted = @($successful | ForEach-Object { [double]$_.Milisegundos } | Sort-Object)
        $p95 = if ($sorted.Count) { $sorted[[int][Math]::Ceiling(0.95 * $sorted.Count) - 1] } else { $null }
        $summaries += [pscustomobject]@{
            Fase=$phase.Nombre; Usuarios=$phase.Usuarios; Solicitudes=$rows.Count
            Fallos=($rows.Count-$successful.Count)
            ErrorPct=[Math]::Round(100*($rows.Count-$successful.Count)/$rows.Count,2)
            ReqSegundo=[Math]::Round($rows.Count/$elapsed,2)
            P95ms=$p95; DuracionSeg=[Math]::Round($elapsed,1)
        }
    } finally {
        if ($jobs.Count) { $jobs | Stop-Job; $jobs | Remove-Job -Force }
    }
}
$summaries | Export-Csv (Join-Path $destination 'resumen.csv') -NoTypeInformation -Encoding UTF8
$summaries | Format-Table -AutoSize | Out-Host
Write-Host ('Evidencia: ' + [IO.Path]::GetFullPath($destination))
Write-Host 'p95 de solicitudes correctas; errores contabilizados por separado. HTTPS incluye el port-forward local.'
Write-Host 'Se midio lectura del catalogo, no compras ni pagos. Comparar HPA en otra terminal.'
