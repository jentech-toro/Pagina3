<?php
/**
 * enviar_cotizacion.php
 * Recibe JSON con PDF en base64 y lo envía por correo con PHPMailer.
 */

require 'init.php';
require 'conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException; // Renombrado para evitar conflictos

// Todas las respuestas de este endpoint son JSON
header('Content-Type: application/json');

// ── Solo usuarios logueados pueden enviar cotizaciones ──
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No autorizado.']);
    exit;
}

// ── Solo POST con JSON ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ── Leer body JSON ──
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido.']);
    exit;
}

// ── Validar CSRF ──
if (!validate_csrf_token($data['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de seguridad inválido.']);
    exit;
}

// ── Validar campos obligatorios ──
$nombre    = trim($data['nombre']    ?? '');
$correo    = trim($data['correo']    ?? '');
$empresa   = trim($data['empresa']   ?? '');
$telefono  = trim($data['telefono']  ?? '');
$folio     = trim($data['folio']     ?? 'COT-000000');
$validez   = trim($data['validez']   ?? '15 días');
$impuesto  = (float)($data['impuesto'] ?? 0);
$subtotal  = (float)($data['subtotal'] ?? 0);
$productos = $data['productos'] ?? [];
$pdfB64    = $data['pdf_base64']  ?? '';
$pdfNombre = $data['pdf_nombre']  ?? 'Cotizacion_Jentechnology.pdf';

if (!$nombre || !$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Nombre o correo inválidos.']);
    exit;
}
if (!$pdfB64) {
    echo json_encode(['ok' => false, 'error' => 'No se recibió el PDF.']);
    exit;
}
if (empty($productos)) {
    echo json_encode(['ok' => false, 'error' => 'La cotización está vacía.']);
    exit;
}
if (count($productos) > 200) {
    echo json_encode(['ok' => false, 'error' => 'Demasiados productos en la cotización.']);
    exit;
}

// ── Limitar tamaño del PDF (~10 MB en base64) ──
if (strlen($pdfB64) > 14_000_000) {
    echo json_encode(['ok' => false, 'error' => 'El PDF es demasiado grande.']);
    exit;
}

// ── Decodificar PDF ──
$pdfBytes = base64_decode($pdfB64, true);
if ($pdfBytes === false || $pdfBytes === '') {
    echo json_encode(['ok' => false, 'error' => 'Error al decodificar el PDF.']);
    exit;
}

// ══════════════════════════════════════════════════════
// CONFIGURACIÓN SMTP
// ══════════════════════════════════════════════════════
$smtp_host     = getenv('SMTP_HOST')     ?: 'smtp.gmail.com';
$smtp_user     = getenv('SMTP_USER')     ?: 'sales@jentechnology.ca';
$smtp_pass     = getenv('SMTP_PASS')     ?: 'xfjbltdguibhqvpy';
$smtp_port     = (int)(getenv('SMTP_PORT') ?: 587);
$smtp_secure   = getenv('SMTP_SECURE')   ?: 'tls';
$correo_destino = getenv('CORREO_DESTINO') ?: 'sales@jentechnology.ca';

if ($smtp_user === '' || $smtp_pass === '') {
    error_log('SMTP no configurado: definí SMTP_USER y SMTP_PASS en el entorno.');
    echo json_encode(['ok' => false, 'error' => 'Servidor de correo no configurado. Contactate por WhatsApp.']);
    exit;
}

// ══════════════════════════════════════════════════════
// CARGAR PHPMAILER
// ══════════════════════════════════════════════════════
$composerAutoload = __DIR__ . '/vendor/autoload.php';
$manualSrc = __DIR__ . '/phpmailer/src';

if (file_exists($composerAutoload)) {
    require $composerAutoload;
} elseif (is_dir($manualSrc)) {
    require $manualSrc . '/Exception.php';
    require $manualSrc . '/PHPMailer.php';
    require $manualSrc . '/SMTP.php';
} else {
    error_log('PHPMailer no encontrado.');
    echo json_encode(['ok' => false, 'error' => 'Servidor de correo no configurado. Contactate por WhatsApp.']);
    exit;
}

// ══════════════════════════════════════════════════════
// ARMAR Y ENVIAR CORREO
// ══════════════════════════════════════════════════════
$imp_monto = $subtotal * ($impuesto / 100);
$total     = $subtotal + $imp_monto;
$fecha     = date('d/m/Y H:i');

$tabla_productos = '';
foreach ($productos as $p) {
    $nom  = htmlspecialchars($p['nombre']   ?? '');
    $cant = (int)($p['cantidad'] ?? 1);
    $pu   = number_format((float)($p['precio'] ?? 0), 2);
    $tot  = number_format((float)($p['total']  ?? 0), 2);
    $tabla_productos .= "
    <tr>
      <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#1a202c;'>{$nom}</td>
      <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:center;color:#4a5568;'>{$cant}</td>
      <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:right;color:#4a5568;'>\${$pu}</td>
      <td style='padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;text-align:right;font-weight:700;color:#0064c8;'>\${$tot}</td>
    </tr>";
}

$cuerpo_html = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f4f8;padding:32px 0;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>
      <tr>
        <td style='background:#0a1428;padding:28px 32px;'>
          <table width='100%' cellpadding='0' cellspacing='0'>
            <tr>
              <td>
                <div style='font-size:22px;font-weight:900;color:#ffffff;letter-spacing:1px;'>JENTECHNOLOGY</div>
                <div style='font-size:11px;color:#8899aa;margin-top:4px;'>Seguridad · Vigilancia · Tecnología Dahua</div>
              </td>
              <td align='right'>
                <div style='font-size:13px;font-weight:700;color:#0099ff;'>COTIZACIÓN</div>
                <div style='font-size:11px;color:#8899aa;margin-top:2px;'>N°: " . htmlspecialchars($folio) . "</div>
                <div style='font-size:11px;color:#8899aa;'>Fecha: {$fecha}</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td style='background:#e8f4ff;border-left:4px solid #0064c8;padding:14px 32px;'>
          <div style='font-size:13px;font-weight:700;color:#0064c8;'>📋 Nueva cotización recibida</div>
          <div style='font-size:12px;color:#4a6080;margin-top:2px;'>Un cliente generó una cotización desde el sitio web.</div>
        </td>
      </tr>
      <tr>
        <td style='padding:24px 32px 16px;'>
          <div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#0064c8;margin-bottom:12px;'>Datos del cliente</div>
          <table cellpadding='0' cellspacing='0' style='background:#f8fafc;border-radius:8px;padding:16px;width:100%;'>
            <tr>
              <td style='padding:4px 0;font-size:12px;color:#718096;width:110px;'>Nombre:</td>
              <td style='padding:4px 0;font-size:13px;color:#1a202c;font-weight:700;'>" . htmlspecialchars($nombre) . "</td>
            </tr>" .
            ($empresa ? "<tr><td style='padding:4px 0;font-size:12px;color:#718096;'>Empresa:</td><td style='padding:4px 0;font-size:13px;color:#1a202c;'>" . htmlspecialchars($empresa) . "</td></tr>" : "") . "
            <tr>
              <td style='padding:4px 0;font-size:12px;color:#718096;'>Correo:</td>
              <td style='padding:4px 0;font-size:13px;color:#0064c8;'><a href='mailto:" . htmlspecialchars($correo) . "' style='color:#0064c8;'>" . htmlspecialchars($correo) . "</a></td>
            </tr>" .
            ($telefono ? "<tr><td style='padding:4px 0;font-size:12px;color:#718096;'>Teléfono:</td><td style='padding:4px 0;font-size:13px;color:#1a202c;'>" . htmlspecialchars($telefono) . "</td></tr>" : "") . "
            <tr>
              <td style='padding:4px 0;font-size:12px;color:#718096;'>Validez:</td>
              <td style='padding:4px 0;font-size:13px;color:#1a202c;'>" . htmlspecialchars($validez) . "</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td style='padding:0 32px 16px;'>
          <div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#0064c8;margin-bottom:12px;'>Productos cotizados</div>
          <table width='100%' cellpadding='0' cellspacing='0' style='border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;'>
            <thead>
              <tr style='background:#0064c8;'>
                <th style='padding:10px 12px;font-size:11px;font-weight:700;color:#fff;text-align:left;text-transform:uppercase;letter-spacing:.5px;'>Producto</th>
                <th style='padding:10px 12px;font-size:11px;font-weight:700;color:#fff;text-align:center;'>Cant.</th>
                <th style='padding:10px 12px;font-size:11px;font-weight:700;color:#fff;text-align:right;'>P. Unit.</th>
                <th style='padding:10px 12px;font-size:11px;font-weight:700;color:#fff;text-align:right;'>Total</th>
              </tr>
            </thead>
            <tbody>{$tabla_productos}</tbody>
          </table>
        </td>
      </tr>
      <tr>
        <td style='padding:0 32px 24px;'>
          <table align='right' cellpadding='0' cellspacing='0' style='min-width:220px;'>
            <tr>
              <td style='padding:5px 12px;font-size:12px;color:#718096;background:#f8fafc;border-radius:6px 6px 0 0;'>Subtotal</td>
              <td style='padding:5px 12px;font-size:12px;color:#1a202c;text-align:right;background:#f8fafc;'>\$" . number_format($subtotal, 2) . "</td>
            </tr>
            <tr>
              <td style='padding:5px 12px;font-size:12px;color:#718096;background:#f8fafc;'>Impuesto (" . number_format($impuesto, 1) . "%)</td>
              <td style='padding:5px 12px;font-size:12px;color:#1a202c;text-align:right;background:#f8fafc;'>\$" . number_format($imp_monto, 2) . "</td>
            </tr>
            <tr>
              <td style='padding:10px 12px;font-size:14px;font-weight:700;color:#fff;background:#0064c8;border-radius:0 0 0 6px;'>TOTAL</td>
              <td style='padding:10px 12px;font-size:14px;font-weight:700;color:#fff;text-align:right;background:#0064c8;border-radius:0 0 6px 0;'>\$" . number_format($total, 2) . "</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td style='padding:0 32px 24px;'>
          <div style='background:#f8fafc;border-radius:8px;padding:14px 16px;font-size:11px;color:#718096;line-height:1.6;'>
            <strong style='color:#4a5568;'>Notas:</strong><br>
            • Los precios no incluyen instalación salvo indicación expresa.<br>
            • Esta cotización es válida por " . htmlspecialchars($validez) . " a partir de la fecha de emisión.<br>
            • El PDF de la cotización se adjunta a este correo.
          </div>
        </td>
      </tr>
      <tr>
        <td style='background:#0a1428;padding:18px 32px;text-align:center;'>
          <div style='font-size:11px;color:#8899aa;'>Jentechnology · contacto@jentechnology.com · www.jentechnology.com</div>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>";

// ── Guardar cotización en BD ──
try {
    $stmt = $conn->prepare(
        'INSERT INTO cotizaciones
         (folio, cliente_nombre, cliente_empresa, cliente_correo, cliente_telefono, productos_json, subtotal, impuesto_pct, impuesto_monto, total, validez)
         VALUES (:folio, :cn, :ce, :cc, :ct, :pj, :sub, :ipct, :imonto, :tot, :val)'
    );
    $stmt->execute([
        ':folio'   => $folio,
        ':cn'      => $nombre,
        ':ce'      => $empresa,
        ':cc'      => $correo,
        ':ct'      => $telefono,
        ':pj'      => json_encode($productos, JSON_UNESCAPED_UNICODE),
        ':sub'     => $subtotal,
        ':ipct'    => $impuesto,
        ':imonto'  => round($subtotal * $impuesto / 100, 2),
        ':tot'     => round($subtotal + $subtotal * $impuesto / 100, 2),
        ':val'     => $validez,
    ]);
} catch (\Exception $dbErr) { // Se cambió a \Exception global para capturar PDOException correctamente
    error_log('No se pudo guardar cotización en BD: ' . $dbErr->getMessage());
}

// ── Enviar Correo con PHPMailer ──
try {
    $mail = new PHPMailer(true);

    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = $smtp_secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    $mail->CharSet    = 'UTF-8';

    // Remitente y destinatario
    $mail->setFrom($smtp_user, 'Jentechnology Web');
    $mail->addAddress($correo_destino, 'Jentechnology');
    $mail->addReplyTo($correo, $nombre);

    // Asunto y cuerpo
    $mail->isHTML(true);
    $mail->Subject = "Nueva cotización {$folio} — {$nombre}" . ($empresa ? " ({$empresa})" : '');
    $mail->Body    = $cuerpo_html;
    $mail->AltBody = "Nueva cotización {$folio} de {$nombre} ({$correo}). Ver PDF adjunto.";

    // Adjuntar PDF desde memoria
    $mail->addStringAttachment($pdfBytes, $pdfNombre, PHPMailer::ENCODING_BASE64, 'application/pdf');

    $mail->send();

    error_log("Cotización {$folio} enviada OK — Cliente: {$nombre} <{$correo}>");
    echo json_encode(['ok' => true, 'folio' => $folio]);

} catch (MailerException $e) { // Captura específica de errores de PHPMailer
    error_log("Error de PHPMailer enviando cotización {$folio}: " . $mail->ErrorInfo);
    echo json_encode(['ok' => false, 'error' => 'No se pudo enviar el correo de manera interna.']);
} catch (\Exception $e) { // Captura cualquier otro error en el proceso de envío
    error_log("Error general enviando cotización {$folio}: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'No se pudo enviar el correo. Intentá de nuevo o contactanos por WhatsApp.']);
}