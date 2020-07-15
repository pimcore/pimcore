Ext.define('Ext.rtl.slider.Widget', {
    override: 'Ext.slider.Widget',

    constructor: function(config) {
        this.callParent([config]);

        if (this.getInherited().rtl) {
            this.horizontalProp = 'right';
        }
    }
});
