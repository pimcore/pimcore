/**
 * A chained store is a store that is a "view" of an existing store. The data comes from the
 * {@link #source}, however this view of the store may be sorted & filtered independently without
 * having any impact on the {@link #source} store.
 */
Ext.define('Ext.data.ChainedStore', {
    extend: 'Ext.data.AbstractStore',
    alias: 'store.chained',

    mixins: [
        'Ext.data.LocalStore'
    ],

    /**
     * @property {Boolean} isChainedStore
     * `true` in this class to identify an object as an instantiated ChainedStore, or subclass
     * thereof.
     */
    isChainedStore: true,

    config: {
        /**
         * @cfg {Ext.data.Store/String} source
         * The backing data source for this chained store. Either a store instance
         * or the id of an existing store.
         */
        source: null,

        remoteFilter: false,

        remoteSort: false
    },

    syncSourceGrouping: false,

    //<debug>
    updateRemoteFilter: function(remoteFilter, oldRemoteFilter) {
        if (remoteFilter) {
            Ext.raise('Remote filtering cannot be used with chained stores.');
        }

        this.callParent([remoteFilter, oldRemoteFilter]);
    },

    updateRemoteSort: function(remoteSort, oldRemoteSort) {
        if (remoteSort) {
            Ext.raise('Remote sorting cannot be used with chained stores.');
        }

        this.callParent([remoteSort, oldRemoteSort]);
    },
    //</debug>

    remove: function() {
        var source = this.getSource();

        //<debug>
        if (!source) {
            Ext.raise('Cannot remove records with no source.');
        }
        //</debug>

        return source.remove.apply(source, arguments);
    },

    removeAll: function() {
        var source = this.getSource();

        //<debug>
        if (!source) {
            Ext.raise('Cannot remove records with no source.');
        }
        //</debug>

        return source.removeAll();
    },

    getData: function() {
        var me = this,
            data = me.data;

        if (!data) {
            me.data = data = me.constructDataCollection();
        }

        return data;
    },

    getTotalCount: function() {
        return this.getCount();
    },

    getSession: function() {
        return this.getSourceValue('getSession', null);
    },

    applySource: function(source) {
        if (source) {
            //<debug>
            /* eslint-disable-next-line vars-on-top */
            var original = source,
                s;
            //</debug>

            source = Ext.data.StoreManager.lookup(source);

            //<debug>
            if (!source) {
                s = 'Invalid source {0}specified for Ext.data.ChainedStore';
                s = Ext.String.format(s, typeof original === 'string' ? '"' + original + '" ' : '');
                Ext.raise(s);
            }
            //</debug>
        }

        return source;
    },

    updateSource: function(source, oldSource) {
        var me = this,
            data;

        if (oldSource && !oldSource.destroyed) {
            oldSource.removeObserver(me);
        }

        if (source) {
            data = me.getData();
            data.setSource(source.getData());

            if (me.syncSourceGrouping) {
                me.setGrouper(source.getGrouper());
            }

            if (!me.isInitializing) {
                me.fireEvent('refresh', me);
                me.fireEvent('datachanged', me);
            }

            source.addObserver(me);
        }
    },

    /**
     * Get the model used for this store.
     * @return {Ext.data.Model} The model
     */
    getModel: function() {
        return this.getSourceValue('getModel', null);
    },

    getProxy: function() {
        return null;
    },

    onCollectionAdd: function(collection, info) {
        var me = this,
            records = info.items,
            lastChunk = !info.next;

        if (me.ignoreCollectionAdd) {
            return;
        }

        // Collection add changes the items reference of the collection, and that array
        // object if directly referenced by Ranges. The ranges have to refresh themselves
        // upon add.
        if (me.activeRanges) {
            me.syncActiveRanges();
        }

        me.fireEvent('add', me, records, info.at);

        // If there is a next property, that means there is another range that needs
        // to be removed after this. Wait until everything is gone before firign datachanged
        // since it should be a bulk operation
        if (lastChunk) {
            me.fireEvent('datachanged', me);
        }
    },

    // Our collection tells us that an item has changed
    onCollectionItemChange: function(collection, info) {
        var me = this,
            record = info.item,
            modifiedFieldNames = info.modified || null,
            type = info.meta;

        if (me.activeRanges && info.newIndex !== info.oldIndex) {
            me.syncActiveRanges();
        }

        // Inform any interested parties that a record has been mutated.
        // This will be invoked on TreeStores in which the invoking record
        // is an descendant of a collapsed node, and so *will not be contained by this store
        me.onUpdate(record, type, modifiedFieldNames, info);
        me.fireEvent('update', me, record, type, modifiedFieldNames, info);
        me.fireEvent('datachanged', me);
    },

    onCollectionUpdateKey: function(source, details) {
        // Must react to upstream Collection key update by firing idchanged event
        this.fireEvent('idchanged', this, details.item, details.oldKey, details.newKey);
    },

    onUpdate: Ext.emptyFn,

    lastCollectionRefesh: null,

    onCollectionRefresh: function(collection) {
        var me = this,
            gen = collection.generation;

        if (!me.isConfiguring && me.lastCollectionRefesh !== gen) {
            me.lastCollectionRefesh = gen;
            me.fireEvent('datachanged', me);
            me.fireEvent('refresh', me);
        }
    },

    onCollectionRemove: function(collection, info) {
        var me = this,
            records = info.items,
            lastChunk = !info.next;

        if (me.ignoreCollectionRemove) {
            return;
        }

        me.fireEvent('remove', me, records, info.at, false);

        // If there is a next property, that means there is another range that needs
        // to be removed after this. Wait until everything is gone before firign datachanged
        // since it should be a bulk operation
        if (lastChunk) {
            me.fireEvent('datachanged', me);
        }
    },

    onSourceBeforeLoad: function(source, operation) {
        this.fireEvent('beforeload', this, operation);
        this.callObservers('BeforeLoad', [operation]);
    },

    onSourceAfterLoad: function(source, records, successful, operation) {
        this.fireEvent('load', this, records, successful, operation);
        this.callObservers('AfterLoad', [records, successful, operation]);
    },

    onFilterEndUpdate: function() {
        var me = this;

        if (me.getData().generation === me.lastCollectionRefesh && !me.getRemoteFilter()) {
            me.suppressNextFilter = true;
        }

        me.callParent(arguments);

        me.callObservers('Filter');
    },

    onSourceBeforePopulate: function() {
        this.ignoreCollectionAdd = true;
        this.callObservers('BeforePopulate');
    },

    onSourceAfterPopulate: function() {
        var me = this;

        me.ignoreCollectionAdd = false;
        me.fireEvent('datachanged', me);
        me.fireEvent('refresh', me);

        this.callObservers('AfterPopulate');
    },

    onSourceBeforeClear: function() {
        this.ignoreCollectionRemove = true;
        this.callObservers('BeforeClear');
    },

    onSourceAfterClear: function() {
        this.ignoreCollectionRemove = false;
        this.callObservers('AfterClear');
    },

    onSourceBeforeRemoveAll: function() {
        this.ignoreCollectionRemove = true;
        this.callObservers('BeforeRemoveAll');
    },

    onSourceAfterRemoveAll: function(source, silent) {
        var me = this;

        me.ignoreCollectionRemove = false;

        if (!silent) {
            me.fireEvent('clear', me);
            me.fireEvent('datachanged', me);
        }

        this.callObservers('AfterRemoveAll', [silent]);
    },

    onSourceFilter: function() {
        var me = this,
            gen = me.getData().generation;

        if (me.lastCollectionRefesh !== gen) {
            me.lastCollectionRefesh = gen;
            me.fireEvent('refresh', me);
            me.fireEvent('datachanged', me);
        }
    },

    onSourceGrouperChange: function(source, grouper) {
        if (this.syncSourceGrouping) {
            this.setGrouper(grouper);
        }
    },

    hasPendingLoad: function() {
        return this.getSourceValue('hasPendingLoad', false);
    },

    isLoaded: function() {
        return this.getSourceValue('isLoaded', false);
    },

    isLoading: function() {
        return this.getSourceValue('isLoading', false);
    },

    doDestroy: function() {
        var me = this;

        me.observers = null;
        me.setSource(null);
        me.getData().destroy(true);
        me.data = null;

        me.callParent();
    },

    privates: {
        getSourceValue: function(method, defaultValue) {
            var source = this.getSource(),
                val = defaultValue;

            if (source && !source.destroyed) {
                val = source[method]();
            }

            return val;
        },

        isMoving: function() {
            var source = this.getSource();

            return source.isMoving ? source.isMoving.apply(source, arguments) : false;
        },

        loadsSynchronously: function() {
            return this.getSource().loadsSynchronously();
        }
    }

    // Provides docs from the mixin

    /**
     * @method add
     * @inheritdoc Ext.data.LocalStore#add
     */

    /**
     * @method each
     * @inheritdoc Ext.data.LocalStore#each
     */

    /**
     * @method collect
     * @inheritdoc Ext.data.LocalStore#collect
     */

    /**
     * @method getById
     * @inheritdoc Ext.data.LocalStore#getById
     */

    /**
     * @method getByInternalId
     * @inheritdoc Ext.data.LocalStore#getByInternalId
     */

    /**
     * @method indexOf
     * @inheritdoc Ext.data.LocalStore#indexOf
     */

    /**
     * @method indexOfId
     * @inheritdoc Ext.data.LocalStore#indexOfId
     */

    /**
     * @method insert
     * @inheritdoc Ext.data.LocalStore#insert
     */

    /**
     * @method queryBy
     * @inheritdoc Ext.data.LocalStore#queryBy
     */

    /**
     * @method query
     * @inheritdoc Ext.data.LocalStore#query
     */

    /**
     * @method first
     * @inheritdoc Ext.data.LocalStore#first
     */

    /**
     * @method last
     * @inheritdoc Ext.data.LocalStore#last
     */

    /**
     * @method sum
     * @inheritdoc Ext.data.LocalStore#sum
     */

    /**
     * @method count
     * @inheritdoc Ext.data.LocalStore#count
     */

    /**
     * @method min
     * @inheritdoc Ext.data.LocalStore#min
     */

    /**
     * @method max
     * @inheritdoc Ext.data.LocalStore#max
     */

    /**
     * @method average
     * @inheritdoc Ext.data.LocalStore#average
     */

    /**
     * @method aggregate
     * @inheritdoc Ext.data.LocalStore#aggregate
     */
});
