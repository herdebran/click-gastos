<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$pdo = getDatabaseConnection();
$page_title = "Dashboard";
$company_id = $_SESSION['company_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Mes y año actual
$month = date('m');
$year = date('Y');

// 1. Ingresos del mes actual
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) 
    FROM incomes 
    WHERE company_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
");
$stmt->execute([$company_id, $month, $year]);
$income_month = (float)$stmt->fetchColumn();

// 2. Gastos del mes actual
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) 
    FROM expenses 
    WHERE company_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
");
$stmt->execute([$company_id, $month, $year]);
$expense_month = (float)$stmt->fetchColumn();

// 3. Saldo del mes = ingresos - gastos
$balance_month = $income_month - $expense_month;

// 4. Top 5 categorías de gasto del mes
$stmt = $pdo->prepare("
    SELECT 
        c.name AS category_name,
        SUM(e.amount) AS total
    FROM expenses e
    INNER JOIN products p ON e.product_id = p.id
    INNER JOIN categories c ON p.category_id = c.id
    WHERE 
        e.company_id = ? 
        AND MONTH(e.date) = ? 
        AND YEAR(e.date) = ?
    GROUP BY c.id, c.name
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$company_id, $month, $year]);
$top_categories = $stmt->fetchAll();

// CONVERTIR LOS MONTOS A FLOAT
$top_categories = array_map(function($item) {
    $item['total'] = (float)$item['total'];
    return $item;
}, $top_categories);

// Traducción de meses a español
$meses_es = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

$mes_actual = date('m');
$nombre_mes = $meses_es[$mes_actual];
$anio_actual = date('Y');

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard – GastosApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .card { transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.1); }
        .btn-blue { background-color: #3b82f6; }
        .btn-blue:hover { background-color: #2563eb; }
        .badge-green { background-color: #dcfce7; color: #166534; }
        .badge-red { background-color: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body class="bg-gray-50">

<main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    <!-- Welcome -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">¡Bienvenido, <?php echo htmlspecialchars($username); ?>!</h1>
        <p class="text-gray-600 mt-1">Resumen financiero de <?php echo $nombre_mes . ' ' . date('Y'); ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Ingresos -->
        <div class="bg-white rounded-xl shadow-sm p-6 card">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 10a5 5 0 110-10 5 5 0 010 10z"></path>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-600">Ingresos Mes</p>
                    <p class="text-2xl font-semibold text-gray-900">$<?php echo number_format($income_month, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <!-- Gastos -->
        <div class="bg-white rounded-xl shadow-sm p-6 card">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-600">Gastos Mes</p>
                    <p class="text-2xl font-semibold text-gray-900">$<?php echo number_format($expense_month, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <!-- Saldo -->
        <div class="bg-white rounded-xl shadow-sm p-6 card">
            <div class="flex items-center">
                <div class="flex-shrink-0 <?php echo $balance_month >= 0 ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full p-3">
                    <svg class="h-6 w-6 <?php echo $balance_month >= 0 ? 'text-green-600' : 'text-red-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-600">Saldo Mes</p>
                    <p class="text-2xl font-semibold <?php echo $balance_month >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        $<?php echo number_format($balance_month, 2, ',', '.'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de distribución de gastos por categoría -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribución de gastos</h2>
        <?php if (empty($top_categories)): ?>
            <div class="text-center py-8 text-gray-500">
                <p>No hay gastos registrados este mes.</p>
            </div>
        <?php else: ?>
            <div class="h-64">
                <canvas id="categoriesChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($top_categories)): ?>
            const ctx = document.getElementById('categoriesChart').getContext('2d');

            // Datos desde PHP
            const categories = <?php echo json_encode(array_column($top_categories, 'category_name')); ?>;
            const amounts = <?php echo json_encode(array_column($top_categories, 'total')); ?>;

            // Calcular total
            const total = amounts.reduce((a, b) => a + b, 0);

            // Generar etiquetas con porcentaje (solo si total > 0)
            const labelsWithPercent = categories.map((name, i) => {
                if (total > 0 && amounts[i] >= 0) {
                    const percent = Math.round((amounts[i] / total) * 100);
                    return `${name} (${percent}%)`;
                } else {
                    return name; // sin porcentaje si no aplica
                }
            });

            // Colores dinámicos
            const colors = [
                '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444',
                '#ec4899', '#06b6d4', '#f97316'
            ];

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labelsWithPercent, // ← ¡etiquetas con porcentaje!
                    datasets: [{
                        data: amounts,
                        backgroundColor: colors.slice(0, categories.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const percent = Math.round((context.parsed / total) * 100);
                                    return `${context.label.split(' (')[0]}: $${context.parsed.toLocaleString('es-AR', { minimumFractionDigits: 2 })} (${percent}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>

<!-- Cuentas con saldos -->
<?php
    // Obtener cuentas con saldos
    $stmt = $pdo->prepare("
    SELECT a.id, a.name, a.balance, a.active,  c.symbol AS currency_symbol
    FROM accounts a
    INNER JOIN currencies c ON a.currency_id = c.id
    WHERE company_id = ? and active=1
    ORDER BY name
");
    $stmt->execute([$company_id]);
    $accounts = $stmt->fetchAll();
    ?>

    <!-- Cuentas con saldos y moneda -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Cuentas y saldos</h2>
        </div>
        <?php if (empty($accounts)): ?>
            <div class="text-center py-8 text-gray-500">
                <p>No tienes cuentas registradas.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($accounts as $acc):
                    $initial = strtoupper(substr($acc['name'], 0, 1));
                    $is_active = (bool)$acc['active'];
                    $balance = (float)$acc['balance'];
                    $symbol = htmlspecialchars($acc['currency_symbol']);
                    ?>
                    <li class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 <?php echo $is_active ? 'bg-blue-100' : 'bg-gray-100'; ?> rounded-full flex items-center justify-center">
                                <span class="<?php echo $is_active ? 'text-blue-600' : 'text-gray-500'; ?> font-medium"><?php echo $initial; ?></span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($acc['name']); ?></p>
                                <span class="inline-block mt-1 px-2 py-1 rounded text-xs <?php echo $is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
              <?php echo $is_active ? 'Activa' : 'Inactiva'; ?>
            </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium <?php echo $balance >= 0 ? 'text-gray-900' : 'text-red-600'; ?>">
                                <?php echo $symbol; ?><?php echo number_format($balance, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <!-- Botón flotante -->
    <div class="fixed bottom-6 right-6">
        <a href="expenses/add.php" class="inline-flex items-center justify-center h-14 w-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </a>
    </div>

</main>

<?php include 'includes/footer.php';?>