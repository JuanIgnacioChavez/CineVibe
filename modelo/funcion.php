<?php

class Funcion {
    private $conn;
    public $funcionesPorPagina;

    public function __construct($dbConnection, $funcionesPorPagina = 5) {
        $this->conn = $dbConnection;
        $this->funcionesPorPagina = $funcionesPorPagina; // Asignar valor por defecto
    }

    public function agregarFuncion($datos) {
        $errores = $this->validarDatos($datos);
        if (!empty($errores)) {
            return $errores;
        }
    
        // Insertar la nueva función con estado activo (1)
        $sql = "INSERT INTO funciones (id_pelicula, fecha, hora, sala, estado) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isss", $datos['id_pelicula'], $datos['fecha'], $datos['hora'], $datos['sala']);
        
        if ($stmt->execute()) {
            // Obtener el id de la función recién insertada
            $funcion_id = $stmt->insert_id;
    
            // Llamar al método cargarAsientos para agregar los asientos para esta función
            $this->cargarAsientos($funcion_id);
    
            return true; // Función y asientos agregados con éxito
        }
    
        return false; // Error al agregar la función
    }
    

    
    public function actualizarFuncion($id, $datos) {
        $errores = $this->validarDatos($datos, true);
        if (!empty($errores)) {
            return $errores;
        }
    
        $sql = "UPDATE funciones SET id_pelicula = ?, fecha = ?, hora = ?, sala = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssi", $datos['id_pelicula'], $datos['fecha'], $datos['hora'], $datos['sala'], $id);
        return $stmt->execute();
    }
    

    public function eliminarFuncion($id) {
        // Iniciamos una transacción
        $this->conn->begin_transaction();
        
        try {
            // Primero, eliminamos los registros en la tabla asientos que dependen de la función
            $sql = "DELETE FROM asientos WHERE funcion_id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error preparando la consulta de asientos");
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            // No verificamos affected_rows aquí porque puede que no haya asientos
            $stmt->close();
            
            // Ahora eliminamos la función en la tabla funciones
            $sql = "DELETE FROM funciones WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error preparando la consulta de funciones");
            }
            
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            // Verificamos si la función existía y fue eliminada
            if ($stmt->affected_rows >= 0) {
                $stmt->close();
                $this->conn->commit();
                return true;
            } else {
                throw new Exception("No se pudo eliminar la función");
            }
            
        } catch (Exception $e) {
            // Si algo sale mal, revertimos los cambios
            $this->conn->rollback();
            return false;
        }
    }
    
    

    public function obtenerFuncion($id) {
        $sql = "SELECT * FROM funciones WHERE id = $id";
        return $this->conn->query($sql)->fetch_assoc();
    }

    public function listarFunciones($offset) {

        $this->desactivarFuncionesPasadas();
    
        $sql = "SELECT f.id, f.fecha, f.hora, f.sala, p.titulo AS pelicula_titulo 
                FROM funciones f 
                JOIN peliculas p ON f.id_pelicula = p.id
                WHERE f.estado = 1
                LIMIT $offset, " . $this->funcionesPorPagina;
        return $this->conn->query($sql);
    }
    

    public function contarFunciones() {
        $query = "SELECT COUNT(*) as total FROM funciones where estado = 1";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['total'];
    }

    public function obtenerPeliculas() {
        $sql = "SELECT id, titulo FROM peliculas";
        return $this->conn->query($sql);
    }

    public function getHorarios($pelicula_id) {
        $sql = "SELECT id AS id_funcion, fecha, hora, sala, estado FROM funciones WHERE id_pelicula = ? AND estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $pelicula_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $horarios = [];
        while ($row = $result->fetch_assoc()) {
            $horarios[] = $row;
        }
    
        $stmt->close();
        return $horarios;
    }

    public function cargarAsientos($funcion_id) {
        $filas = range('A', 'M'); // Filas de la A a la M
        $asientos_por_fila = 15; // 15 asientos por fila
    
        // Preparar la consulta para insertar los asientos en la base de datos
        $sql = "INSERT INTO asientos (funcion_id, fila, numero, disponible) VALUES (?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
    
        // Iterar sobre las filas y los asientos
        foreach ($filas as $fila) {
            for ($numero = 1; $numero <= $asientos_por_fila; $numero++) {
                // Vincular los parámetros
                $stmt->bind_param("isi", $funcion_id, $fila, $numero);
                // Ejecutar la inserción de cada asiento
                $stmt->execute();
            }
        }
    }

    public function obtenerAsientosPorFuncion($funcion_id) {
        $sql = "SELECT id, fila, numero, disponible FROM asientos WHERE funcion_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $funcion_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $asientos = [];
        while ($seat = $result->fetch_assoc()) {
            $asientos[] = $seat;
        }
    
        $stmt->close();
        return $asientos;
    }
    

    public function desactivarFuncionesPasadas() {
        $hoy = new DateTime();
        $fechaActual = $hoy->format('Y-m-d');
        $horaActual = $hoy->format('H:i:s');
        
        // Actualizar el estado a 0 para las funciones cuya fecha y hora han pasado
        $sql = "UPDATE funciones SET estado = 0 WHERE (fecha < ? OR (fecha = ? AND hora < ?)) AND estado = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $fechaActual, $fechaActual, $horaActual);
        $stmt->execute();
        $stmt->close();
    }
    
    private function validarDatos($datos, $editando = false) {
        $errores = array();
    
        // Validar sala (debe ser un número entre 1 y 100)
        if (empty($datos['sala']) || !is_numeric($datos['sala']) || $datos['sala'] < 1 || $datos['sala'] > 100) {
            $errores['sala'] = 'La sala debe ser un número entre 1 y 100.';
        }
    
        // Validar fecha (debe estar en formato Y-m-d y no ser más de 2 meses atrás)
        if (empty($datos['fecha']) || !DateTime::createFromFormat('Y-m-d', $datos['fecha'])) {
            $errores['fecha'] = 'La fecha ingresada no es válida. Debe estar en formato AAAA-MM-DD.';
        } else {
            $fecha = new DateTime($datos['fecha']);
            $hoy = new DateTime();
            $haceDosMeses = (clone $hoy)->modify('-2 months');
            
            if ($fecha < $haceDosMeses) {
                $errores['fecha'] = 'La fecha no puede ser anterior a dos meses desde hoy.';
            }
        }
    
        // Validar que no haya otra función en la misma sala y horario
        if (empty($errores)) {
            // Preparar consulta para verificar conflicto de sala y horario con placeholders `?`
            $query = "SELECT COUNT(*) FROM funciones WHERE sala = ? AND fecha = ? AND hora = ?";
            
            // Si se está editando, excluye la función actual en la verificación
            if ($editando && !empty($datos['id'])) {
                $query .= " AND id != ?";
            }
    
            $stmt = $this->conn->prepare($query);
    
            // Vincular parámetros posicionales
            if ($editando && !empty($datos['id'])) {
                $stmt->bind_param("issi", $datos['sala'], $datos['fecha'], $datos['hora'], $datos['id']);
            } else {
                $stmt->bind_param("iss", $datos['sala'], $datos['fecha'], $datos['hora']);
            }
    
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
    
            if ($count > 0) {
                $errores['conflicto'] = 'Ya existe una función en la misma sala y horario.';
            }
        }
    
        return $errores;
    }

    


}

?>