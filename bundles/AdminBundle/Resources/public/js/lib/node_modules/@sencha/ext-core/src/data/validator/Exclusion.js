/**
 * Validates that the value does not exist in a {@link #list} of values.
 */
Ext.define('Ext.data.validator.Exclusion', {
    extend: 'Ext.data.validator.List',
    alias: 'data.validator.exclusion',

    type: 'exclusion',

    /**
     * @cfg {String} message
     * The error message to return when the passed value exists in the
     * specified {@link #list}.
     * @locale
     */
    message: 'Is a value that has been excluded',

    //<debug>
    constructor: function() {
        this.callParent(arguments);

        if (!this.getList()) {
            Ext.raise('validator.Exclusion requires a list');
        }
    },
    //</debug>

    inclusion: false
});
