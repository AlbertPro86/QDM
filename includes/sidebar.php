<?php
/**
 * CRM QUANTUN Digital - Sidebar Navigation
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();

try {
    $newLeadsCount = db()->query("SELECT COUNT(*) FROM leads WHERE estado = 'nuevo'")->fetchColumn();
} catch (Exception $e) {
    $newLeadsCount = 0;
}
try {
    $mejorasPendientes = db()->query("SELECT COUNT(*) FROM mejoras_plataforma WHERE completada = 0")->fetchColumn();
} catch (Exception $e) {
    $mejorasPendientes = 0;
}
try {
    $newSolicitudesWeb = db()->query("SELECT COUNT(*) FROM crm_solicitudes_web WHERE estado = 'nuevo'")->fetchColumn();
} catch (Exception $e) {
    $newSolicitudesWeb = 0;
}
try {
    $unreadChats = db()->query("SELECT COUNT(DISTINCT m.session_id) FROM crm_chat_messages m JOIN crm_chat_sessions s ON s.id = m.session_id WHERE m.sender = 'visitor' AND m.seen = 0 AND s.status = 'activo'")->fetchColumn();
} catch (Exception $e) {
    $unreadChats = 0;
}
?>

<!-- Mobile Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <!-- Logo + Collapse btn -->
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <img src="Assets/logo_quantun_digital_negro.png" alt="QUANTUN Digital">
        </a>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" onclick="toggleSidebarCollapse()" title="Colapsar menú">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    <!-- Navegación -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard" title="Dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span class="nav-link-text">Dashboard</span>
        </a>

        <a href="leads.php" class="nav-link <?= $currentPage === 'leads' ? 'active' : '' ?>" id="nav-leads" title="Gestión de Leads">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span class="nav-link-text">Gestión de Leads</span>
            <?php if ($newLeadsCount > 0): ?>
                <span class="nav-badge"><?= $newLeadsCount ?></span>
            <?php endif; ?>
        </a>

        <a href="solicitudes_web.php" class="nav-link <?= $currentPage === 'solicitudes_web' ? 'active' : '' ?>" id="nav-solicitudes-web" title="Solicitudes Web">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
            </svg>
            <span class="nav-link-text">Solicitudes Web</span>
            <?php if ($newSolicitudesWeb > 0): ?>
                <span class="nav-badge"><?= $newSolicitudesWeb ?></span>
            <?php endif; ?>
        </a>

        <a href="chat.php" class="nav-link <?= $currentPage === 'chat' ? 'active' : '' ?>" id="nav-chat" title="Chat en Vivo">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <span class="nav-link-text">Chat en Vivo</span>
            <?php if ($unreadChats > 0): ?>
                <span class="nav-badge"><?= $unreadChats ?></span>
            <?php endif; ?>
        </a>

        <a href="clientes.php" class="nav-link <?= ($currentPage === 'clientes' || $currentPage === 'cliente_detalle') ? 'active' : '' ?>" id="nav-clientes" title="Área de Clientes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <span class="nav-link-text">Área de Clientes</span>
        </a>

        <?php
        $facturacion_activa = $currentPage === 'cotizaciones' || $currentPage === 'cotizador';
        $facturacion_tipo_actual = $_GET['tipo'] ?? 'cotizacion';
        ?>
        <a href="cotizaciones.php?tipo=<?= $facturacion_tipo_actual ?>"
           class="nav-link <?= $facturacion_activa ? 'active' : '' ?>"
           title="Facturación">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="nav-link-text">Facturación</span>
        </a>

        <a href="mensajes.php" class="nav-link <?= $currentPage === 'mensajes' ? 'active' : '' ?>" id="nav-mensajes" title="Mensajes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <span class="nav-link-text">Mensajes</span>
        </a>

        <a href="email_marketing.php" class="nav-link <?= $currentPage === 'email_marketing' ? 'active' : '' ?>" id="nav-email" title="Email Marketing">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span class="nav-link-text">Email Marketing</span>
        </a>

        <a href="tareas.php" class="nav-link <?= $currentPage === 'tareas' ? 'active' : '' ?>" id="nav-tareas" title="Tareas">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
            <span class="nav-link-text">Tareas</span>
        </a>

        <a href="finanzas.php" class="nav-link <?= $currentPage === 'finanzas' ? 'active' : '' ?>" id="nav-finanzas" title="Núcleo Financiero">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="nav-link-text">Núcleo Financiero</span>
        </a>

        <a href="proveedores.php" class="nav-link <?= $currentPage === 'proveedores' ? 'active' : '' ?>" id="nav-proveedores" title="Proveedores">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <span class="nav-link-text">Proveedores</span>
        </a>

        <a href="plantillas_factura.php" class="nav-link <?= $currentPage === 'plantillas_factura' ? 'active' : '' ?>" id="nav-plantillas" title="Plantillas">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
            </svg>
            <span class="nav-link-text">Plantillas</span>
        </a>

        <a href="servicios.php" class="nav-link <?= $currentPage === 'servicios' ? 'active' : '' ?>" id="nav-servicios" title="Servicios">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <span class="nav-link-text">Servicios</span>
        </a>

        <div style="height:1px;background:var(--color-border);margin:6px 14px"></div>

        <a href="configuraciones.php" class="nav-link <?= $currentPage === 'configuraciones' ? 'active' : '' ?>" id="nav-configuraciones" title="Configuraciones">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span class="nav-link-text">Configuraciones</span>
        </a>

        <a href="archivo.php" class="nav-link <?= $currentPage === 'archivo' ? 'active' : '' ?>" id="nav-archivo" title="Archivo">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            <span class="nav-link-text">Archivo</span>
        </a>

        <a href="portal_admin.php" class="nav-link <?= $currentPage === 'portal_admin' ? 'active' : '' ?>" id="nav-portal-admin" title="Portal Cliente — Gestión">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="nav-link-text">Portal Cliente</span>
        </a>

        <a href="mejoras.php" class="nav-link <?= $currentPage === 'mejoras' ? 'active' : '' ?>" id="nav-mejoras" title="Mejoras" style="opacity:<?= $currentPage === 'mejoras' ? '1' : '.5' ?>;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity='<?= $currentPage === 'mejoras' ? '1' : '.5' ?>'">
            <span style="position:relative;display:inline-flex;flex-shrink:0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 11h.01"/>
                </svg>
                <?php if ($mejorasPendientes > 0): ?>
                    <span style="position:absolute;top:-5px;right:-6px;min-width:15px;height:15px;border-radius:100px;background:#ef4444;color:#fff;font-size:9px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;letter-spacing:0;font-family:inherit;border:1.5px solid var(--sidebar-bg,#0E0E0C)"><?= $mejorasPendientes ?></span>
                <?php endif; ?>
            </span>
            <span class="nav-link-text">Mejoras</span>
        </a>
    </nav>

    <!-- Footer: User info -->
    <div class="sidebar-footer">
        <div class="sidebar-user" onclick="window.location.href='perfil.php'">
            <div class="sidebar-user-avatar">
                <?= $user ? $user['initials'] : 'Q' ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= $user ? sanitize($user['nombre']) : 'Usuario' ?></div>
                <div class="sidebar-user-role"><?= $user ? $user['rol'] : '' ?></div>
            </div>
            <a href="logout.php" class="sidebar-logout-btn" title="Cerrar sesión" onclick="event.stopPropagation()" style="color:#94a3b8;transition:color 0.15s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </a>
        </div>
    </div>
</aside>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

function toggleSidebarCollapse() {
    const sidebar = document.getElementById('sidebar');
    const btn = document.getElementById('sidebarCollapseBtn');
    const isCollapsed = sidebar.classList.toggle('collapsed');
    btn.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
    localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
}

document.addEventListener('DOMContentLoaded', function() {
    // Quitar clase anti-FOUC del <html> — ya no es necesaria
    document.documentElement.classList.remove('_sb-col');

    if (localStorage.getItem('sidebarCollapsed') === '1') {
        const sidebar = document.getElementById('sidebar');
        const btn = document.getElementById('sidebarCollapseBtn');
        if (sidebar && btn) {
            // Sin transición para la carga inicial — evita el salto animado
            sidebar.style.transition = 'none';
            sidebar.classList.add('collapsed');
            btn.style.transform = 'rotate(180deg)';
            // Reactivar transiciones después de 2 frames (collapses manuales siguen animando)
            requestAnimationFrame(() => requestAnimationFrame(() => {
                sidebar.style.transition = '';
            }));
        }
    }

    // ── Tooltip de íconos del menú ──────────────────────────────────────────
    const tip = document.createElement('div');
    tip.id = 'sidebarNavTooltip';
    tip.style.cssText = [
        'position:fixed',
        'background:#0E0E0C',
        'color:#FAFAF7',
        'font-size:11px',
        'font-weight:700',
        'padding:4px 10px',
        'border-radius:3px',
        'white-space:nowrap',
        'pointer-events:none',
        'opacity:0',
        'transition:opacity .15s',
        'z-index:9999',
        'transform:translateY(-50%)',
    ].join(';');
    document.body.appendChild(tip);

    document.querySelectorAll('#sidebar .nav-link, #sidebar .nav-group-btn').forEach(link => {
        const label = (link.querySelector('.nav-link-text')?.textContent?.trim())
                   || link.getAttribute('title') || '';
        if (!label) return;

        link.addEventListener('mouseenter', function() {
            const sidebar = document.getElementById('sidebar');
            // Solo mostrar cuando el sidebar está colapsado o en mobile cerrado
            if (!sidebar.classList.contains('collapsed') && window.innerWidth > 768) return;
            const rect = link.getBoundingClientRect();
            tip.textContent = label;
            tip.style.top  = (rect.top + rect.height / 2) + 'px';
            tip.style.left = (rect.right + 10) + 'px';
            tip.style.opacity = '1';
        });

        link.addEventListener('mouseleave', function() {
            tip.style.opacity = '0';
        });
    });
});

function toggleNavGroup(btn) {
    const sub = btn.nextElementSibling;
    btn.classList.toggle('open');
    sub.classList.toggle('open');
}
</script>
