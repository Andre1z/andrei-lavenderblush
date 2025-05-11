<?php
// app/helpers/functions.php

// Inicia la sesión si aún no se ha iniciado.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtiene y retorna una instancia PDO para la conexión con la base de datos SQLite.
 * Se utiliza el patrón singleton para que la conexión se establezca una sola vez.
 *
 * @return PDO
 */
function getDBConnection() {
    static $db = null;
    if ($db === null) {
        // Construye la ruta al archivo SQLite. Se asume que se encuentra en "data/data.sqlite" en el directorio raíz.
        $dbFile = __DIR__ . '/../../data/data.sqlite';
        try {
            $db = new PDO('sqlite:' . $dbFile);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Cannot connect to the SQLite database: " . $e->getMessage());
        }
    }
    return $db;
}

/**
 * Redirige a una URL determinada y detiene la ejecución del script.
 *
 * @param string $url URL a la que se redirige.
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Establece un mensaje flash en la sesión.
 *
 * @param string $message El mensaje a mostrar.
 * @param string $type    El tipo de mensaje (por ejemplo, 'info', 'success', 'error').
 * @return void
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type'    => $type
    ];
}

/**
 * Recupera y elimina el mensaje flash almacenado en la sesión.
 *
 * @return array|null Retorna un arreglo con las claves 'message' y 'type' o null si no hay mensaje.
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
 * Retorna el ID del usuario autenticado almacenado en la sesión.
 *
 * @return int
 */
function logged_in_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

/**
 * Verifica que el usuario haya iniciado sesión.
 * De no ser así, establece un mensaje flash y redirige al usuario a la página de login.
 *
 * @return void
 */
function require_login() {
    if (logged_in_user_id() === 0) {
        set_flash_message("Debes iniciar sesión para acceder a esa página.", "error");
        redirect('login.php');
    }
}

/**
 * Función auxiliar para sanitizar datos de entrada.
 * Esta función elimina espacios adicionales, etiquetas HTML y convierte caracteres especiales.
 *
 * @param string $data Texto de entrada.
 * @return string Texto sanitizado.
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}