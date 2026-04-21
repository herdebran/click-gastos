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

  <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- Logo -->
      <div class="flex justify-center">
        <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 10a5 5 0 110-10 5 5 0 010 10z"></path>
        </svg>
      </div>
      <h2 class="mt-6 text-center text-2xl font-bold text-gray-900">Iniciar Sesión</h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Ingresa tus credenciales para acceder a tu gestión de gastos
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow-lg rounded-xl sm:px-10">
        
		<?php
		session_start();
		$error = $_SESSION['login_error'] ?? '';
		if ($error): 
			unset($_SESSION['login_error']); // consumir el mensaje
		?>
		<div class="mb-4 bg-red-50 text-red-700 px-4 py-3 rounded-md text-sm">
			<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
		</div>
		<?php endif; ?>

        <form method="POST" action="login-process.php" class="space-y-6">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Usuario o Email</label>
            <div class="mt-1">
              <input id="username" name="username" type="text" required
                     class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <div class="mt-1">
              <input id="password" name="password" type="password" required
                     class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <!-- Opción "Recordar sesión" opcional -->
              <!--
              <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label for="remember" class="ml-2 block text-sm text-gray-700">Recordarme</label>
              -->
            </div>
            <!-- ¿Olvidaste tu contraseña? (opcional, futuro) -->
            <!-- <div class="text-sm">
              <a href="#" class="font-medium text-blue-600 hover:text-blue-500">¿Olvidaste tu contraseña?</a>
            </div> -->
          </div>

          <div>
            <button type="submit" class="btn-primary w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
              Iniciar Sesión
            </button>
          </div>
        </form>

        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">¿No tienes cuenta?</span>
            </div>
          </div>

          <div class="mt-4">
            <a href="register.php" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
              Crear una cuenta
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>