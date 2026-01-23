<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit();
}

$date = $_POST['date'] ?? '';
$from_account_id = $_POST['from_account_id'] ?? null;
$to_account_id = $_POST['to_account_id'] ?? null;
$product_id = $_POST['product_id'] ?? null;
$note = trim($_POST['note'] ?? '');

// Validar cuentas distintas
if ($from_account_id == $to_account_id) {
    $_SESSION['error'] = "Las cuentas de origen y destino deben ser distintas.";
    header('Location: add.php');
    exit();
}

// Obtener datos de las cuentas
$stmt = $pdo->prepare("SELECT id, balance, currency_id FROM accounts WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$from_account_id, $_SESSION['company_id']]);
$from_account = $stmt->fetch();

$stmt = $pdo->prepare("SELECT id, balance, currency_id FROM accounts WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$to_account_id, $_SESSION['company_id']]);
$to_account = $stmt->fetch();

if (!$from_account || !$to_account) {
    $_SESSION['error'] = "Cuentas no válidas.";
    header('Location: add.php');
    exit();
}

$same_currency = $from_account['currency_id'] == $to_account['currency_id'];

if ($same_currency) {
    $amount = $_POST['amount'] ?? '';
    if (!$amount) {
        $_SESSION['error'] = "Importe es obligatorio.";
        header('Location: add.php');
        exit();
    }
    $amount_origin = $amount_destination = (float)$amount;
} else {
    $amount_origin = $_POST['amount_origin'] ?? '';
    $amount_destination = $_POST['amount_destination'] ?? '';
    if (!$amount_origin || !$amount_destination) {
        $_SESSION['error'] = "Ambos importes son obligatorios.";
        header('Location: add.php');
        exit();
    }
    $amount_origin = (float)$amount_origin;
    $amount_destination = (float)$amount_destination;
}

// Validar motivo (si se proporciona)
if ($product_id) {
    $stmt = $pdo->prepare("
        SELECT p.id 
        FROM products p
        INNER JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.company_id = ? AND c.type = 'transfer'
    ");
    $stmt->execute([(int)$product_id, $_SESSION['company_id']]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Motivo de transferencia no válido.";
        header('Location: add.php');
        exit();
    }
}

try {
    $pdo->beginTransaction();

    //1. Insertar transferencia
    $stmt = $pdo->prepare("
        INSERT INTO transfers (
            date, from_account_id, to_account_id, 
            amount_from, amount_to, 
            currency_from_id, currency_to_id,
            product_id, note, company_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $date,
        (int)$from_account_id,
        (int)$to_account_id,
        $amount_origin,
        $amount_destination,
        (int)$from_account['currency_id'],
        (int)$to_account['currency_id'],
        $product_id ? (int)$product_id : null,
        $note,
        $_SESSION['company_id']
    ]);

    $transfer_id = $pdo->lastInsertId();

    // 2. Insertar gasto (salida)
    $stmt = $pdo->prepare("
        INSERT INTO expenses (
            date, product_id, amount, currency_id, 
            account_id, company_id, note, 
            is_transfer, transfer_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
    ");
    // Nota para gasto
    $note_out = $note ? "Transferencia: $note" : "Transferencia saliente";
    $stmt->execute([
        $date,
        $product_id ? (int)$product_id : null,
        $amount_origin,
        (int)$from_account['currency_id'],
        (int)$from_account_id,
        $_SESSION['company_id'],
        $note_out,
        $transfer_id
    ]);

    // 3. Insertar ingreso (entrada)
    $stmt = $pdo->prepare("
        INSERT INTO incomes (
            date, amount, product_id, 
            account_id, company_id, note, 
            is_transfer, transfer_id
        ) VALUES (?, ?, ?, ?, ?, ?, 1, ?)
    ");
    // Obtener categoría "Transferencias" para ingresos
    $stmt_cat = $pdo->prepare("
        SELECT id FROM categories 
        WHERE name = 'Transferencias' AND company_id = ? AND type = 'transfer'
    ");
    $stmt_cat->execute([$_SESSION['company_id']]);
    $transfer_category = $stmt_cat->fetch();
    $category_id = $transfer_category ? $transfer_category['id'] : null;

    $note_in = $note ? "Transferencia: $note" : "Transferencia entrante";
    $stmt->execute([
        $date,
        $amount_destination,
        $product_id ? (int)$product_id : null,
        (int)$to_account_id,
        $_SESSION['company_id'],
        $note_in,
        $transfer_id
    ]);

    // 4. Actualizar saldos
    $new_from_balance = (float)$from_account['balance'] - $amount_origin;
    $new_to_balance = (float)$to_account['balance'] + $amount_destination;

    $stmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
    $stmt->execute([$new_from_balance, (int)$from_account_id]);
    $stmt->execute([$new_to_balance, (int)$to_account_id]);

    $pdo->commit();

    $_SESSION['success'] = "Transferencia registrada con éxito.";
    header('Location: ../dashboard.php');
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Transfer error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: add.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Transfer DB error: " . $e->getMessage());
    $_SESSION['error'] = "Error al registrar la transferencia.";
    header('Location: add.php');
    exit();
}