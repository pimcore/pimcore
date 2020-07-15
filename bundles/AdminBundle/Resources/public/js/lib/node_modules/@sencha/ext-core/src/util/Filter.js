/**
 * A filter that can be applied to an `Ext.util.Collection` or other data container such
 * an `Ext.data.Store`. A `Filter` can be simply a filter on a `property` and `value` pair
 * or a filter function with custom logic.
 *
 * Normally filters are added to stores or collections but they can be created directly:
 *
 *     var ageFilter = new Ext.util.Filter({
 *         property: 'age',
 *         value: 42,
 *         operator: '<'
 *     });
 *
 *     var longNameFilter = new Ext.util.Filter({
 *         filterFn: function(item) {
 *             return item.name.length > 4;
 *         }
 *     });
 */
Ext.define('Ext.util.Filter', {
    extend: 'Ext.util.BasicFilter',

    config: {
        /**
         * @cfg {String} property
         * The property to filter on. Required unless a {@link #filterFn} is passed.
         */
        property: null,

        /**
         * @cfg {String} root
         * This property is used to descend items to check for meaningful properties on
         * which to filter. For a `Ext.data.Model` for example this would be `'data'`.
         */
        root: null,

        /**
         * @cfg {RegExp/Mixed} value
         * The value you want to match against. Required unless a {@link #filterFn} is passed.
         * 
         * Can be a regular expression which will be used as a matcher or any other value
         * such as an object or an array of objects. This value is compared using the configured
         * {@link #operator}.
         */
        value: null,

        /**
         * @cfg {Function} filterFn
         * A custom filter function which is passed each item. This function must return
         * `true` to accept an item or `false` to reject it.
         */
        filterFn: null,

        /**
         * @cfg {Boolean} anyMatch
         * True to allow any match - no regex start/end line anchors will be added.
         */
        anyMatch: false,

        /**
         * @cfg {Boolean} exactMatch
         * True to force exact match (^ and $ characters added to the regex). Ignored if
         * `anyMatch` is `true`.
         */
        exactMatch: false,

        /**
         * @cfg {Boolean} caseSensitive
         * True to make the regex case sensitive (adds 'i' switch to regex).
         */
        caseSensitive: false,

        /**
         * @cfg {Boolean} disableOnEmpty
         * `true` to not have this filter participate in the filtering process when the
         * {@link #value} of this the filter is empty according to {@link Ext#isEmpty}.
         *
         * @since 5.1.0
         */
        disableOnEmpty: false,

        /**
         * @cfg {String} operator
         * The operator to use to compare the {@link #cfg!property} to this Filter's
         * {@link #cfg!value}.
         *
         * Possible values are:
         *
         *    * `<`
         *    * `<=`
         *    * `=`
         *    * `>=`
         *    * `>`
         *    * `!=`
         *    * `in`
         *    * `notin`
         *    * `like`
         *    * `/=`
         *
         * The `in` and `notin` operator expects this filter's {@link #cfg-value} to be
         * an array and matches values that are present in that array.
         * 
         * The `like` operator matches values that contain this filter's {@link #cfg-value}
         * as a substring.
         *
         * The `/=` operator uses the {@link #cfg-value} as the source for a `RegExp` and
         * tests whether the candidate value matches the regular expression.
         */
        operator: null,

        /**
         * @cfg {Function} [convert]
         * A function to do any conversion on the value before comparison. For example,
         * something that returns the date only part of a date.
         * @cfg {Object} convert.value The value to convert.
         * @cfg {Object} convert.return The converted value.
         * @private
         */
        convert: null
    },

    /**
     * @cfg {Object} [scope]
     * The context (`this` property) in which the filtering function is called. Defaults
     * to this Filter object.
     */
    scope: null,

    // Needed for scope above. If `scope` were a "config" it would be merged and lose its
    // identity.
    $configStrict: false,

    statics: {
        /**
         * Creates a single filter function which encapsulates the passed Filter array or
         * Collection.
         * @param {Ext.util.Filter[]/Ext.util.Collection} filters The filters from which to
         * create a filter function.
         * @return {Function} A function, which when passed a candidate object returns `true`
         * if the candidate passes all the specified Filters.
         */
        createFilterFn: function(filters) {
            if (!filters) {
                return Ext.returnTrue;
            }

            return function(candidate) {
                var items = filters.isCollection ? filters.items : filters,
                    length = items.length,
                    match = true,
                    i, filter;

                for (i = 0; match && i < length; i++) {
                    filter = items[i];

                    // Skip disabled filters
                    if (!filter.getDisabled()) {
                        match = filter.filter(candidate);
                    }
                }

                return match;
            };
        },

        /**
         * Checks if two filters have the same properties (Property, Operator and Value).
         *
         * @param {Ext.util.Filter} filter1 The first filter to be compared
         * @param {Ext.util.Filter} filter2 The second filter to be compared
         * @return {Boolean} `true` if they have the same properties.
         * @since 6.2.0
         */
        isEqual: function(filter1, filter2) {
            if (filter1.getProperty() !== filter2.getProperty()) {
                return false;
            }

            if (filter1.getOperator() !== filter2.getOperator()) {
                return false;
            }

            if (filter1.getValue() === filter2.getValue()) {
                return true;
            }

            if (Ext.isArray(filter1) && Ext.isArray(filter2)) {
                return Ext.Array.equals(filter1, filter2);
            }

            return false;
        },

        /**
         * Checks whether the filter will produce a meaningful value. Since filters
         * may be used in conjunction with data binding, this is a sanity check to
         * check whether the resulting filter will be able to match.
         * 
         * @param {Object} cfg The filter config object
         * @return {Boolean/String} `true` if the filter will produce a valid value
         * 
         * @private
         */
        isInvalid: function(cfg) {
            if (!cfg.filterFn) {
                // If we don't have a filterFn, we must have a property
                if (!cfg.property) {
                    return 'A Filter requires either a property or a filterFn to be set';
                }

                if (!cfg.hasOwnProperty('value') && !cfg.operator) {
                    return 'A Filter requires either a property and value, or a filterFn to be set';
                }

            }

            return false;
        }
    },

    //<debug>
    constructor: function(config) {
        var warn = Ext.util.Filter.isInvalid(config);

        if (warn) {
            Ext.log.warn(warn);
        }

        this.callParent([ config ]);
    },
    //</debug>

    preventConvert: {
        'in': 1,
        notin: 1
    },

    filter: function(item) {
        var me = this,
            filterFn = me._filterFn || me.getFilterFn(),
            convert = me.getConvert(),
            value = me._value;

        me._filterValue = value;
        me.isDateValue = Ext.isDate(value);

        if (me.isDateValue) {
            me.dateValue = value.getTime();
        }

        if (convert && !me.preventConvert[me.getOperator()]) {
            me._filterValue = convert.call(me.scope || me, value);
        }

        return filterFn.call(me.scope || me, item);
    },

    getId: function() {
        var me = this,
            id = me._id;

        if (!id) {
            id = me.getProperty();

            if (!id) {
                id = Ext.id(null, 'ext-filter-');
            }

            me._id = id;
        }

        return id;
    },

    getFilterFn: function() {
        var me = this,
            filterFn = me._filterFn,
            operator;

        if (!filterFn) {
            operator = me.getOperator();

            if (operator) {
                filterFn = me.operatorFns[operator];
            }
            else {
                // This part is broken our into its own method so the function expression
                // contained there does not get hoisted and created on each call this
                // method.
                filterFn = me.createRegexFilter();
            }

            me._filterFn = filterFn;

            // Mark as generated by default. This becomes important when proxies encode
            // filters.  See proxy.Server#encodeFilters().
            me.generatedFilterFn = true;
        }

        return filterFn;
    },

    /**
     * @private
     * Creates a filter function for the configured value/anyMatch/caseSensitive options
     * for this Filter.
     */
    createRegexFilter: function() {
        var me = this,
            anyMatch = !!me.getAnyMatch(),
            exact = !!me.getExactMatch(),
            value = me.getValue(),
            matcher = Ext.String.createRegex(value,
                                             !anyMatch,  // startsWith
                                             !anyMatch && exact, // endsWith
                                             !me.getCaseSensitive());

        return function(item) {
            var val = me.getPropertyValue(item);

            return matcher ? matcher.test(val) : (val == null);
        };
    },

    /**
     * Returns the property of interest from the given item, based on the configured `root`
     * and `property` configs.
     * @param {Object} item The item.
     * @return {Object} The property of the object.
     * @private
     */
    getPropertyValue: function(item) {
        var root = this._root,
            value = (root == null) ? item : item[root];

        return value[this._property];
    },

    /**
     * Returns this filter's state.
     * @return {Object}
     */
    getState: function() {
        var config = this.getInitialConfig(),
            result = {},
            name;

        for (name in config) {
            // We only want the instance properties in this case, not inherited ones,
            // so we need hasOwnProperty to filter out our class values.
            if (config.hasOwnProperty(name)) {
                result[name] = config[name];
            }
        }

        delete result.root;
        result.value = this.getValue();

        return result;
    },

    getScope: function() {
        return this.scope;
    },

    /**
     * Returns this filter's serialized state. This is used when transmitting this filter
     * to a server.
     * @return {Object}
     */
    serialize: function() {
        var result = this.getState(),
            serializer = this.getSerializer(),
            serialized;

        delete result.id;
        delete result.serializer;

        if (serializer) {
            serialized = serializer.call(this, result);

            if (serialized) {
                result = serialized;
            }
        }

        return result;
    },

    serializeTo: function(out) {
        var me = this,
            primitive, serialized;

        // Filters with a custom filterFn cannot be serialized.  But since #getFilterFn()
        // always returns a filterFn, we need to check if it's been generated by default.
        // If so, we know that the filter cannot have a custom filterFn defined, and it
        // is therefore okay to serialize.
        me.getFilterFn();

        if (me.generatedFilterFn) {
            out.push(serialized = me.serialize());
            primitive = me.primitiveRe.test(typeof serialized);
        }

        return !primitive;
    },

    updateOperator: function() {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();
    },

    updateConvert: function() {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();
    },

    updateProperty: function() {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();
    },

    updateAnyMatch: function() {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();
    },

    updateExactMatch: function() {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();
    },

    updateCaseSensitive: function() {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();
    },

    updateValue: function(value) {
        // Need to clear any generated local filter fn and increment generation
        this.onConfigMutation();

        if (this.getDisableOnEmpty()) {
            this.setDisabled(Ext.isEmpty(value));
        }
    },

    updateFilterFn: function(filterFn) {
        delete this.generatedFilterFn;
    },

    onConfigMutation: function() {
        // Developers may use this to see if a filter has changed in ways that must cause
        // a reevaluation of filtering
        this.generation++;

        if (this.generatedFilterFn) {
            this._filterFn = null;
        }
    },

    updateDisableOnEmpty: function(disableOnEmpty) {
        // Only poke disabled if true because otherwise we'll smash the disabled
        // config that may also be getting set.
        if (disableOnEmpty) {
            this.setDisabled(Ext.isEmpty(this.getValue()));
        }
    },

    privates: {
        primitiveRe: /string|number|boolean/,

        getCandidateValue: function(candidate, v, preventCoerce) {
            var me = this,
                convert = me._convert,
                result = me.getPropertyValue(candidate);

            if (convert) {
                result = convert.call(me.scope || me, result);
            }
            else if (!preventCoerce) {
                result = Ext.coerce(result, v);
            }

            return result;
        }
    }
}, function(Filter) {
    var prototype = Filter.prototype,
        operatorFns = (prototype.operatorFns = {
            "<": function(candidate) {
                var v = this._filterValue;

                return this.getCandidateValue(candidate, v) < v;
            },
            "<=": function(candidate) {
                var v = this._filterValue;

                return this.getCandidateValue(candidate, v) <= v;
            },
            "=": function(candidate) {
                var me = this,
                    v = me._filterValue;

                candidate = me.getCandidateValue(candidate, v);

                if (me.isDateValue && candidate instanceof Date) {
                    candidate = candidate.getTime();
                    v = me.dateValue;
                }

                return candidate == v; // eslint-disable-line eqeqeq
            },
            "===": function(candidate) {
                var me = this,
                    v = me._filterValue;

                candidate = me.getCandidateValue(candidate, v, true);

                if (me.isDateValue && candidate instanceof Date) {
                    candidate = candidate.getTime();
                    v = me.dateValue;
                }

                return candidate === v;
            },
            ">=": function(candidate) {
                var v = this._filterValue;

                return this.getCandidateValue(candidate, v) >= v;
            },
            ">": function(candidate) {
                var v = this._filterValue;

                return this.getCandidateValue(candidate, v) > v;
            },
            "!=": function(candidate) {
                var me = this,
                    v = me._filterValue;

                candidate = me.getCandidateValue(candidate, v);

                if (me.isDateValue && candidate instanceof Date) {
                    candidate = candidate.getTime();
                    v = me.dateValue;
                }

                return candidate != v; // eslint-disable-line eqeqeq
            },
            "!==": function(candidate) {
                var me = this,
                    v = me._filterValue;

                candidate = me.getCandidateValue(candidate, v, true);

                if (me.isDateValue && candidate instanceof Date) {
                    candidate = candidate.getTime();
                    v = me.dateValue;
                }

                return candidate !== v;
            },
            "in": function(candidate) {
                var v = this._filterValue;

                return Ext.Array.contains(v, this.getCandidateValue(candidate, v));
            },
            notin: function(candidate) {
                var v = this._filterValue;

                return !Ext.Array.contains(v, this.getCandidateValue(candidate, v));
            },
            like: function(candidate) {
                var v = this._filterValue;

                // eslint-disable-next-line max-len
                return v && this.getCandidateValue(candidate, v).toLowerCase().indexOf(v.toLowerCase()) > -1;
            },
            "/=": function(candidate) {
                var me = this,
                    v = me._filterValue;

                candidate = me.getCandidateValue(candidate, v);

                // Only compile a RegExp when the source string changes
                if (v !== me.lastRegExpSource) {
                    me.lastRegExpSource = v;

                    try {
                        me.regex = new RegExp(v, 'i');
                    }
                    catch (e) {
                        me.regex = null;
                    }
                }

                return me.regex ? me.regex.test(candidate) : false;
            }
        });

    // Operator type '==' is the same as operator type '='
    operatorFns['=='] = operatorFns['='];

    operatorFns.gt = operatorFns['>'];
    operatorFns.ge = operatorFns['>='];

    operatorFns.lt = operatorFns['<'];
    operatorFns.le = operatorFns['<='];

    operatorFns.eq = operatorFns['='];
    operatorFns.ne = operatorFns['!='];
});
