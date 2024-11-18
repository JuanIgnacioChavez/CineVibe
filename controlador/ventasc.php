<?php 

require_once('../modelo/venta.php');

class VentaController {
    private $venta;
    private $message = "";

    public function __construct($conn, $ventasPorPagina = 10) {
        $this->venta = new Venta($conn, $ventasPorPagina);
    }

    public function obtenerVentasPaginadas($paginaActual, $filtroFecha = null, $filtroHora = null) {
        return $this->venta->obtenerVentasPaginadas($paginaActual, $filtroFecha, $filtroHora);
    }

    public function obtenerTotalPaginas($filtroFecha = null, $filtroHora = null) {
        $totalVentas = $this->venta->contarVentas($filtroFecha, $filtroHora);
        return ceil($totalVentas / $this->venta->getVentasPorPagina());
    }

    public function getMessage() {
        return $this->message;
    }
}

?>
