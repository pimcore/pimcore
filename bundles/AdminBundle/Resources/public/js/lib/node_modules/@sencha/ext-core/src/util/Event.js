// @tag core
/**
 * Represents single event type that an Observable object listens to.
 * All actual listeners are tracked inside here.  When the event fires,
 * it calls all the registered listener functions.
 *
 * @private
 */
Ext.define('Ext.util.Event', function() {
    var arraySlice = Array.prototype.slice,
        arrayInsert = Ext.Array.insert,
        toArray = Ext.Array.toArray,
        fireArgs = {};

    return {
        requires: 'Ext.util.DelayedTask',

        /**
         * @property {Boolean} isEvent
         * `true` in this class to identify an object as an instantiated Event, or subclass thereof.
         */
        isEvent: true,

        // Private. Event suspend count
        suspended: 0,

        noOptions: {},

        constructor: function(observable, name) {
            this.name = name;
            this.observable = observable;
            this.listeners = [];
        },

        addListener: function(fn, scope, options, caller, manager) {
            var me = this,
                added = false,
                observable = me.observable,
                eventName = me.name,
                listeners, listener, priority, isNegativePriority, highestNegativePriorityIndex,
                hasNegativePriorityIndex, length, index, i, listenerPriority,
                managedListeners;

            //<debug>
            if (scope && !Ext._namedScopes[scope] && (typeof fn === 'string') &&
                (typeof scope[fn] !== 'function')) {
                Ext.raise("No method named '" + fn + "' found on scope object");
            }
            //</debug>

            if (me.findListener(fn, scope) === -1) {
                listener = me.createListener(fn, scope, options, caller, manager);

                if (me.firing) {
                    // if we are currently firing this event, don't disturb the listener loop
                    me.listeners = me.listeners.slice(0);
                }

                listeners = me.listeners;
                index = length = listeners.length;
                priority = options && options.priority;
                highestNegativePriorityIndex = me._highestNegativePriorityIndex;
                hasNegativePriorityIndex = highestNegativePriorityIndex !== undefined;

                if (priority) {
                    // Find the index at which to insert the listener into the listeners array,
                    // sorted by priority highest to lowest.
                    isNegativePriority = (priority < 0);

                    if (!isNegativePriority || hasNegativePriorityIndex) {
                        // If the priority is a positive number, or if it is a negative number
                        // and there are other existing negative priority listenrs, then we
                        // need to calcuate the listeners priority-order index.
                        // If the priority is a negative number, begin the search for priority
                        // order index at the index of the highest existing negative priority
                        // listener, otherwise begin at 0
                        // eslint-disable-next-line max-len
                        for (i = (isNegativePriority ? highestNegativePriorityIndex : 0); i < length; i++) {
                            // Listeners created without options will have no "o" property
                            listenerPriority = listeners[i].o ? listeners[i].o.priority || 0 : 0;

                            if (listenerPriority < priority) {
                                index = i;
                                break;
                            }
                        }
                    }
                    else {
                        // if the priority is a negative number, and there are no other negative
                        // priority listeners, then no calculation is needed - the negative
                        // priority listener gets appended to the end of the listeners array.
                        me._highestNegativePriorityIndex = index;
                    }
                }
                else if (hasNegativePriorityIndex) {
                    // listeners with a priority of 0 or undefined are appended to the end of
                    // the listeners array unless there are negative priority listeners in the
                    // listeners array, then they are inserted before the highest negative
                    // priority listener.
                    index = highestNegativePriorityIndex;
                }

                if (!isNegativePriority && index <= highestNegativePriorityIndex) {
                    me._highestNegativePriorityIndex ++;
                }

                if (index === length) {
                    listeners[length] = listener;
                }
                else {
                    arrayInsert(listeners, index, [listener]);
                }

                if (observable.isElement) {
                    // It is the role of Ext.util.Event (vs Ext.Element) to handle subscribe/
                    // unsubscribe because it is the lowest level place to intercept the
                    // listener before it is added/removed.  For addListener this could easily
                    // be done in Ext.Element's doAddListener override, but since there are
                    // multiple paths for listener removal (un, clearListeners), it is best
                    // to keep all subscribe/unsubscribe logic here.
                    observable._getPublisher(eventName, options.translate === false).subscribe(
                        observable,
                        eventName,
                        options.delegated !== false,
                        options.capture
                    );
                }

                // If the listener was passed with a manager, add it to the manager's list.
                if (manager) {
                    // if scope is an observable, the listener will be automatically managed
                    // this eliminates the need to call mon() in a majority of cases
                    managedListeners = manager.managedListeners || (manager.managedListeners = []);
                    managedListeners.push({
                        item: me.observable,
                        ename: (options && options.managedName) || me.name,
                        fn: fn,
                        scope: scope,
                        options: options
                    });
                }

                added = true;
            }

            return added;
        },

        createListener: function(fn, scope, o, caller, manager) {
            var me = this,
                namedScopes = Ext._namedScopes,
                namedScope = namedScopes[scope],
                listener = {
                    fn: fn,
                    scope: scope,
                    ev: me,
                    caller: caller,
                    manager: manager,
                    namedScope: namedScope,
                    defaultScope: namedScope ? (scope || me.observable) : undefined,
                    lateBound: typeof fn === 'string'
                },
                handler = fn,
                wrapped = false,
                type;

            if (listener.lateBound && fn[2] === '.') {
                //<debug>
                if (fn.substr(0, 2) !== 'up') {
                    Ext.raise('Invalid listener method: ' + fn);
                }
                //</debug>

                listener.defaultScope = null;
                listener.namedScope = namedScopes[listener.scope = scope = 'up'];
                listener.fn = handler = fn.substr(3);
            }

            // The order is important. The 'single' wrapper must be wrapped by the 'buffer'
            // and 'delayed' wrapper because the event removal that the single listener
            // does destroys the listener's DelayedTask(s)
            if (o) {
                listener.o = o;

                if (o.single) {
                    handler = me.createSingle(handler, listener, o, scope);
                    wrapped = true;
                }

                if (o.target) {
                    handler = me.createTargeted(handler, listener, o, scope, wrapped);
                    wrapped = true;
                }

                if (o.onFrame) {
                    handler = me.createAnimFrame(handler, listener, o, scope, wrapped);
                    wrapped = true;
                }

                if (o.delay) {
                    handler = me.createDelayed(handler, listener, o, scope, wrapped);
                    wrapped = true;
                }

                if (o.buffer) {
                    handler = me.createBuffered(handler, listener, o, scope, wrapped);
                    wrapped = true;
                }

                if (me.observable.isElement) {
                    // If the event type was translated, e.g. mousedown -> touchstart, we need
                    // to save the original type in the listener object so that the Ext.event.Event
                    // object can reflect the correct type at firing time
                    type = o.type;

                    if (type) {
                        listener.type = type;
                    }
                }
            }

            listener.fireFn = handler;
            listener.wrapped = wrapped;

            return listener;
        },

        findListener: function(fn, scope) {
            var listeners = this.listeners,
                i = listeners.length,
                listener;

            while (i--) {
                listener = listeners[i];

                if (listener) {
                    // use ==, not === for scope comparison, so that undefined and null are equal
                    // eslint-disable-next-line eqeqeq
                    if (listener.fn === fn && listener.scope == scope) {
                        return i;
                    }
                }
            }

            return - 1;
        },

        removeListener: function(fn, scope, index) {
            var me = this,
                removed = false,
                observable = me.observable,
                eventName = me.name,
                listener, options, manager, managedListeners, managedListener, i;

            index = index != null ? index : me.findListener(fn, scope);

            if (index !== -1) {
                listener = me.listeners[index];

                if (me.firing) {
                    me.listeners = me.listeners.slice(0);
                }

                // Remove this listener from the listeners array. We can use splice directly here.
                // The IE8 bug which Ext.Array works around only affects *insertion*
                // http://social.msdn.microsoft.com/Forums/en-US/iewebdevelopment/thread/6e946d03-e09f-4b22-a4dd-cd5e276bf05a/
                me.listeners.splice(index, 1);

                // if the listeners array contains negative priority listeners, adjust the
                // internal index if needed.
                if (me._highestNegativePriorityIndex) {
                    if (index < me._highestNegativePriorityIndex) {
                        me._highestNegativePriorityIndex--;
                    }
                    else if (index === me._highestNegativePriorityIndex &&
                             index === me.listeners.length) {
                        delete me._highestNegativePriorityIndex;
                    }
                }

                if (listener) {
                    options = listener.o;

                    // cancel and remove a buffered handler that hasn't fired yet.
                    // When the buffered listener is invoked, it must check whether
                    // it still has a task.
                    if (listener.task) {
                        listener.task.cancel();
                        delete listener.task;
                    }

                    // cancel and remove all delayed handlers that haven't fired yet
                    i = listener.tasks && listener.tasks.length;

                    if (i) {
                        while (i--) {
                            listener.tasks[i].cancel();
                        }

                        delete listener.tasks;
                    }

                    // Cancel the timer that could have been set if the event has already fired
                    listener.fireFn.timerId = Ext.undefer(listener.fireFn.timerId);

                    manager = listener.manager;

                    if (manager) {
                        // If this is a managed listener we need to remove it from the manager's
                        // managedListeners array.  This ensures that if we listen using mon
                        // and then remove without using mun, the managedListeners array is updated
                        // accordingly, for example
                        //
                        //     manager.on(target, 'foo', fn);
                        //
                        //     target.un('foo', fn);
                        managedListeners = manager.managedListeners;

                        if (managedListeners) {
                            for (i = managedListeners.length; i--;) {
                                managedListener = managedListeners[i];

                                if (managedListener.item === me.observable &&
                                    managedListener.ename === eventName &&
                                    managedListener.fn === fn && managedListener.scope === scope) {
                                    managedListeners.splice(i, 1);
                                }
                            }
                        }
                    }

                    if (observable.isElement) {
                        // eslint-disable-next-line max-len
                        observable._getPublisher(eventName, options.translate === false).unsubscribe(
                            observable,
                            eventName,
                            options.delegated !== false,
                            options.capture
                        );
                    }
                }

                removed = true;
            }

            return removed;
        },

        // Iterate to stop any buffered/delayed events
        clearListeners: function() {
            var listeners = this.listeners,
                i = listeners.length,
                listener;

            while (i--) {
                listener = listeners[i];
                this.removeListener(listener.fn, listener.scope);
            }
        },

        suspend: function() {
            ++this.suspended;
        },

        resume: function() {
            if (this.suspended) {
                --this.suspended;
            }
        },

        isSuspended: function() {
            return this.suspended > 0;
        },

        fireDelegated: function(firingObservable, args) {
            this.firingObservable = firingObservable;

            return this.fire.apply(this, args);
        },

        fire: function() {
            var me = this,
                CQ = Ext.ComponentQuery,
                listeners = me.listeners,
                count = listeners.length,
                observable = me.observable,
                isElement = observable.isElement,
                isComponent = observable.isComponent,
                firingObservable = me.firingObservable,
                options, delegate, fireInfo, i, args, listener, len, delegateEl, currentTarget,
                type, chained, firingArgs, e, fireFn, fireScope;

            if (!me.suspended && count > 0) {
                me.firing = true;
                args = arguments.length ? arraySlice.call(arguments, 0) : [];
                len = args.length;

                if (isElement) {
                    e = args[0];
                }

                for (i = 0; i < count; i++) {
                    listener = listeners[i];

                    // Listener may be undefined if one of the previous listeners
                    // destroyed the observable that was listening to these events.
                    // We'd be still in the middle of the loop here, unawares.
                    if (!listener) {
                        continue;
                    }

                    options = listener.o;

                    if (isElement) {
                        if (currentTarget) {
                            // restore the previous currentTarget if we changed it last time
                            // around the loop while processing the delegate option.
                            e.setCurrentTarget(currentTarget);
                        }

                        // For events that have been translated to provide device compatibility,
                        // e.g. mousedown -> touchstart, we want the event object to reflect the
                        // type that was originally listened for, not the type of the actual event
                        // that fired. The listener's "type" property reflects the original type.
                        type = listener.type;

                        if (type) {
                            // chain a new object to the event object before changing the type.
                            // This is more efficient than creating a new event object, and we
                            // don't want to change the type of the original event because it may
                            // be used asynchronously by other handlers
                            // Translated events are not gestures. They must appear to be
                            // atomic events, so that they can be stopped.
                            chained = e;
                            e = args[0] = chained.chain({ type: type, isGesture: false });
                        }

                        // In Ext4 Ext.EventObject was a singleton event object that was reused
                        // as events were fired. Set Ext.EventObject to the last fired event
                        // for compatibility.
                        Ext.EventObject = e;
                    }

                    firingArgs = args;

                    if (options) {
                        delegate = options.delegate;

                        if (delegate) {
                            if (isElement) {
                                // prepending the currentTarget.id to the delegate selector
                                // allows us to match selectors such as "> div"
                                // eslint-disable-next-line max-len
                                delegateEl = e.getTarget(typeof delegate === 'function' ? delegate : '#' + e.currentTarget.id + ' ' + delegate);

                                if (delegateEl) {
                                    args[1] = delegateEl;
                                    // save the current target before changing it to the delegateEl
                                    // so that we can restore it next time around
                                    currentTarget = e.currentTarget;
                                    e.setCurrentTarget(delegateEl);
                                }
                                else {
                                    continue;
                                }
                            }
                            // eslint-disable-next-line max-len
                            else if (isComponent && !CQ.is(firingObservable, delegate, observable)) {
                                continue;
                            }
                        }

                        if (isElement) {
                            if (options.preventDefault) {
                                e.preventDefault();
                            }

                            if (options.stopPropagation) {
                                e.stopPropagation();
                            }

                            if (options.stopEvent) {
                                e.stopEvent();
                            }
                        }

                        args[len] = options;

                        if (options.args) {
                            firingArgs = options.args.concat(args);
                        }
                    }

                    fireInfo = me.getFireInfo(listener);
                    fireFn = fireInfo.fn;
                    fireScope = fireInfo.scope;

                    // We don't want to keep closure and scope on the Event prototype!
                    fireInfo.fn = fireInfo.scope = null;

                    // If the scope is already destroyed, we absolutely cannot deliver events to it.
                    // We also need to clean up the listener to avoid it hanging around forever
                    // like a zombie. Scope can be null/undefined, that's normal.
                    if (fireScope && fireScope.destroyed) {
                        me.removeListener(fireFn, fireScope, i);
                        fireFn = null;

                        //<debug>
                        // Skip warnings for Ext.container.Monitor
                        // It is to be deprecated and removed shortly.
                        if (fireScope.$className !== 'Ext.container.Monitor') {
                            (Ext.raiseOnDestroyed ? Ext.raise : Ext.log.warn)({
                                msg: 'Attempting to fire "' + me.name + '" event on destroyed ' +
                                      (fireScope.$className || 'object') + ' instance with id: ' +
                                      (fireScope.id || 'unknown'),
                                instance: fireScope
                            });
                        }
                        //</debug>
                    }

                    // N.B. This is where actual listener code is called. Step boldly into!
                    if (fireFn && fireFn.apply(fireScope, firingArgs) === false) {
                        Ext.EventObject = null;

                        return (me.firing = false);
                    }

                    // We should remove the last item here to avoid future listeners
                    // in the Array to inherit these options by mistake
                    if (options) {
                        args.length--;
                    }

                    if (chained) {
                        // if we chained the event object for type translation we need to
                        // un-chain it before proceeding to process the next listener, which
                        // may not be a translated event.
                        e = args[0] = chained;
                        chained = null;
                    }

                    // We don't guarantee Ext.EventObject existence outside of the immediate
                    // event propagation scope
                    Ext.EventObject = null;
                }
            }

            me.firing = false;

            return true;
        },

        getFireInfo: function(listener, fromWrapped) {
            var observable = this.observable,
                fireFn = listener.fireFn,
                scope = listener.scope,
                namedScope = listener.namedScope,
                fn, origin;

            // If we are called with a wrapped listener, only attempt to do scope
            // resolution if we are explicitly called by the last wrapped function
            if (!fromWrapped && listener.wrapped) {
                fireArgs.fn = fireFn;

                return fireArgs;
            }

            fn = fromWrapped ? listener.fn : fireFn;

            //<debug>
            var name = fn; // eslint-disable-line vars-on-top
            //</debug>

            if (listener.lateBound) {
                // handler is a function name - need to resolve it to a function reference
                origin = listener.caller || observable;

                if (namedScope && namedScope.isUp) {
                    scope = Ext.lookUpFn(origin, fn);
                }
                else if (!scope || namedScope) {
                    // Only invoke resolveListenerScope if the user did not specify a scope,
                    // or if the user specified a named scope.  Named function handlers that
                    // use an arbitrary object as the scope just skip this part, and just
                    // use the given scope object to resolve the method.
                    scope = origin.resolveListenerScope(listener.defaultScope);
                }

                //<debug>
                if (!scope) {
                    Ext.raise('Unable to dynamically resolve scope for "' + listener.ev.name +
                              '" listener on ' + this.observable.id);
                }

                if (!Ext.isFunction(scope[fn])) {
                    Ext.raise('No method named "' + fn + '" on ' +
                        (scope.$className || 'scope object.'));
                }
                //</debug>

                fn = scope[fn];
            }
            else if (namedScope && namedScope.isController) {
                // If handler is a function reference and scope:'controller' was requested
                // we'll do our best to look up a controller.
                scope = (listener.caller || observable).resolveListenerScope(listener.defaultScope);

                //<debug>
                if (!scope) {
                    Ext.raise('Unable to dynamically resolve scope for "' + listener.ev.name +
                              '" listener on ' + this.observable.id);
                }
                //</debug>
            }
            else if (!scope || namedScope) {
                // If handler is a function reference we use the observable instance as
                // the default scope
                scope = observable;
            }

            // We can only ever be firing one event at a time, so just keep
            // overwriting the object we've got in our closure, otherwise we'll be
            // creating a whole bunch of garbage objects
            fireArgs.fn = fn;
            fireArgs.scope = scope;

            //<debug>
            if (!fn) {
                Ext.raise('Unable to dynamically resolve method "' + name + '" on ' +
                          this.observable.$className);
            }

            //</debug>
            return fireArgs;
        },

        createAnimFrame: function(handler, listener, o, scope, wrapped) {
            var fireInfo;

            if (!wrapped) {
                fireInfo = listener.ev.getFireInfo(listener, true);
                handler = fireInfo.fn;
                scope = fireInfo.scope;

                // We don't want to keep closure and scope references on the Event prototype!
                fireInfo.fn = fireInfo.scope = null;
            }

            return Ext.Function.createAnimationFrame(handler, scope, o.args);
        },

        createTargeted: function(handler, listener, o, scope, wrapped) {
            return function() {
                var fireInfo;

                if (o.target === arguments[0]) {
                    if (!wrapped) {
                        fireInfo = listener.ev.getFireInfo(listener, true);
                        handler = fireInfo.fn;
                        scope = fireInfo.scope;

                        // We don't want to keep closure and scope references
                        // on the Event prototype!
                        fireInfo.fn = fireInfo.scope = null;
                    }

                    return handler.apply(scope, arguments);
                }
            };
        },

        createBuffered: function(handler, listener, o, scope, wrapped) {
            listener.task = new Ext.util.DelayedTask();

            return function() {
                var fireInfo;

                // If the listener is removed during the event call, the listener stays in the
                // list of listeners to be invoked in the fire method, but the task is deleted
                // So if we get here with no task, it's because the listener has been removed.
                if (listener.task) {
                    //<debug>
                    if (Ext._unitTesting) {
                        o.$delayedTask = listener.task;  // for unit test access
                    }
                    //</debug>

                    if (!wrapped) {
                        fireInfo = listener.ev.getFireInfo(listener, true);
                        handler = fireInfo.fn;
                        scope = fireInfo.scope;

                        // We don't want to keep closure and scope references
                        // on the Event prototype!
                        fireInfo.fn = fireInfo.scope = null;
                    }

                    listener.task.delay(o.buffer, handler, scope, toArray(arguments));
                }
            };
        },

        createDelayed: function(handler, listener, o, scope, wrapped) {
            return function() {
                var task = new Ext.util.DelayedTask(),
                    fireInfo;

                if (!wrapped) {
                    fireInfo = listener.ev.getFireInfo(listener, true);
                    handler = fireInfo.fn;
                    scope = fireInfo.scope;

                    // We don't want to keep closure and scope references on the Event prototype!
                    fireInfo.fn = fireInfo.scope = null;
                }

                if (!listener.tasks) {
                    listener.tasks = [];
                }

                listener.tasks.push(task);

                //<debug>
                if (Ext._unitTesting) {
                    o.$delayedTask = task;  // for unit test access
                }
                //</debug>

                task.delay(o.delay || 10, handler, scope, toArray(arguments));
            };
        },

        createSingle: function(handler, listener, o, scope, wrapped) {
            return function() {
                var event = listener.ev,
                    observable = event.observable,
                    fn = listener.fn,
                    fireInfo;

                // If we have an observable, use that to clean up because there
                // can be special cases that need handling. For example element
                // listeners may bind multiple events (mousemove+touchmove) and they
                // need to act in tandem.
                if (observable) {
                    if (!observable.destroyed) {
                        observable.removeListener(event.name, fn, scope);
                    }
                }
                else {
                    event.removeListener(fn, scope);
                }

                if (!wrapped) {
                    fireInfo = event.getFireInfo(listener, true);
                    handler = fireInfo.fn;
                    scope = fireInfo.scope;

                    // We don't want to keep closure and scope references on the Event prototype!
                    fireInfo.fn = fireInfo.scope = null;
                }

                return handler.apply(scope, arguments);
            };
        }
    };
});
