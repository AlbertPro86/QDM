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
      btn.addEventListener('click', (function(k) { return function() { openModal(k); }; })(key));
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

    function openModal(key) {
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

    function closeModal() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (lastFocus) lastFocus.focus();
    }

    document.querySelectorAll('.svc[data-service]').forEach(function (btn) {
      btn.addEventListener('click', function () { openModal(btn.getAttribute('data-service')); });
    });
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

})();
