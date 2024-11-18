<?php

require_once('../login/cambiopass.php');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="../assets/css/forms.css">
</head>
<body>
    <header>
        <h1>Cambiar Contraseña</h1>
    </header>
    <main>
        <?php if ($message): ?>
            <p class="alert <?php echo strpos($message, 'éxito') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form action="" method="post">
            <label for="password_actual">Contraseña Actual:</label>
            <input type="password" id="password_actual" name="password_actual" required>

            <label for="nueva_contrasena">Nueva Contraseña:</label>
            <input type="password" id="nueva_contrasena" name="nueva_contrasena" required>

            <label for="confirmar_contrasena">Confirmar Nueva Contraseña:</label>
            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>

            <input type="submit" value="Actualizar Contraseña">

            <!-- Botón para volver a index.php -->
            <button type="button" onclick="window.location.href='index.php'" class="btn">Volver a Inicio</button>
        </form>

    </main>
</body>
</html>