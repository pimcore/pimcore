/**
 * This is a utility class for being able to track all items of a particular type
 * inside any level at a container. This can be used in favour of bubbling add/remove events
 * which can add a large perf cost when implemented globally
 * @private
 */
Ext.define('Ext.container.Monitor', {
    target: null,
    selector: '',

    scope: null,
    addHandler: null,
    removeHandler: null,
    invalidateHandler: null,

    clearPropertiesOnDestroy: false,
    clearPrototypeOnDestroy: false,

    disabled: 0,

    constructor: function(config) {
        Ext.apply(this, config);
    },

    destroy: function() {
        this.unbind();

        this.callParent();
    },

    bind: function(target) {
        var me = this;

        me.target = target;
        target.on('beforedestroy', me.disable, me);
        me.onContainerAdd(target);
    },

    unbind: function() {
        var me = this,
            target = me.target;

        if (target && !target.destroying && !target.destroyed) {
            me.onContainerRemove(target, target);
            target.un('beforedestroy', me.disable, me);
        }

        me.items = Ext.destroy(me.items);
    },

    disable: function() {
        ++this.disabled;
    },

    enable: function() {
        if (this.disabled > 0) {
            --this.disabled;
        }
    },

    handleAdd: function(ct, comp) {
        if (!this.disabled) {
            if (Ext.ComponentQuery.is(comp, this.selector)) {
                this.onItemAdd(comp.ownerCt, comp);
            }

            if (comp.isQueryable) {
                this.onContainerAdd(comp);
            }
        }
    },

    onItemAdd: function(ct, comp) {
        var me = this,
            items = me.items,
            handler = me.addHandler;

        if (!me.disabled) {
            if (handler) {
                handler.call(me.scope || comp, comp);
            }

            if (items) {
                items.add(comp);
            }
        }

        // This is a temporary hack until we refactor Forms
        // and kill off Ext.container.Monitor
        comp.clearPropertiesOnDestroy = comp.clearPrototypeOnDestroy = false;
    },

    onItemRemove: function(ct, comp) {
        var me = this,
            items = me.items,
            handler = me.removeHandler;

        if (!me.disabled) {
            if (handler) {
                handler.call(me.scope || comp, comp);
            }

            if (items) {
                items.remove(comp);
            }
        }
    },

    onContainerAdd: function(ct, preventChildren) {
        var me = this,
            items, len,
            i, comp;

        if (ct.isContainer) {
            ct.on({
                scope: me,
                add: me.handleAdd,
                dockedadd: me.handleAdd,
                remove: me.handleRemove,
                dockedremove: me.handleRemove
            });
        }

        // Means we've been called by a parent container so the selector
        // matchers have already been processed
        if (preventChildren !== true) {
            items = ct.query(me.selector);

            for (i = 0, len = items.length; i < len; ++i) {
                comp = items[i];
                me.onItemAdd(comp.ownerCt, comp);
            }
        }

        items = ct.query('>container');

        for (i = 0, len = items.length; i < len; ++i) {
            me.onContainerAdd(items[i], true);
        }

        // This is a temporary hack until we refactor Forms
        // and kill off Ext.container.Monitor
        ct.clearPropertiesOnDestroy = ct.clearPrototypeOnDestroy = false;
    },

    handleRemove: function(ct, comp) {
        var me = this;

        // During a destroy we don't want to maintain any of this information,
        // so typically we'll be disabled here
        if (!me.disabled) {
            if (Ext.ComponentQuery.is(comp, me.selector)) {
                me.onItemRemove(ct, comp);
            }

            if (comp.isQueryable) {
                me.onContainerRemove(ct, comp);
            }
        }
    },

    onContainerRemove: function(ct, comp) {
        var me = this,
            items, i, len, item;

        // If it's not a container, it means it's a queryable that isn't a container.
        // For example a button with a menu
        if (!comp.destroyed && comp.isContainer) {
            me.removeCtListeners(comp);

            if (!comp.destroying) {
                items = comp.query(me.selector);

                for (i = 0, len = items.length; i < len; ++i) {
                    item = items[i];
                    me.onItemRemove(item.ownerCt, item);
                }

                items = comp.query('container');

                for (i = 0, len = items.length; i < len; ++i) {
                    me.removeCtListeners(items[i]);
                }
            }
        }

        // comp destroying, or we need to invalidate the collection
        me.invalidateItems(true);
    },

    removeCtListeners: function(ct) {
        var me = this;

        ct.un({
            scope: me,
            add: me.handleAdd,
            dockedadd: me.handleAdd,
            remove: me.handleRemove,
            dockedremove: me.handleRemove
        });
    },

    getItems: function() {
        var me = this,
            items = me.items;

        if (!items) {
            items = me.items = new Ext.util.MixedCollection();
            items.addAll(me.target.query(me.selector));
        }

        return items;
    },

    invalidateItems: function(triggerHandler) {
        var me = this,
            handler = me.invalidateHandler;

        if (triggerHandler && handler) {
            handler.call(me.scope || me, me);
        }

        me.items = Ext.destroy(me.items);
    }
});
