<?php
/**
 * app/controllers/AuthController.php
 *
 * Controlador para la autenticación (login, registro y logout).
 */
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {

    protected $db;

    public function __construct() {
        // Se obtiene la conexión a la base de datos usando getDBConnection()
        $this->db = getDBConnection();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = User::findByUsername($this->db, $username);
            if ($user && password_verify($password, $user->password)) {
                $_SESSION['user_id'] = $user->id;
                set_flash_message("Bienvenido, " . $user->name, "success");
                redirect('index.php');  // Redirige a index.php, que actúa como front controller
            } else {
                set_flash_message("Usuario o contraseña incorrectos", "error");
                redirect('login.php');
            }
        } else {
            require_once __DIR__ . '/../views/login.php';
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name            = trim($_POST['name'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $username        = trim($_POST['username'] ?? '');
            $password        = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($name) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
                set_flash_message("Todos los campos son obligatorios", "error");
                redirect('register.php');
            }

            if ($password !== $confirmPassword) {
                set_flash_message("Las contraseñas no coinciden", "error");
                redirect('register.php');
            }

            if (User::findByUsername($this->db, $username)) {
                set_flash_message("El nombre de usuario ya existe", "error");
                redirect('register.php');
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insertar el usuario en la base de datos
            try {
                $stmt = $this->db->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $username, $hashedPassword]);
                set_flash_message("Registro exitoso. Ahora puedes iniciar sesión.", "success");
                redirect('login.php');
            } catch (PDOException $e) {
                set_flash_message("Error al registrar el usuario: " . $e->getMessage(), "error");
                redirect('register.php');
            }
        } else {
            require_once __DIR__ . '/../views/register.php';
        }
    }

    public function logout() {
        session_destroy();
        redirect('login.php');
    }
}