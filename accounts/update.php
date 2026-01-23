<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$balance = $_POST['balance'] ?? '0.00';
$active = isset($_POST['active']) ? 1 : 0;

if (!$id || !$name) {
    $_SESSION['error'] = "Datos inválidos.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}

// Verificar que la cuenta pertenezca al usuario actual
$stmt = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$id, $_SESSION['company_id']]);
$exists = $stmt->fetch();

if (!$exists) {
    $_SESSION['error'] = "Cuenta no encontrada.";
    header('Location: list.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE accounts 
        SET name = ?, balance = ?, active = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([
        $name,
        (float)$balance,
        $active,
        (int)$id,
        $_SESSION['company_id']
    ]);

    $_SESSION['success'] = "Cuenta actualizada con éxito.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    error_log("Error updating account: " . $e->getMessage());
    $_SESSION['error'] = "Error al actualizar la cuenta.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}