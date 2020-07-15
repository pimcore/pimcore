/**
 * @private
 */
Ext.define('Ext.fx.runner.CssTransition', {
    extend: 'Ext.fx.runner.Css',
    requires: ['Ext.AnimationQueue'],
    alternateClassName: 'Ext.Animator',
    singleton: true,

    listenersAttached: false,

    constructor: function() {
        this.runningAnimationsData = {};
        // if multiple animations run at the same time then we need to queue them
        // to apply them in the same animation frame (see requestAnimationFrame in "run")
        this.transitionQueue = {
            toData: {},
            transitionData: {}
        };

        return this.callParent(arguments);
    },

    attachListeners: function() {
        // NOTE: Ext.getWin() has been used for many years but it doesn't appear to work
        // in the test runner iframe.
        var target = (top === window) ? Ext.getWin() : Ext.getBody();

        this.listenersAttached = true;

        target.on('transitionend', 'onTransitionEnd', this);
    },

    onTransitionEnd: function(e) {
        var target = e.target,
            id = target.id;

        if (id && this.runningAnimationsData.hasOwnProperty(id)) {
            this.refreshRunningAnimationsData(Ext.get(target), [e.browserEvent.propertyName]);
        }
    },

    getElementId: function(element) {
        // usually when the element is destroyed the getId function is nullified
        return element.getId ? element.getId() : element.id;
    },

    onAnimationEnd: function(element, data, animation, isInterrupted, isReplaced) {
        var id = this.getElementId(element),
            runningData = this.runningAnimationsData[id],
            endRules = {},
            endData = {},
            runningNameMap, toPropertyNames, i, ln, name;

        animation.un('stop', 'onAnimationStop', this);

        if (runningData) {
            runningNameMap = runningData.nameMap;
        }

        endRules[id] = endData;

        if (data.onBeforeEnd) {
            data.onBeforeEnd.call(data.scope || this, element, isInterrupted);
        }

        animation.fireEvent('animationbeforeend', animation, element, isInterrupted);
        this.fireEvent('animationbeforeend', this, animation, element, isInterrupted);

        if (isReplaced || (!isInterrupted && !data.preserveEndState)) {
            toPropertyNames = data.toPropertyNames;

            for (i = 0, ln = toPropertyNames.length; i < ln; i++) {
                name = toPropertyNames[i];

                if (runningNameMap && !runningNameMap.hasOwnProperty(name)) {
                    endData[name] = null;
                }
            }
        }

        if (data.after) {
            Ext.merge(endData, data.after);
        }

        this.applyStyles(endRules);

        if (data.onEnd) {
            data.onEnd.call(data.scope || this, element, isInterrupted);
        }

        animation.fireEvent('animationend', animation, element, isInterrupted);
        this.fireEvent('animationend', this, animation, element, isInterrupted);
        Ext.AnimationQueue.stop(Ext.emptyFn, animation);
    },

    onAllAnimationsEnd: function(element) {
        var id = this.getElementId(element),
            transitionQueue = this.transitionQueue,
            endRules = {};

        delete this.runningAnimationsData[id];

        endRules[id] = {
            'transition-property': null,
            'transition-duration': null,
            'transition-timing-function': null,
            'transition-delay': null
        };

        delete transitionQueue.toData[id];
        delete transitionQueue.transitionData[id];

        this.applyStyles(endRules);
        this.fireEvent('animationallend', this, element);
    },

    hasRunningAnimations: function(element) {
        var id = this.getElementId(element),
            runningAnimationsData = this.runningAnimationsData;

        return runningAnimationsData.hasOwnProperty(id) &&
               runningAnimationsData[id].sessions.length > 0;
    },

    refreshRunningAnimationsData: function(element, propertyNames, interrupt, replace) {
        var id = this.getElementId(element),
            runningAnimationsData = this.runningAnimationsData,
            runningData = runningAnimationsData[id],
            hasCompletedSession = false,
            nameMap, nameList, sessions, name, session, map, list,
            i, ln, j, subLn;

        if (!runningData) {
            return;
        }

        nameMap = runningData.nameMap;
        nameList = runningData.nameList;
        sessions = runningData.sessions;

        interrupt = Boolean(interrupt);
        replace = Boolean(replace);

        if (!sessions) {
            return this;
        }

        ln = sessions.length;

        if (ln === 0) {
            return this;
        }

        if (replace) {
            runningData.nameMap = {};
            nameList.length = 0;

            for (i = 0; i < ln; i++) {
                session = sessions[i];
                this.onAnimationEnd(element, session.data, session.animation, interrupt, replace);
            }

            sessions.length = 0;
        }
        else {
            for (i = 0; i < ln; i++) {
                session = sessions[i];
                map = session.map;
                list = session.list;

                for (j = 0, subLn = propertyNames.length; j < subLn; j++) {
                    name = propertyNames[j];

                    if (map[name]) {
                        delete map[name];
                        Ext.Array.remove(list, name);
                        session.length--;

                        if (--nameMap[name] === 0) {
                            delete nameMap[name];
                            Ext.Array.remove(nameList, name);
                        }
                    }
                }

                if (session.length === 0) {
                    sessions.splice(i, 1);
                    i--;
                    ln--;

                    hasCompletedSession = true;
                    this.onAnimationEnd(element, session.data, session.animation, interrupt);
                }
            }
        }

        if (!replace && !interrupt && sessions.length === 0 && hasCompletedSession) {
            this.onAllAnimationsEnd(element);
        }
    },

    getRunningData: function(id) {
        var runningAnimationsData = this.runningAnimationsData;

        if (!runningAnimationsData.hasOwnProperty(id)) {
            runningAnimationsData[id] = {
                nameMap: {},
                nameList: [],
                sessions: []
            };
        }

        return runningAnimationsData[id];
    },

    getTestElement: function() {
        var me = this,
            testElement = me.testElement,
            iframe = me.iframe,
            iframeDocument, iframeStyle;

        if (testElement) {
            // https://sencha.jira.com/browse/EXTJS-21131
            // Forward navigation in Chrome 50 navigates iframes, and orphans
            // the testElement in a detached document. Reconnect it if this has happened.
            if (testElement.ownerDocument.defaultView !== iframe.contentWindow) {
                iframeDocument = iframe.contentDocument;
                iframeDocument.body.appendChild(testElement);

                // eslint-disable-next-line max-len
                me.testElementComputedStyle = iframeDocument.defaultView.getComputedStyle(testElement);
            }
        }
        else {
            iframe = me.iframe = document.createElement('iframe');

            //<debug>
            // Set an attribute that tells the test runner to ignore this node when checking
            // for dom cleanup
            iframe.setAttribute('data-sticky', true);
            //</debug>

            iframe.setAttribute('tabIndex', -1);
            iframeStyle = iframe.style;
            iframeStyle.setProperty('visibility', 'hidden', 'important');
            iframeStyle.setProperty('width', '0px', 'important');
            iframeStyle.setProperty('height', '0px', 'important');
            iframeStyle.setProperty('position', 'absolute', 'important');
            iframeStyle.setProperty('border', '0px', 'important');
            iframeStyle.setProperty('zIndex', '-1000', 'important');

            document.body.appendChild(iframe);
            iframeDocument = iframe.contentDocument;

            iframeDocument.open();
            iframeDocument.writeln('</body>');
            iframeDocument.close();

            me.testElement = testElement = iframeDocument.createElement('div');
            testElement.style.setProperty('position', 'absolute', 'important');
            iframeDocument.body.appendChild(testElement);

            me.testElementComputedStyle = iframeDocument.defaultView.getComputedStyle(testElement);
        }

        return testElement;
    },

    getCssStyleValue: function(name, value) {
        var testElement = this.getTestElement(),
            computedStyle = this.testElementComputedStyle,
            style = testElement.style;

        style.setProperty(name, value);

        if (Ext.browser.is.Firefox) {
            // We force a repaint of the element in Firefox to make sure the computedStyle
            // to be updated
            // eslint-disable-next-line no-unused-expressions
            testElement.offsetHeight;
        }

        value = computedStyle.getPropertyValue(name);
        style.removeProperty(name);

        return value;
    },

    run: function(animations) {
        var me = this,
            ret = [],
            isLengthPropertyMap = me.lengthProperties,
            fromData = {},
            toData = me.transitionQueue.toData,
            data = {},
            transitionData = me.transitionQueue.transitionData,
            element, elementId, from, to, before,
            fromPropertyNames, toPropertyNames,
            doApplyTo, message,
            runningData, elementData,
            i, j, ln, animation, propertiesLength, sessionNameMap,
            computedStyle, formattedName, name, toFormattedValue,
            computedValue, fromFormattedValue, isLengthProperty,
            runningNameMap, runningNameList, runningSessions, runningSession,
            messageTimerFn, onBeforeStart;

        if (!me.listenersAttached) {
            me.attachListeners();
        }

        animations = Ext.Array.from(animations);

        for (i = 0, ln = animations.length; i < ln; i++) {
            animation = animations[i];
            animation = Ext.factory(animation, Ext.fx.Animation);
            ret.push(animation);
            me.activeElement = element = animation.getElement();

            // Empty function to prevent idleTasks from running while we animate.
            Ext.AnimationQueue.start(Ext.emptyFn, animation);

            computedStyle = window.getComputedStyle(element.dom);

            elementId = me.getElementId(element);

            data[elementId] = data = Ext.merge({}, animation.getData());

            onBeforeStart = animation.getOnBeforeStart();

            if (onBeforeStart) {
                onBeforeStart.call(animation.scope || me, element);
            }

            // Allow listeners to mutate animation data
            animation.fireEvent('animationstart', animation, data);
            me.fireEvent('animationstart', me, animation, data);

            before = data.before;
            from = data.from;
            to = data.to;

            data.fromPropertyNames = fromPropertyNames = [];
            data.toPropertyNames = toPropertyNames = [];

            for (name in to) {
                if (to.hasOwnProperty(name)) {
                    to[name] = toFormattedValue = me.formatValue(to[name], name);
                    formattedName = me.formatName(name);
                    isLengthProperty = isLengthPropertyMap.hasOwnProperty(name);

                    if (!isLengthProperty) {
                        toFormattedValue = me.getCssStyleValue(formattedName, toFormattedValue);
                    }

                    if (from.hasOwnProperty(name)) {
                        from[name] = fromFormattedValue = me.formatValue(from[name], name);

                        if (!isLengthProperty) {
                            fromFormattedValue = me.getCssStyleValue(formattedName,
                                                                     fromFormattedValue);
                        }

                        if (toFormattedValue !== fromFormattedValue) {
                            fromPropertyNames.push(formattedName);
                            toPropertyNames.push(formattedName);
                        }
                    }
                    else {
                        computedValue = computedStyle.getPropertyValue(formattedName);

                        if (toFormattedValue !== computedValue) {
                            toPropertyNames.push(formattedName);
                        }
                    }
                }
            }

            propertiesLength = toPropertyNames.length;

            if (propertiesLength === 0) {
                me.onAnimationEnd(element, data, animation);

                continue;
            }

            runningData = me.getRunningData(elementId);
            runningSessions = runningData.sessions;

            if (runningSessions.length > 0) {
                me.refreshRunningAnimationsData(
                    element, Ext.Array.merge(fromPropertyNames, toPropertyNames), true,
                    data.replacePrevious
                );
            }

            runningNameMap = runningData.nameMap;
            runningNameList = runningData.nameList;

            sessionNameMap = {};

            for (j = 0; j < propertiesLength; j++) {
                name = toPropertyNames[j];
                sessionNameMap[name] = true;

                if (!runningNameMap.hasOwnProperty(name)) {
                    runningNameMap[name] = 1;
                    runningNameList.push(name);
                }
                else {
                    runningNameMap[name]++;
                }
            }

            runningSession = {
                element: element,
                map: sessionNameMap,
                list: toPropertyNames.slice(),
                length: propertiesLength,
                data: data,
                animation: animation
            };

            runningSessions.push(runningSession);

            animation.on('stop', 'onAnimationStop', me);

            elementData = Ext.apply({}, before);
            Ext.apply(elementData, from);

            if (runningNameList.length > 0) {
                fromPropertyNames = Ext.Array.difference(runningNameList, fromPropertyNames);
                toPropertyNames = Ext.Array.merge(fromPropertyNames, toPropertyNames);
                elementData['transition-property'] = fromPropertyNames;
            }

            fromData[elementId] = elementData;
            toData[elementId] = Ext.apply({}, to);

            transitionData[elementId] = {
                'transition-property': toPropertyNames,
                'transition-duration': data.duration,
                'transition-timing-function': data.easing,
                'transition-delay': data.delay
            };

            animation.startTime = Date.now();
        }

        me.activeElement = null;

        message = me.$className;

        me.applyStyles(fromData);

        doApplyTo = function(e) {
            if (e.data === message && e.source === window) {
                window.removeEventListener('message', doApplyTo, false);
                me.applyStyles(me.transitionQueue.toData);
            }
        };

        if (!me.messageTimerId) {
            messageTimerFn = function() {
                var messageFollowupFn;

                me.messageTimerId = null;

                if (Ext.isIE) {
                    // https://sencha.jira.com/browse/EXTJS-22362
                    // In some cases IE will fail to animate if the "to" and "transition" styles
                    // are added simultaneously. That is the reason for the multi-delay below.
                    // The first one defines the transition parameters ('transition-property',
                    // 'transition-delay' etc) and the second delay sets the values of the
                    // animating properties, or, the "to" properties. The second delay
                    // is what actually starts the animation.
                    me.applyStyles(me.transitionQueue.transitionData);

                    if (!me.messageFollowupId) {
                        messageFollowupFn = function() {
                            me.messageFollowupId = null;
                            window.addEventListener('message', doApplyTo, false);
                            window.postMessage(message, '*');
                        };

                        //<debug>
                        messageFollowupFn.$skipTimerCheck = true;
                        //</debug>

                        me.messageFollowupId = Ext.raf(messageFollowupFn);
                    }
                }
                else {
                    // In non-IE browsers the above approach can cause a flicker,
                    // so in these browsers we apply all the styles at the same time.
                    Ext.merge(me.transitionQueue.toData, me.transitionQueue.transitionData);
                    window.addEventListener('message', doApplyTo, false);
                    window.postMessage(message, '*');
                }
            };

            //<debug>
            messageTimerFn.$skipTimerCheck = true;
            //</debug>

            me.messageTimerId = Ext.raf(messageTimerFn);
        }

        // TODO: This method needs to attach something to the element it is animating
        // we then need to monitor for destruction of that element
        // and clean up any animations that remain.
        return ret;
    },

    onAnimationStop: function(animation) {
        var me = this,
            runningAnimationsData = me.runningAnimationsData,
            activeAnimations = 0,
            stoppedAnimations = 0,
            id, runningData, sessions, i, ln, session;

        for (id in runningAnimationsData) {
            if (runningAnimationsData.hasOwnProperty(id)) {
                runningData = runningAnimationsData[id];
                sessions = runningData.sessions;
                activeAnimations++;

                for (i = 0, ln = sessions.length; i < ln; i++) {
                    session = sessions[i];

                    if (session.animation === animation) {
                        me.refreshRunningAnimationsData(session.element, session.list.slice(),
                                                        false);

                        if (animation.destroying) {
                            stoppedAnimations++;
                        }
                    }
                }
            }
        }

        if (activeAnimations === stoppedAnimations) {
            if (me.messageFollowupId) {
                Ext.unraf(me.messageFollowupId);
                me.messageFollowupId = null;
            }

            if (me.messageTimerId) {
                Ext.unraf(me.messageTimerId);
                me.messageTimerId = null;
            }

            Ext.apply(me.transitionQueue, {
                toData: {},
                transitionData: {}
            });
        }
    }
});
