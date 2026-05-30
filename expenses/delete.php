<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$company_id = $_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$expense_id = $_GET['id'] ?? null;
$restore = ($_GET['restore'] ?? '0') === '1';

if (!$expense_id || !is_numeric($expense_id)) {
    $_SESSION['error'] = "Gasto no válido.";
    header('Location: list.php');
    exit();
}

// Obtener datos del gasto (y validar que pertenezca a la empresa)
$stmt = $pdo->prepare("
    SELECT e.amount, e.account_id, a.balance, e.paid
    FROM expenses e
    INNER JOIN accounts a ON e.account_id = a.id
    WHERE e.id = ? AND e.company_id = ?
");
$stmt->execute([(int)$expense_id, $company_id]);
$expense = $stmt->fetch();

if (!$expense) {
    $_SESSION['error'] = "Gasto no encontrado.";
    header('Location: list.php');
    exit();
}

try {
    $pdo->beginTransaction();

    // Opción 1: Revertir saldo (Solo si es un movimiento PAGO)
    if ($restore && $expense['paid']) {
        $new_balance = (float)$expense['balance'] + (float)$expense['amount'];
        $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, (int)$expense['account_id']]);
    }

    // Opción 2: Eliminar el gasto
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([(int)$expense_id]);

    $pdo->commit();

    $_SESSION['success'] = "Gasto eliminado" . ($restore ? " y saldo revertido." : ".");
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting expense: " . $e->getMessage());
    $_SESSION['error'] = "Error al eliminar el gasto.";
    header('Location: list.php');
    exit();
}