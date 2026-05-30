<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$company_id = $_SESSION['company_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit();
}

$date = $_POST['date'] ?? '';
$product_id = $_POST['product_id'] ?? null;
$amount = $_POST['amount'] ?? '';
$account_id = $_POST['account_id'] ?? '';
$note = trim($_POST['note'] ?? '');
$currency_id = $_POST['currency_id'] ?? 1;
$paid = isset($_POST['paid']) ? 1 : 0; //Checked de pagado
$paid_at = $paid ? $_POST['date'] : null; // Si está pagado, usar la fecha del movimiento

// Validar campos obligatorios
if (!$date || !$product_id || !$amount || !$account_id) {
    $_SESSION['error'] = "Todos los campos obligatorios deben completarse.";
    header('Location: add.php');
    exit();
}

// Validar que producto y cuenta pertenezcan a la empresa
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$product_id, $company_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "El producto no es válido.";
    header('Location: add.php');
    exit();
}

$stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$account_id, $company_id]);
$account = $stmt->fetch();

if (!$account) {
    $_SESSION['error'] = "La cuenta no es válida.";
    header('Location: add.php');
    exit();
}

$amount = (float)$amount;

//Validar que la moneda exista
$stmt = $pdo->prepare("SELECT id FROM currencies WHERE id = ?");
$stmt->execute([(int)$currency_id]);
if (!$stmt->fetch()) {
    $currency_id = 1; // fallback a ARS
}

// Obtener moneda de la cuenta
$stmt = $pdo->prepare("SELECT currency_id FROM accounts WHERE id = ?");
$stmt->execute([(int)$account_id]);
$account_currency = (int)$stmt->fetchColumn();

// Validar que coincida con la moneda de la transacción
if ((int)$currency_id !== $account_currency) {
    $_SESSION['error'] = "La moneda de la transacción no coincide con la moneda de la cuenta.";
    header('Location: add.php');
    exit();
}

try {
    // Iniciar transacción (opcional, pero recomendado)
    $pdo->beginTransaction();

    // 1. Insertar el gasto
    $stmt = $pdo->prepare("
        INSERT INTO expenses (date, product_id, amount,currency_id, account_id, company_id, note, paid, paid_at)
        VALUES (?, ?, ?, ?, ?, ?,?,?,?)
    ");
    $stmt->execute([$date, (int)$product_id, $amount,(int)$currency_id, (int)$account_id, $company_id, $note,$paid,$paid_at]);

    // 2. Actualizar el saldo de la cuenta: restar el importe (solo si es paid)
    if ($paid) {
        $new_balance = (float)$account['balance'] - $amount;

        $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, (int)$account_id]);
    }

    // Confirmar cambios
    $pdo->commit();

    $_SESSION['success'] = "Gasto registrado y saldo actualizado.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    // Revertir en caso de error
    $pdo->rollBack();
    error_log("Error saving expense: " . $e->getMessage());
    $_SESSION['error'] = "Error al guardar el gasto. Inténtalo más tarde.";
    header('Location: add.php');
    exit();
}