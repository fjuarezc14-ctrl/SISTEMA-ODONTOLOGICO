<?php
// includes/auth_guard.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificación básica de autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// 2. Cierre de sesión por inactividad (2 horas = 7200 segundos)
$timeout_duration = 7200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // La sesión ha expirado
    session_unset();
    session_destroy();
    
    // Iniciar nueva sesión temporal para pasar el mensaje de timeout
    session_start();
    $_SESSION['timeout_msg'] = "Tu sesión ha expirado por inactividad (2 horas). Por favor, ingresa nuevamente.";
    
    header('Location: index.php');
    exit;
}
// Actualizar la marca de tiempo de la última actividad
$_SESSION['last_activity'] = time();

// 3. Generación de Token CSRF global por sesión (si no existe)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Opcional: Función helper para verificar el token CSRF en POSTs
function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
