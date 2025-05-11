<?php
// public/index.php

// Habilitar reporte de errores para ambiente de desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir funciones auxiliares (se inicia la sesión y se definen funciones de ayuda)
require_once __DIR__ . '/../app/helpers/functions.php';

// Obtener la acción desde GET o POST
$action = $_REQUEST['action'] ?? '';

// Incluir de forma centralizada los controladores necesarios
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/ProjectController.php';

// Enrutar la solicitud según la acción definida
switch ($action) {

    case 'login':
        $authController = new AuthController();
        $authController->login();
        break;

    case 'register':
        $authController = new AuthController();
        $authController->register();
        break;

    case 'logout':
        $authController = new AuthController();
        $authController->logout();
        break;

    case 'create_project':
        $projectController = new ProjectController();
        $projectController->createProject();
        break;

    case 'select_project':
        $projectController = new ProjectController();
        $projectController->selectProject();
        break;

    case 'save_classes':
        // Endpoint consumido vía AJAX para guardar datos de clases
        $projectController = new ProjectController();
        $projectController->saveClasses();
        break;

    case 'load_classes':
        // Endpoint consumido vía AJAX para cargar datos de clases
        $projectController = new ProjectController();
        $projectController->loadClasses();
        break;

    default:
        // Por defecto, si el usuario ya ha iniciado sesión, se muestra el dashboard;
        // de lo contrario se muestra la vista de login.
        if (logged_in_user_id()) {
            require_once __DIR__ . '/../app/views/dashboard.php';
        } else {
            require_once __DIR__ . '/../app/views/login.php';
        }
        break;
}