<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$category_id = $_POST['category_id'] ?? null;
$type = $_POST['type'] ?? 'expense'; // retrocompatible

if (!in_array($type, ['expense', 'income', 'transfer'])) {
    $type = 'expense';
}

if (!$name || !$category_id) {
    $_SESSION['error'] = "Nombre y categoría son obligatorios.";
    $redirect = $type === 'income' ? '../income/add.php' : '../expenses/add.php';
    header('Location: add.php?name=' . urlencode($name) . '&income=' . ($type === 'income' ? '1' : ''));
    exit();
}

try {
    // Validar que la categoría pertenezca al usuario y sea del tipo correcto
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE id = ? AND company_id = ? AND type = ?
    ");
    $stmt->execute([(int)$category_id, $_SESSION['company_id'], $type]);
    if (!$stmt->fetch()) {
        throw new Exception("Categoría no válida.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO products (name, category_id, company_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$name, (int)$category_id, $_SESSION['company_id']]);

    $_SESSION['success'] = "Producto creado con éxito.";
    $redirect = $type === 'income' ? '../income/add.php' : '../expenses/add.php';
    header('Location: ' . $redirect);
    exit();
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = "Ya existe un producto con ese nombre.";
    } else {
        error_log("Product save error: " . $e->getMessage());
        $_SESSION['error'] = "Error al guardar el producto.";
    }
    $income_param = $type === 'income' ? '&income=1' : '';
    header('Location: add.php?name=' . urlencode($name) . $income_param);
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $income_param = $type === 'income' ? '&income=1' : '';
    header('Location: add.php?name=' . urlencode($name) . $income_param);
    exit();
}