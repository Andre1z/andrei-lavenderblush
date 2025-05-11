<?php
/**
 * app/helpers/functions.php
 *
 * Este archivo contiene funciones auxiliares para la aplicación, tales como la conexión
 * a la base de datos SQLite, manejo de sesiones, redirección y mensajes flash.
 */

// Inicia la sesión si aún no está iniciada.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna una conexión PDO a la base de datos SQLite.
 * Si el archivo de la base de datos no existe, SQLite lo creará automáticamente.
 * Además, se asegura de que la tabla "users" exista.
 *
 * @return PDO
 */
function getDBConnection() {
    // Define la ruta del archivo de base de datos.
    // Se almacenará en la carpeta "data" ubicada en la raíz del proyecto.
    $db_path = __DIR__ . '/../../data/database.db';
    
    try {
        // Crea una nueva instancia de PDO para SQLite.
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crea la tabla "users" si no existe.
        $createUsersTableSQL = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL
        )";
        $pdo->exec($createUsersTableSQL);
        
        return $pdo;
    } catch (PDOException $e) {
        die("La conexión a la base de datos falló: " . $e->getMessage());
    }
}

/**
 * Verifica que el usuario haya iniciado sesión.
 * Si no es así, establece un mensaje flash y redirige a la página de login.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        set_flash_message("Debes iniciar sesión para acceder a esta página.", "error");
        redirect("login.php");
    }
}

/**
 * Redirige a la URL indicada.
 *
 * @param string $url La URL destino.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Establece un mensaje flash en la sesión.
 *
 * @param string $message El contenido del mensaje.
 * @param string $type    El tipo de mensaje ("info", "success", "error").
 */
function set_flash_message($message, $type = "info") {
    $_SESSION['flash_message'] = [
        "message" => $message,
        "type" => $type
    ];
}

/**
 * Recupera y elimina el mensaje flash almacenado en la sesión, si existe.
 *
 * @return array|null  Un array asociativo con 'message' y 'type' o null si no existe.
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Retorna el ID del usuario que ha iniciado sesión o null si no hay ninguno.
 *
 * @return int|null
 */
function logged_in_user_id() {
    return $_SESSION['user_id'] ?? null;
}