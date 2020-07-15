/**
 * A data field that automatically {@link #convert converts} its value to a string.
 *
 *     @example
 *     Ext.define('User', {
 *         extend: 'Ext.data.Model',
 *         fields: [
 *             { name: 'firstName', type: 'string' }
 *         ]
 *     });
 *
 *     var record = Ext.create('User', { firstName: "Phil" }),
 *         value = record.get('firstName');
 *
 *     Ext.toast("firstName is " + value);
 */
Ext.define('Ext.data.field.String', {
    extend: 'Ext.data.field.Field',

    alias: 'data.field.string',

    sortType: 'asUCString',

    isStringField: true,

    convert: function(v) {
        var defaultValue = this.allowNull ? null : '';

        return (v === undefined || v === null) ? defaultValue : String(v);
    },

    getType: function() {
        return 'string';
    }
});
