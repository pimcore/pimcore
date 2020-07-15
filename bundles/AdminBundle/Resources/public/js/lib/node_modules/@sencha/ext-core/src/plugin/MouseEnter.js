/**
 * This plugin calls a callback whenever the mouse enters or leaves descendant
 * elements of its host component identified by a {@link Ext.plugin.MouseEnter#delegate delegate}
 * query selector string.
 *
 * This is useful for components which render arbitrary and transient child elements
 * such as DataViews and Charts. It allows notification of mousenter events from 
 * child nodes without having to add  listeners to each child element.
 */
Ext.define('Ext.plugin.MouseEnter', {
    extend: 'Ext.plugin.Abstract',
    alias: 'plugin.mouseenter',

    /**
     * @cfg {Ext.dom.Element/String} [element="el"] The element, or component element reference
     * name to which to add the mouse listener.
     */
    element: 'el',

    /**
     * @cfg {String} delegate A query selector string to identify descendant elements
     * which trigger a call to the handler.
     */

    /**
     * @cfg {String/Function} handler A callback to invoke when a the mouse enters a
     * descendant delegate.
     * @cfg {Ext.event.Event} handler.e The `mouseover` event which triggered the mouse enter.
     * @cfg {HTMLElement} handler.target The delegate element into which the mouse just entered.
     */

    /**
     * @cfg {String/Function} [leaveHandler] A callback to invoke when a the mouse leaves a
     * descendant delegate.
     * @cfg {Ext.event.Event} leaveHandler.e The `mouseover` event which triggered the mouse leave.
     * @cfg {HTMLElement} leaveHandler.target The delegate element which the mouse just left.
     */

    /**
     * @cfg {Object} [scope] The scope (`this` pointer) in which to execute the callback(s).
     */

    /**
     * @cfg {Number} [delay] The time in milliseconds to wait before processing the mouse event.
     * This can prevent unwanted processing when the user swipes the mouse rapidly across
     * the component.
     */

    init: function(component) {
        //<debug>
        if (!this.delegate) {
            Ext.raise('mouseenter plugin must be configured with a delegate selector');
        }

        if (!this.handler) {
            Ext.raise('mouseenter plugin must be configured with handler callback');
        }
        //</debug>

        // eslint-disable-next-line vars-on-top
        var me = this,
            listeners = {
                mouseover: 'onMouseEvent',
                scope: me,
                destroyable: true
            },
            element = me.element;

        // Need the mouseout listener if there's a delay, so that we get an event 
        // in which to cancel the mouseover handling.
        if (me.leaveHandler || me.delay) {
            listeners.mouseout = 'onMouseEvent';
        }

        // Element being a string means a referenced element name in the Component
        if (typeof element === 'string') {
            element = component[me.element];
        }

        // If the component has the element, add the listener.
        // Modern components always will have their elements.
        if (element) {
            me.mouseListener = Ext.get(element).on(listeners);
        }
        // For classic, we have to wait until render.
        // destroyable: true does not work on named element listeners on a component
        // https://sencha.jira.com/browse/EXTJS-22866
        else {
            component.on({
                render: function() {
                    me.mouseListener = component[me.element].on(listeners);
                },
                single: true
            });
        }
    },

    onMouseEvent: function(e) {
        var me = this,
            delegate = e.getTarget(me.delegate);

        // If we have changed delegates, fire (or schedule, if we are delaying) the handler
        if (delegate && delegate !== e.getRelatedTarget(me.delegate)) {
            if (me.delay) {
                Ext.undefer(me.mouseEventTimer);
                me.mouseEventTimer = Ext.defer(me.handleMouseEvent, me.delay, me, [e, delegate]);
            }
            else {
                me.handleMouseEvent(e, delegate);
            }
        }
    },

    handleMouseEvent: function(e, delegate) {
        var me = this;

        if (e.type === 'mouseover') {
            Ext.callback(me.handler, null, [e, delegate], 0, me.cmp, me.scope);
        }
        else if (me.leaveHandler) {
            Ext.callback(me.leaveHandler, null, [e, delegate], 0, me.cmp, me.scope);
        }
    },

    destroy: function() {
        Ext.destroy(this.mouseListener);
        this.callParent();
    }
});
