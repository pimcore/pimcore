/**
 * A data field that automatically {@link #convert converts} its value to an integer.
 *
 * **Note:** As you can see in the example below, casting data as an integer
 * can result in a loss of precision. (5.1 is converted to 5).
 *
 *     @example
 *     Ext.define('User', {
 *         extend: 'Ext.data.Model',
 *         fields: [
 *             { name: 'age', type: 'integer' }
 *         ]
 *     });
 *
 *     var record = Ext.create('User', { age: "5.1" }),
 *         value = record.get('age');
 *
 *     Ext.toast("age is " + value);
 */
Ext.define('Ext.data.field.Integer', {
    extend: 'Ext.data.field.Field',

    alias: [
        'data.field.int',
        'data.field.integer'
    ],

    isNumeric: true,
    isIntegerField: true,
    numericType: 'int',

    convert: function(v) {
        // Handle values which are already numbers.
        // Value truncation behaviour of parseInt is historic and must be maintained.
        // parseInt(35.9)  and parseInt("35.9") returns 35
        if (typeof v === 'number') {
            return this.getNumber(v);
        }

        /* eslint-disable-next-line vars-on-top */
        var empty = v == null || v === '',
            allowNull = this.allowNull,
            out;

        if (empty) {
            out = allowNull ? null : 0;
        }
        else {
            out = this.parse(v);

            if (allowNull && isNaN(out)) {
                out = null;
            }
        }

        return out;
    },

    getNumber: function(v) {
        return parseInt(v, 10);
    },

    getType: function() {
        return this.numericType;
    },

    parse: function(v) {
        return parseInt(String(v).replace(this.stripRe, ''), 10);
    },

    sortType: function(s) {
        // If allowNull, null values needed to be sorted last.
        if (s == null) {
            s = Infinity;
        }

        return s;
    }
});
