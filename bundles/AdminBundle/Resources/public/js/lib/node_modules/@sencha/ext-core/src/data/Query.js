/**
 * This class is a filter that compiles from an SQL-like expression. For example:
 *
 *      store.addFilter(new Ext.data.Query('name like "Bob" or age < 20'));
 *
 * Queries can also be assigned an `id`:
 *
 *      store.addFilter(new Ext.data.Query({
 *          id: 'myquery',
 *          source: 'name like "Bob" or age < 20'
 *      ));
 *
 * ## Query Syntax
 *
 * The syntax for a query is SQL-like. The goal of the query syntax is to be as natural
 * to end-users as possible and therefore does not exactly match JavaScript.
 *
 * ### Keyword Operators
 *
 *  - `and` or `&&` - Logical AND
 *  - `or` or `||` - Logical OR
 *  - `like` - String containment or regex match
 *  - `in` - Set membership
 *  - `not` or `!` - Logical negation
 *  - `between` - Bounds check a value (`age between 18 and 99`)
 *
 * #### The `like` Operator
 *
 * There are several forms of `like`. The first uses a simple string on the right-side:
 *
 *      name like "Bob"
 *
 * This expression evaluates as `true` if the `name` contains the substring `'Bob'`
 * (ignoring case).
 *
 * The second form will be more typical of those familiar with SQL. It is when the
 * right-side uses the SQL `%` or `_` wildcards (or the shell `*` or `?` wildcards) and/or
 * character sets (such as `'[a-f]'` and `'[^abc]'`):
 *
 *      name like "[BR]ob%"
 *
 * If any wildcards are used, the typical SQL meaning is assumed (strict match, including
 * case).
 *
 * The right-side can also use shell wildcards `'*'` or `'?'` instead of SQL wildcards.
 *
 * These wildcards can be escaped with a backslash (`\`) character (the `escape` keyword
 * is not supported).
 *
 *      text like 'To be or not to be\?'
 *
 * The final form of `like` is when the right-side is a regular expression:
 *
 *      name like /^Bob/i
 *
 * This form uses the `test()` method of the `RegExp` to match the value of `name`.
 *
 * #### The `in` Operator
 *
 * This operator accepts a parenthesized list of values and evaluates to `true` if the
 * left-side value matches an item in the right-side list:
 *
 *      name in ("Bob", 'Robert')
 *
 * ### Relational Operators
 *
 *  - `<`
 *  - `<=`
 *  - `>`
 *  - `>=`
 *
 * ### Equality and Inequality
 *
 *  - `=` - Equality after conversion (like `==` in JavaScript)
 *  - `==` or `===` - Strict equality (like `===` in JavaScript)
 *  - `!=` or `<>` - Inequality after conversion (like `!=` in JavaScript)
 *  - `!==` - Strict inequality (like `!==` in JavaScript)
 *
 * ### Helper Functions
 *
 * The following functions can be used in a query:
 *
 *  - `abs(x)` - Absolute value of `x`
 *  - `avg(...)` - The average of all parameters.
 *  - `date(d)` - Converts the argument into a date.
 *  - `lower(s)` - The lower-case conversion of the given string.
 *  - `max(...)` - The maximum value of all parameters.
 *  - `min(...)` - The minimum value of all parameters.
 *  - `sum(...)` - The sum of all parameters.
 *  - `upper(s)` - The upper-case conversion of the given string.
 *
 * These functions are used as needed in queries, such as:
 *
 *      upper(name) = 'BOB'
 *
 * @since 6.7.0
 */
Ext.define('Ext.data.Query', {
    extend: 'Ext.util.BasicFilter',

    mixins: [
        'Ext.mixin.Factoryable',
        'Ext.data.query.Compiler',
        'Ext.data.query.Converter',
        'Ext.data.query.Stringifier'
    ],

    alias: 'query.default',

    requires: [
        'Ext.data.query.Parser'
    ],

    config: {
        /**
         * @cfg {"ast"/"filters"/"query"} format
         */
        format: 'ast',

        /**
         * @cfg {Object} functions
         * This config contains the methods that will be made available to queries. To
         * add a custom function:
         *
         *      Ext.define('MyQuery', {
         *          extend: 'Ext.data.Query',
         *
         *          functions: {
         *              round: function (x) {
         *                  return Math.round(x);
         *              },
         *
         *              // When a function name ends with "..." it is called
         *              // with the arguments as an array.
         *              //
         *              'concat...': function (args) {
         *                  return args.join('');
         *              }
         *          }
         *      });
         */
        functions: {
            cached: true,
            $value: {
                abs: function(arg) {
                    return Math.abs(arg);
                },

                'avg...': function(args) {
                    var count = 0,
                        sum = 0,
                        i = args.length,
                        v;

                    for (; i-- > 0; /* empty */) {
                        v = args[i];

                        if (v != null) {
                            sum += v;
                            ++count;
                        }
                    }

                    return count ? sum / count : 0;
                },

                date: function(arg) {
                    return (arg instanceof Date) ? arg : Ext.Date.parse(arg);
                },

                lower: function(arg) {
                    return (arg == null) ? '' : String(arg).toLowerCase();
                },

                'max...': function(args) {
                    var ret = null,
                        i = args.length,
                        v;

                    for (; i-- > 0; /* empty */) {
                        v = args[i];

                        if (v != null) {
                            ret = (ret === null) ? v : (ret < v ? v : ret);
                        }
                    }

                    return ret;
                },

                'min...': function(args) {
                    var ret = null,
                        i = args.length,
                        v;

                    for (; i-- > 0; /* empty */) {
                        v = args[i];

                        if (v != null) {
                            ret = (ret === null) ? v : (ret < v ? ret : v);
                        }
                    }

                    return ret;
                },

                'sum...': function(args) {
                    var ret = null,
                        i = args.length,
                        v;

                    for (; i-- > 0; /* empty */) {
                        v = args[i];

                        if (v != null) {
                            ret = (ret === null) ? v : (ret + v);
                        }
                    }

                    return ret === null ? 0 : ret;
                },

                upper: function(arg) {
                    return (arg == null) ? '' : String(arg).toUpperCase();
                }
            }
        },

        /**
         * @cfg {String} source
         * The source text of this query. See {@link Ext.data.Query class documentation}
         * for syntax details.
         */
        source: ''
    },

    ast: null,
    error: null,
    generation: 0,

    constructor: function(config) {
        if (typeof config === 'string') {
            config = {
                source: config
            };
        }

        // eslint-disable-next-line vars-on-top
        var parser = Ext.data.query.Parser.fly();

        this.symbols = parser.symbols;

        parser.release();

        this.callParent([ config ]);
    },

    filter: function(item) {
        var me = this,
            error = me.error;

        if (error) {
            throw error;
        }

        return !!me.fn(item);
    },

    /**
     * This method should be called if the `ast` has been manipulated directly.
     */
    refresh: function() {
        ++this.generation;
        this.compile();  // assigns me.fn
    },

    serialize: function() {
        var me = this,
            format = me.getFormat(),
            serializer = me.getSerializer(),
            ret, serialized;

        switch (format) {
            case 'ast':
                ret = me.ast;

                if (serializer) {
                    ret = Ext.clone(ret);
                }

                break;

            case 'filters':
                ret = me.getFilters() || null;
                break;

            case 'query':
                ret = me.toString();
                break;
        }

        if (ret && serializer) {
            serialized = serializer.call(this, ret);

            if (serialized) {
                ret = serialized;
            }
        }

        return ret;
    },

    serializeTo: function(out) {
        var filters = this.serialize(),
            ret;

        if (filters && filters.length) {
            out.push.apply(out, filters);

            ret = true;
        }

        return ret;
    },

    sync: function() {
        var me = this,
            fn = me.fn;

        if (!fn || fn.generation !== me.generation) {
            me.compile();
        }
    },

    toString: function() {
        var ast = this.ast;

        return ast ? this.stringify(ast) : '';
    },

    //------------------------------------------------------------------------
    // Configs

    // format

    //<debug>
    validFormatsRe: /^(ast|filters|query)$/,

    applyFormat: function(format) {
        if (!this.validFormatsRe.test(format)) {
            Ext.raise('Invalid query format');
        }

        return format;
    },
    //</debug>

    // functions

    applyFunctions: function(funcs) {
        var ret = {},
            vargsRe = this.vargsRe,
            def, key, name;

        for (key in funcs) {
            def = {
                fn: funcs[name = key],
                vargs: vargsRe.test(key)
            };

            if (def.vargs) {
                name = key.substr(0, key.length - 3); // remove '...'
            }

            ret[name.toLowerCase()] = def;
        }

        return ret;
    },

    // source

    applySource: function(source) {
        if (source) {
            return source;
        }

        ++this.generation;
        this.ast = null;

        this.compile();  // assigns me.fn
    },

    updateSource: function(source) {
        var me = this,
            parser = Ext.data.query.Parser.fly(source);

        ++me.generation;

        try {
            me.error = me.fn = null;
            me.ast = parser.parse();
        }
        catch (e) {
            me.error = e;
            e.message = 'Failed to parse: ' + e.message;
            throw e;
        }
        finally {
            parser.release();
        }

        me.compile();  // assigns me.fn
    },

    //-------------------------------------------------------------------------
    privates: {
        operatorTypeMap: {
            /* eslint-disable no-multi-spaces */
            /* eslint-disable key-spacing */
            //    [ arity,      JS-operator,    Query-operator ]
            and:  ['binary',    '&&',           'and' ],
            or:   ['binary',    '||',           'or' ],

            eq:   ['binary',    '==',           '=' ],
            ge:   ['binary',    '>=',           null ],
            gt:   ['binary',    '>',            null ],
            le:   ['binary',    '<=',           null ],
            lt:   ['binary',    '<',            null ],
            ne:   ['binary',    '!=',           null ],

            add:  ['binary',    '+',            null ],
            div:  ['binary',    '/',            null ],
            mul:  ['binary',    '*',            null ],
            sub:  ['binary',    '-',            null ],

            'in':   ['binary',    null,           'in' ],
            like: ['binary',    null,           'like' ],

            seq:  ['binary',    '===',          '==' ],
            sne:  ['binary',    '!==',          null ],

            neg:  ['unary',     '-',            null ],
            not:  ['unary',     '!',            null ]
            /* eslint-enable no-multi-spaces */
            /* eslint-enable key-spacing */
        },

        vargsRe: /\.\.\.$/,

        getOperatorType: function(op) {
            var map = this.operatorTypeMap,
                key;

            for (key in map) {
                if (map[key][1] === op || map[key][2] === op) {
                    return key;
                }
            }

            //<debug>
            Ext.raise('Unrecognized filter operator: "' + op + '"');
            //</debug>

            return null;
        }
    }
});
