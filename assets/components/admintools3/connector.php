<?php
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
}
else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var AdminTools3 $AdminTools3 */
$corePath = $modx->getOption('admintools3_core_path', null, $modx->getOption('core_path') . 'components/admintools3/');$AdminTools3 = $modx->getService('admintools3', 'AdminTools3', $corePath . 'services/');
$modx->lexicon->load('admintools3:default');

// handle request
$path = $modx->getOption('processorsPath', $AdminTools3->getOptions(), $corePath . 'processors/');
/** @noinspection PhpMethodParametersCountMismatchInspection */
$modx->request->handleRequest(array(
	'processors_path' => $path,
	'location' => '',
));