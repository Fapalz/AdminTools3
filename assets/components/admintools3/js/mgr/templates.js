AdminTools3.grid.TemplResources = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'admintools3-templresources-grid';
	}
	Ext.applyIf(config, {
		url: adminTools3Settings.config.connector_url,
		fields: this.getFields(config),
		columns: this.getColumns(config),
		baseParams: {
			action: 'mgr/resources/getlist',
			tid: MODx.request.id
		},
		showActionsColumn: false,
		viewConfig: {
			forceFit: true,
			enableRowBody: true,
			autoFill: true,
			showPreview: true,
			scrollOffset: 0,
			getRowClass: function (rec, ri, p) {
				return rec.data.deleted
					? 'admintools3-grid-row-disabled'
					: '';
			}
		},
		paging: true,
		remoteSort: true,
		autoHeight: true
	});
	AdminTools3.grid.TemplResources.superclass.constructor.call(this, config);
};
Ext.extend(AdminTools3.grid.TemplResources, MODx.grid.Grid, {
	windows: {},

	getFields: function (config) {
		return ['id', 'pagetitle', 'description', 'deleted' , 'published', 'context_key', 'uri'];
	},
	getColumns: function (config) {
		return [{
			header: "ID",
			dataIndex: 'id',
			fixed: true,
			sortable: true,
			width: 100
		}, {
			header: _('admintools3_title'),
			dataIndex: 'pagetitle',
			renderer: function (title, metadata, row) {
				var id = encodeURIComponent(row.data.id);
				var label = Ext.util.Format.htmlEncode(String(title || ''));
				return '<a href="index.php?a=resource/update&id=' + id + '">' + label + '</a>';
			},
			sortable: true,
			width: 200
		}, {
			header: _('admintools3_description'),
			dataIndex: 'description',
			renderer: Ext.util.Format.htmlEncode,
			sortable: false,
			width: 200
		}, {
			header: 'URI',
			dataIndex: 'uri',
			sortable: false,
			width: 100
		}, {
			header: _('admintools3_context'),
			dataIndex: 'context_key',
			sortable: false,
			width: 70
		}, {
			header: _('admintools3_published'),
			dataIndex: 'published',
			renderer: AdminTools3.utils.renderBoolean,
			sortable: false,
			width: 70
		}, {
			header: _('admintools3_deleted'),
			dataIndex: 'deleted',
			renderer: AdminTools3.utils.renderBoolean,
			sortable: false,
			width: 70
		}];
	},

	getTopBar: function (config) {
		return [{
			text: '<i class="icon icon-plus"></i>&nbsp;' + _('admintools3_create'),
			handler: function () {
				MODx.loadPage('resource/create', 'template=' + encodeURIComponent(MODx.request.id));
			},
			scope: this
		}];
	}

});
Ext.reg('admintools3-templresources-grid', AdminTools3.grid.TemplResources);


/** ******************************** **/

Ext.onReady(function () {
	MODx.addTab("modx-template-tabs",{
		id: "admintools3-resources-tab",
		title: _('admintools3_resources'),
		items: [{
			xtype: "admintools3-templresources-grid",
			//html: "test",
			width: "100%"
		}]
	});
});
