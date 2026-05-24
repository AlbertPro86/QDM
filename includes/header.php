<?php
/**
 * CRM QUANTUN Digital - Header / Topbar
 * 
 * Variables disponibles al incluir:
 * $pageTitle - Título de la página
 * $pageSubtitle - Subtítulo opcional
 */
$user = getCurrentUser();
$pageTitle = $pageTitle ?? 'Panel Operativo';
$pageSubtitle = $pageSubtitle ?? '';

// Obtener flash message si existe
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CRM Operativo de QUANTUN Digital - Gestión de leads, finanzas y servicios">
    <title><?= sanitize($pageTitle) ?> | <?= APP_NAME ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="Assets/logo_quantun_digital_negro.png">

    <!-- Tipografías — QUANTUN Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Estilos -->
    <link rel="stylesheet" href="css/styles.css?v=<?= APP_VERSION ?>">

    <!-- Anti-FOUC sidebar: aplica collapsed ANTES de que el browser pinte -->
    <script>try{if(localStorage.getItem('sidebarCollapsed')==='1')document.documentElement.classList.add('_sb-col')}catch(e){}</script>
</head>
<body>
    <div class="app-layout">
        <!-- SIDEBAR -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()" id="mobileMenuBtn" aria-label="Abrir menú">
                        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <h1 class="topbar-title"><?= sanitize($pageTitle) ?></h1>
                            <?php if (!empty($pageTitleSuffix)): ?><?= $pageTitleSuffix ?><?php endif; ?>
                        </div>
                        <?php if (!empty($pageBreadcrumb)): ?>
                            <p class="topbar-subtitle"><?= $pageBreadcrumb ?></p>
                        <?php elseif ($pageSubtitle): ?>
                            <p class="topbar-subtitle"><?= sanitize($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-separator"></div>
                    <div class="notif-wrapper" id="notifWrapper">
                        <button class="topbar-btn" id="notificationBtn" title="Pendientes" onclick="toggleNotifDropdown(event)">
                            <span class="notif-badge" id="notifBadge">0</span>
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-dropdown-header">
                                <div class="notif-dropdown-title">
                                    <span class="notif-dot"></span>
                                    <span>Pendientes</span>
                                    <span id="notifTotal" class="notif-total-badge">0</span>
                                </div>
                                <a href="clientes.php" class="notif-see-all">Ver clientes →</a>
                            </div>
                            <div id="notifContent" style="max-height:400px;overflow-y:auto">
                                <div class="notif-empty">Cargando...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Avatar -->
                    <div class="topbar-avatar" title="<?= $user ? sanitize($user['nombre']) : 'Usuario' ?>" onclick="window.location.href='perfil.php'" id="topbarAvatar">
                        <?= $user ? $user['initials'] : 'Q' ?>
                    </div>
                </div>
            </header>

            <!-- Toast Container -->
            <div class="toast-container" id="toastContainer"></div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('<?= sanitize($flash['message']) ?>', '<?= $flash['type'] ?>');
                });
            </script>
            <?php endif; ?>

            <!-- Contenido de la página -->
            <div class="content-area">
