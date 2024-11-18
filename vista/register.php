<?php  

require_once('../controlador/registerc.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
    <form action="" method="post">
        <img src="../assets/images/pngs/logocine.png" alt="Logo" class="logo">
        <h2>Registro</h2>

        <?php if (isset($error_message)): ?>
            <div class="error-message visible">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" placeholder="Nombre" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido</label>
                <input type="text" name="apellido" id="apellido" placeholder="Apellido" required>
            </div>

            <div class="form-group">
                <label for="username">Email</label>
                <input type="text" name="email" id="email" placeholder="Email" required>
            </div>

            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" name="user" id="user" placeholder="Usuario" required>
            </div>


            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="pass" id="pass" placeholder="Contraseña" required>
            </div>

            <div class="form-group">
                <label for="confirm_pass">Repetir Contraseña</label>
                <input type="password" name="confirm_pass" id="confirm_pass" placeholder="Contraseña" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" name="telefono" id="telefono" placeholder="Número de Teléfono" required>
            </div>

            <div class="form-group">
                <label for="dni">N° Documento</label>
                <input type="number" name="dni" id="dni" placeholder="Número de Documento" required>
            </div>
        </div>

        <button type="submit" name="guardar">Registrarse</button>
    </form>
</body>
</html>