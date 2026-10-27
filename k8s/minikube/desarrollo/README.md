# Desarrollo independiente en Minikube

Perfil/contexto tienda-dev, namespace tienda-dev, un nodo, 2 CPU y 3072 MiB.
No aplicar estos manifiestos con el contexto tienda-prod. Usar siempre
`minikube -p tienda-dev kubectl -- ...`.

Los siete manifiestos JSON (validos tambien como YAML) son archivos completos
adaptados a Minikube. No usar el overlay antiguo de development para este flujo.
Frontend 0.1.1 y backend candidato 0.1.4, inicialmente una replica de cada uno.
La version 0.1.3 conserva la compatibilidad de CI3, actualiza paquetes del sistema
y corrige el filtro de busqueda del catalogo. Se valida primero aqui; produccion
ya valido 0.1.3; 0.1.4 corrige la escritura del historial con el driver MongoDB 2.x.
La configuracion y los datos
son independientes.

Orden de instalacion:

1. Iniciar el perfil existente, comprobar Calico, habilitar metrics-server y
   csi-hostpath-driver. Comprobar nodos Ready, componentes y StorageClass.
2. Cargar las dos imagenes en tienda-dev.
3. Aplicar plataforma.yaml (namespace Restricted, ServiceAccount, configuracion,
   cuotas, volumen uploads y NetworkPolicy). Ejecutar Preparar-Credenciales.ps1
   con Windows PowerShell y crear tienda-secrets desde k8s/tls/dev-private/secrets.env.
4. Aplicar bases-datos.yaml y esperar ambos StatefulSets. Aplicar
   inicializar-tienda.yaml una sola vez y comprobar finalizacion. Verificar con
   verificar-bases.yaml. Los scripts de inicializacion crean usuarios de servicio;
   la migracion y la semilla no se ejecutan desde cada replica.
5. Aplicar aplicacion.yaml y tareas-programadas.yaml.
6. Instalar Traefik chart 42.1.3 en kube-system, con traefik-values.yaml de esta
   carpeta. Crear certificado y Secret tienda-tls propios para tienda-dev.test,
   aplicar entrada-https.yaml y agregar 127.0.0.1 tienda-dev.test al hosts de Windows.
7. Publicar por port-forward local 9444:443. URL https://tienda-dev.test:9444/.
   Comprobar HTTPS, salud, acceso a ambos motores y separacion de datos.

Los PVC son exclusivos de este cluster: 2 GiB por motor y 1 GiB para uploads,
StorageClass csi-hostpath-sc. Uploads usa ReadWriteOnce: frontend y backend pueden
compartirlo porque este ambiente tiene un solo nodo. No demuestra RWX multinodo.
El almacenamiento y las credenciales de produccion no se montan ni se copian.

La entrada web usa TLS y cookies seguras. Esta configuracion inicial de desarrollo
no exige TLS hacia los motores; produccion lo exige. No se instala Prometheus/Grafana
adicional en desarrollo: se usa Metrics Server para diagnostico basico.

Las contrasenas de prueba estan en el archivo privado dev-private/secrets.env.
No incluirlo en capturas. El script genera valores distintos y conserva el archivo
en ejecuciones posteriores; no es un procedimiento de rotacion de credenciales.
