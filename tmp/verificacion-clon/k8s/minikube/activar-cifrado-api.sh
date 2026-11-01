#!/bin/sh
set -eu
manifest=/etc/kubernetes/manifests/kube-apiserver.yaml
config=/var/lib/minikube/certs/encryption-config.json
flag=--encryption-provider-config=$config
test -s "$config"
grep -q 'mountPath: /var/lib/minikube/certs' "$manifest"
if grep -q -- --encryption-provider-config "$manifest"; then
    grep -Fq -- "$flag" "$manifest" || { echo 'Existe otra configuracion de cifrado; no se modifica.' >&2; exit 1; }
    echo 'La API ya referencia la configuracion de cifrado.'
    exit 0
fi
# La copia queda fuera de la carpeta vigilada de static Pods.
backup=/var/lib/minikube/kube-apiserver.antes-cifrado.yaml
if [ ! -f "$backup" ]; then cp "$manifest" "$backup"; chmod 600 "$backup"; fi
temporary=$(mktemp /etc/kubernetes/apiserver-cifrado.XXXXXX)
trap 'rm -f "$temporary"' EXIT
sed '/^    - kube-apiserver$/a\    - --encryption-provider-config=/var/lib/minikube/certs/encryption-config.json' "$manifest" > "$temporary"
grep -Fq -- "$flag" "$temporary"
chmod 600 "$temporary"
mv "$temporary" "$manifest"
echo 'Configuracion de la API actualizada; kubelet reiniciara el static Pod.'
