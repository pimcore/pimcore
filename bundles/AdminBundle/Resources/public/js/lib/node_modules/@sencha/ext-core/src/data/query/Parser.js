/**
 * This class provides the ability to parse a `String` to produce the `ast` of an
 * `Ext.data.Query` (that is, its Abstract Syntax Tree).
 * @private
 * @since 6.7.0
 */
Ext.define('Ext.data.query.Parser', function(QueryParser) {  // eslint-disable-line brace-style
    var LIST = { list: true, literal: true, type: 'list' };

    return {
        extend: 'Ext.parse.Parser',

        tokenizer: {
            keywords: {
                and: {
                    type: 'operator',
                    name: 'and',
                    value: '&&',
                    is: { operator: true }
                },

                or: {
                    type: 'operator',
                    name: 'or',
                    value: '||',
                    is: { operator: true }
                },

                not: {
                    type: 'operator',
                    name: 'not',
                    value: '!',
                    is: { operator: true }
                },

                between: {
                    type: 'operator',
                    name: 'between',
                    value: 'between',
                    is: { operator: true }
                },

                like: {
                    type: 'operator',
                    name: 'like',
                    value: 'like',
                    is: { operator: true }
                },

                'in': {
                    type: 'operator',
                    name: 'in',
                    value: 'in',
                    is: { operator: true }
                }
            },

            /* eslint-disable key-spacing */
            operators: {
                '=':   'eq',
                '==':  'seq',
                '===': 'seq',
                '!==': 'sne',
                '!=':  'neq',
                '<>':  'neq',
                '<':   'lt',
                '<=':  'lte',
                '>':   'gt',
                '>=':  'gte',
                '&&':  'and',
                '||':  'or',

                ',': 'comma'
            },
            /* eslint-enable key-spacing */

            patterns: {
                regex: {
                    type: 'literal',
                    is: { literal: true, regexp: true, type: 'regexp' },
                    re: /\/(?!\/)((?:\[.+?]|\\.|[^/\\\r\n])+)\/([gimyu]{0,5})/g,

                    extract: function(match) {
                        var body = match[1],
                            flags = match[2];

                        return flags ? [body, flags] : body;
                    }
                }
            }
        },

        infix: {
            '=': 40,
            '<>': 40,
            like: 40,

            // TODO '**': 90,  // exponent

            between: {
                priority: 70,

                led: function(left) {
                    var me = this,
                        parser = me.parser;

                    me.arity = 'between';
                    me.operand = left;
                    me.low = parser.parseExpression(parser.symbols.and.priority);
                    parser.advance('&&');
                    me.high = parser.parseExpression(80);

                    return me;
                }
            },

            'in': {
                priority: 40,

                led: function(left) {
                    var me = this,
                        parser = me.parser;

                    parser.advance('(');

                    me.arity = 'binary';
                    me.lhs = left;
                    me.rhs = {
                        arity: 'literal',
                        value: parser.parseList(),
                        is: LIST
                    };

                    parser.advance(')');

                    return me;
                }
            }
        },

        infixRight: {
            'and': 30,
            'or': 30
        },

        prefix: {
            not: 0
        },

        parse: function() {
            var expr = this.parseExpression();

            return this.convert(expr);
        },

        privates: {
            opCodes: {
                binary: {
                    '=': 'eq',
                    '>': 'gt',
                    '<': 'lt',

                    '>=': 'ge',
                    '<=': 'le',
                    '!=': 'ne',
                    '<>': 'ne',

                    '+': 'add',
                    '/': 'div',
                    '*': 'mul',
                    '-': 'sub'
                },

                unary: {
                    '-': 'neg',
                    '!': 'not'
                }
            },

            convert: function(node) {
                var me = this,
                    arity = node.arity,
                    is = node.is,
                    name = node.name,
                    opCodes = me.opCodes,
                    value = node.value,
                    exprs, lhs, rhs, ret;

                switch (arity) {
                    case 'between':
                        ret = {
                            type: 'between',
                            on: [
                                me.convert(node.operand),
                                me.convert(node.low),
                                me.convert(node.high)
                            ]
                        };
                        break;

                    case 'ident':
                        ret = {
                            type: 'id',
                            value: value
                        };
                        break;

                    case 'invoke':
                        ret = {
                            type: 'fn',
                            fn: node.operand.value,
                            args: me.convertArray(node.args)
                        };
                        break;

                    case 'unary':
                        ret = {
                            type: opCodes.unary[value],
                            on: me.convert(node.operand)
                        };
                        break;

                    case 'binary':
                        if (name === 'and' || name === 'or') {
                            lhs = me.convert(node.lhs);
                            rhs = me.convert(node.rhs);

                            if (rhs.type === name) {
                                exprs = rhs.on;
                                exprs.unshift(lhs);
                            }
                            else {
                                exprs = [lhs, rhs];
                            }

                            ret = {
                                type: name,
                                on: exprs
                            };
                        }
                        else {
                            if (value === 'or') {
                                value = '||';
                            }

                            ret = {
                                type: opCodes.binary[value] || name,
                                on: [
                                    me.convert(node.lhs),
                                    me.convert(node.rhs)
                                ]
                            };

                            if (name === 'like') {
                                ret.on[1] = me.likeToRe(ret.on[1], node.rhs.at);
                            }
                        }

                        break;

                    case 'literal':
                        if (is.string || is.number || is.boolean) {
                            ret = value;
                        }
                        else {
                            ret = {
                                type: is.type,
                                value: value
                            };

                            if (is.list) {
                                ret.value = me.convertArray(value);
                            }
                            else if (is.regexp && typeof value !== 'string') {
                                ret.value = value[0];
                                ret.flags = value[1];
                            }
                        }

                        break;
                }

                if (ret && typeof ret === 'object' && !ret.type) {
                    ret.type = arity;
                }

                return ret;
            },

            convertArray: function(array) {
                var ret = [],
                    i = array.length;

                for (; i-- > 0; /* empty */) {
                    ret[i] = this.convert(array[i]);
                }

                return ret;
            },

            likeToRe: function(node, at) {
                if (typeof node === 'string') {
                    node = {
                        type: 'string',
                        value: node
                    };
                }
                else if (node.type === 'regexp') {
                    return node;
                }

                // eslint-disable-next-line vars-on-top
                var specialChars = this.specialChars || (QueryParser.prototype.specialChars =
                            Ext.Array.toMap('.+*?^$=!|:-<>[](){}\\'.split(''))),
                    like = node.value,
                    n = like.length,
                    re = '',
                    simple = true,
                    escape, c, i, start;

                outer: for (i = 0; i < n; ++i) {
                    c = like[i];

                    if (!escape) {
                        if (c === '\\') {
                            escape = c;
                            continue;
                        }

                        if (c === '*' || c === '%') {
                            re += '.*';
                            simple = false;
                            continue;
                        }

                        if (c === '?' || c === '_') {
                            re += '.';
                            simple = false;
                            continue;
                        }

                        // Some SQL-dialects (TSQL) support charsets: name like '[Bb]ob'
                        if (c === '[') {
                            re += c;
                            simple = false;
                            start = i;

                            while (++i < n) {
                                c = like[i];

                                if (escape) {
                                    re += escape + c;
                                    escape = 0;
                                }
                                else if (c === '\\') {
                                    escape = c;
                                }
                                else {
                                    re += c;

                                    if (c === ']') {
                                        continue outer;
                                    }
                                }
                            }

                            // If we fall out of the while loop we never found the close
                            // of the charset... so throw a parse error
                            this.syntaxError(start + (node.at || at || 0),
                                             'Incomplete character set');
                        }
                    }

                    escape = 0;

                    if (specialChars[c]) {
                        re += '\\';
                    }

                    re += c;
                }

                node.re = re || '.*';

                // Assume most users (at least those that don't include
                // a SQL wildcard) don't know about SQL wildcards in LIKE
                // operators... If there are no wildcards present, assume
                // the user wants a case-insensitive, substring match.
                if (simple) {
                    node.flags = 'i';
                }
                else {
                    node.re = '^' + re + '$';
                }

                return node;
            },

            parseList: function() {
                var me = this,
                    list = [];

                do {
                    if (list.length) {
                        me.advance(); // the ','
                    }

                    list.push(me.parseExpression());
                }
                while (me.token.id === ',');

                return list;
            }
        }
    };
});
