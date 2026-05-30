<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDatabaseConnection();

// Aceptar tanto POST como GET
$expense_id = $_POST['expense_id'] ?? $_GET['id'] ?? null;

if (!$expense_id) {
    $_SESSION['error_message'] = "ID de gasto no especificado";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Obtener datos del gasto
    $stmt = $pdo->prepare("
        SELECT amount, account_id 
        FROM expenses 
        WHERE id = ? AND company_id = ? AND paid = FALSE
    ");
    $stmt->execute([$expense_id, $_SESSION['company_id']]);
    $expense = $stmt->fetch();

    if (!$expense) {
        throw new Exception("Gasto no encontrado o ya pagado");
    }

    // Marcar como pagado
    $stmt = $pdo->prepare("
        UPDATE expenses 
        SET paid = TRUE, paid_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$expense_id]);

    // Actualizar saldo de la cuenta
    $stmt = $pdo->prepare("
        UPDATE accounts 
        SET balance = balance - ? 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$expense['amount'], $expense['account_id'], $_SESSION['company_id']]);

    $pdo->commit();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error al marcar como pagado: " . $e->getMessage());
    $_SESSION['error_message'] = "Error al procesar el pago";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?>