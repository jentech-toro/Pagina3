<?php
/**
 * procesar_contacto.php
 * Procesa el formulario de contacto y envía una alerta por correo usando PHPMailer.
 */

require 'init.php';
require 'conexion.php';

// Los 'use' deben ir siempre al principio del archivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException; // Alias para evitar conflictos

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Validar CSRF ──
if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: index.php?contact=csrf#contacto');
    exit;
}

// ── Campos del Formulario ──
$nombre   = trim($_POST['nombre']   ?? '');
$correo   = trim($_POST['correo']   ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$mensaje  = trim($_POST['mensaje']  ?? '');

if (!$nombre || !$correo || !$mensaje || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?contact=error#contacto');
    exit;
}

// ── Configuración SMTP ──
$smtp_host      = getenv('SMTP_HOST')      ?: 'smtp.gmail.com';
$smtp_user      = getenv('SMTP_USER')      ?: 'sales@jentechnology.ca';
$smtp_pass      = getenv('SMTP_PASS')      ?: 'xfjbltdguibhqvpy';
$smtp_port      = (int)(getenv('SMTP_PORT') ?: 587);
$smtp_secure    = getenv('SMTP_SECURE')    ?: 'tls';
$correo_destino = getenv('CORREO_DESTINO') ?: 'sales@jentechnology.ca';

// ── Cargar PHPMailer ──
$composerAutoload = __DIR__ . '/vendor/autoload.php';
$manualSrc        = __DIR__ . '/phpmailer/src';

if (file_exists($composerAutoload)) {
    require $composerAutoload;
} elseif (is_dir($manualSrc)) {
    require $manualSrc . '/Exception.php';
    require $manualSrc . '/PHPMailer.php';
    require $manualSrc . '/SMTP.php';
} else {
    error_log('PHPMailer no encontrado al intentar procesar un contacto.');
    header('Location: index.php?contact=error#contacto');
    exit;
}

$fecha = date('d/m/Y H:i');

// ── Diseño del Cuerpo HTML ──
$cuerpo_html = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f4f8;padding:32px 0;'>
  <tr><td align='center'>
    <table width='560' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>

      <tr>
        <td style='background:#0a1428;padding:24px 28px;'>
          <table width='100%' cellpadding='0' cellspacing='0'>
            <tr>
              <td>
                <div style='font-size:20px;font-weight:900;color:#ffffff;letter-spacing:1px;'>JENTECHNOLOGY</div>
                <div style='font-size:11px;color:#8899aa;margin-top:3px;'>Nuevo mensaje de contacto</div>
              </td>
              <td align='right'>
                <div style='font-size:11px;color:#8899aa;'>{$fecha}</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td style='background:#e8f4ff;border-left:4px solid #0064c8;padding:14px 28px;'>
          <div style='font-size:13px;font-weight:700;color:#0064c8;'>📬 Nuevo mensaje desde el sitio web</div>
        </td>
      </tr>

      <tr>
        <td style='padding:24px 28px;'>
          <table cellpadding='0' cellspacing='0' style='width:100%;background:#f8fafc;border-radius:8px;padding:16px;'>
            <tr>
              <td style='padding:5px 0;font-size:12px;color:#718096;width:100px;'>Nombre:</td>
              <td style='padding:5px 0;font-size:13px;color:#1a202c;font-weight:700;'>" . htmlspecialchars($nombre) . "</td>
            </tr>
            <tr>
              <td style='padding:5px 0;font-size:12px;color:#718096;'>Correo:</td>
              <td style='padding:5px 0;font-size:13px;'><a href='mailto:" . htmlspecialchars($correo) . "' style='color:#0064c8;'>" . htmlspecialchars($correo) . "</a></td>
            </tr>" .
            ($telefono ? "<tr>
              <td style='padding:5px 0;font-size:12px;color:#718096;'>Teléfono:</td>
              <td style='padding:5px 0;font-size:13px;color:#1a202c;'>" . htmlspecialchars($telefono) . "</td>
            </tr>" : "") . "
          </table>
        </td>
      </tr>

      <tr>
        <td style='padding:0 28px 24px;'>
          <div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#0064c8;margin-bottom:10px;'>Mensaje</div>
          <div style='background:#f8fafc;border-left:3px solid #0064c8;border-radius:0 8px 8px 0;padding:14px 16px;font-size:13px;color:#2d3748;line-height:1.7;'>
            " . nl2br(htmlspecialchars($mensaje)) . "
          </div>
        </td>
      </tr>

      <tr>
        <td style='padding:0 28px 24px;'>
          <a href='mailto:" . htmlspecialchars($correo) . "?subject=Re: Consulta en Jentechnology'
             style='display:inline-block;padding:10px 20px;background:#0064c8;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;'>
            ✉️ Responder a " . htmlspecialchars($nombre) . "
          </a>
        </td>
      </tr>

      <tr>
        <td style='background:#0a1428;padding:16px 28px;text-align:center;'>
          <div style='font-size:11px;color:#8899aa;'>Jentechnology · contacto@jentechnology.com · www.jentechnology.com</div>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>";

// ── Envío del correo ──
try {
    $mail = new PHPMailer(true);

    // Ajustes del Servidor
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = $smtp_secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    $mail->CharSet    = 'UTF-8';

    // Cabeceras de envío
    $mail->setFrom($smtp_user, 'Jentechnology Web');
    $mail->addAddress($correo_destino, 'Jentechnology');
    $mail->addReplyTo($correo, $nombre); // Permite responder directo desde el cliente de correo

    // Contenido del Correo
    $mail->isHTML(true);
    $mail->Subject = "Nuevo contacto: " . $nombre . ($telefono ? " — {$telefono}" : '');
    $mail->Body    = $cuerpo_html;
    $mail->AltBody = "Mensaje de {$nombre} ({$correo}): {$mensaje}";

    $mail->send();

    error_log("Contacto recibido de {$nombre} <{$correo}>");
    header('Location: index.php?contact=ok#contacto');
    exit;

} catch (MailerException $e) {
    error_log('Error de PHPMailer enviando contacto: ' . $mail->ErrorInfo);
    header('Location: index.php?contact=error#contacto');
    exit;
} catch (\Exception $e) {
    error_log('Error general en procesar_contacto: ' . $e->getMessage());
    header('Location: index.php?contact=error#contacto');
    exit;
}