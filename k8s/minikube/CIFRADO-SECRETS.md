# Cifrado de Secrets en tienda-prod

Ejecutar Habilitar-CifradoSecrets.ps1 con Windows PowerShell. Genera una clave
aleatoria de 32 bytes, conserva su configuracion bajo DPAPI CurrentUser en
k8s/tls/secrets-etcd (ignorado por Git y con ACL privada) y copia la configuracion
al control plane con propietario root y modo 0600. No regenera una clave existente.

El proveedor secretbox cifra nuevas escrituras; identity se mantiene al final para
leer recursos anteriores durante la migracion. El script reescribe todos los Secrets
actuales y verifica su prefijo directamente en etcd. No cambia las credenciales.
Esto no elimina versiones historicas en etcd ni copias antiguas sin cifrar.

La configuracion esta en /var/lib/minikube/certs/encryption-config.json, directorio
ya montado en el static Pod de kube-apiserver. No borrar este archivo despues de
cifrar los datos. El manifiesto anterior se guarda fuera del directorio de static Pods.

Para arranques posteriores conservar explicitamente el parametro de Minikube:

```powershell
minikube start -p tienda-prod --extra-config=apiserver.encryption-provider-config=/var/lib/minikube/certs/encryption-config.json
```

Comprobar con Comprobar-CifradoSecrets.ps1 tras cada arranque. Si Minikube regenera
el manifiesto sin el parametro, Habilitar-CifradoSecrets.ps1 puede reaplicarlo usando
la misma clave protegida. No eliminar el cifrado ni generar otra clave para resolver
errores de lectura. Si se recrea el nodo, restaurar la configuracion con su clave
antes de intentar abrir un etcd que ya contenia Secrets cifrados.

La copia DPAPI requiere conservar el perfil y las claves de Windows del usuario.
No equivale a una copia externa de recuperacion ante perdida de la laptop.
La clave local protege una copia de etcd; no protege frente a compromiso de root
del control plane. No se instala KMS ni se implementa rotacion automatica de esta
clave. Las copias y snapshots de etcd anteriores requieren tratamiento separado.
