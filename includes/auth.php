<?php

// Función para detectar móvil
function isMobile() {
    return preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
        $_SERVER["HTTP_USER_AGENT"]
    );
}

// Configurar duración de sesión según dispositivo
if (isMobile()) {
    $session_duration = 60 * 60 * 24 * 30; // 30 días para móviles
} else {
    $session_duration = 60 * 60; // 1 hora para desktop
}

// Aplicar configuración
ini_set('session.cookie_lifetime', $session_duration);
ini_set('session.gc_maxlifetime', $session_duration);

// Iniciar sesion
session_start();

// Si no hay sesión, redirigir al login
if (!isset($_SESSION['user_id'])) {
    // Guardar la URL de destino para redirigir después del login
    if (!empty($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '/login.php') {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    }
    header('Location: /login.php');
    exit();
}
?>