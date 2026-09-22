<?php
/**
 * Endpoints JSON del módulo de capacitación.
 * Todo POST, con CSRF y control de rol.
 */
require_once __DIR__ . '/lib/auth.php';

cap_sesion_iniciar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cap_json(['ok' => false, 'error' => 'Método no permitido.'], 405);
}
if (!cap_csrf_valido($_POST['csrf'] ?? null)) {
    cap_json(['ok' => false, 'error' => 'La sesión expiró. Recarga la página.'], 419);
}

$accion   = (string)($_POST['accion'] ?? '');
$esAdmin  = cap_es_admin();
$yoId     = cap_estudiante_id();
$sesiones = cap_sesiones();

/* =========================================================
   ACCIONES DEL ESTUDIANTE
   ========================================================= */

if ($accion === 'marcar_tema') {
    if (!$yoId) { cap_json(['ok' => false, 'error' => 'Debes iniciar sesión.'], 401); }
    $sesion = (string)(int)($_POST['sesion'] ?? 0);
    $idx    = (int)($_POST['indice'] ?? -1);
    $valor  = (int)($_POST['valor'] ?? 0) === 1;

    if (!isset($sesiones[(int)$sesion])) { cap_json(['ok' => false, 'error' => 'Clase inexistente.'], 400); }
    if ($idx < 0 || $idx >= count($sesiones[(int)$sesion]['temas'])) { cap_json(['ok' => false, 'error' => 'Tema inexistente.'], 400); }

    $e = cap_transaccion(function (array &$d) use ($yoId, $sesion, $idx, $valor) {
        $i = cap_indice_estudiante($d, $yoId);
        if ($i === null) { return null; }
        $est = cap_normalizar_estudiante($d['estudiantes'][$i]);
        if (empty($est['consentimiento']['aceptado'])) { return 'sin_acuerdo'; }
        $est['progreso'][$sesion]['temas'][$idx] = $valor;
        $est['progreso'][$sesion]['actualizado'] = date('c');
        $d['estudiantes'][$i] = $est;
        cap_bitacora($d, cap_nombre_completo($est), 'tema', 'Clase ' . $sesion . ' · tema ' . ($idx + 1) . ($valor ? ' marcado' : ' desmarcado'));
        return $est;
    });

    if ($e === null)          { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    if ($e === 'sin_acuerdo') { cap_json(['ok' => false, 'error' => 'Primero debes firmar el acuerdo de uso.'], 403); }

    cap_json(['ok' => true, 'metricas' => cap_metricas($e)]);
}

if ($accion === 'firmar_acuerdo') {
    if (!$yoId) { cap_json(['ok' => false, 'error' => 'Debes iniciar sesión.'], 401); }
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    if (mb_strlen($nombre, 'UTF-8') < 5) {
        cap_json(['ok' => false, 'error' => 'Escribe tu nombre completo para firmar.'], 400);
    }

    $r = cap_transaccion(function (array &$d) use ($yoId, $nombre) {
        $i = cap_indice_estudiante($d, $yoId);
        if ($i === null) { return null; }
        if (!empty($d['estudiantes'][$i]['consentimiento']['aceptado'])) { return 'ya'; }
        $d['estudiantes'][$i]['consentimiento'] = [
            'aceptado'  => true,
            'fecha'     => date('c'),
            'ip'        => cap_ip(),
            'version'   => CAP_CONSENT_VER,
            'firma'     => $nombre,
            'clausulas' => array_keys(cap_consentimiento()),
        ];
        cap_bitacora($d, cap_nombre_completo($d['estudiantes'][$i]), 'acuerdo', 'Acuerdo de uso firmado (v' . CAP_CONSENT_VER . ')');
        return true;
    });

    if ($r === null) { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    if ($r === 'ya') { cap_json(['ok' => false, 'error' => 'El acuerdo ya estaba firmado.'], 409); }
    cap_json(['ok' => true, 'mensaje' => 'Acuerdo firmado.']);
}

if ($accion === 'enviar_quiz') {
    if (!$yoId) { cap_json(['ok' => false, 'error' => 'Debes iniciar sesión.'], 401); }

    $quiz = cap_quiz();
    $resp = [];
    foreach ($quiz as $i => $q) {
        if (!isset($_POST['r' . $i])) {
            cap_json(['ok' => false, 'error' => 'Responde todas las preguntas antes de enviar.'], 400);
        }
        $resp[$i] = (int)$_POST['r' . $i];
    }

    $r = cap_transaccion(function (array &$d) use ($yoId, $quiz, $resp) {
        $i = cap_indice_estudiante($d, $yoId);
        if ($i === null) { return null; }
        $est = cap_normalizar_estudiante($d['estudiantes'][$i]);
        $m   = cap_metricas($est);
        if (!$m['puede_evaluar'])                     { return 'bloqueado'; }
        if ($m['quiz_aprobado'])                      { return 'aprobado'; }
        if ($m['quiz_intentos'] >= CAP_QUIZ_MAX_INT)  { return 'sin_intentos'; }

        $puntaje = 0;
        $detalle = [];
        foreach ($quiz as $k => $q) {
            $ok = ($resp[$k] === (int)$q['c']);
            if ($ok) { $puntaje++; }
            $detalle[] = ['ok' => $ok, 'ref' => $q['ref']];
        }
        $aprobado = $puntaje >= CAP_QUIZ_MINIMO;

        $est['quiz']['intentos'][] = [
            'fecha'     => date('c'),
            'puntaje'   => $puntaje,
            'aprobado'  => $aprobado,
            'respuestas'=> array_values($resp),
            'detalle'   => $detalle,
            'ip'        => cap_ip(),
        ];
        $est['quiz']['mejor']    = max((int)$est['quiz']['mejor'], $puntaje);
        $est['quiz']['aprobado'] = $est['quiz']['aprobado'] || $aprobado;

        $d['estudiantes'][$i] = $est;
        cap_bitacora($d, cap_nombre_completo($est), 'evaluacion', 'Intento ' . count($est['quiz']['intentos']) . ' · ' . $puntaje . '/5 · ' . ($aprobado ? 'aprobado' : 'no aprobado'));

        return ['puntaje' => $puntaje, 'aprobado' => $aprobado, 'detalle' => $detalle];
    });

    if ($r === null)            { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    if ($r === 'bloqueado')     { cap_json(['ok' => false, 'error' => 'Aún no cumples los requisitos para presentar la evaluación.'], 403); }
    if ($r === 'aprobado')      { cap_json(['ok' => false, 'error' => 'Ya aprobaste la evaluación.'], 409); }
    if ($r === 'sin_intentos')  { cap_json(['ok' => false, 'error' => 'Agotaste los intentos disponibles.'], 403); }

    cap_json(['ok' => true, 'resultado' => $r]);
}

/* =========================================================
   ACCIONES DEL ADMINISTRADOR
   ========================================================= */

if (!$esAdmin) {
    cap_json(['ok' => false, 'error' => 'Acción no permitida.'], 403);
}

if ($accion === 'crear_estudiante') {
    $nombre    = trim((string)($_POST['nombre'] ?? ''));
    $apellidos = trim((string)($_POST['apellidos'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $cargo     = trim((string)($_POST['cargo'] ?? ''));
    $clave     = (string)($_POST['clave'] ?? '');

    if (mb_strlen($nombre, 'UTF-8') < 2)              { cap_json(['ok' => false, 'error' => 'El nombre es obligatorio.'], 400); }
    if (mb_strlen($apellidos, 'UTF-8') < 2)           { cap_json(['ok' => false, 'error' => 'Los apellidos son obligatorios.'], 400); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   { cap_json(['ok' => false, 'error' => 'El correo no es válido.'], 400); }
    if ($cargo === '')                                { cap_json(['ok' => false, 'error' => 'Selecciona un cargo.'], 400); }
    if (mb_strlen($clave, 'UTF-8') < CAP_CLAVE_MIN)   { cap_json(['ok' => false, 'error' => 'La contraseña debe tener al menos ' . CAP_CLAVE_MIN . ' caracteres.'], 400); }

    $r = cap_transaccion(function (array &$d) use ($nombre, $apellidos, $email, $cargo, $clave) {
        foreach ($d['estudiantes'] as $e) {
            if (strtolower($e['email']) === strtolower($email)) { return 'duplicado'; }
        }
        $nuevo = cap_estudiante_nuevo($nombre, $apellidos, $email, $cargo, $clave);
        $d['estudiantes'][] = $nuevo;
        cap_bitacora($d, cap_admin_nombre(), 'alta', 'Estudiante ' . cap_nombre_completo($nuevo) . ' (' . $email . ')');
        return $nuevo;
    });

    if ($r === 'duplicado') { cap_json(['ok' => false, 'error' => 'Ya existe un estudiante con ese correo.'], 409); }

    cap_json([
        'ok' => true,
        'estudiante' => [
            'id'     => $r['id'],
            'nombre' => cap_nombre_completo($r),
            'email'  => $r['email'],
        ],
        'mensaje' => 'Estudiante agregado.',
    ]);
}

if ($accion === 'clave_sugerida') {
    cap_json(['ok' => true, 'clave' => cap_clave_sugerida()]);
}

/* --- Acciones sobre las clases del programa (no requieren estudiante) --- */

if ($accion === 'marcar_sesion') {
    $sesion = (string)(int)($_POST['sesion'] ?? 0);
    $valor  = (int)($_POST['valor'] ?? 0) === 1;
    if (!isset($sesiones[(int)$sesion])) { cap_json(['ok' => false, 'error' => 'Clase inexistente.'], 400); }

    cap_transaccion(function (array &$d) use ($sesion, $valor, $sesiones) {
        $d['sesiones'][$sesion]['dictada']    = $valor;
        $d['sesiones'][$sesion]['cerrada_en'] = $valor ? date('c') : null;
        cap_bitacora($d, cap_admin_nombre(), 'clase',
            'Clase ' . $sesion . ' (' . $sesiones[(int)$sesion]['titulo'] . ') ' . ($valor ? 'marcada como dictada' : 'reabierta'));
    });

    cap_json(['ok' => true, 'mensaje' => $valor ? 'Clase marcada como dictada.' : 'Clase reabierta.']);
}

if ($accion === 'guardar_sesion') {
    $sesion = (string)(int)($_POST['sesion'] ?? 0);
    if (!isset($sesiones[(int)$sesion])) { cap_json(['ok' => false, 'error' => 'Clase inexistente.'], 400); }

    $total = count($sesiones[(int)$sesion]['temas']);
    $nota  = trim((string)($_POST['nota'] ?? ''));

    // 'temas' llega como lista de indices marcados: "0,2,3" (vacio = ninguno)
    $crudo   = trim((string)($_POST['temas'] ?? ''));
    $marcados = $crudo === '' ? [] : array_map('intval', explode(',', $crudo));
    $cubiertos = array_fill(0, $total, false);
    foreach ($marcados as $i) {
        if ($i >= 0 && $i < $total) { $cubiertos[$i] = true; }
    }

    $resumen = cap_transaccion(function (array &$d) use ($sesion, $nota, $cubiertos, $total) {
        $antes = cap_normalizar_sesion($d['sesiones'][$sesion] ?? null, $total);

        $ses = $antes;
        $ses['notas']           = mb_substr($nota, 0, 1000, 'UTF-8');
        $ses['temas_cubiertos'] = $cubiertos;
        $d['sesiones'][$sesion] = $ses;

        $cambioTemas = $antes['temas_cubiertos'] !== $cubiertos;
        $cambioNota  = $antes['notas'] !== $ses['notas'];

        if ($cambioTemas || $cambioNota) {
            $partes = [];
            if ($cambioTemas) { $partes[] = count(array_filter($cubiertos)) . ' de ' . $total . ' temas marcados como dados'; }
            if ($cambioNota)  { $partes[] = 'notas actualizadas'; }
            cap_bitacora($d, cap_admin_nombre(), 'clase', 'Clase ' . $sesion . ' · ' . implode(' · ', $partes));
        }

        return ['cubiertos' => count(array_filter($cubiertos)), 'total' => $total];
    });

    cap_json([
        'ok'      => true,
        'mensaje' => 'Cambios guardados en la clase ' . $sesion . '.',
        'hora'    => date('H:i'),
        'resumen' => $resumen,
    ]);
}

if ($accion === 'reiniciar_sesion') {
    $sesion = (string)(int)($_POST['sesion'] ?? 0);
    if (!isset($sesiones[(int)$sesion])) { cap_json(['ok' => false, 'error' => 'Clase inexistente.'], 400); }

    $total = count($sesiones[(int)$sesion]['temas']);

    cap_transaccion(function (array &$d) use ($sesion, $total, $sesiones) {
        $d['sesiones'][$sesion] = [
            'dictada'         => false,
            'notas'           => '',
            'cerrada_en'      => null,
            'temas_cubiertos' => array_fill(0, $total, false),
        ];
        cap_bitacora($d, cap_admin_nombre(), 'clase',
            'Clase ' . $sesion . ' (' . $sesiones[(int)$sesion]['titulo'] . ') reiniciada: temas, notas y cierre borrados');
    });

    cap_json(['ok' => true, 'mensaje' => 'Clase ' . $sesion . ' reiniciada.']);
}

$id = (string)($_POST['id'] ?? '');
if ($id === '') { cap_json(['ok' => false, 'error' => 'Falta el identificador del estudiante.'], 400); }

if ($accion === 'eliminar_estudiante') {
    $r = cap_transaccion(function (array &$d) use ($id) {
        $i = cap_indice_estudiante($d, $id);
        if ($i === null) { return null; }
        $nom = cap_nombre_completo($d['estudiantes'][$i]);
        array_splice($d['estudiantes'], $i, 1);
        cap_bitacora($d, cap_admin_nombre(), 'baja', 'Estudiante eliminado: ' . $nom);
        return true;
    });
    if (!$r) { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    cap_json(['ok' => true, 'mensaje' => 'Estudiante eliminado.']);
}

if ($accion === 'cambiar_clave') {
    $clave = (string)($_POST['clave'] ?? '');
    if (mb_strlen($clave, 'UTF-8') < CAP_CLAVE_MIN) {
        cap_json(['ok' => false, 'error' => 'La contraseña debe tener al menos ' . CAP_CLAVE_MIN . ' caracteres.'], 400);
    }

    $r = cap_transaccion(function (array &$d) use ($id, $clave) {
        $i = cap_indice_estudiante($d, $id);
        if ($i === null) { return null; }
        $d['estudiantes'][$i]['clave_hash'] = password_hash($clave, PASSWORD_DEFAULT);
        cap_bitacora($d, cap_admin_nombre(), 'clave',
            'Contraseña cambiada para ' . cap_nombre_completo($d['estudiantes'][$i]));
        return cap_nombre_completo($d['estudiantes'][$i]);
    });

    if (!$r) { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    cap_json(['ok' => true, 'mensaje' => 'Contraseña actualizada para ' . $r . '.']);
}

if ($accion === 'toggle_activo') {
    $r = cap_transaccion(function (array &$d) use ($id) {
        $i = cap_indice_estudiante($d, $id);
        if ($i === null) { return null; }
        $d['estudiantes'][$i]['activo'] = empty($d['estudiantes'][$i]['activo']);
        cap_bitacora($d, cap_admin_nombre(), 'estado', cap_nombre_completo($d['estudiantes'][$i]) . ' · ' . ($d['estudiantes'][$i]['activo'] ? 'activado' : 'suspendido'));
        return $d['estudiantes'][$i]['activo'];
    });
    if ($r === null) { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    cap_json(['ok' => true, 'activo' => $r, 'mensaje' => $r ? 'Estudiante activado.' : 'Estudiante suspendido.']);
}

if ($accion === 'reiniciar_quiz') {
    $r = cap_transaccion(function (array &$d) use ($id) {
        $i = cap_indice_estudiante($d, $id);
        if ($i === null) { return null; }
        $d['estudiantes'][$i]['quiz'] = ['aprobado' => false, 'mejor' => 0, 'intentos' => []];
        cap_bitacora($d, cap_admin_nombre(), 'evaluacion', 'Evaluación reiniciada para ' . cap_nombre_completo($d['estudiantes'][$i]));
        return true;
    });
    if (!$r) { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    cap_json(['ok' => true, 'mensaje' => 'Evaluación reiniciada.']);
}

if ($accion === 'marcar_asistencia') {
    $sesion = (string)(int)($_POST['sesion'] ?? 0);
    $valor  = (int)($_POST['valor'] ?? 0) === 1;
    if (!isset($sesiones[(int)$sesion])) { cap_json(['ok' => false, 'error' => 'Clase inexistente.'], 400); }

    $r = cap_transaccion(function (array &$d) use ($id, $sesion, $valor) {
        $i = cap_indice_estudiante($d, $id);
        if ($i === null) { return null; }
        $est = cap_normalizar_estudiante($d['estudiantes'][$i]);
        $est['progreso'][$sesion]['asistio'] = $valor;
        $d['estudiantes'][$i] = $est;
        cap_bitacora($d, cap_admin_nombre(), 'asistencia', cap_nombre_completo($est) . ' · clase ' . $sesion . ($valor ? ' presente' : ' sin asistencia'));
        return true;
    });
    if (!$r) { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    cap_json(['ok' => true, 'mensaje' => 'Asistencia actualizada.']);
}

if ($accion === 'registrar_accesos') {
    $rol  = trim((string)($_POST['rol'] ?? ''));
    $nota = trim((string)($_POST['nota'] ?? ''));
    if ($rol === '') { cap_json(['ok' => false, 'error' => 'Indica el rol asignado en WordPress.'], 400); }

    $r = cap_transaccion(function (array &$d) use ($id, $rol, $nota) {
        $i = cap_indice_estudiante($d, $id);
        if ($i === null) { return null; }
        $est = cap_normalizar_estudiante($d['estudiantes'][$i]);
        $m   = cap_metricas($est);
        if (!$m['habilitado']) { return 'no_habilitado'; }
        $est['accesos'] = ['entregados' => true, 'fecha' => date('c'), 'rol' => $rol, 'nota' => mb_substr($nota, 0, 600, 'UTF-8')];
        $d['estudiantes'][$i] = $est;
        cap_bitacora($d, cap_admin_nombre(), 'accesos', cap_nombre_completo($est) . ' · rol ' . $rol);
        return true;
    });
    if ($r === null)             { cap_json(['ok' => false, 'error' => 'Estudiante no encontrado.'], 404); }
    if ($r === 'no_habilitado')  { cap_json(['ok' => false, 'error' => 'El estudiante aún no cumple los requisitos (acuerdo, temario y evaluación).'], 403); }
    cap_json(['ok' => true, 'mensaje' => 'Accesos registrados.']);
}

cap_json(['ok' => false, 'error' => 'Acción desconocida.'], 400);
