<?php

/**
 * Register the AdminTools3 model independently of the MODX 2 package.
 *
 * @var \MODX\Revolution\modX $modx
 */

$corePath = $modx->getOption('admintools3_core_path', null, MODX_CORE_PATH . 'components/admintools3/');
$corePath = rtrim($corePath, '/\\') . '/';

spl_autoload_register(static function ($class) use ($corePath) {
    $prefix = 'AdminTools3\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $corePath . 'src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$modx->addPackage('AdminTools3\\Model', $corePath . 'src/', null, 'AdminTools3\\');
