<?php

use AdminTools3\Model\Note;
use AdminTools3\Model\Permission;
use MODX\Revolution\modContext;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

$modx = $transport->xpdo;

if (($options[xPDOTransport::PACKAGE_ACTION] ?? null) === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

$corePath = rtrim($modx->getOption('admintools3_core_path', null, MODX_CORE_PATH . 'components/admintools3/'), '/\\') . '/';
require_once $corePath . 'bootstrap.php';

try {
    $marker = $modx->getObject(modSystemSetting::class, 'admintools3_migration_version');
    if (!$marker) {
        throw new RuntimeException('Migration marker setting is missing.');
    }

    $legacy = $modx->getObject(modNamespace::class, 'admintools');
    if ($legacy && (int) $marker->get('value') < 1) {
        foreach (require $corePath . 'config/settings.php' as $key => $definition) {
            if ($key === 'admintools3_migration_version') {
                continue;
            }
            $oldKey = preg_replace('/^admintools3_/', 'admintools_', $key);
            $old = $modx->getObject(modSystemSetting::class, $oldKey);
            $new = $modx->getObject(modSystemSetting::class, $key);
            if (!$old || !$new || (string) $new->get('value') !== (string) $definition['value']) {
                continue;
            }
            $value = (string) $old->get('value');
            if (in_array($key, ['admintools3_custom_css', 'admintools3_custom_js'], true)) {
                $value = str_replace('{adminTools', '{adminTools3', $value);
            }
            $new->set('value', $value);
            if (!$new->save()) {
                throw new RuntimeException('Could not migrate setting ' . $oldKey);
            }
        }

        $profileKeys = [
            'adminToolsStates' => 'adminTools3States',
            'adminToolsElements' => 'adminTools3Elements',
            'systemSettings' => 'adminTools3SystemSettings',
        ];
        foreach ($modx->getIterator(modUserProfile::class) as $profile) {
            $extended = $profile->get('extended');
            if (!is_array($extended)) {
                continue;
            }
            $changed = false;
            foreach ($profileKeys as $oldKey => $newKey) {
                if (array_key_exists($oldKey, $extended) && !array_key_exists($newKey, $extended)) {
                    $extended[$newKey] = $extended[$oldKey];
                    if ($oldKey === 'systemSettings' && ($extended[$newKey]['namespace'] ?? '') === 'admintools') {
                        $extended[$newKey]['namespace'] = 'admintools3';
                    }
                    $changed = true;
                }
            }
            if ($changed) {
                $profile->set('extended', $extended);
                if (!$profile->save()) {
                    throw new RuntimeException('Could not migrate user profile ' . $profile->get('internalKey'));
                }
            }
        }

        $prefix = $modx->getOption('table_prefix');
        foreach ([
            'admintools_notes' => Note::class,
            'admintools_permissions' => Permission::class,
        ] as $oldTable => $class) {
            $table = str_replace('`', '``', $prefix . $oldTable);
            $tableExists = $modx->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :name');
            if (!$tableExists || !$tableExists->execute(['name' => $prefix . $oldTable])) {
                throw new RuntimeException('Could not inspect legacy table ' . $oldTable);
            }
            if (!$tableExists->fetchColumn()) {
                continue;
            }
            $rows = $modx->query('SELECT * FROM `' . $table . '`');
            if (!$rows) {
                throw new RuntimeException('Could not read legacy table ' . $oldTable);
            }
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                if ($modx->getObject($class, (int) $row['id'])) {
                    continue;
                }
                $record = $modx->newObject($class);
                $record->fromArray($row, '', true);
                if (!$record->save()) {
                    throw new RuntimeException('Could not migrate row ' . $row['id'] . ' from ' . $oldTable);
                }
            }
        }

    }

    $newPlugin = $modx->getObject(modPlugin::class, ['name' => 'AdminTools3']);
    if (!$newPlugin) {
        throw new RuntimeException('AdminTools3 plugin is missing.');
    }
    $emptyEvent = $modx->getObject(modPluginEvent::class, [
        'pluginid' => $newPlugin->get('id'),
        'event' => '',
    ]);
    if ($emptyEvent && !$emptyEvent->remove()) {
        throw new RuntimeException('Could not remove invalid AdminTools3 plugin event.');
    }
    $previousDisabled = $modx->getOption('admintools3_previous_plugin_disabled', null, null);
    if ($previousDisabled !== null) {
        $newPlugin->set('disabled', (int) $previousDisabled);
        if (!$newPlugin->save()) {
            throw new RuntimeException('Could not restore AdminTools3 plugin state.');
        }
    } elseif ($newPlugin->get('disabled')) {
        $oldPlugin = $modx->getObject(modPlugin::class, ['name' => 'AdminTools']);
        $oldPluginWasActive = $oldPlugin && !$oldPlugin->get('disabled');
        if ($oldPluginWasActive) {
            $oldPlugin->set('disabled', 1);
            if (!$oldPlugin->save()) {
                throw new RuntimeException('Could not disable legacy plugin.');
            }
        }
        $newPlugin->set('disabled', 0);
        if (!$newPlugin->save()) {
            if ($oldPluginWasActive) {
                $oldPlugin->set('disabled', 0);
                $oldPlugin->save();
            }
            throw new RuntimeException('Could not activate AdminTools3 plugin.');
        }
    }

    if (!$newPlugin->get('disabled')) {
        $oldPlugin = $modx->getObject(modPlugin::class, ['name' => 'AdminTools']);
        if ($oldPlugin && !$oldPlugin->get('disabled')) {
            $oldPlugin->set('disabled', 1);
            if (!$oldPlugin->save()) {
                throw new RuntimeException('Could not disable conflicting legacy plugin.');
            }
        }
    }

    if ((int) $marker->get('value') < 1) {
        $marker->set('value', '1');
        if (!$marker->save()) {
            throw new RuntimeException('Could not save migration marker.');
        }
    }

    $contexts = [];
    foreach ($modx->getIterator(modContext::class) as $context) {
        $contexts[] = $context->get('key');
    }
    $modx->getCacheManager()->refresh([
        'system_settings' => [],
        'context_settings' => ['contexts' => $contexts],
        'db' => [],
    ]);
    return true;
} catch (Throwable $exception) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdminTools3] Migration failed: ' . $exception->getMessage());
    return false;
}
