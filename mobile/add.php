<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
$pdo = getDatabaseConnection();

$page_title = "Nuevo Gasto";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $page_title; ?> – GastosApp</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        .btn-blue { background-color: #3b82f6; }
        .btn-blue:hover { background-color: #2563eb; }
    </style>
</head>
<body class="bg-gray-50">

<div class="max-w-md mx-auto p-4">
    <h1 class="text-xl font-bold text-gray-900 mb-4">Nuevo Gasto</h1>

    <form method="POST" action="../expenses/save.php" class="space-y-4">
        <!-- Fecha -->
        <div>
            <input type="date" name="date" required
                   value="<?php echo date('Y-m-d'); ?>"
                   class="w-full p-3 border border-gray-300 rounded-lg">
        </div>

        <!-- Producto -->
        <div>
            <select name="product_id" required class="w-full p-3 border border-gray-300 rounded-lg">
                <option value="">Selecciona producto</option>
                <?php
                // Cargar productos
                $stmt = $pdo->prepare("SELECT id, name FROM products WHERE company_id = ? ORDER BY name");
                $stmt->execute([$_SESSION['company_id']]);
                while ($p = $stmt->fetch()) {
                    echo '<option value="' . (int)$p['id'] . '">' . htmlspecialchars($p['name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <!-- Importe -->
        <div>
            <input type="number" step="0.01" name="amount" required placeholder="Importe"
                   class="w-full p-3 border border-gray-300 rounded-lg">
        </div>

        <!-- Cuenta -->
        <div>
            <select name="account_id" required class="w-full p-3 border border-gray-300 rounded-lg">
                <?php
                $stmt = $pdo->prepare("SELECT id, name FROM accounts WHERE company_id = ? AND active = 1 ORDER BY name");
                $stmt->execute([$_SESSION['company_id']]);
                while ($a = $stmt->fetch()) {
                    echo '<option value="' . (int)$a['id'] . '">' . htmlspecialchars($a['name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <!-- Botón -->
        <button type="submit" class="btn-blue text-white w-full p-3 rounded-lg font-medium">
            Guardar Gasto
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="../dashboard.php" class="text-blue-600 text-sm">← Volver</a>
    </div>
</div>

<script>
    // Si hay conexión, verifica que la sesión sigue activa
    if (navigator.onLine) {
        fetch('/api/check-session.php')
            .then(response => {
                if (!response.ok) {
                    // Sesión expirada → redirigir a login
                    window.location.href = '/login.php?redirect=/mobile/add.php';
                }
            })
            .catch(() => {
                // Sin conexión → asumir que la sesión sigue válida
            });
    }
</script>

</body>
</html>