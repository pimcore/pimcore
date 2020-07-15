/**
 * @class Ext.draw.Animator
 *
 * Singleton class that manages the animation pool.
 */
Ext.define('Ext.draw.Animator', {
    uses: ['Ext.draw.Draw'],
    singleton: true,

    frameCallbacks: {},
    frameCallbackId: 0,
    scheduled: 0,
    frameStartTimeOffset: Ext.now(),
    animations: [],
    running: false,

    /**
     *  Cross platform `animationTime` implementation.
     *  @return {Number}
     */
    animationTime: function() {
        return Ext.AnimationQueue.frameStartTime - this.frameStartTimeOffset;
    },

    /**
     * Adds an animated object to the animation pool.
     *
     * @param {Object} animation The animation descriptor to add to the pool.
     */
    add: function(animation) {
        var me = this;

        if (!me.contains(animation)) {
            me.animations.push(animation);
            me.ignite();

            if ('fireEvent' in animation) {
                animation.fireEvent('animationstart', animation);
            }
        }
    },

    /**
     * Removes an animation from the pool.
     * TODO: This is broken when called within `step` method.
     * @param {Object} animation The animation to remove from the pool.
     */
    remove: function(animation) {
        var me = this,
            animations = me.animations,
            i = 0,
            l = animations.length;

        for (; i < l; ++i) {
            if (animations[i] === animation) {
                animations.splice(i, 1);

                if ('fireEvent' in animation) {
                    animation.fireEvent('animationend', animation);
                }

                return;
            }
        }
    },

    /**
     * Returns `true` or `false` whether it contains the given animation or not.
     *
     * @param {Object} animation The animation to check for.
     * @return {Boolean}
     */
    contains: function(animation) {
        return Ext.Array.indexOf(this.animations, animation) > -1;
    },

    /**
     * Returns `true` or `false` whether the pool is empty or not.
     * @return {Boolean}
     */
    empty: function() {
        return this.animations.length === 0;
    },

    idle: function() {
        return this.scheduled === 0 && this.animations.length === 0;
    },

    /**
     * Given a frame time it will filter out finished animations from the pool.
     *
     * @param {Number} frameTime The frame's start time, in milliseconds.
     */
    step: function(frameTime) {
        var me = this,
            animations = me.animations,
            animation,
            i = 0,
            ln = animations.length;

        for (; i < ln; i++) {
            animation = animations[i];
            animation.step(frameTime);

            if (!animation.animating) {
                animations.splice(i, 1);
                i--;
                ln--;

                if (animation.fireEvent) {
                    animation.fireEvent('animationend', animation);
                }
            }
        }
    },

    /**
     * Register a one-time callback that will be called at the next frame.
     * @param {Function/String} callback
     * @param {Object} scope
     * @return {String} The ID of the scheduled callback.
     */
    schedule: function(callback, scope) {
        var id = 'frameCallback' + (this.frameCallbackId++);

        scope = scope || this;

        if (Ext.isString(callback)) {
            callback = scope[callback];
        }

        Ext.draw.Animator.frameCallbacks[id] = { fn: callback, scope: scope, once: true };
        this.scheduled++;

        Ext.draw.Animator.ignite();

        return id;
    },

    /**
     * Register a one-time callback that will be called at the next frame,
     * if that callback (with a matching function and scope) isn't already scheduled.
     * @param {Function/String} callback
     * @param {Object} scope
     * @return {String/null} The ID of the scheduled callback or null, if that callback
     * has already been scheduled.
     */
    scheduleIf: function(callback, scope) {
        var frameCallbacks = Ext.draw.Animator.frameCallbacks,
            cb, id;

        scope = scope || this;

        if (Ext.isString(callback)) {
            callback = scope[callback];
        }

        for (id in frameCallbacks) {
            cb = frameCallbacks[id];

            if (cb.once && cb.fn === callback && cb.scope === scope) {
                return null;
            }
        }

        return this.schedule(callback, scope);
    },

    /**
     * Cancel a registered one-time callback
     * @param {String} id
     */
    cancel: function(id) {
        if (Ext.draw.Animator.frameCallbacks[id] && Ext.draw.Animator.frameCallbacks[id].once) {
            this.scheduled = Math.max(--this.scheduled, 0);
            delete Ext.draw.Animator.frameCallbacks[id];
            Ext.draw.Draw.endUpdateIOS();
        }

        if (this.idle()) {
            this.extinguish();
        }
    },

    clear: function() {
        this.animations.length = 0;
        Ext.draw.Animator.frameCallbacks = {};
        this.extinguish();
    },

    /**
     * Register a recursive callback that will be called at every frame.
     *
     * @param {Function} callback
     * @param {Object} scope
     * @return {String}
     */
    addFrameCallback: function(callback, scope) {
        var id = 'frameCallback' + (this.frameCallbackId++);

        scope = scope || this;

        if (Ext.isString(callback)) {
            callback = scope[callback];
        }

        Ext.draw.Animator.frameCallbacks[id] = { fn: callback, scope: scope };

        return id;
    },

    /**
     * Unregister a recursive callback.
     * @param {String} id
     */
    removeFrameCallback: function(id) {
        delete Ext.draw.Animator.frameCallbacks[id];

        if (this.idle()) {
            this.extinguish();
        }
    },

    /**
     * @private
     */
    fireFrameCallbacks: function() {
        var callbacks = this.frameCallbacks,
            id, fn, cb;

        for (id in callbacks) {
            cb = callbacks[id];
            fn = cb.fn;

            if (Ext.isString(fn)) {
                fn = cb.scope[fn];
            }

            fn.call(cb.scope);

            if (callbacks[id] && cb.once) {
                this.scheduled = Math.max(--this.scheduled, 0);
                delete callbacks[id];
            }
        }
    },

    handleFrame: function() {
        var me = this;

        me.step(me.animationTime());
        me.fireFrameCallbacks();

        if (me.idle()) {
            me.extinguish();
        }
    },

    ignite: function() {
        if (!this.running) {
            this.running = true;
            Ext.AnimationQueue.start(this.handleFrame, this);
            Ext.draw.Draw.beginUpdateIOS();
        }
    },

    extinguish: function() {
        this.running = false;
        Ext.AnimationQueue.stop(this.handleFrame, this);
        Ext.draw.Draw.endUpdateIOS();
    }
});
