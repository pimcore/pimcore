/**
 * This mixin is used to track and listen to the `store` of its `owner` component. The
 * component must support a `storechange` event (as do grids and dataviews) as well as
 * a `getStore` method.
 * @since 6.5.0
 */
Ext.define('Ext.mixin.StoreWatcher', {
    mixinId: 'storewatcher',

    config: {
        dataSource: null,

        /**
         * @cfg {Ext.Base} owner
         */
        owner: null,

        /**
         * @cfg {Object} ownerListeners
         * The events and associated handlers to which to listen on the `owner`.
         */
        ownerListeners: {
            destroyable: true,
            storechange: 'onOwnerStoreChange'
        },

        /**
         * @cfg {Object} sourceListeners
         * The events and associated handlers to which to listen on the `source` of the
         * connected `store`. That is, these listeners are attached to the unfiltered
         * collection. When `remoteFilter` is `true` there is no unfiltered collection so
         * these listeners are attached to the only collection that exists (which is
         * filtered by the server).
         */
        sourceListeners: null,

        store: null,

        /**
         * @cfg {Object} storeListeners
         * The events and associated handlers to which to listen on the `store` of the
         * `owner`.
         */
        storeListeners: null
    },

    afterClassMixedIn: function(targetClass) {
        var configurator = this.getConfigurator(),
            prototype = targetClass.prototype,
            config = {},
            prop;

        for (prop in configurator.configs) {
            // For each of our configs, see if the class declared them as well. If so
            // we need to merge their values on top of ours and remove them from the
            // class prototype.
            if (prototype.hasOwnProperty(prop)) {
                config[prop] = prototype[prop];
                delete prototype[prop];
            }
        }

        targetClass.addConfig(config);
    },

    onOwnerStoreChange: function(comp, store) {
        this.setStore(store);
    },

    //---------------------------

    // dataSource

    updateDataSource: function(source) {
        this.syncListeners(source, '$sourceListeners', 'getSourceListeners');
    },

    // owner

    updateOwner: function(owner) {
        var me = this,
            ownerProperty = me.ownerProperty;

        if (ownerProperty) {
            me[ownerProperty] = owner;
        }

        me.syncListeners(owner, '$ownerListeners', 'getOwnerListeners');

        me.setStore(owner ? owner.getStore() : null);
    },

    // store

    applyStore: function(store) {
        return (store && !store.isEmptyStore) ? store : null;
    },

    updateStore: function(store) {
        this.syncListeners(store, '$storeListeners', 'getStoreListeners');

        this.syncDataSource();
    },

    privates: {
        syncDataSource: function() {
            var store = this.getStore(),
                source;

            if (!store) {
                source = null;
            }
            else if (store.getDataSource) {
                source = store.getDataSource();
            }
            else {
                source = store.getData();
            }

            this.setDataSource(source);
        },

        syncListeners: function(instance, token, listeners) {
            var me = this,
                old = me[token];

            if (old) {
                me[token] = null;
                old.destroy();
            }

            if (instance) {
                listeners = me[listeners]();
                listeners = Ext.applyIf({
                    destroyable: true,
                    scope: me
                }, listeners);

                me[token] = instance.on(listeners);
            }
        }
    }
});
