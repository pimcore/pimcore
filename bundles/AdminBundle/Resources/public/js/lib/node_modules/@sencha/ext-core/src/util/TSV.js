/**
 * This class contains utility methods for dealing with TSV (Tab Separated Values) as
 * specified in 
 * <a href="https://www.iana.org/assignments/media-types/text/tab-separated-values">
 * IANA MIME type for text/tab-separated-values</a>.
 *
 * For details see `{@link Ext.util.DelimitedValue}`.
 *
 * @since 5.1.0
 */
Ext.define('Ext.util.TsvDecoder', {
    extend: 'Ext.util.DelimitedValue',
    alternateClassName: 'Ext.util.TSV',

    delimiter: '\t'
}, function(TSVClass) {
    /*
     * @singleton
     * @class Ext.util.TSV
     * @alternateClassName Ext.util.TsvDecoder
     */
    Ext.util.TSV = new TSVClass();
});
