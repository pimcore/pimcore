/**
 * This class is used to manage simple, LRU caches. It provides an absolutely minimal
 * container interface. It is created like this:
 *
 *      this.itemCache = new Ext.util.Cache({
 *          miss: function (key) {
 *              return new CacheItem(key);
 *          }
 *      });
 *
 * The `{@link #miss}` abstract method must be implemented by either a derived class or
 * at the instance level as shown above.
 *
 * Once the cache exists and it can handle cache misses, the cache is used like so:
 *
 *      var item = this.itemCache.get(key);
 *
 * The `key` is some value that uniquely identifies the cached item.
 *
 * In some cases, creating the cache item may require more than just the lookup key. In
 * that case, any extra arguments passed to `get` will be passed to `miss`.
 *
 *      this.otherCache = new Ext.util.Cache({
 *          miss: function (key, extra) {
 *              return new CacheItem(key, extra);
 *          }
 *      });
 *
 *      var item = this.otherCache.get(key, extra);
 *
 * To process items as they are removed, you can provide an `{@link #evict}` method. The
 * stock method is `Ext.emptyFn` and so does nothing.
 *
 * For example:
 *
 *      this.itemCache = new Ext.util.Cache({
 *          miss: function (key) {
 *              return new CacheItem(key);
 *          },
 *
 *          evict: function (key, cacheItem) {
 *              cacheItem.destroy();
 *          }
 *      });
 *
 * @class Ext.util.Cache
 * @private
 * @since 5.1.0
 */
/* eslint-disable indent */
(function(LRU, fn, Cache) {
// @require Ext.util.LRU
// @define Ext.util.Cache

    // NOTE: We have to implement this class old-school because it is used by the
    // platformConfig class processor (so Ext.define is not yet ready for action).
    Ext.util.Cache = Cache = function(config) {
        LRU.call(this, config);
    };

    fn.prototype = LRU.prototype;

    Cache.prototype = Ext.apply(new fn(), {
        /**
         * @cfg {Number} maxSize The maximum size the cache is allowed to grow to before
         * further additions cause removal of the least recently used entry.
         */
        maxSize: 100,

        /**
         * This method is called by `{@link #get}` when the key is not found in the cache.
         * The implementation of this method should create the (expensive) value and return
         * it. Whatever arguments were passed to `{@link #get}` will be passed on to this
         * method.
         *
         * @param {String} key The cache lookup key for the item.
         * @param {Object...} args Any other arguments originally passed to `{@link #get}`.
         * @method miss
         * @abstract
         * @protected
         */

        /**
         * Removes all items from this cache.
         */
        clear: function() {
            LRU.prototype.clear.call(this, this.evict);
        },

        /**
         * Finds an item in this cache and returns its value. If the item is present, it is
         * shuffled into the MRU (most-recently-used) position as necessary. If the item is
         * missing, the `{@link #miss}` method is called to create the item.
         *
         * @param {String} key The cache key of the item.
         * @param {Object...} args Arguments for the `miss` method should it be needed.
         * @return {Object} The cached object.
         */
        get: function(key) {
            var me = this,
                entry = me.map[key],
                value;

            if (entry) {
                value = entry.value;

                me.touch(key);
            }
            else {
                value = me.miss.apply(me, arguments);

                me.add(key, value);
                me.trim(me.maxSize, me.evict);
            }

            return value;
        },

        //-------------------------------------------------------------------------
        // Internals

        /**
         * This method is called internally from `{@link #get}` when the cache is full and
         * the least-recently-used (LRU) item has been removed.
         *
         * @param {String} key The cache lookup key for the item being removed.
         * @param {Object} value The cache value (returned by `{@link #miss}`) for the item
         * being removed.
         * @method evict
         * @template
         * @protected
         */
        evict: Ext.emptyFn
    });
}(Ext.util.LRU, function() {}));
