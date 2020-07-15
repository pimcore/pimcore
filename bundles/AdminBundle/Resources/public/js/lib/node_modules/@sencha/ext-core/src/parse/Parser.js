/**
 * This class parses simple expressions. The parser can be enhanced by providing any of
 * the following configs:
 *
 *  * `constants`
 *  * `infix`
 *  * `infixRight`
 *  * `postfix`
 *  * `symbols`
 *
 * The parser requires a `{@link Ext.parse.Tokenizer tokenizer}` which can be configured
 * using the `tokenizer` config. The parser keeps the tokenizer instance and recycles it
 * as it is itself reused.
 *
 * See http://javascript.crockford.com/tdop/tdop.html for background on the techniques
 * used in this parser.
 * @private
 */
Ext.define('Ext.parse.Parser', function() {
    var ITSELF = function() {
        return this;
    };

/* eslint-disable indent */
return {
    extend: 'Ext.util.Fly',

    requires: [
        'Ext.parse.Tokenizer',
        'Ext.parse.symbol.Constant',
        'Ext.parse.symbol.InfixRight',
        'Ext.parse.symbol.Paren',
        'Ext.parse.symbol.Prefix'
    ],

    isParser: true,

    config: {
        /**
         * @cfg {Object} constants
         * A map of identifiers that should be converted to literal value tokens. The
         * key in this object is the name of the constant and the value is the constant
         * value.
         *
         * If the value of a key is an object, it is a config object for the
         * `{@link Ext.parse.symbol.Constant constant}`.
         */
        constants: {
            'null': null,
            'false': false,
            'true': true
        },

        /**
         * @cfg {Object} infix
         * A map of binary operators and their associated precedence (or binding priority).
         * These binary operators are left-associative.
         *
         * If the value of a key is an object, it is a config object for the
         * `{@link Ext.parse.symbol.Infix operator}`.
         */
        infix: {
            '===': 40,
            '!==': 40,
            '==': 40,
            '!=': 40,
            '<': 40,
            '<=': 40,
            '>': 40,
            '>=': 40,

            '+': 50,
            '-': 50,

            '*': 60,
            '/': 60
        },

        /**
         * @cfg {Object} infixRight
         * A map of binary operators and their associated precedence (or binding priority).
         * These binary operators are right-associative.
         *
         * If the value of a key is an object, it is a config object for the
         * `{@link Ext.parse.symbol.InfixRight operator}`.
         */
        infixRight: {
            '&&': 30,
            '||': 30
        },

        /**
         * @cfg {Object} prefix
         * A map of unary operators. Typically no value is needed, so `0` is used.
         *
         * If the value of a key is an object, it is a config object for the
         * `{@link Ext.parse.symbol.Prefix operator}`.
         */
        prefix: {
            '!': 0,
            '-': 0,
            '+': 0
        },

        /**
         * @cfg {Object} symbols
         * General language symbols. The values in this object are used as config objects
         * to configure the associated `{@link Ext.parse.Symbol symbol}`. If there is no
         * configuration, use `0` for the value.
         */
        symbols: {
            ':': 0,
            ',': 0,
            ')': 0,
            '[': 0,
            ']': 0,
            '{': 0,
            '}': 0,

            '(end)': 0,

            '(ident)': {
                arity: 'ident',
                isIdent: true,
                nud: ITSELF
            },

            '(literal)': {
                arity: 'literal',
                isLiteral: true,
                nud: ITSELF
            },

            '(': {
                xclass: 'Ext.parse.symbol.Paren'
            }
        },

        /**
         * @cfg {Object/Ext.parse.Tokenizer} tokenizer
         * The tokenizer or a config object used to create one.
         */
        tokenizer: {
            keywords: null  // we'll handle keywords here
        }
    },

    /**
     * @cfg {Ext.parse.Symbol} token
     * The current token. These tokens extend this base class and contain additional
     * properties such as:
     *
     *   * `at` - The index of the token in the text.
     *   * `value` - The value of the token (e.g., the name of an identifier).
     *
     * @readonly
     */
    token: null,

    constructor: function(config) {
        this.symbols = {};

        this.initConfig(config);
    },

    /**
     * Advances the token stream and returns the next `token`.
     * @param {String} [expected] The type of symbol that is expected to follow.
     * @return {Ext.parse.Symbol}
     */
    advance: function(expected) {
        var me = this,
            tokenizer = me.tokenizer,
            token = tokenizer.peek(),
            symbols = me.symbols,
            index = tokenizer.index,
            is, name, symbol, value;

        if (me.error) {
            throw me.error;
        }

        if (expected) {
            me.expect(expected);
        }

        if (!token) {
            return me.token = symbols['(end)'];
        }

        tokenizer.next();

        is = token.is;
        value = token.value;

        if (is.ident) {
            symbol = symbols[value] || symbols['(ident)'];
        }
        else if (is.operator) {
            if (!(symbol = symbols[value])) {
                me.syntaxError(token.at, 'Unknown operator "' + value + '"');
            }

            name = token.name;
        }
        else if (is.literal) {
            symbol = symbols['(literal)'];
        }
        else {
            me.syntaxError(token.at, 'Unexpected token');
        }

        me.token = symbol = Ext.Object.chain(symbol);
        symbol.at = index;
        symbol.is = is;
        symbol.value = value;

        if (!symbol.arity) {
            symbol.arity = token.type;
        }

        if (name) {
            symbol.name = name;
        }

        return symbol;
    },

    expect: function(expected) {
        var token = this.token;

        if (expected !== token.id) {
            this.syntaxError(token.at, 'Expected "' + expected + '"');
        }

        return this;
    },

    /**
     *
     * @param {Number} [rightPriority=0] The precedence of the current operator.
     * @return {Ext.parse.Symbol} The parsed expression tree.
     */
    parseExpression: function(rightPriority) {
        var me = this,
            token = me.token,
            left;

        rightPriority = rightPriority || 0;

        me.advance();

        left = token.nud();

        while (rightPriority < (token = me.token).priority) {
            me.advance();
            left = token.led(left);
        }

        return left;
    },

    /**
     * Resets this parser given the text to parse or a `Tokenizer`.
     * @param {String} text
     * @param {Number} [pos=0] The character position at which to start.
     * @param {Number} [end] The index of the first character beyond the token range.
     * @return {Ext.parse.Parser}
     */
    reset: function(text, pos, end) {
        var me = this;

        me.error = me.token = null;
        me.tokenizer.reset(text, pos, end);

        me.advance(); // kick start this.token

        return me;
    },

    /**
     * This method is called when a syntax error is encountered. It updates `error`
     * and returns the error token.
     * @param {Number} at The index of the syntax error (optional).
     * @param {String} message The error message.
     * @return {Object} The error token.
     */
    syntaxError: function(at, message) {
        if (typeof at === 'string') {
            message = at;
            at = this.pos;
        }

        // eslint-disable-next-line vars-on-top
        var suffix = (at == null) ? '' : (' (at index ' + at + ')'),
            error = new Error(message + suffix);

        error.type = 'error';

        if (suffix) {
            error.at = at;
        }

        throw this.error = error;
    },

    privates: {
        /**
         * This property is set to an `Error` instance if the parser encounters a syntax
         * error.
         * @property {Object} error
         * @readonly
         */
        error: null,

        addSymbol: function(id, config, type, update) {
            var symbols = this.symbols,
                symbol = symbols[id],
                cfg, length, i;

            if (symbol) {
                // If the symbol was already defined then we need to update it
                // we either use the config provided in the symbol definition
                // or we use the `update` param to build a config object.
                // We usually need to update either `led` or `nud` function
                if (typeof config === 'object') {
                    cfg = config;
                }
                else if (update && type) {
                    update = Ext.Array.from(update);
                    length = update.length;
                    cfg = {};

                    for (i = 0; i < length; i++) {
                        cfg[update[i]] = type.prototype[update[i]];
                    }
                }
                else {
                    return symbol;
                }

                symbol.update(cfg);
            }
            else {
                if (config && config.xclass) {
                    type = Ext.ClassManager.get(config.xclass);
                }
                else {
                    type = type || Ext.parse.Symbol;
                }

                symbols[id] = symbol = new type(id, config);
                symbol.parser = this;
            }

            return symbol;
        },

        addSymbols: function(symbols, type, update) {
            var id;

            for (id in symbols) {
                this.addSymbol(id, symbols[id], type, update);
            }
        },

        applyConstants: function(constants) {
            this.addSymbols(constants, Ext.parse.symbol.Constant, 'nud');
        },

        applyInfix: function(operators) {
            this.addSymbols(operators, Ext.parse.symbol.Infix, 'led');
        },

        applyInfixRight: function(operators) {
            this.addSymbols(operators, Ext.parse.symbol.InfixRight, 'led');
        },

        applyPrefix: function(operators) {
            this.addSymbols(operators, Ext.parse.symbol.Prefix, 'nud');
        },

        applySymbols: function(symbols) {
            this.addSymbols(symbols);
        },

        applyTokenizer: function(config) {
            var ret = config;

            if (config && !config.isTokenizer) {
                ret = new Ext.parse.Tokenizer(config);
            }

            this.tokenizer = ret;
        }
    }
};
});
