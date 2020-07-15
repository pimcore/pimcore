/**
 * The Animation modifier.
 *
 * Sencha Charts allow users to use transitional animation on sprites. Simply set the duration
 * and easing in the animation modifier, then all the changes to the sprites will be animated.
 * 
 *     @example
 *     var drawCt = Ext.create({
 *         xtype: 'draw',
 *         renderTo: document.body,
 *         width: 400,
 *         height: 400,
 *         sprites: [{
 *             type: 'rect',
 *             x: 50,
 *             y: 50,
 *             width: 100,
 *             height: 100,
 *             fillStyle: '#1F6D91'
 *         }]
 *     });
 *     
 *     var rect = drawCt.getSurface().getItems()[0];
 *     
 *     rect.setAnimation({
 *         duration: 1000,
 *         easing: 'elasticOut'
 *     });
 *     
 *     Ext.defer(function () {
 *         rect.setAttributes({
 *             width: 250
 *         });
 *     }, 500);
 *
 * Also, you can use different durations and easing functions on different attributes by using
 * {@link #customDurations} and {@link #customEasings}.
 *
 * By default, an animation modifier will be created during the initialization of a sprite.
 * You can get the animation modifier of a sprite via its 
 * {@link Ext.draw.sprite.Sprite#method-getAnimation getAnimation} method.
 */
Ext.define('Ext.draw.modifier.Animation', {
    extend: 'Ext.draw.modifier.Modifier',
    alias: 'modifier.animation',

    requires: [
        'Ext.draw.TimingFunctions',
        'Ext.draw.Animator'
    ],

    config: {
        /**
         * @cfg {Function} easing
         * Default easing function.
         */
        easing: Ext.identityFn,

        /**
         * @cfg {Number} duration
         * Default duration time (ms).
         */
        duration: 0,

        /**
         * @cfg {Object} customEasings Overrides the default easing function for defined attributes.
         * E.g.:
         *
         *     // Assuming the sprite the modifier is applied to is a 'circle'.
         *     customEasings: {
         *         r: 'easeOut',
         *         'fillStyle,strokeStyle': 'linear',
         *         'cx,cy': function (p, n) {
         *             p = 1 - p;
         *             n = n || 1.616;
         *             return 1 - p * p * ((n + 1) * p - n);
         *         }
         *     }
         */
        customEasings: {},

        /**
         * @cfg {Object} customDurations Overrides the default duration for defined attributes.
         * E.g.:
         *
         *     // Assuming the sprite the modifier is applied to is a 'circle'.
         *     customDurations: {
         *         r: 1000,
         *         'fillStyle,strokeStyle': 2000,
         *         'cx,cy': 1000
         *     }
         */
        customDurations: {}
    },

    constructor: function(config) {
        var me = this;

        me.anyAnimation = me.anySpecialAnimations = false;
        me.animating = 0;
        me.animatingPool = [];
        me.callParent([config]);
    },

    prepareAttributes: function(attr) {
        if (!attr.hasOwnProperty('timers')) {
            attr.animating = false;
            attr.timers = {};
            // The 'targets' object is used to hold the target values for the
            // attributes while they are being animated from source to target values.
            // The 'targets' is pushed down to the lower level modifiers,
            // instead of the actual attr object, to hide the fact that the
            // attributes are being animated.
            attr.targets = Ext.Object.chain(attr);
            attr.targets.prototype = attr;
        }

        if (this._lower) {
            this._lower.prepareAttributes(attr.targets);
        }
    },

    updateSprite: function(sprite) {
        this.setConfig(sprite.config.animation);
    },

    updateDuration: function(duration) {
        this.anyAnimation = duration > 0;
    },

    applyEasing: function(easing) {
        if (typeof easing === 'string') {
            easing = Ext.draw.TimingFunctions.easingMap[easing];
        }

        return easing;
    },

    applyCustomEasings: function(newEasings, oldEasings) {
        var any, key, attrs, easing, i, ln;

        oldEasings = oldEasings || {};

        for (key in newEasings) {
            any = true;
            easing = newEasings[key];
            attrs = key.split(',');

            if (typeof easing === 'string') {
                easing = Ext.draw.TimingFunctions.easingMap[easing];
            }

            for (i = 0, ln = attrs.length; i < ln; i++) {
                oldEasings[attrs[i]] = easing;
            }
        }

        if (any) {
            this.anySpecialAnimations = any;
        }

        return oldEasings;
    },

    /**
     * Set special easings on the given attributes. E.g.:
     *
     *     circleSprite.getAnimation().setEasingOn('r', 'elasticIn');
     *
     * @param {String/Array} attrs The source attribute(s).
     * @param {String} easing The special easings.
     */
    setEasingOn: function(attrs, easing) {
        var customEasings = {},
            i, ln;

        attrs = Ext.Array.from(attrs).slice();

        for (i = 0, ln = attrs.length; i < ln; i++) {
            customEasings[attrs[i]] = easing;
        }

        this.setCustomEasings(customEasings);
    },

    /**
     * Remove special easings on the given attributes.
     * @param {String/Array} attrs The source attribute(s).
     */
    clearEasingOn: function(attrs) {
        var i, ln;

        attrs = Ext.Array.from(attrs, true);

        for (i = 0, ln = attrs.length; i < ln; i++) {
            delete this._customEasings[attrs[i]];
        }
    },

    applyCustomDurations: function(newDurations, oldDurations) {
        var any, key, duration, attrs, i, ln;

        oldDurations = oldDurations || {};

        for (key in newDurations) {
            any = true;
            duration = newDurations[key];
            attrs = key.split(',');

            for (i = 0, ln = attrs.length; i < ln; i++) {
                oldDurations[attrs[i]] = duration;
            }
        }

        if (any) {
            this.anySpecialAnimations = any;
        }

        return oldDurations;
    },

    /**
     * Set special duration on the given attributes. E.g.:
     *
     *     rectSprite.getAnimation().setDurationOn('height', 2000);
     *
     * @param {String/Array} attrs The source attributes.
     * @param {Number} duration The special duration.
     */
    setDurationOn: function(attrs, duration) {
        var customDurations = {},
            i, ln;

        attrs = Ext.Array.from(attrs).slice();

        for (i = 0, ln = attrs.length; i < ln; i++) {
            customDurations[attrs[i]] = duration;
        }

        this.setCustomDurations(customDurations);
    },

    /**
     * Remove special easings on the given attributes.
     * @param {Object} attrs The source attributes.
     */
    clearDurationOn: function(attrs) {
        var i, ln;

        attrs = Ext.Array.from(attrs, true);

        for (i = 0, ln = attrs.length; i < ln; i++) {
            delete this._customDurations[attrs[i]];
        }
    },

    /**
     * @private
     * Initializes Animator for the animation.
     * @param {Object} attr The source attributes.
     * @param {Boolean} animating The animating flag.
     */
    setAnimating: function(attr, animating) {
        var me = this,
            pool = me.animatingPool,
            i;

        if (attr.animating !== animating) {
            attr.animating = animating;

            if (animating) {
                pool.push(attr);

                if (me.animating === 0) {
                    Ext.draw.Animator.add(me);
                }

                me.animating++;
            }
            else {
                for (i = pool.length; i--;) {
                    if (pool[i] === attr) {
                        pool.splice(i, 1);
                    }
                }

                me.animating = pool.length;
            }
        }
    },

    /**
     * @private
     * Set the attr with given easing and duration.
     * @param {Object} attr The attributes collection.
     * @param {Object} changes The changes that popped up from lower modifier.
     * @return {Object} The changes to pop up.
     */
    setAttrs: function(attr, changes) {
        var me = this,
            timers = attr.timers,
            parsers = me._sprite.self.def._animationProcessors,
            defaultEasing = me._easing,
            defaultDuration = me._duration,
            customDurations = me._customDurations,
            customEasings = me._customEasings,
            anySpecial = me.anySpecialAnimations,
            any = me.anyAnimation || anySpecial,
            targets = attr.targets,
            ignite = false,
            timer, name, newValue, startValue, parser, easing, duration, initial;

        if (!any) { // If there is no animation enabled.
            // When applying changes to attributes, simply stop current animation
            // and set the value.
            for (name in changes) {
                if (attr[name] === changes[name]) {
                    delete changes[name];
                }
                else {
                    attr[name] = changes[name];
                }

                delete targets[name];
                delete timers[name];
            }

            return changes;
        }
        else { // If any animation.
            for (name in changes) {
                newValue = changes[name];
                startValue = attr[name];

                if (newValue !== startValue && startValue !== undefined && startValue !== null &&
                    (parser = parsers[name])) {
                    // If this property is animating.

                    // Figure out the desired duration and easing.
                    easing = defaultEasing;
                    duration = defaultDuration;

                    if (anySpecial) {
                        // Deducing the easing function and duration
                        if (name in customEasings) {
                            easing = customEasings[name];
                        }

                        if (name in customDurations) {
                            duration = customDurations[name];
                        }
                    }

                    // Transitions betweens color and gradient or between gradients
                    // are not supported.
                    if (startValue && startValue.isGradient || newValue && newValue.isGradient) {
                        duration = 0;
                    }

                    // If the property is animating
                    if (duration) {
                        if (!timers[name]) {
                            timers[name] = {};
                        }

                        timer = timers[name];
                        timer.start = 0;
                        timer.easing = easing;
                        timer.duration = duration;
                        timer.compute = parser.compute;
                        timer.serve = parser.serve || Ext.identityFn;
                        timer.remove = changes.removeFromInstance &&
                                       changes.removeFromInstance[name];

                        if (parser.parseInitial) {
                            initial = parser.parseInitial(startValue, newValue);

                            timer.source = initial[0];
                            timer.target = initial[1];
                        }
                        else if (parser.parse) {
                            timer.source = parser.parse(startValue);
                            timer.target = parser.parse(newValue);
                        }
                        else {
                            timer.source = startValue;
                            timer.target = newValue;
                        }

                        // The animation started. Change to originalVal.
                        targets[name] = newValue;
                        delete changes[name];
                        ignite = true;

                        continue;
                    }
                    else {
                        delete targets[name];
                    }
                }
                else {
                    delete targets[name];
                }

                // If the property is not animating.
                delete timers[name];
            }
        }

        if (ignite && !attr.animating) {
            me.setAnimating(attr, true);
        }

        return changes;
    },

    /**
     * @private
     *
     * Update attributes to current value according to current animation time.
     * This method will not affect the values of lower layers, but may delete a
     * value from it.
     * @param {Object} attr The source attributes.
     * @return {Object} The changes to pop up or null.
     */
    updateAttributes: function(attr) {
        if (!attr.animating) {
            return {};
        }

        // eslint-disable-next-line vars-on-top
        var changes = {},
            any = false,
            timers = attr.timers,
            targets = attr.targets,
            now = Ext.draw.Animator.animationTime(),
            name, timer, delta;

        // If updated in the same frame, return.
        if (attr.lastUpdate === now) {
            return null;
        }

        for (name in timers) {
            timer = timers[name];

            if (!timer.start) {
                timer.start = now;
                delta = 0;
            }
            else {
                delta = (now - timer.start) / timer.duration;
            }

            if (delta >= 1) {
                changes[name] = targets[name];
                delete targets[name];

                if (timers[name].remove) {
                    changes.removeFromInstance = changes.removeFromInstance || {};
                    changes.removeFromInstance[name] = true;
                }

                delete timers[name];
            }
            else {
                changes[name] = timer.serve(
                    timer.compute(timer.source, timer.target, timer.easing(delta), attr[name])
                );

                any = true;
            }
        }

        attr.lastUpdate = now;
        this.setAnimating(attr, any);

        return changes;
    },

    pushDown: function(attr, changes) {
        changes = this.callParent([attr.targets, changes]);

        return this.setAttrs(attr, changes);
    },

    popUp: function(attr, changes) {
        attr = attr.prototype;
        changes = this.setAttrs(attr, changes);

        if (this._upper) {
            return this._upper.popUp(attr, changes);
        }
        else {
            return Ext.apply(attr, changes);
        }
    },

    /**
     * @private
     * This is called as an animated object in `Ext.draw.Animator`.
     */
    step: function(frameTime) {
        var me = this,
            pool = me.animatingPool.slice(),
            ln = pool.length,
            i = 0,
            attr, changes;

        for (; i < ln; i++) {
            attr = pool[i];
            changes = me.updateAttributes(attr);

            if (changes && me._upper) {
                me._upper.popUp(attr, changes);
            }
        }
    },

    /**
     * Stop all animations affected by this modifier.
     */
    stop: function() {
        var me = this,
            pool = me.animatingPool,
            i, ln;

        this.step();

        for (i = 0, ln = pool.length; i < ln; i++) {
            pool[i].animating = false;
        }

        me.animatingPool.length = 0;
        me.animating = 0;
        Ext.draw.Animator.remove(me);
    },

    destroy: function() {
        Ext.draw.Animator.remove(this);
        this.callParent();
    }
});
