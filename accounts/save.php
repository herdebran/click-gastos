<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$balance = $_POST['balance'] ?? '0.00';
$currency_id = $_POST['currency_id'] ?? 1;
$active = isset($_POST['active']) ? 1 : 0;

if (!$name) {
    $_SESSION['error'] = "El nombre de la cuenta es obligatorio.";
    header('Location: add.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO accounts (name, active, balance,currency_id, company_id)
        VALUES (?, ?, ?, ?,?)
    ");
    $stmt->execute([
        $name,
        $active,
        (float)$balance,
        (int)$currency_id,
        $_SESSION['company_id']
    ]);

    $_SESSION['success'] = "Cuenta creada con éxito.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    error_log("Error saving account: " . $e->getMessage());
    $_SESSION['error'] = "Error al guardar la cuenta.";
    header('Location: add.php');
    exit();
}