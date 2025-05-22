<?php
require_once __DIR__ . '/vendor/autoload.php';

use Aws\Route53\Route53Client;
use Aws\Exception\AwsException;

function execute_remote_commands($host, $port, $user, $key_path, $key_pass, $commands) {
    if (!function_exists('ssh2_connect')) {
        error_log("execute_remote_commands: Extensión ssh2 no disponible.");
        return false;
    }
    $connection = @ssh2_connect($host, $port);
    if (!$connection) {
        error_log("execute_remote_commands: Fallo conexión SSH a $host:$port");
        return false;
    }
    if (!@ssh2_auth_pubkey_file($connection, $user, $key_path . '.pub', $key_path, $key_pass)) {
         error_log("execute_remote_commands: Fallo autenticación SSH para $user@$host usando $key_path");
        return false;
    }

    $all_success = true;
    foreach ($commands as $command) {
        echo "  Ejecutando en $host: $command\n";
        $stream = ssh2_exec($connection, $command);
        if (!$stream) {
            error_log("execute_remote_commands: ssh2_exec falló para comando: $command");
            $all_success = false;
            break;
        }
        stream_set_blocking($stream, true);
        $stderr_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        $stdout = stream_get_contents($stream);
        $stderr = stream_get_contents($stderr_stream);
        fclose($stream);
        fclose($stderr_stream);

        error_log("Comando: $command\nSTDOUT: $stdout\nSTDERR: $stderr");
        echo "    STDOUT: $stdout\n";
        echo "    STDERR: $stderr\n";


        if (!empty($stderr)) {
            $stderr_lower = strtolower($stderr);
            if (strpos($stderr_lower, 'already exists') === false &&
                strpos($stderr_lower, 'ignoring') === false &&
                strpos($stderr_lower, 'warning') === false &&
                strpos($stderr_lower, 'skipping') === false )
            {
                error_log("execute_remote_commands: Error detectado en comando '$command': $stderr");
                $all_success = false;
            } else {
                 echo "    Advertencia Remota (ignorada): $stderr\n";
            }
        }
    }
    return $all_success;
}

function manage_dns_record_route53($subdomain, $target, $config) {
    $region = $config['aws_region'] ?? 'us-east-1';
    $hostedZoneId = $config['aws_route53_hosted_zone_id'] ?? null;
    $accessKeyId = $config['aws_access_key_id'] ?? null;
    $secretKey = $config['aws_secret_access_key'] ?? null;

    if (!$hostedZoneId) {
        error_log("manage_dns_record_route53: Falta aws_route53_hosted_zone_id en la configuración.");
        return false;
    }

    $sdkConfig = ['version' => 'latest', 'region' => $region];
    if (!empty($accessKeyId) && !empty($secretKey)) {
        $sdkConfig['credentials'] = ['key' => $accessKeyId, 'secret' => $secretKey];
        echo "Usando credenciales AWS explícitas.\n";
    } else {
        echo "Intentando usar credenciales AWS del entorno (Rol IAM o variables).\n";
    }

    try {
        $route53Client = new Route53Client($sdkConfig);
        $recordType = (strpos($target, '.') !== false && !filter_var($target, FILTER_VALIDATE_IP)) ? 'CNAME' : 'A';

        $changeBatch = [
            'Changes' => [[
                'Action' => 'UPSERT',
                'ResourceRecordSet' => [
                    'Name' => $subdomain . '.',
                    'Type' => $recordType,
                    'TTL' => 300,
                    'ResourceRecords' => [['Value' => $target]],
                ],
            ]],
            'Comment' => 'Managed by hosting provision script - TaskID: ' . ($GLOBALS['TareaID'] ?? 'N/A'),
        ];

        echo "Enviando cambio a Route 53 para $subdomain...\n";
        $result = $route53Client->changeResourceRecordSets([
            'HostedZoneId' => $hostedZoneId,
            'ChangeBatch' => $changeBatch,
        ]);

        $changeId = $result['ChangeInfo']['Id'];
        echo "Cambio enviado. ID: $changeId. Esperando sincronización...\n";

        $waiter = $route53Client->waitUntil('ResourceRecordSetsChanged', [
            'Id' => $changeId,
            '@waiter' => [
                'delay'       => $config['route53_waiter_delay'] ?? 15,
                'maxAttempts' => $config['route53_waiter_attempts'] ?? 20,
            ]
        ]);
        echo "Cambio $changeId completado (INSYNC).\n";
        return true;

    } catch (AwsException $e) {
        error_log("Error de AWS Route 53 gestionando $subdomain: " . $e->getMessage());
        echo "Error de AWS Route 53: " . $e->getMessage() . "\n";
        return false;
    } catch (Exception $e) {
        error_log("Error inesperado gestionando DNS con Route 53 para $subdomain: " . $e->getMessage());
         echo "Error inesperado gestionando DNS: " . $e->getMessage() . "\n";
        return false;
    }
}

function generate_kubernetes_manifests_cliente($base_name, $pvc_name, $deployment_name, $service_name, $ingress_name, $subdomain, $tls_secret_name, $nginx_image, $php_fpm_image, $disk_size, $cluster_issuer_name, $doc_root_in_pod, $efs_storage_class, $namespace = 'default') {

    $nginx_dep_name = $deployment_name . "-nginx";
    $php_fpm_dep_name = $deployment_name . "-php";
    $nginx_svc_name = $service_name . "-nginx";
    $php_fpm_svc_name = $service_name . "-php";

    $namespace_escaped = $namespace;

    $yaml = <<<YAML
# --- Recursos para Cliente: $base_name ---
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: $pvc_name
  namespace: $namespace_escaped
  labels:
    app: $base_name # Etiqueta para agrupar recursos del cliente
spec:
  accessModes:
    - ReadWriteMany # EFS
  storageClassName: $efs_storage_class # Usar la SC de EFS
  resources:
    requests:
      storage: $disk_size # Tamaño solicitado (puede ser simbólico para EFS)
---
apiVersion: v1
kind: Service
metadata:
  name: $php_fpm_svc_name
  namespace: $namespace_escaped
  labels:
    app: $base_name
    tier: backend
spec:
  selector:
    app: $base_name
    tier: backend # Selecciona los pods PHP-FPM
  ports:
    - protocol: TCP
      port: 9000
      targetPort: 9000
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: $php_fpm_dep_name
  namespace: $namespace_escaped
  labels:
    app: $base_name
    tier: backend
spec:
  replicas: 1
  selector:
    matchLabels:
      app: $base_name
      tier: backend
  template:
    metadata:
      labels:
        app: $base_name
        tier: backend
    spec:
      containers:
      - name: php-fpm-container
        image: $php_fpm_image
        ports:
        - containerPort: 9000
        volumeMounts:
        - name: client-code-storage
          # Montar el código PHP en la ruta que espera Nginx/PHP
          mountPath: /var/www/html # Asumiendo que esta es la raíz común
        # Definir límites de recursos es CRUCIAL
        resources:
          requests: { memory: "64Mi", cpu: "100m" }
          limits: { memory: "128Mi", cpu: "250m" }
      volumes:
      - name: client-code-storage
        persistentVolumeClaim:
          claimName: $pvc_name
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: $nginx_dep_name
  namespace: $namespace_escaped
  labels:
    app: $base_name
    tier: frontend
spec:
  replicas: 1
  selector:
    matchLabels:
      app: $base_name
      tier: frontend
  template:
    metadata:
      labels:
        app: $base_name
        tier: frontend
    spec:
      containers:
      - name: nginx-container
        image: $nginx_image
        ports:
        - containerPort: 80
        volumeMounts:
        - name: client-code-storage
          mountPath: /var/www/html # Montar el mismo volumen que PHP
          readOnly: true # Nginx solo lee
        # Definir límites de recursos es CRUCIAL
        resources:
          requests: { memory: "32Mi", cpu: "50m" }
          limits: { memory: "64Mi", cpu: "100m" }
      volumes:
      - name: client-code-storage
        persistentVolumeClaim:
          claimName: $pvc_name
---
apiVersion: v1
kind: Service
metadata:
  name: $nginx_svc_name
  namespace: $namespace_escaped
  labels:
    app: $base_name
    tier: frontend
spec:
  selector:
    app: $base_name
    tier: frontend # Selecciona los pods Nginx
  ports:
    - protocol: TCP
      port: 80
      targetPort: 80
---
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: $ingress_name
  namespace: $namespace_escaped
  labels:
    app: $base_name
  annotations:
    cert-manager.io/cluster-issuer: $cluster_issuer_name
    # kubernetes.io/ingress.class: "nginx" # Ajusta si es necesario
spec:
  tls:
  - hosts:
    - $subdomain
    secretName: $tls_secret_name
  rules:
  - host: $subdomain
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: $nginx_svc_name # Apunta al servicio Nginx del cliente
            port:
              number: 80
YAML;
    return $yaml;
}


function apply_kubernetes_yaml_via_ssh($host, $port, $user, $key_path, $key_pass, $yaml_content, $remote_filename) {
     if (!function_exists('ssh2_connect')) {
        error_log("apply_kubernetes_yaml_via_ssh: Extensión ssh2 no disponible.");
        return false;
    }
    $connection = @ssh2_connect($host, $port);
     if (!$connection) {
        error_log("apply_kubernetes_yaml_via_ssh: Fallo conexión SSH a $host:$port");
        return false;
    }
    if (!@ssh2_auth_pubkey_file($connection, $user, $key_path . '.pub', $key_path, $key_pass)) {
        error_log("apply_kubernetes_yaml_via_ssh: Fallo autenticación SSH para $user@$host usando $key_path");
        return false;
    }

    $remote_path = "/tmp/" . $remote_filename;
    $remote_path_escaped = escapeshellarg($remote_path);

    $temp_uri = 'data://text/plain;base64,' . base64_encode($yaml_content);
    if (!@ssh2_scp_send($connection, $temp_uri, $remote_path, 0644)) {
         error_log("apply_kubernetes_yaml_via_ssh: Fallo SCP para $remote_path. Intentando con 'echo | ssh'.");
         $command_echo = "echo " . escapeshellarg($yaml_content) . " > " . $remote_path_escaped;
         $stream_echo = ssh2_exec($connection, $command_echo);
         if(!$stream_echo) {
             error_log("apply_kubernetes_yaml_via_ssh: Fallo ssh2_exec para echo.");
             return false;
         }
         stream_set_blocking($stream_echo, true);
         fclose($stream_echo);
    }

    $command_apply = "kubectl apply -f $remote_path_escaped";
    $command_delete = "rm -f $remote_path_escaped";
    $final_command = "$command_apply && $command_delete";

    echo "  Ejecutando en $host: $final_command\n";
    $stream = ssh2_exec($connection, $final_command);
    if (!$stream) {
        error_log("apply_kubernetes_yaml_via_ssh: Fallo al ejecutar ssh2_exec para kubectl apply.");
        @ssh2_exec($connection, $command_delete);
        return false;
    }
    stream_set_blocking($stream, true);
    $stderr_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    $stdout = stream_get_contents($stream);
    $stderr = stream_get_contents($stderr_stream);
    fclose($stream);
    fclose($stderr_stream);

    error_log("apply_kubernetes_yaml_via_ssh: Salida kubectl apply para $remote_filename:\nSTDOUT: $stdout\nSTDERR: $stderr");
    echo "    Salida Kubectl STDOUT: $stdout\n";
    echo "    Salida Kubectl STDERR: $stderr\n";

    $is_error = false;
    if (!empty($stderr)) {
        $stderr_lower = strtolower($stderr);
        if (strpos($stderr_lower, 'error') !== false || strpos($stderr_lower, 'unable') !== false || strpos($stderr_lower, 'forbidden') !== false || strpos($stderr_lower, 'failed') !== false) {
             if (strpos($stderr_lower, 'already exists') === false && strpos($stderr_lower, 'no matches for kind') === false) {
                 $is_error = true;
             }
        }
    }

    if ($is_error) {
         error_log("apply_kubernetes_yaml_via_ssh: Error detectado en la salida stderr de kubectl apply.");
         return false;
    }

    if (strpos(strtolower($stdout), 'created') === false && strpos(strtolower($stdout), 'configured') === false && strpos(strtolower($stdout), 'unchanged') === false) {
        @ssh2_exec($connection, $command_delete);
    }

    return true;
}

function update_task_status($db_connection, $task_id, $new_status, $result_message) {
    $sql = "UPDATE Tareas_Aprovisionamiento SET Estado = ?, Resultado = ?, FechaActualizacion = NOW() WHERE TareaID = ?";
    $stmt = $db_connection->prepare($sql);
    if ($stmt) {
        $max_len = 65535;
        $result_message_truncated = mb_substr($result_message, 0, $max_len);

        $stmt->bind_param("ssi", $new_status, $result_message_truncated, $task_id);
        if ($stmt->execute()) {
            echo "Estado de TareaID $task_id actualizado a: $new_status\n";
        } else {
            error_log("update_task_status: Error ejecutando update para TareaID $task_id: " . $stmt->error);
        }
        $stmt->close();
    } else {
         error_log("update_task_status: Error preparando update para TareaID $task_id: " . $db_connection->error);
    }
}

?>
