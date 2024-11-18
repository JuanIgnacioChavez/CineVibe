<?php

class FuncionController {
    private $funcion;
    private $message = "";
    public $peliculas; // Agregar esta propiedad

    public function __construct($conn, $funcionesPorPagina = 5) {
        $this->funcion = new Funcion($conn, $funcionesPorPagina); // Pasar el valor de funcionesPorPagina
        $this->peliculas = $this->funcion->obtenerPeliculas(); // Obtener las películas
    }

    public function gestionarFormulario($post, $files) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = isset($post['id']) ? intval($post['id']) : null;  // Almacenar ID para ambos casos
            if ($id) {
                // Actualizar una función
                $success = $this->funcion->actualizarFuncion(
                    $post['id'],
                    $post['id_pelicula'],
                    $post['fecha'],
                    $post['hora'],
                    $post['sala']
                );
                $this->message = $success ? "Función actualizada con éxito." : "Error al actualizar la función.";
            } else {
                // Agregar una nueva función
                $success = $this->funcion->agregarFuncion(
                    $post['id_pelicula'],
                    $post['fecha'],
                    $post['hora'],
                    $post['sala']
                );
                $this->message = $success ? "Función agregada con éxito." : "Error al agregar la función.";
            }
        }
    }

    public function eliminarFuncion($id) {
        $success = $this->funcion->eliminarFuncion($id);
        $this->message = $success ? "Función eliminada con éxito." : "Error al eliminar la función.";
    }

    public function obtenerFuncionParaEdicion($id) {
        return $this->funcion->obtenerFuncion($id);
    }

    public function listarFuncionesConPaginacion($paginaActual) {
        $offset = ($paginaActual - 1) * $this->funcion->funcionesPorPagina; // Calcular el offset
        return $this->funcion->listarFunciones($offset);
    }

    public function obtenerTotalPaginas() {
        $totalFunciones = $this->funcion->contarFunciones();
        return ceil($totalFunciones / $this->funcion->funcionesPorPagina); // Uso de la propiedad
    }

    public function getMessage() {
        return $this->message;
    }
}

$controller = new FuncionController($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $controller->gestionarFormulario($_POST, $_FILES);
}

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($action === 'delete' && $id !== null) {
    $controller->eliminarFuncion($id);
}

$funcion = ($action === 'edit' && $id !== null)
    ? $controller->obtenerFuncionParaEdicion($id)
    : null;

$paginaActual = isset($_GET['page']) ? intval($_GET['page']) : 1;
$funcionesPorPagina = 5; // Si necesitas cambiar este valor, hazlo aquí
$totalPaginas = $controller->obtenerTotalPaginas();
$funciones = $controller->listarFuncionesConPaginacion($paginaActual);
$message = $controller->getMessage();


?>