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
$account_id = $_POST['account_id'] ?? null;
$amount = $_POST['amount'] ?? '';
$note = trim($_POST['note'] ?? '');
$currency_id = $_POST['currency_id'] ?? 1;

if (!$date || !$product_id || !$account_id || !$amount) {
    $_SESSION['error'] = "Todos los campos obligatorios deben completarse.";
    header('Location: add.php');
    exit();
}

// Validar producto (debe pertenecer a la empresa )
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$product_id, $company_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "El producto no es válido.";
    header('Location: add.php');
    exit();
}

// Validar cuenta
$stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$account_id, $company_id]);
$account = $stmt->fetch();
if (!$account) {
    $_SESSION['error'] = "Cuenta no válida.";
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
    $pdo->beginTransaction();

    // Insertar ingreso (opcional: crear tabla incomes, o usar transactions más adelante)
    $stmt = $pdo->prepare("
        INSERT INTO incomes (date, amount,currency_id, product_id, account_id, company_id, note)
        VALUES (?, ?, ?, ?, ?, ?,?)
    ");
    $stmt->execute([$date, $amount,(int)$currency_id, (int)$product_id, (int)$account_id, $company_id, $note]);

    // Actualizar saldo: ¡sumar el importe!
    $new_balance = (float)$account['balance'] + $amount;
    $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
    $stmt->execute([$new_balance, (int)$account_id]);

    $pdo->commit();

    $_SESSION['success'] = "Ingreso registrado y saldo actualizado.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error saving income: " . $e->getMessage());
    $_SESSION['error'] = "Error al guardar el ingreso.";
    header('Location: add.php');
    exit();
}