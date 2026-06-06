<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 — Página no encontrada | Jentechnology</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#070a10;--surface:#0d1117;--card:#0f1620;--border:rgba(255,255,255,0.07);
  --accent:#00aaff;--accent2:#ff3d71;--text:#e8edf5;--muted:#6b7b93;
  --font-head:'Syne',sans-serif;--font-mono:'Space Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{
  background:var(--bg);color:var(--text);
  font-family:var(--font-mono);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  min-height:100vh;overflow:hidden;
  position:relative;
}

/* ── FONDO ANIMADO ── */
.bg-glow{
  position:fixed;inset:0;pointer-events:none;z-index:0;
  background:
    radial-gradient(ellipse 60% 40% at 20% 30%, rgba(0,170,255,.06) 0%, transparent 70%),
    radial-gradient(ellipse 50% 50% at 80% 70%, rgba(255,61,113,.04) 0%, transparent 70%);
}
.scan-line{
  position:fixed;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,rgba(0,170,255,.4),transparent);
  animation:scanMove 4s linear infinite;
  z-index:1;pointer-events:none;
}
@keyframes scanMove{
  0%{top:-2px;opacity:0;}
  5%{opacity:1;}
  95%{opacity:1;}
  100%{top:100vh;opacity:0;}
}

/* ── GRID LINES ── */
.grid-lines{
  position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:
    linear-gradient(rgba(0,170,255,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,170,255,.03) 1px, transparent 1px);
  background-size:60px 60px;
}

/* ── CONTENIDO ── */
.container{
  position:relative;z-index:10;
  text-align:center;
  padding:2rem;
  max-width:600px;
  width:100%;
  animation:fadeUp .6s ease forwards;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

/* Logo */
.logo{
  height:40px;width:auto;
  margin:0 auto 2.5rem;
  display:block;
  filter:drop-shadow(0 0 16px rgba(0,170,255,.4));
}

/* 404 número grande */
.error-code{
  position:relative;
  display:inline-block;
  margin-bottom:1.5rem;
}
.error-num{
  font-family:var(--font-head);
  font-size:clamp(6rem,20vw,10rem);
  font-weight:800;
  letter-spacing:-.05em;
  line-height:1;
  color:transparent;
  -webkit-text-stroke:2px rgba(0,170,255,.3);
  position:relative;
}
.error-num::before{
  content:'404';
  position:absolute;inset:0;
  color:var(--text);
  -webkit-text-stroke:0;
  clip-path:inset(0 0 50% 0);
}
.error-num::after{
  content:'404';
  position:absolute;inset:0;
  color:rgba(0,170,255,.15);
  -webkit-text-stroke:0;
  transform:scaleY(-1) translateY(-2px);
  clip-path:inset(45% 0 0 0);
  filter:blur(1px);
}
.error-badge{
  position:absolute;
  top:.5rem;right:-1rem;
  padding:.2rem .55rem;
  background:rgba(255,61,113,.15);
  border:1px solid rgba(255,61,113,.3);
  border-radius:4px;
  font-size:.58rem;
  font-weight:700;
  letter-spacing:.15em;
  text-transform:uppercase;
  color:var(--accent2);
  animation:badgePulse 2s ease-in-out infinite;
}
@keyframes badgePulse{0%,100%{opacity:1}50%{opacity:.5}}

/* Textos */
.error-title{
  font-family:var(--font-head);
  font-size:clamp(1.2rem,4vw,1.8rem);
  font-weight:800;
  letter-spacing:-.02em;
  margin-bottom:.75rem;
  color:var(--text);
}
.error-title span{color:var(--accent);}
.error-desc{
  font-size:.75rem;
  line-height:1.9;
  color:var(--muted);
  max-width:420px;
  margin:0 auto 2rem;
}

/* Terminal box */
.terminal{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:10px;
  padding:1rem 1.25rem;
  margin:0 auto 2rem;
  max-width:380px;
  text-align:left;
  font-size:.68rem;
  line-height:1.8;
}
.terminal-bar{
  display:flex;gap:.35rem;margin-bottom:.75rem;
}
.terminal-dot{width:10px;height:10px;border-radius:50%;}
.t-red{background:#ff5f57;}
.t-yellow{background:#febc2e;}
.t-green{background:#28c840;}
.terminal-line{color:var(--muted);}
.terminal-line .cmd{color:var(--accent);}
.terminal-line .err{color:var(--accent2);}
.terminal-line .ok{color:#22c55e;}
.cursor{
  display:inline-block;width:8px;height:1em;
  background:var(--accent);
  vertical-align:text-bottom;
  animation:blink .8s step-end infinite;
}
@keyframes blink{50%{opacity:0}}

/* Botones */
.btn-group{display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap;}
.btn-primary{
  padding:.6rem 1.25rem;
  background:var(--accent);color:#000;
  border-radius:7px;font-family:var(--font-mono);
  font-size:.68rem;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;
  text-decoration:none;
  transition:opacity .2s,transform .15s;
  border:none;cursor:pointer;
}
.btn-primary:hover{opacity:.85;transform:scale(.97);}
.btn-ghost{
  padding:.6rem 1.25rem;
  border:1px solid var(--border);color:var(--muted);
  border-radius:7px;font-family:var(--font-mono);
  font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;
  text-decoration:none;
  transition:all .2s;
}
.btn-ghost:hover{border-color:var(--accent);color:var(--accent);}

/* Links rápidos */
.quick-links{
  margin-top:2rem;
  display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;
}
.quick-link{
  font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;
  color:var(--muted);text-decoration:none;transition:color .2s;
  display:flex;align-items:center;gap:.35rem;
}
.quick-link:hover{color:var(--accent);}
.quick-link::before{content:'→';color:var(--accent);font-size:.7rem;}

/* Footer mínimo */
.mini-footer{
  position:fixed;bottom:1.5rem;left:0;right:0;
  text-align:center;font-size:.58rem;
  color:rgba(255,255,255,.2);letter-spacing:.12em;
  z-index:10;
}
</style>
</head>
<body>

<div class="bg-glow"></div>
<div class="grid-lines"></div>
<div class="scan-line"></div>

<div class="container">

  <!-- Logo -->
  <img src="imagen/logo.png" alt="Jentechnology" class="logo"
       onerror="this.style.display='none'">

  <!-- Número 404 -->
  <div class="error-code">
    <div class="error-num">404</div>
    <span class="error-badge">NOT FOUND</span>
  </div>

  <!-- Título y descripción -->
  <h1 class="error-title">Página <span>no encontrada</span></h1>
  <p class="error-desc">
    La URL que buscás no existe o fue movida.<br>
    Verificá la dirección o volvé al inicio.
  </p>

  <!-- Terminal decorativa -->
  <div class="terminal">
    <div class="terminal-bar">
      <div class="terminal-dot t-red"></div>
      <div class="terminal-dot t-yellow"></div>
      <div class="terminal-dot t-green"></div>
    </div>
    <div class="terminal-line"><span class="cmd">$ </span>GET <span style="color:var(--text);"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/???') ?></span></div>
    <div class="terminal-line"><span class="err">✗ Error 404:</span> Recurso no encontrado</div>
    <div class="terminal-line"><span class="ok">→ </span>Redirigiendo a inicio...</div>
    <div class="terminal-line"><span class="cursor"></span></div>
  </div>

  <!-- Botones -->
  <div class="btn-group">
    <a href="index.php" class="btn-primary">← Volver al inicio</a>
    <a href="index.php#catalogo" class="btn-ghost">Ver catálogo</a>
    <a href="https://wa.me/+19053922189?text=Hola%20Jentechnology%2C%20necesito%20ayuda" target="_blank" class="btn-ghost" style="border-color:rgba(37,211,102,.3);color:#25d366;">💬 WhatsApp</a>
  </div>

  <!-- Links rápidos -->
  <div class="quick-links">
    <a href="index.php" class="quick-link">Inicio</a>
    <a href="index.php#catalogo" class="quick-link">Catálogo</a>
    <a href="index.php#contacto" class="quick-link">Contacto</a>
    <a href="index.php#porque" class="quick-link">Nosotros</a>
  </div>

</div>

<div class="mini-footer">© <?= date('Y') ?> Jentechnology — Todos los derechos reservados</div>

<!-- Auto-redirect después de 15s -->
<script>
let segundos = 15;
const timer = setInterval(() => {
  segundos--;
  if (segundos <= 0) {
    clearInterval(timer);
    window.location.href = 'index.php';
  }
}, 1000);
</script>

</body>
</html>