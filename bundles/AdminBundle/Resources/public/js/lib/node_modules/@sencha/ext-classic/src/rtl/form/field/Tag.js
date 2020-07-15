Ext.define('Ext.rtl.form.field.Tag', {
    override: 'Ext.form.field.Tag',

    privates: {
        _getChildElCls: function() {
            return this.getInherited().rtl ? (' ' + this._rtlCls) : '';
        }
    }
});
