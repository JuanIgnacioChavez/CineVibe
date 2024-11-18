<?php 

require_once('../modelo/usuario.php');

class UsuarioController {
    private $usuario;
    private $message = "";

    public function __construct($conn, $usuariosPorPagina = 5) {
        $this->usuario = new Usuario($conn, $usuariosPorPagina); // Pasar el valor de usuariosPorPagina
    }

    public function gestionarFormulario($post) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = isset($post['id']) ? intval($post['id']) : null; // Almacenar ID para ambos casos
            if ($id) {
                // Actualizar un usuario
                $success = $this->usuario->actualizarUsuario(
                    $post['id'],
                    $post['perfil'],
                    $post['nombre'],
                    $post['apellido'],
                    $post['user'],
                    $post['telefono'],
                    $post['documento']
                );
                $this->message = $success ? "Usuario actualizado con éxito." : "Error al actualizar el usuario.";
            } else {
                // Agregar un nuevo usuario
                $success = $this->usuario->agregarUsuario(
                    $post['perfil'],
                    $post['nombre'],
                    $post['apellido'],
                    $post['user'],
                    $post['telefono'],
                    $post['documento']
                );
                $this->message = $success ? "Usuario agregado con éxito." : "Error al agregar el usuario.";
            }
        }
    }

    public function eliminarUsuario($id) {
        $success = $this->usuario->eliminarUsuario($id);
        $this->message = $success ? "Usuario eliminado con éxito." : "Error al eliminar el usuario.";
    }

    public function obtenerUsuarioParaEdicion($id) {
        return $this->usuario->obtenerUsuario($id);
    }

    public function listarUsuariosConPaginacion($paginaActual) {
        $offset = ($paginaActual - 1) * $this->usuario->usuariosPorPagina; // Calcular el offset
        return $this->usuario->listarUsuarios($offset);
    }

    public function obtenerTotalPaginas() {
        $totalUsuarios = $this->usuario->contarUsuarios();
        return ceil($totalUsuarios / $this->usuario->usuariosPorPagina); // Uso de la propiedad
    }

    public function getMessage() {
        return $this->message;
    }
}

$controller = new UsuarioController($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $controller->gestionarFormulario($_POST);
}

$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($action === 'delete' && $id !== null) {
    $controller->eliminarUsuario($id);
}

$usuario = ($action === 'edit' && $id !== null)
    ? $controller->obtenerUsuarioParaEdicion($id)
    : null;

$paginaActual = isset($_GET['page']) ? intval($_GET['page']) : 1;
$totalPaginas = $controller->obtenerTotalPaginas();
$usuarios = $controller->listarUsuariosConPaginacion($paginaActual);
$message = $controller->getMessage();

?>

