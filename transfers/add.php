<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
$page_title = "Nueva Transferencia";
?>
<?php
// Cargar cuentas activas
$stmt = $pdo->prepare("SELECT id, name, currency_id FROM accounts WHERE company_id = ? AND active = 1 ORDER BY name");
$stmt->execute([$_SESSION['company_id']]);
$accounts = $stmt->fetchAll();

// Cargar motivos (productos de categoría "Transferencias")
$stmt = $pdo->prepare("
    SELECT p.id, p.name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    WHERE 
        p.company_id = ? 
        AND c.type = 'transfer'
    ORDER BY p.name
");
$stmt->execute([$_SESSION['company_id']]);
$motivos = $stmt->fetchAll();
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title><?php echo $page_title; ?> – GastosApp</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Poppins', sans-serif; }
            .btn-blue { background-color: #3b82f6; }
            .btn-blue:hover { background-color: #2563eb; }
        </style>
    </head>
<body class="bg-gray-50">

<?php include '../includes/header.php'; ?>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-6">Nueva Transferencia</h1>

            <form method="POST" action="save.php" x-data="{
        originAccount: '',
        destinationAccount: '',
        originCurrency: null,
        destinationCurrency: null,
        sameCurrency: false,
        amountOrigin: '',
        amountDestination: '',
        openProductModal: false,
        newProductName: ''
    }" @change="updateCurrency()" x-init="updateCurrency()">
                <div class="space-y-6">
                    <!-- Fecha -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha</label>
                        <input type="date" name="date" required
                               value="<?php echo date('Y-m-d'); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <!-- Cuenta origen -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cuenta de origen</label>
                        <select name="from_account_id" required x-model="originAccount"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Selecciona una cuenta</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo (int)$acc['id']; ?>" data-currency="<?php echo (int)$acc['currency_id']; ?>">
                                    <?php echo htmlspecialchars($acc['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Cuenta destino -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cuenta de destino</label>
                        <select name="to_account_id" required x-model="destinationAccount"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Selecciona una cuenta</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo (int)$acc['id']; ?>" data-currency="<?php echo (int)$acc['currency_id']; ?>">
                                    <?php echo htmlspecialchars($acc['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Importes -->
                    <div x-show="originAccount && destinationAccount">
                        <div x-show="sameCurrency">
                            <label class="block text-sm font-medium text-gray-700">Importe</label>
                            <input type="number" step="0.01" name="amount" required x-model="amountOrigin"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div x-show="!sameCurrency" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Importe (origen)</label>
                                <input type="number" step="0.01" name="amount_origin" required x-model="amountOrigin"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Importe (destino)</label>
                                <input type="number" step="0.01" name="amount_destination" required x-model="amountDestination"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Motivo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Motivo</label>
                        <div class="mt-1 flex">
                            <select name="product_id" required
                                    class="block w-full rounded-l-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Selecciona un motivo</option>
                                <?php foreach ($motivos as $m): ?>
                                    <option value="<?php echo (int)$m['id']; ?>">
                                        <?php echo htmlspecialchars($m['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button"
                                    @click="openProductModal = true"
                                    class="bg-gray-100 border border-l-0 border-gray-300 rounded-r-md px-3 text-gray-600 hover:bg-gray-200 transition">
                                +
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">¿No está? Agrega un nuevo motivo.</p>
                    </div>

                    <!-- Observación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observación (opcional)</label>
                        <textarea name="note" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3">
                        <a href="../dashboard.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md text-sm font-medium">Cancelar</a>
                        <button type="submit" class="btn-blue text-white px-4 py-2 rounded-md text-sm font-medium">Registrar Transferencia</button>
                    </div>
                </div>

                <!-- Modal para nuevo motivo -->
                <div x-show="openProductModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="openProductModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <div x-show="openProductModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Nuevo Motivo de Transferencia</h3>
                                    <div class="mt-4">
                                        <label class="block text-sm text-gray-700 text-left">Nombre del motivo</label>
                                        <input type="text" x-model="newProductName" placeholder="Ej: Ahorro, Pago Proveedor..."
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="button"
                                        @click="openProductModal = false"
                                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:col-start-1 sm:text-sm">
                                    Cancelar
                                </button>
                                <a :href="'../products/add.php?name=' + encodeURIComponent(newProductName) + '&transfer=1'"
                                   x-show="newProductName.trim() !== ''"
                                   class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:col-start-2 sm:text-sm">
                                    Continuar →
                                </a>
                                <button x-show="newProductName.trim() === ''"
                                        disabled
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-400 text-base font-medium text-white sm:col-start-2 sm:text-sm cursor-not-allowed">
                                    Ingresa un nombre
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateCurrency() {
            const originSelect = document.querySelector('select[name="from_account_id"]');
            const destSelect = document.querySelector('select[name="to_account_id"]');

            const originOption = originSelect.options[originSelect.selectedIndex];
            const destOption = destSelect.options[destSelect.selectedIndex];

            this.originCurrency = originOption ? originOption.dataset.currency : null;
            this.destinationCurrency = destOption ? destOption.dataset.currency : null;
            this.sameCurrency = this.originCurrency === this.destinationCurrency;

            if (this.sameCurrency && this.amountOrigin) {
                this.amountDestination = this.amountOrigin;
            }
        }
    </script>

<?php include '../includes/footer.php'; ?>