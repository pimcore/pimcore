/**
 * A mixin that gives Ext.Component and Ext.Widget the ability to process the "delegate"
 * event option.
 * @private
 */
Ext.define('Ext.mixin.ComponentDelegation', {
    extend: 'Ext.Mixin',
    mixinConfig: {
        id: 'componentDelegation'
    },

    privates: {
        /**
         * @private
         * Adds a listeners with the "delegate" event option.  Users should not invoke this
         * method directly.  Use the "delegate" event option of 
         * {@link Ext.util.Observable#addListener addListener} instead.
         */
        addDelegatedListener: function(eventName, fn, scope, options, order, caller, manager) {
            var me = this,
                delegatedEvents, event, priority;

            eventName = Ext.canonicalEventName(eventName);

            // The following processing of the "order" option is typically done by the
            // doAddListener method of Ext.mixin.Observable, but that method does not
            // get called when adding a delegated listener, so we must do the conversion
            // of order to priority here.
            order = order || options.order;

            if (order) {
                priority = (options && options.priority);

                if (!priority) { // priority option takes precedence over order
                    // do not mutate the user's options
                    options = options ? Ext.Object.chain(options) : {};
                    options.priority = me.$orderToPriority[order];
                }
            }

            //<debug>
            if (options.target) {
                Ext.raise("Cannot add '" + eventName + "' listener to component: '" +
                          me.id +
                          "' - 'delegate' and 'target' event options are incompatible.");
            }
            //</debug>

            // Delegated events are tracked in a map keyed by event name, where the values
            // are instances of Ext.util.Event that track all of the delegate listeners
            // for the given event name.
            delegatedEvents = me.$delegatedEvents || (me.$delegatedEvents = {});
            event = delegatedEvents[eventName] ||
                    (delegatedEvents[eventName] = new Ext.util.Event(me, eventName));

            if (event.addListener(fn, scope, options, caller, manager)) {
                me.$hasDelegatedListeners._incr_(eventName);
            }
        },

        /**
         * @private
         * Clears all listeners that were attached using the "delegate" event option.
         * Users should not invoke this method directly.  It is called automatically as
         * part of normal {@link Ext.util.Observable#clearListeners clearListeners} 
         * processing.
         */
        clearDelegatedListeners: function() {
            var me = this,
                delegatedEvents = me.$delegatedEvents,
                eventName, event, listenerCount;

            if (delegatedEvents) {
                for (eventName in delegatedEvents) {
                    event = delegatedEvents[eventName];
                    listenerCount = event.listeners.length;
                    event.clearListeners();
                    me.$hasDelegatedListeners._decr_(eventName, listenerCount);
                    delete delegatedEvents[eventName];
                }
            }
        },

        /**
         * @private
         * Fires a delegated event.  Users should not invoke this method directly.  It
         * is called automatically by the framework as needed (see the "delegate" event
         * option of {@link Ext.util.Observable#addListener addListener} for more 
         * details.
         */
        doFireDelegatedEvent: function(eventName, args) {
            var me = this,
                ret = true,
                owner, delegatedEvents, event;

            // NOTE: $hasDelegatedListeners exists on the prototype of this mixin
            // which means it is inherited by both Ext.Component and Ext.Widget
            // This means that if any Component in the universe is listening for
            // the given eventName in a delegated manner, we need to traverse up the
            // hierarchy to see if that Component is in fact our ancestor, and if so
            // we need to fire the event on the ancestor.
            if (me.$hasDelegatedListeners[eventName]) {
                owner = me.getRefOwner();

                while (owner) {
                    delegatedEvents = owner.$delegatedEvents;

                    if (delegatedEvents) {
                        event = delegatedEvents[eventName];

                        if (event) {
                            ret = event.fireDelegated(me, args);

                            if (ret === false) {
                                break;
                            }
                        }
                    }

                    owner = owner.getRefOwner();
                }
            }

            return ret;
        },

        /**
         * @private
         * Removes delegated listeners for a given eventName, function, and scope.
         * Users should not invoke this method directly.  It is called automatically by
         * the framework as part of {@link #removeListener} processing.
         */
        removeDelegatedListener: function(eventName, fn, scope) {
            var me = this,
                delegatedEvents = me.$delegatedEvents,
                event;

            if (delegatedEvents) {
                event = delegatedEvents[eventName];

                if (event && event.removeListener(fn, scope)) {
                    me.$hasDelegatedListeners._decr_(eventName);

                    if (event.listeners.length === 0) {
                        delete delegatedEvents[eventName];
                    }
                }
            }
        },

        destroyComponentDelegation: function() {
            if (this.clearPropertiesOnDestroy) {
                this.$delegatedEvents = null;
            }
        }
    },

    onClassMixedIn: function(T) {
        // When a Component listener is attached with the "delegate" option, it means
        // All components anywhere in the hierarchy MUST now fire the event just in case
        // the Component with the delegate listener is an ancestor of the component that
        // fired the event (otherwise the ancestor will not have a chance to intercept
        // and process the event - see doFireDelegatedEvent).  To ensure that this happens
        // we chain the class-level hasListeners object of Ext.Component and Ext.Widget
        // to the single $hasDelegatedListeners object (see class-creation callback
        // of this class for more info)
        function HasListeners() {}

        T.prototype.HasListeners = T.HasListeners = HasListeners;
        HasListeners.prototype = T.hasListeners =
            new Ext.mixin.ComponentDelegation.HasDelegatedListeners();
    }
}, function(ComponentDelegation) {
    // Here We set up a HasListeners instance ($hasDelegatedListeners) that will be incremented
    // and decremented any time a Component or Widget adds or removes a listener using the
    // "delegate" event option.  This HasListeners instance is stored on the prototype
    // of the ComponentDelegation mixin, and therefore will be applied to the prototype
    // of both Ext.Component and Ext.Widget.  This means that Ext.Widget and Ext.Component
    // (intentionally) share the same $hasDelegatedListeners instance.  To understand the
    // reason for this common instance one must first understand how delegated events are
    // fired:
    //
    // When any component or widget fires an event of any kind, it must call doFireDelegatedEvent
    // to process possible delegated listeners.  The implementation of doFireDelegatedEvent
    // traverses up the component hierarchy searching for any ancestors that may be listening
    // for the event in a delegated manner; however, this traversal of the hierarchy can
    // be skipped if there are no known Components with delegated listeners for the given event.
    // The $hasDelegatedListeners instance is used to track whether or not there are any
    // delegated listeners for the given event name for this purpose.  Since Ext.Widgets
    // and Ext.Components can be part of the same hierarchy they must share the same
    // $hasDelegatesListeners instance.
    function HasDelegatedListeners() {}

    ComponentDelegation.HasDelegatedListeners = HasDelegatedListeners;

    HasDelegatedListeners.prototype = ComponentDelegation.prototype.$hasDelegatedListeners =
            new Ext.mixin.Observable.HasListeners();
});
