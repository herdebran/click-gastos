<?php
// login-process.php

session_start();

// Si ya está logueado, ir al dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = $_GET['redirect'] ?? 'dashboard.php';
    header('Location: ' . $redirect);
    exit();
}

// Cargar la conexión a la base de datos
require_once 'config/database.php';
$pdo = getDatabaseConnection(); // ← ¡esta línea es clave!

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = "Por favor, completa todos los campos.";
    } else {
        try {
            // Buscar usuario por username
            $stmt = $pdo->prepare("SELECT id, username, password_hash, company_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Iniciar sesión con user_id y company_id
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['company_id'] = (int)$user['company_id'];
                $_SESSION['username'] = $user['username'];

                // Redirigir a la página original o al dashboard
                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);

                header('Location: ' . $redirect);
                exit();
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Ocurrió un error al iniciar sesión. Inténtalo más tarde.";
        }
    }
}

// Guardar error en sesión y redirigir
$_SESSION['login_error'] = $error;
header('Location: login.php');
exit();