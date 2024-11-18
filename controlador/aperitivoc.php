<?php

require_once('../modelo/aperitivo.php');

class AperitivoController {
    private $aperitivo;
    private $message = "";

    public function __construct($conn) {
        $this->aperitivo = new Aperitivo($conn);
    }

    public function gestionarFormulario($post, $files) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($post['id'])) {
                // Actualizar aperitivo
                $success = $this->aperitivo->actualizarAperitivo(
                    intval($post['id']),
                    $post['nombre'],
                    $post['descripcion'],
                    floatval($post['precio']),
                    $files['imagen'] ?? null
                );
                $this->message = $success ? "Aperitivo actualizado con éxito." : "Error al actualizar el aperitivo.";
            } else {
                // Agregar aperitivo
                $success = $this->aperitivo->agregarAperitivo(
                    $post['nombre'],
                    $post['descripcion'],
                    floatval($post['precio']),
                    $files['imagen']
                );
                $this->message = $success ? "Aperitivo agregado con éxito." : "Error al agregar el aperitivo.";
            }
        }
    }

    public function eliminarAperitivo($id) {
        $success = $this->aperitivo->eliminarAperitivo($id);
        $this->message = $success ? "Aperitivo eliminado con éxito." : "Error al eliminar el aperitivo.";
    }

    public function obtenerAperitivoParaEdicion($id) {
        return $this->aperitivo->obtenerAperitivo($id);
    }

    public function listarAperitivosConPaginacion($paginaActual, $aperitivosPorPagina) {
        $offset = ($paginaActual - 1) * $aperitivosPorPagina;
        return $this->aperitivo->listarAperitivos($offset, $aperitivosPorPagina);
    }

    public function obtenerTotalPaginas($aperitivosPorPagina) {
        $totalAperitivos = $this->aperitivo->contarAperitivos();
        return ceil($totalAperitivos / $aperitivosPorPagina);
    }

    public function getMessage() {
        return $this->message;
    }
}

// Ejecución del controlador
$controller = new AperitivoController($conn);

if (!empty($_POST)) {
    $controller->gestionarFormulario($_POST, $_FILES);
}
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $controller->eliminarAperitivo(intval($_GET['id']));
}

$aperitivo = isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])
    ? $controller->obtenerAperitivoParaEdicion(intval($_GET['id']))
    : null;

$paginaActual = isset($_GET['page']) ? intval($_GET['page']) : 1;
$aperitivosPorPagina = 5;
$aperitivos = $controller->listarAperitivosConPaginacion($paginaActual, $aperitivosPorPagina);
$totalPaginas = $controller->obtenerTotalPaginas($aperitivosPorPagina);
$message = $controller->getMessage();

?>