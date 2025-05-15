<?php
session_start();

if (!isset($_SESSION['ClienteID']) || !isset($_SESSION['token']) || !isset($_COOKIE['session_token'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['token'] !== $_COOKIE['session_token']) {
    session_destroy();
    setcookie("session_token", "", time() - 3600, "/");
    header("Location: login.php");
    exit();
}

include("menu_vertical_usuario.html");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedicated Servers</title>
    <style>
        .menu-vertical {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #9c2531;
            padding-top: 20px;
            color: white;
            z-index: 999;
        }

        .menu-list {
            list-style: none;
            padding: 0;
        }

        .menu-list li a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
        }

        .menu-list li a:hover {
            background-color: #b33644;
        }

        /* Estilo principal */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .server-container {
            margin-left: 270px;
            padding: 20px;
        }

        .accordion {
            background-color: #f1f1f1;
            color: #333;
            cursor: pointer;
            padding: 18px;
            width: 100%;
            text-align: left;
            border: none;
            outline: none;
            transition: 0.4s;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .accordion.active, .accordion:hover {
            background-color: #ccc;
        }

        .accordion-content {
            padding: 0 18px;
            display: none;
            overflow: hidden;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }

        .server-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .server-item .details {
            flex: 1;
        }

        .server-item .pricing {
            text-align: center;
        }

        .server-item .price {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .btn-ordenar {
            background-color: #9c2531;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-ordenar:hover {
            background-color: #b33644;
        }
    </style>
</head>
<body>
<div class="server-container">
    <h2>Dedicated Servers</h2>

    <!-- Categoría: Dedicated Servers Procesadores -->
    <button class="accordion">Dedicated Servers Procesadores</button>
    <div class="accordion-content">
        <h2>Intel Core i7</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Core™ i7-4770</p>
                <p class="specs">32GB DDR4 | NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">80.00€</p>
                <a href="factura.php?id=4" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
        
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Core™ i7-9700 DDR4</p>
                <p class="specs">32GB DDR4 | NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">100.00€</p>
                <a href="factura.php?id=5" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
		<br>
		<h2>AMD Ryzen</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Ryzen™ 9 3950X</p>
                <p class="specs">32GB DDR4 | NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">150.00€</p>
                <a href="factura.php?id=6" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>

        <div class="server-item">
            <div class="details">
                <p class="processor">AMD Ryzen™ 9 7950X</p>
                <p class="specs">32GB DDR4 | NvMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">250.00€</p>
                <a href="factura.php?id=7" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
    </div>

    <button class="accordion">Dedicated Servers RAM</button>
    <div class="accordion-content">
    <h2>64GB RAM</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Xeon® Processor E5-2690 v3</p>
                <p class="specs">64GB DDR4 | NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">90.00€</p>
                <a href="factura.php?id=8" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
        <div class="server-item">
            <div class="details">
                <p class="processor">2x Intel® Xeon® E5-2699 v3</p>
                <p class="specs">64GB RAM | NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">130.00€</p>
                <a href="factura.php?id=9" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
    <br>
    <h2>128GB RAM</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Pentium® G4400</p>
                <p class="specs">128GB DDR4 | NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">150.00€</p>
                <a href="factura.php?id=8" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Core™ i7-9700 DDR4</p>
                <p class="specs">128GB RAM NVMe | 40GB</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">220.00€</p>
                <a href="factura.php?id=9" class="btn-ordenar">Pedir ahora</a>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
    const accordions = document.querySelectorAll(".accordion");
    accordions.forEach(accordion => {
        accordion.addEventListener("click", function () {
            this.classList.toggle("active");
            const content = this.nextElementSibling;
            if (content.style.display === "block") {
                content.style.display = "none";
            } else {
                content.style.display = "block";
            }
        });
    });
</script>
</body>
</html>
