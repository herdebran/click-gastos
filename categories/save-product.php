<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$category_id = $_POST['category_id'] ?? null;

if (!$name || !$category_id) {
    $_SESSION['error'] = "Nombre y categoría son obligatorios.";
    header('Location: manage.php');
    exit();
}

try {
    // Validar categoría
    $stmt = $pdo->prepare("SELECT id, type FROM categories WHERE id = ? AND company_id = ?");
    $stmt->execute([(int)$category_id, $_SESSION['company_id']]);
    $category = $stmt->fetch();

    if (!$category) {
        throw new Exception("Categoría no válida.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO products (name, category_id, company_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$name, (int)$category_id, $_SESSION['company_id']]);

    $_SESSION['success'] = "Producto creado con éxito.";
    header('Location: manage.php');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: manage.php');
    exit();
}