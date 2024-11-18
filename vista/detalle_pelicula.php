<?php

require_once('../includes/db_conn.php');
require_once('../modelo/aperitivo.php');
require_once('../modelo/funcion.php');
require_once('../modelo/pelicula.php');
require_once('../modelo/usuario.php');

session_start();



if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
    $user_id = $_SESSION['id'];
    $user_name = $_SESSION['user'];
    $is_logged_in = true;
} else {
    $user_id = null;
    $user_name = null;
    $is_logged_in = false;
}

$usuario = new Usuario($conn);
$perfil_usuario = $usuario->obtenerPerfil($user_id);


$id = $_GET['id'] ?? '';

function convertToEmbedURL($url) {
    $parsed_url = parse_url($url);
    if (isset($parsed_url['host'])) {
        if ($parsed_url['host'] === 'youtu.be') {
            $video_id = ltrim($parsed_url['path'], '/');
            return "https://www.youtube.com/embed/" . $video_id;
        } elseif ($parsed_url['host'] === 'youtube.com') {
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                if (isset($query_params['v'])) {
                    return "https://www.youtube.com/embed/" . $query_params['v'];
                }
            }
        }
    }
    return $url;
}

if ($id) {
    // Crear una instancia de la clase Pelicula
    $peliculaObj = new Pelicula($conn);

    // Llamar al método buscarPeliculaPorId para obtener la película por ID
    $pelicula = $peliculaObj->buscarPeliculaPorId($id);

    if (!$pelicula) {
        echo "Película no encontrada.";
        exit();
    }

    // Convertir la URL del tráiler para mostrar en formato embebido
    $pelicula['trailer_url'] = convertToEmbedURL($pelicula['trailer_url']);
} else {
    echo "ID de película no proporcionado.";
    exit();
}

// Obtener los horarios de las funciones de la película
$funcion = new Funcion($conn);
$funciones = $funcion->getHorarios($id); // Aquí pasamos el ID de la película

// Obtener los aperitivos disponibles
$aperitivo = new Aperitivo($conn);
$aperitivos = $aperitivo->obtenerAperitivosDisponibles();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pelicula['titulo']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/detalle_pelicula.css">
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        .showtimes-table {
            width: 100%;
            border-collapse: collapse;
        }
        .showtimes-table th, .showtimes-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .showtimes-table .details {
            display: none;
        }
        .showtimes-table .details.open {
            display: table-row;
        }
        .toggle-details {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
        }
        .seating {
            padding: 20px;
            border-top: 2px solid #ddd;
        }
        .seat.available {
            background-color: #4CAF50;
            color: white;
        }
        .seat.unavailable {
            background-color: #f44336;
            color: white;
        }
        .seat.selected {
            background-color: #2196F3;
        }
        .block {
            color: red;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo"><img src="../assets/images/pngs/logocine.png" alt=""></a>
        <div class="nav-icon">
            <a href="https://www.instagram.com/cinesantarosa/" target="_blank"><i class='bx bxl-instagram'></i></a>
            <a href="https://www.facebook.com/CinesSantaRosa" target="_blank"><i class='bx bxl-facebook-circle'></i></a>
            <a href="https://www.google.com/maps/place/Cine+Milenium/@-36.6230078,-64.2946724,15z/data=!4m2!3m1!1s0x0:0xb4c0bd2ec262ec5f?sa=X&ved=1t:2428&ictx=111"><i class='bx bx-pin'></i></a>

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
    <section id="inicio" class="main-films">
        <div class="container">
            <div class="poster">
                <img src="/tesisMVC/assets/images/<?php echo htmlspecialchars($pelicula['imagen']); ?>" alt="<?php echo htmlspecialchars($pelicula['titulo']); ?>">
            </div>
            <div class="details">
                <h1><?php echo htmlspecialchars($pelicula['titulo']); ?></h1>
                <p><span class="label">Sinopsis:</span> <?php echo htmlspecialchars($pelicula['descripcion']); ?></p>
                <p><span class="label">Director:</span> <?php echo isset($pelicula['director']) ? htmlspecialchars($pelicula['director']) : 'Información no disponible'; ?></p>
                <p><span class="label">Género:</span> <?php echo htmlspecialchars($pelicula['genero']); ?></p>
                <p><span class="label">Duración:</span> <?php echo htmlspecialchars($pelicula['duracion']); ?> minutos</p>
                <p><span class="label">Fecha de Estreno:</span> <?php echo htmlspecialchars($pelicula['fecha_estreno']); ?></p>
            </div>
        </div>
    </section>
    
    <!-- Trailer Section -->
    <section>
        <?php if (!empty($pelicula['trailer_url'])): ?>
            <section class="trailer">
                <h2>Tráiler</h2>
                <iframe src="<?php echo htmlspecialchars($pelicula['trailer_url']); ?>" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
            </section>
        <?php else: ?>
            <section class="trailer">
                <h2>Tráiler</h2>
                <p>No hay tráiler disponible.</p>
            </section>
        <?php endif; ?>
    </section>
    
    <section class="showtimes">
    <h2>Horarios de Funciones</h2>
    <?php if ($is_logged_in): ?>
        <<table class="showtimes-table">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Sala</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($funciones)): ?>
            <?php foreach ($funciones as $horario): ?>
                <tr data-funcion-id="<?php echo htmlspecialchars($horario['id_funcion']); ?>"
                    data-pelicula-id="<?php echo htmlspecialchars($pelicula['id']); ?>">
                    <td><?php echo htmlspecialchars($horario['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($horario['hora']); ?></td>
                    <td><?php echo htmlspecialchars($horario['sala']); ?></td>
                    <td class='actions'>
                        <span class="toggle-details" onclick="toggleDetails(this, <?php echo htmlspecialchars($horario['id_funcion']); ?>)">Ver Asientos</span>
                    </td>
                </tr>
                        <tr class="details">
                            <td colspan="4">
                            <section class="seating">
                            <h2>Selecciona tus Asientos</h2>
                                <form action="" method="POST">
                                    <input type="hidden" name="funcion_id" value="<?php echo htmlspecialchars($horario['id_funcion']); ?>">
                                    <input type="hidden" name="asientos" id="asientos-<?php echo $horario['id_funcion']; ?>" value="">

                                     <div class="screen">Pantalla</div>
                                        <div class="seats">
                                        <?php
                                            $funcion_id = $horario['id_funcion'];
                                            $funcionObj = new Funcion($conn);
                                            $asientos = $funcionObj->obtenerAsientosPorFuncion($funcion_id);
                                            if ($asientos) {
                                                foreach ($asientos as $seat): ?>
                                                    <div class="seat <?php echo $seat['disponible'] ? 'available' : 'unavailable'; ?>"
                                                    data-id="<?php echo htmlspecialchars($seat['id']); ?>"
                                                    data-fila="<?php echo htmlspecialchars($seat['fila']); ?>" 
                                                    data-numero="<?php echo htmlspecialchars($seat['numero']); ?>"
                                                    data-disponible="<?php echo $seat['disponible'] ? 'true' : 'false'; ?>"
                                                    onclick="toggleSeat(this, <?php echo $horario['id_funcion']; ?>)">
                                                    <?php echo htmlspecialchars($seat['fila'] . $seat['numero']); ?>
                                                     </div>
                                                <?php endforeach; 
                                            } else {
                                                echo '<p>No hay asientos disponibles para esta función.</p>';
                                            }
                                            ?>
                                        </div>
                                </form>
                            </section>

                                    <section class="snack-menu">
                                        <h2>¡Comprá ahora y ahorra tiempo!</h2>
                                        <div class="snack-items">
                                            <?php if (!empty($aperitivos)): ?>
                                                <?php foreach ($aperitivos as $aperitivo): ?>
                                                    <div class="snack-item">
                                                        <img src="../assets/images/<?php echo htmlspecialchars($aperitivo['imagen']); ?>" alt="<?php echo htmlspecialchars($aperitivo['nombre']); ?>">
                                                        <h3><?php echo htmlspecialchars($aperitivo['nombre']); ?></h3>
                                                        <p class="price">$<?php echo htmlspecialchars($aperitivo['precio']); ?></p>
                                                        <button onclick="addToCart(<?php echo $horario['id_funcion']; ?>, <?php echo $aperitivo['id']; ?>, '<?php echo addslashes($aperitivo['nombre']); ?>', <?php echo $aperitivo['precio']; ?>, true)">AGREGAR</button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p>No hay aperitivos disponibles.</p>
                                            <?php endif; ?>
                                        </div>
                                    </section>

                                    <section class="cart" id="cart-<?php echo $horario['id_funcion']; ?>">
                                        <h2>Detalle de compra</h2>
                                        <table class="cart-table">
                                            <thead>
                                                <tr>
                                                    <th>Detalle</th>
                                                    <th>Precio</th>
                                                    <th>Opción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cart-details-<?php echo $horario['id_funcion']; ?>"></tbody>
                                        </table>
                                        <p id="total-price-<?php echo $horario['id_funcion']; ?>">TOTAL: $0.00</p>
                                        <button class="checkout-btn">PREPARAR PAGO</button>
                                    </section>
                                </section>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No hay funciones disponibles para esta película.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert-message">Iniciar sesión para visualizar información de funciones.</div>
    <?php endif; ?>
</section>
    <script src='../js/scripts.js'></script>
</body>
</html>
