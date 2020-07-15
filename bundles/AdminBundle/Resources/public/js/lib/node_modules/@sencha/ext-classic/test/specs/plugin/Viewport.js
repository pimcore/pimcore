topSuite("Ext.plugin.Viewport",
    ['Ext.container.Viewport', 'Ext.Panel', 'Ext.app.ViewModel',
     'Ext.app.ViewController'],
function() {
    var c;

    function makeComponent(cfg, ComponentClass) {
        var Cls = ComponentClass || Ext.Component;

        c = new Cls(Ext.apply({
            renderTo: Ext.getBody(),
            plugins: 'viewport'
        }, cfg));
    }

    afterEach(function() {
        c = Ext.destroy(c);
    });

    describe("size model", function() {
        it("should be configured before render", function() {
            var sizeModel;

            makeComponent({
                listeners: {
                    beforerender: function(c) {
                        sizeModel = c.getSizeModel();
                    }
                }
            });
            expect(sizeModel.width.configured).toBe(true);
            expect(sizeModel.height.configured).toBe(true);
        });

        it("should be configured after render", function() {
            makeComponent();
            var sizeModel = c.getSizeModel();

            expect(sizeModel.width.configured).toBe(true);
            expect(sizeModel.height.configured).toBe(true);
        });
    });

    describe("inherited state", function() {
        describe("viewmodel", function() {
            var vm;

            beforeEach(function() {
                vm = new Ext.app.ViewModel({
                    data: {
                        foo: 'bar'
                    }
                });
                makeComponent({
                    viewModel: vm
                });
            });

            afterEach(function() {
                vm = Ext.destroy(vm);
            });

            it("should use the viewmodel on the rootInheritedState", function() {
                expect(Ext.rootInheritedState.viewModel).toBe(vm);
            });

            it("should allow non children of the viewport to inherit the viewmodel", function() {
                var other = new Ext.Component({
                    bind: '{foo}',
                    renderTo: Ext.getBody()
                });

                expect(other.lookupViewModel()).toBe(vm);
                other.destroy();
            });
        });

        describe("session", function() {
            var session;

            beforeEach(function() {
                session = new Ext.data.Session();
                makeComponent({
                    session: session
                });
            });

            afterEach(function() {
                session = Ext.destroy(session);
            });

            it("should use the session on the rootInheritedState", function() {
                expect(Ext.rootInheritedState.session).toBe(session);
            });

            it("should allow non children of the viewport to inherit the session", function() {
                var other = new Ext.Component({
                    renderTo: Ext.getBody()
                });

                expect(other.lookupSession()).toBe(session);
                other.destroy();
            });
        });

        describe("controller", function() {
            var controller;

            beforeEach(function() {
                controller = new Ext.app.ViewController();
                makeComponent({
                    controller: controller
                });
            });

            afterEach(function() {
                controller = null;
            });

            it("should use the controller on the rootInheritedState", function() {
                expect(Ext.rootInheritedState.controller).toBe(controller);
            });

            it("should allow non children of the viewport to inherit the controller", function() {
                var other = new Ext.Component({
                    renderTo: Ext.getBody()
                });

                expect(other.lookupController()).toBe(controller);
                other.destroy();
            });
        });
    });

    describe("destruction", function() {
        describe("inheritedState", function() {
            it("should not pollute the rootInheritedState with a viewmodel", function() {
                var vm = new Ext.app.ViewModel();

                makeComponent({
                    viewModel: vm
                });
                c.destroy();
                expect(Ext.rootInheritedState.viewModel).toBeUndefined();
            });

            it("should not pollute the rootInheritedState with a session", function() {
                var session = new Ext.data.Session();

                makeComponent({
                    session: session
                });
                c.destroy();
                expect(Ext.rootInheritedState.session).toBeUndefined();
                session.destroy();
            });

            it("should not pollute the rootInheritedState with a controller", function() {
                var controller = new Ext.app.ViewController();

                makeComponent({
                    controller: controller
                });
                c.destroy();
                expect(Ext.rootInheritedState.controller).toBeUndefined();
            });
        });

        describe("classes", function() {
            it("should remove the layout target class", function() {
                makeComponent({
                    layout: 'hbox'
                }, Ext.container.Container);
                expect(Ext.getBody()).toHaveCls('x-box-layout-ct');
                c.destroy();
                expect(Ext.getBody()).not.toHaveCls('x-box-layout-ct');
            });
        });
    });

    describe("classes", function() {
        function makeSuite(type) {
            describe("for " + type.$className, function() {
                it("should not get the targetCls warning where the layout has a targetCls", function() {
                    var called = false;

                    spyOn(Ext.log, 'warn').andCallFake(function(s) {
                        called = s.indexOf('targetCls') > -1;
                    });

                    makeComponent({
                        layout: 'hbox'
                    }, type);
                    expect(c.getTargetEl()).toHaveCls('x-box-layout-ct');
                });
            });
        }

        makeSuite(Ext.container.Container);
        makeSuite(Ext.panel.Panel);
    });

    describe("ARIA attributes", function() {
        it("should assign role=application to the document body", function() {
            makeComponent();
            expect(Ext.getBody().dom.getAttribute('role')).toBe('application');
        });
    });

    describe("viewport scroll events", function() {
        function makeSuite(name, cls) {
            describe("auto layout " + name, function() {
                var viewportScrollCount = 0;

                beforeEach(function() {
                    document.documentElement.style.height = '2000px';
                    document.documentElement.style.overflow = 'auto';
                    makeComponent({
                        scrollable: true,
                        items: {
                            xtype: 'component',
                            height: 5000,
                            width: 100
                        }
                    }, cls);
                    c.getScrollable().on({
                        scroll: function() {
                            viewportScrollCount++;
                        }
                    });
                });

                afterEach(function() {
                    document.documentElement.style.height = document.documentElement.style.overflow = '';
                });

                it('should only fire one global scroll event per scroll', function() {
                    c.scrollTo(null, 500);

                    // Read to force synchronous layout
                    // eslint-disable-next-line no-unused-expressions
                    document.body.offsetHeight;

                    // Wait for potentially asynchronous scroll events to fire.
                    waitsFor(function() {
                        return viewportScrollCount === 1;
                    }, "scroll never fired");

                    runs(function() {
                        expect(viewportScrollCount).toBe(1);
                    });
                });
            });
        }

        makeSuite('Container', Ext.container.Container);
        makeSuite('Panel', Ext.panel.Panel);
    });

    describe("global DOM scroll viewport", function() {
        function makeSuite(name, cls) {
            describe("auto layout " + name, function() {
                var viewportScrollCount = 0,
                    incrementFn = function() {
                        viewportScrollCount++;
                    };

                beforeEach(function() {
                    document.documentElement.style.height = '2000px';
                    document.documentElement.style.overflow = 'auto';

                    Ext.on('scroll', incrementFn);

                    makeComponent({
                        scrollable: true,
                        items: {
                            xtype: 'component',
                            height: 5000,
                            width: 100
                        }
                    }, cls);
                });

                afterEach(function() {
                    document.documentElement.style.height = document.documentElement.style.overflow = '';
                    Ext.un('scroll', incrementFn);
                });

                it('should only fire one global scroll event per scroll', function() {
                    c.scrollTo(null, 500);

                    // Wait for potentially asynchronous scroll events to fire.
                    waitsFor(function() {
                        return viewportScrollCount === 1;
                    }, "scroll never fired");

                    runs(function() {
                        expect(viewportScrollCount).toBe(1);
                    });
                });
            });
        }

        // makeSuite('Container', Ext.container.Container);
        makeSuite('Panel', Ext.panel.Panel);
    });
});
