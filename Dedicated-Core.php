<?php
include 'menu.html';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedicated Servers</title>
    <link rel="stylesheet" href="css/dedicated.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="server-container">
        <h2>Intel Core i7</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor"> Intel® Core™ i7-4770</p>
                <p class="specs">32GB DDR4 | NVMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">80.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Pedir ahora</button>
            </div>
        </div>
        
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Core™ i7-9700 DDR4</p>
                <p class="specs">32GB DDR4 | NVMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">100.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Pedir ahora</button>
            </div>
        </div>
        <br>

        <h2>AMD Ryzen</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Ryzen™ 9 3950X</p>
                <p class="specs">32GB DDR4 | NVMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">150.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Pedir ahora</button>
            </div>
        </div>

        <div class="server-item">
            <div class="details">
                <p class="processor">AMD Ryzen™ 9 7950X</p>
                <p class="specs">32GB DDR4 | NvMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">250.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Pedir ahora</button>
            </div>
        </div>
    </div>
</body>
</html>
