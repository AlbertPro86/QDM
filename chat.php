<?php
/**
 * CRM QUANTUN Digital — Chat en Vivo
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireAuth();

$pageTitle      = 'Chat en Vivo';
$pageBreadcrumb = '<a href="dashboard.php" style="color:inherit;text-decoration:none;opacity:.65;transition:opacity .15s" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=.65">Dashboard</a>'
    . '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" style="vertical-align:middle;margin:0 4px;opacity:.4"><path d="M9 5l7 7-7 7"/></svg>'
    . '<span style="font-weight:700;color:var(--color-text)">Chat en Vivo</span>';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="margin-bottom:16px">
    <div class="page-header-left">
        <h2 style="font-size:15px;color:var(--color-text-muted);font-weight:500">Conversaciones en tiempo real desde el sitio web</h2>
    </div>
    <div class="page-header-right" style="display:flex;gap:8px;align-items:center">
        <select id="chatFilterStatus" class="form-control" style="width:auto;font-size:13px;padding:6px 12px;border-radius:8px">
            <option value="activo">Activos</option>
            <option value="cerrado">Cerrados</option>
        </select>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted)">
            <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;box-shadow:0 0 0 2px rgba(34,197,94,.2)"></span> En línea
        </span>
    </div>
</div>

<!-- ═══ LAYOUT PRINCIPAL ═══ -->
<div class="chat-layout" id="chatLayout">

    <!-- ── Panel izquierdo: lista de sesiones ── -->
    <div class="chat-sessions">
        <div class="chat-sessions__list" id="chatSessionList">
            <p style="padding:40px 16px;text-align:center;color:var(--color-text-muted);font-size:13px">Cargando chats...</p>
        </div>
    </div>

    <!-- ── Panel central: conversación ── -->
    <div class="chat-conversation">

        <!-- Estado vacío -->
        <div class="chat-conv__empty" id="chatEmpty">
            <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2" style="color:var(--color-text-muted);opacity:.35;margin-bottom:14px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p style="font-weight:600;margin-bottom:4px;font-size:14px">Selecciona una conversación</p>
            <p style="font-size:12px;color:var(--color-text-muted)">Elige un chat de la lista para empezar a responder</p>
        </div>

        <!-- Conversación activa -->
        <div class="chat-conv__active" id="chatActive" hidden>

            <!-- Header -->
            <div class="chat-conv__header" id="chatConvHeader"></div>

            <!-- Mensajes -->
            <div class="chat-conv__messages" id="chatMessages"></div>

            <!-- Toolbar: modos + respuestas rápidas -->
            <div class="chat-toolbar" id="chatToolbar">
                <div class="chat-toolbar__left">
                    <button class="chat-mode-btn chat-mode-btn--reply active" id="btnModeReply" type="button" onclick="setMode('reply')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        Responder
                    </button>
                    <button class="chat-mode-btn chat-mode-btn--note" id="btnModeNote" type="button" onclick="setMode('note')">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Nota interna
                    </button>
                </div>
                <div class="chat-toolbar__right">
                    <div class="chat-qr-wrap" id="chatQrWrap">
                        <button class="chat-qr-btn" id="btnQuickReply" type="button">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            Rápidas
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="chat-qr-list" id="chatQrList">
                            <!-- se genera por JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área de respuesta -->
            <div class="chat-conv__reply" id="chatReplyArea">
                <div id="noteModeBar" class="note-mode-bar" hidden>
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Nota interna — solo visible para el equipo, <strong>no se envía al cliente</strong>
                </div>
                <form id="chatReplyForm" style="display:flex;gap:8px;align-items:center">
                    <input type="text" id="chatReplyInput" class="form-control"
                           placeholder="Escribe tu respuesta..." autocomplete="off"
                           style="flex:1;font-size:13px;border-radius:8px;transition:border-color .15s,background .15s">
                    <button type="submit" id="btnSendMsg" class="btn-primary"
                            style="padding:0 18px;height:38px;font-size:13px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transition:background .15s">
                        Enviar
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </form>
            </div>
        </div><!-- /chat-conv__active -->
    </div><!-- /chat-conversation -->

    <!-- ── Panel derecho: info del visitante ── -->
    <div class="chat-info-panel" id="chatInfoPanel" hidden>
        <div class="cip__section">
            <h4 class="cip__title">Visitante</h4>
            <div id="cipVisitorInfo"></div>
        </div>
        <div class="cip__section">
            <h4 class="cip__title">Estadísticas</h4>
            <div id="cipStats"></div>
        </div>
        <div class="cip__section">
            <h4 class="cip__title">Acciones</h4>
            <div id="cipActions" style="display:flex;flex-direction:column;gap:8px"></div>
        </div>
    </div>

</div><!-- /chat-layout -->

<!-- ═══ MODAL: Convertir a Lead ═══ -->
<div class="cmodal-overlay" id="leadModal" hidden>
    <div class="cmodal">
        <div class="cmodal__head">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center">
                    <svg width="18" height="18" fill="none" stroke="#15803d" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <div>
                    <div style="font-size:15px;font-weight:700">Convertir a Lead</div>
                    <div style="font-size:12px;color:var(--color-text-muted)">Guardar este contacto en Gestión de Leads</div>
                </div>
            </div>
            <button type="button" class="cmodal__close" onclick="closeLeadModal()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="leadForm">
            <div class="cmodal__body">
                <div class="lead-row">
                    <div class="lead-field">
                        <label>Nombre completo *</label>
                        <input type="text" id="lf_nombre" name="nombre" required placeholder="Nombre del contacto">
                    </div>
                    <div class="lead-field">
                        <label>WhatsApp *</label>
                        <input type="text" id="lf_whatsapp" name="whatsapp" required placeholder="+57 300 000 0000">
                    </div>
                </div>
                <div class="lead-row">
                    <div class="lead-field">
                        <label>Correo electrónico</label>
                        <input type="email" id="lf_email" name="email" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="lead-field">
                        <label>Presupuesto estimado (COP)</label>
                        <input type="number" id="lf_presupuesto" name="presupuesto" placeholder="0" min="0" step="1000">
                    </div>
                </div>
                <div class="lead-field">
                    <label>Servicio de interés *</label>
                    <select id="lf_servicio" name="servicio_interes" required>
                        <option value="">— Selecciona —</option>
                        <option value="Diseño Web">Diseño Web</option>
                        <option value="Marketing Digital">Marketing Digital</option>
                        <option value="WordPress">WordPress</option>
                        <option value="Hosting y Dominio">Hosting y Dominio</option>
                        <option value="Correos Corporativos">Correos Corporativos</option>
                        <option value="Actualizaciones Web">Actualizaciones Web</option>
                        <option value="Plan Básico">Plan Básico</option>
                        <option value="Plan Pymes">Plan Pymes</option>
                        <option value="Plan Enterprise">Plan Enterprise</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="lead-field">
                    <label>Notas del agente</label>
                    <textarea id="lf_notas" name="notas" rows="3" placeholder="Resumen de la conversación, necesidades detectadas..."></textarea>
                </div>
                <input type="hidden" id="lf_sid" name="session_id">
            </div>
            <div class="cmodal__foot">
                <button type="button" class="btn-secondary" onclick="closeLeadModal()">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnLeadSubmit">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    Guardar Lead
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ═══ LAYOUT ═══ */
.chat-layout{
    display:grid;
    grid-template-columns:300px 1fr;
    height:calc(100vh - 175px);
    min-height:440px;
    border:1px solid var(--color-border);
    border-radius:12px;
    overflow:hidden;
    transition:grid-template-columns .2s ease;
}
.chat-layout.with-info{
    grid-template-columns:300px 1fr 272px;
}

/* ═══ PANEL SESIONES ═══ */
.chat-sessions{
    border-right:1px solid var(--color-border);
    overflow-y:auto;
    background:var(--color-bg-secondary,#f8f8f6);
}
.chat-sessions__list{display:flex;flex-direction:column}
.chat-sess{
    display:flex;align-items:flex-start;gap:10px;
    padding:13px 14px;cursor:pointer;
    border-bottom:1px solid var(--color-border);
    transition:background .12s;position:relative;
}
.chat-sess:hover{background:var(--color-bg,#fff)}
.chat-sess.active{background:var(--color-bg,#fff);border-left:3px solid var(--color-primary,#0E0E0C)}
.chat-sess__avatar{
    width:34px;height:34px;border-radius:50%;
    background:var(--color-primary,#0E0E0C);color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:700;flex-shrink:0;
}
.chat-sess__info{flex:1;min-width:0}
.chat-sess__name{font-size:13px;font-weight:600;margin-bottom:2px;display:flex;align-items:center;gap:6px}
.chat-sess__preview{font-size:11.5px;color:var(--color-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px}
.chat-sess__time{font-size:10px;color:var(--color-text-muted);white-space:nowrap;position:absolute;top:13px;right:12px}
.chat-sess__unread{
    min-width:17px;height:17px;border-radius:99px;
    background:#ef4444;color:#fff;font-size:10px;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;padding:0 4px;
}

/* ═══ PANEL CONVERSACIÓN ═══ */
.chat-conversation{display:flex;flex-direction:column;background:#fff;min-width:0;overflow:visible}
.chat-conv__empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--color-text-muted)}
/* flex:1 + min-height:0 para que el área de mensajes crezca correctamente en flex column */
.chat-conv__active{display:flex;flex-direction:column;flex:1;min-height:0;overflow:visible}

/* Header */
.chat-conv__header{
    padding:10px 14px;border-bottom:1px solid var(--color-border);
    display:flex;align-items:center;justify-content:space-between;
    flex-shrink:0;background:#fff;gap:8px;
}
.chat-header-btn{
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 10px;border-radius:7px;border:1px solid var(--color-border);
    background:transparent;font-size:12px;font-weight:500;cursor:pointer;
    color:var(--color-text-muted);transition:all .15s;white-space:nowrap;
}
.chat-header-btn:hover{background:var(--color-bg-secondary);color:var(--color-text)}
.chat-header-btn.active{background:var(--color-primary);color:#fff;border-color:var(--color-primary)}
.chat-header-btn--danger:hover{background:#fee2e2;color:#b91c1c;border-color:#fca5a5}
.chat-header-btn--green{background:#dcfce7;color:#15803d;border-color:#bbf7d0}
.chat-header-btn--green:hover{background:#bbf7d0}

/* Mensajes */
.chat-conv__messages{
    flex:1;min-height:0;overflow-y:auto;padding:16px 18px;
    display:flex;flex-direction:column;gap:10px;
    background:var(--color-bg-secondary,#f8f8f6);
}

/* Toolbar */
.chat-toolbar{
    display:flex;align-items:center;justify-content:space-between;
    padding:7px 14px;border-top:1px solid var(--color-border);
    background:#fafaf9;flex-shrink:0;gap:8px;
}
.chat-toolbar__left{display:flex;gap:6px}
.chat-mode-btn{
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 11px;border-radius:6px;border:1px solid var(--color-border);
    font-size:12px;font-weight:500;cursor:pointer;background:transparent;
    color:var(--color-text-muted);transition:all .15s;
}
.chat-mode-btn:hover{background:var(--color-bg);color:var(--color-text)}
.chat-mode-btn--reply.active{background:var(--color-primary);color:#fff;border-color:var(--color-primary)}
.chat-mode-btn--note.active{background:#f59e0b;color:#fff;border-color:#f59e0b}

/* Quick replies */
.chat-qr-wrap{position:relative}
.chat-qr-btn{
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 10px;border-radius:6px;border:1px solid var(--color-border);
    font-size:12px;font-weight:500;cursor:pointer;background:transparent;
    color:var(--color-text-muted);transition:all .15s;
}
.chat-qr-btn:hover{background:var(--color-bg);color:var(--color-text)}
/* posición fixed via JS para escapar de cualquier overflow:hidden */
.chat-qr-list{
    position:fixed;
    width:300px;max-height:320px;overflow-y:auto;
    background:#fff;border:1px solid var(--color-border);
    border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.16);
    z-index:9999;display:none;
}
.chat-qr-list.open{display:block}
.chat-qr-item{
    padding:9px 14px;font-size:12.5px;cursor:pointer;
    border-bottom:1px solid var(--color-border);
    color:var(--color-text);line-height:1.4;
    transition:background .1s;
}
.chat-qr-item:last-child{border-bottom:none}
.chat-qr-item:hover{background:var(--color-bg-secondary)}

/* Reply area */
.chat-conv__reply{
    padding:10px 14px;border-top:1px solid var(--color-border);
    flex-shrink:0;background:#fff;display:flex;flex-direction:column;gap:6px;
}
.note-mode-bar{
    display:flex;align-items:center;gap:6px;
    padding:5px 10px;border-radius:6px;
    background:#fffbeb;border:1px solid #fde68a;
    font-size:11.5px;color:#b45309;
}

/* ═══ BURBUJAS ═══ */
.chat-row{display:flex;align-items:flex-end;gap:8px;animation:chatRowIn .18s ease}
.chat-row--agent{flex-direction:row-reverse}
@keyframes chatRowIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
.chat-av{
    width:28px;height:28px;border-radius:50%;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    font-size:10px;font-weight:700;line-height:1;
    box-shadow:0 1px 4px rgba(0,0,0,.12);
}
.chat-av--visitor{background:var(--color-primary,#0E0E0C);color:#fff}
.chat-av--agent{background:#e8f5e9;color:#15803d;border:1px solid #bbf7d0}
.chat-av--note{background:#fef3c7;color:#b45309;border:1px solid #fde68a}
.chat-bubble{max-width:72%;padding:9px 13px;font-size:13px;line-height:1.45;border-radius:14px;word-wrap:break-word}
.chat-bubble--visitor{background:var(--color-primary,#0E0E0C);color:#fff;border-bottom-left-radius:4px}
.chat-bubble--agent{background:#fff;border:1px solid var(--color-border);color:var(--color-text);border-bottom-right-radius:4px}
.chat-bubble--note{
    background:#fffbeb;border:1px solid #fde68a;color:#78350f;
    border-bottom-right-radius:4px;font-size:12.5px;
}
.chat-bubble--note .chat-note-label{
    display:flex;align-items:center;gap:4px;
    font-size:10.5px;font-weight:700;color:#b45309;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;
}
.chat-bubble__meta{display:flex;align-items:center;gap:6px;margin-top:3px;justify-content:flex-end}
.chat-bubble__time{font-size:10px;opacity:.5;font-family:var(--font-mono,monospace)}
.chat-bubble--visitor .chat-bubble__meta{justify-content:flex-start}
.chat-bubble--note .chat-bubble__meta{justify-content:flex-end}

/* ═══ PANEL INFO ═══ */
.chat-info-panel{
    border-left:1px solid var(--color-border);
    background:#fff;overflow-y:auto;
    display:flex;flex-direction:column;
}
.cip__section{
    padding:14px 16px;border-bottom:1px solid var(--color-border);
}
.cip__title{
    font-size:10.5px;font-weight:700;text-transform:uppercase;
    letter-spacing:.07em;color:var(--color-text-muted);margin-bottom:10px;
}
.cip-row{display:flex;flex-direction:column;gap:2px;margin-bottom:8px}
.cip-row:last-child{margin-bottom:0}
.cip-label{font-size:11px;color:var(--color-text-muted)}
.cip-value{font-size:13px;font-weight:500;word-break:break-all}
.cip-action{
    width:100%;padding:8px 12px;border-radius:8px;border:1px solid var(--color-border);
    background:transparent;cursor:pointer;font-size:13px;font-weight:500;
    display:flex;align-items:center;justify-content:center;gap:7px;
    transition:all .15s;color:var(--color-text);
}
.cip-action:hover{background:var(--color-bg-secondary)}
.cip-action--primary{background:var(--color-primary);color:#fff;border-color:var(--color-primary)}
.cip-action--primary:hover{background:#26241d}
.cip-action--green{background:#dcfce7;color:#15803d;border-color:#bbf7d0}
.cip-action--green:hover{background:#bbf7d0}
.cip-action--red{color:#b91c1c}
.cip-action--red:hover{background:#fee2e2;border-color:#fca5a5}
#cipDuration{font-variant-numeric:tabular-nums;font-family:var(--font-mono,monospace)}

/* ═══ MODAL LEAD ═══ */
.cmodal-overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;
    display:none;align-items:center;justify-content:center;
    animation:fadeIn .18s ease;
}
.cmodal-overlay:not([hidden]){ display:flex; }
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.cmodal{
    background:#fff;border-radius:14px;width:500px;max-width:96vw;
    max-height:92vh;overflow-y:auto;
    box-shadow:0 24px 64px rgba(0,0,0,.2);
    animation:slideUp .22s ease;
}
@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
.cmodal__head{
    padding:18px 22px;border-bottom:1px solid var(--color-border);
    display:flex;align-items:center;justify-content:space-between;
    position:sticky;top:0;background:#fff;z-index:1;
}
.cmodal__close{
    width:30px;height:30px;border-radius:7px;border:none;background:none;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    color:var(--color-text-muted);transition:all .15s;
}
.cmodal__close:hover{background:var(--color-bg-secondary);color:var(--color-text)}
.cmodal__body{padding:20px 22px;display:flex;flex-direction:column;gap:14px}
.cmodal__foot{
    padding:14px 22px;border-top:1px solid var(--color-border);
    display:flex;gap:8px;justify-content:flex-end;
    position:sticky;bottom:0;background:#fff;
}
.lead-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.lead-field{display:flex;flex-direction:column;gap:5px}
.lead-field label{font-size:12px;font-weight:600;color:var(--color-text-muted)}
.lead-field input,.lead-field select,.lead-field textarea{
    padding:9px 12px;border:1px solid var(--color-border);border-radius:8px;
    font-size:13px;font-family:inherit;color:var(--color-text);
    transition:border-color .15s;
}
.lead-field input:focus,.lead-field select:focus,.lead-field textarea:focus{
    outline:none;border-color:var(--color-primary);
}
.lead-field textarea{resize:vertical;min-height:72px}

/* ═══ RESPONSIVE ═══ */
@media(max-width:960px){
    .chat-layout,.chat-layout.with-info{grid-template-columns:1fr}
    .chat-sessions{max-height:200px}
    .chat-info-panel{display:none!important}
}
</style>

<script>
(function(){
    var API           = 'api/chat.php';
    var POLL_MSG      = 3000;
    var POLL_SES      = 5000;
    var activeSid     = null;
    var lastMsgId     = 0;
    var pollMsg       = null;
    var pollSes       = null;
    var activeVisitor = '';
    var activeSession = null;
    var chatMode      = 'reply';   /* 'reply' | 'note' */
    var infoPanelOpen = false;
    var sessionStart  = null;
    var durationTimer = null;

    /* ── DOM refs ── */
    var layout    = document.getElementById('chatLayout');
    var listEl    = document.getElementById('chatSessionList');
    var emptyEl   = document.getElementById('chatEmpty');
    var activeEl  = document.getElementById('chatActive');
    var headerEl  = document.getElementById('chatConvHeader');
    var msgsEl    = document.getElementById('chatMessages');
    var replyF    = document.getElementById('chatReplyForm');
    var replyIn   = document.getElementById('chatReplyInput');
    var filterSt  = document.getElementById('chatFilterStatus');
    var infoPanel = document.getElementById('chatInfoPanel');
    var noteModeBar = document.getElementById('noteModeBar');
    var btnSend   = document.getElementById('btnSendMsg');
    var replyArea = document.getElementById('chatReplyArea');
    var cipVisitor = document.getElementById('cipVisitorInfo');
    var cipStats   = document.getElementById('cipStats');
    var cipActions = document.getElementById('cipActions');

    /* ── Sonido ── */
    function playNotif(){
        try{
            var ctx=new(window.AudioContext||window.webkitAudioContext)();
            [[520,0],[680,0.14]].forEach(function(n){
                var o=ctx.createOscillator(),g=ctx.createGain();
                o.connect(g);g.connect(ctx.destination);
                o.type='sine';o.frequency.value=n[0];
                g.gain.setValueAtTime(0,ctx.currentTime+n[1]);
                g.gain.linearRampToValueAtTime(0.2,ctx.currentTime+n[1]+0.02);
                g.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+n[1]+0.4);
                o.start(ctx.currentTime+n[1]);o.stop(ctx.currentTime+n[1]+0.4);
            });
        }catch(e){}
    }

    /* ── Helpers ── */
    function esc(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
    function timeAgo(dt){
        var diff=Math.floor((Date.now()-new Date(dt.replace(' ','T')).getTime())/1000);
        if(diff<60) return 'ahora';
        if(diff<3600) return Math.floor(diff/60)+'m';
        if(diff<86400) return Math.floor(diff/3600)+'h';
        return Math.floor(diff/86400)+'d';
    }
    function fmtTime(dt){
        if(!dt) return '';
        return new Date(dt.replace(' ','T')).toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'});
    }
    function fmtDuration(secs){
        var h=Math.floor(secs/3600), m=Math.floor((secs%3600)/60), s=secs%60;
        if(h>0) return h+'h '+String(m).padStart(2,'0')+'m';
        return String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
    }

    /* ── Modo reply / nota ── */
    window.setMode = function(mode){
        chatMode = mode;
        var btnR = document.getElementById('btnModeReply');
        var btnN = document.getElementById('btnModeNote');
        btnR.classList.toggle('active', mode==='reply');
        btnN.classList.toggle('active', mode==='note');
        noteModeBar.hidden = mode !== 'note';
        if(mode==='note'){
            replyIn.placeholder = 'Escribe una nota interna...';
            replyIn.style.borderColor = '#f59e0b';
            replyIn.style.background  = '#fffbeb';
            btnSend.style.background  = '#f59e0b';
            btnSend.style.borderColor = '#f59e0b';
            btnSend.innerHTML = 'Guardar <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
        } else {
            replyIn.placeholder = replyIn.disabled ? 'Chat cerrado' : 'Escribe tu respuesta...';
            replyIn.style.borderColor = '';
            replyIn.style.background  = '';
            btnSend.style.background  = '';
            btnSend.style.borderColor = '';
            btnSend.innerHTML = 'Enviar <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>';
        }
        replyIn.focus();
    };

    /* ── Respuestas rápidas ── */
    var QUICK_REPLIES = [
        '¡Hola! Bienvenido a QUANTUN Digital. Soy tu asesor en línea, ¿en qué puedo ayudarte hoy?',
        'Gracias por escribirnos. En un momento reviso tu consulta.',
        'Entiendo perfectamente tu necesidad. Déjame consultar esa información para ti.',
        'Para brindarte una atención más personalizada, ¿podrías compartirme tu número de WhatsApp?',
        'Tenemos planes y servicios que se ajustan muy bien a lo que buscas. ¿Te gustaría recibir una cotización?',
        '¿Tienes alguna pregunta adicional antes de finalizar? Estoy aquí para ayudarte.',
        'Voy a hacer un seguimiento por correo. ¡Que tengas un excelente día!',
    ];
    (function buildQuickReplies(){
        var list = document.getElementById('chatQrList');
        list.innerHTML = QUICK_REPLIES.map(function(r){
            return '<div class="chat-qr-item" data-text="'+esc(r)+'">'+esc(r)+'</div>';
        }).join('');
        list.querySelectorAll('.chat-qr-item').forEach(function(item){
            item.addEventListener('click', function(){
                replyIn.value = item.getAttribute('data-text');
                setMode('reply');
                list.classList.remove('open');
                replyIn.focus();
            });
        });
    })();
    document.getElementById('btnQuickReply').addEventListener('click', function(e){
        e.stopPropagation();
        var list = document.getElementById('chatQrList');
        var isOpen = list.classList.contains('open');
        if(isOpen){
            list.classList.remove('open');
            return;
        }
        /* Posicionar fixed usando las coordenadas reales del botón */
        var rect = e.currentTarget.getBoundingClientRect();
        list.style.right  = (window.innerWidth - rect.right) + 'px';
        list.style.bottom = (window.innerHeight - rect.top + 6) + 'px';
        list.style.left   = 'auto';
        list.style.top    = 'auto';
        list.classList.add('open');
    });
    document.addEventListener('click', function(e){
        if(!e.target.closest('#chatQrWrap'))
            document.getElementById('chatQrList').classList.remove('open');
    });

    /* ── Panel de info ── */
    function toggleInfoPanel(force){
        infoPanelOpen = (force !== undefined) ? force : !infoPanelOpen;
        layout.classList.toggle('with-info', infoPanelOpen);
        infoPanel.hidden = !infoPanelOpen;
        var btn = document.getElementById('btnToggleInfo');
        if(btn) btn.classList.toggle('active', infoPanelOpen);
    }

    function renderInfoPanel(s, messages){
        /* Visitante */
        cipVisitor.innerHTML =
            '<div class="cip-row"><span class="cip-label">Nombre</span><span class="cip-value">'+esc(s.visitor_name)+'</span></div>'+
            (s.visitor_email ? '<div class="cip-row"><span class="cip-label">Correo</span><span class="cip-value">'+esc(s.visitor_email)+'</span></div>' : '')+
            (s.visitor_ip   ? '<div class="cip-row"><span class="cip-label">IP</span><span class="cip-value" style="font-size:12px;font-family:monospace">'+esc(s.visitor_ip)+'</span></div>' : '');

        /* Stats */
        var msgCount = (messages||[]).filter(function(m){ return m.type !== 'note'; }).length;
        var visitorCount = (messages||[]).filter(function(m){ return m.sender === 'visitor'; }).length;
        cipStats.innerHTML =
            '<div class="cip-row"><span class="cip-label">Inicio del chat</span><span class="cip-value">'+fmtTime(s.created_at)+'</span></div>'+
            '<div class="cip-row"><span class="cip-label">Duración</span><span class="cip-value" id="cipDuration">—</span></div>'+
            '<div class="cip-row"><span class="cip-label">Mensajes totales</span><span class="cip-value">'+msgCount+'</span></div>'+
            '<div class="cip-row"><span class="cip-label">Del visitante</span><span class="cip-value">'+visitorCount+'</span></div>';

        /* Contador duración */
        if(durationTimer) clearInterval(durationTimer);
        sessionStart = new Date(s.created_at.replace(' ','T'));
        function updateDur(){
            var el = document.getElementById('cipDuration');
            if(!el) return;
            var secs = Math.floor((Date.now() - sessionStart.getTime()) / 1000);
            el.textContent = fmtDuration(secs);
        }
        updateDur();
        durationTimer = setInterval(updateDur, 1000);

        /* Acciones */
        cipActions.innerHTML = '';
        if(s.status === 'activo'){
            var btnLead = document.createElement('button');
            btnLead.className = 'cip-action cip-action--green';
            btnLead.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Convertir a Lead';
            btnLead.onclick = function(){ openLeadModal(s); };
            cipActions.appendChild(btnLead);

            var btnClose = document.createElement('button');
            btnClose.className = 'cip-action cip-action--red';
            btnClose.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Cerrar conversación';
            btnClose.onclick = function(){ cerrarChat(s.id); };
            cipActions.appendChild(btnClose);
        } else {
            var btnReopen = document.createElement('button');
            btnReopen.className = 'cip-action';
            btnReopen.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg> Reabrir chat';
            btnReopen.onclick = function(){ reabrirChat(s.id); };
            cipActions.appendChild(btnReopen);

            var btnLead2 = document.createElement('button');
            btnLead2.className = 'cip-action';
            btnLead2.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Convertir a Lead';
            btnLead2.onclick = function(){ openLeadModal(s); };
            cipActions.appendChild(btnLead2);
        }
    }

    /* ── Cargar sesiones ── */
    async function loadSessions(){
        try{
            var r = await fetch(API+'?status='+filterSt.value);
            var d = await r.json();
            if(!d.success) return;
            renderSessions(d.sessions||[]);
        }catch(e){}
    }
    function renderSessions(list){
        if(!list.length){
            listEl.innerHTML='<p style="padding:40px 16px;text-align:center;color:var(--color-text-muted);font-size:13px">No hay conversaciones '+filterSt.value+'s</p>';
            return;
        }
        listEl.innerHTML = list.map(function(s){
            var ini=(s.visitor_name||'?').split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase();
            var cls=s.id==activeSid?' active':'';
            return '<div class="chat-sess'+cls+'" data-sid="'+s.id+'">'+
                '<div class="chat-sess__avatar">'+ini+'</div>'+
                '<div class="chat-sess__info">'+
                    '<div class="chat-sess__name">'+esc(s.visitor_name)+
                        (s.unread>0?'<span class="chat-sess__unread">'+s.unread+'</span>':'')+
                    '</div>'+
                    '<div class="chat-sess__preview">'+esc((s.last_message||'').substring(0,48))+'</div>'+
                '</div>'+
                '<span class="chat-sess__time">'+timeAgo(s.last_message_at||s.created_at)+'</span>'+
            '</div>';
        }).join('');
        listEl.querySelectorAll('.chat-sess').forEach(function(el){
            el.addEventListener('click',function(){ openSession(parseInt(el.dataset.sid)); });
        });
    }

    /* ── Abrir sesión ── */
    async function openSession(sid){
        activeSid=sid; lastMsgId=0; activeVisitor=''; activeSession=null;
        setMode('reply');
        emptyEl.hidden=true; activeEl.hidden=false;
        msgsEl.innerHTML='<p style="text-align:center;font-size:12px;color:var(--color-text-muted);padding:16px 0">Cargando mensajes...</p>';
        loadSessions();
        try{
            var r=await fetch(API+'?session_id='+sid);
            var d=await r.json();
            if(!d.success) return;
            var s=d.session;
            activeSession=s;
            activeVisitor=s.visitor_name||'?';

            /* Header */
            var ini=(s.visitor_name||'?').split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase();
            var statusBadge = s.status==='activo'
                ? '<span style="font-size:11px;padding:3px 9px;border-radius:99px;background:#dcfce7;color:#15803d;font-weight:600">● Activo</span>'
                : '<span style="font-size:11px;padding:3px 9px;border-radius:99px;background:#fee2e2;color:#b91c1c;font-weight:600">● Cerrado</span>';

            headerEl.innerHTML =
                '<div style="display:flex;align-items:center;gap:10px;min-width:0">'+
                    '<div style="width:34px;height:34px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">'+ini+'</div>'+
                    '<div style="min-width:0">'+
                        '<div style="font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(s.visitor_name)+'</div>'+
                        (s.visitor_email?'<div style="font-size:11.5px;color:var(--color-text-muted)">'+esc(s.visitor_email)+'</div>':'')+
                    '</div>'+
                    statusBadge+
                '</div>'+
                '<div style="display:flex;gap:6px;align-items:center;flex-shrink:0">'+
                    '<button id="btnToggleInfo" class="chat-header-btn'+(infoPanelOpen?' active':'')+'" onclick="toggleInfoPanel()">'+
                        '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Info'+
                    '</button>'+
                    '<button class="chat-header-btn chat-header-btn--green" onclick="openLeadModal(activeSession)">'+
                        '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Lead'+
                    '</button>'+
                    (s.status==='activo'
                        ?'<button class="chat-header-btn chat-header-btn--danger" onclick="cerrarChat('+s.id+')">Cerrar</button>'
                        :'<button class="chat-header-btn" onclick="reabrirChat('+s.id+')">Reabrir</button>')+
                '</div>';

            /* Mensajes */
            msgsEl.innerHTML='';
            (d.messages||[]).forEach(function(m){
                addBubble(m);
                if(parseInt(m.id)>lastMsgId) lastMsgId=parseInt(m.id);
            });
            msgsEl.scrollTop=msgsEl.scrollHeight;

            /* Input */
            replyIn.disabled=(s.status==='cerrado');
            if(s.status==='cerrado') setMode('reply');
            replyIn.focus();

            /* Info panel */
            renderInfoPanel(s, d.messages||[]);

            startMsgPoll();
        }catch(e){ console.error(e); }
    }

    /* ── Renderizar burbuja ── */
    function addBubble(m){
        var isNote = (m.type === 'note');
        var sender = isNote ? 'agent' : m.sender;

        var avLabel = sender==='agent'
            ? (isNote ? '📝' : 'Yo')
            : (activeVisitor||'?').split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase();
        var avCls = isNote ? 'chat-av chat-av--note' : ('chat-av chat-av--'+sender);
        var av = '<div class="'+avCls+'" title="'+(sender==='agent'?'Agente':esc(activeVisitor))+'">'+avLabel+'</div>';

        var bubbleContent = isNote
            ? '<div class="chat-bubble--note chat-bubble">'+
                '<div class="chat-note-label"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Nota interna</div>'+
                esc(m.message)+
                '<div class="chat-bubble__meta"><span class="chat-bubble__time">'+fmtTime(m.created_at)+'</span></div>'+
              '</div>'
            : '<div class="chat-bubble chat-bubble--'+sender+'">'+
                esc(m.message)+
                '<div class="chat-bubble__meta"><span class="chat-bubble__time">'+fmtTime(m.created_at)+'</span></div>'+
              '</div>';

        var row = document.createElement('div');
        row.className = 'chat-row chat-row--' + (isNote ? 'agent' : sender);
        row.innerHTML = av + bubbleContent;
        msgsEl.appendChild(row);
    }

    /* ── Enviar ── */
    replyF.addEventListener('submit', async function(e){
        e.preventDefault();
        var txt = replyIn.value.trim();
        if(!txt || !activeSid) return;
        if(chatMode==='reply' && replyIn.disabled) return;
        replyIn.value='';

        var isNote = chatMode === 'note';
        var now = new Date().toISOString().replace('T',' ').substring(0,19);
        addBubble({sender:'agent', message:txt, created_at:now, type: isNote ? 'note' : 'message'});
        msgsEl.scrollTop=msgsEl.scrollHeight;

        try{
            var body = {session_id: activeSid, message: txt};
            if(isNote) body.type = 'note';
            var r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
            var d=await r.json();
            if(d.message_id > lastMsgId) lastMsgId=d.message_id;
        }catch(err){ console.error(err); }
    });

    /* ── Polling ── */
    async function pollMessages(){
        if(!activeSid) return;
        try{
            var r=await fetch(API+'?session_id='+activeSid);
            var d=await r.json();
            if(!d.success) return;
            var hasNew=false;
            (d.messages||[]).forEach(function(m){
                if(parseInt(m.id)>lastMsgId){
                    lastMsgId=parseInt(m.id);
                    addBubble(m);
                    msgsEl.scrollTop=msgsEl.scrollHeight;
                    if(m.sender==='visitor') hasNew=true;
                }
            });
            if(hasNew) playNotif();
        }catch(e){}
    }
    function startMsgPoll(){ if(pollMsg) clearInterval(pollMsg); pollMsg=setInterval(pollMessages,POLL_MSG); }

    /* ── Cerrar / Reabrir ── */
    window.cerrarChat = async function(sid){
        await fetch(API,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sid,status:'cerrado'})});
        loadSessions();
        if(sid===activeSid) openSession(sid);
    };
    window.reabrirChat = async function(sid){
        await fetch(API,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sid,status:'activo'})});
        loadSessions();
        if(sid===activeSid) openSession(sid);
    };

    /* ── Info panel global ── */
    window.toggleInfoPanel = toggleInfoPanel;

    /* ── Modal Lead ── */
    window.openLeadModal = function(s){
        if(!s) return;
        document.getElementById('lf_nombre').value    = s.visitor_name || '';
        document.getElementById('lf_email').value     = s.visitor_email || '';
        document.getElementById('lf_whatsapp').value  = '';
        document.getElementById('lf_servicio').value  = '';
        document.getElementById('lf_presupuesto').value = '';
        document.getElementById('lf_notas').value     = '';
        document.getElementById('lf_sid').value       = s.id || '';
        document.getElementById('leadModal').hidden   = false;
        document.body.style.overflow = 'hidden';
        setTimeout(function(){ document.getElementById('lf_whatsapp').focus(); }, 100);
    };
    window.closeLeadModal = function(){
        document.getElementById('leadModal').hidden = true;
        document.body.style.overflow = '';
    };
    document.getElementById('leadModal').addEventListener('click', function(e){
        if(e.target === this) closeLeadModal();
    });
    document.addEventListener('keydown', function(e){
        if(e.key==='Escape' && !document.getElementById('leadModal').hidden) closeLeadModal();
    });

    document.getElementById('leadForm').addEventListener('submit', async function(e){
        e.preventDefault();
        var btn = document.getElementById('btnLeadSubmit');
        btn.disabled = true;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="animation:spin .6s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Guardando…';

        var payload = {
            action:    'lead',
            session_id: parseInt(document.getElementById('lf_sid').value),
            nombre:    document.getElementById('lf_nombre').value.trim(),
            whatsapp:  document.getElementById('lf_whatsapp').value.trim(),
            email:     document.getElementById('lf_email').value.trim(),
            servicio_interes: document.getElementById('lf_servicio').value,
            presupuesto: parseFloat(document.getElementById('lf_presupuesto').value)||0,
            notas:     document.getElementById('lf_notas').value.trim(),
        };

        try{
            var r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            var d=await r.json();
            if(d.success){
                closeLeadModal();
                /* nota interna automática en el chat */
                var notaMsg = '✅ Contacto guardado como Lead ('+payload.nombre+' · '+payload.servicio_interes+')';
                addBubble({sender:'agent',message:notaMsg,created_at:new Date().toISOString().replace('T',' ').substring(0,19),type:'note'});
                msgsEl.scrollTop=msgsEl.scrollHeight;
                /* toast de confirmación con enlace */
                if(d.lead_id){
                    showToast('Lead guardado exitosamente. <a href="leads.php" target="_blank" style="color:inherit;text-decoration:underline;font-weight:600">Ver en Gestión de Leads →</a>', 'success', 5000);
                }
            } else {
                showToast(d.error || 'Error al guardar el lead. Inténtalo de nuevo.', 'error');
            }
        }catch(err){
            showToast('Error de conexión. Verifica tu red e intenta de nuevo.', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Guardar Lead';
    });

    /* ── Init ── */
    filterSt.addEventListener('change',function(){ activeSid=null; emptyEl.hidden=false; activeEl.hidden=true; loadSessions(); });
    loadSessions();
    if(pollSes) clearInterval(pollSes);
    pollSes = setInterval(loadSessions, POLL_SES);
})();
</script>

<style>
@keyframes spin{to{transform:rotate(360deg)}}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
