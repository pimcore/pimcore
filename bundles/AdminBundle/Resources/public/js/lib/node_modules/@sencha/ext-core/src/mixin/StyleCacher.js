/**
 * @private
 */
Ext.define('Ext.mixin.StyleCacher', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'stylecacher'
    },

    getCachedStyle: function(el, style) {
        var cache = this.$styleCache;

        if (!cache) {
            cache = this.$styleCache = {};
        }

        if (!(style in cache)) {
            cache[style] = Ext.fly(el).getStyle(style);
        }

        return cache[style];
    }
});
