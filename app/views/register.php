<?php
// app/views/register.php

// Iniciar la sesión si aún no se ha iniciado.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones auxiliares para manejar mensajes flash.
require_once __DIR__ . '/../helpers/functions.php';
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - andrei-lavenderblush</title>
    <!-- Enlace a la hoja de estilos para la autenticación -->
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="register-box">
        <h1>andrei-lavenderblush - Registro</h1>
        <?php if ($flash): ?>
            <div class="flash-message <?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="/public/index.php">
            <!-- Indica la acción para que el AuthController procese el registro -->
            <input type="hidden" name="action" value="register">
            
            <label for="name">Nombre completo</label>
            <input type="text" name="name" id="name" placeholder="Introduce tu nombre completo" required>
            
            <label for="email">Correo electrónico</label>
            <input type="email" name="email" id="email" placeholder="Introduce tu correo" required>
            
            <label for="username">Usuario</label>
            <input type="text" name="username" id="username" placeholder="Elige un nombre de usuario" required>
            
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" placeholder="Crea una contraseña" required>
            
            <label for="confirm_password">Confirmar contraseña</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirma la contraseña" required>
            
            <button type="submit">Registrarse</button>
        </form>
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>