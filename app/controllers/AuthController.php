<?php
// app/controllers/AuthController.php

// Incluir funciones auxiliares y el modelo de usuario.
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {

    protected $db;

    public function __construct() {
        // Se asume que getDBConnection() retorna una instancia PDO.
        $this->db = getDBConnection();
    }

    /**
     * Procesa el inicio de sesión.
     * Si es un POST valida las credenciales; en otro caso carga la vista de login.
     */
    public function login() {
        // Asegurarse de que la sesión esté iniciada.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            // Se usa el modelo User para buscar el usuario por nombre de usuario.
            $user = User::findByUsername($this->db, $username);

            // Verificar existencia del usuario y validar contraseña usando notación de objeto.
            if ($user && password_verify($password, $user->password)) {
                $_SESSION['user_id'] = $user->id;
                set_flash_message("Bienvenido, " . $user->name, "success");
                redirect('dashboard.php');
            } else {
                set_flash_message("Usuario o contraseña incorrectos", "error");
                redirect('login.php');
            }
        } else {
            // Si no es POST, se carga la vista de login.
            require_once __DIR__ . '/../views/login.php';
        }
    }

    /**
     * Procesa el registro de nuevos usuarios.
     * Si es un POST realiza la validación y registro; en otro caso muestra la vista de registro.
     */
    public function register() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name            = trim($_POST['name'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $username        = trim($_POST['username'] ?? '');
            $password        = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validación básica de campos vacíos.
            if (empty($name) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
                set_flash_message("Todos los campos son obligatorios", "error");
                redirect('register.php');
            }

            if ($password !== $confirmPassword) {
                set_flash_message("Las contraseñas no coinciden", "error");
                redirect('register.php');
            }

            // Verificar que el nombre de usuario no exista ya.
            if (User::findByUsername($this->db, $username)) {
                set_flash_message("El nombre de usuario ya existe", "error");
                redirect('register.php');
            }

            // Encriptar la contraseña.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Crear el arreglo con los datos del nuevo usuario.
            $newUser = [
                'name'     => $name,
                'email'    => $email,
                'username' => $username,
                'password' => $hashedPassword
            ];

            // Utilizamos el método create del modelo User para insertar el usuario en la BD.
            if (User::create($this->db, $newUser)) {
                set_flash_message("Registro exitoso. Ahora puedes iniciar sesión.", "success");
                redirect('login.php');
            } else {
                set_flash_message("Error al registrar el usuario. Inténtalo nuevamente.", "error");
                redirect('register.php');
            }
        } else {
            // Si no es POST, se muestra la vista de registro.
            require_once __DIR__ . '/../views/register.php';
        }
    }

    /**
     * Procesa la salida (logout) del usuario.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Destruir la sesión y redirigir a la página de login.
        session_destroy();
        redirect('login.php');
    }
}