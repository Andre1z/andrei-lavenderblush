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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', sans-serif;
            background: linear-gradient(120deg, #ffeef3, #ffdff0);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
            max-width: 355px;
            width: 100%;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .flash-msg {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: #f8b2cd;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #ffa6c9;
        }
        .login-container img {
            width: 100%;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h1>andrei | lavenderblush</h1>
    <?php 
      $mensaje = getFlash();
      if ($mensaje): ?>
      <div class="flash-msg"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <form method="post">
        <img src="lavenderblush.png" alt="Logo">
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Ubuntu, sans-serif;
            background: #fff;
            color: #333;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background: lavenderblush;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        header img { width: 60px; margin-right: 20px; }
        .flash-msg {
            color: green;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }
        .contenedor {
            flex: 1;
            display: flex;
        }
        nav {
            width: 250px;
            background: linear-gradient(180deg, #fff0f5, #ffe1ec);
            padding: 20px;
            border-right: 2px solid #ddd;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        nav h3 { margin-bottom: 10px; color: #c71585; }
        nav form {
            background: #fff;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        nav label { font-weight: bold; margin-bottom: 5px; display: block; }
        nav input[type="text"], nav select {
            width: 100%;
            padding: 6px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        nav button, nav a.botón {
            display: inline-block;
            padding: 8px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            background: #f8b2cd;
            color: #333;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
        }
        nav button:hover, nav a.botón:hover { background: #ff9ebe; }
        /* Campo de búsqueda para filtrar clases */
        #searchBox {
            width: 100%;
            padding: 6px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        main {
            flex: 1;
            position: relative;
            background: #fafafa;
            overflow-y: auto;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.1);
        }
        .draggable {
            width: 220px;
            height: 320px;
            position: absolute;
            background: #fff;
            border: 2px solid lavenderblush;
            border-radius: 8px;
            box-shadow: 0px 5px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        /* Estilo para la cabecera de cada clase con botones de acción */
        .header-clase {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #c71585;
            padding: 5px;
            color: #fff;
        }
        .header-clase .nombre {
            flex: 1;
            cursor: text;
        }
        .header-clase .acciones button {
            background: #fff;
            border: none;
            padding: 2px 6px;
            margin-left: 5px;
            cursor: pointer;
            border-radius: 3px;
            color: #c71585;
        }
        .header-clase .acciones button:hover {
            background: #ffa6c9;
            color: #fff;
        }
        .propiedades, .metodos { padding: 8px; }
        .propiedades p, .metodos p { font-weight: bold; margin-bottom: 5px; }
        .propiedades ul, .metodos ul { padding-left: 20px; list-style: disc; }
        .propiedades ul li, .metodos ul li { margin-bottom: 5px; }
        [contenteditable="true"]:empty:before {
            content: attr(placeholder);
            color: #aaa;
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
        <a href="?accion=logout" class="botón" style="background:red; color:#fff;">Salir</a>
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
<script>
// Función para aplicar la funcionalidad de arrastrar sobre un elemento
function hacerArrastrable(el) {
    let offsetX = 0, offsetY = 0, arrastrando = false;
    el.addEventListener("mousedown", function(e) {
        arrastrando = true;
        offsetX = e.clientX - el.getBoundingClientRect().left;
        offsetY = e.clientY - el.getBoundingClientRect().top;
        el.style.cursor = "grabbing";
        el.style.zIndex = 9999;
    });
    document.addEventListener("mousemove", function(e) {
        if (!arrastrando) return;
        el.style.left = (e.clientX - offsetX) + "px";
        el.style.top = (e.clientY - offsetY) + "px";
    });
    document.addEventListener("mouseup", function() {
        arrastrando = false;
        el.style.cursor = "grab";
        el.style.zIndex = 1;
    });
}

// Función que agrega eventos extra (eliminar y duplicar) a cada clase (artículo)
function attachExtraEvents(articulo) {
    const btnEliminar = articulo.querySelector(".btnEliminar");
    if (btnEliminar) {
        btnEliminar.addEventListener("click", function(e) {
            e.stopPropagation();
            articulo.remove();
        });
    }
    const btnDuplicar = articulo.querySelector(".btnDuplicar");
    if (btnDuplicar) {
        btnDuplicar.addEventListener("click", function(e) {
            e.stopPropagation();
            const clon = articulo.cloneNode(true);
            clon.style.left = (parseInt(articulo.style.left, 10) + 20) + "px";
            clon.style.top = (parseInt(articulo.style.top, 10) + 20) + "px";
            document.querySelector("main").appendChild(clon);
            hacerArrastrable(clon);
            attachExtraEvents(clon);
        });
    }
}

// Función para recolectar la información de las clases que están en el DOM
function obtenerClases() {
    const lista = [];
    document.querySelectorAll("article.draggable").forEach(function(articulo) {
        const nombre = articulo.querySelector(".nombre")?.textContent.trim() || "Clase";
        const propiedades = [];
        articulo.querySelectorAll(".propiedades ul li").forEach(function(li) {
            propiedades.push(li.textContent.trim());
        });
        const metodos = [];
        articulo.querySelectorAll(".metodos ul li").forEach(function(li) {
            metodos.push(li.textContent.trim());
        });
        const posX = parseInt(articulo.style.left, 10) || 250;
        const posY = parseInt(articulo.style.top, 10) || 250;
        lista.push({
            className: nombre,
            properties: propiedades,
            methods: metodos,
            x: posX,
            y: posY
        });
    });
    return lista;
}

// Función para enviar la información de clases al servidor (guardar)
function guardarClases() {
    const datos = obtenerClases();
    fetch('index.php?ajax=guardar_clases', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(response => response.text())
    .then(texto => {
        alert(texto);
        console.log(texto);
    })
    .catch(error => console.error("Error al guardar clases:", error));
}

// Función para cargar las clases desde el servidor y renderizarlas en el área de trabajo
function cargarClases() {
    fetch('index.php?ajax=cargar_clases')
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data)) {
            // Limpiar clases existentes
            document.querySelectorAll("article.draggable").forEach(el => el.remove());
            // Agregar cada clase
            data.forEach(function(clase) {
                const plantilla = document.getElementById("plantilla-clase");
                const clon = plantilla.content.cloneNode(true);
                const articulo = clon.querySelector("article");
                // Asignar nombre
                articulo.querySelector(".nombre").textContent = clase.className;
                // Propiedades
                const ulProps = articulo.querySelector(".propiedades ul");
                ulProps.innerHTML = "";
                (clase.properties || []).forEach(function(prop) {
                    const li = document.createElement("li");
                    li.textContent = prop;
                    ulProps.appendChild(li);
                });
                // Métodos
                const ulMets = articulo.querySelector(".metodos ul");
                ulMets.innerHTML = "";
                (clase.methods || []).forEach(function(met) {
                    const li = document.createElement("li");
                    li.textContent = met;
                    ulMets.appendChild(li);
                });
                // Posición
                articulo.style.left = (clase.x || 250) + "px";
                articulo.style.top  = (clase.y || 250) + "px";
                document.querySelector("main").appendChild(articulo);
                hacerArrastrable(articulo);
                attachExtraEvents(articulo);
            });
        } else if (data.error) {
            console.warn(data.error);
        }
    })
    .catch(error => console.error("Error cargando clases:", error));
}

// Evento para filtrar clases en tiempo real mediante el campo de búsqueda
document.getElementById("searchBox").addEventListener("input", function() {
    const filtro = this.value.toLowerCase();
    document.querySelectorAll("article.draggable").forEach(function(articulo) {
        const nombre = articulo.querySelector(".nombre")?.textContent.toLowerCase();
        if (nombre && nombre.indexOf(filtro) !== -1) {
            articulo.style.display = "";
        } else {
            articulo.style.display = "none";
        }
    });
});

// Configurar eventos y auto-guardado al cargar el DOM
document.addEventListener("DOMContentLoaded", function() {
    // Botón para agregar nueva clase
    document.getElementById("agregarClase").addEventListener("click", function(e) {
        e.preventDefault();
        const plantilla = document.getElementById("plantilla-clase");
        const clon = plantilla.content.cloneNode(true);
        const articulo = clon.querySelector("article");
        document.querySelector("main").appendChild(articulo);
        hacerArrastrable(articulo);
        attachExtraEvents(articulo);
    });

    // Botón para mostrar (listar en la consola) las clases
    document.getElementById("mostrarClases").addEventListener("click", function(e) {
        e.preventDefault();
        console.log(obtenerClases());
    });

    // Botón para guardar las clases
    document.getElementById("guardarClases").addEventListener("click", function(e) {
        e.preventDefault();
        guardarClases();
    });

    // Auto-guardado cada 30 segundos (30000 milisegundos)
    setInterval(guardarClases, 30000);

    // Cargar clases desde el servidor al iniciar
    cargarClases();
});
</script>
</body>
</html>