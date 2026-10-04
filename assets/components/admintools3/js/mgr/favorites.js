/* Favorite elements on top of MODX 3's native element tree. */
(function () {
    if (!MODx.tree || !MODx.tree.Element || !adminTools3Settings.favoriteElements) {
        return;
    }

    var treePrototype = MODx.tree.Element.prototype;
    var originalMenu = treePrototype._getElementMenu;
    var originalLoad = treePrototype.onLoad;
    var originalToolbar = treePrototype.getToolbar;
    var groups = {
        template: 'templates', tv: 'tvs', chunk: 'chunks',
        plugin: 'plugins', snippet: 'snippets'
    };
    var favoriteCategories = null;
    var categoryRequestId = 0;

    function favoritesOnly() {
        var states = adminTools3Settings.favoriteElements.states || {};
        return Object.keys(groups).every(function (type) { return !!states[type]; });
    }

    function isFavorite(node) {
        var type = node.attributes.type;
        var ids = adminTools3Settings.favoriteElements.elements[groups[type]] || [];
        return ids.some(function (id) { return String(id) === String(node.attributes.pk); });
    }

    function decorate(node) {
        if (!node || !node.attributes || node.attributes.pseudoroot) {
            return;
        }
        var ui = node.getUI();
        var type = node.attributes.type;
        var isCategory = node.attributes.classKey === 'MODX\\Revolution\\modCategory';
        if (!groups[type] && !(isCategory && type === 'category')) {
            return;
        }
        if (isCategory) {
            var allowed = favoriteCategories && (type === 'category'
                ? Object.keys(groups).reduce(function (ids, elementType) {
                    return ids.concat(favoriteCategories[elementType] || []);
                }, [])
                : favoriteCategories[type]);
            if (favoritesOnly() && allowed
                && allowed.indexOf(Number(node.attributes.pk)) === -1) {
                ui.hide();
            } else {
                ui.show();
            }
            return;
        }
        var favorite = isFavorite(node);
        if (favorite) {
            ui.addClass('x-element-favorite');
        } else {
            ui.removeClass('x-element-favorite');
        }
        if (ui.elNode && ui.anchor) {
            ui.addClass('admintools3-has-favorite-star');
            var star = ui.admintools3StarButton;
            if (!star || star.parentNode !== ui.elNode) {
                star = document.createElement('button');
                star.type = 'button';
                star.className = 'admintools3-favorite-star';
                star.addEventListener('mousedown', function (event) {
                    event.stopPropagation();
                });
                star.addEventListener('keydown', function (event) {
                    event.stopPropagation();
                });
                star.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    setFavorite(node.getOwnerTree(), node, !isFavorite(node));
                });
                ui.anchor.parentNode.insertBefore(star, ui.anchor.nextSibling);
                ui.admintools3StarButton = star;
            }
            var starLabel = _(favorite ? 'admintools3_remove_from_favorites' : 'admintools3_add_to_favorites');
            star.classList.toggle('is-favorite', favorite);
            star.setAttribute('title', starLabel);
            star.setAttribute('aria-label', starLabel);
            star.setAttribute('aria-pressed', favorite ? 'true' : 'false');
        }
        if (favoritesOnly() && !favorite) {
            ui.hide();
        } else {
            ui.show();
        }
    }

    function decorateBranch(node) {
        decorate(node);
        if (node && node.childNodes) {
            Ext.each(node.childNodes, decorateBranch);
        }
    }

    function refreshFavoriteCategories(tree) {
        var requestId = ++categoryRequestId;
        Ext.Ajax.request({
            url: adminTools3Settings.config.connector_url,
            params: { action: 'mgr/favorites/getcategories' },
            success: function (response) {
                var result = Ext.decode(response.responseText);
                if (requestId === categoryRequestId && result.success && result.object) {
                    favoriteCategories = result.object;
                    decorateBranch(tree.getRootNode());
                }
            }
        });
    }

    function setFavorite(tree, node, favorite) {
        Ext.Ajax.request({
            url: adminTools3Settings.config.connector_url,
            params: {
                action: favorite ? 'mgr/favorites/add' : 'mgr/favorites/remove',
                type: node.attributes.type,
                id: node.attributes.pk
            },
            success: function (response) {
                var result = Ext.decode(response.responseText);
                if (result.success && result.object) {
                    adminTools3Settings.favoriteElements.elements = result.object;
                    decorate(node);
                    refreshFavoriteCategories(tree);
                }
            },
            scope: tree
        });
    }

    function setFilter(tree, showFavorites) {
        Ext.Ajax.request({
            url: adminTools3Settings.config.connector_url,
            params: {
                action: 'mgr/favorites/savestate',
                type: 'all',
                state: showFavorites
            },
            success: function (response) {
                var result = Ext.decode(response.responseText);
                if (result.success && result.object) {
                    adminTools3Settings.favoriteElements.states = result.object;
                    decorateBranch(tree.getRootNode());
                    if (tree.admintools3FavoriteButton) {
                        updateFilterButton(tree.admintools3FavoriteButton);
                    }
                }
            },
            scope: tree
        });
    }

    function updateFilterButton(button) {
        var active = favoritesOnly();
        var label = _(active ? 'admintools3_show_all' : 'admintools3_show_favorites');
        button.setTooltip(label);
        var buttonElement = button.getEl() && button.getEl().dom.querySelector('button');
        if (buttonElement) {
            buttonElement.setAttribute('aria-label', label);
            buttonElement.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
        if (active) {
            button.getEl().addClass('admintools3-favorite-active');
        } else {
            button.getEl().removeClass('admintools3-favorite-active');
        }
    }

    treePrototype.getToolbar = function () {
        var tree = this;
        var toolbar = originalToolbar.call(this) || [];
        if (toolbar.indexOf('->') === -1) {
            toolbar.push('->');
        }
        toolbar.push({
            text: _('admintools3_favorites'),
            width: 30,
            cls: 'admintools3-tree-action admintools3-favorites-action',
            tooltip: _(favoritesOnly() ? 'admintools3_show_all' : 'admintools3_show_favorites'),
            handler: function () { setFilter(tree, !favoritesOnly()); },
            listeners: {
                render: function (button) {
                    tree.admintools3FavoriteButton = button;
                    updateFilterButton(button);
                }
            }
        });
        return toolbar;
    };

    treePrototype._getElementMenu = function (node) {
        var menu = originalMenu.call(this, node);
        if (groups[node.attributes.type] && node.attributes.pk) {
            var favorite = isFavorite(node);
            menu.push({
                text: favorite ? _('admintools3_remove_from_favorites') : _('admintools3_add_to_favorites'),
                scope: this,
                handler: function () { setFavorite(this, node, !favorite); }
            });
        }
        return menu;
    };

    treePrototype.onLoad = function (loader, node, response) {
        originalLoad.call(this, loader, node, response);
        Ext.each(node.childNodes, decorateBranch);
        if (favoriteCategories === null && categoryRequestId === 0) {
            refreshFavoriteCategories(this);
        }
    };

})();
