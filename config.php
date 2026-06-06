<?php
/**
 * config.php
 * Carga y valida configuración desde variables de entorno
 * NUNCA incluir credenciales reales en el código
 */

// ══════════════════════════════════════════════════════
// DETECTAR ENTORNO
// ══════════════════════════════════════════════════════
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || (getenv('APP_ENV') === 'local');

// ══════════════════════════════════════════════════════
// CARGAR .env MANUALMENTE si exists (para entornos sin auto-loader)
// ══════════════════════════════════════════════════════
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parsear KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // No sobrescribir si ya está definido en el sistema
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// ══════════════════════════════════════════════════════
// CONFIGURACIÓN BASE DE DATOS
// ══════════════════════════════════════════════════════
const DB_CONFIG = [
    'host'     => 'DB_HOST',
    'database' => 'DB_NAME',
    'username' => 'DB_USER',
    'password' => 'DB_PASS',
];

function getDbConfig() {
    $config = [];
    foreach (DB_CONFIG as $key => $env_var) {
        $value = getenv($env_var);
        if ($value === false) {
            throw new RuntimeException(
                "Variable de entorno $env_var no definida. " .
                "Copia .env.example a .env y configura tus credenciales."
            );
        }
        $config[$key] = $value;
    }
    return $config;
}

// ══════════════════════════════════════════════════════
// CONFIGURACIÓN SMTP (Correo)
// ══════════════════════════════════════════════════════
const SMTP_CONFIG = [
    'host'     => 'SMTP_HOST',
    'port'     => 'SMTP_PORT',
    'secure'   => 'SMTP_SECURE',
    'user'     => 'SMTP_USER',
    'password' => 'SMTP_PASS',
    'destino'  => 'CORREO_DESTINO',
];

function getSmtpConfig() {
    $config = [];
    $required = ['host', 'user', 'password', 'destino'];
    
    foreach (SMTP_CONFIG as $key => $env_var) {
        $value = getenv($env_var);
        
        // Las variables opcionales no lanzan error
        if ($value === false && in_array($key, $required)) {
            error_log("ADVERTENCIA: Variable SMTP $env_var no definida");
            return null; // Retornar null para indicar SMTP no configurado
        }
        
        // Defaults para variables opcionales
        if ($value === false) {
            if ($key === 'port') {
                $value = 587;
            } elseif ($key === 'secure') {
                $value = 'tls';
            } else {
                continue;
            }
        }
        
        $config[$key] = $value;
    }
    
    return !empty($config) ? $config : null;
}

// ══════════════════════════════════════════════════════
// CONFIGURACIÓN DE SEGURIDAD
// ══════════════════════════════════════════════════════
const SECURITY_CONFIG = [
    'admin_secret' => 'ADMIN_SECRET',
];

function getSecurityConfig() {
    $config = [];
    foreach (SECURITY_CONFIG as $key => $env_var) {
        $value = getenv($env_var);
        if ($value === false) {
            error_log("ADVERTENCIA: Variable de seguridad $env_var no definida");
            $value = 'CAMBIAR_EN_PRODUCCION';
        }
        $config[$key] = $value;
    }
    return $config;
}

// ══════════════════════════════════════════════════════
// VALIDACIÓN EN DESARROLLO
// ══════════════════════════════════════════════════════
if ($is_local) {
    try {
        getDbConfig();
        getSmtpConfig();
        getSecurityConfig();
    } catch (RuntimeException $e) {
        echo "<div style='background:#ffeb3b; color:#333; padding:15px; border:2px solid #f44336; font-family:monospace; margin:10px; border-radius:5px;'>";
        echo "<strong>⚠️ ERROR DE CONFIGURACIÓN:</strong><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
        exit;
    }
}

?>
