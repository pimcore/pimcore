/**
 * Abstract base class for filter implementations.
 */
Ext.define('Ext.grid.filters.filter.Base', {
    mixins: [
        'Ext.mixin.Factoryable'
    ],

    factoryConfig: {
        type: 'grid.filter'
    },

    $configPrefixed: false,
    $configStrict: false,

    config: {
        /**
         * @cfg {Object} [itemDefaults]
         * The default configuration options for any menu items created by this filter.
         *
         * Example usage:
         *
         *      itemDefaults: {
         *          width: 150
         *      },
         */
        itemDefaults: null,

        menuDefaults: {
            xtype: 'menu'
        },

        /**
         * @cfg {Number} updateBuffer
         * Number of milliseconds to wait after user interaction to fire an update. Only supported
         * by filters: 'list', 'numeric', and 'string'.
         */
        updateBuffer: 500,

        /**
         * @cfg {Function} [serializer]
         * A function to post-process any serialization. Accepts a filter state object
         * containing `property`, `value` and `operator` properties, and may either
         * mutate it, or return a completely new representation.
         * @since 6.2.0
         */
        serializer: null
    },

    /**
     * @property {Boolean} active
     * True if this filter is active. Use setActive() to alter after configuration. If
     * you set a value, the filter will be actived automatically.
     */
    /**
     * @cfg {Boolean} active
     * Indicates the initial status of the filter (defaults to false).
     */
    active: false,

    /**
     * @property {String} type
     * The filter type. Used by the filters.Feature class when adding filters and applying state.
     */
    type: 'string',

    /**
     * @cfg {String} dataIndex
     * The {@link Ext.data.Store} dataIndex of the field this filter represents.
     * The dataIndex does not actually have to exist in the store.
     */
    dataIndex: null,

    /**
     * @property {Ext.menu.Menu} menu
     * The filter configuration menu that will be installed into the filter submenu
     * of a column menu.
     */
    menu: null,

    isGridFilter: true,

    defaultRoot: 'data',

    /**
     * The prefix for id's used to track stateful Store filters.
     * @private
     */
    filterIdPrefix: Ext.baseCSSPrefix + 'gridfilter',

    /**
     * @event filteractivate
     * Fires when an inactive filter becomes active
     * @param {Ext.grid.filters.Filters} this
     * @param {Ext.grid.column.Column} column This filter's assigned column
     * @since 6.5.0
     * @member Ext.panel.Table
     */

    /**
     * @event filterdeactivate
     * Fires when an active filter becomes inactive
     * @param {Ext.grid.filters.Filters} this
     * @param {Ext.grid.column.Column} column This filter's assigned column
     * @since 6.5.0
     * @member Ext.panel.Table
     */

    /**
     * Initializes the filter given its configuration.
     * @param {Object} config
     */
    constructor: function(config) {
        var me = this,
            column;

        // Calling Base constructor is very desirable for testing
        //<debug>
        me.callParent([config]);
        //</debug>

        me.initConfig(config);

        column = me.column;
        me.columnListeners = column.on('destroy', me.destroy, me, { destroyable: true });
        me.dataIndex = me.dataIndex || column.dataIndex;

        me.task = new Ext.util.DelayedTask(me.setValue, me);
    },

    /**
     * Destroys this filter by purging any event listeners, and removing any menus.
     */
    destroy: function() {
        var me = this;

        if (me.task) {
            me.task.cancel();
            me.task = null;
        }

        me.columnListeners = me.columnListeners.destroy();
        me.grid = me.menu = Ext.destroy(me.menu);

        me.callParent();
    },

    addStoreFilter: function(filter) {
        var filters = this.getGridStore().getFilters(),
            idx = filters.indexOf(filter),
            existing = idx !== -1 ? filters.getAt(idx) : null;

        // If the filter being added doesn't exist in the collection we should add it.
        // But if there is a filter with the same id (indexOf tests for the same id), we should
        // check if the filter being added has the same properties as the existing one
        if (!existing || !Ext.util.Filter.isEqual(existing, filter)) {
            filters.add(filter);
        }
    },

    createFilter: function(config, key) {
        var filter = new Ext.util.Filter(this.getFilterConfig(config, key));

        filter.isGridFilter = true;

        return filter;
    },

    // Note that some derived classes may need to do specific processing
    // and will have its own version of this method before calling parent (see the List filter).
    getFilterConfig: function(config, key) {
        config.id = this.getBaseIdPrefix();

        if (!config.property) {
            config.property = this.dataIndex;
        }

        if (!config.root) {
            config.root = this.defaultRoot;
        }

        if (key) {
            config.id += '-' + key;
        }

        config.serializer = this.getSerializer();

        return config;
    },

    /**
     * @private
     * Creates the Menu for this filter.
     * @param {Object} config Filter configuration
     * @return {Ext.menu.Menu}
     */
    createMenu: function() {
        this.menu = Ext.widget(this.getMenuConfig());
    },

    getActiveState: function(config, value) {
        // An `active` config must take precedence over a `value` config.
        var active = config.active;

        return (active !== undefined) ? active : value !== undefined;
    },

    getBaseIdPrefix: function() {
        return this.filterIdPrefix + '-' + this.dataIndex;
    },

    getMenuConfig: function() {
        return Ext.apply({}, this.getMenuDefaults());
    },

    getGridStore: function() {
        return this.grid.getStore();
    },

    getStoreFilter: function(key) {
        var id = this.getBaseIdPrefix();

        if (key) {
            id += '-' + key;
        }

        return this.getGridStore().getFilters().get(id);
    },

    /**
     * @private
     * Handler method called when there is a significant event on an input item.
     */
    onValueChange: function(field, e) {
        var me = this,
            keyCode = e.getKey(),
            updateBuffer = me.updateBuffer,
            value;

        // Don't process tabs!
        if (keyCode === e.TAB) {
            return;
        }

        //<debug>
        if (!field.isFormField) {
            Ext.raise('`field` should be a form field instance.');
        }
        //</debug>

        if (field.isValid()) {
            if (keyCode === e.RETURN) {
                me.menu.hide();

                return;
            }

            value = me.getValue(field);

            if (value === me.value) {
                return;
            }

            if (updateBuffer) {
                me.task.delay(updateBuffer, null, null, [value]);
            }
            else {
                me.setValue(value);
            }
        }
    },

    /**
     * @private
     * @method preprocess
     * Template method to be implemented by all subclasses that need to perform
     * any operations before the column filter has finished construction.
     * @template
     */
    preprocess: Ext.emptyFn,

    removeStoreFilter: function(filter) {
        this.getGridStore().getFilters().remove(filter);
    },

    /**
     * @private
     * @method getValue
     * Template method to be implemented by all subclasses that is to
     * get and return the value of the filter.
     * @return {Object} The 'serialized' form of this filter
     * @template
     */
    getValue: Ext.emptyFn,

    /**
     * @private
     * @method setValue
     * Template method to be implemented by all subclasses that is to
     * set the value of the filter and fire the grid's 'filterchange' event.
     * @param {Object} data The value to set the filter
     * @template
     */

    /**
     * Sets the status of the filter and fires the appropriate events.
     * @param {Boolean} active The new filter state.
     */
    setActive: function(active) {
        var me = this,
            menuItem = me.owner.activeFilterMenuItem,
            filterCollection;

        if (me.active !== active) {
            me.active = active;

            filterCollection = me.getGridStore().getFilters();
            filterCollection.beginUpdate();

            if (active) {
                me.activate();
            }
            else {
                me.deactivate();
            }

            filterCollection.endUpdate();

            // Make sure we update the 'Filters' menu item.
            if (menuItem && menuItem.activeFilter === me) {
                menuItem.setChecked(active);
            }

            me.setColumnActive(active);
            me.grid.fireEventArgs(active ? 'filteractivate' : 'filterdeactivate', [me, me.column]);
        }
    },

    setColumnActive: function(active) {
        this.column[active ? 'addCls' : 'removeCls'](this.owner.filterCls);
    },

    showMenu: function(menuItem) {
        var me = this;

        if (!me.menu) {
            me.createMenu();
        }

        menuItem.activeFilter = me;

        menuItem.setMenu(me.menu, false);
        menuItem.setChecked(me.active);

        // Disable the menu if filter.disabled explicitly set to true.
        menuItem.setDisabled(me.disabled === true);

        me.activate(/* showingMenu */ true);
    },

    updateStoreFilter: function() {
        this.getGridStore().getFilters().notify('endupdate');
    }
});

