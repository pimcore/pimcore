/**
 *
 */
Ext.define('Ext.util.PaintMonitor', {
    requires: [
        'Ext.util.paintmonitor.CssAnimation'
    ],

    constructor: function(config) {
        return new Ext.util.paintmonitor.CssAnimation(config);
    }
});
