<?php
// app/views/templates/header.php
// Se asume que la sesión ya ha sido iniciada en un archivo de nivel superior o mediante autoload.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'andrei-lavenderblush'; ?></title>
    <!-- Enlace a los estilos específicos para el header y estilos globales -->
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Puedes agregar otros meta tags o enlaces a CSS adicionales -->
</head>
<body>
<header class="header">
    <div class="header-container">
        <div class="logo-container">
            <a href="dashboard.php">
                <img src="/assets/images/lavenderblush.png" alt="Logo andrei-lavenderblush" class="logo">
            </a>
        </div>
        <div class="title-container">
            <h1>andrei-lavenderblush</h1>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="dashboard.php">Inicio</a></li>
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <li><a href="logout.php">Cerrar sesión</a></li>
                <?php else: ?>
                    <li><a href="login.php">Iniciar sesión</a></li>
                    <li><a href="register.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>