<?php
/**
 * app/models/User.php
 *
 * Modelo de la entidad User.
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
     * @param PDO    $db
     * @param string $username
     * @return User|null
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
    
    // Otros métodos y lógica del modelo...
}