/* ===== QUANTUN Digital — interactions & animations ===== */
(function () {
  'use strict';

  /* ── Particle system — reacciona al mouse ─────────────────────────────── */
  function initParticles() {
    var canvas = document.getElementById('heroBg');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var sec  = canvas.parentElement;
    var W = 0, H = 0;
    var mouse = { x: -9999, y: -9999 };
    var COUNT = 48;
    var CONNECT_DIST = 140;
    var REPEL_DIST   = 110;
    var pts = [];

    function resize() {
      W = canvas.width  = sec.offsetWidth  > 0 ? sec.offsetWidth  : window.innerWidth;
      H = canvas.height = sec.offsetHeight > 0 ? sec.offsetHeight : window.innerHeight;
    }

    function mkPt() {
      var angle = Math.random() * 6.2832;
      var speed = 0.3 + Math.random() * 0.4;
      return {
        x:  Math.random() * W,
        y:  Math.random() * H,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        r:  1.4 + Math.random() * 1.2,
        wanderAngle: Math.random() * 6.2832
      };
    }

    function init() {
      pts = [];
      for (var i = 0; i < COUNT; i++) pts.push(mkPt());
    }

    var raf;
    function draw() {
      ctx.clearRect(0, 0, W, H);

      // Conexiones entre partículas cercanas
      for (var i = 0; i < pts.length; i++) {
        for (var j = i + 1; j < pts.length; j++) {
          var dx = pts[i].x - pts[j].x;
          var dy = pts[i].y - pts[j].y;
          var d  = Math.sqrt(dx*dx + dy*dy);
          if (d < CONNECT_DIST) {
            ctx.beginPath();
            ctx.moveTo(pts[i].x, pts[i].y);
            ctx.lineTo(pts[j].x, pts[j].y);
            ctx.strokeStyle = 'rgba(110,110,110,' + ((1 - d / CONNECT_DIST) * 0.28).toFixed(3) + ')';
            ctx.lineWidth = 0.7;
            ctx.stroke();
          }
        }
      }

      // Partículas
      for (var i = 0; i < pts.length; i++) {
        var p  = pts[i];

        // Repulsión del mouse
        var mdx = p.x - mouse.x;
        var mdy = p.y - mouse.y;
        var md  = Math.sqrt(mdx*mdx + mdy*mdy);
        if (md < REPEL_DIST && md > 0) {
          var f = (REPEL_DIST - md) / REPEL_DIST * 1.2;
          p.vx += (mdx / md) * f;
          p.vy += (mdy / md) * f;
        }

        // Fuerza de vagabundeo autónoma (cambia dirección suavemente)
        p.wanderAngle += (Math.random() - 0.5) * 0.15;
        p.vx += Math.cos(p.wanderAngle) * 0.03;
        p.vy += Math.sin(p.wanderAngle) * 0.03;

        // Fricción suave + límite de velocidad
        p.vx *= 0.985;
        p.vy *= 0.985;
        var spd = Math.sqrt(p.vx*p.vx + p.vy*p.vy);
        if (spd > 1.6) { p.vx = p.vx/spd*1.6; p.vy = p.vy/spd*1.6; }

        p.x += p.vx;
        p.y += p.vy;

        // Rebote en bordes
        if (p.x < 0)  { p.x = 0;  p.vx *= -1; }
        if (p.x > W)  { p.x = W;  p.vx *= -1; }
        if (p.y < 0)  { p.y = 0;  p.vy *= -1; }
        if (p.y > H)  { p.y = H;  p.vy *= -1; }

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, 6.2832);
        ctx.fillStyle = 'rgba(100,100,100,0.55)';
        ctx.fill();
      }

      raf = requestAnimationFrame(draw);
    }

    // Tracking del mouse relativo al canvas
    sec.addEventListener('mousemove', function(e) {
      var rect = canvas.getBoundingClientRect();
      mouse.x = e.clientX - rect.left;
      mouse.y = e.clientY - rect.top;
    });
    sec.addEventListener('mouseleave', function() {
      mouse.x = -9999; mouse.y = -9999;
    });

    window.addEventListener('resize', function() { resize(); init(); });
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) cancelAnimationFrame(raf);
      else raf = requestAnimationFrame(draw);
    });

    resize();
    init();
    raf = requestAnimationFrame(draw);
  }
  window.addEventListener('load', initParticles);

  /* Año footer */
  var y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  /* Nav: fondo al hacer scroll */
  var nav = document.getElementById('nav');
  function onScroll() {
    if (window.scrollY > 24) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  }

  /* Menú móvil */
  var burger = document.getElementById('burger');
  var links = document.getElementById('navLinks');
  if (burger && links) {
    burger.addEventListener('click', function () { links.classList.toggle('open'); });
    links.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { links.classList.remove('open'); });
    });
  }

  /* Contador animado */
  function animateCount(el) {
    var to = parseInt(el.getAttribute('data-to'), 10) || 0;
    var dur = 1100, start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* Reveal basado en posición de scroll (fiable en cualquier contexto) */
  var items = Array.prototype.slice.call(document.querySelectorAll('.reveal, .skill'));

  function reveal(el) {
    if (el.classList.contains('in')) return;
    el.classList.add('in');
    var c = el.querySelector ? el.querySelector('.count') : null;
    if (c && !c.dataset.done) { c.dataset.done = '1'; animateCount(c); }
  }

  /* Muestra TODO sin depender de transiciones (contextos sin pintado/oscultos
     o navegadores que pausan animaciones — garantiza que nada quede invisible) */
  function revealAllInstant() {
    document.documentElement.classList.add('reveal-static');
    items.slice().forEach(reveal);
    items.length = 0;
    document.querySelectorAll('.count').forEach(function (c) {
      if (!c.dataset.done) { c.dataset.done = '1'; c.textContent = c.getAttribute('data-to'); }
    });
  }

  function check() {
    var vh = window.innerHeight || document.documentElement.clientHeight || 800;
    var trigger = vh * 0.9;
    for (var i = items.length - 1; i >= 0; i--) {
      var r = items[i].getBoundingClientRect();
      if (r.top < trigger && r.bottom > -40) {
        reveal(items[i]);
        items.splice(i, 1);
      }
    }
  }

  /* Red de seguridad: nada debe quedar oculto en ningún contexto */
  function revealAll() {
    while (items.length) reveal(items.pop());
  }
  var ticking = false;
  function onFrame() {
    onScroll();
    check();
    ticking = false;
  }
  function request() {
    if (!ticking) { ticking = true; requestAnimationFrame(onFrame); }
  }

  window.addEventListener('scroll', request, { passive: true });
  window.addEventListener('resize', request, { passive: true });

  /* Si el documento no está visible (captura/preview offscreen, pestaña en
     segundo plano), las transiciones se pausan: mostramos todo al instante. */
  if (document.hidden) revealAllInstant();
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) revealAllInstant(); else onFrame();
  });

  /* Detector de contexto sin pintado: requestAnimationFrame se pausa cuando la
     página no se está renderizando, pero setTimeout sigue corriendo. Si el rAF
     no disparó, asumimos contexto throttled y mostramos todo sin animación.
     En páginas visibles normales esto nunca ocurre y se conserva la animación. */
  var rafFired = false;
  requestAnimationFrame(function () { rafFired = true; });
  setTimeout(function () { if (!rafFired) revealAllInstant(); }, 700);

  /* Disparo inicial + safety nets de scroll */
  onFrame();
  setTimeout(onFrame, 150);
  setTimeout(onFrame, 400);
  window.addEventListener('load', onFrame);
  /* Restauración desde bfcache / prerender */
  window.addEventListener('pageshow', onFrame);

  /* GARANTÍA ABSOLUTA: pase lo que pase, nada queda invisible. A los 1.8s
     (tiempo de sobra para que la animación del hero se reproduzca primero en
     páginas normales) forzamos el estado final sin depender de transiciones. */
  setTimeout(revealAllInstant, 1800);
})();
