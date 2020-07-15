/**
 * @abstract
 * A superclass for a validator that checks if a value is within a certain range.
 */
Ext.define('Ext.data.validator.Bound', {
    extend: 'Ext.data.validator.Validator',
    alias: 'data.validator.bound',

    type: 'bound',

    config: {
        /**
         * @cfg {Number} min
         * The minimum length value.
         */
        min: undefined,

        /**
         * @cfg {Number} max
         * The maximum length value.
         */
        max: undefined,

        /**
         * @cfg {String} emptyMessage
         * The error message to return when the value is empty.
         * @locale
         */
        emptyMessage: 'Must be present',

        /**
         * @cfg {String} minOnlyMessage
         * The error message to return when the value is less than the minimum
         * and only a minimum is specified.
         * @locale
         */
        minOnlyMessage: 'Value must be greater than {0}',

        /**
         * @cfg {String} maxOnlyMessage
         * The error message to return when the value is more than the maximum
         * and only a maximum is specified.
         * @locale
         */
        maxOnlyMessage: 'Value must be less than {0}',

        /**
         * @cfg {String} bothMessage
         * The error message to return when the value is not in the specified range
         * and both the minimum and maximum are specified.
         * @locale
         */
        bothMessage: 'Value must be between {0} and {1}'
    },

    resetMessages: function() {
        this._bothMsg = this._minMsg = this._maxMsg = null;
    },

    updateMin: function() {
        this.resetMessages();
    },

    updateMax: function() {
        this.resetMessages();
    },

    updateMinOnlyMessage: function() {
        this.resetMessages();
    },

    updateMaxOnlyMessage: function() {
        this.resetMessages();
    },

    updateBothMessage: function() {
        this.resetMessages();
    },

    validate: function(value) {
        var me = this,
            min = me.getMin(),
            max = me.getMax(),
            hasMin = (min != null),
            hasMax = (max != null),
            msg = this.validateValue(value);

        if (msg !== true) {
            return msg;
        }

        value = me.getValue(value);

        if (hasMin && hasMax) {
            if (value < min || value > max) {
                msg = me._bothMsg ||
                    (me._bothMsg = Ext.String.format(me.getBothMessage(), min, max));
            }
        }
        else if (hasMin) {
            if (value < min) {
                msg = me._minMsg ||
                    (me._minMsg = Ext.String.format(me.getMinOnlyMessage(), min));
            }
        }
        else if (hasMax) {
            if (value > max) {
                msg = me._maxMsg ||
                    (me._maxMsg = Ext.String.format(me.getMaxOnlyMessage(), max));
            }
        }

        return msg;
    },

    validateValue: function(value) {
        if (value === undefined || value === null) {
            return this.getEmptyMessage();
        }

        return true;
    },

    getValue: Ext.identityFn
});
