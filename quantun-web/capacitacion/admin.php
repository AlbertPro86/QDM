<?php
/**
 * Panel del equipo.
 * Administrador: todo. Supervisor: tareas, reportes y consulta de usuarios.
 */
require_once __DIR__ . '/lib/ui.php';

cap_exigir_staff();

$esAdmin  = cap_es_admin();
$data     = cap_leer();
$sesiones = cap_sesiones();

$usuarios = array_map('cap_normalizar_estudiante', $data['estudiantes']);
usort($usuarios, fn($a, $b) => strcmp(cap_nombre_completo($a), cap_nombre_completo($b)));
$estudiantes     = array_values(array_filter($usuarios, 'cap_es_estudiante'));
$totSupervisores = count($usuarios) - count($estudiantes);

$metricas = [];
foreach ($estudiantes as $e) { $metricas[$e['id']] = cap_metricas($e); }

$totAlumnos   = count($estudiantes);
$totFirmados  = count(array_filter($metricas, fn($m) => $m['consentimiento']));
$totAprobados = count(array_filter($metricas, fn($m) => $m['quiz_aprobado']));
$totAccesos   = count(array_filter($estudiantes, fn($e) => !empty($e['accesos']['entregados'])));
$promedio     = $totAlumnos ? (int)round(array_sum(array_column($metricas, 'general')) / $totAlumnos) : 0;

$metSesion = [];
foreach (array_keys($sesiones) as $n) { $metSesion[$n] = cap_metricas_sesion($data, $n); }
$clasesDictadas = count(array_filter($metSesion, fn($m) => $m['dictada']));
$jornadas       = cap_jornadas();

/* Tareas */
$tareas = $data['tareas'] ?? [];
usort($tareas, fn($a, $b) => strcmp($b['creada'] ?? '', $a['creada'] ?? ''));
$resTareas  = cap_resumen_tareas_global($data);
$porId      = [];
foreach ($usuarios as $u) { $porId[(string)$u['id']] = $u; }
$estActivos = array_values(array_filter($estudiantes, fn($e) => !empty($e['activo'])));
$tareasEst  = [];
foreach ($estudiantes as $e) { $tareasEst[$e['id']] = cap_resumen_tareas_de($data, $e['id']); }

/* Vistas según el rol */
$vistas = $esAdmin
    ? ['usuarios', 'tareas', 'programa', 'asistencia', 'accesos', 'reportes', 'bitacora', 'cuenta']
    : ['usuarios', 'tareas', 'reportes'];
$vista = $_GET['v'] ?? ($esAdmin ? 'usuarios' : 'tareas');
if ($vista === 'estudiantes') { $vista = 'usuarios'; }
if (!in_array($vista, $vistas, true)) { $vista = $vistas[0]; }

cap_head($esAdmin ? 'Panel del instructor' : 'Panel del supervisor');
cap_nav('', [], ['nombre' => cap_admin_nombre()], 'index.php?salir=1');
?>

<main class="app">
  <div class="container">

    <div class="app__head">
      <div>
        <span class="eyebrow"><span class="eyebrow__dot"></span><?= h(CAP_CLIENTE) ?> · capacitación CMS · <?= h(cap_rol_nombre()) ?></span>
        <h1>Gestión del programa</h1>
        <p><?= $esAdmin
            ? 'Administra usuarios y roles, asigna tareas, registra asistencia y entrega accesos solo cuando el proceso esté completo.'
            : 'Asigna tareas a los estudiantes, sigue quién cumple y cuánto tarda, y genera los reportes del programa.' ?></p>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <a class="btn btn--ghost btn--sm" href="index.php" target="_blank" rel="noopener"><?= cap_icono('external', 'ico ico--sm') ?> Ver landing</a>
        <button class="btn btn--<?= $esAdmin ? 'ghost' : 'accent' ?> btn--sm" type="button" data-nueva-tarea><?= cap_icono('tasks', 'ico ico--sm') ?> Nueva tarea</button>
        <?php if ($esAdmin): ?>
          <a class="btn btn--accent btn--sm" href="#alta"><?= cap_icono('plus', 'ico ico--sm') ?> Agregar usuario</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="stats" style="margin-bottom:26px">
      <div class="stat"><div class="stat__l">Estudiantes</div><div class="stat__v"><?= $totAlumnos ?></div><div class="stat__u"><?= $totSupervisores ?> supervisor(es)</div></div>
      <div class="stat"><div class="stat__l">Acuerdo firmado</div><div class="stat__v"><?= $totFirmados ?></div><div class="stat__u">de <?= $totAlumnos ?></div></div>
      <div class="stat"><div class="stat__l">Evaluación aprobada</div><div class="stat__v"><?= $totAprobados ?></div><div class="stat__u">de <?= $totAlumnos ?></div></div>
      <?php if ($esAdmin): ?>
        <div class="stat"><div class="stat__l">Accesos entregados</div><div class="stat__v"><?= $totAccesos ?></div><div class="stat__u">promedio de avance <?= $promedio ?>%</div></div>
      <?php else: ?>
        <div class="stat"><div class="stat__l">Cumplimiento de tareas</div><div class="stat__v"><?= $resTareas['pct'] ?>%</div><div class="stat__u"><?= $resTareas['completadas'] ?> de <?= $resTareas['total'] ?> asignaciones</div></div>
      <?php endif; ?>
    </div>

    <nav class="tabs">
      <button class="tab<?= $vista === 'usuarios' ? ' is-active' : '' ?>" data-tab="usuarios" type="button"><?= cap_icono('users', 'ico ico--sm') ?> Usuarios <span class="tab__n"><?= count($usuarios) ?></span></button>
      <button class="tab<?= $vista === 'tareas' ? ' is-active' : '' ?>" data-tab="tareas" type="button"><?= cap_icono('tasks', 'ico ico--sm') ?> Tareas <span class="tab__n"><?= count($tareas) ?></span></button>
      <?php if ($esAdmin): ?>
      <button class="tab<?= $vista === 'programa' ? ' is-active' : '' ?>" data-tab="programa" type="button"><?= cap_icono('book', 'ico ico--sm') ?> Programa <span class="tab__n"><?= $clasesDictadas ?>/<?= count($sesiones) ?></span></button>
      <button class="tab<?= $vista === 'asistencia' ? ' is-active' : '' ?>" data-tab="asistencia" type="button"><?= cap_icono('calendar', 'ico ico--sm') ?> Asistencia</button>
      <button class="tab<?= $vista === 'accesos' ? ' is-active' : '' ?>" data-tab="accesos" type="button"><?= cap_icono('key', 'ico ico--sm') ?> Entrega de accesos</button>
      <?php endif; ?>
      <button class="tab<?= $vista === 'reportes' ? ' is-active' : '' ?>" data-tab="reportes" type="button"><?= cap_icono('report', 'ico ico--sm') ?> Reportes</button>
      <?php if ($esAdmin): ?>
      <button class="tab<?= $vista === 'bitacora' ? ' is-active' : '' ?>" data-tab="bitacora" type="button"><?= cap_icono('clipboard', 'ico ico--sm') ?> Bitácora</button>
      <button class="tab<?= $vista === 'cuenta' ? ' is-active' : '' ?>" data-tab="cuenta" type="button"><?= cap_icono('user', 'ico ico--sm') ?> Mi cuenta</button>
      <?php endif; ?>
    </nav>

    <!-- ============ USUARIOS ============ -->
    <section class="panel<?= $vista === 'usuarios' ? ' is-active' : '' ?>" data-panel="usuarios">
      <div class="stack">
        <?php if ($esAdmin): ?>
        <div id="alta" class="alta<?= $usuarios ? '' : ' is-open' ?>">
          <form class="card" id="formAlta">
            <div class="card__head">
              <div><div class="card__title">Agregar usuario</div><div class="card__sub">Elige su rol y define la contraseña con la que entrará</div></div>
              <button class="btn btn--ghost btn--xs" type="button" data-alta-cerrar>Cerrar</button>
            </div>
            <div class="card__body">
              <div class="alta__grid">
                <label class="field">
                  <span class="field__label">Nombre</span>
                  <input class="input" type="text" name="nombre" required placeholder="Nombre">
                </label>
                <label class="field">
                  <span class="field__label">Apellidos</span>
                  <input class="input" type="text" name="apellidos" required placeholder="Apellidos">
                </label>
                <label class="field">
                  <span class="field__label">Correo electrónico</span>
                  <input class="input" type="email" name="email" required placeholder="nombre@empresa.com">
                </label>
                <label class="field">
                  <span class="field__label">Rol</span>
                  <select class="select" name="rol" required>
                    <?php foreach (CAP_ROLES as $k => $lbl): ?>
                      <option value="<?= h($k) ?>"><?= h($lbl) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <span class="field__hint">Supervisor: asigna tareas y revisa reportes. No toma la capacitación.</span>
                </label>
                <label class="field">
                  <span class="field__label">Cargo</span>
                  <select class="select" name="cargo" required>
                    <option value="">Selecciona un cargo</option>
                    <?php foreach (CAP_CARGOS as $c): ?>
                      <option value="<?= h($c) ?>"><?= h($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label class="field">
                  <span class="field__label">Contraseña</span>
                  <span class="alta__clave">
                    <input class="input" type="text" name="clave" required minlength="<?= CAP_CLAVE_MIN ?>"
                           placeholder="Mínimo <?= CAP_CLAVE_MIN ?> caracteres" autocomplete="off" data-clave>
                    <button class="btn btn--ghost btn--sm" type="button" data-generar-clave title="Generar una contraseña">
                      <?= cap_icono('refresh', 'ico ico--sm') ?>
                    </button>
                  </span>
                </label>
                <div class="field alta__accion">
                  <button class="btn btn--primary btn--sm btn--block" type="submit"><?= cap_icono('plus', 'ico ico--sm') ?> Agregar usuario</button>
                </div>
              </div>
            </div>
            <div class="card__foot">
              <span class="muted" style="font-size:13px">La contraseña se guarda cifrada: al terminar te la mostramos una sola vez para que la envíes por canal privado. Si se pierde, defines una nueva desde la tabla.</span>
            </div>
          </form>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card__head">
            <div><div class="card__title">Usuarios del programa</div><div class="card__sub"><?= $totAlumnos ?> estudiante(s) · <?= $totSupervisores ?> supervisor(es)</div></div>
            <label style="position:relative;display:block;width:220px">
              <input class="input" id="filtroEstudiantes" type="search" placeholder="Buscar..." style="height:38px;font-size:14px">
            </label>
          </div>

          <?php if (!$usuarios): ?>
            <div class="card__body">
              <div class="empty">
                <span class="empty__ico"><?= cap_icono('users', 'ico ico--lg') ?></span>
                <h3>Todavía no hay usuarios</h3>
                <p>Agrega al equipo de <?= h(CAP_CLIENTE) ?> con su nombre, apellidos, correo, rol y cargo. Tú defines la contraseña con la que entrará cada uno.</p>
              </div>
            </div>
          <?php else: ?>
            <div class="table-wrap" style="border:0;border-radius:0">
              <table class="table">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Cargo</th>
                    <th>Último ingreso</th>
                    <th>Estado</th>
                    <th style="width:150px">Avance</th>
                    <?php if ($esAdmin): ?><th style="text-align:right">Acciones</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $e):
                    $esEst = cap_es_estudiante($e);
                    $m     = $esEst ? $metricas[$e['id']] : null;
                    $nomCompleto = cap_nombre_completo($e);
                    if ($esEst) { [$txt, $tono] = cap_estado_texto($m); }
                ?>
                  <tr data-fila="<?= h($nomCompleto . ' ' . $e['email'] . ' ' . $e['cargo'] . ' ' . CAP_ROLES[$e['rol']]) ?>">
                    <td>
                      <div class="cell-person">
                        <span class="avatar<?= $esEst ? ($m['quiz_aprobado'] ? ' avatar--lima' : ' avatar--soft') : '' ?>"><?= h(cap_iniciales($nomCompleto)) ?></span>
                        <div class="cell-person__t">
                          <div class="cell-person__n"><?= h($nomCompleto) ?><?= empty($e['activo']) ? ' <span class="badge badge--danger">suspendido</span>' : '' ?></div>
                          <div class="cell-person__s"><?= h($e['email']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td><span class="badge badge--<?= $esEst ? 'muted' : 'ink' ?>"><?= h(CAP_ROLES[$e['rol']]) ?></span></td>
                    <td><span class="badge badge--muted"><?= h($e['cargo']) ?></span></td>
                    <td>
                      <?php if (!empty($e['ultimo_ingreso'])): ?>
                        <span class="mono" style="font-size:12.5px;color:var(--q-ink-3)"><?= h(date('d/m/Y H:i', strtotime($e['ultimo_ingreso']))) ?></span>
                      <?php else: ?>
                        <span class="badge badge--muted">Nunca entró</span>
                      <?php endif; ?>
                    </td>
                    <?php if ($esEst): $te = $tareasEst[$e['id']]; ?>
                      <td><span class="badge badge--<?= $tono ?>"><?= h($txt) ?></span></td>
                      <td>
                        <div class="bar bar--thin" style="margin-bottom:6px"><div class="bar__fill bar__fill--lima" style="width:<?= $m['general'] ?>%"></div></div>
                        <span class="mono" style="font-size:12px;color:var(--q-ink-4)"><?= $m['general'] ?>% · tareas <?= $te['completadas'] ?>/<?= $te['total'] ?></span>
                      </td>
                    <?php else: ?>
                      <td><span class="badge badge--info">Asigna y revisa tareas</span></td>
                      <td><span class="muted">—</span></td>
                    <?php endif; ?>
                    <?php if ($esAdmin): ?>
                    <td>
                      <div class="cell-actions">
                        <button class="btn btn--ghost btn--xs" type="button" data-cambiar-clave data-id="<?= h($e['id']) ?>" data-nombre="<?= h($nomCompleto) ?>" data-email="<?= h($e['email']) ?>" title="Cambiar contraseña"><?= cap_icono('key', 'ico ico--sm') ?></button>
                        <button class="btn btn--ghost btn--xs" type="button" data-accion="toggle_activo" data-id="<?= h($e['id']) ?>" title="<?= empty($e['activo']) ? 'Activar' : 'Suspender' ?>"><?= cap_icono(empty($e['activo']) ? 'check-c' : 'lock', 'ico ico--sm') ?></button>
                        <?php if ($esEst && $m['quiz_intentos']): ?>
                        <button class="btn btn--ghost btn--xs" type="button" data-accion="reiniciar_quiz" data-id="<?= h($e['id']) ?>" title="Reiniciar evaluación"><?= cap_icono('award', 'ico ico--sm') ?></button>
                        <?php endif; ?>
                        <button class="btn btn--danger btn--xs" type="button" data-accion="eliminar_estudiante" data-id="<?= h($e['id']) ?>" title="Eliminar"><?= cap_icono('trash', 'ico ico--sm') ?></button>
                      </div>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <div data-sin-resultados style="display:none;padding:34px;text-align:center;color:var(--q-ink-4);font-size:14px">Ningún usuario coincide con la búsqueda.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ============ TAREAS ============ -->
    <section class="panel<?= $vista === 'tareas' ? ' is-active' : '' ?>" data-panel="tareas">
      <div class="stats" style="margin-bottom:18px">
        <div class="stat"><div class="stat__l">Tareas</div><div class="stat__v"><?= count($tareas) ?></div><div class="stat__u">creadas</div></div>
        <div class="stat"><div class="stat__l">Asignaciones</div><div class="stat__v"><?= $resTareas['total'] ?></div><div class="stat__u"><?= $resTareas['en_progreso'] ?> en progreso · <?= $resTareas['pendientes'] ?> pendientes</div></div>
        <div class="stat"><div class="stat__l">Cumplimiento</div><div class="stat__v"><?= $resTareas['pct'] ?>%</div><div class="stat__u"><?= $resTareas['completadas'] ?> completadas · prom. <?= h(cap_duracion_texto($resTareas['promedio'])) ?></div></div>
        <div class="stat"><div class="stat__l">Vencidas</div><div class="stat__v"><?= $resTareas['vencidas'] ?></div><div class="stat__u">sin completar a tiempo</div></div>
      </div>

      <div class="stack">
        <div id="tareaForm" class="alta">
          <form class="card" id="formTarea">
            <input type="hidden" name="tarea" value="">
            <div class="card__head">
              <div><div class="card__title" data-tarea-titulo-form>Nueva tarea</div><div class="card__sub">Define la tarea y marca a quiénes se asigna</div></div>
              <button class="btn btn--ghost btn--xs" type="button" data-tarea-cerrar>Cerrar</button>
            </div>
            <div class="card__body">
              <div class="tarea-grid">
                <label class="field tarea-grid__full">
                  <span class="field__label">Título de la tarea</span>
                  <input class="input" type="text" name="titulo" required minlength="3" maxlength="140" placeholder="Ej.: Publicar la entrada semanal de servicios">
                </label>
                <label class="field">
                  <span class="field__label">Prioridad</span>
                  <select class="select" name="prioridad">
                    <?php foreach (CAP_PRIORIDADES as $k => $lbl): ?>
                      <option value="<?= h($k) ?>"<?= $k === 'media' ? ' selected' : '' ?>><?= h($lbl) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label class="field">
                  <span class="field__label">Fecha límite (opcional)</span>
                  <input class="input" type="date" name="vence">
                </label>
                <label class="field tarea-grid__full">
                  <span class="field__label">Descripción (opcional)</span>
                  <textarea class="textarea" name="descripcion" maxlength="2000" rows="3" placeholder="Qué hay que hacer, criterios de cumplimiento, enlaces de referencia..."></textarea>
                </label>
              </div>

              <div class="asignar">
                <div class="asignar__head">
                  <span class="field__label" style="margin:0">Asignar a</span>
                  <span class="asignar__acciones">
                    <span class="mono muted" style="font-size:12.5px" data-asig-n>0 seleccionados</span>
                    <button type="button" class="btn btn--ghost btn--xs" data-asig-todos>Todos</button>
                    <button type="button" class="btn btn--ghost btn--xs" data-asig-ninguno>Ninguno</button>
                  </span>
                </div>
                <?php if (!$estActivos): ?>
                  <p class="muted" style="font-size:14px;margin:6px 0 0">No hay estudiantes activos. Agrégalos en la pestaña Usuarios.</p>
                <?php else: ?>
                  <ul class="checklist asignar__lista">
                    <?php foreach ($estActivos as $e): ?>
                    <li class="check-item">
                      <label>
                        <input type="checkbox" value="<?= h($e['id']) ?>" data-asig>
                        <span class="check-box"><?= cap_icono('check', 'ico') ?></span>
                        <span class="check-text"><?= h(cap_nombre_completo($e)) ?> <span class="muted" style="font-size:12.5px">· <?= h($e['cargo']) ?></span></span>
                      </label>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
            <div class="card__foot" style="display:flex;justify-content:flex-end">
              <button class="btn btn--primary btn--sm" type="submit" data-tarea-submit><?= cap_icono('check', 'ico ico--sm') ?> <span>Crear y asignar</span></button>
            </div>
          </form>
        </div>

        <div class="card">
          <div class="card__head">
            <div><div class="card__title">Tareas asignadas</div><div class="card__sub">Quién cumple, cuánto tardó y cómo avanza cada tarea</div></div>
            <button class="btn btn--accent btn--sm" type="button" data-nueva-tarea><?= cap_icono('plus', 'ico ico--sm') ?> Nueva tarea</button>
          </div>
          <?php if (!$tareas): ?>
            <div class="card__body">
              <div class="empty">
                <span class="empty__ico"><?= cap_icono('tasks', 'ico ico--lg') ?></span>
                <h3>Todavía no hay tareas</h3>
                <p>Crea una tarea, marca a qué estudiantes se asigna y sigue aquí su cumplimiento.</p>
              </div>
            </div>
          <?php else: ?>
            <div class="card__body stack" style="gap:12px">
              <?php foreach ($tareas as $t):
                  $r = cap_resumen_tarea($t);
                  $json = json_encode([
                      'id'          => $t['id'],
                      'titulo'      => $t['titulo'],
                      'descripcion' => $t['descripcion'] ?? '',
                      'prioridad'   => $t['prioridad'] ?? 'media',
                      'vence'       => $t['vence'] ?? '',
                      'asignados'   => array_map('strval', array_keys($t['asignaciones'] ?? [])),
                  ], JSON_UNESCAPED_UNICODE);
              ?>
              <article class="clase tarea<?= $r['total'] && $r['completadas'] === $r['total'] ? ' clase--done' : '' ?>">
                <button class="clase__head" type="button" aria-expanded="false">
                  <span class="clase__n"><?= cap_icono('tasks', 'ico') ?></span>
                  <span class="clase__main">
                    <span class="clase__title"><?= h($t['titulo']) ?></span>
                    <span class="clase__meta">
                      <span class="badge badge--<?= cap_tono_prioridad($t['prioridad'] ?? 'media') ?>" style="height:auto;padding:2px 7px">Prioridad <?= h(strtolower(CAP_PRIORIDADES[$t['prioridad'] ?? 'media'] ?? 'media')) ?></span>
                      <?php if (!empty($t['vence'])): ?>
                        <?= cap_icono('calendar', 'ico ico--sm') ?><span>Vence <?= h(date('d/m/Y', strtotime($t['vence']))) ?></span>
                      <?php endif; ?>
                      <?php if ($r['vencidas']): ?><span class="badge badge--danger" style="height:auto;padding:2px 7px"><?= $r['vencidas'] ?> vencida(s)</span><?php endif; ?>
                      <span aria-hidden="true">·</span>
                      <span>Por <?= h($t['creada_por'] ?? '') ?></span>
                    </span>
                  </span>
                  <span class="clase__side">
                    <span class="bar" style="width:90px"><span class="bar__fill bar__fill--lima" style="width:<?= $r['pct'] ?>%"></span></span>
                    <span class="mono ses__pct"><?= $r['completadas'] ?>/<?= $r['total'] ?></span>
                    <?= cap_icono('chevron', 'ico clase__chev') ?>
                  </span>
                </button>
                <div class="clase__body">
                  <?php if (!empty($t['descripcion'])): ?>
                    <p class="clase__resumen" style="white-space:pre-line"><?= h($t['descripcion']) ?></p>
                  <?php endif; ?>

                  <div class="prog-stats prog-stats--4">
                    <div class="prog-stat"><div class="prog-stat__l">Completadas</div><div class="prog-stat__v"><?= $r['completadas'] ?><span>/<?= $r['total'] ?></span></div><div class="bar bar--thin"><div class="bar__fill bar__fill--lima" style="width:<?= $r['pct'] ?>%"></div></div></div>
                    <div class="prog-stat"><div class="prog-stat__l">En progreso</div><div class="prog-stat__v"><?= $r['en_progreso'] ?></div><div class="prog-stat__u"><?= $r['pendientes'] ?> sin iniciar</div></div>
                    <div class="prog-stat"><div class="prog-stat__l">Tiempo promedio</div><div class="prog-stat__v" style="font-size:18px;padding-top:4px"><?= h(cap_duracion_texto($r['promedio'])) ?></div><div class="prog-stat__u">de asignación a cumplimiento</div></div>
                    <div class="prog-stat"><div class="prog-stat__l">Creada</div><div class="prog-stat__v" style="font-size:15px;padding-top:6px"><?= h(cap_fecha_hora($t['creada'] ?? null)) ?></div><div class="prog-stat__u"><?= $r['vencidas'] ?> vencida(s)</div></div>
                  </div>

                  <div class="table-wrap">
                    <table class="table" style="min-width:760px">
                      <thead><tr><th>Estudiante</th><th>Estado</th><th>Asignada</th><th>Iniciada</th><th>Completada</th><th>Tiempo</th><th>Nota</th><th></th></tr></thead>
                      <tbody>
                      <?php foreach (($t['asignaciones'] ?? []) as $eid => $a):
                          $eid = (string)$eid;
                          $u = $porId[$eid] ?? null;
                          if (!$u) { continue; }
                          [$eTxt, $eTono] = cap_estado_tarea($a['estado'], cap_asignacion_vencida($t, $a));
                      ?>
                        <tr>
                          <td>
                            <div class="cell-person__n" style="font-size:14px"><?= h(cap_nombre_completo($u)) ?></div>
                            <div class="cell-person__s"><?= h($u['cargo']) ?></div>
                          </td>
                          <td><span class="badge badge--<?= $eTono ?>"><?= h($eTxt) ?></span></td>
                          <td class="mono" style="font-size:12px"><?= h(cap_fecha_hora($a['asignada'] ?? null)) ?></td>
                          <td class="mono" style="font-size:12px"><?= h(cap_fecha_hora($a['iniciada'] ?? null)) ?></td>
                          <td class="mono" style="font-size:12px"><?= h(cap_fecha_hora($a['completada'] ?? null)) ?></td>
                          <td class="mono" style="font-size:12px;font-weight:600"><?= h(cap_duracion_texto(cap_asignacion_duracion($a))) ?></td>
                          <td style="font-size:13px;color:var(--q-ink-3);max-width:220px;white-space:normal"><?= $a['nota'] !== '' ? h($a['nota']) : '<span class="muted">—</span>' ?></td>
                          <td style="text-align:right">
                            <?php if ($a['estado'] === 'completada'): ?>
                              <button class="btn btn--ghost btn--xs" type="button" data-accion="reabrir_asignacion" data-id="<?= h($t['id']) ?>" data-est="<?= h($eid) ?>" title="Reabrir para este estudiante"><?= cap_icono('refresh', 'ico ico--sm') ?></button>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <div class="tarea-acciones">
                    <button class="btn btn--ghost btn--sm" type="button" data-editar-tarea="<?= h($json) ?>"><?= cap_icono('edit', 'ico ico--sm') ?> Editar y reasignar</button>
                    <button class="btn btn--danger btn--sm" type="button" data-accion="eliminar_tarea" data-id="<?= h($t['id']) ?>"><?= cap_icono('trash', 'ico ico--sm') ?> Eliminar</button>
                  </div>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if ($esAdmin): ?>
    <!-- ============ PROGRAMA ============ -->
    <section class="panel<?= $vista === 'programa' ? ' is-active' : '' ?>" data-panel="programa">
      <div class="callout callout--ink" style="margin-bottom:20px">
        <span class="callout__ico"><?= cap_icono('book') ?></span>
        <div>
          <div class="callout__t">Programa oficial · <?= count($sesiones) ?> clases en <?= count($jornadas) ?> jornadas · <?= cap_total_temas() ?> temas</div>
          <div class="callout__d">
            2 viernes, <?= h(CAP_HORA_INICIO) ?> a <?= h(CAP_HORA_FIN) ?> cada uno (4 horas en total):
            <?php foreach ($jornadas as $i => $j): ?>
              <?= h(cap_fecha_larga($j['fecha'])) ?><?= $i < count($jornadas) - 1 ? ' y ' : '' ?>
            <?php endforeach; ?>.
            Marca cada clase como dictada al terminarla: queda registrada en la bitácora y sirve de referencia
            frente al avance que reporta cada estudiante.
          </div>
        </div>
      </div>

      <div class="stack">
        <?php foreach ($sesiones as $n => $s): $ms = $metSesion[$n]; [$txtS, $tonoS] = cap_estado_sesion($ms); ?>
        <article class="clase<?= $n === 1 ? ' is-open' : '' ?><?= $ms['dictada'] ? ' clase--done' : '' ?>" data-clase="<?= $n ?>">
          <button class="clase__head" type="button" aria-expanded="<?= $n === 1 ? 'true' : 'false' ?>">
            <span class="clase__n"><?= $n ?></span>
            <span class="clase__main">
              <span class="clase__title">Clase <?= $n ?> · <?= h($s['titulo']) ?></span>
              <span class="clase__meta">
                <span class="badge badge--muted" style="height:auto;padding:2px 7px">Jornada <?= cap_jornada_de($n) ?></span>
                <?= cap_icono('calendar', 'ico ico--sm') ?><span><?= h(cap_fecha_larga($s['fecha'])) ?></span>
                <span aria-hidden="true">·</span>
                <span class="mono"><?= h($s['hora_inicio']) ?> — <?= h($s['hora_fin']) ?></span>
                <span aria-hidden="true">·</span>
                <span><?= count($s['temas']) ?> temas</span>
              </span>
            </span>
            <span class="clase__side">
              <span class="badge badge--warn" data-sucio-badge hidden>Sin guardar</span>
              <span class="badge badge--<?= $tonoS ?>"><?= h($txtS) ?></span>
              <?php if ($totAlumnos): ?>
                <span class="badge badge--muted"><?= cap_icono('users', 'ico ico--sm') ?> <?= $ms['asistieron'] ?>/<?= $ms['total'] ?></span>
              <?php endif; ?>
              <?= cap_icono('chevron', 'ico clase__chev') ?>
            </span>
          </button>

          <div class="clase__body">
            <p class="clase__resumen"><?= h($s['resumen']) ?></p>

            <?php if ($totAlumnos): ?>
            <div class="prog-stats">
              <div class="prog-stat">
                <div class="prog-stat__l">Asistencia</div>
                <div class="prog-stat__v"><?= $ms['asistieron'] ?><span>/<?= $ms['total'] ?></span></div>
                <div class="bar bar--thin"><div class="bar__fill bar__fill--lima" style="width:<?= $ms['pct_asistencia'] ?>%"></div></div>
              </div>
              <div class="prog-stat">
                <div class="prog-stat__l">Avance del grupo</div>
                <div class="prog-stat__v"><?= $ms['pct_grupo'] ?><span>%</span></div>
                <div class="bar bar--thin"><div class="bar__fill bar__fill--lima" style="width:<?= $ms['pct_grupo'] ?>%"></div></div>
              </div>
              <div class="prog-stat">
                <div class="prog-stat__l">Estado</div>
                <div class="prog-stat__v" style="font-size:17px;padding-top:6px"><?= h($txtS) ?></div>
                <div class="prog-stat__u"><?= $ms['cerrada_en'] ? 'Cerrada el ' . h(date('d/m/Y', strtotime($ms['cerrada_en']))) : 'Sin cerrar' ?></div>
              </div>
            </div>
            <?php endif; ?>

            <div class="prog-temas">
              <div class="prog-temas__t">Temario de la clase · marca lo que ya diste<?= $totAlumnos ? ' · a la derecha, cuántos estudiantes lo marcaron' : '' ?></div>
              <ul class="checklist">
                <?php foreach ($s['temas'] as $i => $t): $c = $ms['por_tema'][$i]; $pct = $totAlumnos ? (int)round($c / $totAlumnos * 100) : 0; $dado = $ms['temas_cubiertos'][$i]; ?>
                <li class="check-item" style="align-items:center">
                  <label>
                    <input type="checkbox" data-tema-admin data-tema-idx="<?= $i ?>" <?= $dado ? 'checked' : '' ?>>
                    <span class="check-box"><?= cap_icono('check', 'ico') ?></span>
                    <span class="check-text"><?= h($t) ?></span>
                  </label>
                  <?php if ($totAlumnos): ?>
                    <span class="prog-tema__c">
                      <span class="bar bar--thin" style="width:70px"><span class="bar__fill bar__fill--lima" style="width:<?= $pct ?>%"></span></span>
                      <span class="mono"><?= $c ?>/<?= $totAlumnos ?></span>
                    </span>
                  <?php endif; ?>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <form class="prog-cierre" data-form-sesion="<?= $n ?>">
              <label class="field" style="margin-bottom:0;flex:1;min-width:240px">
                <span class="field__label">Notas de la clase</span>
                <input class="input" type="text" name="nota" value="<?= h($ms['notas']) ?>" placeholder="Qué se cubrió, quién faltó, pendientes...">
              </label>
              <button class="btn btn--primary btn--sm" type="submit" data-guardar>
                <?= cap_icono('check', 'ico ico--sm') ?> Guardar cambios
              </button>
              <button class="btn btn--<?= $ms['dictada'] ? 'ghost' : 'accent' ?> btn--sm" type="button"
                      data-accion="marcar_sesion" data-sesion="<?= $n ?>" data-valor="<?= $ms['dictada'] ? '0' : '1' ?>">
                <?= cap_icono($ms['dictada'] ? 'refresh' : 'check-c', 'ico ico--sm') ?>
                <?= $ms['dictada'] ? 'Reabrir clase' : 'Marcar como dictada' ?>
              </button>
            </form>
            <div class="prog-pie">
              <div class="save-state" data-estado>
                <span class="save-state__dot"></span>
                <span data-estado-txt>Todo guardado</span>
              </div>
              <button class="btn btn--danger btn--xs" type="button"
                      data-accion="reiniciar_sesion" data-sesion="<?= $n ?>">
                <?= cap_icono('refresh', 'ico ico--sm') ?> Reiniciar clase
              </button>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ============ ASISTENCIA ============ -->
    <section class="panel<?= $vista === 'asistencia' ? ' is-active' : '' ?>" data-panel="asistencia">
      <div class="card">
        <div class="card__head">
          <div><div class="card__title">Registro de asistencia</div><div class="card__sub">2 jornadas · <?= h(CAP_HORA_INICIO) ?> a <?= h(CAP_HORA_FIN) ?> cada una</div></div>
          <span class="badge badge--accent"><?= h(cap_fecha_corta($jornadas[0]['fecha'])) ?> y <?= h(cap_fecha_corta($jornadas[1]['fecha'])) ?></span>
        </div>
        <?php if (!$totAlumnos): ?>
          <div class="card__body"><div class="empty"><span class="empty__ico"><?= cap_icono('calendar', 'ico ico--lg') ?></span><h3>Sin estudiantes</h3><p>Agrega estudiantes para poder registrar asistencia.</p></div></div>
        <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0">
          <table class="table">
            <thead>
              <tr>
                <th rowspan="2" style="vertical-align:bottom">Estudiante</th>
                <?php foreach ($jornadas as $i => $j): ?>
                  <th colspan="<?= count($j['clases']) ?>" style="text-align:center;background:var(--q-surface-2)">
                    Jornada <?= $i + 1 ?> · <?= h(cap_fecha_corta($j['fecha'])) ?>
                  </th>
                <?php endforeach; ?>
                <th rowspan="2" style="text-align:center;vertical-align:bottom">Total</th>
              </tr>
              <tr>
                <?php foreach ($sesiones as $n => $s): ?>
                  <th style="text-align:center">
                    Clase <?= $n ?><br>
                    <span class="mono" style="font-weight:400;text-transform:none;letter-spacing:0"><?= h($s['hora_inicio']) ?></span>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($estudiantes as $e): $m = $metricas[$e['id']]; ?>
              <tr>
                <td>
                  <div class="cell-person">
                    <span class="avatar avatar--soft"><?= h(cap_iniciales(cap_nombre_completo($e))) ?></span>
                    <div class="cell-person__t">
                      <div class="cell-person__n"><?= h(cap_nombre_completo($e)) ?></div>
                      <div class="cell-person__s"><?= h($e['cargo']) ?></div>
                    </div>
                  </div>
                </td>
                <?php foreach ($sesiones as $n => $s): $ps = $m['por_sesion'][$n]; ?>
                <td style="text-align:center">
                  <label class="check-item" style="justify-content:center;padding:0;display:inline-flex">
                    <input type="checkbox" data-asistencia data-id="<?= h($e['id']) ?>" data-sesion="<?= $n ?>" <?= $ps['asistio'] ? 'checked' : '' ?>>
                    <span class="check-box"><?= cap_icono('check', 'ico') ?></span>
                  </label>
                  <div class="mono" style="font-size:11px;color:var(--q-ink-5);margin-top:5px"><?= $ps['ok'] ?>/<?= $ps['total'] ?></div>
                </td>
                <?php endforeach; ?>
                <td style="text-align:center"><span class="badge badge--<?= $m['asistidas'] === 5 ? 'ok' : 'muted' ?>"><?= $m['asistidas'] ?>/5</span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </section>

    <!-- ============ ACCESOS ============ -->
    <section class="panel<?= $vista === 'accesos' ? ' is-active' : '' ?>" data-panel="accesos">
      <div class="callout callout--warn" style="margin-bottom:20px">
        <span class="callout__ico"><?= cap_icono('shield') ?></span>
        <div>
          <div class="callout__t">Nunca guardes credenciales en esta plataforma</div>
          <div class="callout__d">Aquí solo se registra el rol asignado y la fecha de entrega. Las contraseñas se envían por canal privado y el estudiante debe cambiarlas en el primer ingreso.</div>
        </div>
      </div>

      <?php if (!$totAlumnos): ?>
        <div class="card"><div class="card__body"><div class="empty"><span class="empty__ico"><?= cap_icono('key', 'ico ico--lg') ?></span><h3>Sin estudiantes</h3><p>Agrega estudiantes para gestionar la entrega de accesos.</p></div></div></div>
      <?php else: ?>
      <div class="stack">
        <?php foreach ($estudiantes as $e): $m = $metricas[$e['id']]; $entregado = !empty($e['accesos']['entregados']); ?>
        <div class="card">
          <div class="card__head">
            <div class="cell-person">
              <span class="avatar<?= $m['habilitado'] ? ' avatar--lima' : ' avatar--soft' ?> avatar--lg"><?= h(cap_iniciales(cap_nombre_completo($e))) ?></span>
              <div class="cell-person__t">
                <div class="cell-person__n" style="font-size:16px"><?= h(cap_nombre_completo($e)) ?></div>
                <div class="cell-person__s"><?= h($e['cargo']) ?> · <?= h($e['email']) ?></div>
              </div>
            </div>
            <span class="badge badge--<?= $entregado ? 'ok' : ($m['habilitado'] ? 'accent' : 'muted') ?>">
              <?= $entregado ? 'Accesos entregados' : ($m['habilitado'] ? 'Habilitado' : 'No habilitado') ?>
            </span>
          </div>
          <div class="card__body">
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:<?= $entregado ? '0' : '20px' ?>">
              <span class="badge badge--<?= $m['consentimiento'] ? 'ok' : 'muted' ?>"><?= cap_icono($m['consentimiento'] ? 'check' : 'x', 'ico ico--sm') ?> Acuerdo</span>
              <span class="badge badge--<?= $m['asistidas'] === 5 ? 'ok' : 'muted' ?>"><?= cap_icono($m['asistidas'] === 5 ? 'check' : 'x', 'ico ico--sm') ?> Asistencia <?= $m['asistidas'] ?>/5</span>
              <span class="badge badge--<?= $m['pct_temas'] === 100 ? 'ok' : 'muted' ?>"><?= cap_icono($m['pct_temas'] === 100 ? 'check' : 'x', 'ico ico--sm') ?> Temario <?= $m['pct_temas'] ?>%</span>
              <span class="badge badge--<?= $m['quiz_aprobado'] ? 'ok' : 'muted' ?>"><?= cap_icono($m['quiz_aprobado'] ? 'check' : 'x', 'ico ico--sm') ?> Evaluación <?= $m['quiz_intentos'] ? $m['quiz_mejor'] . '/5' : 'sin presentar' ?></span>
            </div>

            <?php if ($entregado): ?>
              <div class="callout callout--ok" style="margin-top:16px">
                <span class="callout__ico"><?= cap_icono('key') ?></span>
                <div>
                  <div class="callout__t">Rol: <?= h($e['accesos']['rol']) ?></div>
                  <div class="callout__d">Entregados el <?= h(date('d/m/Y H:i', strtotime($e['accesos']['fecha']))) ?><?= !empty($e['accesos']['nota']) ? ' · ' . h($e['accesos']['nota']) : '' ?></div>
                </div>
              </div>
            <?php elseif ($m['habilitado']): ?>
              <form data-form-accesos="<?= h($e['id']) ?>">
                <div class="grid-2">
                  <label class="field">
                    <span class="field__label">Rol asignado en WordPress</span>
                    <select class="select" name="rol" required>
                      <option value="">Selecciona el rol</option>
                      <option value="Editor">Editor</option>
                      <option value="Autor">Autor</option>
                      <option value="Colaborador">Colaborador</option>
                      <option value="Administrador">Administrador</option>
                    </select>
                  </label>
                  <label class="field">
                    <span class="field__label">Nota de entrega (opcional)</span>
                    <input class="input" type="text" name="nota" placeholder="Canal por el que se enviaron las credenciales">
                  </label>
                </div>
                <button class="btn btn--accent btn--sm" type="submit"><?= cap_icono('key', 'ico ico--sm') ?> Registrar entrega de accesos</button>
              </form>
            <?php else: ?>
              <p class="muted" style="font-size:14px">Faltan requisitos por completar. Los accesos se habilitan cuando el estudiante firma el acuerdo, marca el 100% del temario y aprueba la evaluación.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <?php endif; /* fin de paneles solo admin (programa, asistencia, accesos) */ ?>

    <!-- ============ REPORTES ============ -->
    <section class="panel<?= $vista === 'reportes' ? ' is-active' : '' ?>" data-panel="reportes">
      <div class="rep-cards">
        <?php foreach ([
            'tareas'     => ['Reporte de tareas', 'Cumplimiento por estudiante, tiempos y detalle de cada tarea asignada.', 'tasks'],
            'evaluacion' => ['Reporte de evaluación', 'Evaluación completa: preguntas, respuesta correcta, aciertos y respuestas de cada estudiante.', 'award'],
            'general'    => ['Reporte general', 'Tareas y evaluación en un solo documento para entregar al cliente.', 'report'],
        ] as $tipo => [$tit, $desc, $ico]): ?>
        <div class="rep-card">
          <span class="regla__ico"><?= cap_icono($ico) ?></span>
          <div class="rep-card__t"><?= h($tit) ?></div>
          <p class="rep-card__d"><?= h($desc) ?></p>
          <div class="rep-card__a">
            <a class="btn btn--ghost btn--sm" href="reporte.php?tipo=<?= $tipo ?>" target="_blank" rel="noopener"><?= cap_icono('external', 'ico ico--sm') ?> Ver</a>
            <a class="btn btn--primary btn--sm" href="reporte.php?tipo=<?= $tipo ?>&amp;pdf=1" target="_blank" rel="noopener"><?= cap_icono('download', 'ico ico--sm') ?> Exportar PDF</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card" style="margin-top:18px">
        <div class="card__head"><div><div class="card__title">Cumplimiento de tareas por estudiante</div><div class="card__sub">Asignadas, completadas, vencidas y tiempo promedio de cumplimiento</div></div></div>
        <?php if (!$estudiantes): ?>
          <div class="card__body"><p class="muted">Sin estudiantes todavía.</p></div>
        <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0">
          <table class="table">
            <thead><tr><th>Estudiante</th><th style="text-align:center">Asignadas</th><th style="text-align:center">Completadas</th><th style="text-align:center">En progreso</th><th style="text-align:center">Pendientes</th><th style="text-align:center">Vencidas</th><th style="width:160px">Cumplimiento</th><th>Tiempo prom.</th></tr></thead>
            <tbody>
            <?php foreach ($estudiantes as $e): $te = $tareasEst[$e['id']]; ?>
              <tr>
                <td><div class="cell-person__n" style="font-size:14px"><?= h(cap_nombre_completo($e)) ?></div><div class="cell-person__s"><?= h($e['cargo']) ?></div></td>
                <td class="mono" style="text-align:center"><?= $te['total'] ?></td>
                <td class="mono" style="text-align:center"><?= $te['completadas'] ?></td>
                <td class="mono" style="text-align:center"><?= $te['en_progreso'] ?></td>
                <td class="mono" style="text-align:center"><?= $te['pendientes'] ?></td>
                <td class="mono" style="text-align:center"><?= $te['vencidas'] ? '<span class="badge badge--danger">' . $te['vencidas'] . '</span>' : '0' ?></td>
                <td>
                  <div class="bar bar--thin" style="margin-bottom:5px"><div class="bar__fill bar__fill--lima" style="width:<?= $te['pct'] ?>%"></div></div>
                  <span class="mono" style="font-size:12px;color:var(--q-ink-4)"><?= $te['pct'] ?>%</span>
                </td>
                <td class="mono" style="font-size:12.5px"><?= h(cap_duracion_texto($te['promedio'])) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <div class="card" style="margin-top:18px">
        <div class="card__head"><div><div class="card__title">Evaluación por estudiante</div><div class="card__sub">Acuerdo, asistencia, temario y resultado de la evaluación final</div></div></div>
        <?php if (!$estudiantes): ?>
          <div class="card__body"><p class="muted">Sin estudiantes todavía.</p></div>
        <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0">
          <table class="table">
            <thead><tr><th>Estudiante</th><th>Acuerdo</th><th style="text-align:center">Asistencia</th><th style="text-align:center">Temario</th><th style="text-align:center">Intentos</th><th style="text-align:center">Mejor puntaje</th><th>Evaluación</th></tr></thead>
            <tbody>
            <?php foreach ($estudiantes as $e): $m = $metricas[$e['id']]; [$evTxt, $evTono] = cap_estado_evaluacion($m); ?>
              <tr>
                <td><div class="cell-person__n" style="font-size:14px"><?= h(cap_nombre_completo($e)) ?></div><div class="cell-person__s"><?= h($e['cargo']) ?></div></td>
                <td><?= $m['consentimiento'] ? '<span class="badge badge--ok">Firmado</span>' : '<span class="badge badge--warn">Pendiente</span>' ?></td>
                <td class="mono" style="text-align:center"><?= $m['asistidas'] ?>/<?= count($sesiones) ?></td>
                <td class="mono" style="text-align:center"><?= $m['pct_temas'] ?>%</td>
                <td class="mono" style="text-align:center"><?= $m['quiz_intentos'] ?>/<?= CAP_QUIZ_MAX_INT ?></td>
                <td class="mono" style="text-align:center"><?= $m['quiz_intentos'] ? $m['quiz_mejor'] . '/' . count(cap_quiz()) : '—' ?></td>
                <td><span class="badge badge--<?= $evTono ?>"><?= h($evTxt) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($esAdmin): ?>
    <!-- ============ BITÁCORA ============ -->
    <section class="panel<?= $vista === 'bitacora' ? ' is-active' : '' ?>" data-panel="bitacora">
      <div class="card">
        <div class="card__head">
          <div><div class="card__title">Bitácora de actividad</div><div class="card__sub">Últimos <?= min(200, count($data['bitacora'])) ?> eventos registrados</div></div>
        </div>
        <?php $log = array_slice(array_reverse($data['bitacora']), 0, 200); ?>
        <?php if (!$log): ?>
          <div class="card__body"><div class="empty"><span class="empty__ico"><?= cap_icono('clipboard', 'ico ico--lg') ?></span><h3>Sin actividad todavía</h3><p>Aquí quedará registrado cada ingreso, tema marcado, acuerdo firmado y entrega de accesos.</p></div></div>
        <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0">
          <table class="table" style="min-width:640px">
            <thead><tr><th style="width:150px">Fecha</th><th style="width:170px">Actor</th><th style="width:120px">Acción</th><th>Detalle</th><th style="width:130px">IP</th></tr></thead>
            <tbody>
            <?php foreach ($log as $l): ?>
              <tr>
                <td class="mono" style="font-size:12.5px;color:var(--q-ink-4)"><?= h(date('d/m/Y H:i', strtotime($l['fecha']))) ?></td>
                <td style="font-weight:600"><?= h($l['actor']) ?></td>
                <td><span class="badge badge--muted"><?= h($l['accion']) ?></span></td>
                <td><?= h($l['detalle']) ?></td>
                <td class="mono" style="font-size:12px;color:var(--q-ink-5)"><?= h($l['ip']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>


    <!-- ============ MI CUENTA ============ -->
    <section class="panel<?= $vista === 'cuenta' ? ' is-active' : '' ?>" data-panel="cuenta">
      <div class="container--narrow" style="padding:0;max-width:640px">
        <form class="card" id="formCuenta">
          <div class="card__head">
            <div><div class="card__title">Mis credenciales</div><div class="card__sub">Usuario y contraseña con los que entras al panel</div></div>
          </div>
          <div class="card__body">
            <label class="field">
              <span class="field__label">Usuario</span>
              <input class="input" type="text" name="usuario" required minlength="3" value="<?= h(cap_admin_nombre()) ?>">
              <span class="field__hint">Puede ser tu correo. Es con lo que inicias sesión.</span>
            </label>

            <label class="field">
              <span class="field__label">Contraseña actual</span>
              <span class="field__pass">
                <input class="input" type="password" name="clave_actual" required autocomplete="current-password">
                <button type="button" class="field__eye" data-toggle-pass tabindex="-1" aria-label="Mostrar contraseña"><?= cap_icono('eye', 'ico ico--sm') ?></button>
              </span>
              <span class="field__hint">Se pide siempre para confirmar que eres tú.</span>
            </label>

            <label class="field" style="margin-bottom:0">
              <span class="field__label">Contraseña nueva (opcional)</span>
              <span class="field__pass">
                <input class="input" type="password" name="clave_nueva" minlength="10" autocomplete="new-password" placeholder="Déjalo vacío para no cambiarla">
                <button type="button" class="field__eye" data-toggle-pass tabindex="-1" aria-label="Mostrar contraseña"><?= cap_icono('eye', 'ico ico--sm') ?></button>
              </span>
              <span class="field__hint">Mínimo 10 caracteres. No se puede recuperar: guárdala en tu gestor de claves.</span>
            </label>
          </div>
          <div class="card__foot" style="display:flex;justify-content:flex-end">
            <button class="btn btn--primary btn--sm" type="submit"><?= cap_icono('check', 'ico ico--sm') ?> Guardar credenciales</button>
          </div>
        </form>
      </div>
    </section>
    <?php endif; /* fin bitacora y cuenta */ ?>

  </div>
</main>

<?php cap_footer(); ?>
