<?php
/**
 * Reportes imprimibles / exportables a PDF (tareas, evaluación o general).
 * El PDF se genera con el diálogo de impresión del navegador ("Guardar como PDF"):
 * texto real, seleccionable y sin librerías externas.
 */
require_once __DIR__ . '/lib/ui.php';

cap_exigir_staff();
if (!cap_puede('reportes')) {
    http_response_code(403);
    exit('Tu rol no tiene acceso a los reportes.');
}

$tipos = ['tareas' => 'Reporte de tareas', 'evaluacion' => 'Reporte de evaluación', 'general' => 'Reporte general'];
$tipo  = isset($tipos[$_GET['tipo'] ?? '']) ? $_GET['tipo'] : 'general';
$pdf   = !empty($_GET['pdf']);

$data        = cap_leer();
$sesiones    = cap_sesiones();
$quiz        = cap_quiz();
$usuarios    = array_map('cap_normalizar_estudiante', $data['estudiantes']);
usort($usuarios, fn($a, $b) => strcmp(cap_nombre_completo($a), cap_nombre_completo($b)));
$estudiantes = array_values(array_filter($usuarios, 'cap_es_estudiante'));
$porId       = [];
foreach ($usuarios as $u) { $porId[(string)$u['id']] = $u; }

$metricas = [];
foreach ($estudiantes as $e) { $metricas[$e['id']] = cap_metricas($e); }

$tareas = $data['tareas'] ?? [];
usort($tareas, fn($a, $b) => strcmp($b['creada'] ?? '', $a['creada'] ?? ''));
$resGlobal = cap_resumen_tareas_global($data);

$verTareas = ($tipo === 'tareas' || $tipo === 'general');
$verEval   = ($tipo === 'evaluacion' || $tipo === 'general');

$tituloDoc = $tipos[$tipo] . ' - ' . CAP_CLIENTE . ' - ' . date('Y-m-d');
$letra     = fn(int $i) => chr(65 + $i);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($tituloDoc) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Nunito+Sans:opsz,wght@6..12,600;6..12,700;6..12,800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/cap.css?v=<?= cap_asset_ver('assets/cap.css') ?>">
<link rel="stylesheet" href="assets/reporte.css?v=<?= cap_asset_ver('assets/reporte.css') ?>">
</head>
<body class="rep-body">

<div class="rep-toolbar">
  <a class="rep-toolbar__back" href="admin.php?v=reportes"><?= cap_icono('arrow', 'ico ico--sm rep-flip') ?> Volver al panel</a>
  <nav class="rep-toolbar__tipos">
    <?php foreach ($tipos as $k => $lbl): ?>
      <a href="reporte.php?tipo=<?= $k ?>" class="<?= $k === $tipo ? 'is-active' : '' ?>"><?= h(['tareas' => 'Tareas', 'evaluacion' => 'Evaluación', 'general' => 'General'][$k]) ?></a>
    <?php endforeach; ?>
  </nav>
  <button class="rep-toolbar__pdf" type="button" onclick="window.print()"><?= cap_icono('download', 'ico ico--sm') ?> Descargar PDF</button>
</div>

<main class="rep">
  <header class="rep-head">
    <div>
      <img src="../assets/quantun-logo.png" alt="QUANTUN Digital">
      <h1><?= h($tipos[$tipo]) ?></h1>
      <p class="rep-head__sub">Programa de capacitación CMS · <?= h(CAP_CLIENTE) ?></p>
    </div>
    <dl class="rep-meta">
      <div><dt>Generado</dt><dd><?= h(date('d/m/Y H:i')) ?></dd></div>
      <div><dt>Por</dt><dd><?= h(cap_admin_nombre()) ?> · <?= h(cap_rol_nombre()) ?></dd></div>
      <div><dt>Estudiantes</dt><dd><?= count($estudiantes) ?></dd></div>
    </dl>
  </header>

  <?php if ($verTareas): ?>
  <!-- ======================== TAREAS ======================== -->
  <section class="rep-sec">
    <h2>Tareas</h2>
    <div class="rep-kpis">
      <div class="rep-kpi"><div class="rep-kpi__l">Tareas creadas</div><div class="rep-kpi__v"><?= count($tareas) ?></div></div>
      <div class="rep-kpi"><div class="rep-kpi__l">Asignaciones</div><div class="rep-kpi__v"><?= $resGlobal['total'] ?></div></div>
      <div class="rep-kpi"><div class="rep-kpi__l">Cumplimiento</div><div class="rep-kpi__v"><?= $resGlobal['pct'] ?>%</div><div class="rep-kpi__u"><?= $resGlobal['completadas'] ?> completadas</div></div>
      <div class="rep-kpi"><div class="rep-kpi__l">Tiempo promedio</div><div class="rep-kpi__v rep-kpi__v--sm"><?= h(cap_duracion_texto($resGlobal['promedio'])) ?></div><div class="rep-kpi__u"><?= $resGlobal['vencidas'] ?> vencida(s)</div></div>
    </div>

    <h3>Cumplimiento por estudiante</h3>
    <?php if (!$estudiantes): ?>
      <p class="rep-vacio">Sin estudiantes registrados.</p>
    <?php else: ?>
    <div class="rep-table-wrap">
    <table class="rep-table">
      <thead><tr><th>Estudiante</th><th>Cargo</th><th class="c">Asignadas</th><th class="c">Completadas</th><th class="c">En progreso</th><th class="c">Pendientes</th><th class="c">Vencidas</th><th class="c">Cumplimiento</th><th>Tiempo prom.</th></tr></thead>
      <tbody>
      <?php foreach ($estudiantes as $e): $te = cap_resumen_tareas_de($data, $e['id']); ?>
        <tr>
          <td class="b"><?= h(cap_nombre_completo($e)) ?></td>
          <td><?= h($e['cargo']) ?></td>
          <td class="c"><?= $te['total'] ?></td>
          <td class="c"><?= $te['completadas'] ?></td>
          <td class="c"><?= $te['en_progreso'] ?></td>
          <td class="c"><?= $te['pendientes'] ?></td>
          <td class="c"><?= $te['vencidas'] ? '<span class="rep-tag rep-tag--danger">' . $te['vencidas'] . '</span>' : '0' ?></td>
          <td class="c"><span class="rep-pct"><span style="width:<?= $te['pct'] ?>%"></span></span> <?= $te['pct'] ?>%</td>
          <td><?= h(cap_duracion_texto($te['promedio'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>

    <h3>Detalle por tarea</h3>
    <?php if (!$tareas): ?>
      <p class="rep-vacio">Todavía no se han creado tareas.</p>
    <?php endif; ?>
    <?php foreach ($tareas as $t): $r = cap_resumen_tarea($t); $prio = $t['prioridad'] ?? 'media'; ?>
      <article class="rep-block">
        <div class="rep-block__head">
          <div>
            <div class="rep-block__t"><?= h($t['titulo']) ?></div>
            <div class="rep-block__m">
              Prioridad <?= h(strtolower(CAP_PRIORIDADES[$prio] ?? 'media')) ?>
              · Creada el <?= h(cap_fecha_hora($t['creada'] ?? null)) ?> por <?= h($t['creada_por'] ?? '') ?>
              <?php if (!empty($t['vence'])): ?> · Vence el <?= h(date('d/m/Y', strtotime($t['vence']))) ?><?php endif; ?>
            </div>
          </div>
          <div class="rep-block__r"><strong><?= $r['completadas'] ?>/<?= $r['total'] ?></strong> completadas · <?= $r['pct'] ?>%</div>
        </div>
        <?php if (!empty($t['descripcion'])): ?>
          <p class="rep-block__d"><?= nl2br(h($t['descripcion'])) ?></p>
        <?php endif; ?>
        <table class="rep-table rep-table--sm">
          <thead><tr><th>Estudiante</th><th>Estado</th><th>Asignada</th><th>Iniciada</th><th>Completada</th><th>Tiempo</th><th>Nota</th></tr></thead>
          <tbody>
          <?php foreach (($t['asignaciones'] ?? []) as $eid => $a):
              $u = $porId[(string)$eid] ?? null;
              if (!$u) { continue; }
              [$eTxt, $eTono] = cap_estado_tarea($a['estado'], cap_asignacion_vencida($t, $a));
          ?>
            <tr>
              <td class="b"><?= h(cap_nombre_completo($u)) ?></td>
              <td><span class="rep-tag rep-tag--<?= $eTono ?>"><?= h($eTxt) ?></span></td>
              <td class="mono"><?= h(cap_fecha_hora($a['asignada'] ?? null)) ?></td>
              <td class="mono"><?= h(cap_fecha_hora($a['iniciada'] ?? null)) ?></td>
              <td class="mono"><?= h(cap_fecha_hora($a['completada'] ?? null)) ?></td>
              <td class="b"><?= h(cap_duracion_texto(cap_asignacion_duracion($a))) ?></td>
              <td><?= $a['nota'] !== '' ? h($a['nota']) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </article>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($verEval):
      $aprobados = count(array_filter($metricas, fn($m) => $m['quiz_aprobado']));
      $firmados  = count(array_filter($metricas, fn($m) => $m['consentimiento']));
      $conIntento = array_filter($metricas, fn($m) => $m['quiz_intentos'] > 0);
      $promMejor = $conIntento ? round(array_sum(array_column($conIntento, 'quiz_mejor')) / count($conIntento), 1) : null;

      // Aciertos por pregunta, según el último intento de cada estudiante
      $aciertos = array_fill(0, count($quiz), 0);
      $respondieron = 0;
      foreach ($estudiantes as $e) {
          $ult = end($e['quiz']['intentos']) ?: null;
          if (!$ult) { continue; }
          $respondieron++;
          foreach ($ult['detalle'] ?? [] as $i => $dd) { if (!empty($dd['ok'])) { $aciertos[$i]++; } }
      }
  ?>
  <!-- ======================== EVALUACIÓN ======================== -->
  <section class="rep-sec<?= $verTareas ? ' rep-sec--salto' : '' ?>">
    <h2>Evaluación</h2>
    <div class="rep-kpis">
      <div class="rep-kpi"><div class="rep-kpi__l">Acuerdo firmado</div><div class="rep-kpi__v"><?= $firmados ?><span>/<?= count($estudiantes) ?></span></div></div>
      <div class="rep-kpi"><div class="rep-kpi__l">Presentaron</div><div class="rep-kpi__v"><?= $respondieron ?><span>/<?= count($estudiantes) ?></span></div></div>
      <div class="rep-kpi"><div class="rep-kpi__l">Aprobados</div><div class="rep-kpi__v"><?= $aprobados ?><span>/<?= count($estudiantes) ?></span></div></div>
      <div class="rep-kpi"><div class="rep-kpi__l">Puntaje promedio</div><div class="rep-kpi__v"><?= $promMejor !== null ? $promMejor : '—' ?><span>/<?= count($quiz) ?></span></div><div class="rep-kpi__u">mejor intento</div></div>
    </div>
    <p class="rep-nota">Criterio: <?= count($quiz) ?> preguntas · se aprueba con <?= CAP_QUIZ_MINIMO ?> correctas · máximo <?= CAP_QUIZ_MAX_INT ?> intentos · se habilita con acuerdo firmado, asistencia a las <?= count($sesiones) ?> clases y temario al 100%.</p>

    <h3>Resultado por estudiante</h3>
    <?php if (!$estudiantes): ?>
      <p class="rep-vacio">Sin estudiantes registrados.</p>
    <?php else: ?>
    <div class="rep-table-wrap">
    <table class="rep-table">
      <thead><tr><th>Estudiante</th><th>Acuerdo</th><th class="c">Asistencia</th><th class="c">Temario</th><th class="c">Intentos</th><th class="c">Mejor</th><th>Evaluación</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach ($estudiantes as $e):
          $m = $metricas[$e['id']];
          [$evTxt, $evTono] = cap_estado_evaluacion($m);
          $fechaAprob = null;
          foreach ($e['quiz']['intentos'] as $it) { if (!empty($it['aprobado'])) { $fechaAprob = $it['fecha']; break; } }
          $ult = end($e['quiz']['intentos']) ?: null;
      ?>
        <tr>
          <td><span class="b"><?= h(cap_nombre_completo($e)) ?></span><br><span class="rep-muted"><?= h($e['cargo']) ?></span></td>
          <td><?= $m['consentimiento'] ? 'Firmado ' . h(date('d/m/Y', strtotime($e['consentimiento']['fecha']))) : '<span class="rep-tag rep-tag--warn">Pendiente</span>' ?></td>
          <td class="c"><?= $m['asistidas'] ?>/<?= count($sesiones) ?></td>
          <td class="c"><?= $m['pct_temas'] ?>%</td>
          <td class="c"><?= $m['quiz_intentos'] ?>/<?= CAP_QUIZ_MAX_INT ?></td>
          <td class="c b"><?= $m['quiz_intentos'] ? $m['quiz_mejor'] . '/' . count($quiz) : '—' ?></td>
          <td><span class="rep-tag rep-tag--<?= $evTono ?>"><?= h($evTxt) ?></span></td>
          <td class="mono"><?= h(cap_fecha_hora($fechaAprob ?: ($ult['fecha'] ?? null))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>

    <h3>Contenido de la evaluación</h3>
    <?php foreach ($quiz as $i => $q): $pctQ = $respondieron ? (int)round($aciertos[$i] / $respondieron * 100) : null; ?>
      <article class="rep-block rep-q">
        <div class="rep-block__head">
          <div>
            <div class="rep-q__n">Pregunta <?= $i + 1 ?> · <?= h($q['ref']) ?></div>
            <div class="rep-block__t"><?= h($q['p']) ?></div>
          </div>
          <div class="rep-block__r">
            <?php if ($pctQ !== null): ?><strong><?= $aciertos[$i] ?>/<?= $respondieron ?></strong> aciertos · <?= $pctQ ?>%<?php else: ?>Sin respuestas aún<?php endif; ?>
          </div>
        </div>
        <ol class="rep-opts">
          <?php foreach ($q['o'] as $j => $op): ?>
            <li class="<?= $j === (int)$q['c'] ? 'is-ok' : '' ?>">
              <span class="rep-opts__l"><?= $letra($j) ?></span>
              <span><?= h($op) ?></span>
              <?php if ($j === (int)$q['c']): ?><span class="rep-tag rep-tag--ok">Correcta</span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </article>
    <?php endforeach; ?>

    <h3>Respuestas de cada estudiante</h3>
    <?php $alguno = false; foreach ($estudiantes as $e): if (!$e['quiz']['intentos']) { continue; } $alguno = true; ?>
      <article class="rep-block">
        <div class="rep-block__head">
          <div><div class="rep-block__t"><?= h(cap_nombre_completo($e)) ?></div><div class="rep-block__m"><?= h($e['cargo']) ?> · <?= h($e['email']) ?></div></div>
          <?php [$evTxt, $evTono] = cap_estado_evaluacion($metricas[$e['id']]); ?>
          <div class="rep-block__r"><span class="rep-tag rep-tag--<?= $evTono ?>"><?= h($evTxt) ?></span></div>
        </div>
        <table class="rep-table rep-table--sm">
          <thead>
            <tr>
              <th>Intento</th><th>Fecha</th>
              <?php foreach ($quiz as $i => $q): ?><th class="c">P<?= $i + 1 ?></th><?php endforeach; ?>
              <th class="c">Puntaje</th><th>Resultado</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($e['quiz']['intentos'] as $k => $it): ?>
            <tr>
              <td class="b">#<?= $k + 1 ?></td>
              <td class="mono"><?= h(cap_fecha_hora($it['fecha'] ?? null)) ?></td>
              <?php foreach ($quiz as $i => $q):
                  $resp = $it['respuestas'][$i] ?? null;
                  $ok   = !empty($it['detalle'][$i]['ok']);
              ?>
                <td class="c"><span class="rep-ans rep-ans--<?= $ok ? 'ok' : 'no' ?>"><?= $resp === null ? '—' : $letra((int)$resp) ?></span></td>
              <?php endforeach; ?>
              <td class="c b"><?= (int)$it['puntaje'] ?>/<?= count($quiz) ?></td>
              <td><span class="rep-tag rep-tag--<?= !empty($it['aprobado']) ? 'ok' : 'danger' ?>"><?= !empty($it['aprobado']) ? 'Aprobado' : 'No aprobado' ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </article>
    <?php endforeach; ?>
    <?php if (!$alguno): ?><p class="rep-vacio">Ningún estudiante ha presentado la evaluación todavía.</p><?php endif; ?>
    <p class="rep-nota">Letras en verde: respuesta correcta · en rojo: incorrecta.</p>
  </section>
  <?php endif; ?>

  <footer class="rep-foot">
    <span>QUANTUN Digital · Capacitación CMS · <?= h(CAP_CLIENTE) ?></span>
    <span>Documento confidencial · <?= h(date('d/m/Y H:i')) ?></span>
  </footer>
</main>

<?php if ($pdf): ?>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
<?php endif; ?>
</body>
</html>
