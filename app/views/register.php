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
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="../../public/assets/css/auth.css">
</head>
<body>
    <?php if($flash = get_flash_message()): ?>
        <div class="flash-message <?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <h1>Registro</h1>
    <form action="register.php" method="post">
        <label for="name">Nombre</label>
        <input type="text" name="name" id="name" required>

        <label for="email">Correo Electrónico</label>
        <input type="email" name="email" id="email" required>

        <label for="username">Usuario</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Contraseña</label>
        <input type="password" name="password" id="password" required>

        <label for="confirm_password">Confirmar Contraseña</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <button type="submit">Registrarse</button>
    </form>
    <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar sesión</a></p>
</body>
</html>