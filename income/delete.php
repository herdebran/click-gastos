<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$company_id = $_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$income_id = $_GET['id'] ?? null;
$restore = ($_GET['restore'] ?? '0') === '1';

if (!$income_id || !is_numeric($income_id)) {
    $_SESSION['error'] = "Ingreso no válido.";
    header('Location: list.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT amount, account_id 
    FROM incomes 
    WHERE id = ? AND company_id = ?
");
$stmt->execute([(int)$income_id, $company_id]);
$income = $stmt->fetch();

if (!$income) {
    $_SESSION['error'] = "Ingreso no encontrado.";
    header('Location: list.php');
    exit();
}

try {
    $pdo->beginTransaction();

    if ($restore) {
        // Revertir el saldo: restar el monto de la cuenta
        $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
        $stmt->execute([(int)$income['account_id']]);
        $account = $stmt->fetch();
        if ($account) {
            $new_balance = (float)$account['balance'] - (float)$income['amount'];
            $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, (int)$income['account_id']]);
        }
    }

    // Eliminar el ingreso
    $stmt = $pdo->prepare("DELETE FROM incomes WHERE id = ?");
    $stmt->execute([(int)$income_id]);

    $pdo->commit();

    $_SESSION['success'] = "Ingreso eliminado" . ($restore ? " y saldo revertido." : ".");
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting income: " . $e->getMessage());
    $_SESSION['error'] = "Error al eliminar el ingreso.";
    header('Location: list.php');
    exit();
}