<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Iniciar Sesión – GastosApp</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
        }
        .btn-primary {
            background-color: #3b82f6;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 10a5 5 0 110-10 5 5 0 010 10z"></path>
            </svg>
        </div>

        <h2 class="text-center text-2xl font-bold text-gray-900 mb-2">Iniciar Sesión</h2>
        <p class="text-center text-sm text-gray-600 mb-6">
            Ingresa tus credenciales para acceder a tu gestión de gastos
        </p>

        <div class="bg-white p-6 shadow-lg rounded-xl">

            <?php
            session_start();
            $error = $_SESSION['login_error'] ?? '';
            if ($error):
                unset($_SESSION['login_error']);
                ?>
                <div class="mb-4 bg-red-50 text-red-700 px-3 py-2 rounded-md text-sm">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login-process.php" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Usuario o Email</label>
                    <input id="username" name="username" type="text" required
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input id="password" name="password" type="password" required
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <div>
                    <button type="submit" class="btn-primary w-full py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <span class="text-sm text-gray-500">¿No tienes cuenta?</span>
                    <a href="register.php" class="ml-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                        Crear una cuenta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>