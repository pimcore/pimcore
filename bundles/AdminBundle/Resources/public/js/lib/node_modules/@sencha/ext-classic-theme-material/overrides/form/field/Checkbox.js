Ext.define('Ext.theme.material.form.field.Checkbox', {
    override: 'Ext.form.field.Checkbox',

    ripple: {
        delegate: '.' + Ext.baseCSSPrefix + 'form-checkbox',
        bound: false
    }
});
