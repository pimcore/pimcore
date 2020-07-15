/**
 * Validates that the value is a valid time.
 */
Ext.define('Ext.data.validator.Time', {
    extend: 'Ext.data.validator.AbstractDate',
    alias: 'data.validator.time',

    type: 'time',

    isTimeValidator: true,

    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid time.
     * @locale
     */
    message: 'Is not a valid time',

    /**
     * @cfg {String/String[]} format
     * The format(s) to allow. See {@link Ext.Date}. Defaults to {@link Ext.Date#defaultTimeFormat}
     * @locale
     */

    privates: {
        getDefaultFormat: function() {
            return Ext.Date.defaultTimeFormat;
        }
    }
});
