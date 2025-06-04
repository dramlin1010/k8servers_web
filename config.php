<?php
session_set_cookie_params([
    'lifetime' => 3600,
    'path'     => '/',
    'domain'   => '.k8servers.es',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
]);
?>