// @tag class
/**
 *
 * @since 6.7.0
 * @private
 */
Ext.define('Ext.mixin.Watchable', {
    on: function(name, fn, scope) {
        return this._watchUpdate(false, '_watchAdd', name, fn, scope);
    },

    fire: function(event, args) {
        var me = this,
            watching = me.watching,
            watchers = watching && watching[event],
            fn, i, r, scope;

        if (watchers) {
            ++watchers.$firing;

            for (i = 0; i < watchers.length; ++i) {
                scope = watchers[i][0];
                fn = watchers[i][1];

                if (fn.charAt) {
                    r = args ? scope[fn].apply(scope, args) : scope[fn]();
                }
                else {
                    r = args ? fn.apply(scope, args) : fn.call(scope);
                }

                if (r === false) {
                    return r;
                }
            }

            --watchers.$firing;
        }
    },

    fireEvent: function() {
        var args = Ext.Array.slice(arguments),
            event = args.shift();

        return this.fire(event, args);
    },

    un: function(name, fn, scope) {
        return this._watchUpdate(true, '_watchRemove', name, fn, scope);
    },

    privates: {
        watching: null,

        $watchOptions: {
            destroyable: 1,
            scope: 1
        },

        _watchAdd: function(watching, name, fn, scope, destroyable) {
            //<debug>
            if (typeof fn === 'string' && !scope[fn]) {
                Ext.raise('No such method "' + fn + '" on ' + scope.$className);
            }
            //</debug>

            // eslint-disable-next-line vars-on-top
            var watchers = watching[name],
                entry = [scope, fn],
                i, ent;

            if (!watchers) {
                watching[name] = watchers = [];
                watchers.$firing = 0;
            }
            else {
                // If the scope/fn pair is already registered, don't duplicate it.
                for (i = watchers.length; i-- > 0; /* empty */) {
                    ent = watchers[i];

                    if (fn === ent[1]) {
                        if (scope ? ent[0] === scope : !ent[0]) {
                            return;
                        }
                    }
                }

                if (watchers.$firing) {
                    watching[name] = watchers = watchers.slice();
                    watchers.$firing = 0;
                }
            }

            watchers.push(entry);

            if (destroyable) {
                entry.push(name);
                destroyable.items.push(entry);
            }
        },

        _watchRemove: function(watching, name, fn, scope) {
            var watchers = watching[name],
                i;

            if (watchers) {
                if (watchers.$firing) {
                    watching[name] = watchers = watchers.slice();
                    watchers.$firing = 0;
                }

                for (i = watchers.length; i-- > 0; /* empty */) {
                    if (watchers[i][0] === scope && watchers[i][1] === fn) {
                        watchers.splice(i, 1);
                    }
                }
            }
        },

        _watchUpdate: function(remove, process, name, fn, scope) {
            var me = this,
                watch = name,
                watching = me.watching,
                destroyable;

            if (!watching) {
                if (remove) {
                    return;
                }

                me.watching = watching = {};
            }

            if (typeof name === 'string') {
                me[process](watching, name, fn, scope);
            }
            else {
                destroyable = watch.destroyable
                    ? { owner: me, items: [], destroy: me._watcherDestroyer }
                    : null;

                scope = watch.scope;

                for (name in watch) {
                    if (!me.$watchOptions[name]) {
                        me[process](watching, name, watch[name], scope, destroyable);
                    }
                }
            }

            return destroyable;
        },

        _watcherDestroyer: function() {
            var me = this.owner,
                watching = me.watching,
                items = this.items,
                entry, i;

            for (i = 0; i < items.length; ++i) {
                entry = items[i];

                me._watchRemove(watching, entry[2], entry[1], entry[0]);
            }
        }
    }
});
