<?php
// includes/session_init.php

if (session_status() === PHP_SESSION_NONE) {
    // Definir duración de sesión a 30 días (2,592,000 segundos)
    $lifetime = 2592000;
    
    // Configurar duración en php.ini de forma dinámica
    ini_set('session.cookie_lifetime', $lifetime);
    ini_set('session.gc_maxlifetime', $lifetime);
    
    // Detectar si se está usando HTTPS para habilitar la cookie segura
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
?>
