
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
$page_title = "Gestionar Productos";

$pdo = getDatabaseConnection();

// Cargar categorías y productos
$stmt = $pdo->prepare("
    SELECT 
        c.id as category_id,
        c.name as category_name,
        c.type,
        p.id as product_id,
        p.name as product_name
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND c.company_id = p.company_id
    WHERE c.company_id = ?
    ORDER BY c.type, c.name, p.name
");
$stmt->execute([$_SESSION['company_id']]);
$categories = [];
while ($row = $stmt->fetch()) {
    $cat_id = $row['category_id'];
    if (!isset($categories[$cat_id])) {
        $categories[$cat_id] = [
            'id' => $row['category_id'],
            'name' => $row['category_name'],
            'type' => $row['type'],
            'products' => []
        ];
    }
    if ($row['product_id']) {
        $categories[$cat_id]['products'][] = [
            'id' => $row['product_id'],
            'name' => $row['product_name']
        ];
    }
}

// Agrupar por tipo
$types = [
    'expense' => ['label' => 'Gastos', 'color' => 'bg-blue-50', 'border' => 'border-blue-200'],
    'income' => ['label' => 'Ingresos', 'color' => 'bg-green-50', 'border' => 'border-green-200'],
    'transfer' => ['label' => 'Transferencias', 'color' => 'bg-purple-50', 'border' => 'border-purple-200']
];
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title><?php echo $page_title; ?> – GastosApp</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Poppins', sans-serif; }
            .dragging { opacity: 0.5; }
            .dropzone { background-color: #dbeafe !important; border-color: #3b82f6 !important; }
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                padding: 1rem;
                border-radius: 0.5rem;
                color: white;
                font-weight: 500;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                transform: translateX(120%);
                transition: transform 0.3s ease-out;
            }
            .notification.show {
                transform: translateX(0);
            }
            .success { background-color: #10b981; }
            .error { background-color: #ef4444; }
        </style>
    </head>
<body class="bg-gray-50">

<?php include '../includes/header.php'; ?>

    <!-- Notificaciones -->
    <div id="notification" class="notification"></div>

    <div class="max-w-4xl mx-auto py-6 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gestión de Productos</h1>
            <a href="add.php" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                + Nueva Categoría
            </a>
        </div>

        <?php foreach ($types as $type_key => $type_config):
            $type_categories = array_filter($categories, fn($c) => $c['type'] === $type_key);
            if (empty($type_categories)) continue;
            ?>
            <div class="<?php echo $type_config['color']; ?> rounded-xl p-4 mb-6 border <?php echo $type_config['border']; ?>">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2"><?php echo htmlspecialchars($type_config['label']); ?></span>
                </h2>

                <div class="space-y-4">
                    <?php foreach ($type_categories as $cat): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-4 py-3">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($cat['name']); ?></h3>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
              <?php echo count($cat['products']); ?>
            </span>
                                </div>

                                <!-- Formulario nuevo producto -->
                                <form method="POST" action="save-product.php" class="mt-3 flex space-x-2">
                                    <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                                    <input type="text" name="name" placeholder="Nuevo producto..."
                                           class="flex-1 text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                           required>
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-bold">+</button>
                                </form>
                            </div>

                            <!-- Área de productos (siempre visible, incluso si está vacía) -->
                            <ul id="category-<?php echo (int)$cat['id']; ?>"
                                class="divide-y divide-gray-100 min-h-[60px]"
                                data-category-id="<?php echo (int)$cat['id']; ?>">
                                <?php if (empty($cat['products'])): ?>
                                    <li class="px-4 py-2 text-sm text-gray-400 italic">
                                        Arrastra productos aquí
                                    </li>
                                <?php else: ?>
                                    <?php foreach ($cat['products'] as $prod): ?>
                                        <li draggable="true"
                                            data-product-id="<?php echo (int)$prod['id']; ?>"
                                            class="px-4 py-2 text-sm text-gray-700 cursor-move hover:bg-gray-50"
                                            title="Arrastra a otra categoría">
                                            <?php echo htmlspecialchars($prod['name']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('notification');
            let draggedItem = null;

            // Mostrar notificación
            function showNotification(message, isSuccess = true) {
                notification.textContent = message;
                notification.className = `notification ${isSuccess ? 'success' : 'error'} show`;
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }

            // Drag & Drop
            document.querySelectorAll('[draggable="true"]').forEach(item => {
                item.addEventListener('dragstart', function() {
                    draggedItem = this;
                    this.classList.add('dragging');
                });
                item.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                });
            });

            document.querySelectorAll('[data-category-id]').forEach(zone => {
                zone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dropzone');
                });

                zone.addEventListener('dragleave', function() {
                    this.classList.remove('dropzone');
                });

                zone.addEventListener('drop', async function(e) {
                    e.preventDefault();
                    this.classList.remove('dropzone');

                    if (!draggedItem) return;

                    const productId = draggedItem.dataset.productId;
                    const newCategoryId = this.dataset.categoryId;
                    const oldCategoryId = draggedItem.closest('[data-category-id]').dataset.categoryId;

                    if (oldCategoryId === newCategoryId) return;

                    try {
                        const response = await fetch('move-product.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                product_id: parseInt(productId),
                                category_id: parseInt(newCategoryId)
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            // Mover visualmente
                            const placeholder = this.querySelector('.text-gray-400');
                            if (placeholder) {
                                placeholder.remove();
                            }
                            this.appendChild(draggedItem);
                            showNotification('Producto movido correctamente');
                        } else {
                            throw new Error(result.error || 'Error al mover');
                        }
                    } catch (error) {
                        console.error('Move error:', error);
                        showNotification('Error: ' + error.message, false);
                    }
                });
            });
        });
    </script>

<?php include '../includes/footer.php'; ?>