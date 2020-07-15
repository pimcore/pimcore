/**
 * @class Ext.view.TagKeyNav
 * @private
 */
Ext.define('Ext.view.TagKeyNav', {
    extend: 'Ext.view.BoundListKeyNav',

    alias: 'view.navigation.tagfield',

    onKeySpace: function(e) {
        var me = this,
            field = me.view.pickerField;

        if (field.isExpanded && field.inputEl.dom.value === '') {
            field.preventKeyUpEvent = true;

            me.navigateOnSpace = true;

            me.callParent([e]);

            e.stopEvent();

            return false;
        }

        // Allow propagating to the field
        return true;
    }
});
