<?php
// app/models/ClassModel.php

/**
 * Class ClassModel
 *
 * Representa una clase (entidad gráfica) asociada a un proyecto.
 *
 * Los datos estructurales se corresponden con la tabla "classes" en la base de datos, la cual contiene:
 * - id
 * - project_id
 * - class_name
 * - properties (almacenadas como JSON)
 * - methods (almacenadas como JSON)
 * - pos_x (posición X para la visualización)
 * - pos_y (posición Y para la visualización)
 */
class ClassModel {
    // Propiedades de la clase
    public $id;
    public $projectId;
    public $className;
    public $properties;
    public $methods;
    public $pos_x;
    public $pos_y;

    /**
     * Constructor opcional para inicializar propiedades a partir de un arreglo asociativo.
     *
     * @param array $data Datos iniciales (pueden venir directamente de la base de datos).
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->id         = $data['id'] ?? null;
            $this->projectId  = $data['project_id'] ?? null;
            $this->className  = $data['class_name'] ?? 'Clase';
            $this->properties = isset($data['properties']) ? json_decode($data['properties'], true) : [];
            $this->methods    = isset($data['methods']) ? json_decode($data['methods'], true) : [];
            $this->pos_x      = isset($data['pos_x']) ? floatval($data['pos_x']) : 250;
            $this->pos_y      = isset($data['pos_y']) ? floatval($data['pos_y']) : 250;
        }
    }

    /**
     * Guarda todas las clases asociadas a un proyecto.
     * El método elimina las entradas existentes para el proyecto y reinserta todas las clases recibidas.
     *
     * @param PDO   $db           Instancia de la conexión a la base de datos.
     * @param int   $projectId    ID del proyecto.
     * @param array $classesData  Lista de clases. Cada elemento debe ser un arreglo con las claves:
     *                            - className
     *                            - properties (array de strings)
     *                            - methods (array de strings)
     *                            - x (posición X)
     *                            - y (posición Y)
     *
     * @return bool Retorna true si se completó correctamente.
     * @throws Exception En caso de error durante la transacción.
     */
    public static function saveAll(PDO $db, int $projectId, array $classesData): bool {
        try {
            // Inicia la transacción
            $db->beginTransaction();

            // Elimina las clases anteriores asociadas al proyecto.
            $deleteStmt = $db->prepare("DELETE FROM classes WHERE project_id = ?");
            $deleteStmt->execute([$projectId]);

            // Prepara la inserción de cada clase.
            $insertStmt = $db->prepare("
                INSERT INTO classes (project_id, class_name, properties, methods, pos_x, pos_y)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($classesData as $cls) {
                $className = trim($cls['className'] ?? 'Clase');
                $props     = isset($cls['properties']) ? json_encode($cls['properties']) : '[]';
                $methods   = isset($cls['methods']) ? json_encode($cls['methods']) : '[]';
                $pos_x     = floatval($cls['x'] ?? 250);
                $pos_y     = floatval($cls['y'] ?? 250);

                $insertStmt->execute([$projectId, $className, $props, $methods, $pos_x, $pos_y]);
            }

            // Finaliza la transacción exitosamente.
            $db->commit();
            return true;
        } catch (Exception $ex) {
            // En caso de error, revierte la transacción.
            $db->rollBack();
            throw new Exception("Error saving classes: " . $ex->getMessage(), 0, $ex);
        }
    }

    /**
     * Recupera todas las clases asociadas a un proyecto dado.
     *
     * @param PDO $db         Instancia de la conexión a la base de datos.
     * @param int $projectId  ID del proyecto.
     *
     * @return array Lista de instancias de ClassModel.
     */
    public static function getAll(PDO $db, int $projectId): array {
        $stmt = $db->prepare("SELECT * FROM classes WHERE project_id = ?");
        $stmt->execute([$projectId]);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        return $results;
    }

    /**
     * Método auxiliar para convertir una instancia de ClassModel a un arreglo asociativo.
     * Esto es útil para responder con formato JSON en las solicitudes AJAX.
     *
     * @return array Arreglo con los datos de la clase.
     */
    public function toArray(): array {
        return [
            'id'          => $this->id,
            'project_id'  => $this->projectId,
            'className'   => $this->className,
            'properties'  => $this->properties,
            'methods'     => $this->methods,
            'x'           => $this->pos_x,
            'y'           => $this->pos_y,
        ];
    }
}