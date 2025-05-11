<?php
// app/models/Project.php

/**
 * Class Project
 *
 * Representa un proyecto.
 *
 * La tabla "projects" en la base de datos se asume que tiene los siguientes campos:
 * - id: Identificador único (INTEGER PRIMARY KEY AUTOINCREMENT)
 * - title: Título del proyecto (TEXT NOT NULL)
 * - description: Descripción del proyecto (TEXT)
 * - created_at: Fecha y hora de creación (DATETIME DEFAULT CURRENT_TIMESTAMP)
 */
class Project {
    public $id;
    public $title;
    public $description;
    public $created_at;

    /**
     * Constructor opcional para inicializar propiedades a partir de un arreglo asociativo.
     *
     * @param array $data Datos iniciales.
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->id          = $data['id'] ?? null;
            $this->title       = $data['title'] ?? 'Sin título';
            $this->description = $data['description'] ?? '';
            $this->created_at  = $data['created_at'] ?? date('Y-m-d H:i:s');
        }
    }

    /**
     * Crea la tabla "projects" si no existe.
     *
     * @param PDO $db Conexión a la base de datos.
     */
    public static function createTableIfNotExists(PDO $db) {
        $sql = "CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
    }

    /**
     * Busca un proyecto por su ID.
     *
     * @param PDO $db Conexión a la base de datos.
     * @param int $id ID del proyecto.
     * @return Project|null
     */
    public static function findById(PDO $db, int $id) {
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    /**
     * Guarda el proyecto en la base de datos.
     * Si el proyecto tiene un ID, realiza una actualización; si no, inserta uno nuevo.
     *
     * @param PDO $db Conexión a la base de datos.
     */
    public function save(PDO $db) {
        if ($this->id) {
            // Actualiza el registro existente.
            $stmt = $db->prepare("UPDATE projects SET title = ?, description = ? WHERE id = ?");
            $stmt->execute([$this->title, $this->description, $this->id]);
        } else {
            // Inserta un nuevo registro.
            $stmt = $db->prepare("INSERT INTO projects (title, description) VALUES (?, ?)");
            $stmt->execute([$this->title, $this->description]);
            $this->id = $db->lastInsertId();
        }
    }

    /**
     * Convierte la instancia del proyecto a un arreglo asociativo.
     *
     * @return array Arreglo con los datos del proyecto.
     */
    public function toArray(): array {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'created_at'  => $this->created_at,
        ];
    }
}