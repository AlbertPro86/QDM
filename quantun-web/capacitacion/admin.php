<?php
/**
 * Panel del instructor: estudiantes, asistencia, entrega de accesos y bitácora.
 */
require_once __DIR__ . '/lib/ui.php';

cap_exigir_admin();

$data      = cap_leer();
$sesiones  = cap_sesiones();
$estudiantes = array_map('cap_normalizar_estudiante', $data['estudiantes']);

usort($estudiantes, fn($a, $b) => strcmp(cap_nombre_completo($a), cap_nombre_completo($b)));

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
$jornadas        = cap_jornadas();

$vista = $_GET['v'] ?? 'estudiantes';

cap_head('Panel del instructor');
cap_nav('', [], ['nombre' => cap_admin_nombre()], 'index.php?salir=1');
?>

<main class="app">
  <div class="container">

    <div class="app__head">
      <div>
        <span class="eyebrow"><span class="eyebrow__dot"></span><?= h(CAP_CLIENTE) ?> · capacitación CMS</span>
        <h1>Gestión del programa</h1>
        <p>Administra estudiantes, registra asistencia y entrega accesos solo cuando el proceso esté completo.</p>
      </div>
      <div style="display:flex;gap:10px;align-items:center">
        <a class="btn btn--ghost btn--sm" href="index.php" target="_blank" rel="noopener"><?= cap_icono('external', 'ico ico--sm') ?> Ver landing</a>
        <a class="btn btn--accent btn--sm" href="#alta"><?= cap_icono('plus', 'ico ico--sm') ?> Agregar estudiante</a>
      </div>
    </div>

    <div class="stats" style="margin-bottom:26px">
      <div class="stat"><div class="stat__l">Estudiantes</div><div class="stat__v"><?= $totAlumnos ?></div><div class="stat__u">registrados</div></div>
      <div class="stat"><div class="stat__l">Acuerdo firmado</div><div class="stat__v"><?= $totFirmados ?></div><div class="stat__u">de <?= $totAlumnos ?></div></div>
      <div class="stat"><div class="stat__l">Evaluación aprobada</div><div class="stat__v"><?= $totAprobados ?></div><div class="stat__u">de <?= $totAlumnos ?></div></div>
      <div class="stat"><div class="stat__l">Accesos entregados</div><div class="stat__v"><?= $totAccesos ?></div><div class="stat__u">promedio de avance <?= $promedio ?>%</div></div>
    </div>

    <nav class="tabs">
      <button class="tab<?= $vista === 'estudiantes' ? ' is-active' : '' ?>" data-tab="estudiantes" type="button"><?= cap_icono('users', 'ico ico--sm') ?> Estudiantes <span class="tab__n"><?= $totAlumnos ?></span></button>
      <button class="tab<?= $vista === 'programa' ? ' is-active' : '' ?>" data-tab="programa" type="button"><?= cap_icono('book', 'ico ico--sm') ?> Programa <span class="tab__n"><?= $clasesDictadas ?>/<?= count($sesiones) ?></span></button>
      <button class="tab<?= $vista === 'asistencia' ? ' is-active' : '' ?>" data-tab="asistencia" type="button"><?= cap_icono('calendar', 'ico ico--sm') ?> Asistencia</button>
      <button class="tab<?= $vista === 'accesos' ? ' is-active' : '' ?>" data-tab="accesos" type="button"><?= cap_icono('key', 'ico ico--sm') ?> Entrega de accesos</button>
      <button class="tab<?= $vista === 'bitacora' ? ' is-active' : '' ?>" data-tab="bitacora" type="button"><?= cap_icono('clipboard', 'ico ico--sm') ?> Bitácora</button>
      <button class="tab<?= $vista === 'cuenta' ? ' is-active' : '' ?>" data-tab="cuenta" type="button"><?= cap_icono('user', 'ico ico--sm') ?> Mi cuenta</button>
    </nav>

    <!-- ============ ESTUDIANTES ============ -->
    <section class="panel<?= $vista === 'estudiantes' ? ' is-active' : '' ?>" data-panel="estudiantes">
      <div class="stack">
        <div id="alta" class="alta<?= $totAlumnos ? '' : ' is-open' ?>">
          <form class="card" id="formAlta">
            <div class="card__head">
              <div><div class="card__title">Agregar estudiante</div><div class="card__sub">Tú defines la contraseña con la que entrará</div></div>
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
                  <button class="btn btn--primary btn--sm btn--block" type="submit"><?= cap_icono('plus', 'ico ico--sm') ?> Agregar estudiante</button>
                </div>
              </div>
            </div>
            <div class="card__foot">
              <span class="muted" style="font-size:13px">La contraseña se guarda cifrada: al terminar te la mostramos una sola vez para que la envíes por canal privado. Si se pierde, defines una nueva desde la tabla.</span>
            </div>
          </form>
        </div>

          <div class="card">
            <div class="card__head">
              <div><div class="card__title">Estudiantes del programa</div><div class="card__sub">Nombre, correo, cargo y estado del proceso</div></div>
              <label style="position:relative;display:block;width:220px">
                <input class="input" id="filtroEstudiantes" type="search" placeholder="Buscar..." style="height:38px;font-size:14px">
              </label>
            </div>

            <?php if (!$totAlumnos): ?>
              <div class="card__body">
                <div class="empty">
                  <span class="empty__ico"><?= cap_icono('users', 'ico ico--lg') ?></span>
                  <h3>Todavía no hay estudiantes</h3>
                  <p>Agrega al equipo de <?= h(CAP_CLIENTE) ?> con su nombre, apellidos, correo y cargo. Tú defines la contraseña con la que entrará cada uno.</p>
                </div>
              </div>
            <?php else: ?>
              <div class="table-wrap" style="border:0;border-radius:0">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Estudiante</th>
                      <th>Cargo</th>
                      <th>Último ingreso</th>
                      <th>Estado</th>
                      <th style="width:150px">Avance</th>
                      <th style="text-align:right">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($estudiantes as $e): $m = $metricas[$e['id']]; [$txt, $tono] = cap_estado_texto($m); ?>
                    <?php $nomCompleto = cap_nombre_completo($e); ?>
                    <tr data-fila="<?= h($nomCompleto . ' ' . $e['email'] . ' ' . $e['cargo']) ?>">
                      <td>
                        <div class="cell-person">
                          <span class="avatar<?= $m['quiz_aprobado'] ? ' avatar--lima' : ' avatar--soft' ?>"><?= h(cap_iniciales($nomCompleto)) ?></span>
                          <div class="cell-person__t">
                            <div class="cell-person__n"><?= h($nomCompleto) ?><?= empty($e['activo']) ? ' <span class="badge badge--danger">suspendido</span>' : '' ?></div>
                            <div class="cell-person__s"><?= h($e['email']) ?></div>
                          </div>
                        </div>
                      </td>
                      <td><span class="badge badge--muted"><?= h($e['cargo']) ?></span></td>
                      <td>
                        <?php if (!empty($e['ultimo_ingreso'])): ?>
                          <span class="mono" style="font-size:12.5px;color:var(--q-ink-3)"><?= h(date('d/m/Y H:i', strtotime($e['ultimo_ingreso']))) ?></span>
                        <?php else: ?>
                          <span class="badge badge--muted">Nunca entró</span>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge badge--<?= $tono === 'muted' ? 'muted' : $tono ?>"><?= h($txt) ?></span></td>
                      <td>
                        <div class="bar bar--thin" style="margin-bottom:6px"><div class="bar__fill bar__fill--lima" style="width:<?= $m['general'] ?>%"></div></div>
                        <span class="mono" style="font-size:12px;color:var(--q-ink-4)"><?= $m['general'] ?>% · <?= $m['temas_ok'] ?>/<?= $m['temas_total'] ?> temas</span>
                      </td>
                      <td>
                        <div class="cell-actions">
                          <button class="btn btn--ghost btn--xs" type="button" data-cambiar-clave data-id="<?= h($e['id']) ?>" data-nombre="<?= h($nomCompleto) ?>" data-email="<?= h($e['email']) ?>" title="Cambiar contraseña"><?= cap_icono('key', 'ico ico--sm') ?></button>
                          <button class="btn btn--ghost btn--xs" type="button" data-accion="toggle_activo" data-id="<?= h($e['id']) ?>" title="<?= empty($e['activo']) ? 'Activar' : 'Suspender' ?>"><?= cap_icono(empty($e['activo']) ? 'check-c' : 'lock', 'ico ico--sm') ?></button>
                          <?php if ($m['quiz_intentos']): ?>
                          <button class="btn btn--ghost btn--xs" type="button" data-accion="reiniciar_quiz" data-id="<?= h($e['id']) ?>" title="Reiniciar evaluación"><?= cap_icono('award', 'ico ico--sm') ?></button>
                          <?php endif; ?>
                          <button class="btn btn--danger btn--xs" type="button" data-accion="eliminar_estudiante" data-id="<?= h($e['id']) ?>" title="Eliminar"><?= cap_icono('trash', 'ico ico--sm') ?></button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
                <div data-sin-resultados style="display:none;padding:34px;text-align:center;color:var(--q-ink-4);font-size:14px">Ningún estudiante coincide con la búsqueda.</div>
              </div>
            <?php endif; ?>
          </div>
      </div>
    </section>

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

  </div>
</main>

<?php cap_footer(); ?>
