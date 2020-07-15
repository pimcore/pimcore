Ext.define('Ext.theme.material.form.field.Radio', {
    override: 'Ext.form.field.Radio',

    ripple: {
        delegate: '.' + Ext.baseCSSPrefix + 'form-radio',
        bound: false
    }
});
