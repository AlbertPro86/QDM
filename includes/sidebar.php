<?php
/**
 * CRM QUANTUN Digital - Sidebar Navigation
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();

// Contar leads nuevos para badge
try {
    $newLeadsCount = db()->query("SELECT COUNT(*) FROM leads WHERE estado = 'nuevo'")->fetchColumn();
} catch (Exception $e) {
    $newLeadsCount = 0;
}
?>

<!-- Mobile Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-header">
        <a href="dashboard.php">
            <img src="Assets/logo_quantun_digital_blanco.png" alt="QUANTUN Digital">
        </a>
    </div>

    <!-- Navegación -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Dashboard
        </a>

        <a href="leads.php" class="nav-link <?= $currentPage === 'leads' ? 'active' : '' ?>" id="nav-leads">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Gestión de Leads
            <?php if ($newLeadsCount > 0): ?>
                <span class="nav-badge"><?= $newLeadsCount ?></span>
            <?php endif; ?>
        </a>

        <a href="clientes.php" class="nav-link <?= ($currentPage === 'clientes' || $currentPage === 'cliente_detalle') ? 'active' : '' ?>" id="nav-clientes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            Área de Clientes
        </a>

        <a href="cotizador.php" class="nav-link <?= $currentPage === 'cotizador' ? 'active' : '' ?>" id="nav-cotizador">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Cotizador
        </a>

        <a href="cotizaciones.php" class="nav-link <?= $currentPage === 'cotizaciones' ? 'active' : '' ?>" id="nav-cotizaciones">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
            Cotizaciones
        </a>

        <a href="mensajes.php" class="nav-link <?= $currentPage === 'mensajes' ? 'active' : '' ?>" id="nav-mensajes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            Mensajes
        </a>

        <a href="email_marketing.php" class="nav-link <?= $currentPage === 'email_marketing' ? 'active' : '' ?>" id="nav-email">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Email Marketing
        </a>

        <a href="tareas.php" class="nav-link <?= $currentPage === 'tareas' ? 'active' : '' ?>" id="nav-tareas">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
            Tareas
        </a>

        <a href="finanzas.php" class="nav-link <?= $currentPage === 'finanzas' ? 'active' : '' ?>" id="nav-finanzas">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Núcleo Financiero
        </a>

<a href="proveedores.php" class="nav-link <?= $currentPage === 'proveedores' ? 'active' : '' ?>" id="nav-proveedores">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Proveedores
        </a>

        <a href="plantillas_factura.php" class="nav-link <?= $currentPage === 'plantillas_factura' ? 'active' : '' ?>" id="nav-plantillas">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
            </svg>
            Plantillas
        </a>

        <a href="servicios.php" class="nav-link <?= $currentPage === 'servicios' ? 'active' : '' ?>" id="nav-servicios">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            Servicios
        </a>

        <a href="configuraciones.php" class="nav-link <?= $currentPage === 'configuraciones' ? 'active' : '' ?>" id="nav-configuraciones">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Configuraciones
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
            <a href="logout.php" title="Cerrar sesión" style="color: rgba(255,255,255,0.3); transition: color 0.15s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
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
</script>
