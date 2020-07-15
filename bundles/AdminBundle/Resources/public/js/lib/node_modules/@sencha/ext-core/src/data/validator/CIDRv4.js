/**
 * Validates that the value is a valid CIDR block.
 *
 * Works for both IPV4 only.
 */
Ext.define('Ext.data.validator.CIDRv4', {
    extend: 'Ext.data.validator.Format',
    alias: 'data.validator.cidrv4',

    type: 'cidrv4',

    // https://github.com/flipjs/cidr-regex
    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid CIDR block.
     * @locale
     */
    message: 'Is not a valid CIDR block',

    // http://www.regexpal.com/93987

    /**
     * @cfg {RegExp} matcher
     * A matcher to check for valid CIDR block. This may be overridden.
     */
    matcher: /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|[1-2][0-9]|3[0-2]))$/
});
