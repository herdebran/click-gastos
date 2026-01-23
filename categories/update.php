<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$id || !$name) {
    $_SESSION['error'] = "El nombre de la categoría es obligatorio.";
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}

// Verificar que la categoría pertenezca a la empresa actual
$stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND company_id = ?");
$stmt->execute([(int)$id, $_SESSION['company_id']]);
$exists = $stmt->fetch();

if (!$exists) {
    $_SESSION['error'] = "Categoría no encontrada.";
    header('Location: list.php');
    exit();
}

try {
    $active = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare("
    UPDATE categories 
    SET name = ?, description = ?, active = ?
    WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([
        $name,
        $description,
        $active,
        (int)$id,
        $_SESSION['company_id']
    ]);

    $_SESSION['success'] = "Categoría actualizada con éxito.";
    header('Location: list.php');
    exit();
} catch (PDOException $e) {
    // Si hay violación de clave única (nombre duplicado)
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = "Ya existe una categoría con ese nombre.";
    } else {
        error_log("Category update error: " . $e->getMessage());
        $_SESSION['error'] = "Error al actualizar la categoría.";
    }
    header('Location: edit.php?id=' . urlencode($id));
    exit();
}