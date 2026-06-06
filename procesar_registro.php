<?php
/**
 * procesar_registro.php (ACTUALIZADO)
 * Manejo seguro del registro con admin_secret desde configuración centralizada
 */

require 'init.php';
require 'config.php';
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email        = isset($_POST['email'])            ? trim($_POST['email'])            : '';
    $password     = isset($_POST['password'])         ? trim($_POST['password'])         : '';
    $confirm      = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $admin_code   = isset($_POST['admin_code'])       ? trim($_POST['admin_code'])       : '';
    $csrf         = isset($_POST['csrf_token'])       ? $_POST['csrf_token']             : null;

    $returnRaw = isset($_POST['return_to']) ? basename(trim($_POST['return_to'])) : 'index.php';
    $safeReturn = in_array($returnRaw, ['index.php', 'productos.php']) ? $returnRaw : 'index.php';

    if (!validate_csrf_token($csrf)) {
        header('Location: ' . $safeReturn . '?reg_error=csrf');
        exit;
    }

    if ($email === '' || $password === '' || $confirm === '') {
        header('Location: ' . $safeReturn . '?reg_error=1');
        exit;
    }

    if ($password !== $confirm) {
        header('Location: ' . $safeReturn . '?reg_error=mismatch');
        exit;
    }

    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[A-Za-z]/', $password)) {
        header('Location: ' . $safeReturn . '?reg_error=weak');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . $safeReturn . '?reg_error=1');
        exit;
    }

    // ── Verificar si el correo ya existe ──
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = :email');
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: ' . $safeReturn . '?reg_error=existe');
        exit;
    }

    $nombre = explode('@', $email)[0];
    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $rol    = 'user';
    
    // ── Obtener admin_secret desde config.php (seguro, no hardcodeado) ──
    $securityConfig = getSecurityConfig();
    $adminSecret = $securityConfig['admin_secret'];

    if ($admin_code !== '') {
        if ($admin_code !== $adminSecret) {
            header('Location: ' . $safeReturn . '?reg_error=badcode');
            exit;
        }
        $rol = 'admin';
    }

    $insert = $conn->prepare(
        'INSERT INTO usuarios (nombre, email, password, rol, fecha_registro)
         VALUES (:nombre, :email, :password, :rol, NOW())'
    );
    $insert->bindParam(':nombre',   $nombre);
    $insert->bindParam(':email',    $email);
    $insert->bindParam(':password', $hash);
    $insert->bindParam(':rol',      $rol);

    if ($insert->execute()) {
        header('Location: ' . $safeReturn . '?reg_success=1');
    } else {
        header('Location: ' . $safeReturn . '?reg_error=1');
    }
    exit;
}

header('Location: index.php');
exit;
?>
