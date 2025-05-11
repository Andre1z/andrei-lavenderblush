<?php
/**
 * save_classes.php
 *
 * Este script procesa la solicitud POST que contiene los datos de las clases en formato JSON.
 * Por cada definición enviada, genera un archivo PHP en el directorio de clases.
 * Se espera que cada clase cuente con las siguientes propiedades:
 * - className: Nombre de la clase.
 * - properties: Array de propiedades (cada propiedad se convierte en una variable pública).
 * - methods: Array de métodos (cada método se convierte en una función pública con un comentario TODO).
 *
 * Este archivo debería ubicarse en la carpeta "public" o en una ruta accesible,
 * mientras que los archivos generados se colocan en la carpeta "classes".
 */

// Establecer el header de respuesta para texto plano.
header("Content-Type: text/plain");

// Leer el JSON enviado en el cuerpo de la petición.
$data = file_get_contents("php://input");
$classes = json_decode($data, true);

// Validar que el JSON se haya decodificado correctamente.
if ($classes === null) {
    http_response_code(400);
    echo "Invalid JSON data.";
    exit;
}

// Definir el directorio donde se guardarán los archivos de clase.
// Se usa __DIR__ para obtener la ruta actual y se asume que la carpeta "classes" está un nivel arriba.
$dir = __DIR__ . '/../classes';

// Crear el directorio si no existe.
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Procesar cada clase enviada.
foreach ($classes as $classData) {
    // Extraer el nombre de la clase, usando "Clase" por defecto si no se proporciona.
    $className = trim($classData['className'] ?? '');
    if (empty($className)) {
        $className = 'Clase';
    }

    // Sanitizar el nombre de la clase para permitir solo caracteres alfanuméricos y guion bajo.
    $sanitizedClassName = preg_replace('/[^A-Za-z0-9_]/', '', $className);
    if (empty($sanitizedClassName)) {
        $sanitizedClassName = 'UnnamedClass';
    }

    // Construir el nombre del archivo de salida (ejemplo: classes/NombreDeClase.php).
    $fileName = $dir . '/' . $sanitizedClassName . '.php';

    // Iniciar la generación del código PHP de la clase.
    $classCode = "<?php\n";
    $classCode .= "class $sanitizedClassName {\n\n";
    
    // Agregar las propiedades: cada propiedad se declara como pública.
    if (!empty($classData['properties']) && is_array($classData['properties'])) {
        foreach ($classData['properties'] as $property) {
            $property = trim($property);
            // Sanitizar el nombre de la propiedad para caracteres válidos.
            $propertyNameSanitized = preg_replace('/[^A-Za-z0-9_]/', '', $property);
            if (!empty($propertyNameSanitized)) {
                $classCode .= "    public \$$propertyNameSanitized;\n";
            }
        }
        $classCode .= "\n";
    }
    
    // Agregar los métodos: cada método se declara como público con un comentario para implementar.
    if (!empty($classData['methods']) && is_array($classData['methods'])) {
        foreach ($classData['methods'] as $method) {
            $method = trim($method);
            // Sanitizar el nombre del método.
            $methodNameSanitized = preg_replace('/[^A-Za-z0-9_]/', '', $method);
            if (!empty($methodNameSanitized)) {
                $classCode .= "    public function $methodNameSanitized() {\n";
                $classCode .= "        // TODO: implement $methodNameSanitized\n";
                $classCode .= "    }\n\n";
            }
        }
    }
    
    $classCode .= "}\n";

    // Escribir el código generado en el archivo.
    file_put_contents($fileName, $classCode);
}

// Responder al cliente
echo "Classes saved successfully.";
?>