let AdminTools3 = function (config) {
	config = config || {};
	AdminTools3.superclass.constructor.call(this, config);
};
Ext.extend(AdminTools3, Ext.Component, {
	page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}, toolbar: {},
	lock: function () {
		MODx.Ajax.request({
			url: adminTools3Settings.config.connector_url,
			params: {
				action: 'mgr/system/lock'
			},
			listeners: {
				success: {
					fn: function(response) {
						document.location.reload();
					},
					scope: this
				}
			}
		});
	},
	setTimeout: function (time) {
		return setTimeout(function () {
			AdminTools3.lock();
		}, time)
	},
	syncMessageCounter: function () {
		if (AdminTools3.messageCounterEl === undefined) {
			AdminTools3.messageCounterEl = document.getElementById('message-counter');
		}
		let counter = this.getMessageCounter();
		if (AdminTools3.messageCounterEl) {
			AdminTools3.messageCounterEl.innerText = (counter == 0) ? '' : counter;
		}
	},
	getMessageCounter: function () {
		return adminTools3Settings.config.messages;
	},
	increaseMessageCounter: function () {
		adminTools3Settings.config.messages++;
		this.syncMessageCounter();
		return adminTools3Settings.config.messages;
	},
	decreaseMessageCounter: function () {
		adminTools3Settings.config.messages--;
		if (adminTools3Settings.config.messages < 0) {
			adminTools3Settings.config.messages = 0;
		}
		this.syncMessageCounter();
		return adminTools3Settings.config.messages;
	},
});
Ext.reg('admintools3', AdminTools3);

AdminTools3 = new AdminTools3();

AdminTools3.utils.renderBoolean = function (value, props, row) {
	return value
		? String.format('<span class="green">{0}</span>', _('yes'))
		: String.format('<span class="red">{0}</span>', _('no'));
};

AdminTools3.utils.renderPrincipalType = function (value, props, row) {
	let output;
	switch (value) {
		case 'grp':
			output = '<i class="icon icon-group"></i>';
			break;
		case 'usr':
			output = '<i class="icon icon-user"></i>';
			break;
		default:
			output = '';
			break;
	}
	return output;
};

AdminTools3.utils.getMenu = function (actions, grid, selected) {
	let menu = [];
	let cls, icon, title, action = '';

	for (let i in actions) {
		if (!actions.hasOwnProperty(i)) {
			continue;
		}

		let a = actions[i];
		if (!a['menu']) {
			if (a == '-') {
				menu.push('-');
			}
			continue;
		}
		else if (menu.length > 0 && /^remove/i.test(a['action'])) {
			menu.push('-');
		}

		if (selected.length > 1) {
			if (!a['multiple']) {
				continue;
			}
			else if (typeof(a['multiple']) == 'string') {
				a['title'] = a['multiple'];
			}
		}

		cls = a['cls'] ? a['cls'] : '';
		icon = a['icon'] ? a['icon'] : '';
		title = a['title'] ? a['title'] : a['title'];
		action = a['action'] ? grid[a['action']] : '';

		menu.push({
			handler: action,
			text: String.format(
				'<span class="{0}"><i class="x-menu-item-icon {1}"></i>{2}</span>',
				cls, icon, title
			),
		});
	}

	return menu;
};


AdminTools3.utils.renderActions = function (value, props, row) {
	let res = [];
	let cls, icon, title, action, item = '';
	for (let i in row.data.actions) {
		if (!row.data.actions.hasOwnProperty(i)) {
			continue;
		}
		let a = row.data.actions[i];
		if (!a['button']) {
			continue;
		}

		cls = a['cls'] ? a['cls'] : '';
		icon = a['icon'] ? a['icon'] : '';
		action = a['action'] ? a['action'] : '';
		title = a['title'] ? a['title'] : '';

		item = String.format(
			'<li class="{0}"><button class="btn btn-default {1}" action="{2}" title="{3}"></button></li>',
			cls, icon, action, title
		);

		res.push(item);
	}

	return String.format(
		'<ul class="admintools3-row-actions">{0}</ul>',
		res.join('')
	);
};
// Status
AdminTools3.combo.SearchTypes = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		triggerAction: 'all',
		typeAhead: true,
		mode: 'local',
		hideMode: 'offsets',
		autoScroll: true,
		maxHeight: 200,
		store: [[1,_('admintools3_search_everywhere')],[2,_('admintools3_search_in_titles')],[3,_('admintools3_search_in_text')],[4,_('admintools3_search_in_tags')]],
		hiddenName: 'wheresearch',
		editable: true
	});
	AdminTools3.combo.SearchTypes.superclass.constructor.call(this,config);
};
Ext.extend(AdminTools3.combo.SearchTypes,MODx.combo.ComboBox);
Ext.reg('admintools3-combo-wheresearch',AdminTools3.combo.SearchTypes);

Ext.onReady(function () {
	let theme = '', region = '';
	//let adminTools3Settings = adminTools3Settings || {config:{theme:'', region:'west'}};
	if (adminTools3Settings) {
		theme = adminTools3Settings.config.theme;
		region = adminTools3Settings.config.region;
	}
	if (theme) Ext.getBody().addClass(theme);
	if (region == 'east') {
		Ext.getBody().addClass('right-side-tree');
		let contentNode = Ext.get('modx-content'),
			actionButtonsNode = Ext.get('modx-action-buttons-container');
		if (actionButtonsNode) actionButtonsNode.appendTo(contentNode);
	}
	let Items = Ext.query('ul.modx-subsubnav');
	for (let i = 0; Items.length > i; i++) {
		Items[i].parentNode.classList.add('has-subnav');
	}
	// Lock
	if (adminTools3Settings.config.show_lockmenu > 0) {
		let userMenuList = document.getElementById('limenu-user-submenu');
		let newLi = document.createElement('li');
		newLi.id = 'admintools3-lock';
		newLi.innerHTML = '<a href="javascript:;">' + _('admintools3_lock') + ' <span class="description">' + _('admintools3_lock_desc') + '</span></a>';
		newLi.querySelector('a').addEventListener('click', function (event) {
			event.preventDefault();
			AdminTools3.lock();
		});

		if (userMenuList && !document.getElementById('admintools3-lock')) {
			userMenuList.insertBefore(newLi, userMenuList.lastElementChild);
		}
	}
	if (adminTools3Settings.config.lock_timeout > 0) {
		let lockTimeout = AdminTools3.setTimeout(adminTools3Settings.config.lock_timeout);
		['mousemove','keydown','wheel','click','contextmenu'].forEach(function(event) {
			Ext.select('body').on(event, function(e) {
				clearTimeout(lockTimeout);
				lockTimeout = AdminTools3.setTimeout(adminTools3Settings.config.lock_timeout);
			});

		});
	}

	// Messages
	if (adminTools3Settings.config.messages >= 0) {
		Ext.ComponentMgr.onAvailable('modx-grid-message', function () {
			this.getStore().on("update", function (g, p, o) {
				if (o == 'commit') {
					if (p.data.read) {
						AdminTools3.decreaseMessageCounter();
					} else {
						AdminTools3.increaseMessageCounter();
					}
				}
			}, this);
			this.on('afterRemoveRow', function (r) {
				AdminTools3.decreaseMessageCounter();
			})
		});
		let userMenu = document.querySelector('#limenu-user > a');
		let newSpan = document.createElement('span');
		newSpan.id = 'message-counter';
		newSpan.className = 'badge';
		newSpan.innerText = AdminTools3.getMessageCounter() ? AdminTools3.getMessageCounter() : '';

		setTimeout(function () {
			if (userMenu) userMenu.appendChild(newSpan);
		}, 300);
	}

	// Package actions
	if (MODx.grid.Package) {
		var originalPackageClick = MODx.grid.Package.prototype.onClick;
		Ext.override(MODx.grid.Package, {
			onClick: function (e) {
				var target = e.getTarget();
				var classes = (target.className || '').split(' ');
				if (classes[0] === 'controlBtn') {
					var record = this.getSelectionModel().getSelected();
					var rules = record && adminTools3PackageActions[record.data.name];
					var action = classes[1];
					if (rules) {
						var rule = Object.prototype.hasOwnProperty.call(rules, action) ? rules[action] : rules.all;
						if (rule === false || Ext.isString(rule)) {
							Ext.MessageBox.alert(_('warning'), Ext.isString(rule) ? rule : (rules.message || _('permission_denied')));
							return false;
						}
				}
				}
				return originalPackageClick.call(this, e);
			}
		});
	}
});
