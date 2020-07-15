Ext.define('Ext.theme.material.form.field.Text', {
    override: 'Ext.form.field.Text',

    labelSeparator: '',

    listeners: {
        change: function(field, value) {
            if (field.el) {
                field.el.toggleCls('not-empty', value || field.emptyText);
            }
        },

        render: function(ths, width, height, eOpts) {
            if ((ths.getValue() || ths.emptyText) && ths.el) {
                ths.el.addCls('not-empty');
            }
        }
    }
});
