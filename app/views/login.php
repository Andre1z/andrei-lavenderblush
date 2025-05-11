<?php
// app/views/login.php

// Inicia la sesión si aún no lo está.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones auxiliares para obtener mensajes flash (esto asume que functions.php ya está en el path adecuado)
require_once __DIR__ . '/../helpers/functions.php';
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - andrei-lavenderblush</title>
    <!-- Enlace a la hoja de estilos específica para la autenticación -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="login-box">
        <h1>andrei-lavenderblush - Login</h1>
        <?php if ($flash): ?>
            <div class="flash-message <?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="/public/index.php">
            <!-- Indica la acción para que el controlador sepa que debe procesar el login -->
            <input type="hidden" name="action" value="login">
            <label for="username">Usuario</label>
            <input type="text" name="username" id="username" placeholder="Introduce tu usuario" required>
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" placeholder="Introduce tu contraseña" required>
            <button type="submit">Iniciar sesión</button>
        </form>
        <p>¿No tienes cuenta? <a href="../app/views/register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>