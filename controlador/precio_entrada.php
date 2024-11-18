    <?php
    // Incluye el archivo donde está definida la clase que contiene obtenerPrecioEntrada

    require_once('../modelo/funcion.php');

    if (isset($_GET['funcion_id'])) {
        $funcion_id = intval($_GET['funcion_id']);
        $obj = new Funcion(); // Instancia la clase que tiene obtenerPrecioEntrada
        $precio = $obj->obtenerPrecioEntrada($funcion_id);

        // Retorna el precio en formato JSON
        echo json_encode(['precio' => $precio]);
    } else {
        echo json_encode(['error' => 'Función ID no proporcionada']);
    }
    ?>
