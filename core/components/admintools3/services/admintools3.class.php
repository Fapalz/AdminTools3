<?php

use MODX\Revolution\modX as modX;
use MODX\Revolution\modCacheManager as modCacheManager;
use MODX\Revolution\Mail\modMail as modMail;
use MODX\Revolution\Mail\modPHPMailer;
use MODX\Revolution\Processors\ProcessorResponse as modProcessorResponse;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use xPDO\xPDO as xPDO;

/**
 * The base class for AdminTools3.
 */
class AdminTools3
{
    public const ASSET_VERSION = '1.0.0';

    /* @var modX $modx */
    public $modx;
    public $initialized = [];
    protected $config = [];

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $corePath = $this->modx->getOption('admintools3_core_path', $config, $this->modx->getOption('core_path') . 'components/admintools3/');
        $assetsUrl = $this->modx->getOption('admintools3_assets_url', $config, $this->modx->getOption('assets_url') . 'components/admintools3/');
        $connectorUrl = $assetsUrl . 'connector.php';
        $this->config = array_merge([
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'connectorUrl' => $connectorUrl,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'src/',
            'templatesPath' => $corePath . 'elements/templates/',
            'processorsPath' => $corePath . 'processors/',
            'unlockCode' => $this->modx->getOption('admintools3_unlock_code', null, ''),
            'lockTimeout' => $this->modx->getOption('admintools3_lock_timeout', null, 0) * 60 * 1000,
            'show_lockmenu' => $this->modx->getOption('admintools3_show_lockmenu', null, true),
        ], $config);
        require_once $corePath . 'bootstrap.php';
        $this->modx->lexicon->load('admintools3:default');
    }

    public function initialize($ctx = 'mgr')
    {
        switch ($ctx) {
            case 'mgr':
                if (empty($this->initialized[$ctx])) {
                    $this->modx->controller->addLexiconTopic('admintools3:default');
                    $this->modx->controller->addCss($this->config['cssUrl'] . 'mgr/main.css?v=' . self::ASSET_VERSION);
                    $theme = $this->modx->getOption('admintools3_theme', null, '');
                    $theme = trim($theme) === 'default' ? '' : trim($theme);
                    if (!empty($theme)) {
                        $themeCssFile = 'mgr/themes/' . $theme . '.css';
                        $this->modx->controller->addCss($this->config['cssUrl'] . $themeCssFile . '?v=' . self::ASSET_VERSION);
                        $theme .= '-theme';
                    }
                    // Custom style files
                    if ($customCSS = $this->modx->getOption('admintools3_custom_css')) {
                        $customCSS = explode(',', $customCSS);
                        foreach ($customCSS as $cssFile) {
                            $cssFile = str_replace('{adminTools3Css}', $this->config['cssUrl'] . 'mgr/', $cssFile);
                            $this->modx->controller->addCss($cssFile);
                        }
                    }
                    $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/admintools.js');
                    $this->modx->controller->addJavascript($this->config['jsUrl'] . 'mgr/superboxselect-search.js?v=' . self::ASSET_VERSION);
                    /** @var bool $pElementTree Permission for the element tree */
                    $pElementTree = $this->modx->hasPermission('element_tree');
                    // favorite elements
                    if ($pElementTree && $this->modx->getOption('admintools3_enable_favorite_elements', null, true)) {
                        $this->modx->controller->addLastJavascript($this->config['jsUrl'] . 'mgr/favorites.js?v=' . self::ASSET_VERSION);
                        // View "All/Favorites"
                        $states = $this->getFromProfile('adminTools3States');
                        if (empty($states)) {
                            $_SESSION['admintools3']['favoriteElements']['states'] = ['template' => false, 'chunk' => false, 'tv' => false, 'plugin' => false, 'snippet' => false];
                            $this->saveToProfile($_SESSION['admintools3']['favoriteElements']['states'], 'adminTools3States');
                            //$this->saveToCache($_SESSION['admintools3']['favoriteElements']['states'], 'states', 'favorite_elements/' . $this->modx->user->id);
                        } else {
                            $_SESSION['admintools3']['favoriteElements']['states'] = $states;
                        }
                        // Get favorites elements
                        $elements = $this->getFromProfile('adminTools3Elements');
                        if (empty($elements)) {
                            $_SESSION['admintools3']['favoriteElements']['elements'] = [
                                'templates' => [],
                                'tvs' => [],
                                'chunks' => [],
                                'plugins' => [],
                                'snippets' => [],
                            ];
                            $this->saveToProfile($_SESSION['admintools3']['favoriteElements']['elements'], 'adminTools3Elements');
                        } else {
                            $_SESSION['admintools3']['favoriteElements']['elements'] = $elements;
                        }
                        $_SESSION['admintools3']['favoriteElements']['icon'] = $this->modx->getOption('admintools3_favorites_icon') ? 'icon ' . $this->modx->getOption('admintools3_favorites_icon') : '';
                    }
                    // system settings
                    if ($this->modx->hasPermission('settings') && $this->modx->getOption('admintools3_remember_system_settings', null, true)) {
                        $this->modx->controller->addLastJavascript($this->config['jsUrl'] . 'mgr/systemsettings.js');
                        $settings = $this->getFromProfile('adminTools3SystemSettings');
                        if (empty($settings)) {
                            $_SESSION['admintools3']['systemSettings'] = ['namespace' => 'core', 'area' => ''];
                            $this->saveToProfile($_SESSION['admintools3']['systemSettings'], 'adminTools3SystemSettings');
                        } else {
                            $_SESSION['admintools3']['systemSettings'] = $settings;
                        }
                        if (empty($_SESSION['admintools3']['systemSettings']['namespace'])) {
                            $_SESSION['admintools3']['systemSettings']['namespace'] = 'core';
                        }
                    }
                    // edited elements log
                    if ($pElementTree && $this->modx->getOption('admintools3_enable_elements_log', null, true)) {
                        $this->modx->controller->addLastJavascript($this->config['jsUrl'] . 'mgr/elementlog.js');
                        $this->modx->controller->addLexiconTopic('manager_log');
                    }
                    // admin notes
                    if ($this->modx->getOption('admintools3_enable_notes', null, true)) {
                        $this->modx->controller->addLastJavascript($this->config['jsUrl'] . 'mgr/notes.js?v=' . self::ASSET_VERSION);
                    }
                    // Hide components description
                    $_css = '';
                    if ($this->modx->getOption('admintools3_hide_component_description', null, true)) {
                        $_css .= "\t#limenu-components ul.modx-subnav li a span.description {display: none;}\n";
                    }
                    if ($_css) {
                        $this->modx->controller->addHtml("<style>\n" . $_css . "</style>");
                    }
                    // Plugins
                    if ($pElementTree && $this->modx->getOption('admintools3_plugins_events', null, true)) {
                        $this->modx->controller->addLastJavascript($this->config['jsUrl'] . 'mgr/plugins.js?v=' . self::ASSET_VERSION);
                    }
                    // taskpanel
                    /*
                    if ($this->modx->getOption('admintools3_enable_taskpanel',null,false)) {
                        $this->modx->controller->addLastJavascript($this->config['jsUrl'] . 'mgr/taskpanel.js');
                    }
                    */
                    // config
                    $region = $this->modx->getOption('admintools3_modx_tree_position', null, 'left', true) == 'right' ? 'east' : 'west';
                    if ($region === 'east') {
                        $scripts = "<script>let sideBarRegion = '{$region}'</script>\n";
                        $scripts .= $this->modx->smarty->get_template_vars('maincssjs');
                        $layout_src = $this->getOption('jsUrl') . 'mgr/core/modx.layout.js';
                        $scripts .= "<script src=\"{$layout_src}\"></script>";
                        $this->modx->smarty->assign('maincssjs', $scripts);
                    }
                    // Messages
                    $messageNumber = $this->modx->getOption('admintools3_messages', null, true)
                        ? $this->modx->getCount('modUserMessage', ['recipient' => $this->modx->user->id, 'read' => 0])
                        : -1;
                    // Lock
                    $_SESSION['admintools3']['locked'] = isset($_SESSION['admintools3']['locked']) ? $_SESSION['admintools3']['locked'] : false;
                    $_SESSION['admintools3']['config'] = [
                        'connector_url' => $this->config['assetsUrl'] . 'connector.php',
                        'theme' => $theme,
                        'region' => $region,
                        'lock_timeout' => $this->config['lockTimeout'],
                        'show_lockmenu' => $this->config['show_lockmenu'],
                        'messages' => $messageNumber,
                    ];
                    $scripts = "<script>\n";
                    $scripts .= "\tlet adminTools3Settings = " . $this->modx->toJSON(array_merge($_SESSION['admintools3'], ['currentUser' => $this->modx->user->id])) . ";\n";
                    // Package Denies
                    $packageActions = $this->modx->getOption('admintools3_package_actions', null, '{}', true);
                    $scripts .= "\tlet adminTools3PackageActions = " . $packageActions . ";\n";
                    $scripts .= "</script>";
                    $this->modx->controller->addHtml($scripts);
                    // Custom javascript files
                    if ($customJS = $this->modx->getOption('admintools3_custom_js')) {
                        $customJS = explode(',', $customJS);
                        foreach ($customJS as $jsFile) {
                            $jsFile = str_replace('{adminTools3Js}', $this->config['jsUrl'] . 'mgr/', $jsFile);
                            $this->modx->controller->addLastJavascript($jsFile);
                        }
                    }
                    $this->initialized[$ctx] = true;
                }
                break;
            case 'web':
                break;
        }
        return true;
    }

    /**
     * @param $key
     * @param mixed $value
     * @internal param $property
     */
    public function setOption($key, $value)
    {
        if (!empty($key)) {
            $this->config[$key] = $value;
        }
    }

    /**
     * @param $property
     * @param string $default
     * @return mixed
     */
    public function getOption($property, $default = '')
    {
        return isset($this->config[$property]) ? $this->config[$property] : $default;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->config;
    }

    public function getFromProfile($key)
    {
        if ($this->modx->user->isAuthenticated('mgr')) {
            $profile = $this->modx->user->getOne('Profile');
            $fields = $profile->get('extended');

            if (isset($fields[$key])) {
                return $fields[$key];
            }

            return null;
        }

        return null;
    }

    /**
     * @param array $data
     * @param string $key
     */
    public function saveToProfile($data, $key)
    {
        if ($this->modx->user->isAuthenticated('mgr')) {
            $profile = $this->modx->user->getOne('Profile');
            $fields = $profile->get('extended');

            $fields[$key] = $data;
            $profile->set('extended', $fields);
            if (!$profile->save()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[' . __METHOD__ . '] Could not save extended fields = ' . print_r($fields, 1));
            }
        }
    }

    /**
     * @param array $object
     * @deprecated
     */
    public function updateElementLog(array $object)
    {
        $type = explode('/', $object['action']);
        $elementData = [
            'type' => $type[1],
            'eid' => $object['id'],
            'name' => $type[1] === 'template' ? $object['templatename'] : $object['name'],
            'editedon' => date('Y-m-d H:i:s'),
            'user' => $this->modx->user->get('username'),
        ];
        $key = $elementData['type'] . '-' . $elementData['eid'];
        $data[$key] = $elementData;
        $elements = $this->getFromCache('element_log', 'elementlog/');
        if (is_array($elements)) {
            if (isset($elements[$key])) {
                unset($elements[$key]);
            }
            $elements = array_merge($data, $elements);
        } else {
            $elements = $data;
        }
        $this->saveToCache($elements, 'element_log', 'elementlog/');
    }

    /**
     * @deprecated
     * @return mixed
     */
    public function getElementLog()
    {
        return $this->getFromCache('element_log', 'elementlog/');
    }

    /**
     * @param modResource $resource
     */
    public function clearResourceCache(&$resource)
    {
//        $resource->clearCache();
        $resource->_contextKey = $resource->context_key;
        /** @var modCacheManager $cache */
        $cache = $this->modx->cacheManager->getCacheProvider($this->modx->getOption('cache_resource_key', null, 'resource'));
        $key = $resource->getCacheKey();
        $cache->delete($key, ['deleteTop' => true]);
        $cache->delete($key);

        $this->modx->_clearResourceCache = true;
        $this->modx->cacheManager = new AdminTools3CacheManager($this->modx);
    }

    /**
     * @param array $data
     * @return string|null
     */
    public function sendLoginLink($data)
    {
        $this->loginDataValidate($data);

        $c = $this->modx->newQuery('modUser');
        $c->select(['modUser.*', 'Profile.email', 'Profile.fullname']);
        $c->innerJoin('modUserProfile', 'Profile');
        $c->where([
            'modUser.username' => $data['userdata'],
            'OR:Profile.email:=' => $data['userdata'],
        ]);
        $c->where([
            'modUser.active' => 1,
            'Profile.blocked' => 0,
        ]);
        $message = '';
        /** @var modUser $user */
        $user = $this->modx->getObject('modUser', $c);
        if ($user) {
            $previousUser = $this->modx->user;
            $this->modx->user = $user;
            try {
                if (!$this->modx->hasPermission('frames')) {
                    return $this->modx->lexicon('admintools3_user_not_found');
                }
                if ($this->getLoginUser($user->id)) {
                    return $this->modx->lexicon('admintools3_link_already_sent');
                }
                $token = $this->getToken();
                if (!$this->addLoginState($user->id, $token)) {
                    return 'Could not store login link.';
                }
                $args = ['a' => 'login', 'token' => $token];
                $url = $this->modx->makeUrl($this->modx->resource->id, '', $args, 'full');
                $options['email_body'] = $this->modx->lexicon('admintools3_authorization_email_body', ['url' => $url]);
                $sent = $this->sendEmail($user->get('email'), $options);
                if ($sent !== true) {
                    $this->deleteLoginState($token, $user->id);
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdminTools3] Login email failed: ' . $sent);
                    return 'Could not send login link.';
                }
            } finally {
                $this->modx->user = $previousUser;
            }
        } else {
            $message = $this->modx->lexicon('admintools3_user_not_found');
        }
        return $message;
    }

    protected function getLoginUser($uid)
    {
        $cacheManager = $this->modx->getCacheManager();
        return $cacheManager->get($uid, [xPDO::OPT_CACHE_KEY => 'admintools3/login']);
    }

    /**
     * @param int $uid
     * @param string $token
     */
    public function addLoginState($uid, $token)
    {
        $ttl = $this->modx->getOption('admintools3_authorization_ttl', null, 600);
        $key = $this->getUserLoginKey();
        /*$this->modx->registry->user->subscribe('/admintools3/login/');
        $this->modx->registry->user->send('/admintools3/login/', [
            $token => [
                'key' => $key,
                'uid' => $user->get('id'),
            ],
        ], ['ttl' => $ttl]);*/
        $payload = [
            'key' => $key,
            'uid' => $uid,
        ];
        $cacheManager = $this->modx->getCacheManager();
        return  $cacheManager->set($token, $payload, $ttl, [xPDO::OPT_CACHE_KEY => 'admintools3/login']) &&
                $cacheManager->set($uid, $token, $ttl, [xPDO::OPT_CACHE_KEY => 'admintools3/login']);
    }

    /**
     * @param string $token
     * @return false|mixed
     */
    public function getLoginState($token)
    {
        /*$message = [];
        if ($this->modx->getService('registry', 'registry.modRegistry')) {
            $registry = $this->modx->registry->getRegister('user', 'registry.modDbRegister');
//            $registry->connect();
            $registry->subscribe('/admintools3/login/' . $token);
            $message = $registry->read(['remove_read' => $delete, 'poll_limit' => 1]);
            if ($delete) {
                return true;
            }
        }*/
        return $this->modx->getCacheManager()->get($token, [xPDO::OPT_CACHE_KEY => 'admintools3/login']);
    }

    /**
     * @param string $token
     * @param int $uid
     * @return bool
     */
    public function deleteLoginState($token, $uid)
    {
        $cacheManager = $this->modx->getCacheManager();

        return  $cacheManager->delete($token, [xPDO::OPT_CACHE_KEY => 'admintools3/login']) &&
                $cacheManager->delete($uid, [xPDO::OPT_CACHE_KEY => 'admintools3/login']);
    }

    protected function loginDataValidate(array $data = [])
    {
        if (empty($data['action']) || $data['action'] !== 'login') {
            throw new InvalidArgumentException('Access is denied');
        }
        if (empty($data['userdata'])) {
            throw new InvalidArgumentException($this->modx->lexicon('admintools3_enter_username_or_email'));
        }
    }
    /**
     * Sends email with authorization link
     *
     * @param $email
     * @param array $options
     *
     * @return string|bool
     */
    public function sendEmail($email, array $options = [])
    {
        /** @var modPHPMailer $mail */
        $mail = $this->modx->getService('mail', modPHPMailer::class);

        $mail->set(modMail::MAIL_SUBJECT, $this->modx->getOption('email_subject', $options, $this->modx->lexicon('admintools3_authorization_email_subject')));
        $mail->set(modMail::MAIL_BODY, $this->modx->getOption('email_body', $options, ''));
        $mail->set(modMail::MAIL_SENDER, $this->modx->getOption('email_from', $options, $this->modx->getOption('emailsender'), true));
        $mail->set(modMail::MAIL_FROM, $this->modx->getOption('email_from', $options, $this->modx->getOption('emailsender'), true));
        $mail->set(modMail::MAIL_FROM_NAME, $this->modx->getOption('email_from_name', $options, $this->modx->getOption('site_name'), true));

        $mail->address('to', $email);
        $mail->address('reply-to', $this->modx->getOption('email_from', $options, $this->modx->getOption('emailsender'), true));
        $mail->setHTML(true);

        $response = !$mail->send()
            ? $mail->mailer->errorInfo
            : true;
        $mail->reset();

        return $response;
    }

    /**
     * @param int $uid User id
     * @param string $token
     * @return string|null
     */
    public function loginUser($uid, $token)
    {
        $error_message = '';
        /** @var modUser $user */
        if ($user = $this->modx->getObject('modUser', ['id' => $uid])) {
            $data['username'] = $user->get('username');
            $data['password'] = 'password';
            $data['login_context'] = 'mgr';
            $data['addContexts'] = [];
            $data['rememberme'] = (int)$this->modx->getOption('admintools3_rememberme', null, 0);
        } else {
            return 'Error when try to login.';
        }
        $plugin = $this->modx->getObject(\MODX\Revolution\modPlugin::class, ['name' => 'AdminTools3']);
        $id = $plugin ? (int) $plugin->get('id') : 0;
        if (empty($id)) {
            return $this->modx->lexicon('admintools3_plugin_not_found');
        }
        $this->modx->eventMap['OnManagerPageBeforeRender'][$id] = $id;
        $this->modx->eventMap['OnManagerAuthentication'][$id] = $id;
        $this->modx->setOption('admintools3_user_can_login', true);
        /** @var modProcessorResponse $response */
        $response = $this->modx->runProcessor(\MODX\Revolution\Processors\Security\Login::class, $data);
        if (($response instanceof modProcessorResponse) && !$response->isError()) {
            $this->deleteLoginState($token, $uid);
            $this->modx->sendRedirect($this->getManagerUrl());
        } elseif ($response) {
            $errors = $response->getAllErrors();
            $error_message = implode("\n", $errors);
        } else {
            $error_message = $this->modx->lexicon('login_username_password_incorrect');
        }

        return $error_message;
    }

    /**
     * @param int $rid Resource id
     * @return bool
     */
    public function hasPermissions($rid = 0)
    {
        //TODO-sergant  Make a map file?
        if ($rid === 0) {
            $rid = $this->modx->resource->get('id');
        }
        $user = $this->modx->user;
        $userId = $this->modx->user->get('id');
        $q = $this->modx->newQuery(\AdminTools3\Model\Permission::class);
        $q->setClassAlias('Permissions');
//        $q->leftJoin('modUserProfile','User', 'Permissions.principal = User.internalKey AND Permissions.principal_type = "usr"');
        $q->leftJoin('modUserGroup', 'Group', 'Permissions.principal = Group.id AND Permissions.principal_type = "grp"');
        $q->select('Permissions.*, Group.name as groupname');
        $q->where([
            'Permissions.rid' => $rid,
        ]);
        $q->sortby('Permissions.weight', 'ASC');
        $q->sortby('Permissions.priority', 'ASC');
        $tstart = microtime(true);
        if ($q->prepare() && $q->stmt->execute()) {
            $this->modx->queryTime += microtime(true) - $tstart;
            $this->modx->executedQueries++;
            $permissions = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $allow = true;
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                switch ($permission['principal_type']) {
                    case 'all':
                        $allow = (bool)$permission['status'];
                        break;
                    case 'gst':
                        if ($userId === 0) {
                            $allow = (bool)$permission['status'];
                        }
                        break;
                    case 'grp':
                        if ($userId && $user->isMember($permission['groupname'])) {
                            $allow = (bool)$permission['status'];
                        }
                        break;
                    case 'usr':
                        if ($userId === (int)$permission['principal']) {
                            $allow = (bool)$permission['status'];
                        }
                        break;
                }
            }
        }
        return $allow;
    }

    /**
     * @param string $uri
     */
    public function createResourceCache($uri = '/')
    {
        $siteUrl = rtrim($this->modx->getOption('site_url'), '/');
        $url = $siteUrl . '/' . ltrim($uri, '/');
        try {
            $client = $this->modx->services->get(ClientInterface::class);
            $requestFactory = $this->modx->services->get(RequestFactoryInterface::class);
            $client->sendRequest($requestFactory->createRequest('GET', $url));
        } catch (Throwable $exception) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdminTools3] Cache warmup failed: ' . $exception->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return !empty($_SESSION['admintools3']['locked']);
    }

    /**
     * @param string $unlockCode
     * @return bool
     */
    public function unlock($unlockCode)
    {
        if (!empty($unlockCode)) {
            $_SESSION['admintools3']['locked'] = empty($this->config['unlockCode'])
                ? !$this->modx->user->passwordMatches($unlockCode)
                : $this->config['unlockCode'] !== $unlockCode;
        }
        return !$_SESSION['admintools3']['locked'];
    }

    public function getInputPlaceholder()
    {
        return empty($this->config['unlockCode']) ? $this->modx->lexicon('admintools3_enter_password') : $this->modx->lexicon('admintools3_enter_unlockcode');
    }

    /**
     * @return string
     */
    public function getManagerUrl()
    {
        $url = $this->modx->getOption('manager_url', null, MODX_MANAGER_URL);

        return $this->modx->getOption('url_scheme', null, MODX_URL_SCHEME) . $this->modx->getOption('http_host', null, MODX_HTTP_HOST) . rtrim($url, '/');
    }

    /**
     * @return string
     */
    public function getUserLoginKey()
    {
        return md5($_SERVER['REMOTE_ADDR'] . '/' . $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     *
     * @param int $length Token length
     * @return string
     */
    public function getToken($length = 16)
    {
        if(!isset($length) || (int)$length <= 8 ){
            $length = 16;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        do {
            $bytes = openssl_random_pseudo_bytes(32, $innerStrong);
        } while(!$bytes || !$innerStrong);

        return bin2hex($bytes);
    }
}

/**
 * Cache manager class for adminTools3.
 */
class AdminTools3CacheManager extends modCacheManager
{
    public function refresh(array $providers = [], array &$results = [])
    {
        if ($this->modx->getOption('admintools3_clear_only_resource_cache', null, false) && !empty($this->modx->_clearResourceCache)) {
            $this->modx->_clearResourceCache = false;
            unset($providers['resource']);
            $this->modx->cacheManager = null;
            $this->modx->getCacheManager();
        }
        return parent::refresh($providers, $results);
    }
}
