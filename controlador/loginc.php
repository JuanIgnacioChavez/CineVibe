<?php
session_start();
require_once "../includes/db_conn.php";

if (isset($_POST['user']) && isset($_POST['pass'])) {

    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $user = validate($_POST['user']);
    $pass = validate($_POST['pass']);

    if (empty($user)) {
        header("Location: login.php?error=User is required");
        exit();
    } else if (empty($pass)) {
        header("Location: login.php?error=Password is required");
        exit();
    } else {
        // Uso de consultas preparadas para prevenir inyecciones SQL
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE user = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            // Comprobar la contraseña hasheada
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user'] = $row['user'];
                $_SESSION['id'] = $row['id'];
                $_SESSION['perfil'] = $row['perfil']; // Almacenar el perfil
                header("Location: ../vista/index.php");
                exit();
            } else {
                header("Location: login.php?error=User or Password Incorrect");
                exit();
            }
        } else {
            header("Location: login.php?error=User or Password Incorrect");
            exit();
        }
    }
}
?>