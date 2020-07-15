/**
 * This class manages a double-linked list. It provides an absolutely minimal container
 * interface.
 *
 * @class Ext.util.LRU
 * @private
 * @since 6.5.0
 */
/* eslint-disable indent */
(function(LRU, prototype) {
// @define Ext.util.LRU

    // NOTE: We have to implement this class old-school because it is used by the
    // platformConfig class processor (so Ext.define is not yet ready for action).
    (Ext.util || (Ext.util = {})).LRU = LRU = function(config) {
        var me = this,
            head;

        if (config) {
            Ext.apply(me, config);
        }

        // Give all entries the same object shape.
        me.head = head = {
            //<debug>
            id: (me.seed = 0),
            //</debug>
            key: null,
            value: null
        };

        /**
         * @property {Object} map
         * The items in the list indexed by their `key`.
         * @readonly
         */
        me.map = {};

        head.next = head.prev = head;
    };

    LRU.prototype = prototype = {
        /**
         * @property {Number} count
         * The number of items in this list.
         * @readonly
         */
        count: 0,

        /**
         * Adds an item to the list with the specified `key`. Items are added at the
         * front (MRU) of the list.
         * @param {String} key
         * @param {Object} value
         */
        add: function(key, value) {
            var me = this,
                map = me.map,
                entry = map[key];

            if (entry) {
                me.unlink(entry);
                --me.count;
            }

            map[key] = entry = {
                //<debug>
                id: ++me.seed,
                //</debug>
                key: key,
                value: value
            };

            me.link(entry);
            ++me.count;

            return entry;
        },

        /**
         * Removes all items from this list optionally calling a function for each
         * remove item.
         * @param {Function} [fn] A function to call for each removed item.
         * @param {Object} fn.key The key of the removed item.
         * @param {Object} fn.value The removed item.
         * @param {Object} [scope] The `this` pointer for `fn`.
         */
        clear: function(fn, scope) {
            var me = this,
                head = me.head,
                entry = head.next;

            head.next = head.prev = head;

            me.count = 0;

            if (fn && !fn.$nullFn) {
                for (; entry !== head; entry = entry.next) {
                    fn.call(scope || me, entry.key, entry.value);
                }
            }
        },

        /**
         * Calls the given function `fn` for each item in the list. The items will be
         * passed to `fn` from most-to-least recently added or touched.
         * @param {Function} fn The function to call for each cache item.
         * @param {String} fn.key The key for the item.
         * @param {Object} fn.value The item.
         * @param {Object} [scope] The `this` pointer to use for `fn`.
         */
        each: function(fn, scope) {
            var head, ent;

            scope = scope || this;

            for (head = this.head, ent = head.next; ent !== head; ent = ent.next) {
                if (fn.call(scope, ent.key, ent.value)) {
                    break;
                }
            }
        },

        /**
         * Removes the item at the end (LRU) of the list. Optionally the item can be passed
         * to a callback function. If the list is empty, no callback is made and this
         * method will return `undefined`.
         * @param {Function} fn The function to call for the removed item.
         * @param {Object} fn.key The key of the removed item.
         * @param {Object} fn.value The removed item.
         * @param {Object} [scope] The `this` pointer to use for `fn`.
         * @return {Object} The removed item.
         */
        prune: function(fn, scope) {
            var me = this,
                entry = me.head.prev,
                ret;

            if (me.count) {
                ret = entry.value;
                me.unlink(entry);
                --me.count;

                if (fn) {
                    fn.call(scope || me, entry.key, ret);
                }
            }

            return ret;
        },

        /**
         * Removes an item from the list given its key.
         * @param {String} key The key of the item to remove.
         * @return {Object} The removed item or `undefined` if the key was not found.
         */
        remove: function(key) {
            var me = this,
                map = me.map,
                entry = map[key],
                value;

            if (entry) {
                me.unlink(entry);
                value = entry.value;
                delete map[key];
                --me.count;
            }

            return value;
        },

        /**
         * Moves the item with the given key to the front (MRU) of the list.
         * @param {String} key The key of the item to move to the front.
         */
        touch: function(key) {
            var me = this,
                head = me.head,
                entry = me.map[key];

            if (entry && entry.prev !== head) {
                // The entry is not at the front, so remove it and insert it at the front
                // (to make it the MRU - Most Recently Used).
                me.unlink(entry);
                me.link(entry);
            }
        },

        /**
         * Reduces the length of the list to be no more than the specified `size`, removing
         * items from the end of the list as necessary. Optionally each removed item can
         * be passed to a callback `fn`.
         * @param {Number} size The number of items in the list
         * @param {Function} [fn] A function to call for each removed item.
         * @param {Object} fn.key The key of the removed item.
         * @param {Object} fn.value The removed item.
         * @param {Object} [scope] The `this` pointer for `fn`.
         */
        trim: function(size, fn, scope) {
            while (this.count > size) {
                this.prune(fn, scope);
            }
        },

        //-------------------------------------------------------------------------
        // Internals

        /**
         * Inserts the given entry at the front (MRU) end of the entry list.
         * @param {Object} entry The cache item entry.
         * @private
         */
        link: function(entry) {
            var head = this.head,
                first = head.next;

            entry.next = first;
            entry.prev = head;
            head.next = entry;
            first.prev = entry;
        },

        /**
         * Removes the given entry from the entry list.
         * @param {Object} entry The cache item entry.
         * @private
         */
        unlink: function(entry) {
            var next = entry.next,
                prev = entry.prev;

            prev.next = next;
            next.prev = prev;
        }
    };

    prototype.destroy = function() {
        this.clear.apply(this, arguments);
    };
}());
