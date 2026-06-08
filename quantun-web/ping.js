/* QUANTUN Digital — visitante activo (heartbeat) */
(function () {
  var sid = sessionStorage.getItem('_qsid');
  if (!sid) {
    sid = Math.random().toString(36).slice(2) + Date.now().toString(36);
    sessionStorage.setItem('_qsid', sid);
  }

  var base = location.hostname === 'localhost' ? '/CRM-QUANTUN-Digital' : '/crm';
  var URL  = base + '/api/blog_visitas.php?action=ping';

  // Detectar página actual
  var pagina = 'home';
  var path = location.pathname;
  if (path.indexOf('/blog/') !== -1) {
    pagina = path.split('/blog/')[1].replace('.html', '') || 'blog';
  } else if (path.indexOf('blog.html') !== -1) {
    pagina = 'blog';
  }

  function ping() {
    fetch(URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: sid, pagina: pagina }),
      keepalive: true
    }).catch(function () {});
  }

  ping();
  setInterval(ping, 30000);

  // Ping final al salir
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') ping();
  });
})();
