topSuite("Ext.util.KeyNav", 'Ext.dom.Element', function() {
    var el, nav, createNav, fireKey, defaultFn,
        KEYS = Ext.util.KeyNav.keyOptions;

    beforeEach(function() {
        el = Ext.getBody().createChild({
            id: 'test-keyNav-el'
        });

        createNav = function(config) {
            config = Ext.apply({
                target: el
            }, config);

            nav = new Ext.KeyNav(config);
        };

        fireKey = function(key, eventName, options) {
            jasmine.fireKeyEvent(el, nav.getKeyEvent(), key);
        };

        defaultFn = jasmine.createSpy('defaultKeyNavHandler');
    });

    afterEach(function() {
        if (nav) {
            nav.disable();
        }

        el.destroy();
        fireKey = el = nav = createNav = defaultFn = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.KeyNav as the alternate class name", function() {
            expect(Ext.util.KeyNav.prototype.alternateClassName).toEqual("Ext.KeyNav");
        });

        it("should allow the use of Ext.KeyNav", function() {
            expect(Ext.KeyNav).toBeDefined();
        });
    });

    describe("keys", function() {
        describe("key options", function() {
            it("should fire for the left key", function() {
                createNav({
                    left: defaultFn
                });
                fireKey(KEYS.left);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the right key", function() {
                createNav({
                    right: defaultFn
                });
                fireKey(KEYS.right);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the up key", function() {
                createNav({
                    up: defaultFn
                });
                fireKey(KEYS.up);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the down key", function() {
                createNav({
                    down: defaultFn
                });
                fireKey(KEYS.down);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the pageUp key", function() {
                createNav({
                    pageUp: defaultFn
                });
                fireKey(KEYS.pageUp);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the pageDown key", function() {
                createNav({
                    pageDown: defaultFn
                });
                fireKey(KEYS.pageDown);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the del key", function() {
                createNav({
                    del: defaultFn
                });
                fireKey(KEYS.del);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the home key", function() {
                createNav({
                    home: defaultFn
                });
                fireKey(KEYS.home);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the end key", function() {
                createNav({
                    end: defaultFn
                });
                fireKey(KEYS.end);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the enter key", function() {
                createNav({
                    enter: defaultFn
                });
                fireKey(KEYS.enter);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the esc key", function() {
                createNav({
                    esc: defaultFn
                });
                fireKey(KEYS.esc);
                expect(defaultFn).toHaveBeenCalled();
            });

            it("should fire for the tab key", function() {
                createNav({
                    tab: defaultFn
                });
                fireKey(KEYS.tab);
                expect(defaultFn).toHaveBeenCalled();
            });
        });

        it("should bind multiple keys at once", function() {
            createNav({
                left: defaultFn,
                right: defaultFn
            });
            fireKey(KEYS.left);
            fireKey(KEYS.right);
            expect(defaultFn.callCount).toEqual(2);
        });
    });

    describe("scope/params", function() {
        it("should default the scope to the nav", function() {
            var actual;

            createNav({
                left: function() {
                    actual = this;
                }
            });
            fireKey(KEYS.left);
            expect(actual).toBe(nav);
        });

        it("should use the passed scope", function() {
            var scope = {},
                actual;

            createNav({
                scope: scope,
                left: function() {
                    actual = this;
                }
            });
            fireKey(KEYS.left);
            expect(actual).toBe(scope);
        });

        it("should receive an event object as only argument", function() {
            var realEvent;

            createNav({
                enter: function(event) {
                    realEvent = event;
                }
            });
            fireKey(KEYS.enter);
            expect(realEvent.getXY()).toBeTruthy();
            expect(realEvent.type).toBeTruthy();
            expect(realEvent.target).toBeTruthy();
        });
    });

    describe("enable/disable", function() {
        beforeEach(function() {
            createNav({
                esc: defaultFn
            });
        });

        it("should be enabled by default", function() {
            fireKey(KEYS.esc);
            expect(defaultFn).toHaveBeenCalled();
        });

        it("should not fire any events when disabled", function() {
            nav.disable();
            fireKey(KEYS.esc);
            expect(defaultFn).not.toHaveBeenCalled();
        });

        it("should fire events after being disabled then enabled", function() {
            nav.disable();
            fireKey(KEYS.esc);
            expect(defaultFn).not.toHaveBeenCalled();
            nav.enable();
            fireKey(KEYS.esc);
            expect(defaultFn).toHaveBeenCalled();
        });
    });

    describe("defaultEventAction", function() {
        var ev;

        beforeEach(function() {
            createNav({
                tab: function(e) {
                    ev = e;
                }
            });
        });

        it("should not prevent default event action by default", function() {
            fireKey(KEYS.tab);

            // eslint-disable-next-line multiline-ternary
            var prevented = Ext.isIE9m ? ev.browserEvent.returnValue === false
                          :              ev.browserEvent.defaultPrevented
                          ;

            expect(prevented).toBe(false);
        });
    });
});
