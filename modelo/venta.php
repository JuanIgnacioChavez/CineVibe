<?php

class Venta {
    private $conn;
    private $ventasPorPagina;

    public function __construct($dbConnection, $ventasPorPagina = 10) {
        $this->conn = $dbConnection;
        $this->ventasPorPagina = $ventasPorPagina;
    }

    public function getVentasPorPagina() {
        return $this->ventasPorPagina;
    }

    public function obtenerVentasPaginadas($paginaActual = 1, $filtroFecha = null, $filtroHora = null) {
        $offset = ($paginaActual - 1) * $this->ventasPorPagina;
        
        // Base query
        $sql = "SELECT * FROM ventas";
        $params = [];
        $types = "";
        
        // Add filters if present
        if ($filtroFecha && $filtroHora) {
            $sql .= " WHERE fecha = ? AND hora = ?";
            $params = [$filtroFecha, $filtroHora];
            $types = "ss";
        } elseif ($filtroFecha) {
            $sql .= " WHERE fecha = ?";
            $params = [$filtroFecha];
            $types = "s";
        } elseif ($filtroHora) {
            $sql .= " WHERE hora = ?";
            $params = [$filtroHora];
            $types = "s";
        }
        
        // Add pagination
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $this->ventasPorPagina;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function contarVentas($filtroFecha = null, $filtroHora = null) {
        $sql = "SELECT COUNT(*) as total FROM ventas";
        $params = [];
        $types = "";
        
        if ($filtroFecha && $filtroHora) {
            $sql .= " WHERE fecha = ? AND hora = ?";
            $params = [$filtroFecha, $filtroHora];
            $types = "ss";
        } elseif ($filtroFecha) {
            $sql .= " WHERE fecha = ?";
            $params = [$filtroFecha];
            $types = "s";
        } elseif ($filtroHora) {
            $sql .= " WHERE hora = ?";
            $params = [$filtroHora];
            $types = "s";
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }
}
?>
