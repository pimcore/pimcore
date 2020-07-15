Ext.define('Ext.overrides.app.domain.View', {
    override: 'Ext.app.domain.View',
    requires: [
        'Ext.Component'
    ],

    constructor: function(controller) {
        this.callParent([controller]);
        // The base class handles Ext.Widget, which encompasses
        // component for modern, so we only need the override here.
        this.monitoredClasses.push(Ext.Component);
    }
});
