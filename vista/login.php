<?php

require_once('../controlador/loginc.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <form action="login.php" method="post">
        <img src="../assets/images/pngs/logocine.png" alt="Logo" class="logo">
        <h2>Iniciar Sesión</h2>

        <?php if(isset($_GET['error'])) { ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php } ?>

        <label for="username">Usuario</label>
        <input type="text" name="user" id="user" placeholder="Usuario" required>

        <label for="password">Contraseña</label>
        <input type="password" name="pass" id="pass" placeholder="Contraseña" required>

        <p class="register-text">¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>.</p>
        <br>
        <button type="submit">Login</button>
    </form>
</body>
</html>