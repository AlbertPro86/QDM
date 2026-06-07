<?php
/**
 * CRM QUANTUN Digital — Portal Cliente · Login
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Si ya hay sesión de portal activa, redirigir
if (!empty($_SESSION['portal_logged_in'])) {
    header('Location: mi_cuenta.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Cliente | QUANTUN Digital</title>
    <link rel="icon" type="image/png" href="Assets/logo_quantun_digital_negro.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=<?= APP_VERSION ?>">
    <style>
        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--q-lima);
            color: #0E0E0C;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: var(--radius-full);
            margin-bottom: 20px;
        }
        .portal-hint {
            margin-top: 16px;
            padding: 12px 14px;
            background: rgba(198,242,78,.08);
            border: 1px solid rgba(198,242,78,.2);
            border-radius: var(--radius-md);
            font-size: 12.5px;
            color: var(--color-text-muted);
            line-height: 1.55;
        }
        .portal-hint strong { color: var(--color-text); }
        .portal-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 4px;
            color: var(--color-text-light);
            font-size: 12px;
        }
        .portal-divider::before,
        .portal-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.08);
        }
        .portal-back {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 18px;
            font-size: 12.5px;
            color: var(--color-text-light);
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        .portal-back:hover { color: var(--q-lima); }
        #portalError {
            animation: fadeIn .2s ease;
        }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:none} }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-dot"></div>
        <div class="login-bg-pattern"></div>

        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <img src="Assets/logo_quantun_digital_blanco.png" alt="QUANTUN Digital">
                </div>

                <div style="text-align:center">
                    <span class="portal-badge">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Portal Cliente
                    </span>
                </div>

                <h1 class="login-title">Bienvenido</h1>
                <p class="login-subtitle">Accede a tu estado de cuenta y servicios</p>

                <!-- Error -->
                <div id="portalError" style="display:none" class="login-error"></div>

                <!-- Formulario -->
                <form id="portalForm" class="login-form" autocomplete="on" onsubmit="return false">
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" id="pEmail" name="email" class="form-input"
                               placeholder="tu@correo.com" required autofocus autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" id="pPass" name="password" class="form-input"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="login-btn" id="pLoginBtn">
                        Ingresar a mi cuenta
                    </button>
                </form>


                <div class="portal-divider">¿Eres del equipo QUANTUN?</div>
                <div style="text-align:center">
                    <a href="index.php" class="portal-back">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Ir al CRM Operativo
                    </a>
                </div>

                <div class="login-footer" style="margin-top:28px">
                    © <?= date('Y') ?> QUANTUN Digital · Todos los derechos reservados
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('portalForm').addEventListener('submit', async function() {
        const btn   = document.getElementById('pLoginBtn');
        const err   = document.getElementById('portalError');
        const email = document.getElementById('pEmail').value.trim();
        const pass  = document.getElementById('pPass').value.trim();

        err.style.display = 'none';
        btn.disabled  = true;
        btn.textContent = 'Verificando...';

        try {
            const r = await fetch('api/portal_auth.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ action:'login', email, password: pass })
            });
            const d = await r.json();

            if (d.success) {
                btn.textContent = '¡Bienvenido!';
                window.location.href = 'mi_cuenta.php';
            } else {
                err.textContent    = d.error || 'Error al iniciar sesión.';
                err.style.display  = 'block';
                btn.disabled       = false;
                btn.textContent    = 'Ingresar a mi cuenta';
                document.getElementById('pPass').value = '';
                document.getElementById('pPass').focus();
            }
        } catch (e) {
            err.textContent   = 'Error de conexión. Intenta de nuevo.';
            err.style.display = 'block';
            btn.disabled      = false;
            btn.textContent   = 'Ingresar a mi cuenta';
        }
    });
    </script>
</body>
</html>
