//<feature legacyBrowser>
Ext.define('Ext.overrides.event.publisher.Dom', {
    override: 'Ext.event.publisher.Dom'

}, function(DomPublisher) {
    var focusEvents = {
        focus: true,
        focusin: true,
        focusout: true,
        blur: true
    };

    if (Ext.isIE10m) {
        DomPublisher.override({
            isEventBlocked: function(e) {
                if (!focusEvents[e.type]) {
                    return this.callParent([e]);
                }

                // eslint-disable-next-line vars-on-top
                var body = document.body,
                    ev = e.browserEvent,
                    el = Ext.synchronouslyFocusing;

                /* eslint-disable max-len, brace-style */
                // This horrid hack is necessary to work around the issue with input elements
                // in IE10m that can fail to focus under certain conditions. See comment in
                // Ext.dom.Element override.
                if (el &&
                    ((ev.type === 'focusout' && (ev.srcElement === el || ev.srcElement === window) && ev.toElement === body) ||
                     (ev.type === 'focusin' && (ev.srcElement === body || ev.srcElement === window) && ev.fromElement === el &&
                      ev.toElement === null)))
                {
                    return true;
                }
                /* eslint-enable max-len, brace-style */

                return false;
            }
        });
    }

    if (Ext.isIE9m) {
        // eslint-disable-next-line vars-on-top
        var docElement = document.documentElement,
            docBody = document.body,
            prototype = DomPublisher.prototype,
            onDirectEvent, onDirectCaptureEvent; // eslint-disable-line no-unused-vars

        prototype.target = document;
        prototype.directBoundListeners = {};

        // This method gets bound to the element scope in addDirectListener so that
        // the currentTarget can be captured using "this".
        onDirectEvent = function(e, publisher, capture) {
            e.target = e.srcElement || window;
            e.currentTarget = this;

            if (capture) {
                // Although directly attached capture listeners are not supported in IE9m
                // we still need to call the handler so at least the event fires.
                publisher.onDirectCaptureEvent(e);
            }
            else {
                publisher.onDirectEvent(e);
            }
        };

        onDirectCaptureEvent = function(e, publisher) {
            e.target = e.srcElement || window;
            e.currentTarget = this; // this, not DomPublisher
            publisher.onDirectCaptureEvent(e);
        };

        DomPublisher.override({
            addDelegatedListener: function(eventName) {
                this.delegatedListeners[eventName] = 1;
                // Use attachEvent for IE9 and below.  Even though IE9 strict supports
                // addEventListener, it has issues with using synthetic events.
                this.target.attachEvent('on' + eventName, this.onDelegatedEvent);
            },

            removeDelegatedListener: function(eventName) {
                delete this.delegatedListeners[eventName];
                this.target.detachEvent('on' + eventName, this.onDelegatedEvent);
            },

            addDirectListener: function(eventName, element, capture) {
                var me = this,
                    dom = element.dom,
                    // binding the listener to the element allows us to capture the
                    // "currentTarget" (see onDirectEvent)
                    boundFn = Ext.Function.bind(onDirectEvent, dom, [me, capture], true),
                    directBoundListeners = me.directBoundListeners,
                    handlers = directBoundListeners[eventName] ||
                               (directBoundListeners[eventName] = {});

                handlers[dom.id] = boundFn;

                // may be called with an SVG element here, which
                // does not have the attachEvent method on IE 9 strict
                if (dom.attachEvent) {
                    dom.attachEvent('on' + eventName, boundFn);
                }
                else {
                    me.callParent([eventName, element, capture]);
                }
            },

            removeDirectListener: function(eventName, element, capture) {
                var dom = element.dom;

                if (dom.detachEvent) {
                    dom.detachEvent(
                        'on' + eventName,
                        this.directBoundListeners[eventName][dom.id]
                    );
                }
                else {
                    this.callParent([eventName, element, capture]);
                }
            },

            doDelegatedEvent: function(e) {
                e.target = e.srcElement || window;

                if (e.type === 'focusin') {
                    // IE8 sometimes happen to focus <html> element instead of the body
                    // eslint-disable-next-line max-len
                    e.relatedTarget = e.fromElement === docBody || e.fromElement === docElement ? null : e.fromElement;
                }
                else if (e.type === 'focusout') {
                    // eslint-disable-next-line max-len
                    e.relatedTarget = e.toElement === docBody || e.toElement === docElement ? null : e.toElement;
                }

                return this.callParent([e]);
            }
        });

        // can't capture any events without addEventListener.  Have to have direct
        // listeners for every event that does not bubble.
        Ext.apply(prototype.directEvents, prototype.captureEvents);

        // These do not bubble in IE9m so have to attach direct listeners as well.
        Ext.apply(prototype.directEvents, {
            change: 1,
            input: 1,
            paste: 1
        });

        prototype.captureEvents = {};
    }
});
//</feature>
