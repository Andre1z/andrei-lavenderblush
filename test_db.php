<?php
/**
 * test_db.php
 *
 * Script de prueba para verificar la conexión a la base de datos SQLite,
 * insertar un registro en la tabla "users" y listar los registros.
 */
require_once __DIR__ . '/app/helpers/functions.php';

$pdo = getDBConnection();

echo "Conexión a la base de datos exitosa.<br>";

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
    $name = "Test User";
    $email = "test@example.com";
    $username = "testuser";
    $password = password_hash("1234", PASSWORD_DEFAULT);
    $stmt->execute([$name, $email, $username, $password]);
    echo "Registro insertado con éxito.<br>";
} catch (PDOException $e) {
    echo "Error al insertar: " . $e->getMessage() . "<br>";
}

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