<?php

require_once('../modelo/usuario.php');
require_once('../includes/db_conn.php'); // Asegúrate de que este archivo se incluye antes de usar la conexión
require_once('../controlador/aperitivoc.php');
require_once('../modelo/aperitivo.php');


session_start();  


$user_name = null;
$user_id = null;

if(isset($_SESSION['id']) && isset($_SESSION['user'])){
    $user_id = $_SESSION['id']; // Obtenemos el ID del usuario
    $user_name = $_SESSION['user'];
} else {
    // Usuario no está logueado, manejar el caso
    header("Location: login.php");
    exit;
}

$usuario = new Usuario($conn);
$perfil_usuario = $usuario->obtenerPerfil($user_id);

$aperitivO = new Aperitivo($conn);
$message = null;
$errores = array();

// Manejar la acción de eliminación por GET
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $resultado = $aperitivO->eliminarAperitivo($_GET['id']);
    if ($resultado === true) {
        $_SESSION['message'] = 'Aperitivo eliminado con éxito.';
    } else {
        $_SESSION['message'] = 'Error al eliminar el aperitivo.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $datos = $_POST;
    if (isset($datos['id'])){
        //Editar aperitivo
        $resultado = $aperitivO->actualizarAperitivo($datos['id'], $datos);
        if ($resultado === true) {
            $_SESSION['message'] = 'Aperitivo actualizada con éxito.'; // Almacena el mensaje en sesión
            header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
            exit; // Detener la ejecución después de redirigir
        } else {
            $errores = $resultado;
            $message = 'Se encontraron errores al actualizar el aperitivo.';
        }
    } else {
        //Agregar nuevo aperitivo
        $resultado = $aperitivO->agregarAperitivo($datos);
        if($resultado === true){
            $_SESSION['message'] = 'Aperitivo agregado con éxito.'; // Almacena el mensaje en sesión
        } elseif (is_array($resultado)) {
            $errores = $resultado;
            $message = 'Se encontraron errores al agregar el aperitivo.';
        } else {
            $message = 'Error al agregar el aperitivo.';
        }
    }
}

// Verificar si se ha solicitado cambiar el estado
if (isset($_GET['action']) && $_GET['action'] === 'changeStatus' && isset($_GET['id']) && isset($_GET['estado'])) {
    $id = $_GET['id'];
    $estado = $_GET['estado']; // 1 para activo, 0 para inactivo
    $resultado = $aperitivO->modificarEstado($id, $estado);
    if ($resultado) {
        $_SESSION['message'] = 'Estado del aperitivo actualizado correctamente.';
    } else {
        $_SESSION['message'] = 'Error al actualizar el estado del aperitivo.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $aperitivO = $aperitivO->obtenerAperitivo($_GET['id']);
} else {
    $aperitivO = array();
}

// Verificar si hay mensaje almacenado en la sesión
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']; // Obtener el mensaje
    unset($_SESSION['message']); // Eliminar el mensaje de la sesión
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Aperitivos</title>
    <link rel="stylesheet" href="../assets/css/forms.css">
</head>
<body>
    <!-- Menú Lateral -->
    <nav class="sidebar">
        <ul>
            <li class="menu-item"><a href="menupeliculas.php" class="menu-link">Gestionar Películas</a></li>
            <li class="menu-item"><a href="menufunciones.php" class="menu-link">Gestionar Funciones</a></li>
            <li class="menu-item"><a href="menuasientos.php" class="menu-link">Gestionar Asientos</a></li>
            <li class="menu-item"><a href="menuusuarios.php" class="menu-link">Gestionar Usuarios</a></li>
            <li class="menu-item"><a href="menuaperitivos.php" class="menu-link">Gestionar Aperitivos</a></li>
            <li class="menu-item"><a href="menuventas.php" class="menu-link">Ver Ventas</a></li>
            <li class="menu-item"><a href="index.php" class="menu-link">Volver a Inicio</a></li>
            <li class="menu-item"><a href="../user/logout.php" class="menu-link">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main>
        <?php if ($message): ?>
            <p class="alert <?php echo strpos($message, 'éxito') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($aperitivo): ?>
    <!-- Formulario de edición -->
    <h2>Editar Aperitivo</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($aperitivo['id']); ?>">

        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($aperitivo['nombre']); ?>" required>
        <?php if (isset($errores['nombre'])): ?>
            <span class="error"><?php echo $errores['nombre']; ?></span>
        <?php endif; ?>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($aperitivo['descripcion']); ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
            <span class="error"><?php echo $errores['descripcion']; ?></span>
        <?php endif; ?>

        <label for="precio">Precio:</label>
        <input type="number" id="precio" name="precio" value="<?php echo htmlspecialchars($aperitivo['precio']); ?>" required step="0.01">
        <?php if (isset($errores['precio'])): ?>
            <span class="error"><?php echo $errores['precio']; ?></span>
        <?php endif; ?>

        <label for="imagen">Imagen (dejar en blanco si no se cambia):</label>
        <input type="file" id="imagen" name="imagen">

        <button type="submit">Guardar Cambios</button>
    </form>
        <?php else: ?>
            <!-- Formulario de creación -->
            <h2>Agregar Nuevo Aperitivo</h2>
            <form action="" method="post" enctype="multipart/form-data">

                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
                <?php if (isset($errores['nombre'])): ?>
                    <span class="error"><?php echo $errores['nombre']; ?></span>
                <?php endif; ?>

                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
                <?php if (isset($errores['descripcion'])): ?>
                    <span class="error"><?php echo $errores['descripcion']; ?></span>
                <?php endif; ?>

                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" required step="0.01">
                <?php if (isset($errores['precio'])): ?>
                    <span class="error"><?php echo $errores['precio']; ?></span>
                <?php endif; ?>

                <label for="imagen">Imagen:</label>
                <input type="file" id="imagen" name="imagen" required>

                <!-- Nuevo campo para el estado -->
                <label>Estado:</label>
                    <select name="estado" required>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                <?php if (isset($errores['estado'])): ?>
                    <span class="error"><?php echo $errores['estado']; ?></span>
                <?php endif; ?>

                <button type="submit">Agregar Aperitivo</button>
            </form>
        <?php endif; ?>

        <!-- Tabla de aperitivos existentes -->
        <h2>Aperitivos Existentes</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Imagen</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    <?php while ($aperitivo = $aperitivos->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($aperitivo['id']); ?></td>
            <td><?php echo htmlspecialchars($aperitivo['nombre']); ?></td>
            <td><?php echo htmlspecialchars($aperitivo['descripcion']); ?></td>
            <td><?php echo htmlspecialchars($aperitivo['precio']); ?></td>
            <td>
                <img src="../assets/images/<?php echo htmlspecialchars($aperitivo['imagen']); ?>" alt="<?php echo htmlspecialchars($aperitivo['nombre']); ?>" width="100">
            </td>
            <!-- Columna Estado -->
            <td>
                    <?php if (isset($aperitivo['estado'])): ?>
                        <?php if ($aperitivo['estado'] == 1): ?>
                            <span class="estado activo">Activo</span>
                        <?php elseif ($aperitivo['estado'] == 0): ?>
                            <span class="estado inactivo">Inactivo</span>
                        <?php else: ?>
                            <span class="estado">Estado no válido</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="estado">Estado no disponible</span>
                    <?php endif; ?>
                </td>
            <!-- Columna Acciones (con el botón para activar/desactivar) -->
            <td class="actions">
                    <a href="?action=edit&id=<?php echo $aperitivo['id']; ?>">Editar</a>
                    <a href="?action=delete&id=<?php echo $aperitivo['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar esta película?');">Eliminar</a>

                    <!-- Mover el botón de activar/desactivar a esta columna -->
                    <?php if (isset($aperitivo['estado'])): ?>
                        <?php if ($aperitivo['estado'] == 1): ?>
                            <a href="?action=changeStatus&id=<?php echo $aperitivo['id']; ?>&estado=0" onclick="return confirm('¿Estás seguro de que quieres desactivar esta película?');">Desactivar</a>
                        <?php elseif ($aperitivo['estado'] == 0): ?>
                            <a href="?action=changeStatus&id=<?php echo $aperitivo['id']; ?>&estado=1" onclick="return confirm('¿Estás seguro de que quieres activar esta película?');">Activar</a>
                        <?php endif; ?>
                    <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>

        </table>

        <div class="pagination">
            <?php if ($paginaActual > 1): ?>
                <a href="menuaperitivos.php?page=<?php echo $paginaActual - 1; ?>">Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?></span>
            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="menuaperitivos.php?page=<?php echo $paginaActual + 1; ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>