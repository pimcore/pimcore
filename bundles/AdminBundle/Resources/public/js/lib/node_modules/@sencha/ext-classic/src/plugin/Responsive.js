/**
 * @deprecated 6.6.0 Require `Ext.Responsive` instead to enable `responsiveConfig`.
 */
Ext.define('Ext.plugin.Responsive', {
    extend: 'Ext.plugin.Abstract',
    alias: 'plugin.responsive',

    requires: [
        'Ext.Responsive'
    ],

    //<debug>
    constructor: function() {
        this.callParent(arguments);

        Ext.log.warn('responsive plugin is deprecated; require "Ext.Responsive" instead');
    },
    //</debug>

    id: 'responsive'
});
