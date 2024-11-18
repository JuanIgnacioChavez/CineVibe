<?php

session_start();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra Fallida</title>
    <link rel="stylesheet" href="../assets/css/pago_exitoso.css">
</head>
<body>
<div class="message-container">
    <img src="../assets/images/pngs/logocine.png" alt="Logo del Cine" class="logo">
    
    <h1>Lo sentimos, tu compra no pudo completarse</h1>
    <p>Hubo un problema al procesar tu compra. Por favor, intenta nuevamente o contacta con soporte si el problema persiste.</p>
    <?php if (isset($_SESSION['mensaje'])): ?>
        <p><?php echo $_SESSION['mensaje']; ?></p>
    <?php endif; ?>
    <div class="redirect-text">
        <p>Serás redirigido al inicio en <span id="countdown">10</span> segundos...</p>
        <p><a href="index.php">Ir al inicio ahora</a></p>
    </div>
</div>

<script>
    // Redirigir después de 10 segundos
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
