<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$active = isset($_POST['active']) ? 1 : 0;

if (!$name) {
    $_SESSION['error'] = "El nombre de la categoría es obligatorio.";
    header('Location: add.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO categories (name, description, active, company_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $description,
        $active,
        $_SESSION['company_id']
    ]);

    $_SESSION['success'] = "Categoría creada con éxito.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    // Si hay violación de clave única (nombre duplicado)
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = "Ya existe una categoría con ese nombre.";
    } else {
        error_log("Category save error: " . $e->getMessage());
        $_SESSION['error'] = "Error al guardar la categoría.";
    }
    header('Location: add.php');
    exit();
}