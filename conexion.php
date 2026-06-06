<?php
/**
 * conexion.php (ACTUALIZADO)
 * Conexión a base de datos usando configuración centralizada
 */

require 'config.php';

// Configurar display de errores
ini_set('display_errors', $is_local ? '1' : '0');
ini_set('display_startup_errors', $is_local ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

try {
    $dbConfig = getDbConfig();
    
    $dsn = sprintf(
        "mysql:host=%s;dbname=%s;charset=utf8mb4",
        $dbConfig['host'],
        $dbConfig['database']
    );

    $conn = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password']
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());

    echo "<div style='background:#ffeb3b; color:#333; padding:15px; border:2px solid #f44336; font-family:monospace; margin:10px; border-radius:5px;'>";
    echo "<strong>Aviso del Sistema:</strong> No se pudo conectar a la base de datos.";
    if ($is_local) {
        echo "<br>Detalle técnico: " . htmlspecialchars($e->getMessage());
    }
    echo "</div>";

    $conn = null;
}
?>
