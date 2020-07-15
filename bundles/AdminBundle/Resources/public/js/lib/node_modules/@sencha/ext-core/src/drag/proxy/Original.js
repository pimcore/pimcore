/**
 * A drag proxy that uses the {@link Ext.drag.Source#element}.
 */
Ext.define('Ext.drag.proxy.Original', {
    extend: 'Ext.drag.proxy.None',
    alias: 'drag.proxy.original',

    getElement: function(info) {
        return info.source.getElement();
    },

    getPositionable: function(info) {
        var source = info.source;

        return source.getComponent() || source.getElement();
    }
});
