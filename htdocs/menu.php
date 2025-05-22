<?php
?>
<nav class="main-nav">
    <div class="container nav-container">
        <a href="index.php" class="logo">k8servers</a>
        <ul class="nav-links">
            <li><a href="index.php#features">Características</a></li>
            <li><a href="index.php#pricing">Precio</a></li>
            <li><a href="sobre_nosotros.php">Sobre Nosotros</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="registro.php" class="nav-link-cta">Regístrate</a></li>
        </ul>
        <div class="controls">
            <button id="theme-toggle" class="theme-toggle-btn" title="Cambiar tema">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM12 5c.7 0 1.37.1 2 .29V3h-4v2.29c.63-.19 1.3-.29 2-.29zm0 14c-.7 0-1.37-.1-2-.29V21h4v-2.29c-.63.19-1.3.29-2 .29zM4.22 5.64l1.42-1.42L7.05 5.63 5.63 7.05 4.22 5.64zM16.95 18.37l1.42-1.42 1.41 1.41-1.42 1.42-1.41-1.41zM21 11h-2v2h2v-2zm-19 0H0v2h2v-2zM5.63 16.95l1.42 1.42L5.64 19.78 4.22 18.37l1.41-1.42zM18.37 4.22l1.42 1.42L19.78 7.05l-1.42-1.41-1.42-1.42z"/></svg>
                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M10 2c-1.82 0-3.53.5-5 1.35C7.99 5.08 10 8.3 10 12s-2.01 6.92-5 8.65C6.47 21.5 8.18 22 10 22c5.52 0 10-4.48 10-10S15.52 2 10 2z"/></svg>
            </button>
            <button class="hamburger" id="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</nav>
<script>
    const hamburger = document.getElementById('hamburger-menu');
    const navUl = document.querySelector('.main-nav .nav-links');
    if (hamburger && navUl) {
        hamburger.addEventListener('click', () => {
            navUl.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }

    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    
    function applyTheme(theme) {
        body.classList.remove('light-mode', 'dark-mode');
        body.classList.add(theme + '-mode');
        if (themeToggle) {
            const sunIcon = themeToggle.querySelector('.sun-icon');
            const moonIcon = themeToggle.querySelector('.moon-icon');
            if (theme === 'light') {
                if(sunIcon) sunIcon.style.display = 'none';
                if(moonIcon) moonIcon.style.display = 'block';
            } else {
                if(sunIcon) sunIcon.style.display = 'block';
                if(moonIcon) moonIcon.style.display = 'none';
            }
        }
        localStorage.setItem('theme', theme);
    }
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            let newTheme = body.classList.contains('light-mode') ? 'dark' : 'light';
            applyTheme(newTheme);
        });
    }

    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
</script>
