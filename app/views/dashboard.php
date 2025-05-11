<?php
// app/views/dashboard.php

require_once __DIR__ . '/../helpers/functions.php';
require_login();

// Conexión a la BD y obtención de los proyectos del usuario
$db     = getDBConnection();
$userId = logged_in_user_id();

// Recuperación del mensaje flash (si existe)
$flash = get_flash_message();

// Obtener proyectos del usuario
$stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$stmt->execute([$userId]);
$userProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validar el proyecto seleccionado en sesión
$currentProjectId   = $_SESSION['project_id'] ?? 0;
$currentProjectName = "";
if ($currentProjectId) {
    $stmt = $db->prepare("SELECT id, project_name FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$currentProjectId, $userId]);
    $currentProject = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($currentProject) {
        $currentProjectName = $currentProject['project_name'];
    } else {
        $currentProjectId = 0;
        unset($_SESSION['project_id']);
    }
}
?>
<?php include __DIR__ . '/templates/header.php'; ?>

<!-- Muestra mensaje flash si existe -->
<div class="flash-msg">
    <?php if ($flash): ?>
        <div class="flash <?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>
</div>

<div class="container">
  <nav>
    <h3>Proyectos</h3>
    <!-- Formulario para crear un nuevo proyecto -->
    <form method="post" action="/public/index.php?action=create_project">
      <label for="pname">Nuevo proyecto</label>
      <input type="text" id="pname" name="project_name" placeholder="Ingrese el nombre del proyecto" required>
      <button type="submit">Crear</button>
    </form>

    <!-- Formulario para seleccionar un proyecto existente -->
    <?php if ($userProjects): ?>
      <form method="post" action="/public/index.php?action=select_project">
        <label for="projid">Abrir proyecto existente:</label>
        <select name="project_id" id="projid">
          <?php foreach ($userProjects as $proj): ?>
            <option value="<?php echo $proj['id']; ?>" <?php echo ($proj['id'] == $currentProjectId) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($proj['project_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Abrir</button>
      </form>
    <?php endif; ?>

    <h3>Acciones</h3>
    <a href="#" class="nav-button" id="addBtn">Añadir clase</a><br><br>
    <a href="#" class="nav-button" id="listBtn">Mostrar clases</a><br><br>
    <a href="#" class="nav-button" id="saveBtn">Guardar clases</a><br><br>
    <a href="/public/index.php?action=logout" class="nav-button" style="background:red; color:white;">Cerrar sesión</a>
  </nav>

  <main>
    <!-- Plantilla para los elementos "draggable" de clases -->
    <template id="article-template">
      <article class="draggable" style="left:250px; top:250px;">
        <div class="nombre" contenteditable="true" placeholder="Nombre de la clase">Clase</div>
        <div class="propiedades">
          <p>Propiedades</p>
          <ul contenteditable="true" placeholder="Introduce tus propiedades...">
            <li></li>
          </ul>
        </div>
        <div class="metodos">
          <p>Métodos</p>
          <ul contenteditable="true" placeholder="Introduce tus métodos...">
            <li></li>
          </ul>
        </div>
      </article>
    </template>
  </main>
</div>

<!-- Se incluye el script que contiene la funcionalidad de drag & drop y AJAX -->
<script src="/assets/js/script.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>