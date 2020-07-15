/**
 * @private
 * @class Ext.draw.SurfaceBase
 */
Ext.define('Ext.draw.SurfaceBase', {
    extend: 'Ext.Widget',

    getOwnerBody: function() {
        return this.getRefOwner().bodyElement;
    }

});
