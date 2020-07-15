/**
 * @private
 */
Ext.define('Ext.util.ItemCollection', {
    extend: 'Ext.util.MixedCollection',
    alternateClassName: 'Ext.ItemCollection',

    getKey: function(item) {
        return item.getItemId && item.getItemId();
    },

    has: function(item) {
        return this.map.hasOwnProperty(item.getId());
    }
});
