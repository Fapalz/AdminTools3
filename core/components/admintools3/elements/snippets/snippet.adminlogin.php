<?php
/** @var AdminTools3 $AdminTools3 */
/** @var array $scriptProperties */
$path = $modx->getOption('admintools3_core_path', null, $modx->getOption('core_path') . 'components/admintools3/') . 'services/';
$AdminTools3 = $modx->getService('admintools3', 'AdminTools3', $path, $scriptProperties);
$tpl = $modx->getOption('tpl', $scriptProperties, 'tpl.login.form3');
$get = [];
foreach ($_GET as $key => $value) {
    if (is_string($value)) {
        $get[$key] = trim($value);
    }
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $success = true;
    $message = $modx->lexicon('admintools3_link_is_sent');

    try {
        $result = $AdminTools3->sendLoginLink($get);
        if ($result !== null && $result !== '') {
            $success = false;
            $message = $result;
        }
    } catch (InvalidArgumentException $e) {
        $success = false;
        $message =  $e->getMessage();
    }
    $response = ['success' => $success, 'message' => $message];

    exit($modx->toJSON($response));
}

if ($modx->user->isAuthenticated('mgr')) {
    $modx->sendRedirect($AdminTools3->getManagerUrl());
}
$errormsg = '';
if (isset($get['a'], $get['token']) && $get['a'] === 'login') {
    $get['token'] = $modx->sanitizeString($get['token']);
    $data = $AdminTools3->getLoginState($get['token']);
    if (!empty($data['uid']) && hash_equals($data['key'], $AdminTools3->getUserLoginKey())) {
        $errormsg = $AdminTools3->loginUser($data['uid'], $get['token']);
    }
}
/** @var array $scriptProperties */
$assetsUrl = $AdminTools3->getOption('assetsUrl');
$modx->regClientCss($assetsUrl . 'css/mgr/login.css');
$modx->regClientScript($assetsUrl . 'js/mgr/login.js');
return $modx->getChunk($tpl, ['errormsg' => $errormsg]);
