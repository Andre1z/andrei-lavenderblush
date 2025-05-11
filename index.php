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
    <style>
        /* Nuevo estilo de login: Tema minimalista y moderno */
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #2a2a72, #009ffd);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Roboto', sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.92);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .login-container h1 {
            color: #2a2a72;
            margin-bottom: 20px;
        }
        .flash-msg {
            color: #ff3333;
            margin-bottom: 12px;
            font-weight: bold;
        }
        .login-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            color: #2a2a72;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #2a2a72;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .login-container button:hover {
            background: #1d1d5e;
        }
        .login-container img {
            max-width: 80%;
            margin-bottom: 20px;
        }
    </style>
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
    <style>
        /* Nuevo estilo del área principal: Tema oscuro y moderno con tipografía Roboto */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            background: #121212;
            color: #e0e0e0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background: #1f1f1f;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid #333;
        }
        header img {
            width: 60px;
            margin-right: 20px;
        }
        header { font-size: 28px; color: #61dafb; font-weight: bold; }
        .flash-msg {
            color: #66ff66;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }
        .contenedor {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        nav {
            width: 250px;
            background: #1a1a1a;
            padding: 20px;
            border-right: 2px solid #333;
            box-shadow: 4px 0 15px rgba(0,0,0,0.8);
            overflow-y: auto;
        }
        nav h3 {
            margin-bottom: 10px;
            color: #61dafb;
        }
        nav form {
            background: #2a2a2a;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        nav label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #ccc;
        }
        nav input[type="text"], nav select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background: #333;
            color: #e0e0e0;
        }
        nav button, nav a.botón {
            display: inline-block;
            padding: 8px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            background: #61dafb;
            color: #121212;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        nav button:hover, nav a.botón:hover {
            background: #52c7e5;
        }
        /* Campo de búsqueda */
        #searchBox {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #444;
            border-radius: 4px;
            background: #333;
            color: #e0e0e0;
        }
        main {
            flex: 1;
            position: relative;
            background: #181818;
            overflow-y: auto;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.8);
        }
        .draggable {
            width: 220px;
            height: 320px;
            position: absolute;
            background: #242424;
            border: 2px solid #61dafb;
            border-radius: 8px;
            box-shadow: 0px 5px 25px rgba(0,0,0,0.8);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .draggable:hover {
            transform: scale(1.02);
        }
        /* Cabecera de clase con botones */
        .header-clase {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #61dafb;
            padding: 8px;
            color: #121212;
        }
        .header-clase .nombre {
            flex: 1;
            cursor: text;
            font-weight: bold;
        }
        .header-clase .acciones button {
            background: #121212;
            border: none;
            padding: 4px 8px;
            margin-left: 4px;
            cursor: pointer;
            border-radius: 4px;
            color: #61dafb;
            font-weight: bold;
            transition: background 0.3s;
        }
        .header-clase .acciones button:hover {
            background: #333;
        }
        .propiedades, .metodos {
            padding: 12px;
        }
        .propiedades p, .metodos p {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .propiedades ul, .metodos ul {
            padding-left: 20px;
            list-style: disc;
        }
        .propiedades ul li, .metodos ul li {
            margin-bottom: 6px;
        }
        [contenteditable="true"]:empty:before {
            content: attr(placeholder);
            color: #888;
        }
    </style>
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