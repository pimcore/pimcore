/**
 * Validates that the value is a valid date with time.
 */
Ext.define('Ext.data.validator.DateTime', {
    extend: 'Ext.data.validator.AbstractDate',
    alias: 'data.validator.datetime',

    type: 'datetime',

    isDateTimeValidator: true,

    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid time.
     * @locale
     */
    message: 'Is not a valid date and time',

    /**
     * @cfg {String/String[]} format
     * The format(s) to allow. See {@link Ext.Date}. Defaults to  the concatenation of
     * the {@link Ext.Date#defaultFormat} and the {@link Ext.Date#defaultTimeFormat}.
     * @locale
     */

    privates: {
        getDefaultFormat: function() {
            var D = Ext.Date;

            return D.defaultFormat + ' ' + D.defaultTimeFormat;
        }
    }
});
