/**
 * @class Ext.form.field.Checkbox
 */
Ext.define(null, {
    override: 'Ext.form.field.Checkbox',

    compatibility: Ext.isIE8,

    // IE8 does not support change event but it has propertychange which is even better
    changeEventName: 'propertychange',

    onChangeEvent: function(e) {
        // IE8 propertychange fires for *any* property change but we're only interested in checked
        // We also don't want to react to propertychange fired as the result of assigning
        // checked property in setRawValue().
        if (this.duringSetRawValue || e.browserEvent.propertyName !== 'checked') {
            return;
        }

        this.callParent([e]);
    },

    updateCheckedCls: function(checked) {
        var me = this,
            displayEl = me.displayEl;

        me.callParent([checked]);

        // IE8 has a bug with font icons and pseudo-elements
        if (displayEl && checked !== me.lastValue) {
            displayEl.repaint();
        }
    }
});
