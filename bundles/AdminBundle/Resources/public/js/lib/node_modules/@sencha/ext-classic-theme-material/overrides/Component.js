Ext.define('Ext.theme.material.Component', {
    override: 'Ext.Component',

    config: {
        /**
         * @cfg {Boolean/Object/String} ripple
         * Set to truthy, Color or Object value for the ripple.
         * @cfg {String} ripple.color The background color of the ripple.
         * @cfg {Array} ripple.position Position for the ripple to start at [x,y].
         * Determines if a Ripple effect should happen whenever this element is pressed.
         *
         * For example:
         *      {
         *          ripple: true
         *      }
         *
         * Or:
         *
         *      {
         *          ripple: {
         *              color: 'red'
         *          }
         *      }
         *
         * For complex components, individual elements can suppress ripples by adding the
         * `x-no-ripple` class to disable rippling for a tree of elements.
         *
         * @since 7.0.0
         */
        ripple: null,
        labelAlign: 'top'
    },

    initComponent: function() {
        var me = this;

        me.callParent();

        if (me.ripple) {
            me.on('afterrender', function() {
                me.updateRipple(me.getRipple());
            }, me);
        }
    },

    updateRipple: function(ripple) {
        var me = this,
            el = me.el;

        if (Ext.isIE9m) {
            Ext.log({ level: 'warn' }, 'Ripple effect is not supported in IE9 and below!');

            return;
        }

        if (el) {
            el.un('touchstart', 'onRippleStart', me);
            el.un('touchend', 'onRippleStart', me);

            el.destroyAllRipples();

            el.on(ripple.release ? 'touchend' : 'touchstart', 'onRippleStart', me);
        }
    },

    shouldRipple: function(e) {
        var me = this,
            disabled = me.getDisabled && me.getDisabled(),
            el = me.el,
            ripple = !disabled && me.getRipple(),
            target;

        if (ripple && e) {
            target = e.getTarget(me.noRippleSelector);

            if (target) {
                if ((el.dom === target) || el.contains(target)) {
                    ripple = null;
                }
            }
        }

        return ripple;
    },

    onRippleStart: function(e) {
        var me = this,
            ripple = this.shouldRipple(e);

        if (e.button === 0 && ripple) {
            me.el.ripple(e, ripple);
        }
    },

    privates: {
        noRippleSelector: '.' + Ext.baseCSSPrefix + 'no-ripple',
        /**
         * Queue a function to run when the component is visible & painted. If those conditions
         * are met, the function will execute  immediately, otherwise it will wait until it is
         * visible and painted.
         *
         * @param {String} fn The function to execute on this component.
         * @param {Object[]} [args] The arguments to pass.
         * @return {Boolean} `true` if the function was executed immediately.
         *
         * @private
         */
        whenVisible: function(fn, args) {
            var me = this,
                listener, pending, visible;

            args = args || Ext.emptyArray;

            listener = me.visibleListener;
            pending = me.pendingVisible;
            visible = me.isVisible(true);

            if (!visible && !listener) {
                me.visibleListener = Ext.on({
                    scope: me,
                    show: 'handleGlobalShow',
                    destroyable: true
                });
            }

            if (visible) {
                // Due to animations, it's possible that we may get called
                // and the show event hasn't fired. If that is the case
                // then just run now

                if (pending) {
                    pending[fn] = args;
                    me.runWhenVisible();
                }
                else {
                    me[fn].apply(me, args);
                }
            }
            else {
                if (!pending) {
                    me.pendingVisible = pending = {};
                }

                pending[fn] = args;
            }

            return visible;
        },

        clearWhenVisible: function(fn) {
            var me = this,
                pending = me.pendingVisible;

            if (pending) {
                delete pending[fn];

                if (Ext.Object.isEmpty(pending)) {
                    me.pendingVisible = null;

                    me.visibleListener = Ext.destroy(me.visibleListener);
                }
            }
        },

        runWhenVisible: function() {
            var me = this,
                pending = me.pendingVisible,
                key;

            me.pendingVisible = null;
            me.visibleListener = Ext.destroy(me.visibleListener);

            for (key in pending) {
                me[key].apply(me, pending[key]);
            }
        },

        handleGlobalShow: function(c) {
            var me = this;

            if (me.isVisible(true) && (c === me || me.isDescendantOf(c))) {
                me.runWhenVisible();
            }
        }
    }
}, function() {
    Ext.namespace('Ext.theme.is').Material = true;
    Ext.theme.name = 'Material';
});
