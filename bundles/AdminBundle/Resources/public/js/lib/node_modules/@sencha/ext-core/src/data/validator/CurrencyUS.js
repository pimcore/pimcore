/**
 * Validates that the value is a valid U.S. currency value.
 *
 */
Ext.define('Ext.data.validator.CurrencyUS', {
    extend: 'Ext.data.validator.Currency',
    alias: 'data.validator.currency-us',

    type: 'currency-us',

    thousandSeparator: ',',
    decimalSeparator: '.',
    symbol: '$',
    spacer: '',
    symbolAtEnd: false
});
