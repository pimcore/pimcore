Ext.define('Ext.theme.material.form.field.Tag', {
    override: 'Ext.form.field.Tag',

    labelSeparator: '',

    listeners: {
        change: function(field, value) {
            if (field.el) {
                field.el.toggleCls('not-empty', value.length);
            }
        },

        render: function(ths, width, height, eOpts) {
            if (ths.getValue() && ths.el) {
                ths.el.addCls('not-empty');
            }
        }
    }
});
