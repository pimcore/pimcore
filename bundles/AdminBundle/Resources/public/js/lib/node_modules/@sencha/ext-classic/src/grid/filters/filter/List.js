/**
 * The list grid filter allows you to create a filter selection that limits results
 * to values matching an element in a list.  The filter can be set programmatically or via 
 * user input with a configurable {@link Ext.form.field.Checkbox check box field} in the filter
 * section  of the column header.
 * 
 * List filters are able to be preloaded/backed by an Ext.data.Store to load
 * their options the first time they are shown.  They are also able to create their own 
 * list of values from  all unique values of the specified {@link #dataIndex} field in 
 * the store at first time of filter invocation.
 *
 * Example List Filter Usage:
 *
 *     @example
 *     var shows = Ext.create('Ext.data.Store', {
 *         fields: ['id','show','rating'],
 *         data: [
 *             {id: 0, show: 'Battlestar Galactica', rating: 2},
 *             {id: 1, show: 'Doctor Who', rating: 4},
 *             {id: 2, show: 'Farscape', rating: 3},
 *             {id: 3, show: 'Firefly', rating: 4},
 *             {id: 4, show: 'Star Trek', rating: 1},
 *             {id: 5, show: 'Star Wars: Christmas Special', rating: 5}
 *         ]
 *     });
 *   
 *     Ext.create('Ext.grid.Panel', {
 *         renderTo: Ext.getBody(),
 *         title: 'Sci-Fi Television',
 *         height: 250,
 *         width: 350,
 *         store: shows,
 *         plugins: {
 *             gridfilters: true
 *         },
 *         columns: [{
 *             dataIndex: 'id',
 *             text: 'ID',
 *             width: 50
 *         },{
 *             dataIndex: 'show',
 *             text: 'Show',
 *             flex: 1                  
 *         },{
 *             dataIndex: 'rating',
 *             text: 'Rating',
 *             width: 75,
 *             filter: {
 *                 type: 'list',
 *                 value: 5                      
 *             }
 *         }]
 *     });
 *
 * ## Options
 * 
 * There are three means to determine the list of options to present to the user:
 * 
 *   * The `{@link #cfg-options options}` config.
 *   * The `{@link #cfg-store store}` config. In this mode, the `{@link #cfg-idField}`
 *     and `{@link #cfg-labelField}` configs are used to extract the presentation and
 *     filtering values from the `store` and apply to the menu items and grid store
 *     filter, respectively.
 *   * If none of the above is specified, the associated grid's store is used. In this
 *     case, the `{@link #cfg-dataIndex}` is used to determine the filter values and
 *     the `{@link #cfg-labelIndex}` is used to populate the menu items. These fields
 *     are extracted from the records in the associated grid's store. Both of these
 *     configs default to the column's `dataIndex` property.
 * 
 * In all of these modes, a store is created that is synchronized with the menu items.
 * The records in this store have `{@link #cfg-idField}` and `{@link #cfg-labelField}`
 * fields that get populated from which ever source was provided.
 * 
 *     var filters = Ext.create('Ext.grid.Panel', {
 *         ...
 *         columns: [{
 *             text: 'Size',
 *             dataIndex: 'size',
 *
 *             filter: {
 *                 type: 'list',
 *                 // options will be used as data to implicitly creates an ArrayStore
 *                 options: ['extra small', 'small', 'medium', 'large', 'extra large']
 *             }
 *         }],
 *         ...
 *     });
 */
Ext.define('Ext.grid.filters.filter.List', {
    extend: 'Ext.grid.filters.filter.SingleFilter',
    alias: 'grid.filter.list',

    type: 'list',

    operator: 'in',

    /**
     * @cfg {Object} [itemDefaults]
     * See the {@link Ext.grid.filters.filter.Base#cfg-itemDefaults documentation} for
     * the base class for details.
     * 
     * In the case of this class, however, note that the `checked` config should **not** be
     * specified.
     */
    itemDefaults: {
        checked: false,
        hideOnClick: false
    },

    /**
     * @cfg {Array} [options]
     * The data to be used to implicitly create a data store to back this list. This is used only
     * when the data source is **local**. If the data for the list is remote, use the {@link #store}
     * config instead.
     *
     * If neither store nor {@link #options} is specified, then the choices list is automatically
     * populated from all unique values of the specified {@link #dataIndex} field in the store
     * at first time of filter invocation.
     *
     * Each item within the provided array may be in one of the following formats:
     *
     *   - **Array** :
     *
     *         options: [
     *             [11, 'extra small'],
     *             [18, 'small'],
     *             [22, 'medium'],
     *             [35, 'large'],
     *             [44, 'extra large']
     *         ]
     *
     *   - **Object** :
     *
     *         labelField: 'name', // override default of 'text'
     *         options: [
     *             {id: 11, name:'extra small'},
     *             {id: 18, name:'small'},
     *             {id: 22, name:'medium'},
     *             {id: 35, name:'large'},
     *             {id: 44, name:'extra large'}
     *         ]
     *
     *   - **String** :
     *
     *         options: ['extra small', 'small', 'medium', 'large', 'extra large']
     *
     */

    /**
     * @cfg {String} [idField="id"]
     * The field name for the `id` of records in this list's `{@link #cfg-store}`. These values are
     * used to populate the filter for the grid's store.
     */
    idField: 'id',

    /**
     * @cfg {String} [labelField="text"]
     * The field name for the menu item text in the records in this list's `{@link #cfg-store}`.
     */
    labelField: 'text',

    /**
     * @cfg {String} [labelIndex]
     * The field in the records of the grid's store from which the menu item text should be
     * retrieved. This field is only used when no `{@link #cfg-options}` and no `{@link #cfg-store}`
     * is provided and the distinct value of the grid's store need to be generated dynamically.
     * 
     * If not provided, this field defaults to the column's `dataIndex` property.
     * @since 5.1.0
     */
    labelIndex: null,

    /**
     * @cfg {String} [loadingText]
     * The text that is displayed while the configured store is loading.
     * @locale
     */
    loadingText: 'Loading...',

    /**
     * @cfg {Boolean} loadOnShow
     * Defaults to true.
     */
    loadOnShow: true,

    /**
     * @cfg {Boolean} single
     * Specify true to group all items in this list into a single-select
     * radio button group. Defaults to false.
     */
    single: false,

    plain: true,

    /**
     * @cfg {Ext.data.Store} [store]
     * The {@link Ext.data.Store} this list should use as its data source.
     *
     * If neither store nor {@link #options} is specified, then the choices list is automatically
     * populated from all unique values of the specified {@link #dataIndex} field in the store
     * at first time of filter invocation.
     */

    /**
     * @private
     */
    gridStoreListenersCfg: {
        add: 'onDataChanged',
        refresh: 'onDataChanged',
        remove: 'onDataChanged',
        update: 'onDataChanged'
    },

    constructor: function(config) {
        var me = this,
            gridStore;

        me.callParent([config]);

        //<debug>
        if (me.itemDefaults.checked) {
            Ext.raise('The itemDefaults.checked config is not supported, ' +
                      'use the value config instead.');
        }
        //</debug>

        me.labelIndex = me.labelIndex || me.column.dataIndex;

        if (me.store) {
            me.store = Ext.StoreManager.lookup(me.store);
        }

        // In order to fully support the `active` config, we need to do some preprocessing in case
        // we need to fetch store data in order to create the options menu items.
        //
        // For instance, imagine if a list filter has the following definition:
        //
        //    filter: {
        //        type: 'list',
        //        value: 'Bruce Springsteen'
        //    }
        //
        // Since there is no `options` or `store` config, it will need to infer its options store
        // data from the grid store. Since it is also active by default if not explicitly
        // configured as `value: false`, it must register listeners with the grid store now
        // so its own column filter store will be created and filtered immediately and properly sync
        // its options when the grid store changes.
        //
        // So here we need to subscribe to very specific events. We can't subscribe to a catch-all
        // like 'datachanged' because the listener will get called too many times. This will respond
        // to the following scenarios:
        //  1. Removing a filter
        //  2. Adding a filter
        //  3. (Re)loading the store
        //  4. Updating a model
        //
        if (!me.store && !me.options) {
            gridStore = me.getGridStore();

            if (me.value != null && me.active) {
                me.gridStoreListeners = gridStore.on(Ext.apply({
                    scope: me,
                    destroyable: true
                }, me.gridStoreListenersCfg));
            }

            me.gridListeners = me.grid.on({
                reconfigure: me.onReconfigure,
                scope: me,
                destroyable: true
            });

            me.inferOptionsFromGridStore = true;
        }
    },

    destroy: function() {
        var me = this,
            store = me.store,
            autoStore = me.autoStore;

        // We may bind listeners to both the options store & grid store, so we
        // need to unbind both sets here
        if (store && store.isStore) {
            if (autoStore || store.autoDestroy) {
                store.destroy();
            }
            else {
                store.un('load', me.bindMenuStore, me);
            }

            me.store = null;
        }

        Ext.destroy(me.gridStoreListeners, me.gridListeners);

        me.callParent();
    },

    activateMenu: function() {
        var me = this,
            value = me.filter.getValue(),
            items, i, len, checkItem;

        if (!value || !value.length) {
            return;
        }

        items = me.menu.items;

        for (i = 0, len = items.length; i < len; i++) {
            checkItem = items.getAt(i);

            if (Ext.Array.indexOf(value, checkItem.value) > -1) {
                checkItem.setChecked(true, /* suppressEvents */ true);
            }
        }
    },

    bindMenuStore: function(options) {
        var me = this;

        if (me.grid.destroyed || me.preventFilterRemoval) {
            return;
        }

        me.createListStore(options);
        me.createMenuItems(me.store);
        me.loaded = true;
    },

    /**
     * Returns a store for the filter.
     * An instantiated store may be passed.
     * 
     * If that store is the grid's store, then all unique values of this filter's
     * {@link #dataIndex} field are extracted for use in the filter.
     * 
     * Otherwise the passed store is used.
     *
     * If the passed parameter is not a store, it is taken to be a list of possible
     * values for the filter.
     * 
     * @private
     */
    createListStore: function(options) {
        var me = this,
            store = me.store,
            isStore = options.isStore,
            idField = me.idField,
            labelField = me.labelField,
            optionsStore = false,
            storeData, o, i, len, value;

        if (isStore) {
            if (options !== me.getGridStore()) {
                optionsStore = true;
                store = me.store = options;
            }
            else {
                me.autoStore = true;
                storeData = me.getOptionsFromStore(options);
            }
        }
        else {
            storeData = [];

            for (i = 0, len = options.length; i < len; i++) {
                value = options[i];

                switch (Ext.typeOf(value)) {
                    case 'array':
                        storeData.push(value);
                        break;

                    case 'object':
                        storeData.push(value);
                        break;

                    default:
                        if (value != null) {
                            o = {};
                            o[idField] = value;
                            o[labelField] = value;
                            storeData.push(o);
                        }
                }
            }
        }

        if (!optionsStore) {
            if (store) {
                store.destroy();
            }

            store = me.store = new Ext.data.Store({
                fields: [idField, labelField],
                data: storeData
            });

            // Note that the grid store listeners may have been bound in the constructor
            // if it was determined that the grid filter was active and defined with a value.
            if (me.inferOptionsFromGridStore & !me.gridStoreListeners) {
                me.gridStoreListeners = me.getGridStore().on(Ext.apply({
                    scope: me,
                    destroyable: true
                }, me.gridStoreListenersCfg));
            }

            me.loaded = true;
        }

        me.setStoreFilter(store);
    },

    /**
     * @private
     * Creates the Menu for this filter.
     * @param {Object} config Filter configuration
     * @return {Ext.menu.Menu}
     */
    createMenu: function(config) {
        var me = this,
            gridStore = me.getGridStore(),
            store = me.store,
            options = me.options,
            menu;

        if (store) {
            me.store = store = Ext.StoreManager.lookup(store);
        }

        me.callParent([config]);
        menu = me.menu;

        if (store) {
            if (!store.getCount()) {
                menu.add({
                    text: me.loadingText,
                    iconCls: Ext.baseCSSPrefix + 'mask-msg-text'
                });

                // Add a listener that will auto-load the menu store if `loadOnShow` is true
                // (the default). Don't bother with mon here, the menu is destroyed when we are.
                menu.on({
                    show: me.show,
                    scope: me
                });

                store.on('load', me.bindMenuStore, me, { single: true });
            }
            else {
                me.createMenuItems(store);
            }

        }
        // If there are supplied options, then we know the store is local.
        else if (options) {
            me.bindMenuStore(options);
        }
        // A ListMenu which is completely unconfigured acquires its store from the unique values
        // of its field in the store. Note that the gridstore may have already been filtered on load
        // if the column filter had been configured as active with no items checked by default.
        else if (gridStore.getCount() || gridStore.isFiltered()) {
            me.bindMenuStore(gridStore);
        }
        // If there are no records in the grid store, then we know it's async and we need to listen
        // for its 'load' event.
        else {
            gridStore.on('load', me.bindMenuStore, me, { single: true });
        }
    },

    /**
     * @private
     */
    createMenuItems: function(store) {
        var me = this,
            menu = me.menu,
            len = store.getCount(),
            contains = Ext.Array.contains,
            itemDefaults, record, gid, idValue, idField, labelValue, labelField,
            i, processed;

        // B/c we're listening to datachanged event, we need to make sure there's a menu.
        if (len && menu) {
            itemDefaults = me.getItemDefaults();
            menu.suspendLayouts();
            menu.removeAll(true);
            gid = me.single ? Ext.id() : null;
            idField = me.idField;
            labelField = me.labelField;

            processed = [];

            for (i = 0; i < len; i++) {
                record = store.getAt(i);
                idValue = record.get(idField);
                labelValue = record.get(labelField);

                // Only allow unique values.
                if (labelValue == null || contains(processed, idValue)) {
                    continue;
                }

                processed.push(labelValue);

                // Note that the menu items will be set checked in filter#activate()
                // if the value of the menu item is in the cfg.value array.
                menu.add(Ext.apply({
                    text: labelValue,
                    group: gid,
                    value: idValue,
                    checkHandler: me.onCheckChange,
                    scope: me
                }, itemDefaults));
            }

            menu.resumeLayouts(true);
        }
    },

    getFilterConfig: function(config, key) {
        // List filter needs to have its value set immediately or else could will fail
        // when filtering since its _value would be undefined.
        var value = config.value;

        if (Ext.isEmpty(value)) {
            value = [];
        }
        else if (!Ext.isArray(value)) {
            value = [value];
        }

        config.value = value;

        return this.callParent([config, key]);
    },

    getOptionsFromStore: function(store) {
        var me = this,
            data = store.getData(), // eslint-disable-line no-unused-vars
            map = {},
            ret = [],
            dataIndex = me.dataIndex,
            labelIndex = me.labelIndex,
            recData, idValue, labelValue;

        if (store.isFiltered() && !store.remoteFilter) {
            data = data.getSource();
        }

        // Use store type agnostic each method.
        // TreeStore and Store implement this differently.
        // In a TreeStore, the items array only contains nodes
        // below *expanded* ancestors. Nodes below a collapsed ancestor
        // are removed from the collection. TreeStores walk the tree
        // to implement each.
        store.each(function(record) {
            recData = record.data;

            idValue = recData[dataIndex];
            labelValue = recData[labelIndex];

            if (labelValue === undefined) {
                labelValue = idValue;
            }

            // TODO: allow null?
            // if ((allowNull || !Ext.isEmpty(value)) && !map[strValue1]) {
            if (!map[idValue]) {
                map[idValue] = 1;
                ret.push([idValue, labelValue]);
            }
        }, null, {
            filtered: true,     // Include filtered out nodes.
            collapsed: true     // Include nodes below collapsed ancestors.
        });

        return ret;
    },

    onCheckChange: function() {
        // Note that we don't care about the checked state here because #setValue
        // will sort this out. #setValue will get the values of the currently-checked items
        // and set its filter value from that.
        var me = this,
            updateBuffer = me.updateBuffer;

        if (updateBuffer) {
            me.task.delay(updateBuffer);
        }
        else {
            me.setValue();
        }
    },

    onDataChanged: function(store) {
        // If the menu item options (and the options store) are being auto-generated
        // from the grid store, then it needs to know when the grid store has changed its data
        // so it can remain in sync.
        if (this.preventDefault) {
            this.preventDefault = false;
        }
        else {
            this.bindMenuStore(store);
        }
    },

    onReconfigure: function(grid, store) {
        // We need to listen for reconfigure not only for when the list filter has inferred
        // its options from the grid store but also when the grid has a VM and is late-binding
        // the store.
        if (store) {
            this.bindMenuStore(store);
        }
    },

    setActive: function(active) {
        if (this.active !== active) {
            // The store filter will be updated, but we don't want to recreate the list store
            // or the menu items in the onDataChanged listener so we need to set this flag.
            // It will be reset in the onDatachanged listener when the store has filtered/cleared
            // filters.
            this.preventDefault = true;
            this.callParent([active]);
        }
    },

    setStoreFilter: function(options) {
        var me = this,
            value = me.value,
            filter = me.filter;

        // If there are user-provided values we trust that they are valid
        // (an empty array IS valid!).
        if (value) {
            if (!Ext.isArray(value)) {
                value = [value];
            }

            filter.setValue(value);
        }

        if (me.active) {
            me.preventFilterRemoval = true;
            me.addStoreFilter(filter);
            me.preventFilterRemoval = false;
        }
    },

    /**
     * @private
     * Template method that is to set the value of the filter.
     */
    setValue: function() {
        var me = this,
            items = me.menu.items,
            value = [],
            i, len, checkItem;

        // The store filter will be updated, but we don't want to recreate the list store
        // or the menu items in the onDataChanged listener so we need to set this flag.
        // It will be reset in the onDatachanged listener when the store has filtered.
        me.preventDefault = true;

        for (i = 0, len = items.length; i < len; i++) {
            checkItem = items.getAt(i);

            if (checkItem.checked) {
                value.push(checkItem.value);
            }
        }

        // Only update the store if the value has changed
        if (!Ext.Array.equals(value, me.filter.getValue())) {
            me.filter.setValue(value);
            len = value.length;

            if (len && me.active) {
                me.updateStoreFilter();
            }
            else {
                me.setActive(!!len);
            }
        }
    },

    show: function() {
        var store = this.store;

        if (this.loadOnShow && !this.loaded && !store.hasPendingLoad()) {
            store.load();
        }
    }
});
