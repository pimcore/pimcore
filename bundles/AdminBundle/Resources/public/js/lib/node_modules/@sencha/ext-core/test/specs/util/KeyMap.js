/* global expect, spyOn, jasmine, Ext */

topSuite("Ext.util.KeyMap", 'Ext.dom.Element', function() {
    var el, map, createMap, defaultFn, fireKey, origProcessEvent,
        KEYS = {
            A: 65,
            B: 66,
            C: 67,
            X: 88,
            Y: 89,
            Z: 90
        };

    beforeEach(function() {
        el = Ext.getBody().createChild({
            id: 'test-keyMap-el'
        });

        createMap = function(config, eventName) {
            if (Ext.isArray(config)) {
                config = {
                    target: el,
                    binding: config
                };
            }
            else {
                config = Ext.apply({
                    target: el
                }, config);
            }

            if (eventName) {
                config.eventName = eventName;
            }

            map = new Ext.util.KeyMap(config);
        };

        fireKey = function(key, eventName, options) {
            jasmine.fireKeyEvent(el, eventName || 'keydown', key, options || null);
        };

        defaultFn = jasmine.createSpy('defaultKeyNavHandler');
        origProcessEvent = Ext.util.KeyMap.prototype.processEvent;
    });

    afterEach(function() {
        if (map && !map.destroyed) {
            map.disable();
        }

        el.destroy();

        Ext.util.KeyMap.prototype.processEvent = origProcessEvent;
        origProcessEvent = fireKey = defaultFn = map = createMap = el = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.KeyMap as the alternate class name", function() {
            expect(Ext.util.KeyMap.prototype.alternateClassName).toEqual("Ext.KeyMap");
        });

        it("should allow the use of Ext.KeyMap", function() {
            expect(Ext.KeyMap).toBeDefined();
        });
    });

    describe("constructor", function() {
        describe("receiving element", function() {
            it("should take a string id", function() {
                map = new Ext.util.KeyMap({ target: 'test-keyMap-el' });
                map.addBinding({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should take a dom element", function() {
                map = new Ext.util.KeyMap({ target: el });
                map.addBinding({
                    key: KEYS.X,
                    handler: defaultFn
                });
                fireKey(KEYS.X);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should take an Ext.Element", function() {
                map = new Ext.util.KeyMap({ target: Ext.get(el) });
                map.addBinding({
                    key: KEYS.Z,
                    handler: defaultFn
                });
                fireKey(KEYS.Z);
                expect(defaultFn).toHaveBeenCalled();
            });
        });

        it("should pass the config to addBinding", function() {
            createMap({
                key: KEYS.Z,
                handler: defaultFn
            });
            fireKey(KEYS.Z);
            expect(defaultFn).toHaveBeenCalled();
        });

        it("should default the eventName to keydown", function() {
            createMap({
                key: KEYS.C,
                handler: defaultFn
            });
            fireKey(KEYS.C, 'keydown');
            expect(defaultFn).toHaveBeenCalled();
        });

        it("should accept an eventName argument", function() {
            createMap({
                key: KEYS.B,
                handler: defaultFn
            }, 'keyup');
            fireKey(KEYS.B, 'keyup');
            expect(defaultFn).toHaveBeenCalled();
        });
    });

    describe("addBinding", function() {

        describe("single binding", function() {
            it("should listen to a single keycode", function() {
                createMap();
                map.addBinding({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should accept an array of keycodes", function() {
                createMap();
                map.addBinding({
                    key: [KEYS.A, KEYS.Z],
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                fireKey(KEYS.Z);

                expect(defaultFn.callCount).toEqual(2);
            });

            it("should accept a single character as a string", function() {
                createMap();
                map.addBinding({
                    key: 'b',
                    handler: defaultFn
                });
                fireKey(KEYS.B);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should accept multiple characters as a string", function() {
                createMap();
                map.addBinding({
                    key: 'xyz',
                    handler: defaultFn
                });
                fireKey(KEYS.X);
                fireKey(KEYS.Y);
                fireKey(KEYS.Z);

                expect(defaultFn.callCount).toEqual(3);
            });

            it("should accept an array of characters", function() {
                createMap();
                map.addBinding({
                    key: ['c', 'y'],
                    handler: defaultFn
                });
                fireKey(KEYS.C);
                fireKey(KEYS.Y);

                expect(defaultFn.callCount).toEqual(2);
            });
        });

        describe("array binding", function() {
            it("should support an array of mixed bindings", function() {
                createMap();
                map.addBinding([{
                    key: KEYS.A,
                    handler: defaultFn
                }, {
                    key: 'b',
                    handler: defaultFn
                }]);
                fireKey(KEYS.A);
                fireKey(KEYS.B);

                expect(defaultFn.callCount).toEqual(2);
            });

            it("should process all bindings", function() {
                createMap();
                map.addBinding([{
                    key: KEYS.A,
                    handler: defaultFn
                }, {
                    key: KEYS.A,
                    handler: defaultFn
                }]);
                fireKey(KEYS.A);
                expect(defaultFn.callCount).toEqual(2);
            });
        });

        it("should support multiple addBinding calls", function() {
            createMap();
            map.addBinding({
                key: KEYS.A,
                handler: defaultFn
            });
            map.addBinding({
                key: KEYS.B,
                handler: defaultFn
            });
            fireKey(KEYS.A);
            fireKey(KEYS.B);
            expect(defaultFn.callCount).toEqual(2);
        });
    });

    describe("ctrl/alt/shift", function() {

        var createOverride = function(altKey, ctrlKey, shiftKey) {
            Ext.util.KeyMap.prototype.processEvent = function(event) {
                event.altKey = altKey || false;
                event.ctrlKey = ctrlKey || false;
                event.shiftKey = shiftKey || false;

                return event;
            };
        };

        describe("alt", function() {
            it("should fire the event if the alt key is not pressed and the alt option is undefined", function() {
                createOverride();
                createMap({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire the event if the alt key is pressed and the alt option is undefined", function() {
                createOverride(true);
                createMap({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire the event if the alt key is not pressed and the alt option is false", function() {
                createOverride();
                createMap({
                    key: KEYS.B,
                    handler: defaultFn,
                    alt: false
                });
                fireKey(KEYS.B);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should not fire the event if the alt key is pressed and the alt option is true", function() {
                createOverride();
                createMap({
                    key: KEYS.C,
                    handler: defaultFn,
                    alt: true
                });
                fireKey(KEYS.C);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should not fire the event if the alt key is pressed and the alt option is false", function() {
                createOverride(true);
                createMap({
                    key: KEYS.X,
                    handler: defaultFn,
                    alt: false
                });
                fireKey(KEYS.X);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should fire the event if the alt key is pressed and the alt option is true", function() {
                createOverride(true);
                createMap({
                    key: KEYS.X,
                    handler: defaultFn,
                    alt: true
                });
                fireKey(KEYS.X);
                expect(defaultFn).toHaveBeenCalled();
            });
        });

        describe("ctrl", function() {
            it("should fire the event if the ctrl key is not pressed and the ctrl option is undefined", function() {
                createOverride();
                createMap({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire the event if the ctrl key is pressed and the ctrl option is undefined", function() {
                createOverride(false, true);
                createMap({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire the event if the ctrl key is not pressed and the ctrl option is false", function() {
                createOverride();
                createMap({
                    key: KEYS.A,
                    handler: defaultFn,
                    ctrl: false
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should not fire the event if the ctrl key is pressed and the ctrl option is true", function() {
                createOverride();
                createMap({
                    key: KEYS.C,
                    handler: defaultFn,
                    ctrl: true
                });
                fireKey(KEYS.C);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should not fire the event if the ctrl key is pressed and the ctrl option is false", function() {
                createOverride(false, true);
                createMap({
                    key: KEYS.X,
                    handler: defaultFn,
                    ctrl: false
                });
                fireKey(KEYS.X);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should fire the event if the ctrl key is pressed and the ctrl option is true", function() {
                createOverride(false, true);
                createMap({
                    key: KEYS.X,
                    handler: defaultFn,
                    ctrl: true
                });
                fireKey(KEYS.X);
                expect(defaultFn).toHaveBeenCalled();
            });
        });

        describe("shift", function() {
            it("should fire the event if the shift key is not pressed and the shift option is undefined", function() {
                createOverride();
                createMap({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire the event if the shift key is pressed and the shift option is undefined", function() {
                createOverride(false, false, true);
                createMap({
                    key: KEYS.A,
                    handler: defaultFn
                });
                fireKey(KEYS.A);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire the event if the shift key is not pressed and the shift option is false", function() {
                createOverride();
                createMap({
                    key: KEYS.B,
                    handler: defaultFn,
                    shift: false
                });
                fireKey(KEYS.B);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should not fire the event if the shift key is pressed and the shift option is true", function() {
                createOverride();
                createMap({
                    key: KEYS.C,
                    handler: defaultFn,
                    shift: true
                });
                fireKey(KEYS.C);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should not fire the event if the shift key is pressed and the shift option is false", function() {
                createOverride(false, false, true);
                createMap({
                    key: KEYS.X,
                    handler: defaultFn,
                    shift: false
                });
                fireKey(KEYS.X);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should fire the event if the shift key is pressed and the shift option is true", function() {
                createOverride(false, false, true);
                createMap({
                    key: KEYS.X,
                    handler: defaultFn,
                    shift: true
                });
                fireKey(KEYS.X);
                expect(defaultFn).toHaveBeenCalled();
            });
        });

        describe("combinations", function() {
            // these are just some of the combinations, but are sufficient for testing purposes
            it("should not fire the event if alt & ctrl are set to true but only alt is pressed", function() {
                createOverride(true);
                createMap({
                    key: KEYS.Y,
                    handler: defaultFn,
                    alt: true,
                    ctrl: true
                });
                fireKey(KEYS.Y);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should not fire the event if alt, ctrl & shift are set but only shift and ctrl are pressed", function() {
                createOverride(false, true, true);
                createMap({
                    key: KEYS.Y,
                    handler: defaultFn,
                    alt: true,
                    ctrl: true,
                    shift: true
                });
                fireKey(KEYS.Y);
                expect(defaultFn).not.toHaveBeenCalled();
            });

            it("should fire the event if alt & shift are set and alt, ctrl & shift are pressed", function() {
                createOverride(true, true, true);
                createMap({
                    key: KEYS.Z,
                    handler: defaultFn,
                    alt: true,
                    shift: true
                });
                fireKey(KEYS.Z);
                expect(defaultFn).toHaveBeenCalled();
            });
        });
    });

    describe("params/scope", function() {
        describe("scope", function() {
            it("should default the scope to the map", function() {
                var actual;

                createMap({
                    key: KEYS.A,
                    handler: function() {
                        actual = this;
                    }
                });
                fireKey(KEYS.A);
                expect(actual).toEqual(map);
            });

            it("should execute the callback in the passed scope", function() {
                var scope = {},
                    actual;

                createMap({
                    key: KEYS.Y,
                    scope: scope,
                    handler: function() {
                        actual = this;
                    }
                });
                fireKey(KEYS.Y);
                expect(actual).toBe(scope);
            });

            it("should execute each matched binding in the specified scope", function() {
                var scope1 = {},
                    scope2 = {},
                    actual1,
                    actual2;

                createMap([{
                    key: KEYS.B,
                    scope: scope1,
                    handler: function() {
                        actual1 = this;
                    }
                }, {
                    key: KEYS.X,
                    scope: scope2,
                    handler: function() {
                        actual2 = this;
                    }
                }]);

                fireKey(KEYS.B);
                fireKey(KEYS.X);

                expect(actual1).toBe(scope1);
                expect(actual2).toBe(scope2);
            });
        });

        it("should execute the handler with the key and an event", function() {
            var realKey,
                realEvent;

            createMap({
                key: KEYS.Z,
                handler: function(key, event) {
                    realKey = key;
                    realEvent = event;
                }
            });
            fireKey(KEYS.Z);

            expect(realKey).toEqual(KEYS.Z);
            expect(realEvent.getXY()).toBeTruthy();
            expect(realEvent.type).toBeTruthy();
            expect(realEvent.getTarget()).toBeTruthy();
        });
    });

    describe("disable/enabling", function() {
        it("should be enabled by default", function() {
            createMap({
                key: KEYS.B,
                fn: defaultFn
            });
            fireKey(KEYS.B);
            expect(defaultFn).toHaveBeenCalled();
        });

        it("should not fire any events when disabled", function() {
            createMap({
                key: KEYS.C,
                fn: defaultFn
            });
            map.disable();
            fireKey(KEYS.C);
            expect(defaultFn).not.toHaveBeenCalled();
        });

        it("should fire events after being disabled/enabled", function() {
            createMap({
                key: KEYS.Z,
                fn: defaultFn
            });
            map.disable();
            fireKey(KEYS.Z);
            expect(defaultFn).not.toHaveBeenCalled();
            map.enable();
            fireKey(KEYS.Z);
            expect(defaultFn).toHaveBeenCalled();
        });
    });

    describe("event propagation", function() {
        var spy001, spy002;

        beforeEach(function() {
            spy001 = jasmine.createSpy('Agent 001');
            spy002 = jasmine.createSpy('Agent 002');

            createMap([{
                key: [KEYS.A, KEYS.A],
                fn: spy001
            }, {
                key: KEYS.A,
                fn: spy002
            }]);
        });

        describe("stopping", function() {
            beforeEach(function() {
                spy001.andReturn(false);
                spyOn(map, 'processBinding').andCallThrough();

                // Re-bind the handler to allow spying on it
                map.disable();
                spyOn(map, 'handleTargetEvent').andCallThrough();
                map.enable();

                fireKey(KEYS.A);
            });

            it("should not call subsequent handlers in processBinding", function() {
                expect(spy001.callCount).toBe(1);
            });

            it("should not call processBinding more than once", function() {
                expect(map.processBinding.callCount).toBe(1);
            });

            it("should not call subsequent bindings' handlers", function() {
                expect(spy002).not.toHaveBeenCalled();
            });

            it("should return false from the main event handler", function() {
                var result = map.handleTargetEvent.mostRecentCall.result;

                expect(result).toBe(false);
            });
        });
    });

    describe('removing bindings', function() {
        it('should remove a binding whem re,oveBinding is called', function() {
            var bindings = [{
                key: 'A',
                handler: Ext.emptyFn
            }, {
                key: 'A',
                ctrl: true,
                handler: Ext.emptyFn
            }, {
                key: 'A',
                shift: true,
                handler: Ext.emptyFn
            }, {
                key: 'A',
                alt: true,
                handler: Ext.emptyFn
            }];

            createMap(bindings);

            expect(map.bindings.length).toBe(4);
            map.removeBinding(bindings[0]);
            expect(map.bindings.length).toBe(3);

            // Attempt to remove the same handler.
            // It should not match any, and the bindings should not change.
            // There must be an exact match with the key modifiers.
            map.removeBinding(bindings[0]);
            expect(map.bindings.length).toBe(3);

            map.removeBinding(bindings[1]);
            expect(map.bindings.length).toBe(2);
            map.removeBinding(bindings[2]);
            expect(map.bindings.length).toBe(1);
            map.removeBinding(bindings[3]);
            expect(map.bindings.length).toBe(0);
        });
    });

    describe("destroying", function() {
        it("should unbind any events on the element", function() {
            createMap({
                key: KEYS.A
            });
            map.destroy();
            fireKey(KEYS.A);
            expect(defaultFn).not.toHaveBeenCalled();
        });

        /**
         * This test has been commented out because I'm unable to get it to check
         * whether the item has been removed from the DOM. Tried:
         * a) expect(el.parentNode).toBeNull();
         * b) expect(el.parentNode).toBeFalsy();
         * c) expect(jasmine.util.argsToArray(Ext.getBody().dom.childNodes)).not.toContain(el)
         * 
         * None of which work. Odd.
         */
        xit("should remove the element if removeEl is specified", function() {
            createMap({
                key: KEYS.A
            });
            map.destroy(true);
            expect(jasmine.util.argsToArray(Ext.getBody().dom.childNodes)).not.toContain(el);
        });
    });
});
