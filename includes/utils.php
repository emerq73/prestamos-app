<?php
/**
 * Log de errores personalizado para el Router
 */
if (!function_exists('router_log')) {
    function router_log($msg)
    {
        $logFile = __DIR__ . '/../logs/router.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        ini_set('error_log', $logFile);
        error_log(date('Y-m-d H:i:s') . " [ROUTER] " . $msg);
    }
}
