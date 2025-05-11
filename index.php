<?php
// Iniciar sesión y configurar la conexión a la base de datos
session_start();

$dbFilePath = __DIR__ . '/data.sqlite';
$isNewDatabase = !file_exists($dbFilePath);

try {
    $db = new PDO('sqlite:' . $dbFilePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $ex) {
    die("Error conectando a la base de datos SQLite: " . $ex->getMessage());
}

if ($isNewDatabase) {
    // Crear el esquema inicial
    $schemaSQL = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    project_name TEXT NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    class_name TEXT NOT NULL,
    properties TEXT,
    methods TEXT,
    FOREIGN KEY(project_id) REFERENCES projects(id)
);
SQL;
    $db->exec($schemaSQL);
    
    // Insertar un usuario de demostración
    $stmt = $db->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Jose Vicente Carratalá',
        'info@josevicentecarratala.com',
        'andrei',
        'andrei'
    ]);
}

// Intentar agregar las columnas de posición, sin problemas si ya existen
try {
    $db->exec("ALTER TABLE classes ADD COLUMN pos_x REAL DEFAULT 250");
    $db->exec("ALTER TABLE classes ADD COLUMN pos_y REAL DEFAULT 250");
} catch (Exception $ignored) {
    // No es necesario actuar si las columnas ya existen
}

// Funciones auxiliares (helpers)
function usuarioConectado() {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

function forzarLogin() {
    if (!usuarioConectado()) {
        header("Location: index.php");
        exit;
    }
}

function setFlash($mensaje) {
    $_SESSION['flash_msg'] = $mensaje;
}

function getFlash() {
    if (!empty($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $msg;
    }
    return "";
}

function redirigir($ruta) {
    header("Location: $ruta");
    exit;
}

// Manejo de acciones

// Cerrar sesión
if (isset($_GET['accion']) && $_GET['accion'] === 'logout') {
    session_destroy();
    redirigir('index.php');
}

// Iniciar sesión
if (isset($_POST['accion']) && $_POST['accion'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['id'];
        redirigir('index.php');
    } else {
        setFlash("Usuario o contraseña incorrecta.");
        redirigir('index.php');
    }
}

// Crear un nuevo proyecto
if (isset($_GET['accion']) && $_GET['accion'] === 'crear_proyecto') {
    forzarLogin();
    if (!empty($_POST['nombre_proyecto'])) {
        $stmt = $db->prepare("INSERT INTO projects (user_id, project_name) VALUES (?, ?)");
        $stmt->execute([usuarioConectado(), $_POST['nombre_proyecto']]);
        $_SESSION['project_id'] = $db->lastInsertId();
        setFlash("Proyecto creado: " . htmlspecialchars($_POST['nombre_proyecto']));
    }
    redirigir('index.php');
}

// Seleccionar un proyecto existente
if (isset($_GET['accion']) && $_GET['accion'] === 'seleccionar_proyecto') {
    forzarLogin();
    if (!empty($_POST['proyecto_id'])) {
        $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['proyecto_id'], usuarioConectado()]);
        $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($proyecto) {
            $_SESSION['project_id'] = $proyecto['id'];
            setFlash("Proyecto seleccionado.");
        } else {
            setFlash("Proyecto inválido.");
        }
    }
    redirigir('index.php');
}

// Peticiones AJAX para guardar y cargar las clases
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax']) && $_GET['ajax'] === 'guardar_clases') {
    forzarLogin();
    $projectId = $_SESSION['project_id'] ?? 0;
    if (!$projectId) {
        http_response_code(400);
        echo "No se ha seleccionado ningún proyecto.";
        exit;
    }
    // Verificar que el proyecto pertenezca al usuario
    $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, usuarioConectado()]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(403);
        echo "Proyecto no autorizado.";
        exit;
    }
    $jsonData = file_get_contents("php://input");
    $clases = json_decode($jsonData, true);
    if (!is_array($clases)) {
        http_response_code(400);
        echo "Datos JSON inválidos.";
        exit;
    }
    
    $db->beginTransaction();
    try {
        // Eliminar las clases anteriores del proyecto
        $delStmt = $db->prepare("DELETE FROM classes WHERE project_id = ?");
        $delStmt->execute([$projectId]);
        
        // Insertar las nuevas definiciones de clases
        $insStmt = $db->prepare("INSERT INTO classes (project_id, class_name, properties, methods, pos_x, pos_y) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($clases as $clase) {
            $nombre = trim($clase['className'] ?? 'Clase');
            $props = isset($clase['properties']) ? json_encode($clase['properties']) : '[]';
            $methods = isset($clase['methods']) ? json_encode($clase['methods']) : '[]';
            $posX = floatval($clase['x'] ?? 250);
            $posY = floatval($clase['y'] ?? 250);
            $insStmt->execute([$projectId, $nombre, $props, $methods, $posX, $posY]);
        }
        $db->commit();
        echo "Clases guardadas correctamente para el proyecto #{$projectId}";
    } catch (Exception $error) {
        $db->rollBack();
        http_response_code(500);
        echo "Error guardando las clases: " . $error->getMessage();
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'cargar_clases') {
    forzarLogin();
    $projectId = $_SESSION['project_id'] ?? 0;
    if (!$projectId) {
        echo json_encode(["error" => "No se ha seleccionado ningún proyecto."]);
        exit;
    }
    $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, usuarioConectado()]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["error" => "Proyecto no válido."]);
        exit;
    }
    $stmt = $db->prepare("SELECT class_name, properties, methods, pos_x, pos_y FROM classes WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $salida = [];
    foreach ($rows as $row) {
        $salida[] = [
            'className'  => $row['class_name'],
            'properties' => json_decode($row['properties'], true),
            'methods'    => json_decode($row['methods'], true),
            'x'          => (float)$row['pos_x'],
            'y'          => (float)$row['pos_y']
        ];
    }
    echo json_encode($salida);
    exit;
}

// Si el usuario no ha iniciado sesión, se muestra la pantalla de login.
if (!usuarioConectado()):
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>andrei | lavenderblush - Acceso</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
<div class="login-container">
    <img src="lavenderblush.png" alt="Logo">
    <h1>andrei | lavenderblush</h1>
    <?php 
      $mensaje = getFlash();
      if ($mensaje): ?>
      <div class="flash-msg"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="accion" value="login">
        <label>Usuario</label>
        <input type="text" name="username" required>
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <button type="submit">Iniciar Sesión</button>
    </form>
</div>
</body>
</html>
<?php
exit;
endif;

// Usuario autenticado: mostrar la interfaz principal.
$stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$stmt->execute([usuarioConectado()]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$proyectoActivo = $_SESSION['project_id'] ?? 0;
$nombreProyecto = '';
if ($proyectoActivo) {
    $stmt = $db->prepare("SELECT id, project_name FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$proyectoActivo, usuarioConectado()]);
    $proyectoDatos = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($proyectoDatos) {
        $nombreProyecto = $proyectoDatos['project_name'];
    } else {
        $proyectoActivo = 0;
        unset($_SESSION['project_id']);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>andrei | lavenderblush - Multiproyecto</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <img src="lavenderblush.png" alt="Logo">
    andrei | lavenderblush
</header>
<?php
$mensajeFlash = getFlash();
if ($mensajeFlash):
?>
<div class="flash-msg"><?= htmlspecialchars($mensajeFlash) ?></div>
<?php endif; ?>
<div class="contenedor">
    <nav>
        <h3>Proyectos</h3>
        <form method="post" action="?accion=crear_proyecto">
            <label for="nombre_proyecto">Nuevo proyecto</label>
            <input type="text" id="nombre_proyecto" name="nombre_proyecto" placeholder="Nombre del proyecto" required>
            <button type="submit">Crear</button>
        </form>
        <?php if ($proyectos): ?>
        <form method="post" action="?accion=seleccionar_proyecto">
            <label for="proyecto_id">Abrir proyecto</label>
            <select name="proyecto_id" id="proyecto_id">
                <?php foreach ($proyectos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($p['id'] == $proyectoActivo) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($p['project_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Abrir</button>
        </form>
        <?php endif; ?>
        <!-- Campo de búsqueda para filtrar clases -->
        <input type="text" id="searchBox" placeholder="Buscar clases...">
        <h3>Acciones</h3>
        <a href="#" class="botón" id="agregarClase">Añadir clase</a><br><br>
        <a href="#" class="botón" id="mostrarClases">Mostrar clases</a><br><br>
        <a href="#" class="botón" id="guardarClases">Guardar clases</a><br><br>
        <a href="?accion=logout" class="botón" style="background:#d32f2f; color:#fff;">Salir</a>
    </nav>
    <main>
        <!-- Plantilla para elementos de clase con botones de eliminar y duplicar -->
        <template id="plantilla-clase">
            <article class="draggable" style="left:250px; top:250px;">
                <div class="header-clase">
                    <div class="nombre" contenteditable="true" placeholder="Nombre de la clase">Clase</div>
                    <div class="acciones">
                        <button type="button" class="btnEliminar" title="Eliminar">X</button>
                        <button type="button" class="btnDuplicar" title="Duplicar">D</button>
                    </div>
                </div>
                <div class="propiedades">
                    <p>Propiedades</p>
                    <ul contenteditable="true" placeholder="Agrega propiedades">
                        <li></li>
                    </ul>
                </div>
                <div class="metodos">
                    <p>Métodos</p>
                    <ul contenteditable="true" placeholder="Agrega métodos">
                        <li></li>
                    </ul>
                </div>
            </article>
        </template>
    </main>
</div>
<script src="script.js"></script>
</body>
</html>