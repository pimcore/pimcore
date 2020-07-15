/**
 * @class Ext.fx.Queue
 * Animation Queue mixin to handle chaining and queueing by target.
 * @private
 */

Ext.define('Ext.fx.Queue', {
    requires: ['Ext.util.HashMap'],

    constructor: function() {
        this.targets = new Ext.util.HashMap();
        this.fxQueue = {};
    },

    /**
     * @private
     */
    getFxDefaults: function(targetId) {
        var target = this.targets.get(targetId);

        if (target) {
            return target.fxDefaults;
        }

        return {};
    },

    /**
     * @private
     */
    setFxDefaults: function(targetId, obj) {
        var target = this.targets.get(targetId);

        if (target) {
            target.fxDefaults = Ext.apply(target.fxDefaults || {}, obj);
        }
    },

    /**
     * @private
     */
    stopAnimation: function(targetId, suppressEvent) {
        var me = this,
            queue = me.getFxQueue(targetId),
            ln = queue.length,
            item;

        while (ln) {
            item = queue[ln - 1];

            if (item) {
                item.end(suppressEvent);
            }

            ln--;
        }
    },

    /**
     * @private
     * Returns current animation object if the element has any effects actively running or queued,
     * else returns false.
     */
    getActiveAnimation: function(targetId) {
        var queue = this.getFxQueue(targetId);

        return (queue && !!queue.length) ? queue[0] : false;
    },

    /**
     * @private
     */
    hasFxBlock: function(targetId) {
        var queue = this.getFxQueue(targetId);

        return queue && queue[0] && queue[0].block;
    },

    /**
     * @private
     * Get fx queue for passed target, create if needed.
     */
    getFxQueue: function(targetId) {
        if (!targetId) {
            return false;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            fxQueue = me.fxQueue,
            queue = fxQueue[targetId],
            target = me.targets.get(targetId);

        if (!target) {
            return false;
        }

        if (!queue) {
            me.fxQueue[targetId] = fxQueue[targetId] = [];

            // GarbageCollector will need to clean up Elements since they
            // aren't currently observable
            if (target.type !== 'element') {
                target.target.on('destroy', function() {
                    fxQueue[targetId] = null;
                    delete fxQueue[targetId];
                });
            }
        }

        return me.fxQueue[targetId];
    },

    /**
     * @private
     * Clears the fx queue of any pending animations
     */
    clearFxQueue: function() {
        Ext.Object.clear(this.fxQueue);
    },

    /**
     * @private
     */
    queueFx: function(anim) {
        var me = this,
            target = anim.target,
            targetId = target.getId(),
            queue, ln;

        if (!target) {
            return;
        }

        queue = me.getFxQueue(targetId);
        ln = queue.length;

        if (ln) {
            if (anim.concurrent) {
                anim.paused = false;
            }
            else {
                queue[ln - 1].on('afteranimate', function() {
                    anim.paused = false;
                });
            }
        }
        else {
            anim.paused = false;
        }

        anim.on('afteranimate', function() {
            var el;

            Ext.Array.remove(queue, anim);

            if (queue.length === 0) {
                me.targets.remove(anim.target);
                me.fxQueue[targetId] = null;
                delete me.fxQueue[targetId];
            }

            if (anim.remove) {
                if (target.type === 'element') {
                    el = Ext.get(targetId);

                    if (el) {
                        el.destroy();
                    }
                }
            }
        }, me, {
            single: true
        });

        queue.push(anim);
    }
});
