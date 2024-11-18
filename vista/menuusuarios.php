<?php

require_once('../includes/db_conn.php');
require_once('../controlador/usuarioc.php');
require_once('../modelo/usuario.php');

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

$user = new Usuario($conn);
$perfil_usuario = $user->obtenerPerfil($user_id);

$usuarie = new Usuario($conn);
$message = null;
$errores = array();

// Filtro de búsqueda
$buscarDocumento = isset($_GET['buscarDocumento']) ? $_GET['buscarDocumento'] : '';

if ($buscarDocumento) {
    // Consulta para buscar usuarios por número de documento
    $usuarios = $user->buscarPorDocumento($buscarDocumento);
    
    // Si no se encuentra ningún usuario
    if ($usuarios->num_rows == 0) {
        $_SESSION['message'] = 'No se encontró un usuario con ese documento.'; // Almacena el mensaje de error en sesión
        $usuarios = $user->obtenerTodosLosUsuarios();
    }
} else {
    $usuarios = $user->obtenerTodosLosUsuarios();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = $_POST;
    if (isset($datos['id'])) {
        // Editar usuario
        $resultado = $usuarie->actualizarUsuario($datos['id'], $datos);
        if ($resultado === true) {
            // Mensaje exitoso y redirección
            $_SESSION['message'] = 'Usuario actualizado con éxito.'; // Almacena el mensaje en sesión
            header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
            exit; // Detener la ejecución después de redirigir
        } else {
            $errores = $resultado;
            $message = 'Se encontraron errores al actualizar el usuario.';
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Eliminar usuario
    $id_usuario = $_GET['id'];
    $resultado_eliminacion = $usuarie->eliminarUsuario($id_usuario); // Suponiendo que hay un método eliminarUsuario()

    if ($resultado_eliminacion === true) {
        // Si la eliminación es exitosa
        $_SESSION['message'] = 'Usuario eliminado con éxito.';
    } else {
        // Si ocurrió un error durante la eliminación
        $_SESSION['message'] = 'Error al eliminar el usuario. Intenta nuevamente.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']); // Recargar la página
    exit; // Detener la ejecución después de redirigir
}


if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $usuarie = $usuarie->obtenerUsuario($_GET['id']);
} else {
    $usuarie = array(); // Asegúrate de que no haya datos previos si no estamos editando una película
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
    <title>Panel de Usuarios</title>
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

        <h2>Buscar Usuario</h2>
        <form method="GET" action="menuusuarios.php">
            <label for="buscarDocumento">Documento:</label>
            <input type="text" id="buscarDocumento" name="buscarDocumento" placeholder="Ingrese el número de documento">
            <button type="submit">Buscar</button>
        </form>

        <?php if (isset($usuario)): ?>
            <h2>Editar Usuario</h2>
            <form action="" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">

                <label for="perfil">Perfil:</label>
                <select id="perfil" name="perfil" required>
                    <option value="1" <?php echo $usuario['perfil'] == 1 ? 'selected' : ''; ?>>Admin</option>
                    <option value="0" <?php echo $usuario['perfil'] == 0 ? 'selected' : ''; ?>>Usuario Normal</option>
                </select>
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required readonly>
                <?php if (isset($errores['nombre'])): ?>
                    <span class="error"><?php echo $errores['nombre']; ?></span>
                <?php endif; ?>

                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required readonly>
                <?php if (isset($errores['apellido'])): ?>
                    <span class="error"><?php echo $errores['apellido']; ?></span>
                <?php endif; ?>

                <label for="email">Email:</label>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required readonly>
                <?php if (isset($errores['email'])): ?>
                    <span class="error"><?php echo $errores['email']; ?></span>
                <?php endif; ?>

                <label for="user">Usuario:</label>
                <input type="text" id="user" name="user" value="<?php echo htmlspecialchars($usuario['user']); ?>" required readonly>
                <?php if (isset($errores['user'])): ?>
                    <span class="error"><?php echo $errores['user']; ?></span>
                <?php endif; ?>

                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" required readonly>
                <?php if (isset($errores['telefono'])): ?>
                    <span class="error"><?php echo $errores['telefono']; ?></span>
                <?php endif; ?>

                <label for="documento">Documento:</label>
                <input type="text" id="documento" name="documento" value="<?php echo htmlspecialchars($usuario['documento']); ?>" required readonly>
                <?php if (isset($errores['documento'])): ?>
                    <span class="error"><?php echo $errores['documento']; ?></span>
                <?php endif; ?>

                <button type="submit">Guardar Cambios</button>
            </form>
        <?php endif; ?>

        <h2>Lista de Usuarios</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Perfil</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>correo</th>
                    <th>Usuario</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['perfil']); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['user']); ?></td>
                        <td><?php echo htmlspecialchars($row['documento']); ?></td>
                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                        <td class= 'actions'>
                            <a href="menuusuarios.php?action=edit&id=<?php echo $row['id']; ?>">Editar</a>
                            <a href="menuusuarios.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($paginaActual > 1): ?>
                <a href="menuusuarios.php?page=<?php echo $paginaActual - 1; ?>">Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?></span>
            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="menuusuarios.php?page=<?php echo $paginaActual + 1; ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
