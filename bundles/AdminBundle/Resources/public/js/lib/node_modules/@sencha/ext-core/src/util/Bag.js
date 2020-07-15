/**
 * This class provides an **unordered** collection similar to `Ext.util.Collection`. The
 * removal of order maintenance provides a significant performance increase. Further, this
 * class does not provide events or other high-level features. It maintains an array of
 * `items` and a map to quickly find items by their `id`.
 *
 * @private
 * @since 5.1.1
 */
Ext.define('Ext.util.Bag', {
    isBag: true,

    constructor: function() {
        /**
         * @property {Object[]} items
         * An array containing the items.
         * @private
         * @since 5.1.1
         */
        this.items = [];

        /**
         * @property {Object} map
         * An object used as a map to find items based on their key.
         * @private
         * @since 5.1.1
         */
        this.map = {};
    },

    /**
     * @property {Number} generation
     * Mutation counter which is incremented when the collection changes.
     * @readonly
     * @since 5.1.1
     */
    generation: 0,

    /**
     * @property {Number} length
     * The count of items in the collection.
     * @readonly
     * @since 5.1.1
     */
    length: 0,

    beginUpdate: Ext.emptyFn,

    endUpdate: Ext.emptyFn,

    add: function(item) {
        var me = this,
            items = me.items,
            map = me.map,
            n = 1,
            old, i, idx, id, it, ret, was;

        if (Ext.isArray(item)) {
            old = ret = [];
            n = item.length;
        }

        for (i = 0; i < n; i++) {
            id = me.getKey(it = old ? item[i] : item);
            idx = map[id];

            if (idx === undefined) {
                items.push(it);
                map[id] = me.length++;

                if (old) {
                    old.push(it);
                }
                else {
                    ret = it;
                }
            }
            else {
                was = items[idx];

                if (old) {
                    old.push(was);
                }
                else {
                    ret = was;
                }

                items[idx] = it;
            }
        }

        ++me.generation;

        return ret;
    },

    clear: function() {
        var me = this,
            needsClear = me.generation || me.length,
            ret = needsClear ? me.items : [];

        if (needsClear) {
            me.items = [];
            me.length = 0;
            me.map = {};
            ++me.generation;
        }

        return ret;
    },

    clone: function() {
        var me = this,
            ret = new me.self(),
            len = me.length;

        if (len) {
            Ext.apply(ret.map, me.map);
            ret.items = me.items.slice();
            ret.length = me.length;
        }

        return ret;
    },

    contains: function(item) {
        var ret = false,
            map = this.map,
            key;

        if (item != null) {
            key = this.getKey(item);

            if (key in map) {
                ret = this.items[map[key]] === item;
            }
        }

        return ret;
    },

    containsKey: function(key) {
        return key in this.map;
    },

    destroy: function() {
        this.items = this.map = null;
        this.callParent();
    },

    each: function(fn, scope) {
        var items = this.items,
            len = items.length,
            i, ret;

        if (len) {
            scope = scope || this;
            items = items.slice(0); // safe for re-entrant calls

            for (i = 0; i < len; i++) {
                ret = fn.call(scope, items[i], i, len);

                if (ret === false) {
                    break;
                }
            }
        }

        return ret;
    },

    getAt: function(index) {
        var out = null;

        if (index < this.length) {
            out = this.items[index];
        }

        return out;
    },

    get: function(key) {
        return this.getByKey(key);
    },

    getByKey: function(key) {
        var map = this.map,
            ret = (key in map) ? this.items[map[key]] : null;

        return ret;
    },

    indexOfKey: function(key) {
        var map = this.map,
            ret = (key in map) ? map[key] : -1;

        return ret;
    },

    last: function() {
        return this.items[this.length - 1];
    },

    updateKey: function(item, oldKey) {
        var me = this,
            map = me.map,
            newKey;

        if (!item || !oldKey) {
            return;
        }

        if ((newKey = me.getKey(item)) !== oldKey) {
            if (me.getAt(map[oldKey]) === item && !(newKey in map)) {
                me.generation++;
                map[newKey] = map[oldKey];
                delete map[oldKey];
            }
        }
        //<debug>
        else {
            // It may be that the item is (somehow) already in the map using the
            // newKey or that there is no item in the map with the oldKey. These
            // are not errors.

            if (newKey in map && me.getAt(map[newKey]) !== item) {
                // There is a different item in the map with the newKey which is an
                // error. To properly handle this, add the item instead.
                Ext.raise('Duplicate newKey "' + newKey +
                                '" for item with oldKey "' + oldKey + '"');
            }

            if (oldKey in map && me.getAt(map[oldKey]) !== item) {
                // There is a different item in the map with the oldKey which is also
                // an error. Do not call this method for items that are not part of
                // the collection.
                Ext.raise('Incorrect oldKey "' + oldKey +
                                '" for item with newKey "' + newKey + '"');
            }
        }
        //</debug>
    },

    getCount: function() {
        return this.length;
    },

    getKey: function(item) {
        return item.id || item.getId();
    },

    getRange: function(begin, end) {
        var items = this.items,
            length = items.length,
            range;

        if (!length) {
            range = [];
        }
        else {
            range = Ext.Number.clipIndices(length, [begin, end]);
            range = items.slice(range[0], range[1]);
        }

        return range;
    },

    remove: function(item) {
        var me = this,
            map = me.map,
            items = me.items,
            ret = null,
            n = 1,
            changed, old, i, idx, id, last, was;

        if (Ext.isArray(item)) {
            n = item.length;
            old = ret = [];
        }

        if (me.length) {
            for (i = 0; i < n; i++) {
                idx = map[id = me.getKey(old ? item[i] : item)];

                if (idx !== undefined) {
                    delete map[id];
                    was = items[idx];

                    if (old) {
                        old.push(was);
                    }
                    else {
                        ret = was;
                    }

                    last = items.pop();

                    if (idx < --me.length) {
                        items[idx] = last;
                        map[me.getKey(last)] = idx;
                    }

                    changed = true;
                }
            }

            if (changed) {
                ++me.generation;
            }
        }

        return ret;
    },

    removeByKey: function(key) {
        var item = this.getByKey(key);

        if (item) {
            this.remove(item);
        }

        return item || null;
    },

    replace: function(item) {
        this.add(item);

        return item;
    },

    sort: function(fn) {
        var me = this,
            items = me.items,
            n = items.length,
            item;

        if (n) {
            Ext.Array.sort(items, fn);

            me.map = {};

            while (n-- > 0) {
                item = items[n];
                me.map[me.getKey(item)] = n;
            }

            ++me.generation;
        }
    }
});
