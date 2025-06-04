<?php
require_once 'config.php';
session_start();
require 'conexion.php';

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    $_SESSION['error_message'] = "Acceso no autorizado.";
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    $_SESSION['error_message'] = "Token de sesión inválido.";
    header("Location: login.php");
    exit();
}

$faqs = [
    [
        'question' => "¿Qué tipo de hosting ofrece k8servers?",
        'answer' => "En k8servers nos especializamos en ofrecer un plan de hosting único, potente y flexible, pensado para desarrolladores y creativos que buscan control total. Te proporcionamos un entorno robusto donde puedes subir tus propios archivos (HTML, CSS, JS, PHP, etc.) y gestionar tu sitio web con libertad, similar a tener tu propio espacio en un servidor, pero sin la complejidad de administrar un VPS completo. Es ideal para sitios estáticos, aplicaciones PHP, landing pages, y proyectos que requieren acceso directo a los archivos."
    ],
    [
        'question' => "Si contrato el servicio, ¿cómo accedo a mi sitio?",
        'answer' => "Una vez contratas nuestro plan, se te asignará un subdominio único con el formato <code>tunombre.k8servers.es</code>. Esta será la dirección principal de tu sitio. Te proporcionaremos credenciales FTP/SFTP para que puedas subir los archivos de tu página web directamente a tu espacio asignado. ¡Es así de simple!"
    ],
    [
        'question' => "¿Puedo usar mi propio dominio personalizado (ej: midominio.com)?",
        'answer' => "¡Absolutamente! Aunque inicialmente te proporcionamos un subdominio <code>tunombre.k8servers.es</code> para un acceso rápido, puedes apuntar fácilmente tu propio dominio personalizado a tu sitio alojado con nosotros. Te guiaremos en el proceso de configuración de los registros DNS necesarios (generalmente un registro CNAME o A)."
    ],
    [
        'question' => "¿Qué tecnologías y lenguajes de programación son compatibles?",
        'answer' => "Nuestro entorno está optimizado para una amplia gama de tecnologías web. Soportamos HTML, CSS, JavaScript de forma nativa. Para el backend, ofrecemos soporte completo para PHP (múltiples versiones seleccionables), y también puedes ejecutar scripts o aplicaciones basadas en Node.js o Python, siempre que se ajusten a un entorno de hosting compartido gestionado. Si tienes requisitos específicos, no dudes en consultarnos."
    ],
    [
        'question' => "¿Qué pasa si necesito más de un sitio web?",
        'answer' => "Actualmente, nuestro plan 'Developer Pro' está diseñado para alojar un proyecto web principal bajo el subdominio que elijas (o tu dominio personalizado apuntado a él). Si necesitas alojar múltiples sitios web completamente independientes, cada uno requeriría su propia instancia de servicio. Sin embargo, dentro de tu sitio principal, puedes tener múltiples páginas, secciones o incluso subdirectorios que funcionen como micrositios."
    ],
    [
        'question' => "¿Cómo funciona la facturación y la renovación?",
        'answer' => "Nuestro plan de hosting se factura mensualmente. Al contratar, se genera tu primera factura. Las renovaciones se facturarán automáticamente unos días antes de la fecha de vencimiento de tu servicio. Podrás ver y gestionar todas tus facturas desde la sección 'Facturas' en tu panel de usuario. Te enviaremos recordatorios antes de cada renovación."
    ],
    [
        'question' => "¿Qué medidas de seguridad implementan?",
        'answer' => "La seguridad es una prioridad en k8servers. Implementamos firewalls a nivel de red y servidor, protección contra ataques DDoS comunes, y monitorización constante. Además, realizamos copias de seguridad diarias de tus datos para que puedas estar tranquilo. Te recomendamos también seguir buenas prácticas de seguridad en tu propio código y aplicaciones."
    ],
    [
        'question' => "¿Ofrecen bases de datos?",
        'answer' => "Sí, nuestro plan incluye la posibilidad de crear y gestionar bases de datos MySQL (o MariaDB). Podrás administrarlas fácilmente a través de herramientas como phpMyAdmin, a la cual te daremos acceso."
    ],
    [
        'question' => "¿Qué tipo de soporte técnico puedo esperar?",
        'answer' => "Ofrecemos soporte técnico prioritario 24/7 a través de nuestro sistema de tickets en tu panel de usuario. Nuestro equipo está listo para ayudarte con cualquier problema relacionado con el servicio de hosting, configuración de tu subdominio, acceso FTP, o dudas sobre las funcionalidades de la plataforma. Para problemas específicos de tu código o diseño web, te podremos orientar, pero el desarrollo en sí corre por tu cuenta."
    ],
    [
        'question' => "No soy un desarrollador experto, ¿es k8servers para mí?",
        'answer' => "Si bien nuestro servicio ofrece gran control y es ideal para quienes tienen conocimientos técnicos, también es accesible si estás empezando. La clave es que necesitas tener los archivos de tu sitio web listos para subir (HTML, CSS, imágenes, etc.). Si usas un constructor de sitios que exporta estos archivos, o si has descargado una plantilla, podrás usar k8servers. La subida por FTP es un proceso estándar y te podemos guiar. No ofrecemos un constructor visual 'drag and drop' integrado, el foco está en darte el espacio y las herramientas para alojar lo que tú crees."
    ]
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preguntas Frecuentes (FAQ) - k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .faq-item {
            background-color: var(--card-bg);
            margin-bottom: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-current);
            border-left: 4px solid var(--primary-color);
        }
        .faq-question {
            padding: 20px 25px;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color var(--transition-speed) ease;
        }
        .faq-question:hover {
            background-color: color-mix(in srgb, var(--primary-color) 8%, transparent);
        }
        .faq-question .arrow {
            font-size: 1.5rem;
            transition: transform 0.3s ease-in-out;
            color: var(--primary-color);
        }
        .faq-answer {
            padding: 0px 25px;
            font-size: 1rem;
            color: var(--text-color-muted);
            line-height: 1.7;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out, padding 0.4s ease-in-out;
        }
        .faq-answer p, .faq-answer ul, .faq-answer ol {
            margin-bottom: 15px;
        }
        .faq-answer ul, .faq-answer ol {
            padding-left: 20px;
        }
        .faq-answer code {
            background-color: var(--bg-color);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: var(--font-code);
            color: var(--accent-color);
            font-size: 0.9em;
        }
        .faq-item.active .faq-question .arrow {
            transform: rotate(180deg);
        }
        .faq-item.active .faq-answer {
            padding: 20px 25px;
            max-height: 1000px;
        }
        .faq-contact-prompt {
            margin-top: 40px;
            padding: 25px;
            background-color: var(--bg-color-secondary);
            border-radius: var(--border-radius);
            text-align: center;
        }
        .faq-contact-prompt p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="panel-layout">
        <?php include 'menu_panel.php'; ?>

        <main class="panel-main-content animated-section">
            <header class="panel-main-header">
                <div class="container-fluid">
                    <h1>Preguntas Frecuentes (FAQ)</h1>
                    <p>Encuentra respuestas a las dudas más comunes sobre k8servers.</p>
                </div>
            </header>

            <div class="panel-content-area">
                <div class="container-fluid">
                    <section class="content-section">
                        <h2 class="section-subtitle">Sobre Nuestro Servicio</h2>
                        <div class="faq-list">
                            <?php foreach ($faqs as $index => $faq): ?>
                                <div class="faq-item">
                                    <div class="faq-question" role="button" tabindex="0" aria-expanded="false" aria-controls="faq-answer-<?php echo $index; ?>">
                                        <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                        <span class="arrow">&#9662;</span>
                                    </div>
                                    <div class="faq-answer" id="faq-answer-<?php echo $index; ?>" role="region">
                                        <?php echo $faq['answer']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="faq-contact-prompt">
                            <p>¿No encontraste la respuesta que buscabas?</p>
                            <a href="support.php" class="btn btn-primary">Contacta con Soporte</a>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

<script>
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

    document.addEventListener('DOMContentLoaded', () => {
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

        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', () => {
                const currentlyActive = document.querySelector('.faq-item.active');
                if (currentlyActive && currentlyActive !== item) {
                    currentlyActive.classList.remove('active');
                    currentlyActive.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                }
                item.classList.toggle('active');
                const isExpanded = item.classList.contains('active');
                question.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            });
            question.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    question.click();
                }
            });
        });
    });
</script>
</body>
</html>
