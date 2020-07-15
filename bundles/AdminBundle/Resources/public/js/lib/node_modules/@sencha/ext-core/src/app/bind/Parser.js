/**
 * This class parses bind template format expressions.
 * @private
 */
Ext.define('Ext.app.bind.Parser', {
    extend: 'Ext.parse.Parser',

    requires: [
        'Ext.util.Format'
    ],

    infix: {
        ':': {
            priority: 70, // bind tighter than multiplication

            //<debug>
            dump: function() {
                var me = this,
                    ret = {
                        at: me.at,
                        arity: me.arity,
                        value: me.value,
                        operand: me.operand.dump(),
                        fmt: []
                    },
                    fmt = me.fmt,
                    i;

                for (i = 0; i < fmt.length; ++i) {
                    ret.fmt.push(fmt[i].dump());
                }

                return ret;
            },
            //</debug>

            led: function(left) {
                // We parse a sequence of ":" separated formatter expressions (like a
                // traditional "," operator) and gather the sequence in our "fmt" array
                var me = this;

                me.arity = 'formatter';
                me.operand = left;
                me.fmt = me.parser.parseFmt();

                return me;
            }
        },

        '?': {
            priority: 20,

            led: function(left) {
                var me = this,
                    parser = me.parser,
                    symbol = parser.symbols[':'],
                    temp;

                me.condition = left;

                // temporarily set priority of `:` symbol to 0
                temp = symbol.priority;
                symbol.priority = 0;

                me.tv = parser.parseExpression(0);
                me.parser.advance(':');

                // restore priority of `:`
                symbol.priority = temp;

                me.fv = parser.parseExpression(0);
                me.arity = 'ternary';

                return me;
            }
        }
    },

    symbols: {
        '(': {
            nud: function() {
                // Handles parenthesized expressions
                var parser = this.parser,
                    symbol = parser.symbols[':'],
                    ret, temp;

                // temporarily set priority of `:` symbol to 70 to correctly extract formatters 
                // inside parens
                temp = symbol.priority;
                symbol.priority = 70;
                ret = parser.parseExpression();

                parser.advance(")");
                // restore priority of `:`
                symbol.priority = temp;

                return ret;
            }

        }
    },

    prefix: {
        '@': 0
    },

    tokenizer: {
        operators: {
            '@': 'at',
            '?': 'qmark',
            '===': 'feq',
            '!==': 'fneq',
            '==': 'eq',
            '!=': 'neq',
            '<': 'lt',
            '<=': 'lte',
            '>': 'gt',
            '>=': 'gte',
            '&&': 'and',
            '||': 'or'
        }
    },

    /**
     * Parses the expression from the current position and compiles it as a function.
     * The expression tokens are stored in the provided arguments.
     *
     * Called by Ext.app.bind.Template.
     *
     * @param {Array} tokens
     * @param {Object} tokensMaps
     * @return {Function}
     */
    compileExpression: function(tokens, tokensMaps) {
        var me = this,
            debug, fn;

        me.tokens = tokens;
        me.tokensMap = tokensMaps;

        //<debug>
        debug = me.token.value === '@' && me.tokenizer.peek();

        if (debug) {
            debug = debug.value === 'debugger';

            if (debug) {
                me.advance();
                me.advance();
            }
        }
        //</debug>

        fn = me.parseSlot(me.parseExpression(), debug);

        me.tokens = me.tokensMap = null;

        return fn;
    },

    /**
     * Parses the chained format functions and compiles them as a function.
     *
     * Called by the grid column formatter.
     *
     * @return {Function}
     */
    compileFormat: function() {
        var me = this,
            fn;

        //<debug>
        try {
        //</debug>
            fn = me.parseSlot({
                arity: 'formatter',
                fmt: me.parseFmt(),
                operand: {
                    arity: 'ident',
                    value: 'dummy'
                }
            });
            me.expect('(end)');
        //<debug>
        }
        catch (e) {
            Ext.raise('Invalid format expression: "' + me.tokenizer.text + '"');
        }
        //</debug>

        return fn;
    },

    privates: {
        // Chrome really likes "new Function" to realize the code block (as in it is
        // 2x-3x faster to call it than using eval), but Firefox chokes on it badly.
        // IE and Opera are also fine with the "new Function" technique.
        useEval: Ext.isGecko,
        escapeRe: /(["'\\])/g,

        /**
         * Parses a series of ":" delimited format expressions.
         * @return {Ext.parse.Symbol[]}
         * @private
         */
        parseFmt: function() {
            // We parse a sequence of ":" separated formatter expressions (like a
            // traditional "," operator)
            var me = this,
                fmt = [],
                priority = me.symbols[':'].priority,
                expr;

            do {
                if (fmt.length) {
                    me.advance();
                }

                expr = me.parseExpression(priority);

                if (expr.isIdent || expr.isInvoke) {
                    fmt.push(expr);
                }
                else {
                    me.syntaxError(expr.at, 'Expected formatter name');
                }
            } while (me.token.id === ':');

            return fmt;
        },

        /**
         * Parses the expression tree and compiles it as a function
         *
         * @param expr
         * @param {Boolean} debug
         * @return {Function}
         * @private
         */
        parseSlot: function(expr, debug) {
            var me = this,
                defs = [],
                body = [],
                tokens = me.tokens || [],
                fn, code, i, length, temp;

            me.definitions = defs;
            me.body = body;

            body.push('return ' + me.compile(expr) + ';');

            // now we have the tokens
            length = tokens.length;
            code = 'var fm = Ext.util.Format,\nme,';
            temp = 'var a = Ext.Array.from(values);\nme = scope;\n';

            if (tokens.length) {
                for (i = 0; i < length; i++) {
                    code += 'v' + i + ((i === length - 1) ? ';' : ',');
                    temp += 'v' + i + ' = a[' + i + ']; ';
                }
            }
            else {
                code += 'v0;';
                temp += 'v0 = a[0];';
            }

            defs = Ext.Array.insert(defs, 0, [code]);
            body = Ext.Array.insert(body, 0, [temp]);
            body = body.join('\n');

            //<debug>
            if (debug) {
                body = 'debugger;\n' + body;
            }
            //</debug>

            defs.push(
                (me.useEval ? '$=' : 'return') + ' function (values, scope) {',
                body,
                '}'
            );

            code = defs.join('\n');

            fn = me.useEval ? me.evalFn(code) : (new Function('Ext', code))(Ext);

            me.definitions = me.body = null;

            return fn;
        },

        /**
         * Compiles the specified symbol
         *
         * @param expr
         * @return {String}
         * @private
         */
        compile: function(expr) {
            var me = this,
                v;

            switch (expr.arity) {
                case 'ident':
                    // identifiers are our expression's tokens
                    return me.addToken(expr.value);

                case 'literal':
                    v = expr.value;

                    // strings need to be escaped before adding them to formula
                    return (typeof v === 'string')
                        ? '"' + String(v).replace(me.escapeRe, '\\$1') + '"'
                        : v;

                case 'unary':
                    return me.compileUnary(expr);

                case 'binary':
                    return me.compileBinary(expr);

                case 'ternary':
                    return me.compileTernary(expr);

                case 'formatter':
                    return me.compileFormatter(expr);

            }

            return this.syntaxError(expr.at, 'Compile error! Unknown symbol');
        },

        /**
         * Compiles unary symbol
         *
         * @param expr
         * @return {String}
         * @private
         */
        compileUnary: function(expr) {
            var v = expr.value,
                op = expr.operand;

            if (v === '!' || v === '-' || v === '+') {
                return v + '(' + this.compile(op) + ')';
            }
            else if (v === '@') {
                // @ should be used to prefix global identifiers and nothing else
                if (!op.isIdent) {
                    return this.syntaxError(expr.at, 'Compile error! Unexpected symbol');
                }

                return op.value;
            }

            return '';
        },

        /**
         * Compiles binary symbol
         *
         * @param expr
         * @return {String}
         * @private
         */
        compileBinary: function(expr) {
            return '(' + this.compile(expr.lhs) + ' ' + expr.value + ' ' + this.compile(expr.rhs) +
                   ')';
        },

        /**
         * Compiles ternary symbol
         *
         * @param expr
         * @return {String}
         * @private
         */
        compileTernary: function(expr) {
            return '(' + this.compile(expr.condition) + ' ? ' + this.compile(expr.tv) + ' : ' +
                   this.compile(expr.fv) + ')';
        },

        /**
         * Compiles formatter symbol
         *
         * @param expr
         * @return {String}
         * @private
         */
        compileFormatter: function(expr) {
            var me = this,
                fmt = expr.fmt,
                length = fmt.length,
                body = [
                    'var ret;'
                ],
                i;

            if (fmt.length) {
                body.push('ret = ' + me.compileFormatFn(fmt[0], me.compile(expr.operand)) + ';');

                for (i = 1; i < length; i++) {
                    body.push('ret = ' + me.compileFormatFn(fmt[i], 'ret') + ';');
                }
            }

            body.push('return ret;');

            return me.addFn(body.join('\n'));
        },

        /**
         * Compiles a single format symbol using `value` as the first argument
         *
         * @param expr
         * @param value
         * @return {String}
         * @private
         */
        compileFormatFn: function(expr, value) {
            var fmt,
                args = [],
                code = '',
                length, i;

            if (expr.isIdent) {
                // the function has no arguments
                fmt = expr.value;
            }
            else if (expr.isInvoke) {
                fmt = expr.operand.value;
                args = expr.args;
            }

            if (fmt.substring(0, 5) === 'this.') {
                fmt = 'me.' + fmt.substring(5);
            }
            else {
                if (!(fmt in Ext.util.Format)) {
                    return this.syntaxError(expr.at, 'Compile error! Invalid format specified "' +
                                            fmt + '"');
                }

                fmt = 'fm.' + fmt;
            }

            code += value;
            length = args.length;

            for (i = 0; i < length; i++) {
                code += ', ' + this.compile(args[i]);
            }

            return fmt + '(' + code + ')';
        },

        /**
         * Adds a new function to the final compiled function
         * @param body
         * @return {string} Name of the function
         * @private
         */
        addFn: function(body) {
            var defs = this.definitions,
                name = 'f' + defs.length;

            defs.push(
                'function ' + name + '() {',
                body,
                '}'
            );

            return name + '()';
        },

        /**
         * Evaluates a function
         * @param $
         * @return {Function}
         * @private
         */
        evalFn: function($) {
            eval($);

            return $;
        },

        /**
         * Adds the specified expression token to the internal tokens
         * @param token
         * @return {string} Name of the variable assigned for this token in the compiled function
         * @private
         */
        addToken: function(token) {
            var tokensMap = this.tokensMap,
                tokens = this.tokens,
                pos = 0;

            // token can be ignored when this function is called via `compileFormatFn`
            if (tokensMap && tokens) {
                if (token in tokensMap) {
                    pos = tokensMap[token];
                }
                else {
                    tokensMap[token] = pos = tokens.length;
                    tokens.push(token);
                }
            }

            return 'v' + pos;
        }
    }
});
