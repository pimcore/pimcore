/**
 * @private
 */
Ext.define('Ext.event.publisher.Focus', {
    extend: 'Ext.event.publisher.Dom',
    requires: [
        'Ext.dom.Element',
        'Ext.GlobalEvents'
    ],

    type: 'focus',

    handledEvents: ['focusenter', 'focusleave', 'focusmove'],

    // At this point only Firefox does not support focusin/focusout, see this bug:
    // https://bugzilla.mozilla.org/show_bug.cgi?id=687787
    // TODO: Fix event order: https://github.com/jquery/jquery/issues/3123
    handledDomEvents: ['focusin', 'focusout'],

    publishDelegatedDomEvent: function(e) {
        var me = this,
            relatedTarget = e.relatedTarget;

        //<debug>
        if (me.$suppressEvents) {
            return;
        }
        //</debug>

        if (e.type === 'focusout') {
            // If focus is departing to the document, there will be no forthcoming focusin event
            // to trigger a focusleave, so fire a focusleave now.
            if (relatedTarget == null) {
                me.processFocusIn(e, e.target, document.body);
            }
        }
        else {
            // IE reports relatedTarget as either an inaccessible object which coercively
            // equates to null, or just a blank object in the case of focusing from nowhere.
            if (relatedTarget == null || !relatedTarget.tagName) {
                relatedTarget = document.body;
            }

            me.processFocusIn(e, relatedTarget, e.target);
        }
    },

    processFocusIn: function(e, fromElement, toElement) {
        var me = this,
            focusFly = me.focusFly,
            targets = [],
            commonAncestor, node, backwards, event, focusEnterEvent;

        // If we have suspended focus/blur processing due to framework needing to
        // silently manipulate focus position, then return early.
        if ((fromElement && focusFly.attach(fromElement).isFocusSuspended()) ||
            (toElement && focusFly.attach(toElement).isFocusSuspended())) {
            return;
        }

        if (toElement.compareDocumentPosition) {
            // Flag if the fromElement is DOCUMENT_POSITION_FOLLOWING toElement
            backwards = !!(toElement.compareDocumentPosition(fromElement) & 4);
        }

        // Gather targets for focusleave event from the fromElement to the parentNode
        // (not inclusive)
        // eslint-disable-next-line max-len
        for (node = fromElement, commonAncestor = Ext.dom.Element.getCommonAncestor(toElement, fromElement, true);
            node && node !== commonAncestor; node = node.parentNode) {
            targets.push(node);
        }

        // Publish the focusleave event for the bubble hierarchy
        if (targets.length) {
            event = me.createSyntheticEvent('focusleave', e, fromElement, toElement, fromElement,
                                            toElement, backwards);
            me.publish(event, targets);

            if (event.stopped) {
                return;
            }
        }

        // Gather targets for focusenter event from the focus targetElement to the parentNode
        // (not inclusive)
        targets.length = 0;

        for (node = toElement; node && node !== commonAncestor; node = node.parentNode) {
            targets.push(node);
        }

        // We always need this event; this is what we pass to the global focus event
        focusEnterEvent = me.createSyntheticEvent('focusenter', e, toElement, fromElement,
                                                  fromElement, toElement, backwards);

        // Publish the focusleave event for the bubble hierarchy
        if (targets.length) {
            me.publish(focusEnterEvent, targets);

            if (focusEnterEvent.stopped) {
                return;
            }
        }

        // When focus moves within an element, fire a bubbling focusmove event
        targets = me.getPropagatingTargets(commonAncestor);

        // Publish the focusleave event for the bubble hierarchy
        if (targets.length) {
            event = me.createSyntheticEvent('focusmove', e, toElement, fromElement, fromElement,
                                            toElement, backwards);
            me.publish(event, targets);

            if (event.stopped) {
                return;
            }
        }

        if (Ext.GlobalEvents.hasListeners.focus) {
            Ext.GlobalEvents.fireEvent('focus', {
                event: focusEnterEvent,
                toElement: toElement,
                fromElement: fromElement,
                backwards: backwards
            });
        }
    },

    createSyntheticEvent: function(eventName, browserEvent, target, relatedTarget, fromElement,
        toElement, backwards) {
        var event = new Ext.event.Event(browserEvent);

        event.type = eventName;
        event.relatedTarget = relatedTarget;
        event.target = target;
        event.fromElement = fromElement;
        event.toElement = toElement;
        event.backwards = backwards;

        return event;
    }
}, function(Focus) {
    var focusTimeout;

    Focus.prototype.focusFly = new Ext.dom.Fly();
    Focus.instance = new Focus();

    // At this point only Firefox does not support focusin/focusout, see this bug:
    // https://bugzilla.mozilla.org/show_bug.cgi?id=687787
    if (!Ext.supports.FocusinFocusoutEvents) {
        // When focusin/focusout are not available we capture focus event instead,
        // and fire both focusenter *and* focusleave in the focus handler.
        this.override({
            handledDomEvents: ['focus', 'blur'],

            publishDelegatedDomEvent: function(e) {
                var me = this,
                    targetIsElement;

                me.callSuper([e]);

                // We need to know if event target was an element or (window || document)
                targetIsElement = e.target !== window && e.target !== document;

                // There might be an upcoming focus event, but if none happens
                // within a minimal timeout, then we treat this as a focus of the body
                if (e.type === 'blur') {
                    if (!targetIsElement) {
                        // Apparently when focus goes outside of the document, Firefox
                        // will fire blur on the currently focused element, then on the document,
                        // then on the window. Interestingly enough, both follow-up blur events
                        // will have explicitOriginalTarget pointing at the previously focused
                        // element; when that happens we can be reasonably sure that focus
                        // indeed goes out the window.
                        if (e.explicitOriginalTarget === Focus.previousActiveElement) {
                            // But we want that to fire only once, so process window blur
                            // which happens last.
                            if (e.target === window) {
                                Ext.undefer(focusTimeout);
                                focusTimeout = 0;
                                me.processFocusIn(e, Focus.previousActiveElement, document.body);
                                Focus.previousActiveElement = null;
                            }
                        }
                    }
                    else {
                        // If event target is a valid element, blur could have been caused
                        // by removing previously focused element from the DOM, or some
                        // other happening that doesn't involve <strike>Elvis</strike>focus
                        // completely leaving the building.
                        focusTimeout = Ext.defer(function() {
                            focusTimeout = 0;
                            me.processFocusIn(e, e.target, document.body);
                            Focus.previousActiveElement = null;
                        }, 1);

                        // Store the timer in case the element gets destroyed before
                        // the function above has a chance to fire
                        if (targetIsElement && Ext.cache[e.target.id]) {
                            Ext.cache[e.target.id].focusinTimeout = focusTimeout;
                        }
                    }

                    Focus.previousActiveElement = targetIsElement ? e.target : null;
                }
                else {
                    Ext.undefer(focusTimeout);
                    focusTimeout = 0;

                    me.processFocusIn(
                        e,
                        Focus.previousActiveElement || document.body,
                        targetIsElement ? e.target : document.body
                    );
                }
            }
        });

        Ext.define(null, {
            override: 'Ext.dom.Element',

            destroy: function() {
                if (this.focusinTimeout) {
                    Ext.undefer(this.focusinTimeout);
                    this.focusinTimeout = null;
                }

                this.callParent();
            }
        });
    }
});
