<?php
require_once '../includes/db_conn.php';
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header('Location: login'); // Redirige al usuario a la página de inicio de sesión si no está autenticado
    exit();
}

$user_id = $_SESSION['id'];

// Obtener los datos del usuario desde la base de datos
$sql = "SELECT nombre, apellido, user, password FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Procesar la actualización de la contraseña
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordActual = $_POST['password_actual'];
    $nuevaContrasena = $_POST['nueva_contrasena'];
    $confirmarContrasena = $_POST['confirmar_contrasena'];

    // Validar que la contraseña actual tenga más de 8 caracteres
    if (strlen($passwordActual) < 8) {
        $message = 'La contraseña actual debe tener al menos 8 caracteres';
    } elseif (strlen($nuevaContrasena) < 8) { // Validar que la nueva contraseña tenga al menos 8 caracteres
        $message = 'La nueva contraseña debe tener al menos 8 caracteres';
    } elseif ($passwordActual === $nuevaContrasena) { // Verificar que la nueva contraseña no sea la misma que la actual
        $message = 'La nueva contraseña no puede ser igual a la contraseña actual';
    } else {
        // Verificar la contraseña actual
        if (password_verify($passwordActual, $user['password'])) {
            // Verificar que las nuevas contraseñas coincidan
            if ($nuevaContrasena === $confirmarContrasena) {
                // Encriptar la nueva contraseña
                $hashedPassword = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

                // Actualizar la contraseña en la base de datos
                $update_sql = "UPDATE usuarios SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("si", $hashedPassword, $user_id);

                if ($stmt->execute()) {
                    $message = 'Contraseña actualizada con éxito';
                } else {
                    $message = 'Error en la actualización de la contraseña';
                }
            } else {
                $message = 'Las nuevas contraseñas no coinciden';
            }
        } else {
            $message = 'La contraseña actual es incorrecta';
        }
    }
}

?>