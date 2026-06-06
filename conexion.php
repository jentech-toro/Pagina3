<?php
// conexion.php - Conexión a la base de datos
// Los errores solo se muestran en desarrollo local; en producción se registran en log.
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || (getenv('APP_ENV') === 'local');

ini_set('display_errors', $is_local ? '1' : '0');
ini_set('display_startup_errors', $is_local ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$host     = getenv('DB_HOST') ?: '127.0.0.1';
$db_name  = getenv('DB_NAME') ?: 'sistema_login';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: ''; // Vacío por defecto en XAMPP

try {
    // Usamos el charset utf8mb4 estándar para MySQL/phpMyAdmin
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

    $conn = new PDO($dsn, $username, $password);

    // Configuración para que reporte los errores correctamente
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Siempre registramos el detalle técnico en el log del servidor
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());

    // En desarrollo mostramos el detalle; en producción solo un aviso genérico
    echo "<div style='background:#ffeb3b; color:#333; padding:15px; border:2px solid #f44336; font-family:monospace; margin:10px; border-radius:5px;'>";
    echo "<strong>Aviso del Sistema:</strong> No se pudo conectar a la base de datos.";
    if ($is_local) {
        echo "<br>Detalle técnico: " . htmlspecialchars($e->getMessage());
    }
    echo "</div>";

    // Dejamos la variable como null para que las páginas usen datos de prueba sin romperse
    $conn = null;
}
?>
