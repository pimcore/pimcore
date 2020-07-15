/**
 * Validates that the value is a valid U.S. currency value.
 *
 */
Ext.define('Ext.data.validator.Currency', {
    extend: 'Ext.data.validator.Number',
    alias: 'data.validator.currency',

    type: 'currency',

    config: {
        /**
         * @cfg {Boolean} symbolAtEnd
         * `true` to show the currency symbol after the number.
         * Defaults to {Ext.util.Format#currencyAtEnd}.
         */
        symbolAtEnd: undefined,

        /**
         * @cfg {String} spacer
         * The spacer to show between the number and the currency symbol.
         * Defaults to {Ext.util.Format#currencySpacer}.
         */
        spacer: undefined,

        /**
         * @cfg {String} symbol
         * The symbol used to denote currency.
         * Defaults to {Ext.util.Format#currencySign}.
         */
        symbol: undefined
    },

    /**
     * @cfg {String} message
     * The error message to return when the value is not a valid currency amount.
     * @locale
     */
    message: 'Is not a valid currency amount',

    applySymbolAtEnd: function(value) {
        return value === undefined ? Ext.util.Format.currencyAtEnd : value;
    },

    updateSymbolAtEnd: function() {
        this.rebuildMatcher();
    },

    applySpacer: function(value) {
        return value === undefined ? Ext.util.Format.currencySpacer : value;
    },

    updateSpacer: function() {
        this.rebuildMatcher();
    },

    applySymbol: function(value) {
        return value === undefined ? Ext.util.Format.currencySign : value;
    },

    updateSymbol: function() {
        this.rebuildMatcher();
    },

    privates: {
        getMatcherText: function() {
            var me = this,
                ret = me.callParent([true]),
                symbolPart = me.getSymbolMatcher();

            if (me.getSymbolAtEnd()) {
                ret += symbolPart;
            }
            else {
                ret = symbolPart + ret;
            }

            return me.getSignPart() + ret;
        },

        getSymbolMatcher: function() {
            var symbol = Ext.String.escapeRegex(this.getSymbol()),
                spacer = Ext.String.escapeRegex(this.getSpacer() || ''),
                s = this.getSymbolAtEnd() ? (spacer + symbol) : (symbol + spacer);

            return '(?:' + s + ')?';
        },

        parseValue: function(v) {
            // If we're at the front, replace -/+$1 with -/+1
            v = v.replace(this.currencyMatcher, this.atEnd ? '' : '$1');

            return this.callParent([v]);
        },

        rebuildMatcher: function() {
            var me = this,
                symbolPart, atEnd, sign;

            me.callParent();

            if (!me.isConfiguring) {
                atEnd = me.getSymbolAtEnd();
                symbolPart = me.getSymbolMatcher();
                sign = me.getSignPart();

                me.atEnd = atEnd;
                me.currencyMatcher = new RegExp(atEnd ? (symbolPart + '$') : ('^' + sign + symbolPart)); // eslint-disable-line max-len
            }
        }
    }
});
