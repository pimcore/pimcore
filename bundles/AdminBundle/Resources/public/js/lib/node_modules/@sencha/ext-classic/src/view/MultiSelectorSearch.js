/**
 * This component provides a grid holding selected items from a second store of potential
 * members. The `store` of this component represents the selected items. The `searchStore`
 * represents the potentially selected items.
 *
 * The default view defined by this class is intended to be easily replaced by deriving a
 * new class and overriding the appropriate methods. For example, the following is a very
 * different view that uses a date range and a data view:
 *
 *      Ext.define('App.view.DateBoundSearch', {
 *          extend: 'Ext.view.MultiSelectorSearch',
 *
 *          makeDockedItems: function () {
 *              return {
 *                  xtype: 'toolbar',
 *                  items: [{
 *                      xtype: 'datefield',
 *                      emptyText: 'Start date...',
 *                      flex: 1
 *                  },{
 *                      xtype: 'datefield',
 *                      emptyText: 'End date...',
 *                      flex: 1
 *                  }]
 *              };
 *          },
 *
 *          makeItems: function () {
 *              return [{
 *                  xtype: 'dataview',
 *                  itemSelector: '.search-item',
 *                  selModel: 'rowselection',
 *                  store: this.store,
 *                  scrollable: true,
 *                  tpl:
 *                      '<tpl for=".">' +
 *                          '<div class="search-item">' +
 *                              '<img src="{icon}">' +
 *                              '<div>{name}</div>' +
 *                          '</div>' +
 *                      '</tpl>'
 *              }];
 *          },
 *
 *          getSearchStore: function () {
 *              return this.items.getAt(0).getStore();
 *          },
 *
 *          selectRecords: function (records) {
 *              var view = this.items.getAt(0);
 *              return view.getSelectionModel().select(records);
 *          }
 *      });
 *
 * **Important**: This class assumes there are two components with specific `reference`
 * names assigned to them. These are `"searchField"` and `"searchGrid"`. These components
 * are produced by the `makeDockedItems` and `makeItems` method, respectively. When
 * overriding these it is important to remember to place these `reference` values on the
 * appropriate components.
 */
Ext.define('Ext.view.MultiSelectorSearch', {
    extend: 'Ext.panel.Panel',

    xtype: 'multiselector-search',

    /**
     * @cfg layout
     * @inheritdoc
     */
    layout: 'fit',

    /**
     * @cfg floating
     * @inheritdoc
     */
    floating: true,

    /**
     * @cfg alignOnScroll
     * @inheritdoc
     */
    alignOnScroll: false,

    /**
     * @cfg minWidth
     * @inheritdoc
     */
    minWidth: 200,

    /**
     * @cfg minHeight
     * @inheritdoc
     */
    minHeight: 200,

    /**
     * @cfg border
     * @inheritdoc
     */
    border: true,

    /**
     * @cfg keyMap
     * @inheritdoc
     */
    keyMap: {
        scope: 'this',
        ESC: 'hide'
    },

    platformConfig: {
        desktop: {
            resizable: true
        },
        'tablet && rtl': {
            resizable: {
                handles: 'sw'
            }
        },
        'tablet && !rtl': {
            resizable: {
                handles: 'se'
            }
        }
    },

    /**
     * @cfg defaultListenerScope
     * @inheritdoc
     */
    defaultListenerScope: true,

    /**
     * @cfg referenceHolder
     * @inheritdoc
     */
    referenceHolder: true,

    /**
     * @cfg {String} field
     * A field from your grid's store that will be used for filtering your search results.
     */

    /**
     * @cfg store
     * @inheritdoc Ext.panel.Table#cfg-store
     */

    /**
     * @cfg {String} searchText
     * This text is displayed as the "emptyText" of the search `textfield`.
     */
    searchText: 'Search...',

    initComponent: function() {
        var me = this,
            owner = me.owner,
            items = me.makeItems(),
            i, item, records, store;

        me.dockedItems = me.makeDockedItems();
        me.items = items;

        store = Ext.data.StoreManager.lookup(me.store);

        for (i = items.length; i--;) {
            if ((item = items[i]).xtype === 'grid') {
                item.store = store;
                item.isSearchGrid = true;
                item.selModel = item.selModel || {
                    type: 'checkboxmodel',
                    pruneRemoved: false,
                    listeners: {
                        selectionchange: 'onSelectionChange'
                    }
                };

                Ext.merge(item, me.grid);

                if (!item.columns) {
                    item.hideHeaders = true;
                    item.columns = [{
                        flex: 1,
                        dataIndex: me.field
                    }];
                }

                break;
            }
        }

        me.callParent();

        records = me.getOwnerStore().getRange();

        if (!owner.convertSelectionRecord.$nullFn) {
            for (i = records.length; i--;) {
                records[i] = owner.convertSelectionRecord(records[i]);
            }
        }

        if (store.isLoading() || (store.loadCount === 0 && !store.getCount())) {

            // If it is NOT a preloaded store, then unless a Session is being used,
            // The newly loaded records will NOT match any in the ownerStore.
            // So we must match them by ID in order to select the same dataset.
            store.on('load', function() {
                if (!me.destroyed) {
                    me.selectRecords(records);
                }
            }, null, { single: true });
        }
        else {
            me.selectRecords(records);
        }
    },

    getOwnerStore: function() {
        return this.owner.getStore();
    },

    afterShow: function() {
        var searchField;

        this.callParent(arguments);

        // Do not focus if this was invoked by a touch gesture
        if (!this.invocationEvent || this.invocationEvent.pointerType !== 'touch') {
            searchField = this.lookupReference('searchField');

            if (searchField) {
                searchField.focus();
            }
        }

        this.invocationEvent = null;
    },

    /**
     * Returns the store that holds search results. By default this comes from the
     * "search grid". If this aspect of the view is changed sufficiently so that the
     * search grid cannot be found, this method should be overridden to return the proper
     * store.
     * @return {Ext.data.Store}
     */
    getSearchStore: function() {
        var searchGrid = this.lookupReference('searchGrid');

        return searchGrid.getStore();
    },

    makeDockedItems: function() {
        return [{
            xtype: 'textfield',
            reference: 'searchField',
            dock: 'top',
            hideFieldLabel: true,
            emptyText: this.searchText,
            cls: Ext.baseCSSPrefix + 'multiselector-search-input',
            triggers: {
                clear: {
                    cls: Ext.baseCSSPrefix + 'form-clear-trigger',
                    handler: 'onClearSearch',
                    hidden: true
                }
            },
            listeners: {
                specialKey: 'onSpecialKey',
                change: {
                    fn: 'onSearchChange',
                    buffer: 300
                }
            }
        }];
    },

    onSpecialKey: function(field, event) {
        if (event.getKey() === event.TAB && event.shiftKey) {
            event.preventDefault();
            this.owner.searchTool.focus();
        }
    },

    makeItems: function() {
        return [{
            xtype: 'grid',
            reference: 'searchGrid',
            trailingBufferZone: 2,
            leadingBufferZone: 2,
            viewConfig: {
                deferEmptyText: false,
                emptyText: 'No results.'
            }
        }];
    },

    getMatchingRecords: function(records) {
        var searchGrid = this.lookupReference('searchGrid'),
            store = searchGrid.getStore(),
            selections = [],
            i, record, len;

        records = Ext.isArray(records) ? records : [records];

        for (i = 0, len = records.length; i < len; i++) {
            record = store.getById(records[i].getId());

            if (record) {
                selections.push(record);
            }
        }

        return selections;
    },

    selectRecords: function(records) {
        var searchGrid = this.lookupReference('searchGrid');

        // match up passed records to the records in the search store so that the right
        // internal ids are used
        records = this.getMatchingRecords(records);

        return searchGrid.getSelectionModel().select(records);
    },

    deselectRecords: function(records) {
        var searchGrid = this.lookupReference('searchGrid');

        // match up passed records to the records in the search store so that the right
        // internal ids are used
        records = this.getMatchingRecords(records);

        return searchGrid.getSelectionModel().deselect(records);
    },

    search: function(text) {
        var me = this,
            filter = me.searchFilter,
            filters = me.getSearchStore().getFilters();

        if (text) {
            filters.beginUpdate();

            if (filter) {
                filter.setValue(text);
            }
            else {
                me.searchFilter = filter = new Ext.util.Filter({
                    id: 'search',
                    property: me.field,
                    value: text
                });
            }

            filters.add(filter);

            filters.endUpdate();
        }
        else if (filter) {
            filters.remove(filter);
        }
    },

    privates: {
        onClearSearch: function() {
            var searchField = this.lookupReference('searchField');

            searchField.setValue(null);
            searchField.focus();
        },

        onSearchChange: function(searchField) {
            var value = searchField.getValue(),
                trigger = searchField.getTrigger('clear');

            trigger.setHidden(!value);
            this.search(value);
        },

        onSelectionChange: function(selModel, selection) {
            var owner = this.owner,
                store = owner.getStore(),
                data = store.data,
                remove = 0,
                map = {},
                add, i, id, record;

            for (i = selection.length; i--;) {
                record = selection[i];
                id = record.id;
                map[id] = record;

                if (!data.containsKey(id)) {
                    (add || (add = [])).push(owner.convertSearchRecord(record));
                }
            }

            for (i = data.length; i--;) {
                record = data.getAt(i);

                if (!map[record.id]) {
                    (remove || (remove = [])).push(record);
                }
            }

            if (add || remove) {
                data.splice(data.length, remove, add);
            }
        }
    }
});
