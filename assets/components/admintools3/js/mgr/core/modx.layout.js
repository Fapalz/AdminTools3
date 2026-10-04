/* Put the stock MODX 3 tree on the right without replacing its controls. */
(function () {
    if (!MODx.Layout || !MODx.Layout.prototype.getCenter) {
        return;
    }

    var originalGetCenter = MODx.Layout.prototype.getCenter;
    MODx.Layout.prototype.getCenter = function (config) {
        var wrapper = originalGetCenter.call(this, config);
        if (window.innerWidth <= 640 || !wrapper || !wrapper.items || wrapper.items.length < 2) {
            return wrapper;
        }

        var tree = wrapper.items[0];
        var content = wrapper.items[1];
        tree.region = 'east';
        tree.margins = {top: 0, right: 0, bottom: 0, left: 0};
        content.margins.right = 0;
        return wrapper;
    };
})();
