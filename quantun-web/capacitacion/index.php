<?php
/**
 * Landing pública del programa + accesos (estudiante / administrador).
 */
require_once __DIR__ . '/lib/ui.php';

cap_sesion_iniciar();

if (isset($_GET['salir'])) {
    cap_logout();
    header('Location: index.php');
    exit;
}

$vista  = $_GET['v'] ?? 'landing';
$error  = '';
$aviso  = '';
$sesiones = cap_sesiones();

/* ---------- POST: accesos ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!cap_csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró. Vuelve a intentarlo.';
    } elseif ($accion === 'setup') {
        $vista = 'admin';
        $u  = trim((string)($_POST['usuario'] ?? ''));
        $c1 = (string)($_POST['clave'] ?? '');
        $c2 = (string)($_POST['clave2'] ?? '');
        if (cap_hay_admin()) {
            $error = 'La cuenta de administrador ya fue creada.';
        } elseif (mb_strlen($u, 'UTF-8') < 3) {
            $error = 'El usuario debe tener al menos 3 caracteres.';
        } elseif (strlen($c1) < 10) {
            $error = 'La contraseña debe tener al menos 10 caracteres.';
        } elseif ($c1 !== $c2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            cap_transaccion(function (array &$d) use ($u, $c1) {
                $d['admin'] = ['usuario' => $u, 'hash' => password_hash($c1, PASSWORD_DEFAULT), 'creado' => date('c')];
                cap_bitacora($d, $u, 'setup', 'Cuenta de administrador creada');
            });
            cap_login_admin($u, $c1);
            header('Location: admin.php');
            exit;
        }
    } elseif ($accion === 'login') {
        $vista = 'acceso';
        $rol = cap_login((string)($_POST['email'] ?? ''), (string)($_POST['clave'] ?? ''));
        if ($rol === 'admin')      { header('Location: admin.php'); exit; }
        if ($rol === 'estudiante') { header('Location: panel.php'); exit; }
        $error = cap_intento_permitido()
            ? 'Correo o contraseña incorrectos.'
            : 'Demasiados intentos. Espera 10 minutos antes de reintentar.';
    }
}

/* Redirecciones si ya hay sesión */
if ($vista === 'acceso' || $vista === 'admin') {
    if (cap_es_admin())            { header('Location: admin.php'); exit; }
    if (cap_estudiante_actual())   { header('Location: panel.php'); exit; }
}

$hayAdmin = cap_hay_admin();

/* =========================================================
   VISTAS DE ACCESO
   ========================================================= */
if ($vista === 'acceso' || $vista === 'admin'):
    $esAdmin = ($vista === 'admin');
    $setup   = $esAdmin && !$hayAdmin;
    cap_head($setup ? 'Configuración inicial' : 'Acceso');
?>
<main class="auth">
  <aside class="auth__aside">
    <div>
      <div class="auth__brand"><img src="../assets/quantun-logo.png" alt="QUANTUN Digital"></div>
      <h2>Programa de habilitación para la gestión del CMS</h2>
      <p>5 clases en 2 jornadas, un temario verificable y una evaluación final. Los accesos al sitio se entregan cuando el proceso está completo.</p>
      <ul class="auth__list">
        <li><?= cap_icono('check', 'ico ico--sm') ?><span>2 viernes · <?= h(CAP_HORA_INICIO) ?> a <?= h(CAP_HORA_FIN) ?></span></li>
        <li><?= cap_icono('check', 'ico ico--sm') ?><span>Checklist por tema con trazabilidad</span></li>
        <li><?= cap_icono('check', 'ico ico--sm') ?><span>Acuerdo de uso firmado en línea</span></li>
        <li><?= cap_icono('check', 'ico ico--sm') ?><span>Evaluación de 5 preguntas para validar</span></li>
      </ul>
    </div>
    <p class="auth__foot">QUANTUN Digital · <?= h(CAP_CLIENTE) ?> · v<?= CAP_VERSION ?></p>
  </aside>

  <section class="auth__main">
    <div class="auth__box">
      <span class="eyebrow"><span class="eyebrow__dot"></span><?= $setup ? 'Configuración inicial' : 'Acceso a la plataforma' ?></span>

      <?php if ($setup): ?>
        <h1>Crea tu acceso de administrador</h1>
        <p>Este es el primer ingreso. Define un usuario y una contraseña para el panel del instructor; la contraseña queda guardada cifrada y no se puede recuperar.</p>
        <?php if ($error): ?><div class="alert alert--err"><?= cap_icono('alert', 'ico ico--sm') ?><span><?= h($error) ?></span></div><?php endif; ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="accion" value="setup">
          <input type="hidden" name="csrf" value="<?= h(cap_csrf()) ?>">
          <label class="field">
            <span class="field__label">Usuario</span>
            <input class="input" type="text" name="usuario" minlength="3" required autofocus placeholder="ej. instructor">
          </label>
          <label class="field">
            <span class="field__label">Contraseña</span>
            <span class="field__pass">
              <input class="input" type="password" name="clave" minlength="10" required>
              <button type="button" class="field__eye" data-toggle-pass tabindex="-1" aria-label="Mostrar contraseña"><?= cap_icono('eye', 'ico ico--sm') ?></button>
            </span>
            <span class="field__hint">Mínimo 10 caracteres. Usa un gestor de claves.</span>
          </label>
          <label class="field">
            <span class="field__label">Repetir contraseña</span>
            <span class="field__pass">
              <input class="input" type="password" name="clave2" minlength="10" required>
              <button type="button" class="field__eye" data-toggle-pass tabindex="-1" aria-label="Mostrar contraseña"><?= cap_icono('eye', 'ico ico--sm') ?></button>
            </span>
          </label>
          <button class="btn btn--primary btn--block" type="submit">Crear cuenta <?= cap_icono('arrow', 'ico ico--sm') ?></button>
        </form>

      <?php else: ?>
        <h1>Ingresa a la plataforma</h1>
        <p>Un solo acceso para el instructor y para los estudiantes: usa tu correo y tu contraseña.</p>
        <?php if ($error): ?><div class="alert alert--err"><?= cap_icono('alert', 'ico ico--sm') ?><span><?= h($error) ?></span></div><?php endif; ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="accion" value="login">
          <input type="hidden" name="csrf" value="<?= h(cap_csrf()) ?>">
          <label class="field">
            <span class="field__label">Correo electrónico</span>
            <input class="input" type="text" name="email" required autofocus placeholder="nombre@empresa.com" autocomplete="username">
          </label>
          <label class="field">
            <span class="field__label">Contraseña</span>
            <span class="field__pass">
              <input class="input" type="password" name="clave" required autocomplete="current-password">
              <button type="button" class="field__eye" data-toggle-pass tabindex="-1" aria-label="Mostrar contraseña"><?= cap_icono('eye', 'ico ico--sm') ?></button>
            </span>
            <span class="field__hint">Si eres estudiante, es la contraseña que te entregó el instructor. Es personal: no la compartas.</span>
          </label>
          <button class="btn btn--primary btn--block" type="submit">Entrar <?= cap_icono('arrow', 'ico ico--sm') ?></button>
        </form>
        <p class="auth__alt">¿No tienes acceso? Escríbele al instructor. · <a href="index.php">Ver el programa</a></p>
      <?php endif; ?>
    </div>
  </section>
</main>
<script src="assets/cap.js?v=<?= cap_asset_ver('assets/cap.js') ?>"></script>
</body>
</html>
<?php
exit;
endif;

/* =========================================================
   LANDING PÚBLICA
   ========================================================= */
$totalTemas = cap_total_temas();
$jornadas   = cap_jornadas();
$data       = cap_leer();
$yo         = cap_estudiante_actual();

cap_head('Programa de capacitación CMS', 'Capacitación de 4 horas en 2 jornadas para la gestión, seguridad y mantenimiento del CMS de ' . CAP_CLIENTE . '.');
cap_nav('Capacitación CMS', ['Programa' => '#programa', 'Temario' => '#temario', 'Reglas' => '#reglas', 'Evaluación' => '#evaluacion'], $yo ? ['nombre' => cap_nombre_completo($yo)] : null, 'index.php?salir=1');
?>

<!-- HERO -->
<section class="hero">
  <div class="container hero__inner">
    <div>
      <span class="eyebrow"><span class="eyebrow__dot"></span>Cliente · <?= h(CAP_CLIENTE) ?></span>
      <h1 class="hero__title">Capacitación para <span class="hl">administrar el CMS</span> de Taxi Taxi.</h1>
      <p class="hero__lead">
        5 clases prácticas en 2 jornadas de 2 horas, para que el equipo administre WordPress con criterio:
        contenido, accesos, copias de seguridad y mantenimiento. <strong>Los accesos se entregan al terminar el proceso</strong>,
        con acuerdo de uso firmado y evaluación aprobada.
      </p>
      <div class="hero__actions">
        <?php if ($yo): ?>
          <a class="btn btn--primary" href="panel.php">Ir a mi panel <?= cap_icono('arrow', 'ico ico--sm') ?></a>
        <?php else: ?>
          <a class="btn btn--primary" href="index.php?v=acceso">Ingresar a mi capacitación <?= cap_icono('arrow', 'ico ico--sm') ?></a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="#temario">Ver el temario</a>
      </div>
      <ul class="hero__chips">
        <li><?= cap_icono('calendar', 'ico ico--sm') ?>2 viernes · <?= h(cap_fecha_corta($jornadas[0]['fecha'])) ?> y <?= h(cap_fecha_corta($jornadas[1]['fecha'])) ?></li>
        <li><?= cap_icono('clock', 'ico ico--sm') ?><?= h(CAP_HORA_INICIO) ?> a <?= h(CAP_HORA_FIN) ?> (4 h en total)</li>
        <li><?= cap_icono('clipboard', 'ico ico--sm') ?><?= $totalTemas ?> temas verificables</li>
        <li><?= cap_icono('award', 'ico ico--sm') ?>Evaluación final</li>
      </ul>
    </div>

    <aside class="plan-card">
      <div class="plan-card__top">
        <div>
          <div class="plan-card__label">Primera jornada</div>
          <div class="plan-card__value"><?= h(cap_fecha_corta($jornadas[0]['fecha'])) ?></div>
          <div class="plan-card__unit"><?= h(CAP_HORA_INICIO) ?> — <?= h(CAP_HORA_FIN) ?></div>
        </div>
        <span class="badge badge--accent">2 jornadas</span>
      </div>
      <?php foreach ($jornadas as $i => $j): ?>
      <div class="plan-day">
        <div class="plan-day__head">
          <span>Jornada <?= $i + 1 ?> · <?= h(cap_fecha_corta($j['fecha'])) ?></span>
          <span class="mono"><?= h(CAP_HORA_INICIO) ?>–<?= h(CAP_HORA_FIN) ?></span>
        </div>
        <div class="plan-card__rows">
          <?php foreach ($j['clases'] as $n): $s = $sesiones[$n]; ?>
          <div class="plan-row">
            <span class="plan-row__n"><?= $n ?></span>
            <span class="plan-row__t"><?= h($s['titulo']) ?></span>
            <span class="plan-row__d mono"><?= h($s['hora_inicio']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </aside>
  </div>
</section>

<!-- PROGRAMA -->
<section class="section section--soft" id="programa">
  <div class="container">
    <div class="section-head section-head--center">
      <span class="eyebrow"><span class="eyebrow__dot"></span>Cómo funciona</span>
      <h2>Del temario al acceso, con trazabilidad</h2>
      <p>Cada paso queda registrado: qué tema se cubrió, quién asistió, quién firmó el acuerdo y quién aprobó la evaluación.</p>
    </div>
    <div class="stats">
      <div class="stat"><div class="stat__l">Jornadas</div><div class="stat__v">2</div><div class="stat__u">viernes, 2 horas cada una</div></div>
      <div class="stat"><div class="stat__l">Temas</div><div class="stat__v"><?= $totalTemas ?></div><div class="stat__u">con check individual</div></div>
      <div class="stat"><div class="stat__l">Evaluación</div><div class="stat__v">5</div><div class="stat__u">preguntas · mínimo <?= CAP_QUIZ_MINIMO ?> correctas</div></div>
      <div class="stat"><div class="stat__l">Acuerdo</div><div class="stat__v"><?= count(cap_consentimiento()) ?></div><div class="stat__u">cláusulas por aceptar</div></div>
    </div>

    <div class="stack" style="margin-top:22px">
      <div class="callout callout--ink">
        <span class="callout__ico"><?= cap_icono('alert') ?></span>
        <div>
          <div class="callout__t">Regla número uno: es un sitio en producción</div>
          <div class="callout__d">
            El CMS de <?= h(CAP_CLIENTE) ?> está en línea y atiende usuarios reales. Nada de tema, plugins, menús o
            configuración global se modifica sin consultar y obtener aprobación previa por escrito. La formación
            existe justamente para saber qué sí se puede tocar y qué no.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TEMARIO -->
<section class="section" id="temario">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow"><span class="eyebrow__dot"></span>Temario</span>
      <h2>5 clases en 2 jornadas, <?= $totalTemas ?> temas verificables</h2>
      <p>Al ingresar a tu panel podrás marcar cada tema como comprendido. Ese checklist es la trazabilidad del proceso.</p>
    </div>

    <div class="temario">
      <?php foreach ($sesiones as $n => $s): $abierto = ($n === 1); ?>
      <article class="clase<?= $abierto ? ' is-open' : '' ?>">
        <button class="clase__head" type="button" aria-expanded="<?= $abierto ? 'true' : 'false' ?>">
          <span class="clase__n"><?= $n ?></span>
          <span class="clase__main">
            <span class="clase__title">Clase <?= $n ?> · <?= h($s['titulo']) ?></span>
            <span class="clase__meta">
              <span class="badge badge--muted" style="height:auto;padding:2px 7px">Jornada <?= cap_jornada_de($n) ?></span>
              <?= cap_icono('calendar', 'ico ico--sm') ?><span><?= h(cap_fecha_larga($s['fecha'])) ?></span>
              <span aria-hidden="true">·</span>
              <span class="mono"><?= h($s['hora_inicio']) ?> — <?= h($s['hora_fin']) ?></span>
            </span>
          </span>
          <span class="clase__side">
            <span class="badge badge--muted"><?= count($s['temas']) ?> temas</span>
            <?= cap_icono('chevron', 'ico clase__chev') ?>
          </span>
        </button>
        <div class="clase__body">
          <p class="clase__resumen"><?= h($s['resumen']) ?></p>
          <ul class="checklist">
            <?php foreach ($s['temas'] as $t): ?>
            <li class="check-item check-item--static">
              <span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span>
              <span class="check-text"><?= h($t) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- REGLAS -->
<section class="section section--soft" id="reglas">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow"><span class="eyebrow__dot"></span>Condiciones de acceso</span>
      <h2>Lo que se firma antes de recibir credenciales</h2>
      <p>Tener acceso al CMS implica responsabilidad sobre un activo del negocio. Estas son las reglas que cada estudiante acepta en su panel.</p>
    </div>
    <div class="reglas">
      <div class="regla">
        <span class="regla__ico"><?= cap_icono('alert') ?></span>
        <h3>Consultar antes de cambiar</h3>
        <p>Tema, plugins, menús, estructura y configuración global no se tocan sin aprobación previa por escrito. Contenido sí, dentro del alcance del cargo.</p>
      </div>
      <div class="regla">
        <span class="regla__ico"><?= cap_icono('database') ?></span>
        <h3>Backup antes de todo</h3>
        <p>Ninguna actualización ni cambio estructural ocurre sin una copia de seguridad generada y verificada previamente.</p>
      </div>
      <div class="regla">
        <span class="regla__ico"><?= cap_icono('key') ?></span>
        <h3>Credenciales personales</h3>
        <p>Contraseña única, gestor de claves y doble factor. Las credenciales no se comparten, no se reutilizan y no viajan por chat.</p>
      </div>
      <div class="regla">
        <span class="regla__ico"><?= cap_icono('shield') ?></span>
        <h3>Mínimo privilegio</h3>
        <p>Cada persona recibe el rol exacto que su cargo necesita. Nadie es administrador por comodidad.</p>
      </div>
      <div class="regla">
        <span class="regla__ico"><?= cap_icono('edit') ?></span>
        <h3>Bitácora de cambios</h3>
        <p>Todo cambio relevante se registra: quién, qué, cuándo y por qué. Sin bitácora no hay forma de revertir con criterio.</p>
      </div>
      <div class="regla">
        <span class="regla__ico"><?= cap_icono('lock') ?></span>
        <h3>Accesos revocables</h3>
        <p>El acceso se otorga por función, queda auditado y puede revocarse si se incumplen las condiciones acordadas.</p>
      </div>
    </div>
  </div>
</section>

<!-- EVALUACIÓN -->
<section class="section" id="evaluacion">
  <div class="container container--narrow">
    <div class="card">
      <div class="card__head">
        <div>
          <div class="card__title">Evaluación final</div>
          <div class="card__sub">5 preguntas de caso real · se aprueba con <?= CAP_QUIZ_MINIMO ?> correctas · hasta <?= CAP_QUIZ_MAX_INT ?> intentos</div>
        </div>
        <span class="badge badge--accent">Requisito para los accesos</span>
      </div>
      <div class="card__body">
        <p style="font-size:15.5px;color:var(--q-ink-3);margin-bottom:20px">
          La evaluación se habilita cuando tienes asistencia a las 5 clases, el 100% del temario marcado y el acuerdo de uso firmado.
          No mide memoria: mide criterio para decidir qué hacer frente a una situación real en el CMS.
        </p>
        <ul class="checklist">
          <li class="check-item check-item--static"><span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span><span class="check-text">Prioridad del respaldo antes de actualizar</span></li>
          <li class="check-item check-item--static"><span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span><span class="check-text">Cuándo consultar antes de aplicar un cambio</span></li>
          <li class="check-item check-item--static"><span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span><span class="check-text">Asignación correcta de roles de usuario</span></li>
          <li class="check-item check-item--static"><span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span><span class="check-text">Buenas prácticas de credenciales y 2FA</span></li>
          <li class="check-item check-item--static"><span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span><span class="check-text">Contenido mínimo del informe mensual</span></li>
        </ul>
      </div>
      <div class="card__foot" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between">
        <span class="muted" style="font-size:13.5px">Al aprobar se emite la constancia y se habilita la entrega de accesos.</span>
        <a class="btn btn--accent btn--sm" href="<?= $yo ? 'panel.php?v=evaluacion' : 'index.php?v=acceso' ?>">
          <?= $yo ? 'Ir a la evaluación' : 'Ingresar' ?> <?= cap_icono('arrow', 'ico ico--sm') ?>
        </a>
      </div>
    </div>

    <div class="callout callout--info" style="margin-top:18px">
      <span class="callout__ico"><?= cap_icono('user') ?></span>
      <div>
        <div class="callout__t">¿Eres el instructor?</div>
        <div class="callout__d">
          Gestiona estudiantes, asistencia, acuerdos firmados y entrega de accesos desde el
          <a href="index.php?v=admin" style="color:inherit;font-weight:600;text-decoration:underline;text-underline-offset:3px">panel del instructor</a>.
        </div>
      </div>
    </div>
  </div>
</section>

<?php cap_footer(); ?>
