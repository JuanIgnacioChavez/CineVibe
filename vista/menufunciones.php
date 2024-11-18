<?php
require_once('../includes/db_conn.php');
require_once('../modelo/funcion.php');
require_once('../controlador/funcionc.php');
require_once('../modelo/usuario.php');

session_start();  

$user_name = null;
$user_id = null;

if(isset($_SESSION['id']) && isset($_SESSION['user'])){
    $user_id = $_SESSION['id'];
    $user_name = $_SESSION['user'];
} else {
    header("Location: login.php");
    exit;
}

$usuario = new Usuario($conn);
$perfil_usuario = $usuario->obtenerPerfil($user_id);

$funcion = new Funcion($conn);
$message = null;
$errores = array();

// Manejar la acción de eliminación por GET
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $resultado = $funcion->eliminarFuncion($_GET['id']);
    if ($resultado === true) {
        $_SESSION['message'] = 'Función eliminada con éxito.';
    } else {
        $_SESSION['message'] = 'Error al eliminar la función.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = $_POST;
    
    if (isset($datos['id'])) {
        // Editar función
        $resultado = $funcion->actualizarFuncion($datos['id'], $datos);
        if ($resultado === true) {
            $_SESSION['message'] = 'Función actualizada con éxito.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errores = $resultado;
            $message = 'Se encontraron errores al actualizar la función.';
        }
    } else {
        // Agregar nueva función
        $resultado = $funcion->agregarFuncion($datos);
        if ($resultado === true) {
            $_SESSION['message'] = 'Función agregada con éxito.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } elseif (is_array($resultado)) {
            $errores = $resultado;
            $_SESSION['message'] = 'Se encontraron errores al cargar la función';
        } else {
            $message = 'Error al agregar la función.';
        }
    }
}

// Obtener función para editar
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset ($_GET['id'])){
    $funcion_editar = $funcion->obtenerFuncion($_GET['id']);
} else {
    $funcion_editar = array();
}

// Verificar si hay mensaje almacenado en la sesión
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Funciones</title>
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
        <!-- Mostrar mensajes de éxito o error -->
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'éxito') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($funcion_editar['id'])): ?>
            <h2>Editar Función</h2>
            <form action="" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($funcion_editar['id']); ?>">

                <label for="id_pelicula">Película:</label>
                <select id="id_pelicula" name="id_pelicula" required>
                    <?php foreach ($funcion->obtenerPeliculas() as $pelicula): ?>
                        <option value="<?php echo $pelicula['id']; ?>" 
                                <?php echo ($pelicula['id'] == $funcion_editar['id_pelicula']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pelicula['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" 
                       value="<?php echo htmlspecialchars($funcion_editar['fecha']); ?>" required>
                <?php if (isset($errores['fecha'])): ?>
                    <span class="error"><?php echo $errores['fecha']; ?></span>
                <?php endif; ?>

                <label for="hora">Hora:</label>
                <input type="time" id="hora" name="hora" 
                       value="<?php echo htmlspecialchars($funcion_editar['hora']); ?>" required>
                <?php if (isset($errores['hora'])): ?>
                    <span class="error"><?php echo $errores['hora']; ?></span>
                <?php endif; ?>

                <label for="sala">Sala:</label>
                <input type="text" id="sala" name="sala" 
                       value="<?php echo htmlspecialchars($funcion_editar['sala']); ?>" required>
                <?php if (isset($errores['sala'])): ?>
                    <span class="error"><?php echo $errores['sala']; ?></span>
                <?php endif; ?>

                <?php if (isset($errores['conflicto'])): ?>
                    <span class="error"><?php echo $errores['conflicto']; ?></span>
                <?php endif; ?>

                <button type="submit">Guardar Cambios</button>
            </form>
        <?php else: ?>
            <h2>Agregar Nueva Función</h2>
            <form action="" method="post">
                <label for="id_pelicula">Película:</label>
                <select id="id_pelicula" name="id_pelicula" required>
                    <?php foreach ($funcion->obtenerPeliculas() as $pelicula): ?>
                        <option value="<?php echo $pelicula['id']; ?>">
                            <?php echo htmlspecialchars($pelicula['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required>
                <?php if (isset($errores['fecha'])): ?>
                    <span class="error"><?php echo $errores['fecha']; ?></span>
                <?php endif; ?>

                <label for="hora">Hora:</label>
                <input type="time" id="hora" name="hora" required>
                <?php if (isset($errores['hora'])): ?>
                    <span class="error"><?php echo $errores['hora']; ?></span>
                <?php endif; ?>

                <label for="sala">Sala:</label>
                <input type="text" id="sala" name="sala" required>
                <?php if (isset($errores['sala'])): ?>
                    <span class="error"><?php echo $errores['sala']; ?></span>
                <?php endif; ?>

                <?php if (isset($errores['conflicto'])): ?>
                    <span class="error"><?php echo $errores['conflicto']; ?></span>
                <?php endif; ?>

                <button type="submit">Agregar Función</button>
            </form>
        <?php endif; ?>

        <h2>Lista de Funciones</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Película</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Sala</th>
                <th>Acciones</th>
            </tr>
            <?php while ($f = $funciones->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['id']); ?></td>
                    <td><?php echo htmlspecialchars($f['pelicula_titulo']); ?></td>
                    <td><?php echo htmlspecialchars($f['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($f['hora']); ?></td>
                    <td><?php echo htmlspecialchars($f['sala']); ?></td>
                    <td class='actions'>
                        <a href="?action=edit&id=<?php echo htmlspecialchars($f['id']); ?>">Editar</a>
                        <a href="?action=delete&id=<?php echo htmlspecialchars($f['id']); ?>" 
                           onclick="return confirm('¿Estás seguro de que deseas eliminar esta función?');">
                            Eliminar
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <?php if (isset($paginaActual) && isset($totalPaginas)): ?>
            <div class="pagination">
                <?php if ($paginaActual > 1): ?>
                    <a href="?page=<?php echo $paginaActual - 1; ?>">Anterior</a>
                <?php endif; ?>
                <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?></span>
                <?php if ($paginaActual < $totalPaginas): ?>
                    <a href="?page=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>