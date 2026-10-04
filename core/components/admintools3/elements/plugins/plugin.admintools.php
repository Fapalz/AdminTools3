<?php
use MODX\Revolution\modSystemEvent as modSystemEvent;
/** @var array $scriptProperties */
$path = $modx->getOption('admintools3_core_path', null, $modx->getOption('core_path') . 'components/admintools3/') . 'services/';
/** @var AdminTools3 $AdminTools3 */
$AdminTools3 = $modx->getService('admintools3', 'AdminTools3', $path);
$elementType = null;
if ($AdminTools3 instanceof AdminTools3) {
    switch ($modx->event->name) {
        case 'OnManagerPageBeforeRender':
            if ($modx->user->id) {
                $AdminTools3->initialize();
            }
            break;
        case 'OnManagerPageAfterRender':
            if ($AdminTools3->isLocked()) {
                $controller->content = $modx->getChunk('tpl.lockScreen3', [
                        'username' => $modx->user->username,
                        'photo' => $modx->user->getPhoto(),
                        'title' => $modx->getOption('site_name'),
                        'lang' => $modx->getOption('manager_language'),
                        'form_action' => $AdminTools3->getOption('connectorUrl'),
                        'auth' => $modx->user->getUserToken('mgr'),
                        'assets_url' => MODX_ASSETS_URL,
                        'input_placeholder' => $AdminTools3->getInputPlaceholder(),
                    ]
                );
            }
            break;
        case 'OnDocFormSave':
            if ($modx->getOption('admintools3_clear_only_resource_cache', null, false) && $modx->event->params['mode'] === modSystemEvent::MODE_UPD) {
                if ($resource->get('syncsite')) {
                    $AdminTools3->clearResourceCache($resource);
                }
                if (!empty($_POST['createCache'])) {
                    $AdminTools3->createResourceCache($resource->uri);
                }
            }
            break;
        case 'OnManagerPageInit':
            if (!$modx->user->isAuthenticated('mgr') && $modx->getOption('admintools3_email_authorization', null, false)) {
                $id = (int) $modx->getOption('admintools3_loginform_resource');
                if (!empty($id) && $modx->getCount('modResource', ['id' => $id, 'published' => 1, 'deleted' => 0])) {
                    $url = $modx->makeUrl($id, '', '', 'full');
                    $modx->setOption('manager_login_url_alternate', $url);
                }
            }
            break;
        case 'OnManagerAuthentication':
            if ($modx->getOption('admintools3_user_can_login', null, false)) {
                $modx->setOption('admintools3_user_can_login', false);
                $modx->event->output(true);
            }
            break;
        case 'OnLoadWebDocument':
            if ($modx->user->isAuthenticated($modx->context->get('key'))) {
                $profile = $modx->user->getOne('Profile');
                if (!$modx->user->active || !$profile || $profile->blocked) {
                    $modx->runProcessor(\MODX\Revolution\Processors\Security\Logout::class);
                }
            }
            if ($modx->getOption('admintools3_alternative_permissions', null, false) && !$AdminTools3->hasPermissions()){
                $modx->sendUnauthorizedPage();
            }
            break;
        case 'OnTempFormPrerender':
            if ($modx->getOption('admintools3_template_resource_relationship', null, true)) {
                $modx->controller->addLastJavascript($AdminTools3->getOption('jsUrl') . 'mgr/templates.js?v=' . AdminTools3::ASSET_VERSION);
            }
            break;
        case 'OnDocFormPrerender':
            $_html = [];
            $output = '';
            if ($modx->getOption('admintools3_template_resource_relationship', null, true)) {
                $_html['tpl_res_relationship'] = '
            var tmpl = Ext.getCmp("modx-resource-template");
            if (tmpl.getValue()) tmpl.label.update(_("resource_template") + "&nbsp;&nbsp;<a href=\"?a=element/template/update&id=" + tmpl.getValue() + "\"><i class=\"icon icon-external-link\"></i></a>");';
            }
            if ($modx->getOption('admintools3_clear_only_resource_cache', null, false) && $modx->event->params['mode'] != modSystemEvent::MODE_NEW) {
                $_html['create_resource_cache'] = '
            var cb = Ext.create({
                xtype: "xcheckbox",
                boxLabel: _("admintools3_create_resource_cache"),
                description: _("admintools3_create_resource_cache_help"),
                hideLabel: true,
                name: "createCache",
                id: "admintools3-create-cache",
                checked: '. (int)$modx->getOption('admintools3_create_resource_cache', null, false) .'
            });
            var syncSite = Ext.getCmp("modx-resource-syncsite");
            if (syncSite && syncSite.ownerCt) {
                syncSite.ownerCt.add(cb);
                syncSite.ownerCt.doLayout();
            }';
            }
            if (!empty($_html)) {
            $output .= '
    Ext.onReady(function() {
        setTimeout(function(){' . implode("\n", $_html) . '
        }, 200);
    });';
            }
            if ($modx->getOption('admintools3_alternative_permissions', null, true) && $modx->hasPermission('access_permissions')) {
                $modx->controller->addLastJavascript($AdminTools3->getOption('jsUrl') . 'mgr/permissions.js');
                $output .= '
    Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
		this.on("beforerender", function() {
			this.add({
				title: _("admintools3_permissions"),
				border: false,
				items: [{
					layout: "anchor",
					border: false,
					items: [{
						html: _("admintools3_permissions_desc"),
						border: false,
						bodyCssClass: "panel-desc"
					}, {
						xtype: "admintools3-grid-permissions",
						anchor: "100%",
						cls: "main-wrapper",
						resource: ' . (int) ($modx->event->params['id'] ?? 0) . '
					}]
				}]
			});
		});
	});
';
            }
            if (!empty($output)) {
                $modx->controller->addHtml('<script>' . $output . '</script>');
            }
            break;
        case 'OnMODXInit':
            if (($modx->context->get('key') !== 'mgr')
                && $modx->getOption('admintools3_only_current_context_user', null, false)
                && $modx->user->isAuthenticated('mgr')
                && !$modx->user->isAuthenticated($modx->context->get('key')))
            {
               $modx->user = $modx->newObject('modUser');
                $modx->user->fromArray(['id' => 0, 'username' => $modx->getOption('default_username', '', '(anonymous)', true)], '', true);
            }
            break;
    }
}
