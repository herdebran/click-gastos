<?php

// Función para detectar móvil
function isMobile() {
    return preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
        $_SERVER["HTTP_USER_AGENT"] ?? ''
    );
}

// Determinar duración según dispositivo
if (isMobile()) {
    $lifetime = 60 * 60 * 24 * 30; // 30 días en móvil
} else {
    $lifetime = 60 * 60; // 1 hora en desktop
}

// Configurar sesión ANTES de session_start()
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => true,      // Solo HTTPS
    'httponly' => true,    // No accesible desde JS
    'samesite' => 'Lax'
]);

session_start();

// Si no hay sesión activa → login
if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '/login.php') {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    }
    header('Location: /login.php');
    exit();
}
?>