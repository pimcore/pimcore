/**
 * This singleton provides the ability to convert the parse tree of an `Ext.data.Query`
 * (that is, its `ast` or Abstract Syntax Tree) into a `Function` that accepts an object
 * or {@link Ext.data.Model record} and returns a `Boolean` if the query matches it.
 * @private
 * @since 6.7.0
 */
Ext.define('Ext.data.query.Compiler', {
    compile: function() {
        /*
            O = Operation helpers
            F = Functions

            function create (Ext, O, F) {
                return function (item) {
                    return (
                        4
                        && (

                        )
                    );
                };
            }
        */
        var me = this,
            ast = me.ast,
            body, factory, vars;

        me.error = null;

        if (!ast) {
            me.fn = Ext.returnTrue;
        }
        else {
            body = [
                'return function (item) {',
                '\tvar rec = item.isEntity && item;',
                '\treturn '
            ];
            vars = [];

            me.query = me;
            me.assemble(body, vars, '\t', ast);

            body.push('}');
            body = vars.concat(body).join('\n');

            try {
                factory = new Function('Ext', 'O', 'F', body);

                me.fn = factory(Ext, me.operators, me.getFunctions());
                me.fn.generation = me.generation;
            }
            catch (e) {
                me.error = e;
                e.message = 'Failed to compile: ' + e.message;

                throw e;
            }
            finally {
                me.query = null;
            }
        }
    },

    privates: {
        asmOps: {
            '>': 'gt',
            '<': 'lt',

            '==': 'eq',
            '>=': 'ge',
            '<=': 'le',
            '!=': 'ne'
        },

        assemblers: {
            binary: function(me, body, vars, indent, node, last, childIndent) {
                var op = me.operatorTypeMap[node.type][1],
                    asmOp = me.asmOps[op],
                    operands = node.on,
                    close = '',
                    i;

                if (asmOp) {
                    body[last] += 'O.' + asmOp + '(';
                    op = ', ';
                    close = ')';
                }
                else {
                    op = ' ' + op + ' ';
                }

                body[last] += '(';

                for (i = 0; i < operands.length; ++i) {
                    if (i) {
                        body.push(indent + ')' + op + '(');
                    }

                    body.push(childIndent);

                    me.assemble(body, vars, childIndent, operands[i]);
                }

                body.push(indent + ')' + close);
            },

            between: function(me, body, vars, indent, node, last, childIndent) {
                var operands = node.on,
                    i;

                body[last] += 'O.between(';

                for (i = 0; i < 3; ++i) {
                    if (i) {
                        last = body.length - 1;
                        body[last] += ', ';
                    }

                    me.assemble(body, vars, childIndent, operands[i]);
                }

                body.push(indent + ')');
            },

            fn: function(me, body, vars, indent, node, last, childIndent) {
                var fn = node.fn.toLowerCase(),
                    func = me.query.getFunctions(),
                    exprs, i;

                //<debug>
                if (!func[fn]) {
                    Ext.raise('Unsupported function "' + node.fn + '"');
                }
                //</debug>

                func = func[fn];

                if (func.vargs) {
                    body[last] += 'F.' + fn + '.fn([';
                }
                else {
                    body[last] += 'F.' + fn + '.fn(';
                }

                exprs = node.args;

                for (i = 0; i < exprs.length; ++i) {
                    if (i) {
                        last = body.length - 1;
                        body[last] += ', ';
                    }

                    body.push(childIndent);

                    me.assemble(body, vars, childIndent, exprs[i]);
                }

                if (func.vargs) {
                    body.push(indent + '])');
                }
                else {
                    body.push(indent + ')');
                }
            },

            id: function(me, body, vars, indent, node, last, childIndent) {
                var v = node.value,
                    exprs = v.split('.');

                if (exprs.length === 1) {
                    body[last] += 'rec ? rec.interpret(' + Ext.JSON.encode(v) +
                        ') : item.' + v;
                }
                else {
                    v = 'p' + vars.length;
                    vars.push('var ' + v + ' = ' + Ext.JSON.encode(exprs) + ';');
                    body[last] += 'O.dots(item, ' + v + ')';
                }
            },

            'in': function(me, body, vars, indent, node, last, childIndent) {
                var operands = node.on;

                body[last] += 'O.in(';

                me.assemble(body, vars, childIndent, operands[0]);

                last = body.length - 1;
                body[last] += ', ';

                me.assemble(body, vars, childIndent, operands[1]);

                body.push(indent + ')');
            },

            like: function(me, body, vars, indent, node, last, childIndent) {
                var operands = node.on,
                    rhs;

                body[last] += 'O.like(';

                me.assemble(body, vars, childIndent, operands[0]);

                last = body.length - 1;
                body[last] += ', ';

                rhs = operands[1];

                if (rhs.re) {
                    rhs = {
                        type: 'regexp',
                        value: rhs.re,
                        flags: rhs.flags
                    };
                }

                me.assemble(body, vars, childIndent, rhs);

                last = body.length - 1;
                body[last] += ') ';
            },

            list: function(me, body, vars, indent, node, last, childIndent) {
                body[last] += '[';

                // eslint-disable-next-line vars-on-top
                for (var i = 0, exprs = node.value; i < exprs.length; ++i) {
                    if (i) {
                        last = body.length - 1;
                        body[last] += ', ';
                    }

                    body.push(childIndent);

                    me.assemble(body, vars, childIndent, exprs[i]);
                }

                body.push(indent + ']');
            },

            string: 'regexp',
            regexp: function(me, body, vars, indent, node, last) {
                var re = 're' + vars.length;

                vars.push('var ' + re + ' = /' + (node.re || node.value) + '/' +
                    (node.flags || '') + ';');

                body[last] += re;
            },

            unary: function(me, body, vars, indent, node, last, childIndent) {
                var op = me.operatorTypeMap[node.type][1],
                    operands = node.on;

                body[last] += op + '(';
                body.push(childIndent);

                me.assemble(body, vars, childIndent, operands);

                body.push(indent + ')');
            }
        },

        /**
         * These methods are used by the compiled function to process certain operators.
         * @private
         */
        operators: {
            between: function(val, lo, hi) {
                return lo <= val && val <= hi;
            },

            dots: function(item, names) {
                var i, ret;

                if (item.isEntity) {
                    for (ret = item, i = 0; i < names.length; ++i) {
                        if (!ret || !ret.interpret) {
                            ret = undefined; // don't return '', 0 or false
                            break;
                        }

                        ret = ret.interpret(names[i]);
                    }
                }
                else {
                    for (ret = item, i = 0; i < names.length; ++i) {
                        if (!ret) {
                            ret = undefined;
                            break;
                        }

                        ret = ret[names[i]];
                    }
                }

                return ret;
            },

            'in': function(val, values) {
                return Ext.Array.contains(values, val);
            },

            like: function(val, pat) {
                val = String(val);

                if (typeof pat === 'string') {
                    return !!val && val.toLowerCase().indexOf(pat.toLowerCase()) > -1;
                }

                return pat.test(val);
            },

            //--------------------------------

            eq: function(lhs, rhs) {
                if (lhs && rhs && (lhs instanceof Date || rhs instanceof Date)) {
                    return !Ext.Date.compare(lhs, rhs);
                }

                return lhs == rhs;  // eslint-disable-line eqeqeq
            },

            ge: function(lhs, rhs) {
                if (lhs && rhs && (lhs instanceof Date || rhs instanceof Date)) {
                    return Ext.Date.compare(lhs, rhs) >= 0;
                }

                return lhs >= rhs;
            },

            gt: function(lhs, rhs) {
                if (lhs && rhs && (lhs instanceof Date || rhs instanceof Date)) {
                    return Ext.Date.compare(lhs, rhs) > 0;
                }

                return lhs > rhs;
            },

            le: function(lhs, rhs) {
                if (lhs && rhs && (lhs instanceof Date || rhs instanceof Date)) {
                    return Ext.Date.compare(lhs, rhs) <= 0;
                }

                return lhs <= rhs;
            },

            lt: function(lhs, rhs) {
                if (lhs && rhs && (lhs instanceof Date || rhs instanceof Date)) {
                    return Ext.Date.compare(lhs, rhs) < 0;
                }

                return lhs < rhs;
            },

            ne: function(lhs, rhs) {
                if (lhs && rhs && (lhs instanceof Date || rhs instanceof Date)) {
                    return !!Ext.Date.compare(lhs, rhs);
                }

                return lhs != rhs;  // eslint-disable-line eqeqeq
            }
        },

        assemble: function(body, vars, indent, node) {
            var me = this,
                assemblers = me.assemblers,
                t = typeof node,
                last = body.length - 1,
                childIndent = indent + '\t',
                type = node.type,
                arity, asm;

            if (t === 'boolean' || t === 'number') {
                body[last] += node;
            }
            else if (t === 'string') {
                body[last] += Ext.JSON.encode(node);
            }
            else {
                arity = me.operatorTypeMap[type];
                asm = assemblers[type] || (arity && assemblers[arity[0]]);

                if (typeof asm === 'string') {
                    asm = assemblers[asm];
                }

                asm(me, body, vars, indent, node, body.length - 1, childIndent);
            }
        }
    }
});
