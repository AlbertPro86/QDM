<?php
/**
 * CRM QUANTUN Digital - Footer
 */
?>
            </div><!-- .content-area -->
        </main><!-- .main-content -->
    </div><!-- .app-layout -->

    <!-- Toast System JS -->
    <script>
    (function() {
        // Crear overlay singleton
        const overlay = document.createElement('div');
        overlay.id = 'alertOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;pointer-events:none;';
        document.body.appendChild(overlay);

        let _dismissTimer = null;
        let _barTimer     = null;

        function closeAlert() {
            const box = document.getElementById('alertBox');
            if (!box) return;
            clearTimeout(_dismissTimer);
            cancelAnimationFrame(_barTimer);
            box.style.opacity = '0';
            box.style.transform = 'scale(.94) translateY(8px)';
            overlay.style.background = 'rgba(0,0,0,0)';
            setTimeout(() => {
                overlay.style.pointerEvents = 'none';
                overlay.innerHTML = '';
            }, 220);
        }

        window.showToast = function(message, type = 'info', duration = 4000) {
            clearTimeout(_dismissTimer);
            cancelAnimationFrame(_barTimer);
            overlay.innerHTML = '';

            const cfg = {
                success: { bg:'#f0fdf4', border:'#22c55e', icon_bg:'#dcfce7', icon_color:'#16a34a', title:'¡Hecho!',
                    svg:'<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
                error:   { bg:'#fff1f2', border:'#ef4444', icon_bg:'#fee2e2', icon_color:'#dc2626', title:'Error',
                    svg:'<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
                warning: { bg:'#fffbeb', border:'#f59e0b', icon_bg:'#fef3c7', icon_color:'#d97706', title:'Atención',
                    svg:'<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>' },
                info:    { bg:'#eff6ff', border:'#3b82f6', icon_bg:'#dbeafe', icon_color:'#2563eb', title:'Información',
                    svg:'<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
            };
            const c = cfg[type] || cfg.info;

            // Limpiar emoji de prefijo si viene (✅, ⚠️, etc.)
            const cleanMsg = message.replace(/^[\u{1F300}-\u{1FAFF}✅⚠️❌ℹ️]+\s*/u, '').trim();

            const box = document.createElement('div');
            box.id = 'alertBox';
            box.style.cssText = `
                background:${c.bg};
                border:1.5px solid ${c.border}33;
                border-radius:20px;
                padding:32px 28px 24px;
                max-width:360px;
                width:90%;
                box-shadow:0 24px 60px rgba(0,0,0,0.14),0 0 0 1px ${c.border}22;
                text-align:center;
                position:relative;
                opacity:0;
                transform:scale(.94) translateY(8px);
                transition:opacity .22s ease,transform .22s ease;
            `;
            box.innerHTML = `
                <button onclick="(function(){var b=document.getElementById('alertBox');if(b){b.style.opacity='0';b.style.transform='scale(.94) translateY(8px)';document.getElementById('alertOverlay').style.background='rgba(0,0,0,0)';setTimeout(function(){var o=document.getElementById('alertOverlay');if(o){o.style.pointerEvents='none';o.innerHTML=''}},220)}})()"
                    style="position:absolute;top:12px;right:12px;width:28px;height:28px;border:none;background:rgba(0,0,0,0.06);border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;transition:background .15s;font-size:16px;line-height:1"
                    onmouseenter="this.style.background='rgba(0,0,0,0.12)'" onmouseleave="this.style.background='rgba(0,0,0,0.06)'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div style="width:56px;height:56px;border-radius:50%;background:${c.icon_bg};display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <svg width="28" height="28" fill="none" stroke="${c.icon_color}" viewBox="0 0 24 24" stroke-width="2">${c.svg}</svg>
                </div>
                <div style="font-size:15px;font-weight:800;color:#0f172a;margin-bottom:8px;letter-spacing:-.01em">${c.title}</div>
                <div style="font-size:13px;color:#475569;line-height:1.6">${cleanMsg}</div>
                <div style="margin-top:20px;height:3px;border-radius:99px;background:rgba(0,0,0,0.07);overflow:hidden">
                    <div id="alertBar" style="height:100%;width:100%;border-radius:99px;background:${c.border};transform-origin:left;transition:none"></div>
                </div>
            `;
            overlay.appendChild(box);
            overlay.style.background = 'rgba(0,0,0,0.18)';
            overlay.style.pointerEvents = 'auto';
            overlay.onclick = function(e) { if (e.target === overlay) closeAlert(); };

            // Animate in
            requestAnimationFrame(() => requestAnimationFrame(() => {
                box.style.opacity = '1';
                box.style.transform = 'scale(1) translateY(0)';
            }));

            // Progress bar
            const bar = document.getElementById('alertBar');
            const start = performance.now();
            function tick(now) {
                const elapsed = now - start;
                const pct = Math.max(0, 1 - elapsed / duration);
                if (bar) bar.style.transform = 'scaleX(' + pct + ')';
                if (elapsed < duration) _barTimer = requestAnimationFrame(tick);
            }
            _barTimer = requestAnimationFrame(tick);

            // Auto-dismiss
            _dismissTimer = setTimeout(closeAlert, duration);
        };
    })();

    // CSRF Token helper for fetch
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    // Global fetch wrapper to automatically include session credentials
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const [resource, config] = args;
        const fetchConfig = config || {};
        // Always include credentials for API calls (and same-origin requests)
        if (typeof resource === 'string' && (resource.startsWith('api/') || resource.startsWith('/api/'))) {
            fetchConfig.credentials = 'include';
        } else if (fetchConfig && !fetchConfig.credentials) {
            fetchConfig.credentials = 'include';
        }
        return originalFetch.apply(this, [resource, fetchConfig]);
    };

    // ── Modal de confirmación custom ────────────────────────
    (function() {
        const overlay = document.createElement('div');
        overlay.id = 'confirmModal';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:opacity .18s,visibility .18s;backdrop-filter:blur(2px)';
        overlay.innerHTML = `
            <div id="confirmBox" style="background:#fff;border-radius:18px;padding:32px 32px 24px;max-width:400px;width:90%;box-shadow:0 24px 64px rgba(0,0,0,0.18);transform:translateY(12px) scale(.97);transition:transform .18s,opacity .18s;opacity:0;text-align:center">
                <div id="confirmIcon" style="width:52px;height:52px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <svg width="24" height="24" fill="none" stroke="#dc2626" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 id="confirmTitle" style="font-size:17px;font-weight:800;color:#0f172a;margin:0 0 8px"></h3>
                <p id="confirmMsg" style="font-size:13px;color:#64748b;margin:0 0 24px;line-height:1.6"></p>
                <div style="display:flex;gap:10px;justify-content:center">
                    <button id="confirmCancel" style="flex:1;padding:10px 16px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;font-size:13px;font-weight:700;color:#64748b;cursor:pointer;transition:all .15s"
                        onmouseenter="this.style.borderColor='#94a3b8';this.style.color='#475569'"
                        onmouseleave="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">Cancelar</button>
                    <button id="confirmOk" style="flex:1;padding:10px 16px;border-radius:10px;border:none;background:#dc2626;font-size:13px;font-weight:700;color:#fff;cursor:pointer;transition:background .15s"
                        onmouseenter="this.style.background='#b91c1c'"
                        onmouseleave="this.style.background='#dc2626'">Eliminar</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);

        function openConfirm() {
            overlay.style.opacity = '1';
            overlay.style.visibility = 'visible';
            const box = document.getElementById('confirmBox');
            box.style.opacity = '1';
            box.style.transform = 'translateY(0) scale(1)';
        }
        function closeConfirm() {
            overlay.style.opacity = '0';
            overlay.style.visibility = 'hidden';
            const box = document.getElementById('confirmBox');
            box.style.opacity = '0';
            box.style.transform = 'translateY(12px) scale(.97)';
        }

        overlay.addEventListener('click', function(e) { if (e.target === overlay) closeConfirm(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && overlay.style.visibility === 'visible') closeConfirm(); });

        window.confirmAction = function(message, { title = '¿Eliminar?', okText = 'Eliminar', okColor = '#dc2626', okHover = '#b91c1c' } = {}) {
            return new Promise((resolve) => {
                document.getElementById('confirmTitle').textContent = title;
                document.getElementById('confirmMsg').textContent = message;
                const okBtn = document.getElementById('confirmOk');
                okBtn.textContent = okText;
                okBtn.style.background = okColor;
                okBtn.onmouseenter = () => okBtn.style.background = okHover;
                okBtn.onmouseleave = () => okBtn.style.background = okColor;

                openConfirm();
                document.getElementById('confirmOk').onclick = () => { closeConfirm(); resolve(true); };
                document.getElementById('confirmCancel').onclick = () => { closeConfirm(); resolve(false); };
            });
        };
    })();

    // Format money helper
    function formatMoney(amount, symbol = '$') {
        return symbol + ' ' + parseFloat(amount).toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }) + ' COP';
    }
    // Solo el número formateado, sin símbolo de moneda — usar en cards grandes para evitar wrap
    function moneyNum(amount) {
        return '$ ' + parseFloat(amount).toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // Close sidebar on window resize (desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    /* ── Notificaciones / Pendientes ──────────────────────── */
    function fmtMoney(n) {
        return '$ ' + parseFloat(n).toLocaleString('es-CO', {minimumFractionDigits:0, maximumFractionDigits:0});
    }

    function toggleNotifDropdown(e) {
        e.stopPropagation();
        const dd = document.getElementById('notifDropdown');
        dd.classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('notifWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            document.getElementById('notifDropdown').classList.remove('open');
        }
    });

    async function loadPendientes() {
        try {
            const r = await fetch('api/pendientes.php', { credentials: 'include' });
            const d = await r.json();
            if (!d.success) return;

            const badge   = document.getElementById('notifBadge');
            const total   = document.getElementById('notifTotal');
            const content = document.getElementById('notifContent');

            // Badge
            if (d.total > 0) {
                badge.textContent = d.total > 99 ? '99+' : d.total;
                badge.classList.add('visible');
            } else {
                badge.classList.remove('visible');
            }
            if (total) total.textContent = d.total + ' ítem' + (d.total !== 1 ? 's' : '');

            if (d.total === 0) {
                content.innerHTML = '<div class="notif-empty" style="padding:36px 18px"><svg style="margin:0 auto 8px;display:block;opacity:.3" width="28" height="28" fill="none" stroke="#64748b" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Sin pendientes. ¡Todo al día!</div>';
                return;
            }

            let html = '';

            if (d.vencidas.length) {
                html += '<div class="notif-group-label" style="color:#ef4444;background:#fef2f2"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:6px;vertical-align:middle"></span>Vencidas (' + d.vencidas.length + ')</div>';
                html += d.vencidas.map(item => notifItemHtml(item, '#ef4444')).join('');
            }

            if (d.pendientes.length) {
                html += '<div class="notif-group-label" style="color:#f59e0b;background:#fffbeb"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:6px;vertical-align:middle"></span>Pendientes (' + d.pendientes.length + ')</div>';
                html += d.pendientes.map(item => notifItemHtml(item, '#f59e0b')).join('');
            }

            if (d.renovaciones.length) {
                html += '<div class="notif-group-label" style="color:#3b82f6;background:#eff6ff"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3b82f6;margin-right:6px;vertical-align:middle"></span>Renovaciones próximas</div>';

                // Agrupar por cliente y calcular totales
                const porCliente = {};
                d.renovaciones.forEach(item => {
                    if (!porCliente[item.cliente]) {
                        porCliente[item.cliente] = {
                            items: [],
                            totalMonto: 0,
                            url: item.url,
                            minDias: null
                        };
                    }
                    porCliente[item.cliente].items.push(item);
                    porCliente[item.cliente].totalMonto += item.monto;
                    if (porCliente[item.cliente].minDias === null || item.dias < porCliente[item.cliente].minDias) {
                        porCliente[item.cliente].minDias = item.dias;
                    }
                });

                // Mostrar una sola fila por cliente con totales
                Object.keys(porCliente).forEach(cliente => {
                    const datos = porCliente[cliente];
                    const diasStr = datos.minDias !== null
                        ? (datos.minDias === 0 ? 'Hoy' : datos.minDias > 0 ? 'vence en ' + datos.minDias + 'd' : Math.abs(datos.minDias) + 'd vencida')
                        : 'Sin fecha';
                    const diasColor = datos.minDias !== null && datos.minDias < 0 ? '#ef4444'
                        : datos.minDias <= 7 ? '#f59e0b' : '#64748b';

                    html += `<a href="${datos.url}" class="notif-item" style="display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-bottom:1px solid #f1f5f9;gap:12px">
                        <span class="notif-item-dot" style="background:#3b82f6;flex-shrink:0"></span>
                        <div style="flex:1;min-width:0;font-size:12px;color:#475569">${cliente}</div>
                        <div style="color:#10b981;font-weight:500;font-size:12px;white-space:nowrap">${fmtMoney(datos.totalMonto)}</div>
                        <div style="color:${diasColor};font-size:11px;white-space:nowrap;font-weight:400">${diasStr}</div>
                    </a>`;
                });
            }

            content.innerHTML = html;
        } catch(e) { console.error('Error pendientes:', e); }
    }

    function notifItemHtml(item, color) {
        const diasStr = item.dias !== null
            ? (item.dias === 0 ? 'Hoy' : item.dias < 0 ? Math.abs(item.dias) + 'd vencida' : 'en ' + item.dias + 'd')
            : 'Sin fecha';
        const diasColor = item.dias !== null && item.dias < 0 ? '#ef4444'
            : item.dias <= 7 ? '#f59e0b' : '#64748b';
        return `<a href="${item.url}" class="notif-item">
            <span class="notif-item-dot" style="background:${color}"></span>
            <div class="notif-item-body">
                <div class="notif-item-title">${item.titulo}</div>
                <div class="notif-item-sub">${item.cliente}</div>
            </div>
            <div class="notif-item-right">
                <div class="notif-item-monto" style="color:${item.tx_tipo==='ingreso'?'#10b981':'#ef4444'}">${fmtMoney(item.monto)}</div>
                <div class="notif-item-dias" style="color:${diasColor}">${diasStr}</div>
            </div>
        </a>`;
    }

    // Cargar al inicio y cada 60 seg
    loadPendientes();
    setInterval(loadPendientes, 60000);
    </script>
</body>
</html>
