/**
 * This class is used to store data on the client in a specified `storage` (either 'local'
 * or 'session'). Unlike the {@link Ext.data.proxy.LocalStorage localStorage proxy}, this
 * store uses a single `storageKey` to hold the entire contents of the store. This helps
 * reduce key overhead but also requires that all records and field be saved for any change.
 */
Ext.define('Ext.data.ClientStore', {
    extend: 'Ext.data.Store',
    alias: 'store.clientstorage',

    requires: [
        'Ext.data.proxy.Memory'
    ],

    config: {
        /**
         * @cfg {"local"/"session"} storage
         * Specify 'local' to use `localStorage` and 'session' to use `sessionStorage`.
         */
        storage: 'local',

        /**
         * @cfg {String} storageKey (required)
         * The key to use for saving the content of this store.
         */
        storageKey: null
    },

    trackRemoved: false,

    proxy: {
        type: 'memory',
        clearOnRead: true
    },

    sync: function(options) {
        var me = this,
            key = me._getKey(),
            storage = me.getStorage(),
            source = me.getDataSource(),
            proxy = me.getProxy(),
            writer = proxy.getWriter(),
            writeAll = writer.getWriteAllFields(),
            data = [];

        try {
            me.suspendAutoSync();
            writer.setWriteAllFields(true);

            source.each(function(rec) {
                if (rec.phantom) {
                    rec.setId(me.nextId());
                }

                data.push(writer.getRecordData(rec));
                rec.commit();
            });

            if (data.length) {
                data = JSON.stringify(data);
                data = storage.setItem(key, data);
            }
            else {
                storage.removeItem(key);
            }

            if (options && options.success) {
                Ext.callback(options.success, options.scope || proxy, [null, options]);
            }
        }
        catch (e) {
            if (options && options.failure) {
                Ext.callback(options.failure, options.scope || proxy, [null, options]);
            }
        }
        finally {
            me.resumeAutoSync();
            writer.setWriteAllFields(writeAll);
        }

        if (options && options.callback) {
            Ext.callback(options.callback, options.scope || proxy, [null, options]);
        }

        return me;
    },

    applyStorage: function(storage) {
        var ret = Ext.global[storage + 'Storage'];

        //<debug>
        if (!ret || !ret.getItem || !ret.setItem) {
            Ext.raise('Invalid storage config "' + storage + '"; ' +
                'expected "local" or "session"');
        }
        //</debug>

        return ret;
    },

    updateProxy: function(proxy, oldProxy) {
        var me = this,
            key = me._getKey(),
            storage = me.getStorage(),
            data;

        me.callParent([ proxy, oldProxy ]);

        data = storage.getItem(key);

        if (data) {
            proxy.setData(JSON.parse(data));
        }
    },

    privates: {
        _getKey: function() {
            var key = this.getStorageKey();

            //<debug>
            if (!key) {
                Ext.raise('ClientStore requires a storageKey');
            }
            //</debug>

            return key;
        },

        nextId: function() {
            var source = this.getDataSource(),
                id = 1;

            while (source.containsKey(id)) {
                ++id;
            }

            return id;
        }
    }
});
