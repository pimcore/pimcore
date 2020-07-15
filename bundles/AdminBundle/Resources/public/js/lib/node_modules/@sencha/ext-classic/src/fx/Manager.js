/**
 * @class Ext.fx.Manager
 * Animation Manager which keeps track of all current animations and manages them
 * on a frame by frame basis.
 * @private
 * @singleton
 */

Ext.define('Ext.fx.Manager', {
    singleton: true,

    requires: [
        'Ext.util.MixedCollection',
        'Ext.util.TaskRunner',
        'Ext.fx.target.Element',
        'Ext.fx.target.ElementCSS',
        'Ext.fx.target.CompositeElement',
        'Ext.fx.target.CompositeElementCSS',
        'Ext.fx.target.Sprite',
        'Ext.fx.target.CompositeSprite',
        'Ext.fx.target.Component'
    ],

    mixins: {
        queue: 'Ext.fx.Queue'
    },

    /**
     * @private
     */
    constructor: function() {
        var me = this;

        me.items = new Ext.util.MixedCollection();
        me.targetArr = {};
        me.mixins.queue.constructor.call(me);

        // Do not use fireIdleEvent: false. Each tick of the TaskRunner needs to fire the idleEvent
        // in case an animation callback/listener adds a listener.
        me.taskRunner = new Ext.util.TaskRunner();
    },

    /**
     * @cfg {Number} interval Default interval in miliseconds to calculate each frame.
     * Defaults to 16ms (~60fps)
     */
    interval: 16,

    /**
     * @cfg {Boolean} forceJS Force the use of JavaScript-based animation instead of CSS3 animation,
     * even when CSS3 animation is supported by the browser. This defaults to true currently,
     * as CSS3 animation support is still considered experimental at this time, and if used
     * should be thouroughly tested across all targeted browsers.
     * @protected
     */
    forceJS: true,

    /**
     * @private
     * Target Factory
     */
    createTarget: function(target) {
        var me = this,
            useCSS3 = !me.forceJS && Ext.supports.Transitions,
            targetObj;

        me.useCSS3 = useCSS3;

        if (target) {
            // dom element, string or fly
            if (target.tagName || Ext.isString(target) || target.isFly) {
                target = Ext.get(target);
                targetObj = new Ext.fx.target['Element' + (useCSS3 ? 'CSS' : '')](target);
            }
            // Element
            else if (target.dom) {
                targetObj = new Ext.fx.target['Element' + (useCSS3 ? 'CSS' : '')](target);
            }
            // Element Composite
            else if (target.isComposite) {
                targetObj = new Ext.fx.target['CompositeElement' + (useCSS3 ? 'CSS' : '')](target);
            }
            // Draw Sprite
            else if (target.isSprite) {
                targetObj = new Ext.fx.target.Sprite(target);
            }
            // Draw Sprite Composite
            else if (target.isCompositeSprite) {
                targetObj = new Ext.fx.target.CompositeSprite(target);
            }
            // Component
            else if (target.isComponent) {
                targetObj = new Ext.fx.target.Component(target);
            }
            else if (target.isAnimTarget) {
                return target;
            }
            else {
                return null;
            }

            me.targets.add(targetObj);

            return targetObj;
        }
        else {
            return null;
        }
    },

    /**
     * Add an Anim to the manager. This is done automatically when an Anim instance is created.
     * @param {Ext.fx.Anim} anim
     */
    addAnim: function(anim) {
        var me = this,
            items = me.items,
            task = me.task;

        // Make sure we use the anim's id, not the anim target's id here. The anim id will be unique
        // on each call to addAnim. `anim.target` is the DOM element being targeted, and since
        // multiple animations can target a single DOM node concurrently, the target id cannot
        // be assumned to be unique.
        items.add(anim.id, anim);

        // Start the timer if not already running
        if (!task && items.length) {
            task = me.task = {
                run: me.runner,
                interval: me.interval,
                scope: me
            };

            me.taskRunner.start(task);
        }
    },

    /**
     * Remove an Anim from the manager. This is done automatically when an Anim ends.
     * @param {Ext.fx.Anim} anim
     */
    removeAnim: function(anim) {
        var me = this,
            items = me.items,
            task = me.task;

        items.removeAtKey(anim.id);

        // Stop the timer if there are no more managed Anims
        if (task && !items.length) {
            me.taskRunner.stop(task);
            delete me.task;
        }
    },

    /**
     * @private
     * Runner function being called each frame
     */
    runner: function() {
        var me = this,
            items = me.items.getRange(),
            i = 0,
            len = items.length,
            anim;

        me.targetArr = {};

        // Single timestamp for all animations this interval
        me.timestamp = new Date();

        // Loop to start any new animations first before looping to
        // execute running animations (which will also include all animations
        // started in this loop). This is a subtle difference from simply
        // iterating in one loop and starting then running each animation,
        // but separating the loops is necessary to ensure that all new animations
        // actually kick off prior to existing ones regardless of array order.
        // Otherwise in edge cases when there is excess latency in overall
        // performance, allowing existing animations to run before new ones can
        // lead to dropped frames and subtle race conditions when they are
        // interdependent, which is often the case with certain Element fx.
        for (; i < len; i++) {
            anim = items[i];

            if (anim.isReady()) {
                me.startAnim(anim);
            }
        }

        for (i = 0; i < len; i++) {
            anim = items[i];

            if (anim.isRunning()) {
                me.runAnim(anim);
            }
        }

        // Apply all the pending changes to their targets
        me.applyPendingAttrs();

        // Avoid retaining target references after we are finished with anims
        me.targetArr = null;
    },

    /**
     * @private
     * Start the individual animation (initialization)
     */
    startAnim: function(anim) {
        anim.start(this.timestamp);
    },

    /**
     * @private
     * Run the individual animation for this frame
     */
    runAnim: function(anim, forceEnd) {
        if (!anim) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            useCSS3 = me.useCSS3 && anim.target.type === 'element',
            elapsedTime = me.timestamp - anim.startTime,
            lastFrame = (elapsedTime >= anim.duration),
            target, o;

        if (forceEnd) {
            elapsedTime = anim.duration;
            lastFrame = true;
        }

        target = me.collectTargetData(anim, elapsedTime, useCSS3, lastFrame);

        // For CSS3 animation, we need to immediately set the first frame's attributes
        // without any transition to get a good initial state, then add the transition
        // properties and set the final attributes.
        if (useCSS3) {
            // Flush the collected attributes, without transition
            anim.target.setAttr(target.anims[anim.id].attributes, true);

            // Add the end frame data
            me.collectTargetData(anim, anim.duration, useCSS3, lastFrame);

            // Pause the animation so runAnim doesn't keep getting called
            anim.paused = true;

            target = anim.target.target;

            // We only want to attach an event on the last element in a composite
            if (anim.target.isComposite) {
                target = anim.target.target.last();
            }

            // Listen for the transitionend event
            o = {};
            o[Ext.supports.CSS3TransitionEnd] = anim.lastFrame;
            o.scope = anim;
            o.single = true;
            target.on(o);
        }

        return target;
    },

    jumpToEnd: function(anim) {
        var me = this,
            target, clear;

        // We may not be in the middle of a tick, where targetAttr is cleared,
        // so if we don't have it, poke it in here while we jump to the end state
        if (!me.targetArr) {
            me.targetArr = {};
            clear = true;
        }

        target = me.runAnim(anim, true);
        me.applyAnimAttrs(target, target.anims[anim.id]);

        if (clear) {
            me.targetArr = null;
        }
    },

    /**
     * @private
     * Collect target attributes for the given Anim object at the given timestamp
     * @param {Ext.fx.Anim} anim The Anim instance
     * @param {Number} elapsedTime Time after the anim's start time
     * @param {Boolean} [useCSS3=false] True if using CSS3-based animation, else false
     * @param {Boolean} [isLastFrame=false] True if this is the last frame of animation to be run,
     * else false
     * @return {Object} The animation target wrapper object containing the passed animation
     * along with the new attributes to set on the target's element in the next animation frame.
     */
    collectTargetData: function(anim, elapsedTime, useCSS3, isLastFrame) {
        var targetId = anim.target.getId(),
            target = this.targetArr[targetId];

        if (!target) {
            // Create a thin wrapper around the target so that we can create a link between the
            // target element and its associated animations. This is important later when applying
            // attributes to the target so that each animation can be independently run with its own
            // duration and stopped at any point without affecting other animations for the same
            // target.
            target = this.targetArr[targetId] = {
                id: targetId,
                el: anim.target,
                anims: {}
            };
        }

        // This is a wrapper for the animation so that we can also save state along with it,
        // including the current elapsed time and lastFrame status. Even though this method only
        // adds a single anim object per call, each target element could have multiple animations
        // associated with it, which is why the anim is added to the target's `anims` hash by id.
        target.anims[anim.id] = {
            id: anim.id,
            anim: anim,
            elapsed: elapsedTime,
            isLastFrame: isLastFrame,
            // This is the object that gets applied to the target element below
            // in applyPendingAttrs():
            attributes: [{
                duration: anim.duration,
                easing: (useCSS3 && anim.reverse) ? anim.easingFn.reverse().toCSS3() : anim.easing,
                // This is where the magic happens. The anim calculates what its new attributes
                // should be based on the current frame and returns those as a hash of values.
                attrs: anim.runAnim(elapsedTime)
            }]
        };

        return target;
    },

    // Duplicating this code for performance reasons. We only want to apply the anims
    // to a single animation because we're hitting the end. It may be out of sequence from
    // the runner timer.
    applyAnimAttrs: function(target, animWrap) {
        var anim = animWrap.anim;

        if (animWrap.attributes && anim.isRunning()) {
            target.el.setAttr(animWrap.attributes, false, animWrap.isLastFrame);

            // If this particular anim is at the last frame end it
            if (animWrap.isLastFrame) {
                anim.lastFrame();
            }
        }
    },

    /**
     * @private
     * Apply all pending attribute changes to their targets
     */
    applyPendingAttrs: function() {
        var targetArr = this.targetArr,
            target, targetId, animWrap, anim, animId;

        // Loop through each target
        for (targetId in targetArr) {
            if (targetArr.hasOwnProperty(targetId)) {
                target = targetArr[targetId];

                // Each target could have multiple associated animations, so iterate those
                for (animId in target.anims) {
                    if (target.anims.hasOwnProperty(animId)) {
                        animWrap = target.anims[animId];
                        anim = animWrap.anim;

                        // If the animation has valid attributes, set them on the target
                        if (animWrap.attributes && anim.isRunning()) {
                            target.el.setAttr(animWrap.attributes, false, animWrap.isLastFrame);

                            // If this particular anim is at the last frame end it
                            if (animWrap.isLastFrame) {
                                anim.lastFrame();
                            }
                        }
                    }
                }
            }
        }
    },

    clear: function() {
        var me = this;

        if (me.taskRunner) {
            me.taskRunner.stopAll(true);
        }

        me.targetArr = {};
        me.items.clear();
        me.targets.clear();
        me.clearFxQueue();
    }
});
