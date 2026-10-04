<?php

declare(strict_types=1);

use AdminTools3\Model\Note;
use AdminTools3\Model\Permission;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;
use xPDO\xPDO;

if (PHP_SAPI !== 'cli' || empty($argv[1]) || !is_file(rtrim($argv[1], '/\\') . '/config.core.php')) {
    fwrite(STDERR, "Usage: php build/tests/uninstall-integration.php /path/to/modx-site\n");
    exit(1);
}

require_once rtrim($argv[1], '/\\') . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$site = new modX();
$prefix = 'at3qa_' . bin2hex(random_bytes(4)) . '_';
$qa = new modX('', [xPDO::OPT_TABLE_PREFIX => $prefix]);
if ($qa->getOption(xPDO::OPT_TABLE_PREFIX) !== $prefix) {
    throw new RuntimeException('Temporary table prefix was not applied.');
}
$modx = $qa;
require MODX_CORE_PATH . 'components/admintools3/bootstrap.php';

$classes = [modUserProfile::class, modSystemSetting::class, Note::class, Permission::class];
$testTables = [];
foreach ($classes as $class) {
    $source = $site->getTableName($class);
    $target = $qa->getTableName($class);
    if (!$source || !$target || $source === $target || !str_contains($target, $prefix)) {
        throw new RuntimeException('Invalid test table name for ' . $class);
    }
    $testTables[$class] = [$source, $target];
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$exists = static function (modX $modx, string $table): bool {
    return $modx->query('SELECT 1 FROM ' . $table . ' LIMIT 1') !== false;
};
$resolver = dirname(__DIR__) . '/resolvers/uninstall.php';
$transport = (object) ['xpdo' => $qa];

try {
    foreach ($testTables as [$source, $target]) {
        if ($qa->exec('CREATE TABLE ' . $target . ' LIKE ' . $source) === false) {
            throw new RuntimeException('Could not create temporary table ' . $target);
        }
    }

    $profile = $qa->newObject(modUserProfile::class);
    $profile->set('internalKey', 999999);
    $profile->set('extended', [
        'adminTools3States' => ['show' => true],
        'adminTools3Elements' => ['templates' => [1]],
        'adminTools3SystemSettings' => ['namespace' => 'admintools3'],
        'adminToolsStates' => ['legacy' => true],
        'unrelated' => 'keep',
    ]);
    $assert($profile->save(), 'Could not create test profile.');

    foreach (['admintools3_qa', 'admintools3_migration_version', 'admintools3_theme', 'admintools_qa'] as $key) {
        $setting = $qa->newObject(modSystemSetting::class);
        $setting->set('key', $key);
        $setting->set('value', 'test');
        $assert($setting->save(), 'Could not create test setting ' . $key);
    }

    foreach ([xPDOTransport::ACTION_UPGRADE, xPDOTransport::ACTION_UNINSTALL, xPDOTransport::ACTION_UNINSTALL] as $index => $action) {
        $mode = [xPDOTransport::REMOVE_PREEXISTING, xPDOTransport::PRESERVE_PREEXISTING, xPDOTransport::RESTORE_PREEXISTING][$index];
        $options = [xPDOTransport::PACKAGE_ACTION => $action, xPDOTransport::PREEXISTING_MODE => $mode];
        $assert((include $resolver) === true, 'Resolver failed for retained-data mode ' . $index);
        foreach ($testTables as [, $target]) {
            $assert($exists($qa, $target), 'Retained-data mode removed ' . $target);
        }
        $assert($qa->getObject(modSystemSetting::class, 'admintools3_qa') !== null, 'Retained-data mode removed a setting.');
        $extended = $qa->getObject(modUserProfile::class, ['internalKey' => 999999])->get('extended');
        $assert(isset($extended['adminTools3States']), 'Retained-data mode removed profile preferences.');
    }

    $options = [
        xPDOTransport::PACKAGE_ACTION => xPDOTransport::ACTION_UNINSTALL,
        xPDOTransport::PREEXISTING_MODE => xPDOTransport::REMOVE_PREEXISTING,
    ];
    $assert((include $resolver) === true, 'Resolver failed for full data removal.');
    foreach ([Note::class, Permission::class] as $class) {
        $assert(!$exists($qa, $testTables[$class][1]), 'AdminTools3 table remains: ' . $class);
        $assert($exists($site, $testTables[$class][0]), 'Site table was altered: ' . $class);
    }
    foreach (['admintools3_qa', 'admintools3_migration_version', 'admintools3_theme'] as $key) {
        $assert($qa->getObject(modSystemSetting::class, $key) === null, 'AdminTools3 setting remains: ' . $key);
    }
    $assert($qa->getObject(modSystemSetting::class, 'admintools_qa') !== null, 'Legacy setting was removed.');
    $extended = $qa->getObject(modUserProfile::class, ['internalKey' => 999999])->get('extended');
    foreach (['adminTools3States', 'adminTools3Elements', 'adminTools3SystemSettings'] as $key) {
        $assert(!array_key_exists($key, $extended), 'AdminTools3 profile key remains: ' . $key);
    }
    $assert(isset($extended['adminToolsStates'], $extended['unrelated']), 'Unrelated profile keys were removed.');

    fwrite(STDOUT, "AdminTools3 uninstall modes passed with temporary prefix {$prefix}\n");
} finally {
    foreach (array_reverse($testTables) as [, $target]) {
        if ($qa->exec('DROP TABLE IF EXISTS ' . $target) === false || $exists($qa, $target)) {
            throw new RuntimeException('Could not clean temporary test table ' . $target);
        }
    }
}
