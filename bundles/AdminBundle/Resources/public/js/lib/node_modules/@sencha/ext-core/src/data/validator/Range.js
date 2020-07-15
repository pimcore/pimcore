/**
 * Validates that the the value is between a {@link #min} and {@link #max}.
 */
Ext.define('Ext.data.validator.Range', {
    extend: 'Ext.data.validator.Bound',
    alias: 'data.validator.range',

    type: 'range',

    /**
     * @cfg {Number} min
     * The minimum value.
     */

    /**
     * @cfg {Number} max
     * The maximum value.
     */

    /**
     * @cfg minOnlyMessage
     * @inheritdoc
     * @locale
     */
    minOnlyMessage: 'Must be at least {0}',

    /**
     * @cfg maxOnlyMessage
     * @inheritdoc
     * @locale
     */
    maxOnlyMessage: 'Must be no more than than {0}',

    /**
     * @cfg bothMessage
     * @inheritdoc
     * @locale
     */
    bothMessage: 'Must be between {0} and {1}',

    config: {
        /**
         * @cfg {String} nanMessage
         * The error message to return when the value is not numeric.
         * @locale
         */
        nanMessage: 'Must be numeric'
    },

    validateValue: function(value) {
        var msg = this.callParent([value]);

        if (msg === true && isNaN(value)) {
            msg = this.getNanMessage();
        }

        return msg;
    }
});
