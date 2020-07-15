/**
 * A data field that automatically {@link #convert converts} its value to a boolean.
 *
 *     @example
 *     Ext.define('Task', {
 *         extend: 'Ext.data.Model',
 *         fields: [
 *             { name: 'completed', type: 'boolean' }
 *         ]
 *     });
 *
 *     var record = Ext.create('Task', { completed: true }),
 *         value = record.get('completed');
 *
 *     Ext.toast("completed is " + value);
 */
Ext.define('Ext.data.field.Boolean', {
    extend: 'Ext.data.field.Field',

    alias: [
        'data.field.bool',
        'data.field.boolean'
    ],

    isBooleanField: true,

    /**
     * @property [trueRe]
     * Values matching this regular expression are considered `true`.
     */
    trueRe: /^\s*(?:true|yes|on|1)\s*$/i,

    convert: function(v) {
        if (typeof v === 'boolean') {
            return v;
        }

        if (this.allowNull && (v === undefined || v === null || v === '')) {
            return null;
        }

        return this.trueRe.test(String(v));
    },

    getType: function() {
        return 'bool';
    }
});
