topSuite("Ext.mixin.Keyboard", 'Ext.Component', function() {
    // more specs in Ext.Widget
    var Event = Ext.event.Event,
        c, focusEl;

    function stdComponent(config) {
        return Ext.apply({
            xtype: 'component',
            renderTo: Ext.getBody(),
            width: 100,
            height: 100,
            focusable: true,
            tabIndex: 0,
            getFocusEl: function() {
                return this.el;
            },
            onKeyDefault: Ext.emptyFn
        }, config);
    }

    function makeComponent(config) {
        var cmpCfg = stdComponent(config);

        c = new Ext.Component(cmpCfg);

        return c;
    }

    afterEach(function() {
        if (c) {
            c.destroy();
        }

        c = null;
    });

    describe("config", function() {
        describe("extending", function() {
            it("should combine keyMap cached config", function() {
                var ParentClass = Ext.define(null, {
                    extend: 'Ext.Component',
                    keyMap: {
                        ENTER: 'onKeyEnter'
                    },
                    onKeyEnter: Ext.emptyFn
                });

                var ChildClass = Ext.define(null, {
                    extend: ParentClass,
                    keyMap: {
                        ESC: 'onEsc'
                    }
                });

                var ChildClass2 = Ext.define(null, {
                    extend: ParentClass,
                    keyMap: {
                        HOME: 'onHome'
                    }
                });

                c = new ChildClass();
                var km = c.getKeyMap();

                // cached on prototype
                expect(c.hasOwnProperty('keyMap')).toBe(false);
                expect(ChildClass.prototype.hasOwnProperty('keyMap')).toBe(true);

                expect(Ext.Array.sort(Ext.Object.getKeys(km))).toEqual(['ENTER', 'ESC']);
                expect(km.ENTER[0].handler).toBe('onKeyEnter');
                expect(km.ESC[0].handler).toBe('onEsc');

                expect(c._keyMapListenCount).toBe(0);
                c.render(Ext.getBody());
                expect(c._keyMapListenCount).toBe(1);

                c.destroy();

                c = new ChildClass();
                var km2 = c.getKeyMap();

                expect(c.hasOwnProperty('keyMap')).toBe(false); // still on prototype
                expect(km === km2).toBe(true);

                c.destroy();

                c = new ChildClass({
                    keyMap: {
                        ESC: {
                            handler: 'onEscape',
                            scope: 'this'
                        }
                    }
                });

                var km3 = c.getKeyMap();

                expect(c.hasOwnProperty('keyMap')).toBe(true); // has its own now
                expect(km !== km3).toBe(true);

                var km3keys = Ext.Object.getKeys(km3);

                Ext.Array.remove(km3keys, '$owner');
                Ext.Array.sort(km3keys);
                expect(km3keys).toEqual(['ENTER', 'ESC']);

                c.destroy();

                c = new ChildClass2();
                var km4 = c.getKeyMap();

                expect(Ext.Array.sort(Ext.Object.getKeys(km4))).toEqual(['ENTER', 'HOME']);
                expect(c.hasOwnProperty('keyMap')).toBe(false); // on prototype
                expect(ChildClass2.prototype.hasOwnProperty('keyMap')).toBe(true);

                // the tests in Ext.Widget for keyMap are more thorough on
                // instance configs to manage keyMap and ensuring things
                // don't get shared into the prototype keyMap... it actually
                // calls the handlers :)
            });

            it("should allow nulling keyMap config", function() {
                var ParentClass = Ext.define(null, {
                    extend: 'Ext.Component',
                    keyMap: {
                        ENTER: 'onKeyEnter'
                    },
                    onKeyEnter: Ext.emptyFn
                });

                var ChildClass = Ext.define(null, {
                    extend: ParentClass,
                    keyMap: null
                });

                c = new ChildClass();

                expect(c.getKeyMap() == null).toBe(true); // null or undefined

                c.destroy();

                c = new ParentClass();
                var km = c.getKeyMap();

                expect(km.ENTER[0].handler).toBe('onKeyEnter');
                expect(Ext.Object.getKeys(km)).toEqual(['ENTER']);
                expect(c.hasOwnProperty('keyMap')).toBe(false);
                expect(ParentClass.prototype.hasOwnProperty('keyMap')).toBe(true);
                expect(ParentClass.prototype.keyMap).toBe(km);

                c.setKeyMap(null);

                expect(c.getKeyMap()).toBe(null);
                expect(c.hasOwnProperty('keyMap')).toBe(true);
                expect(ParentClass.prototype.hasOwnProperty('keyMap')).toBe(true);
                expect(ParentClass.prototype.keyMap).toBe(km);
            });

            it("should null the keyMap if no keys", function() {
                var ParentClass = Ext.define(null, {
                    extend: 'Ext.Component',
                    keyMap: {
                        ENTER: null
                    },
                    onKeyEnter: Ext.emptyFn
                });

                var ChildClass = Ext.define(null, {
                    extend: ParentClass,
                    keyMap: {}
                });

                c = new ChildClass();

                expect(c.getKeyMap()).toBe(null);

                c.destroy();

                c = new ParentClass();
                var km = c.getKeyMap();

                expect(km).toBe(null);
                expect(c.hasOwnProperty('keyMap')).toBe(false);
                expect(ParentClass.prototype.hasOwnProperty('keyMap')).toBe(true);
                expect(ParentClass.prototype.keyMap).toBe(km);
            });
        });

        describe("handling", function() {
            beforeEach(function() {
                makeComponent();
            });

            it("should accept binding as function", function() {
                spyOn(Ext.log, 'warn');

                c.setKeyMap({ UP: Ext.emptyFn });

                expect(Ext.log.warn).not.toHaveBeenCalled();

                var handlers = c.getKeyMap();

                expect(handlers.UP[0].handler).toBe(Ext.emptyFn);
            });

            it("should accept binding as fn name", function() {
                c.setKeyMap({ DOWN: 'onKeyDefault' });

                var handlers = c.getKeyMap();

                expect(handlers.DOWN[0].handler).toBe('onKeyDefault');
            });

            it("should accept binding as fn name with a _ in the key name", function() {
                c.setKeyMap({ PAGE_UP: 'onKeyPageUp' });

                var handlers = c.getKeyMap();

                expect(handlers.PAGE_UP[0].handler).toBe('onKeyPageUp');
            });

            it('should accept single characters for keys', function() {
                c.setKeyMap({
                    '+': 'onPlus'
                });

                var cc = '+'.charCodeAt(0),
                    entry = c.findKeyMapEntries(new Ext.event.Event({
                        type: 'keypress',
                        charCode: cc
                    }))[0];

                expect(entry.charCode).toBe(cc);
                expect(entry.handler).toBe('onPlus');
            });

            it('should accept modifier and single characters', function() {
                c.setKeyMap({
                    'Ctrl-+': 'onCtrlPlus'
                });

                var cc = '+'.charCodeAt(0),
                    entry = c.findKeyMapEntries(new Ext.event.Event({
                        type: 'keypress',
                        charCode: cc,
                        ctrlKey: true
                    }))[0];

                expect(entry.charCode).toBe(cc);
                expect(entry.handler).toBe('onCtrlPlus');
            });

            it('should accept #num for charCode', function() {
                c.setKeyMap({
                    '#65': 'onKey65'
                });

                var entry = c.findKeyMapEntries(new Ext.event.Event({
                        type: 'keypress',
                        charCode: 65
                    }))[0];

                expect(entry.charCode).toBe(65);
                expect(entry.keyCode).toBe(undefined);
                expect(entry.handler).toBe('onKey65');
            });

            it('should accept number as key for keyCode', function() {
                c.setKeyMap({
                    65: 'onKey65'
                });

                var entry = c.findKeyMapEntries(new Ext.event.Event({
                        type: 'keydown',
                        keyCode: 65
                    }))[0];

                expect(entry.charCode).toBe(undefined);
                expect(entry.keyCode).toBe(65);
                expect(entry.handler).toBe('onKey65');
            });

            it('should accept modifier and charCode', function() {
                c.setKeyMap({
                    'Ctrl+#65': 'onCtrlKey65'
                });

                var entry = c.findKeyMapEntries(new Ext.event.Event({
                        type: 'keypress',
                        charCode: 65,
                        ctrlKey: true
                    }))[0];

                expect(entry.charCode).toBe(65);
                expect(entry.keyCode).toBe(undefined);
                expect(entry.handler).toBe('onCtrlKey65');
                expect(entry.ctrlKey).toBe(true);
            });

            it('should accept modifier and keyCode', function() {
                c.setKeyMap({
                    'Alt+Meta+65': 'onAltMetaKey65'
                });

                var entry = c.findKeyMapEntries(new Ext.event.Event({
                    type: 'keydown',
                    keyCode: 65,
                    altKey: true,
                    metaKey: true
                }))[0];

                expect(entry.charCode).toBe(undefined);
                expect(entry.keyCode).toBe(65);
                expect(entry.handler).toBe('onAltMetaKey65');
                expect(entry.altKey).toBe(true);
                expect(entry.metaKey).toBe(true);
            });

            it("should throw on unknown keycode", function() {
                var err = 'Invalid keyMap key specification "FOO"';

                expect(function() {
                    c.setKeyMap({ FOO: 'onKeyFoo' });
                }).toThrow(err);
            });

            it("should throw an error on undefined binding", function() {
                expect(function() {
                    c.setKeyMap({ UP: undefined });
                }).toThrow();
            });
        });
    });

    describe("keydown listener", function() {
        describe("w/o config", function() {
            beforeEach(function() {
                makeComponent();

                focusEl = c.getFocusEl();
            });

            it("should not attach listener initially", function() {
                expect(focusEl.hasListener('keydown')).toBe(false);
            });

            it("should attach listener on config update", function() {
                c.setKeyMap({ HOME: 'onKeyDefault' });

                expect(focusEl.hasListener('keydown')).toBe(true);
            });
        });

        describe("with config", function() {
            beforeEach(function() {
                makeComponent({
                    keyMap: {
                        LEFT: 'onKeyDefault'
                    },
                    keyMapTarget: 'focusEl'
                });

                focusEl = c.getFocusEl();
            });

            it("should attach listener after render", function() {
                expect(focusEl.hasListener('keydown')).toBe(true);
            });

            it("should not attach listener more than once", function() {
                c.setKeyMap({ RIGHT: 'onKeyDefault' });

                expect(focusEl.hasListeners.keydown).toBe(1);
            });
        });
    });

    describe("handlers", function() {
        var leftSpy, rightSpy;

        beforeEach(function() {
            leftSpy = jasmine.createSpy('left');
            rightSpy = jasmine.createSpy('right');

            makeComponent({
                keyMap: {
                    LEFT: 'onKeyLeft',
                    RIGHT: 'onKeyRight'
                },

                onKeyLeft: leftSpy,
                onKeyRight: rightSpy,

                renderTo: null
            });

            c.render(Ext.getBody());
        });

        afterEach(function() {
            leftSpy = rightSpy = null;
        });

        describe("resolving", function() {
            it("should resolve handler name to function", function() {

                jasmine.fireKeyEvent(c.el, 'keydown', Ext.event.Event.LEFT);
                expect(leftSpy.callCount).toBe(1);
                expect(rightSpy.callCount).toBe(0);

                jasmine.fireKeyEvent(c.el, 'keydown', Ext.event.Event.RIGHT);
                expect(leftSpy.callCount).toBe(1);
                expect(rightSpy.callCount).toBe(1);
            });
        });

        describe("invoking", function() {
            describe("matching a handler", function() {
                it("should invoke the handler", function() {
                    pressKey(c, 'left');

                    runs(function() {
                        expect(leftSpy).toHaveBeenCalled();
                    });
                });

                it("should pass the key event", function() {
                    focusAndWait(c);

                    runs(function() {
                        jasmine.fireKeyEvent(c.getFocusEl(), 'keydown', Event.RIGHT);
                    });

                    waitAWhile();

                    runs(function() {
                        var args = rightSpy.mostRecentCall.args,
                            ev = args[0];

                        expect(ev.getKey()).toBe(Event.RIGHT);
                    });
                });
            });

            xdescribe("enabled keyMap", function() {
                beforeEach(function() {
                    // c.getKeyMap().disabled = true;
                });

                it("should not invoke the handler", function() {
                    pressKey(c, 'left');

                    waitForSpy(leftSpy);
                });
            });

            describe("not matching a handler", function() {
                it("should not throw", function() {
                    focusAndWait(c);

                    runs(function() {
                        expect(function() {
                            jasmine.fireKeyEvent(c.getFocusEl(), 'keydown', Event.UP);
                        }).not.toThrow();
                    });
                });
            });
        });
    });
});
