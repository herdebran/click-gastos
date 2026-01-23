<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$pdo = getDatabaseConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Nueva Categoría – GastosApp</title>
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
        <h1 class="text-xl font-bold text-gray-900 mb-6">Nueva Categoría</h1>

        <form method="POST" action="save.php">
            <!-- Nombre -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">Nombre de la categoría</label>
                <input type="text" name="name" required
                       placeholder="Ej: Alimentación, Transporte..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <!-- Descripción (opcional) -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">Descripción (opcional)</label>
                <textarea name="description"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                          rows="2" placeholder="Breve descripción..."></textarea>
            </div>

            <!-- Activa -->
            <div class="mb-6 flex items-center">
                <input type="checkbox" name="active" id="active" checked
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="active" class="ml-2 block text-sm text-gray-700">
                    Categoría activa
                </label>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
                <a href="list.php" class="btn-gray px-4 py-2 rounded-md text-sm font-medium">Cancelar</a>
                <button type="submit" class="btn-blue text-white px-4 py-2 rounded-md text-sm font-medium">Guardar Categoría</button>
            </div>
        </form>
    </div>
</main>

</body>
</html>