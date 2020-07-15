/**
 * @override Ext.rtl.layout.ContextItem
 * This override adds RTL support to Ext.layout.ContextItem.
 */
Ext.define('Ext.rtl.layout.ContextItem', {
    override: 'Ext.layout.ContextItem',

    rtlTranslateProps: {
        x: 'right',
        y: 'top'
    },

    constructor: function(config) {
        var me = this,
            componentContext = config.componentContext;

        me.callParent([config]);

        // If a componentContext exists, it means this context item is the child element
        // of a component, so just ask for the state. If no componentContext, then ask
        // the component for the state
        me.rtl = componentContext ? componentContext.rtl : me.target.getInherited().rtl;

        if (me.rtl) {
            me.translateProps = me.rtlTranslateProps;
        }
    }

});
