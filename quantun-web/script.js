/* ===== QUANTUN Digital — interactions & animations ===== */
(function () {
  'use strict';

  /* ── Dot-wave canvas (enhancement sobre CSS dot-grid) ─────────────────── */
  function initDotWave() {
    var canvas = document.getElementById('heroBg');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var sec  = canvas.parentElement;
    var GAP  = 42;
    var W = 0, H = 0;

    function resize() {
      W = canvas.width  = (sec.offsetWidth  > 0 ? sec.offsetWidth  : window.innerWidth);
      H = canvas.height = (sec.offsetHeight > 0 ? sec.offsetHeight : window.innerHeight);
    }

    var raf;
    function draw(ts) {
      var t = ts * 0.001;
      if (W < 10) { resize(); }
      ctx.clearRect(0, 0, W, H);
      var cols = Math.ceil(W / GAP) + 1;
      var rows = Math.ceil(H / GAP) + 1;
      for (var r = 0; r <= rows; r++) {
        for (var c = 0; c <= cols; c++) {
          var x = c * GAP, y = r * GAP;
          // onda que viaja diagonal
          var v = 0.5 + 0.5 * Math.sin(x * 0.018 + t * 0.65) * Math.cos(y * 0.018 - t * 0.45);
          var alpha  = (0.08 + v * 0.28).toFixed(3);
          var radius = 0.8 + v * 1.0;
          ctx.beginPath();
          ctx.arc(x, y, radius, 0, 6.2832);
          ctx.fillStyle = 'rgba(100,100,100,' + alpha + ')';
          ctx.fill();
        }
      }
      raf = requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) cancelAnimationFrame(raf);
      else raf = requestAnimationFrame(draw);
    });

    resize();
    canvas.style.opacity = '1'; // activar canvas (oculta el ::before CSS)
    raf = requestAnimationFrame(draw);
  }
  window.addEventListener('load', initDotWave);

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
