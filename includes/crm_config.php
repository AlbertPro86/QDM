<?php
/**
 * CRM QUANTUN Digital — Acceso centralizado a configuraciones del sistema
 *
 * Carga la tabla crm_configuraciones UNA sola vez por request (static cache).
 * Uso:  crmConfig('empresa_tel')            → valor guardado o '' si vacío
 *       crmConfig('empresa_nombre', 'MiEmpresa') → fallback si no está configurado
 *       crmWaNum('empresa_tel')             → solo dígitos, listo para wa.me/...
 */

/**
 * Devuelve el valor de una clave de configuración del CRM.
 */
function crmConfig(string $key, string $default = ''): string {
    static $cfg = null;

    if ($cfg === null) {
        try {
            $pdo  = db();
            $rows = $pdo->query("SELECT clave, valor FROM crm_configuraciones")
                        ->fetchAll(PDO::FETCH_KEY_PAIR);
            $cfg  = $rows ?: [];
        } catch (Exception $e) {
            $cfg = [];
        }
    }

    $val = $cfg[$key] ?? '';
    return ($val !== '' && $val !== null) ? (string)$val : $default;
}

/**
 * Devuelve el número de WhatsApp/teléfono solo con dígitos.
 * "+57 333 274 7801" → "573332747801"
 */
function crmWaNum(string $key, string $default = ''): string {
    $raw = crmConfig($key, $default);
    return preg_replace('/\D/', '', $raw);
}
