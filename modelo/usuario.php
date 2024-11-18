<?php

class Usuario {
    private $conn;
    public $usuariosPorPagina;

    public function __construct($dbConnection, $usuariosPorPagina = 5) {
        $this->conn = $dbConnection;
        $this->usuariosPorPagina = $usuariosPorPagina; // Asignar valor por defecto
    }
    
    
    public function actualizarUsuario($id, $datos) {
        // Validar los datos antes de proceder
        $errores = $this->validarDatos($datos, true);
        if (!empty($errores)) {
            return $errores;
        }
    
        // Extraer los valores del array $datos
        $perfil = $datos['perfil'];
        $nombre = $datos['nombre'];
        $apellido = $datos['apellido'];
        $email = $datos['email'];
        $user = $datos['user'];
        $telefono = $datos['telefono'];
        $documento = $datos['documento'];
    
        // Usar sentencia preparada para evitar inyecciones SQL
        $sql = "UPDATE usuarios SET perfil = ?, nombre = ?, apellido = ?, email= ?, user = ?, telefono = ?, documento = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssssi", $perfil, $nombre, $apellido, $email, $user, $telefono, $documento, $id);
    
        // Ejecutar la consulta
        return $stmt->execute();
    }
    
    
    public function eliminarUsuario($id) {
        $sql = "DELETE FROM usuarios WHERE id = $id";
        return $this->conn->query($sql);
    }

    public function obtenerUsuario($id) {
        $sql = "SELECT * FROM usuarios WHERE id = $id";
        return $this->conn->query($sql)->fetch_assoc();
    }

    public function listarUsuarios($offset) {
        $sql = "SELECT * FROM usuarios LIMIT $offset, " . $this->usuariosPorPagina;
        return $this->conn->query($sql);
    }

    public function contarUsuarios() {
        $query = "SELECT COUNT(*) as total FROM usuarios";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    public function obtenerPerfil($user_id) {
        $perfil_usuario = null;
    
        // Verificar si la conexión está activa
        if ($this->conn->ping()) {
            $sql_perfil = "SELECT perfil FROM usuarios WHERE id = ?";
            $stmt = $this->conn->prepare($sql_perfil);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->bind_result($perfil_usuario);
            $stmt->fetch();
            $stmt->close();
        } else {
            // Manejo de error en caso de que la conexión esté cerrada
            die('Error: La conexión a la base de datos está cerrada.');
        }
    
        return $perfil_usuario;
    }

    public function buscarPorDocumento($documento) {
        $sql = "SELECT * FROM usuarios WHERE documento = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function obtenerTodosLosUsuarios() {
        $sql = "SELECT * FROM usuarios";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result();
    }

    private function validarDatos($datos, $editando = false) {
        $errores = array();
    
        // Validar Nombre (solo letras, puede incluir acentos)
        if (empty($datos['nombre']) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/", $datos['nombre'])) {
            $errores['nombre'] = 'El nombre solo debe contener letras y puede incluir acentos.';
        }
    
        // Validar Apellido (solo letras, puede incluir acentos)
        if (empty($datos['apellido']) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚÑñ\s]+$/", $datos['apellido'])) {
            $errores['apellido'] = 'El apellido solo debe contener letras y puede incluir acentos.';
        }
    
        // Validar Teléfono (mínimo 10 dígitos, formato Argentina sin el +54)
        if (empty($datos['telefono']) || !preg_match("/^\d{10,}$/", $datos['telefono'])) {
            $errores['telefono'] = 'El teléfono debe tener al menos 10 dígitos.';
        } else {
            // Verificar que el teléfono no comience con +54
            if (substr($datos['telefono'], 0, 2) == '54') {
                $errores['telefono'] = 'El teléfono no debe comenzar con +54.';
            }
        }
    
        return $errores;
    }
    
    
    

}


?>