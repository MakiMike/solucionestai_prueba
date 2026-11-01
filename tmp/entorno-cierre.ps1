$env:PATH = 'C:\Program Files\Kubernetes\Minikube;C:\Program Files\Docker\Docker\resources\bin;C:\Users\josem\AppData\Local\Microsoft\WinGet\Links;' + $env:PATH
$evidence = Join-Path $PWD 'output/evidencias/cierre-final-20261031'
New-Item -ItemType Directory -Force $evidence | Out-Null
