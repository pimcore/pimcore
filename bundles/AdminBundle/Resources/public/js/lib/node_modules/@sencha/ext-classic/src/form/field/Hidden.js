/**
 * A basic hidden field for storing hidden values in forms that need to be passed in the form
 * submit.
 *
 * This creates an actual input element with type="hidden" in the DOM. While its label is
 * {@link #hideLabel not rendered} by default, it is still a real component and may be sized
 * according to its owner container's layout.
 *
 * Because of this, in most cases it is more convenient and less problematic to simply
 * {@link Ext.form.action.Action#params pass hidden parameters} directly when
 * {@link Ext.form.Basic#submit submitting the form}.
 *
 * Example:
 *
 *     @example
 *     new Ext.form.Panel({
 *         title: 'My Form',
 *         items: [{
 *             xtype: 'textfield',
 *             fieldLabel: 'Text Field',
 *             name: 'text_field',
 *             value: 'value from text field'
 *         }, {
 *             xtype: 'hiddenfield',
 *             name: 'hidden_field_1',
 *             value: 'value from hidden field'
 *         }],
 *
 *         buttons: [{
 *             text: 'Submit',
 *             handler: function() {
 *                 this.up('form').getForm().submit({
 *                     params: {
 *                         hidden_field_2: 'value from submit call'
 *                     }
 *                 });
 *             }
 *         }]
 *     });
 *
 * Submitting the above form will result in three values sent to the server:
 *
 *     text_field=value+from+text+field&hidden;_field_1=value+from+hidden+field&
 *     hidden_field_2=value+from+submit+call
 *
 */
Ext.define('Ext.form.field.Hidden', {
    extend: 'Ext.form.field.Base',
    alias: ['widget.hiddenfield', 'widget.hidden'],
    alternateClassName: 'Ext.form.Hidden',

    focusable: false,
    inputType: 'hidden',
    isTextInput: false,
    hideLabel: true,
    hidden: true,

    ariaRole: 'presentation',

    initComponent: function() {
        this.formItemCls += '-hidden';
        this.callParent();
    },

    /**
     * @private
     * Override. Treat undefined and null values as equal to an empty string value.
     */
    isEqual: function(value1, value2) {
        return this.isEqualAsString(value1, value2);
    },

    initEvents: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    setSize: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    setWidth: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    setHeight: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    setPosition: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    setPagePosition: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    markInvalid: Ext.emptyFn,

    /**
     * @method
     * @hide
     */
    clearInvalid: Ext.emptyFn
});
