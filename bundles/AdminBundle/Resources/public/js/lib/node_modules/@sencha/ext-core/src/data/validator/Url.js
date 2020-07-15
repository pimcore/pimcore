/**
 * Validates that the value is a proper URL.
 */
Ext.define('Ext.data.validator.Url', {
    extend: 'Ext.data.validator.Format',
    alias: 'data.validator.url',

    type: 'url',

    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid URL.
     * @locale
     */
    message: 'Is not a valid URL',

    // URL validator that works is non-trivial
    // There are numerous examples online but not all pass rigoruous test:
    //      https://mathiasbynens.be/demo/url-regex
    // The only one that looks to be comprehensive and bulletproof is this one:
    //      https://gist.github.com/dperini/729294
    //      which requires inclusion of a copyright header

    /**
     * @cfg {RegExp} matcher
     * A matcher to check for simple Urls. This may be overridden.
     */
    /* eslint-disable-next-line no-useless-escape */
    matcher: /^(http:\/\/|https:\/\/|ftp:\/\/|\/\/)([-a-zA-Z0-9@:%_\+.~#?&//=])+$/
});
