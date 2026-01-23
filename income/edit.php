<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$company_id = $_SESSION['company_id'];

$income_id = $_GET['id'] ?? null;
if (!$income_id || !is_numeric($income_id)) {
    header('Location: list.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT i.id, i.date, i.description, i.amount, i.category_id, i.account_id, i.note,
           c.name AS category_name, a.name AS account_name
    FROM incomes i
    INNER JOIN categories c ON i.category_id = c.id
    INNER JOIN accounts a ON i.account_id = a.id
    WHERE i.id = ? AND i.company_id = ?
");
$stmt->execute([(int)$income_id, $company_id]);
$income = $stmt->fetch();

if (!$income) {
    header('Location: list.php');
    exit();
}

// Cargar categorías de ingreso
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE company_id = ? AND type = 'income' ORDER BY name");
$stmt->execute([$company_id]);
$categories = $stmt->fetchAll();

// Cargar cuentas
$stmt = $pdo->prepare("SELECT id, name FROM accounts WHERE company_id = ? AND active = 1 ORDER BY name");
$stmt->execute([$company_id]);
$accounts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Editar Ingreso – GastosApp</title>
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
        <h1 class="text-xl font-bold text-gray-900 mb-6">Editar Ingreso</h1>

        <form method="POST" action="update.php">
            <input type="hidden" name="id" value="<?php echo (int)$income['id']; ?>">

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha</label>
                    <input type="date" name="date" required
                           value="<?php echo htmlspecialchars($income['date']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <input type="text" name="description" required
                           value="<?php echo htmlspecialchars($income['description']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select name="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo $cat['id'] == $income['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Cuenta</label>
                    <select name="account_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo (int)$acc['id']; ?>" <?php echo $acc['id'] == $income['account_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($acc['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Importe</label>
                    <input type="number" step="0.01" name="amount" required min="0.01"
                           value="<?php echo number_format($income['amount'], 2, '.', ''); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Observación (opcional)</label>
                    <textarea name="note" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo htmlspecialchars($income['note']); ?></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="list.php" class="btn-gray px-4 py-2 rounded-md text-sm font-medium">Cancelar</a>
                    <button type="submit" class="btn-blue text-white px-4 py-2 rounded-md text-sm font-medium">Guardar Cambios</button>
                </div>
            </div>
        </form>
    </div>
</main>

</body>
</html>