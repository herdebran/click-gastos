<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$page_title = "Categorias";
$company_id = $_SESSION['company_id'];

// Obtener categorías con conteo de productos asociados
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.name,
        c.description,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.company_id = ?
    WHERE c.company_id = ?
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name
");
$stmt->execute([$company_id, $company_id]);
$categories = $stmt->fetchAll();

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Categorías – GastosApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .btn-blue { background-color: #3b82f6; }
        .btn-blue:hover { background-color: #2563eb; }
        .badge-blue { background-color: #dbeafe; color: #1e40af; }
    </style>
</head>
<body class="bg-gray-50">

<main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Categorías</h1>
        <a href="add.php" class="btn-blue text-white px-4 py-2 rounded-md text-sm font-medium mt-4 md:mt-0">
            + Nueva Categoría
        </a>
    </div>

    <!-- Lista de categorías -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <?php if (empty($categories)): ?>
            <div class="text-center py-12 text-gray-500">
                <p>No tienes categorías registradas.</p>
                <p class="mt-1">Crea tu primera categoría para organizar tus gastos.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($categories as $cat):
                    // Letra inicial para el ícono
                    $initial = strtoupper(substr($cat['name'], 0, 1));
                    ?>
                    <li class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-medium"><?php echo htmlspecialchars($initial); ?></span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cat['name']); ?></p>
                                <?php if (!empty($cat['description'])): ?>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($cat['description']); ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo $cat['product_count']; ?> producto(s) asignado(s)
                                </p>
                            </div>
                        </div>
                        <div>
                            <a href="edit.php?id=<?php echo (int)$cat['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Editar</a>
                            <a href="#" class="text-red-600 hover:text-red-900">Eliminar</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Botón flotante -->
    <div class="fixed bottom-6 right-6">
        <a href="add.php" class="inline-flex items-center justify-center h-14 w-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </a>
    </div>

</main>

<?php include '../includes/footer.php';?>