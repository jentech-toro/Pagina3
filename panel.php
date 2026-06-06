<?php
require 'init.php';
require 'conexion.php';

// ── Solo admins ──
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!$isAdmin) { header('Location: index.php'); exit; }

$csrf     = generate_csrf_token();
$userName = htmlspecialchars($_SESSION['user_name'] ?? '');
$msg      = ['type'=>'','text'=>''];

// ══════════════════════════════════════════════════════
// PROCESAR ACCIONES POST
// ══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $msg = ['type'=>'error','text'=>'Token de seguridad inválido.'];
    } else {
        $action = $_POST['action'] ?? '';

        // ── PRODUCTOS ──
        if ($action === 'crear_producto') {
            $nombre      = trim($_POST['nombre']      ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio      = trim($_POST['precio']      ?? '');
            $tag         = trim($_POST['tag']         ?? 'Nuevo');
            $imagen      = trim($_POST['imagen']      ?? '');
            $activo      = 1;

            if (!$nombre || !$descripcion || !is_numeric($precio)) {
                $msg = ['type'=>'error','text'=>'Nombre, descripción y precio son obligatorios.'];
            } else {
                // Subida de imagen
                if (!empty($_FILES['imagen_file']['name'])) {
                    $ext  = strtolower(pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','webp'];
                    if (!in_array($ext, $allowed)) {
                        $msg = ['type'=>'error','text'=>'Formato de imagen no permitido. Usá JPG, PNG o WEBP.'];
                        goto end_action;
                    }
                    $filename    = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                    $destino_abs = __DIR__ . '/imagen/' . $filename;
                    $destino_rel = 'imagen/' . $filename;
                    // Crear carpeta si no existe
                    if (!is_dir(__DIR__ . '/imagen')) {
                        mkdir(__DIR__ . '/imagen', 0755, true);
                    }
                    if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $destino_abs)) {
                        $imagen = $destino_rel;
                    } else {
                        $msg = ['type'=>'error','text'=>'No se pudo subir la imagen. Verificá permisos de la carpeta imagen/.'];
                        goto end_action;
                    }
                }
                $stmt = $conn->prepare('INSERT INTO productos (nombre,descripcion,precio,tag,imagen,activo) VALUES (:n,:d,:p,:t,:i,:a)');
                $stmt->execute([':n'=>$nombre,':d'=>$descripcion,':p'=>(float)$precio,':t'=>$tag,':i'=>$imagen,':a'=>$activo]);
                $msg = ['type'=>'success','text'=>'Producto creado correctamente.'];
            }
        }

        elseif ($action === 'editar_producto') {
            $id          = (int)($_POST['producto_id'] ?? 0);
            $nombre      = trim($_POST['nombre']       ?? '');
            $descripcion = trim($_POST['descripcion']  ?? '');
            $precio      = trim($_POST['precio']       ?? '');
            $tag         = trim($_POST['tag']          ?? '');
            $imagen      = trim($_POST['imagen']       ?? '');
            $activo      = isset($_POST['activo']) ? 1 : 0;

            if (!$id || !$nombre || !$descripcion || !is_numeric($precio)) {
                $msg = ['type'=>'error','text'=>'Todos los campos son obligatorios.'];
            } else {
                if (!empty($_FILES['imagen_file']['name'])) {
                    $ext  = strtolower(pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','webp'];
                    if (!in_array($ext, $allowed)) {
                        $msg = ['type'=>'error','text'=>'Formato de imagen no permitido.'];
                        goto end_action;
                    }
                    $filename    = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                    $destino_abs = __DIR__ . '/imagen/' . $filename;
                    $destino_rel = 'imagen/' . $filename;
                    if (!is_dir(__DIR__ . '/imagen')) {
                        mkdir(__DIR__ . '/imagen', 0755, true);
                    }
                    if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $destino_abs)) {
                        $imagen = $destino_rel;
                    }
                }
                $stmt = $conn->prepare('UPDATE productos SET nombre=:n,descripcion=:d,precio=:p,tag=:t,imagen=:i,activo=:a WHERE id=:id');
                $stmt->execute([':n'=>$nombre,':d'=>$descripcion,':p'=>(float)$precio,':t'=>$tag,':i'=>$imagen,':a'=>$activo,':id'=>$id]);
                $msg = ['type'=>'success','text'=>'Producto actualizado.'];
            }
        }

        elseif ($action === 'toggle_producto') {
            $id     = (int)($_POST['producto_id'] ?? 0);
            $activo = (int)($_POST['activo_actual'] ?? 1) === 1 ? 0 : 1;
            $conn->prepare('UPDATE productos SET activo=:a WHERE id=:id')->execute([':a'=>$activo,':id'=>$id]);
            $msg = ['type'=>'success','text'=>$activo ? 'Producto activado.' : 'Producto desactivado.'];
        }

        elseif ($action === 'eliminar_producto') {
            $id = (int)($_POST['producto_id'] ?? 0);
            $conn->prepare('DELETE FROM productos WHERE id=:id')->execute([':id'=>$id]);
            $msg = ['type'=>'success','text'=>'Producto eliminado.'];
        }

        // ── USUARIOS ──
        elseif ($action === 'cambiar_rol') {
            $uid  = (int)($_POST['user_id'] ?? 0);
            $rol  = $_POST['rol'] === 'admin' ? 'admin' : 'user';
            if ($uid === (int)$_SESSION['user_id']) {
                $msg = ['type'=>'error','text'=>'No podés cambiar tu propio rol.'];
            } else {
                $conn->prepare('UPDATE usuarios SET rol=:r WHERE id=:id')->execute([':r'=>$rol,':id'=>$uid]);
                $msg = ['type'=>'success','text'=>'Rol actualizado.'];
            }
        }

        elseif ($action === 'eliminar_usuario') {
            $uid = (int)($_POST['user_id'] ?? 0);
            if ($uid === (int)$_SESSION['user_id']) {
                $msg = ['type'=>'error','text'=>'No podés eliminar tu propia cuenta.'];
            } else {
                $conn->prepare('DELETE FROM usuarios WHERE id=:id')->execute([':id'=>$uid]);
                $msg = ['type'=>'success','text'=>'Usuario eliminado.'];
            }
        }
    }
}
end_action:

// ══════════════════════════════════════════════════════
// LEER DATOS
// ══════════════════════════════════════════════════════
$productos   = $conn->query('SELECT * FROM productos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$usuarios    = $conn->query('SELECT id,nombre,email,rol,fecha_registro FROM usuarios ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

// Cotizaciones (si la tabla existe)
$cotizaciones = [];
try {
    $cotizaciones = $conn->query('SELECT * FROM cotizaciones ORDER BY creado_en DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$tags = ['Destacado','Popular','Nuevo','Oferta','Premium'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Admin — Jentechnology</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#070a10;--surface:#0d1117;--card:#0f1620;--border:rgba(255,255,255,0.07);
  --accent:#00aaff;--accent2:#ff3d71;--green:#22c55e;--yellow:#fbbf24;
  --text:#e8edf5;--muted:#6b7b93;
  --font-head:'Syne',sans-serif;--font-mono:'Space Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--bg);color:var(--text);font-family:var(--font-mono);min-height:100vh;}
a{color:inherit;text-decoration:none;}
button{cursor:pointer;border:none;background:none;font-family:inherit;}
img{display:block;max-width:100%;}

/* ── LAYOUT ── */
.layout{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:220px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar-logo{padding:1.5rem 1.25rem 1rem;border-bottom:1px solid var(--border);}
.sidebar-logo img{height:28px;width:auto;object-fit:contain;}
.sidebar-logo p{font-size:.55rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-top:.4rem;}
.sidebar-nav{padding:.75rem 0;flex:1;}
.nav-section{padding:.4rem 1.25rem .2rem;font-size:.55rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);}
.nav-item{display:flex;align-items:center;gap:.6rem;padding:.55rem 1.25rem;font-size:.68rem;color:var(--muted);transition:all .2s;cursor:pointer;border-left:2px solid transparent;}
.nav-item:hover{color:var(--text);background:rgba(255,255,255,.03);}
.nav-item.active{color:var(--accent);border-left-color:var(--accent);background:rgba(0,170,255,.05);}
.nav-item .icon{font-size:.9rem;width:18px;text-align:center;}
.sidebar-footer{padding:1rem 1.25rem;border-top:1px solid var(--border);}
.sidebar-user{font-size:.62rem;color:var(--muted);margin-bottom:.6rem;}
.sidebar-user strong{display:block;color:var(--text);margin-bottom:.1rem;}
.btn-logout{width:100%;padding:.45rem;border:1px solid rgba(255,61,113,.25);border-radius:6px;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--accent2);transition:all .2s;}
.btn-logout:hover{background:rgba(255,61,113,.1);}

/* MAIN */
.main{flex:1;min-width:0;display:flex;flex-direction:column;}
.topbar{padding:1rem 1.75rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:rgba(7,10,16,.8);backdrop-filter:blur(20px);position:sticky;top:0;z-index:50;}
.topbar h1{font-family:var(--font-head);font-size:1.1rem;font-weight:800;}
.topbar-actions{display:flex;gap:.5rem;align-items:center;}
.btn-primary{padding:.45rem 1rem;background:var(--accent);color:#000;border-radius:6px;font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;transition:opacity .2s;}
.btn-primary:hover{opacity:.85;}
.btn-secondary{padding:.45rem 1rem;border:1px solid var(--border);border-radius:6px;font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);transition:all .2s;}
.btn-secondary:hover{border-color:var(--accent);color:var(--accent);}
.btn-danger{padding:.38rem .75rem;border:1px solid rgba(255,61,113,.3);border-radius:6px;font-size:.6rem;letter-spacing:.08em;text-transform:uppercase;color:var(--accent2);transition:all .2s;}
.btn-danger:hover{background:rgba(255,61,113,.1);}
.btn-warning{padding:.38rem .75rem;border:1px solid rgba(251,191,36,.3);border-radius:6px;font-size:.6rem;letter-spacing:.08em;text-transform:uppercase;color:var(--yellow);transition:all .2s;}
.btn-warning:hover{background:rgba(251,191,36,.08);}
.btn-edit{padding:.38rem .75rem;border:1px solid var(--border);border-radius:6px;font-size:.6rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);transition:all .2s;}
.btn-edit:hover{border-color:var(--accent);color:var(--accent);}

/* CONTENT */
.content{padding:1.75rem;flex:1;}
.section-page{display:none;}
.section-page.active{display:block;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1.75rem;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.25rem;}
.stat-label{font-size:.58rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;}
.stat-value{font-family:var(--font-head);font-size:1.8rem;font-weight:800;line-height:1;}
.stat-value.accent{color:var(--accent);}
.stat-value.green{color:var(--green);}
.stat-value.yellow{color:var(--yellow);}
.stat-value.red{color:var(--accent2);}
.stat-sub{font-size:.58rem;color:var(--muted);margin-top:.3rem;}

/* TABLES */
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.table-header{padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;}
.table-header h3{font-family:var(--font-head);font-size:.9rem;font-weight:700;}
.table-search{background:var(--surface);border:1px solid var(--border);border-radius:6px;padding:.4rem .75rem;font-family:var(--font-mono);font-size:.68rem;color:var(--text);outline:none;transition:border-color .2s;width:200px;}
.table-search:focus{border-color:var(--accent);}
.table-search::placeholder{color:var(--muted);}
table{width:100%;border-collapse:collapse;}
th{padding:.7rem 1rem;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);text-align:left;border-bottom:1px solid var(--border);white-space:nowrap;}
td{padding:.75rem 1rem;font-size:.72rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,.02);}
.td-img{width:48px;height:48px;object-fit:contain;border-radius:6px;background:#060c14;padding:4px;}
.badge{display:inline-flex;align-items:center;padding:.18rem .55rem;border-radius:4px;font-size:.56rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;white-space:nowrap;}
.badge-active{background:rgba(34,197,94,.15);color:var(--green);border:1px solid rgba(34,197,94,.25);}
.badge-inactive{background:rgba(107,123,147,.12);color:var(--muted);border:1px solid rgba(107,123,147,.2);}
.badge-admin{background:rgba(0,170,255,.15);color:var(--accent);border:1px solid rgba(0,170,255,.25);}
.badge-user{background:rgba(107,123,147,.12);color:var(--muted);border:1px solid rgba(107,123,147,.2);}
.badge-tag{background:rgba(251,191,36,.12);color:var(--yellow);border:1px solid rgba(251,191,36,.2);}
.td-actions{display:flex;gap:.35rem;flex-wrap:wrap;}
.prod-name{font-weight:700;font-size:.72rem;margin-bottom:.15rem;}
.prod-model{font-size:.58rem;color:var(--muted);font-family:var(--font-mono);}

/* ALERT */
.alert{padding:.65rem 1rem;border-radius:8px;font-size:.72rem;margin-bottom:1.25rem;line-height:1.5;}
.alert-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--green);}
.alert-error{background:rgba(255,61,113,.1);border:1px solid rgba(255,61,113,.25);color:var(--accent2);}

/* MODALS */
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:500;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px);}
.modal-backdrop.open{display:flex;animation:fadeIn .2s ease;}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:14px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;animation:slideUp .2s ease;position:relative;}
@keyframes slideUp{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-hd{padding:1.4rem 1.5rem .8rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-hd h3{font-family:var(--font-head);font-size:1rem;font-weight:800;}
.modal-x{width:26px;height:26px;border-radius:50%;border:1px solid var(--border);color:var(--muted);font-size:.9rem;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.modal-x:hover{border-color:var(--accent2);color:var(--accent2);}
.modal-bd{padding:1.25rem 1.5rem;}
.modal-ft{padding:0 1.5rem 1.5rem;display:flex;gap:.5rem;justify-content:flex-end;}
.form-group{margin-bottom:.9rem;}
.form-group label{display:block;font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;}
.form-group input,.form-group textarea,.form-group select{width:100%;background:var(--card);border:1px solid var(--border);border-radius:7px;padding:.55rem .8rem;font-family:var(--font-mono);font-size:.74rem;color:var(--text);outline:none;transition:border-color .2s;}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--accent);}
.form-group textarea{resize:vertical;min-height:80px;}
.form-group select option{background:var(--surface);}
.form-row{display:flex;gap:.65rem;}
.form-row .form-group{flex:1;}
.form-check{display:flex;align-items:center;gap:.5rem;font-size:.72rem;color:var(--muted);cursor:pointer;}
.form-check input{width:auto;cursor:pointer;}
.img-preview{width:80px;height:80px;object-fit:contain;border-radius:6px;background:#060c14;padding:4px;border:1px solid var(--border);margin-top:.4rem;}

/* EMPTY STATE */
.empty-state{text-align:center;padding:3rem 1rem;color:var(--muted);}
.empty-state p{font-size:.8rem;margin-bottom:.35rem;}
.empty-state span{font-size:.65rem;}

/* COTIZACIONES */
.cot-prod-list{font-size:.65rem;color:var(--muted);line-height:1.7;}

@media(max-width:768px){
  .sidebar{display:none;}
  .content{padding:1rem;}
  .stats-grid{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body>
<div class="layout">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="imagen/logo.png" alt="Jentechnology">
    <p>Panel Admin</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">General</div>
    <div class="nav-item active" onclick="showSection('dashboard',this)">
      <span class="icon">📊</span> Dashboard
    </div>
    <div class="nav-section">Catálogo</div>
    <div class="nav-item" onclick="showSection('productos',this)">
      <span class="icon">📷</span> Productos
    </div>
    <div class="nav-section">Clientes</div>
    <div class="nav-item" onclick="showSection('usuarios',this)">
      <span class="icon">👥</span> Usuarios
    </div>
    <div class="nav-item" onclick="showSection('cotizaciones',this)">
      <span class="icon">📄</span> Cotizaciones
    </div>
    <div class="nav-section">Sitio</div>
    <div class="nav-item" onclick="window.open('index.php','_blank')">
      <span class="icon">🌐</span> Ver tienda
    </div>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <strong><?= $userName ?></strong>
      Administrador
    </div>
    <a href="logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main">

  <div class="topbar">
    <h1 id="topbarTitle">Dashboard</h1>
    <div class="topbar-actions">
      <button class="btn-primary" id="topbarBtn" onclick="openModalCrear()" style="display:none;">+ Nuevo producto</button>
      <a href="index.php" class="btn-secondary">← Tienda</a>
    </div>
  </div>

  <div class="content">

    <?php if ($msg['text']): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= htmlspecialchars($msg['text']) ?></div>
    <?php endif; ?>

    <!-- ══ DASHBOARD ══ -->
    <div class="section-page active" id="sec-dashboard">
      <?php
      $totalProd    = count($productos);
      $activos      = count(array_filter($productos, fn($p)=>$p['activo']));
      $totalUsers   = count($usuarios);
      $totalCots    = count($cotizaciones);
      ?>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Productos totales</div>
          <div class="stat-value accent"><?= $totalProd ?></div>
          <div class="stat-sub"><?= $activos ?> activos</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Usuarios registrados</div>
          <div class="stat-value green"><?= $totalUsers ?></div>
          <div class="stat-sub"><?= count(array_filter($usuarios,fn($u)=>$u['rol']==='admin')) ?> admins</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Cotizaciones</div>
          <div class="stat-value yellow"><?= $totalCots ?></div>
          <div class="stat-sub">enviadas por correo</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Inactivos</div>
          <div class="stat-value red"><?= $totalProd - $activos ?></div>
          <div class="stat-sub">productos ocultos</div>
        </div>
      </div>

      <!-- Últimos productos -->
      <div class="table-wrap">
        <div class="table-header"><h3>Últimos productos</h3></div>
        <table>
          <thead><tr><th>Imagen</th><th>Nombre</th><th>Precio</th><th>Estado</th></tr></thead>
          <tbody>
          <?php foreach(array_slice($productos,0,5) as $p): ?>
          <tr>
            <td><img class="td-img" src="<?= htmlspecialchars($p['imagen']) ?>" alt=""></td>
            <td><div class="prod-name"><?= htmlspecialchars($p['nombre']) ?></div><div class="prod-model"><?= htmlspecialchars($p['tag']) ?></div></td>
            <td>$<?= number_format((float)$p['precio'],2) ?></td>
            <td><span class="badge <?= $p['activo']?'badge-active':'badge-inactive' ?>"><?= $p['activo']?'Activo':'Inactivo' ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ PRODUCTOS ══ -->
    <div class="section-page" id="sec-productos">
      <div class="table-wrap">
        <div class="table-header">
          <h3>Productos (<?= count($productos) ?>)</h3>
          <div style="display:flex;gap:.5rem;align-items:center;">
            <input type="search" class="table-search" placeholder="Buscar producto..." oninput="tableSearch(this,'tbl-productos')">
            <button class="btn-primary" onclick="openModalCrear()">+ Nuevo</button>
          </div>
        </div>
        <div style="overflow-x:auto;">
        <table id="tbl-productos">
          <thead><tr><th>Img</th><th>Nombre</th><th>Precio</th><th>Tag</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach($productos as $p): ?>
          <tr>
            <td><img class="td-img" src="<?= htmlspecialchars($p['imagen']) ?>" alt=""></td>
            <td>
              <div class="prod-name"><?= htmlspecialchars($p['nombre']) ?></div>
              <div class="prod-model" style="margin-top:.2rem;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($p['descripcion']) ?></div>
            </td>
            <td>$<?= number_format((float)$p['precio'],2) ?></td>
            <td><span class="badge badge-tag"><?= htmlspecialchars($p['tag']) ?></span></td>
            <td><span class="badge <?= $p['activo']?'badge-active':'badge-inactive' ?>"><?= $p['activo']?'Activo':'Inactivo' ?></span></td>
            <td>
              <div class="td-actions">
                <button class="btn-edit" onclick="openModalEditar(<?= htmlspecialchars(json_encode($p)) ?>)">Editar</button>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="toggle_producto">
                  <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="activo_actual" value="<?= $p['activo'] ?>">
                  <button type="submit" class="btn-warning"><?= $p['activo']?'Desactivar':'Activar' ?></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este producto?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="eliminar_producto">
                  <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-danger">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($productos)): ?>
          <tr><td colspan="6"><div class="empty-state"><p>No hay productos</p><span>Creá el primero con el botón + Nuevo</span></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ══ USUARIOS ══ -->
    <div class="section-page" id="sec-usuarios">
      <div class="table-wrap">
        <div class="table-header">
          <h3>Usuarios (<?= count($usuarios) ?>)</h3>
          <input type="search" class="table-search" placeholder="Buscar usuario..." oninput="tableSearch(this,'tbl-usuarios')">
        </div>
        <div style="overflow-x:auto;">
        <table id="tbl-usuarios">
          <thead><tr><th>ID</th><th>Correo</th><th>Rol</th><th>Registro</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach($usuarios as $u): $esYo = (int)$u['id'] === (int)$_SESSION['user_id']; ?>
          <tr>
            <td style="color:var(--muted)"><?= $u['id'] ?></td>
            <td>
              <div style="font-weight:700;font-size:.72rem;"><?= htmlspecialchars($u['email']) ?></div>
              <?php if($esYo): ?><div style="font-size:.58rem;color:var(--accent);">← Tú</div><?php endif; ?>
            </td>
            <td><span class="badge <?= $u['rol']==='admin'?'badge-admin':'badge-user' ?>"><?= $u['rol'] ?></span></td>
            <td style="color:var(--muted);font-size:.65rem;"><?= htmlspecialchars($u['fecha_registro']) ?></td>
            <td>
              <?php if(!$esYo): ?>
              <div class="td-actions">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="cambiar_rol">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="rol" value="<?= $u['rol']==='admin'?'user':'admin' ?>">
                  <button type="submit" class="btn-warning"><?= $u['rol']==='admin'?'→ User':'→ Admin' ?></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar usuario?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="eliminar_usuario">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-danger">Eliminar</button>
                </form>
              </div>
              <?php else: ?><span style="font-size:.6rem;color:var(--muted);">Sin acción</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- ══ COTIZACIONES ══ -->
    <div class="section-page" id="sec-cotizaciones">

      <!-- Filtros -->
      <div class="table-wrap" style="margin-bottom:1rem;">
        <div style="padding:1rem 1.25rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
          <div style="flex:1;min-width:160px;">
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Buscar folio / cliente</div>
            <input type="search" class="table-search" style="width:100%;" id="cotSearch" placeholder="COT-123456 o nombre..." oninput="filtrarCots()">
          </div>
          <div style="min-width:140px;">
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Desde</div>
            <input type="date" class="table-search" style="width:100%;" id="cotDesde" onchange="filtrarCots()">
          </div>
          <div style="min-width:140px;">
            <div style="font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.35rem;">Hasta</div>
            <input type="date" class="table-search" style="width:100%;" id="cotHasta" onchange="filtrarCots()">
          </div>
          <button class="btn-secondary" onclick="limpiarFiltrosCots()" style="white-space:nowrap;">Limpiar filtros</button>
        </div>
        <div style="padding:0 1.25rem .75rem;font-size:.62rem;color:var(--muted);" id="cotResultInfo"></div>
      </div>

      <div class="table-wrap">
        <div class="table-header">
          <h3>Historial de cotizaciones</h3>
          <span style="font-size:.62rem;color:var(--muted);" id="cotTotalLabel"><?= count($cotizaciones) ?> registros</span>
        </div>
        <?php if(empty($cotizaciones)): ?>
        <div class="empty-state" style="padding:3rem;">
          <p>No hay cotizaciones registradas</p>
          <span>Aparecerán aquí cuando los clientes envíen cotizaciones por correo</span>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table id="tbl-cots">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Cliente</th>
              <th>Correo</th>
              <th>Productos</th>
              <th>Total</th>
              <th>Fecha</th>
              <th>Detalle</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($cotizaciones as $c):
            $prods_arr = json_decode($c['productos_json'] ?? '[]', true);
            $fecha_iso = date('Y-m-d', strtotime($c['creado_en']));
          ?>
          <tr data-folio="<?= strtolower(htmlspecialchars($c['folio'])) ?>"
              data-cliente="<?= strtolower(htmlspecialchars($c['cliente_nombre'].' '.$c['cliente_empresa'].' '.$c['cliente_correo'])) ?>"
              data-fecha="<?= $fecha_iso ?>">
            <td><span class="badge badge-tag"><?= htmlspecialchars($c['folio']) ?></span></td>
            <td>
              <div style="font-weight:700;font-size:.72rem;"><?= htmlspecialchars($c['cliente_nombre']) ?></div>
              <?php if(!empty($c['cliente_empresa'])): ?>
              <div style="font-size:.6rem;color:var(--muted);"><?= htmlspecialchars($c['cliente_empresa']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:.65rem;color:var(--muted);">
              <a href="mailto:<?= htmlspecialchars($c['cliente_correo']) ?>" style="color:var(--accent);"><?= htmlspecialchars($c['cliente_correo']) ?></a>
              <?php if(!empty($c['cliente_telefono'])): ?>
              <div style="color:var(--muted);margin-top:.1rem;"><?= htmlspecialchars($c['cliente_telefono']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="cot-prod-list">
                <?php foreach(array_slice($prods_arr,0,3) as $cp):
                  echo htmlspecialchars($cp['nombre']??'').' ×'.((int)($cp['cantidad']??1)).'<br>';
                endforeach;
                if(count($prods_arr)>3) echo '<span style="color:var(--muted)">+'. (count($prods_arr)-3).' más</span>';
                ?>
              </div>
            </td>
            <td style="font-weight:700;color:var(--accent);">$<?= number_format((float)$c['total'],2) ?></td>
            <td style="font-size:.62rem;color:var(--muted);white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($c['creado_en'])) ?></td>
            <td>
              <button class="btn-edit" onclick='abrirDetalleCot(<?= htmlspecialchars(json_encode([
                'folio'    => $c['folio'],
                'nombre'   => $c['cliente_nombre'],
                'empresa'  => $c['cliente_empresa'],
                'correo'   => $c['cliente_correo'],
                'telefono' => $c['cliente_telefono'],
                'validez'  => $c['validez'],
                'subtotal' => (float)$c['subtotal'],
                'impuesto_pct'   => (float)$c['impuesto_pct'],
                'impuesto_monto' => (float)$c['impuesto_monto'],
                'total'    => (float)$c['total'],
                'productos'=> $prods_arr,
                'fecha'    => date('d/m/Y H:i', strtotime($c['creado_en'])),
              ]), ENT_QUOTES) ?>)'>Ver detalle</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /layout -->

<!-- ══ MODAL: DETALLE COTIZACIÓN ══ -->
<div class="modal-backdrop" id="modalDetalleCot" onclick="backdropClose(event,'modalDetalleCot')">
  <div class="modal" style="max-width:600px;">
    <div class="modal-hd">
      <h3 id="detCotTitulo">Cotización</h3>
      <button class="modal-x" onclick="closeModal('modalDetalleCot')">✕</button>
    </div>
    <div class="modal-bd" id="detCotBody" style="padding:1.25rem 1.5rem;"></div>
    <div class="modal-ft">
      <button class="btn-secondary" onclick="closeModal('modalDetalleCot')">Cerrar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: CREAR PRODUCTO ══ -->
<div class="modal-backdrop" id="modalCrear" onclick="backdropClose(event,'modalCrear')">
  <div class="modal">
    <div class="modal-hd">
      <h3>Nuevo producto</h3>
      <button class="modal-x" onclick="closeModal('modalCrear')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-bd">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="crear_producto">
        <div class="form-group">
          <label>Nombre / Modelo *</label>
          <input type="text" name="nombre" required placeholder="Ej: DH-HAC-HDW1200TQN-IL-T">
        </div>
        <div class="form-group">
          <label>Descripción *</label>
          <textarea name="descripcion" required placeholder="Descripción técnica del producto..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Precio (USD) *</label>
            <input type="number" name="precio" required min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="form-group">
            <label>Tag / Categoría</label>
            <select name="tag">
              <?php foreach($tags as $t): ?><option><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Imagen (subir archivo)</label>
          <input type="file" name="imagen_file" accept=".jpg,.jpeg,.png,.webp" onchange="previewImg(this,'prev-crear')">
          <img id="prev-crear" class="img-preview" style="display:none;">
        </div>
        <div class="form-group">
          <label>O ruta de imagen existente</label>
          <input type="text" name="imagen" placeholder="imagen/nombre-archivo.jpg">
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn-secondary" onclick="closeModal('modalCrear')">Cancelar</button>
        <button type="submit" class="btn-primary">Crear producto</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: EDITAR PRODUCTO ══ -->
<div class="modal-backdrop" id="modalEditar" onclick="backdropClose(event,'modalEditar')">
  <div class="modal">
    <div class="modal-hd">
      <h3>Editar producto</h3>
      <button class="modal-x" onclick="closeModal('modalEditar')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-bd">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="editar_producto">
        <input type="hidden" name="producto_id" id="edit-id">
        <div class="form-group">
          <label>Nombre / Modelo *</label>
          <input type="text" name="nombre" id="edit-nombre" required>
        </div>
        <div class="form-group">
          <label>Descripción *</label>
          <textarea name="descripcion" id="edit-desc" required></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Precio (USD) *</label>
            <input type="number" name="precio" id="edit-precio" required min="0" step="0.01">
          </div>
          <div class="form-group">
            <label>Tag</label>
            <select name="tag" id="edit-tag">
              <?php foreach($tags as $t): ?><option><?= $t ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Imagen actual</label>
          <img id="edit-img-preview" class="img-preview">
        </div>
        <div class="form-group">
          <label>Cambiar imagen (subir archivo)</label>
          <input type="file" name="imagen_file" accept=".jpg,.jpeg,.png,.webp" onchange="previewImg(this,'edit-img-preview')">
        </div>
        <div class="form-group">
          <label>O editar ruta de imagen</label>
          <input type="text" name="imagen" id="edit-imagen">
        </div>
        <label class="form-check">
          <input type="checkbox" name="activo" id="edit-activo">
          Producto activo (visible en tienda)
        </label>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn-secondary" onclick="closeModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── NAVEGACIÓN ── */
function showSection(id, el) {
  document.querySelectorAll('.section-page').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('sec-' + id).classList.add('active');
  if (el) el.classList.add('active');
  const titles = {dashboard:'Dashboard',productos:'Productos',usuarios:'Usuarios',cotizaciones:'Cotizaciones'};
  document.getElementById('topbarTitle').textContent = titles[id] || id;
  document.getElementById('topbarBtn').style.display = id === 'productos' ? '' : 'none';
  // Activar sección desde URL hash
  history.replaceState(null, '', '#' + id);
}

// Activar sección por hash al cargar
(function(){
  const hash = location.hash.replace('#','');
  const valid = ['dashboard','productos','usuarios','cotizaciones'];
  if (valid.includes(hash)) {
    const navItem = document.querySelector(`[onclick*="'${hash}'"]`);
    showSection(hash, navItem);
  }
})();

/* ── MODALS ── */
function openModal(id){ document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function backdropClose(e,id){ if(e.target===document.getElementById(id)) closeModal(id); }

function openModalCrear(){ openModal('modalCrear'); }

function openModalEditar(p) {
  document.getElementById('edit-id').value       = p.id;
  document.getElementById('edit-nombre').value   = p.nombre;
  document.getElementById('edit-desc').value     = p.descripcion;
  document.getElementById('edit-precio').value   = p.precio;
  document.getElementById('edit-imagen').value   = p.imagen;
  document.getElementById('edit-activo').checked = p.activo == 1;
  document.getElementById('edit-img-preview').src = p.imagen;
  // Seleccionar tag
  const sel = document.getElementById('edit-tag');
  for (let i = 0; i < sel.options.length; i++) {
    if (sel.options[i].value === p.tag) { sel.selectedIndex = i; break; }
  }
  openModal('modalEditar');
}

/* ── PREVIEW IMAGEN ── */
function previewImg(input, previewId) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById(previewId);
    img.src = e.target.result;
    img.style.display = 'block';
  };
  reader.readAsDataURL(file);
}

/* ── BUSCADOR EN TABLAS ── */
function tableSearch(input, tableId) {
  const q = input.value.toLowerCase();
  const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

/* ══════════════════════════════════════════════
   FILTROS COTIZACIONES
══════════════════════════════════════════════ */
function filtrarCots() {
  const q      = (document.getElementById('cotSearch').value  || '').toLowerCase().trim();
  const desde  =  document.getElementById('cotDesde').value;
  const hasta  =  document.getElementById('cotHasta').value;
  const rows   = document.querySelectorAll('#tbl-cots tbody tr');
  let visible  = 0, total = rows.length;

  rows.forEach(row => {
    const folio   = row.dataset.folio   || '';
    const cliente = row.dataset.cliente || '';
    const fecha   = row.dataset.fecha   || '';

    const matchQ     = !q     || folio.includes(q) || cliente.includes(q);
    const matchDesde = !desde || fecha >= desde;
    const matchHasta = !hasta || fecha <= hasta;

    if (matchQ && matchDesde && matchHasta) {
      row.style.display = ''; visible++;
    } else {
      row.style.display = 'none';
    }
  });

  const info  = document.getElementById('cotResultInfo');
  const label = document.getElementById('cotTotalLabel');
  const hayFiltro = q || desde || hasta;
  if (hayFiltro) {
    info.textContent  = visible === 0
      ? 'Sin resultados para los filtros aplicados'
      : `Mostrando ${visible} de ${total} registros`;
    label.textContent = `${visible} registros`;
  } else {
    info.textContent  = '';
    label.textContent = `${total} registros`;
  }
}

function limpiarFiltrosCots() {
  document.getElementById('cotSearch').value = '';
  document.getElementById('cotDesde').value  = '';
  document.getElementById('cotHasta').value  = '';
  filtrarCots();
}

/* ══════════════════════════════════════════════
   DETALLE COTIZACIÓN
══════════════════════════════════════════════ */
function abrirDetalleCot(c) {
  document.getElementById('detCotTitulo').textContent = 'Cotización ' + c.folio;

  // Tabla de productos
  let filas = '';
  let subtotalCalc = 0;
  (c.productos || []).forEach((p, i) => {
    const lt = (parseFloat(p.precio)||0) * (parseInt(p.cantidad)||1);
    subtotalCalc += lt;
    const bg = i % 2 === 0 ? 'background:rgba(255,255,255,.02)' : '';
    filas += `<tr style="${bg}">
      <td style="padding:.55rem .75rem;font-size:.7rem;color:var(--text);border-bottom:1px solid var(--border);">${esc(p.nombre||'')}</td>
      <td style="padding:.55rem .75rem;font-size:.7rem;text-align:center;color:var(--muted);border-bottom:1px solid var(--border);">${parseInt(p.cantidad)||1}</td>
      <td style="padding:.55rem .75rem;font-size:.7rem;text-align:right;color:var(--muted);border-bottom:1px solid var(--border);">$${parseFloat(p.precio||0).toFixed(2)}</td>
      <td style="padding:.55rem .75rem;font-size:.7rem;text-align:right;font-weight:700;color:var(--accent);border-bottom:1px solid var(--border);">$${lt.toFixed(2)}</td>
    </tr>`;
  });

  const imp = parseFloat(c.impuesto_monto) || 0;
  const tot = parseFloat(c.total) || 0;

  document.getElementById('detCotBody').innerHTML = `
    <!-- Header info -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem;">
      <div style="background:var(--card);border:1px solid var(--border);border-radius:8px;padding:.85rem 1rem;">
        <div style="font-size:.56rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;">Cliente</div>
        <div style="font-size:.82rem;font-weight:700;color:var(--text);">${esc(c.nombre)}</div>
        ${c.empresa ? `<div style="font-size:.65rem;color:var(--muted);margin-top:.15rem;">${esc(c.empresa)}</div>` : ''}
        <div style="font-size:.65rem;color:var(--accent);margin-top:.3rem;">${esc(c.correo)}</div>
        ${c.telefono ? `<div style="font-size:.65rem;color:var(--muted);">${esc(c.telefono)}</div>` : ''}
      </div>
      <div style="background:var(--card);border:1px solid var(--border);border-radius:8px;padding:.85rem 1rem;">
        <div style="font-size:.56rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;">Cotización</div>
        <div style="font-size:.82rem;font-weight:700;color:var(--accent);">${esc(c.folio)}</div>
        <div style="font-size:.65rem;color:var(--muted);margin-top:.15rem;">Fecha: ${esc(c.fecha)}</div>
        <div style="font-size:.65rem;color:var(--muted);">Validez: ${esc(c.validez)}</div>
      </div>
    </div>

    <!-- Tabla productos -->
    <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:1rem;">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:rgba(0,170,255,.12);">
            <th style="padding:.55rem .75rem;font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);text-align:left;">Producto</th>
            <th style="padding:.55rem .75rem;font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);text-align:center;">Cant.</th>
            <th style="padding:.55rem .75rem;font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);text-align:right;">P.Unit.</th>
            <th style="padding:.55rem .75rem;font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);text-align:right;">Total</th>
          </tr>
        </thead>
        <tbody>${filas}</tbody>
      </table>
    </div>

    <!-- Totales -->
    <div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
      <div style="min-width:220px;background:var(--card);border:1px solid var(--border);border-radius:8px;overflow:hidden;">
        <div style="display:flex;justify-content:space-between;padding:.5rem .85rem;font-size:.7rem;color:var(--muted);border-bottom:1px solid var(--border);">
          <span>Subtotal</span><span style="color:var(--text);">$${parseFloat(c.subtotal||subtotalCalc).toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:.5rem .85rem;font-size:.7rem;color:var(--muted);border-bottom:1px solid var(--border);">
          <span>Impuesto (${parseFloat(c.impuesto_pct||0).toFixed(1)}%)</span><span>$${imp.toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:.65rem .85rem;font-size:.85rem;font-weight:700;background:rgba(0,170,255,.1);">
          <span style="color:var(--accent);">TOTAL</span><span style="color:var(--accent);">$${tot.toFixed(2)}</span>
        </div>
      </div>
    </div>

    <!-- Acciones rápidas -->
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a href="mailto:${esc(c.correo)}?subject=Re: Cotización ${esc(c.folio)}"
         style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border:1px solid rgba(0,170,255,.3);border-radius:6px;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);transition:all .2s;"
         onmouseover="this.style.background='rgba(0,170,255,.08)'" onmouseout="this.style.background=''">
        ✉️ Responder al cliente
      </a>
      <a href="https://wa.me/${esc(c.telefono?.replace(/\D/g,'') || '')}?text=Hola ${esc(c.nombre)}, con respecto a tu cotización ${esc(c.folio)}..."
         target="_blank"
         style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border:1px solid rgba(37,211,102,.3);border-radius:6px;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:#25d366;transition:all .2s;"
         onmouseover="this.style.background='rgba(37,211,102,.08)'" onmouseout="this.style.background=''">
        💬 WhatsApp
      </a>
    </div>`;

  openModal('modalDetalleCot');
}

function esc(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') ['modalCrear','modalEditar','modalDetalleCot'].forEach(closeModal);
});
</script>
</body>
</html>