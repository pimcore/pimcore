topSuite("Ext.Evented", function() {
    function createSuite(mode) {
        function getCls(cfg) {
            var base;

            cfg = cfg || {};

            if (mode === 'extension') {
                base = {
                    extend: "Ext.Evented",
                    eventedConfig: {
                        foo: 'foo'
                    }
                };
            }
            else {
                base = {
                    extend: "Ext.mixin.Observable",
                    config: {
                        foo: {
                            $value: 'foo',
                            evented: true
                        }
                    }
                };
            }

            Ext.merge(cfg, base);

            return Ext.define(null, cfg);
        }

        describe("Evented Config via " + mode, function() {
            describe("Getter/Setter", function() {
                var Cls;

                beforeEach(function() {
                    Cls = getCls();
                });

                it("getter should should return initial value", function() {
                    var cmp = Ext.create(Cls, {});

                    expect(cmp.getFoo()).toBe('foo');
                });

                it("getter should return value set by setter", function() {
                    var cmp = Ext.create(Cls, {});

                    cmp.setFoo('bar');
                    expect(cmp.getFoo()).toBe('bar');
                });
            });

            describe("Config Lifecycle", function() {
                var Cls;

                beforeEach(function() {
                    var cfg = {
                        applyFoo: function(newValue, oldValue) {
                            return newValue;
                        },
                        updateFoo: function(newValue) {

                        }
                    };

                    Cls = getCls(cfg);
                });

                it("applier should be called with proper new and old values", function() {
                    var cmp = Ext.create(Cls, {});

                    spyOn(cmp, 'applyFoo');

                    cmp.setFoo('bar');
                    expect(cmp.applyFoo).toHaveBeenCalledWith('bar', 'foo');
                });

                it("updater should be called with proper new and old values", function() {
                    var cmp = Ext.create(Cls, {});

                    spyOn(cmp, 'updateFoo');

                    cmp.setFoo('bar');
                    expect(cmp.updateFoo).toHaveBeenCalledWith('bar', 'foo');
                });

                it("should run each lifecycle function once when set", function() {
                    var cmp = Ext.create(Cls, {});

                    spyOn(cmp, 'setFoo').andCallThrough();
                    spyOn(cmp, 'applyFoo').andCallThrough();
                    spyOn(cmp, 'updateFoo').andCallThrough();

                    cmp.setFoo('bar');

                    expect(cmp.setFoo.callCount).toEqual(1);
                    expect(cmp.applyFoo.callCount).toEqual(1);
                    expect(cmp.updateFoo.callCount).toEqual(1);
                });
            });

            describe("Evented Events listeners via listener config", function() {
                var Cls;

                beforeEach(function() {
                    var cfg = {
                        listeners: {
                            foochange: 'onFooChange',
                            beforefoochange: 'onBeforeFooChange'
                        },
                        onFooChange: function(cmp, newValue, oldValue) {},
                        onBeforeFooChange: function(cmp, newValue, oldValue) {}
                    };

                    Cls = getCls(cfg);
                });

                it("setter should trigger change listener once with proper args and scope", function() {
                    var cmp = Ext.create(Cls, {});

                    spyOn(cmp, 'onFooChange');

                    cmp.setFoo('bar');

                    expect(cmp.onFooChange.callCount).toEqual(1);
                    expect(cmp.onFooChange.mostRecentCall.object).toBe(cmp);
                    expect(cmp.onFooChange.mostRecentCall.args[0]).toBe(cmp);
                    expect(cmp.onFooChange.mostRecentCall.args[1]).toBe('bar');
                    expect(cmp.onFooChange.mostRecentCall.args[2]).toBe('foo');
                });

                it("setter should trigger beforechange listener once with proper args and scope", function() {
                    var cmp = Ext.create(Cls, {});

                    spyOn(cmp, 'onBeforeFooChange');

                    cmp.setFoo('bar');

                    expect(cmp.onBeforeFooChange.callCount).toEqual(1);
                    expect(cmp.onBeforeFooChange.mostRecentCall.object).toBe(cmp);
                    expect(cmp.onBeforeFooChange.mostRecentCall.args[0]).toBe(cmp);
                    expect(cmp.onBeforeFooChange.mostRecentCall.args[1]).toBe('bar');
                    expect(cmp.onBeforeFooChange.mostRecentCall.args[2]).toBe('foo');
                });

            });

            describe("Evented Event listeners via on with order options", function() {
                it("setter should trigger beforechange, before change, change, after change in proper order", function() {
                    var Cls = getCls({}),
                        cmp = Ext.create(Cls, {}),
                        order = [];

                    cmp.on('beforefoochange', function() {
                        order.push(1);
                    });

                    cmp.on('foochange', function() {
                        order.push(2);
                    }, this, null, 'before');

                    cmp.on('foochange', function() {
                        order.push(3);
                    });

                    cmp.on('foochange', function() {
                        order.push(4);
                    }, this, null, 'after');
                    cmp.setFoo('bar');

                    expect(order).toEqual([1, 2, 3, 4]);
                });
            });

            describe("Evented Event listeners via on, onBefore & onAfter", function() {
                it("setter should trigger beforechange, before change, change, after change in proper order", function() {
                    var Cls = getCls({}),
                        cmp = Ext.create(Cls, {}),
                        order = [];

                    cmp.on('beforefoochange', function() {
                        order.push(1);
                    });

                    cmp.onBefore('foochange', function() {
                        order.push(2);
                    });

                    cmp.on('foochange', function() {
                        order.push(3);
                    });

                    cmp.onAfter('foochange', function() {
                        order.push(4);
                    });

                    cmp.setFoo('bar');

                    expect(order).toEqual([1, 2, 3, 4]);
                });
            });

            describe("Evented config beforeChange Listener", function() {
                it("beforeChange event should be given a controller as the 4th parameter with resume/pause functions", function() {
                    var cfg = {
                            listeners: {
                                beforefoochange: 'onBeforeFooChange'
                            },
                            onBeforeFooChange: function(cmp, newValue, oldValue, controller) {},
                            updateFoo: function(newValue, oldValue) {}
                        },
                        Cls = getCls(cfg),
                        cmp = Ext.create(Cls, {}),
                        controller, args;

                    spyOn(cmp, 'onBeforeFooChange');
                    cmp.setFoo('bar');

                    args = cmp.onBeforeFooChange.mostRecentCall.args;
                    expect(args.length).toBeGreaterThan(3);
                    controller = args[3];
                    expect(controller.pause).toBeDefined();
                    expect(controller.resume).toBeDefined();
                });

                it("controller pause should delay updater until resume is called", function(done) {
                    var cfg = {
                            listeners: {
                                beforefoochange: 'onBeforeFooChange'
                            },
                            onBeforeFooChange: function(cmp, newValue, oldValue, controller) {
                                controller.pause();

                                // Use native setTimeout not the one injected by Jazzman
                                var setTimeout = jasmine._setTimeout;

                                setTimeout(function() {
                                    controller.resume();
                                    expect(cmp.updateFoo.callCount).toBe(1);
                                    done();
                                }, 500);
                            },
                            updateFoo: function(newValue, oldValue) {}
                        },
                        Cls = getCls(cfg),
                        cmp = Ext.create(Cls, {});

                    spyOn(cmp, 'updateFoo');
                    cmp.setFoo('bar');
                    expect(cmp.updateFoo.callCount).toBe(0);
                });
            });
        });
    }

    createSuite('extension');
    createSuite('metadata');
});
