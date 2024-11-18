<?php
require_once('../includes/db_conn.php');
require_once('../modelo/venta.php');
require_once('../controlador/ventasc.php');

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

// Inicializar el controlador
$controller = new VentaController($conn);

// Obtener los parámetros de paginación y filtros
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$ventasPorPagina = 10;
$buscarFecha = isset($_GET['buscarFecha']) ? $_GET['buscarFecha'] : null;
$buscarHora = isset($_GET['buscarHora']) ? $_GET['buscarHora'] : null;

// Obtener el total de páginas y las ventas con los filtros aplicados
$totalPaginas = $controller->obtenerTotalPaginas($buscarFecha, $buscarHora);
$ventas = $controller->obtenerVentasPaginadas($paginaActual, $buscarFecha, $buscarHora);

// Obtener mensaje del controlador si existe
$message = $controller->getMessage();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Ventas</title>
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

        <h2>Buscar Ventas por Fecha</h2>
        <form method="GET" action="">
            <label for="buscarFecha">Fecha (YYYY-MM-DD):</label>
            <input type="date" id="buscarFecha" name="buscarFecha" 
                   value="<?php echo htmlspecialchars($buscarFecha ?? ''); ?>">

            <label for="buscarHora">Hora (HH:MM):</label>
            <input type="time" id="buscarHora" name="buscarHora" 
                   value="<?php echo htmlspecialchars($buscarHora ?? ''); ?>">

            <!-- Mantener la página actual en el formulario -->
            <input type="hidden" name="page" value="1">
            <button type="submit">Buscar</button>
        </form>

        <h2>Lista de Ventas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>ID Usuario</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Función</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ventas && $ventas->num_rows > 0): ?>
                    <?php while ($row = $ventas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['monto']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                            <td><?php echo htmlspecialchars($row['hora']); ?></td>
                            <td><?php echo htmlspecialchars($row['funcion_id']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No se encontraron ventas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPaginas > 0): ?>
            <div class="pagination">
                <?php if ($paginaActual > 1): ?>
                    <a href="?page=<?php echo ($paginaActual - 1); ?>
                        <?php echo $buscarFecha ? '&buscarFecha=' . htmlspecialchars($buscarFecha) : ''; ?>
                        <?php echo $buscarHora ? '&buscarHora=' . htmlspecialchars($buscarHora) : ''; ?>">
                        Anterior
                    </a>
                <?php endif; ?>

                <span>Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?></span>

                <?php if ($paginaActual < $totalPaginas): ?>
                    <a href="?page=<?php echo ($paginaActual + 1); ?>
                        <?php echo $buscarFecha ? '&buscarFecha=' . htmlspecialchars($buscarFecha) : ''; ?>
                        <?php echo $buscarHora ? '&buscarHora=' . htmlspecialchars($buscarHora) : ''; ?>">
                        Siguiente
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>