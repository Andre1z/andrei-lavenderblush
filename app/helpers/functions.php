<?php
/**
 * app/helpers/functions.php
 *
 * Funciones auxiliares que incluyen:
 * - Conexión a la base de datos SQLite (crea el archivo en /data/database.db y la tabla users si no existe).
 * - Manejo de sesiones.
 * - Redirecciones.
 * - Mensajes flash.
 */

// Inicia la sesión si aún no se ha iniciado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna una conexión PDO a la base de datos SQLite.
 * Se crea la tabla "users" si no existe.
 *
 * @return PDO
 */
function getDBConnection() {
    // La ruta a la base de datos se construye desde /app/helpers hasta /data/database.db
    $db_path = __DIR__ . '/../../data/database.db';
    
    try {
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear la tabla "users" si no existe
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
        die("Error de conexión: " . $e->getMessage());
    }
}

/**
 * Verifica que el usuario haya iniciado sesión, de lo contrario redirige a login.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        set_flash_message("Debes iniciar sesión para acceder a esta página.", "error");
        redirect("index.php?action=login");
    }
}

/**
 * Redirige a la URL indicada.
 *
 * @param string $url La URL de destino.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Establece un mensaje flash en la sesión.
 *
 * @param string $message El mensaje.
 * @param string $type    Tipo: "info", "success", o "error".
 */
function set_flash_message($message, $type = "info") {
    $_SESSION['flash_message'] = [
        "message" => $message,
        "type"    => $type
    ];
}

/**
 * Recupera y elimina el mensaje flash, si existe.
 *
 * @return array|null Array con 'message' y 'type' o null.
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
 * Retorna el ID del usuario logueado o null.
 *
 * @return int|null
 */
function logged_in_user_id() {
    return $_SESSION['user_id'] ?? null;
}