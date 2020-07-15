/**
 * An `{@link Ext.mixin.Observable Observable}` through which Ext fires global events.
 * 
 * Ext.on() and Ext.un() are shorthand for {@link #addListener} and {@link #removeListener}
 * on this Observable.  For example, to listen for the idle event:
 * 
 *     Ext.on('idle', function() {
 *         // do something
 *     });
 */
Ext.define('Ext.GlobalEvents', {
    extend: 'Ext.mixin.Observable',
    alternateClassName: 'Ext.globalEvents', // for compat with Ext JS 4.2 and earlier

    requires: [
        'Ext.dom.Element'
    ],

    observableType: 'global',

    singleton: true,

    /**
     * @private
     */
    resizeBuffer: 100,

    /**
     * @event added
     * Fires when a Component is added to a Container.
     * @param {Ext.Component} component
     */

    /**
     * @event beforeresponsiveupdate
     * Fires before {@link Ext.mixin.Responsive} perform any updates in response to
     * dynamic changes. This is prior to refreshing `responsiveFormulas`.
     * @param {Object} context The context object used by `responsiveConfig` expressions.
     * @since 5.0.1
     */

    /**
     * @event beginresponsiveupdate
     * Fires when {@link Ext.mixin.Responsive} is about to perform updates in response to
     * dynamic changes. At this point all `responsiveFormulas` have been refreshed.
     * @param {Object} context The context object used by `responsiveConfig` expressions.
     * @since 5.0.1
     */

    /**
     * @event responsiveupdate
     * Fires after {@link Ext.mixin.Responsive} has performed updates in response to
     * dynamic changes.
     * @param {Object} context The context object used by `responsiveConfig` expressions.
     * @since 5.0.1
     */

    /**
     * @event collapse
     * Fires when a Component is collapsed (e.g., a panel).
     * @param {Ext.Component} component
     */

    /**
     * @event expand
     * Fires when a Component is expanded (e.g., a panel).
     * @param {Ext.Component} component
     */

    /**
     * @event hide
     * Fires when a Component is hidden.
     * @param {Ext.Component} component
     */

    /**
     * @event idle
     * Fires when an event handler finishes its run, just before returning to
     * browser control.
     * 
     * This includes DOM event handlers, Ajax (including JSONP) event handlers,
     * and {@link Ext.util.TaskRunner TaskRunners}
     * 
     * When called at the tail of a DOM event, the event object is passed as the
     * sole parameter.
     * 
     * This can be useful for performing cleanup, or update tasks which need to
     * happen only after all code in an event handler has been run, but which
     * should not be executed in a timer due to the intervening browser
     * reflow/repaint which would take place.
     */

    /**
     * @event onlinechange
     * Fires when the online status of the page changes. See {@link Ext#method-isOnline}
     * @param {Boolean} online `true` if in an online state.
     *
     * @since 6.2.1
     */

    /**
     * @event removed
     * Fires when a Component is removed from a Container.
     * @param {Ext.Component} component
     */

    /**
     * @event resize
     * Fires when the browser window is resized.  To avoid running resize handlers
     * too often resulting in sluggish window resizing, the resize event uses a buffer
     * of 100 milliseconds in the Classic toolkit, and fires on animation frame
     * in the Modern toolkit.
     * @param {Number} width The new width
     * @param {Number} height The new height
     */

    /**
     * @event show
     * Fires when a Component is shown.
     * @param {Ext.Component} component
     */

    /**
     * @event beforebindnotify
     * Fires before a scheduled set of bindings are fired. This allows interested parties
     * to react and do any required work.
     *
     * @private
     * @since 5.1.0
     */

    /**
     * @event mousedown
     * A mousedown listener on the document that is immune to stopPropagation()
     * used in cases where we need to know if a mousedown event occurred on the
     * document regardless of whether some other handler tried to stop it.  An
     * example where this is useful is a menu that needs to be hidden whenever
     * there is a mousedown event on the document.
     * @param {Ext.event.Event} e The event object
     */

    /**
     * @event mouseup
     * A mouseup listener on the document that is immune to stopPropagation()
     * used in cases where we need to know if a mouseup event occurred on the
     * document regardless of whether some other handler tried to stop it.  An
     * example where this is useful is a component which enters a "pressed" state
     * upon mousedown, and needs to exit that state even if the mouse exits
     * before being released.
     * @param {Ext.event.Event} e The event object
     */

    /**
     * @property {Object} idleEventMask
     * This object holds event names for events that should not trigger an `idle` event
     * following their dispatch.
     * @private
     * @since 5.0.0
     */
    idleEventMask: {
        mousemove: 1,
        touchmove: 1,
        pointermove: 1,
        MSPointerMove: 1,
        unload: 1
    },

    // @private
    windowListeners: {
        resize: {
            fn: 'fireResize'
        }
    },

    constructor: function() {
        var me = this;

        me.callParent();

        Ext.onInternalReady(function() {
            // using a closure here instead of attaching the event directly to the
            // attachListeners method gives us a chance to override the method
            me.attachListeners();
        });
    },

    setPressedComponent: function(component, e) {
        var me = this,
            pressedComponent = me.pressedComponent;

        if (pressedComponent && pressedComponent.onRelease) {
            pressedComponent.onRelease(e);
        }

        me.pressedComponent = component;

        if (component) {
            me.pressedScrollStart = Ext.on({
                scrollstart: function() {
                    me.setPressedComponent(null, e);
                },
                destroyable: true
            });
        }
        else {
            me.pressedScrollStart = Ext.destroy(me.pressedScrollStart);
        }
    },

    attachListeners: function() {
        var me = this,
            win = Ext.getWin(),
            winListeners = me.windowListeners;

        me.onlineState = Ext.isOnline();

        // Capture width/height to compare later in fireResize
        me.curHeight = Ext.Element.getViewportHeight();
        me.curWidth = Ext.Element.getViewportWidth();

        win.on({
            scope: me,
            online: 'handleOnlineChange',
            offline: 'handleOnlineChange'
        });

        // This function is not entirely harmless but since it is not directly related
        // to any component, element, or other destructible entity and effectively cannot
        // be cleaned up, we have to pretend it's OK for this timer to go unnoticed.
        //<debug>
        me.fireResize.$skipTimerCheck = true;
        //</debug>

        // IE8 does its own thing
        if (winListeners) {
            winListeners.scope = me;

            // CSS layouts only require buffering to the next animation frame
            if (Ext.isModern) {
                winListeners.resize.onFrame = true;
            }
            else {
                winListeners.resize.buffer = me.resizeBuffer;
            }

            win.on(winListeners);
        }

        Ext.getDoc().on({
            touchstart: 'fireMouseDown',
            mousedown: 'fireMouseDown',
            mouseup: 'fireMouseUp',
            touchend: 'fireMouseUp',
            drop: 'fireMouseUp',
            dragend: 'fireMouseUp',
            scope: me
        });
    },

    fireMouseDown: function(e) {
        this.fireEvent('mousedown', e);

        // Synchronize floated component ordering.
        // Note that this is an ASAP method and will complete asynchronously
        // after this event has finished.
        Ext.ComponentManager.handleDocumentMouseDown(e);
    },

    fireMouseUp: function(e) {
        this.fireEvent('mouseup', e);
        this.setPressedComponent(null, e);
    },

    fireResize: function() {
        var me = this,
            Element = Ext.Element,
            w = Element.getViewportWidth(),
            h = Element.getViewportHeight();

        // In IE the resize event will sometimes fire even though the w/h are the same.
        if (me.curHeight !== h || me.curWidth !== w) {
            me.curHeight = h;
            me.curWidth = w;

            if (me.hasListeners.resize) {
                me.fireEvent('resize', w, h);
            }
        }
    },

    handleOnlineChange: function() {
        var online = Ext.isOnline();

        if (online !== this.onlineState) {
            this.onlineState = online;
            this.fireEvent('onlinechange', online);
        }
    }

}, function(GlobalEvents) {
    Ext.hasListeners = GlobalEvents.hasListeners;

    /**
     * @member Ext
     * @method on
     * Shorthand for {@link Ext.GlobalEvents#addListener}.
     * @inheritdoc Ext.mixin.Observable#addListener
     */
    Ext.on = function() {
        return GlobalEvents.addListener.apply(GlobalEvents, arguments);
    };

    /**
     * @member Ext
     * @method un
     * Shorthand for {@link Ext.GlobalEvents#removeListener}.
     * @inheritdoc Ext.mixin.Observable#removeListener
     */
    Ext.un = function() {
        return GlobalEvents.removeListener.apply(GlobalEvents, arguments);
    };

    /**
     * @member Ext
     * @method fireEvent
     * Shorthand for {@link Ext.GlobalEvents#fireEvent}.
     * @inheritdoc Ext.mixin.Observable#fireEvent
     *
     * @since 6.2.0
     */
    Ext.fireEvent = function() {
        return GlobalEvents.fireEvent.apply(GlobalEvents, arguments);
    };

    /**
     * @member Ext
     * @method fireIdle
     * Fires the global `idle` event if there are any listeners registered.
     *
     * @since 6.5.1
     * @private
     */
    Ext.fireIdle = function() {
        if (GlobalEvents.hasListeners.idle && !Ext._suppressIdle) {
            GlobalEvents.fireEventArgs('idle');
        }

        Ext._suppressIdle = false;
    };

    Ext._suppressIdle = false;
});
