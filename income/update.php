<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$company_id = $_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$id = $_POST['id'] ?? null;
$date = $_POST['date'] ?? '';
$description = trim($_POST['description'] ?? '');
$category_id = $_POST['category_id'] ?? null;
$account_id = $_POST['account_id'] ?? null;
$amount = $_POST['amount'] ?? '';
$note = trim($_POST['note'] ?? '');

if (!$id || !$date || !$description || !$category_id || !$account_id || !$amount) {
    $_SESSION['error'] = "Todos los campos son obligatorios.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}

// Validar que el ingreso exista y pertenezca a la empresa
$stmt = $pdo->prepare("SELECT amount, account_id FROM incomes WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$id, $company_id]);
$old_income = $stmt->fetch();

if (!$old_income) {
    $_SESSION['error'] = "Ingreso no encontrado.";
    header('Location: list.php');
    exit();
}

// Validar categoría de ingreso
$stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND company_id = ? AND type = 'income'");
$stmt->execute([(int)$category_id, $company_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Categoría no válida.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}

// Validar cuenta
$stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$account_id, $company_id]);
$new_account = $stmt->fetch();
if (!$new_account) {
    $_SESSION['error'] = "Cuenta no válida.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}

try {
    $pdo->beginTransaction();

    // Si la cuenta cambió o el monto cambió, ajustar saldos
    $old_amount = (float)$old_income['amount'];
    $new_amount = (float)$amount;
    $old_account_id = (int)$old_income['account_id'];
    $new_account_id = (int)$account_id;

    // Revertir el monto anterior de la cuenta antigua
    if ($old_account_id == $new_account_id) {
        // Misma cuenta: ajustar diferencia
        $diff = $new_amount - $old_amount;
        $updated_balance = (float)$new_account['balance'] + $diff;
        $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$updated_balance, $new_account_id]);
    } else {
        // Cuentas distintas: revertir vieja, sumar nueva
        $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
        $stmt->execute([$old_account_id]);
        $old_account = $stmt->fetch();
        if ($old_account) {
            $reverted_balance = (float)$old_account['balance'] - $old_amount;
            $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $stmt->execute([$reverted_balance, $old_account_id]);
        }

        $new_updated_balance = (float)$new_account['balance'] + $new_amount;
        $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_updated_balance, $new_account_id]);
    }

    // Actualizar el ingreso
    $stmt = $pdo->prepare("
        UPDATE incomes 
        SET date = ?, description = ?, amount = ?, category_id = ?, account_id = ?, note = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$date, $description, $new_amount, (int)$category_id, (int)$account_id, $note, (int)$id, $company_id]);

    $pdo->commit();

    $_SESSION['success'] = "Ingreso actualizado correctamente.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error updating income: " . $e->getMessage());
    $_SESSION['error'] = "Error al actualizar el ingreso.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}