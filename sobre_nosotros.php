<?php
require_once 'config.php';
include 'menu.php';

$faqs_sobre_nosotros = [
    [
        'question' => "¿Cuál es la misión de k8servers?",
        'answer' => "Nuestra misión en k8servers es empoderar a desarrolladores, diseñadores y emprendedores creativos proporcionándoles una plataforma de hosting web que combine potencia, flexibilidad y simplicidad. Creemos que no deberías tener que luchar con la complejidad de la infraestructura para lanzar tus ideas online. Queremos ser el cimiento sólido sobre el cual construyes tus proyectos web más ambiciosos."
    ],
    [
        'question' => "¿Quién está detrás de k8servers?",
        'answer' => "k8servers nació de la pasión de un equipo de entusiastas de la tecnología y desarrolladores web con años de experiencia en la industria del hosting y la infraestructura en la nube. Frustrados por las opciones que eran o demasiado restrictivas o innecesariamente complejas para muchos proyectos, decidimos crear la solución que nosotros mismos querríamos usar: un servicio de hosting directo, sin adornos innecesarios, pero con todo el rendimiento y control que necesitas."
    ],
    [
        'question' => "¿Por qué elegir k8servers en lugar de otras opciones?",
        'answer' => "Ofrecemos una propuesta de valor clara: <strong>control total y rendimiento superior sin complicaciones</strong>. A diferencia de los constructores de sitios que te encierran en su ecosistema, o los VPS que requieren una gestión técnica intensiva, k8servers te da un punto intermedio ideal. Obtienes acceso directo a tus archivos vía FTP/SFTP, la libertad de usar las tecnologías que prefieras (PHP, Node.js, Python, HTML/CSS/JS), y la tranquilidad de un hosting rápido y seguro gestionado por nosotros. Nuestro plan único simplifica la elección y te asegura que siempre tendrás los mejores recursos disponibles."
    ],
    [
        'question' => "¿Qué tipo de hosting ofrece k8servers?",
        'answer' => "Nos especializamos en ofrecer un plan de hosting único, potente y flexible, pensado para quienes buscan control total. Te proporcionamos un entorno robusto donde puedes subir tus propios archivos (HTML, CSS, JS, PHP, etc.) y gestionar tu sitio web con libertad. Es ideal para sitios estáticos, aplicaciones PHP, landing pages, y proyectos que requieren acceso directo a los archivos."
    ],
    [
        'question' => "¿Cómo accedo a mi sitio una vez contratado el servicio?",
        'answer' => "Se te asignará un subdominio único <code>tunombre.k8servers.es</code> y credenciales FTP/SFTP para que puedas subir los archivos de tu página web directamente a tu espacio asignado."
    ],
    [
        'question' => "¿Puedo usar mi propio dominio personalizado?",
        'answer' => "¡Absolutamente! Puedes apuntar fácilmente tu propio dominio personalizado a tu sitio alojado con nosotros. Te guiaremos en la configuración de los registros DNS."
    ],
    [
        'question' => "¿Qué tecnologías son compatibles?",
        'answer' => "Soportamos HTML, CSS, JavaScript, PHP (múltiples versiones), y también puedes ejecutar scripts o aplicaciones basadas en Node.js o Python, dentro de un entorno de hosting compartido gestionado."
    ],
    [
        'question' => "¿Cómo funciona la facturación?",
        'answer' => "Nuestro plan se factura mensualmente. Las renovaciones son automáticas y puedes gestionar tus facturas desde tu panel de usuario una vez registrado."
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nosotros - k8servers</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .about-hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1522071820081-009f0129c7da?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8d2ViJTIwZGVzaWdufGVufDB8fDB8fHww&auto=format&fit=crop&w=1200&q=70') no-repeat center center/cover;
            color: var(--light-text-color);
            padding: 120px 0 80px;
            text-align: center;
        }
        .about-hero h1 {
            color: var(--light-text-color);
            font-size: clamp(2.2rem, 5vw, 3.5rem);
        }
        .about-hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 20px auto;
            color: rgba(255,255,255,0.9);
        }
        .about-content-section {
            padding: 60px 0;
            background-color: var(--bg-color);
        }
        .about-text-block {
            max-width: 800px;
            margin: 0 auto 40px auto;
            line-height: 1.8;
            font-size: 1.1rem;
            color: var(--text-color-muted);
        }
        .about-text-block h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            text-align: center;
        }
        .dark-mode .about-text-block h2 {
            color: var(--primary-color);
        }
        .light-mode .about-text-block h2 {
            color: var(--primary-color);
        }
        .about-text-block p {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <header class="about-hero animated-section">
        <div class="container">
            <h1>Conoce k8servers</h1>
            <p>Nuestra historia, nuestra misión y por qué estamos aquí para potenciar tus proyectos web.</p>
        </div>
    </header>

    <main>
        <section class="about-content-section animated-section">
            <div class="container">
                <div class="about-text-block">
                    <h2>Nuestra Misión</h2>
                    <p>En k8servers, nuestra misión es simplificar el hosting web sin sacrificar potencia ni control. Creemos que cada idea merece una plataforma robusta y accesible para brillar online. Nos dedicamos a proporcionar a desarrolladores, diseñadores y emprendedores las herramientas que necesitan para construir y escalar sus proyectos web con confianza y libertad.</p>
                    <p>Buscamos eliminar las barreras técnicas innecesarias, ofreciendo un servicio directo, transparente y centrado en el rendimiento, para que puedas concentrarte en lo que mejor sabes hacer: crear.</p>
                </div>

                <div class="about-text-block">
                    <h2>¿Quiénes Somos?</h2>
                    <p>Somos un equipo de apasionados por la tecnología, con una profunda experiencia en la infraestructura de internet y el desarrollo web. k8servers nació de nuestra propia necesidad de encontrar un servicio de hosting que ofreciera un equilibrio perfecto: la flexibilidad de un entorno de servidor propio, pero con la facilidad de gestión de un hosting compartido de alta calidad. Decidimos construirlo nosotros mismos, enfocándonos en la velocidad, la seguridad y un soporte al cliente excepcional.</p>
                </div>
            </div>
        </section>

        <section id="faq-sobre-nosotros" class="faq-public-section animated-section" style="background-color: var(--bg-color-secondary); padding-top: 60px; padding-bottom: 60px;">
            <div class="container">
                <h2 class="section-title">Preguntas Frecuentes</h2>
                <div class="faq-list">
                    <?php foreach ($faqs_sobre_nosotros as $index_faq_sn => $faq_item_sn): ?>
                        <div class="faq-item">
                            <div class="faq-question" role="button" tabindex="0" aria-expanded="false" aria-controls="faq-answer-sn-<?php echo $index_faq_sn; ?>">
                                <span><?php echo htmlspecialchars($faq_item_sn['question']); ?></span>
                                <span class="arrow">&#9662;</span>
                            </div>
                            <div class="faq-answer" id="faq-answer-sn-<?php echo $index_faq_sn; ?>" role="region">
                                <?php echo $faq_item_sn['answer']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                 <div class="faq-contact-prompt" style="margin-top: 40px; text-align:center; background-color: var(--card-bg);">
                    <p>¿Tienes más preguntas o necesitas ayuda específica?</p>
                    <a href="contacto.php" class="btn btn-primary">Contáctanos</a>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const animatedSections = document.querySelectorAll('.animated-section');
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedSections.forEach(section => {
            observer.observe(section);
        });

        const faqItems = document.querySelectorAll('#faq-sobre-nosotros .faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', () => {
                const currentlyActiveFaq = document.querySelector('#faq-sobre-nosotros .faq-item.active');
                if (currentlyActiveFaq && currentlyActiveFaq !== item) {
                    currentlyActiveFaq.classList.remove('active');
                    currentlyActiveFaq.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
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
