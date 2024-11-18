<?php
require_once('../includes/db_conn.php');
require_once('../modelo/funcion.php');

// Obtener el ID de la función desde la solicitud
$funcion_id = isset($_GET['funcion_id']) ? (int)$_GET['funcion_id'] : 0;

// Validar que el ID de función sea válido
if ($funcion_id > 0) {
    $funcionObj = new Funcion($conn);
    $asientos = $funcionObj->obtenerAsientosPorFuncion($funcion_id);

    // Verificar si hay asientos disponibles
    if (count($asientos) > 0) {
        // Crear el HTML para los asientos
        $html_asientos = '';
        foreach ($asientos as $seat) {
            $disponible = $seat['disponible'] ? 'available' : 'unavailable';
            $html_asientos .= '<div class="seat ' . $disponible . '" 
                                    data-fila="' . htmlspecialchars($seat['fila']) . '" 
                                    data-numero="' . htmlspecialchars($seat['numero']) . '"
                                    data-disponible="' . ($seat['disponible'] ? 'true' : 'false') . '">
                                    ' . htmlspecialchars($seat['fila'] . $seat['numero']) . '
                                </div>';
        }

        // Enviar el HTML de los asientos y los datos JSON
        echo json_encode([
            'html' => $html_asientos, // HTML para los asientos
            'success' => true          // Indicamos que la carga fue exitosa
        ]);
    } else {
        // Si no hay asientos cargados para la función, enviar mensaje
        echo json_encode([
            'html' => '',              // HTML vacío si no hay asientos
            'success' => false,        // Indicamos que la carga falló
            'mensaje' => 'No hay asientos cargados para esta función.' // Mensaje adicional
        ]);
    }
} else {
    // Si no hay una función válida seleccionada, enviar respuesta vacía
    echo json_encode([
        'html' => '',              // HTML vacío si no hay asientos
        'success' => false,        // Indicamos que la carga falló
        'mensaje' => 'Función no válida.' // Mensaje adicional
    ]);
}



?>
