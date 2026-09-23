<?php
/**
 * Panel del estudiante: progreso, temario, acuerdo, evaluación y accesos.
 */
require_once __DIR__ . '/lib/ui.php';

$yo       = cap_exigir_estudiante();
$m        = cap_metricas($yo);
$sesiones = cap_sesiones();
$clausulas= cap_consentimiento();
$firmado  = $m['consentimiento'];

$vista = $_GET['v'] ?? ($firmado ? 'progreso' : 'acuerdo');
$vistasOk = ['progreso', 'temario', 'tareas', 'acuerdo', 'evaluacion', 'accesos'];

$misTareas  = cap_tareas_de(cap_leer(), $yo['id']);
$pendientes = count(array_filter($misTareas, fn($x) => $x['asig']['estado'] !== 'completada'));
if (!in_array($vista, $vistasOk, true)) { $vista = 'progreso'; }
if (!$firmado) { $vista = 'acuerdo'; }

$ultimoIntento = end($yo['quiz']['intentos']) ?: null;
$mostrarResultado = isset($_GET['r']) && $ultimoIntento;

cap_head('Mi capacitación');
cap_nav('Mi capacitación', [], ['nombre' => cap_nombre_completo($yo)], 'index.php?salir=1');
?>

<main class="app">
  <div class="container">

    <div class="app__head">
      <div>
        <span class="eyebrow"><span class="eyebrow__dot"></span><?= h($yo['cargo']) ?> · <?= h(CAP_CLIENTE) ?></span>
        <h1>Hola, <?= h($yo['nombre']) ?></h1>
        <p>Este es el seguimiento de tu habilitación para gestionar el CMS. Marca cada tema a medida que lo cubrimos en clase.</p>
      </div>
      <div class="card" style="padding:18px 22px;display:flex;align-items:center;gap:20px">
        <div class="ring" data-ring style="--pct:<?= $m['general'] ?>">
          <span class="ring__v" data-ring-v><?= $m['general'] ?>%</span>
          <span class="ring__l">Avance</span>
        </div>
        <div style="min-width:150px">
          <?php [$txt, $tono] = cap_estado_texto($m); ?>
          <span class="badge badge--<?= $tono === 'muted' ? 'muted' : $tono ?>"><?= h($txt) ?></span>
          <div style="font-size:13px;color:var(--q-ink-4);margin-top:10px;line-height:1.7">
            Acuerdo · <strong style="color:var(--q-ink)"><?= $firmado ? 'firmado' : 'pendiente' ?></strong><br>
            Temario · <strong style="color:var(--q-ink)" data-m-temas><?= $m['temas_ok'] ?>/<?= $m['temas_total'] ?></strong><br>
            Evaluación · <strong style="color:var(--q-ink)"><?= $m['quiz_aprobado'] ? 'aprobada' : ($m['quiz_intentos'] ? $m['quiz_mejor'] . '/5' : 'pendiente') ?></strong>
          </div>
        </div>
      </div>
    </div>

    <?php if (!$firmado): ?>
      <div class="callout callout--warn" style="margin-bottom:24px">
        <span class="callout__ico"><?= cap_icono('signature') ?></span>
        <div>
          <div class="callout__t">Primero firma el acuerdo de uso</div>
          <div class="callout__d">El temario y la evaluación se habilitan cuando aceptes las <?= count($clausulas) ?> cláusulas. Léelas con calma: definen qué puedes hacer en el CMS y qué requiere aprobación previa.</div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($firmado): ?>
    <nav class="tabs">
      <button class="tab<?= $vista === 'progreso' ? ' is-active' : '' ?>" data-tab="progreso" type="button"><?= cap_icono('chart', 'ico ico--sm') ?> Mi progreso</button>
      <button class="tab<?= $vista === 'temario' ? ' is-active' : '' ?>" data-tab="temario" type="button"><?= cap_icono('clipboard', 'ico ico--sm') ?> Temario <span class="tab__n" data-m-pct><?= $m['pct_temas'] ?>%</span></button>
      <button class="tab<?= $vista === 'tareas' ? ' is-active' : '' ?>" data-tab="tareas" type="button"><?= cap_icono('tasks', 'ico ico--sm') ?> Mis tareas<?php if ($pendientes): ?> <span class="tab__n"><?= $pendientes ?></span><?php endif; ?></button>
      <button class="tab<?= $vista === 'evaluacion' ? ' is-active' : '' ?>" data-tab="evaluacion" type="button"><?= cap_icono('award', 'ico ico--sm') ?> Evaluación</button>
      <button class="tab<?= $vista === 'accesos' ? ' is-active' : '' ?>" data-tab="accesos" type="button"><?= cap_icono('key', 'ico ico--sm') ?> Mis accesos</button>
      <button class="tab<?= $vista === 'acuerdo' ? ' is-active' : '' ?>" data-tab="acuerdo" type="button"><?= cap_icono('signature', 'ico ico--sm') ?> Acuerdo</button>
    </nav>
    <?php endif; ?>

    <!-- ============ PROGRESO ============ -->
    <section class="panel<?= $vista === 'progreso' ? ' is-active' : '' ?>" data-panel="progreso">
      <div class="cols">
        <div class="stack">
          <div class="card">
            <div class="card__head">
              <div><div class="card__title">Avance por clase</div><div class="card__sub">Asistencia registrada por el instructor y temas marcados por ti</div></div>
            </div>
            <div class="card__body" style="padding-top:6px">
              <?php foreach ($sesiones as $n => $s): $ps = $m['por_sesion'][$n]; ?>
              <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid var(--q-border-subtle)">
                <span class="clase__n" style="width:38px;height:38px;border-radius:11px;font-size:13px<?= $ps['completa'] ? ';background:var(--q-lima);border-color:var(--q-lima)' : '' ?>"><?= $n ?></span>
                <div style="flex:1;min-width:0">
                  <div style="font-weight:600;font-size:14.5px"><?= h($s['titulo']) ?></div>
                  <div style="font-size:12.5px;color:var(--q-ink-4);margin:5px 0 8px">Jornada <?= cap_jornada_de($n) ?> · <?= h(cap_fecha_larga($s['fecha'])) ?> · <?= h($s['hora_inicio']) ?></div>
                  <div class="bar bar--thin"><div class="bar__fill bar__fill--lima" style="width:<?= $ps['pct'] ?>%"></div></div>
                </div>
                <div style="text-align:right;flex:none;display:flex;flex-direction:column;gap:6px;align-items:flex-end">
                  <span class="badge badge--<?= $ps['asistio'] ? 'ok' : 'muted' ?>"><?= $ps['asistio'] ? 'Asistió' : 'Sin asistencia' ?></span>
                  <span class="mono" style="font-size:12.5px;color:var(--q-ink-4)"><?= $ps['ok'] ?>/<?= $ps['total'] ?> temas</span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <?php if ($m['quiz_aprobado']): ?>
          <div class="cert">
            <span class="cert__seal"><?= cap_icono('award', 'ico ico--lg') ?></span>
            <span class="eyebrow"><span class="eyebrow__dot"></span>Constancia de aprobación</span>
            <div class="cert__n"><?= h(cap_nombre_completo($yo)) ?></div>
            <p class="cert__r">
              Completó el programa de capacitación para la gestión del CMS de <?= h(CAP_CLIENTE) ?>,
              firmó el acuerdo de uso y aprobó la evaluación final.
            </p>
            <div class="cert__meta">
              <div class="cert__cell"><div class="cert__cl">Puntaje</div><div class="cert__cv"><?= $m['quiz_mejor'] ?>/5</div></div>
              <div class="cert__cell"><div class="cert__cl">Fecha</div><div class="cert__cv"><?= $ultimoIntento ? h(date('d/m/Y', strtotime($ultimoIntento['fecha']))) : '—' ?></div></div>
              <div class="cert__cell"><div class="cert__cl">Código</div><div class="cert__cv"><?= h(strtoupper(substr($yo['id'], 0, 8))) ?></div></div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <aside class="stack sticky">
          <div class="card">
            <div class="card__body">
              <div class="card__title" style="margin-bottom:16px">Requisitos para los accesos</div>
              <ul class="checklist">
                <li class="check-item check-item--static">
                  <span class="check-dot" style="<?= $firmado ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($firmado ? 'check' : 'x', 'ico ico--sm') ?></span>
                  <span class="check-text">Acuerdo de uso firmado</span>
                </li>
                <li class="check-item check-item--static">
                  <span class="check-dot" style="<?= $m['asistidas'] >= 5 ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($m['asistidas'] >= 5 ? 'check' : 'x', 'ico ico--sm') ?></span>
                  <span class="check-text">Asistencia a las 5 clases <span class="mono muted">(<?= $m['asistidas'] ?>/5)</span></span>
                </li>
                <li class="check-item check-item--static">
                  <span class="check-dot" style="<?= $m['pct_temas'] === 100 ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($m['pct_temas'] === 100 ? 'check' : 'x', 'ico ico--sm') ?></span>
                  <span class="check-text">Temario al 100% <span class="mono muted">(<?= $m['pct_temas'] ?>%)</span></span>
                </li>
                <li class="check-item check-item--static">
                  <span class="check-dot" style="<?= $m['quiz_aprobado'] ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($m['quiz_aprobado'] ? 'check' : 'x', 'ico ico--sm') ?></span>
                  <span class="check-text">Evaluación aprobada</span>
                </li>
              </ul>
              <div class="bar" style="margin-top:16px"><div class="bar__fill" data-bar-general style="width:<?= $m['general'] ?>%"></div></div>
            </div>
          </div>

          <div class="callout callout--warn">
            <span class="callout__ico"><?= cap_icono('alert') ?></span>
            <div>
              <div class="callout__t">Antes de tocar el CMS</div>
              <div class="callout__d">Consulta siempre antes de cambiar tema, plugins, menús o configuración global. El contenido sí es tuyo; la estructura del sitio no.</div>
            </div>
          </div>
        </aside>
      </div>
    </section>

    <!-- ============ TEMARIO ============ -->
    <section class="panel<?= $vista === 'temario' ? ' is-active' : '' ?>" data-panel="temario">
      <div class="section-head" style="margin-bottom:22px">
        <h2 style="font-size:24px">Checklist del temario</h2>
        <p style="font-size:15.5px">Marca cada tema cuando lo hayas comprendido. Queda registrado con fecha y es lo que revisamos antes de entregar accesos.</p>
      </div>
      <div class="stack">
        <?php foreach ($sesiones as $n => $s): $ps = $m['por_sesion'][$n]; ?>
        <article class="ses<?= $ps['completa'] ? ' ses--full' : '' ?><?= $n === 1 ? ' is-open' : '' ?>" data-ses="<?= $n ?>">
          <button class="ses__head" type="button" aria-expanded="<?= $n === 1 ? 'true' : 'false' ?>">
            <span class="ses__n"><?= $n ?></span>
            <span class="ses__main">
              <span class="ses__t">Clase <?= $n ?> · <?= h($s['titulo']) ?></span>
              <span class="ses__m">
                <?= cap_icono('calendar', 'ico ico--sm') ?><span><?= h(cap_fecha_corta($s['fecha'])) ?> · <?= h($s['hora_inicio']) ?></span>
                <span aria-hidden="true">·</span>
                <span class="badge badge--<?= $ps['asistio'] ? 'ok' : 'muted' ?>"><?= $ps['asistio'] ? 'Asistió' : 'Sin asistencia' ?></span>
              </span>
            </span>
            <span class="ses__r">
              <span class="bar" style="width:84px"><span class="bar__fill bar__fill--lima" data-ses-bar style="width:<?= $ps['pct'] ?>%"></span></span>
              <span class="ses__pct mono" data-ses-pct><?= $ps['ok'] ?>/<?= $ps['total'] ?></span>
              <?= cap_icono('chevron', 'ico ses__chev') ?>
            </span>
          </button>
          <div class="ses__body">
            <p style="font-size:14.5px;color:var(--q-ink-3);margin:0 0 14px"><?= h($s['resumen']) ?></p>
            <ul class="checklist">
              <?php foreach ($s['temas'] as $i => $t): $ok = !empty($yo['progreso'][(string)$n]['temas'][$i]); ?>
              <li class="check-item">
                <label>
                  <input type="checkbox" data-tema="<?= $i ?>" data-sesion="<?= $n ?>" <?= $ok ? 'checked' : '' ?>>
                  <span class="check-box"><?= cap_icono('check', 'ico') ?></span>
                  <span class="check-text"><?= h($t) ?></span>
                </label>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ============ MIS TAREAS ============ -->
    <section class="panel<?= $vista === 'tareas' ? ' is-active' : '' ?>" data-panel="tareas">
      <div class="container--narrow" style="padding:0;max-width:820px">
        <div class="section-head" style="margin-bottom:20px">
          <h2 style="font-size:24px">Mis tareas</h2>
          <p style="font-size:15.5px">Tareas que te asignó el equipo. Márcalas al empezar y al terminar: el tiempo queda registrado.</p>
        </div>

        <?php if (!$misTareas): ?>
          <div class="card"><div class="card__body">
            <div class="empty">
              <span class="empty__ico"><?= cap_icono('tasks', 'ico ico--lg') ?></span>
              <h3>No tienes tareas asignadas</h3>
              <p>Cuando el supervisor o el instructor te asigne una tarea, aparecerá aquí.</p>
            </div>
          </div></div>
        <?php else: ?>
          <div class="stack">
            <?php foreach ($misTareas as $it):
                $t = $it['tarea']; $a = $it['asig'];
                $venc = cap_asignacion_vencida($t, $a);
                [$eTxt, $eTono] = cap_estado_tarea($a['estado'], $venc);
                $prio = $t['prioridad'] ?? 'media';
            ?>
            <article class="card tarea-est<?= $a['estado'] === 'completada' ? ' tarea-est--ok' : '' ?>">
              <div class="card__head">
                <div style="min-width:0">
                  <div class="card__title"><?= h($t['titulo']) ?></div>
                  <div class="card__sub">
                    Asignada el <?= h(cap_fecha_hora($a['asignada'] ?? null)) ?> por <?= h($t['creada_por'] ?? '') ?>
                    <?php if (!empty($t['vence'])): ?> · Vence el <?= h(date('d/m/Y', strtotime($t['vence']))) ?><?php endif; ?>
                  </div>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <span class="badge badge--<?= cap_tono_prioridad($prio) ?>">Prioridad <?= h(strtolower(CAP_PRIORIDADES[$prio] ?? 'media')) ?></span>
                  <span class="badge badge--<?= $eTono ?>"><?= h($eTxt) ?></span>
                </div>
              </div>
              <?php if (!empty($t['descripcion'])): ?>
                <div class="card__body" style="padding-top:18px;padding-bottom:18px">
                  <p style="font-size:15px;color:var(--q-ink-2);white-space:pre-line;line-height:1.6"><?= h($t['descripcion']) ?></p>
                </div>
              <?php endif; ?>
              <div class="card__foot tarea-est__foot">
                <?php if ($a['estado'] === 'completada'): ?>
                  <span class="tarea-est__info">
                    <?= cap_icono('check-c', 'ico ico--sm') ?>
                    Completada el <?= h(cap_fecha_hora($a['completada'])) ?> · tiempo <?= h(cap_duracion_texto(cap_asignacion_duracion($a))) ?>
                    <?php if ($a['nota'] !== ''): ?><span class="tarea-est__nota">"<?= h($a['nota']) ?>"</span><?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="tarea-est__info">
                    <?= $a['estado'] === 'en_progreso'
                        ? cap_icono('clock', 'ico ico--sm') . ' Iniciada el ' . h(cap_fecha_hora($a['iniciada']))
                        : cap_icono('clock', 'ico ico--sm') . ' Aún no la inicias' ?>
                  </span>
                  <span style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php if ($a['estado'] === 'pendiente'): ?>
                      <button class="btn btn--ghost btn--sm" type="button" data-tarea-iniciar="<?= h($t['id']) ?>"><?= cap_icono('arrow', 'ico ico--sm') ?> Empezar</button>
                    <?php endif; ?>
                    <button class="btn btn--primary btn--sm" type="button" data-tarea-completar="<?= h($t['id']) ?>" data-titulo="<?= h($t['titulo']) ?>"><?= cap_icono('check', 'ico ico--sm') ?> Marcar como completada</button>
                  </span>
                <?php endif; ?>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ============ ACUERDO ============ -->
    <section class="panel<?= $vista === 'acuerdo' ? ' is-active' : '' ?>" data-panel="acuerdo">
      <div class="container--narrow" style="padding:0;max-width:820px">
        <?php if ($firmado): ?>
          <div class="card">
            <div class="card__head">
              <div><div class="card__title">Acuerdo de uso firmado</div><div class="card__sub">Versión <?= h($yo['consentimiento']['version']) ?> · constancia registrada</div></div>
              <span class="badge badge--ok"><?= cap_icono('check', 'ico ico--sm') ?> Firmado</span>
            </div>
            <div class="card__body">
              <div class="callout callout--ok" style="margin-bottom:22px">
                <span class="callout__ico"><?= cap_icono('signature') ?></span>
                <div>
                  <div class="callout__t">Firmado por <?= h(cap_nombre_completo($yo)) ?></div>
                  <div class="callout__d">
                    <?= h(date('d/m/Y', strtotime($yo['consentimiento']['fecha'])) . ' ' . cap_hora12(strtotime($yo['consentimiento']['fecha']))) ?> ·
                    IP <span class="mono"><?= h($yo['consentimiento']['ip']) ?></span>
                  </div>
                </div>
              </div>
              <ul class="checklist">
                <?php foreach ($clausulas as $k => $txt): ?>
                <li class="check-item check-item--static">
                  <span class="check-dot"><?= cap_icono('check', 'ico ico--sm') ?></span>
                  <span class="check-text"><?= h($txt) ?></span>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="card__foot">
              <span class="muted" style="font-size:13.5px">Para modificar o revocar este acuerdo, contacta al instructor.</span>
            </div>
          </div>
        <?php else: ?>
          <form class="card" id="formAcuerdo">
            <div class="card__head">
              <div><div class="card__title">Acuerdo de uso del CMS</div><div class="card__sub">Lee y acepta cada cláusula · versión <?= CAP_CONSENT_VER ?></div></div>
              <span class="badge badge--warn"><span data-acuerdo-n>0/<?= count($clausulas) ?></span> aceptadas</span>
            </div>
            <div class="card__body">
              <p style="font-size:15px;color:var(--q-ink-3);margin-bottom:20px">
                El sitio <a href="<?= h(CAP_CLIENTE_URL) ?>" target="_blank" rel="noopener" style="font-weight:600;color:var(--q-ink);text-decoration:underline;text-underline-offset:3px">taxi-taxi.com.co</a>
                está en producción. Al aceptar, te comprometes con las siguientes condiciones de uso y seguridad.
              </p>
              <ul class="checklist">
                <?php foreach ($clausulas as $k => $txt): ?>
                <li class="check-item">
                  <label>
                    <input type="checkbox" data-clausula="<?= h($k) ?>">
                    <span class="check-box"><?= cap_icono('check', 'ico') ?></span>
                    <span class="check-text"><?= h($txt) ?></span>
                  </label>
                </li>
                <?php endforeach; ?>
              </ul>
              <div style="margin-top:24px;padding-top:22px;border-top:1px solid var(--q-border-subtle)">
                <label class="field" style="margin-bottom:0">
                  <span class="field__label">Firma: escribe tu nombre completo</span>
                  <input class="input" type="text" data-acuerdo-cont value="<?= h(cap_nombre_completo($yo)) ?>" required>
                  <span class="field__hint">Se registrará junto con la fecha y tu dirección IP como constancia.</span>
                </label>
              </div>
            </div>
            <div class="card__foot" style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
              <span class="muted" style="font-size:13px">Esta firma habilita el temario y la evaluación.</span>
              <button class="btn btn--primary btn--sm" type="submit" data-acuerdo-btn disabled><?= cap_icono('signature', 'ico ico--sm') ?> Firmar acuerdo</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <!-- ============ EVALUACIÓN ============ -->
    <section class="panel<?= $vista === 'evaluacion' ? ' is-active' : '' ?>" data-panel="evaluacion">
      <div class="container--narrow" style="padding:0;max-width:820px">

        <?php if ($mostrarResultado): $ap = $ultimoIntento['aprobado']; ?>
          <div class="card" style="margin-bottom:18px">
            <div class="card__body">
              <div class="score">
                <div class="ring" style="--pct:<?= (int)round($ultimoIntento['puntaje'] / 5 * 100) ?>">
                  <span class="ring__v"><?= $ultimoIntento['puntaje'] ?>/5</span>
                  <span class="ring__l">Puntaje</span>
                </div>
                <div>
                  <span class="badge badge--<?= $ap ? 'ok' : 'danger' ?>"><?= $ap ? 'Aprobado' : 'No aprobado' ?></span>
                  <h2 style="font-size:26px;margin:12px 0 8px"><?= $ap ? 'Evaluación aprobada' : 'Aún no alcanzas el mínimo' ?></h2>
                  <p class="score__d">
                    <?php if ($ap): ?>
                      Quedas habilitado para recibir los accesos al CMS. El instructor los asignará con el rol que corresponde a tu cargo.
                    <?php else: ?>
                      Se requieren <?= CAP_QUIZ_MINIMO ?> respuestas correctas de 5. Te quedan
                      <strong><?= $m['quiz_restantes'] ?></strong> intento(s). Repasa las clases indicadas abajo antes de reintentar.
                    <?php endif; ?>
                  </p>
                </div>
              </div>
              <?php if (!empty($ultimoIntento['detalle'])): ?>
              <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--q-border-subtle)">
                <?php foreach ($ultimoIntento['detalle'] as $i => $d): ?>
                  <div class="quiz-res quiz-res--<?= $d['ok'] ? 'ok' : 'no' ?>" style="margin-top:8px">
                    <?= cap_icono($d['ok'] ? 'check-c' : 'alert', 'ico ico--sm') ?>
                    <span>Pregunta <?= $i + 1 ?> · <?= $d['ok'] ? 'correcta' : 'incorrecta — repasa ' . h($d['ref']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
            <?php if (!$ap && $m['quiz_restantes'] > 0): ?>
            <div class="card__foot" style="text-align:right">
              <a class="btn btn--primary btn--sm" href="panel.php?v=evaluacion">Reintentar evaluación <?= cap_icono('refresh', 'ico ico--sm') ?></a>
            </div>
            <?php endif; ?>
          </div>

        <?php elseif ($m['quiz_aprobado']): ?>
          <div class="callout callout--ok" style="margin-bottom:18px">
            <span class="callout__ico"><?= cap_icono('award') ?></span>
            <div>
              <div class="callout__t">Evaluación aprobada con <?= $m['quiz_mejor'] ?>/5</div>
              <div class="callout__d">Tu constancia está disponible en la pestaña "Mi progreso". No necesitas presentarla de nuevo.</div>
            </div>
          </div>

        <?php elseif (!$m['puede_evaluar']): ?>
          <div class="card">
            <div class="card__body">
              <div class="empty">
                <span class="empty__ico"><?= cap_icono('lock', 'ico ico--lg') ?></span>
                <h3>Evaluación aún no habilitada</h3>
                <p data-cta-quiz-nota>Se habilita cuando tengas el acuerdo firmado, asistencia a las 5 clases y el 100% del temario marcado.</p>
                <div style="max-width:340px;margin:24px auto 0;text-align:left">
                  <ul class="checklist">
                    <li class="check-item check-item--static"><span class="check-dot" style="<?= $firmado ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($firmado ? 'check' : 'x', 'ico ico--sm') ?></span><span class="check-text">Acuerdo firmado</span></li>
                    <li class="check-item check-item--static"><span class="check-dot" style="<?= $m['asistidas'] >= 5 ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($m['asistidas'] >= 5 ? 'check' : 'x', 'ico ico--sm') ?></span><span class="check-text">Asistencia <span class="mono"><?= $m['asistidas'] ?>/5</span></span></li>
                    <li class="check-item check-item--static"><span class="check-dot" style="<?= $m['pct_temas'] === 100 ? '' : 'background:var(--q-bg-soft);color:var(--q-ink-5)' ?>"><?= cap_icono($m['pct_temas'] === 100 ? 'check' : 'x', 'ico ico--sm') ?></span><span class="check-text">Temario <span class="mono"><?= $m['pct_temas'] ?>%</span></span></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

        <?php elseif ($m['quiz_restantes'] <= 0): ?>
          <div class="callout callout--danger">
            <span class="callout__ico"><?= cap_icono('alert') ?></span>
            <div>
              <div class="callout__t">Intentos agotados</div>
              <div class="callout__d">Usaste los <?= CAP_QUIZ_MAX_INT ?> intentos disponibles (mejor puntaje: <?= $m['quiz_mejor'] ?>/5). Contacta al instructor para reforzar los temas y habilitar un nuevo intento.</div>
            </div>
          </div>

        <?php else: ?>
          <form class="card" id="formQuiz" data-preguntas="<?= count(cap_quiz()) ?>">
            <div class="card__head">
              <div><div class="card__title">Evaluación final</div><div class="card__sub">5 preguntas · se aprueba con <?= CAP_QUIZ_MINIMO ?> correctas · intento <?= $m['quiz_intentos'] + 1 ?> de <?= CAP_QUIZ_MAX_INT ?></div></div>
              <span class="badge badge--muted"><span data-quiz-n>0/5</span> respondidas</span>
            </div>
            <div class="card__body" style="padding-top:4px">
              <?php foreach (cap_quiz() as $i => $q): ?>
              <fieldset class="quiz-q" style="border:0">
                <legend style="display:contents">
                  <span class="quiz-q__n">Pregunta <?= $i + 1 ?></span>
                  <span class="quiz-q__p" style="display:block"><?= h($q['p']) ?></span>
                </legend>
                <div class="quiz-opts">
                  <?php foreach ($q['o'] as $j => $op): ?>
                  <label class="quiz-opt">
                    <input type="radio" name="q<?= $i ?>" value="<?= $j ?>" data-q="<?= $i ?>" required>
                    <span class="quiz-opt__r"></span>
                    <span class="quiz-opt__t"><?= h($op) ?></span>
                  </label>
                  <?php endforeach; ?>
                </div>
              </fieldset>
              <?php endforeach; ?>
            </div>
            <div class="card__foot" style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
              <span class="muted" style="font-size:13px">El intento queda registrado al enviar.</span>
              <button class="btn btn--primary btn--sm" type="submit" data-quiz-btn disabled>Enviar evaluación <?= cap_icono('arrow', 'ico ico--sm') ?></button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <!-- ============ ACCESOS ============ -->
    <section class="panel<?= $vista === 'accesos' ? ' is-active' : '' ?>" data-panel="accesos">
      <div class="container--narrow" style="padding:0;max-width:820px">
        <div class="card">
          <div class="card__head">
            <div><div class="card__title">Mis accesos al CMS</div><div class="card__sub"><?= h(CAP_CLIENTE_URL) ?></div></div>
            <span class="badge badge--<?= !empty($yo['accesos']['entregados']) ? 'ok' : 'muted' ?>"><?= !empty($yo['accesos']['entregados']) ? 'Entregados' : 'Pendientes' ?></span>
          </div>
          <div class="card__body">
            <?php if (!empty($yo['accesos']['entregados'])): ?>
              <div class="callout callout--ok" style="margin-bottom:20px">
                <span class="callout__ico"><?= cap_icono('key') ?></span>
                <div>
                  <div class="callout__t">Rol asignado: <?= h($yo['accesos']['rol'] ?: 'por definir') ?></div>
                  <div class="callout__d">Entregados el <?= h(date('d/m/Y', strtotime($yo['accesos']['fecha']))) ?>. Las credenciales se enviaron por canal privado, nunca se publican aquí.</div>
                </div>
              </div>
              <?php if (!empty($yo['accesos']['nota'])): ?>
                <p style="font-size:15px;color:var(--q-ink-3)"><?= nl2br(h($yo['accesos']['nota'])) ?></p>
              <?php endif; ?>
            <?php else: ?>
              <div class="empty" style="padding:40px 20px">
                <span class="empty__ico"><?= cap_icono('key', 'ico ico--lg') ?></span>
                <h3>Accesos aún no entregados</h3>
                <p>El instructor los asigna cuando el proceso está completo: acuerdo firmado, asistencia, temario y evaluación aprobada.</p>
              </div>
            <?php endif; ?>

            <div class="callout callout--info" style="margin-top:20px">
              <span class="callout__ico"><?= cap_icono('shield') ?></span>
              <div>
                <div class="callout__t">Al recibir tus credenciales</div>
                <div class="callout__d">Cambia la contraseña en el primer ingreso, guárdala en un gestor de claves y activa el doble factor. Nunca las compartas por chat ni por correo.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>
</main>

<?php cap_footer(); ?>
