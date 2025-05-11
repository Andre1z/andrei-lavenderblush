<?php
/**
 * app/controllers/AuthController.php
 *
 * Controlador que gestiona el flujo de autenticación: iniciar sesión, registrarse y cerrar sesión.
 */

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {

    protected $db;

    public function __construct() {
        // Se obtiene la conexión a la base de datos mediante la función centralizada.
        $this->db = getDBConnection();
    }

    /**
     * Proceso para iniciar sesión:
     * - Si se envía el formulario (POST), se recuperan los datos enviados, se busca el usuario
     *   y se verifica la contraseña. Si son correctos, se establece la sesión y se redirige.
     * - Si se accede mediante GET, se carga la vista del formulario de login.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                set_flash_message("Por favor, complete todos los campos.", "error");
                redirect("login.php");
            }

            // Buscar el usuario en la base de datos.
            $user = User::findByUsername($this->db, $username);

            if ($user && password_verify($password, $user->password)) {
                // Establecer la sesión y redirigir a index.php.
                $_SESSION['user_id'] = $user->id;
                set_flash_message("Bienvenido, " . $user->name, "success");
                redirect("index.php");
            } else {
                set_flash_message("Usuario o contraseña incorrectos.", "error");
                redirect("login.php");
            }
        } else {
            // Cargar la vista del formulario de login.
            require_once __DIR__ . '/../views/login.php';
        }
    }

    /**
     * Proceso de registro:
     * - Si se envía el formulario (POST), se recuperan los datos, se validan los campos,
     *   se verifica que el usuario no exista y se inserta el nuevo usuario en la base de datos.
     * - Si se accede mediante GET, se carga la vista del formulario de registro.
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $name            = trim($_POST['name'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $username        = trim($_POST['username'] ?? '');
            $password        = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validar que ningún campo esté vacío.
            if (empty($name) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
                set_flash_message("Todos los campos son obligatorios.", "error");
                redirect("register.php");
            }

            // Verificar que las contraseñas coincidan.
            if ($password !== $confirmPassword) {
                set_flash_message("Las contraseñas no coinciden.", "error");
                redirect("register.php");
            }

            // Verificar que el nombre de usuario no esté ya en uso.
            if (User::findByUsername($this->db, $username)) {
                set_flash_message("El nombre de usuario ya existe.", "error");
                redirect("register.php");
            }

            // Hashear la contraseña para almacenarla de forma segura.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Insertar el usuario en la tabla "users".
                $stmt = $this->db->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $username, $hashedPassword]);

                set_flash_message("Registro exitoso, ahora puedes iniciar sesión.", "success");
                redirect("login.php");
            } catch (PDOException $e) {
                set_flash_message("Error al registrar el usuario: " . $e->getMessage(), "error");
                redirect("register.php");
            }
        } else {
            // Cargar la vista del formulario de registro.
            require_once __DIR__ . '/../views/register.php';
        }
    }

    /**
     * Procesa la salida del usuario: cierra la sesión y redirige a la página de login.
     */
    public function logout() {
        session_destroy();
        redirect("login.php");
    }
}