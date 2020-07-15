/**
 * This singleton provides the ability to convert the parse tree of an `Ext.data.Query`
 * (that is, its `ast` or Abstract Syntax Tree) into a `String`.
 * @private
 * @since 6.7.0
 */
Ext.define('Ext.data.query.Stringifier', {
    stringify: function(node) {
        var me = this,
            t = typeof node,
            type = node.type,
            operatorTypeMap = me.operatorTypeMap,
            priority = me.getPriority(node),
            stringifiers = me.stringifiers,
            op, stringifier;

        if (t === 'boolean' || t === 'number') {
            return String(node);
        }

        if (t === 'string') {
            return Ext.JSON.encode(node);
        }

        stringifier = stringifiers[type];

        if (!stringifier && type in operatorTypeMap) {
            op = operatorTypeMap[type];
            stringifier = stringifiers[op[0]];
            op = op[2] || op[1];
        }

        if (typeof stringifier === 'string') {
            stringifier = stringifiers[stringifier];
        }

        return stringifier(me, node, priority, op);
    },

    privates: {
        getPriority: function(node) {
            var symbols = this.symbols,
                operatorTypeMap = this.operatorTypeMap,
                type = node.type,
                ret = 1e9,
                op;

            if (type === 'between') {
                ret = 0;
                ret = symbols[type].priority;
            }
            else if (type === 'and' || type === 'or' || type === 'in' || type === 'like') {
                ret = symbols[type].priority;
            }
            else if (type in operatorTypeMap) {
                op = operatorTypeMap[type];
                ret = symbols[op[1]].priority;
            }

            return ret;
        },

        stringifiers: {
            and: 'or',
            or: function(me, node, priority) {
                var op = (node.type === 'or') ? ' or ' : ' and ',
                    s = '',
                    on = node.on,
                    i, lhs, parenL;

                for (i = 0; i < on.length; ++i) {
                    if (s) {
                        s += op;
                    }

                    lhs = on[i];
                    parenL = me.getPriority(lhs) < priority;

                    lhs = me.stringify(lhs);

                    if (parenL) {
                        lhs = '(' + lhs + ')';
                    }

                    s += lhs;
                }

                return s;
            },

            between: function(me, node, priority) {
                var on = node.on,
                    lhs = on[0],
                    parenL = me.getPriority(lhs) < priority,
                    i, parenR, rhs, s;

                lhs = me.stringify(lhs);

                if (parenL) {
                    lhs = '(' + lhs + ')';
                }

                s = lhs + ' between ';

                priority = me.symbols.and.priority;

                for (i = 0; i < 2; ++i) {
                    if (i) {
                        s += ' and ';
                    }

                    rhs = on[i + 1];

                    parenR = i
                        ? (rhs.type !== 'id' && !Ext.isPrimitive(rhs))
                        : (me.getPriority(rhs) < priority);

                    rhs = me.stringify(rhs);

                    if (parenR) {
                        rhs = '(' + rhs + ')';
                    }

                    s += rhs;
                }

                return s;
            },

            binary: function(me, node, priority, op) {
                var on = node.on,
                    lhs = on[0],
                    rhs = on[1],
                    parenL = me.getPriority(lhs) < priority,
                    parenR = me.getPriority(rhs) < priority;

                lhs = me.stringify(lhs);
                rhs = me.stringify(rhs);

                if (parenL) {
                    lhs = '(' + lhs + ')';
                }

                if (parenR) {
                    rhs = '(' + rhs + ')';
                }

                return lhs + ' ' + op + ' ' + rhs;
            },

            fn: function(me, node) {
                return node.fn + '(' + me.stringifyArray(node.args) + ')';
            },

            id: function(me, node) {
                return node.value;
            },

            list: function(me, node) {
                return '(' + me.stringifyArray(node.value) + ')';
            },

            regexp: function(me, node) {
                return '/' + node.value + '/' + (node.flags || '');
            },

            string: function(me, node) {
                return Ext.JSON.encode(node.value);
            },

            unary: function(me, node, priority, op) {
                var on = node.on,
                    rhs = me.stringify(on),
                    t = on.type;

                if (t !== 'fn' && t !== 'id' && t !== 'unary') {
                    rhs = '(' + rhs + ')';
                }

                return op + rhs;
            }
        },

        stringifyArray: function(array) {
            var s = '',
                i, expr;

            for (i = 0; i < array.length; ++i) {
                if (s) {
                    s += ', ';
                }

                expr = array[i];
                expr = this.stringify(expr);

                s += expr;
            }

            return s;
        }
    }
});
