/**
 *
 */
Ext.define('Ext.util.SizeMonitor', {
    requires: [
        'Ext.util.sizemonitor.Scroll'
        // 'Ext.util.sizemonitor.OverflowChange'
    ],

    constructor: function(config) {
        return new Ext.util.sizemonitor.Scroll(config);
        // var namespace = Ext.util.sizemonitor;
        //
        // if (Ext.browser.is.Firefox) {
        //     // this one decreases the grid performance in Firefox
        //     return new namespace.OverflowChange(config);
        // } else {
        //     return new namespace.Scroll(config);
        // }
    }
});
