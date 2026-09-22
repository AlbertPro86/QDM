/* =========================================================
   QUANTUN Digital · Capacitación CMS — interacción
   ========================================================= */
(function () {
  'use strict';

  const $  = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const CSRF = () => (document.body.dataset.csrf || '');

  /* ---------- Iconos inline ---------- */
  const ICO = {
    check: '<svg class="ico" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>',
    alert: '<svg class="ico" viewBox="0 0 24 24"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>',
    trash: '<svg class="ico ico--lg" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>',
    ask:   '<svg class="ico ico--lg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.3a2.5 2.5 0 0 1 4.8.8c0 1.7-2.4 2.1-2.4 3.4"/><path d="M12 17h.01"/></svg>',
    eye:    '<svg class="ico ico--sm" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg class="ico ico--sm" viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 5.2A9.9 9.9 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C4 8.3 2 12 2 12s3.6 7 10 7c1.4 0 2.6-.3 3.7-.8"/><path d="M9.9 10a3 3 0 0 0 4.2 4.2"/></svg>'
  };

  /* ---------- Toasts ---------- */
  let toastBox;
  function toast(msg, tipo) {
    if (!toastBox) {
      toastBox = document.createElement('div');
      toastBox.className = 'toasts';
      document.body.appendChild(toastBox);
    }
    const t = document.createElement('div');
    t.className = 'toast' + (tipo === 'err' ? ' toast--err' : '');
    t.innerHTML = (tipo === 'err' ? ICO.alert : ICO.check) + '<span></span>';
    $('span', t).textContent = msg;
    toastBox.appendChild(t);
    setTimeout(() => {
      t.style.transition = 'opacity .25s, transform .25s';
      t.style.opacity = '0';
      t.style.transform = 'translateX(16px)';
      setTimeout(() => t.remove(), 260);
    }, 3200);
  }
  window.capToast = toast;

  /* ---------- Modal de confirmación (nunca confirm() nativo) ---------- */
  function confirmar(opts) {
    return new Promise((resolve) => {
      const o = Object.assign({
        titulo: '¿Confirmas la acción?',
        texto: 'Esta acción no se puede deshacer.',
        ok: 'Sí, continuar',
        cancelar: 'Cancelar',
        peligro: true
      }, opts || {});

      const m = document.createElement('div');
      m.className = 'modal is-open';
      m.innerHTML =
        '<div class="modal__box" role="dialog" aria-modal="true">' +
          '<div class="modal__body">' +
            '<div class="modal__ico' + (o.peligro ? '' : ' modal__ico--ask') + '">' + (o.peligro ? ICO.trash : ICO.ask) + '</div>' +
            '<div class="modal__t"></div>' +
            '<div class="modal__d"></div>' +
          '</div>' +
          '<div class="modal__foot">' +
            '<button type="button" class="btn btn--ghost btn--sm" data-no></button>' +
            '<button type="button" class="btn ' + (o.peligro ? 'btn--danger' : 'btn--primary') + ' btn--sm" data-yes></button>' +
          '</div>' +
        '</div>';
      $('.modal__t', m).textContent = o.titulo;
      $('.modal__d', m).textContent = o.texto;
      $('[data-no]', m).textContent = o.cancelar;
      $('[data-yes]', m).textContent = o.ok;
      document.body.appendChild(m);
      $('[data-yes]', m).focus();

      const cerrar = (v) => { document.removeEventListener('keydown', onKey); m.remove(); resolve(v); };
      const onKey = (e) => { if (e.key === 'Escape') cerrar(false); };
      document.addEventListener('keydown', onKey);
      $('[data-yes]', m).addEventListener('click', () => cerrar(true));
      $('[data-no]', m).addEventListener('click', () => cerrar(false));
      m.addEventListener('click', (e) => { if (e.target === m) cerrar(false); });
    });
  }
  window.confirmAction = confirmar;

  /* ---------- API ---------- */
  async function api(accion, datos) {
    const body = new FormData();
    body.append('accion', accion);
    body.append('csrf', CSRF());
    Object.keys(datos || {}).forEach((k) => body.append(k, datos[k]));
    const r = await fetch('api.php', { method: 'POST', body, credentials: 'same-origin' });
    const j = await r.json().catch(() => ({ ok: false, error: 'Respuesta inválida del servidor.' }));
    if (!r.ok || !j.ok) { throw new Error(j.error || 'No se pudo completar la acción.'); }
    return j;
  }
  window.capApi = api;

  /* ---------- Acordeones ---------- */
  function acordeon(sel, itemSel) {
    $$(sel).forEach((btn) => {
      btn.addEventListener('click', () => {
        const item = btn.closest(itemSel);
        const abierto = item.classList.contains('is-open');
        item.classList.toggle('is-open', !abierto);
        btn.setAttribute('aria-expanded', String(!abierto));
      });
    });
  }

  /* ---------- Tabs ---------- */
  function tabs() {
    $$('[data-tab]').forEach((t) => {
      t.addEventListener('click', () => {
        const destino = t.dataset.tab;
        $$('[data-tab]').forEach((x) => x.classList.toggle('is-active', x === t));
        $$('[data-panel]').forEach((p) => p.classList.toggle('is-active', p.dataset.panel === destino));
        try { history.replaceState(null, '', '#' + destino); } catch (e) {}
      });
    });
    const hash = location.hash.replace('#', '');
    if (hash && $('[data-tab="' + hash + '"]')) { $('[data-tab="' + hash + '"]').click(); }
  }

  /* ---------- Mostrar/ocultar contraseña ---------- */
  function togglePass() {
    document.addEventListener('click', (e) => {
      const b = e.target.closest('[data-toggle-pass]');
      if (!b) return;
      const inp = b.previousElementSibling;
      if (!inp) return;
      const oculto = inp.type === 'password';
      inp.type = oculto ? 'text' : 'password';
      b.innerHTML = oculto ? ICO.eyeOff : ICO.eye;
      b.setAttribute('aria-label', oculto ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  }

  /* ---------- Copiar al portapapeles ---------- */
  function copiar() {
    document.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-copiar]');
      if (!b) return;
      const txt = b.dataset.copiar;
      try {
        await navigator.clipboard.writeText(txt);
        toast('Copiado: ' + txt);
      } catch (err) {
        const ta = document.createElement('textarea');
        ta.value = txt; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); toast('Copiado: ' + txt); }
        catch (e2) { toast('No se pudo copiar', 'err'); }
        ta.remove();
      }
    });
  }

  /* ---------- Checklist del temario (estudiante) ---------- */
  function checklist() {
    $$('[data-tema]').forEach((inp) => {
      inp.addEventListener('change', async () => {
        const sesion = inp.dataset.sesion;
        const idx    = inp.dataset.tema;
        const valor  = inp.checked ? 1 : 0;
        inp.disabled = true;
        try {
          const r = await api('marcar_tema', { sesion, indice: idx, valor });
          pintarProgreso(r.metricas);
          if (r.metricas.por_sesion[sesion] && r.metricas.por_sesion[sesion].completa && valor) {
            toast('Clase ' + sesion + ' completa');
          }
        } catch (err) {
          inp.checked = !inp.checked;
          toast(err.message, 'err');
        } finally {
          inp.disabled = false;
        }
      });
    });
  }

  function pintarProgreso(m) {
    if (!m) return;
    const ring = $('[data-ring]');
    if (ring) { ring.style.setProperty('--pct', m.general); $('[data-ring-v]').textContent = m.general + '%'; }

    const bg = $('[data-bar-general]');
    if (bg) bg.style.width = m.general + '%';

    $('[data-m-temas]') && ($('[data-m-temas]').textContent = m.temas_ok + '/' + m.temas_total);
    $('[data-m-pct]')   && ($('[data-m-pct]').textContent = m.pct_temas + '%');

    Object.keys(m.por_sesion).forEach((n) => {
      const s = m.por_sesion[n];
      const cont = $('[data-ses="' + n + '"]');
      if (!cont) return;
      const bar = $('[data-ses-bar]', cont);
      const pct = $('[data-ses-pct]', cont);
      if (bar) bar.style.width = s.pct + '%';
      if (pct) pct.textContent = s.ok + '/' + s.total;
      cont.classList.toggle('ses--full', s.completa);
    });

    const cta = $('[data-cta-quiz]');
    if (cta) {
      cta.disabled = !m.puede_evaluar;
      const nota = $('[data-cta-quiz-nota]');
      if (nota) {
        nota.textContent = m.puede_evaluar
          ? 'Ya puedes presentar la evaluación final.'
          : 'Disponible al marcar el 100% del temario y registrar asistencia a las 5 clases.';
      }
    }
  }

  /* ---------- Acuerdo de uso ---------- */
  function acuerdo() {
    const form = $('#formAcuerdo');
    if (!form) return;
    const boxes = $$('input[data-clausula]', form);
    const btn   = $('[data-acuerdo-btn]', form);
    const cont  = $('[data-acuerdo-cont]', form);

    const revisar = () => {
      const todas = boxes.every((b) => b.checked);
      const nom   = cont ? cont.value.trim().length >= 5 : true;
      btn.disabled = !(todas && nom);
      const n = boxes.filter((b) => b.checked).length;
      const c = $('[data-acuerdo-n]');
      if (c) c.textContent = n + '/' + boxes.length;
    };
    boxes.forEach((b) => b.addEventListener('change', revisar));
    if (cont) cont.addEventListener('input', revisar);
    revisar();

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const ok = await confirmar({
        titulo: 'Firmar el acuerdo de uso',
        texto: 'Se registrará tu nombre, la fecha y tu dirección IP como constancia de aceptación. Esta firma no se puede deshacer desde tu panel.',
        ok: 'Firmar y aceptar',
        cancelar: 'Revisar de nuevo',
        peligro: false
      });
      if (!ok) return;
      btn.disabled = true;
      try {
        await api('firmar_acuerdo', { nombre: cont ? cont.value.trim() : '' });
        toast('Acuerdo firmado');
        setTimeout(() => location.reload(), 600);
      } catch (err) {
        toast(err.message, 'err');
        btn.disabled = false;
      }
    });
  }

  /* ---------- Evaluación final ---------- */
  function quiz() {
    const form = $('#formQuiz');
    if (!form) return;
    const total = parseInt(form.dataset.preguntas, 10);
    const btn   = $('[data-quiz-btn]', form);

    const revisar = () => {
      const resp = $$('input[type=radio]:checked', form).length;
      btn.disabled = resp < total;
      const c = $('[data-quiz-n]');
      if (c) c.textContent = resp + '/' + total;
    };
    form.addEventListener('change', revisar);
    revisar();

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const ok = await confirmar({
        titulo: 'Enviar la evaluación',
        texto: 'Una vez enviada se registra el intento y el resultado. No podrás modificar tus respuestas.',
        ok: 'Enviar respuestas',
        cancelar: 'Volver a revisar',
        peligro: false
      });
      if (!ok) return;
      btn.disabled = true;
      const datos = {};
      $$('input[type=radio]:checked', form).forEach((r) => { datos['r' + r.dataset.q] = r.value; });
      try {
        const r = await api('enviar_quiz', datos);
        sessionStorage.setItem('capQuizResultado', JSON.stringify(r.resultado));
        location.href = 'panel.php?v=evaluacion&r=1';
      } catch (err) {
        toast(err.message, 'err');
        btn.disabled = false;
      }
    });
  }

  /* ---------- Admin: mostrar/ocultar el formulario de alta ---------- */
  function altaToggle() {
    const caja = $('#alta');
    if (!caja) return;

    const abrir = () => {
      caja.classList.add('is-open');
      const primero = $('input[name="nombre"]', caja);
      if (primero) primero.focus();
      caja.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    $$('a[href="#alta"]').forEach((a) => {
      a.addEventListener('click', (e) => { e.preventDefault(); abrir(); });
    });
    const cerrar = $('[data-alta-cerrar]', caja);
    if (cerrar) cerrar.addEventListener('click', () => caja.classList.remove('is-open'));
  }

  /* ---------- Modal: mostrar credenciales una sola vez ---------- */
  function modalCredenciales(o) {
    const m = document.createElement('div');
    m.className = 'modal is-open';
    m.innerHTML =
      '<div class="modal__box" role="dialog" aria-modal="true">' +
        '<div class="modal__body" style="text-align:left">' +
          '<div class="modal__ico modal__ico--ask" style="margin-bottom:14px">' + ICO.check + '</div>' +
          '<div class="modal__t" style="text-align:left"></div>' +
          '<div class="modal__d" style="text-align:left;margin-bottom:18px"></div>' +
          '<div class="cred">' +
            '<div class="cred__row"><span class="cred__l">Correo</span><span class="cred__v" data-cred-email></span></div>' +
            '<div class="cred__row"><span class="cred__l">Contraseña</span><span class="cred__v cred__v--clave" data-cred-clave></span></div>' +
          '</div>' +
        '</div>' +
        '<div class="modal__foot">' +
          '<button type="button" class="btn btn--ghost btn--sm" data-copiar-cred>Copiar credenciales</button>' +
          '<button type="button" class="btn btn--primary btn--sm" data-cerrar>Listo</button>' +
        '</div>' +
      '</div>';
    $('.modal__t', m).textContent = 'Credenciales de ' + o.nombre;
    $('.modal__d', m).textContent = 'Envíaselas por canal privado. La contraseña no se puede volver a ver: si se pierde, define una nueva desde la tabla.';
    $('[data-cred-email]', m).textContent = o.email;
    $('[data-cred-clave]', m).textContent = o.clave;
    document.body.appendChild(m);
    $('[data-cerrar]', m).focus();

    $('[data-copiar-cred]', m).addEventListener('click', async () => {
      const txt = 'Acceso a la capacitación\nCorreo: ' + o.email + '\nContraseña: ' + o.clave;
      try { await navigator.clipboard.writeText(txt); toast('Credenciales copiadas'); }
      catch (e) { toast('No se pudo copiar', 'err'); }
    });
    const cerrar = () => { m.remove(); location.reload(); };
    $('[data-cerrar]', m).addEventListener('click', cerrar);
    m.addEventListener('click', (e) => { if (e.target === m) cerrar(); });
  }

  /* ---------- Modal: pedir una contraseña nueva ---------- */
  function modalNuevaClave(nombre) {
    return new Promise((resolve) => {
      const m = document.createElement('div');
      m.className = 'modal is-open';
      m.innerHTML =
        '<div class="modal__box" role="dialog" aria-modal="true">' +
          '<div class="modal__body" style="text-align:left">' +
            '<div class="modal__t" style="text-align:left"></div>' +
            '<div class="modal__d" style="text-align:left;margin-bottom:16px"></div>' +
            '<label class="field" style="margin-bottom:0">' +
              '<span class="field__label">Contraseña nueva</span>' +
              '<span class="alta__clave">' +
                '<input class="input" type="text" data-nueva autocomplete="off">' +
                '<button class="btn btn--ghost btn--sm" type="button" data-gen>Generar</button>' +
              '</span>' +
            '</label>' +
          '</div>' +
          '<div class="modal__foot">' +
            '<button type="button" class="btn btn--ghost btn--sm" data-no>Cancelar</button>' +
            '<button type="button" class="btn btn--primary btn--sm" data-yes>Cambiar contraseña</button>' +
          '</div>' +
        '</div>';
      $('.modal__t', m).textContent = 'Nueva contraseña para ' + nombre;
      $('.modal__d', m).textContent = 'La anterior deja de funcionar de inmediato. Tendrás que enviarle la nueva.';
      document.body.appendChild(m);

      const inp = $('[data-nueva]', m);
      inp.focus();

      $('[data-gen]', m).addEventListener('click', async () => {
        try { const r = await api('clave_sugerida', {}); inp.value = r.clave; inp.focus(); }
        catch (e) { toast(e.message, 'err'); }
      });

      const cerrar = (v) => { m.remove(); resolve(v); };
      $('[data-no]', m).addEventListener('click', () => cerrar(null));
      $('[data-yes]', m).addEventListener('click', () => cerrar(inp.value));
      inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') cerrar(inp.value); });
      m.addEventListener('click', (e) => { if (e.target === m) cerrar(null); });
    });
  }

  /* ---------- Admin: alta de estudiante ---------- */
  function altaEstudiante() {
    const form = $('#formAlta');
    if (!form) return;

    const btnGen = $('[data-generar-clave]', form);
    if (btnGen) {
      btnGen.addEventListener('click', async () => {
        try { const r = await api('clave_sugerida', {}); $('[data-clave]', form).value = r.clave; }
        catch (e) { toast(e.message, 'err'); }
      });
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('[type=submit]', form);
      btn.disabled = true;
      const fd = new FormData(form);
      const clave = (fd.get('clave') || '').toString();
      try {
        const r = await api('crear_estudiante', {
          nombre:    (fd.get('nombre')    || '').toString().trim(),
          apellidos: (fd.get('apellidos') || '').toString().trim(),
          email:     (fd.get('email')     || '').toString().trim(),
          cargo:     (fd.get('cargo')     || '').toString().trim(),
          clave:     clave
        });
        modalCredenciales({ nombre: r.estudiante.nombre, email: r.estudiante.email, clave: clave });
      } catch (err) {
        toast(err.message, 'err');
        btn.disabled = false;
      }
    });
  }

  /* ---------- Admin: cambiar la contraseña de un estudiante ---------- */
  function cambiarClave() {
    document.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-cambiar-clave]');
      if (!b) return;
      const clave = await modalNuevaClave(b.dataset.nombre);
      if (clave === null) return;
      try {
        await api('cambiar_clave', { id: b.dataset.id, clave: clave });
        modalCredenciales({ nombre: b.dataset.nombre, email: b.dataset.email, clave: clave });
      } catch (err) {
        toast(err.message, 'err');
      }
    });
  }

  /* ---------- Admin: acciones de fila ---------- */
  function accionesAdmin() {
    document.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-accion]');
      if (!b) return;
      const accion = b.dataset.accion;
      const id     = b.dataset.id || '';

      if (accion === 'eliminar_estudiante') {
        const ok = await confirmar({
          titulo: 'Eliminar estudiante',
          texto: 'Se borran su progreso, su acuerdo firmado y sus intentos de evaluación. Esta acción no se puede deshacer.',
          ok: 'Eliminar',
          cancelar: 'Cancelar'
        });
        if (!ok) return;
      }
      if (accion === 'reiniciar_sesion') {
        const ok = await confirmar({
          titulo: 'Reiniciar esta clase',
          texto: 'Se borran los temas que marcaste como dados, las notas y el cierre de la clase. No afecta la asistencia ni el avance que reportaron los estudiantes.',
          ok: 'Reiniciar clase',
          cancelar: 'Cancelar'
        });
        if (!ok) return;
      }
      if (accion === 'reiniciar_quiz') {
        const ok = await confirmar({
          titulo: 'Reiniciar evaluación',
          texto: 'Se borran los intentos registrados y el estudiante podrá volver a presentar la evaluación.',
          ok: 'Reiniciar',
          cancelar: 'Cancelar'
        });
        if (!ok) return;
      }

      b.disabled = true;
      try {
        const extra = {};
        if (b.dataset.sesion) extra.sesion = b.dataset.sesion;
        if (b.dataset.valor)  extra.valor  = b.dataset.valor;
        const r = await api(accion, Object.assign({ id: id }, extra));
        toast(r.mensaje || 'Listo');
        setTimeout(() => location.reload(), 550);
      } catch (err) {
        toast(err.message, 'err');
        b.disabled = false;
      }
    });
  }

  /* ---------- Admin: asistencia ---------- */
  function asistencia() {
    $$('[data-asistencia]').forEach((inp) => {
      inp.addEventListener('change', async () => {
        inp.disabled = true;
        try {
          await api('marcar_asistencia', {
            id: inp.dataset.id,
            sesion: inp.dataset.sesion,
            valor: inp.checked ? 1 : 0
          });
          toast('Asistencia actualizada');
        } catch (err) {
          inp.checked = !inp.checked;
          toast(err.message, 'err');
        } finally {
          inp.disabled = false;
        }
      });
    });
  }

  /* ---------- Admin: entrega de accesos ---------- */
  function accesos() {
    const forms = $$('[data-form-accesos]');
    forms.forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('[type=submit]', form);
        btn.disabled = true;
        const fd = new FormData(form);
        try {
          await api('registrar_accesos', {
            id:   form.dataset.formAccesos,
            rol:  (fd.get('rol')  || '').toString(),
            nota: (fd.get('nota') || '').toString()
          });
          toast('Accesos registrados');
          setTimeout(() => location.reload(), 600);
        } catch (err) {
          toast(err.message, 'err');
          btn.disabled = false;
        }
      });
    });
  }

  /* ---------- Admin: editor de cada clase (temas + notas) ----------
     Guardado manual con boton, autoguardado de respaldo a los 4 s,
     estado visible y aviso si se intenta salir con cambios pendientes. */
  function editorClases() {
    const bloques = $$('[data-clase]');
    if (!bloques.length) return;

    const pendientes = new Set();

    bloques.forEach((clase) => {
      const n       = clase.dataset.clase;
      const form    = $('[data-form-sesion]', clase);
      const estado  = $('[data-estado]', clase);
      const txt     = $('[data-estado-txt]', clase);
      const badge   = $('[data-sucio-badge]', clase);
      const btn     = $('[data-guardar]', clase);
      if (!form || !estado) return;

      let sucio = false;
      let timer = null;

      const pintar = (modo, mensaje) => {
        estado.dataset.modo = modo;
        txt.textContent = mensaje;
        badge.hidden = (modo !== 'sucio');
        btn.disabled = (modo === 'guardando') || (modo === 'limpio');
      };

      const marcarSucio = () => {
        sucio = true;
        pendientes.add(n);
        pintar('sucio', 'Cambios sin guardar');
        clearTimeout(timer);
        timer = setTimeout(() => { guardar(true); }, 4000);
      };

      async function guardar(auto) {
        if (!sucio) return;
        clearTimeout(timer);
        pintar('guardando', auto ? 'Guardando automáticamente...' : 'Guardando...');

        const marcados = $$('input[data-tema-admin]', clase)
          .filter((c) => c.checked)
          .map((c) => c.dataset.temaIdx)
          .join(',');

        try {
          const r = await api('guardar_sesion', {
            sesion: n,
            nota: (new FormData(form).get('nota') || '').toString(),
            temas: marcados
          });
          sucio = false;
          pendientes.delete(n);
          pintar('limpio', 'Todo guardado · ' + (r.hora || ''));
          if (!auto) toast(r.mensaje || 'Cambios guardados');
        } catch (err) {
          pintar('sucio', 'No se pudo guardar');
          toast(err.message, 'err');
        }
      }

      $$('input[data-tema-admin]', clase).forEach((c) => c.addEventListener('change', marcarSucio));
      $('input[name="nota"]', form).addEventListener('input', marcarSucio);
      form.addEventListener('submit', (e) => { e.preventDefault(); guardar(false); });

      // Guardar lo pendiente antes de marcar la clase como dictada (recarga la pagina)
      const btnDictada = $('[data-accion="marcar_sesion"]', clase);
      if (btnDictada) {
        btnDictada.addEventListener('click', () => { if (sucio) guardar(true); }, true);
      }

      // Al reiniciar se descarta lo pendiente: cancelamos el autoguardado
      // para que no vuelva a escribir lo que se acaba de borrar.
      const btnReiniciar = $('[data-accion="reiniciar_sesion"]', clase);
      if (btnReiniciar) {
        btnReiniciar.addEventListener('click', () => {
          clearTimeout(timer);
          sucio = false;
          pendientes.delete(n);
        }, true);
      }

      pintar('limpio', 'Todo guardado');
    });

    window.addEventListener('beforeunload', (e) => {
      if (pendientes.size) { e.preventDefault(); e.returnValue = ''; }
    });
  }

  /* ---------- Filtro de tabla ---------- */
  function filtro() {
    const inp = $('#filtroEstudiantes');
    if (!inp) return;
    inp.addEventListener('input', () => {
      const q = inp.value.trim().toLowerCase();
      let n = 0;
      $$('[data-fila]').forEach((tr) => {
        const match = !q || tr.dataset.fila.toLowerCase().includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) n++;
      });
      const vacio = $('[data-sin-resultados]');
      if (vacio) vacio.style.display = n === 0 ? '' : 'none';
    });
  }

  /* ---------- Init ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    acordeon('.clase__head', '.clase');
    acordeon('.ses__head', '.ses');
    tabs(); copiar(); togglePass(); checklist(); acuerdo(); quiz();
    altaToggle(); altaEstudiante(); cambiarClave(); accionesAdmin(); asistencia(); accesos();
    editorClases(); filtro();
  });
})();
