<?php
/**
 * Piezas de interfaz compartidas: head, nav, footer e iconografía SVG.
 * Sin emojis: toda la iconografía es SVG inline.
 */

require_once __DIR__ . '/auth.php';

function cap_icono(string $n, string $clase = 'ico'): string {
    $p = [
        'check'     => '<polyline points="20 6 9 17 4 12"/>',
        'check-c'   => '<circle cx="12" cy="12" r="9"/><polyline points="8.5 12 11 14.5 15.5 9.5"/>',
        'chevron'   => '<polyline points="6 9 12 15 18 9"/>',
        'arrow'     => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'lock'      => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'   => '<path d="M3 3l18 18"/><path d="M10.6 5.2A9.9 9.9 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C4 8.3 2 12 2 12s3.6 7 10 7c1.4 0 2.6-.3 3.7-.8"/><path d="M9.9 10a3 3 0 0 0 4.2 4.2"/>',
        'shield'    => '<path d="M12 3l7 3v5.5c0 4.3-2.9 8.2-7 9.5-4.1-1.3-7-5.2-7-9.5V6l7-3z"/><polyline points="9 12 11 14 15 10"/>',
        'user'      => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c0-3.8 3.4-6.6 7.5-6.6s7.5 2.8 7.5 6.6"/>',
        'users'     => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.5 2.9-6.1 6.5-6.1s6.5 2.6 6.5 6.1"/><path d="M16.5 5.2a3.2 3.2 0 0 1 0 6"/><path d="M18 13.9c2.1.6 3.5 2.3 3.5 4.4"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="16" y1="3" x2="16" y2="7"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/>',
        'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3h6v1"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="9" y1="14" x2="13" y2="14"/>',
        'copy'      => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
        'trash'     => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'refresh'   => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><polyline points="21 4 21 9 16 9"/>',
        'alert'     => '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'book'      => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v16H6.5A2.5 2.5 0 0 0 4 20.5z"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="14" y2="11"/>',
        'award'     => '<circle cx="12" cy="9" r="5.5"/><polyline points="8.2 13.4 7 22 12 19.2 17 22 15.8 13.4"/>',
        'database'  => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        'edit'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'chart'     => '<line x1="4" y1="20" x2="20" y2="20"/><rect x="6" y="11" width="3" height="6"/><rect x="11" y="7" width="3" height="10"/><rect x="16" y="13" width="3" height="4"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'external'  => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3.5 6.5 12 13 20.5 6.5"/>',
        'plus'      => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.2" y2="16.2"/>',
        'key'       => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M17 6l2 2"/><path d="M14.5 8.5l2 2"/>',
        'x'         => '<line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/>',
        'download'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'signature' => '<path d="M3 17c3.5 0 4-11 7-11s2.5 11 5.5 11c1.6 0 2.4-1.4 2.5-2.6"/><line x1="3" y1="21" x2="21" y2="21"/>',
    ];
    $d = $p[$n] ?? $p['info'];
    return '<svg class="' . h($clase) . '" viewBox="0 0 24 24" aria-hidden="true">' . $d . '</svg>';
}

function cap_iniciales(string $nombre): string {
    $partes = preg_split('/\s+/u', trim($nombre));
    $ini = '';
    foreach ($partes as $p) {
        if ($p === '') continue;
        $ini .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8');
        if (mb_strlen($ini, 'UTF-8') >= 2) break;
    }
    return $ini !== '' ? $ini : '?';
}

function cap_head(string $titulo, string $desc = ''): void {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($titulo) ?> · Capacitación CMS · QUANTUN Digital</title>
<meta name="description" content="<?= h($desc ?: 'Programa de capacitación y habilitación para la gestión del CMS de ' . CAP_CLIENTE . '.') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/cap.css?v=<?= CAP_VERSION ?>">
</head>
<body data-csrf="<?= h(cap_csrf()) ?>"><?php
}

function cap_nav(string $contexto = '', array $links = [], ?array $usuario = null, string $salir = ''): void {
    ?>
<header class="nav">
  <div class="nav__inner">
    <div class="nav__brand">
      <a href="../index.html" aria-label="QUANTUN Digital"><img src="../assets/quantun-logo.png" alt="QUANTUN Digital"></a>
      <?php if ($contexto): ?>
        <span class="nav__div" aria-hidden="true"></span>
        <span class="nav__ctx"><?= h($contexto) ?></span>
      <?php endif; ?>
    </div>
    <?php if ($links): ?>
    <nav class="nav__links">
      <?php foreach ($links as $txt => $href): ?>
        <a href="<?= h($href) ?>"><?= h($txt) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <div class="nav__side">
      <?php if ($usuario): ?>
        <span class="nav__user">
          <span class="avatar avatar--soft"><?= h(cap_iniciales($usuario['nombre'])) ?></span>
          <span class="nav__user-n"><?= h($usuario['nombre']) ?></span>
        </span>
        <a class="btn btn--ghost btn--xs" href="<?= h($salir ?: 'index.php?salir=1') ?>"><?= cap_icono('logout', 'ico ico--sm') ?> Salir</a>
      <?php else: ?>
        <a class="btn btn--ghost btn--xs" href="index.php?v=acceso"><?= cap_icono('lock', 'ico ico--sm') ?> Acceder</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<?php
}

function cap_footer(): void {
    ?>
<footer class="foot">
  <div class="foot__inner">
    <div class="foot__l">
      <img src="../assets/quantun-logo.png" alt="QUANTUN Digital">
      <span class="foot__t">Programa de capacitación CMS · <?= h(CAP_CLIENTE) ?> · <?= date('Y') ?></span>
    </div>
    <nav class="foot__links">
      <a href="<?= h(CAP_CLIENTE_URL) ?>" target="_blank" rel="noopener">Sitio del cliente</a>
      <a href="../index.html">QUANTUN Digital</a>
      <a href="index.php?v=acceso">Acceso estudiantes</a>
    </nav>
  </div>
</footer>
<script src="assets/cap.js?v=<?= CAP_VERSION ?>"></script>
</body>
</html>
<?php
}
