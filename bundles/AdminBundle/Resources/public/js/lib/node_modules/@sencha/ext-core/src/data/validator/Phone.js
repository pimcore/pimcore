/**
 * Validates that the value is a valid phone.
 */
Ext.define('Ext.data.validator.Phone', {
    extend: 'Ext.data.validator.Format',
    alias: 'data.validator.phone',

    type: 'phone',

    // https://www.sitepoint.com/community/t/phone-number-regular-expression-validation/2204/2
    //      (modified)

    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid phone.
     * @locale
     */
    message: 'Is not a valid phone number',

    /**
     * @cfg {RegExp} matcher
     * A matcher to check for simple phones. This may be overridden.
     */
    matcher: new RegExp(
        '^ *' +

        // optional country code
        '(?:' +
            '\\+?' + // maybe + prefix
            '(\\d{1,3})' +
            // optional separator
            '[- .]?' +
        ')?' +

        // optional area code
        '(?:' +
            '(?:' +
                '(\\d{3})' + // without ()
                '|' +
                '\\((\\d{3})\\)' + // with ()
            ')?' +
            // optional separator
            '[- .]?' +
        ')' +

        // CO code (3 digit prefix)
        '(?:' +
            '([2-9]\\d{2})' +
            // optional separator
            '[- .]?' +
        ')' +

        // line number (4 digits)
        '(\\d{4})' +

        // optional extension
        '(?: *(?:e?xt?) *(\\d*))?' +

        ' *$'
    )
});
