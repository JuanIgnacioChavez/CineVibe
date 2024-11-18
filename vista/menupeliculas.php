<?php

require_once('../includes/db_conn.php');
require_once('../modelo/pelicula.php');
require_once('../controlador/peliculac.php');
require_once('../modelo/usuario.php');

session_start();

$user_name = null;
$user_id = null;

if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
    $user_id = $_SESSION['id'];
    $user_name = $_SESSION['user'];
} else {
    header("Location: login.php");
    exit;
}

$usuario = new Usuario($conn);
$perfil_usuario = $usuario->obtenerPerfil($user_id);

$film = new Pelicula($conn);
$message = null;
$errores = array();

// Manejar la acción de eliminación por GET
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $resultado = $film->eliminarPelicula($_GET['id']);
    if ($resultado === true) {
        $_SESSION['message'] = 'Película eliminada con éxito.';
    } else {
        $_SESSION['message'] = 'Error al eliminar la película.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = $_POST;
    
    if (isset($datos['id'])) {
        // Editar película
        $resultado = $film->actualizarPelicula($datos['id'], $datos);
        if ($resultado === true) {
            // Mensaje exitoso y redirección
            $_SESSION['message'] = 'Película actualizada con éxito.'; // Almacena el mensaje en sesión
            header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
            exit; // Detener la ejecución después de redirigir
        } else {
            $errores = $resultado;
            $message = 'Se encontraron errores al actualizar la película.';
        }
    } else {
        // Agregar nueva película
        $resultado = $film->agregarPelicula($datos);
        if ($resultado === true) {
            $_SESSION['message'] = 'Película agregada con éxito.'; // Almacena el mensaje en sesión
            header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
            exit; // Detener la ejecución después de redirigir
        } elseif (is_array($resultado)) {
            $errores = $resultado;
            $message = 'Se encontraron errores al agregar la película.';
        } else {
            $message = 'Error al agregar la película.';
        }
    }
}



// Verificar si se ha solicitado cambiar el estado
if (isset($_GET['action']) && $_GET['action'] === 'changeStatus' && isset($_GET['id']) && isset($_GET['estado'])) {
    $id = $_GET['id'];
    $estado = $_GET['estado']; // 1 para activo, 0 para inactivo
    $resultado = $film->modificarEstado($id, $estado);
    if ($resultado) {
        $_SESSION['message'] = 'Estado de la película actualizado correctamente.';
    } else {
        $_SESSION['message'] = 'Error al actualizar el estado de la película.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $pelicula = $film->obtenerPelicula($_GET['id']);
} else {
    $pelicula = array(); // Asegúrate de que no haya datos previos si no estamos editando una película
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
    <title>Panel de Películas</title>
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
            <li class="menu-item"><a href="../login/logout.php" class="menu-link">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main>
        <?php if ($message): ?>
            <p class="alert <?php echo strpos($message, 'éxito') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if (isset($pelicula) && !empty($pelicula)): ?>
            <h2>Editar Película</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($pelicula['id']); ?>">
                <label>Título:</label>
                <input type="text" name="titulo" value="<?php echo htmlspecialchars($pelicula['titulo'] ?? ''); ?>" required>
                <?php if (isset($errores['titulo'])): ?>
                    <span class="error"><?php echo $errores['titulo']; ?></span>
                <?php endif; ?>

                <label>Descripción:</label>
                <textarea name="descripcion" required><?php echo htmlspecialchars($pelicula['descripcion'] ?? ''); ?></textarea>
                <?php if (isset($errores['descripcion'])): ?>
                    <span class="error"><?php echo $errores['descripcion']; ?></span>
                <?php endif; ?>

                <label>Director:</label>
                <input type="text" name="director" value="<?php echo htmlspecialchars($pelicula['director'] ?? ''); ?>" required>
                <?php if (isset($errores['director'])): ?>
                    <span class="error"><?php echo $errores['director']; ?></span>
                <?php endif; ?>

                <label>Duración (minutos):</label>
                <input type="number" name="duracion" value="<?php echo htmlspecialchars($pelicula['duracion'] ?? ''); ?>" required>
                <?php if (isset($errores['duracion'])): ?>
                    <span class="error"><?php echo $errores['duracion']; ?></span>
                <?php endif; ?>

                <label>Género:</label>
                <input type="text" name="genero" value="<?php echo htmlspecialchars($pelicula['genero'] ?? ''); ?>" required>
                <?php if (isset($errores['genero'])): ?>
                    <span class="error"><?php echo $errores['genero']; ?></span>
                <?php endif; ?>

                <label>Fecha de Estreno:</label>
                <input type="date" name="fecha_estreno" value="<?php echo htmlspecialchars($pelicula['fecha_estreno'] ?? ''); ?>" required>
                <?php if (isset($errores['fecha_estreno'])): ?>
                    <span class="error"><?php echo $errores['fecha_estreno']; ?></span>
                <?php endif; ?>

                <label>Trailer (URL):</label>
                <input type="text" name="trailer" value="<?php echo htmlspecialchars($pelicula['trailer_url'] ?? ''); ?>">

                <label>Imagen (dejar en blanco si no se cambia):</label>
                <input type="file" name="imagen">

                <button type="submit">Guardar Cambios</button>
            </form>
            <?php else: ?>
    <!-- Formulario de creación -->
    <h2>Añadir Nueva Película</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Título:</label>
        <input type="text" name="titulo" required>
        <?php if (isset($errores['titulo'])): ?>
            <span class="error"><?php echo $errores['titulo']; ?></span>
        <?php endif; ?>

        <label>Descripción:</label>
        <textarea name="descripcion" required></textarea>
        <?php if (isset($errores['descripcion'])): ?>
            <span class="error"><?php echo $errores['descripcion']; ?></span>
        <?php endif; ?>

        <label>Director:</label>
        <input type="text" name="director" required>
        <?php if (isset($errores['director'])): ?>
            <span class="error"><?php echo $errores['director']; ?></span>
        <?php endif; ?>

        <label>Duración (minutos):</label>
        <input type="number" name="duracion" required>
        <?php if (isset($errores['duracion'])): ?>
            <span class="error"><?php echo $errores['duracion']; ?></span>
        <?php endif; ?>

        <label>Género:</label>
        <input type="text" name="genero" required>
        <?php if (isset($errores['genero'])): ?>
            <span class="error"><?php echo $errores['genero']; ?></span>
        <?php endif; ?>

        <label>Fecha de Estreno:</label>
        <input type="date" name="fecha_estreno" required>
        <?php if (isset($errores['fecha_estreno'])): ?>
            <span class="error"><?php echo $errores['fecha_estreno']; ?></span>
        <?php endif; ?>

        <label>Trailer (URL):</label>
        <input type="text" name="trailer">

        <label>Imagen:</label>
        <input type="file" name="imagen">

        <!-- Nuevo campo para el estado -->
        <label>Estado:</label>
        <select name="estado" required>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
        </select>
        <?php if (isset($errores['estado'])): ?>
            <span class="error"><?php echo $errores['estado']; ?></span>
        <?php endif; ?>

        <button type="submit">Agregar Película</button>
    </form>
<?php endif; ?>

<h2>Lista de Películas</h2>
<table>
    <thead>
        <tr>
            <th>Título</th>
            <th>Descripción</th>
            <th>Director</th>
            <th>Duración</th>
            <th>Imagen</th>
            <th>Fecha de Estreno</th>
            <th>Género</th>
            <th>Trailer</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($pelicula = $peliculas->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($pelicula['titulo']); ?></td>

                <!-- Descripción oculta por defecto -->
                <td>
                    <button class="ver-descripcion" onclick="verDescripcion('<?php echo $pelicula['id']; ?>')">Ver descripción</button>
                    <div id="descripcion-<?php echo $pelicula['id']; ?>" class="descripcion-completa" style="display:none;">
                        <?php echo htmlspecialchars($pelicula['descripcion']); ?>
                    </div>
                </td>

                <td><?php echo htmlspecialchars($pelicula['director']); ?></td>
                <td><?php echo htmlspecialchars($pelicula['duracion']); ?> min</td>
                <td><img src="../assets/images/<?php echo htmlspecialchars($pelicula['imagen']); ?>" alt="<?php echo htmlspecialchars($pelicula['titulo']); ?>" width="100"></td>
                <td><?php echo htmlspecialchars($pelicula['fecha_estreno']); ?></td>
                <td><?php echo htmlspecialchars($pelicula['genero']); ?></td>
                <td><a href="<?php echo htmlspecialchars($pelicula['trailer_url']); ?>" target="_blank">Ver Trailer</a></td>

                <!-- Columna Estado -->
                <td>
                    <?php if (isset($pelicula['estado'])): ?>
                        <?php if ($pelicula['estado'] == 1): ?>
                            <span class="estado activo">Activo</span>
                        <?php elseif ($pelicula['estado'] == 0): ?>
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
                    <a href="?action=edit&id=<?php echo $pelicula['id']; ?>">Editar</a>
                    <a href="?action=delete&id=<?php echo $pelicula['id']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar esta película?');">Eliminar</a>

                    <!-- Mover el botón de activar/desactivar a esta columna -->
                    <?php if (isset($pelicula['estado'])): ?>
                        <?php if ($pelicula['estado'] == 1): ?>
                            <a href="?action=changeStatus&id=<?php echo $pelicula['id']; ?>&estado=0" onclick="return confirm('¿Estás seguro de que quieres desactivar esta película?');">Desactivar</a>
                        <?php elseif ($pelicula['estado'] == 0): ?>
                            <a href="?action=changeStatus&id=<?php echo $pelicula['id']; ?>&estado=1" onclick="return confirm('¿Estás seguro de que quieres activar esta película?');">Activar</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>




        <div class="pagination">
            <?php if ($paginaActual > 1): ?>
                <a href="menupeliculas.php?page=<?php echo $paginaActual - 1; ?>">Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?></span>
            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="menupeliculas.php?page=<?php echo $paginaActual + 1; ?>">Siguiente</a>
            <?php endif; ?>
        </div>
        <script>
    // Función para mostrar la descripción completa
    function verDescripcion(id) {
        var descripcionDiv = document.getElementById('descripcion-' + id);
        if (descripcionDiv.style.display === 'none') {
            descripcionDiv.style.display = 'block';
        } else {
            descripcionDiv.style.display = 'none';
        }
    }
</script>

    </main>
</body>
</html>