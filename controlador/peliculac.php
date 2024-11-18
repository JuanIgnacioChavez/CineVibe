<?php

class PeliculaController {
    private $pelicula;
    private $message = "";

    public function __construct($conn) {
        $this->pelicula = new Pelicula($conn);
    }

    public function gestionarFormulario($post, $files) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($post['id']) ? intval($post['id']) : null;  // Store ID for both cases
            if ($id) {
                // Update a movie
                $success = $this->pelicula->actualizarPelicula(
                    $id,
                    $post['titulo'],
                    $post['descripcion'],
                    $post['director'],
                    intval($post['duracion']),
                    $post['genero'],
                    $post['fecha_estreno'],
                    $post['trailer'],  // Pass the trailer URL directly
                    $files['imagen'] ?? null,
                    $post['estado']
                );
                $this->message = $success ? "Película actualizada con éxito." : "Error al actualizar la película.";
            } else {
                // Add a new movie
                $success = $this->pelicula->agregarPelicula(
                    $post['titulo'],
                    $post['descripcion'],
                    $post['director'],
                    intval($post['duracion']),
                    $post['genero'],
                    $post['fecha_estreno'],
                    $post['trailer'],  // Pass the trailer URL directly
                    $files['imagen'],
                    $post['estado']
                );
                $this->message = $success ? "Película agregada con éxito." : "Error al agregar la película.";
            }
        }
    }

    public function eliminarPelicula($id) {
        $success = $this->pelicula->eliminarPelicula($id);
        $this->message = $success ? "Película eliminada con éxito." : "Error al eliminar la película.";  // Fixed ternary operator
    }

    public function obtenerPeliculaParaEdicion($id) {
        return $this->pelicula->obtenerPelicula($id);
    }

    public function listarPeliculasConPaginacion($paginaActual, $peliculasPorPagina) {
        $offset = ($paginaActual - 1) * $peliculasPorPagina;
        return $this->pelicula->listarPeliculas($offset, $peliculasPorPagina);
    }

    public function obtenerTotalPaginas($peliculasPorPagina) {
        $totalPeliculas = $this->pelicula->contarPeliculas();  // Fixed $this->peliculas to $this->pelicula
        return ceil($totalPeliculas / $peliculasPorPagina);
    }

    public function getMessage() {
        return $this->message;
    }

    
}

if (isset($_GET['id_pelicula'])) {
    $id_pelicula = $_GET['id_pelicula'];
    
    try {
        $pelicula = new Pelicula($conn);
        $precio = $pelicula->obtenerPrecioTicket($id_pelicula);
        
        echo json_encode([
            'success' => true,
            'precio' => $precio
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener el precio'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'ID de película no proporcionado'
    ]);
}

$controller = new PeliculaController($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {  // Fixed method check
    $controller->gestionarFormulario($_POST, $_FILES);
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {  // Fixed syntax error
    $controller->eliminarPelicula(intval($_GET['id']));
}

$pelicula = isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])
    ? $controller->obtenerPeliculaParaEdicion(intval($_GET['id']))
    : null;

$paginaActual = isset($_GET['page']) ? intval($_GET['page']) : 1;
$peliculasPorPagina = 5;  
$totalPaginas = $controller->obtenerTotalPaginas($peliculasPorPagina);
$peliculas = $controller->listarPeliculasConPaginacion($paginaActual, $peliculasPorPagina);
$message = $controller->getMessage();

?>