# Solo descarga y prepara los archivos. No crea clústeres ni aplica recursos.
$ErrorActionPreference = 'Stop'
$calicoVersion = 'v3.33.0'
$calicoUrl = "https://raw.githubusercontent.com/projectcalico/calico/$calicoVersion/manifests/calico.yaml"
$calicoSource = Join-Path $PSScriptRoot 'calico-original.yaml'
Invoke-WebRequest -Uri $calicoUrl -OutFile $calicoSource
$calicoText = Get-Content -LiteralPath $calicoSource -Raw
$calicoMarker = '# - name: CALICO_IPV4POOL_CIDR'
if (-not $calicoText.Contains($calicoMarker)) {
    throw 'El manifiesto cambió. Revisar el CIDR antes de instalar; no se generaron variantes.'
}
foreach ($calicoTarget in @(
    @{ Name = 'development'; Cidr = '10.244.0.0/16' },
    @{ Name = 'production'; Cidr = '10.245.0.0/16' }
)) {
    $calicoOutput = $calicoText.Replace($calicoMarker, '- name: CALICO_IPV4POOL_CIDR').Replace('#   value: "192.168.0.0/16"', '  value: "' + $calicoTarget.Cidr + '"')
    if (-not $calicoOutput.Contains('value: "' + $calicoTarget.Cidr + '"')) {
        throw 'No se pudo configurar el rango de Pods. No aplicar los archivos.'
    }
    $calicoPath = Join-Path $PSScriptRoot ('calico-' + $calicoTarget.Name + '.yaml')
    [System.IO.File]::WriteAllText($calicoPath, $calicoOutput, [System.Text.UTF8Encoding]::new($false))
    Get-FileHash -LiteralPath $calicoPath -Algorithm SHA256
}
