/**
 * This class is used to parse a string into a series of tokens. The syntax of the string
 * is JavaScript-like. This class is useful for creating higher-level parsers to allow
 * them to assemble tokens into a meaningful language (such as bind properties).
 *
 * The following set of punctuation characters are supported:
 *
 *      + - * / ! , : [ ] { } ( )
 *
 * This class does not currently separate the dot operator but instead includes it in a
 * single "ident" token. Whitespace between tokens is skipped.
 *
 * Tokens are parsed on-demand when `next` or `peek` are called. As much as possible,
 * the returned tokens are reused (e.g., to represent tokens like ":" the same object is
 * always returned). For tokens that contain values, a new object must be created to
 * return the value. Even so, the `is` property that describes the data is a reused object
 * in all cases.
 *
 *      var tokenizer;  // see below for getting instance
 *
 *      for (;;) {
 *          if (!(token = tokenizer.next())) {
 *              // When null is returned, there are no more tokens
 *
 *              break;
 *          }
 *
 *          var is = token.is;  // the token's classification object
 *
 *          if (is.error) {
 *              // Once an error is encountered, it will always be returned by
 *              // peek or next. The error is cleared by calling reset().
 *
 *              console.log('Syntax error', token.message);
 *              break;
 *          }
 *
 *          if (is.ident) {
 *              // an identifier...
 *              // use token.value to access the name or dot-path
 *
 *              var t = tokenizer.peek();  // don't consume next token (yet)
 *
 *              if (t && t.is.parenOpen) {
 *                  tokenizer.next();  // we'll take this one
 *
 *                  parseThingsInParens();
 *
 *                  t = tokenizer.next();
 *
 *                  mustBeCloseParen(t);
 *              }
 *          }
 *          else if (is.literal) {
 *              // a literal value (null, true/false, string, number)
 *              // use token.value to access the value
 *          }
 *          else if (is.at) {
 *              // @
 *          }
 *      }
 *
 * For details on the returned token see the `peek` method.
 *
 * There is a pool of flyweight instances to reduce memory allocation.
 *
 *      var tokenizer = Ext.parse.Tokenizer.fly('some.thing:foo()');
 *
 *      // use tokenizer (see above)
 *
 *      tokenizer.release();  // returns the fly to the flyweigt pool
 *
 * The `release` method returns the flyweight to the pool for later reuse. Failure to call
 * `release` will leave the flyweight empty which simply forces the `fly` method to always
 * create new instances on each call.
 *
 * A tokenizer can also be reused by calling its `reset` method and giving it new text to
 * tokenize.
 *
 *      this.tokenizer = new Ext.parse.Tokenizer();
 *
 *      // Later...
 *
 *      this.tokenizer.reset('some.thing:foo()');
 *
 *      // use tokenizer (see above)
 *
 *      this.tokenizer.reset();
 *
 * The final call to `reset` is optional but will avoid holding large text strings or
 * parsed results that rae no longer needed.
 *
 * @private
 */
Ext.define('Ext.parse.Tokenizer', function(Tokenizer) {
    var flyweights = (Tokenizer.flyweights = []),
        BOOLEAN = { literal: true, boolean: true, type: 'boolean' },
        ERROR = { error: true },
        IDENT = { ident: true },
        LITERAL = { literal: true },
        NULL = { literal: true, nil: true },
        NUMBER = { literal: true, number: true, type: 'number' },
        STRING = { literal: true, string: true, type: 'string' };

/* eslint-disable indent */
return {
    extend: 'Ext.util.Fly',

    isTokenizer: true,

    statics: {
        BOOLEAN: BOOLEAN,
        ERROR: ERROR,
        IDENT: IDENT,
        LITERAL: LITERAL,
        NULL: NULL,
        NUMBER: NUMBER,
        STRING: STRING
    },

    config: {
        /**
         * @cfg {Object} keywords
         * A map of keywords that should be mapped to other token types. By default the
         * `null`, `true` and `false` keywords are mapped to their respective literal
         * value tokens.
         */
        keywords: {
            'null': { type: 'literal', is: NULL, value: null },
            'false': { type: 'literal', is: BOOLEAN, value: false },
            'true': { type: 'literal', is: BOOLEAN, value: true }
        },

        /**
         * @cfg {Object} operators
         * A map of operators and their names. The keys are the operator text and the
         * name (the values) are placed in the token's `is` object as `true`.
         */
        operators: {
            '+': 'plus',
            '-': 'minus',
            '*': 'multiply',
            '/': 'divide',
            '!': 'not',
            ',': 'comma',
            ':': 'colon',
            '[': 'arrayOpen',
            ']': 'arrayClose',
            '{': 'curlyOpen',
            '}': 'curlyClose',
            '(': 'parenOpen',
            ')': 'parenClose'
        },

        patterns: null
    },

    /**
     * This property is set to an `Error` instance if the parser encounters a syntax
     * error.
     * @property {Object} error
     * @readonly
     */
    error: null,

    /**
     * This property is set to the character index of the current token. This value can
     * be captured immediately after calling the `peek` or `next` method to know the
     * index of the returned token. This value is not included in the returned token to
     * allow those tokens that could otherwise be immutable to be reused.
     * @property {Number} index
     * @readonly
     */
    index: -1,

    constructor: function(config) {
        this.operators = {};
        this.patterns = [];

        this.initConfig(config);
    },

    /**
     * Advance the token stream and return the next token. See `{@link #peek}` for a
     * description of the returned token.
     *
     * After calling this method, the next call to it or `peek` will not return the same
     * token but instead the token that follows the one returned.
     *
     * @return {Object} The next token in the stream (now consumed).
     */
    next: function() {
        var token = this.peek();

        this.head = undefined;  // indicates that more parsing is needed (see peek)

        return token;
    },

    /**
     * Peeks at the next token stream and returns it. The token remains as the next token
     * and will be returned again by the next call to this method or `next`.
     *
     * At the end of the token stream, the token returned will be `null`.
     *
     * If a syntax error is encountered, the returned token will be an `Error` object. It
     * has the standard `message` property and also additional properties to make it more
     * like a standard token: `error: true`, `type: 'error'` and `at` (the index in the
     * string where the syntax error started.
     *
     * @return {Object} The next token in the stream (not yet consumed).
     *
     * @return {String} return.type The type of the token. This will be one of the
     * following values: `ident`, `literal` and `error` or the text of a operator
     * (i.e., "@", "!", ",", ":", "[", "]", "{", "}", "(" or ")").
     *
     * @return {String} return.value The value of a `"literal"` token.
     *
     * @return {Object} return.is An object containing boolean properties based on type.
     * @return {Boolean} return.is.literal True if the token is a literal value.
     * @return {Boolean} return.is.boolean True if the token is a literal boolean value.
     * @return {Boolean} return.is.error True if the token is an error.
     * @return {Boolean} return.is.ident True if the token is an identifier.
     * @return {Boolean} return.is.nil True if the token is the `null` keyword.
     * @return {Boolean} return.is.number True if the token is a number literal.
     * @return {Boolean} return.is.string True if the token is a string literal.
     * @return {Boolean} return.is.operator True if the token is a operator (i.e.,
     * "@!,:[]{}()"). operators will also have one of these boolean proprieties, in
     * the respective order: `at`, `not`, `comma`, `colon`, `arrayOpen`, `arrayClose`,
     * `curlyOpen`, `curlyClose`, `parentOpen` and `parenClose`).
     */
    peek: function() {
        var me = this,
            error = me.error,
            token = me.head;

        if (error) {
            return error;
        }

        if (token === undefined) {
            me.head = token = me.advance();
        }

        return token;
    },

    /**
     * Returns this flyweight instance to the flyweight pool for reuse.
     */
    release: function() {
        this.reset();

        if (flyweights.length < Tokenizer.flyPoolSize) {
            flyweights.push(this);
        }
    },

    /**
     * Resets the tokenizer for a new string at a given offset (defaults to 0).
     *
     * @param {String} text The text to tokenize.
     * @param {Number} [pos=0] The character position at which to start.
     * @param {Number} [end] The index of the first character beyond the token range.
     * @returns {Ext.parse.Tokenizer}
     */
    reset: function(text, pos, end) {
        var me = this;

        me.error = null;
        me.head = undefined;
        me.index = -1;
        me.text = text || null;
        me.pos = pos || 0;
        me.end = (text && end == null) ? text.length : end;

        return me;
    },

    privates: {
        digitRe: /[0-9]/,
        identFirstRe: /[a-z_$]/i,
        identRe: /[0-9a-z_$]/i,
        spaceRe: /[ \t]/,

        /**
         * The index one beyond the last character of the input text. This defaults to
         * the `text.length`.
         * @property {Number} end
         * @readonly
         */
        end: 0,

        /**
         * The current token at the head of the token stream. This will be `undefined`
         * if the next token must be parsed from `text`. It is `null` if there are no
         * more tokens.
         * @property {Object} head
         * @readonly
         */
        head: undefined,

        /**
         * The current character position in the `text` from which the next token will
         * be parsed.
         * @property {Number} pos
         * @readonly
         */
        pos: 0,

        /**
         * The text to be tokenized.
         * @property {String} text
         * @readonly
         */
        text: null,

        applyOperators: function(ops) {
            var operators = this.operators,
                block, c, def, i, len, name, op;

            /*
             Builds a map one character at a time (i.e., a "trie"):

                operators: {
                    '=': {
                        '=': {
                            token: // the "==" token
                        },

                        token:  // the "=" token
                    }
                }
             */
            for (op in ops) {
                block = operators;
                name = ops[op];
                len = op.length;

                for (i = 0; i < len; ++i) {
                    c = op.charAt(i);
                    block = block[c] || (block[c] = {});
                }

                if (name) {
                    block.token = def = {
                        type: 'operator',
                        name: name,
                        value: op,
                        is: { operator: true }
                    };

                    def.is[name] = true;
                }
                else {
                    block.token = null;
                }
            }
        },

        applyPatterns: function(pat) {
            var patterns = this.patterns,
                def, extract, name, re;

            for (name in pat) {
                def = pat[name];

                extract = def.extract;
                re = def.re;

                delete def.extract;
                delete def.re;

                patterns.push({
                    name: name,
                    re: re,
                    extract: extract,
                    token: def
                });
            }
        },

        /**
         * Parses and returns the next token from `text` starting at `pos`.
         * @return {Object} The next token
         */
        advance: function() {
            var me = this,
                spaceRe = me.spaceRe,
                text = me.text,
                length = me.end,
                c;

            while (me.pos < length) {
                c = text.charAt(me.pos);

                if (spaceRe.test(c)) {
                    ++me.pos;  // consume the whitespace
                    continue;
                }

                me.index = me.pos;

                return me.parse(c);
            }

            return null;
        },

        /**
         * Parses the current token that starts with the provided character `c` and
         * located at the current `pos` in the `text`.
         * @param {String} c The current character.
         * @return {Object} The next token
         */
        parse: function(c) {
            var me = this,
                digitRe = me.digitRe,
                text = me.text,
                length = me.end,
                patterns = me.patterns,
                i, match, pat, ret;

            // Handle ".123"
            if (c === '.' && me.pos + 1 < length) {
                if (digitRe.test(text.charAt(me.pos + 1))) {
                    ret = me.parseNumber();
                }
            }

            if (!ret) {
                for (i = 0; i < patterns.length; ++i) {
                    pat = patterns[i];

                    pat.re.lastIndex = me.pos;
                    match = pat.re.exec(text);

                    if (match && match.index === me.pos) {
                        ret = Ext.apply({
                            value: pat.extract ? pat.extract(match) : match[0]
                        }, pat.token);

                        me.pos += match[0].length;
                        break;
                    }
                }
            }

            if (!ret && me.operators[c]) {
                ret = me.parseOperator(c);
            }

            if (!ret) {
                if (c === '"' || c === "'") {
                    ret = me.parseString();
                }
                else if (digitRe.test(c)) {
                    ret = me.parseNumber();
                }
                else if (me.identFirstRe.test(c)) {
                    ret = me.parseIdent();
                }
                else {
                    ret = me.syntaxError('Unexpected character');
                }
            }

            return ret;
        },

        /**
         * Parses the next identifier token.
         * @return {Object} The next token.
         */
        parseIdent: function() {
            var me = this,
                identRe = me.identRe,
                keywords = me.getKeywords(),
                includeDots = !me.operators['.'],
                text = me.text,
                start = me.pos,
                end = start,
                length = me.end,
                prev = 0,
                c, value;

            while (end < length) {
                c = text.charAt(end);

                if (includeDots && c === '.') {
                    if (prev === '.') {
                        return me.syntaxError(end, 'Unexpected dot operator');
                    }

                    ++end;
                }
                else if (identRe.test(c)) {
                    ++end;
                }
                else {
                    break;
                }

                prev = c;
            }

            if (prev === '.') {
                return me.syntaxError(end - 1, 'Unexpected dot operator');
            }

            value = text.substring(start, me.pos = end);

            return (keywords && keywords[value]) || {
                type: 'ident',
                is: IDENT,
                value: value
            };
        },

        /**
         * Parses the next number literal token.
         * @return {Object} The next token.
         */
        parseNumber: function() {
            var me = this,
                digitRe = me.digitRe,
                text = me.text,
                start = me.pos,
                length = me.end,
                c, decimal, exp, token;

            while (me.pos < length) {
                c = text.charAt(me.pos);

                if (c === '-' || c === '+') {
                    if (me.pos !== start) {
                        break;
                    }

                    ++me.pos;
                }
                else if (c === '.') {
                    if (decimal) {
                        break;
                    }

                    decimal = true;
                    ++me.pos;
                }
                else if (c === 'e' || c === 'E') {
                    if (exp) {
                        break;
                    }

                    decimal = exp = true; // exp from here on, no decimal allowed

                    c = text.charAt(++me.pos); // consume E and peek ahead

                    if (c === '-' || c === '+') {
                        ++me.pos;  // keep the exp sign
                    }
                }
                else if (digitRe.test(c)) {
                    ++me.pos;
                }
                else {
                    break;
                }
            }

            token = {
                type: 'literal',
                is: NUMBER,
                // Beware parseFloat as it will stop parsing and return what it could
                // parse. For example parseFloat('1x') == 1 whereas +'1x' == NaN.
                value: +text.substring(start, me.pos)
            };

            if (!isFinite(token.value)) {
                token = me.syntaxError(start, 'Invalid number');
            }

            return token;
        },

        parseOperator: function(c) {
            var me = this,
                block = me.operators,
                text = me.text,
                length = me.end,
                end = me.pos,
                match, matchEnd, token;

            while (block[c]) {
                block = block[c];
                token = block.token;
                ++end;

                if (token) {
                    match = token;
                    matchEnd = end;
                }

                if (end < length) {
                    c = text.charAt(end);
                }
                else {
                    break;
                }
            }

            if (match) {
                me.pos = matchEnd;
            }

            return match;
        },

        /**
         * Parses the next string literal token.
         * @return {Object} The next token.
         */
        parseString: function() {
            var me = this,
                text = me.text,
                pos = me.pos,
                start = pos,
                length = me.end,
                str = '',
                c, closed, quote;

            quote = text.charAt(pos++);

            while (pos < length) {
                c = text.charAt(pos++);

                if (c === quote) {
                    closed = true;

                    break;
                }

                if (c === '\\' && pos < length) {
                    c = text.charAt(pos++);
                }

                // Processing escapes means we cannot use substring() to pick up the
                // text as a single chunk...
                str += c;
            }

            me.pos = pos;

            if (!closed) {
                return me.syntaxError(start, 'Unterminated string');
            }

            return {
                type: 'literal',
                is: STRING,
                value: str
            };
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
            error.is = ERROR;

            if (suffix) {
                error.at = at;
            }

            return this.error = error;
        }
    }
};
});
