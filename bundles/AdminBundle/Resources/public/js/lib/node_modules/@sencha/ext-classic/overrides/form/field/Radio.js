/**
 * @class Ext.form.field.Radio
 */
Ext.define(null, {
    override: 'Ext.form.field.Radio',

    compatibility: Ext.isIE8,

    getSubTplData: function(fieldData) {
        var data = this.callParent([fieldData]);

        // Rendering a radio button with checked attribute
        // will have a curious side effect in IE8: the DOM
        // node will have checked property set to `true` but
        // radio group (radios with the same name attribute)
        // will behave as if no radio is checked in the group;
        // tabbing into the group will select first or last
        // button instead of the checked one.
        // So instead of rendering the attribute we will set
        // checked value in the DOM after rendering. Apparently
        // such a tiny nudge is enough for the browser to behave.
        delete data.checked;

        return data;
    },

    afterRender: function() {
        this.callParent();

        if (this.checked) {
            this.inputEl.dom.checked = true;
        }
    },

    onChange: function(newValue, oldValue) {
        // We don't need to bother updating other radio buttons in IE8
        // since it will fire propertychange event on any change, not only false -> true.
        // This is unlike standard compliant browsers, see main class.
        this.callSuper([newValue, oldValue]);
    }
});
