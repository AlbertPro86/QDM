/* ===== QUANTUN Digital — interacciones avanzadas ===== */
(function () {
  'use strict';

  /* =========================================================
     CONEXIÓN AL CRM QUANTUN Digital
     ========================================================= */
  var CRM_ENDPOINT       = '/CRM-QUANTUN-Digital/api/solicitudes_web.php';
  var SERVICIOS_ENDPOINT = '/CRM-QUANTUN-Digital/api/servicios_publico.php';

  async function submitToCRM(payload) {
    payload.submittedAt = new Date().toISOString();
    payload.source = 'quantun-web';

    if (CRM_ENDPOINT) {
      var res = await fetch(CRM_ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error('CRM respondió ' + res.status);
      return res.json().catch(function () { return {}; });
    }

    // Fallback local
    try {
      var leads = JSON.parse(localStorage.getItem('quantun_leads') || '[]');
      leads.push(payload);
      localStorage.setItem('quantun_leads', JSON.stringify(leads));
    } catch (e) {}
    return new Promise(function (r) { setTimeout(r, 650); });
  }

  /* ============ Catálogo de servicios (fallback estático) ============ */
  var ICONS = {
    web: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2"></rect><path d="M3 9h18M8 14h5"></path></svg>',
    marketing: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l16-7-3 16-5-4-3 3v-5z"></path></svg>',
    wordpress: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M5 9l4 9 3-9 3 9 4-9"></path></svg>',
    hosting: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6" rx="1.5"></rect><rect x="3" y="14" width="18" height="6" rx="1.5"></rect><path d="M7 7h.01M7 17h.01"></path></svg>',
    correos: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M4 7l8 6 8-6"></path></svg>',
    mantenimiento: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 00-5.4 5.4l-6 6 2 2 6-6a4 4 0 005.4-5.4l-2.3 2.3-2-2 2.3-2.3z"></path></svg>',
    // Genérico
    default: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>'
  };

  var SERVICES = {
    web: {
      title: 'Diseño Web adaptable',
      eyebrow: 'Servicio · Diseño',
      desc: 'Sitios responsive, modernos y rápidos, enfocados en experiencia de usuario (UI/UX) y en convertir visitas en clientes.',
      features: ['Diseño 100% responsive', 'UI/UX a medida', 'Optimización de velocidad', 'Hasta 5 secciones', 'Formulario de contacto', 'SEO básico on-page'],
      plans: [
        { name: 'Landing', tag: 'Inicia' },
        { name: 'Profesional', tag: 'Popular' },
        { name: 'Premium', tag: 'Full' }
      ]
    },
    marketing: {
      title: 'Marketing de Contenido',
      eyebrow: 'Servicio · Marketing',
      desc: 'Estrategias de contenido para atraer tráfico orgánico, mejorar tu posicionamiento y conectar con tus clientes.',
      features: ['Estrategia de contenidos', 'Calendario editorial', 'Redacción SEO', 'Optimización on-page', 'Reporte mensual', 'Palabras clave objetivo'],
      plans: [
        { name: 'Básico', tag: 'Inicia' },
        { name: 'Crecimiento', tag: 'Popular' },
        { name: 'Escala', tag: 'Pro' }
      ]
    },
    wordpress: {
      title: 'Diseño y Maquetado WordPress',
      eyebrow: 'Servicio · WordPress',
      desc: 'Diseño personalizado y maquetación profesional con WordPress, optimizado para velocidad, escalabilidad y fácil gestión.',
      features: ['Tema personalizado', 'Panel fácil de editar', 'Plugins esenciales', 'Optimización de carga', 'Backup inicial', 'Capacitación de uso'],
      plans: [
        { name: 'Starter', tag: 'Inicia' },
        { name: 'Negocio', tag: 'Popular' },
        { name: 'Tienda', tag: 'E-com' }
      ]
    },
    hosting: {
      title: 'Hosting y Dominio',
      eyebrow: 'Servicio · Infraestructura',
      desc: 'Te ayudo a elegir, configurar y mantener el mejor hosting y dominio para tu proyecto, con seguridad y soporte incluido.',
      features: ['Dominio .com incluido', 'Certificado SSL', 'Hosting optimizado', 'Copias de seguridad', 'Soporte técnico', 'Panel cPanel'],
      plans: [
        { name: 'Personal', tag: 'Inicia' },
        { name: 'Empresa', tag: 'Popular' },
        { name: 'Pro', tag: 'Alto tráfico' }
      ]
    },
    correos: {
      title: 'Correos Corporativos',
      eyebrow: 'Servicio · Identidad',
      desc: 'Configuración profesional de correos con tu dominio, integrados con Gmail o Outlook, para dar seriedad a tu marca.',
      features: ['Correos @tudominio', 'Integración Gmail/Outlook', 'Configuración en dispositivos', 'Antispam', 'Firmas profesionales', 'Soporte de uso'],
      plans: [
        { name: '3 cuentas', tag: 'Inicia' },
        { name: '10 cuentas', tag: 'Popular' },
        { name: 'Ilimitado', tag: 'Empresa' }
      ]
    },
    mantenimiento: {
      title: 'Actualizaciones Web',
      eyebrow: 'Servicio · Soporte',
      desc: 'Mantengo tu sitio actualizado, optimizado y seguro: ajustes técnicos, mejoras de rendimiento y respaldo constante.',
      features: ['Actualizaciones mensuales', 'Monitoreo de seguridad', 'Respaldo automático', 'Cambios de contenido', 'Optimización continua', 'Soporte prioritario'],
      plans: [
        { name: 'Esencial', tag: 'Inicia' },
        { name: 'Activo', tag: 'Popular' },
        { name: 'Total', tag: 'Pro' }
      ]
    }
  };

  /* ============ Datos estáticos de planes ============ */
  var PLANS = {
    basico: {
      title: 'Plan Básico',
      eyebrow: 'Suscripción anual · QUANTUN Digital',
      desc: 'Presencia digital profesional para emprendedores y pequeñas empresas.',
      price: '$309.000 COP / año',
      icon: 'web',
      features: ['Dominio .com', 'Hosting 1 GB', '5 Correos Corporativos Zoho Mail', 'Certificado SSL', 'Soporte Técnico']
    },
    pymes: {
      title: 'Plan Pymes',
      eyebrow: 'Suscripción anual · QUANTUN Digital',
      desc: 'Para negocios en crecimiento con web, correos corporativos y presencia completa.',
      price: '$580.000 COP / año',
      icon: 'marketing',
      features: ['Todo lo del plan Básico', 'Sitio Web Básico', 'Hosting 1 GB', '5 Correos Zoho Mail 5 GB c/u', 'Certificado SSL incluido']
    },
    enterprise: {
      title: 'Plan Enterprise',
      eyebrow: 'Suscripción anual · QUANTUN Digital',
      desc: 'Solución avanzada con CRM, mayor almacenamiento, correos ilimitados y actualizaciones continuas.',
      price: '$989.000 COP / año',
      icon: 'mantenimiento',
      features: ['Dominio .com + Hosting 50 GB', '20 Correos Zoho Mail 10 GB c/u', 'Google Analytics y Negocios', 'Certificado SSL Premium', 'Motor CRM Inmobiliario', 'Actualizaciones Web mensuales', 'Soporte prioritario dedicado']
    }
  };
  /* ============ Carga dinámica de servicios desde CRM ============ */
  async function loadServicesFromCRM() {
    try {
      var res = await fetch(SERVICIOS_ENDPOINT);
      var json = await res.json();
      if (!json.success || !json.data || !json.data.length) return false;

      var newServices = {};
      json.data.forEach(function(svc) {
        var key = svc.icono || ('svc_' + svc.id);
        if (!ICONS[key]) ICONS[key] = ICONS.default;

        var plans = (svc.planes || []).map(function(p) {
          return { name: p.nombre, tag: p.nombre };
        });

        newServices[key] = {
          title: svc.nombre,
          eyebrow: 'Servicio · QUANTUN Digital',
          desc: svc.descripcion || '',
          features: svc.features || [],
          plans: plans.length ? plans : [{ name: 'Cotizar', tag: 'Personalizado' }]
        };
      });

      SERVICES = newServices;
      return true;
    } catch(e) {
      console.warn('[QUANTUN] Servicios CRM no disponibles, usando datos locales:', e.message);
      return false;
    }
  }

  // openModal declarado en scope del IIFE para que renderServiceCards pueda accederlo
  var openModal = function() {};

  function renderServiceCards() {
    var grid = document.querySelector('.svc-grid');
    if (!grid) return;
    grid.innerHTML = '';
    var idx = 0;
    for (var key in SERVICES) {
      var svc = SERVICES[key];
      var btn = document.createElement('button');
      btn.className = 'svc reveal';
      btn.type = 'button';
      btn.setAttribute('data-service', key);
      if (idx > 0) btn.setAttribute('data-delay', String(idx));
      btn.innerHTML =
        '<span class="svc__icon" aria-hidden="true">' + (ICONS[key] || ICONS.default) + '</span>' +
        '<span class="svc__num">' + String(idx + 1).padStart(2, '0') + '</span>' +
        '<h3>' + svc.title + '</h3>' +
        '<p>' + svc.desc + '</p>' +
        '<span class="svc__more">Solicitar información <span class="svc__arrow">&rarr;</span></span>';
      grid.appendChild(btn);
      idx++;
    }
  }

  /* ============ Modal ============ */
  var modal = document.getElementById('svcModal');
  if (modal) {
    var elIcon = document.getElementById('modalIcon');
    var elEyebrow = document.getElementById('modalEyebrow');
    var elTitle = document.getElementById('modalTitle');
    var elDesc = document.getElementById('modalDesc');
    var elFeatures = document.getElementById('modalFeatures');
    var elPlans = document.getElementById('modalPlans');
    var elService = document.getElementById('leadService');
    var elPlan = document.getElementById('leadPlan');
    var form = document.getElementById('leadForm');
    var okBox = document.getElementById('leadOk');
    var lastFocus = null;

    openModal = function(key) {
      var s = SERVICES[key];
      if (!s) return;
      elIcon.innerHTML = ICONS[key] || ICONS.default || '';
      elEyebrow.textContent = s.eyebrow;
      elTitle.textContent = s.title;
      elDesc.textContent = s.desc;
      elService.value = s.title;

      elFeatures.innerHTML = s.features.map(function (f) { return '<li>' + f + '</li>'; }).join('');

      // Planes SIN precios
      elPlans.innerHTML = s.plans.map(function (p, i) {
        return '<button type="button" class="plan' + (i === 1 ? ' sel' : (s.plans.length === 1 ? ' sel' : '')) + '" data-plan="' + p.name + '">' +
          '<span class="plan__check">&#10003;</span>' +
          '<span class="plan__tag">' + p.tag + '</span>' +
          '<span class="plan__name">' + p.name + '</span>' +
          '</button>';
      }).join('');
      elPlan.value = s.plans[1] ? s.plans[1].name : (s.plans[0] && s.plans[0].name) || '';

      // reset form
      form.hidden = false;
      okBox.hidden = true;
      form.querySelector('.lead__foot').style.display = '';
      form.reset();
      elService.value = s.title;
      elPlan.value = s.plans[1] ? s.plans[1].name : (s.plans[0] && s.plans[0].name) || '';

      lastFocus = document.activeElement;
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(function () { var f = form.querySelector('input'); if (f) f.focus(); }, 280);
    }

    window.openPlanModal = function(planKey) {
      var p = PLANS[planKey];
      if (!p || !modal) return;
      var iconKey = p.icon || 'default';
      elIcon.innerHTML = ICONS[iconKey] || ICONS.default;
      elEyebrow.textContent = p.eyebrow;
      elTitle.textContent = p.title;
      elDesc.textContent = p.desc;
      elFeatures.innerHTML = p.features.map(function(f) { return '<li>' + f + '</li>'; }).join('');
      elPlans.innerHTML = '<button type="button" class="plan sel" data-plan="' + p.title + '">' +
        '<span class="plan__check">&#10003;</span>' +
        '<span class="plan__tag">Anual</span>' +
        '<span class="plan__name">' + p.price + '</span>' +
        '</button>';
      form.hidden = false;
      okBox.hidden = true;
      form.querySelector('.lead__foot').style.display = '';
      form.reset();
      elService.value = p.title;
      elPlan.value = p.title;
      lastFocus = document.activeElement;
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(function() { var f = form.querySelector('input'); if (f) f.focus(); }, 280);
    };

    function closeModal() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (lastFocus) lastFocus.focus();
    }

    modal.querySelectorAll('[data-close]').forEach(function (el) {
      el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });

    // Selección de plan
    elPlans.addEventListener('click', function (e) {
      var p = e.target.closest('.plan');
      if (!p) return;
      elPlans.querySelectorAll('.plan').forEach(function (x) { x.classList.remove('sel'); });
      p.classList.add('sel');
      elPlan.value = p.getAttribute('data-plan');
    });

    // Envío del formulario → CRM
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (!form.checkValidity()) { form.reportValidity(); return; }
      var btn = form.querySelector('.lead__submit');
      var prev = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Enviando&hellip;';

      var data = Object.fromEntries(new FormData(form).entries());
      try {
        await submitToCRM(data);
        form.querySelector('.lead__foot').style.display = 'none';
        okBox.hidden = false;
      } catch (err) {
        btn.disabled = false;
        btn.innerHTML = prev;
        alert('No se pudo enviar la solicitud. Intenta de nuevo o escríbeme por WhatsApp.');
        console.error(err);
      }
    });
  }

  /* ============ Acordeón FAQ ============ */
  document.querySelectorAll('.acc__q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.parentElement;
      var ans = item.querySelector('.acc__a');
      var open = item.classList.contains('open');
      var sibs = item.parentElement.querySelectorAll('.acc__item.open');
      sibs.forEach(function (s) { s.classList.remove('open'); s.querySelector('.acc__a').style.maxHeight = null; });
      if (!open) {
        item.classList.add('open');
        ans.style.maxHeight = ans.scrollHeight + 'px';
      }
    });
  });

  /* ============ Slider de testimonios ============ */
  (function () {
    var track = document.getElementById('tstTrack');
    if (!track) return;
    var cards = track.children;
    var dotsWrap = document.getElementById('tstDots');
    var prev = document.getElementById('tstPrev');
    var next = document.getElementById('tstNext');
    var idx = 0;

    function perView() {
      if (window.innerWidth <= 680) return 1;
      if (window.innerWidth <= 1024) return 2;
      return 3;
    }
    function maxIdx() { return Math.max(0, cards.length - perView()); }

    function render() {
      if (idx > maxIdx()) idx = maxIdx();
      var card = cards[0];
      var gap = 20;
      var step = card.getBoundingClientRect().width + gap;
      track.style.transform = 'translateX(' + (-idx * step) + 'px)';
      var pages = maxIdx() + 1;
      dotsWrap.innerHTML = '';
      for (var i = 0; i < pages; i++) {
        var b = document.createElement('button');
        if (i === idx) b.className = 'active';
        (function (n) { b.addEventListener('click', function () { idx = n; render(); }); })(i);
        dotsWrap.appendChild(b);
      }
    }
    next.addEventListener('click', function () { idx = idx >= maxIdx() ? 0 : idx + 1; render(); });
    prev.addEventListener('click', function () { idx = idx <= 0 ? maxIdx() : idx - 1; render(); });
    window.addEventListener('resize', render);
    render();

    var timer = setInterval(function () { idx = idx >= maxIdx() ? 0 : idx + 1; render(); }, 5000);
    track.parentElement.addEventListener('mouseenter', function () { clearInterval(timer); });
  })();

  /* ============ Efecto ripple ============ */
  document.addEventListener('pointerdown', function (e) {
    var btn = e.target.closest('.btn.fx');
    if (!btn) return;
    var r = document.createElement('span');
    r.className = 'ripple';
    var rect = btn.getBoundingClientRect();
    r.style.left = (e.clientX - rect.left) + 'px';
    r.style.top = (e.clientY - rect.top) + 'px';
    btn.appendChild(r);
    setTimeout(function () { r.remove(); }, 650);
  });

  /* ============ Botones magnéticos ============ */
  if (window.matchMedia('(pointer:fine)').matches) {
    document.querySelectorAll('.btn--primary.fx, .btn--accent.fx').forEach(function (btn) {
      btn.addEventListener('pointermove', function (e) {
        var r = btn.getBoundingClientRect();
        var x = e.clientX - r.left - r.width / 2;
        var y = e.clientY - r.top - r.height / 2;
        btn.style.transform = 'translate(' + x * 0.18 + 'px,' + (y * 0.18 - 2) + 'px)';
      });
      btn.addEventListener('pointerleave', function () { btn.style.transform = ''; });
    });
  }

  /* ============ Inicialización: cargar servicios del CRM ============ */
  (async function initServices() {
    var loaded = await loadServicesFromCRM();
    if (loaded) {
      renderServiceCards();
    }
    // Si falla, los servicios estáticos hardcoded funcionan como fallback
  })();

  /* ============ Pre-seleccion de plan desde seccion Planes ============ */
  window.preselectPlan = function(planName) {
    var el = document.getElementById('leadPlan');
    if (el) el.value = planName;
    var elSvc = document.getElementById('leadService');
    if (elSvc) elSvc.value = 'Plan ' + planName;
  };

  /* ============ Modal Área Cliente (Login) ============ */
  (function() {
    var loginModal = document.getElementById('loginModal');
    var openBtn    = document.getElementById('openLogin');
    if (!loginModal || !openBtn) return;

    function openLogin() {
      loginModal.classList.add('open');
      loginModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(function() {
        var f = loginModal.querySelector('input[type="email"]');
        if (f) f.focus();
      }, 240);
    }
    function closeLogin() {
      loginModal.classList.remove('open');
      loginModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openLogin);
    loginModal.querySelectorAll('[data-close-login]').forEach(function(el) {
      el.addEventListener('click', closeLogin);
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && loginModal.classList.contains('open')) closeLogin();
    });

    /* Mostrar / ocultar contraseña */
    var eyeBtn   = document.getElementById('loginEye');
    var passInput = document.getElementById('loginPass');
    var eyeIcon  = document.getElementById('eyeIcon');
    if (eyeBtn && passInput) {
      eyeBtn.addEventListener('click', function() {
        var show = passInput.type === 'password';
        passInput.type = show ? 'text' : 'password';
        eyeIcon.innerHTML = show
          ? '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
          : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>';
      });
    }

    /* Placeholder — bloquear submit hasta implementar auth real */
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = loginForm.querySelector('.login-form__submit');
        btn.disabled = true;
        btn.innerHTML = 'Verificando&hellip;';
        setTimeout(function() {
          btn.disabled = false;
          btn.innerHTML = 'Ingresar <span class="btn__ic">→</span>';
          var note = loginForm.querySelector('.login-form__note');
          if (note) { note.style.color = 'var(--q-lima-deep)'; note.textContent = 'Acceso disponible próximamente. Te avisaremos.'; }
        }, 1200);
      });
    }
  })();

  /* ============ Chat Widget en vivo ============ */
  (function() {
    var CHAT_API = '/CRM-QUANTUN-Digital/api/chat_publico.php';
    var POLL_MS  = 3500;

    var root   = document.getElementById('qchat');
    var fab    = document.getElementById('qchatFab');
    var win    = document.getElementById('qchatWindow');
    var body   = document.getElementById('qchatBody');
    var inputW = document.getElementById('qchatInput');
    var form   = document.getElementById('qchatForm');
    var msgIn  = document.getElementById('qchatMsg');
    var badge  = document.getElementById('qchatBadge');
    var closeB = document.getElementById('qchatClose');
    if (!root || !fab) return;

    var session  = JSON.parse(localStorage.getItem('qchat_session') || 'null');
    var lastId   = 0;
    var pollTimer= null;
    var isOpen   = false;
    var unread   = 0;

    /* — toggle ventana — */
    function toggle() {
      isOpen = !isOpen;
      root.classList.toggle('open', isOpen);
      win.hidden = !isOpen;
      if (isOpen) {
        unread = 0; updateBadge();
        if (session) { renderChat(); startPoll(); }
        else renderStart();
      } else { stopPoll(); }
    }
    fab.addEventListener('click', toggle);
    closeB.addEventListener('click', toggle);

    function updateBadge() {
      badge.textContent = unread;
      badge.hidden = unread < 1;
    }

    /* — pantalla inicial — */
    function renderStart() {
      inputW.hidden = true;
      var now = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
      body.innerHTML =
        '<div class="qchat__msg qchat__msg--agent" style="margin-bottom:4px">¡Hola! 👋 Bienvenido a <strong>QUANTUN Digital</strong>. ¿En qué podemos ayudarte hoy?<span class="qchat__msg-time">' + now + '</span></div>' +
        '<div class="qchat__msg qchat__msg--agent">Para iniciar la conversación déjanos tu nombre y te atendemos de inmediato.<span class="qchat__msg-time">' + now + '</span></div>' +
        '<div class="qchat__start">' +
          '<input type="text" id="qchatName" placeholder="Tu nombre *" required>' +
          '<input type="email" id="qchatEmail" placeholder="Tu correo (opcional)">' +
          '<button type="button" class="qchat__start-btn" id="qchatStartBtn">Iniciar chat →</button>' +
        '</div>';
      document.getElementById('qchatStartBtn').addEventListener('click', initSession);
    }

    /* — crear sesión — */
    async function initSession() {
      var name  = (document.getElementById('qchatName').value || '').trim();
      var email = (document.getElementById('qchatEmail').value || '').trim();
      if (!name) { document.getElementById('qchatName').focus(); return; }

      var btn = document.getElementById('qchatStartBtn');
      btn.disabled = true; btn.textContent = 'Conectando...';

      try {
        var res = await fetch(CHAT_API, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ action: 'init', name: name, email: email })
        });
        var data = await res.json();
        if (!data.success) throw new Error(data.error || 'Error');

        session = { id: data.session_id, token: data.token, name: name };
        localStorage.setItem('qchat_session', JSON.stringify(session));
        lastId = 0;
        renderChat();
        startPoll();
      } catch(e) {
        btn.disabled = false; btn.textContent = 'Iniciar chat';
        console.error('[QChat]', e);
      }
    }

    /* — renderizar chat — */
    function renderChat() {
      body.innerHTML = '';
      inputW.hidden = false;
      msgIn.focus();
      pollNow();
    }

    function addMessage(m) {
      var div = document.createElement('div');
      div.className = 'qchat__msg qchat__msg--' + m.sender;
      var t = m.created_at ? new Date(m.created_at.replace(' ','T')).toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'}) : '';
      div.innerHTML = escChat(m.message) + (t ? '<span class="qchat__msg-time">' + t + '</span>' : '');
      body.appendChild(div);
      body.scrollTop = body.scrollHeight;
    }

    function escChat(s) {
      var d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    /* — enviar mensaje — */
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      var txt = msgIn.value.trim();
      if (!txt || !session) return;
      msgIn.value = '';

      addMessage({ sender: 'visitor', message: txt, created_at: null });

      try {
        var res = await fetch(CHAT_API, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ action: 'send', session_id: session.id, token: session.token, message: txt })
        });
        var data = await res.json();
        if (data.message_id && data.message_id > lastId) lastId = data.message_id;
      } catch(e) { console.error('[QChat] send:', e); }
    });

    /* — polling — */
    async function pollNow() {
      if (!session) return;
      try {
        var url = CHAT_API + '?action=poll&session_id=' + session.id + '&token=' + session.token + '&after=' + lastId;
        var res = await fetch(url);
        var data = await res.json();
        if (!data.success) return;
        if (data.closed) { stopPoll(); inputW.hidden = true; return; }
        (data.messages || []).forEach(function(m) {
          if (parseInt(m.id) > lastId) lastId = parseInt(m.id);
          addMessage(m);
          if (!isOpen && m.sender === 'agent') { unread++; updateBadge(); }
        });
      } catch(e) {}
    }

    function startPoll() { stopPoll(); pollTimer = setInterval(pollNow, POLL_MS); }
    function stopPoll()  { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    /* — restaurar sesión previa — */
    if (session) {
      // Sesión previa existe, mostrar badge si hay mensajes
      fetch(CHAT_API + '?action=poll&session_id=' + session.id + '&token=' + session.token + '&after=0')
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (!d.success) { session = null; localStorage.removeItem('qchat_session'); return; }
          var agentMsgs = (d.messages || []).filter(function(m) { return m.sender === 'agent'; });
          if (agentMsgs.length > 1) { unread = 1; updateBadge(); }
        })
        .catch(function() {});
    }
  })();

})();