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
        <h2>64 GB</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Xeon® Processor E5-2690 v3</p>
                <p class="specs">64GB DDR4 | NVMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">90.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Order Now</button>
            </div>
        </div>
        
        <div class="server-item">
            <div class="details">
                <p class="processor">2x Intel® Xeon® E5-2699 v3</p>
                <p class="specs">64GB DDR4 NVMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">176.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Order Now</button>
            </div>
        </div>
        <br>

        <h2>128 GB</h2>
        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Pentium® G4400</p>
                <p class="specs">128GB DDR4 | NVMe</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">150.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Order Now</button>
            </div>
        </div>

        <div class="server-item">
            <div class="details">
                <p class="processor">Intel® Core™ i7-9700 DDR4</p>
                <p class="specs">128GB DDR4 ECC</p>
                <p class="network">1 Gbps Shared | 1 IPv4 | ∞ Traffic</p>
            </div>
            <div class="pricing">
                <p class="price">220.00€</p>
                <button class="btn-ordenar" onclick="window.location.href='vps.php';">Order Now</button>
            </div>
        </div>

    

    </div>
</body>
</html>
