<?php
// includes/auth_guard.php

// Iniciar sesión si no está iniciada
require_once __DIR__ . '/session_init.php';

// Helper para responder JSON en caso de petición AJAX
function handle_unauthorized_access($mensaje = 'No autorizado') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (strpos($_SERVER['SCRIPT_NAME'] ?? '', 'ajax_') !== false)
        || (strpos($_SERVER['REQUEST_URI'] ?? '', 'ajax_') !== false);

    if ($is_ajax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $mensaje, 'session_expired' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: index.php');
    exit;
}

// 1. Verificación básica de autenticación
if (!isset($_SESSION['usuario_id'])) {
    handle_unauthorized_access('Sesión no iniciada. Por favor, ingresa al sistema.');
}

// 2. Cierre de sesión por inactividad (2 horas = 7200 segundos)
$timeout_duration = 7200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // La sesión ha expirado
    session_unset();
    session_destroy();
    
    // Iniciar nueva sesión temporal para pasar el mensaje de timeout
    require __DIR__ . '/session_init.php';
    $_SESSION['timeout_msg'] = "Tu sesión ha expirado por inactividad (2 horas). Por favor, ingresa nuevamente.";
    
    handle_unauthorized_access('Tu sesión ha expirado por inactividad. Por favor, ingresa nuevamente.');
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
