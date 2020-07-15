/**
 * This class provides a flexible means to control the
 * `{@link Ext.util.Collection#cfg!filters filters}` of a
 * `{@link Ext.util.Collection Collection}`. Instances of this class are created
 * automatically when filters are added to added to Collections.
 *
 * This collection can be directly manipulated by application code to gain full
 * control over the filters of the owner collection. Be aware that some components
 * create filters internally (such as `Ext.form.field.ComboBox` and the
 * `Ext.grid.filters.Filters` plugin) so be careful in such cases to not disturb
 * these additional filters.
 *
 * Items in this collection are `Ext.util.Filter` instances and can be managed
 * individually by their `id`. This is the recommended way to manage application
 * filters while preserving filters applied from other sources.
 *
 * Bulk changes to this collection should be wrapped in
 * `{@link Ext.util.Collection#method!beginUpdate beginUpdate}` and
 * `{@link Ext.util.Collection#method!endUpdate endUpdate}` (as with any collection).
 * During these bulk updates all reactions to filter changes will be suspended.
 */
Ext.define('Ext.util.FilterCollection', {
    extend: 'Ext.util.Collection',

    requires: [
        'Ext.util.Filter'
    ],

    isFilterCollection: true,

    /**
     * @property {Ext.util.Collection} $filterable
     * The owning filterable instance. The filterable's configuration governs this
     * collection.
     * @private
     * @readonly
     */
    $filterable: null,

    /**
     * @property filterFn
     * This is the cached filter function.
     * @readonly
     */
    filterFn: null,

    constructor: function(config) {
        var me = this;

        // Because this closure operates on the collection, we are able to use it for as
        // long as we have the Collection instance.
        me.filterFn = Ext.util.Filter.createFilterFn(me);

        me.callParent([config]);
        me.setDecoder(me.decodeFilter);
    },

    /**
     * This method will filter an array based on the currently configured `filters`.
     * @param {Array} data The array you want to have filtered.
     * @return {Array} The array you passed after it is filtered.
     */
    filterData: function(data) {
        return this.filtered ? Ext.Array.filter(data, this.filterFn) : data;
    },

    /**
     * Returns the filter function.
     * @return {Function} The filter function.
     */
    getFilterFn: function() {
        return this.filterFn;
    },

    isItemFiltered: function(item) {
        return !this.filterFn(item);
    },

    /**
     * returns the number of *enabled* filters in this `FilterCollection`
     * @returns {Number}
     */
    getFilterCount: function() {
        var filters = this.items,
            len = filters.length,
            i;

        for (i = len - 1; i >= 0; i--) {
            if (filters[i].getDisabled()) {
                len--;
            }
        }

        return len;
    },

    //-------------------------------------------------------------------------
    // Private

    decodeFilter: function(filter) {
        var options = this.getOptions(),
            filterRoot = options.getRootProperty(),
            filterConfig;

        if (filter.isFilter) {
            if (filter.setRoot && !filter.getRoot()) {
                filter.setRoot(filterRoot);
            }
        }
        else {
            filterConfig = {
                root: filterRoot
            };

            if (Ext.isFunction(filter)) {
                filterConfig.filterFn = filter;
            }
            // If we are dealing with an object, we assume its a Filter configuration. In
            // this case we create an instance of Ext.util.Filter passing the config.
            else {
                // Finally we get to the point where it has to be invalid
                //<debug>
                if (!Ext.isObject(filter)) {
                    Ext.raise('Invalid filter specified: ' + filter);
                }
                //</debug>

                filterConfig = Ext.apply(filterConfig, filter);

                if (filterConfig.fn) {
                    filterConfig.filterFn = filterConfig.fn;
                    delete filterConfig.fn;
                }

                if (Ext.util.Filter.isInvalid(filterConfig)) {
                    return false;
                }
            }

            filter = new Ext.util.Filter(filterConfig);
        }

        return filter;
    },

    decodeRemoveItems: function(args, index) {
        var me = this,
            ret = (index === undefined) ? args : args[index];

        if (!ret.$cloned) {
            if (args.length > index + 1 || !Ext.isIterable(ret)) {
                ret = Ext.Array.slice(args, index);
            }

            // eslint-disable-next-line vars-on-top
            var currentFilters = me.items,
                ln = ret.length,
                remove = [],
                filter, i, isFunction, isProp, isString, item, match, n, type;

            for (i = 0; i < ln; i++) {
                filter = ret[i];

                if (filter && filter.isFilter) {
                    remove.push(filter);
                }
                else {
                    type = typeof filter;

                    isFunction = type === 'function';
                    isProp = filter.property !== undefined && filter.value !== undefined;
                    isString = type === 'string';

                    //<debug>
                    if (!isFunction && !isProp && !isString) {
                        Ext.raise('Invalid filter specification: ' + filter);
                    }
                    //</debug>

                    for (n = currentFilters.length; n-- > 0;) {
                        item = currentFilters[n];
                        match = false;

                        if (isString) {
                            match = item.getProperty() === filter;
                        }
                        else if (isFunction) {
                            match = item.getFilterFn() === filter;
                        }
                        else if (isProp) {
                            match = item.getProperty() === filter.property &&
                                    item.getValue() === filter.value;
                        }

                        if (match) {
                            remove.push(item);
                        }
                    }
                }
            }

            ret = remove;
            ret.$cloned = true;
        }

        return ret;
    },

    getOptions: function() {
        // Odd thing this. We need a Filterable to know how to manage our collection, but
        // we may not have one. Of course as a Collection, we *are* one as well... just
        // that is not really useful to filter the filters themselves, but we do have the
        // default options for Filterable baked in, so we'll do.
        return this.$filterable || this;
    }
});
