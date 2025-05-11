<?php
/**
 * test_db.php
 *
 * Este script se utiliza para probar la conexión a la base de datos SQLite, 
 * insertar un registro en la tabla "users" y mostrar los registros existentes.
 * Asegúrate de que la función getDBConnection() (definida en app/helpers/functions.php)
 * esté configurada correctamente con la ruta a /data/data.sqlite.
 */

// Ajusta la ruta según la ubicación de este archivo.
// Si lo colocas en la raíz del proyecto:
require_once __DIR__ . '/app/helpers/functions.php';

// Si prefieres colocarlo en la carpeta public, la línea sería similar a:
// require_once __DIR__ . '/../app/helpers/functions.php';

$pdo = getDBConnection();

// Mensaje indicativo de correcta conexión
echo "Conexión a la base de datos exitosa.<br>";

// Intentar insertar un usuario de prueba en la tabla "users"
try {
    // Preparar la consulta de inserción
    $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
    $name = "Test User";
    $email = "test@example.com";
    $username = "testuser";
    $password = password_hash("1234", PASSWORD_DEFAULT);

    // Ejecutar la consulta
    $stmt->execute([$name, $email, $username, $password]);
    echo "Registro insertado con éxito.<br>";
} catch (PDOException $e) {
    echo "Error al insertar: " . $e->getMessage() . "<br>";
}

// Consultar y mostrar todos los registros de la tabla "users"
try {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        echo "<strong>Registros en la tabla 'users':</strong><br>";
        echo "<pre>" . print_r($users, true) . "</pre>";
    } else {
        echo "No se encontraron registros en la tabla 'users'.<br>";
    }
} catch (PDOException $e) {
    echo "Error al consultar: " . $e->getMessage() . "<br>";
}
?>