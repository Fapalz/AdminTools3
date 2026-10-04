<?php

declare(strict_types=1);

use MODX\Revolution\modChunk;
use MODX\Revolution\modCategory;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modPackageBuilder;
use xPDO\Transport\xPDOTransport;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$projectRoot = dirname(__DIR__);
$modxRoot = isset($argv[1]) ? rtrim($argv[1], '/\\') . '/' : null;
if (!$modxRoot || !is_file($modxRoot . 'config.core.php')) {
    fwrite(STDERR, "Usage: php build/build.php /path/to/modx-site\n");
    exit(1);
}

require_once $modxRoot . 'config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new modX();
if (!$modx->initialize('mgr')) {
    throw new RuntimeException('Could not initialize MODX.');
}
if (version_compare($modx->getVersionData()['full_version'] ?? '0', '3.0.0', '<')) {
    throw new RuntimeException('AdminTools3 requires MODX 3.');
}

$builder = new modPackageBuilder($modx);
$output = $projectRoot . '/packages/';
if (!is_dir($output) && !mkdir($output, 0775, true) && !is_dir($output)) {
    throw new RuntimeException('Could not create packages directory.');
}
$builder->directory = $output;
$builder->createPackage('admintools3', '1.0.0', 'pl');

$coreSource = $projectRoot . '/core/components/admintools3/';
$assetsSource = $projectRoot . '/assets/components/admintools3/';
$namespace = $modx->newObject(modNamespace::class);
$namespace->fromArray([
    'name' => 'admintools3',
    'path' => '{core_path}components/admintools3/',
    'assets_path' => '{assets_path}components/admintools3/',
], '', true);
$builder->namespace = $namespace;
$namespaceVehicle = $builder->createVehicle($namespace, [
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RESOLVE_FILES => true,
    xPDOTransport::RESOLVE_PHP => true,
]);
$namespaceVehicle->resolve('file', [
    'source' => $coreSource,
    'target' => "return MODX_CORE_PATH . 'components/';",
]);
$namespaceVehicle->resolve('file', [
    'source' => $assetsSource,
    'target' => "return MODX_ASSETS_PATH . 'components/';",
]);
$namespaceVehicle->resolve('php', [
    'source' => $projectRoot . '/build/resolvers/capture.php',
]);
$namespaceVehicle->resolve('php', [
    'source' => $projectRoot . '/build/resolvers/schema.php',
]);
if (!$builder->putVehicle($namespaceVehicle)) {
    throw new RuntimeException('Could not add namespace vehicle.');
}

foreach (require $coreSource . 'config/settings.php' as $definition) {
    $setting = $modx->newObject(modSystemSetting::class);
    $setting->fromArray($definition, '', true);
    $vehicle = $builder->createVehicle($setting, [
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
    ]);
    if (!$builder->putVehicle($vehicle)) {
        throw new RuntimeException('Could not add setting ' . $definition['key']);
    }
}

$plugin = $modx->newObject(modPlugin::class);
$plugin->fromArray([
    'name' => 'AdminTools3',
    'description' => 'Manager tools for MODX 3: favorites, notes, element history, and plugin controls',
    'plugincode' => <<<'PHP'
$corePath = $modx->getOption('admintools3_core_path', null, MODX_CORE_PATH . 'components/admintools3/');
return require rtrim($corePath, '/\\') . '/elements/plugins/plugin.admintools.php';
PHP,
    'disabled' => 1,
]);
foreach ([
    'OnMODXInit', 'OnManagerPageBeforeRender', 'OnManagerPageAfterRender',
    'OnManagerPageInit', 'OnManagerAuthentication', 'OnLoadWebDocument',
    'OnDocFormSave', 'OnDocFormPrerender', 'OnTempFormPrerender',
] as $eventName) {
    $event = $modx->newObject(modPluginEvent::class);
    $event->set('event', $eventName);
    $event->set('priority', 0);
    $plugin->addMany($event, 'PluginEvents');
}
$snippet = $modx->newObject(modSnippet::class);
$snippet->fromArray([
    'name' => 'adminLogin3',
    'description' => 'Manager email login for AdminTools3',
    'snippet' => <<<'PHP'
$corePath = $modx->getOption('admintools3_core_path', null, MODX_CORE_PATH . 'components/admintools3/');
return require rtrim($corePath, '/\\') . '/elements/snippets/snippet.adminlogin.php';
PHP,
]);
$chunks = [];
foreach ([
    'tpl.login.form3' => 'elements/chunks/chunk.login.form.tpl',
    'tpl.lockScreen3' => 'elements/chunks/chunk.lockscreen.tpl',
] as $name => $file) {
    $chunk = $modx->newObject(modChunk::class);
    $chunk->fromArray(['name' => $name, 'content' => file_get_contents($coreSource . $file)]);
    $chunks[] = $chunk;
}

$category = $modx->newObject(modCategory::class);
$category->set('category', 'AdminTools3');
$category->addMany($plugin, 'Plugins');
$category->addMany($snippet, 'Snippets');
$category->addMany($chunks, 'Chunks');
$categoryVehicle = $builder->createVehicle($category, [
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RESOLVE_PHP => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'Plugins' => [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                    // "event" is part of the composite primary key; xPDO drops it otherwise.
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                ],
            ],
        ],
        'Snippets' => [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
        ],
        'Chunks' => [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
        ],
    ],
]);
$categoryVehicle->resolve('php', [
    'source' => $projectRoot . '/build/resolvers/migrate.php',
]);
$categoryVehicle->resolve('php', [
    'source' => $projectRoot . '/build/resolvers/uninstall.php',
]);
$categoryVehicle->resolve('php', [
    'source' => $projectRoot . '/build/resolvers/package-name.php',
]);
if (!$builder->putVehicle($categoryVehicle)) {
    throw new RuntimeException('Could not add AdminTools3 category vehicle.');
}

$builder->setPackageAttributes([
    'license' => file_get_contents($coreSource . 'docs/license.txt'),
    'readme' => file_get_contents($coreSource . 'docs/readme.txt'),
    'changelog' => file_get_contents($coreSource . 'docs/changelog.txt'),
]);
if (!$builder->pack()) {
    throw new RuntimeException('Could not pack transport archive.');
}

echo $output . $builder->filename . PHP_EOL;
