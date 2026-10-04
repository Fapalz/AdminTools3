<?php

use AdminTools3\Model\Note;
use AdminTools3\Model\Permission;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

$modx = $transport->xpdo;

if (($options[xPDOTransport::PACKAGE_ACTION] ?? null) !== xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

// MODX's uninstall dialog offers Preserve (0), Remove (1), and Restore (2).
// Only the explicit Remove choice deletes data created or migrated by AdminTools3.
if ((int) ($options[xPDOTransport::PREEXISTING_MODE] ?? xPDOTransport::PRESERVE_PREEXISTING)
    !== xPDOTransport::REMOVE_PREEXISTING) {
    $modx->log(modX::LOG_LEVEL_INFO, '[AdminTools3] Keeping notes, permissions, settings, and profile preferences.');
    return true;
}

try {
    // The category vehicle is uninstalled before the namespace vehicle, so the
    // model files are still available at this point.
    $corePath = rtrim($modx->getOption('admintools3_core_path', null, MODX_CORE_PATH . 'components/admintools3/'), '/\\') . '/';
    require_once $corePath . 'bootstrap.php';
    $tables = [
        $modx->getTableName(Note::class),
        $modx->getTableName(Permission::class),
    ];
    if (in_array(null, $tables, true) || in_array('', $tables, true)) {
        throw new RuntimeException('Could not resolve AdminTools3 table names.');
    }

    $profileKeys = ['adminTools3States', 'adminTools3Elements', 'adminTools3SystemSettings'];
    foreach ($modx->getIterator(modUserProfile::class) as $profile) {
        $extended = $profile->get('extended');
        if (!is_array($extended)) {
            continue;
        }
        $originalCount = count($extended);
        foreach ($profileKeys as $key) {
            unset($extended[$key]);
        }
        if (count($extended) !== $originalCount) {
            $profile->set('extended', $extended);
            if (!$profile->save()) {
                throw new RuntimeException('Could not clean user profile ' . $profile->get('internalKey'));
            }
        }
    }

    foreach ($modx->getIterator(modSystemSetting::class, ['key:LIKE' => 'admintools3_%']) as $setting) {
        if (str_starts_with((string) $setting->get('key'), 'admintools3_') && !$setting->remove()) {
            throw new RuntimeException('Could not remove setting ' . $setting->get('key'));
        }
    }

    foreach ($tables as $table) {
        if ($modx->exec('DROP TABLE IF EXISTS ' . $table) === false) {
            throw new RuntimeException('Could not remove table ' . $table);
        }
    }

    $modx->log(modX::LOG_LEVEL_INFO, '[AdminTools3] Removed notes, permissions, settings, and profile preferences.');
    return true;
} catch (Throwable $exception) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdminTools3] Data removal failed: ' . $exception->getMessage());
    return false;
}
