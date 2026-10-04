AdminTools3.page.Home = function (config) {
	config = config || {};
	Ext.applyIf(config, {
		components: [{
			xtype: 'admintools3-panel-home', renderTo: 'admintools3-panel-home-div'
		}]
	});
	AdminTools3.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(AdminTools3.page.Home, MODx.Component);
Ext.reg('admintools3-page-home', AdminTools3.page.Home);

Ext.onReady(function() {
	MODx.load({ xtype: "admintools3-page-home"});
});