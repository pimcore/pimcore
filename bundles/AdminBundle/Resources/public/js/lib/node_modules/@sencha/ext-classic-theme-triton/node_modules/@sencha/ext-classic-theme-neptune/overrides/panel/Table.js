Ext.define('Ext.theme.neptune.panel.Table', {
    override: 'Ext.panel.Table',

    lockableBodyBorder: true,

    initComponent: function() {
        var me = this;

        me.callParent();

        if (!me.hasOwnProperty('bodyBorder') && !me.hideHeaders &&
            (me.lockableBodyBorder || !me.lockable)) {
            me.bodyBorder = true;
        }
    }
});
