/**
 * Validates that the value is a valid date.
 */
Ext.define('Ext.data.validator.Date', {
    extend: 'Ext.data.validator.AbstractDate',
    alias: 'data.validator.date',

    type: 'date',

    isDateValidator: true,

    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid date.
     * @locale
     */
    message: 'Is not a valid date',

    /**
     * @cfg {String/String[]} format
     * The format(s) to allow. See {@link Ext.Date}. Defaults to {@link Ext.Date#defaultFormat}
     * @locale
     */

    privates: {
        getDefaultFormat: function() {
            return [
                Ext.Date.defaultFormat,
                'm/d/Y',
                'n/j/Y',
                'n/j/y',
                'm/j/y',
                'n/d/y',
                'm/j/Y',
                'n/d/Y',
                'm-d-y',
                'n-d-y',
                'm-d-Y',
                'mdy',
                'mdY',
                'Y-m-d'
            ];
        }
    }
});
