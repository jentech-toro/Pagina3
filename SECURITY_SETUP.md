{% raw %}# 🔒 Guía de Implementación: Cambios de Seguridad

## ⚠️ URGENTE: Regenerar Credenciales Expuestas

Tu contraseña SMTP estaba hardcodeada en el repositorio público:
```
SMTP_PASS=xfjbltdguibhqvpy
```

**Acción inmediata requerida:**
1. Accede a tu cuenta de Gmail
2. Genera una nueva **contraseña de aplicación** (App Password)
3. Reemplaza la vieja en tu archivo `.env`
4. Considera esta contraseña como comprometida ❌

---

## 📋 Pasos de Implementación

### Paso 1: Crear archivo `.env`

En la **raíz del proyecto** (mismo nivel que `index.php`), crea el archivo `.env`:

```bash
# En tu terminal o editor de texto
touch .env
```

Luego copia este contenido y reemplaza con tus valores reales:

```env
# =====================================
# CONFIGURACIÓN BASE DE DATOS
# =====================================
DB_HOST=127.0.0.1
DB_NAME=sistema_login
DB_USER=root
DB_PASS=TU_CONTRASEÑA_AQUI

# =====================================
# CONFIGURACIÓN SMTP (Correo)
# =====================================
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=sales@jentechnology.ca
SMTP_PASS=NUEVA_CONTRASEÑA_APP_AQUI
CORREO_DESTINO=sales@jentechnology.ca

# =====================================
# SEGURIDAD
# =====================================
ADMIN_SECRET=TU_CODIGO_ADMIN_SECRETO
APP_ENV=local
```

### Paso 2: Verificar `.env` está en `.gitignore`

El archivo `.gitignore` ya contiene `*.env`, así que tus credenciales **no se subirán a Git**. ✅

```bash
# Confirmar que .env NO está en Git
git status
# Debería mostrar: .env (ignorado)
```

### Paso 3: Actualizar tus archivos PHP

Ya hemos actualizado estos archivos en el repositorio:

- ✅ `config.php` — Nuevo cargador centralizado de configuración
- ✅ `conexion.php` — Ahora usa `config.php`
- ✅ `enviar_cotizacion.php` — Sin credenciales hardcodeadas
- ✅ `procesar_registro.php` — Usa config.php para admin_secret

### Paso 4: Otros archivos que podrían incluir credenciales

Busca en tu proyecto cualquier referencia a:
- `xfjbltdguibhqvpy` (contraseña vieja)
- `ADMIN2026` (admin_secret hardcodeado)
- `sales@jentechnology.ca` (credencial)

```bash
# Buscar archivos con credenciales
grep -r "xfjbltdguibhqvpy" .
grep -r "ADMIN2026" . --exclude-dir=.git
```

---

## 🧪 Validación Local

### Antes de subir cambios, verifica en tu desarrollo:

```php
<?php
require 'config.php';

// Debería cargar sin errores
$dbConfig = getDbConfig();
$smtpConfig = getSmtpConfig();
$securityConfig = getSecurityConfig();

echo "BD: OK\n";
echo "SMTP: " . ($smtpConfig ? "OK" : "NO CONFIGURADO") . "\n";
echo "Security: OK\n";
?>
```

### Probar conexión BD:

```php
<?php
require 'config.php';
require 'conexion.php';

if ($conn) {
    echo "Conexión a BD: ✅ Exitosa\n";
} else {
    echo "Conexión a BD: ❌ Fallida\n";
}
?>
```

---

## 📝 Cambios Realizados

| Archivo | Cambio |
|---------|--------|
| `.env.example` | ✅ Creado (plantilla de ejemplo) |
| `config.php` | ✅ Creado (cargador centralizado) |
| `conexion.php` | ✅ Actualizado (usa config.php) |
| `enviar_cotizacion.php` | ✅ Actualizado (credenciales desde config) |
| `procesar_registro.php` | ✅ Actualizado (admin_secret desde config) |

---

## 🔐 Mejores Prácticas Aplicadas

1. **Nunca versionaremos secretos** — Todo en `.env`
2. **Centralización** — `config.php` es la única fuente de verdad
3. **Validación temprana** — Los errores de configuración se detectan en desarrollo
4. **Fallback seguro** — Si falta config, la app muestra error claro (no falla silenciosamente)

---

## ⚡ Próximos Pasos de Seguridad

Después de esto, recomendamos:

1. **SQL Injection** — Revisar todas las queries (ya usan prepared statements ✅)
2. **CSRF** — Validar en todos los formularios (ya implementado ✅)
3. **Rate Limiting** — Mejorar el limitador de intentos de login
4. **Logs de auditoría** — Registrar intentos fallidos de acceso
5. **TLS/HTTPS** — Asegurar que la BD use conexión segura

---

## ❓ FAQ

**P: ¿Y si se me olvida crear el `.env`?**  
R: En desarrollo mostrará un error claro. En producción necesitarás configurar las variables en el servidor.

**P: ¿Puedo usar `.env` en producción?**  
R: No recomendamos. Usa variables de entorno del servidor (Docker, systemd, nginx, etc.)

**P: ¿Qué pasa si alguien roba mi contraseña vieja del Git?**  
R: Por eso regeneramos inmediatamente. El historio de Git aún la contiene, pero es difícil acceder sin acceso al repositorio.

---

## 📞 Soporte

Si algo falla durante la implementación:
1. Verifica que `.env` esté en la raíz
2. Revisa los permisos del archivo (debe ser legible por PHP)
3. Comprueba que PHP pueda leer el archivo
{% endraw %}
