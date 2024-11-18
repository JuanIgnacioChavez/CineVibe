<?php
require_once('../includes/db_conn.php');
require_once('../modelo/usuario.php');
require_once('../modelo/funcion.php');

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

// Instanciar la clase Funcion
$funcionObj = new Funcion($conn);
$funciones = $funcionObj->listarFunciones(0); // Obtener funciones

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Asientos</title>
    <link rel="stylesheet" href="../assets/css/forms.css">
    <script>
    // Función para cargar los asientos según la función seleccionada
    function cargarAsientos() {
        const funcionId = document.getElementById('funcion').value;

        if (!funcionId) {
            document.querySelector('.seats').innerHTML = ''; // Limpiar si no hay función seleccionada
            alert("Por favor, seleccione una función.");
            return;
        }

        fetch('../controlador/asientosc.php?funcion_id=' + funcionId)
            .then(response => response.json())  // Convertir la respuesta a JSON
            .then(data => {
                if (data.success) {
                    document.querySelector('.seats').innerHTML = data.html; // Insertar el HTML de los asientos
                } else {
                    // Si no hay asientos, mostrar mensaje
                    document.querySelector('.seats').innerHTML = '<p>' + data.mensaje + '</p>';
                }
            })
            .catch(error => console.error('Error al cargar asientos:', error));
    }
</script>

    <style>
        .seats {
            display: grid;
            grid-template-columns: repeat(15, 1fr); /* Muestra 15 asientos por fila */;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px; /* Espacio entre asientos */
            padding: 10px;
        }

        .seat {
            width: 40px; /* Ajusta el ancho del asiento */
            height: 40px; /* Ajusta la altura del asiento */
            display: grid;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.8em;
            background-color: #4CAF50; /* Asiento disponible */
            cursor: pointer;
            border-radius: 5px;
        }

        .seat.unavailable {
            background-color: #FF4D4D; /* Asiento no disponible */
        }

        .seat.selected {
            background-color: #FFD700; /* Asiento seleccionado */
        }

        .screen {
            text-align: center;
            margin-bottom: 10px;
            font-size: 1.2em;
            color: #fff;
            background-color: #333;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
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
        <h2>Gestión de Asientos</h2>

        <!-- Formulario para seleccionar función -->
        <form id="form-funcion">
            <label for="funcion">Seleccione una función:</label>
            <select name="funcion_id" id="funcion">
                <option value="">Seleccione una función</option>
                <?php while($row = $funciones->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['pelicula_titulo'] . " - " . $row['fecha'] . " " . $row['hora']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="button" onclick="cargarAsientos()">Ver Asientos</button>
        </form>

        <div class="screen">Pantalla</div>
        <div class="seats" id="seats-container">
            <!-- Aquí se cargarán los asientos disponibles mediante AJAX -->
        </div>
    </main>
</body>
</html>
