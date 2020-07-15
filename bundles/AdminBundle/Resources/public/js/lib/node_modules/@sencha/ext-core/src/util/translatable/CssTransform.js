/**
 * @private
 *
 * CSS Transform implementation
 */
Ext.define('Ext.util.translatable.CssTransform', {
    extend: 'Ext.util.translatable.Dom',

    alias: 'translatable.csstransform', // also configures Factoryable

    isCssTransform: true,

    posRegex: /(\d+)px[^\d]*(\d+)px/,

    doTranslate: function(x, y) {
        this.getElement().translate(x, y);
    },

    syncPosition: function() {
        var pos = this.posRegex.exec(this.getElement().dom.style.tranform);

        if (pos) {
            this.x = parseFloat(pos[1]);
            this.y = parseFloat(pos[2]);
        }

        return [this.x, this.y];
    },

    destroy: function() {
        var element = this.getElement();

        if (element && !element.destroyed) {
            element.dom.style.webkitTransform = null;
        }

        this.callParent();
    }
});
