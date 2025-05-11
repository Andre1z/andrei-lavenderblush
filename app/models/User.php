<?php
/**
 * app/models/User.php
 *
 * Modelo para la entidad User.
 */
class User {

    public $id;
    public $name;
    public $email;
    public $username;
    public $password;

    /**
     * Busca un usuario por su nombre de usuario.
     *
     * @param PDO $db Conexión a la base de datos.
     * @param string $username Nombre de usuario.
     * @return User|null Instancia de User o null.
     */
    public static function findByUsername(PDO $db, $username) {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($data) {
            $user = new self();
            $user->id       = $data->id;
            $user->name     = $data->name;
            $user->email    = $data->email;
            $user->username = $data->username;
            $user->password = $data->password;
            return $user;
        }
        return null;
    }
}