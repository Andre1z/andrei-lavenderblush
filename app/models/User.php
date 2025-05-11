<?php
// app/models/User.php

/**
 * Class User
 *
 * Representa a un usuario de la aplicación.
 */
class User {
    public $id;
    public $name;
    public $email;
    public $username;
    public $password; // Almacena el hash de la contraseña

    /**
     * Constructor para inicializar un usuario a partir de un arreglo asociativo.
     *
     * @param array $data Datos del usuario.
     */
    public function __construct(array $data = []) {
        $this->id       = $data['id'] ?? null;
        $this->name     = $data['name'] ?? '';
        $this->email    = $data['email'] ?? '';
        $this->username = $data['username'] ?? '';
        $this->password = $data['password'] ?? '';
    }

    /**
     * Busca un usuario por su nombre de usuario.
     *
     * @param PDO    $db       Instancia de la conexión a la base de datos.
     * @param string $username El nombre de usuario a buscar.
     *
     * @return User|null Retorna la instancia del usuario encontrado o null si no se encuentra.
     */
    public static function findByUsername(PDO $db, string $username): ?User {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return new User($data);
        }
        return null;
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     *
     * @param PDO  $db   Instancia de la conexión a la base de datos.
     * @param array $data Arreglo asociativo con las claves: 'name', 'email', 'username' y 'password'.
     *                    Se espera que la contraseña ya esté hasheada.
     *
     * @return User|null Retorna la instancia del usuario creado o null en caso de error.
     */
    public static function create(PDO $db, array $data): ?User {
        $stmt = $db->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([
            $data['name'],
            $data['email'],
            $data['username'],
            $data['password'] // Se espera que esta contraseña ya esté hasheada
        ])) {
            $data['id'] = $db->lastInsertId();
            return new User($data);
        }
        return null;
    }

    /**
     * Busca un usuario por su ID.
     *
     * @param PDO $db Instancia de la conexión a la base de datos.
     * @param int $id ID del usuario.
     *
     * @return User|null Retorna la instancia del usuario encontrado o null si no existe.
     */
    public static function getById(PDO $db, int $id): ?User {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return new User($data);
        }
        return null;
    }
}