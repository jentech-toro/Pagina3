<?php
require 'init.php';
require 'conexion.php';

$products_php = [];
try {
    $stmt = $conn->query('SELECT id, nombre, descripcion, precio, tag, imagen FROM productos WHERE activo = 1 ORDER BY id ASC');
    $products_php = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (empty($products_php)) {
    $products_php = [
        ['id'=>1,'nombre'=>'DH-HAC-HDW1200TQN-IL-T-0280B-DIP','descripcion'=>'CAMARA TIPO DOMO TORRETA SEMIMETALICO 2MP@30FPS 2.8MM CVI/CVBS/AHD/TVI SMART DUAL LIGHT 40M AUDIO BIDIRECCIONAL IP67','precio'=>51.23,'tag'=>'Destacado','imagen'=>'imagen/DH-HAC-HDW1200TQN-IL-T-0280B-DIP.jpeg'],
        ['id'=>2,'nombre'=>'DH-HAC-PT1200AN-IL-A-0280B-S6','descripcion'=>'CAMARA DOMO PT 2MP 4 EN 1 LENTE FIJO 2.8MM IR 40M 3D NR CON AUDIO IP66','precio'=>83.70,'tag'=>'Popular','imagen'=>'imagen/DH-HAC-PT1200AN-IL-A-0280B-S6.jpeg'],
        ['id'=>3,'nombre'=>'DH-HAC-HFW1500RLN-IL-A-0280B-S3-DIP','descripcion'=>'BALA PLASTICA 4 EN 1 5MP@25FPS 2.8MM SMART DUAL LIGHT 30M 3D NR STARLIGHT CON MIC. IP67','precio'=>52.25,'tag'=>'Nuevo','imagen'=>'imagen/DH-HAC-HFW1500RLN-IL-A-0280B-S3-DIP.webp'],
        ['id'=>4,'nombre'=>'DH-SD49218DBN-HC','descripcion'=>'CAMARA DOMO PTZ HDCVI 2MP@30FPS ZOOM 18X STARLIGHT IR 100M 120DB TRUE WDR IP66','precio'=>478.84,'tag'=>'Premium','imagen'=>'imagen/DH-SD49218DBN-HC.jpeg'],
    ];
}

$loggedIn  = isset($_SESSION['user_id']);
$userName  = $loggedIn ? htmlspecialchars($_SESSION['user_name'] ?? '') : '';
$userEmail = $loggedIn ? ($_SESSION['user_email'] ?? '') : '';
$csrf      = generate_csrf_token();

$loginError = ''; $regError = ''; $regSuccess = false; $openModal = '';
if (isset($_GET['error'])) {
    $errMap = ['csrf'=>'Token de seguridad inválido. Refresca la página.','locked'=>'Demasiados intentos fallidos. Espera 15 minutos.','1'=>'Correo o contraseña incorrectos.'];
    $loginError = $errMap[$_GET['error']] ?? 'Error al iniciar sesión.';
    $openModal  = 'loginModal';
}
if (isset($_GET['reg_error'])) {
    $regMap = ['1'=>'Completá todos los campos.','existe'=>'Ese correo ya está registrado.','weak'=>'La contraseña debe tener mínimo 8 caracteres con letras y números.','mismatch'=>'Las contraseñas no coinciden.','badcode'=>'Código de administrador incorrecto.','csrf'=>'Token de seguridad inválido.'];
    $regError  = $regMap[$_GET['reg_error']] ?? 'Error al registrarse.';
    $openModal = 'registerModal';
}
if (isset($_GET['reg_success'])) { $regSuccess = true; $openModal = 'loginModal'; }

// Datos de empresa para el PDF (editá estos valores)
$empresa_nombre  = 'Jentechnology';
$empresa_email   = 'sales@jentechnology.ca';
$empresa_web     = 'www.jentechnology.ca';
$empresa_wa      = '+19053922189';
$empresa_slogan  = 'Seguridad · Vigilancia · Tecnología';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jentechnology — Seguridad & Vigilancia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
:root{
  --bg:#070a10;--surface:#0d1117;--card:#0f1620;--border:rgba(255,255,255,0.07);
  --accent:#00aaff;--accent2:#ff3d71;--text:#e8edf5;--muted:#6b7b93;
  --font-head:'Syne',sans-serif;--font-mono:'Space Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{background:var(--bg);color:var(--text);font-family:var(--font-mono);overflow-x:hidden;}
img{display:block;max-width:100%;}
a{text-decoration:none;color:inherit;}
button{cursor:pointer;border:none;background:none;font-family:inherit;}

/* INTRO */
#intro{position:fixed;inset:0;z-index:9999;background:#000;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;}
.scan-lines{position:absolute;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.4) 2px,rgba(0,0,0,.4) 4px);pointer-events:none;z-index:2;}
.vhs-noise{position:absolute;inset:0;opacity:.15;pointer-events:none;z-index:3;animation:noiseShift .08s steps(1) infinite;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.08'/%3E%3C/svg%3E");}
@keyframes noiseShift{0%{transform:translate(0,0)}25%{transform:translate(-2px,1px)}50%{transform:translate(1px,-2px)}75%{transform:translate(-1px,2px)}100%{transform:translate(2px,0)}}
.glitch-bar{position:absolute;left:0;right:0;height:2px;background:rgba(0,170,255,.7);top:0;z-index:4;animation:glitchScan 3.5s linear infinite;box-shadow:0 0 14px rgba(0,170,255,.9);}
@keyframes glitchScan{0%{top:-4px;opacity:0}5%{opacity:1}95%{opacity:1}100%{top:100%;opacity:0}}
.rec-badge{position:absolute;top:1.5rem;left:1.5rem;z-index:10;display:flex;align-items:center;gap:.5rem;font-family:var(--font-mono);font-size:.72rem;letter-spacing:.18em;color:#fff;text-transform:uppercase;}
.rec-dot{width:10px;height:10px;border-radius:50%;background:#ff3b30;box-shadow:0 0 8px #ff3b30;animation:blink 1s step-end infinite;}
@keyframes blink{50%{opacity:0}}
.rec-timecode{position:absolute;top:1.5rem;right:1.5rem;z-index:10;font-family:var(--font-mono);font-size:.72rem;color:rgba(255,255,255,.6);letter-spacing:.12em;}
.cam-label{position:absolute;bottom:1.5rem;left:1.5rem;z-index:10;font-family:var(--font-mono);font-size:.62rem;color:rgba(255,255,255,.4);letter-spacing:.15em;text-transform:uppercase;}
.corner{position:absolute;width:20px;height:20px;z-index:10;}
.corner--tl{top:3rem;left:3rem;border-top:1.5px solid rgba(0,170,255,.5);border-left:1.5px solid rgba(0,170,255,.5);}
.corner--tr{top:3rem;right:3rem;border-top:1.5px solid rgba(0,170,255,.5);border-right:1.5px solid rgba(0,170,255,.5);}
.corner--bl{bottom:3rem;left:3rem;border-bottom:1.5px solid rgba(0,170,255,.5);border-left:1.5px solid rgba(0,170,255,.5);}
.corner--br{bottom:3rem;right:3rem;border-bottom:1.5px solid rgba(0,170,255,.5);border-right:1.5px solid rgba(0,170,255,.5);}
.intro-content{position:relative;z-index:10;text-align:center;display:flex;flex-direction:column;align-items:center;gap:1.75rem;}
.intro-logo{width:180px;height:auto;filter:drop-shadow(0 0 20px rgba(0,170,255,.5));animation:logoPulse 2.5s ease-in-out infinite;}
@keyframes logoPulse{0%,100%{filter:drop-shadow(0 0 20px rgba(0,170,255,.5))}50%{filter:drop-shadow(0 0 35px rgba(0,170,255,.85))}}
.intro-sub{font-size:.68rem;letter-spacing:.4em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-top:-.5rem;}
.intro-progress-wrap{width:min(300px,80vw);height:2px;background:rgba(255,255,255,.07);border-radius:99px;overflow:hidden;}
.intro-progress-bar{height:100%;width:0%;background:linear-gradient(90deg,var(--accent),#fff);border-radius:99px;box-shadow:0 0 10px var(--accent);}
.intro-msg{font-size:.62rem;letter-spacing:.22em;color:rgba(255,255,255,.28);text-transform:uppercase;min-height:1em;}

/* SITE */
#site{opacity:0;transform:scale(1.02);transition:opacity .8s ease,transform .8s ease;min-height:100vh;}
#site.visible{opacity:1;transform:scale(1);}

/* NAV */
nav{position:sticky;top:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:.9rem 2rem;background:rgba(7,10,16,.9);backdrop-filter:blur(24px);border-bottom:1px solid var(--border);}
.nav-logo{height:36px;width:auto;object-fit:contain;}
.nav-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;}
.btn-ghost{padding:.4rem .9rem;border:1px solid var(--border);border-radius:6px;font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);transition:all .2s;}
.btn-ghost:hover{border-color:var(--accent);color:var(--accent);}
.btn-accent{padding:.4rem .9rem;background:var(--accent);color:#000;border-radius:6px;font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;transition:opacity .2s;}
.btn-accent:hover{opacity:.85;}
.cart-btn{position:relative;padding:.4rem .7rem;border:1px solid var(--border);border-radius:6px;font-size:.95rem;color:var(--text);transition:border-color .2s;}
.cart-btn:hover{border-color:var(--accent);}
.cart-count{position:absolute;top:-6px;right:-6px;width:16px;height:16px;border-radius:50%;background:var(--accent2);font-size:.58rem;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;opacity:0;transition:opacity .2s;}
.cart-count.has-items{opacity:1;}

/* HERO */
.hero{padding:5rem 2rem 3.5rem;max-width:860px;margin:0 auto;}
.hero-tag{display:inline-flex;align-items:center;gap:.5rem;padding:.28rem .75rem;border:1px solid rgba(0,170,255,.25);border-radius:99px;font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;color:var(--accent);margin-bottom:1.5rem;}
.hero-tag::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--accent);animation:blink 1.4s step-end infinite;}
.hero h1{font-family:var(--font-head);font-size:clamp(2.2rem,5.5vw,4.5rem);font-weight:800;line-height:1.05;letter-spacing:-.03em;margin-bottom:1rem;}
.hero h1 em{font-style:normal;color:var(--accent);}
.hero p{font-size:.8rem;line-height:1.9;color:var(--muted);max-width:500px;margin-bottom:2rem;}
.hero-stripe{width:50px;height:2px;background:var(--accent);margin-bottom:2rem;box-shadow:0 0 10px var(--accent);}
.hero-btns{display:flex;gap:.65rem;flex-wrap:wrap;}

/* PRICE GATE */
.price-gate-wrap{padding:0 2rem;max-width:1280px;margin:0 auto 2.5rem;}
.price-gate{border:1px solid rgba(255,61,113,.2);border-radius:12px;background:rgba(255,61,113,.04);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1.1rem 1.75rem;}
.price-gate p{font-size:.73rem;line-height:1.7;color:var(--muted);}
.price-gate p strong{color:var(--text);}
.price-gate-actions{display:flex;gap:.5rem;flex-shrink:0;}

/* SECTION HEADER */
.section-header{display:flex;align-items:baseline;justify-content:space-between;padding:0 2rem;max-width:1280px;margin:0 auto 1.75rem;}
.section-header h2{font-family:var(--font-head);font-size:1.5rem;font-weight:800;letter-spacing:-.02em;}
.section-header span{font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);}

/* PRODUCT GRID */
.products-wrap{padding:0 2rem 6rem;max-width:1280px;margin:0 auto;}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.15rem;}
.product-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:transform .25s,border-color .25s,box-shadow .25s;cursor:pointer;}
.product-card:hover{transform:translateY(-5px);border-color:rgba(0,170,255,.3);box-shadow:0 22px 44px rgba(0,0,0,.55),0 0 0 1px rgba(0,170,255,.12);}
.product-img-wrap{height:190px;background:#060c14;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.product-img-wrap img{width:100%;height:100%;object-fit:contain;padding:1rem;transition:transform .4s ease;filter:brightness(.95);}
.product-card:hover .product-img-wrap img{transform:scale(1.06);}
.product-badge{position:absolute;top:.65rem;left:.65rem;padding:.18rem .55rem;border-radius:4px;font-size:.56rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;}
.badge-hot{background:rgba(255,61,113,.2);color:var(--accent2);border:1px solid rgba(255,61,113,.3);}
.badge-new{background:rgba(0,170,255,.15);color:var(--accent);border:1px solid rgba(0,170,255,.28);}
.badge-sale{background:rgba(255,200,0,.14);color:#ffc800;border:1px solid rgba(255,200,0,.25);}
.badge-premium{background:rgba(160,100,255,.18);color:#c084fc;border:1px solid rgba(160,100,255,.3);}
.product-body{padding:1rem 1.15rem 1.15rem;}
.product-name{font-family:var(--font-head);font-size:.85rem;font-weight:700;line-height:1.3;margin-bottom:.45rem;color:var(--text);letter-spacing:-.01em;word-break:break-all;}
.product-desc{font-size:.67rem;line-height:1.75;color:var(--muted);margin-bottom:.9rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.product-footer{display:flex;align-items:flex-end;justify-content:space-between;gap:.5rem;flex-wrap:wrap;}
.product-price{font-family:var(--font-head);font-weight:800;font-size:1.1rem;color:var(--accent);}
.product-price.locked{filter:blur(5px);user-select:none;pointer-events:none;font-size:.85rem;color:var(--muted);}
.price-lock-msg{font-size:.55rem;color:var(--accent2);letter-spacing:.1em;text-transform:uppercase;margin-top:.15rem;display:none;}
.price-lock-msg.show{display:block;}
.product-actions{display:flex;gap:.35rem;align-items:center;flex-shrink:0;}
.btn-detail{padding:.38rem .7rem;border:1px solid var(--border);border-radius:6px;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);transition:all .2s;white-space:nowrap;}
.btn-detail:hover{border-color:var(--accent);color:var(--accent);}
.btn-cart{padding:.38rem .7rem;background:var(--accent);color:#000;border-radius:6px;font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;transition:opacity .2s,transform .15s;white-space:nowrap;}
.btn-cart:hover{opacity:.85;transform:scale(.97);}
.btn-cart:active{transform:scale(.92);}
.btn-wa-card{padding:.38rem .55rem;background:#25d366;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;transition:opacity .2s,transform .15s;flex-shrink:0;}
.btn-wa-card:hover{opacity:.85;transform:scale(.97);}
.btn-compare{padding:.38rem .55rem;border:1px solid var(--border);border-radius:6px;font-size:.75rem;color:var(--muted);transition:all .2s;flex-shrink:0;}
.btn-compare:hover{border-color:var(--accent);color:var(--accent);}
.btn-compare.selected{background:rgba(0,170,255,.15);border-color:var(--accent);color:var(--accent);}

/* WHATSAPP FAB */
.whatsapp-fab{position:fixed;bottom:2rem;right:2rem;z-index:200;width:54px;height:54px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 20px rgba(37,211,102,.4);transition:transform .2s,box-shadow .2s;}
.whatsapp-fab:hover{transform:scale(1.1);box-shadow:0 6px 28px rgba(37,211,102,.55);}
.whatsapp-fab svg{width:28px;height:28px;fill:#fff;}

/* MODALS */
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:500;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(5px);}
.modal-backdrop.open{display:flex;animation:fadeIn .2s ease;}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:16px;width:100%;max-width:460px;max-height:90vh;overflow-y:auto;animation:slideUp .25s ease;position:relative;}
@keyframes slideUp{from{transform:translateY(18px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-close{position:absolute;top:.9rem;right:.9rem;width:28px;height:28px;border-radius:50%;border:1px solid var(--border);color:var(--muted);font-size:.95rem;display:flex;align-items:center;justify-content:center;transition:all .2s;z-index:2;}
.modal-close:hover{border-color:var(--accent2);color:var(--accent2);}
.modal-header{padding:1.6rem 1.6rem .9rem;border-bottom:1px solid var(--border);}
.modal-logo{height:32px;width:auto;margin-bottom:.75rem;object-fit:contain;}
.modal-header h3{font-family:var(--font-head);font-size:1.15rem;font-weight:800;color:var(--text);}
.modal-header p{font-size:.7rem;color:var(--muted);margin-top:.2rem;}
.modal-body{padding:1.35rem 1.6rem;}
.form-group{margin-bottom:.9rem;}
.form-group label{display:block;font-size:.62rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;}
.form-group input,.form-group select{width:100%;background:var(--card);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem;font-family:var(--font-mono);font-size:.76rem;color:var(--text);outline:none;transition:border-color .2s;}
.form-group input:focus,.form-group select:focus{border-color:var(--accent);}
.form-group input::placeholder{color:var(--muted);}
.modal-footer{padding:0 1.6rem 1.6rem;display:flex;flex-direction:column;gap:.55rem;}
.btn-full{width:100%;padding:.72rem;border-radius:8px;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;transition:all .2s;cursor:pointer;}
.btn-full.primary{background:var(--accent);color:#000;border:none;}
.btn-full.primary:hover{opacity:.85;}
.btn-full.secondary{border:1px solid var(--border);color:var(--muted);background:none;}
.btn-full.secondary:hover{border-color:var(--accent);color:var(--accent);}
.modal-note{font-size:.6rem;color:var(--muted);text-align:center;line-height:1.6;}
.modal-note a{color:var(--accent);text-decoration:underline;}
.modal-alert{padding:.55rem .85rem;border-radius:6px;font-size:.68rem;margin-bottom:.7rem;line-height:1.5;}
.modal-alert.error{background:rgba(255,61,113,.1);border:1px solid rgba(255,61,113,.28);color:#ff7a9a;}
.modal-alert.success{background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.28);color:var(--accent);}

/* DETAIL MODAL */
.detail-modal{max-width:580px;}
.detail-img-wrap{height:220px;background:#060c14;display:flex;align-items:center;justify-content:center;border-radius:14px 14px 0 0;overflow:hidden;}
.detail-img-wrap img{max-height:200px;width:auto;object-fit:contain;padding:1rem;}
.detail-tag-row{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.9rem;}
.detail-chip{padding:.18rem .55rem;border:1px solid var(--border);border-radius:4px;font-size:.58rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);}
.detail-name{font-family:var(--font-head);font-size:1.1rem;font-weight:800;letter-spacing:-.01em;margin-bottom:.5rem;line-height:1.3;word-break:break-all;}
.detail-desc{font-size:.73rem;line-height:1.8;color:var(--muted);margin-bottom:1.15rem;}
.detail-price-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.detail-price{font-family:var(--font-head);font-weight:800;font-size:1.6rem;color:var(--accent);}
.detail-price.locked{filter:blur(7px);color:var(--muted);font-size:1.15rem;}
.whatsapp-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:#25d366;color:#fff;border-radius:7px;font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;transition:opacity .2s;}
.whatsapp-btn:hover{opacity:.85;}

/* CART PANEL */
.cart-panel{position:fixed;top:0;right:-420px;width:min(420px,100vw);height:100%;background:var(--surface);border-left:1px solid var(--border);z-index:600;transition:right .35s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;}
.cart-panel.open{right:0;}
.cart-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:599;display:none;backdrop-filter:blur(3px);}
.cart-overlay.open{display:block;}
.cart-panel-header{padding:1.35rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.cart-panel-header h3{font-family:var(--font-head);font-size:.95rem;font-weight:800;}
.cart-items{flex:1;overflow-y:auto;padding:.9rem 1.35rem;display:flex;flex-direction:column;gap:.65rem;}
.cart-empty{text-align:center;padding:3rem 1rem;color:var(--muted);font-size:.73rem;}
.cart-item{display:flex;gap:.65rem;align-items:center;padding:.7rem;border:1px solid var(--border);border-radius:10px;background:var(--card);}
.cart-item img{width:52px;height:52px;object-fit:contain;border-radius:6px;flex-shrink:0;background:#060c14;padding:4px;}
.cart-item-info{flex:1;min-width:0;}
.cart-item-name{font-size:.65rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.18rem;}
.cart-item-qty{display:flex;align-items:center;gap:.4rem;margin-top:.25rem;}
.cart-item-qty button{width:20px;height:20px;border-radius:4px;border:1px solid var(--border);color:var(--text);font-size:.75rem;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.cart-item-qty button:hover{border-color:var(--accent);color:var(--accent);}
.cart-item-qty span{font-size:.65rem;min-width:16px;text-align:center;}
.cart-item-price{font-size:.62rem;color:var(--accent);margin-top:.1rem;}
.cart-item-remove{font-size:.8rem;color:var(--muted);padding:.2rem;transition:color .2s;flex-shrink:0;}
.cart-item-remove:hover{color:var(--accent2);}
.cart-panel-footer{padding:1.15rem 1.5rem;border-top:1px solid var(--border);}
.cart-total-block{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:.75rem 1rem;margin-bottom:.9rem;}
.cart-total-row{display:flex;justify-content:space-between;align-items:center;font-size:.68rem;padding:.15rem 0;}
.cart-total-row.grand{font-family:var(--font-head);font-size:1rem;font-weight:800;color:var(--accent);border-top:1px solid var(--border);margin-top:.4rem;padding-top:.5rem;}
.cart-total-row span:first-child{color:var(--muted);}

/* TOAST */
.toast-wrap{position:fixed;bottom:6rem;right:2rem;z-index:700;display:flex;flex-direction:column;gap:.5rem;}
.toast{padding:.55rem .95rem;border-radius:8px;font-size:.68rem;letter-spacing:.07em;background:#131c28;border:1px solid var(--border);color:var(--text);animation:toastIn .3s ease,toastOut .3s ease 2.2s forwards;box-shadow:0 8px 24px rgba(0,0,0,.5);}
.toast.success{border-color:rgba(0,170,255,.35);color:var(--accent);}
.toast.error{border-color:rgba(255,61,113,.35);color:var(--accent2);}
@keyframes toastIn{from{transform:translateX(18px);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes toastOut{to{transform:translateX(18px);opacity:0}}
@keyframes cardIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* FOOTER */
footer{border-top:1px solid var(--border);padding:3rem 2rem 2rem;display:flex;flex-direction:column;align-items:center;gap:1.5rem;}
.footer-logo{height:32px;width:auto;opacity:.7;transition:opacity .2s;}
.footer-logo:hover{opacity:1;}
.footer-social{display:flex;align-items:center;gap:1rem;}
.social-link{width:38px;height:38px;border-radius:8px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);transition:all .2s;}
.social-link:hover{border-color:var(--accent);color:var(--accent);background:rgba(0,170,255,.07);}
.social-link svg{width:17px;height:17px;fill:currentColor;}
.footer-copy{font-size:.6rem;color:var(--muted);letter-spacing:.12em;text-align:center;line-height:1.8;}

/* PDF MODAL inputs row */
.form-row{display:flex;gap:.75rem;}
.form-row .form-group{flex:1;}

/* ── SEARCH & FILTERS ── */
.search-bar-wrap{padding:0 2rem;max-width:1280px;margin:0 auto 1.5rem;}
.search-bar{display:flex;gap:.65rem;flex-wrap:wrap;align-items:center;}
.search-input-wrap{flex:1;min-width:200px;position:relative;}
.search-input-wrap svg{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);width:15px;height:15px;fill:none;stroke:var(--muted);stroke-width:2;pointer-events:none;}
.search-input{width:100%;background:var(--card);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem .6rem 2.4rem;font-family:var(--font-mono);font-size:.75rem;color:var(--text);outline:none;transition:border-color .2s;}
.search-input:focus{border-color:var(--accent);}
.search-input::placeholder{color:var(--muted);}
.filter-chips{display:flex;gap:.4rem;flex-wrap:wrap;}
.chip{padding:.35rem .8rem;border:1px solid var(--border);border-radius:99px;font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);background:none;transition:all .2s;cursor:pointer;white-space:nowrap;}
.chip:hover{border-color:var(--accent);color:var(--accent);}
.chip.active{background:var(--accent);border-color:var(--accent);color:#000;font-weight:700;}
.search-results-info{padding:0 2rem;max-width:1280px;margin:-0.5rem auto 1rem;font-size:.65rem;color:var(--muted);letter-spacing:.1em;min-height:1rem;}
.no-results{grid-column:1/-1;text-align:center;padding:4rem 1rem;color:var(--muted);}
.no-results p{font-size:.8rem;margin-bottom:.5rem;}
.no-results span{font-size:.65rem;letter-spacing:.1em;}

@media(max-width:640px){
  nav{padding:.75rem 1rem;}
  .hero{padding:3rem 1rem 2rem;}
  .section-header,.products-wrap{padding-left:1rem;padding-right:1rem;}
  .price-gate-wrap{padding:0 1rem;}
  .modal-body,.modal-footer,.modal-header{padding-left:1.15rem;padding-right:1.15rem;}
  .form-row{flex-direction:column;gap:0;}

/* ── WHY SECTION ── */
.why-section{padding:5rem 2rem;background:linear-gradient(180deg,var(--bg) 0%,rgba(0,100,200,.04) 50%,var(--bg) 100%);position:relative;overflow:hidden;}
.why-section::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(0,170,255,.06) 0%,transparent 70%);pointer-events:none;}
.why-wrap{max-width:1100px;margin:0 auto;}
.why-header{text-align:center;margin-bottom:3rem;}
.why-title{font-family:var(--font-head);font-size:clamp(1.8rem,4vw,3rem);font-weight:800;letter-spacing:-.03em;line-height:1.1;margin-bottom:1rem;}
.why-title em{font-style:normal;color:var(--accent);}
.why-sub{font-size:.78rem;line-height:1.9;color:var(--muted);max-width:520px;margin:0 auto;}
.why-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.15rem;margin-bottom:3rem;}
.why-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.5rem;display:flex;flex-direction:column;gap:1rem;transition:transform .25s,border-color .25s,box-shadow .25s;position:relative;overflow:hidden;}
.why-card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(0,170,255,.04),transparent);opacity:0;transition:opacity .3s;}
.why-card:hover{transform:translateY(-4px);border-color:rgba(0,170,255,.25);box-shadow:0 16px 40px rgba(0,0,0,.4);}
.why-card:hover::before{opacity:1;}
.why-icon{font-size:2rem;width:52px;height:52px;background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.why-card-body h3{font-family:var(--font-head);font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:.4rem;}
.why-card-body p{font-size:.68rem;line-height:1.8;color:var(--muted);}
.why-cta{text-align:center;padding:2rem;border:1px solid var(--border);border-radius:16px;background:rgba(0,170,255,.03);}
.why-cta p{font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:var(--text);}

/* ── CONTACT SECTION ── */
.contact-section{padding:5rem 2rem;background:var(--bg);}
.contact-wrap{max-width:1100px;margin:0 auto;}
.contact-grid{display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start;}
.contact-form-wrap{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:2rem;}
.form-row-contact{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;}
.contact-field{margin-bottom:.9rem;}
.contact-field label{display:block;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;}
.contact-field input,.contact-field textarea{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem;font-family:var(--font-mono);font-size:.75rem;color:var(--text);outline:none;transition:border-color .2s;resize:vertical;}
.contact-field input:focus,.contact-field textarea:focus{border-color:var(--accent);}
.contact-field input::placeholder,.contact-field textarea::placeholder{color:var(--muted);}
.contact-info{display:flex;flex-direction:column;gap:.75rem;}
.contact-info-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.15rem;display:flex;align-items:flex-start;gap:.85rem;transition:border-color .2s;}
.contact-info-card:hover{border-color:rgba(0,170,255,.2);}
.contact-info-label{font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.25rem;}
.contact-info-value{font-size:.75rem;color:var(--text);font-weight:700;}
a.contact-info-value{color:var(--accent);transition:opacity .2s;}
a.contact-info-value:hover{opacity:.8;}
@media(max-width:860px){
  .contact-grid{grid-template-columns:1fr;}
  .contact-info{display:grid;grid-template-columns:1fr 1fr;}
}
@media(max-width:540px){
  .form-row-contact{grid-template-columns:1fr;}
  .contact-info{grid-template-columns:1fr;}
}

@media(max-width:640px){
  nav{padding:.75rem 1rem;}
  .hero{padding:3rem 1rem 2rem;}
  .section-header,.products-wrap{padding-left:1rem;padding-right:1rem;}
  .price-gate-wrap{padding:0 1rem;}
  .modal-body,.modal-footer,.modal-header{padding-left:1.15rem;padding-right:1.15rem;}
  .form-row{flex-direction:column;gap:0;}
}

</style>
</head>
<body>

<!-- INTRO -->
<div id="intro">
  <div class="scan-lines"></div><div class="vhs-noise"></div><div class="glitch-bar"></div>
  <div class="corner corner--tl"></div><div class="corner corner--tr"></div>
  <div class="corner corner--bl"></div><div class="corner corner--br"></div>
  <div class="rec-badge"><span class="rec-dot"></span> REC</div>
  <div class="rec-timecode" id="timecode">00:00:00:00</div>
  <div class="cam-label">CAM 01 · JENTECHNOLOGY · HDCVI · 2MP</div>
  <div class="intro-content">
    <img src="imagen/logo.png" alt="Jentechnology" class="intro-logo">
    <div class="intro-sub">Seguridad · Vigilancia · Tecnología</div>
    <div style="width:100%;display:flex;flex-direction:column;align-items:center;gap:.6rem;">
      <div class="intro-progress-wrap"><div class="intro-progress-bar" id="introBar"></div></div>
      <div class="intro-msg" id="introMsg">INICIALIZANDO SISTEMA...</div>
    </div>
  </div>
</div>

<!-- SITE -->
<div id="site">

<!-- NAV -->
<nav>
  <img src="imagen/logo.png" alt="Jentechnology" class="nav-logo">
  <div class="nav-actions">
    <button class="cart-btn" onclick="openCart()" title="Mi cotización">
      🛒 <span class="cart-count" id="cartCount">0</span>
    </button>
    <?php if ($loggedIn): ?>
      <span style="font-size:.68rem;color:var(--muted);">Hola, <strong style="color:var(--accent)"><?= $userName ?></strong></span>
      <a href="panel.php" class="btn-ghost">Panel</a>
      <a href="logout.php" class="btn-ghost">Salir</a>
    <?php else: ?>
      <button class="btn-ghost" onclick="openModal('loginModal')">Ingresar</button>
      <button class="btn-accent" onclick="openModal('registerModal')">Registrarse</button>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-tag">Catálogo de seguridad Dahua</div>
  <h1>Sistemas de<br>vigilancia de <em>última</em><br>generación.</h1>
  <div class="hero-stripe"></div>
  <p>Cámaras HDCVI, IP y grabadores DVR. Protegé tu hogar o negocio con tecnología Dahua de alta resolución.<?= !$loggedIn ? ' Registrate para ver precios.' : '' ?></p>
  <div class="hero-btns">
    <button class="btn-accent" onclick="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})">Ver catálogo</button>
    <button class="btn-ghost" onclick="document.getElementById('contacto').scrollIntoView({behavior:'smooth'})">Contacto</button>
    <?php if (!$loggedIn): ?>
    <button class="btn-ghost" onclick="openModal('registerModal')">Crear cuenta gratis</button>
    <?php endif; ?>
  </div>
</section>

<!-- PRICE GATE -->
<?php if (!$loggedIn): ?>
<div class="price-gate-wrap">
  <div class="price-gate">
    <p><strong>¿Querés ver los precios?</strong><br>Registrate o iniciá sesión para desbloquear precios y generar cotizaciones PDF.</p>
    <div class="price-gate-actions">
      <button class="btn-ghost" onclick="openModal('loginModal')">Ingresar</button>
      <button class="btn-accent" onclick="openModal('registerModal')">Registrarse</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- SEARCH & FILTERS -->
<div class="search-bar-wrap">
  <div class="search-bar">
    <div class="search-input-wrap">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" class="search-input" id="searchInput" placeholder="Buscar por modelo o descripción..." oninput="filterProducts()" autocomplete="off">
    </div>
    <div class="filter-chips" id="filterChips">
      <button class="chip active" data-cat="Todos" onclick="setFilter(this)">Todos</button>
      <button class="chip" data-cat="Domo"    onclick="setFilter(this)">Domo</button>
      <button class="chip" data-cat="Bala"    onclick="setFilter(this)">Bala</button>
      <button class="chip" data-cat="PTZ"     onclick="setFilter(this)">PTZ</button>
      <button class="chip" data-cat="DVR"     onclick="setFilter(this)">DVR</button>
      <button class="chip" data-cat="Fisheye" onclick="setFilter(this)">Fisheye</button>
    </div>
  </div>
</div>
<div class="search-results-info" id="searchInfo"></div>

<!-- CATALOG -->
<div class="section-header" id="catalogo">
  <h2>Catálogo</h2>
  <span id="productCountLabel"><?= count($products_php) ?> productos</span>
</div>
<div class="products-wrap">
  <div class="product-grid" id="productGrid">
  <?php
  $badgeMap = ['Destacado'=>'badge-hot','Popular'=>'badge-hot','Nuevo'=>'badge-new','Oferta'=>'badge-sale','Premium'=>'badge-premium'];
  // Detectar categoría por nombre/descripción
  function detectarCategoria($nombre, $desc) {
    $txt = strtolower($nombre . ' ' . $desc);
    if (strpos($txt,'fisheye')!==false || strpos($txt,'ebw')!==false) return 'Fisheye';
    if (strpos($txt,'xvr')!==false || strpos($txt,'dvr')!==false || strpos($txt,'grabador')!==false) return 'DVR';
    if (strpos($txt,'ptz')!==false || strpos($txt,'pt1200')!==false || strpos($txt,'sd49')!==false || strpos($txt,'pan')!==false) return 'PTZ';
    if (strpos($txt,'hfw')!==false || strpos($txt,'bala')!==false || strpos($txt,'bullet')!==false) return 'Bala';
    return 'Domo';
  }
  foreach ($products_php as $p):
    $bc  = $badgeMap[$p['tag']] ?? 'badge-new';
    $cat = detectarCategoria($p['nombre'], $p['descripcion']);
  ?>
  <article class="product-card"
    onclick="openDetail(<?= (int)$p['id'] ?>)"
    data-name="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
    data-desc="<?= htmlspecialchars(strtolower($p['descripcion'])) ?>"
    data-cat="<?= $cat ?>">
    <div class="product-img-wrap">
      <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy">
      <span class="product-badge <?= $bc ?>"><?= htmlspecialchars($p['tag']) ?></span>
    </div>
    <div class="product-body">
      <div class="product-name"><?= htmlspecialchars($p['nombre']) ?></div>
      <div class="product-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
      <div class="product-footer">
        <div>
          <?php if ($loggedIn): ?>
            <div class="product-price">$<?= number_format((float)$p['precio'], 2) ?></div>
          <?php else: ?>
            <div class="product-price locked">$000.00</div>
            <div class="price-lock-msg show">🔒 Solo registrados</div>
          <?php endif; ?>
        </div>
        <div class="product-actions" onclick="event.stopPropagation()">
          <button class="btn-detail" onclick="openDetail(<?= (int)$p['id'] ?>)">Detalles</button>
          <?php if ($loggedIn): ?>
            <button class="btn-cart" onclick="addToCart(<?= (int)$p['id'] ?>,event)">+ Cotizar</button>
          <?php else: ?>
            <button class="btn-accent" style="padding:.38rem .7rem;font-size:.6rem;border-radius:6px;" onclick="openModal('registerModal')">Ver precio</button>
          <?php endif; ?>
        <button class="btn-compare" id="cmp-<?= (int)$p['id'] ?>" onclick="toggleCompare(<?= (int)$p['id'] ?>,event)" title="Comparar">⇄</button>
          <button class="btn-wa-card" onclick="consultarWa(<?= (int)$p['id'] ?>,event)" title="Consultar por WhatsApp">
            <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:#fff;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </button>
        </div>
      </div>
    </div>
  </article>
  <?php endforeach; ?>
  </div>
</div>

<!-- ══ ¿POR QUÉ ELEGIRNOS? ══ -->
<section style="padding:5rem 2rem;background:var(--bg);position:relative;overflow:hidden;" id="porque">
  <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(0,170,255,.05) 0%,transparent 70%);pointer-events:none;"></div>
  <div style="max-width:1100px;margin:0 auto;">

    <!-- Header -->
    <div style="text-align:center;margin-bottom:3rem;">
      <div style="display:inline-flex;align-items:center;gap:.5rem;padding:.28rem .75rem;border:1px solid rgba(0,170,255,.25);border-radius:99px;font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;color:var(--accent);margin-bottom:1.25rem;">¿Por qué Jentechnology?</div>
      <h2 style="font-family:var(--font-head);font-size:clamp(1.8rem,4vw,3rem);font-weight:800;letter-spacing:-.03em;line-height:1.1;margin-bottom:1rem;">Respaldo, calidad y <span style="color:var(--accent);">servicio real.</span></h2>
      <p style="font-size:1.8rem;line-height:1.9;color:var(--muted);max-width:520px;margin:0 auto;">Somos distribuidores autorizados con años de experiencia en sistemas de seguridad. No solo vendemos — instalamos, configuramos y te acompañamos.</p>
    </div>

    <!-- Grid 4 tarjetas -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.15rem;margin-bottom:2.5rem;">

      <div style="background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:transform .25s,border-color .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(0,170,255,.3)'" onmouseout="this.style.transform='';this.style.borderColor='var(--border)'">
        <div style="width:52px;height:52px;background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.9rem;margin-bottom:1rem;">🛡️</div>
        <h3 style="font-family:var(--font-head);font-size:1.25rem;font-weight:700;color:var(--text);margin-bottom:.4rem;">Garantía oficial</h3>
        <p style="font-size:0.98rem;line-height:1.8;color:var(--muted);">Todos nuestros productos cuentan con garantía oficial . Si algo falla, lo resolvemos sin costo adicional dentro del período de garantía.</p>
      </div>

      <div style="background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:transform .25s,border-color .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(0,170,255,.3)'" onmouseout="this.style.transform='';this.style.borderColor='var(--border)'">
        <div style="width:52px;height:52px;background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:1rem;">🔧</div>
        <h3 style="font-family:var(--font-head);font-size:1.25rem;font-weight:700;color:var(--text);margin-bottom:.4rem;">Instalación profesional</h3>
        <p style="font-size:0.98rem;line-height:1.8;color:var(--muted);">Técnicos certificados para la instalación y configuración de cámaras, DVR y sistemas completos en hogares y negocios.</p>
      </div>

      <div style="background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:transform .25s,border-color .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(0,170,255,.3)'" onmouseout="this.style.transform='';this.style.borderColor='var(--border)'">
        <div style="width:52px;height:52px;background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:1rem;">🎧</div>
        <h3 style="font-family:var(--font-head);font-size:1.25rem;font-weight:700;color:var(--text);margin-bottom:.4rem;">Soporte técnico</h3>
        <p style="font-size:.98rem;line-height:1.8;color:var(--muted);">Asistencia antes y después de tu compra. Configuración remota, actualizaciones y solución de problemas cuando lo necesités.</p>
      </div>

      <div style="background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:transform .25s,border-color .25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(0,170,255,.3)'" onmouseout="this.style.transform='';this.style.borderColor='var(--border)'">
        <div style="width:52px;height:52px;background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:1rem;">✅</div>
        <h3 style="font-family:var(--font-head);font-size:1.25rem;font-weight:700;color:var(--text);margin-bottom:.4rem;">Productos 100% originales</h3>
        <p style="font-size:.98rem;line-height:1.8;color:var(--muted);">Distribuidor autorizado. Solo equipos originales con número de serie verificable y respaldo de fábrica garantizado.</p>
      </div>

    </div>

    <!-- CTA -->
    <div style="text-align:center;padding:2rem;border:1px solid var(--border);border-radius:16px;background:rgba(0,170,255,.03);">
      <p style="font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:1.25rem;">¿Listo para proteger lo que más importa?</p>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;">
        <button class="btn-accent" onclick="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})">Ver catálogo</button>
        <a class="btn-ghost" href="https://wa.me/+19053922189?text=Hola%20Jentechnology%2C%20quiero%20más%20información" target="_blank">Contactar por WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ CONTACTO ══ -->
<section style="padding:5rem 2rem 4rem;background:linear-gradient(180deg,var(--bg) 0%,rgba(0,100,200,.03) 100%);" id="contacto">
  <div style="max-width:1100px;margin:0 auto;">

    <!-- Header -->
    <div style="text-align:center;margin-bottom:3rem;">
      <div style="display:inline-flex;align-items:center;gap:.5rem;padding:.28rem .75rem;border:1px solid rgba(0,170,255,.25);border-radius:99px;font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;color:var(--accent);margin-bottom:1.25rem;">Contacto</div>
      <h2 style="font-family:var(--font-head);font-size:clamp(1.8rem,4vw,3rem);font-weight:800;letter-spacing:-.03em;line-height:1.1;margin-bottom:1rem;">¿Tenés alguna <span style="color:var(--accent);">consulta?</span></h2>
      <p style="font-size:.78rem;line-height:1.9;color:var(--muted);max-width:520px;margin:0 auto;">Completá el formulario y te respondemos a la brevedad. También podés escribirnos directo por WhatsApp.</p>
    </div>

    <!-- Grid: form + info -->
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">

      <!-- Formulario -->
      <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:2rem;">

        <?php
        if (isset($_GET['contact'])) {if ($_GET['contact'] === 'ok') {echo '<div style="padding:.65rem 1rem;border-radius:8px;font-size:.72rem;margin-bottom:1.25rem;line-height:1.5;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#22c55e;">✓ Mensaje enviado. Te respondemos pronto.</div>';
          } elseif ($_GET['contact'] === 'error' || $_GET['contact'] === 'csrf') {
            echo '<div style="padding:.65rem 1rem;border-radius:8px;font-size:.72rem;margin-bottom:1.25rem;line-height:1.5;background:rgba(255,61,113,.1);border:1px solid rgba(255,61,113,.25);color:#ff3d71;">✗ No se pudo enviar. Intentá por WhatsApp.</div>';
          }
        }
        ?>

        <form action="procesar_contacto.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

          <!-- Nombre y correo en fila -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.85rem;">
            <div>
              <label style="display:block;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Nombre completo *</label>
              <input type="text" name="nombre" required placeholder="Tu nombre"style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem;font-family:var(--font-mono);font-size:.75rem;color:var(--text);outline:none;"onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            <div>
              <label style="display:block;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Correo electrónico *</label>
              <input type="email" name="correo" required placeholder="correo@ejemplo.com"
                style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem;font-family:var(--font-mono);font-size:.75rem;color:var(--text);outline:none;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
            </div>
          </div>

          <!-- Teléfono -->
          <div style="margin-bottom:.85rem;">
            <label style="display:block;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Teléfono</label>
            <input type="tel" name="telefono" placeholder="+1 000 000 0000" style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem;font-family:var(--font-mono);font-size:.75rem;color:var(--text);outline:none;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
          </div>

          <!-- Mensaje -->
          <div style="margin-bottom:1.25rem;">
            <label style="display:block;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Mensaje *</label>
            <textarea name="mensaje" required rows="5" placeholder="¿En qué podemos ayudarte?"
              style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .85rem;font-family:var(--font-mono);font-size:.75rem;color:var(--text);outline:none;transition:border-color .2s;resize:vertical;"
              onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"></textarea>
          </div>

          <!-- Botones -->
          <div style="display:flex;gap:.65rem;flex-wrap:wrap;">
            <button type="submit" class="btn-accent" style="padding:.65rem 1.5rem;font-size:.72rem;">✉️ Enviar mensaje</button>
            <a href="https://wa.me/+19053922189?text=Hola%20Jentechnology%2C%20quiero%20hacer%20una%20consulta"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.5rem;border:1px solid rgba(37,211,102,.35);border-radius:6px;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#25d366;text-decoration:none;" onmouseover="this.style.background='rgba(37,211,102,.08)'" onmouseout="this.style.background=''">💬 WhatsApp</a>
            </a>
          </div>
        </form>
      </div>
<!-- Info lateral -->
      <div style="display:flex;flex-direction:column;gap:.75rem;">

        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.15rem;display:flex;align-items:flex-start;gap:.85rem;">
          <div style="width:42px;height:42px;background:rgba(0,170,255,.08);border:1px solid rgba(0,170,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">📧</div>
          <div>
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.25rem;">Correo</div>
            <a href="mailto:contacto@jentechnology.com" style="font-size:.73rem;color:var(--accent);font-weight:700;text-decoration:none;">contacto@jentechnology.com</a>
          </div>
        </div>

        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.15rem;display:flex;align-items:flex-start;gap:.85rem;">
          <div style="width:42px;height:42px;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">💬</div>
          <div>
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.25rem;">WhatsApp</div>
            <a href="https://wa.me/+19053922189" target="_blank" style="font-size:.73rem;color:#25d366;font-weight:700;text-decoration:none;">+1 (905) 392-2189</a>
          </div>
        </div>

        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.15rem;display:flex;align-items:flex-start;gap:.85rem;">
          <div style="width:42px;height:42px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">🕐</div>
          <div>
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.25rem;">Horario</div><div style="font-size:.73rem;color:var(--text);font-weight:700;">Lun – Vie: 8AM – 6PM</div><div style="font-size:.63rem;color:var(--muted);margin-top:.15rem;">Sábados: 9AM – 2PM</div></div>
        </div>

        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.15rem;display:flex;align-items:flex-start;gap:.85rem;">
          <div style="width:42px;height:42px;background:rgba(160,100,255,.08);border:1px solid rgba(160,100,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">🌐</div>
          <div style="flex:1;">
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;">Redes sociales</div>
            <div style="display:flex;gap:.4rem;">
              <a href="https://facebook.com/jentechnology" target="_blank" class="social-link" style="width:32px;height:32px;border-radius:7px;">
                <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
              </a>
              <a href="https://instagram.com/jentechnology" target="_blank" class="social-link" style="width:32px;height:32px;border-radius:7px;">
                <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
              </a>
              <a href="https://tiktok.com/@jentechnology" target="_blank" class="social-link" style="width:32px;height:32px;border-radius:7px;">
                <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:currentColor;"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.73a4.85 4.85 0 01-1.01-.04z"/></svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Nota responsivo -->
    <style>
      @media(max-width:860px){#contacto > div > div:last-of-type { grid-template-columns: 1fr !important; }
      }
      @media(max-width:540px){
        #contacto > div > div:last-of-type > div:first-child > form > div:first-of-type { grid-template-columns: 1fr !important; }
      }
    </style>
</div>
</section>

<footer>
  <img src="imagen/logo.png" alt="Jentechnology" class="footer-logo">
  <div class="footer-social">
    <a class="social-link" href="https://facebook.com/jentechnology" target="_blank" title="Facebook">
      <svg viewBox="0 0 24 24"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
    </a>
    <a class="social-link" href="https://instagram.com/jentechnology" target="_blank" title="Instagram">
      <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
    </a>
    <a class="social-link" href="https://tiktok.com/@jentechnology" target="_blank" title="TikTok">
      <svg viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.73a4.85 4.85 0 01-1.01-.04z"/></svg>
    </a>
    <a class="social-link" href="https://wa.me/+19053922189" target="_blank" title="WhatsApp">
      <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
  </div>
  <div class="footer-copy">
    <p>© <?= date('Y') ?> <strong style="color:var(--accent)">Jentechnology</strong> — Todos los derechos reservados.</p>
    <p>Seguridad · Vigilancia · Tecnología</p>
  </div>
</footer>
</div><!-- /#site -->

<!-- BARRA FLOTANTE COMPARACIÓN -->
<div id="compareBar" style="display:none;position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);z-index:300;background:var(--surface);border:1px solid rgba(0,170,255,.3);border-radius:14px;padding:.75rem 1.25rem;align-items:center;gap:1rem;box-shadow:0 8px 32px rgba(0,0,0,.5);backdrop-filter:blur(20px);min-width:320px;max-width:90vw;">
  <div style="display:flex;gap:.6rem;flex:1;">
    <div id="compareSlot0" style="flex:1;background:var(--card);border:1px dashed var(--border);border-radius:8px;padding:.5rem .75rem;font-size:.62rem;color:var(--muted);text-align:center;min-width:100px;">Producto 1</div>
    <div id="compareSlot1" style="flex:1;background:var(--card);border:1px dashed var(--border);border-radius:8px;padding:.5rem .75rem;font-size:.62rem;color:var(--muted);text-align:center;min-width:100px;">Producto 2</div>
  </div>
  <button id="compareBtnLaunch" onclick="openModal('compareModal')" style="padding:.5rem 1rem;background:var(--accent);color:#000;border-radius:7px;font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;white-space:nowrap;opacity:.4;pointer-events:none;transition:all .2s;">Comparar</button>
  <button onclick="clearCompare()" style="padding:.5rem .6rem;border:1px solid var(--border);border-radius:7px;font-size:.75rem;color:var(--muted);transition:all .2s;" title="Limpiar">✕</button>
</div>

<!-- MODAL COMPARACIÓN -->
<div class="modal-backdrop" id="compareModal" onclick="backdropClose(event,'compareModal')">
  <div class="modal" style="max-width:700px;">
    <button class="modal-close" onclick="closeModal('compareModal')">✕</button>
    <div class="modal-header"><h3>⇄ Comparar productos</h3><p>Comparación lado a lado.</p></div>
    <div id="compareBody" style="padding:1.25rem 1.5rem;overflow-x:auto;"></div>
    <div style="padding:0 1.5rem 1.5rem;display:flex;gap:.5rem;justify-content:flex-end;">
      <button class="btn-full secondary" style="width:auto;padding:.5rem 1rem;" onclick="clearCompare();closeModal('compareModal')">Limpiar</button>
      <button class="btn-full primary" style="width:auto;padding:.5rem 1rem;" onclick="closeModal('compareModal')">Cerrar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: PDF ══ -->
<div class="modal-backdrop" id="pdfModal" onclick="backdropClose(event,'pdfModal')">
  <div class="modal" style="max-width:460px;">
    <button class="modal-close" onclick="closeModal('pdfModal')">✕</button>
    <div class="modal-header">
      <img src="imagen/logo.png" alt="Jentechnology" class="modal-logo">
      <h3>Datos de la cotización</h3>
      <p>Completá los datos del cliente para generar el PDF.</p>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" id="pdfNombre" placeholder="Ej: Juan Pérez" autocomplete="name">
      </div>
      <div class="form-group">
        <label>Empresa / Negocio</label>
        <input type="text" id="pdfEmpresa" placeholder="Ej: Comercial del Norte S.A.">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Correo electrónico *</label>
          <input type="email" id="pdfCorreo" placeholder="correo@ejemplo.com" autocomplete="email">
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="tel" id="pdfTelefono" placeholder="+1 000 000 0000">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Impuesto (%)</label>
          <input type="number" id="pdfImpuesto" value="0" min="0" max="100" step="0.1">
        </div>
        <div class="form-group">
          <label>Validez</label>
          <input type="text" id="pdfValidez" value="15 días">
        </div>
      </div>
      <!-- Resumen del carrito dentro del modal -->
      <div id="pdfCartResumen" style="margin-top:.5rem;background:var(--card);border:1px solid var(--border);border-radius:8px;padding:.75rem;font-size:.65rem;color:var(--muted);max-height:120px;overflow-y:auto;"></div>
    </div>
    <div class="modal-footer">
      <div id="pdfActionMsg" style="display:none;padding:.55rem .85rem;border-radius:6px;font-size:.68rem;margin-bottom:.4rem;line-height:1.5;"></div>
      <button class="btn-full primary" id="btnDescargarPdf" onclick="generarPDF()">📄 Descargar PDF</button>
      <button class="btn-full secondary" id="btnEnviarCorreo" onclick="enviarPorCorreo()" style="background:rgba(0,170,255,.07);border-color:rgba(0,170,255,.25);color:var(--accent);">✉️ Enviar al correo de Jentechnology</button>
      <button class="btn-full secondary" onclick="closeModal('pdfModal')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: LOGIN ══ -->
<div class="modal-backdrop" id="loginModal" onclick="backdropClose(event,'loginModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('loginModal')">✕</button>
    <div class="modal-header">
      <img src="imagen/logo.png" alt="Jentechnology" class="modal-logo">
      <h3>Iniciar sesión</h3>
      <p>Accedé a precios y cotizaciones exclusivas.</p>
    </div>
    <div class="modal-body">
      <?php if ($regSuccess): ?><div class="modal-alert success">✓ Cuenta creada. ¡Ya podés ingresar!</div><?php endif; ?>
      <?php if ($loginError): ?><div class="modal-alert error"><?= htmlspecialchars($loginError) ?></div><?php endif; ?>
      <form action="procesar_login.php" method="POST" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="return_to" value="index.php">
        <div class="form-group"><label>Correo electrónico</label><input type="email" name="email" placeholder="correo@ejemplo.com" required autocomplete="email"></div>
        <div class="form-group"><label>Contraseña</label><input type="password" name="password" placeholder="••••••••" required autocomplete="current-password"></div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn-full primary" onclick="document.getElementById('loginForm').submit()">Ingresar</button>
      <p class="modal-note">¿No tenés cuenta? <a href="#" onclick="switchModal('loginModal','registerModal')">Registrate gratis</a></p>
    </div>
  </div>
</div>

<!-- ══ MODAL: REGISTRO ══ -->
<div class="modal-backdrop" id="registerModal" onclick="backdropClose(event,'registerModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('registerModal')">✕</button>
    <div class="modal-header">
      <img src="imagen/logo.png" alt="Jentechnology" class="modal-logo">
      <h3>Crear cuenta</h3>
      <p>Gratis. Desbloqueá precios y generá cotizaciones PDF.</p>
    </div>
    <div class="modal-body">
      <?php if ($regError): ?><div class="modal-alert error"><?= htmlspecialchars($regError) ?></div><?php endif; ?>
      <form action="procesar_registro.php" method="POST" id="regForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="return_to" value="index.php">
        <div class="form-group"><label>Correo electrónico</label><input type="email" name="email" placeholder="correo@ejemplo.com" required autocomplete="email"></div>
        <div class="form-group"><label>Contraseña</label><input type="password" name="password" placeholder="Mín. 8 caracteres con letras y números" required autocomplete="new-password"></div>
        <div class="form-group"><label>Confirmar contraseña</label><input type="password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password"></div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn-full primary" onclick="document.getElementById('regForm').submit()">Crear cuenta</button>
      <p class="modal-note">¿Ya tenés cuenta? <a href="#" onclick="switchModal('registerModal','loginModal')">Iniciá sesión</a></p>
    </div>
  </div>
</div>

<!-- ══ MODAL: DETALLE ══ -->
<div class="modal-backdrop" id="detailModal" onclick="backdropClose(event,'detailModal')">
  <div class="modal detail-modal">
    <button class="modal-close" onclick="closeModal('detailModal')">✕</button>
    <div class="detail-img-wrap"><img src="" alt="" id="detailImg"></div>
    <div class="modal-body">
      <div class="detail-tag-row"><span class="detail-chip" id="detailTag"></span></div>
      <div class="detail-name" id="detailName"></div>
      <div class="detail-desc" id="detailDesc"></div>
      <div class="detail-price-row">
        <div>
          <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem;">Precio</div>
          <div class="detail-price" id="detailPrice"></div>
          <div class="price-lock-msg" id="detailLockMsg">Registrate para ver el precio</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:.4rem;align-items:flex-end;">
          <button class="btn-cart" id="detailCartBtn" style="padding:.55rem 1.1rem;font-size:.68rem;" onclick="addFromDetail()">+ Cotizar</button>
          <a class="whatsapp-btn" id="detailWa" href="#" target="_blank">
            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Consultar
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PRODUCTOS RELACIONADOS -->
      <div id="relacionadosWrap" style="margin-top:1.5rem;display:none;">
        <div style="font-size:.6rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:.75rem;padding-top:1rem;border-top:1px solid var(--border);">También te puede interesar</div>
        <div id="relacionadosGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;"></div>
      </div>

    </div>
  </div>
</div>

<!-- WhatsApp FAB -->
<a class="whatsapp-fab" href="https://wa.me/+19053922189?text=Hola%20Jentechnology%2C%20quiero%20consultar%20sobre%20un%20producto" target="_blank" title="Consultar por WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<div class="toast-wrap" id="toastWrap"></div>

<!-- CART PANEL -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-panel" id="cartPanel">
  <div class="cart-panel-header">
    <h3>🛒 Mi Cotización</h3>
    <button class="modal-close" onclick="closeCart()">✕</button>
  </div>
  <div class="cart-items" id="cartItems"><div class="cart-empty">Tu cotización está vacía</div></div>
  <div class="cart-panel-footer">
    <div class="cart-total-block">
      <div class="cart-total-row"><span>Subtotal</span><span id="cartSubtotal">$0.00</span></div>
      <div class="cart-total-row grand"><span>Total</span><span id="cartTotal">$0.00</span></div>
    </div>
    <button class="btn-full primary" onclick="openPdfModal()">📄 Descargar cotización PDF</button>
    <button class="btn-full secondary" style="margin-top:.4rem;background:rgba(37,211,102,.08);border-color:rgba(37,211,102,.3);color:#25d366;" onclick="checkout()">💬 Enviar por WhatsApp</button>
    <button class="btn-full secondary" style="margin-top:.4rem;" onclick="closeCart()">Seguir viendo</button>
  </div>
</div>

<script>
/* ── DATOS ── */
const products  = <?= json_encode(array_map(fn($p)=>['id'=>(int)$p['id'],'name'=>$p['nombre'],'desc'=>$p['descripcion'],'price'=>(float)$p['precio'],'tag'=>$p['tag'],'img'=>$p['imagen']], $products_php), JSON_HEX_TAG|JSON_HEX_APOS) ?>;
const loggedIn  = <?= $loggedIn ? 'true' : 'false' ?>;
const phpNombre = <?= json_encode($userName) ?>;
const phpCorreo = <?= json_encode($userEmail) ?>;

// Datos empresa para PDF (sincronizados con PHP)
const EMP = {
  nombre:  <?= json_encode($empresa_nombre) ?>,
  email:   <?= json_encode($empresa_email) ?>,
  web:     <?= json_encode($empresa_web) ?>,
  wa:      <?= json_encode($empresa_wa) ?>,
  slogan:  <?= json_encode($empresa_slogan) ?>
};
let cart = [], currentProduct = null, compareList = [];

/* ── INTRO ── */
(function(){
  const bar=document.getElementById('introBar'),msg=document.getElementById('introMsg'),tc=document.getElementById('timecode');
  const msgs=['INICIALIZANDO SISTEMA...','CARGANDO CATÁLOGO...','VERIFICANDO INVENTARIO...','CONECTANDO SERVIDORES...','SISTEMA LISTO'];
  let start=null; const dur=4200; const t0=Date.now();
  const iv=setInterval(()=>{
    const e=Date.now()-t0;
    tc.textContent=[e/3600000,e%3600000/60000,e%60000/1000,e%1000/33].map(v=>String(Math.floor(v)).padStart(2,'0')).join(':');
  },33);
  function step(ts){
    if(!start)start=ts;
    const p=Math.min((ts-start)/dur*100,100);
    bar.style.width=p+'%';
    msg.textContent=msgs[Math.min(Math.floor(p/20),msgs.length-1)];
    if(p<100){requestAnimationFrame(step);}
    else{
      clearInterval(iv);
      setTimeout(()=>{
        const el=document.getElementById('intro');
        el.style.transition='opacity .75s ease,transform .75s ease';
        el.style.opacity='0'; el.style.transform='scale(1.03)';
        document.getElementById('site').classList.add('visible');
        setTimeout(()=>el.remove(),800);
        const m=<?= json_encode($openModal) ?>;
        if(m) setTimeout(()=>openModal(m),950);
      },400);
    }
  }
  requestAnimationFrame(step);
})();

/* ── MODALS ── */
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
function backdropClose(e,id){if(e.target===document.getElementById(id))closeModal(id);}
function switchModal(a,b){closeModal(a);setTimeout(()=>openModal(b),150);}
function openModal(id){
  if(id==='compareModal'&&compareList.length===2) buildCompareModal();
  document.getElementById(id).classList.add('open');
  document.body.style.overflow='hidden';
}
/* ── CART ── */
function addToCart(id,e){
  if(e) e.stopPropagation();
  if(!loggedIn){openModal('registerModal');return;}
  const p=products.find(x=>x.id===id); if(!p)return;
  const ex=cart.find(x=>x.id===id);
  if(ex) ex.qty++; else cart.push({...p,qty:1});
  renderCart(); updateCartCount(); toast(p.name.slice(0,30)+'… agregado ✓','success');
}
function addFromDetail(){if(currentProduct) addToCart(currentProduct.id);}
function removeFromCart(id){cart=cart.filter(x=>x.id!==id);renderCart();updateCartCount();}
function changeQty(id,delta){
  const item=cart.find(x=>x.id===id); if(!item) return;
  item.qty+=delta;
  if(item.qty<=0) removeFromCart(id); else{renderCart();updateCartCount();}
}
function renderCart(){
  const el=document.getElementById('cartItems');
  const subtotal=cart.reduce((s,x)=>s+x.price*x.qty,0);
  document.getElementById('cartSubtotal').textContent='$'+subtotal.toFixed(2);
  document.getElementById('cartTotal').textContent='$'+subtotal.toFixed(2);
  if(!cart.length){el.innerHTML='<div class="cart-empty">Tu cotización está vacía</div>';return;}
  el.innerHTML=cart.map(x=>`
<div class="cart-item">
<img src="${x.img}" alt="${x.name}">
<div class="cart-item-info">
<div class="cart-item-name">${x.name}</div>
<div class="cart-item-qty">
<button onclick="changeQty(${x.id},-1)">−</button>
<span>${x.qty}</span>
<button onclick="changeQty(${x.id},1)">+</button>
</div>
<div class="cart-item-price">$${x.price.toFixed(2)} × ${x.qty} = $${(x.price*x.qty).toFixed(2)}</div>
</div>
<button class="cart-item-remove" onclick="removeFromCart(${x.id})">✕</button>
</div>`).join('');
}

function updateCartCount(){
  const el=document.getElementById('cartCount'); if(!el)return;
  const t=cart.reduce((s,x)=>s+x.qty,0);
  el.textContent=t; el.classList.toggle('has-items',t>0);
}
function openCart(){
  document.getElementById('cartPanel').classList.add('open');
  document.getElementById('cartOverlay').classList.add('open');
  document.body.style.overflow='hidden'; renderCart();
}
function closeCart(){
  document.getElementById('cartPanel').classList.remove('open');
  document.getElementById('cartOverlay').classList.remove('open');
  document.body.style.overflow='';
}
function checkout(){
  if(!cart.length){toast('Tu cotización está vacía','error');return;}
  const items=cart.map(x=>`• ${x.name} x${x.qty}`).join('%0A');
  const total=cart.reduce((s,x)=>s+x.price*x.qty,0);
  window.open(`https://wa.me/${EMP.wa}?text=Hola%20${EMP.nombre}%2C%20quiero%20cotizar:%0A${items}%0ATotal%3A%20%24${total.toFixed(2)}`,'_blank');
}
/* ── WHATSAPP POR CATEGORÍA ── */
const WA_MSGS = {
  'Domo':    'Hola Jentechnology, me interesa una cámara tipo *Domo*. Modelo: ',
  'Bala':    'Hola Jentechnology, me interesa una cámara tipo *Bala*. Modelo: ',
  'PTZ':     'Hola Jentechnology, me interesa una cámara *PTZ* (Pan/Tilt). Modelo: ',
  'DVR':     'Hola Jentechnology, me interesa un *Grabador DVR*. Modelo: ',
  'Fisheye': 'Hola Jentechnology, me interesa una cámara *Fisheye 360°*. Modelo: ',
  'default': 'Hola Jentechnology, quiero consultar sobre el producto: ',
};

function getWaMsg(product){
  const txt=(product.name+' '+product.desc).toLowerCase();
  let cat='default';
  if(txt.includes('fisheye')||txt.includes('ebw')) cat='Fisheye';
  else if(txt.includes('xvr')||txt.includes('dvr')||txt.includes('grabador')) cat='DVR';
  else if(txt.includes('ptz')||txt.includes('pt1200')||txt.includes('sd49')) cat='PTZ';
  else if(txt.includes('hfw')||txt.includes('bala')||txt.includes('bullet')) cat='Bala';
  else if(txt.includes('domo')||txt.includes('hdw')||txt.includes('hac-t')) cat='Domo';
  
  const base=WA_MSGS[cat]||WA_MSGS['default'];
  return `https://wa.me/${EMP.wa}?text=${encodeURIComponent(base+product.name+'. ¿Podrían darme más información y precio?')}`;
}
function consultarWa(id,e){if(e)e.stopPropagation();const p=products.find(x=>x.id===id);if(!p)return;window.open(getWaMsg(p),'_blank');}

/* ── DETAIL ── */
function getCatJS(p){
  const t=(p.name+' '+p.desc).toLowerCase();
  if(t.includes('fisheye')||t.includes('ebw')) return 'Fisheye';
  if(t.includes('xvr')||t.includes('dvr')||t.includes('grabador')) return 'DVR';
  if(t.includes('ptz')||t.includes('pt1200')||t.includes('sd49')) return 'PTZ';
  if(t.includes('hfw')||t.includes('bala')||t.includes('bullet')) return 'Bala';
  return 'Domo';
}
function openDetail(id){
  const p=products.find(x=>x.id===id); if(!p)return;
  currentProduct=p;
  document.getElementById('detailImg').src=p.img;
  document.getElementById('detailImg').alt=p.name;
  document.getElementById('detailTag').textContent=p.tag;
  document.getElementById('detailName').textContent=p.name;
  document.getElementById('detailDesc').textContent=p.desc;
  const pr=document.getElementById('detailPrice'),lk=document.getElementById('detailLockMsg'),btn=document.getElementById('detailCartBtn'),wa=document.getElementById('detailWa');
  wa.href=getWaMsg(p);
  if(loggedIn){pr.textContent='$'+p.price.toFixed(2);pr.classList.remove('locked');lk.classList.remove('show');btn.style.display='';}
  else{pr.textContent='$000.00';pr.classList.add('locked');lk.classList.add('show');btn.style.display='none';}

  // Productos relacionados — misma categoría, excluir el actual, máx 3
  const cat = getCatJS(p);
  const relacionados = products.filter(x => x.id !== p.id && getCatJS(x) === cat).slice(0, 3);
  const wrap = document.getElementById('relacionadosWrap');
  const grid = document.getElementById('relacionadosGrid');

  if (relacionados.length > 0) {
    wrap.style.display = 'block';
    grid.innerHTML = relacionados.map(r => `
      <div onclick="openDetail(${r.id})" style="cursor:pointer;background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:border-color .2s,transform .2s;" onmouseover="this.style.borderColor='rgba(0,170,255,.3)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--border)';this.style.transform=''">
        <div style="height:80px;background:#060c14;display:flex;align-items:center;justify-content:center;overflow:hidden;">
          <img src="${r.img}" alt="${r.name}" style="width:100%;height:100%;object-fit:contain;padding:.4rem;">
        </div>
        <div style="padding:.5rem .6rem;">
          <div style="font-size:.6rem;font-weight:700;color:var(--text);line-height:1.3;margin-bottom:.2rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">${r.name}</div>
          <div style="font-size:.6rem;color:${loggedIn?'var(--accent)':'var(--muted)'};">${loggedIn?'$'+r.price.toFixed(2):'Ver precio'}</div>
        </div>
      </div>`).join('');
  } else {
    wrap.style.display = 'none';
  }

  openModal('detailModal');
}

/* ── TOAST ── */
function toast(msg,type='success'){
  const w=document.getElementById('toastWrap'),el=document.createElement('div');
  el.className='toast '+(type==='error'?'error':'success'); el.textContent=msg;
  w.appendChild(el); setTimeout(()=>el.remove(),2700);
}

/* ── COMPARACIÓN ── */
function toggleCompare(id,e){
  if(e) e.stopPropagation();
  const idx=compareList.indexOf(id), btn=document.getElementById('cmp-'+id);
  if(idx!==-1){compareList.splice(idx,1);if(btn)btn.classList.remove('selected');}
  else{if(compareList.length>=2){toast('Ya tenés 2 productos seleccionados. Quitá uno primero.','error');return;}compareList.push(id);if(btn)btn.classList.add('selected');}
  updateCompareBar();
}
function updateCompareBar(){
  const bar=document.getElementById('compareBar'),s0=document.getElementById('compareSlot0'),s1=document.getElementById('compareSlot1'),launch=document.getElementById('compareBtnLaunch');
  if(!compareList.length){bar.style.display='none';return;}
  bar.style.display='flex';
  const p0=compareList[0]?products.find(x=>x.id===compareList[0]):null;
  const p1=compareList[1]?products.find(x=>x.id===compareList[1]):null;
  s0.style.border='1px solid '+(p0?'rgba(0,170,255,.3)':'var(--border)');s0.style.color=p0?'var(--text)':'var(--muted)';s0.textContent=p0?p0.name.slice(0,22)+(p0.name.length>22?'…':''):'Producto 1';
  s1.style.border='1px solid '+(p1?'rgba(0,170,255,.3)':'var(--border)');s1.style.color=p1?'var(--text)':'var(--muted)';s1.textContent=p1?p1.name.slice(0,22)+(p1.name.length>22?'…':''):'Producto 2';
  launch.style.opacity=compareList.length===2?'1':'.4';launch.style.pointerEvents=compareList.length===2?'auto':'none';
}
function clearCompare(){compareList.forEach(id=>{const b=document.getElementById('cmp-'+id);if(b)b.classList.remove('selected');});compareList=[];updateCompareBar();}
function buildCompareModal(){
  const p0=products.find(x=>x.id===compareList[0]),p1=products.find(x=>x.id===compareList[1]);if(!p0||!p1)return;
  function getCat(p){const t=(p.name+' '+p.desc).toLowerCase();if(t.includes('fisheye')||t.includes('ebw'))return'Fisheye';if(t.includes('xvr')||t.includes('dvr'))return'DVR';if(t.includes('ptz')||t.includes('pt1200')||t.includes('sd49'))return'PTZ';if(t.includes('hfw')||t.includes('bala'))return'Bala';return'Domo';}
  const rows=[
    {label:'Imagen',v0:`<img src="${p0.img}" style="width:100%;height:120px;object-fit:contain;border-radius:8px;background:#060c14;padding:.5rem;">`,v1:`<img src="${p1.img}" style="width:100%;height:120px;object-fit:contain;border-radius:8px;background:#060c14;padding:.5rem;">`},
    {label:'Modelo',v0:`<strong>${p0.name}</strong>`,v1:`<strong>${p1.name}</strong>`},
    {label:'Categoría',v0:getCat(p0),v1:getCat(p1)},
    {label:'Tag',v0:p0.tag,v1:p1.tag},
    {label:'Precio',v0:loggedIn?`<span style="color:var(--accent);font-weight:700;font-size:1.1rem;">$${p0.price.toFixed(2)}</span>`:'<span style="filter:blur(5px);color:var(--muted);">$000.00</span>',v1:loggedIn?`<span style="color:var(--accent);font-weight:700;font-size:1.1rem;">$${p1.price.toFixed(2)}</span>`:'<span style="filter:blur(5px);color:var(--muted);">$000.00</span>'},
    {label:'Descripción',v0:p0.desc,v1:p1.desc},
  ];
  const cs='width:50%;padding:.85rem 1rem;vertical-align:top;font-size:.72rem;border-bottom:1px solid var(--border);',ls='padding:.6rem 1rem;font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);background:rgba(0,170,255,.04);border-bottom:1px solid var(--border);border-right:1px solid var(--border);white-space:nowrap;vertical-align:middle;';
  let html=`<table style="width:100%;border-collapse:collapse;min-width:480px;"><thead><tr><th style="${ls}min-width:90px;"></th><th style="padding:.85rem 1rem;font-size:.75rem;font-weight:700;color:var(--text);border-bottom:1px solid var(--border);text-align:left;width:50%;">Producto 1</th><th style="padding:.85rem 1rem;font-size:.75rem;font-weight:700;color:var(--text);border-bottom:1px solid var(--border);text-align:left;width:50%;">Producto 2</th></tr></thead><tbody>`;
  rows.forEach(r=>{html+=`<tr><td style="${ls}">${r.label}</td><td style="${cs}color:var(--text);">${r.v0}</td><td style="${cs}color:var(--text);">${r.v1}</td></tr>`;});
  html+=`<tr><td style="${ls}">Acción</td><td style="${cs}"><div style="display:flex;flex-direction:column;gap:.4rem;"><button onclick="addToCart(${p0.id});closeModal('compareModal')" class="btn-cart" style="font-size:.62rem;padding:.4rem .75rem;">+ Cotizar</button><button onclick="window.open(getWaMsg(products.find(x=>x.id===${p0.id})),'_blank')" style="padding:.4rem .75rem;border:1px solid rgba(37,211,102,.3);border-radius:6px;font-size:.6rem;color:#25d366;background:none;cursor:pointer;">💬 WhatsApp</button></div></td><td style="${cs}"><div style="display:flex;flex-direction:column;gap:.4rem;"><button onclick="addToCart(${p1.id});closeModal('compareModal')" class="btn-cart" style="font-size:.62rem;padding:.4rem .75rem;">+ Cotizar</button><button onclick="window.open(getWaMsg(products.find(x=>x.id===${p1.id})),'_blank')" style="padding:.4rem .75rem;border:1px solid rgba(37,211,102,.3);border-radius:6px;font-size:.6rem;color:#25d366;background:none;cursor:pointer;">💬 WhatsApp</button></div></td></tr>`;
  html+='</tbody></table>';
  document.getElementById('compareBody').innerHTML=html;
}

/* ══════════════════════════════════════════════
   BUSCADOR + FILTROS
══════════════════════════════════════════════ */
let activeFilter = 'Todos';

function setFilter(btn) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  activeFilter = btn.dataset.cat;
  filterProducts();
}
function filterProducts() {
  const query = document.getElementById('searchInput').value.trim().toLowerCase();
  const cards = document.querySelectorAll('#productGrid .product-card');
  let visible = 0;

  cards.forEach(card=>{
    const matchCat=activeFilter==='Todos'||card.dataset.cat===activeFilter;
    const matchSearch=!query||card.dataset.name.includes(query)||card.dataset.desc.includes(query);
    if(matchCat&&matchSearch){card.style.display='';visible++;}else{card.style.display='none';}
  });
/* Info de resultados */
  const info=document.getElementById('searchInfo'),label=document.getElementById('productCountLabel'),total=cards.length;
  if(query||activeFilter!=='Todos')
    {info.textContent=visible===0?'Sin resultados — probá con otro término o categoría':visible+' de '+total+' productos';label.textContent=visible+' productos';
  } 
  else {
    info.textContent  = '';
    label.textContent = total + ' productos';}
/* Tarjeta "sin resultados" */
  let noRes = document.getElementById('noResultsCard');
  if (visible === 0) {
    if (!noRes) {
      noRes = document.createElement('div');
      noRes.id = 'noResultsCard';
      noRes.className = 'no-results';
      noRes.innerHTML = '<p>No se encontraron productos</p><span>Intentá con otro término o categoría</span>';document.getElementById('productGrid').appendChild(noRes);
    }
    noRes.style.display = '';
  } else if (noRes) {
    noRes.style.display = 'none';}
}
/* ══════════════════════════════════════════════
   PDF
══════════════════════════════════════════════ */
function openPdfModal(){
  if(!cart.length){toast('Tu cotización está vacía','error');return;}
  // Prellenar datos del usuario
  const n=document.getElementById('pdfNombre'), c=document.getElementById('pdfCorreo');
  if(!n.value) n.value=phpNombre;
  if(!c.value) c.value=phpCorreo;
  // Resumen del carrito en el modal
  const res=document.getElementById('pdfCartResumen');
  res.innerHTML='<strong style="color:var(--text)">Productos:</strong><br>'+
    cart.map(x=>`${x.name} ×${x.qty} — $${(x.price*x.qty).toFixed(2)}`).join('<br>');
  closeCart();
  setTimeout(()=>openModal('pdfModal'),220);
}
function generarPDF(){
  const nombre=document.getElementById('pdfNombre').value.trim(),empresa=document.getElementById('pdfEmpresa').value.trim(),correo=document.getElementById('pdfCorreo').value.trim(),telefono=document.getElementById('pdfTelefono').value.trim(),impPct=parseFloat(document.getElementById('pdfImpuesto').value)||0,validez=document.getElementById('pdfValidez').value.trim()||'15 días';

  if(!nombre){toast('El nombre es obligatorio','error');return;}
  if(!correo) {toast('El correo es obligatorio','error');return;}

  /* jsPDF ya cargado en <head> — acceder vía window.jspdf */
  try {
    _buildPDF(nombre,empresa,correo,telefono,impPct,validez);
  } catch(err) {
    toast('Error al generar PDF. Intenta de nuevo.','error');
    console.error(err);
  }
}

function _buildPDF(nombre,empresa,correo,telefono,impPct,validez){
  const {jsPDF}=window.jspdf,doc=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
  const W=210,M=18,AZUL=[0,100,200],OSCURO=[10,20,40],GRIS=[245,247,250],GRISC=[170,180,200],BLANCO=[255,255,255];

  /* ── Header ── */
  doc.setFillColor(...OSCURO); doc.rect(0,0,W,44,'F');
  doc.setFillColor(...AZUL);   doc.rect(0,0,6,44,'F');

  doc.setTextColor(...BLANCO); doc.setFont('helvetica','bold'); doc.setFontSize(20);
  doc.text(EMP.nombre.toUpperCase(), M+6, 16);
  doc.setFont('helvetica','normal'); doc.setFontSize(7.5); doc.setTextColor(...GRISC);
  doc.text(EMP.slogan,          M+6, 22);
  doc.text(EMP.email+' | wa.me/'+EMP.wa, M+6, 28);
  doc.text(EMP.web,             M+6, 34);

  const folio='COT-'+Date.now().toString().slice(-6),fecha=new Date().toLocaleDateString('es-MX',{year:'numeric',month:'long',day:'numeric'});
  doc.setFont('helvetica','bold'); doc.setFontSize(13); doc.setTextColor(...AZUL);
  doc.text('COTIZACIÓN', W-M, 16, {align:'right'});
  doc.setFont('helvetica','normal'); doc.setFontSize(7.5); doc.setTextColor(...GRISC);
  doc.text('N°: '+folio,        W-M, 23, {align:'right'});
  doc.text('Fecha: '+fecha,     W-M, 29, {align:'right'});
  doc.text('Válida: '+validez,  W-M, 35, {align:'right'});

  let y=54;

  /* ── Cliente ── */
  const clientH = (empresa||telefono) ? 30 : 22;
  doc.setFillColor(...GRIS); doc.roundedRect(M,y,W-M*2,clientH,2,2,'F');
  doc.setFont('helvetica','bold'); doc.setFontSize(7.5); doc.setTextColor(...AZUL);
  doc.text('CLIENTE', M+4, y+7);
  doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(...OSCURO);
  doc.text(nombre, M+4, y+15);
  if(empresa){doc.setFont('helvetica','normal');doc.setFontSize(7.5);doc.setTextColor(80,90,110);doc.text(empresa,M+4,y+21);}
  const rc=W/2+4;
  doc.setFont('helvetica','normal'); doc.setFontSize(7.5); doc.setTextColor(80,90,110);
  doc.text('Email: '+correo, rc, y+15);
  if(telefono) doc.text('Tel: '+telefono, rc, y+21);
  y+=clientH+8;

  /* ── Tabla header ── */
  doc.setFillColor(...AZUL); doc.rect(M,y,W-M*2,9,'F');
  doc.setFont('helvetica','bold'); doc.setFontSize(8); doc.setTextColor(...BLANCO);
  const cx={n:M+3, prod:M+13, qty:W-M-46, pu:W-M-28, tot:W-M-4};
  doc.text('#',       cx.n,   y+6);
  doc.text('Modelo',  cx.prod,y+6);
  doc.text('Cant.',   cx.qty, y+6,{align:'right'});
  doc.text('P.Unit.', cx.pu,  y+6,{align:'right'});
  doc.text('Total',   cx.tot, y+6,{align:'right'});
  y+=9;

  /* ── Filas ── */
  let subtotal=0;
  cart.forEach((item,i)=>{
    const rh=14;
    if(i%2===0){doc.setFillColor(250,252,255);doc.rect(M,y,W-M*2,rh,'F');}
    const lt=item.price*item.qty; subtotal+=lt;

    doc.setFont('helvetica','bold'); doc.setFontSize(7.5); doc.setTextColor(...OSCURO);
    doc.text(String(i+1), cx.n, y+5.5);

    doc.text(item.name.length>40?item.name.slice(0,40)+'…':item.name,cx.prod,y+5.5);
    doc.setFont('helvetica','normal');doc.setFontSize(6);doc.setTextColor(100,115,135);doc.text(item.desc.length>60?item.desc.slice(0,60)+'…':item.desc,cx.prod,y+10.5);
    doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(...OSCURO);doc.text(String(item.qty),cx.qty,y+7.5,{align:'right'});doc.text('$'+item.price.toFixed(2),cx.pu,y+7.5,{align:'right'});doc.text('$'+lt.toFixed(2),cx.tot,y+7.5,{align:'right'});
    doc.setDrawColor(215,225,238);doc.setLineWidth(0.15);doc.line(M,y+rh,W-M,y+rh);y+=rh;
  });

  y+=7;

  /* ── Totales ── */
  const imp=subtotal*(impPct/100),total=subtotal+imp,tw=72,tx=W-M-tw;

  const totRow=(label,val,bold=false,hi=false)=>{
    const h=hi?10:8;
    if(hi){doc.setFillColor(...AZUL);}else{doc.setFillColor(...GRIS);}
    doc.rect(tx,y,tw,h,'F');
    doc.setFont('helvetica',bold?'bold':'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(...(hi?BLANCO:OSCURO));
    doc.text(label,    tx+4,    y+(hi?7:5.5));
    doc.text(val,      tx+tw-4, y+(hi?7:5.5),{align:'right'});
    y+=h+1;
  };

  totRow('Subtotal', '$'+subtotal.toFixed(2));
  totRow(`Impuesto (${impPct}%)`, impPct>0?'$'+imp.toFixed(2):'—');
  totRow('TOTAL',   '$'+total.toFixed(2), true, true);

  y+=10;

  /* ── Notas ── */
  if(y<255){
    doc.setFillColor(...GRIS); doc.roundedRect(M,y,W-M*2,22,2,2,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(7.5); doc.setTextColor(...AZUL);
    doc.text('NOTAS', M+4, y+7);
    doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(80,90,110);
    doc.text('• Precios no incluyen instalación salvo indicación expresa.', M+4, y+13);
    doc.text(`• Cotización válida por ${validez} a partir de la fecha de emisión.`, M+4, y+19);
  }

  /* ── Pie ── */
  doc.setDrawColor(...AZUL); doc.setLineWidth(0.4); doc.line(M,283,W-M,283);
  doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(...GRISC);
  doc.text(`${EMP.nombre}  ·  ${EMP.email}  ·  ${EMP.web}`, W/2, 287,{align:'center'});

  doc.save(`Cotizacion_${EMP.nombre}_${folio}.pdf`);
  closeModal('pdfModal');
  toast('PDF descargado correctamente ✓','success');
}




/* ── ENVIAR POR CORREO ── */
function enviarPorCorreo() {
  const nombre=document.getElementById('pdfNombre').value.trim(),empresa=document.getElementById('pdfEmpresa').value.trim(),correo=document.getElementById('pdfCorreo').value.trim(),telefono=document.getElementById('pdfTelefono').value.trim(),impPct=parseFloat(document.getElementById('pdfImpuesto').value)||0,validez=document.getElementById('pdfValidez').value.trim()||'15 días';

  if(!nombre){toast('El nombre es obligatorio','error');return;}if(!correo){toast('El correo es obligatorio','error');return;}if(!cart.length){toast('La cotización está vacía','error');return;}

  /* Generar PDF en base64 para enviarlo al servidor */
  try {
    const b64=_buildPDFBase64(nombre,empresa,correo,telefono,impPct,validez),folio='COT-'+Date.now().toString().slice(-6);
    /* Deshabilitar botón y mostrar spinner */
    const btn=document.getElementById('btnEnviarCorreo'),msg=document.getElementById('pdfActionMsg');
    btn.disabled=true;btn.textContent='⏳ Enviando...';msg.style.display='none';

    /* Armar payload */
    fetch('enviar_cotizacion.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf_token:<?= json_encode($csrf) ?>,folio,nombre,empresa,correo,telefono,validez,impuesto:impPct,productos:cart.map(x=>({id:x.id,nombre:x.name,descripcion:x.desc,precio:x.price,cantidad:x.qty,total:parseFloat((x.price*x.qty).toFixed(2))})),subtotal:parseFloat(cart.reduce((s,x)=>s+x.price*x.qty,0).toFixed(2)),pdf_base64:b64,pdf_nombre:`Cotizacion_Jentechnology_${folio}.pdf`})})
    
    
    .then(r=>r.json()).then(data=>{btn.disabled=false;btn.textContent='✉️ Enviar al correo de Jentechnology';msg.style.display='block';if(data.ok){msg.style.background='rgba(0,170,255,.08)';msg.style.border='1px solid rgba(0,170,255,.28)';msg.style.color='var(--accent)';msg.textContent='✓ Cotización enviada correctamente.';toast('Correo enviado ✓','success');}else{msg.style.background='rgba(255,61,113,.1)';msg.style.border='1px solid rgba(255,61,113,.28)';msg.style.color='#ff7a9a';msg.textContent='✗ '+(data.error||'Error al enviar.');toast('Error al enviar correo','error');}})
    .catch(()=>{btn.disabled=false;btn.textContent='✉️ Enviar al correo de Jentechnology';toast('Error de conexión.','error');});
  }catch(err){toast('Error al preparar el PDF.','error');console.error(err);}
}

/* Versión que retorna base64 en lugar de descargar */
function _buildPDFBase64(nombre, empresa, correo, telefono, impPct, validez) {
  const {jsPDF}=window.jspdf,doc=new jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
  const W=210,M=18,AZUL=[0,100,200],OSCURO=[10,20,40],GRIS=[245,247,250],GRISC=[170,180,200],BLANCO=[255,255,255];

  doc.setFillColor(...OSCURO); doc.rect(0,0,W,44,'F');
  doc.setFillColor(...AZUL);   doc.rect(0,0,6,44,'F');
  doc.setTextColor(...BLANCO); doc.setFont('helvetica','bold'); doc.setFontSize(20);
  doc.text(EMP.nombre.toUpperCase(), M+6, 16);
  doc.setFont('helvetica','normal'); doc.setFontSize(7.5); doc.setTextColor(...GRISC);
  doc.text(EMP.slogan, M+6, 22);
  doc.text(EMP.email+' | wa.me/'+EMP.wa, M+6, 28);
  doc.text(EMP.web, M+6, 34);

  const folio='COT-'+Date.now().toString().slice(-6),fecha=new Date().toLocaleDateString('es-MX',{year:'numeric',month:'long',day:'numeric'});
  doc.setFont('helvetica','bold'); doc.setFontSize(13); doc.setTextColor(...AZUL);
  doc.text('COTIZACIÓN', W-M, 16, {align:'right'});
  doc.setFont('helvetica','normal');doc.setFontSize(7.5);doc.setTextColor(...GRISC);doc.text('N°: '+folio,W-M,23,{align:'right'});doc.text('Fecha: '+fecha,W-M,29,{align:'right'});doc.text('Válida: '+validez,W-M,35,{align:'right'});

  let y=54;
  const clientH=(empresa||telefono)?30:22;
  doc.setFillColor(...GRIS);doc.roundedRect(M,y,W-M*2,clientH,2,2,'F');doc.setFont('helvetica','bold');doc.setFontSize(7.5);doc.setTextColor(...AZUL);doc.text('CLIENTE',M+4,y+7);
  doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(...OSCURO);
  doc.text(nombre, M+4, y+15);
  if(empresa){doc.setFont('helvetica','normal');doc.setFontSize(7.5);doc.setTextColor(80,90,110);doc.text(empresa,M+4,y+21);}
  const rc=W/2+4;
  doc.setFont('helvetica','normal'); doc.setFontSize(7.5); doc.setTextColor(80,90,110);
  doc.text('Email: '+correo, rc, y+15);
  if(telefono) doc.text('Tel: '+telefono, rc, y+21);
  y+=clientH+8;

  doc.setFillColor(...AZUL);doc.rect(M,y,W-M*2,9,'F');doc.setFont('helvetica','bold');doc.setFontSize(8);doc.setTextColor(...BLANCO);
  const cx={n:M+3,prod:M+13,qty:W-M-46,pu:W-M-28,tot:W-M-4};
  doc.text('#',cx.n,y+6); doc.text('Modelo',cx.prod,y+6);
  doc.text('Cant.',cx.qty,y+6,{align:'right'}); doc.text('P.Unit.',cx.pu,y+6,{align:'right'}); doc.text('Total',cx.tot,y+6,{align:'right'});
  y+=9;

  let subtotal=0;
  cart.forEach((item,i)=>{const rh=14;if(i%2===0){doc.setFillColor(250,252,255);doc.rect(M,y,W-M*2,rh,'F');}const lt=item.price*item.qty;subtotal+=lt;doc.setFont('helvetica','bold');doc.setFontSize(7.5);doc.setTextColor(...OSCURO);doc.text(String(i+1),cx.n,y+5.5);doc.text(item.name.length>40?item.name.slice(0,40)+'…':item.name,cx.prod,y+5.5);doc.setFont('helvetica','normal');doc.setFontSize(6);doc.setTextColor(100,115,135);doc.text(item.desc.length>60?item.desc.slice(0,60)+'…':item.desc,cx.prod,y+10.5);doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(...OSCURO);doc.text(String(item.qty),cx.qty,y+7.5,{align:'right'});doc.text('$'+item.price.toFixed(2),cx.pu,y+7.5,{align:'right'});doc.text('$'+lt.toFixed(2),cx.tot,y+7.5,{align:'right'});doc.setDrawColor(215,225,238);doc.setLineWidth(0.15);doc.line(M,y+rh,W-M,y+rh);y+=rh;});
  y+=7;

  const imp=subtotal*(impPct/100),total=subtotal+imp,tw=72,tx=W-M-tw;
  const totRow=(label,val,bold=false,hi=false)=>{
    const h=hi?10:8;
    if(hi){doc.setFillColor(...AZUL);}else{doc.setFillColor(...GRIS);}
    doc.rect(tx,y,tw,h,'F');
    doc.setFont('helvetica',bold?'bold':'normal'); doc.setFontSize(8.5);
    doc.setTextColor(...(hi?BLANCO:OSCURO));
    doc.text(label,tx+4,y+(hi?7:5.5));
    doc.text(val,tx+tw-4,y+(hi?7:5.5),{align:'right'});
    y+=h+1;
  };
  totRow('Subtotal','$'+subtotal.toFixed(2));
  totRow(`Impuesto (${impPct}%)`,impPct>0?'$'+imp.toFixed(2):'—');
  totRow('TOTAL','$'+total.toFixed(2),true,true);
  y+=10;

  if(y<255){
    doc.setFillColor(...GRIS); doc.roundedRect(M,y,W-M*2,22,2,2,'F');
    doc.setFont('helvetica','bold'); doc.setFontSize(7.5); doc.setTextColor(...AZUL);
    doc.text('NOTAS',M+4,y+7);
    doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(80,90,110);
    doc.text('• Precios no incluyen instalación salvo indicación expresa.',M+4,y+13);
    doc.text(`• Cotización válida por ${validez} a partir de la fecha de emisión.`,M+4,y+19);
  }
  doc.setDrawColor(...AZUL); doc.setLineWidth(0.4); doc.line(M,283,W-M,283);
  doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(...GRISC);
  doc.text(`${EMP.nombre}  ·  ${EMP.email}  ·  ${EMP.web}`,W/2,287,{align:'center'});

  return doc.output('datauristring').split(',')[1]; // base64 puro
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){['loginModal','registerModal','detailModal','pdfModal','compareModal'].forEach(closeModal);closeCart();}});
</script>
</body>
</html>