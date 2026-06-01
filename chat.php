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

<div class="chat-layout" id="chatLayout">
    <!-- Panel izquierdo: sesiones -->
    <div class="chat-sessions">
        <div class="chat-sessions__list" id="chatSessionList">
            <p style="padding:40px 16px;text-align:center;color:var(--color-text-muted);font-size:13px">Cargando chats...</p>
        </div>
    </div>

    <!-- Panel derecho: conversación -->
    <div class="chat-conversation">
        <div class="chat-conv__empty" id="chatEmpty">
            <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2" style="color:var(--color-text-muted);opacity:.35;margin-bottom:14px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p style="font-weight:600;margin-bottom:4px;font-size:14px">Selecciona una conversación</p>
            <p style="font-size:12px;color:var(--color-text-muted)">Elige un chat de la lista para empezar a responder</p>
        </div>

        <div class="chat-conv__active" id="chatActive" hidden>
            <div class="chat-conv__header" id="chatConvHeader"></div>
            <div class="chat-conv__messages" id="chatMessages"></div>
            <div class="chat-conv__reply">
                <form id="chatReplyForm" style="display:flex;gap:8px;align-items:center">
                    <input type="text" id="chatReplyInput" class="form-control"
                           placeholder="Escribe tu respuesta..." autocomplete="off"
                           style="flex:1;font-size:13px;border-radius:8px">
                    <button type="submit" class="btn-primary"
                            style="padding:0 18px;height:38px;font-size:13px;border-radius:8px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap">
                        Enviar
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.chat-layout{
    display:grid;
    grid-template-columns:300px 1fr;
    height:calc(100vh - 175px);
    min-height:400px;
    border:1px solid var(--color-border);
    border-radius:12px;
    overflow:hidden;
}
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
.chat-conversation{display:flex;flex-direction:column;background:#fff;overflow:hidden}
.chat-conv__empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--color-text-muted)}
.chat-conv__header{
    padding:12px 18px;border-bottom:1px solid var(--color-border);
    display:flex;align-items:center;justify-content:space-between;
    flex-shrink:0;background:#fff;
}
.chat-conv__messages{
    flex:1;overflow-y:auto;padding:18px 20px;
    display:flex;flex-direction:column;gap:8px;
    background:var(--color-bg-secondary,#f8f8f6);
}
.chat-conv__reply{
    padding:12px 14px;border-top:1px solid var(--color-border);
    flex-shrink:0;background:#fff;
}
/* filas de mensajes con avatar */
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
.chat-bubble{max-width:72%;padding:9px 13px;font-size:13px;line-height:1.45;border-radius:14px;word-wrap:break-word}
.chat-bubble--visitor{background:var(--color-primary,#0E0E0C);color:#fff;border-bottom-left-radius:4px}
.chat-bubble--agent{background:#fff;border:1px solid var(--color-border);color:var(--color-text);border-bottom-right-radius:4px}
.chat-bubble__meta{display:flex;align-items:center;gap:6px;margin-top:3px;justify-content:flex-end}
.chat-bubble__time{font-size:10px;opacity:.5;font-family:var(--font-mono,monospace)}
.chat-bubble--visitor .chat-bubble__meta{justify-content:flex-start}
@media(max-width:768px){
    .chat-layout{grid-template-columns:1fr;height:auto}
    .chat-sessions{max-height:220px}
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
    var activeVisitor = '';   /* nombre del visitante activo */

    var listEl   = document.getElementById('chatSessionList');
    var emptyEl  = document.getElementById('chatEmpty');
    var activeEl = document.getElementById('chatActive');
    var headerEl = document.getElementById('chatConvHeader');
    var msgsEl   = document.getElementById('chatMessages');
    var replyF   = document.getElementById('chatReplyForm');
    var replyIn  = document.getElementById('chatReplyInput');
    var filterSt = document.getElementById('chatFilterStatus');

    /* ── sonido de notificación ── */
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

    /* ── helpers ── */
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

    /* ── cargar sesiones ── */
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

    /* ── abrir sesión ── */
    async function openSession(sid){
        activeSid=sid; lastMsgId=0; activeVisitor='';
        emptyEl.hidden=true; activeEl.hidden=false;
        msgsEl.innerHTML='<p style="text-align:center;font-size:12px;color:var(--color-text-muted);padding:16px 0">Cargando mensajes...</p>';
        loadSessions();
        try{
            var r=await fetch(API+'?session_id='+sid);
            var d=await r.json();
            if(!d.success) return;
            var s=d.session;
            headerEl.innerHTML=
                '<div style="display:flex;align-items:center;gap:10px">'+
                    '<div style="width:32px;height:32px;border-radius:50%;background:var(--color-primary,#0E0E0C);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">'+
                        (s.visitor_name||'?').split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase()+
                    '</div>'+
                    '<div>'+
                        '<div style="font-size:14px;font-weight:600">'+esc(s.visitor_name)+'</div>'+
                        (s.visitor_email?'<div style="font-size:11.5px;color:var(--color-text-muted)">'+esc(s.visitor_email)+'</div>':'')+
                    '</div>'+
                '</div>'+
                '<div style="display:flex;gap:6px;align-items:center">'+
                    '<span style="font-size:11px;padding:3px 8px;border-radius:99px;background:'+(s.status==='activo'?'#dcfce7;color:#15803d':'#fee2e2;color:#b91c1c')+'">'+(s.status==='activo'?'Activo':'Cerrado')+'</span>'+
                    (s.status==='activo'
                        ?'<button onclick="cerrarChat('+s.id+')" style="font-size:12px;padding:4px 10px;border-radius:6px;border:1px solid var(--color-border);background:transparent;cursor:pointer;color:var(--color-text-muted)">Cerrar chat</button>'
                        :'<button onclick="reabrirChat('+s.id+')" style="font-size:12px;padding:4px 10px;border-radius:6px;border:1px solid var(--color-border);background:transparent;cursor:pointer;color:var(--color-text-muted)">Reabrir</button>')+
                '</div>';

            activeVisitor=s.visitor_name||'?';
            msgsEl.innerHTML='';
            (d.messages||[]).forEach(function(m){
                addBubble(m);
                if(parseInt(m.id)>lastMsgId) lastMsgId=parseInt(m.id);
            });
            msgsEl.scrollTop=msgsEl.scrollHeight;
            replyIn.disabled=(s.status==='cerrado');
            replyIn.placeholder=s.status==='cerrado'?'Chat cerrado':'Escribe tu respuesta...';
            replyIn.focus();
            startMsgPoll();
        }catch(e){}
    }

    function addBubble(m){
        /* avatar */
        var avLabel = m.sender==='agent'
            ? 'Yo'
            : (activeVisitor||'?').split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase();
        var avClass = 'chat-av chat-av--'+m.sender;
        var av = '<div class="'+avClass+'" title="'+(m.sender==='agent'?'Agente':esc(activeVisitor))+'">'+avLabel+'</div>';

        var row=document.createElement('div');
        row.className='chat-row chat-row--'+m.sender;
        row.innerHTML=av+
            '<div class="chat-bubble chat-bubble--'+m.sender+'">'+
                esc(m.message)+
                '<div class="chat-bubble__meta"><span class="chat-bubble__time">'+fmtTime(m.created_at)+'</span></div>'+
            '</div>';
        msgsEl.appendChild(row);
    }

    /* ── enviar respuesta ── */
    replyF.addEventListener('submit',async function(e){
        e.preventDefault();
        var txt=replyIn.value.trim();
        if(!txt||!activeSid||replyIn.disabled) return;
        replyIn.value='';
        addBubble({sender:'agent',message:txt,created_at:new Date().toISOString().replace('T',' ').substring(0,19)});
        msgsEl.scrollTop = msgsEl.scrollHeight;
        try{
            var r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:activeSid,message:txt})});
            var d=await r.json();
            if(d.message_id>lastMsgId) lastMsgId=d.message_id;
        }catch(e){}
    });

    /* ── polling ── */
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

    /* ── cerrar / reabrir ── */
    window.cerrarChat=async function(sid){
        await fetch(API,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sid,status:'cerrado'})});
        loadSessions(); if(sid===activeSid) openSession(sid);
    };
    window.reabrirChat=async function(sid){
        await fetch(API,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sid,status:'activo'})});
        loadSessions(); if(sid===activeSid) openSession(sid);
    };

    filterSt.addEventListener('change',function(){ activeSid=null; emptyEl.hidden=false; activeEl.hidden=true; loadSessions(); });

    loadSessions();
    if(pollSes) clearInterval(pollSes);
    pollSes=setInterval(loadSessions,POLL_SES);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
