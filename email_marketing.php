<?php
/**
 * CRM QUANTUN Digital — Email Marketing
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle = 'Email Marketing';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:600;color:var(--color-text)">Email Marketing</span>';

// KPI data
$pdo = db();

// Auto-create tables if needed (same as API)
$pdo->exec("CREATE TABLE IF NOT EXISTS email_plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    asunto VARCHAR(300) NOT NULL,
    cuerpo LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$pdo->exec("CREATE TABLE IF NOT EXISTS email_campanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    asunto VARCHAR(300) NOT NULL,
    cuerpo LONGTEXT NOT NULL,
    plantilla_id INT NULL,
    filtro_estado VARCHAR(50) DEFAULT 'todos',
    filtro_servicio VARCHAR(200) DEFAULT '',
    total INT DEFAULT 0,
    enviados INT DEFAULT 0,
    fallidos INT DEFAULT 0,
    estado ENUM('borrador','enviando','enviada','error') DEFAULT 'borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enviada_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$pdo->exec("CREATE TABLE IF NOT EXISTS email_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campana_id INT NOT NULL,
    cliente_id INT NOT NULL,
    email VARCHAR(200) NOT NULL,
    nombre_dest VARCHAR(200) DEFAULT '',
    estado ENUM('pendiente','enviado','fallido') DEFAULT 'pendiente',
    error_msg TEXT NULL,
    enviado_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$kpiCampanas    = (int) $pdo->query("SELECT COUNT(*) FROM email_campanas")->fetchColumn();
$kpiEnviados    = (int) ($pdo->query("SELECT COALESCE(SUM(enviados),0) FROM email_campanas")->fetchColumn() ?? 0);
$kpiPlantillas  = (int) $pdo->query("SELECT COUNT(*) FROM email_plantillas")->fetchColumn();
$kpiTotal       = (int) ($pdo->query("SELECT COALESCE(SUM(total),0) FROM email_campanas")->fetchColumn() ?? 0);
$kpiTasa        = $kpiTotal > 0 ? round(($kpiEnviados / $kpiTotal) * 100, 1) : 0;

include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Page scope styles ────────────────────────────────────────────── */
.em-wrap { padding: 0; }
@media(max-width:768px) { .em-wrap { padding: 0; } }

/* KPI row */
.em-kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
@media(max-width:900px) { .em-kpi-row { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px) { .em-kpi-row { grid-template-columns: 1fr; } }

.em-kpi {
    background: #fff;
    border: 1px solid #E8E5DD;
    border-radius: 6px;
    padding: 22px 24px 18px;
    color: #0E0E0C;
    position: relative;
    overflow: hidden;
}
.em-kpi.lime  { background: #C6F24E; color: #0E0E0C; }
.em-kpi.red   { background: #F4DEDB; color: #B0382F; }
.em-kpi.slate { background: #FAFAF7; border-color: #E8E5DD; color: #0E0E0C; }
.em-kpi-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #57544D;
    margin-bottom: 6px;
}
.em-kpi-val {
    font-size: 32px;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -.02em;
}
.em-kpi-sub {
    font-size: 11px;
    color: #8A867C;
    margin-top: 4px;
}

/* Tabs */
.em-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    border-bottom: 2px solid #E8E5DD;
}
.em-tab {
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 700;
    color: #57544D;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
    border-radius: 4px 4px 0 0;
}
.em-tab:hover { color: #0E0E0C; background: #FAFAF7; }
.em-tab.active { color: #0E0E0C; border-bottom-color: #C6F24E; }

.em-tab-panel { display: none; }
.em-tab-panel.active { display: block; }

/* Card */
.em-card {
    background: #fff;
    border-radius: 6px;
    border: 1.5px solid #E8E5DD;
    box-shadow: 0 1px 2px rgba(0,0,0,.02);
    overflow: hidden;
}
.em-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1.5px solid #EFECE5;
    gap: 12px;
    flex-wrap: wrap;
}
.em-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0E0E0C;
}

/* Table */
.em-table-wrap { overflow-x: auto; }
.em-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.em-table th {
    background: #FAFAF7;
    color: #57544D;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: .05em;
    text-transform: uppercase;
    padding: 10px 16px;
    text-align: left;
    border-bottom: 1.5px solid #E8E5DD;
    white-space: nowrap;
}
.em-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #EFECE5;
    color: #2A2926;
    vertical-align: middle;
}
.em-table tr:last-child td { border-bottom: none; }
.em-table tr:hover td { background: #FAFAF7; }
.em-table .name { font-weight: 700; color: #0E0E0C; }
.em-table .sub { font-size: 11px; color: #8A867C; margin-top: 2px; }

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .03em;
}
.badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    opacity: .7;
}
.badge-gray    { background: #EFECE5; color: #57544D; }
.badge-blue    { background: #E1E7F2; color: #3F5E9E; }
.badge-green   { background: #E3F1E8; color: #2D8F5A; }
.badge-red     { background: #F4DEDB; color: #B0382F; }
.badge-purple  { background: #EFECE5; color: #57544D; }

/* Action buttons */
.em-actions { display: flex; gap: 6px; align-items: center; }
.btn-icon {
    width: 30px;
    height: 30px;
    border-radius: 4px;
    border: 1.5px solid #E8E5DD;
    background: #fff;
    color: #57544D;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s;
}
.btn-icon:hover { background: #FAFAF7; border-color: #D6D2C7; color: #0E0E0C; }
.btn-icon.danger:hover { background: #F4DEDB; border-color: #e8c9c6; color: #B0382F; }
.btn-icon.send:hover   { background: #E3F1E8; border-color: #c8e6d4; color: #2D8F5A; }
.btn-icon:disabled     { opacity: .35; cursor: not-allowed; pointer-events: none; }

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all .15s;
    white-space: nowrap;
}
.btn-primary { background: #0E0E0C; color: #C6F24E; }
.btn-primary:hover { background: #2A2926; }
.btn-outline { background: #fff; color: #0E0E0C; border: 1.5px solid #E8E5DD; }
.btn-outline:hover { border-color: #0E0E0C; }
.btn-ghost { background: transparent; color: #57544D; }
.btn-ghost:hover { background: #EFECE5; color: #0E0E0C; }
.btn-lime { background: #C6F24E; color: #0E0E0C; }
.btn-lime:hover { background: #b8e019; }
.btn-sm { padding: 7px 14px; font-size: 12px; }

/* Empty state */
.em-empty {
    text-align: center;
    padding: 56px 24px;
    color: #8A867C;
}
.em-empty svg { margin: 0 auto 16px; display: block; opacity: .3; }
.em-empty p { font-size: 14px; margin: 0; }

/* Template grid */
.em-tpl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    padding: 20px;
}
.em-tpl-card {
    background: #fff;
    border: 1.5px solid #E8E5DD;
    border-radius: 6px;
    padding: 18px;
    transition: box-shadow .15s;
}
.em-tpl-card:hover { box-shadow: 0 1px 3px rgba(0,0,0,.03); }
.em-tpl-card .tpl-name {
    font-weight: 700;
    font-size: 14px;
    color: #0E0E0C;
    margin-bottom: 4px;
}
.em-tpl-card .tpl-asunto {
    font-size: 12px;
    color: #57544D;
    margin-bottom: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.em-tpl-card .tpl-date {
    font-size: 11px;
    color: #8A867C;
}
.em-tpl-card .tpl-actions {
    display: flex;
    gap: 6px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #EFECE5;
}

/* Modal base */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 5000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity .2s, visibility .2s;
    backdrop-filter: blur(3px);
    padding: 16px;
}
.modal-overlay.show { opacity: 1; visibility: visible; }
.modal {
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    width: 100%;
    max-height: 96vh;
    overflow-y: auto;
    transform: translateY(16px) scale(.97);
    transition: transform .2s;
}
.modal-overlay.show .modal { transform: translateY(0) scale(1); }
.modal-sm  { max-width: 480px; }
.modal-md  { max-width: 680px; }
.modal-lg  { max-width: 960px; }
.modal-xl  { max-width: 1300px; }
.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 28px 18px;
    border-bottom: 1.5px solid #EFECE5;
}
.modal-head h2 { font-size: 17px; font-weight: 700; color: #0E0E0C; margin: 0; }
.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    border: 1.5px solid #E8E5DD;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content:center;
    color: #57544D;
    transition: all .15s;
    flex-shrink: 0;
}
.modal-close:hover { background: #FAFAF7; color: #0E0E0C; }
.modal-body { padding: 24px 28px; }
.modal-foot {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 16px 28px 22px;
    border-top: 1.5px solid #EFECE5;
    flex-wrap: wrap;
}

/* Form */
.form-group { margin-bottom: 16px; }
.form-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #57544D;
    margin-bottom: 6px;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.form-control {
    width: 100%;
    padding: 9px 13px;
    border: 1.5px solid #E8E5DD;
    border-radius: 4px;
    font-size: 13px;
    color: #0E0E0C;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
    font-family: inherit;
}
.form-control:focus {
    outline: none;
    border-color: #0E0E0C;
    box-shadow: 0 0 0 3px rgba(14,14,12,.06);
}
select.form-control { cursor: pointer; }
textarea.form-control { resize: vertical; font-family: 'Courier New', monospace; font-size: 12px; }

/* Recipient preview chip */
.dest-preview {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 99px;
    background: #E3F1E8;
    color: #2D8F5A;
    font-size: 12px;
    font-weight: 700;
    border: 1.5px solid #c8e6d4;
    margin-top: 8px;
    transition: all .2s;
}
.dest-preview.loading { background: #FAFAF7; color: #8A867C; border-color: #E8E5DD; }
.dest-preview.zero    { background: #F5EBD3; color: #B47A1E; border-color: #e8d5a8; }

/* Variable chips */
.var-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.var-chip {
    padding: 4px 12px;
    border-radius: 99px;
    background: #EFECE5;
    color: #2A2926;
    font-size: 11px;
    font-weight: 700;
    font-family: monospace;
    cursor: pointer;
    border: 1.5px solid #E8E5DD;
    transition: all .15s;
}
.var-chip:hover { background: #0E0E0C; color: #C6F24E; border-color: #0E0E0C; }

/* Editor layout (template modal) */
.tpl-editor-grid {
    display: grid;
    grid-template-columns: 5fr 4fr;
    gap: 28px;
}
@media(max-width:900px) { .tpl-editor-grid { grid-template-columns: 1fr; } }
.tpl-preview-frame {
    border: 1.5px solid #E8E5DD;
    border-radius: 4px;
    width: 100%;
    height: 620px;
    background: #FAFAF7;
    position: sticky;
    top: 0;
}

/* Historial filter */
.em-hist-filter {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Preview modal grid */
@media(max-width:700px) {
    #modalPreviewCampana .modal-body { grid-template-columns: 1fr !important; }
    #prev-iframe { height: 300px !important; }
}

/* Spinner */
@keyframes spin { to { transform: rotate(360deg); } }
.spinner {
    width: 16px; height: 16px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: inline-block;
    flex-shrink: 0;
}
</style>

<div class="em-wrap">

    <!-- Page header -->
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-left" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1">
            <div style="position:relative">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                    style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--color-text-light);pointer-events:none">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="em-filtro-nombre" placeholder="Buscar campaña…"
                    oninput="renderCampanas()"
                    style="width:220px;padding:8px 12px 8px 33px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;background:#fff;color:var(--color-text);outline:none"
                    onfocus="this.style.borderColor='var(--color-text)'" onblur="this.style.borderColor='var(--color-border)'">
            </div>
            <select id="em-filtro-estado" onchange="renderCampanas()"
                style="padding:8px 12px;border:1.5px solid var(--color-border);border-radius:var(--radius-sm);font-size:13px;color:var(--color-text);background:#fff;cursor:pointer;outline:none">
                <option value="todos">Todos los estados</option>
                <option value="borrador">Borrador</option>
                <option value="enviando">Enviando</option>
                <option value="enviada">Enviada</option>
                <option value="programada">Programada</option>
                <option value="error">Error</option>
            </select>
            <span id="em-filtro-conteo" style="font-size:12px;color:var(--color-text-muted);white-space:nowrap"></span>
        </div>
        <div class="page-header-right">
            <button class="btn btn-accent" onclick="openNuevaCampana()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nueva Campaña
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="em-kpi-row">
        <!-- Total Campañas -->
        <a href="#" onclick="switchTab('campanas');return false;" style="background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;padding:16px 20px;transition:box-shadow .15s;text-decoration:none;display:block"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Total Campañas</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpi-campanas"><?= $kpiCampanas ?></div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D">historial completo</span>
                <span style="font-size:10px;font-weight:700;background:#E8E5DD;color:#0E0E0C;padding:2px 8px;border-radius:100px">Total</span>
            </div>
        </a>
        <!-- Emails Enviados -->
        <a href="#" onclick="switchTab('historial');return false;" style="background:#C6F24E;border:1.5px solid #A8D87A;border-radius:3px;padding:16px 20px;transition:box-shadow .15s;text-decoration:none;display:block"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:rgba(0,0,0,.55);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Emails Enviados</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpi-enviados"><?= number_format($kpiEnviados) ?></div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:rgba(0,0,0,.5)">correos despachados</span>
                <span style="font-size:10px;font-weight:700;background:rgba(0,0,0,0.12);color:#0E0E0C;padding:2px 8px;border-radius:100px">Enviados</span>
            </div>
        </a>
        <!-- Plantillas -->
        <a href="#" onclick="switchTab('plantillas');return false;" style="background:#FAFAF7;border:1.5px solid #E8E5DD;border-radius:3px;padding:16px 20px;transition:box-shadow .15s;text-decoration:none;display:block"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:#57544D;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Plantillas</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpi-plantillas"><?= $kpiPlantillas ?></div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D">guardadas</span>
                <span style="font-size:10px;font-weight:700;background:#E8E5DD;color:#0E0E0C;padding:2px 8px;border-radius:100px">Activas</span>
            </div>
        </a>
        <!-- Tasa de Entrega -->
        <?php
            $tasaBg     = ($kpiTasa < 80 && $kpiTotal > 0) ? '#F4DEDB' : '#E3F1E8';
            $tasaBorder = ($kpiTasa < 80 && $kpiTotal > 0) ? '#E8BCB8' : '#B8DEC5';
            $tasaAccent = ($kpiTasa < 80 && $kpiTotal > 0) ? '#6E211B' : '#1B5A39';
            $tasaPillBg = ($kpiTasa < 80 && $kpiTotal > 0) ? '#EFCFCC' : '#C8EAD3';
            $tasaPillCol= ($kpiTasa < 80 && $kpiTotal > 0) ? '#6E211B' : '#1B5A39';
        ?>
        <a href="#" onclick="switchTab('campanas');return false;" style="background:<?= $tasaBg ?>;border:1.5px solid <?= $tasaBorder ?>;border-radius:3px;padding:16px 20px;transition:box-shadow .15s;text-decoration:none;display:block"
            onmouseenter="this.style.boxShadow='0 2px 8px rgba(14,14,12,.08)'" onmouseleave="this.style.boxShadow='none'">
            <div style="font-size:10px;font-weight:700;color:<?= $tasaAccent ?>;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px">Tasa de Entrega</div>
            <div style="font-size:26px;font-weight:900;color:#0E0E0C;line-height:1" id="kpi-tasa"><?= $kpiTasa ?>%</div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:11px;color:#57544D">de <?= number_format($kpiTotal) ?> intentos</span>
                <span style="font-size:10px;font-weight:700;background:<?= $tasaPillBg ?>;color:<?= $tasaPillCol ?>;padding:2px 8px;border-radius:100px"><?= $kpiTasa >= 80 || $kpiTotal === 0 ? 'Óptima' : 'Revisar' ?></span>
            </div>
        </a>
    </div>

    <!-- Tabs -->
    <div class="em-tabs">
        <button class="em-tab active" onclick="switchTab('campanas')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-right:5px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Campañas
        </button>
        <button class="em-tab" onclick="switchTab('plantillas')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-right:5px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            Plantillas
        </button>
        <button class="em-tab" onclick="switchTab('historial')">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-right:5px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Historial
        </button>
    </div>

    <!-- Tab: Campañas -->
    <div class="em-tab-panel active" id="panel-campanas">
        <div class="em-card">
            <div class="em-card-head">
                <span class="em-card-title">Campañas de email</span>
                <button class="btn btn-outline btn-sm" onclick="loadCampanas()">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Actualizar
                </button>
            </div>
            <div class="em-table-wrap">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Campaña</th>
                            <th>Estado</th>
                            <th>Destinatarios</th>
                            <th>Enviados</th>
                            <th>Fallidos</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="campanas-tbody">
                        <tr><td colspan="7"><div class="em-empty"><div class="spinner" style="margin:0 auto 12px;color:#8A867C;width:24px;height:24px;border-width:3px;"></div><p>Cargando campañas…</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab: Plantillas -->
    <div class="em-tab-panel" id="panel-plantillas">
        <div class="em-card">
            <div class="em-card-head">
                <span class="em-card-title">Plantillas de email</span>
                <button class="btn btn-accent btn-sm" onclick="openTplEditor(null)">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Plantilla
                </button>
            </div>
            <div id="plantillas-container">
                <div class="em-empty">
                    <div class="spinner" style="margin:0 auto 12px;color:#8A867C;width:24px;height:24px;border-width:3px;"></div>
                    <p>Cargando plantillas…</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Historial -->
    <div class="em-tab-panel" id="panel-historial">
        <div class="em-card">
            <div class="em-card-head">
                <span class="em-card-title">Historial de envíos</span>
                <div class="em-hist-filter">
                    <label style="font-size:12px;font-weight:700;color:#57544D;">Campaña:</label>
                    <select class="form-control" id="hist-campana-sel" onchange="loadHistorial()" style="width:220px;">
                        <option value="">— Seleccionar campaña —</option>
                    </select>
                </div>
            </div>
            <div class="em-table-wrap">
                <table class="em-table">
                    <thead>
                        <tr>
                            <th>Destinatario</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Enviado</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody id="historial-tbody">
                        <tr><td colspan="5"><div class="em-empty"><svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p>Selecciona una campaña para ver el historial</p></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- .em-wrap -->

<!-- ═══════════════════════════════════════════════════════════
     MODAL: Nueva / Editar Campaña
════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalCampana">
    <div class="modal modal-md">
        <div class="modal-head">
            <h2 id="modalCampanaTitulo">Nueva Campaña</h2>
            <button class="modal-close" onclick="closeModal('modalCampana')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cp-id">
            <input type="hidden" id="cp-cuerpo">

            <div class="form-group">
                <label class="form-label">Nombre de la Campaña *</label>
                <input type="text" class="form-control" id="cp-nombre" placeholder="Ej: Promoción de verano 2026">
            </div>

            <div class="form-group">
                <label class="form-label">Usar Plantilla (opcional)</label>
                <select class="form-control" id="cp-plantilla" onchange="applyTemplate()">
                    <option value="">— Sin plantilla —</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Asunto del email *</label>
                <input type="text" class="form-control" id="cp-asunto" placeholder="Asunto que verá el destinatario">
            </div>

            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Destinatarios</label>
                <div style="display:flex;gap:6px;margin-bottom:10px">
                    <button type="button" id="btn-modo-segmento" onclick="setModoDestinatarios('segmento')"
                        style="flex:1;padding:7px 0;border-radius:4px;border:1.5px solid #C6F24E;background:#C6F24E;color:#0E0E0C;font-size:12px;font-weight:700;cursor:pointer">
                        Por segmento
                    </button>
                    <button type="button" id="btn-modo-manual" onclick="setModoDestinatarios('manual')"
                        style="flex:1;padding:7px 0;border-radius:4px;border:1.5px solid #E8E5DD;background:#fff;color:#57544D;font-size:12px;font-weight:700;cursor:pointer">
                        Selección manual
                    </button>
                </div>

                <!-- Modo segmento -->
                <div id="modo-segmento">
                    <select class="form-control" id="cp-filtro-estado" onchange="previewDestinatarios()">
                        <option value="todos">Todos los clientes</option>
                        <option value="activo">Solo activos</option>
                        <option value="en_mora">En mora</option>
                        <option value="inactivo">Inactivos</option>
                    </select>
                </div>

                <!-- Modo manual -->
                <div id="modo-manual" style="display:none">
                    <input type="text" id="cp-buscar-cliente" class="form-control" placeholder="Buscar cliente por nombre o contacto…"
                        oninput="filtrarListaClientes()" style="margin-bottom:8px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <span id="cp-sel-count" style="font-size:12px;color:#57544D">0 seleccionados</span>
                        <div style="display:flex;gap:8px">
                            <button type="button" onclick="seleccionarTodosClientes()" style="font-size:11px;color:#57544D;background:none;border:none;cursor:pointer;font-weight:700">Todos</button>
                            <button type="button" onclick="limpiarSeleccionClientes()" style="font-size:11px;color:#B0382F;background:none;border:none;cursor:pointer;font-weight:700">Limpiar</button>
                        </div>
                    </div>
                    <div id="cp-lista-clientes" style="max-height:200px;overflow-y:auto;border:1.5px solid #E8E5DD;border-radius:4px;padding:4px 0">
                        <div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px">Cargando clientes…</div>
                    </div>
                </div>
            </div>

            <div id="dest-preview-wrap">
                <div class="dest-preview loading" id="dest-preview">
                    <span class="spinner"></span> Calculando destinatarios…
                </div>
            </div>
        </div>
        <!-- Programar envío -->
        <div style="padding:0 24px 16px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:#57544D">
                <input type="checkbox" id="cp-programar" onchange="toggleProgramar()" style="width:15px;height:15px;accent-color:#0E0E0C">
                Programar envío para después
            </label>
            <div id="cp-programar-wrap" style="display:none;margin-top:10px">
                <input type="datetime-local" id="cp-programada-at" class="form-control"
                    style="max-width:280px" min="<?= date('Y-m-d\TH:i') ?>">
                <p style="font-size:11px;color:#94a3b8;margin:4px 0 0">La campaña se enviará automáticamente en la fecha y hora seleccionada.</p>
            </div>
        </div>

        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeModal('modalCampana')">Cancelar</button>
            <button class="btn btn-outline" onclick="saveCampana('borrador')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar borrador
            </button>
            <button class="btn btn-lime" id="btn-guardar-enviar" onclick="saveCampana('enviar')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span id="btn-enviar-label">Guardar y Enviar ahora</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL: Editor de Plantilla
════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalPlantilla">
    <div class="modal modal-xl">
        <div class="modal-head">
            <h2 id="modalPlantillaTitulo">Nueva Plantilla</h2>
            <button class="modal-close" onclick="closeModal('modalPlantilla')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding-bottom:8px;">
            <input type="hidden" id="tpl-id">
            <div class="tpl-editor-grid">
                <!-- Left: fields -->
                <div>
                    <div class="form-group">
                        <label class="form-label">Nombre de la plantilla *</label>
                        <input type="text" class="form-control" id="tpl-nombre" placeholder="Ej: Bienvenida a nuevos clientes">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Asunto *</label>
                        <input type="text" class="form-control" id="tpl-asunto" placeholder="Asunto del correo">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Variables disponibles (clic para insertar)</label>
                        <div class="var-chips">
                            <span class="var-chip" onclick="insertVar('{{nombre_comercial}}')">{{nombre_comercial}}</span>
                            <span class="var-chip" onclick="insertVar('{{persona_contacto}}')">{{persona_contacto}}</span>
                            <span class="var-chip" onclick="insertVar('{{email}}')">{{email}}</span>
                        </div>
                        <label class="form-label" style="margin-top:4px;">Cuerpo HTML *</label>
                        <textarea class="form-control" id="tpl-cuerpo" rows="20" style="height:520px;font-size:12px;" oninput="schedulePreview()" placeholder="HTML del email…"></textarea>
                    </div>
                </div>
                <!-- Right: preview -->
                <div>
                    <label class="form-label" style="margin-bottom:8px;">Vista previa en tiempo real</label>
                    <iframe class="tpl-preview-frame" id="tpl-preview" title="Vista previa"></iframe>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeModal('modalPlantilla')">Cancelar</button>
            <button class="btn btn-primary" onclick="savePlantilla()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Guardar Plantilla
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL: Vista Previa de Campaña
════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalPreviewCampana">
    <div class="modal modal-lg">
        <div class="modal-head">
            <div style="min-width:0;flex:1;margin-right:12px">
                <h2 id="prev-nombre" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></h2>
                <div id="prev-asunto" style="font-size:12px;color:#64748b;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
            </div>
            <button class="modal-close" onclick="closeModal('modalPreviewCampana')">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;padding-bottom:8px">
            <!-- Left: campaign info -->
            <div>
                <div id="prev-estado-wrap" style="margin-bottom:18px"></div>
                <div id="prev-fecha-wrap" style="margin-bottom:18px"></div>
                <div id="prev-dest-wrap"></div>
            </div>
            <!-- Right: email preview -->
            <div>
                <div class="form-label" style="margin-bottom:8px">Vista previa del email</div>
                <iframe id="prev-iframe" title="Vista previa del email"
                    style="width:100%;height:440px;border:1.5px solid #E8E5DD;border-radius:4px;background:#FAFAF7"></iframe>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" onclick="closeModal('modalPreviewCampana')">Cerrar</button>
            <button class="btn btn-outline" id="prev-btn-historial" onclick="prevShowHistorial()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Ver historial
            </button>
            <button class="btn btn-outline" id="prev-btn-editar" onclick="editarCampanaPreview()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar
            </button>
            <button class="btn btn-lime" id="prev-btn-enviar" onclick="enviarCampanaPreview()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Enviar ahora
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     JavaScript
════════════════════════════════════════════════════════════════ -->
<script>
const API = 'api/email_marketing.php';

// ── Default HTML template ─────────────────────────────────────────────────
const DEFAULT_TPL = `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{margin:0;padding:0;font-family:'Helvetica Neue',Arial,sans-serif;background:#f4f4f4}
  .wrapper{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)}
  .header{background:#0f172a;padding:32px 40px;text-align:center}
  .header h1{color:#c9f31d;margin:0;font-size:26px;font-weight:800;letter-spacing:-.02em}
  .header p{color:rgba(255,255,255,.55);margin:6px 0 0;font-size:13px}
  .body{padding:36px 40px}
  .body h2{color:#0f172a;font-size:20px;font-weight:800;margin:0 0 16px}
  .body p{color:#475569;font-size:14px;line-height:1.7;margin:0 0 16px}
  .cta{display:inline-block;background:#0f172a;color:#c9f31d;padding:13px 28px;border-radius:10px;font-weight:800;font-size:14px;text-decoration:none;margin:8px 0 24px}
  .divider{border:none;border-top:1.5px solid #f1f5f9;margin:24px 0}
  .footer{background:#f8fafc;padding:24px 40px;text-align:center;color:#94a3b8;font-size:11px}
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>QUANTUN Digital</h1>
    <p>Tu agencia de marketing digital</p>
  </div>
  <div class="body">
    <h2>Hola, {{persona_contacto}} 👋</h2>
    <p>Esperamos que todo marche excelente en <strong>{{nombre_comercial}}</strong>.</p>
    <p>Tenemos novedades especiales para ti. Haz clic a continuación para conocer más:</p>
    <a href="#" class="cta">Ver oferta exclusiva</a>
    <hr class="divider">
    <p style="font-size:13px;color:#94a3b8">Este correo fue enviado a <strong>{{email}}</strong>. Si no deseas recibir más comunicaciones, responde a este correo.</p>
  </div>
  <div class="footer">
    &copy; 2026 QUANTUN Digital &mdash; Todos los derechos reservados<br>
    <a href="#" style="color:#94a3b8">Darse de baja</a>
  </div>
</div>
</body>
</html>`;

// ── State ─────────────────────────────────────────────────────────────────
let campanas    = [];
let plantillas  = [];
let destCount   = 0;
let destTimer   = null;
let previewTimer = null;

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadCampanas();
    loadPlantillas();
});

// ── Tab switching ─────────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.em-tab').forEach((t, i) => {
        const names = ['campanas','plantillas','historial'];
        t.classList.toggle('active', names[i] === tab);
    });
    document.querySelectorAll('.em-tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    if (tab === 'historial') populateHistCampanas();
}

// ── Modal helpers ─────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('show'); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
});

// ── Load Campañas ─────────────────────────────────────────────────────────
let _autoReloadTimer = null;

async function loadCampanas() {
    try {
        const r = await fetch(API + '?resource=campanas');
        const d = await r.json();
        campanas = d.data || [];
        renderCampanas();
        refreshKpis();

        // Si hay campañas programadas con hora vencida o en estado "enviando",
        // recargar automáticamente en 8 segundos para reflejar el resultado del envío
        const now = Date.now();
        const needsRefresh = campanas.some(c => {
            if (c.estado === 'enviando') return true;
            if (c.estado === 'programada' && c.programada_at) {
                return new Date(c.programada_at).getTime() <= now;
            }
            return false;
        });
        clearTimeout(_autoReloadTimer);
        if (needsRefresh) {
            _autoReloadTimer = setTimeout(() => loadCampanas(), 8000);
        }
    } catch(e) {
        document.getElementById('campanas-tbody').innerHTML =
            '<tr><td colspan="7"><div class="em-empty"><p>Error al cargar las campañas</p></div></td></tr>';
    }
}

function renderCampanas() {
    const tbody = document.getElementById('campanas-tbody');
    if (!campanas.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="em-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><p>No hay campañas. ¡Crea la primera!</p></div></td></tr>';
        return;
    }

    const q      = (document.getElementById('em-filtro-nombre')?.value || '').toLowerCase().trim();
    const estado = document.getElementById('em-filtro-estado')?.value || 'todos';

    const filtradas = campanas.filter(c => {
        const matchQ = !q || c.nombre.toLowerCase().includes(q) || (c.asunto || '').toLowerCase().includes(q);
        const matchE = estado === 'todos' || c.estado === estado;
        return matchQ && matchE;
    });

    const conteo = document.getElementById('em-filtro-conteo');
    if (conteo) conteo.textContent = filtradas.length + ' de ' + campanas.length + ' campañas';

    if (!filtradas.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="em-empty"><p>No hay campañas con los filtros aplicados.</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = filtradas.map(c => {
        const badgeClass = { borrador:'badge-gray', enviando:'badge-blue', enviada:'badge-green', error:'badge-red', programada:'badge-purple' }[c.estado] || 'badge-gray';
        const badgeLabel = { borrador:'Borrador', enviando:'Enviando…', enviada:'Enviada', error:'Error', programada:'Programada' }[c.estado] || c.estado;
        const fechaStr = c.enviada_at
            ? `<span title="Enviada">${fmtDate(c.enviada_at)}</span>`
            : c.programada_at
                ? `<span style="color:#7c3aed;font-weight:600" title="Programada">⏰ ${fmtDate(c.programada_at)}</span>`
                : `<span style="color:#94a3b8">${fmtDate(c.created_at)}</span>`;
        const canSend = (c.estado === 'borrador' || c.estado === 'error' || c.estado === 'programada');
        const canEdit = (c.estado === 'borrador' || c.estado === 'programada' || c.estado === 'error');
        const pct = c.total > 0 ? Math.round((c.enviados / c.total) * 100) : 0;
        return `<tr>
            <td>
                <div class="name">${escHtml(c.nombre)}</div>
                <div class="sub">${escHtml(c.asunto)}</div>
            </td>
            <td><span class="badge ${badgeClass}">${badgeLabel}</span></td>
            <td>${c.total || '—'}</td>
            <td>
                ${c.enviados > 0
                    ? `<span style="color:#15803d;font-weight:700">${c.enviados}</span>${c.total > 0 ? `<span style="color:#94a3b8;font-size:11px;margin-left:4px">(${pct}%)</span>` : ''}`
                    : '<span style="color:#94a3b8">—</span>'}
            </td>
            <td>${c.fallidos > 0 ? `<span style="color:#dc2626;font-weight:700">${c.fallidos}</span>` : '<span style="color:#94a3b8">—</span>'}</td>
            <td style="white-space:nowrap;color:#64748b;font-size:12px">${fechaStr}</td>
            <td>
                <div class="em-actions">
                    <button class="btn-icon" title="Vista previa" onclick="previewCampana(${c.id})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    ${canEdit ? `<button class="btn-icon" title="Editar campaña" onclick="editarCampana(${c.id})">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>` : ''}
                    ${c.estado === 'programada' ? `<button class="btn-icon" title="Cancelar programación → Borrador" onclick="changeEstadoCampana(${c.id}, 'borrador', 'Cancelar la programación y mover a borrador')">
                        <svg width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l4 4m0-4l-4 4"/></svg>
                    </button>` : ''}
                    ${c.estado === 'error' ? `<button class="btn-icon" title="Resetear a Borrador" onclick="changeEstadoCampana(${c.id}, 'borrador', 'Resetear campaña a borrador para reintentar')">
                        <svg width="14" height="14" fill="none" stroke="#dc2626" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>` : ''}
                    <button class="btn-icon send" title="Enviar campaña" onclick="sendCampana(${c.id})" ${!canSend ? 'disabled' : ''}>
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                    <button class="btn-icon danger" title="Eliminar" onclick="deleteCampana(${c.id}, '${escHtml(c.nombre).replace(/'/g,"\\'")}')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Load Plantillas ───────────────────────────────────────────────────────
async function loadPlantillas() {
    try {
        const r = await fetch(API + '?resource=plantillas');
        const d = await r.json();
        plantillas = d.data || [];
        renderPlantillas();
        populatePlantillaSelects();
    } catch(e) {
        document.getElementById('plantillas-container').innerHTML =
            '<div class="em-empty"><p>Error al cargar las plantillas</p></div>';
    }
}

function renderPlantillas() {
    const container = document.getElementById('plantillas-container');
    if (!plantillas.length) {
        container.innerHTML = '<div class="em-empty"><svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg><p>No hay plantillas. ¡Crea la primera!</p></div>';
        return;
    }
    container.innerHTML = '<div class="em-tpl-grid">' + plantillas.map(t =>
        `<div class="em-tpl-card">
            <div class="tpl-name">${escHtml(t.nombre)}</div>
            <div class="tpl-asunto">${escHtml(t.asunto)}</div>
            <div class="tpl-date">${fmtDate(t.updated_at)}</div>
            <div class="tpl-actions">
                <button class="btn btn-outline btn-sm" style="flex:1" onclick="openTplEditor(${t.id})">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar
                </button>
                <button class="btn btn-outline btn-sm" style="flex:1" onclick="usarPlantilla(${t.id})" title="Usar en nueva campaña">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Usar
                </button>
                <button class="btn-icon danger" title="Eliminar" onclick="deletePlantilla(${t.id}, '${escHtml(t.nombre).replace(/'/g,"\\'")}')">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>`
    ).join('') + '</div>';
}

function populatePlantillaSelects() {
    const sel = document.getElementById('cp-plantilla');
    const cur = sel.value;
    sel.innerHTML = '<option value="">— Sin plantilla —</option>';
    plantillas.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.nombre;
        sel.appendChild(opt);
    });
    if (cur) sel.value = cur;
}

// ── Nueva Campaña modal ───────────────────────────────────────────────────
function openNuevaCampana(plantillaId) {
    document.getElementById('cp-id').value      = '';
    document.getElementById('cp-nombre').value  = '';
    document.getElementById('cp-asunto').value  = '';
    document.getElementById('cp-cuerpo').value  = '';
    document.getElementById('cp-filtro-estado').value = 'todos';
    document.getElementById('cp-programar').checked = false;
    document.getElementById('cp-programar-wrap').style.display = 'none';
    document.getElementById('btn-enviar-label').textContent = 'Guardar y Enviar ahora';
    document.getElementById('modalCampanaTitulo').textContent = 'Nueva Campaña';
    // Reset destinatarios mode
    clientesSeleccionados.clear();
    todosClientes = [];
    modoDestinatarios = 'segmento';
    setModoDestinatarios('segmento');
    populatePlantillaSelects();

    if (plantillaId) {
        document.getElementById('cp-plantilla').value = plantillaId;
        applyTemplate();
    } else {
        document.getElementById('cp-plantilla').value = '';
    }
    previewDestinatarios();
    openModal('modalCampana');
}

function applyTemplate() {
    const sel = document.getElementById('cp-plantilla');
    const id  = parseInt(sel.value);
    if (!id) return;
    const tpl = plantillas.find(t => t.id === id);
    if (!tpl) return;
    document.getElementById('cp-asunto').value = tpl.asunto;
    // store cuerpo in a data attribute for use when saving
    sel.dataset.cuerpo = tpl.cuerpo;
}

function usarPlantilla(id) {
    switchTab('campanas');
    openNuevaCampana(id);
}

// ── Modo destinatarios ────────────────────────────────────────────────────
let modoDestinatarios = 'segmento';
let todosClientes = [];
let clientesSeleccionados = new Set();

function setModoDestinatarios(modo) {
    modoDestinatarios = modo;
    document.getElementById('modo-segmento').style.display = modo === 'segmento' ? '' : 'none';
    document.getElementById('modo-manual').style.display   = modo === 'manual'   ? '' : 'none';
    const btnSeg = document.getElementById('btn-modo-segmento');
    const btnMan = document.getElementById('btn-modo-manual');
    if (modo === 'segmento') {
        btnSeg.style.cssText = 'flex:1;padding:7px 0;border-radius:3px;border:1.5px solid #C6F24E;background:#C6F24E;color:#0E0E0C;font-size:12px;font-weight:700;cursor:pointer';
        btnMan.style.cssText = 'flex:1;padding:7px 0;border-radius:3px;border:1.5px solid #E8E5DD;background:#fff;color:#57544D;font-size:12px;font-weight:700;cursor:pointer';
        previewDestinatarios();
    } else {
        btnMan.style.cssText = 'flex:1;padding:7px 0;border-radius:3px;border:1.5px solid #C6F24E;background:#C6F24E;color:#0E0E0C;font-size:12px;font-weight:700;cursor:pointer';
        btnSeg.style.cssText = 'flex:1;padding:7px 0;border-radius:3px;border:1.5px solid #E8E5DD;background:#fff;color:#57544D;font-size:12px;font-weight:700;cursor:pointer';
        cargarListaClientes();
    }
}

async function cargarListaClientes() {
    if (todosClientes.length) { renderListaClientes(); return; }
    document.getElementById('cp-lista-clientes').innerHTML = '<div style="padding:16px;text-align:center;color:#94a3b8;font-size:13px">Cargando…</div>';
    try {
        const r = await fetch(`${API}?resource=destinatarios&filtro_estado=todos`);
        const d = await r.json();
        todosClientes = d.data || [];
        renderListaClientes();
    } catch(e) {
        document.getElementById('cp-lista-clientes').innerHTML = '<div style="padding:16px;text-align:center;color:#ef4444;font-size:13px">Error al cargar</div>';
    }
}

function renderListaClientes() {
    const q = (document.getElementById('cp-buscar-cliente')?.value || '').toLowerCase();
    const filtered = todosClientes.filter(c =>
        c.nombre_comercial.toLowerCase().includes(q) ||
        (c.persona_contacto || '').toLowerCase().includes(q)
    );
    if (!filtered.length) {
        document.getElementById('cp-lista-clientes').innerHTML = '<div style="padding:16px;text-align:center;color:#94a3b8;font-size:13px">Sin resultados</div>';
        return;
    }
    document.getElementById('cp-lista-clientes').innerHTML = filtered.map(c => `
        <label style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s"
            onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background=''">
            <input type="checkbox" value="${c.id}" ${clientesSeleccionados.has(c.id) ? 'checked' : ''}
                onchange="toggleClienteSeleccion(${c.id})"
                style="width:15px;height:15px;accent-color:#0E0E0C;cursor:pointer;flex-shrink:0">
            <div style="min-width:0;flex:1">
                <div style="font-size:13px;font-weight:600;color:var(--color-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(c.nombre_comercial)}</div>
                ${c.persona_contacto ? `<div style="font-size:11px;color:var(--color-text-muted)">${escHtml(c.persona_contacto)}</div>` : ''}
            </div>
            <div style="font-size:11px;color:var(--color-text-light);flex-shrink:0;max-width:140px;overflow:hidden;text-overflow:ellipsis">${escHtml(c.email_facturacion)}</div>
        </label>`).join('');
    actualizarContadorSeleccion();
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

function toggleClienteSeleccion(id) {
    if (clientesSeleccionados.has(id)) clientesSeleccionados.delete(id);
    else clientesSeleccionados.add(id);
    actualizarContadorSeleccion();
    actualizarChipManual();
}

function actualizarContadorSeleccion() {
    const n = clientesSeleccionados.size;
    document.getElementById('cp-sel-count').textContent = `${n} seleccionado${n !== 1 ? 's' : ''}`;
}

function filtrarListaClientes() { renderListaClientes(); }

function seleccionarTodosClientes() {
    const q = (document.getElementById('cp-buscar-cliente')?.value || '').toLowerCase();
    todosClientes.filter(c =>
        c.nombre_comercial.toLowerCase().includes(q) || (c.persona_contacto||'').toLowerCase().includes(q)
    ).forEach(c => clientesSeleccionados.add(c.id));
    renderListaClientes();
    actualizarChipManual();
}

function limpiarSeleccionClientes() {
    clientesSeleccionados.clear();
    renderListaClientes();
    actualizarChipManual();
}

function actualizarChipManual() {
    const n = clientesSeleccionados.size;
    const chip = document.getElementById('dest-preview');
    chip.className = 'dest-preview' + (n === 0 ? ' zero' : '');
    chip.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        ${n} destinatario${n !== 1 ? 's' : ''} seleccionado${n !== 1 ? 's' : ''}`;
    destCount = n;
}

// Destinatarios preview (segmento mode)
function previewDestinatarios() {
    if (modoDestinatarios !== 'segmento') return;
    clearTimeout(destTimer);
    const chip = document.getElementById('dest-preview');
    chip.className = 'dest-preview loading';
    chip.innerHTML = '<span class="spinner"></span> Calculando…';
    destTimer = setTimeout(fetchDestCount, 400);
}

async function fetchDestCount() {
    const estado = document.getElementById('cp-filtro-estado').value;
    try {
        const r = await fetch(`${API}?resource=destinatarios&filtro_estado=${encodeURIComponent(estado)}`);
        const d = await r.json();
        destCount = d.total || 0;
        const chip = document.getElementById('dest-preview');
        chip.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            ${destCount} destinatario${destCount !== 1 ? 's' : ''} encontrado${destCount !== 1 ? 's' : ''}`;
        chip.className = 'dest-preview' + (destCount === 0 ? ' zero' : '');
    } catch(e) {
        const chip = document.getElementById('dest-preview');
        chip.className = 'dest-preview zero';
        chip.innerHTML = 'Error al calcular destinatarios';
    }
}

function toggleProgramar() {
    const chk  = document.getElementById('cp-programar').checked;
    document.getElementById('cp-programar-wrap').style.display = chk ? '' : 'none';
    document.getElementById('btn-enviar-label').textContent = chk ? 'Guardar programada' : 'Guardar y Enviar ahora';
}

// Save campaign  (mode: 'borrador' | 'enviar')
async function saveCampana(mode) {
    const id          = document.getElementById('cp-id').value;
    const nombre      = document.getElementById('cp-nombre').value.trim();
    const asunto      = document.getElementById('cp-asunto').value.trim();
    const filtroEstado= document.getElementById('cp-filtro-estado').value;
    const tplSel      = document.getElementById('cp-plantilla');
    const plantillaId = tplSel.value ? parseInt(tplSel.value) : null;
    const programar   = document.getElementById('cp-programar').checked;
    const programadaAt= programar ? document.getElementById('cp-programada-at').value : null;

    let cuerpo = '';
    if (plantillaId) {
        const tpl = plantillas.find(t => t.id === plantillaId);
        cuerpo = tpl ? tpl.cuerpo : '';
    }
    if (!cuerpo) cuerpo = document.getElementById('cp-cuerpo').value || DEFAULT_TPL;

    if (!nombre) { showToast('Ingresa el nombre de la campaña', 'warning'); return; }
    if (!asunto)  { showToast('Ingresa el asunto del email', 'warning'); return; }
    if (programar && !programadaAt) { showToast('Selecciona la fecha y hora de envío', 'warning'); return; }

    // Collect selected client IDs (manual mode)
    const selectedIds = modoDestinatarios === 'manual' ? [...clientesSeleccionados] : [];
    if (modoDestinatarios === 'manual' && selectedIds.length === 0) {
        showToast('Selecciona al menos un cliente', 'warning'); return;
    }

    const payload = {
        action: 'save_campana', nombre, asunto, cuerpo,
        filtro_estado: filtroEstado, filtro_servicio: '',
        plantilla_id: plantillaId,
        clientes_ids: selectedIds,
        programada_at: programadaAt || null
    };
    if (id) payload.id = parseInt(id);

    try {
        const r = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if (!d.ok) { showToast(d.error || 'Error al guardar', 'error'); return; }

        closeModal('modalCampana');
        await loadCampanas();

        if (programar) {
            showToast('Campaña programada correctamente', 'success');
        } else if (mode === 'enviar') {
            await sendCampana(d.id, selectedIds);
        } else {
            showToast('Campaña guardada como borrador', 'success');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}

// Send campaign — selectedIds is the manual selection from the modal (passed as backup)
async function sendCampana(id, selectedIds = []) {
    const camp = campanas.find(c => c.id === id);
    const n = selectedIds.length || (camp && camp.total > 0 ? camp.total : destCount);
    const totalHint = n > 0 ? `${n} destinatario${n !== 1 ? 's' : ''}` : 'los destinatarios';

    const ok = await confirmAction(`¿Deseas enviar esta campaña a ${totalHint}? Esta acción no se puede deshacer.`, {
        title: '¿Enviar campaña?',
        okText: 'Sí, enviar ahora',
        okColor: '#0f172a',
        okHover: '#2A2926'
    });
    if (!ok) return;

    showToast('Enviando campaña… esto puede tardar unos segundos', 'info', 15000);

    try {
        const body = { action: 'enviar', campana_id: id };
        if (selectedIds.length) body.clientes_ids = selectedIds;  // authoritative override
        const r = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(body)
        });
        const d = await r.json();
        if (!d.ok) {
            showToast(d.error || 'Error al enviar la campaña', 'error');
            await loadCampanas();
            return;
        }
        const msg = `${d.enviados} enviado${d.enviados !== 1 ? 's' : ''}${d.fallidos > 0 ? `, ${d.fallidos} fallido${d.fallidos !== 1 ? 's' : ''}` : ''}`;
        showToast(msg, d.fallidos === d.total ? 'error' : d.fallidos > 0 ? 'warning' : 'success', 6000);
        await loadCampanas();
    } catch(e) {
        showToast('Error de conexión al enviar', 'error');
    }
}

// Change campaign status
async function changeEstadoCampana(id, nuevoEstado, mensaje) {
    const labels = { borrador: 'Borrador', programada: 'Programada' };
    const ok = await confirmAction(mensaje, {
        title: 'Cambiar estado',
        okText: `Mover a ${labels[nuevoEstado]}`,
        okColor: '#0f172a',
        okHover: '#2A2926'
    });
    if (!ok) return;
    try {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_estado', campana_id: id, estado: nuevoEstado })
        });
        const d = await r.json();
        if (d.ok) {
            showToast(`Estado cambiado a ${labels[nuevoEstado]}`, 'success');
            await loadCampanas();
        } else {
            showToast(d.error || 'Error al cambiar estado', 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}

// Delete campaign
async function deleteCampana(id, nombre) {
    const ok = await confirmAction(`¿Eliminar la campaña "${nombre}"? También se eliminará su historial de envíos.`, { title: 'Eliminar campaña' });
    if (!ok) return;
    try {
        const r = await fetch(`${API}?resource=campanas&id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (d.ok) { showToast('Campaña eliminada', 'success'); await loadCampanas(); }
        else showToast(d.error || 'Error al eliminar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── Template editor ───────────────────────────────────────────────────────
function openTplEditor(id) {
    const tpl = id ? plantillas.find(t => t.id === id) : null;
    document.getElementById('tpl-id').value = id || '';
    document.getElementById('tpl-nombre').value = tpl ? tpl.nombre : '';
    document.getElementById('tpl-asunto').value  = tpl ? tpl.asunto : '';
    document.getElementById('tpl-cuerpo').value  = tpl ? tpl.cuerpo : DEFAULT_TPL;
    document.getElementById('modalPlantillaTitulo').textContent = tpl ? 'Editar Plantilla' : 'Nueva Plantilla';
    updatePreview();
    openModal('modalPlantilla');
}

function insertVar(v) {
    const ta = document.getElementById('tpl-cuerpo');
    const start = ta.selectionStart;
    const end   = ta.selectionEnd;
    ta.value = ta.value.slice(0, start) + v + ta.value.slice(end);
    ta.selectionStart = ta.selectionEnd = start + v.length;
    ta.focus();
    schedulePreview();
}

function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(updatePreview, 500);
}

function updatePreview() {
    const iframe = document.getElementById('tpl-preview');
    const html   = document.getElementById('tpl-cuerpo').value
        .replace(/\{\{nombre_comercial\}\}/g, 'Empresa Demo S.A.S')
        .replace(/\{\{persona_contacto\}\}/g, 'Juan Pérez')
        .replace(/\{\{email\}\}/g,            'demo@empresa.com');
    try {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open(); doc.write(html); doc.close();
    } catch(e) {}
}

async function savePlantilla() {
    const id     = document.getElementById('tpl-id').value;
    const nombre = document.getElementById('tpl-nombre').value.trim();
    const asunto = document.getElementById('tpl-asunto').value.trim();
    const cuerpo = document.getElementById('tpl-cuerpo').value.trim();

    if (!nombre) { showToast('Ingresa el nombre de la plantilla', 'warning'); return; }
    if (!asunto) { showToast('Ingresa el asunto', 'warning'); return; }
    if (!cuerpo) { showToast('El cuerpo no puede estar vacío', 'warning'); return; }

    const payload = { action: 'save_plantilla', nombre, asunto, cuerpo };
    if (id) payload.id = parseInt(id);

    try {
        const r = await fetch(API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const d = await r.json();
        if (d.ok) {
            showToast('Plantilla guardada', 'success');
            closeModal('modalPlantilla');
            await loadPlantillas();
            refreshKpis();
        } else {
            showToast(d.error || 'Error al guardar', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }
}

async function deletePlantilla(id, nombre) {
    const ok = await confirmAction(`¿Eliminar la plantilla "${nombre}"?`, { title: 'Eliminar plantilla' });
    if (!ok) return;
    try {
        const r = await fetch(`${API}?resource=plantillas&id=${id}`, { method: 'DELETE' });
        const d = await r.json();
        if (d.ok) { showToast('Plantilla eliminada', 'success'); await loadPlantillas(); refreshKpis(); }
        else showToast(d.error || 'Error al eliminar', 'error');
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── Vista previa de campaña ───────────────────────────────────────────────
let _previewCampanaId = null;

async function previewCampana(id) {
    _previewCampanaId = id;
    const c = campanas.find(c => c.id === id);
    if (!c) return;

    document.getElementById('prev-nombre').textContent = c.nombre;
    document.getElementById('prev-asunto').textContent = 'Asunto: ' + c.asunto;

    // Status
    const badgeClass = { borrador:'badge-gray', enviando:'badge-blue', enviada:'badge-green', error:'badge-red', programada:'badge-purple' }[c.estado] || 'badge-gray';
    const badgeLabel = { borrador:'Borrador', enviando:'Enviando…', enviada:'Enviada', error:'Error', programada:'Programada' }[c.estado] || c.estado;
    document.getElementById('prev-estado-wrap').innerHTML =
        `<div class="form-label">Estado</div><span class="badge ${badgeClass}">${badgeLabel}</span>`;

    // Date
    let fechaHtml = '';
    if (c.programada_at) {
        const dt = new Date(c.programada_at.replace(' ', 'T'));
        const dStr = dt.toLocaleDateString('es-CO', { weekday:'long', day:'2-digit', month:'long', year:'numeric' });
        const tStr = dt.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
        fechaHtml = `<div class="form-label">Fecha programada</div>
            <div style="font-size:13px;color:#7c3aed;font-weight:600">⏰ ${dStr} a las ${tStr}</div>`;
    } else if (c.enviada_at) {
        const dt = new Date(c.enviada_at.replace(' ', 'T'));
        const dStr = dt.toLocaleDateString('es-CO', { day:'2-digit', month:'long', year:'numeric' });
        const tStr = dt.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
        fechaHtml = `<div class="form-label">Enviada</div>
            <div style="font-size:13px;color:#15803d;font-weight:600">✓ ${dStr} a las ${tStr}</div>`;
    } else {
        fechaHtml = `<div class="form-label">Creada</div>
            <div style="font-size:13px;color:#64748b">${fmtDate(c.created_at)}</div>`;
    }
    document.getElementById('prev-fecha-wrap').innerHTML = fechaHtml;

    // Recipients
    let destHtml = '';
    if (c.clientes_ids) {
        let ids;
        try { ids = JSON.parse(c.clientes_ids); } catch(e) { ids = []; }
        if (!todosClientes.length) {
            try {
                const r = await fetch(`${API}?resource=destinatarios&filtro_estado=todos`);
                const dd = await r.json();
                todosClientes = dd.data || [];
            } catch(e) {}
        }
        const names = todosClientes.filter(cl => ids.includes(cl.id));
        destHtml = `<div class="form-label">Destinatarios seleccionados (${ids.length})</div>
            <div style="max-height:180px;overflow-y:auto;border:1.5px solid var(--color-border);border-radius:var(--radius-sm)">
            ${(names.length ? names : ids.map(i => ({ nombre_comercial: `Cliente #${i}`, email_facturacion: '' }))).map(cl =>
                `<div style="padding:7px 12px;font-size:12px;color:var(--color-text);border-bottom:1px solid var(--color-border-light);display:flex;justify-content:space-between;gap:8px">
                    <span style="font-weight:600">${escHtml(cl.nombre_comercial)}</span>
                    <span style="color:var(--color-text-light);font-size:11px;flex-shrink:0">${escHtml(cl.email_facturacion || '')}</span>
                </div>`).join('')}
            </div>`;
    } else {
        const filtroLabel = { todos:'Todos los clientes', activo:'Solo activos', en_mora:'En mora', inactivo:'Inactivos' }[c.filtro_estado] || c.filtro_estado || 'Todos';
        destHtml = `<div class="form-label">Segmento</div>
            <div style="font-size:13px;font-weight:600;color:#334155">${filtroLabel}</div>`;
        if (c.total > 0) destHtml += `<div style="font-size:12px;color:#64748b;margin-top:4px">${c.total} destinatarios</div>`;
    }
    if (parseInt(c.enviados) > 0 || parseInt(c.fallidos) > 0) {
        destHtml += `<div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
            <div style="background:#f0fdf4;border-radius:8px;padding:8px 14px;font-size:12px;color:#15803d;font-weight:700">✓ ${c.enviados} enviados</div>
            ${parseInt(c.fallidos) > 0 ? `<div style="background:#fef2f2;border-radius:8px;padding:8px 14px;font-size:12px;color:#dc2626;font-weight:700">✗ ${c.fallidos} fallidos</div>` : ''}
        </div>`;
    }
    document.getElementById('prev-dest-wrap').innerHTML = destHtml;

    // Email preview iframe
    const html = (c.cuerpo || '')
        .replace(/\{\{nombre_comercial\}\}/g, 'Empresa Demo S.A.S')
        .replace(/\{\{persona_contacto\}\}/g, 'Juan Pérez')
        .replace(/\{\{email\}\}/g, 'demo@empresa.com');
    const iframe = document.getElementById('prev-iframe');
    try {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open(); doc.write(html); doc.close();
    } catch(e) {}

    // Button visibility
    const canSend   = ['borrador','error','programada'].includes(c.estado);
    const canEdit   = ['borrador','programada','error'].includes(c.estado);
    const hasSent   = parseInt(c.enviados) > 0 || parseInt(c.fallidos) > 0;
    document.getElementById('prev-btn-enviar').style.display   = canSend ? '' : 'none';
    document.getElementById('prev-btn-editar').style.display   = canEdit ? '' : 'none';
    document.getElementById('prev-btn-historial').style.display = hasSent ? '' : 'none';

    openModal('modalPreviewCampana');
}

function prevShowHistorial() {
    closeModal('modalPreviewCampana');
    showHistorial(_previewCampanaId);
}

function editarCampanaPreview() {
    closeModal('modalPreviewCampana');
    editarCampana(_previewCampanaId);
}

function enviarCampanaPreview() {
    closeModal('modalPreviewCampana');
    const c = campanas.find(c => c.id === _previewCampanaId);
    let savedIds = [];
    if (c && c.clientes_ids) {
        try { savedIds = JSON.parse(c.clientes_ids); } catch(e) {}
    }
    sendCampana(_previewCampanaId, savedIds);
}

async function editarCampana(id) {
    const c = campanas.find(c => c.id === id);
    if (!c) return;

    document.getElementById('cp-id').value     = c.id;
    document.getElementById('cp-nombre').value = c.nombre;
    document.getElementById('cp-asunto').value = c.asunto;
    document.getElementById('cp-cuerpo').value = c.cuerpo || '';
    document.getElementById('cp-filtro-estado').value = c.filtro_estado || 'todos';
    document.getElementById('modalCampanaTitulo').textContent = 'Editar Campaña';

    populatePlantillaSelects();
    document.getElementById('cp-plantilla').value = c.plantilla_id || '';

    // Scheduling
    const programar = !!c.programada_at;
    document.getElementById('cp-programar').checked = programar;
    document.getElementById('cp-programar-wrap').style.display = programar ? '' : 'none';
    document.getElementById('btn-enviar-label').textContent = programar ? 'Guardar programada' : 'Guardar y Enviar ahora';
    if (programar && c.programada_at) {
        document.getElementById('cp-programada-at').value = c.programada_at.replace(' ', 'T').slice(0, 16);
    }

    // Recipients mode
    clientesSeleccionados.clear();
    if (c.clientes_ids) {
        let ids;
        try { ids = JSON.parse(c.clientes_ids); } catch(e) { ids = []; }
        if (!todosClientes.length) {
            try {
                const r = await fetch(`${API}?resource=destinatarios&filtro_estado=todos`);
                const dd = await r.json();
                todosClientes = dd.data || [];
            } catch(e) {}
        }
        ids.forEach(i => clientesSeleccionados.add(i));
        modoDestinatarios = 'manual';
        setModoDestinatarios('manual');
    } else {
        modoDestinatarios = 'segmento';
        setModoDestinatarios('segmento');
    }

    openModal('modalCampana');
}

// ── Historial ─────────────────────────────────────────────────────────────
function populateHistCampanas() {
    const sel = document.getElementById('hist-campana-sel');
    const cur = sel.value;
    sel.innerHTML = '<option value="">— Seleccionar campaña —</option>';
    campanas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nombre + ' (' + (c.enviada_at ? fmtDate(c.enviada_at) : 'borrador') + ')';
        sel.appendChild(opt);
    });
    if (cur) { sel.value = cur; loadHistorial(); }
}

function showHistorial(campanaId) {
    switchTab('historial');
    populateHistCampanas();
    const sel = document.getElementById('hist-campana-sel');
    sel.value = campanaId;
    loadHistorial();
}

async function loadHistorial() {
    const id = document.getElementById('hist-campana-sel').value;
    const tbody = document.getElementById('historial-tbody');
    if (!id) {
        tbody.innerHTML = '<tr><td colspan="5"><div class="em-empty"><p>Selecciona una campaña para ver el historial</p></div></td></tr>';
        return;
    }
    tbody.innerHTML = '<tr><td colspan="5"><div class="em-empty"><div class="spinner" style="margin:0 auto 12px;color:#8A867C;width:24px;height:24px;border-width:3px;"></div><p>Cargando…</p></div></td></tr>';
    try {
        const r = await fetch(`${API}?resource=historial&campana_id=${id}`);
        const d = await r.json();
        const rows = d.data || [];
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="5"><div class="em-empty"><p>Sin envíos registrados para esta campaña</p></div></td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(e => {
            const badge = {
                pendiente: '<span class="badge badge-gray">Pendiente</span>',
                enviado:   '<span class="badge badge-green">Enviado</span>',
                fallido:   '<span class="badge badge-red">Fallido</span>'
            }[e.estado] || '<span class="badge badge-gray">' + e.estado + '</span>';
            return `<tr>
                <td>
                    <div class="name">${escHtml(e.nombre_dest || e.nombre_comercial || '—')}</div>
                </td>
                <td style="color:#64748b;font-size:13px">${escHtml(e.email)}</td>
                <td>${badge}</td>
                <td style="color:#64748b;font-size:12px;white-space:nowrap">${e.enviado_at ? fmtDate(e.enviado_at) : '—'}</td>
                <td style="font-size:11px;color:#ef4444;max-width:200px;word-break:break-word">${e.error_msg ? escHtml(e.error_msg) : '<span style="color:#94a3b8">—</span>'}</td>
            </tr>`;
        }).join('');
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="5"><div class="em-empty"><p>Error al cargar historial</p></div></td></tr>';
    }
}

// ── KPI refresh ───────────────────────────────────────────────────────────
function refreshKpis() {
    const totalCamp  = campanas.length;
    const totalEnv   = campanas.reduce((a, c) => a + (parseInt(c.enviados) || 0), 0);
    const totalAll   = campanas.reduce((a, c) => a + (parseInt(c.total) || 0), 0);
    const tasa       = totalAll > 0 ? (totalEnv / totalAll * 100).toFixed(1) : 0;
    const tplCount   = plantillas.length;

    const el = id => document.getElementById(id);
    if (el('kpi-campanas'))   el('kpi-campanas').textContent   = totalCamp;
    if (el('kpi-enviados'))   el('kpi-enviados').textContent   = totalEnv.toLocaleString('es-CO');
    if (el('kpi-plantillas')) el('kpi-plantillas').textContent = tplCount;
    if (el('kpi-tasa'))       el('kpi-tasa').textContent       = tasa + '%';
}

// ── Utilities ─────────────────────────────────────────────────────────────
function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d.replace(' ', 'T'));
    return dt.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
