/* ===== QUANTUN Digital — interactions & animations ===== */
(function () {
  'use strict';

  /* ── Aurora Hero Background ──────────────────────────────────────────── */
  (function initAurora() {
    var canvas = document.getElementById('heroBg');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');

    // Orbs: posición base (0–1), radio relativo, color RGB, frecuencia, fase, amplitud
    var orbs = [
      { x:.14, y:.42, r:.62, c:[198,242,78],  f:.19, p:0,   a:.13 },  // lima principal
      { x:.78, y:.28, r:.56, c:[255,199,168],  f:.15, p:1.8, a:.11 },  // peach
      { x:.50, y:.78, r:.50, c:[167,239,208],  f:.21, p:3.2, a:.10 },  // mint
      { x:.88, y:.72, r:.46, c:[205,180,246],  f:.17, p:2.5, a:.12 },  // lila
      { x:.26, y:.12, r:.40, c:[198,242,78],   f:.13, p:4.1, a:.09 },  // lima suave
      { x:.60, y:.50, r:.44, c:[255,230,140],  f:.22, p:5.6, a:.08 },  // dorado suave
    ];

    function resize() {
      var hero = canvas.parentElement;
      canvas.width  = hero.offsetWidth;
      canvas.height = hero.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    var raf;
    function draw(ts) {
      var t  = ts * 0.001;           // tiempo en segundos
      var w  = canvas.width;
      var h  = canvas.height;
      var dpr = window.devicePixelRatio || 1;
      ctx.clearRect(0, 0, w, h);

      for (var i = 0; i < orbs.length; i++) {
        var o  = orbs[i];
        var cx = (o.x + Math.sin(t * o.f + o.p) * o.a) * w;
        var cy = (o.y + Math.cos(t * o.f * 1.3 + o.p * 1.2) * o.a * .65) * h;
        var r  = (o.r + Math.sin(t * o.f * .7 + o.p) * .04) * Math.min(w, h) * .72;

        var g = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
        var rgb = o.c[0]+','+o.c[1]+','+o.c[2];
        g.addColorStop(0,   'rgba('+rgb+',.20)');
        g.addColorStop(.45, 'rgba('+rgb+',.07)');
        g.addColorStop(1,   'rgba('+rgb+',0)');

        ctx.fillStyle = g;
        ctx.beginPath();
        ctx.arc(cx, cy, r, 0, Math.PI * 2);
        ctx.fill();
      }
      raf = requestAnimationFrame(draw);
    }
    raf = requestAnimationFrame(draw);

    // Pausa cuando la pestaña queda inactiva (ahorra CPU)
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) cancelAnimationFrame(raf);
      else raf = requestAnimationFrame(draw);
    });
  })();

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
