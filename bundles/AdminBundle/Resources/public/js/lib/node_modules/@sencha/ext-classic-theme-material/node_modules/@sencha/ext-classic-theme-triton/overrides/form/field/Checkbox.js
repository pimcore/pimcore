Ext.define('Ext.theme.triton.form.field.Checkbox', {
    override: 'Ext.form.field.Checkbox',

    compatibility: Ext.isIE8,

    initComponent: function() {
        this.callParent();

        Ext.on({
            show: 'onGlobalShow',
            scope: this
        });
    },

    onFocus: function(e) {
        var focusClsEl;

        this.callParent([e]);

        focusClsEl = this.getFocusClsEl();

        if (focusClsEl) {
            focusClsEl.syncRepaint();
        }
    },

    onBlur: function(e) {
        var focusClsEl;

        this.callParent([e]);

        focusClsEl = this.getFocusClsEl();

        if (focusClsEl) {
            focusClsEl.syncRepaint();
        }
    },

    onGlobalShow: function(cmp) {
        if (cmp.isAncestor(this)) {
            this.getFocusClsEl().syncRepaint();
        }
    }
});
