<?php

use MODX\Revolution\modPlugin;
use xPDO\Transport\xPDOTransport;

$modx = $transport->xpdo;

if (($options[xPDOTransport::PACKAGE_ACTION] ?? null) === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

$existing = $modx->getObject(modPlugin::class, ['name' => 'AdminTools3']);
if ($existing) {
    $modx->setOption('admintools3_previous_plugin_disabled', (int) $existing->get('disabled'));
}

return true;
