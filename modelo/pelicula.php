<?php


class Pelicula {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;  
    }

    public function convertToEmbedURL($url) {
        $parsed_url = parse_url($url);
        if (isset($parsed_url['host']) && $parsed_url['host'] === 'youtu.be') {
            $video_id = ltrim($parsed_url['path'], '/');
            return "https://www.youtube.com/embed/" . $video_id;
        }
        return $url;  
    }

    public function agregarPelicula($datos) {
        // Llamar a validarDatos, pasando false para indicar que no estamos editando
        $errores = $this->validarDatos($datos, false);
    
        if (!empty($errores)) {
            // Retornar los errores si existen
            return $errores;
        }
    
        // Obtener los datos
        $titulo = $datos['titulo'];
        $descripcion = $datos['descripcion'];
        $director = $datos['director'];
        $duracion = $datos['duracion'];
        $genero = $datos['genero'];
        $fecha_estreno = $datos['fecha_estreno'];
        $trailer = $datos['trailer'];
        $estado = isset($datos['estado']) ? $datos['estado'] : 1; // Estado "activo" por defecto
        $imagen = $_FILES['imagen'];
    
        // Convertir URL de trailer y subir imagen
        $trailer_embed_url = $this->convertToEmbedURL($trailer);
        $relative_file_path = $this->subirImagen($imagen);
        if ($relative_file_path === false) {
            return ['imagen' => 'Error al subir la imagen.'];
        }
    
        // Preparar la consulta SQL con parámetros
        $sql = "INSERT INTO peliculas (titulo, descripcion, director, duracion, genero, fecha_estreno, trailer_url, imagen, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Preparar la sentencia
        $stmt = $this->conn->prepare($sql);
    
        // Verificar si la preparación fue exitosa
        if ($stmt === false) {
            return ['error' => 'Error en la preparación de la consulta.'];
        }
    
        // Vincular los parámetros a la consulta
        $stmt->bind_param("sssssssss", $titulo, $descripcion, $director, $duracion, $genero, $fecha_estreno, $trailer_embed_url, $relative_file_path, $estado);
    
        // Ejecutar la consulta
        return $stmt->execute();
    }
    
    
    
    public function actualizarPelicula($id, $datos) {
        $errores = $this->validarDatos($datos, true);
        if (!empty($errores)) {
            return $errores;
        }
    
        $titulo = $this->conn->real_escape_string($datos['titulo']);
        $descripcion = $this->conn->real_escape_string($datos['descripcion']);
        $director = $this->conn->real_escape_string($datos['director']);
        $duracion = $this->conn->real_escape_string($datos['duracion']);
        $genero = $this->conn->real_escape_string($datos['genero']);
        $fecha_estreno = $this->conn->real_escape_string($datos['fecha_estreno']);
        $trailer = $this->conn->real_escape_string($datos['trailer']);
        $estado = isset($datos['estado']) ? $this->conn->real_escape_string($datos['estado']) : 1; // Asigna "1" (activo) si no se proporciona el estado
        $imagen = $_FILES['imagen'];
    
        $trailer_embed_url = $this->convertToEmbedURL($trailer);  // Convert trailer URL
        $relative_file_path = $imagen['name'] ? $this->subirImagen($imagen) : '';
        
        // Ahora incluimos el campo estado en la consulta SQL
        $sql = "UPDATE peliculas SET titulo='$titulo', descripcion='$descripcion', director='$director', duracion='$duracion', 
                genero='$genero', fecha_estreno='$fecha_estreno', trailer_url='$trailer_embed_url', estado='$estado'" .
               ($relative_file_path ? ", imagen='$relative_file_path'" : "") . 
               " WHERE id=$id";
        return $this->conn->query($sql);
    }
    

    public function eliminarPelicula($id) {
        // Paso 1: Eliminar los asientos asociados a las funciones de la película
        $sql = "DELETE FROM asientos WHERE funcion_id IN (SELECT id FROM funciones WHERE id_pelicula = ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false; // Si falla la preparación de la consulta, retorna false
        }
        $stmt->bind_param('i', $id);
        $stmt->execute(); // Ejecutamos la eliminación en la tabla asientos
    
        // Paso 2: Eliminar las funciones asociadas a la película
        $sql = "DELETE FROM funciones WHERE id_pelicula = ? AND estado = 0";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false; // Si falla la preparación de la consulta para eliminar las funciones, retorna false
        }
        $stmt->bind_param('i', $id);
        $stmt->execute(); // Ejecutamos la eliminación en la tabla funciones
    
        // Paso 3: Eliminar la película de la tabla peliculas
        $sql = "DELETE FROM peliculas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return false; // Si falla la preparación de la consulta para eliminar la película, retorna false
        }
        $stmt->bind_param('i', $id);
        $stmt->execute(); // Ejecutamos la eliminación en la tabla peliculas
    
        // Verificamos si la eliminación de la película fue exitosa
        if ($stmt->affected_rows >= 0) {
            return true; // Si la película fue eliminada correctamente
        }
    
        return false; // Si no se pudo eliminar la película
    }
    
    // Añadir método para actualizar el precio del ticket
    public function actualizarPrecioTicket($id_pelicula, $nuevo_precio) {
        $sql = "UPDATE peliculas SET precio_ticket = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$nuevo_precio, $id_pelicula]);
    }

    // Añadir método para obtener el precio del ticket
    public function obtenerPrecioTicket($id_pelicula) {
        $sql = "SELECT precio_ticket FROM peliculas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id_pelicula]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['precio_ticket'];
    }

    public function obtenerPelicula($id) {  // Added parameter for ID
        $sql = "SELECT * FROM peliculas WHERE id = $id";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    public function listarPeliculas($offset, $limit) {
        $sql = "SELECT id, titulo, descripcion, director, duracion, genero, fecha_estreno, trailer_url, imagen, estado FROM peliculas LIMIT $offset, $limit";
        return $this->conn->query($sql);
    }

    public function contarPeliculas() {
        $sql = "SELECT COUNT(*) as total FROM peliculas";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc()['total'] : 0;
    }

    private function subirImagen($imagen) {
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK) {
            $target_dir = "C:/xampp/htdocs/tesisMVC/assets/images/pelis/";
            $original_file_name = basename($imagen["name"]);
            $target_file = $target_dir . $original_file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $counter = 1;
            while (file_exists($target_file)) {
                $target_file = $target_dir . pathinfo($original_file_name, PATHINFO_FILENAME) . "_" . $counter . "." . $imageFileType;
                $counter++;
            }

            $check = getimagesize($imagen["tmp_name"]);
            if ($check !== false && move_uploaded_file($imagen["tmp_name"], $target_file)) {
                return "pelis/" . basename($target_file);
            }
        }
        return false;
    }

    public function modificarEstado($id, $estado) {
        $query = "UPDATE peliculas SET estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $estado, $id); // 'i' indica un parámetro entero
        return $stmt->execute();
    }


    public function validarDatos($datos, $editando = false) {
        $errores = array();

    // Validar título
    if (empty($datos['titulo']) || strlen($datos['titulo']) < 8 || !preg_match('/[a-zA-Z]/', $datos['titulo'])) {
        $errores['titulo'] = 'El título debe contener al menos 8 caracteres y al menos un carácter alfabético.';
    } else {
        // Si hay un ID de película, excluimos este ID de la validación (para actualizar)
        $id_pelicula = isset($datos['id']) ? $datos['id'] : null;

        $sql = "SELECT COUNT(*) FROM peliculas WHERE titulo = ? AND (? IS NULL OR id != ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $datos['titulo'], $id_pelicula, $id_pelicula);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

    if ($count > 0) {
        $errores['titulo'] = 'Ya existe una película con este título en el sistema.';
    }
}


        // Validar descripción
        if (empty($datos['descripcion']) || strlen($datos['descripcion']) < 8 || !preg_match('/[a-zA-Z]/', $datos['descripcion'])) {
            $errores['descripcion'] = 'La descripción debe contener al menos 8 caracteres y al menos un carácter alfabético.';
        }
    
        // Validar director
        if (empty($datos['director']) || !preg_match('/^[\p{L}\s]{8,}$/u', $datos['director'])) {
            $errores['director'] = 'El nombre del director debe contener solo letras y espacios, con un mínimo de 8 caracteres.';
        }
    
        // Validar duración
        if (!isset($datos['duracion']) || !is_numeric($datos['duracion']) || $datos['duracion'] <= 0 || $datos['duracion'] > 300) {
            $errores['duracion'] = 'La duración debe ser un número entre 1 y 300 minutos (5 horas).';
        }
    
        // Validar género
        if (empty($datos['genero']) || !preg_match('/^[\p{L}\s]+$/u', $datos['genero'])) {
            $errores['genero'] = 'El género debe contener solo letras y puede incluir acentos.';
        }
    
        // Validar fecha de estreno
        $hoy = date('Y-m-d');
        $dosMesesAtras = date('Y-m-d', strtotime('-2 months'));
        
        if (empty($datos['fecha_estreno']) || $datos['fecha_estreno'] < $dosMesesAtras || $datos['fecha_estreno'] > $hoy) {
            $errores['fecha_estreno'] = 'La fecha de estreno debe estar entre la fecha de hoy y hasta dos meses atrás.';
        }
    
        return $errores;
    }
    
    
    
    public function obtenerPeliculas() {
        $sql = "SELECT id, titulo, duracion, imagen, fecha_estreno, genero, estado FROM peliculas";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $peliculas = [];

        while ($row = $result->fetch_assoc()) {
            $peliculas[] = $row;
        }

        $stmt->close();
        return $peliculas;
    }

    public function obtenerPeliculasDisponibles() {
        $sql = "SELECT id, titulo, duracion, imagen, fecha_estreno, genero, estado FROM peliculas where estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $peliculas = [];

        while ($row = $result->fetch_assoc()) {
            $peliculas[] = $row;
        }

        $stmt->close();
        return $peliculas;
    }

    public function buscarPeliculaPorId($id) {
        $sql = "SELECT * FROM peliculas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pelicula = $result->fetch_assoc();
        return $pelicula;
    }
}

?>