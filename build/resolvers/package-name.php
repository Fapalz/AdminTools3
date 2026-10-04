<?php

use MODX\Revolution\Transport\modTransportPackage;
use xPDO\Transport\xPDOTransport;

if (($options[xPDOTransport::PACKAGE_ACTION] ?? null) === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

// MODX lowercases the transport signature, then derives the manager label from it.
// Keep the signature for upgrades and change only the display name of this package's versions.
foreach ($transport->xpdo->getIterator(modTransportPackage::class, [
    'signature:LIKE' => 'admintools3-%',
]) as $package) {
    if ($package->get('package_name') !== 'AdminTools3') {
        $package->set('package_name', 'AdminTools3');
        if (!$package->save()) {
            return false;
        }
    }
}

return true;
