<?php

class Aperitivo {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function agregarAperitivo($datos) {
        // Validar los datos antes de proceder
        $errores = $this->validarDatosAperitivos($datos);
        if (!empty($errores)) {
            return $errores; // Retornar errores si existen
        }

        // Extraer los valores del array $datos
        $nombre = $this->sanitizeInput($datos['nombre']);
        $descripcion = $this->sanitizeInput($datos['descripcion']);
        $precio = (float)$datos['precio'];
        $estado = isset($datos['estado']) ? (int)$datos['estado'] : 1;
        $imagen = $_FILES['imagen'];

        // Subir la imagen
        $relative_file_path = $this->subirImagen($imagen);
        if ($relative_file_path === false) {
            return false; // Retornar false si la imagen no se pudo subir
        }

        // Preparar la consulta SQL utilizando declaraciones preparadas para evitar SQL Injection
        $sql = "INSERT INTO aperitivos (nombre, descripcion, precio, imagen, estado) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return "Error en la preparación de la consulta: " . $this->conn->error;
        }

        // Vincular parámetros de la consulta (tipo: s = string, d = double)
        $stmt->bind_param("ssisi", $nombre, $descripcion, $precio, $relative_file_path, $estado);

        // Ejecutar la consulta y verificar si tuvo éxito
        if ($stmt->execute()) {
            return true; // Retornar true si la operación fue exitosa
        } else {
            return "Error al agregar el aperitivo: " . $stmt->error;
        }

        $stmt->close();
    }
    
    public function actualizarAperitivo($id, $datos) {
        // Validar los datos antes de proceder
        $errores = $this->validarDatosAperitivos($datos);
        if (!empty($errores)) {
            return $errores; // Retornar errores si existen
        }
    
        // Extraer los valores del array $datos
        $nombre = $datos['nombre'];
        $descripcion = $datos['descripcion'];
        $precio = $datos['precio'];
        // Obtener el estado
        $estado = isset($datos['estado']) ? (int)$datos['estado'] : 1; // Asignar estado si se proporciona
    
        // Verificar si se subió una nueva imagen
        $imagen = isset($_FILES['imagen']) ? $_FILES['imagen'] : null;
    
        // Subir la nueva imagen si existe
        $relative_file_path = '';
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
            $relative_file_path = $this->subirImagen($imagen);
            if ($relative_file_path === false) {
                return 'Error al subir la imagen.';
            }
        }
    
        // Preparar la consulta SQL utilizando declaraciones preparadas para evitar SQL Injection
        $sql = "UPDATE aperitivos SET nombre = ?, descripcion = ?, precio = ?, estado = ?" .
               ($relative_file_path ? ", imagen = ?" : "") . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return "Error en la preparación de la consulta: " . $this->conn->error;
        }
    
        // Vincular parámetros de la consulta
        if ($relative_file_path) {
            $stmt->bind_param("ssissi", $nombre, $descripcion, $precio, $estado, $relative_file_path, $id);
        } else {
            $stmt->bind_param("ssiii", $nombre, $descripcion, $precio, $estado, $id);
        }
    
        // Ejecutar la consulta y verificar si tuvo éxito
        if ($stmt->execute()) {
            return true; // Retornar true si la operación fue exitosa
        } else {
            return "Error al actualizar el aperitivo: " . $stmt->error;
        }
    
        $stmt->close();
    }
    
    
    public function eliminarAperitivo($id) {
        // Usar declaración preparada para evitar inyección SQL
        $sql = "DELETE FROM aperitivos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return "Error en la preparación de la consulta: " . $this->conn->error;
        }

        $stmt->bind_param('i', $id); // Bind el parámetro de tipo entero
        if ($stmt->execute()) {
            return true;
        } else {
            return "Error al eliminar el aperitivo: " . $stmt->error;
        }

        $stmt->close();
    }

    public function obtenerAperitivo($id) {
        $sql = "SELECT * FROM aperitivos WHERE id = $id";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    public function listarAperitivos($offset, $limit) {
        $sql = "SELECT id, nombre, descripcion, precio, imagen, estado FROM aperitivos LIMIT $offset, $limit";
        return $this->conn->query($sql);
    }

    public function contarAperitivos() {
        $sql = "SELECT COUNT(*) as total FROM aperitivos";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc()['total'] : 0;
    }

    private function subirImagen($imagen) {
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
            $target_dir = "C:/xampp/htdocs/tesisMVC/assets/images/aperitivos/";
            $original_file_name = basename($imagen["name"]);
            $target_file = $target_dir . $original_file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validar el tipo de archivo
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowedTypes)) {
                return false; // Solo permitir imágenes de tipo JPG, JPEG, PNG y GIF
            }

            // Validar el tamaño del archivo (limitar a 5MB)
            if ($imagen["size"] > 5000000) {
                return false; // Limitar a 5MB
            }

            $counter = 1;
            while (file_exists($target_file)) {
                $target_file = $target_dir . pathinfo($original_file_name, PATHINFO_FILENAME) . "_" . $counter . "." . $imageFileType;
                $counter++;
            }

            $check = getimagesize($imagen["tmp_name"]);
            if ($check !== false && move_uploaded_file($imagen["tmp_name"], $target_file)) {
                return "aperitivos/" . basename($target_file);
            }
        }
        return false;
    }

    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public function obtenerAperitivos() {
        $sql = "SELECT * FROM aperitivos";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $aperitivos = [];

        while ($row = $result->fetch_assoc()) {
            $aperitivos[] = $row;
        }

        $stmt->close();
        return $aperitivos;
    }

    public function obtenerAperitivosDisponibles() {
        $sql = "SELECT * FROM aperitivos where estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $aperitivos = [];

        while ($row = $result->fetch_assoc()) {
            $aperitivos[] = $row;
        }

        $stmt->close();
        return $aperitivos;
    }

    public function modificarEstado($id, $estado) {
        $query = "UPDATE aperitivos SET estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $estado, $id); // 'i' indica un parámetro entero
        return $stmt->execute();
    }

    private function validarDatosAperitivos($datos, $editando = false) {
        $errores = array();
        
        // Validar nombre (solo caracteres alfabéticos, mínimo 5 caracteres)
        if (empty($datos['nombre']) || strlen($datos['nombre']) < 5 || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $datos['nombre'])) {
            $errores['nombre'] = 'El nombre debe contener al menos 5 caracteres alfabéticos y no puede tener números.';
        } else {
            $sql = "SELECT COUNT(*) FROM aperitivos WHERE nombre = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $datos['nombre']);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
    
            if ($count > 0) {
                $errores['nombre'] = 'Ya existe un aperitivo con este título en el sistema.';
            }
        }
    
        // Validar descripción (puede contener caracteres alfabéticos, números, mínimo 6 caracteres)
        if (empty($datos['descripcion']) || strlen($datos['descripcion']) < 6) {
            $errores['descripcion'] = 'La descripción debe contener al menos 6 caracteres.';
        }
    
        // Validar precio (solo números, mayor a 0)
        if (empty($datos['precio']) || !is_numeric($datos['precio']) || $datos['precio'] <= 0) {
            $errores['precio'] = 'El precio debe ser un número positivo.';
        }
    
        return $errores;
    }
    
}
?>
