<?php
// public/index.php

// Incluir funciones auxiliares y, en consecuencia, iniciar la sesión.
require_once __DIR__ . '/../app/helpers/functions.php';

// Obtener la acción a partir de la solicitud (puede provenir de GET o POST).
$action = $_REQUEST['action'] ?? '';

// Enrutar la solicitud según la acción definida.
switch ($action) {

    case 'login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $authController = new AuthController();
        $authController->login();
        break;

    case 'register':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $authController = new AuthController();
        $authController->register();
        break;

    case 'logout':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        $authController = new AuthController();
        $authController->logout();
        break;

    case 'create_project':
        require_once __DIR__ . '/../app/controllers/ProjectController.php';
        $projectController = new ProjectController();
        $projectController->createProject();
        break;

    case 'select_project':
        require_once __DIR__ . '/../app/controllers/ProjectController.php';
        $projectController = new ProjectController();
        $projectController->selectProject();
        break;

    case 'save_classes':
        // Este endpoint es consumido vía AJAX para guardar los datos de las clases.
        require_once __DIR__ . '/../app/controllers/ProjectController.php';
        $projectController = new ProjectController();
        $projectController->saveClasses();
        break;

    case 'load_classes':
        // Este endpoint es consumido vía AJAX para obtener los datos de las clases.
        require_once __DIR__ . '/../app/controllers/ProjectController.php';
        $projectController = new ProjectController();
        $projectController->loadClasses();
        break;

    default:
        // Si el usuario ya está autenticado, mostrar el dashboard.
        if (logged_in_user_id()) {
            require_once __DIR__ . '/../app/views/dashboard.php';
        } else {
            // De lo contrario, mostrar la página de login.
            require_once __DIR__ . '/../app/views/login.php';
        }
        break;
}