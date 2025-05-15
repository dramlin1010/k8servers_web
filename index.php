<?php
include 'menu.html';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Hosting</title>
    <link rel="stylesheet" href="styles.css">
    <style>
body, h1, h2, h3, p, ul, li, a {
    margin: 0;
    padding: 0;
    list-style: none;
    text-decoration: none;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    line-height: 1.6;
    margin: 0;
    padding: 0;
}

.hero {
    background: linear-gradient(to right, #9c2531, #b33644);
    color: white;
    text-align: center;
    padding: 60px 20px;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.hero h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.hero p {
    font-size: 1.2rem;
    margin-bottom: 25px;
}

.btn {
    display: inline-block;
    background-color: #fff;
    color: #9c2531;
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: bold;
    text-transform: uppercase;
    transition: background 0.3s;
}

.btn:hover {
    background-color: #f4f4f4;
}

.features {
    padding: 40px 20px;
    background-color: #fff;
    text-align: center;
}

.features h2 {
    margin-bottom: 20px;
    font-size: 2rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.feature-item {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.feature-item img {
    width: 80px;
    margin-bottom: 15px;
}

.feature-item h3 {
    margin-bottom: 10px;
    color: #9c2531;
}

.plans {
    padding: 40px 20px;
    text-align: center;
    background-color: #f4f4f4;
}

.plans h2 {
    margin-bottom: 20px;
    font-size: 2rem;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.plan-item {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
}

.plan-item h3 {
    color: #9c2531;
    margin-bottom: 15px;
}

.plan-item p {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: #333;
}

.plan-item ul {
    margin-bottom: 20px;
}

.plan-item ul li {
    margin-bottom: 10px;
    color: #555;
}

.cta {
    background-color: #9c2531;
    color: white;
    text-align: center;
    padding: 40px 20px;
}

.cta h2 {
    margin-bottom: 15px;
    font-size: 2rem;
}

.cta p {
    margin-bottom: 25px;
    font-size: 1.2rem;
}

footer {
    background-color: #333;
    color: white;
    text-align: center;
    padding: 20px;
    font-size: 0.9rem;
}

footer p {
    margin: 0;
}

    </style>
</head>
<body>
    <header class="hero">
        <div class="hero-content">
            <h1>Bienvenido a mi Hosting</h1>
            <p>Soluciones confiables para tus necesidades de hosting. VPS, servidores dedicados y más al alcance de un clic.</p>
            <a href="#planes" class="btn">Ver Planes</a>
        </div>
    </header>

    <main>
        <section class="features">
            <h2>¿Por qué elegirnos?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <h3>99.9% de Uptime Garantizado</h3>
                    <p>Tu sitio estará disponible en todo momento gracias a nuestra infraestructura avanzada.</p>
                </div>
                <div class="feature-item">
                    <h3>Soporte 24/7</h3>
                    <p>Nuestro equipo está disponible a cualquier hora para resolver tus dudas.</p>
                </div>
                <div class="feature-item">
                    <h3>Seguridad de Alto Nivel</h3>
                    <p>Protegemos tus datos con medidas de seguridad avanzadas y copias de seguridad automáticas.</p>
                </div>
            </div>
        </section>

        <section id="planes" class="plans">
            <h2>Planes Destacados</h2>
            <div class="plans-grid">
                <div class="plan-item">
                    <h3>Plan Básico</h3>
                    <p>Desde <strong>10€/mes</strong></p>
                    <ul>
                        <li>1 dominio incluido</li>
                        <li>CPU de VCore x1</li>
                        <li>RAM de 2048 MB</li>
                        <li>10 GB de almacenamiento</li>
                        <li>Soporte 24/7</li>
                    </ul>
                    <a href="vps.php" class="btn">Contratar</a>
                </div>
                <div class="plan-item">
                    <h3>Plan Avanzado</h3>
                    <p>Desde <strong>18€/mes</strong></p>
                    <ul>
                        <li>1 dominio incluido</li>
                        <li>CPU de VCore x2</li>
                        <li>RAM de 4096 MB</li>
                        <li>40 GB de almacenamiento</li>
                        <li>Soporte 24/7</li>
                    </ul>
                    <a href="vps.php" class="btn">Contratar</a>
                </div>
                <div class="plan-item">
                    <h3>Plan Premium</h3>
                    <p>Desde <strong>24€/mes</strong></p>
                    <ul>
                        <li>1 dominio incluido</li>
                        <li>CPU de VCore x4</li>
                        <li>RAM de 8196 MB</li>
                        <li>80 GB de almacenamiento</li>
                        <li>Soporte 24/7</li>
                    </ul>
                    <a href="vps.php" class="btn">Contratar</a>
                </div>
            </div>
        </section>

        <section class="cta">
            <h2>¿Listo para empezar?</h2>
            <p>Descubre cómo podemos ayudarte a llevar tu proyecto al siguiente nivel.</p>
            <a href="registro.php" class="btn">Crear Cuenta</a>
        </section>
    </main>

    <?php include 'footer.html'; ?>
</body>
</html>
