<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$company_id = $_SESSION['company_id'];

// ¿Es para transferencia?
$is_transfer = isset($_GET['transfer']) && $_GET['transfer'] == '1';

// Nombre prellenado
$pre_name = $_GET['name'] ?? '';

if ($is_transfer) {
    // Forzar tipo 'transfer'
    $category_type = 'transfer';

    // Buscar o crear categoría "Transferencias"
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Transferencias' AND company_id = ? AND type = 'transfer'");
    $stmt->execute([$company_id]);
    $cat = $stmt->fetch();

    if (!$cat) {
        // Crear la categoría si no existe
        $stmt = $pdo->prepare("INSERT INTO categories (name, type, active, company_id) VALUES ('Transferencias', 'transfer', 1, ?)");
        $stmt->execute([$company_id]);
        $category_id = $pdo->lastInsertId();
    } else {
        $category_id = $cat['id'];
    }
} else {
    // Comportamiento normal (gastos/ingresos)
    $is_income = isset($_GET['income']) && $_GET['income'] == '1';
    $category_type = $is_income ? 'income' : 'expense';

    // Cargar categorías del tipo correspondiente
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE company_id = ? AND type = ? ORDER BY name");
    $stmt->execute([$company_id, $category_type]);
    $categories = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Nuevo Producto – GastosApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .btn-blue { background-color: #3b82f6; }
        .btn-blue:hover { background-color: #2563eb; }
        .btn-gray { background-color: #e5e7eb; color: #374151; }
        .btn-gray:hover { background-color: #d1d5db; }
    </style>
</head>
<body class="bg-gray-50">

<main class="max-w-3xl mx-auto py-8 px-4">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h1 class="text-xl font-bold text-gray-900 mb-6">
            <?php echo $is_transfer ? 'Nuevo Motivo de Transferencia' : 'Nuevo Producto'; ?>
        </h1>

        <form method="POST" action="save.php">
            <!-- Nombre -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">
                    <?php echo $is_transfer ? 'Nombre del motivo' : 'Nombre del producto'; ?>
                </label>
                <input type="text" name="name" required
                       value="<?php echo htmlspecialchars($pre_name); ?>"
                       placeholder="<?php echo $is_transfer ? 'Ej: Ahorro, Pago Proveedor...' : 'Ej: Café, Consultoría...'; ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($category_type); ?>">
            </div>

            <!-- Categoría -->
            <div class="mb-6">
                <?php if ($is_transfer): ?>
                    <!-- Para transferencias: categoría fija -->
                    <input type="hidden" name="category_id" value="<?php echo (int)$category_id; ?>">
                    <p class="text-sm text-gray-600">
                        Categoría: <strong>Transferencias</strong>
                    </p>
                <?php else: ?>
                    <!-- Para gastos/ingresos: selector normal -->
                    <label class="block text-sm font-medium text-gray-700">
                        Categoría de <?php echo $category_type === 'income' ? 'ingreso' : 'gasto'; ?>
                    </label>
                    <?php if (empty($categories)): ?>
                        <p class="text-sm text-red-600 mt-1">
                            No hay categorías disponibles.
                            <a href="../categories/add.php<?php echo $category_type === 'income' ? '?type=income' : ''; ?>" class="text-blue-600">
                                Crea una primero.
                            </a>
                        </p>
                    <?php else: ?>
                        <select name="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
                <?php if ($is_transfer): ?>
                    <a href="../transfers/add.php" class="btn-gray px-4 py-2 rounded-md text-sm font-medium">Cancelar</a>
                <?php elseif (isset($is_income) && $is_income): ?>
                    <a href="../income/add.php" class="btn-gray px-4 py-2 rounded-md text-sm font-medium">Cancelar</a>
                <?php else: ?>
                    <a href="../expenses/add.php" class="btn-gray px-4 py-2 rounded-md text-sm font-medium">Cancelar</a>
                <?php endif; ?>
                <button type="submit" class="btn-blue text-white px-4 py-2 rounded-md text-sm font-medium">
                    <?php echo $is_transfer ? 'Guardar Motivo' : 'Guardar Producto'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

</body>
</html>