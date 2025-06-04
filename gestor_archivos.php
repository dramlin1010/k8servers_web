<?php
require_once 'config.php';
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado. Por favor, inicia sesión.";
    header("Location: login.php");
    exit();
}
if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy(); setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido. Por favor, inicia sesión de nuevo.";
    header("Location: login.php");
    exit();
}
$clienteID_session = $_SESSION['ClienteID'];


$sitio_id_actual = null;
$sitio_dominio_actual = null;
$directorio_base_cliente_www = null;
$archivos_y_carpetas = [];
$error_gestor = $_SESSION['error_message_gestor'] ?? null;
$success_gestor = $_SESSION['success_message_gestor'] ?? null;
unset($_SESSION['error_message_gestor'], $_SESSION['success_message_gestor']);

if (!isset($_GET['sitio_id']) || !filter_var($_GET['sitio_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_sitios'] = "No se especificó un sitio válido para gestionar archivos.";
    header("Location: mis_sitios.php");
    exit();
}
$sitio_id_actual = (int)$_GET['sitio_id'];

$stmt_sitio_info = $conn->prepare("SELECT DominioCompleto, DirectorioEFSRuta, EstadoServicio, EstadoAprovisionamientoK8S 
                                   FROM SitioWeb 
                                   WHERE SitioID = ? AND ClienteID = ?");
if ($stmt_sitio_info) {
    $stmt_sitio_info->bind_param("ii", $sitio_id_actual, $clienteID_session);
    $stmt_sitio_info->execute();
    $result_sitio_info = $stmt_sitio_info->get_result();
    $sitio_info = $result_sitio_info->fetch_assoc();
    $stmt_sitio_info->close();

    if (!$sitio_info) {
        $_SESSION['error_message_sitios'] = "Sitio no encontrado o no te pertenece.";
        header("Location: mis_sitios.php");
        exit();
    }

    if ($sitio_info['EstadoServicio'] !== 'activo' || $sitio_info['EstadoAprovisionamientoK8S'] !== 'k8s_aprovisionado') {
        $_SESSION['warning_message_sitios'] = "El sitio '" . htmlspecialchars($sitio_info['DominioCompleto']) . "' no está completamente activo o aprovisionado. La gestión de archivos podría no funcionar correctamente.";
    }

    if (empty($sitio_info['DirectorioEFSRuta'])) {
        $error_gestor = "La ruta base para los archivos de este sitio no ha sido configurada. Contacta a soporte.";
    } else {
        $directorio_base_cliente_www = rtrim($sitio_info['DirectorioEFSRuta'], '/') . '/www';
        $sitio_dominio_actual = $sitio_info['DominioCompleto'];

        if (!is_dir($directorio_base_cliente_www)) {
            if (!@mkdir($directorio_base_cliente_www, 0775, true)) {
                 $error_gestor = "El directorio de archivos del sitio (<code>" . htmlspecialchars($directorio_base_cliente_www) . "</code>) no existe y no pudo ser creado. Contacta a soporte.";
            } else {
                @chgrp($directorio_base_cliente_www, 101);
                @chmod($directorio_base_cliente_www, 0775);
            }
        }
        
        if (is_dir($directorio_base_cliente_www) && is_readable($directorio_base_cliente_www)) {
            $items = scandir($directorio_base_cliente_www);
            if ($items !== false) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $ruta_completa_item = $directorio_base_cliente_www . '/' . $item;
                    $archivos_y_carpetas[] = [
                        'nombre' => $item,
                        'tipo' => is_dir($ruta_completa_item) ? 'directorio' : 'archivo',
                        'tamano' => is_file($ruta_completa_item) ? filesize($ruta_completa_item) : 0,
                        'modificado' => date("d/m/Y H:i", filemtime($ruta_completa_item))
                    ];
                }
            } else {
                $error_gestor = "No se pudo leer el contenido del directorio del sitio.";
            }
        } elseif (!$error_gestor) {
             $error_gestor = "El directorio de archivos del sitio (<code>" . htmlspecialchars($directorio_base_cliente_www) . "</code>) no es accesible. Contacta a soporte.";
        }
    }
} else {
    $_SESSION['error_message_sitios'] = "Error al obtener información del sitio.";
    header("Location: mis_sitios.php");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Archivos: <?php echo htmlspecialchars($sitio_dominio_actual ?? 'Sitio'); ?> - Panel k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .file-manager-layout { display: flex; flex-direction: column; gap: 20px; }
        .file-list-panel, .upload-panel { background-color: var(--card-bg); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--box-shadow-current); }
        .file-list-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .file-list-table th, .file-list-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .file-list-table th { font-weight: 600; color: var(--text-color); font-size: 0.9em; text-transform: uppercase; }
        .file-list-table td { font-size: 0.95em; color: var(--text-color-muted); }
        .file-list-table td.actions a { margin-right: 10px; }
        .icon-folder::before { content: "\1F4C1"; margin-right: 8px; } /* 📁 */
        .icon-file::before { content: "\1F4C4"; margin-right: 8px; }   /* 📄 */

        .drop-zone { border: 2px dashed var(--border-color); border-radius: var(--border-radius); padding: 30px; text-align: center; cursor: pointer; transition: background-color 0.2s ease, border-color 0.2s ease; margin-bottom:15px; }
        .drop-zone.dragover { background-color: color-mix(in srgb, var(--primary-color) 10%, transparent); border-color: var(--primary-color); }
        .drop-zone p { margin: 0; color: var(--text-color-muted); }
        #fileList li { padding: 5px 0; font-size:0.9em; border-bottom: 1px dashed var(--border-color); }
        #fileList li:last-child { border-bottom: none; }
        #fileList li.success { color: var(--secondary-color); }
        #fileList li.error { color: var(--accent-color); }
        .file-input-label { display: inline-block; padding: 10px 15px; background-color: var(--primary-color); color: var(--bg-color); border-radius: var(--border-radius); cursor: pointer; transition: background-color 0.2s; }
        .dark-mode .file-input-label { color: #0f172a; }
        .light-mode .file-input-label { color: #fff; }
        .file-input-label:hover { background-color: var(--accent-color); }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>
        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Gestor de Archivos</h1>
                    <p>Administra los archivos de tu sitio: <strong><?php echo htmlspecialchars($sitio_dominio_actual ?? 'N/A'); ?></strong></p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <?php if (isset($_SESSION['warning_message_sitios'])): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($_SESSION['warning_message_sitios']); unset($_SESSION['warning_message_sitios']); ?></div>
                    <?php endif; ?>
                    <?php if ($error_gestor): ?><div class="alert alert-danger"><?php echo $error_gestor; ?></div><?php endif; ?>
                    <?php if ($success_gestor): ?><div class="alert alert-success"><?php echo $success_gestor; ?></div><?php endif; ?>

                    <?php if ($sitio_dominio_actual && !$error_gestor): ?>
                    <div class="file-manager-layout">
                        <div class="upload-panel">
                            <h2 class="section-subtitle">Subir Archivos a <code>/www</code></h2>
                            <form id="uploadForm" action="upload_handler.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="sitio_id_upload" value="<?php echo $sitio_id_actual; ?>">
                                <div id="dropZone" class="drop-zone">
                                    <p>Arrastra archivos aquí o</p>
                                    <label for="fileInput" class="file-input-label" style="margin-top:10px;">Seleccionar Archivos</label>
                                    <input type="file" name="archivos_usuario[]" id="fileInput" multiple style="display: none;">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-block btn-lg">Subir Archivos Seleccionados</button>
                                </div>
                            </form>
                            <div class="upload-progress" style="margin-top: 20px;">
                                <h4>Archivos seleccionados / Progreso:</h4>
                                <ul id="fileList" style="list-style: none; padding-left:0;"></ul>
                            </div>
                        </div>

                        <div class="file-list-panel">
                            <h2 class="section-subtitle">Contenido de <code>/www</code></h2>
                            <?php if (empty($archivos_y_carpetas)): ?>
                                <p>El directorio <code>/www</code> está vacío. ¡Sube tu primer archivo!</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="file-list-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Tipo</th>
                                                <th>Tamaño</th>
                                                <th>Última Modificación</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archivos_y_carpetas as $item): ?>
                                                <tr>
                                                    <td>
                                                        <span class="<?php echo $item['tipo'] === 'directorio' ? 'icon-folder' : 'icon-file'; ?>"></span>
                                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                                    </td>
                                                    <td><?php echo ucfirst($item['tipo']); ?></td>
                                                    <td><?php echo $item['tipo'] === 'archivo' ? round($item['tamano'] / 1024, 2) . ' KB' : '-'; ?></td>
                                                    <td><?php echo htmlspecialchars($item['modificado']); ?></td>
                                                    <!-- <td class="actions">
                                                        <?php // if ($item['tipo'] === 'archivo'): ?>
                                                            <a href="#" class="btn btn-xs btn-outline-danger">Eliminar</a>
                                                        <?php // endif; ?>
                                                    </td> -->
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php elseif (!$error_gestor): ?>
                        <div class="alert alert-info">No se ha podido cargar la información del gestor de archivos para el sitio seleccionado.</div>
                    <?php endif; ?>
                     <div style="margin-top: 30px;">
                        <a href="mis_sitios.php" class="btn btn-outline">&larr; Volver a Mis Sitios</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const themeTogglePanel = document.getElementById('theme-toggle-panel');
    const bodyPanel = document.body;
    function applyThemePanel(theme) {
        bodyPanel.classList.remove('light-mode', 'dark-mode');
        bodyPanel.classList.add(theme + '-mode');
        const sunIconPanel = themeTogglePanel ? themeTogglePanel.querySelector('.sun-icon') : null;
        const moonIconPanel = themeTogglePanel ? themeTogglePanel.querySelector('.moon-icon') : null;
        if (theme === 'light') {
            if(sunIconPanel) sunIconPanel.style.display = 'none';
            if(moonIconPanel) moonIconPanel.style.display = 'block';
        } else {
            if(sunIconPanel) sunIconPanel.style.display = 'block';
            if(moonIconPanel) moonIconPanel.style.display = 'none';
        }
        localStorage.setItem('theme', theme);
    }
    if (themeTogglePanel) {
        themeTogglePanel.addEventListener('click', () => {
            let newTheme = bodyPanel.classList.contains('light-mode') ? 'dark' : 'light';
            applyThemePanel(newTheme);
        });
    }
    const savedThemePanel = localStorage.getItem('theme') || 'dark';
    applyThemePanel(savedThemePanel);

    const animatedSections = document.querySelectorAll('.animated-section');
    if (animatedSections.length > 0) {
        const observerOptions = { root: null, rootMargin: '0px', threshold: 0.1 };
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        animatedSections.forEach(section => { observer.observe(section); });
    }

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const fileListUI = document.getElementById('fileList');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', (e) => {
            if (e.target.tagName !== 'LABEL') {
                 fileInput.click();
            }
        });
        fileInput.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        dropZone.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        dropZone.addEventListener('drop', (event) => {
            event.preventDefault();
            dropZone.classList.remove('dragover');
            if (event.dataTransfer.files.length) {
                fileInput.files = event.dataTransfer.files;
                updateSelectedFilesDisplay(); 
            }
        });
        fileInput.addEventListener('change', updateSelectedFilesDisplay);
    }
    
    function updateSelectedFilesDisplay() {
        if (!fileListUI || !fileInput) return;
        fileListUI.innerHTML = ''; 
        if (fileInput.files.length > 0) {
            for (const file of fileInput.files) {
                const listItem = document.createElement('li');
                listItem.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                fileListUI.appendChild(listItem);
            }
        } else {
             fileListUI.innerHTML = '<li>No hay archivos seleccionados.</li>';
        }
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (!fileInput || !fileInput.files.length) {
                alert("Por favor, selecciona al menos un archivo para subir.");
                return;
            }

            const formData = new FormData(this);
            if (fileListUI) fileListUI.innerHTML = '<li><span class="icon">&#8987;</span> Subiendo...</li>';

            try {
                const response = await fetch('upload_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const resultText = await response.text();
                let result;
                try {
                    result = JSON.parse(resultText);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    console.error('Respuesta del servidor (no JSON):', resultText);
                    if (fileListUI) {
                        fileListUI.innerHTML = `<li>Error: Respuesta inesperada del servidor. Revisa la consola.</li>`;
                        fileListUI.querySelector('li').className = 'error';
                    }
                    return;
                }
                
                if (fileListUI) fileListUI.innerHTML = ''; 
                if (result.status === 'success' || result.status === 'partial_error') {
                    result.files.forEach(fileStatus => {
                        const li = document.createElement('li');
                        li.textContent = `${fileStatus.name}: ${fileStatus.message}`;
                        li.className = fileStatus.uploaded ? 'success' : 'error';
                        fileListUI.appendChild(li);
                    });
                    if (result.status === 'success' || (result.status === 'partial_error' && result.files.some(f => f.uploaded))) {
                        setTimeout(() => { 
                            window.location.href = window.location.pathname + '?sitio_id=' + formData.get('sitio_id_upload') + '&upload_success=1';
                        }, 2500);
                    }
                } else {
                    const li = document.createElement('li');
                    li.textContent = `Error general: ${result.message || 'Error desconocido.'}`;
                    li.className = 'error';
                    fileListUI.appendChild(li);
                }
            } catch (error) {
                console.error('Error en la subida (fetch):', error);
                if (fileListUI) {
                    fileListUI.innerHTML = `<li>Error de conexión o al procesar la subida.</li>`;
                    fileListUI.querySelector('li').className = 'error';
                }
            }
        });
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('upload_success') && fileListUI) {
        const successLi = document.createElement('li');
        successLi.textContent = "Archivos subidos. La lista se ha actualizado.";
        successLi.className = 'success';
        // fileListUI.prepend(successLi); // Añadir al principio
    }

});
</script>
</body>
</html>
