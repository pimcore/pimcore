/**
 * A data field that automatically {@link #convert converts} its value to a floating-point
 * number.
 *
 *     @example
 *     Ext.define('Product', {
 *         extend: 'Ext.data.Model',
 *         fields: [
 *             { name: 'price', type: 'number' }
 *         ]
 *     });
 *
 *     var record = Ext.create('Product', { price: "5.1" }),
 *         value = record.get('price');
 *
 *     Ext.toast("price is " + value);
 */
Ext.define('Ext.data.field.Number', {
    extend: 'Ext.data.field.Integer',

    alias: [
        'data.field.float',
        'data.field.number'
    ],

    isIntegerField: false,
    isNumberField: true,
    numericType: 'float',

    getNumber: Ext.identityFn,

    parse: function(v) {
        return parseFloat(String(v).replace(this.stripRe, ''));
    }
});
