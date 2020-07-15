/**
 * A wrapper class which can be applied to any element. Fires a "click" event while the
 * mouse is pressed. The interval between firings may be specified in the config but
 * defaults to 20 milliseconds.
 *
 * Optionally, a CSS class may be applied to the element during the time it is pressed.
 */
Ext.define('Ext.util.ClickRepeater', {
    alternateClassName: 'Ext.util.TapRepeater',

    mixins: [
        'Ext.mixin.Observable'
    ],

    /**
     * @event mousedown
     * Fires when the mouse button is depressed.
     * @param {Ext.util.ClickRepeater} this
     * @param {Ext.event.Event} e
     */

    /**
     * @event click
     * Fires on a specified interval during the time the element is pressed.
     * @param {Ext.util.ClickRepeater} this
     * @param {Ext.event.Event} e
     */

    /**
     * @event mouseup
     * Fires when the mouse key is released.
     * @param {Ext.util.ClickRepeater} this
     * @param {Ext.event.Event} e
     */

    config: {
        /**
         * @cfg {Ext.dom.Element} [el]
         * The element to listen for clicks/taps on.
         */
        el: null,

        /**
         * @cfg {Ext.Component} [target]
         * The Component who's encapsulating element to listen for clicks/taps on.
         */
        target: null,

        disabled: null
    },

    /**
     * @cfg {String/HTMLElement/Ext.dom.Element} el
     * The element to act as a button.
     */

    /**
     * @cfg {String} pressedCls
     * A CSS class name to be applied to the element while pressed.
     */

    /**
     * @cfg {Boolean} accelerate
     * True if autorepeating should start slowly and accelerate.
     * "interval" and "delay" are ignored.
     */

    /**
     * @cfg {Number} interval
     * The interval between firings of the "click" event (in milliseconds).
     */
    interval: 20,

    /**
     * @cfg {Number} delay
     * The initial delay before the repeating event begins firing.
     * Similar to an autorepeat key delay.
     */
    delay: 250,

    /**
     * @cfg {Boolean} preventDefault
     * True to prevent the default click event
     */
    preventDefault: true,

    /**
     * @cfg {Boolean} stopDefault
     * True to stop the default click event
     */
    stopDefault: false,

    timer: 0,

    /**
    * @cfg {Function/String} handler
    * A function called when the menu item is clicked (can be used instead of {@link #click} event).
    * @cfg {Ext.util.ClickRepeater} handler.clickRepeater This ClickRepeater.
    * @cfg {Ext.event.Event} handler.e The underlying {@link Ext.event.Event}.
    * @controllable
    */
    handler: null,

    /**
     * @cfg {Object} scope
     * The scope (`this` refeence) in which the configured {@link #handler} will be executed,
     * unless the scope is a ViewController method nmame.
     * @accessor
     */
    scope: null,

    /**
     * Creates new ClickRepeater.
     * @param {Object} [config] Config object.
     */
    constructor: function(config) {
        var me = this;

        // Legacy constructor. Element is first parameter
        if (arguments.length === 2) {
            me.setEl(config);
            config = arguments[1];
        }

        me.mixins.observable.constructor.call(this, config);
    },

    destroy: function() {
        this.setEl(null);
        this.callParent();
    },

    privates: {

        fireClick: function(e) {
            var me = this;

            me.fireEvent("click", me, e);
            Ext.callback(me.handler, me.scope, [me, e], 0, me.getTarget());
        },

        updateDisabled: function(disabled) {
            var me = this;

            if (disabled) {
                me.savedEl = me.getEl();
                me.setEl(null);
            }
            else if (me.savedEl) {
                me.setEl(me.savedEl);
            }
        },

        updateTarget: function(target) {
            this.setEl(target.el);
        },

        updateEl: function(newEl, oldEl) {
            var me = this,
                elListeners;

            if (oldEl) {
                oldEl.selectable();
                Ext.undefer(me.timer);

                if (me.pressedCls) {
                    oldEl.removeCls(me.pressedCls);
                }

                Ext.getDoc().un('mouseup', me.handleMouseUp, me);
                me.elListeners = Ext.destroy(me.elListeners);
            }

            if (newEl) {
                newEl.unselectable();
                elListeners = {
                    mousedown: me.handleMouseDown,
                    scope: me,
                    destroyable: true
                };

                if (me.preventDefault || me.stopDefault) {
                    elListeners.click = me.eventOptions;
                }

                me.elListeners = newEl.on(elListeners);
            }
        },

        eventOptions: function(e) {
            if (this.preventDefault) {
                e.preventDefault();
            }

            if (this.stopDefault) {
                e.stopEvent();
            }
        },

        handleMouseDown: function(e) {
            var me = this,
                el = me.getEl();

            Ext.undefer(me.timer);

            if (me.pressedCls) {
                el.addCls(me.pressedCls);
            }

            me.mousedownTime = Ext.now();

            if (e.pointerType === 'mouse') {
                el.on("mouseout", me.handleMouseOut, me);
            }

            Ext.getDoc().on("mouseup", me.handleMouseUp, me);

            me.fireEvent("mousedown", me, e);
            me.fireClick(e);

            // Do not honor delay or interval if acceleration wanted.
            if (me.accelerate) {
                me.delay = 400;
            }

            me.timer = Ext.defer(me.click, me.delay || me.interval, me, [e]);

            if (me.mousedownPreventDefault) {
                e.preventDefault();
            }

            if (me.mousedownStopEvent) {
                e.stopEvent();
            }
        },

        click: function(e) {
            var me = this;

            me.fireClick(e);
            me.timer = Ext.defer(me.click, me.accelerate
                ? me.easeOutExpo(Ext.now() - me.mousedownTime,
                                 400,
                                 -390,
                                 12000)
                : me.interval, me, [e]);
        },

        easeOutExpo: function(t, b, c, d) {
            return (t === d) ? b + c : c * (-Math.pow(2, -10 * t / d) + 1) + b;
        },

        handleMouseOut: function() {
            var me = this,
                el = me.getEl();

            Ext.undefer(me.timer);

            if (me.pressedCls) {
                el.removeCls(me.pressedCls);
            }

            el.on("mouseover", me.handleMouseReturn, me);
        },

        handleMouseReturn: function(e) {
            var me = this,
                el = me.getEl();

            el.un("mouseover", me.handleMouseReturn, me);

            if (me.pressedCls) {
                el.addCls(me.pressedCls);
            }

            me.click(e);
        },

        handleMouseUp: function(e) {
            var me = this,
                el = me.getEl();

            Ext.undefer(me.timer);
            el.un("mouseover", me.handleMouseReturn, me);
            el.un("mouseout", me.handleMouseOut, me);
            Ext.getDoc().un("mouseup", me.handleMouseUp, me);

            if (me.pressedCls) {
                el.removeCls(me.pressedCls);
            }

            me.fireEvent("mouseup", me, e);
        }
    }
});
