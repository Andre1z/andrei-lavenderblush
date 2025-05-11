<?php
/**
 * Class Project
 *
 * Representa un proyecto asociado a un usuario.
 * Los campos 'id', 'user_id' y 'project_name' corresponden a los de la tabla "projects".
 */
class Project {
    public $id;
    public $user_id;
    public $project_name;

    /**
     * Constructor para inicializar un proyecto a partir de un arreglo asociativo.
     *
     * @param array $data Datos iniciales del proyecto.
     */
    public function __construct(array $data = []) {
        $this->id           = $data['id'] ?? null;
        $this->user_id      = $data['user_id'] ?? null;
        $this->project_name = $data['project_name'] ?? '';
    }

    /**
     * Crea un nuevo proyecto en la base de datos.
     *
     * @param PDO    $db          Instancia de la conexión a la base de datos.
     * @param int    $userId      ID del usuario propietario del proyecto.
     * @param string $projectName Nombre del proyecto.
     *
     * @return Project|null Retorna la instancia del proyecto recién creado, o null en caso de error.
     */
    public static function create(PDO $db, int $userId, string $projectName): ?Project {
        $stmt = $db->prepare("INSERT INTO projects (user_id, project_name) VALUES (?, ?)");
        if ($stmt->execute([$userId, $projectName])) {
            $id = $db->lastInsertId();
            return new Project([
                'id'           => $id,
                'user_id'      => $userId,
                'project_name' => $projectName
            ]);
        }
        return null;
    }

    /**
     * Busca un proyecto específico basado en su ID y el ID del usuario.
     *
     * @param PDO $db         Instancia de la conexión a la base de datos.
     * @param int $projectId  ID del proyecto a buscar.
     * @param int $userId     ID del usuario propietario del proyecto.
     *
     * @return Project|null Retorna una instancia del proyecto encontrado o null si no se localiza.
     */
    public static function findById(PDO $db, int $projectId, int $userId): ?Project {
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new Project($data) : null;
    }

    /**
     * Obtiene todos los proyectos asociados a un usuario específico.
     *
     * @param PDO $db      Instancia de la conexión a la base de datos.
     * @param int $userId  ID del usuario.
     *
     * @return Project[] Retorna un arreglo de instancias de Project.
     */
    public static function getProjectsByUser(PDO $db, int $userId): array {
        $stmt = $db->prepare("SELECT * FROM projects WHERE user_id = ?");
        $stmt->execute([$userId]);
        $projects = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $projects[] = new Project($data);
        }
        return $projects;
    }
}