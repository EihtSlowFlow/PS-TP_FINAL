<?php
session_start();
require_once 'bd.php';

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables para la vista
$usuarios = [];
$error = null;
$mensaje = null;
$usuarioAEditar = null;

// Recuperar mensajes flash de la sesión
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Manejo de peticiones POST (Alta, Baja, Modificación)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación CSRF (Security Skill)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Error de seguridad: Token CSRF inválido.";
        header('Location: index.php');
        exit;
    }

    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            // ALTA
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if ($nombre !== '') {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email) VALUES (:nombre, :email)");
                $stmt->execute(['nombre' => $nombre, 'email' => $email]);
                
                $_SESSION['mensaje'] = "Usuario creado con éxito.";
                header('Location: index.php');
                exit;
            } else {
                $error = "El nombre es obligatorio para el alta.";
            }

        } elseif ($accion === 'actualizar') {
            // MODIFICACIÓN
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if ($id && $nombre !== '') {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id");
                $stmt->execute(['nombre' => $nombre, 'email' => $email, 'id' => $id]);
                
                $_SESSION['mensaje'] = "Usuario actualizado con éxito.";
                header('Location: index.php');
                exit;
            } else {
                $error = "El nombre y un ID válido son obligatorios para actualizar.";
            }

        } elseif ($accion === 'eliminar') {
            // BAJA
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                $_SESSION['mensaje'] = "Usuario eliminado con éxito.";
                header('Location: index.php');
                exit;
            }
        }
    } catch (PDOException $e) {
        $error = "Error en la base de datos durante la operación.";
    }
}

// Ya no se requiere recuperar datos vía GET para editar, la edición es In-Line.

// LECTURA (Obtener listado de usuarios siempre al final para mostrar la tabla actualizada)
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios ORDER BY id DESC");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    if (!$error) { 
        $error = "Error al obtener los usuarios. Verifique que la base de datos esté accesible.";
    }
}
?>
