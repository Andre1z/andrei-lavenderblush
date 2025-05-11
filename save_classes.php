<?php
/**
 * Este script recibe datos JSON enviados vía POST y genera archivos PHP
 * con la definición de cada clase. Cada objeto enviado en el JSON debe
 * contener las claves: className, properties y methods.
 */

header("Content-Type: text/plain; charset=utf-8");

// Leer el contenido raw del POST
$jsonInput = file_get_contents("php://input");
$classesData = json_decode($jsonInput, true);

// Validamos que se haya recibido un arreglo adecuado
if (!is_array($classesData)) {
    http_response_code(400);
    echo "Error: Datos JSON no válidos o ausentes.";
    exit;
}

// Definir el directorio donde se guardarán las clases
$classesDir = __DIR__ . '/classes';

// Crear el directorio si no existe
if (!is_dir($classesDir)) {
    if (!mkdir($classesDir, 0755, true)) {
        http_response_code(500);
        echo "Error: No se pudo crear el directorio 'classes'.";
        exit;
    }
}

// Procesar cada definición de clase
foreach ($classesData as $classItem) {
    // Obtener y limpiar el nombre de la clase
    $rawClassName = trim($classItem['className'] ?? 'Clase');
    $className = preg_replace('/[^A-Za-z0-9_]/', '', $rawClassName);
    if (empty($className)) {
        $className = 'ClaseSinNombre';
    }

    // Construir la ruta del archivo (por ejemplo: classes/MiClase.php)
    $filePath = $classesDir . '/' . $className . '.php';

    // Iniciar la generación del código PHP para la clase
    $phpCode  = "<?php\n\n";
    $phpCode .= "class $className {\n\n";

    // Agregar propiedades public: cada elemento se convierte en miembro
    if (!empty($classItem['properties']) && is_array($classItem['properties'])) {
        foreach ($classItem['properties'] as $prop) {
            $prop = trim($prop);
            // Sanitizamos para que solo contenga letras, números y guiones bajos
            $propClean = preg_replace('/[^A-Za-z0-9_]/', '', $prop);
            if (!empty($propClean)) {
                $phpCode .= "    public \$$propClean;\n";
            }
        }
        $phpCode .= "\n";
    }

    // Agregar métodos: cada uno con un comentario indicando "TODO"
    if (!empty($classItem['methods']) && is_array($classItem['methods'])) {
        foreach ($classItem['methods'] as $method) {
            $method = trim($method);
            $methodClean = preg_replace('/[^A-Za-z0-9_]/', '', $method);
            if (!empty($methodClean)) {
                $phpCode .= "    public function $methodClean() {\n";
                $phpCode .= "        // TODO: Implementar el método $methodClean\n";
                $phpCode .= "    }\n\n";
            }
        }
    }

    $phpCode .= "}\n";

    // Guardar el código generado en el archivo correspondiente
    if (file_put_contents($filePath, $phpCode) === false) {
        http_response_code(500);
        echo "Error al guardar la clase $className.";
        exit;
    }
}

echo "Las clases se han guardado correctamente.";
?>