/* Remember the MODX 3 settings grid namespace and area filters. */
(function () {
    if (!MODx.grid.SystemSettings || !adminTools3Settings.systemSettings) {
        return;
    }

    var prototype = MODx.grid.SystemSettings.prototype;
    var originalApplyGridFilter = prototype.applyGridFilter;

    prototype.applyGridFilter = function (field, parameter) {
        var result = originalApplyGridFilter.call(this, field, parameter);
        if (!this.adminTools3Restoring && (parameter === 'ns' || parameter === 'area')) {
            var toolbar = this.getTopToolbar();
            var namespaceField = toolbar && toolbar.getComponent('filter-ns');
            var areaField = toolbar && toolbar.getComponent('filter-area');
            var values = {
                namespace: namespaceField ? (namespaceField.getValue() || 'core') : 'core',
                area: areaField ? (areaField.getValue() || '') : ''
            };

            Ext.Ajax.request({
                url: adminTools3Settings.config.connector_url,
                params: {
                    action: 'mgr/systemsettings/savestate',
                    namespace: values.namespace,
                    area: values.area
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    if (data.success && data.object) {
                        adminTools3Settings.systemSettings = data.object;
                    }
                }
            });
        }
        return result;
    };

    Ext.onReady(function () {
        var namespaceField = Ext.getCmp('modx-filter-namespace');
        var areaField = Ext.getCmp('modx-filter-area');
        var grid = namespaceField && namespaceField.findParentByType('modx-grid-system-settings');
        if (!grid || !areaField) {
            return;
        }

        var stored = adminTools3Settings.systemSettings;
        var namespaceInUrl = MODx.util.url.getParamValue('ns');
        var areaInUrl = MODx.util.url.getParamValue('area');
        grid.adminTools3Restoring = true;
        if (!namespaceInUrl && stored.namespace) {
            namespaceField.setValue(stored.namespace);
            areaField.store.baseParams.namespace = stored.namespace;
            grid.applyGridFilter(namespaceField, 'ns');
        }
        if (!areaInUrl && stored.area) {
            areaField.setValue(stored.area);
            grid.applyGridFilter(areaField, 'area');
        }
        grid.adminTools3Restoring = false;
    });
})();
