<?php
// app/controllers/ProjectController.php

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/ClassModel.php';

class ProjectController {

    protected $db;

    public function __construct() {
        $this->db = getDBConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Crea un nuevo proyecto.
     * Se espera que se envíe el nombre del proyecto mediante POST.
     */
    public function createProject() {
        require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['project_name'])) {
            $projectName = trim($_POST['project_name']);
            $userId      = logged_in_user_id();

            // Insertar el nuevo proyecto en la base de datos.
            $stmt = $this->db->prepare("INSERT INTO projects (user_id, project_name) VALUES (?, ?)");
            $stmt->execute([$userId, $projectName]);

            // Seleccionar inmediatamente el proyecto creado.
            $_SESSION['project_id'] = $this->db->lastInsertId();
            set_flash_message("Project created: " . htmlspecialchars($projectName), "success");
        } else {
            set_flash_message("Debe proporcionar un nombre de proyecto", "error");
        }
        redirect('dashboard.php');
    }

    /**
     * Selecciona un proyecto existente.
     * Se valida que el proyecto pertenezca al usuario.
     */
    public function selectProject() {
        require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['project_id'])) {
            $projectId = $_POST['project_id'];
            $userId    = logged_in_user_id();

            // Comprobar que el proyecto pertenezca al usuario.
            $stmt = $this->db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$projectId, $userId]);
            $proj = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($proj) {
                $_SESSION['project_id'] = $proj['id'];
                set_flash_message("Project selected.", "success");
            } else {
                set_flash_message("Proyecto seleccionado no es válido.", "error");
            }
        }
        redirect('dashboard.php');
    }

    /**
     * Guarda las clases enviadas vía solicitud AJAX.
     * Se espera que el método HTTP sea POST y el contenido sea JSON.
     */
    public function saveClasses() {
        require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projectId = $_SESSION['project_id'] ?? 0;
            if (!$projectId) {
                http_response_code(400);
                echo "No project selected.";
                exit;
            }

            // Validar que el proyecto corresponda al usuario logueado.
            $stmt = $this->db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$projectId, logged_in_user_id()]);
            $proj = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$proj) {
                http_response_code(403);
                echo "Invalid project.";
                exit;
            }

            // Leer y decodificar el JSON recibido.
            $rawJson     = file_get_contents("php://input");
            $classesData = json_decode($rawJson, true);
            if (!is_array($classesData)) {
                http_response_code(400);
                echo "Invalid JSON data.";
                exit;
            }

            // Guardar en la base de datos:
            // Eliminar las clases antiguas para el proyecto y agregar las nuevas.
            $this->db->beginTransaction();
            try {
                $del = $this->db->prepare("DELETE FROM classes WHERE project_id = ?");
                $del->execute([$projectId]);

                $ins = $this->db->prepare("
                    INSERT INTO classes (project_id, class_name, properties, methods, pos_x, pos_y)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                foreach ($classesData as $cls) {
                    $className = trim($cls['className'] ?? 'Clase');
                    $props     = isset($cls['properties']) ? json_encode($cls['properties']) : '[]';
                    $methods   = isset($cls['methods'])    ? json_encode($cls['methods'])    : '[]';
                    $posX      = floatval($cls['x'] ?? 250);
                    $posY      = floatval($cls['y'] ?? 250);

                    $ins->execute([$projectId, $className, $props, $methods, $posX, $posY]);
                }

                $this->db->commit();
                echo "Classes saved successfully for project #{$projectId}";
                exit;
            } catch (Exception $ex) {
                $this->db->rollBack();
                http_response_code(500);
                echo "Error saving classes: " . $ex->getMessage();
                exit;
            }
        }
    }

    /**
     * Carga las clases del proyecto seleccionado mediante AJAX.
     * Responde con un JSON con los datos de cada clase.
     */
    public function loadClasses() {
        require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $projectId = $_SESSION['project_id'] ?? 0;
            if (!$projectId) {
                echo json_encode(["error" => "No project selected."]);
                exit;
            }

            // Validar que el proyecto pertenece al usuario.
            $stmt = $this->db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$projectId, logged_in_user_id()]);
            $proj = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$proj) {
                echo json_encode(["error" => "Invalid project."]);
                exit;
            }

            // Obtener las clases asociadas al proyecto.
            $stmt = $this->db->prepare("SELECT class_name, properties, methods, pos_x, pos_y FROM classes WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'className'  => $r['class_name'],
                    'properties' => json_decode($r['properties'], true),
                    'methods'    => json_decode($r['methods'], true),
                    'x'          => (float)$r['pos_x'],
                    'y'          => (float)$r['pos_y']
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($out);
            exit;
        }
    }
}