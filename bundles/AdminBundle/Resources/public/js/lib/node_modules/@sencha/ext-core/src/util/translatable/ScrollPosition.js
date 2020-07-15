/**
 * @private
 *
 * Scroll position implementation
 */
Ext.define('Ext.util.translatable.ScrollPosition', {
    extend: 'Ext.util.translatable.Dom',

    alias: 'translatable.scrollposition', // also configures Factoryable

    constructor: function(config) {
        if (config && config.element) {
            this.x = config.element.getScrollLeft();
            this.y = config.element.getScrollTop();
        }

        this.callParent([config]);
    },

    translateAnimated: function() {
        var element = this.getElement();

        this.x = element.getScrollLeft();
        this.y = element.getScrollTop();

        this.callParent(arguments);
    },

    doTranslate: function(x, y) {
        var element = this.getElement();

        element.setScrollLeft(Math.round(x));
        element.setScrollTop(Math.round(y));
    },

    getPosition: function() {
        var me = this,
            position = me.position,
            element = me.getElement();

        position.x = element.getScrollLeft();
        position.y = element.getScrollTop();

        return position;
    }

});
