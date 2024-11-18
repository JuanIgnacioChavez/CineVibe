<?php

session_start();

require_once('../includes/db_conn.php');
require_once('../modelo/pelicula.php');
require_once('../modelo/usuario.php');

$user_name = null;
$user_id = null;

if(isset($_SESSION['id']) && isset($_SESSION['user'])){
    $user_id = $_SESSION['id']; // Obtenemos el ID del usuario
    $user_name = $_SESSION['user'];
}

$pelicula = new Pelicula($conn);
$peliculas = $pelicula->obtenerPeliculasDisponibles();

$usuario = new Usuario($conn);
$perfil_usuario = $usuario->obtenerPerfil($user_id);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB CINE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header>
        <a class="logo"><img src="../assets/images/pngs/logocine.png" alt=""></a>
        <ul class="navmenu">
            <li><a href="#inicio">inicio</a></li>
            <li><a href="#cartelera">cartelera</a></li>
            <li><a href="#contacto">contacto</a></li>
        </ul>
        <div class="nav-icon">
            <a href="https://www.instagram.com/cinesantarosa/" target="_blank"><i class='bx bxl-instagram'></i></a>
            <a href="https://www.facebook.com/CinesSantaRosa" target="_blank"><i class='bx bxl-facebook-circle'></i></a>
            <a href="#contacto"><i class='bx bx-pin'></i></a>

            <!-- Verifica si el usuario ha iniciado sesión -->
            <?php if($user_name): ?>
                <div class="dropdown">
                    <button class="dropbtn"><?php echo htmlspecialchars($user_name); ?></button>
                    <div class="dropdown-content">
                        <a href="settings.php">Mi cuenta</a>
                        <?php if ($perfil_usuario == 1): ?>
                            <a href="menuadmin.php">Panel de Administración</a>
                        <?php endif; ?>
                        <a href="../login/logout.php">Cerrar Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php"><i class='bx bxs-user'></i></a>
            <?php endif; ?>
        </div>
    </header>

    <!--MAIN SECTION-->
    <section id="inicio" class="main-home">
        <div class="main-text">
            <h1>¡Nuevos estrenos <br>todas las semanas!</h1>
            <p>No te los pierdas</p>
            <a href="#cartelera" class="main-btn">Ver Ahora <i class='bx bx-right-arrow-alt'></i></a>
        </div>
    </section>

<!--CARTELERA SECTION-->
<section id="cartelera" class="cartelera">
    <div class="center-text">
        <h2>Nuestros Últimos Estrenos</h2>
    </div>   

    <div class="peliculas">
        <?php if (count($peliculas) > 0): ?>
            <?php foreach ($peliculas as $pelicula): ?>
                <a href='detalle_pelicula.php?id=<?php echo htmlspecialchars($pelicula["id"]); ?>' class="pelicula-link">
                    <div class="pelicula">
                        <img src="/tesisMVC/assets/images/<?php echo htmlspecialchars($pelicula['imagen']); ?>" alt="<?php echo htmlspecialchars($pelicula['titulo']); ?>">
                        <h3><?php echo htmlspecialchars($pelicula['titulo']); ?></h3>
                        <p><strong>Fecha de Estreno:</strong> <?php echo htmlspecialchars($pelicula['fecha_estreno']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay películas activas para mostrar.</p>
        <?php endif; ?>
    </div>
</section>

    <!--CONTACTO SECTION-->
    <section id="contacto" class="contacto">
        <div class="left-text">
            <h2>Donde Encontrarnos</h2>
        </div>
        <div class="mapa">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3202.140545203418!2d-64.29724732331367!3d-36.62300346692975!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95c2cd0689920f4f%3A0xb4c0bd2ec262ec5f!2sCine%20Milenium!5e0!3m2!1ses!2sar!4v1724309271493!5m2!1ses!2sar" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>

    <div class="end-text">
        <p>Copyright @2024. Todos los Derechos Reservados. Diseñado por Juan Chavez  </p>
    </div>

    <script>
       // inicio
       document.querySelector('a[href="#inicio"]').addEventListener('click', function(event) {
           event.preventDefault(); // Prevenir el comportamiento por defecto
           document.getElementById('inicio').scrollIntoView({ behavior: 'smooth' });
           window.history.pushState(null, null, window.location.pathname); // Limpiar el fragmento #inicio de la URL
       });

       // cartelera
       document.querySelector('a[href="#cartelera"]').addEventListener('click', function(event) {
           event.preventDefault();
           document.getElementById('cartelera').scrollIntoView({ behavior: 'smooth' });
           window.history.pushState(null, null, window.location.pathname); // Limpiar el fragmento #cartelera de la URL
       });

       // contacto
       document.querySelector('a[href="#contacto"]').addEventListener('click', function(event) {
           event.preventDefault();
           document.getElementById('contacto').scrollIntoView({ behavior: 'smooth' });
           window.history.pushState(null, null, window.location.pathname); // Limpiar el fragmento #contacto de la URL
       });
    </script>

</body>
</html>

<?php
$conn->close();
?>
