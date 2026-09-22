<?php
/**
 * Persistencia en archivo JSON con bloqueo exclusivo.
 * No toca la base de datos del CRM ni ninguna otra tabla del hosting.
 */

require_once __DIR__ . '/config.php';

define('CAP_DATA_DIR',  __DIR__ . '/../data');
define('CAP_DATA_FILE', CAP_DATA_DIR . '/capacitacion.json');

function cap_estructura_inicial(): array {
    $sesiones = [];
    foreach (cap_sesiones() as $n => $s) {
        $sesiones[(string)$n] = [
            'dictada'         => false,
            'notas'           => '',
            'cerrada_en'      => null,
            'temas_cubiertos' => array_fill(0, count($s['temas']), false),
        ];
    }
    return [
        'version'      => CAP_VERSION,
        'creado'       => date('c'),
        'admin'        => null,   // ['hash' => ..., 'creado' => ...]
        'estudiantes'  => [],
        'sesiones'     => $sesiones,
        'bitacora'     => [],
    ];
}

function cap_asegurar_dir(): void {
    if (!is_dir(CAP_DATA_DIR)) {
        @mkdir(CAP_DATA_DIR, 0755, true);
    }
    $ht = CAP_DATA_DIR . '/.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Order allow,deny\nDeny from all\n");
    }
}

function cap_leer(): array {
    cap_asegurar_dir();
    if (!file_exists(CAP_DATA_FILE)) {
        return cap_estructura_inicial();
    }
    $raw = file_get_contents(CAP_DATA_FILE);
    $d   = json_decode($raw, true);
    if (!is_array($d)) {
        return cap_estructura_inicial();
    }
    return $d + cap_estructura_inicial();
}

/**
 * Lee, aplica el callback y escribe de forma atómica.
 * @param callable $fn function(array &$data): mixed
 */
function cap_transaccion(callable $fn) {
    cap_asegurar_dir();
    $fh = fopen(CAP_DATA_FILE, 'c+');
    if (!$fh) { throw new RuntimeException('No se pudo abrir el almacén de datos.'); }
    flock($fh, LOCK_EX);

    $raw  = stream_get_contents($fh);
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = cap_estructura_inicial(); }
    else { $data = $data + cap_estructura_inicial(); }

    $resultado = $fn($data);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, $json);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    return $resultado;
}

function cap_bitacora(array &$data, string $actor, string $accion, string $detalle = ''): void {
    $data['bitacora'][] = [
        'fecha'   => date('c'),
        'actor'   => $actor,
        'accion'  => $accion,
        'detalle' => $detalle,
        'ip'      => cap_ip(),
    ];
    if (count($data['bitacora']) > 500) {
        $data['bitacora'] = array_slice($data['bitacora'], -500);
    }
}

function cap_ip(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function cap_uuid(): string {
    return bin2hex(random_bytes(8));
}

/**
 * Sugerencia de contraseña legible para entregar al estudiante.
 * Evita caracteres ambiguos (O/0, l/1) para que no haya errores al dictarla.
 */
function cap_clave_sugerida(): string {
    $silabas = ['ta','xi','ru','ma','le','po','ne','sa','vi','to','da','mi','fe','co','lu','ra'];
    $numeros = '23456789';
    $c = '';
    for ($i = 0; $i < 4; $i++) { $c .= $silabas[random_int(0, count($silabas) - 1)]; }
    $c = ucfirst($c);
    for ($i = 0; $i < 3; $i++) { $c .= $numeros[random_int(0, strlen($numeros) - 1)]; }
    return $c;
}

/** Nombre completo de un estudiante. */
function cap_nombre_completo(array $e): string {
    return trim(($e['nombre'] ?? '') . ' ' . ($e['apellidos'] ?? ''));
}

/** Esqueleto de progreso para un estudiante nuevo. */
function cap_progreso_inicial(): array {
    $p = [];
    foreach (cap_sesiones() as $n => $s) {
        $p[(string)$n] = [
            'asistio'    => false,
            'temas'      => array_fill(0, count($s['temas']), false),
            'actualizado'=> null,
        ];
    }
    return $p;
}

function cap_estudiante_nuevo(string $nombre, string $apellidos, string $email, string $cargo, string $clave): array {
    return [
        'id'         => cap_uuid(),
        'nombre'     => $nombre,
        'apellidos'  => $apellidos,
        'email'      => strtolower($email),
        'cargo'      => $cargo,
        'clave_hash' => password_hash($clave, PASSWORD_DEFAULT),
        'creado'     => date('c'),
        'activo'    => true,
        'ultimo_ingreso' => null,
        'consentimiento' => [
            'aceptado' => false,
            'fecha'    => null,
            'ip'       => null,
            'version'  => CAP_CONSENT_VER,
            'clausulas'=> [],
        ],
        'progreso'  => cap_progreso_inicial(),
        'quiz'      => [
            'aprobado' => false,
            'mejor'    => 0,
            'intentos' => [],
        ],
        'accesos'   => [
            'entregados' => false,
            'fecha'      => null,
            'rol'        => '',
            'nota'       => '',
        ],
    ];
}

/** Normaliza un estudiante antiguo si el temario cambió de tamaño o faltan campos nuevos. */
function cap_normalizar_estudiante(array $e): array {
    $e['apellidos']  = (string)($e['apellidos'] ?? '');
    $e['clave_hash'] = (string)($e['clave_hash'] ?? '');

    $base = cap_progreso_inicial();
    foreach ($base as $n => $def) {
        if (!isset($e['progreso'][$n])) { $e['progreso'][$n] = $def; continue; }
        $cur   = $e['progreso'][$n];
        $temas = is_array($cur['temas'] ?? null) ? $cur['temas'] : [];
        $total = count($def['temas']);
        $temas = array_slice(array_pad($temas, $total, false), 0, $total);
        $e['progreso'][$n] = [
            'asistio'     => (bool)($cur['asistio'] ?? false),
            'temas'       => array_map('boolval', $temas),
            'actualizado' => $cur['actualizado'] ?? null,
        ];
    }
    return $e;
}

function cap_buscar_estudiante(array $data, string $id): ?array {
    foreach ($data['estudiantes'] as $e) {
        if ($e['id'] === $id) { return cap_normalizar_estudiante($e); }
    }
    return null;
}

function cap_indice_estudiante(array $data, string $id): ?int {
    foreach ($data['estudiantes'] as $i => $e) {
        if ($e['id'] === $id) { return $i; }
    }
    return null;
}

/** Métricas de avance de un estudiante. */
function cap_metricas(array $e): array {
    $e        = cap_normalizar_estudiante($e);
    $sesiones = cap_sesiones();
    $totTemas = cap_total_temas();
    $okTemas  = 0;
    $asistidas= 0;
    $porSesion= [];

    foreach ($sesiones as $n => $s) {
        $p   = $e['progreso'][(string)$n];
        $ok  = count(array_filter($p['temas']));
        $tot = count($s['temas']);
        $okTemas  += $ok;
        $asistidas += $p['asistio'] ? 1 : 0;
        $porSesion[$n] = [
            'ok'      => $ok,
            'total'   => $tot,
            'pct'     => $tot ? (int)round($ok / $tot * 100) : 0,
            'asistio' => (bool)$p['asistio'],
            'completa'=> $ok === $tot,
        ];
    }

    $pctTemas = $totTemas ? (int)round($okTemas / $totTemas * 100) : 0;
    $consent  = (bool)($e['consentimiento']['aceptado'] ?? false);
    $quizOk   = (bool)($e['quiz']['aprobado'] ?? false);
    $intentos = count($e['quiz']['intentos'] ?? []);

    // Avance general: 10% acuerdo + 60% temario + 30% evaluación
    $general = (int)round(($consent ? 10 : 0) + $pctTemas * 0.6 + ($quizOk ? 30 : 0));

    return [
        'por_sesion'    => $porSesion,
        'temas_ok'      => $okTemas,
        'temas_total'   => $totTemas,
        'pct_temas'     => $pctTemas,
        'asistidas'     => $asistidas,
        'consentimiento'=> $consent,
        'quiz_aprobado' => $quizOk,
        'quiz_mejor'    => (int)($e['quiz']['mejor'] ?? 0),
        'quiz_intentos' => $intentos,
        'quiz_restantes'=> max(0, CAP_QUIZ_MAX_INT - $intentos),
        'general'       => min(100, $general),
        'habilitado'    => $consent && $pctTemas === 100 && $quizOk,
        'puede_evaluar' => $consent && $asistidas >= count(cap_sesiones()) && $pctTemas === 100,
    ];
}

function cap_estado_texto(array $m): array {
    if (!$m['consentimiento'])      return ['Acuerdo pendiente', 'warn'];
    if ($m['quiz_aprobado'])        return ['Certificado', 'ok'];
    if ($m['pct_temas'] === 100)    return ['Listo para evaluación', 'info'];
    if ($m['pct_temas'] > 0)        return ['En formación', 'info'];
    return ['Sin iniciar', 'muted'];
}

/** Normaliza el registro de una clase si el temario cambió de tamaño o faltan campos nuevos. */
function cap_normalizar_sesion($ses, int $totalTemas): array {
    $ses    = is_array($ses) ? $ses : [];
    $cubiertos = is_array($ses['temas_cubiertos'] ?? null) ? $ses['temas_cubiertos'] : [];
    $cubiertos = array_slice(array_pad($cubiertos, $totalTemas, false), 0, $totalTemas);
    return [
        'dictada'         => (bool)($ses['dictada'] ?? false),
        'notas'           => (string)($ses['notas'] ?? ''),
        'cerrada_en'      => $ses['cerrada_en'] ?? null,
        'temas_cubiertos' => array_map('boolval', $cubiertos),
    ];
}

/**
 * Métricas de una sesión a nivel de grupo: asistencia, avance por tema y estado.
 */
function cap_metricas_sesion(array $data, int $n): array {
    $sesiones = cap_sesiones();
    if (!isset($sesiones[$n])) { return []; }

    $ses    = $sesiones[$n];
    $clave  = (string)$n;
    $temas  = $ses['temas'];
    $total  = count($data['estudiantes']);

    $asistieron  = 0;
    $porTema     = array_fill(0, count($temas), 0);
    $marcasTotal = 0;

    foreach ($data['estudiantes'] as $e) {
        $e = cap_normalizar_estudiante($e);
        $p = $e['progreso'][$clave];
        if (!empty($p['asistio'])) { $asistieron++; }
        foreach ($p['temas'] as $i => $ok) {
            if ($ok) { $porTema[$i]++; $marcasTotal++; }
        }
    }

    $posibles = $total * count($temas);
    $estado   = cap_normalizar_sesion($data['sesiones'][$clave] ?? null, count($temas));

    $hoy      = date('Y-m-d');
    $esFutura = $ses['fecha'] > $hoy;
    $esHoy    = $ses['fecha'] === $hoy;

    return [
        'n'            => $n,
        'titulo'       => $ses['titulo'],
        'fecha'        => $ses['fecha'],
        'resumen'      => $ses['resumen'],
        'temas'        => $temas,
        'por_tema'     => $porTema,
        'asistieron'   => $asistieron,
        'total'        => $total,
        'pct_grupo'    => $posibles ? (int)round($marcasTotal / $posibles * 100) : 0,
        'pct_asistencia' => $total ? (int)round($asistieron / $total * 100) : 0,
        'dictada'         => $estado['dictada'],
        'notas'           => $estado['notas'],
        'cerrada_en'      => $estado['cerrada_en'],
        'temas_cubiertos' => $estado['temas_cubiertos'],
        'es_futura'       => $esFutura,
        'es_hoy'          => $esHoy,
    ];
}

/** Etiqueta de estado de una clase. */
function cap_estado_sesion(array $ms): array {
    if ($ms['dictada'])   return ['Dictada', 'ok'];
    if ($ms['es_hoy'])    return ['Hoy', 'accent'];
    if ($ms['es_futura']) return ['Programada', 'muted'];
    return ['Pendiente de cierre', 'warn'];
}
