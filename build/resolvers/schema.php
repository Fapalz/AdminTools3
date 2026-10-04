<?php

use AdminTools3\Model\Note;
use AdminTools3\Model\Permission;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

$modx = $transport->xpdo;

if (($options[xPDOTransport::PACKAGE_ACTION] ?? null) === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

if (PHP_VERSION_ID < 80100 || version_compare($modx->getVersionData()['full_version'] ?? '0', '3.0.0', '<')) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdminTools3] MODX 3 and PHP 8.1 or newer are required.');
    return false;
}

$corePath = rtrim($modx->getOption('admintools3_core_path', null, MODX_CORE_PATH . 'components/admintools3/'), '/\\') . '/';
require_once $corePath . 'bootstrap.php';
$manager = $modx->getManager();
foreach ([Note::class, Permission::class] as $class) {
    if (!$manager->createObjectContainer($class)) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[AdminTools3] Failed to create table for ' . $class);
        return false;
    }
}

return true;
