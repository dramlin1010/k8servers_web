#!/bin/bash
DB_USER="${DB_APP_USER:-daniel}"
DB_PASSWORD="${DB_APP_PASSWORD:-Kt3xa6RqSAgdpskCZyuWfX}"
DB_NAME="${DB_APP_NAME:-k8servers}"
DB_HOST="${DB_APP_HOST:-mariadb-host-svc.default.svc.cluster.local}"

LOG_FILE="/var/log/k8s_provisioning_worker.log"
EFS_CLIENT_BASE_PATH_ON_NODE="/mnt/efs-clientes"
KUBECTL_CMD="kubectl"
SFTP_USER_GROUP="sftpusers"
DEFAULT_SHELL="/usr/sbin/nologin"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

mysql_exec() {
    local query="$1"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -NBe "$query" 2>> "$LOG_FILE"
}

update_task_status() {
    local task_id=$1
    local new_status=$2
    local error_msg=$3
    local error_sql_val="NULL"
    if [ -n "$error_msg" ]; then
        error_sql_val="'${error_msg//\'/\'\'}'"
    fi
    mysql_exec "UPDATE Tareas_Aprovisionamiento_K8S SET EstadoTarea='$new_status', UltimoError=$error_sql_val, Intentos=Intentos+1, FechaActualizacion=NOW() WHERE TareaID=$task_id;"
}

update_sitio_k8s_status() {
    local sitio_id=$1
    local new_status=$2
    mysql_exec "UPDATE SitioWeb SET EstadoAprovisionamientoK8S='$new_status', FechaActualizacion=NOW() WHERE SitioID=$sitio_id;"
}

generate_pv_yaml() {
    local sitio_id=$1
    local subdominio_elegido=$2
    local efs_site_path_on_node="${EFS_CLIENT_BASE_PATH_ON_NODE}/${subdominio_elegido}"

    cat <<EOF
apiVersion: v1
kind: PersistentVolume
metadata:
  name: pv-site-${sitio_id}-${subdominio_elegido}
  labels:
    type: efs-site
    siteId: "${sitio_id}"
spec:
  storageClassName: ""
  capacity:
    storage: 2Gi
  accessModes:
    - ReadWriteMany
  persistentVolumeReclaimPolicy: Retain
  hostPath:
    path: "${efs_site_path_on_node}"
    type: DirectoryOrCreate
#  csi:
#    driver: efs.csi.aws.com
#    volumeHandle: ${EFS_FILE_SYSTEM_ID}::/${subdominio_elegido}
EOF
}

generate_pvc_yaml() {
    local sitio_id=$1
    local subdominio_elegido=$2
    cat <<EOF
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: pvc-site-${sitio_id}-${subdominio_elegido}
  namespace: default # O un namespace por cliente si lo gestionas así
  labels:
    siteId: "${sitio_id}"
spec:
  storageClassName: ""
  accessModes:
    - ReadWriteMany
  resources:
    requests:
      storage: 2Gi
  selector:
    matchLabels:
      type: efs-site
      siteId: "${sitio_id}"
EOF
}

generate_deployment_yaml() {
    local sitio_id=$1
    local subdominio_elegido=$2
    local pvc_name="pvc-site-${sitio_id}-${subdominio_elegido}"
    local php_fpm_image="${PHP_FPM_CLIENT_IMAGE:-280972575853.dkr.ecr.us-east-1.amazonaws.com/web/k8servers:v1}"
    local nginx_image="${NGINX_CLIENT_IMAGE:-nginx:alpine}"

    cat <<EOF
apiVersion: apps/v1
kind: Deployment
metadata:
  name: site-${sitio_id}-${subdominio_elegido}-dep
  namespace: default
  labels:
    app: site-${sitio_id}
    component: web
spec:
  replicas: 1
  selector:
    matchLabels:
      app: site-${sitio_id}
      component: web
  template:
    metadata:
      labels:
        app: site-${sitio_id}
        component: web
    spec:
      securityContext:
        fsGroup: 101
      imagePullSecrets:
        - name: aws-ecr-creds 
      volumes:
        - name: site-storage
          persistentVolumeClaim:
            claimName: ${pvc_name}
        - name: nginx-client-conf
          configMap:
            name: nginx-conf-site-${sitio_id}
      containers:
      - name: nginx
        image: ${nginx_image}
        imagePullPolicy: Always
        ports:
        - containerPort: 80
        volumeMounts:
        - name: site-storage
          mountPath: /var/www/html
          subPath: www
        - name: nginx-client-conf
          mountPath: /etc/nginx/conf.d/default.conf
          subPath: default.conf
      - name: php-fpm
        image: ${php_fpm_image}
        imagePullPolicy: Always
        ports:
        - containerPort: 9000
        volumeMounts:
        - name: site-storage
          mountPath: /var/www/html
          subPath: www
EOF
}

generate_nginx_configmap_yaml() {
    local sitio_id=$1
    local php_fpm_service_in_pod="127.0.0.1:9000"

    cat <<EOF
apiVersion: v1
kind: ConfigMap
metadata:
  name: nginx-conf-site-${sitio_id}
  namespace: default
data:
  default.conf: |
    server {
        listen 80;
        server_name _;
        root /var/www/html;
        index index.php index.html index.htm;

        location / {
            try_files \$uri \$uri/ /index.php?\$query_string;
        }

        location ~ \.php\$ {
            try_files \$uri =404;
            fastcgi_split_path_info ^(.+\.php)(/.+)\$;
            fastcgi_pass ${php_fpm_service_in_pod};
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.ht {
            deny all;
        }
        # access_log /var/log/nginx/access.log;
        # error_log /var/log/nginx/error.log;
    }
EOF
}

generate_service_yaml() {
    local sitio_id=$1
    local subdominio_elegido=$2
    cat <<EOF
apiVersion: v1
kind: Service
metadata:
  name: site-${sitio_id}-${subdominio_elegido}-svc
  namespace: default
  labels:
    app: site-${sitio_id}
    component: web
spec:
  selector:
    app: site-${sitio_id}
    component: web
  ports:
  - name: http
    protocol: TCP
    port: 80
    targetPort: 80
  type: ClusterIP
EOF
}

generate_ingress_yaml() {
    local sitio_id=$1
    local dominio_completo_cliente=$2
    local service_name="site-${sitio_id}-$(echo "$dominio_completo_cliente" | cut -d. -f1)-svc"
    local tls_secret_name="tls-site-${sitio_id}-$(echo "$dominio_completo_cliente" | cut -d. -f1)"
    local cluster_issuer="${CERT_MANAGER_ISSUER:-letsencrypt-staging}"

    cat <<EOF
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: site-${sitio_id}-$(echo "$dominio_completo_cliente" | cut -d. -f1)-ing
  namespace: default
  annotations:
    cert-manager.io/cluster-issuer: "${cluster_issuer}"
    nginx.ingress.kubernetes.io/proxy-body-size: "256m"
spec:
  ingressClassName: nginx
  tls:
  - hosts:
    - ${dominio_completo_cliente}
    secretName: ${tls_secret_name}
  rules:
  - host: ${dominio_completo_cliente}
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: ${service_name}
            port:
              number: 80
EOF
}

ensure_sftp_user_group_exists() {
    if ! getent group "$SFTP_USER_GROUP" > /dev/null; then
        log "Grupo SFTP '$SFTP_USER_GROUP' no existe. Creandolo..."
        if groupadd "$SFTP_USER_GROUP"; then
            log "Grupo '$SFTP_USER_GROUP' creado."
        else
            log "ERROR: No se pudo crear el grupo '$SFTP_USER_GROUP'."
            return 1
        fi
    fi
    return 0
}

create_sftp_user() {
    local username="$1"
    local user_home_base_path="$2"
    local user_www_path="${user_home_base_path}/www"

    if id -u "$username" > /dev/null 2>&1; then
        log "Usuario SFTP '$username' ya existe."
        return 0
    fi

    log "Creando usuario SFTP '$username'..."
    if [ ! -d "$user_home_base_path" ]; then
        log "ERROR: Directorio base EFS '$user_home_base_path' no encontrado para el nuevo usuario SFTP '$username'."
        return 1
    fi
     if [ ! -d "$user_www_path" ]; then
        log "ADVERTENCIA: Directorio WWW '$user_www_path' no encontrado. Creándolo."
        mkdir -p "$user_www_path"
        chown -R "${username}:${SFTP_USER_GROUP}" "$user_www_path"
        chmod -R 775 "$user_www_path"
    fi

    if useradd -m -d "$user_home_base_path" -s "$DEFAULT_SHELL" -g "$SFTP_USER_GROUP" "$username"; then
        log "Usuario SFTP '$username' creado."
        chown root:"$SFTP_USER_GROUP" "$user_home_base_path"
        chmod 750 "$user_home_base_path"

        mkdir -p "${user_home_base_path}/.ssh"
        touch "${user_home_base_path}/.ssh/authorized_keys"
        chown -R "${username}:${SFTP_USER_GROUP}" "${user_home_base_path}/.ssh"
        chmod 700 "${user_home_base_path}/.ssh"
        chmod 600 "${user_home_base_path}/.ssh/authorized_keys"
        log "Directorio .ssh y authorized_keys preparados para '$username'. El cliente deberá añadir su clave pública."
        return 0
    else
        log "ERROR: No se pudo crear el usuario SFTP '$username'."
        return 1
    fi
}

log "--- Iniciando Worker de Aprovisionamiento K8s ---"
ensure_sftp_user_group_exists || exit 1

while true; do
    log "Buscando tareas de aprovisionamiento pendientes..."
    PENDING_TASKS=$(mysql_exec \
        "SELECT T.TareaID, T.SitioID, S.SubdominioElegido, S.DominioCompleto, S.DirectorioEFSRuta
         FROM Tareas_Aprovisionamiento_K8S T
         JOIN SitioWeb S ON T.SitioID = S.SitioID
         WHERE T.TipoTarea = 'aprovisionar_pod' 
           AND T.EstadoTarea = 'pendiente' 
           AND S.EstadoAprovisionamientoK8S = 'directorio_creado'
         ORDER BY T.FechaSolicitud ASC LIMIT 3;")

    if [ -z "$PENDING_TASKS" ]; then
        log "No hay tareas de aprovisionamiento listas. Esperando 30 segundos..."
        sleep 30
        continue
    fi

    echo "$PENDING_TASKS" | while IFS=$'\t' read -r tarea_id sitio_id subdominio_elegido dominio_completo dir_efs_ruta; do
        log "Procesando TareaID: $tarea_id para SitioID: $sitio_id ($dominio_completo)"
        
        update_task_status "$tarea_id" "procesando_usuario_sftp"
        sftp_username_candidate="$subdominio_elegido"
        
        if create_sftp_user "$sftp_username_candidate" "$dir_efs_ruta"; then
            log "Usuario SFTP '$sftp_username_candidate' gestionado para SitioID $sitio_id."
        else
            log "ERROR al gestionar usuario SFTP para SitioID $sitio_id. TareaID: $tarea_id"
            update_task_status "$tarea_id" "error_usuario_sftp" "Fallo en la creación/gestión del usuario SFTP"
            update_sitio_k8s_status "$sitio_id" "error_usuario_sftp"
            continue
        fi

        update_task_status "$tarea_id" "procesando_k8s"
        update_sitio_k8s_status "$sitio_id" "k8s_manifiesto_pendiente"

        # Generar y aplicar cada manifiesto
        # Es importante el orden: PV -> PVC -> ConfigMap -> Deployment -> Service -> Ingress
        
        apply_error=0

        log "Generando PV para SitioID $sitio_id..."
        generate_pv_yaml "$sitio_id" "$subdominio_elegido" > "/tmp/pv_site_${sitio_id}.yaml"
        if $KUBECTL_CMD apply -f "/tmp/pv_site_${sitio_id}.yaml"; then log "PV aplicado."; else log "ERROR aplicando PV."; apply_error=1; fi
        rm -f "/tmp/pv_site_${sitio_id}.yaml"
        [ $apply_error -ne 0 ] && { update_task_status "$tarea_id" "error_k8s" "Fallo aplicando PV"; update_sitio_k8s_status "$sitio_id" "error_k8s"; continue; }
        sleep 2

        log "Generando PVC para SitioID $sitio_id..."
        generate_pvc_yaml "$sitio_id" "$subdominio_elegido" > "/tmp/pvc_site_${sitio_id}.yaml"
        if $KUBECTL_CMD apply -f "/tmp/pvc_site_${sitio_id}.yaml"; then log "PVC aplicado."; else log "ERROR aplicando PVC."; apply_error=1; fi
        rm -f "/tmp/pvc_site_${sitio_id}.yaml"
        [ $apply_error -ne 0 ] && { update_task_status "$tarea_id" "error_k8s" "Fallo aplicando PVC"; update_sitio_k8s_status "$sitio_id" "error_k8s"; continue; }
        sleep 5

        log "Generando ConfigMap Nginx para SitioID $sitio_id..."
        generate_nginx_configmap_yaml "$sitio_id" > "/tmp/cm_nginx_site_${sitio_id}.yaml"
        if $KUBECTL_CMD apply -f "/tmp/cm_nginx_site_${sitio_id}.yaml"; then log "ConfigMap Nginx aplicado."; else log "ERROR aplicando ConfigMap Nginx."; apply_error=1; fi
        rm -f "/tmp/cm_nginx_site_${sitio_id}.yaml"
        [ $apply_error -ne 0 ] && { update_task_status "$tarea_id" "error_k8s" "Fallo aplicando ConfigMap Nginx"; update_sitio_k8s_status "$sitio_id" "error_k8s"; continue; }

        log "Generando Deployment para SitioID $sitio_id..."
        generate_deployment_yaml "$sitio_id" "$subdominio_elegido" > "/tmp/dep_site_${sitio_id}.yaml"
        if $KUBECTL_CMD apply -f "/tmp/dep_site_${sitio_id}.yaml"; then log "Deployment aplicado."; else log "ERROR aplicando Deployment."; apply_error=1; fi
        rm -f "/tmp/dep_site_${sitio_id}.yaml"
        [ $apply_error -ne 0 ] && { update_task_status "$tarea_id" "error_k8s" "Fallo aplicando Deployment"; update_sitio_k8s_status "$sitio_id" "error_k8s"; continue; }

        log "Generando Service para SitioID $sitio_id..."
        generate_service_yaml "$sitio_id" "$subdominio_elegido" > "/tmp/svc_site_${sitio_id}.yaml"
        if $KUBECTL_CMD apply -f "/tmp/svc_site_${sitio_id}.yaml"; then log "Service aplicado."; else log "ERROR aplicando Service."; apply_error=1; fi
        rm -f "/tmp/svc_site_${sitio_id}.yaml"
        [ $apply_error -ne 0 ] && { update_task_status "$tarea_id" "error_k8s" "Fallo aplicando Service"; update_sitio_k8s_status "$sitio_id" "error_k8s"; continue; }

        log "Generando Ingress para SitioID $sitio_id..."
        generate_ingress_yaml "$sitio_id" "$dominio_completo" > "/tmp/ing_site_${sitio_id}.yaml"
        if $KUBECTL_CMD apply -f "/tmp/ing_site_${sitio_id}.yaml"; then log "Ingress aplicado."; else log "ERROR aplicando Ingress."; apply_error=1; fi
        rm -f "/tmp/ing_site_${sitio_id}.yaml"
        [ $apply_error -ne 0 ] && { update_task_status "$tarea_id" "error_k8s" "Fallo aplicando Ingress"; update_sitio_k8s_status "$sitio_id" "error_k8s"; continue; }

        log "Aprovisionamiento K8S para SitioID $sitio_id completado."
        update_task_status "$tarea_id" "completado"
        update_sitio_k8s_status "$sitio_id" "k8s_aprovisionado"
        
        sleep 2
    done
    sleep 10
done
