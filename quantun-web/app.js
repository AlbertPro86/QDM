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
      title: 'Asesoría Personalizada',
      eyebrow: 'Servicio · Asesoría',
      desc: 'Orientación y acompañamiento personalizado para que tomes mejores decisiones digitales y hagas crecer tu negocio en internet.',
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
      price: '$309.000 COP / Año',
      icon: 'web',
      features: ['Dominio .com', 'Landing Page Profesional', 'Hosting 1 GB', '5 Correos Corporativos (5 GB c/u)', 'Certificado SSL', 'Soporte Técnico Anual']
    },
    pymes: {
      title: 'Plan Pymes',
      eyebrow: 'Suscripción anual · QUANTUN Digital',
      desc: 'Para negocios en crecimiento que necesitan una presencia digital más completa y herramientas para impulsar sus ventas.',
      price: '$580.000 COP / Año',
      icon: 'marketing',
      features: ['Todo lo del Plan Básico', 'Sitio Web Completo (5 páginas)', 'Hosting 3 GB', '5 Correos Corporativos (5 GB c/u)', 'Certificado SSL incluido', '1 Actualización Mensual', 'WhatsApp y Redes Sociales', 'Acceso a la Comunidad']
    },
    enterprise: {
      title: 'Plan Enterprise',
      eyebrow: 'Suscripción anual · QUANTUN Digital',
      desc: 'Solución avanzada para empresas que buscan gestionar sus servicios, clientes y procesos desde una sola plataforma.',
      price: '$989.000 COP / Año',
      icon: 'mantenimiento',
      features: ['Dominio .com', 'Sitio Web Profesional', 'Hosting 5 GB', 'Motor de Servicios', '5 Correos Corporativos (5 GB c/u)', 'Google Analytics', 'Google Negocios', 'Certificado SSL', '1 Actualización Mensual', 'Soporte Técnico', 'Comunidad Exclusiva', 'Área de Cliente']
    }
  };
  /* ============ Detalles de planes (modal informativo) ============ */
  var PDETAIL_ICONS = {
    globe: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    rocket: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    server: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>',
    mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    wrench: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>',
    calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    refresh: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>',
    smartphone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
    mappin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    cogs: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>'
  };

  var PLAN_DETAILS = {
    basico: {
      title: 'Plan Básico',
      eyebrow: 'Detalle del plan · Básico',
      items: [
        { icon: 'globe',    name: 'Dominio .com',            notes: ['Incluye dominio .com por 1 año.', 'Otras extensiones pueden generar costos adicionales.', 'Transferencias o migraciones futuras no están incluidas.'] },
        { icon: 'rocket',   name: 'Landing Page',            notes: ['Sitio web de una sola página.', 'Diseño profesional para presentar tu negocio.', 'Puede incluir información, servicios, galería, formularios y WhatsApp.', 'No incluye actualizaciones de contenido posteriores.'] },
        { icon: 'server',   name: 'Hosting 1 GB',            notes: ['Espacio para alojar tu sitio web.', 'Mantiene tu página disponible en internet.', 'Incluye configuración inicial.'] },
        { icon: 'mail',     name: '5 Correos Corporativos',  notes: ['Incluye 5 cuentas de correo.', '5 GB de almacenamiento por cuenta.', 'Todos los correos deben mantener la misma capacidad.', 'Si se requiere más espacio, el valor del servicio cambia.', 'Requiere dominio, hosting y SSL activos.', 'Si ya tienes correos con otro proveedor, se debe revisar la migración previamente.'] },
        { icon: 'shield',   name: 'Certificado SSL',         notes: ['Incluido sin costo adicional.', 'Protege la navegación de tu sitio.', 'Activa el protocolo HTTPS.'] },
        { icon: 'wrench',   name: 'Soporte Técnico',         notes: ['Monitoreo y soporte técnico anual.', 'Mantiene los servicios operativos.', 'No incluye cambios de textos, imágenes o diseño web.'] },
        { icon: 'user',     name: 'Área de Cliente',         notes: ['Consulta tus servicios.', 'Revisa renovaciones.', 'Gestiona solicitudes de soporte.'] },
        { icon: 'calendar', name: 'Vigencia',                notes: ['Todos los servicios son por 1 año.'] },
        { icon: 'users',    name: 'Comunidad',               notes: ['Este plan no incluye acceso a la comunidad privada.'] }
      ]
    },
    pymes: {
      title: 'Plan Pymes',
      eyebrow: 'Detalle del plan · Pymes',
      items: [
        { icon: 'rocket',     name: 'Sitio Web Completo (5 Páginas)', notes: ['Sitio web profesional de hasta 5 páginas.', 'Páginas incluidas: Home, Nosotros, Servicios, Blog y Contacto.', 'Diseño adaptable para móviles, tablets y computadores.', 'Incluye botón de WhatsApp y enlaces a redes sociales.'] },
        { icon: 'globe',      name: 'Dominio .com',                   notes: ['Incluye dominio .com por 1 año.', 'Otras extensiones pueden generar costos adicionales.', 'Transferencias o migraciones futuras no están incluidas.'] },
        { icon: 'server',     name: 'Hosting 3 GB',                   notes: ['3 GB de espacio para alojar tu sitio web.', 'Mayor capacidad para imágenes, documentos y contenido.', 'Mantiene tu sitio disponible en internet.'] },
        { icon: 'mail',       name: '5 Correos Corporativos',         notes: ['Incluye 5 cuentas de correo corporativo.', '5 GB de almacenamiento por cuenta.', 'Todos los correos deben mantener la misma capacidad.', 'Si se requiere ampliar el almacenamiento, el valor del servicio cambia.', 'Requiere dominio, hosting y SSL activos.', 'Si ya cuentas con correos corporativos, se debe revisar la migración previamente.'] },
        { icon: 'shield',     name: 'Certificado SSL',                notes: ['Incluido sin costo adicional.', 'Protección y navegación segura mediante HTTPS.'] },
        { icon: 'refresh',    name: '1 Actualización Mensual',        notes: ['Incluye un cambio mensual sobre el sitio web.', 'Puede ser: publicación de artículos, cambio de textos, actualización de imágenes, carga de documentos, creación de una nueva página sencilla o ajustes menores de contenido.', 'Cambios adicionales podrán cotizarse por separado.'] },
        { icon: 'smartphone', name: 'WhatsApp y Redes Sociales',      notes: ['Integración directa con WhatsApp.', 'Acceso rápido a redes sociales desde el sitio web.', 'Facilita el contacto con clientes potenciales.'] },
        { icon: 'wrench',     name: 'Soporte Técnico',                notes: ['Supervisión y soporte técnico durante toda la vigencia.', 'Monitoreo de dominio, hosting, correos y funcionamiento general.'] },
        { icon: 'users',      name: 'Comunidad de Crecimiento',       notes: ['Acceso exclusivo a recursos y contenido de apoyo para tu negocio.', 'Temas: marketing digital, redes sociales, sitios web, comercio electrónico, modelos de negocio, ventas, estrategias comerciales y plantillas prácticas.'] },
        { icon: 'user',       name: 'Área de Cliente',                notes: ['Consulta tus servicios activos.', 'Revisa renovaciones y facturación.', 'Gestiona solicitudes de soporte.'] },
        { icon: 'calendar',   name: 'Vigencia',                       notes: ['Todos los servicios incluidos tienen una duración de 1 año.'] }
      ]
    },
    enterprise: {
      title: 'Plan Enterprise',
      eyebrow: 'Detalle del plan · Enterprise',
      items: [
        { icon: 'rocket',   name: 'Sitio Web Profesional',       notes: ['Sitio web corporativo para tu empresa.', 'Diseño adaptable para móviles, tablets y computadores.', 'Incluye formulario de contacto, botón de WhatsApp y enlaces a redes sociales.', 'Ideal para presentar tu negocio, servicios y canales de contacto.'] },
        { icon: 'globe',    name: 'Dominio .com',                 notes: ['Incluye dominio .com por 1 año.', 'Otras extensiones pueden generar costos adicionales.', 'Transferencias o migraciones futuras no están incluidas.', 'Inicialmente el dominio es administrado por nuestra empresa.', 'Para trasladarlo a otro proveedor, el cliente deberá estar a paz y salvo y asumir los costos correspondientes.'] },
        { icon: 'server',   name: 'Hosting 5 GB',                notes: ['5 GB de espacio para alojar tu sitio web y servicios asociados.', 'Mayor capacidad para crecimiento y almacenamiento de contenido.', 'Mantiene tu plataforma disponible en internet.'] },
        { icon: 'mail',     name: '5 Correos Corporativos',      notes: ['Incluye 5 cuentas de correo corporativo.', '5 GB de almacenamiento por cuenta.', 'Todos los correos deben mantener la misma capacidad.', 'Si se requiere ampliar el almacenamiento, el valor del servicio cambia.', 'Requiere dominio, hosting y certificado SSL activos.', 'Si ya cuentas con correos corporativos, se debe revisar la migración previamente mediante una reunión técnica.'] },
        { icon: 'cogs',     name: 'Motor de Servicios',          notes: ['Herramienta para administrar clientes, servicios y procesos.', 'Permite centralizar información importante de tu negocio.', 'Facilita el seguimiento de solicitudes y actividades.', 'Diseñado para mejorar la organización y eficiencia operativa.'] },
        { icon: 'chart',    name: 'Google Analytics',            notes: ['Configuración e integración con Google Analytics.', 'Permite medir visitas, comportamiento y rendimiento del sitio web.', 'Acceso a estadísticas para apoyar la toma de decisiones.'] },
        { icon: 'mappin',   name: 'Google Negocios',             notes: ['Configuración de perfil empresarial en Google.', 'Mejora la visibilidad de tu negocio en búsquedas y Google Maps.', 'Facilita que los clientes encuentren tu empresa.'] },
        { icon: 'shield',   name: 'Certificado SSL',             notes: ['Incluido sin costo adicional.', 'Protección y navegación segura mediante HTTPS.', 'Mejora la confianza de los visitantes.'] },
        { icon: 'refresh',  name: '1 Actualización Mensual',     notes: ['Incluye un cambio mensual sobre el sitio web.', 'Puede ser: publicación de artículos, cambio de textos, actualización de imágenes, carga de documentos, creación de una nueva página sencilla o ajustes menores de contenido.', 'Cambios adicionales podrán cotizarse por separado.'] },
        { icon: 'wrench',   name: 'Soporte Técnico',             notes: ['Supervisión y soporte técnico durante toda la vigencia.', 'Monitoreo de dominio, hosting, correos y funcionamiento general de la plataforma.', 'Garantiza la continuidad operativa de los servicios contratados.'] },
        { icon: 'users',    name: 'Comunidad Exclusiva',         notes: ['Acceso a contenido y recursos para fortalecer tu negocio.', 'Temas: marketing digital, sitios web, redes sociales, comercio electrónico, automatización, modelos de negocio, ventas y estrategias comerciales.', 'Recursos y plantillas de apoyo incluidos.'] },
        { icon: 'user',     name: 'Área de Cliente',             notes: ['Consulta tus servicios activos.', 'Revisa renovaciones y facturación.', 'Gestiona solicitudes de soporte.', 'Accede al historial de servicios contratados.'] },
        { icon: 'calendar', name: 'Vigencia',                    notes: ['Todos los servicios incluidos tienen una duración de 1 año.'] }
      ]
    }
  };

  var planDetailModal = document.getElementById('planDetailModal');
  if (planDetailModal) {
    window.openPlanDetail = function(planKey) {
      var d = PLAN_DETAILS[planKey];
      if (!d) return;
      document.getElementById('planDetailEyebrow').textContent = d.eyebrow;
      document.getElementById('planDetailTitle').textContent = d.title;
      document.getElementById('planDetailBody').innerHTML = d.items.map(function(item) {
        return '<div class="pdetail__item">' +
          '<div class="pdetail__item-head"><span class="pdetail__icon">' + (PDETAIL_ICONS[item.icon] || PDETAIL_ICONS.globe) + '</span><strong>' + item.name + '</strong></div>' +
          '<ul class="pdetail__notes">' + item.notes.map(function(n) { return '<li>' + n + '</li>'; }).join('') + '</ul>' +
          '</div>';
      }).join('');
      planDetailModal.classList.add('open');
      planDetailModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };
    function closePlanDetail() {
      planDetailModal.classList.remove('open');
      planDetailModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
    planDetailModal.querySelectorAll('[data-close-pdetail]').forEach(function(el) {
      el.addEventListener('click', closePlanDetail);
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && planDetailModal.classList.contains('open')) closePlanDetail();
    });
  }

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

      // Obtener token reCAPTCHA v3 (invisible para el usuario)
      try {
        if (typeof grecaptcha !== 'undefined') {
          var rcToken = await new Promise(function(resolve) {
            grecaptcha.ready(function() {
              grecaptcha.execute('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', { action: 'cotizacion' }).then(resolve);
            });
          });
          data.recaptcha_token = rcToken;
        }
      } catch(rcErr) { /* si falla reCAPTCHA, el servidor decide */ }

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
    var CHAT_API = window.location.hostname === 'localhost'
      ? '/CRM-QUANTUN-Digital/api/chat_publico.php'
      : '/crm/api/chat_publico.php';
    var POLL_MS  = 3500;

    var root   = document.getElementById('qchat');
    var fab    = document.getElementById('qchatFab');
    var win    = document.getElementById('qchatWindow');
    var chatBody = document.getElementById('qchatBody');
    var inputW = document.getElementById('qchatInput');
    var form   = document.getElementById('qchatForm');
    var msgIn  = document.getElementById('qchatMsg');
    var badge  = document.getElementById('qchatBadge');
    var closeB = document.getElementById('qchatClose');
    if (!root || !fab) return;

    var session        = JSON.parse(localStorage.getItem('qchat_session') || 'null');
    var lastId         = 0;
    var pollTimer      = null;
    var isOpen         = false;
    var unread         = 0;
    var historyLoaded  = false; /* true tras la primera carga; luego el poll solo pinta mensajes del agente */

    /* — sonido de notificación (Web Audio API, sin archivos externos) — */
    function playNotif() {
      try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        [[660, 0], [880, 0.13]].forEach(function(note) {
          var o = ctx.createOscillator(), g = ctx.createGain();
          o.connect(g); g.connect(ctx.destination);
          o.type = 'sine';
          o.frequency.value = note[0];
          g.gain.setValueAtTime(0, ctx.currentTime + note[1]);
          g.gain.linearRampToValueAtTime(0.22, ctx.currentTime + note[1] + 0.02);
          g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + note[1] + 0.38);
          o.start(ctx.currentTime + note[1]);
          o.stop(ctx.currentTime + note[1] + 0.38);
        });
      } catch(e) {}
    }

    /* — construir fila con avatar — */
    function buildRow(sender, msgHtml, timeStr) {
      var visIni = session && session.name ? session.name.charAt(0).toUpperCase() : '?';
      var av = sender === 'agent'
        ? '<div class="qchat__av qchat__av--agent" title="Asesor QUANTUN">Q</div>'
        : '<div class="qchat__av qchat__av--visitor" title="Tú">' + visIni + '</div>';
      return '<div class="qchat__row qchat__row--' + sender + '">' +
        av +
        '<div class="qchat__msg qchat__msg--' + sender + '">' +
          msgHtml +
          (timeStr ? '<span class="qchat__msg-time">' + timeStr + '</span>' : '') +
        '</div>' +
      '</div>';
    }

    /* — abrir ventana — */
    function openFull() {
      win.hidden = false;
      isOpen = true;
      root.classList.add('open');
      unread = 0; updateBadge();
      if (session) { renderChat(); startPoll(); }
      else renderStart();
    }

    /* — cerrar ventana — */
    function closeWin() {
      win.hidden = true;
      isOpen = false;
      root.classList.remove('open');
      stopPoll();
    }

    /* — toggle FAB — */
    function toggle() {
      if (isOpen) { closeWin(); }
      else { openFull(); }
    }

    fab.addEventListener('click', toggle);
    closeB.addEventListener('click', function(e) {
      e.stopPropagation();
      closeWin();
      sessionStorage.setItem('qchat_dismissed', '1');
    });

    function updateBadge() {
      badge.textContent = unread;
      badge.hidden = unread < 1;
    }

    /* — pantalla inicial con saludo — */
    function renderStart() {
      inputW.hidden = true;
      var now = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
      chatBody.innerHTML =
        buildRow('agent', '¡Hola! Bienvenido a <strong>QUANTUN Digital</strong>. ¿En qué podemos ayudarte?', now) +
        '<div class="qchat__start">' +
          '<input type="text" id="qchatName" placeholder="Tu nombre *">' +
          '<input type="email" id="qchatEmail" placeholder="Tu correo (opcional)">' +
          '<button type="button" class="qchat__start-btn" id="qchatStartBtn">Iniciar chat →</button>' +
          '<p class="qchat__start-err" id="qchatStartErr" hidden></p>' +
        '</div>';
      document.getElementById('qchatStartBtn').addEventListener('click', initSession);
      setTimeout(function() { var n = document.getElementById('qchatName'); if (n) n.focus(); }, 150);
      chatBody.addEventListener('input', function() {
        var errEl = document.getElementById('qchatStartErr');
        if (errEl) errEl.hidden = true;
      }, { once: true });
    }

    /* — crear sesión — */
    async function initSession() {
      var nameEl  = document.getElementById('qchatName');
      var emailEl = document.getElementById('qchatEmail');
      var name    = (nameEl ? nameEl.value : '').trim();
      var email   = (emailEl ? emailEl.value : '').trim();
      if (!name) { if (nameEl) nameEl.focus(); return; }

      var btn = document.getElementById('qchatStartBtn');
      if (btn) { btn.disabled = true; btn.textContent = 'Conectando...'; }

      try {
        var res = await fetch(CHAT_API, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ action: 'init', name: name, email: email })
        });
        var data = await res.json();
        if (!data.success) throw new Error(data.error || 'Error al conectar');

        session = { id: data.session_id, token: data.token, name: name };
        localStorage.setItem('qchat_session', JSON.stringify(session));
        lastId = 0;
        renderChat();
        startPoll();
      } catch(e) {
        if (btn) { btn.disabled = false; btn.textContent = 'Iniciar chat →'; }
        var errEl = document.getElementById('qchatStartErr');
        if (errEl) {
          var msg = e.message || '';
          if (msg === 'Failed to fetch' || msg.indexOf('fetch') !== -1) {
            msg = 'No se pudo conectar. Asegúrate de abrir el sitio desde http://localhost.';
          }
          errEl.textContent = msg || 'Error al conectar. Intenta de nuevo.';
          errEl.hidden = false;
        }
        console.error('[QChat]', e);
      }
    }

    /* — vista de conversación — */
    function renderChat() {
      historyLoaded = false;   /* resetear: el próximo pollNow carga el historial completo */
      chatBody.innerHTML = '<p class="qchat__typing">Cargando conversación…</p>';
      inputW.hidden = false;
      setTimeout(function() { if (msgIn) msgIn.focus(); }, 100);
      pollNow();
    }

    function addMessage(m) {
      /* quitar "Cargando..." si existe */
      var loading = chatBody.querySelector('.qchat__typing');
      if (loading) loading.remove();

      var t = m.created_at
        ? new Date(m.created_at.replace(' ', 'T')).toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'})
        : new Date().toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'});

      var row = document.createElement('div');
      row.innerHTML = buildRow(m.sender, escChat(m.message), t);
      chatBody.appendChild(row.firstElementChild);
      chatBody.scrollTop = chatBody.scrollHeight;

      /* sonido al recibir mensaje del agente */
      if (m.sender === 'agent') playNotif();
    }

    function escChat(s) {
      var d = document.createElement('div');
      d.textContent = s || '';
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
        var d = await res.json();
        if (d.message_id && d.message_id > lastId) lastId = d.message_id;
      } catch(e) { console.error('[QChat] send:', e); }
    });

    /* — polling de mensajes — */
    async function pollNow() {
      if (!session) return;
      try {
        var url = CHAT_API + '?action=poll&session_id=' + session.id + '&token=' + session.token + '&after=' + lastId;
        var res = await fetch(url);
        var data = await res.json();
        if (!data.success) {
          /* sesión inválida/expirada → limpiar y mostrar pantalla inicial */
          session = null;
          localStorage.removeItem('qchat_session');
          stopPoll();
          renderStart();
          return;
        }
        /* quitar "Cargando..." en cuanto llega cualquier respuesta válida */
        var loadingEl = chatBody.querySelector('.qchat__typing');
        if (loadingEl) loadingEl.remove();

        if (data.closed) {
          stopPoll();
          session = null;
          localStorage.removeItem('qchat_session');
          inputW.hidden = true;
          var closedMsg = document.createElement('div');
          closedMsg.innerHTML = buildRow('agent', 'Este chat ha sido cerrado. Si tienes más preguntas, escríbenos de nuevo.', new Date().toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'}));
          chatBody.appendChild(closedMsg.firstElementChild || closedMsg);
          /* botón para reiniciar sin necesidad de recargar la página */
          var restartBtn = document.createElement('button');
          restartBtn.type = 'button';
          restartBtn.className = 'qchat__restart-btn';
          restartBtn.textContent = 'Iniciar nueva conversación';
          restartBtn.addEventListener('click', function() { renderStart(); });
          chatBody.appendChild(restartBtn);
          chatBody.scrollTop = chatBody.scrollHeight;
          return;
        }
        (data.messages || []).forEach(function(m) {
          if (parseInt(m.id) > lastId) {
            lastId = parseInt(m.id);
            /* Carga inicial (historial): pintar todo.
               Polls siguientes: solo mensajes del agente — los del visitante
               ya están en pantalla porque se agregan al enviar. */
            if (historyLoaded && m.sender === 'visitor') return;
            addMessage(m);
            if (!isOpen && m.sender === 'agent') { unread++; updateBadge(); }
          }
        });
        historyLoaded = true;
      } catch(e) { /* red caída, reintentar en próximo ciclo */ }
    }

    function startPoll() { stopPoll(); pollTimer = setInterval(pollNow, POLL_MS); }
    function stopPoll()  { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

    /* — restaurar sesión previa: validar y mostrar badge si hay no leídos — */
    if (session) {
      fetch(CHAT_API + '?action=poll&session_id=' + session.id + '&token=' + session.token + '&after=0')
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (!d.success) { session = null; localStorage.removeItem('qchat_session'); return; }
          var newAgentMsgs = (d.messages || []).filter(function(m) { return m.sender === 'agent'; });
          if (newAgentMsgs.length > 0) { unread = newAgentMsgs.length; updateBadge(); }
        })
        .catch(function() {});
    }

    /* — auto-abrir una vez por visita a los 3.5s (sessionStorage = se resetea al cerrar el tab) — */
    if (!sessionStorage.getItem('qchat_dismissed')) {
      setTimeout(function() {
        if (isOpen) return;
        openFull();
      }, 3500);
    }
  })();

})();