<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
$pdo = getDatabaseConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Leer el cuerpo JSON correctamente
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id']) || !isset($input['category_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$product_id = (int)$input['product_id'];
$category_id = (int)$input['category_id'];

try {
    // Validar que el producto exista y pertenezca a la empresa
    $stmt = $pdo->prepare("
        SELECT p.id 
        FROM products p
        WHERE p.id = ? AND p.company_id = ?
    ");
    $stmt->execute([$product_id, $_SESSION['company_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Producto no encontrado.");
    }

    // Validar categoría destino
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND company_id = ?");
    $stmt->execute([$category_id, $_SESSION['company_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Categoría no válida.");
    }

    // Actualizar la categoría del producto
    $stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE id = ?");
    $stmt->execute([$category_id, $product_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}