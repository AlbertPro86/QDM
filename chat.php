<?php
require_once __DIR__ . '/includes/auth.php';
requireAuth();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header" style="margin-bottom:0;padding-bottom:16px;border-bottom:1px solid var(--color-border)">
        <div>
            <h1>Chat en Vivo</h1>
            <p class="text-muted" style="font-size:13px">Conversaciones desde el sitio web</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <select id="chatFilterStatus" class="form-control" style="width:auto;font-size:13px;padding:6px 12px">
                <option value="activo">Activos</option>
                <option value="cerrado">Cerrados</option>
            </select>
            <span id="chatOnlineIndicator" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--color-text-muted)">
                <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block"></span> En línea
            </span>
        </div>
    </div>

    <div class="chat-layout" id="chatLayout">
        <!-- Panel izquierdo: sesiones -->
        <div class="chat-sessions" id="chatSessions">
            <div class="chat-sessions__list" id="chatSessionList">
                <p style="padding:32px 16px;text-align:center;color:var(--color-text-muted);font-size:13px">Cargando chats...</p>
            </div>
        </div>

        <!-- Panel derecho: conversación -->
        <div class="chat-conversation" id="chatConversation">
            <div class="chat-conv__empty" id="chatEmpty">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2" style="color:var(--color-text-muted);margin-bottom:12px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p style="font-weight:600;margin-bottom:4px">Selecciona una conversación</p>
                <p style="font-size:12px;color:var(--color-text-muted)">Elige un chat de la lista para empezar a responder</p>
            </div>

            <div class="chat-conv__active" id="chatActive" hidden>
                <!-- Header info -->
                <div class="chat-conv__header" id="chatConvHeader"></div>
                <!-- Messages -->
                <div class="chat-conv__messages" id="chatMessages"></div>
                <!-- Reply input -->
                <div class="chat-conv__reply">
                    <form id="chatReplyForm" style="display:flex;gap:8px;align-items:center">
                        <input type="text" id="chatReplyInput" class="form-control" placeholder="Escribe tu respuesta..." autocomplete="off" style="flex:1;font-size:13px">
                        <button type="submit" class="btn-primary" style="padding:8px 18px;font-size:13px;white-space:nowrap;border-radius:8px;height:38px;display:flex;align-items:center;gap:6px">
                            Enviar
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-layout{
    display:grid;
    grid-template-columns:320px 1fr;
    height:calc(100vh - 140px);
    border:1px solid var(--color-border);
    border-radius:12px;
    overflow:hidden;
    background:var(--color-bg);
}
.chat-sessions{
    border-right:1px solid var(--color-border);
    overflow-y:auto;
    background:var(--color-bg-secondary, #fafafa);
}
.chat-sessions__list{display:flex;flex-direction:column}

/* Session item */
.chat-sess{
    display:flex;align-items:flex-start;gap:10px;
    padding:14px 16px;cursor:pointer;
    border-bottom:1px solid var(--color-border);
    transition:background .12s;
    position:relative;
}
.chat-sess:hover{background:var(--color-bg)}
.chat-sess.active{background:var(--color-bg);border-left:3px solid var(--color-primary)}
.chat-sess__avatar{
    width:36px;height:36px;border-radius:50%;
    background:var(--color-primary);color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:700;flex-shrink:0;
}
.chat-sess__info{flex:1;min-width:0}
.chat-sess__name{font-size:13px;font-weight:600;margin-bottom:2px;display:flex;align-items:center;gap:6px}
.chat-sess__preview{font-size:12px;color:var(--color-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-sess__time{font-size:10px;color:var(--color-text-muted);white-space:nowrap;position:absolute;top:14px;right:16px;font-family:var(--font-mono, monospace)}
.chat-sess__unread{
    min-width:18px;height:18px;border-radius:99px;
    background:#ef4444;color:#fff;font-size:10px;font-weight:700;
    display:inline-flex;align-items:center;justify-content:center;
    padding:0 4px;
}

/* Conversation */
.chat-conversation{display:flex;flex-direction:column;background:#fff}
.chat-conv__empty{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    height:100%;color:var(--color-text-muted);
}
.chat-conv__header{
    padding:12px 20px;border-bottom:1px solid var(--color-border);
    display:flex;align-items:center;justify-content:space-between;
    background:#fff;flex-shrink:0;
}
.chat-conv__messages{
    flex:1;overflow-y:auto;padding:20px;
    display:flex;flex-direction:column;gap:8px;
    background:var(--color-bg-secondary, #fafafa);
}
.chat-conv__reply{
    padding:12px 16px;border-top:1px solid var(--color-border);
    background:#fff;flex-shrink:0;
}

/* Message bubble */
.chat-bubble{
    max-width:70%;padding:10px 14px;
    font-size:13px;line-height:1.45;
    border-radius:14px;word-wrap:break-word;
}
.chat-bubble--visitor{
    background:var(--color-primary);color:#fff;
    align-self:flex-start;border-bottom-left-radius:4px;
}
.chat-bubble--agent{
    background:#fff;border:1px solid var(--color-border);color:var(--color-text);
    align-self:flex-end;border-bottom-right-radius:4px;
}
.chat-bubble__time{display:block;font-size:10px;margin-top:3px;opacity:.55;font-family:var(--font-mono, monospace)}

/* Responsive */
@media(max-width:768px){
    .chat-layout{grid-template-columns:1fr;height:calc(100vh - 120px)}
    .chat-sessions{max-height:200px}
}
</style>

<script>
(function(){
    var API = 'api/chat.php';
    var POLL = 3000;
    var activeSid = null;
    var lastMsgId = 0;
    var pollTimer = null;
    var sessTimer = null;

    var listEl  = document.getElementById('chatSessionList');
    var emptyEl = document.getElementById('chatEmpty');
    var activeEl= document.getElementById('chatActive');
    var headerEl= document.getElementById('chatConvHeader');
    var msgsEl  = document.getElementById('chatMessages');
    var replyF  = document.getElementById('chatReplyForm');
    var replyIn = document.getElementById('chatReplyInput');
    var filterSt= document.getElementById('chatFilterStatus');

    /* ── cargar sesiones ── */
    async function loadSessions() {
        try {
            var res = await fetch(API + '?status=' + filterSt.value);
            var data = await res.json();
            if (!data.success) return;
            renderSessions(data.sessions || []);
        } catch(e) {}
    }

    function renderSessions(sessions) {
        if (!sessions.length) {
            listEl.innerHTML = '<p style="padding:32px 16px;text-align:center;color:var(--color-text-muted);font-size:13px">No hay conversaciones ' + filterSt.value + 's</p>';
            return;
        }
        listEl.innerHTML = sessions.map(function(s) {
            var initials = (s.visitor_name || '?').split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase();
            var time = s.last_message_at ? timeAgo(s.last_message_at) : '';
            var preview = escH(s.last_message || '').substring(0, 50);
            var cls = s.id == activeSid ? ' active' : '';
            return '<div class="chat-sess' + cls + '" data-sid="' + s.id + '">' +
                '<div class="chat-sess__avatar">' + initials + '</div>' +
                '<div class="chat-sess__info">' +
                    '<div class="chat-sess__name">' + escH(s.visitor_name) +
                        (s.unread > 0 ? ' <span class="chat-sess__unread">' + s.unread + '</span>' : '') +
                    '</div>' +
                    '<div class="chat-sess__preview">' + preview + '</div>' +
                '</div>' +
                '<span class="chat-sess__time">' + time + '</span>' +
            '</div>';
        }).join('');

        listEl.querySelectorAll('.chat-sess').forEach(function(el) {
            el.addEventListener('click', function() { openSession(parseInt(el.dataset.sid)); });
        });
    }

    /* ── abrir sesión ── */
    async function openSession(sid) {
        activeSid = sid;
        lastMsgId = 0;
        emptyEl.hidden = true;
        activeEl.hidden = false;
        msgsEl.innerHTML = '';
        loadSessions(); // refresh para marcar active

        try {
            var res = await fetch(API + '?session_id=' + sid);
            var data = await res.json();
            if (!data.success) return;

            var s = data.session;
            headerEl.innerHTML =
                '<div style="display:flex;align-items:center;gap:10px">' +
                    '<strong style="font-size:14px">' + escH(s.visitor_name) + '</strong>' +
                    (s.visitor_email ? '<span style="font-size:12px;color:var(--color-text-muted)">' + escH(s.visitor_email) + '</span>' : '') +
                '</div>' +
                '<div style="display:flex;gap:6px">' +
                    (s.status === 'activo'
                        ? '<button class="btn-secondary" style="font-size:11px;padding:4px 10px;border-radius:6px" onclick="cerrarChat(' + s.id + ')">Cerrar chat</button>'
                        : '<button class="btn-secondary" style="font-size:11px;padding:4px 10px;border-radius:6px" onclick="reabrirChat(' + s.id + ')">Reabrir</button>') +
                '</div>';

            (data.messages || []).forEach(function(m) {
                addBubble(m);
                if (parseInt(m.id) > lastMsgId) lastMsgId = parseInt(m.id);
            });
            msgsEl.scrollTop = msgsEl.scrollHeight;
            replyIn.focus();
            startMsgPoll();
        } catch(e) {}
    }

    function addBubble(m) {
        var div = document.createElement('div');
        div.className = 'chat-bubble chat-bubble--' + m.sender;
        var t = m.created_at ? new Date(m.created_at.replace(' ','T')).toLocaleTimeString('es-CO', {hour:'2-digit',minute:'2-digit'}) : '';
        div.innerHTML = escH(m.message) + '<span class="chat-bubble__time">' + t + '</span>';
        msgsEl.appendChild(div);
    }

    /* ── enviar respuesta ── */
    replyF.addEventListener('submit', async function(e) {
        e.preventDefault();
        var txt = replyIn.value.trim();
        if (!txt || !activeSid) return;
        replyIn.value = '';

        addBubble({ sender: 'agent', message: txt, created_at: new Date().toISOString().replace('T',' ').substring(0,19) });
        msgsEl.scrollTop = msgsEl.scrollHeight;

        try {
            var res = await fetch(API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ session_id: activeSid, message: txt })
            });
            var data = await res.json();
            if (data.message_id > lastMsgId) lastMsgId = data.message_id;
        } catch(e) {}
    });

    /* ── polling mensajes ── */
    async function pollMessages() {
        if (!activeSid) return;
        try {
            var res = await fetch(API + '?session_id=' + activeSid);
            var data = await res.json();
            if (!data.success) return;
            (data.messages || []).forEach(function(m) {
                var mid = parseInt(m.id);
                if (mid > lastMsgId) {
                    lastMsgId = mid;
                    addBubble(m);
                    msgsEl.scrollTop = msgsEl.scrollHeight;
                }
            });
        } catch(e) {}
    }

    function startMsgPoll() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollMessages, POLL);
    }

    /* ── polling sesiones ── */
    function startSessPoll() {
        if (sessTimer) clearInterval(sessTimer);
        sessTimer = setInterval(loadSessions, 5000);
    }

    /* ── cerrar / reabrir ── */
    window.cerrarChat = async function(sid) {
        await fetch(API, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify({session_id:sid,status:'cerrado'}) });
        loadSessions();
        if (sid === activeSid) openSession(sid);
    };
    window.reabrirChat = async function(sid) {
        await fetch(API, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify({session_id:sid,status:'activo'}) });
        loadSessions();
        if (sid === activeSid) openSession(sid);
    };

    /* ── helpers ── */
    function escH(s) { var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
    function timeAgo(dt) {
        var diff = Math.floor((Date.now() - new Date(dt.replace(' ','T')).getTime()) / 1000);
        if (diff < 60)   return 'ahora';
        if (diff < 3600)  return Math.floor(diff/60) + 'm';
        if (diff < 86400) return Math.floor(diff/3600) + 'h';
        return Math.floor(diff/86400) + 'd';
    }

    filterSt.addEventListener('change', function() {
        activeSid = null; emptyEl.hidden = false; activeEl.hidden = true;
        loadSessions();
    });

    loadSessions();
    startSessPoll();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
