(function () {
    if (!window.Ext || !Ext.ux || !Ext.ux.form || !Ext.ux.form.SuperBoxSelect) {
        return;
    }

    var prototype = Ext.ux.form.SuperBoxSelect.prototype;
    if (prototype.admintools3SearchPatched) {
        return;
    }

    var doTransform = prototype.doTransform;
    prototype.doTransform = function () {
        if (!/(^|\s)modx-tv-listbox-multiple(\s|$)/.test(this.listClass || '')) {
            return doTransform.apply(this, arguments);
        }

        // The store must retain Unicode text for Ext's local filter. Escape only
        // HTML syntax, since SuperBoxSelect later inserts option text as HTML.
        this.htmlEncode = function (value) {
            return String(value).replace(/[&<>"']/g, function (character) {
                return '&#' + character.charCodeAt(0) + ';';
            });
        };

        // Keep the stock prefix filter. Type-ahead completes the first filtered
        // option and can select an unrelated one when the match is in the middle.
        return doTransform.apply(this, arguments);
    };

    prototype.admintools3SearchPatched = true;
}());
