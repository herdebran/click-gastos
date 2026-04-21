<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$page_title = "Gastos";
$company_id = $_SESSION['company_id'];

// Fechas por defecto: desde el 1ro del mes hasta hoy
$fecha_hasta_default = date('Y-m-d');
$fecha_desde_default = date('Y-m-01');

// Obtener fechas del formulario (si existen)
$fecha_desde = $_GET['fecha_desde'] ?? $fecha_desde_default;
$fecha_hasta = $_GET['fecha_hasta'] ?? $fecha_hasta_default;

// Validar formato
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = $fecha_desde_default;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = $fecha_hasta_default;

// Asegurar que fecha_hasta no sea mayor a hoy
if ($fecha_hasta > date('Y-m-d')) {
    $fecha_hasta = date('Y-m-d');
}

// Asegurar que fecha_desde no sea mayor a fecha_hasta
if ($fecha_desde > $fecha_hasta) {
    $fecha_desde = $fecha_hasta;
}

// Construir condiciones
$where = "e.company_id = :company_id AND e.is_transfer = 0";
$params = ['company_id' => $company_id];

// Siempre aplicar el rango de fechas (incluso por defecto)
$where .= " AND e.date >= :fecha_desde AND e.date <= :fecha_hasta";
$params['fecha_desde'] = $fecha_desde;
$params['fecha_hasta'] = $fecha_hasta;

// Obtener gastos con JOINs para producto, categoría y cuenta (con filtro de fechas)
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.date,
        e.amount,
        e.note,
        p.name AS product_name,
        c.name AS category_name,
        a.name AS account_name,
        curr.symbol
    FROM expenses e
    INNER JOIN products p ON e.product_id = p.id
    INNER JOIN categories c ON p.category_id = c.id
    INNER JOIN accounts a ON e.account_id = a.id
    INNER JOIN currencies curr ON e.currency_id = curr.id
    WHERE $where
    ORDER BY e.date DESC, e.created_at DESC
");
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de gastos y total gastado
$total_count = count($expenses);
$total_amount = array_sum(array_column($expenses, 'amount'));


// Exportar a Excel si se solicita

// Función para quitar acentos
function removeAccents($str)
{
    return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
}

// Exportar a CSV si se solicita
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'gastos_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=iso-8859-1');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Escribir encabezado SIN ACENTOS y con ;
    fputcsv($output, [
        removeAccents('Fecha'),
        removeAccents('Categoria'),
        removeAccents('Producto'),
        removeAccents('Descripcion'),
        removeAccents('Monto')
    ], ';');

    foreach ($expenses as $e) {
        fputcsv($output, [
            $e['date'],
            removeAccents($e['category_name']),
            removeAccents($e['product_name']),
            removeAccents($e['description'] ?? ''),
            number_format((float)$e['amount'], 2, ',', '') // Formato 1.234,56
        ], ';');
    }
    fclose($output);
    exit;
}

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Mis Gastos – GastosApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .btn-blue { background-color: #3b82f6; }
        .btn-blue:hover { background-color: #2563eb; }
        .badge-blue { background-color: #dbeafe; color: #1e40af; }
        .badge-green { background-color: #dcfce7; color: #166534; }
        .badge-purple { background-color: #ede9fe; color: #4c1d95; }
    </style>
</head>
<body class="bg-gray-50">

<main x-data="expensesList" class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mis Gastos</h1>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo number_format($total_count, 0, ',', '.'); ?> gastos registrados • Total: $<?php echo number_format($total_amount, 2, ',', '.'); ?>
            </p>
        </div>
        <a href="add.php" class="btn-blue text-white px-4 py-2 rounded-md text-sm font-medium mt-4 md:mt-0">
            + Agregar Gasto
        </a>
    </div>

    <!-- Filtro de fechas -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label for="fecha_desde" class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde"
                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? date('Y-m-01')) ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta"
                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? date('Y-m-d')) ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 h-[42px]">
                    Filtrar
                </button>
            </div>
            <div>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>"
                   class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 h-[42px] inline-flex items-center">
                    Exportar a Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de gastos -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <?php if (empty($expenses)): ?>
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2">No tienes gastos registrados.</p>
                <p class="mt-1">¡Empieza a agregar tus primeros gastos!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuenta</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Importe</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($e['date']); ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($e['product_name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <span class="badge-blue px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($e['category_name']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <span class="badge-purple px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($e['account_name']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right text-gray-900"><?php echo htmlspecialchars($e['symbol']); ?><?php echo number_format($e['amount'], 2, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button"
                                        @click="openDeleteModal(<?php echo (int)$e['id']; ?>, '<?php echo htmlspecialchars($e['account_name']); ?>', <?php echo $e['amount']; ?>)"
                                        class="text-red-600 hover:text-red-900">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
    <!-- Modal de confirmación de eliminación -->
    <div x-show="deleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="deleteModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Eliminar Gasto</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                ¿Estás seguro de eliminar el gasto de <strong>$<span x-text="deleteAmount"></span></strong> en <strong x-text="deleteAccountName"></strong>?
                            </p>
                            <div class="mt-4 flex items-center">
                                <input type="checkbox" id="restoreBalance" x-model="restoreBalance" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="restoreBalance" class="ml-2 block text-sm text-gray-700">
                                    Devolver el importe a la cuenta
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form :action="'delete.php?id=' + deleteExpenseId + '&restore=' + (restoreBalance ? '1' : '0')" method="POST" class="inline">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Eliminar
                        </button>
                    </form>
                    <button type="button" @click="deleteModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('expensesList', () => ({
                deleteModalOpen: false,
                deleteExpenseId: null,
                deleteAccountName: '',
                deleteAmount: 0,
                restoreBalance: true,

                openDeleteModal(id, accountName, amount) {
                    this.deleteExpenseId = id;
                    this.deleteAccountName = accountName;
                    this.deleteAmount = parseFloat(amount).toFixed(2);
                    this.restoreBalance = true; // por defecto marcado
                    this.deleteModalOpen = true;
                }
            }));
        });
    </script>

</main>

<?php include '../includes/footer.php';?>