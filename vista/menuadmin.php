<?php

session_start();  

require_once('../modelo/usuario.php');
require_once('../includes/db_conn.php');

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


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
</head>
<body>
<div class="wrapper">
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
    <!-- Contenido Principal -->
    <main>
        <header>
            <!-- Mostrar bienvenida con el nombre del usuario -->
            <p class="bienvenida">Bienvenido, <?php echo $_SESSION['user']; ?>!</p>
            <img src="../assets/images/pngs/logocine.png" alt="Logo del Cine" class="logo">
            <h1>Panel de Administración del Cine</h1>
        </header>
    </main>
</div>

</body>
</html>