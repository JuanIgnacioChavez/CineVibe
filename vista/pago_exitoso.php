<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');


if (!isset($_GET['status']) || $_GET['status'] !== 'approved') {
    header("Location: pago_fallido.php");
    exit;
}

if (isset($_SESSION['funcionId'], $_SESSION['asientos'])) {
    $funcionId = $_SESSION['funcionId'];
    $asientos = $_SESSION['asientos'];
    $aperitivos = isset($_SESSION['aperitivos']) ? $_SESSION['aperitivos'] : [];

    include_once('../includes/db_conn.php');
    require_once __DIR__ . '/../vendor/autoload.php';
    \MercadoPago\SDK::setAccessToken('APP_USR-2323788171765170-110810-1678d55203462454c7bca2dde7f3f5fe-2086356036');

    if (!isset($_GET['payment_id'])) {
        echo "No se recibió el ID de pago. Por favor, intenta nuevamente.";
        exit;
    }

    try {
        $payment = \MercadoPago\Payment::find_by_id($_GET['payment_id']);
        $montoTotal = $payment->transaction_amount;
    } catch (Exception $e) {
        error_log("Error al obtener el pago de MercadoPago: " . $e->getMessage());
        echo "Hubo un error al procesar el pago. Por favor, contacta al soporte.";
        exit;
    }

    $conn->begin_transaction();
    
    try {
        // 1. Crear la venta principal
        $queryVenta = "INSERT INTO ventas (user_id, monto, fecha, hora, funcion_id) VALUES (?, ?, ?, ?, ?)";
        $stmtVenta = $conn->prepare($queryVenta);
        
        $userId = $_SESSION['id'];
        $fecha = date('Y-m-d');
        $hora = date('H:i');
        
        $stmtVenta->bind_param("dissi", $userId, $montoTotal, $fecha, $hora, $funcionId);
        
        if (!$stmtVenta->execute()) {
            throw new Exception("Error al registrar la venta: " . $stmtVenta->error);
        }
        
        $ventaId = $conn->insert_id;

        // 2. Registrar los asientos
        $queryAsientos = "UPDATE asientos SET disponible = 0 WHERE funcion_id = ? AND id = ?";
        $stmtAsientos = $conn->prepare($queryAsientos);
        
        $queryDetalleVenta = "INSERT INTO venta_detalles (venta_id, tipo, item_id, cantidad, precio_unitario) 
                             VALUES (?, ?, ?, ?, ?)";
        $stmtDetalle = $conn->prepare($queryDetalleVenta);

        foreach ($asientos as $asiento) {
            $asientoId = is_array($asiento) ? $asiento['id'] : $asiento;
            
            // Actualizar disponibilidad del asiento
            $stmtAsientos->bind_param("ii", $funcionId, $asientoId);
            if (!$stmtAsientos->execute()) {
                throw new Exception("Error al actualizar asiento: " . $stmtAsientos->error);
            }

            // Registrar detalle de venta para el asiento
            $tipo = 'asiento';
            $cantidad = 1;
            $precioUnitario = 5500; // Precio del ticket
            $stmtDetalle->bind_param("isids", $ventaId, $tipo, $asientoId, $cantidad, $precioUnitario);
            if (!$stmtDetalle->execute()) {
                throw new Exception("Error al registrar detalle de asiento: " . $stmtDetalle->error);
            }
        }

        // 3. Registrar los aperitivos
        if (!empty($aperitivos)) {
            foreach ($aperitivos as $aperitivo) {
                if (isset($aperitivo['id'], $aperitivo['cantidad'])) {
                    $tipo = 'aperitivo';
                    // Obtener el precio del aperitivo de la base de datos
                    $queryPrecio = "SELECT precio FROM aperitivos WHERE id = ?";
                    $stmtPrecio = $conn->prepare($queryPrecio);
                    $stmtPrecio->bind_param("i", $aperitivo['id']);
                    $stmtPrecio->execute();
                    $resultPrecio = $stmtPrecio->get_result();
                    $precioData = $resultPrecio->fetch_assoc();
                    $precioUnitario = $precioData['precio'];

                    // Registrar detalle de venta para el aperitivo
                    $stmtDetalle->bind_param("isids", 
                        $ventaId, 
                        $tipo, 
                        $aperitivo['id'], 
                        $aperitivo['cantidad'], 
                        $precioUnitario
                    );
                    
                    if (!$stmtDetalle->execute()) {
                        throw new Exception("Error al registrar detalle de aperitivo: " . $stmtDetalle->error);
                    }
                }
            }
        }

        $conn->commit();

        // 4. Generar PDFs y enviar email
        try {
            $pdfInfo = generarPDF($funcionId, $_SESSION['asientos'], $userId, $conn);
            
            // Generar ticket de aperitivos si hay aperitivos en la compra
            if (!empty($_SESSION['aperitivos'])) {
                try {
                    $ticketPath = generarTicketAperitivos($ventaId, $userId, $conn);
                    if ($ticketPath) {
                        // Asegurarse de que 'files' exista en el array
                        if (!isset($pdfInfo['files'])) {
                            $pdfInfo['files'] = [];
                        }
                        // Agregar el ticket de aperitivos al array de archivos
                        $pdfInfo['files'][] = $ticketPath;
                        error_log("Ticket de aperitivos agregado: " . $ticketPath);
                    }
                } catch (Exception $e) {
                    error_log("Error al generar ticket de aperitivos: " . $e->getMessage());
                    $_SESSION['mensaje'] = "La compra fue exitosa pero hubo un problema al generar el ticket de aperitivos.";
                }
            }
        
            if (enviarEmailConEntradas($pdfInfo)) {
                $_SESSION['mensaje'] = "Las entradas y tickets han sido enviados a tu correo electrónico.";
            } else {
                $_SESSION['mensaje'] = "La compra fue exitosa pero hubo un problema al enviar las entradas. Contacta a soporte.";
            }
        } catch (Exception $e) {
            error_log("Error al generar/enviar entradas: " . $e->getMessage());
            $_SESSION['mensaje'] = "La compra fue exitosa pero hubo un problema al generar las entradas. Contacta a soporte.";
        }

        // Limpiar la sesión
        unset($_SESSION['funcionId']);
        unset($_SESSION['asientos']);
        unset($_SESSION['aperitivos']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error en la transacción: " . $e->getMessage());
        echo "Hubo un error al procesar tu reserva. Por favor, intenta nuevamente o contacta al soporte.";
        exit;
    } finally {
        if (isset($stmtVenta)) $stmtVenta->close();
        if (isset($stmtAsientos)) $stmtAsientos->close();
        if (isset($stmtDetalle)) $stmtDetalle->close();
        if (isset($stmtPrecio)) $stmtPrecio->close();
        $conn->close();
    }
} else {
    header("Location: pago_fallido.php");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

function generarPDF($funcionId, $asientos, $userId, $conn) {
    error_log("Iniciando generación de PDFs - FuncionID: $funcionId, UserID: $userId");
    $pdfInfoArray = [];

    try {
        if (!class_exists('TCPDF')) {
            require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
        }

        // Obtener información de la función
        $queryFuncion = "SELECT f.id, f.fecha, f.hora, f.sala, p.titulo 
                        FROM funciones f 
                        JOIN peliculas p ON f.id_pelicula = p.id 
                        WHERE f.id = ?";
        $stmtFuncion = $conn->prepare($queryFuncion);
        $stmtFuncion->bind_param("i", $funcionId);
        $stmtFuncion->execute();
        $resultado = $stmtFuncion->get_result();
        $funcion = $resultado->fetch_assoc();

        // Obtener información del usuario
        $queryUsuario = "SELECT nombre, email FROM usuarios WHERE id = ?";
        $stmtUsuario = $conn->prepare($queryUsuario);
        $stmtUsuario->bind_param("i", $userId);
        $stmtUsuario->execute();
        $resultadoUsuario = $stmtUsuario->get_result();
        $usuario = $resultadoUsuario->fetch_assoc();

        $asientosArray = [];
        if (is_array($_SESSION['asientos'])) {
            foreach ($_SESSION['asientos'] as $asiento) {
                if (isset($asiento['id'])) {
                    $asientosArray[] = $asiento['id'];
                }
            }
        }

        error_log("Asientos extraídos para procesar: " . print_r($asientosArray, true));

        // Generar un PDF por cada asiento
        foreach ($asientosArray as $asientoId) {
            error_log("Procesando asiento ID: " . $asientoId);
            
            // Validar que el asientoId sea numérico
            if (!is_numeric($asientoId)) {
                error_log("ID de asiento no válido: " . print_r($asientoId, true));
                continue;
            }

            // Obtener la información específica del asiento
            $queryAsiento = "SELECT a.id, a.fila, a.numero, CONCAT(a.fila, a.numero) as numero_asiento 
                            FROM asientos a 
                            WHERE a.id = ? AND a.funcion_id = ?";
            $stmtAsiento = $conn->prepare($queryAsiento);
            $stmtAsiento->bind_param("ii", $asientoId, $funcionId);
            $stmtAsiento->execute();
            $resultadoAsiento = $stmtAsiento->get_result();
            $infoAsiento = $resultadoAsiento->fetch_assoc();

            if (!$infoAsiento) {
                error_log("No se encontró información para el asiento ID: " . $asientoId);
                continue;
            }

            
            // Crear nuevo PDF para este asiento
            $pdf = new TCPDF('P', 'mm', array(80, 160), true, 'UTF-8', false);
            $pdf->SetCreator('Cine Vibe');
            $pdf->SetTitle('Entrada - ' . $funcion['titulo'] . ' - Asiento ' . $infoAsiento['numero_asiento']);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(5, 5, 5);
            $pdf->AddPage();

            // Establecer color de fondo
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'F');

            // Título del cine
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'CINE VIBE', 0, 1, 'C');
            $pdf->Ln(2);

            // Título de la película
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(0, 8, strtoupper($funcion['titulo']), 0, 'C');
            
            // Línea decorativa
            $pdf->SetLineWidth(0.5);
            $pdf->Line(10, $pdf->GetY(), 70, $pdf->GetY());
            $pdf->Ln(5);

            // Información principal
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'INFORMACIÓN DE LA FUNCIÓN', 0, 1, 'C');
            $pdf->Ln(2);

            // Detalles
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(20, 6, 'Fecha:', 0, 0, 'L');
            $pdf->Cell(0, 6, date("d/m/Y", strtotime($funcion['fecha'])), 0, 1, 'L');
            
            $pdf->Cell(20, 6, 'Hora:', 0, 0, 'L');
            $pdf->Cell(0, 6, date("H:i", strtotime($funcion['hora'])), 0, 1, 'L');
            
            $pdf->Cell(20, 6, 'Sala:', 0, 0, 'L');
            $pdf->Cell(0, 6, $funcion['sala'], 0, 1, 'L');
            
            $pdf->Cell(20, 6, 'Asiento:', 0, 0, 'L');
            $pdf->Cell(0, 6, $infoAsiento['numero_asiento'], 0, 1, 'L');
            
            // Línea decorativa
            $pdf->Ln(2);
            $pdf->Line(10, $pdf->GetY(), 70, $pdf->GetY());
            $pdf->Ln(5);

            // Código QR con la información de la entrada
            $qrData = "Función: " . $funcion['id'] . "\n";
            $qrData .= "Película: " . $funcion['titulo'] . "\n";
            $qrData .= "Fecha: " . $funcion['fecha'] . "\n";
            $qrData .= "Hora: " . $funcion['hora'] . "\n";
            $qrData .= "Sala: " . $funcion['sala'] . "\n";
            $qrData .= "Asiento: " . $infoAsiento['numero_asiento'];
            
            $pdf->write2DBarcode($qrData, 'QRCODE,L', 25, $pdf->GetY(), 30, 30);
            $pdf->Ln(35);

            // Información adicional
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 4, 'ENTRADA VÁLIDA PARA UNA PERSONA', 0, 1, 'C');
            $pdf->Cell(0, 4, 'Defensa del Consumidor', 0, 1, 'C');
            $pdf->Cell(0, 4, 'La Pampa 08003337148', 0, 1, 'C');

            // Guardar PDF individual
            $pdfDir = __DIR__ . '/../assets/pdfs/';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }
            $pdfFile = 'entrada_' . $userId . '_' . $funcionId . '_asiento_' . $infoAsiento['numero_asiento'] . '_' . time() . '_' . uniqid() . '.pdf';
            $pdfPath = $pdfDir . $pdfFile;
            $pdf->Output($pdfPath, 'F');

            $pdfInfoArray[] = $pdfPath;
            error_log("PDF generado para asiento {$infoAsiento['numero_asiento']}: $pdfPath");
            
            $stmtAsiento->close();
        }

        if (empty($pdfInfoArray)) {
            throw new Exception("No se generó ningún PDF");
        }

        return [
            'files' => $pdfInfoArray,
            'email' => $usuario['email']
        ];

    } catch (Exception $e) {
        error_log("ERROR CRÍTICO en generación de PDF: " . $e->getMessage());
        throw $e;
    } finally {
        if (isset($stmtFuncion)) $stmtFuncion->close();
        if (isset($stmtUsuario)) $stmtUsuario->close();
    }
}

function generarTicketAperitivos($ventaId, $userId, $conn) {
    try {
        if (!class_exists('TCPDF')) {
            require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
        }

        // Obtener información de los aperitivos de la venta
        $queryAperitivos = "
            SELECT a.nombre, vd.cantidad, vd.precio_unitario,
                   (vd.cantidad * vd.precio_unitario) as subtotal,
                   v.fecha, v.hora
            FROM venta_detalles vd
            JOIN ventas v ON v.id = vd.venta_id
            JOIN aperitivos a ON a.id = vd.item_id
            WHERE v.id = ? AND vd.tipo = 'aperitivo'
        ";
        $stmtAperitivos = $conn->prepare($queryAperitivos);
        $stmtAperitivos->bind_param("i", $ventaId);
        $stmtAperitivos->execute();
        $resultadoAperitivos = $stmtAperitivos->get_result();
        $aperitivos = $resultadoAperitivos->fetch_all(MYSQLI_ASSOC);

        if (empty($aperitivos)) {
            return null; // No hay aperitivos para esta venta
        }

        // Obtener información del usuario
        $queryUsuario = "SELECT nombre, apellido, email FROM usuarios WHERE id = ?";
        $stmtUsuario = $conn->prepare($queryUsuario);
        $stmtUsuario->bind_param("i", $userId);
        $stmtUsuario->execute();
        $usuario = $stmtUsuario->get_result()->fetch_assoc();

        // Crear PDF para el ticket de aperitivos
        $pdf = new TCPDF('P', 'mm', array(80, 150), true, 'UTF-8', false);
        $pdf->SetCreator('Cine Vibe');
        $pdf->SetTitle('Ticket Aperitivos - Cine Vibe');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(5, 5, 5);
        $pdf->AddPage();

        // Diseño del ticket
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'F');

        // Encabezado
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'CINE VIBE', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'TICKET DE APERITIVOS', 0, 1, 'C');
        
        // Línea separadora
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->Ln(2);

        // Información de la venta
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 4, 'Fecha: ' . date('d/m/Y', strtotime($aperitivos[0]['fecha'])), 0, 1, 'L');
        $pdf->Cell(0, 4, 'Hora: ' . date('H:i'), 0, 1, 'L');
        $pdf->Cell(0, 4, 'Cliente: ' . $usuario['nombre'] . ' ' . $usuario['apellido'], 0, 1, 'L');
        $pdf->Cell(0, 4, 'Ticket #: ' . str_pad($ventaId, 6, '0', STR_PAD_LEFT), 0, 1, 'L');
        
        $pdf->Ln(2);
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->Ln(2);

        // Encabezados de la tabla
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(35, 4, 'Producto', 0, 0, 'L');
        $pdf->Cell(10, 4, 'Cant', 0, 0, 'R');
        $pdf->Cell(15, 4, 'Precio', 0, 0, 'R');
        $pdf->Cell(15, 4, 'Total', 0, 1, 'R');

        // Detalles de los aperitivos
        $total = 0;
        $pdf->SetFont('helvetica', '', 8);
        foreach ($aperitivos as $aperitivo) {
            $pdf->Cell(35, 4, substr($aperitivo['nombre'], 0, 20), 0, 0, 'L');
            $pdf->Cell(10, 4, $aperitivo['cantidad'], 0, 0, 'R');
            $pdf->Cell(15, 4, '$' . number_format($aperitivo['precio_unitario'], 2), 0, 0, 'R');
            $pdf->Cell(15, 4, '$' . number_format($aperitivo['subtotal'], 2), 0, 1, 'R');
            $total += $aperitivo['subtotal'];
        }

        // Total
        $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(60, 4, 'TOTAL:', 0, 0, 'R');
        $pdf->Cell(15, 4, '$' . number_format($total, 2), 0, 1, 'R');

        // Ajuste del espacio entre el detalle y el pie de página
        $pdf->Ln(4); // Espacio pequeño entre el total y el pie

        // Pie del ticket (ajustar posición Y)
        $pdf->SetY($pdf->GetY() + 5); // Ajusta el valor para controlar el espacio
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 3, 'Presente este ticket en el candy bar', 0, 1, 'C');
        $pdf->Cell(0, 3, 'para retirar sus aperitivos', 0, 1, 'C');
        // Guardar PDF
        $pdfDir = __DIR__ . '/../assets/pdfs/';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }
        $pdfFile = 'aperitivos_' . $ventaId . '_' . time() . '_' . uniqid() . '.pdf';
        $pdfPath = $pdfDir . $pdfFile;
        $pdf->Output($pdfPath, 'F');

        return $pdfPath;

    } catch (Exception $e) {
        error_log("Error generando ticket de aperitivos: " . $e->getMessage());
        throw $e;
    } finally {
        if (isset($stmtAperitivos)) $stmtAperitivos->close();
        if (isset($stmtUsuario)) $stmtUsuario->close();
    }
}

function enviarEmailConEntradas($pdfInfo) {
    error_log("Iniciando envío de email con múltiples archivos");
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer ($level): $str");
        };

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cinevibestarosa@gmail.com';
        $mail->Password = 't s l b m t g e z b u m u l w e';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('cinevibestarosa@gmail.com', 'Cine Vibe');
        $mail->addAddress($pdfInfo['email']);

        $mail->Subject = 'Tus entradas para el cine';
        $mail->Body = "¡Gracias por tu compra!\n\n";
        $mail->Body .= "Adjunto encontrarás tus entradas para la función. Por favor, asegúrate de presentarlas al ingresar a la sala.\n\n";
        $mail->Body .= "¡Que disfrutes la película!\n\n";
        $mail->Body .= "Atentamente,\nEquipo Cine Vibe";

        // Adjuntar cada archivo PDF
        foreach ($pdfInfo['files'] as $pdfPath) {
            if (!file_exists($pdfPath)) {
                error_log("ERROR: El archivo PDF no existe en: " . $pdfPath);
                continue;
            }
            $mail->addAttachment($pdfPath);
        }

        error_log("Enviando email con " . count($pdfInfo['files']) . " archivos adjuntos...");
        $mail->send();
        error_log("Email enviado exitosamente");

        // Eliminar los archivos PDF después de enviarlos
        foreach ($pdfInfo['files'] as $pdfPath) {
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }

        return true;

    } catch (Exception $e) {
        error_log("ERROR en envío de email: " . $e->getMessage());
        if (isset($mail)) {
            error_log("Error específico de PHPMailer: " . $mail->ErrorInfo);
        }
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra Exitosa</title>
    <link rel="stylesheet" href="../assets/css/pago_exitoso.css">
</head>
<body>
<div class="message-container">
    <img src="../assets/images/pngs/logocine.png" alt="Logo del Cine" class="logo">
    
    <h1>¡Compra Realizada Exitosamente!</h1>
    <p>Gracias por tu compra. Tu reserva ha sido completada con éxito.</p>
    <?php if (isset($_SESSION['mensaje'])): ?>
        <p><?php echo $_SESSION['mensaje']; ?></p>
    <?php endif; ?>
    <div class="redirect-text">
        <p>Serás redirigido al inicio en <span id="countdown">10</span> segundos...</p>
        <p><a href="index.php">Ir al inicio ahora</a></p>
    </div>
</div>

    <script>
        // Redirigir después de 5 segundos
        let countdown = document.getElementById("countdown");
        let timeLeft = 10;

        setInterval(function() {
            if (timeLeft <= 0) {
                window.location.href = "index.php"; // Redirige al inicio
            } else {
                countdown.innerText = timeLeft;
            }
            timeLeft--;
        }, 1000);
    </script>
</body>
</html>

