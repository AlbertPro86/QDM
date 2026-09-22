<?php
/**
 * Capacitación CMS WordPress — taxi-taxi.com.co
 * Configuración: programa, temario, consentimiento y evaluación.
 */

const CAP_VERSION      = '1.0';
const CAP_CLIENTE      = 'Taxi Taxi';
const CAP_CLIENTE_URL  = 'https://taxi-taxi.com.co/';
const CAP_CONSENT_VER  = '1.0';
const CAP_QUIZ_MINIMO  = 4;   // respuestas correctas de 5 para aprobar
const CAP_QUIZ_MAX_INT = 3;   // intentos máximos
const CAP_CLAVE_MIN    = 8;   // longitud mínima de la contraseña del estudiante
const CAP_HORA_INICIO  = '4:00 p.m.';  // inicio de cada jornada
const CAP_HORA_FIN     = '6:00 p.m.';  // fin de cada jornada

const CAP_CARGOS = [
    'Administrador de contenido',
    'Editor de contenido',
    'Community manager',
    'Coordinador de operaciones',
    'Soporte / TI',
    'Dirección',
    'Otro',
];

/**
 * Programa: 5 clases repartidas en 2 jornadas de 2 horas (4:00 p.m. – 6:00 p.m.),
 * los próximos dos viernes. 4 horas de capacitación en total.
 *
 * Jornada 1 · viernes 25 sep 2026 · clases 1-3 (35 + 40 + 45 = 120 min)
 * Jornada 2 · viernes 2 oct 2026  · clases 4-5 (60 + 60 = 120 min)
 */
function cap_sesiones(): array {
    return [
        1 => [
            'titulo'       => 'Gestionar el panel',
            'fecha'        => '2026-09-25',
            'hora_inicio'  => '4:00 p.m.',
            'hora_fin'     => '4:35 p.m.',
            'resumen' => 'Reconocer el escritorio de WordPress, la estructura del menú y el alcance real de cada rol.',
            'temas'   => [
                'Ingreso al escritorio: URL, sesión y cierre seguro',
                'Estructura del menú: Entradas, Páginas, Medios, Apariencia',
                'Roles de usuario y qué puede hacer cada uno',
                'Barra de administración y perfil personal',
                'Qué se puede tocar y qué requiere aprobación previa',
            ],
        ],
        2 => [
            'titulo'       => 'Accesos y copias de seguridad',
            'fecha'        => '2026-09-25',
            'hora_inicio'  => '4:35 p.m.',
            'hora_fin'     => '5:15 p.m.',
            'resumen' => 'Administrar usuarios con mínimo privilegio y dominar el ciclo completo de respaldo.',
            'temas'   => [
                'Alta y baja de usuarios con principio de mínimo privilegio',
                'Contraseñas únicas, gestor de claves y doble factor (2FA)',
                'Copia de seguridad manual: cuándo y cómo generarla',
                'Copia programada: frecuencia, destino y retención',
                'Verificar y descargar la copia antes de cualquier cambio',
                'Restauración: procedimiento y autorización requerida',
            ],
        ],
        3 => [
            'titulo'       => 'Creación de contenido',
            'fecha'        => '2026-09-25',
            'hora_inicio'  => '5:15 p.m.',
            'hora_fin'     => '6:00 p.m.',
            'resumen' => 'Publicar contenido correcto, optimizado y consistente con la identidad del sitio.',
            'temas'   => [
                'Diferencia real entre Entradas y Páginas',
                'Editor de bloques: bloques base, patrones y plantillas',
                'Biblioteca de medios: nombres, peso, formato y texto alternativo',
                'SEO on-page: título, meta descripción, slug y enlaces internos',
                'Borradores, revisión, programación y publicación',
                'Respetar la línea gráfica: no alterar estilos globales',
            ],
        ],
        4 => [
            'titulo'       => 'Mantenimiento del CMS',
            'fecha'        => '2026-10-02',
            'hora_inicio'  => '4:00 p.m.',
            'hora_fin'     => '5:00 p.m.',
            'resumen' => 'Mantener el sitio actualizado y seguro sin romper lo que ya funciona.',
            'temas'   => [
                'Actualizaciones de núcleo, plugins y tema: orden correcto',
                'Backup previo obligatorio antes de actualizar',
                'Instalar, desactivar y eliminar plugins: criterios y riesgos',
                'Seguridad: firewall, protección de login, antispam y permisos',
                'Rendimiento: caché, compresión e imágenes optimizadas',
                'Enlaces rotos, errores 404 y redirecciones',
            ],
        ],
        5 => [
            'titulo'       => 'Mantenimiento e informes',
            'fecha'        => '2026-10-02',
            'hora_inicio'  => '5:00 p.m.',
            'hora_fin'     => '6:00 p.m.',
            'resumen' => 'Convertir el mantenimiento en una rutina medible y reportable.',
            'temas'   => [
                'Rutina semanal y rutina mensual de mantenimiento',
                'Google Analytics y Search Console: métricas que importan',
                'Estructura del informe mensual y evidencias',
                'Bitácora de cambios: quién, qué, cuándo y por qué',
                'Escalamiento de incidencias y canal de soporte',
                'Cierre: entrega de accesos definitivos',
            ],
        ],
    ];
}

/**
 * Agrupa las clases en jornadas (mismo día = misma jornada), en orden.
 * Devuelve [1 => ['fecha'=>'2026-09-25','clases'=>[1,2,3]], 2 => [...]]
 */
function cap_jornadas(): array {
    $jornadas = [];
    foreach (cap_sesiones() as $n => $s) {
        $fecha = $s['fecha'];
        if (!isset($jornadas[$fecha])) { $jornadas[$fecha] = ['fecha' => $fecha, 'clases' => []]; }
        $jornadas[$fecha]['clases'][] = $n;
    }
    return array_values($jornadas);
}

/** Número de jornada (1, 2...) a la que pertenece una clase. */
function cap_jornada_de(int $n): int {
    foreach (cap_jornadas() as $i => $j) {
        if (in_array($n, $j['clases'], true)) { return $i + 1; }
    }
    return 1;
}

/** Cláusulas del acuerdo de uso. El estudiante marca cada una. */
function cap_consentimiento(): array {
    return [
        'no_cambios'   => 'Entiendo que este es el CMS de un sitio en producción. No modificaré tema, plugins, menús, estructura ni configuración global sin consultar y obtener aprobación previa por escrito.',
        'backup'       => 'Antes de cualquier actualización o cambio estructural generaré y verificaré una copia de seguridad.',
        'credenciales' => 'Mis credenciales son personales e intransferibles. Usaré contraseña única y activaré doble factor. No comparto ni reutilizo accesos.',
        'privilegio'   => 'No solicitaré ni asignaré permisos superiores a los que mi cargo necesita, y no crearé usuarios sin autorización.',
        'confidencial' => 'La información del sitio, sus usuarios y sus datos es confidencial. No la extraigo, copio ni comparto fuera del alcance de mi trabajo.',
        'bitacora'     => 'Registraré en la bitácora todo cambio relevante que ejecute, con fecha y motivo.',
        'incidencias'  => 'Ante cualquier error, caída o comportamiento sospechoso reportaré de inmediato y no intentaré soluciones por mi cuenta.',
        'revocacion'   => 'Acepto que los accesos son revocables y que su uso queda registrado y auditado.',
    ];
}

/** Evaluación final: 5 preguntas, una correcta por pregunta. */
function cap_quiz(): array {
    return [
        [
            'p' => 'Te piden actualizar tres plugins y el tema del sitio. ¿Cuál es el primer paso?',
            'o' => [
                'Actualizar primero el tema y luego los plugins, de mayor a menor.',
                'Generar una copia de seguridad completa, verificarla y recién después actualizar.',
                'Actualizar todo a la vez desde la pantalla de actualizaciones para ahorrar tiempo.',
                'Desactivar todos los plugins y actualizarlos uno por uno sin respaldo.',
            ],
            'c'   => 1,
            'ref' => 'Clase 4 · Mantenimiento del CMS',
        ],
        [
            'p' => 'Un compañero pide cambiar la estructura del menú principal y el color de los botones. ¿Qué corresponde hacer?',
            'o' => [
                'Aplicarlo de una vez: son cambios pequeños y reversibles.',
                'Aplicarlo y avisar después por el grupo de WhatsApp.',
                'Consultar y esperar aprobación por escrito antes de tocar menús o estilos globales.',
                'Crear un usuario administrador temporal para que lo haga esa persona.',
            ],
            'c'   => 2,
            'ref' => 'Clase 1 · Gestionar el panel / Acuerdo de uso',
        ],
        [
            'p' => 'Entra una persona nueva que solo debe redactar y publicar artículos. ¿Qué rol se le asigna?',
            'o' => [
                'Administrador, para que no dependa de nadie.',
                'Editor o Autor, según necesite o no revisar contenido de terceros.',
                'Se comparte el usuario del administrador para no crear más cuentas.',
                'Suscriptor, y se le entrega además la clave del FTP.',
            ],
            'c'   => 1,
            'ref' => 'Clase 2 · Accesos y copias de seguridad',
        ],
        [
            'p' => '¿Cuál de estas prácticas de acceso es la correcta?',
            'o' => [
                'Usar la misma contraseña del correo para entrar más rápido.',
                'Dejar la sesión abierta en el equipo compartido de la oficina.',
                'Contraseña única guardada en gestor de claves y doble factor activo.',
                'Enviar la clave por chat para que todo el equipo la tenga a mano.',
            ],
            'c'   => 2,
            'ref' => 'Clase 2 · Accesos y copias de seguridad',
        ],
        [
            'p' => '¿Qué debe contener el informe mensual de gestión del sitio?',
            'o' => [
                'Solo el número de visitas del mes.',
                'Métricas de tráfico, tareas de mantenimiento ejecutadas, incidencias y bitácora de cambios.',
                'Un listado de las publicaciones y nada más.',
                'No se requiere informe si el sitio no presentó fallas.',
            ],
            'c'   => 1,
            'ref' => 'Clase 5 · Mantenimiento e informes',
        ],
    ];
}

function cap_total_temas(): int {
    $n = 0;
    foreach (cap_sesiones() as $s) { $n += count($s['temas']); }
    return $n;
}

function cap_fecha_larga(string $iso): string {
    $meses = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $t = strtotime($iso);
    return sprintf('viernes %d de %s de %d', (int)date('j', $t), $meses[(int)date('n', $t)], (int)date('Y', $t));
}

function cap_fecha_corta(string $iso): string {
    $meses = [1=>'ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $t = strtotime($iso);
    return (int)date('j', $t) . ' ' . $meses[(int)date('n', $t)];
}
