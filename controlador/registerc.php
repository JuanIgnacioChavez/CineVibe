<?php   
if (isset($_REQUEST['guardar'])) {
    include_once "../includes/db_conn.php";

    $nombre = $_REQUEST['nombre'] ?? '';
    $apellido = $_REQUEST['apellido'] ?? '';
    $email = $_REQUEST['email'] ?? '';
    $user = $_REQUEST['user'] ?? '';
    $pass = $_REQUEST['pass'] ?? '';
    $confirm_pass = $_REQUEST['confirm_pass'] ?? '';  // Capturar el campo confirmación de contraseña
    $telefono = $_REQUEST['telefono'] ?? '';
    $dni = $_REQUEST['dni'] ?? '';

    // Inicializar variable de error
    $error_message = '';

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($user) || empty($pass) || empty($confirm_pass) || empty($telefono) || empty($dni)) {
        $error_message = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "El formato del email no es válido.";
    } elseif (strlen($pass) < 8) {
        $error_message = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($pass !== $confirm_pass) {
        $error_message = "Las contraseñas no coinciden.";  // Nueva verificación de contraseñas
    } elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
        $error_message = "El número de teléfono debe tener 10 dígitos.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,15}$/', $user)) {
        $error_message = "El nombre de usuario debe tener entre 3 y 15 caracteres y solo puede contener letras, números y guiones bajos.";
    } elseif (!preg_match('/^[0-9]{7,8}$/', $dni)) {
        $error_message = "El DNI debe tener entre 7 y 8 dígitos.";
    } else {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $email_check = $stmt->get_result()->fetch_assoc();
        
        // Verificar si el DNI ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE documento = ?");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $dni_check = $stmt->get_result()->fetch_assoc();

        // Verificar si el teléfono ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE telefono = ?");
        $stmt->bind_param("s", $telefono);
        $stmt->execute();
        $telefono_check = $stmt->get_result()->fetch_assoc();
        
        // Verificar si el nombre de usuario ya existe
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE user = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $user_check = $stmt->get_result()->fetch_assoc();

        // Comprobar si hay errores
        if ($email_check['count'] > 0) {
            $error_message = "Error: El email ya está registrado.";
        } elseif ($dni_check['count'] > 0) {
            $error_message = "Error: El DNI ya está registrado.";
        } elseif ($telefono_check['count'] > 0) {
            $error_message = "Error: El número de teléfono ya está registrado.";
        } elseif ($user_check['count'] > 0) {
            $error_message = "Error: El nombre de usuario ya está registrado.";
        } else {
            // Hashear la contraseña
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);

            // Insertar el usuario usando una consulta preparada
            $stmt = $conn->prepare("INSERT INTO usuarios (perfil, nombre, apellido, email, user, password, telefono, documento) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $perfil = 0;
            $stmt->bind_param("isssssss", $perfil, $nombre, $apellido, $email, $user, $hashedPass, $telefono, $dni);
            $result = $stmt->execute();

            if ($result) {
                header("Location: ../vista/index.php");
                exit(); 
            } else {
                $error_message = "Error al registrarse: " . $conn->error;
            }
        }
    }
}
?>
