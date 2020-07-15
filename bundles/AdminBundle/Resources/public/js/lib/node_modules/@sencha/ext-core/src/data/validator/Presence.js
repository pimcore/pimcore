/**
 * Validates that the passed value is not `null` or `undefined` or `''`.
 * An empty string may be allowed with {@link #allowEmpty}.
 */
Ext.define('Ext.data.validator.Presence', {
    extend: 'Ext.data.validator.Validator',
    alias: 'data.validator.presence',

    type: 'presence',

    isPresence: true,

    config: {
        /**
         * @cfg {String} message
         * The error message to return when the value is not specified.
         * @locale
         */
        message: 'Must be present',

        /**
         * @cfg {Boolean} allowEmpty
         * `true` to allow `''` as a valid value.
         */
        allowEmpty: false
    },

    validate: function(value) {
        var valid = !(value === undefined || value === null);

        if (valid && !this.getAllowEmpty()) {
            valid = value !== '';
        }

        return valid ? true : this.getMessage();
    }
});
