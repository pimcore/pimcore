/**
 * Validates that the value is a valid number.
 *
 * A valid number may include a leading + or -, comma separators, and a single decimal point.
 */
Ext.define('Ext.data.validator.Number', {
    extend: 'Ext.data.validator.Validator',
    alias: 'data.validator.number',

    type: 'number',

    config: {
        /**
         * @cfg {String} decimalSeparator
         * The decimal separator. Defaults to {@link Ext.util.Format#decimalSeparator}.
         */
        decimalSeparator: undefined,

        /**
         * @cfg {String} message
         * The error message to return when the value is not a valid number.
         * @locale
         */
        message: 'Is not a valid number',

        /**
         * @cfg {String} thousandSeparator
         * The thousand separator. Defaults to {@link Ext.util.Format#thousandSeparator}.
         */
        thousandSeparator: undefined
    },

    constructor: function(config) {
        this.callParent([config]);
        this.rebuildMatcher();
    },

    applyDecimalSeparator: function(v) {
        return v === undefined ? Ext.util.Format.decimalSeparator : v;
    },

    updateDecimalSeparator: function() {
        this.rebuildMatcher();
    },

    applyThousandSeparator: function(v) {
        return v === undefined ? Ext.util.Format.thousandSeparator : v;
    },

    updateThousandSeparator: function() {
        this.rebuildMatcher();
    },

    parse: function(v) {
        var sep = this.getDecimalSeparator(),
            N = Ext.Number;

        if (typeof v === 'string') {
            if (!this.matcher.test(v)) {
                return null;
            }

            v = this.parseValue(v);
        }

        return sep ? N.parseFloat(v) : N.parseInt(v);
    },

    validate: function(value) {
        return this.parse(value) === null ? this.getMessage() : true;
    },

    privates: {
        getMatcherText: function(preventSign) {
            var t = this.getThousandSeparator(),
                d = this.getDecimalSeparator(),
                s = '(?:';

            if (t) {
                t = Ext.String.escapeRegex(t);
                s += '(?:\\d{1,3}(' + t + '\\d{3})*)|';
            }

            s += '\\d*)';

            if (d) {
                d = Ext.String.escapeRegex(d);
                s += '(?:' + d + '\\d*)?';
            }

            if (!preventSign) {
                s = this.getSignPart() + s;
            }

            return s;
        },

        getSignPart: function() {
            return '(\\+|\\-)?';
        },

        parseValue: function(v) {
            var thousandMatcher = this.thousandMatcher,
                decimal;

            if (thousandMatcher) {
                v = v.replace(thousandMatcher, '');
            }

            decimal = this.getDecimalSeparator();

            if (decimal && decimal !== '.') {
                v = v.replace(decimal, '.');
            }

            return v;
        },

        rebuildMatcher: function() {
            var me = this,
                sep;

            if (!me.isConfiguring) {
                sep = me.getThousandSeparator();
                me.matcher = new RegExp('^' + me.getMatcherText() + '$');

                if (sep) {
                    me.thousandMatcher = sep ? new RegExp(Ext.String.escapeRegex(sep), 'g') : null;
                }
            }
        }
    }
});
