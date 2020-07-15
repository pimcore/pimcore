/**
 * This mixin provides the ability to convert the parse tree of an `Ext.data.Query`
 * (that is, its `ast` or Abstract Syntax Tree) into an array of serialized
 * {@link Ext.util.Filter filters}. This singleton can also produce an `ast` from such an
 * array.
 * @private
 * @since 6.7.0
 */
Ext.define('Ext.data.query.Converter', {
    /**
     * Returns the array of serialized {@link Ext.util.Filter filter} objects equivalent
     * to this query if possible. If this query is empty, this method returns `null`. If
     * the query cannot be converted without loss into a filters array, this method will
     * return `undefined`.
     * @return {Object[]}
     */
    getFilters: function() {
        var me = this,
            ast = me.ast,
            exprToFilter = me.exprToFilter,
            operatorTypeMap = me.operatorTypeMap,
            fn = me.fn,
            on = ast && ast.on,
            expr, filter, filters, i, ident, n, op, ret, value, xlat;

        if (ast) {
            // We cache the filters on the fn since they are valid for as long as the
            // fn is valid.
            if (fn.hasOwnProperty('$filters')) {
                ret = fn.$filters;
            }
            else {
                if (ast.type === 'and' && on) {
                    filters = [];

                    for (i = 0, n = on.length; i < n; ++i) {
                        expr = on[i];
                        ident = expr.on;

                        if (!ident || ident.length !== 2) {
                            break;
                        }

                        value = ident[1];
                        ident = ident[0];

                        if (ident.type !== 'id') {
                            break;
                        }

                        if (!(xlat = exprToFilter[expr.type])) {
                            op = operatorTypeMap[expr.type];

                            if (!op || !(xlat = exprToFilter[op[0]])) {
                                break;
                            }
                        }

                        if (!(filter = xlat(expr, ident.value, value, op))) {
                            break;
                        }

                        filters.push(filter);
                    }

                    if (i === n) {
                        ret = filters;
                    }
                }

                fn.$filters = ret;
            }
        }
        else {
            ret = null;
        }

        return ret;
    },

    setFilters: function(filters) {
        var me = this,
            ast = null,
            n = filters && filters.length,
            expr, filter, i, op, xlat;

        if (n) {
            ast = {
                type: 'and',
                on: []
            };

            for (i = 0; i < n; ++i) {
                filter = filters[i];

                if (!(xlat = me.filterToExpr[op = filter.operator])) {
                    expr = {
                        type: me.getOperatorType(op),
                        on: [
                            { type: 'id', value: filter.property },
                            filter.value
                        ]
                    };
                }
                else {
                    expr = xlat(filter);
                }

                ast.on.push(expr);
            }
        }

        me.ast = ast;
        me.refresh();
    },

    privates: {
        exprToFilter: {
            binary: function(expr, ident, value, info) {
                return Ext.isPrimitive(value) && {
                    property: ident,
                    operator: info[1],
                    value: value
                };
            },

            'in': function(expr, ident, value) {
                var i = 0,
                    list = value.value;

                if (value.type === 'list' && Ext.isArray(list)) {
                    for (i = list.length; i-- > 0; /* empty */) {
                        if (!Ext.isPrimitive(list[i])) {
                            break;
                        }
                    }
                }

                return (i < 0) && {
                    property: ident,
                    operator: 'in',
                    value: list
                };
            },

            like: function(expr, ident, value) {
                if (value.type === 'regexp') {
                    return {
                        property: ident,
                        operator: '/=',
                        value: value.value
                    };
                }

                return (value.type === 'string') && {
                    property: ident,
                    operator: 'like',
                    value: value.value
                };
            }
        },

        filterToExpr: {
            '/=': function(filter) {
                return {
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: filter.property
                    }, {
                        type: 'regexp',
                        value: filter.value
                    }]
                };
            },

            'in': function(filter) {
                return {
                    type: 'in',
                    on: [{
                        type: 'id',
                        value: filter.property
                    }, {
                        type: 'list',
                        value: filter.value
                    }]
                };
            },

            like: function(filter) {
                return {
                    type: 'like',
                    on: [{
                        type: 'id',
                        value: filter.property
                    }, {
                        type: 'string',
                        value: filter.value,
                        re: filter.value,
                        flags: 'i'
                    }]
                };
            }
        }
    }
});
