topSuite("Ext.Component",
    ['Ext.Container', 'Ext.layout.container.*', 'Ext.Panel', 'Ext.form.FieldSet', 'Ext.form.field.*',
     'Ext.data.Model', 'Ext.app.ViewModel', 'Ext.app.ViewController', 'Ext.plugin.Viewport', 'Ext.grid.Panel', 'Ext.window.Window', 'Ext.form.Panel'],
function() {
    var Component = Ext.Component,
        proto = Component.prototype,
        compIdAttr = 'data-componentid',
        c;

    function makeComponent(cfg, isConfiguredEl) {
        c = new Ext.Component(cfg || {});

        // Note that els not created by the component need an extra step to inform from what its
        // owning component is.
        if (isConfiguredEl) {
            c.el.dom.setAttribute(compIdAttr, c.id);
        }

        return c;
    }

    function makeText() {
        var text = [],
            i;

        for (i = 0; i < 100; i++) {
            text.push('RIP Lucy the cat');
        }

        return text.join(' ');
    }

    beforeEach(function() {
        // Suppress console warnings about elements already destroyed
        spyOn(Ext.Logger, 'warn');
    });

    afterEach(function() {
        if (c) {
            c.destroy();
        }

        c = null;
    });

    describe("configuration", function() {
        it("should have a hideMode", function() {
            expect(proto.hideMode).toEqual('display');
        });

        it("should have no bubbleEvents", function() {
            expect(proto.bubbleEvents).toBeUndefined();
        });

        it("should have a renderTpl", function() {
            expect(proto.renderTpl).toBeDefined();
        });
    });

    describe("ids", function() {
        it("should generate an id if one isn't specified", function() {
            makeComponent();
            expect(c.id).toBeDefined();
        });

        it("should use an id if one is specified", function() {
            makeComponent({
                id: 'foo'
            });
            expect(c.id).toEqual('foo');
        });

        it("should return the itemId if one exists", function() {
            makeComponent({
                itemId: 'a'
            });
            expect(c.getItemId()).toEqual('a');
        });

        it("should fall back on the id if no itemId is specified", function() {
            makeComponent({
                id: 'foo'
            });
            expect(c.getItemId()).toEqual('foo');
        });

        it("should give the itemId precedence", function() {
            makeComponent({
                id: 'foo',
                itemId: 'bar'
            });
            expect(c.getItemId()).toEqual('bar');
        });

        it("should throw error if the Component has an invalid id", function() {
            function expectError(id) {
                expect(function() {
                    new Ext.Component({
                        id: id
                    });
                }).toThrow('Invalid component "id": "' + id + '"');
            }

            expectError('.abcdef');
            expectError('0a...');
            expectError('12345');
            expectError('.abc-def');
            expectError('<12345/>');
            expectError('1<>234.567');
        });
    });

    describe("registering with ComponentManager", function() {
        it("should register itself upon creation", function() {
            makeComponent({
                id: 'foo'
            });
            expect(Ext.ComponentManager.get('foo')).toEqual(c);
        });

        it("should unregister on destroy", function() {
            makeComponent({
                id: 'foo'
            });

            c.destroy();
            expect(Ext.ComponentManager.get('foo')).toBeUndefined();
        });
    });

    describe("setHtml/setData", function() {
        var MyModel = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: ['name']
        });

        describe("during construction", function() {
            it("should add the html", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    html: 'Foo'
                });
                expect(c.getEl().dom.innerHTML).toBe('Foo');
            });

            it("should add the data according to the template", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    tpl: '{x}/{y}',
                    data: {
                        x: 1,
                        y: 2
                    }
                });
                expect(c.getEl().dom.innerHTML).toBe('1/2');
            });

            it("should add data from a record", function() {
                var rec = new MyModel({
                    name: 'recName'
                });

                makeComponent({
                    renderTo: Ext.getBody(),
                    tpl: '{name}',
                    data: rec
                });

                expect(c.getEl().dom.innerHTML).toBe('recName');
            });

            it("should retain the data on the object", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    tpl: '{x}/{y}',
                    data: {
                        x: 1,
                        y: 2
                    }
                });
                expect(c.getData()).toEqual({
                    x: 1,
                    y: 2
                });
            });

            it("should retain record data", function() {
                var rec = new MyModel({
                    id: 1,
                    name: 'recName'
                });

                makeComponent({
                    renderTo: Ext.getBody(),
                    tpl: '{name}',
                    data: rec
                });

                expect(c.getData()).toEqual({
                    id: 1,
                    name: 'recName'
                });
            });
        });

        describe("before rendering", function() {
            it("should add the html", function() {
                makeComponent();
                c.setHtml('Foo');
                c.render(Ext.getBody());
                expect(c.getEl().dom.innerHTML).toBe('Foo');
            });

            it("should add the data according to the template", function() {
                makeComponent({
                    tpl: '{x}/{y}'
                });

                c.setData({
                    x: 1,
                    y: 2
                });

                c.render(Ext.getBody());
                expect(c.getEl().dom.innerHTML).toBe('1/2');
            });

            it("should add data from a record", function() {
                var rec = new MyModel({
                    name: 'recName'
                });

                makeComponent({
                    tpl: '{name}'
                });

                c.setData(rec);
                c.render(Ext.getBody());

                expect(c.getEl().dom.innerHTML).toBe('recName');
            });
        });

        describe("after rendering", function() {
            it("should add the html", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                c.setHtml('Foo');
                expect(c.getEl().dom.innerHTML).toBe('Foo');
            });

            it("should add the data according to the template", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    tpl: '{x}/{y}'
                });
                c.setData({
                    x: 1,
                    y: 2
                });
                expect(c.getEl().dom.innerHTML).toBe('1/2');
            });

            it("should add data from a record", function() {
                var rec = new MyModel({
                    name: 'recName'
                });

                makeComponent({
                    renderTo: Ext.getBody(),
                    tpl: '{name}'
                });

                c.setData(rec);
                expect(c.getEl().dom.innerHTML).toBe('recName');
            });
        });
    });

    describe("view controllers", function() {
        var Controller;

        beforeEach(function() {
            // Suppress console warning about mapping being overridden
            spyOn(Ext.log, 'warn');

            Controller = Ext.define('spec.TestController', {
                extend: 'Ext.app.ViewController',
                alias: 'controller.test',

                someFn: function() {}
            });
        });

        afterEach(function() {
            Ext.undefine('spec.TestController');
            Controller = null;
            Ext.Factory.controller.instance.clearCache();
        });

        describe("initializing", function() {
            it("should accept an alias string", function() {
                makeComponent({
                    controller: 'test'
                });

                var controller = c.getController();

                expect(controller instanceof spec.TestController).toBe(true);
                expect(controller.getView()).toBe(c);
            });

            it("should accept a controller config", function() {
                makeComponent({
                    controller: {
                        type: 'test'
                    }
                });

                var controller = c.getController();

                expect(controller instanceof spec.TestController).toBe(true);
                expect(controller.getView()).toBe(c);
            });

            it("should accept a controller instance", function() {
                var controller = new spec.TestController();

                makeComponent({
                    controller: controller
                });

                expect(c.getController()).toBe(controller);
                expect(controller.getView()).toBe(c);
            });

            it("should be able to pass null", function() {
                makeComponent({
                    controller: null
                });
                expect(c.getController()).toBeNull();
            });
        });

        it("should destroy the controller when destroying the component", function() {
            makeComponent({
                controller: 'test'
            });

            var controller = c.getController();

            spyOn(controller, 'destroy');
            c.destroy();

            expect(controller.destroy).toHaveBeenCalled();
        });

        describe("lookupController", function() {
            describe("skipThis: false", function() {
                it("should return null when there is no controller attached to the view", function() {
                    makeComponent();
                    expect(c.lookupController(false)).toBeNull();
                });

                it("should return null when there is no controller in the hierarchy", function() {
                    var ct = new Ext.container.Container({
                        items: {
                            xtype: 'component'
                        }
                    });

                    expect(ct.items.first().lookupController(false)).toBeNull();
                    ct.destroy();
                });

                it("should return the controller attached to the component when it is at the root", function() {
                    var controller = new spec.TestController();

                    makeComponent({
                        controller: controller
                    });
                    expect(c.lookupController(false)).toBe(controller);
                });

                it("should return the controller attached to the component when it is in a hierarchy", function() {
                    var controller = new spec.TestController();

                    var ct = new Ext.container.Container({
                        items: {
                            xtype: 'component',
                            controller: controller
                        }
                    });

                    expect(ct.items.first().lookupController(false)).toBe(controller);
                    ct.destroy();
                });

                it("should return a controller above it in the hierarchy", function() {
                    var controller = new spec.TestController();

                    var ct = new Ext.container.Container({
                        controller: controller,
                        items: {
                            xtype: 'component'
                        }
                    });

                    expect(ct.items.first().lookupController(false)).toBe(controller);
                    ct.destroy();
                });

                it("should return the closest controller in the hierarchy", function() {
                    var controller1 = new spec.TestController(),
                        controller2 = new spec.TestController();

                    var ct = new Ext.container.Container({
                        controller: controller1,
                        items: {
                            xtype: 'container',
                            controller: controller2,
                            items: {
                                xtype: 'component',
                                itemId: 'x'
                            }
                        }
                    });

                    expect(ct.down('#x').lookupController(false)).toBe(controller2);
                    ct.destroy();
                });
            });

            describe("skipThis: true", function() {
                it("should return null when there is no controller attached to the view", function() {
                    makeComponent();
                    expect(c.lookupController(true)).toBeNull();
                });

                it("should return null when there is no controller in the hierarchy", function() {
                    var ct = new Ext.container.Container({
                        items: {
                            xtype: 'component'
                        }
                    });

                    expect(ct.items.first().lookupController(true)).toBeNull();
                    ct.destroy();
                });

                it("should not return the controller attached to the component when it is at the root", function() {
                    var controller = new spec.TestController();

                    makeComponent({
                        controller: controller
                    });
                    expect(c.lookupController(true)).toBeNull();
                });

                it("should not return the controller attached to the component when it is in a hierarchy and no controllers exist above it", function() {
                    var controller = new spec.TestController();

                    var ct = new Ext.container.Container({
                        items: {
                            xtype: 'component',
                            controller: controller
                        }
                    });

                    expect(ct.items.first().lookupController(true)).toBeNull();
                    ct.destroy();
                });

                it("should return a controller above it in the hierarchy", function() {
                    var controller = new spec.TestController();

                    var ct = new Ext.container.Container({
                        controller: controller,
                        items: {
                            xtype: 'component'
                        }
                    });

                    expect(ct.items.first().lookupController(true)).toBe(controller);
                    ct.destroy();
                });

                it("should return the closest controller in the hierarchy", function() {
                    var controller1 = new spec.TestController(),
                        controller2 = new spec.TestController();

                    var ct = new Ext.container.Container({
                        controller: controller1,
                        items: {
                            xtype: 'container',
                            controller: controller2,
                            items: {
                                xtype: 'component',
                                itemId: 'x'
                            }
                        }
                    });

                    expect(ct.down('#x').lookupController(true)).toBe(controller2);
                    ct.destroy();
                });
            });

            it("should default to skipThis: false", function() {
                var controller = new spec.TestController();

                makeComponent({
                    controller: controller
                });
                expect(c.lookupController()).toBe(controller);
            });
        });
    });

    describe("viewmodel", function() {
        var spy, order, called;

        beforeEach(function() {
            called = false;
            Ext.define('spec.ViewModel', {
                extend: 'Ext.app.ViewModel',
                alias: 'viewmodel.test',
                constructor: function() {
                    this.callParent(arguments);
                    order.push(this.getId());
                    called = true;
                }
            });
            order = [];
        });

        afterEach(function() {
            Ext.undefine('spec.ViewModel');
            Ext.Factory.viewModel.instance.clearCache();
            order = null;
            called = false;
        });

        it("should accept a string alias", function() {
            makeComponent({
                viewModel: 'test'
            });
            expect(c.getViewModel() instanceof spec.ViewModel).toBe(true);
        });

        it("should accept an object config", function() {
            makeComponent({
                viewModel: {
                    type: 'test'
                }
            });
            expect(c.getViewModel() instanceof spec.ViewModel).toBe(true);
        });

        it("should accept an object instance", function() {
            var vm = new spec.ViewModel();

            makeComponent({
                viewModel: vm
            });
            expect(c.getViewModel()).toBe(vm);
        });

        it("should not create the instance while constructing the component", function() {
            makeComponent({
                viewModel: {
                    type: 'test'
                }
            });
            expect(called).toBe(false);
        });

        it("should initialize if there are no binds/publishes", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                viewModel: {
                    type: 'test'
                }
            });
            expect(called).toBe(true);
        });

        it("should not create an instance while constructing with binds", function() {
            makeComponent({
                bind: '{html}',
                viewModel: {
                    type: 'test'
                }
            });
            expect(called).toBe(false);
        });

        it("should create an instance while rendering with binds", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                bind: '{html}',
                viewModel: {
                    type: 'test'
                }
            });
            expect(called).toBe(true);
        });

        it("should create an instance when getViewModel is called", function() {
            makeComponent({
                viewModel: {
                    type: 'test'
                }
            });
            c.getViewModel();
            expect(called).toBe(true);
        });

        it("should create an instance when lookupViewModel is called", function() {
            makeComponent({
                viewModel: {
                    type: 'test'
                }
            });
            c.lookupViewModel();
            expect(called).toBe(true);
        });

        describe("calling initViewController", function() {
            var TestController = Ext.define(null, {
                extend: 'Ext.app.ViewController'
            });

            it("should call initViewController when creating an instance during rendering", function() {
                var ctrl = new TestController();

                makeComponent({
                    controller: ctrl,
                    viewModel: {
                        type: 'test'
                    },
                    bind: '{foo}'
                });
                spyOn(ctrl, 'initViewModel');
                c.render(Ext.getBody());
                expect(ctrl.initViewModel).toHaveBeenCalledWith(c.getViewModel());
            });

            it("should call initViewController when creating view a direct call to getViewModel", function() {
                var ctrl = new TestController();

                makeComponent({
                    controller: ctrl,
                    viewModel: {
                        type: 'test'
                    },
                    bind: '{foo}'
                });
                spyOn(ctrl, 'initViewModel');
                c.getViewModel();
                expect(ctrl.initViewModel).toHaveBeenCalledWith(c.getViewModel());
            });
        });

        describe("hierarchy", function() {
            var ct, inner;

            function vm(id) {
                return {
                    type: 'test',
                    id: id
                };
            }

            function makeHierarchy(bind) {
                ct = new Ext.container.Container({
                    viewModel: vm('top'),
                    items: {
                        xtype: 'container',
                        viewModel: vm('middle'),
                        items: {
                            xtype: 'component',
                            viewModel: vm('bottom'),
                            bind: bind || null
                        }
                    }
                });
                inner = ct.items.first();
                c = inner.items.first();
            }

            afterEach(function() {
                ct.destroy();
                ct = inner = null;
            });

            it("should not initialize any view models", function() {
                makeHierarchy();
                expect(called).toBe(false);
            });

            it("should initialize viewmodels top down", function() {
                makeHierarchy();
                c.getViewModel();
                expect(order).toEqual(['top', 'middle', 'bottom']);
            });

            it("should only initialize view models as needed when calling getViewModel", function() {
                makeHierarchy();
                ct.getViewModel();
                expect(order).toEqual(['top']);
                inner.getViewModel();
                expect(order).toEqual(['top', 'middle']);
                c.getViewModel();
                expect(order).toEqual(['top', 'middle', 'bottom']);
            });

            it("should only initialize view models as needed when calling lookupViewModel", function() {
                makeHierarchy();
                ct.lookupViewModel();
                expect(order).toEqual(['top']);
                inner.lookupViewModel();
                expect(order).toEqual(['top', 'middle']);
                c.lookupViewModel();
                expect(order).toEqual(['top', 'middle', 'bottom']);
            });

            it("should not create the instance when calling getInherited and skipping ourselves", function() {
                makeHierarchy();
                c.lookupViewModel(true);
                expect(order).toEqual(['top', 'middle']);
            });

            it("should automatically create the hierarchy during render with a bind", function() {
                makeHierarchy('{html}');
                ct.render(Ext.getBody());
                expect(order).toEqual(['top', 'middle', 'bottom']);
            });
        });

        describe("session", function() {
            it("should attach the view model to the session", function() {
                var session = new Ext.data.Session();

                makeComponent({
                    session: session,
                    viewModel: {}
                });
                expect(c.getViewModel().getSession()).toBe(session);
            });

            it("should attach the view model to a session higher up in the hierarchy", function() {
                var session = new Ext.data.Session();

                var ct = new Ext.container.Container({
                    session: session,
                    items: {
                        xtype: 'component',
                        viewModel: true
                    }
                });

                expect(ct.items.first().getViewModel().getSession()).toBe(session);
                ct.destroy();
            });

            it("should use an attached session at the same level instead of a higher one", function() {
                var session1 = new Ext.data.Session(),
                    session2 = new Ext.data.Session();

                var ct = new Ext.container.Container({
                    session: session1,
                    items: {
                        xtype: 'component',
                        session: session2,
                        viewModel: {}
                    }
                });

                expect(ct.items.first().getViewModel().getSession()).toBe(session2);
                ct.destroy();
            });
        });

        describe("destruction", function() {
            it("should destroy the viewModel when the component is destroyed", function() {
                makeComponent({
                    viewModel: {},
                    renderTo: Ext.getBody()
                });
                var vm = c.getViewModel();

                c.destroy();
                expect(vm.destroyed).toBe(true);
            });

            it("should not throw error when destroying components with null binding", function() {
                var ct = new Ext.Container({
                    renderTo: document.body,
                    viewModel: {
                        data: {
                            isDisabled: true
                        }
                    },
                    defaultType: 'button',
                    defaults: {
                        bind: {
                            disabled: '{isDisabled}'
                        }
                    },
                    items: [{
                        text: 'Foo',
                        bind: {
                            disabled: null
                        }
                    }, {
                        text: 'Bar'
                    }]
                });

                expect(function() {
                    ct.destroy();
                }).not.toThrow();
            });
        });
    });

    describe("session", function() {
        it("should not have a session by default", function() {
            makeComponent();
            expect(c.getSession()).toBeNull();
        });

        it("should use a passed session", function() {
            var session = new Ext.data.Session();

            makeComponent({
                session: session
            });
            expect(c.getSession()).toBe(session);
        });

        it("should create a session when session: true is specified", function() {
            makeComponent({
                session: true
            });
            expect(c.getSession().isSession).toBe(true);
        });

        it("should destroy the session when the component is destroyed", function() {
            var session = new Ext.data.Session(),
                spy = spyOn(session, 'destroy').andCallThrough();

            makeComponent({
                session: session
            });
            c.destroy();
            expect(spy).toHaveBeenCalled();
        });

        it("should not destroy the session with autoDestroy: false", function() {
            var session = new Ext.data.Session({
                autoDestroy: false
            });

            var spy = spyOn(session, 'destroy').andCallThrough();

            makeComponent({
                session: session
            });
            c.destroy();
            expect(spy).not.toHaveBeenCalled();
            session.destroy();
        });

        describe("hierarchy", function() {
            it("should use a parent session", function() {
                var session = new Ext.data.Session();

                var ct = new Ext.container.Container({
                    session: session,
                    items: {
                        xtype: 'component'
                    }
                });

                expect(ct.items.first().lookupSession()).toBe(session);
                ct.destroy();
            });

            it("should spawn a session from the parent if specifying session: true", function() {
                var session = new Ext.data.Session();

                var ct = new Ext.container.Container({
                    session: session,
                    items: {
                        xtype: 'component',
                        session: true
                    }
                });

                var child = ct.items.first().getSession();

                expect(child.getParent()).toBe(session);

                ct.destroy();
            });
        });
    });

    describe("bind", function() {
        describe("defaultBindProperty", function() {
            it("should bind with a string", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    viewModel: {
                        data: {
                            theHtml: 'foo'
                        }
                    },
                    bind: '{theHtml}'
                });
                c.getViewModel().notify();
                expect(c.getEl().dom.innerHTML).toBe('foo');
            });

            it("should throw an exception if we have no default bind", function() {
                expect(function() {
                    makeComponent({
                        defaultBindProperty: '',
                        viewModel: {
                            data: {
                                theHtml: 'foo'
                            }
                        },
                        bind: '{theHtml}'
                    });
                    c.getBind();
                }).toThrow();
            });
        });

        it("should be able to bind to multiple properties", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                viewModel: {
                    data: {
                        width: 200,
                        height: 200
                    }
                },
                bind: {
                    width: '{width}',
                    height: '{height}'
                }
            });
            c.getViewModel().notify();
            expect(c.getWidth()).toBe(200);
            expect(c.getHeight()).toBe(200);
        });

        it("should stop reacting when setting the binding to null", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                height: 200,
                viewModel: {
                    data: {
                        width: 200
                    }
                },
                bind: {
                    width: '{width}'
                }
            });
            var vm = c.getViewModel();

            vm.notify();
            expect(c.getWidth()).toBe(200);
            c.setBind({
                width: null
            });
            vm.set('width', 400);
            vm.notify();
            expect(c.getWidth()).toBe(200);
        });

        describe("twoWayBindable", function() {
            var Cls, viewModel;

            beforeEach(function() {
                Cls = Ext.define(null, {
                    extend: 'Ext.Component',
                    config: {
                        customA: 1,
                        customB: null,
                        customC: undefined,
                        customD: 'foo'
                    },
                    twoWayBindable: ['customB', 'customC', 'customD']
                });
            });

            afterEach(function() {
                viewModel = Cls = null;
            });

            function makeCls(cfg) {
                c = new Cls(Ext.apply({
                    renderTo: Ext.getBody()
                }, cfg));
                viewModel = c.getViewModel();
            }

            it("should not be twoWayBindable by default", function() {
                makeCls({
                    viewModel: {
                        data: {
                            a: 1
                        }
                    },
                    bind: {
                        customA: '{a}'
                    }
                });
                viewModel.notify();
                c.setCustomA('Foo');
                expect(viewModel.get('a')).toBe(1);
            });

            it("should not cause an error if a twoWayBindable is not bound", function() {
                expect(function() {
                    makeCls({
                        viewModel: {},
                        bind: {}
                    });
                }).not.toThrow();
            });

            it("should stop reacting when setting the binding to null", function() {
                makeCls({
                    renderTo: Ext.getBody(),
                    viewModel: {
                        data: {
                            d: 200
                        }
                    },
                    bind: {
                        customD: '{d}'
                    }
                });
                var vm = c.getViewModel();

                vm.notify();
                expect(c.getCustomD()).toBe(200);
                c.setBind({
                    customD: null
                });
                vm.set('customD', 400);
                vm.notify();
                expect(c.getCustomD()).toBe(200);
            });

            it("should not be two way if the binding has twoWay: false", function() {
                makeCls({
                    renderTo: Ext.getBody(),
                    viewModel: {
                        data: {
                            d: 200
                        }
                    },
                    bind: {
                        customD: {
                            bindTo: '{d}',
                            twoWay: false
                        }
                    }
                });
                var vm = c.getViewModel();

                vm.notify();
                c.setCustomD(400);
                vm.notify();
                expect(vm.get('d')).toBe(200);
            });

            describe("when the binding has not fired", function() {
                it("should not publish when the value is undefined", function() {
                    makeCls({
                        viewModel: {
                            data: {
                                c: 100
                            }
                        },
                        bind: {
                            customC: '{c}'
                        }
                    });
                    expect(viewModel.get('c')).toBe(100);
                    viewModel.notify();
                    expect(c.getCustomC()).toBe(100);
                });

                it("should not publish when the value is null", function() {
                    makeCls({
                        viewModel: {
                            data: {
                                b: 200
                            }
                        },
                        bind: {
                            customB: '{b}'
                        }
                    });
                    expect(viewModel.get('b')).toBe(200);
                    viewModel.notify();
                    expect(c.getCustomB()).toBe(200);
                });

                it("should not publish when the value is equal to the class default", function() {
                    makeCls({
                        viewModel: {
                            data: {
                                d: 'bar'
                            }
                        },
                        bind: {
                            customD: '{d}'
                        }
                    });
                    expect(viewModel.get('d')).toBe('bar');
                    viewModel.notify();
                    expect(c.getCustomD()).toBe('bar');
                });

                it("should not publish when the value is equal to the instance config value", function() {
                    makeCls({
                        customD: 'baz',
                        viewModel: {
                            data: {
                                d: 'bar'
                            }
                        },
                        bind: {
                            customD: '{d}'
                        }
                    });
                    expect(viewModel.get('d')).toBe('bar');
                    viewModel.notify();
                    expect(c.getCustomD()).toBe('bar');
                });

                it("should publish any other value", function() {
                    makeCls({
                        viewModel: {
                            data: {
                                d: 'bar'
                            }
                        },
                        bind: {
                            customD: '{d}'
                        }
                    });
                    c.setCustomD('new');
                    expect(viewModel.get('d')).toBe('new');
                });
            });

            describe("when the binding has fired", function() {
                it("should publish undefined", function() {
                    makeCls({
                        viewModel: {
                            b: 'x'
                        },
                        bind: {
                            customB: '{b}'
                        }
                    });
                    viewModel.notify();
                    c.setCustomB(undefined);
                    // ViewModel converts undefined to null
                    expect(viewModel.get('b')).toBeNull();
                });

                it("should publish null", function() {
                    makeCls({
                        viewModel: {
                            b: 'x'
                        },
                        bind: {
                            customB: '{b}'
                        }
                    });
                    viewModel.notify();
                    c.setCustomB(null);
                    expect(viewModel.get('b')).toBeNull();
                });

                it("should publish the class default", function() {
                    makeCls({
                        viewModel: {
                            data: {
                                d: 'bar'
                            }
                        },
                        bind: {
                            customD: '{d}'
                        }
                    });
                    viewModel.notify();
                    c.setCustomD('foo');
                    expect(viewModel.get('d')).toBe('foo');
                });

                it("should publish the instance config value", function() {
                    makeCls({
                        customD: 'baz',
                        viewModel: {
                            data: {
                                d: 'bar'
                            }
                        },
                        bind: {
                            customD: '{d}'
                        }
                    });
                    viewModel.notify();
                    c.setCustomD('baz');
                    expect(viewModel.get('d')).toBe('baz');
                });
            });
        });

        describe("on destruction", function() {
            beforeEach(function() {
                Ext.define('spec.BindCls', {
                    extend: 'Ext.Component',
                    xtype: 'bindcls',
                    config: {
                        test: null
                    }
                });
            });

            afterEach(function() {
                Ext.undefine('spec.BindCls');
            });

            it("should remove bindings when children are destroyed", function() {
                var ct = new Ext.container.Container({
                    viewModel: {
                        data: {
                            foo: 1
                        }
                    },
                    renderTo: Ext.getBody(),
                    items: {
                        xtype: 'bindcls',
                        bind: {
                            test: '{foo}'
                        }
                    }
                }),
                vm = ct.getViewModel();

                var c = ct.items.first();

                spyOn(c, 'setTest');
                vm.notify();
                expect(c.setTest.callCount).toBe(1);
                c.setTest.reset();

                vm.set('foo', 2);

                // The bind is queued up
                c.destroy();
                vm.notify();
                expect(c.setTest).not.toHaveBeenCalled();

                ct.destroy();
            });
        });
    });

    describe("listener scope resolution", function() {
        var spies, scopes, Cmp, cmp, Parent, parent, Grandparent, grandparent,
            Controller, ParentController, GrandparentController;

        function defineParent(cfg) {
            Parent = Ext.define(null, Ext.apply({
                extend: 'Ext.Container',
                onFoo: spies.parent
            }, cfg));
        }

        function defineGrandparent(cfg) {
            Grandparent = Ext.define(null, Ext.apply({
                extend: 'Ext.Container',
                onFoo: spies.grandparent
            }, cfg));
        }

        function expectScope(scope) {
            var scopes = {
                    component: cmp,
                    controller: cmp.getController(),
                    parent: parent,
                    parentController: parent && parent.getController(),
                    grandparent: grandparent,
                    grandparentController: grandparent && grandparent.getController()
                },
                name, spy;

            for (name in spies) {
                spy = spies[name];

                if (name === scope) {
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.object).toBe(scopes[name]);
                }
                else {
                    expect(spy).not.toHaveBeenCalled();
                }
            }
        }

        beforeEach(function() {
            spies = {
                component: jasmine.createSpy(),
                controller: jasmine.createSpy(),
                parent: jasmine.createSpy(),
                parentController: jasmine.createSpy(),
                grandparent: jasmine.createSpy(),
                grandparentController: jasmine.createSpy()
            };

            Controller = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.controller
            });

            ParentController = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.parentController
            });

            GrandparentController = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.grandparentController
            });
        });

        afterEach(function() {
            cmp = parent = grandparent = Ext.destroy(cmp, parent, grandparent);
        });

        describe("listener declared on class body", function() {
            function defineCmp(cfg) {
                Cmp = Ext.define(null, Ext.merge({
                    extend: 'Ext.Component',
                    listeners: {
                        foo: 'onFoo'
                    },
                    onFoo: spies.component
                }, cfg));
            }

            it("should resolve to the component with unspecified scope", function() {
                defineCmp();
                cmp = new Cmp();
                cmp.fireEvent('foo');
                expectScope('component');
            });

            it("should fail with scope:'controller'", function() {
                defineCmp({
                    listeners: {
                        scope: 'controller'
                    }
                });
                cmp = new Cmp();
                expect(function() {
                    cmp.fireEvent('foo');
                }).toThrow();
            });

            it("should resolve to the component with scope:'this'", function() {
                defineCmp({
                    listeners: {
                        scope: 'this'
                    }
                });
                cmp = new Cmp();
                cmp.fireEvent('foo');
                expectScope('component');
            });

            describe("with view controller", function() {
                it("should resolve to the view controller with unspecified scope", function() {
                    defineCmp({
                        controller: new Controller()
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the view controller with scope:'controller'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with defaultListenerScope", function() {
                it("should resolve to the component with unspecified scope", function() {
                    defineCmp({
                        defaultListenerScope: true
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller and defaultListenerScope", function() {
                it("should resolve to the component with unspecified scope", function() {
                    defineCmp({
                        controller: new Controller(),
                        defaultListenerScope: true
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should resolve to the view controller with scope:'controller'", function() {
                    defineCmp({
                        controller: new Controller(),
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        controller: new Controller(),
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the parent with unspecified scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController(),
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the parent with unspecified scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parent');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should fail with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController(),
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineCmp({
                        controller: new Controller()
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineCmp({
                        controller: new Controller()
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineCmp({
                        controller: new Controller()
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on parent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the component with unspecified scope", function() {
                    defineCmp({
                        defaultListenerScope: true
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineCmp({
                        controller: new Controller()
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on grandparent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                });

                it("should resolve to the component with unspecified scope", function() {
                    defineCmp({
                        defaultListenerScope: true
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with scope declared on inner object", function() {
                it("should resolve to controller with unspecified outer scope", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        controller: new Controller(),
                        listeners: {
                            foo: {
                                fn: 'onFoo',
                                scope: 'controller'
                            }
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to controller with outer scope of controller", function() {
                    defineCmp({
                        defaultListenerScope: true,
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller',
                            foo: {
                                fn: 'onFoo',
                                scope: 'controller'
                            }
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expectScope('controller');
                });
            });

            describe("with handler declared as a function reference", function() {
                var handler, scope;

                function defineCmp(cfg) {
                    Cmp = Ext.define(null, Ext.merge({
                        extend: 'Ext.Component',
                        listeners: {
                            foo: handler
                        }
                    }, cfg));
                }

                beforeEach(function() {
                    handler = jasmine.createSpy();
                    handler.andCallFake(function() {
                        scope = this;
                    });
                });

                afterEach(function() {
                    scope = null;
                });

                it("should use the component as the default scope", function() {
                    defineCmp();
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expect(handler).toHaveBeenCalled();
                    expect(handler.mostRecentCall.object).toBe(cmp);
                });

                it("should use an arbitrary object as the scope", function() {
                    var obj = {};

                    defineCmp({
                        listeners: {
                            scope: obj
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expect(scope).toBe(scope);
                });

                it("should use the component with scope:'this'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expect(scope).toBe(cmp);
                });

                it("should fail with scope:'controller'", function() {
                    defineCmp({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    cmp = new Cmp();
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should use the component with scope:'this' specified on an inner object", function() {
                    defineCmp({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'this'
                            }
                        }
                    });
                    cmp = new Cmp();
                    cmp.fireEvent('foo');
                    expect(scope).toBe(cmp);
                });

                it("should fail with scope:'controller' specified on an inner object", function() {
                    defineCmp({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'controller'
                            }
                        }
                    });
                    cmp = new Cmp();
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                describe("with view controller", function() {
                    it("should resolve to the component with unspecified scope", function() {
                        defineCmp({
                            controller: new Controller()
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should resolve to the view controller with scope:'controller'", function() {
                        defineCmp({
                            controller: new Controller(),
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp.getController());
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        defineCmp({
                            controller: new Controller(),
                            listeners: {
                                scope: 'this'
                            }
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with defaultListenerScope", function() {
                    it("should resolve to the component with unspecified scope", function() {
                        defineCmp({
                            defaultListenerScope: true
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should fail with scope:'controller'", function() {
                        defineCmp({
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        cmp = new Cmp();
                        expect(function() {
                            cmp.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        defineCmp({
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'this'
                            }
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with view controller and defaultListenerScope", function() {
                    it("should resolve to the component with unspecified scope", function() {
                        defineCmp({
                            controller: new Controller(),
                            defaultListenerScope: true
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should resolve to the view controller with scope:'controller'", function() {
                        defineCmp({
                            controller: new Controller(),
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp.getController());
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        defineCmp({
                            controller: new Controller(),
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'this'
                            }
                        });
                        cmp = new Cmp();
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with defaultListenerScope on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            defaultListenerScope: true
                        });
                    });

                    it("should resolve to the component with unspecified scope", function() {
                        defineCmp();
                        cmp = new Cmp();
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should fail with scope:'controller'", function() {
                        defineCmp({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        cmp = new Cmp();
                        parent = new Parent({
                            items: cmp
                        });
                        expect(function() {
                            cmp.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        defineCmp({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        cmp = new Cmp();
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with view controller on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            controller: new ParentController()
                        });
                    });

                    it("should resolve to the component with unspecified scope", function() {
                        defineCmp();
                        cmp = new Cmp();
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should resolve to the parent view controller with scope:'controller'", function() {
                        defineCmp({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        cmp = new Cmp();
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(parent.getController());
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        defineCmp({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        cmp = new Cmp();
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });
            });
        });

        describe("listener declared on instance config", function() {
            function defineCmp(cfg) {
                Cmp = Ext.define(null, Ext.merge({
                    extend: 'Ext.Component',
                    onFoo: spies.component
                }, cfg));
            }

            it("should resolve to the component with unspecified scope", function() {
                defineCmp();
                cmp = new Cmp({
                    listeners: {
                        foo: 'onFoo'
                    }
                });
                cmp.fireEvent('foo');
                expectScope('component');
            });

            it("should fail with scope:'controller'", function() {
                defineCmp();
                cmp = new Cmp({
                    listeners: {
                        foo: 'onFoo',
                        scope: 'controller'
                    }
                });
                expect(function() {
                    cmp.fireEvent('foo');
                }).toThrow();
            });

            it("should resolve to the component with scope:'this'", function() {
                defineCmp();
                cmp = new Cmp({
                    listeners: {
                        foo: 'onFoo',
                        scope: 'this'
                    }
                });
                cmp.fireEvent('foo');
                expectScope('component');
            });

            describe("with view controller", function() {
                beforeEach(function() {
                    defineCmp({
                        controller: new Controller()
                    });
                });

                it("should resolve to the component with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with defaultListenerScope", function() {
                beforeEach(function() {
                    defineCmp({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to fail with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller and defaultListenerScope", function() {
                beforeEach(function() {
                    defineCmp({
                        controller: new Controller(),
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the component with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                    defineCmp();
                });

                it("should resolve to the parent with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                    defineCmp();
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController(),
                        defaultListenerScope: true
                    });
                    defineCmp();
                });

                it("should resolve to the parent with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parent');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });
                    defineCmp();
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                    defineCmp();
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController(),
                        defaultListenerScope: true
                    });
                    defineCmp();
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });

                    defineCmp({
                        controller: new Controller()
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });

                    defineCmp({
                        controller: new Controller()
                    });
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });

                    defineCmp({
                        controller: new Controller()
                    });
                });

                it("should resolve to the parent with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on parent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });

                    defineCmp({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: cmp
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on child and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });

                    defineCmp({
                        controller: new Controller()
                    });
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should fail with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with view controller on grandparent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });

                    defineCmp({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the component with scope:'this'", function() {
                    cmp = new Cmp({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: cmp
                        }
                    });
                    cmp.fireEvent('foo');
                    expectScope('component');
                });
            });

            describe("with handler declared as a function reference", function() {
                var handler, scope;

                function defineCmp(cfg) {
                    Cmp = Ext.define(null, Ext.merge({
                        extend: 'Ext.Component'
                    }, cfg));
                }

                beforeEach(function() {
                    handler = jasmine.createSpy();
                    handler.andCallFake(function() {
                        scope = this;
                    });
                });

                afterEach(function() {
                    scope = null;
                });

                it("should use the component as the default scope", function() {
                    defineCmp();
                    cmp = new Cmp({
                        listeners: {
                            foo: handler
                        }
                    });
                    cmp.fireEvent('foo');
                    expect(scope).toBe(cmp);
                });

                it("should use an arbitrary object as the scope", function() {
                    defineCmp();
                    var scope = {};

                    cmp = new Cmp({
                        listeners: {
                            foo: handler,
                            scope: scope
                        }
                    });
                    cmp.fireEvent('foo');
                    expect(scope).toBe(scope);
                });

                it("should use the component with scope:'this'", function() {
                    defineCmp();
                    cmp = new Cmp({
                        listeners: {
                            foo: handler,
                            scope: 'this'
                        }
                    });
                    cmp.fireEvent('foo');
                    expect(scope).toBe(cmp);
                });

                it("should fail with scope:'controller'", function() {
                    defineCmp();
                    cmp = new Cmp({
                        listeners: {
                            foo: handler,
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                it("should use the component with scope:'this' specified on an inner object", function() {
                    defineCmp();
                    cmp = new Cmp({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'this'
                            }
                        }
                    });
                    cmp.fireEvent('foo');
                    expect(scope).toBe(cmp);
                });

                it("should fail with scope:'controller' specified on an inner object", function() {
                    defineCmp();
                    cmp = new Cmp({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'controller'
                            }
                        }
                    });
                    expect(function() {
                        cmp.fireEvent('foo');
                    }).toThrow();
                });

                describe("with view controller", function() {
                    beforeEach(function() {
                        defineCmp({
                            controller: new Controller()
                        });
                    });

                    it("should resolve to the component with unspecified scope", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler
                            }
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should fail with scope:'controller'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        expect(function() {
                            cmp.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with defaultListenerScope", function() {
                    beforeEach(function() {
                        defineCmp({
                            defaultListenerScope: true
                        });
                    });

                    it("should resolve to the component with unspecified scope", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler
                            }
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should fail with scope:'controller'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        expect(function() {
                            cmp.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with defaultListenerScope on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            defaultListenerScope: true
                        });
                        defineCmp();
                    });

                    it("should resolve to the component with unspecified scope", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler
                            }
                        });
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should fail with scope:'controller'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        parent = new Parent({
                            items: cmp
                        });
                        expect(function() {
                            cmp.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });

                describe("with view controller on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            controller: new ParentController()
                        });
                        defineCmp();
                    });

                    it("should resolve to the component with unspecified scope", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler
                            }
                        });
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });

                    it("should resolve to the parent view controller with scope:'controller'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(parent.getController());
                    });

                    it("should resolve to the component with scope:'this'", function() {
                        cmp = new Cmp({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        parent = new Parent({
                            items: cmp
                        });
                        cmp.fireEvent('foo');
                        expect(scope).toBe(cmp);
                    });
                });
            });
        });
    });

    describe("suspend/resume layouts", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
        });

        function s() {
            c.suspendLayouts();
        }

        function r() {
            c.resumeLayouts();
        }

        function expectSuspended(suspended) {
            expect(c.isLayoutSuspended()).toBe(suspended);
        }

        it("should not be suspended by default", function() {
            expectSuspended(false);
        });

        it("should suspend layouts when suspend is called", function() {
            s();
            expectSuspended(true);
        });

        it("should not suspend when resume is called", function() {
            s();
            r();
            expectSuspended(false);
        });

        it("should be suspended after calling suspend more times than resume", function() {
            s();
            s();
            s();
            r();
            r();
            expectSuspended(true);
        });

        it("should not be suspended after calling resume more times than suspend", function() {
            // Suppress console warning about mismatched resumeLayout call
            spyOn(Ext.log, 'warn');

            s();
            s();
            r();
            r();
            r();
            expectSuspended(false);
        });

        it("should not run a layout while suspended", function() {
            var spy = jasmine.createSpy();

            c.on('resize', spy);
            c.suspendLayouts();
            c.setSize(200, 200);
            expect(spy).not.toHaveBeenCalled();
        });

        it("should keep any layout pending if resume is called without the flush param", function() {
            var spy = jasmine.createSpy();

            c.on('resize', spy);
            c.suspendLayouts();
            c.setSize(200, 200);
            c.resumeLayouts();
            expect(spy).not.toHaveBeenCalled();

            c.updateLayout();
            expect(c.el.getWidth()).toBe(200);
            expect(c.el.getHeight()).toBe(200);
        });

        it("should suspend layouts in Ext.batchLayouts", function() {
            Ext.batchLayouts(function() {
                expect(Component.layoutSuspendCount).toBe(1);
            });
            expect(Component.layoutSuspendCount).toBe(0);
        });

        it("should resume layouts in Ext.batchLayouts if fn throws an error", function() {
            // try/catch required to avoid causing test to fail due to the exception thrown
            try {
                Ext.batchLayouts(function() {
                    expect(Component.layoutSuspendCount).toBe(1);
                    throw 'unexpected exception';
                });
            }
            catch (e) {
            }

            // finally should resume
            expect(Component.layoutSuspendCount).toBe(0);
        });

        it("should run the layout straight away when resuming layouts with flush", function() {
            var spy = jasmine.createSpy();

            c.on('resize', spy);
            c.suspendLayouts();
            c.setSize(200, 200);
            c.resumeLayouts(true);
            expect(spy.callCount).toBe(1);
        });

        it("should layout correctly when the sizeModel is altered during a suspend and the component is pending", function() {
            c.destroy();
            makeComponent({
                floating: true,
                autoShow: true
            });
            var count = c.componentLayoutCounter;

            c.suspendLayouts();
            c.setWidth(200);
            c.setHeight(200);
            c.resumeLayouts(true);
            expect(c.componentLayoutCounter).toBe(count + 1);
            expect(c.getWidth()).toBe(200);
            expect(c.getHeight()).toBe(200);

            count = c.componentLayoutCounter;
            c.suspendLayouts();
            c.setHtml('<div style="width: 50px; height: 30px;"></div>');
            c.shrinkWrap = true;
            c.setWidth(null);
            c.setHeight(null);
            c.resumeLayouts(true);
            expect(c.componentLayoutCounter).toBe(count + 1);
            expect(c.getWidth()).toBe(50);
            expect(c.getHeight()).toBe(30);
         });

        it("should update the size model correctly while suspended as state changes", function() {
            var sizeModels = Ext.layout.SizeModel.sizeModels,
                cCount, ctCount;

            var ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'fit',
                width: 300
            });

            function expectSizeModel(comp, widthModel, heightModel) {
                var sizeModel = comp.getSizeModel();

                expect(sizeModel.width).toBe(widthModel);
                expect(sizeModel.height).toBe(heightModel);
            }

            cCount = c.componentLayoutCounter;
            ctCount = ct.componentLayoutCounter;
            Ext.suspendLayouts();

            expectSizeModel(ct, sizeModels.configured, sizeModels.shrinkWrap);
            expectSizeModel(c, sizeModels.natural, sizeModels.shrinkWrap);

            // Forces both items to be invalidated
            ct.setWidth(600);
            c.setWidth(400);

            expectSizeModel(ct, sizeModels.configured, sizeModels.shrinkWrap);
            expectSizeModel(c, sizeModels.configured, sizeModels.shrinkWrap);

            ct.add(c);

            expectSizeModel(ct, sizeModels.configured, sizeModels.shrinkWrap);
            expectSizeModel(c, sizeModels.calculated, sizeModels.shrinkWrap);

            c.setHeight(100);

            expectSizeModel(ct, sizeModels.configured, sizeModels.shrinkWrap);
            expectSizeModel(c, sizeModels.calculated, sizeModels.configured);

            ct.setHeight(200);

            expectSizeModel(ct, sizeModels.configured, sizeModels.configured);
            expectSizeModel(c, sizeModels.calculated, sizeModels.calculated);

            Ext.resumeLayouts(true);

            expect(c.componentLayoutCounter).toBe(cCount + 1);
            expect(ct.componentLayoutCounter).toBe(ctCount + 1);

            ct.destroy();
         });
    });

    describe("layouts", function() {
        var count;

        function makeLayoutComponent(cfg) {
            makeComponent(Ext.apply({
                renderTo: Ext.getBody()
            }, cfg));
            count = c.componentLayoutCounter;
        }

        afterEach(function() {
            count = 0;
        });

        describe("when setting dimensions", function() {
            describe("setWidth", function() {
                describe("with a configured width", function() {
                    beforeEach(function() {
                        makeLayoutComponent({
                            width: 200
                        });
                    });

                    it("should trigger a layout when setting a different width", function() {
                        c.setWidth(300);
                        expect(c.componentLayoutCounter).toBe(count + 1);
                    });

                    it("should trigger a layout when clearing the width", function() {
                        c.setWidth(null);
                        expect(c.componentLayoutCounter).toBe(count + 1);
                    });

                    it("should not trigger a layout when setting the same width", function() {
                        c.setWidth(200);
                        expect(c.componentLayoutCounter).toBe(count);
                    });

                    it("should not trigger a layout when passing undefined", function() {
                        c.setWidth(undefined);
                        expect(c.componentLayoutCounter).toBe(count);
                    });
                });

                describe("without a configured width", function() {
                    beforeEach(function() {
                        makeLayoutComponent();
                    });

                    it("should trigger a layout when setting a width", function() {
                        c.setWidth(300);
                        expect(c.componentLayoutCounter).toBe(count + 1);
                    });

                    it("should not trigger a layout when clearing the width", function() {
                        c.setWidth(null);
                        expect(c.componentLayoutCounter).toBe(count);
                    });

                    it("should not trigger a layout when passing undefined", function() {
                        c.setWidth(undefined);
                        expect(c.componentLayoutCounter).toBe(count);
                    });
                });
            });

            describe("setHeight", function() {
                describe("with a configured height", function() {
                    beforeEach(function() {
                        makeLayoutComponent({
                            height: 200
                        });
                    });

                    it("should trigger a layout when setting a different height", function() {
                        c.setHeight(300);
                        expect(c.componentLayoutCounter).toBe(count + 1);
                    });

                    it("should trigger a layout when clearing the height", function() {
                        c.setHeight(null);
                        expect(c.componentLayoutCounter).toBe(count + 1);
                    });

                    it("should not trigger a layout when setting the same height", function() {
                        c.setHeight(200);
                        expect(c.componentLayoutCounter).toBe(count);
                    });

                    it("should not trigger a layout when passing undefined", function() {
                        c.setHeight(undefined);
                        expect(c.componentLayoutCounter).toBe(count);
                    });
                });

                describe("without a configured height", function() {
                    beforeEach(function() {
                        makeLayoutComponent();
                    });

                    it("should trigger a layout when setting a height", function() {
                        c.setHeight(300);
                        expect(c.componentLayoutCounter).toBe(count + 1);
                    });

                    it("should not trigger a layout when clearing the height", function() {
                        c.setHeight(null);
                        expect(c.componentLayoutCounter).toBe(count);
                    });

                    it("should not trigger a layout when passing undefined", function() {
                        c.setHeight(undefined);
                        expect(c.componentLayoutCounter).toBe(count);
                    });
                });
            });

            describe("setSize", function() {
                function makeSuite(widthOptions, heightOptions, expected) {
                    Ext.each(widthOptions, function(widthItem, i) {
                        Ext.each(heightOptions, function(heightItem, j) {
                            var offset = expected[i][j];

                            it("should" + (!offset ? " not " : "") + " layout when " + widthItem.name + " width and " + heightItem.name + " height", function() {
                                c.setSize(widthItem.value, heightItem.value);
                                expect(c.componentLayoutCounter).toBe(count + offset);
                            });
                        });
                    });
                }

                describe("with a configured width and configured height", function() {
                    beforeEach(function() {
                        makeLayoutComponent({
                            width: 200,
                            height: 200
                        });
                    });

                    makeSuite([{
                        name: 'setting the same',
                        value: 200
                    }, {
                        name: 'setting a different',
                        value: 300
                    }, {
                        name: 'clearing the',
                        value: null
                    }, {
                        name: 'leaving the',
                        value: undefined
                    }], [{
                        name: 'setting the same',
                        value: 200
                    }, {
                        name: 'setting a different',
                        value: 300
                    }, {
                        name: 'clearing the',
                        value: null
                    }, {
                        name: 'leaving the',
                        value: undefined
                    }], [
                        [0, 1, 1, 0],
                        [1, 1, 1, 1],
                        [1, 1, 1, 1],
                        [0, 1, 1, 0]
                    ]);

                });

                describe("with a configured width and without a configured height", function() {
                    beforeEach(function() {
                        makeLayoutComponent({
                            width: 200
                        });
                    });

                    makeSuite([{
                        name: 'setting the same',
                        value: 200
                    }, {
                        name: 'setting a different',
                        value: 300
                    }, {
                        name: 'clearing',
                        value: null
                    }, {
                        name: 'leaving the',
                        value: undefined
                    }], [{
                        name: 'setting a',
                        value: 300
                    }, {
                        name: 'leaving the'
                    }], [
                        [1, 0],
                        [1, 1],
                        [1, 1],
                        [1, 0]
                    ]);
                });

                describe("without a configured width and with a configured height", function() {
                    beforeEach(function() {
                        makeLayoutComponent({
                            height: 200
                        });
                    });

                    makeSuite([{
                        name: 'setting a',
                        value: 300
                    }, {
                        name: 'leaving the'
                    }], [{
                        name: 'setting the same',
                        value: 200
                    }, {
                        name: 'setting a different',
                        value: 300
                    }, {
                        name: 'clearing',
                        value: null
                    }, {
                        name: 'leaving the',
                        value: undefined
                    }], [
                        [1, 1, 1, 1],
                        [0, 1, 1, 0]
                    ]);
                });

                describe("without a configured width and without a configured height", function() {
                    beforeEach(function() {
                        makeLayoutComponent();
                    });

                    makeSuite([{
                        name: 'setting a',
                        value: 300
                    }, {
                        name: 'leaving the'
                    }], [{
                        name: 'setting a',
                        value: 300
                    }, {
                        name: 'leaving the'
                    }], [
                        [1, 1],
                        [1, 0]
                    ]);
                });
            });
        });
    });

    describe("xtypes",  function() {
        it("should work with a string", function() {
            makeComponent();
            expect(c.isXType('component')).toBe(true);
        });

        it("should not match incorrectly", function() {
            makeComponent();
            expect(c.isXType('panel')).toBe(false);
        });

        it("should match subclasses by default", function() {
            var ct = new Ext.container.Container();

            expect(ct.isXType('component')).toBe(true);
            ct.destroy();
        });

        it("should match exactly if shallow is true", function() {
            var ct = new Ext.container.Container();

            expect(ct.isXType('component', true)).toBe(false);
            ct.destroy();
        });
    });

    describe('render', function() {
        it('should veto its render', function() {
            var comp = new Ext.Component({
                renderTo: document.body,
                listeners: {
                    beforerender: function() {
                        return false;
                    }
                }
            });

            // Component not rendered
            expect(comp.rendered).toBe(false);

            comp.destroy();
        });

        describe("with an existing element", function() {
            var ct;

            afterEach(function() {
                ct = Ext.destroy(ct);
            });

            it("should be able to render", function() {
                var el = Ext.getBody().createChild();

                makeComponent({
                    el: el,
                    renderTo: Ext.getBody()
                });
                expect(c.getEl()).toBe(el);
            });

            it("should be able to render a panel with component in a fit layout", function() {
                var body = Ext.getBody(),
                    el = body.createChild();

                ct = new Ext.panel.Panel({
                    el: el,
                    layout: 'fit',
                    items: {
                        xtype: 'component',
                        width: 200,
                        height: 200
                    }
                });

                ct.render();
                expect(ct.el).toBe(el);
                expect(ct.el.component).toBe(ct);
                expect(ct.body.component).toBe(ct);

                var bodyEl = ct.body.el.dom;

                expect(bodyEl.parentNode.parentNode).toBe(ct.el.dom);

                var item = ct.items.getAt(0);

                expect(item.el.dom.parentNode).toBe(bodyEl);
                expect(item.el.component).toBe(item);
            });

            it("should be able to render a panel in panel using fit layout", function() {
                var body = Ext.getBody(),
                    el = body.createChild(),
                    el2 = body.createChild();

                ct = new Ext.panel.Panel({
                    el: el,
                    layout: 'fit',
                    items: {
                        xtype: 'panel',
                        layout: 'fit',
                        el: el2,
                        width: 200,
                        height: 200,
                        items: [{
                            xtype: 'component'
                        }]
                    }
                });

                ct.render();
                expect(ct.el).toBe(el);
                expect(ct.el.component).toBe(ct);
                expect(ct.body.component).toBe(ct);

                var bodyEl = ct.body.el.dom;

                expect(bodyEl.parentNode.parentNode).toBe(ct.el.dom);

                var subPanel = ct.items.getAt(0);

                var bodyEl2 = subPanel.body.el.dom;

                expect(subPanel.el.dom.parentNode).toBe(bodyEl);
                expect(bodyEl2.parentNode.parentNode).toBe(subPanel.el.dom);

                var item = subPanel.items.getAt(0);

                expect(item.el.dom.parentNode).toBe(bodyEl2);
                expect(item.el.component).toBe(item);
            });

            it("should be able to render a panel with component in an anchor layout", function() {
                var body = Ext.getBody(),
                    el = body.createChild();

                ct = new Ext.panel.Panel({
                    el: el,
                    layout: 'anchor',
                    items: {
                        xtype: 'component',
                        width: 200,
                        height: 200
                    }
                });

                ct.render();
                expect(ct.el).toBe(el);
                expect(ct.el.component).toBe(ct);
                expect(ct.body.component).toBe(ct);

                var bodyEl = ct.body.el.dom;

                expect(bodyEl.parentNode.parentNode).toBe(ct.el.dom);

                var outerCt = ct.layout.outerCt;

                expect(outerCt.component).toBe(ct);
                expect(outerCt.dom.parentNode).toBe(bodyEl);

                var innerCt = ct.layout.innerCt;

                expect(innerCt.component).toBe(ct);
                expect(innerCt.dom.parentNode).toBe(outerCt.dom);

                var item = ct.items.getAt(0);

                expect(item.el.dom.parentNode).toBe(innerCt.dom);
                expect(item.el.component).toBe(item);
            });

            it("should be able to render a panel with component in an hbox layout", function() {
                var body = Ext.getBody(),
                    el = body.createChild();

                ct = new Ext.panel.Panel({
                    el: el,
                    layout: 'hbox',
                    items: {
                        xtype: 'component',
                        width: 200,
                        height: 200
                    }
                });

                ct.render();
                expect(ct.el).toBe(el);
                expect(ct.el.component).toBe(ct);
                expect(ct.body.component).toBe(ct);

                var bodyEl = ct.body.el.dom;

                expect(bodyEl.parentNode.parentNode).toBe(ct.el.dom);

                var innerCt = ct.layout.innerCt;

                expect(innerCt.component).toBe(ct);
                expect(innerCt.dom.parentNode).toBe(bodyEl);

                var targetEl = ct.layout.targetEl;

                expect(targetEl.component).toBe(ct);
                expect(targetEl.dom.parentNode).toBe(innerCt.dom);

                var item = ct.items.getAt(0);

                expect(item.el.dom.parentNode).toBe(targetEl.dom);
                expect(item.el.component).toBe(item);
            });

            it("should be able to render a panel with component in a vbox layout", function() {
                var body = Ext.getBody(),
                    el = body.createChild();

                ct = new Ext.panel.Panel({
                    el: el,
                    layout: 'vbox',
                    items: {
                        xtype: 'component',
                        width: 200,
                        height: 200
                    }
                });

                ct.render();
                expect(ct.el).toBe(el);
                expect(ct.el.component).toBe(ct);
                expect(ct.body.component).toBe(ct);

                var bodyEl = ct.body.el.dom;

                expect(bodyEl.parentNode.parentNode).toBe(ct.el.dom);

                var innerCt = ct.layout.innerCt;

                expect(innerCt.component).toBe(ct);
                expect(innerCt.dom.parentNode).toBe(bodyEl);

                var targetEl = ct.layout.targetEl;

                expect(targetEl.component).toBe(ct);
                expect(targetEl.dom.parentNode).toBe(innerCt.dom);

                var item = ct.items.getAt(0);

                expect(item.el.dom.parentNode).toBe(targetEl.dom);
                expect(item.el.component).toBe(item);
            });
        });
    });

    describe("enable/disable", function() {
        var enableSpy, disableSpy;

        var C = Ext.define(null, {
            extend: 'Ext.Component',
            onDisableCount: 0,
            onEnableCount: 0,
            unmaskCount: 0,
            maskCount: 0,

            onDisable: function() {
                ++this.onDisableCount;
                this.callParent(arguments);
            },

            onEnable: function() {
                ++this.onEnableCount;
                this.callParent(arguments);
            },

            unmask: function() {
                ++this.unmaskCount;
                this.callParent(arguments);
            },

            mask: function() {
                ++this.maskCount;
                this.callParent(arguments);
            }
        });

        beforeEach(function() {
            enableSpy = jasmine.createSpy();
            disableSpy = jasmine.createSpy();
        });

        afterEach(function() {
            enableSpy = disableSpy = null;
        });

        function makeDisableComp(cfg) {
            cfg = Ext.apply({
                listeners: {
                    enable: enableSpy,
                    disable: disableSpy
                }
            }, cfg);
            c = new C(cfg);
        }

        describe("configuration", function() {
            describe("default", function() {
                it("should be enabled", function() {
                    makeDisableComp();
                    expect(c.isDisabled()).toBe(false);
                    expect(c.disabled).toBe(false);
                });

                describe("events/template methods", function() {
                    it("should not fire the enable event", function() {
                        makeDisableComp();
                        expect(enableSpy).not.toHaveBeenCalled();
                    });

                    it("should not call onEnable before or after rendered", function() {
                        makeDisableComp();
                        expect(c.onEnableCount).toBe(0);
                        c.render(Ext.getBody());
                        expect(c.onEnableCount).toBe(0);
                    });
                });

                describe("disabledCls", function() {
                    it("should not have the disabledCls after render", function() {
                        makeDisableComp({
                            renderTo: Ext.getBody()
                        });
                        expect(c.getEl().hasCls(c.disabledCls)).toBe(false);
                    });
                });
            });

            describe("disabled: true", function() {
                it("should be disabled", function() {
                    makeDisableComp({
                        disabled: true
                    });
                    expect(c.isDisabled()).toBe(true);
                    expect(c.disabled).toBe(true);
                });

                describe("events/template methods", function() {
                    it("should not fire the disable event", function() {
                        makeDisableComp({
                            disabled: true
                        });
                        expect(disableSpy).not.toHaveBeenCalled();
                    });

                    it("should call onDisable when rendered", function() {
                        makeDisableComp({
                            disabled: true
                        });
                        expect(c.onDisableCount).toBe(0);
                        c.render(Ext.getBody());
                        expect(c.onDisableCount).toBe(1);
                    });
                });

                describe("disabledCls", function() {
                    it("should have the disabledCls after render", function() {
                        makeDisableComp({
                            renderTo: Ext.getBody(),
                            disabled: true
                        });
                        expect(c.getEl().hasCls(c.disabledCls)).toBe(true);
                    });
                });
            });
        });

        describe("methods", function() {
            describe("enable", function() {
                describe("before render", function() {
                    describe("return type", function() {
                        describe("when disabled", function() {
                            it("should return the component", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                expect(c.enable()).toBe(c);
                            });
                        });

                        describe("when enabled", function() {
                            it("should return the component", function() {
                                makeDisableComp();
                                expect(c.enable()).toBe(c);
                            });
                        });
                    });

                    describe("events/template methods", function() {
                        describe("when disabled", function() {
                            it("should fire the enable event", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.enable();
                                expect(enableSpy.callCount).toBe(1);
                                expect(enableSpy.mostRecentCall.args[0]).toBe(c);
                            });

                            it("should not fire the enable event when passing silent: true", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.enable(true);
                                expect(enableSpy).not.toHaveBeenCalled();
                            });

                            it("should not call onEnable", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.enable();
                                expect(c.onEnableCount).toBe(0);
                            });

                            it("should not call onEnable after rendering", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.enable();
                                c.render(Ext.getBody());
                                expect(c.onEnableCount).toBe(0);
                            });
                        });

                        describe("when enabled", function() {
                            it("should not fire the enable event", function() {
                                makeDisableComp();
                                c.enable();
                                expect(enableSpy).not.toHaveBeenCalled();
                            });

                            it("should not call onEnable", function() {
                                makeDisableComp();
                                c.enable();
                                expect(c.onEnableCount).toBe(0);
                            });

                            it("should not call onEnable after rendering", function() {
                                makeDisableComp();
                                c.enable();
                                c.render(Ext.getBody());
                                expect(c.onEnableCount).toBe(0);
                            });
                        });
                    });

                    describe("disabledCls", function() {
                        it("should not have the disabledCls after render", function() {
                            makeDisableComp({
                                disabled: true
                            });
                            c.enable();
                            c.render(Ext.getBody());
                            expect(c.getEl().hasCls(c.disabledCls)).toBe(false);
                        });
                    });
                });

                describe("after render", function() {
                    describe("return type", function() {
                        describe("when disabled", function() {
                            it("should return the component", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody(),
                                    disabled: true
                                });
                                expect(c.enable()).toBe(c);
                            });
                        });

                        describe("when enabled", function() {
                            it("should return the component", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody()
                                });
                                expect(c.enable()).toBe(c);
                            });
                        });
                    });

                    describe("events/template methods", function() {
                        describe("when disabled", function() {
                            it("should fire the enable event", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody(),
                                    disabled: true
                                });
                                c.enable();
                                expect(enableSpy.callCount).toBe(1);
                                expect(enableSpy.mostRecentCall.args[0]).toBe(c);
                            });

                            it("should not fire the enable event when passing silent: true", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody(),
                                    disabled: true
                                });
                                c.enable(true);
                                expect(enableSpy).not.toHaveBeenCalled();
                            });

                            it("should call onEnable", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody(),
                                    disabled: true
                                });
                                expect(c.onEnableCount).toBe(0);
                                c.enable();
                                expect(c.onEnableCount).toBe(1);
                            });
                        });

                        describe("when enabled", function() {
                            it("should not fire the enable event", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody()
                                });
                                c.enable();
                                expect(enableSpy).not.toHaveBeenCalled();
                            });

                            it("should not call onEnable", function() {
                                makeDisableComp();
                                c.enable();
                                expect(c.onEnableCount).toBe(0);
                            });
                        });
                    });

                    describe("disabledCls", function() {
                        it("should not have the disabledCls", function() {
                            makeDisableComp({
                                renderTo: Ext.getBody(),
                                disabled: true
                            });
                            c.enable();
                            expect(c.getEl().hasCls(c.disabledCls)).toBe(false);
                        });
                    });
                });
            });

            describe("disable", function() {
                describe("before render", function() {
                    describe("return type", function() {
                        describe("when enabled", function() {
                            it("should return the component", function() {
                                makeDisableComp();
                                expect(c.disable()).toBe(c);
                            });
                        });

                        describe("when disabled", function() {
                            it("should return the component", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                expect(c.disable()).toBe(c);
                            });
                        });
                    });

                    describe("events/template methods", function() {
                        describe("when enabled", function() {
                            it("should fire the disable event", function() {
                                makeDisableComp();
                                c.disable();
                                expect(disableSpy.callCount).toBe(1);
                                expect(disableSpy.mostRecentCall.args[0]).toBe(c);
                            });

                            it("should not fire the disable event when passing silent: true", function() {
                                makeDisableComp();
                                c.disable(true);
                                expect(disableSpy).not.toHaveBeenCalled();
                            });

                            it("should not call onDisable", function() {
                                makeDisableComp();
                                c.disable();
                                expect(c.onDisableCount).toBe(0);
                            });

                            it("should call onDisable after rendering", function() {
                                makeDisableComp();
                                c.disable();
                                expect(c.onDisableCount).toBe(0);
                                c.render(Ext.getBody());
                                expect(c.onDisableCount).toBe(1);
                            });
                        });

                        describe("when disabled", function() {
                            it("should not fire the enable event", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.disable();
                                expect(disableSpy).not.toHaveBeenCalled();
                            });

                            it("should not call onDisable", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.disable();
                                expect(c.onDisableCount).toBe(0);
                            });

                            it("should call onDisable after rendering", function() {
                                makeDisableComp({
                                    disabled: true
                                });
                                c.disable();
                                expect(c.onDisableCount).toBe(0);
                                c.render(Ext.getBody());
                                expect(c.onDisableCount).toBe(1);
                            });
                        });
                    });

                    describe("disabledCls", function() {
                        it("should have the disabledCls after render", function() {
                            makeDisableComp();
                            c.disable();
                            c.render(Ext.getBody());
                            expect(c.getEl().hasCls(c.disabledCls)).toBe(true);
                        });
                    });
                });

                describe("after render", function() {
                    describe("return type", function() {
                        describe("when enabled", function() {
                            it("should return the component", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody()
                                });
                                expect(c.disable()).toBe(c);
                            });
                        });

                        describe("when disabled", function() {
                            it("should return the component", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody(),
                                    disabled: true
                                });
                                expect(c.disable()).toBe(c);
                            });
                        });
                    });

                    describe("events/template methods", function() {
                        describe("when disabled", function() {
                            it("should not fire the disable event", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody(),
                                    disabled: true
                                });
                                c.disable();
                                expect(disableSpy).not.toHaveBeenCalled();
                            });

                            it("should not call onDisable", function() {
                                makeDisableComp();
                                c.disable();
                                expect(c.onDisableCount).toBe(0);
                            });
                        });

                        describe("when enabled", function() {
                            it("should fire the disable event", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody()
                                });
                                c.disable();
                                expect(disableSpy.callCount).toBe(1);
                                expect(disableSpy.mostRecentCall.args[0]).toBe(c);
                            });

                            it("should not fire the disable event when passing silent: true", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody()
                                });
                                c.disable(true);
                                expect(disableSpy).not.toHaveBeenCalled();
                            });

                            it("should call onDisable", function() {
                                makeDisableComp({
                                    renderTo: Ext.getBody()
                                });
                                expect(c.onDisableCount).toBe(0);
                                c.disable();
                                expect(c.onDisableCount).toBe(1);
                            });
                        });
                    });

                    describe("disabledCls", function() {
                        it("should have the disabledCls", function() {
                            makeDisableComp({
                                renderTo: Ext.getBody()
                            });
                            c.disable();
                            expect(c.getEl().hasCls(c.disabledCls)).toBe(true);
                        });
                    });
                });
            });

            describe("setDisabled", function() {
                describe("before render", function() {
                    it("should call the disabled method when a truthy value is passed", function() {
                        makeDisableComp();
                        var spy = spyOn(c, 'disable');

                        c.setDisabled(true);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should call the enable method when a falsy value is passed", function() {
                        makeDisableComp();
                        var spy = spyOn(c, 'enable');

                        c.setDisabled(false);
                        expect(spy).toHaveBeenCalled();
                    });
                });

                describe("after render", function() {
                    it("should call the disabled method when a truthy value is passed", function() {
                        makeDisableComp({
                            renderTo: Ext.getBody()
                        });
                        var spy = spyOn(c, 'disable');

                        c.setDisabled(true);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should call the enable method when a falsy value is passed", function() {
                        makeDisableComp({
                            renderTo: Ext.getBody()
                        });
                        var spy = spyOn(c, 'enable');

                        c.setDisabled(false);
                        expect(spy).toHaveBeenCalled();
                    });
                });
            });
        });

        describe("masking", function() {
            describe("with maskOnDisable: false", function() {
                describe("configured", function() {
                    it("should not mask", function() {
                        makeDisableComp({
                            renderTo: Ext.getBody(),
                            disabled: true,
                            maskOnDisable: false
                        });
                        expect(c.maskCount).toBe(0);
                    });
                });

                describe("before render", function() {
                    it("should not mask when rendered", function() {
                        makeDisableComp({
                            maskOnDisable: false
                        });
                        c.disable();
                        c.render(Ext.getBody());
                        expect(c.maskCount).toBe(0);
                    });
                });

                describe("after render", function() {
                    it("should not mask when rendered", function() {
                        makeDisableComp({
                            renderTo: Ext.getBody(),
                            maskOnDisable: false
                        });
                        c.disable();
                        expect(c.maskCount).toBe(0);
                    });
                });
            });

            describe("with maskOnDisable: true", function() {
                describe("disable", function() {
                    describe("configured", function() {
                        it("should mask", function() {
                            makeDisableComp({
                                renderTo: Ext.getBody(),
                                disabled: true,
                                maskOnDisable: true
                            });
                            expect(c.maskCount).toBe(1);
                        });
                    });

                    describe("before render", function() {
                        it("should mask when rendered", function() {
                            makeDisableComp({
                                maskOnDisable: true
                            });
                            c.disable();
                            c.render(Ext.getBody());
                            expect(c.maskCount).toBe(1);
                        });
                    });

                    describe("after render", function() {
                        it("should mask", function() {
                            makeDisableComp({
                                renderTo: Ext.getBody(),
                                maskOnDisable: true
                            });
                            c.disable();
                            expect(c.maskCount).toBe(1);
                        });
                    });
                });

                describe("enable", function() {
                    describe("before render", function() {
                        it("should not mask when rendered", function() {
                            makeDisableComp({
                                maskOnDisable: true,
                                disabled: true
                            });
                            c.enable();
                            c.render(Ext.getBody());
                            expect(c.maskCount).toBe(0);
                        });
                    });

                    describe("after render", function() {
                        it("should clear the mask", function() {
                            makeDisableComp({
                                renderTo: Ext.getBody(),
                                maskOnDisable: true,
                                disabled: true
                            });
                            c.enable();
                            expect(c.unmaskCount).toBe(1);
                        });
                    });
                });
            });
        });

        (Ext.isIE ? xdescribe : describe)('disabling the dom element', function() {
            // Only disable whitelisted elements as determined by W3C.
            // http://www.w3.org/TR/html5/disabled-elements.html
            // See EXTJS-12705.
            var dom;

            afterEach(function() {
                dom = null;
            });

            Ext.each(['button', 'input', 'select', 'textarea', 'fieldset'], function(tagName) {
                it('should disable ' + tagName, function() {
                    makeComponent({
                        autoEl: {
                            tag: tagName
                        },
                        maskOnDisable: true,
                        renderTo: Ext.getBody()
                    });

                    dom = c.el.dom;
                    c.disable();

                    expect(dom.nodeName.toLowerCase()).toBe(tagName);
                    expect(dom.disabled).toBe(true);
                });
            });

            Ext.each(['div', 'table', 'p'], function(tagName) {
                it('should not disable ' + tagName, function() {
                    makeComponent({
                        autoEl: {
                            tag: tagName
                        },
                        maskOnDisable: true,
                        renderTo: Ext.getBody()
                    });

                    dom = c.el.dom;
                    c.disable();

                    expect(dom.nodeName.toLowerCase()).toBe(tagName);
                    expect(dom.disabled).toBeFalsy();
                });
            });
        });

        (Ext.isIE ? xdescribe : describe)('masking a disabled comp', function() {
            // See EXTJS-12705.
            Ext.each(['button', 'fieldset', 'div', 'p'], function(tagName) {
                it('should mask ' + tagName, function() {
                    makeComponent({
                        autoEl: {
                            tag: tagName
                        },
                        renderTo: Ext.getBody()
                    });

                    spyOn(c, 'mask');
                    c.disable();

                    expect(c.mask).toHaveBeenCalled();
                });
            });

            Ext.each(['input', 'select', 'textarea', 'option', 'optgroup', 'table'], function(tagName) {
                it('should not mask ' + tagName, function() {
                    makeComponent({
                        autoEl: {
                            tag: tagName
                        },
                        renderTo: Ext.getBody()
                    });

                    spyOn(c, 'mask');
                    c.disable();

                    expect(c.mask).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("component lookup by element", function() {
        describe("focusable components", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    autoEl: 'button',
                    childEls: [ 'divEl', 'spanEl' ],
                    focusable: true,
                    renderTpl: [
                        '<div id="{id}-divEl" data-ref="divEl">',
                            '<span id="{id}-spanEl" data-ref="spanEl">foo bar</span>',
                        '</div>'
                    ],
                    scrollable: false,
                    getFocusEl: function() {
                        return this.el;
                    }
                });
            });

            it("should add " + compIdAttr + " attribute to the focusable element", function() {
                var cmpId = c.getFocusEl().dom.getAttribute(compIdAttr);

                expect(cmpId).toBe(c.id);
            });

            it("should be able to look Component up by " + compIdAttr + " attribute", function() {
                var cmp = Ext.Component.from(c.getFocusEl());

                expect(cmp).toEqual(c);
            });

            it("should be able to look Component up by its inner element", function() {
                var cmp = Ext.Component.from(c.spanEl);

                expect(cmp).toEqual(c);
            });

            // We don't have a Viewport here, so lookup on the body element should fail
            it("should return null if no Component is found", function() {
                var cmp = Ext.Component.from(Ext.getBody());

                expect(cmp).toBe(null);
            });
        });

        describe("non-focusable components", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    childEls: [ 'divEl' ],
                    renderTpl: [
                        '<div id="{id}-divEl" data-ref="divEl">',
                            'foo bar',
                        '</div>'
                    ]
                });
            });

            it("should not add " + compIdAttr + " attribute to Component's elements", function() {
                var el = c.el.down('[' + compIdAttr + ']');

                expect(el).toEqual(null);
            });

            it("should be able to look Component up by its main element", function() {
                var cmp = Ext.Component.from(c.el);

                expect(cmp).toEqual(c);
            });

            it("should be able to look Component up by its inner elements", function() {
                var cmp = Ext.Component.from(c.divEl);

                expect(cmp).toEqual(c);
            });
        });
    });

    describe('focusenter/focusleave', function() {
        var inner1,
            inner11,
            textfield1,
            inner2,
            inner21,
            textfield2,
            cFocusEnterSpy,
            cFocusLeaveSpy,
            inner1FocusEnterSpy,
            inner11FocusEnterSpy,
            textfield1FocusEnterSpy,
            inner1FocusLeaveSpy,
            inner11FocusLeaveSpy,
            textfield1FocusLeaveSpy,
            inner2FocusEnterSpy,
            inner21FocusEnterSpy,
            textfield2FocusEnterSpy,
            inner2FocusLeaveSpy,
            inner21FocusLeaveSpy,
            textfield2FocusLeaveSpy;

        beforeEach(function() {
            c = new Ext.container.Container({
                renderTo: document.body,
                items: [{
                    itemId: 'inner-1',
                    xtype: 'container',
                    items: {
                        itemId: 'inner-11',
                        xtype: 'container',
                        items: {
                            itemId: 'textfield-1',
                            xtype: 'textfield'
                        }
                    }
                }, {
                    itemId: 'inner-2',
                    xtype: 'container',
                    items: {
                        itemId: 'inner-21',
                        xtype: 'container',
                        items: {
                            itemId: 'textfield-2',
                            xtype: 'textfield'
                        }
                    }
                }]
            });
            inner1 = c.child('#inner-1');
            inner11 = inner1.child('#inner-11');
            textfield1 = inner11.child('#textfield-1');
            inner2 = c.child('#inner-2');
            inner21 = inner2.child('#inner-21');
            textfield2 = inner21.child('#textfield-2');
            cFocusEnterSpy = spyOn(c, 'onFocusEnter').andCallThrough();
            cFocusLeaveSpy = spyOn(c, 'onFocusLeave').andCallThrough();
            inner1FocusEnterSpy = spyOn(inner1, 'onFocusEnter').andCallThrough();
            inner11FocusEnterSpy = spyOn(inner11, 'onFocusEnter').andCallThrough();
            textfield1FocusEnterSpy = spyOn(textfield1, 'onFocusEnter').andCallThrough();
            inner1FocusLeaveSpy = spyOn(inner1, 'onFocusLeave').andCallThrough();
            inner11FocusLeaveSpy = spyOn(inner11, 'onFocusLeave').andCallThrough();
            textfield1FocusLeaveSpy = spyOn(textfield1, 'onFocusLeave').andCallThrough();
            inner2FocusEnterSpy = spyOn(inner2, 'onFocusEnter').andCallThrough();
            inner21FocusEnterSpy = spyOn(inner21, 'onFocusEnter').andCallThrough();
            textfield2FocusEnterSpy = spyOn(textfield2, 'onFocusEnter').andCallThrough();
            inner2FocusLeaveSpy = spyOn(inner2, 'onFocusLeave').andCallThrough();
            inner21FocusLeaveSpy = spyOn(inner21, 'onFocusLeave').andCallThrough();
            textfield2FocusLeaveSpy = spyOn(textfield2, 'onFocusLeave').andCallThrough();
        });

        it('should fire focusEnter on the whole tree into which focus enters, and focusleave on the whole tree from which focus leaves', function() {
            expect(c.containsFocus).toBeFalsy();
            expect(inner1.containsFocus).toBeFalsy();
            expect(inner11.containsFocus).toBeFalsy();
            expect(textfield1.containsFocus).toBeFalsy();
            expect(inner2.containsFocus).toBeFalsy();
            expect(inner21.containsFocus).toBeFalsy();
            expect(textfield2.containsFocus).toBeFalsy();
            textfield1.focus();

            // Some browsers deliver the focus event asynchronously
            waitForSpy(cFocusEnterSpy);

            runs(function() {
                // Focus has entered "c" and inner1 (and all inner1's descendants)
                expect(cFocusEnterSpy.callCount).toBe(1);
                expect(inner1FocusEnterSpy.callCount).toBe(1);
                expect(inner11FocusEnterSpy.callCount).toBe(1);
                expect(textfield1FocusEnterSpy.callCount).toBe(1);

                // Focus has not entered inner2
                expect(inner2FocusEnterSpy.callCount).toBe(0);
                expect(inner21FocusEnterSpy.callCount).toBe(0);
                expect(textfield2FocusEnterSpy.callCount).toBe(0);

                // Check correct state of containsFocus flags
                expect(inner1.containsFocus).toBe(true);
                expect(inner11.containsFocus).toBe(true);
                expect(textfield1.containsFocus).toBe(true);
                expect(inner2.containsFocus).toBeFalsy();
                expect(inner21.containsFocus).toBeFalsy();
                expect(textfield2.containsFocus).toBeFalsy();
                textfield2.focus();
            });

            // Some browsers deliver the focus event asynchronously
            waitForSpy(inner2FocusEnterSpy);

            runs(function() {
                expect(cFocusEnterSpy.callCount).toBe(1);
                expect(inner1FocusEnterSpy.callCount).toBe(1);
                expect(inner11FocusEnterSpy.callCount).toBe(1);
                expect(textfield1FocusEnterSpy.callCount).toBe(1);

                // Focus has not left container "c". It's the root of both trees between which focus is moving.
                expect(cFocusLeaveSpy.callCount).toBe(0);

                // Focus has left inner1
                expect(inner1FocusLeaveSpy.callCount).toBe(1);
                expect(inner11FocusEnterSpy.callCount).toBe(1);
                expect(textfield1FocusLeaveSpy.callCount).toBe(1);

                // Focus has entered inner2
                expect(inner2FocusEnterSpy.callCount).toBe(1);
                expect(inner21FocusEnterSpy.callCount).toBe(1);
                expect(textfield2FocusEnterSpy.callCount).toBe(1);

                // Check correct state of containsFocus flags
                expect(c.containsFocus).toBe(true);
                expect(inner2.containsFocus).toBe(true);
                expect(inner21.containsFocus).toBe(true);
                expect(textfield2.containsFocus).toBe(true);
                expect(inner1.containsFocus).toBe(false);
                expect(inner11.containsFocus).toBe(false);
                expect(textfield1.containsFocus).toBe(false);
            });
        });
    });

    describe('onFocusEnter', function() {
        var grid;

        beforeEach(function() {
            var gridData = [],
                i = 0;

                 while (i < 14) {
                    gridData.push({
                        firstName: 'C',
                        lastName: 'C'
                    });
                    ++i;
                }

            Ext.define('FooView', {
                extend: 'Ext.window.Window',
                alias: 'widget.fooview',
                modal: true,
                closable: false,

                initComponent: function() {
                    var me = this;

                    Ext.apply(me, {
                        items: [{
                            xtype: 'form',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: 'Name',
                                width: 400,
                                labelWidth: 50
                            }]
                        }]
                    });

                    me.callParent();
                }
            });

            grid = Ext.create('Ext.grid.Panel', {
                renderTo: Ext.getBody(),
                height: 300,
                width: 500,

                store: Ext.create('Ext.data.Store', {
                    fields: [{
                        name: 'firstName',
                        type: 'string'
                    }, {
                        name: 'lastName',
                        type: 'string'
                    }],
                    data: gridData
                }),
                columns: {
                    items: [{
                        text: 'First Name',
                        dataIndex: 'firstName',
                        editor: {
                            xtype: 'textfield'
                        }
                    }, {
                        text: 'Last Name',
                        dataIndex: 'lastName'
                    }]
                }
            });
        });

        afterEach(function() {
            Ext.undefine('FooView');
            Ext.destroy(grid);
        });

        it('should focus on grid scrollbar after retaining focus from dialog', function() {
            // Show fooview dialog box
            var foo = Ext.widget('fooview'),
                errorSpy = jasmine.createSpy(),
                old = window.onerror;

            window.onerror = errorSpy.andCallFake(function() {
                if (old) {
                    old();
                }
            });

            foo.show();

            // Open another dialog box
            Ext.Msg.show({
                title: 'Duplicate Category Name',
                msg: 'The name of the category may not be a duplicate.',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });

            Ext.Msg.down('#ok').el.dom.click();

            // Close the dialog box
            foo.close();
            grid.getView().lastFocused = 'scrollbar';
            grid.getView().el.dom.focus();
            Ext.destroy(foo);
            // No errors must have been caught
            expect(errorSpy).not.toHaveBeenCalled();

        });

    });

    describe("visibility", function() {
        // TODO: need to change show/hide to be testable
    });

    describe("rendering", function() {

        it("should set the rendered property  when it's rendered", function() {
            makeComponent();
            expect(c.rendered).toBeFalsy();
            c.render(Ext.getBody());
            expect(c.rendered).toBeTruthy();
        });

        describe("cancelling render", function() {
            it("should not create an element if we veto beforerender and do not provide an el", function() {
                makeComponent({
                    id: 'testComp'
                });
                c.on('beforerender', function() {
                    return false;
                });
                c.render(Ext.getBody());
                expect(Ext.get('testComp')).toBeNull();
            });

            it("should not move the element if we veto beforerender and we do provide an el", function() {
                var a = Ext.getBody().createChild({
                    id: 'a'
                });

                var b = Ext.getBody().createChild({
                    id: 'b'
                });

                makeComponent({
                    el: a
                });
                c.on('beforerender', function() {
                    return false;
                });
                c.render(b);
                expect(a.parent()).not.toBe(b);
                expect(a.parent().dom).toBe(Ext.getBody().dom);
                a.remove();
                b.remove();
            });
        });

        describe("renderTpl", function() {
            it("should not use any renderTpl by default", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                expect(c.el.dom.firstChild).toBeNull();
            });

            it("should take a renderTpl", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    renderTpl: '<div><span>a</span></div>'
                });

                expect(c.el.dom).hasHTML('<div><span>a</span></div>');
            });
        });

        describe("rendering to the dom", function() {
            it("should use the renderTo option", function() {
                var el = Ext.getBody().createChild();

                makeComponent({
                    renderTo: el
                });
                expect(el.dom.getElementsByTagName('div')[0]).toEqual(c.el.dom);
                el.remove();
            });

            it("should not render if not explicitly told to", function() {
                var total = document.body.getElementsByTagName('div').length;

                makeComponent();
                expect(document.body.getElementsByTagName('div').length).toEqual(total);
            });

            it("should render to a specific element", function() {
                var el = Ext.getBody().createChild();

                makeComponent();
                c.render(el);
                expect(el.dom.getElementsByTagName('div')[0]).toEqual(c.el.dom);
                el.remove();
            });
        });

        describe("content", function() {

            describe("initialization", function() {
                it("should accept an html string", function() {
                    makeComponent({
                        html: 'foo',
                        renderTo: Ext.getBody()
                    });
                    expect(c.el.dom).hasHTML('foo');
                });

                it("should accept a markup config for html", function() {
                    makeComponent({
                        html: {
                            tag: 'span',
                            html: 'foo'
                        },
                        renderTo: document.body
                    });

                    expect(c.el.dom).hasHTML('<span>foo</span>');

                });

                it("should accept a contentEl", function() {
                    var div = Ext.getBody().createChild({
                        tag: 'div',
                        html: 'foo'
                    });

                    makeComponent({
                        contentEl: div,
                        renderTo: document.body
                    });
                    expect(c.el.dom.firstChild).hasHTML('foo');
                });

                describe("tpl", function() {

                    it("should accept a raw template", function() {
                        makeComponent({
                            renderTo: Ext.getBody(),
                            tpl: '{first} - {last}',
                            data: {
                                first: 'John',
                                last: 'Foo'
                            }
                        });
                        expect(c.el.dom).hasHTML('John - Foo');
                    });

                    it("should take a template instance", function() {
                        makeComponent({
                            tpl: new Ext.XTemplate('{0} - {1}'),
                            data: [3, 7],
                            renderTo: Ext.getBody()
                        });
                        expect(c.el.dom).hasHTML('3 - 7');
                    });

                });
            });

            describe("before render", function() {
                it("should be able to change the html before render", function() {
                    makeComponent();
                    c.update('foo');
                    c.render(Ext.getBody());
                    expect(c.el.dom).hasHTML('foo');
                });

                it("should be able to update the markup when not rendered", function() {
                    makeComponent();
                    c.update({
                        tag: 'span',
                        html: 'bar'
                    });
                    c.render(Ext.getBody());

                    expect(c.el.dom).hasHTML('<span>bar</span>');
                });

                it("should be able to change the data when not rendered", function() {
                    makeComponent({
                        tpl: '{a} - {b}'
                    });
                    c.update({
                        a: 'foo',
                        b: 'bar'
                    });
                    c.render(Ext.getBody());
                    expect(c.el.dom).hasHTML('foo - bar');
                });
            });

            describe("after render", function() {
                it("should change the html after being rendered", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        html: 'foo'
                    });
                    expect(c.el.dom).hasHTML('foo');
                    c.update('bar');
                    expect(c.el.dom).hasHTML('bar');
                });

                it("should change markup if an html config is provided", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        html: {
                            tag: 'span',
                            html: '1'
                        }
                    });

                    expect(c.el.dom).hasHTML('<span>1</span>');
                    c.update({
                        tag: 'div',
                        html: '2'
                    });
                    expect(c.el.dom).hasHTML('<div>2</div>');
                });

                it("should update tpl data", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        tpl: '{a} - {b}',
                        data: {
                            a: 'v1',
                            b: 'v2'
                        }
                    });
                    expect(c.el.dom).hasHTML('v1 - v2');
                    c.update({
                        a: 'v3',
                        b: 'v4'
                    });
                    expect(c.el.dom).hasHTML('v3 - v4');
                });

                it("should use the correct writeMode", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        tpl: '{a} - {b}',
                        tplWriteMode: 'append',
                        data: {
                            a: 'v1',
                            b: 'v2'
                        }
                    });
                    expect(c.el.dom).hasHTML('v1 - v2');
                    c.update({
                        a: 'v3',
                        b: 'v4'
                    });
                    expect(c.el.dom).hasHTML('v1 - v2v3 - v4');
                });
            });
        });

        describe('afterrender event', function() {
            var mock, fireEventSpy;

            beforeEach(function() {
                mock = { handler: function() {} };
                fireEventSpy = spyOn(mock, 'handler');
            });

            it('should fire "afterrender" after render', function() {
                expect(fireEventSpy.callCount).toEqual(0);

                makeComponent({
                    listeners: { afterrender: mock.handler },
                    renderTo: Ext.getBody()
                });

                expect(fireEventSpy.callCount).toEqual(1);
            });

        });

    });

    describe("addCls/removeCls", function() {
        it("should be able to add class when not rendered", function() {
            makeComponent();
            c.addCls('foo');
            c.render(Ext.getBody());
            expect(c.el.hasCls('foo')).toBe(true);
        });

        it("should add the class if the item is rendered", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            c.addCls('foo');
            expect(c.el.hasCls('foo')).toBe(true);
        });

        it("should be able to remove class when not rendered", function() {
            makeComponent({
                additionalCls: ['foo']
            });
            c.removeCls('foo');
            c.render(Ext.getBody());
            expect(c.el.hasCls('foo')).toBe(false);
        });

        it("should remove the class if the item is rendered", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                additionalCls: ['foo']
            });
            c.removeCls('foo');
            expect(c.el.hasCls('foo')).toBe(false);
        });
    });

    describe("styling", function() {
        it("should apply the cls to the element", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                cls: 'foo'
            });
            expect(c.el.hasCls('foo')).toBe(true);
        });

        it("should add the baseCls to the element", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            expect(c.el.hasCls(c.baseCls)).toBe(true);
        });

        describe("style", function() {
            it("should accept a style string", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    style: 'background-color: red;'
                });
                expect(c.el.dom.style.backgroundColor).toMatch('^(red|#ff0000)$');
            });

            it("should accept a style config", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    style: {
                        color: 'red'
                    }
                });
                expect(c.el.dom.style.color).toMatch('^(red|#ff0000)$');
            });
        });

        describe("padding", function() {
            it("should accept a single number", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    padding: 5
                });
                var style = c.el.dom.style;

                expect(style.paddingTop).toEqual('5px');
                expect(style.paddingRight).toEqual('5px');
                expect(style.paddingBottom).toEqual('5px');
                expect(style.paddingLeft).toEqual('5px');
            });

            it("should accept a css style string", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    padding: '1 2 3 4'
                });
                var style = c.el.dom.style;

                expect(style.paddingTop).toEqual('1px');
                expect(style.paddingRight).toEqual('2px');
                expect(style.paddingBottom).toEqual('3px');
                expect(style.paddingLeft).toEqual('4px');
            });
        });

        describe("margin", function() {
            it("should accept a single number", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    margin: 1
                });
                var style = c.el.dom.style;

                expect(style.marginTop).toEqual('1px');
                expect(style.marginRight).toEqual('1px');
                expect(style.marginBottom).toEqual('1px');
                expect(style.marginLeft).toEqual('1px');
            });

            it("should accept a css style string", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    margin: '4 5 6 7'
                });
                var style = c.el.dom.style;

                expect(style.marginTop).toEqual('4px');
                expect(style.marginRight).toEqual('5px');
                expect(style.marginBottom).toEqual('6px');
                expect(style.marginLeft).toEqual('7px');
            });
        });
    });

    describe("plugins", function() {
        var Plugin;

        beforeEach(function() {
            Plugin = Ext.define('MyPlugin', {
                alias: 'plugin.myplug',

                constructor: function(cfg) {
                    this.marked = (cfg || {}).marked;
                },

                init: function(c) {
                    c.marked = this.marked || true;
                }
            });
        });

        afterEach(function() {
            Ext.undefine('MyPlugin');
        });

        it("should accept a single plugin", function() {
            var p = new Plugin();

            spyOn(p, 'init');
            makeComponent({
                plugins: p
            });
            expect(p.init).toHaveBeenCalledWith(c);
        });

        it("should accept an array of plugins", function() {
            var p1 = new Plugin(),
                p2 = new Plugin(),
                p3 = new Plugin();

            spyOn(p1, 'init');
            spyOn(p2, 'init');
            spyOn(p3, 'init');

            makeComponent({
                plugins: [p1, p2, p3]
            });
            expect(p1.init).toHaveBeenCalledWith(c);
            expect(p2.init).toHaveBeenCalledWith(c);
            expect(p3.init).toHaveBeenCalledWith(c);
        });

        it("should be able to create string plugins", function() {
            makeComponent({
                plugins: 'myplug'
            });
            expect(c.marked).toBeTruthy();
        });

        it("should be able to create config object plugins", function() {
            makeComponent({
                plugins: {
                    ptype: 'myplug',
                    marked: 'foo'
                }
            });
            expect(c.marked).toBe('foo');
        });

        describe("adding dynamically", function() {
            it("should be able to add if there are no existing plugins", function() {
                var p1 = new Plugin(),
                    p2 = new Plugin();

                spyOn(p2, 'init');

                makeComponent({
                    plugins: [p1]
                });
                c.addPlugin(p2);
                expect(p2.init).toHaveBeenCalled();
            });

            it("should be able to add if there are existing plugins", function() {
                var p = new Plugin();

                spyOn(p, 'init');

                makeComponent();
                c.addPlugin(p);
                expect(p.init).toHaveBeenCalled();
            });
        });

        // https://sencha.jira.com/browse/EXTJSIV-8568
        it("should not break on getPlugin when there are no plugins", function() {
            makeComponent();

            var p = c.getPlugin('foo');

            expect(p).toBe(null);
        });

        it("should be able to add a plugin");
    });

    describe("previousSibling", function() {
        var ct;

        beforeEach(function() {
            makeComponent();
            ct = new Ext.container.Container();
        });

        afterEach(function() {
            ct.destroy();
        });
        it("should return null if it is not in a container", function() {
            expect(c.previousSibling()).toBeNull();
        });

        it("should return null if it is the only item in the container", function() {
            ct.add(c);
            expect(c.previousSibling()).toBeNull();
        });

        it("should return null if it is the first item in the container", function() {
            ct.add([c, {}, {}, {}, {}]);
            expect(c.previousSibling()).toBeNull();
        });

        it("should return the closest previous sibling", function() {
            var other = new Ext.Component();

            ct.add([{}, {}, {}, other, c, {}]);
            expect(c.previousSibling()).toBe(other);
        });

        it("should return null if no previous items match the selector", function() {
            ct.add([{}, {}, {}, {}, c]);
            expect(c.previousSibling('*[aProp=1]')).toBeNull();
        });

        it("should return an item matching the selector", function() {
            var other = new Ext.Component({
                aProp: 1
            });

            ct.add([{}, other, {}, {}, {}, c]);
            expect(c.previousSibling('*[aProp=1]')).toBe(other);
        });

        it("should return the first item matching the selector", function() {
            var other = new Ext.Component({
                aProp: 1
            });

            ct.add([{}, { aProp: 1 }, other, {}, {}, c]);
            expect(c.previousSibling('*[aProp=1]')).toBe(other);
        });
    });

    describe("previousNode", function() {
        var ct, mainCt;

        beforeEach(function() {
            makeComponent();
            ct = new Ext.container.Container();
            mainCt = new Ext.container.Container();
        });

        afterEach(function() {
            ct.destroy();
            mainCt.destroy();
            ct = mainCt = null;
        });

        describe("without selectors", function() {

            it("should return null if it is not in a container", function() {
                expect(c.previousNode()).toBeNull();
            });

            it("should return the owner container if there are no siblings", function() {
                ct.add(c);
                expect(c.previousNode()).toBe(ct);
            });

            it("should return the previous sibling if it exists", function() {
                var prev = new Ext.Component();

                ct.add(prev, c);
                expect(c.previousNode()).toBe(prev);
            });

            it("should be able to select itself if includeSelf is passed", function() {
                ct.add(c);
                expect(c.previousNode(null, true)).toBe(c);
            });
        });

        describe("with selectors", function() {

            describe("flat", function() {

                it("should return null if no component matches the selector", function() {
                    ct.add(c);
                    expect(c.previousNode('foo')).toBeNull();
                });

                it("should return the previous sibling if it matches the selector", function() {
                    var prev = new Ext.Component({
                        type: 'foo'
                    });

                    ct.add(prev, c);
                    expect(c.previousNode('[type=foo]')).toBe(prev);
                });

                it("should return any previous sibling that matches the selector", function() {
                    var prev = new Ext.Component({
                        type: 'foo'
                    });

                    ct.add(prev, {}, {}, {}, c);
                    expect(c.previousNode('[type=foo]')).toBe(prev);
                });

                it("should return the closest previous sibling that matches the selector", function() {
                    var prev = new Ext.Component({
                        type: 'foo'
                    });

                    ct.add({}, {
                        type: 'foo'
                    }, prev, {}, c);
                    expect(c.previousNode('[type=foo]')).toBe(prev);
                });

                it("should return the container if the container matches the selector and no siblings do", function() {
                    ct.add({}, {}, {}, c);
                    ct.type = 'foo';
                    expect(c.previousNode('[type=foo]')).toBe(ct);
                });
            });

            describe("nested", function() {

                it("should give precedence to children", function() {
                    var prev = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add(prev);
                    ct.add({
                        xtype: 'component',
                        type: 'foo'
                    }, mainCt, c);
                    expect(c.previousNode('[type=foo]')).toBe(prev);
                });

                it("should match the deepest, last child", function() {
                    var prev = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add({
                        xtype: 'component'
                    }, {
                        xtype: 'component',
                        type: 'foo'
                    }, {
                        xtype: 'component'
                    }, {
                        xtype: 'container',
                        items: [{
                            xtype: 'component'
                        }, {
                            xtype: 'container',
                            items: [{
                                xtype: 'component'
                            }, prev, {
                                xtype: 'component'
                            }]
                        }]
                    });
                    ct.add(mainCt, c);
                    expect(c.previousNode('[type=foo]')).toBe(prev);
                });

                it("should match any sibling if children don't match", function() {
                    var prev = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add({
                        xtype: 'component'
                    }, {
                        xtype: 'component'
                    }, {
                        xtype: 'component'
                    }, {
                        xtype: 'container',
                        items: [{
                            xtype: 'component'
                        }, {
                            xtype: 'container',
                            items: [{
                                xtype: 'component'
                            }, {
                                xtype: 'component'
                            }, {
                                xtype: 'component'
                            }]
                        }]
                    });
                    ct.add(prev, mainCt, c);
                    expect(c.previousNode('[type=foo]')).toBe(prev);
                });
            });

        });
    });

    describe("findParentBy", function() {
        var ct;

        describe("findParentByType", function() {
            beforeEach(function() {
                ct = new Ext.toolbar.Toolbar({
                    renderTo: document.body
                });
                makeComponent({
                    renderTo: null
                });
                ct.add(c);
            });

            afterEach(function() {
                ct.destroy();
            });

            it("should find by xtype", function() {
                expect(c.findParentByType('toolbar')).toBe(ct);
            });

            it("should find by class", function() {
                expect(c.findParentByType(Ext.toolbar.Toolbar)).toBe(ct);
            });
        });
    });

    describe("nextSibling", function() {
        var ct;

        beforeEach(function() {
            makeComponent();
            ct = new Ext.container.Container();
        });

        afterEach(function() {
            ct.destroy();
        });
        it("should return null if it is not in a container", function() {
            expect(c.nextSibling()).toBeNull();
        });

        it("should return null if it is the only item in the container", function() {
            ct.add(c);
            expect(c.nextSibling()).toBeNull();
        });

        it("should return null if it is the last item in the container", function() {
            ct.add([{}, {}, {}, {}, c]);
            expect(c.nextSibling()).toBeNull();
        });

        it("should return the closest next sibling", function() {
            var other = new Ext.Component();

            ct.add([{}, {}, {}, c, other, {}]);
            expect(c.nextSibling()).toBe(other);
        });

        it("should return null if no next items match the selector", function() {
            ct.add([c, {}, {}, {}, {}]);
            expect(c.nextSibling('*[aProp=1]')).toBeNull();
        });

        it("should return an item matching the selector", function() {
            var other = new Ext.Component({
                aProp: 1
            });

            ct.add([c, {}, {}, other, {}]);
            expect(c.nextSibling('*[aProp=1]')).toBe(other);
        });

        it("should return the first item matching the selector", function() {
            var other = new Ext.Component({
                aProp: 1
            });

            ct.add([c, {}, other, { aProp: 1 }, {}, {}]);
            expect(c.nextSibling('*[aProp=1]')).toBe(other);
        });
    });

    describe("nextNode", function() {
        var ct, mainCt;

        beforeEach(function() {
            makeComponent();
            ct = new Ext.container.Container();
            mainCt = new Ext.container.Container();
        });

        afterEach(function() {
            ct.destroy();
            mainCt.destroy();
            ct = mainCt = null;
        });

        describe("without selectors", function() {

            it("should return null if it is not in a container", function() {
                expect(c.nextNode()).toBeNull();
            });

            it("should return the nextNode of the owner container if there are no siblings", function() {
                var next = new Ext.Component();

                mainCt.add(c);
                ct.add(mainCt, next);
                expect(c.nextNode()).toBe(next);
            });

            it("should return the next sibling if it exists", function() {
                var next = new Ext.Component();

                ct.add(c, next);
                expect(c.nextNode()).toBe(next);
            });

            it("should be able to select itself if includeSelf is passed", function() {
                ct.add(c);
                expect(c.nextNode(null, true)).toBe(c);
            });
        });

        describe("with selectors", function() {

            describe("flat", function() {

                it("should return null if no component matches the selector", function() {
                    ct.add(c);
                    expect(c.nextNode('foo')).toBeNull();
                });

                it("should return the next sibling if it matches the selector", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    ct.add(c, next);
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });

                it("should return any next sibling that matches the selector", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    ct.add(c, {}, {}, {}, next);
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });

                it("should return the closest next sibling that matches the selector", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    ct.add(c, {}, next, {}, {
                        type: 'foo'
                    }, {});
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });

                it("should return the owner container nextNode if the nextNode matches the selector and no siblings do", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add(c, {}, {}, {});
                    ct.add(mainCt, next);
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });
            });

            describe("nested", function() {

                it("should give precedence to children", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add(next);
                    ct.add(c, mainCt, {
                        xtype: 'component',
                        type: 'foo'
                    });
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });

                it("should match the least deep, first child", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add({
                        xtype: 'component'
                    }, next, {
                        xtype: 'component'
                    }, {
                        xtype: 'container',
                        items: [{
                            xtype: 'component'
                        }, {
                            xtype: 'container',
                            items: [{
                                xtype: 'component'
                            }, {
                                xtype: 'component',
                                type: 'foo'
                            }, {
                                xtype: 'component'
                            }]
                        }]
                    });
                    ct.add(c, mainCt);
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });

                it("should match any sibling if children don't match", function() {
                    var next = new Ext.Component({
                        type: 'foo'
                    });

                    mainCt.add({
                        xtype: 'component'
                    }, {
                        xtype: 'component'
                    }, {
                        xtype: 'component'
                    }, {
                        xtype: 'container',
                        items: [{
                            xtype: 'component'
                        }, {
                            xtype: 'container',
                            items: [{
                                xtype: 'component'
                            }, {
                                xtype: 'component'
                            }, {
                                xtype: 'component'
                            }]
                        }]
                    });
                    ct.add(c, mainCt, next);
                    expect(c.nextNode('[type=foo]')).toBe(next);
                });
            });

        });
    });

    describe("rendering cycle", function() {
        var makeContainer,
            ct;

        beforeEach(function() {
            makeContainer = function(event, fn1, fn2) {

                var l1 = {},
                    l2 = {};

                l1[event + 'render'] = fn1;
                l2[event + 'render'] = fn2;

                ct = new Ext.container.Container({
                    defaultType: 'component',
                    items: [{
                        id: 'a',
                        listeners: l1
                    }, {
                        id: 'b',
                        cls: 'clsB'
                    }, {
                        id: 'c',
                        cls: 'clsC'
                    }, {
                        id: 'd',
                        listeners: l2
                    }]
                });
            };
        });

        afterEach(function() {
            makeContainer = null;
            Ext.destroy(ct);
            ct = null;
        });

        it("should be able to add a class in beforerender using the API", function() {
            makeContainer('before', function(c) {
                c.next().addCls('foo');
            }, function(c) {
                c.prev().addCls('bar');
            });
            ct.render(Ext.getBody());

            expect(Ext.getCmp('b').el.hasCls('foo')).toBe(true);
            expect(Ext.getCmp('c').el.hasCls('bar')).toBe(true);
        });

        it("should be able to add a class in beforerender using this.cls", function() {
            makeContainer('before', function(c) {
                c.next().cls += ' foo';
            }, function(c) {
            });
            ct.render(Ext.getBody());

            expect(Ext.getCmp('b').el.hasCls('foo')).toBe(true);
        });

        it("should be able to check if a class exists in beforerender", function() {
            var hasB,
                hasC;

            makeContainer('before', function(c) {
                hasB = c.next().hasCls('clsB');
            }, function(c) {
                hasC = c.prev().hasCls('clsC');
            });
            ct.render(Ext.getBody());

            expect(hasB).toBe(true);
            expect(hasC).toBe(true);
        });

        it("should be able to remove a class in beforerender", function() {
            makeContainer('before', function(c) {
                c.next().removeCls('clsB');
            }, function(c) {
                c.prev().removeCls('clsC');
            });
            ct.render(Ext.getBody());

            expect(Ext.getCmp('b').el.hasCls('clsB')).toBe(false);
            expect(Ext.getCmp('c').el.hasCls('clsC')).toBe(false);
        });

        it("should be able to add a class in aftererender", function() {
            makeContainer('after', function(c) {
                c.next().addCls('foo');
            }, function(c) {
                c.prev().addCls('bar');
            });
            ct.render(Ext.getBody());

            expect(Ext.getCmp('b').el.hasCls('foo')).toBe(true);
            expect(Ext.getCmp('c').el.hasCls('bar')).toBe(true);
        });

        it("should be able to check if a class exists in afterrender", function() {
            var hasB,
                hasC;

            makeContainer('before', function(c) {
                hasB = c.next().hasCls('clsB');
            }, function(c) {
                hasC = c.prev().hasCls('clsC');
            });
            ct.render(Ext.getBody());

            expect(hasB).toBe(true);
            expect(hasC).toBe(true);
        });

        it("should be able to remove a class in afterrender", function() {
            makeContainer('after', function(c) {
                c.next().removeCls('clsB');
            }, function(c) {
                c.prev().removeCls('clsC');
            });
            ct.render(Ext.getBody());

            expect(Ext.getCmp('b').el.hasCls('clsB')).toBe(false);
            expect(Ext.getCmp('c').el.hasCls('clsC')).toBe(false);
        });
    });

    describe("destruction", function() {
        it("should be destroyed if not rendered", function() {
            makeComponent();
            expect(c.destroyed).toBe(false);

            c.destroy();
            expect(c.destroyed).toBe(true);
        });

        it("should be destroyed if rendered", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });

            expect(c.destroyed).toBe(false);
            expect(Ext.get(c.id).dom.id).toBe(c.id);

            c.destroy();

            // component and el should be cleaned up
            expect(c.destroyed).toBe(true);
            expect(Ext.get(c.id)).toBe(null);
        });

        it("should be destroyed and child els removed if childEls defined", function() {
            // Button is a convenient component to use since it already has childEls defined
            c = Ext.createWidget('button', {
                renderTo: Ext.getBody()
            });

            expect(c.destroyed).toBe(false);
            expect(Ext.get(c.id).dom.id).toBe(c.id);

            var childId = c.btnEl.id;

            expect(Ext.get(childId).dom.id).toBe(childId);

            c.destroy();

            // component and child refs should be cleaned up
            expect(c.destroyed).toBe(true);
            expect(c.btnEl).toBe(null);

            // component and child els should be gone
            expect(Ext.get(c.id)).toBeNull();
            expect(Ext.get(childId)).toBeNull();
        });

        it("should be destroyed and child els removed if renderSelectors defined", function() {
            // Button does not have renderSelectors (since they were converted to childEls)
            // but it's an easy component to add selectors for to verify this case.
            c = Ext.createWidget('button', {
                renderTo: Ext.getBody(),
                renderSelectors: {
                    btnSelector: '.x-btn-button',
                    btnSelector2: '.x-btn-button'
                }
            });

            expect(c.destroyed).toBe(false);
            expect(Ext.get(c.id).dom.id).toBe(c.id);

            var childId = c.btnSelector.id;

            expect(Ext.get(childId).dom.id).toBe(childId);

            c.destroy();

            // component and child refs should be cleaned up
            expect(c.destroyed).toBe(true);
            expect(c.btnSelector).not.toBeDefined();

            // component and child els should be gone
            expect(Ext.get(c.id)).toBeNull();
            expect(Ext.get(childId)).toBeNull();
        });

        it("should be destroyed when a childEl and a renderSelector have duplicate names", function() {
            // This should not normally happen, but is possible, especially when subclassing.
            // This test verifies the fix for a bug that happened in real code.
            c = Ext.createWidget('button', {
                renderTo: Ext.getBody(),
                renderSelectors: {
                    // there is already a default childEl named 'btnEl'
                    btnEl: '.x-btn-button'
                }
            });

            expect(c.destroyed).toBe(false);
            expect(Ext.get(c.id).dom.id).toBe(c.id);

            var childId = c.btnEl.id;

            expect(Ext.get(childId).dom.id).toBe(childId);

            c.destroy();

            // component and child refs should be cleaned up
            expect(c.destroyed).toBe(true);
            expect(c.btnEl).toBeNull();

            // component and child els should be gone
            expect(Ext.get(c.id)).toBeNull();
            expect(Ext.get(childId)).toBeNull();
        });

        describe("before render", function() {
            it("should remove itself from a container", function() {
                makeComponent({
                    itemId: 'foo'
                });

                var ct = new Ext.container.Container({
                    items: c
                });

                var spy = jasmine.createSpy();

                ct.on('remove', spy);
                expect(ct.down('#foo')).toBe(c);
                c.destroy();
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toBe(ct);
                expect(spy.mostRecentCall.args[1]).toBe(c);
                expect(ct.down('#foo')).toBeNull();

                ct.destroy();
            });

            it("should remove itself from a container when floating", function() {
                makeComponent({
                    itemId: 'foo',
                    floating: true
                });

                var ct = new Ext.container.Container({
                    items: c
                });

                var spy = jasmine.createSpy();

                ct.on('remove', spy);
                expect(ct.down('#foo')).toBe(c);
                c.destroy();
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toBe(ct);
                expect(spy.mostRecentCall.args[1]).toBe(c);
                expect(ct.down('#foo')).toBeNull();

                ct.destroy();
            });
        });

        describe("after render", function() {
            it("should remove itself from a container", function() {
                makeComponent({
                    itemId: 'foo'
                });

                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    items: c
                });

                c.show();
                var spy = jasmine.createSpy();

                ct.on('remove', spy);
                expect(ct.down('#foo')).toBe(c);
                c.destroy();
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toBe(ct);
                expect(spy.mostRecentCall.args[1]).toBe(c);
                expect(ct.down('#foo')).toBeNull();

                ct.destroy();
            });

            it("should remove itself from a container when floating", function() {
                makeComponent({
                    itemId: 'foo',
                    floating: true
                });

                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    items: c
                });

                c.show();

                var spy = jasmine.createSpy();

                ct.on('remove', spy);
                expect(ct.down('#foo')).toBe(c);
                c.destroy();
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toBe(ct);
                expect(spy.mostRecentCall.args[1]).toBe(c);
                expect(ct.down('#foo')).toBeNull();

                ct.destroy();
            });
        });

        describe("floating", function() {
            it("should not destroy a previous align target", function() {
                var el = Ext.getBody().createChild();

                makeComponent({
                    floating: true,
                    width: 100,
                    height: 100
                });
                c.show();
                c.alignTo(el);
                c.destroy();
                expect(el.dom.parentNode).toBe(Ext.getBody().dom);
                el.destroy();
            });
        });
    });

    describe("afterRender", function() {
        var spy;

        describe("when pageX/pageY is set", function() {
            describe("call setPagePosition", function() {
                it("pageX", function() {
                    makeComponent({ pageX: 10 });

                    spy = spyOn(c, "setPagePosition");

                    c.render(Ext.getBody());

                    expect(spy).toHaveBeenCalledWith(10, undefined);
                });

                it("pageY", function() {
                    makeComponent({ pageY: 10 });

                    spy = spyOn(c, "setPagePosition");

                    c.render(Ext.getBody());

                    expect(spy).toHaveBeenCalledWith(undefined, 10);
                });
            });
        });

        describe("resizable", function() {
            it("should call initResizable", function() {
                makeComponent({ resizable: true });

                spy = spyOn(c, "initResizable");

                c.render(Ext.getBody());

                expect(spy).toHaveBeenCalled();
            });
        });

        describe("draggable", function() {
            it("should call initDraggable", function() {
                makeComponent({ draggable: true });

                spy = spyOn(c, "initDraggable");

                c.render(Ext.getBody());

                expect(spy).toHaveBeenCalled();
            });
        });

        describe("setAutoScroll", function() {
            describe("if autoScroll is not defined", function() {
                it("should not call setAutoScroll", function() {
                    makeComponent();

                    spy = spyOn(c, "getOverflowStyle").andCallThrough();

                    c.render(Ext.getBody());

                    expect(spy).toHaveBeenCalled();
                });
            });
            describe("if autoScroll is  defined", function() {
                it("should  call setAutoScroll", function() {
                    makeComponent({
                        autoScroll: false
                    });

                    spy = spyOn(c, "getOverflowStyle").andCallThrough();

                    c.render(Ext.getBody());

                    expect(spy).toHaveBeenCalled();
                });
            });
        });
    });

    describe("scrollable", function() {
        describe("initial configuration", function() {
            it("should not create a scroller by default", function() {
                makeComponent({
                    renderTo: document.body
                });

                expect(c.getScrollable()).toBe(null);
            });

            it("should not create a scroller if scrollable is false", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: false
                });

                expect(c.getScrollable()).toBe(false);
            });

            it("should configure a default scroller if scrollable is true", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: true
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should configure a default scroller if scrollable is 'both'", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: 'both'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should configure a vertical scroller if scrollable is 'y'", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: 'y'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should configure a vertical scroller if scrollable is 'vertical'", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: 'vertical'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should configure a horizontal scrollbar if scrollable is 'x'", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: 'x'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should configure a horizontal scrollbar if scrollable is 'horizontal'", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: 'horizontal'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should configure a non user-scrollable scroller if x and y are both false", function() {
                // useful when you need a scroller so you can set scroll positions
                // programmatically, but you don't want the user to be able to scroll
                makeComponent({
                    renderTo: document.body,
                    scrollable: {
                        x: false,
                        y: false
                    }
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should pass along a scroller config object to the Scroller constructor", function() {
                makeComponent({
                    renderTo: document.body,
                    scrollable: {
                        x: true,
                        y: false
                    }
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should thow an error if configured with an invalid string", function() {
                expect(function() {
                    makeComponent({
                        renderTo: document.body,
                        id: 'foo',
                        scrollable: 'bar'
                    });
                }).toThrow("'bar' is not a valid value for 'scrollable'");
            });
        });

        describe("reconfiguring the scroller", function() {
            it("should reconfigure the existing scroller if there is one", function() {
                var scroller;

                makeComponent({
                    renderTo: document.body,
                    scrollable: true
                });

                scroller = c.getScrollable();

                c.setScrollable({
                    x: false,
                    y: true
                });

                // should reconfigure the existing scroller, not create a new instance
                expect(c.getScrollable()).toBe(scroller);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should create a new scroller if one does not already exist", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setScrollable(true);

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should be able to disable scrolling", function() {
                makeComponent({
                    renderTo: document.body,
                    width: 300,
                    height: 300,
                    scrollable: true
                });

                c.setScrollable(false);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(false);
            });
        });

        describe("retaining scroll position across layouts", function() {
            var endSpy, s;

            beforeEach(function() {
                endSpy = jasmine.createSpy();
            });

            afterEach(function() {
                endSpy = s = null;
            });

            describe("configured dimensions", function() {
                beforeEach(function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        width: 200,
                        height: 200,
                        scrollable: true,
                        html: '<div style="width: 600px; height: 600px;"></div>'
                    });
                    s = c.getScrollable();
                    s.on('scrollend', endSpy);
                });

                it("should retain position", function() {
                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        c.setSize(199, 199);
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 300,
                            y: 300
                        });
                    });
                });

                it("should retain position when hiding/showing", function() {
                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        c.hide();
                        c.show();
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 300,
                            y: 300
                        });
                    });
                });

                it("should not fire a scroll event", function() {
                    var spy = jasmine.createSpy();

                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        s.on('scroll', spy);
                        c.setSize(199, 199);
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("calculated dimensions", function() {
                var ct;

                beforeEach(function() {
                    makeComponent({
                        scrollable: true,
                        html: '<div style="width: 600px; height: 600px;"></div>'
                    });

                    ct = new Ext.container.Container({
                        renderTo: Ext.getBody(),
                        width: 200,
                        height: 200,
                        layout: 'fit',
                        items: c
                    });

                    s = c.getScrollable();
                    s.on('scrollend', endSpy);
                });

                afterEach(function() {
                    ct.destroy();
                    ct = null;
                });

                it("should retain position", function() {
                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        ct.setSize(199, 199);
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 300,
                            y: 300
                        });
                    });
                });

                it("should retain position when hiding/showing", function() {
                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        c.hide();
                        c.show();
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 300,
                            y: 300
                        });
                    });
                });

                it("should not fire a scroll event", function() {
                    var spy = jasmine.createSpy();

                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        s.on('scroll', spy);
                        ct.setSize(199, 199);
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("shrinkWrap with constraints", function() {
                var ct;

                beforeEach(function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        scrollable: true,
                        html: '<div style="width: 600px; height: 600px;"></div>',
                        shrinkWrap: true,
                        floating: true,
                        maxWidth: 200,
                        maxHeight: 200,
                        x: 0,
                        y: 0
                    });

                    s = c.getScrollable();
                    s.on('scrollend', endSpy);
                });

                it("should retain position", function() {
                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        c.setHtml('<div style="width: 700px; height: 700px;"></div>');
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 300,
                            y: 300
                        });
                    });
                });

                it("should retain position when hiding/showing", function() {
                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        c.hide();
                        c.show();
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 300,
                            y: 300
                        });
                    });
                });

                it("should not fire a scroll event", function() {
                    var spy = jasmine.createSpy();

                    s.scrollTo(300, 300);
                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });
                    runs(function() {
                        s.on('scroll', spy);
                        c.setHtml('<div style="width: 700px; height: 700px;"></div>');
                    });
                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });
                    runs(function() {
                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            });
        });
    });

    describe("autoScroll", function() {
        describe("initial configuration", function() {
            it("should configure a scroller if autoScroll is true", function() {
                makeComponent({
                    renderTo: document.body,
                    autoScroll: true
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should not configure a scroller if autoScroll is false", function() {
                makeComponent({
                    renderTo: document.body,
                    autoScroll: false
                });

                expect(c.getScrollable()).toBe(null);
            });

            it("should not override a scrollable config (autoScroll: true)", function() {
                makeComponent({
                    renderTo: document.body,
                    autoScroll: true,
                    scrollable: false
                });

                expect(c.getScrollable()).toBe(false);
            });

            it("should not override a scrollable config (autoScroll: false)", function() {
                makeComponent({
                    renderTo: document.body,
                    autoScroll: false,
                    scrollable: true
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(true);
            });
        });

        describe("setAutoScroll", function() {
            it("should set scrollable with autoScroll:true", function() {
                makeComponent({
                    renderTo: document.body
                });

                spyOn(c, 'setScrollable').andCallThrough();

                c.setAutoScroll(true);

                expect(c.setScrollable).toHaveBeenCalledWith(true);
            });

            it("should set scrollable with autoScroll:false", function() {
                makeComponent({
                    renderTo: document.body
                });

                spyOn(c, 'setScrollable').andCallThrough();

                c.setAutoScroll(false);

                expect(c.setScrollable).toHaveBeenCalledWith(false);
            });
        });
    });

    describe("overflowX and overflowY", function() {
        describe("initial configuration", function() {
            it("should set overflowX:true", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowX: true
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowX:'auto'", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowX: 'auto'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe('auto');
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowX:'scroll'", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowX: 'scroll'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe('scroll');
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowX:'hidden'", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowX: 'hidden'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowY:true", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowY: true
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should set overflowY:'auto'", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowY: 'auto'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe('auto');
            });

            it("should set overflowY:'scroll'", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowY: 'scroll'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe('scroll');
            });

            it("should set overflowY:'hidden'", function() {
                makeComponent({
                    renderTo: document.body,
                    overflowY: 'hidden'
                });

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(false);
            });
        });

        describe("setOverflowXY", function() {
            it("should set overflowX:true", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY(true);

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(true);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowX:'auto'", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY('auto');

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe('auto');
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowX:'scroll'", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY('scroll');

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe('scroll');
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowX:'hidden'", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY('hidden');

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(false);
            });

            it("should set overflowY:true", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY(undefined, true);

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(true);
            });

            it("should set overflowY:'auto'", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY(undefined, 'auto');

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe('auto');
            });

            it("should set overflowY:'scroll'", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY(undefined, 'scroll');

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe('scroll');
            });

            it("should set overflowY:'hidden'", function() {
                makeComponent({
                    renderTo: document.body
                });

                c.setOverflowXY(undefined, 'hidden');

                expect(c.getScrollable() instanceof Ext.scroll.Scroller).toBe(true);
                expect(c.getScrollable().getX()).toBe(false);
                expect(c.getScrollable().getY()).toBe(false);
            });
        });
    });

    (jasmine.supportsTouch ? xdescribe : describe)("scroll template methods", function() {
        var startSpy, moveSpy, endSpy;

        beforeEach(function() {
            startSpy = jasmine.createSpy();
            moveSpy = jasmine.createSpy();
            endSpy = jasmine.createSpy();

            makeComponent({
                renderTo: document.body,
                height: 100,
                width: 100,
                scrollable: true,
                html: '<div style="height:200px;width:300px"></div>',
                xhooks: {
                    onScrollStart: startSpy,
                    onScrollMove: moveSpy,
                    onScrollEnd: endSpy
                }
            });
        });

        it("should call onScrollStart when scrolling begins", function() {
            // scroll twice, make sure onScrollStart only ran once
            c.scrollTo(10, 20);

            // onScrollMove is called afteronScrollStart, so both will be done and testable after this
            // But the buffered scroll end timer will not have expired, so the next scroll impulse
            // will not call onScrollStart
            waitsForSpy(moveSpy);

            runs(function() {
                expect(startSpy.callCount).toBe(1);
                expect(startSpy.mostRecentCall.args).toEqual([10, 20]);
                c.scrollTo(20, 30);
            });

            waitsForSpy(moveSpy);

            runs(function() {
                // Because we moved through with no waiting, the scroll end timer has not fired, so this
                // will be part of one scroll sequence
                expect(startSpy.callCount).toBe(1);
            });

            // This is still due
            waitsForSpy(endSpy);
        });

        it("should call onScrollMove during scrolling", function() {
            c.scrollTo(10, 20);

            waitsFor(function() {
                return moveSpy.callCount === 1;
            }, 'moveSpy to fire the first time', 1000);

            runs(function() {
                expect(moveSpy.mostRecentCall.args).toEqual([10, 20]);
                c.scrollTo(20, 30);
            });

            waitsFor(function() {
                return moveSpy.callCount === 2;
            }, 'moveSpy to fire the second time', 1000);

            runs(function() {
                expect(moveSpy.mostRecentCall.args).toEqual([20, 30]);
            });
        });

        it("should call onScrollEnd when scrolling ends", function() {
            c.scrollTo(10, 20);

            waitsFor(function() {
                return endSpy.callCount === 1;
            }, 'endSpy to fire the first time', 1000);

            runs(function() {
                c.scrollTo(20, 30);
            });

            waitsFor(function() {
                return endSpy.callCount === 2;
            }, 'endSpy to fire the second time', 1000);

            runs(function() {
                expect(endSpy.mostRecentCall.args).toEqual([20, 30]);
            });
        });
    });

    describe("initResizer", function() {
        beforeEach(function() {
            makeComponent({ renderTo: Ext.getBody() });
        });

        it("should create this.resizer", function() {
            expect(c.resizer).not.toBeDefined();

            c.initResizable();

            expect(c.resizer).toBeDefined();
        });
    });

    xdescribe("initDraggable", function() {

    });

    describe("setPosition", function() {
        beforeEach(function() {
            makeComponent({ renderTo: Ext.getBody() });
        });

        describe("when arguments", function() {
            it("should set x", function() {
                c.setPosition(10, 0);

                expect(c.x).toEqual(10);
            });

            it("should set y", function() {
                c.setPosition(0, 10);

                expect(c.y).toEqual(10);
            });
        });

        describe("when array", function() {
            it("should set x", function() {
                c.setPosition([10, 0]);

                expect(c.x).toEqual(10);
            });

            it("should set y", function() {
                c.setPosition([0, 10]);

                expect(c.y).toEqual(10);
            });
        });

        describe("when rendered", function() {
            it("should call adjustPosition", function() {
                var spy = spyOn(c, "adjustPosition").andCallThrough();

                c.setPosition(10, 0);

                expect(spy).toHaveBeenCalled();
            });

            it("should call onPosition", function() {
                var spy = spyOn(c, "onPosition");

                c.setPosition(10, 0);

                expect(spy).toHaveBeenCalled();
            });

            it("should fire the move event", function() {
                var fired = false;

                c.on({ move: function() { fired = true; } });

                c.setPosition(10, 0);

                expect(fired).toBeTruthy();
            });
        });
    });

    describe("showAt", function() {
        beforeEach(function() {
            makeComponent({ renderTo: Ext.getBody() });
        });

        it("should call setPagePosition", function() {
            var spy = spyOn(c, "setPagePosition");

            c.showAt(10, 0, true);

            expect(spy).toHaveBeenCalledWith(10, 0, true);
        });

        it("should call show", function() {
            var spy = spyOn(c, "show");

            c.showAt(10, 0);

            expect(spy).toHaveBeenCalled();
        });
    });

    describe("setPagePosition", function() {
        beforeEach(function() {
            makeComponent({ renderTo: Ext.getBody() });
        });

        describe("when arguments", function() {
            it("should set x", function() {
                c.setPagePosition(10, 0);

                expect(c.pageX).toEqual(10);
            });

            it("should set y", function() {
                c.setPagePosition(0, 10);

                expect(c.pageY).toEqual(10);
            });
        });

        describe("when array", function() {
            it("should set x", function() {
                c.setPagePosition([10, 0]);

                expect(c.pageX).toEqual(10);
            });

            it("should set y", function() {
                c.setPagePosition([0, 10]);

                expect(c.pageY).toEqual(10);
            });
        });

        it("should set the element's X", function() {
            c.el.dom.style.position = 'absolute';
            c.setPagePosition(10, 0);
            expect(c.el.getX()).toEqual(10);
        });

        it("should set the element's Y", function() {
            c.el.dom.style.position = 'absolute';
            c.setPagePosition(0, 10);
            expect(c.el.getY()).toEqual(10);
        });
    });

    describe("Component traversal", function() {
        var cq = Ext.ComponentQuery,
            result, f1, f2, f3, f4, f5, fieldset, p;

        beforeEach(function() {
            p = new Ext.Panel({
                layout: 'card',
                items: fieldset = new Ext.form.FieldSet({
                    id: 'fieldset-1',
                    items: [
                        f1 = new Ext.form.field.Number({
                            name: 'tab1-field1'
                        }),
                        f2 = new Ext.form.field.Date({
                            name: 'tab1-field2'
                        }),
                        f3 = new Ext.form.field.Text({
                            name: 'tab1-field3'
                        }),
                        f4 = new Ext.container.Container({
                            items: [
                                f5 = new Ext.form.field.Text({
                                    name: 'baz'
                                })
                            ]
                        })
                    ]
                })
            });
        });

        afterEach(function() {
            p.destroy();
            p = fieldset = f1 = f2 = f3 = f4 = f5 = null;
        });

        describe("Component.prev()", function() {
            it("Should select f2", function() {
                result = f3.prev();
                expect(result).toEqual(f2);
            });
        });

        describe("Component.prev('selector')", function() {
            it("Should select f1", function() {
                result = f3.prev('numberfield');
                expect(result).toEqual(f1);
            });
        });

        describe("Component.prev() on first child", function() {
            it("Should select null", function() {
                result = f1.prev();
                expect(result).toBeNull();
            });
        });

        describe("Component.next()", function() {
            it("Should select f2", function() {
                result = f1.next();
                expect(result).toEqual(f2);
            });
        });

        describe("Component.next('selector')", function() {
            it("Should select f3", function() {
                result = f1.next('textfield(true)');
                expect(result).toEqual(f3);
            });
        });

        describe("Component.next() on last child", function() {
            it("Should select null", function() {
                result = f4.next();
                expect(result).toBeNull();
            });
        });

        describe("Component.up()", function() {
            it("Should select fieldset", function() {
                result = f3.up();
                expect(result).toEqual(fieldset);
            });
        });

        describe("Component.up searches for a string", function() {
            describe("Component.up('selector')", function() {
                it("Should select panel", function() {
                    result = f3.up('panel');
                    expect(result).toEqual(p);
                });
            });

            describe("Component.up() on outermost container", function() {
                it("Should select undefined", function() {
                    result = p.up();
                    expect(result).toBeUndefined();
                });
            });

            describe("Component.up('selector') on xtype which does not occur", function() {
                it("Should select undefined", function() {
                    result = f3.up('gridpanel');
                    expect(result).toBeUndefined();
                });
            });

            describe("Component.up(':pseudo-class')", function() {
                beforeEach(function() {
                    cq.pseudos.cardLayout = function(items) {
                        var result = [],
                            c,
                            i = 0,
                            l = items.length;

                        for (; i < l; i++) {
                            if ((c = items[i]).getLayout() instanceof Ext.layout.CardLayout) {
                                result.push(c);
                            }
                        }

                        return result;
                    };
                });

                afterEach(function() {
                    delete cq.pseudos.cardLayout;
                });

                it("Should select the panel", function() {
                    result = f3.up(':cardLayout');
                    expect(result).toEqual(p);
                });
            });
        });

        describe("Component.up searches for a Component", function() {
            it("should not find children", function() {
                expect(p.up(f1)).toBe(undefined);
            });

            it("should not find siblings", function() {
                expect(f3.up(f2)).toBe(undefined);
            });

            it("should find ancestors at any level", function() {
                expect(f3.up(p)).toBe(p);
                expect(f5.up(f4)).toBe(f4);
                expect(f5.up(p)).toBe(p);
            });
        });
    });

    describe("getPosition of static Component", function() {
        it("should report the element position of a component rendered to the body", function() {
            c = Ext.create('Ext.Component', { floating: true, x: 10, y: 10, renderTo: document.body });
            expect(c.getPosition()).toEqual([10, 10]);
            expect(c.getPosition(true)).toEqual([10, 10]);
        });

        // This test seems to be failing intermittently in all browsers
        xit("should report the element position when not local, and the container-relative position when local", function() {
            c = Ext.create('Ext.container.Container', {
                margin: 10,
                renderTo: document.body,
                items: {
                    xtype: 'window',
                    id: 'getPositionTestWindow',
                    x: 10,
                    y: 10,
                    width: 100,
                    height: 100
                }
            });
            Ext.getCmp('getPositionTestWindow').show();
            expect(Ext.getCmp('getPositionTestWindow').getPosition()).toEqual([20, 20]);
            expect(Ext.getCmp('getPositionTestWindow').getPosition(true)).toEqual([10, 10]);
        });
    });

    describe("floaters with %age size", function() {
        it("should use the natural width of the window to calculate the header's width", function() {
            document.body.style.height = '100%';
            var body = Ext.getBody(),
                w = Math.floor(body.getWidth() / 2),
                h = Math.floor(Ext.dom.Element.getViewportHeight() / 2);

            // account for rounding errors
            w = [w, w + (Ext.isIE9m ? 10 : 1)];
            h = [h, h + 1];

            c = Ext.create('Ext.panel.Panel', {
                floating: {
                    shadow: false
                },
                title: 'test',
                width: '50%',
                height: '50%',
                x: 0, y: 0
            });

            c.show();

            expect(c).toHaveLayout({
                el: {
                    w: w,
                    h: h
                },
                dockedItems: {
                    0: {
                        el: {
                            w: w
                        }
                    }
                }
            });
            document.body.style.height = '';
        });

        it("should use the natural height of the window to calculate the header's height", function() {
            document.body.style.height = '100%';
            var body = Ext.getBody(),
                w = Math.floor(body.getWidth() / 2),
                h = Math.floor(Ext.dom.Element.getViewportHeight() / 2);

            // account for rounding errors
            w = [w, w + (Ext.isIE9m ? 10 : 1)];
            h = [h, h + 1];

            c = Ext.create('Ext.panel.Panel', {
                floating: {
                    shadow: false
                },
                title: 'test',
                headerPosition: 'left',
                width: '50%',
                height: '50%',
                x: 0, y: 0
            });

            c.show();

            expect(c).toHaveLayout({
                el: {
                    w: w,
                    h: h
                },
                dockedItems: {
                    0: {
                        el: {
                            h: h
                        }
                    }
                }
            });
            document.body.style.height = '';
        });
    });

    describe("scrollFlags", function() {
        it("should set default flags", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: '',
                overflowY: '',
                x: false,
                y: false,
                both: false
            });
        });

        it("should set flags with scrollable: true", function() {
            makeComponent({
                scrollable: true,
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: 'auto',
                overflowY: 'auto',
                x: true,
                y: true,
                both: true
            });
        });

        it("should set flags with scrollable: false", function() {
            makeComponent({
                scrollable: false,
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: '',
                overflowY: '',
                x: false,
                y: false,
                both: false
            });
        });

        it("should set flags with scrollable: { x: false, y: false }", function() {
            // in this case we have a scroller instance so we can control the scroll
            // position programmatically, but the component is not users scrollable.
            // the only difference between this and not having a scroller as far as
            // scrollFlags are concerned is overflow-x/overflow-y styles are 'hidden'
            // vs just ''
            makeComponent({
                scrollable: {
                    x: false,
                    y: false
                },
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: 'hidden',
                overflowY: 'hidden',
                x: false,
                y: false,
                both: false
            });
        });

        it("should set flags with horizontal auto-scrolling", function() {
            makeComponent({
                scrollable: 'x',
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: 'auto',
                overflowY: 'hidden',
                x: true,
                y: false,
                both: false
            });
        });

        it("should set flags with horizontal scroll", function() {
            makeComponent({
                scrollable: {
                    x: 'scroll',
                    y: false
                },
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: 'scroll',
                overflowY: 'hidden',
                x: true,
                y: false,
                both: false
            });
        });

        it("should set flags with vertical auto-scrolling", function() {
            makeComponent({
                scrollable: 'y',
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: 'hidden',
                overflowY: 'auto',
                x: false,
                y: true,
                both: false
            });
        });

        it("should set flags with vertical scroll", function() {
            makeComponent({
                scrollable: {
                    x: false,
                    y: 'scroll'
                },
                renderTo: Ext.getBody()
            });
            expect(c.scrollFlags).toEqual({
                overflowX: 'hidden',
                overflowY: 'scroll',
                x: false,
                y: true,
                both: false
            });
        });

        function createBothScrollSuite(ox, oy, expectX, expectY) {
            it("should set flags with overflowX: " + ox + ' & overflowY: ' + oy, function() {
                makeComponent({
                    scrollable: {
                        x: ox,
                        y: oy
                    },
                    renderTo: Ext.getBody()
                });
                expect(c.scrollFlags).toEqual({
                    overflowX: expectX,
                    overflowY: expectY,
                    x: true,
                    y: true,
                    both: true
                });
            });
        }

        // All x values, Y true
        createBothScrollSuite(true, true, 'auto', 'auto');
        createBothScrollSuite('auto', true, 'auto', 'auto');
        createBothScrollSuite('scroll', true, 'scroll', 'auto');

        // All x values, Y 'auto'
        createBothScrollSuite(true, 'auto', 'auto', 'auto');
        createBothScrollSuite('auto', 'auto', 'auto', 'auto');
        createBothScrollSuite('scroll', 'auto', 'scroll', 'auto');

        // All x values, Y 'scroll'
        createBothScrollSuite(true, 'scroll', 'auto', 'scroll');
        createBothScrollSuite('auto', 'scroll', 'auto', 'scroll');
        createBothScrollSuite('scroll', 'scroll', 'scroll', 'scroll');
    });

    describe("initStyles", function() {
        function createSuite(scopeCss) {
            describe("root classes" + (scopeCss ? ' - with Ext.scopeCss == true' : ''), function() {
                if (scopeCss) {
                    beforeEach(function() {
                        Ext.scopeCss = true;
                    });
                    afterEach(function() {
                        Ext.scopeCss = undefined;
                    });
                }

                it("should apply root classes to root level components", function() {
                    var container = Ext.widget({
                        xtype: 'container',
                        renderTo: document.body,
                        items: [{
                            id: 'child',
                            xtype: 'container',
                            items: {
                                id: 'grandchild',
                                xtype: 'component'
                            }
                        }]
                    }),
                    child = Ext.getCmp('child'),
                    grandchild = Ext.getCmp('grandchild');

                    if (scopeCss) {
                        expect(container.el).toHaveCls(Ext.baseCSSPrefix + 'body');
                        expect(child.el).not.toHaveCls(Ext.baseCSSPrefix + 'body');
                        expect(grandchild.el).not.toHaveCls(Ext.baseCSSPrefix + 'body');
                    }

                    expect(container.el).toHaveCls(Ext.baseCSSPrefix + 'border-box');
                    expect(child.el).not.toHaveCls(Ext.baseCSSPrefix + 'border-box');
                    expect(grandchild.el).not.toHaveCls(Ext.baseCSSPrefix + 'border-box');

                    container.destroy();
                });

                it("should apply root classes to floaters, but not to their descendants", function() {
                    var container = Ext.widget({
                        xtype: 'container',
                        floating: {
                            shadow: false
                        },
                        items: [{
                            id: 'child',
                            xtype: 'container',
                            items: {
                                id: 'grandchild',
                                xtype: 'component'
                            }
                        }]
                    }),
                    child = Ext.getCmp('child'),
                    grandchild = Ext.getCmp('grandchild'),
                    // make a floater that it is not root level, but should still get the root
                    // classes since it is rendered to DOM root
                    owner = Ext.widget({
                        xtype: 'container',
                        renderTo: document.body,
                        items: [container]
                    });

                    container.show();

                    if (scopeCss) {
                        expect(container.el).toHaveCls(Ext.baseCSSPrefix + 'body');
                        expect(child.el).not.toHaveCls(Ext.baseCSSPrefix + 'body');
                        expect(grandchild.el).not.toHaveCls(Ext.baseCSSPrefix + 'body');
                    }

                    expect(container.el).toHaveCls(Ext.baseCSSPrefix + 'border-box');
                    expect(child.el).not.toHaveCls(Ext.baseCSSPrefix + 'border-box');
                    expect(grandchild.el).not.toHaveCls(Ext.baseCSSPrefix + 'border-box');
                    owner.destroy();
                });

            });
        }

        createSuite();
        createSuite(true);
    });

    describe('Updating with raw HTML', function() {
        it('should not cause a layout if no dimensions are shrinkwrapped', function() {

            var c = new Ext.container.Container({
                    items: {
                        height: 100,
                        width: 100,
                        xtype: 'component'
                    },
                    renderTo: document.body
                }),
                child = c.child('component');

            child.update('foo');
            expect(child.componentLayoutCounter).toBe(1);
            c.destroy();
        });
    });

    describe('mask()/unmask() methods', function() {
        beforeEach(function() {
            makeComponent({
                height: 100,
                width: 100,
                renderTo: document.body,
                style: 'background-color:red'
            });
        });

        it('should render the mask into the Component el by default', function() {
            c.mask();

            expect(c.el.dom.childNodes.length).toBe(1);
            expect(c.el.dom.firstChild.className).toBe(Ext.baseCSSPrefix + 'mask ' + Ext.baseCSSPrefix + 'border-box');
            expect(Ext.fly(c.el.dom.firstChild).getBox()).toEqual(c.el.getBox());
        });

        it('should render the mask into the Component el if getMaskTarget has been overridden to return null', function() {
            c.getMaskTarget = function() { return null; };

            c.mask();

            expect(c.el.dom.childNodes.length).toBe(1);
            expect(c.el.dom.firstChild.className).toBe(Ext.baseCSSPrefix + 'mask ' + Ext.baseCSSPrefix + 'border-box');
            expect(Ext.fly(c.el.dom.firstChild).getBox()).toEqual(c.el.getBox());
        });

        it("should remove the mask on unmask()", function() {
            c.mask();
            expect(c.el.dom.childNodes.length).toBe(1);

            c.unmask();
            expect(c.el.dom.childNodes.length).toBe(0);
        });

        describe("tabbable elements", function() {
            beforeEach(function() {
                if (c) {
                    Ext.destroy(c);
                }

                makeComponent({
                    height: 100,
                    width: 100,
                    renderTo: document.body,
                    style: 'background-color:magenta',
                    focusable: true,
                    autoEl: {
                        tag: 'div',
                        tabIndex: 0
                    },
                    renderTpl: [
                        '<input />',
                        '<div>',
                            '<textarea>foo</textarea>',
                        '</div>'
                    ]
                });

                c.mask();
            });

            describe("masking", function() {
                it("should remove itself from tab order", function() {
                    expect(c.el.isTabbable()).toBeFalsy();
                });

                it("should remove its children from tab order", function() {
                    var tabbables = c.el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(0);
                });
            });

            describe("unmasking", function() {
                beforeEach(function() {
                    c.unmask();
                });

                it("should restore itself in tab order", function() {
                    expect(c.el.isTabbable()).toBeTruthy();
                });

                it("should restore its children tabbable state", function() {
                    var tabbables = c.el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(2);
                });
            });
        });

        describe("masked hierarchy state", function() {
            it("should be undefined before masking", function() {
                expect(c.getInherited().masked).not.toBeDefined();
            });

            it("should be true when masked", function() {
                c.mask();

                expect(c.getInherited().masked).toBe(true);
            });

            it("should be undefined again after masking", function() {
                c.mask();
                c.unmask();

                expect(c.getInherited().masked).not.toBeDefined();
            });
        });

        describe("isMasked", function() {
            var ct;

            beforeEach(function() {
                if (c) {
                    Ext.destroy(c);
                }

                ct = new Ext.container.Container({
                    renderTo: document.body,
                    height: 100,
                    width: 100,
                    items: [{
                        xtype: 'component',
                        height: 100,
                        width: 100,
                        style: 'background-color: green'
                    }]
                });

                c = ct.down();
            });

            afterEach(function() {
                if (ct) {
                    Ext.destroy(ct);
                }

                ct = null;
            });

            it("should return false when component is not masked", function() {
                expect(c.isMasked()).toBeFalsy();
            });

            it("should return false when parent is masked but !hierarchy", function() {
                ct.mask();

                expect(c.isMasked()).toBeFalsy();
            });

            it("should return true when component is masked", function() {
                c.mask();

                expect(c.isMasked()).toBeTruthy();
            });

            it("should return true when parent is masked && hierarchy", function() {
                ct.mask();

                expect(c.isMasked(true)).toBeTruthy();
            });

            it("should return false again when parent is unmasked", function() {
                ct.mask();
                ct.unmask();

                expect(c.isMasked(true)).toBeFalsy();
            });
        });
    });

    describe('setLoading() method', function() {
        describe("mask target === main el", function() {
            beforeEach(function() {
                makeComponent({
                    height: 100,
                    width: 100,
                    renderTo: document.body,
                    style: 'background-color:red',
                    maskElement: 'el'
                });

                c.setLoading(true);
            });

            it('should render the LoadMask into the Component', function() {
                expect(c.el.dom.childNodes.length).toBe(1);
            });

            it("should set " + Ext.baseCSSPrefix + "mask class on the mask", function() {
                var maskEl = Ext.get(c.el.dom.firstChild);

                expect(maskEl.hasCls(Ext.baseCSSPrefix + 'mask')).toBe(true);
            });

            it("should size the mask to target el", function() {
                expect(Ext.fly(c.el.dom.firstChild).getBox()).toEqual(c.el.getBox());
            });

            it("should move the mask with the Component", function() {
                // Mask should follow target component
                c.setXY([100, 100]);
                expect(Ext.fly(c.el.dom.firstChild).getBox()).toEqual(c.el.getBox());
            });
        });

        describe("Default mask target (document body)", function() {
            var childNo, maskNode, body;

            beforeEach(function() {
                body = document.body;
                childNo = body.children.length;

                makeComponent({
                    height: 100,
                    width: 100,
                    renderTo: document.body,
                    style: 'background-color:red'
                });

                c.setLoading(true);

                maskNode = body.children[childNo + 1];
            });

            it("should render LoadMask", function() {
                expect(body.childNodes.length).toBe(childNo + 2); // target + mask = 2
            });

            it("should set " + Ext.baseCSSPrefix + "mask class on the mask el", function() {
                var maskEl = Ext.get(maskNode);

                expect(maskEl.hasCls(Ext.baseCSSPrefix + 'mask')).toBe(true);
            });

            it("should size the mask to the target component", function() {
                expect(Ext.fly(maskNode).getBox()).toEqual(c.el.getBox());
            });

            it("should unmask document.body on destroy", function() {
                c.destroy();

                expect(body.childNodes.length).toBe(childNo);

                c = null;
            });
        });

        describe("isMasked", function() {
            var ct;

            beforeEach(function() {
                ct = new Ext.container.Container({
                    renderTo: document.body,
                    height: 100,
                    width: 100,
                    items: [{
                        xtype: 'component',
                        height: 100,
                        width: 100,
                        style: 'background-color: red'
                    }]
                });

                c = ct.down();
            });

            afterEach(function() {
                if (ct) {
                    Ext.destroy(ct);
                }

                ct = c = null;
            });

            it("should return false when component is not masked", function() {
                expect(c.isMasked()).toBeFalsy();
            });

            it("should return false when parent is masked but !hierarchy", function() {
                ct.setLoading(true);

                expect(c.isMasked()).toBeFalsy();
            });

            it("should return true when component is masked", function() {
                c.setLoading(true);

                expect(c.isMasked()).toBeTruthy();
            });

            it("should return true when parent is masked && hierarchy", function() {
                ct.setLoading(true);

                expect(c.isMasked(true)).toBeTruthy();
            });

            it("should return false again when parent is unmasked", function() {
                ct.setLoading(true);
                ct.setLoading(false);

                expect(c.isMasked(true)).toBeFalsy();
            });
        });

        describe("tabbable elements", function() {
            beforeEach(function() {
                if (c) {
                    Ext.destroy(c);
                }

                makeComponent({
                    height: 100,
                    width: 100,
                    renderTo: document.body,
                    style: 'background-color:magenta',
                    focusable: true,
                    autoEl: {
                        tag: 'div',
                        tabIndex: 0
                    },
                    renderTpl: [
                        '<input />',
                        '<div>',
                            '<textarea>foo</textarea>',
                        '</div>'
                    ],
                    getFocusEl: function() {
                        return this.el;
                    }
                });
            });

            it("should be tabbable initially (sanity check)", function() {
                expect(c.el.isTabbable()).toBeTruthy();
            });

            describe("masking", function() {
                beforeEach(function() {
                    c.setLoading(true);
                });

                it("should remove itself from tab order", function() {
                    expect(c.el.isTabbable()).toBeFalsy();
                });

                it("should remove its children from tab order", function() {
                    var tabbables = c.el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(0);
                });
            });

            describe("unmasking", function() {
                beforeEach(function() {
                    c.setLoading(true);
                    c.setLoading(false);
                });

                it("should restore itself in tab order", function() {
                    expect(c.el.isTabbable()).toBeTruthy();
                });

                it("should restore its children tabbable state", function() {
                    var tabbables = c.el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(2);
                });
            });
        });

        describe("maskDefaults", function() {
            var c;

            afterEach(function() {
                c = Ext.destroy(c);
            });

            it("should display default message if no maskDefaults is used", function() {
                c = new Ext.Component({
                    height: 100,
                    width: 100,
                    renderTo: document.body
                });

                c.setLoading(true);

                expect(c.loadMask.msgTextEl.dom.innerHTML).toBe('Loading...');
            });

            it("should not display message if supplied with maskDefaults", function() {
                c = new Ext.Component({
                    height: 100,
                    width: 100,
                    renderTo: document.body,
                    maskDefaults: {
                        useMsg: false
                    }
                });

                c.setLoading(true);

                expect(c.loadMask.msgWrapEl.isVisible(true)).toBe(false);
            });
        });

        describe('function args', function() {
            var c, loadMask;

            beforeEach(function() {
                c = new Ext.Component({
                    height: 100,
                    width: 100,
                    renderTo: Ext.getBody()
                });

                loadMask = c.loadMask = new Ext.LoadMask({
                    target: c
                });

                spyOn(loadMask, 'show').andCallThrough();
            });

            afterEach(function() {
                Ext.destroy(c, loadMask);
                c = loadMask = null;
            });

            describe('load mask message', function() {
                describe('no message string is passed', function() {
                    it('should render with a default loading message if no arguments are passed', function() {
                        c.setLoading();
                        expect(loadMask.msgTextEl.dom.innerHTML).toBe(loadMask.msg);
                    });

                    it('should render with a default loading message if first arg is not a string', function() {
                        c.setLoading(true);
                        expect(loadMask.msgTextEl.dom.innerHTML).toBe(loadMask.msg);
                    });

                    it('should render with a default loading message if config does not have a msg property', function() {
                        c.setLoading({ target: c });
                        expect(loadMask.msgTextEl.dom.innerHTML).toBe(loadMask.msg);
                    });
                });

                describe('message string is passed', function() {
                    it('should render with the passed message string', function() {
                        c.setLoading('Lulz');
                        expect(loadMask.msgTextEl.dom.innerHTML).toBe('Lulz');
                    });

                    it('should render with the passed message string in the config object', function() {
                        c.setLoading({ msg: 'Rupert!' });
                        expect(loadMask.msgTextEl.dom.innerHTML).toBe('Rupert!');
                    });
                });
            });

            describe('first argument is false', function() {
                it('should not render the load mask if false', function() {
                    c.setLoading(false);
                    expect(loadMask.show).not.toHaveBeenCalled();
                });
            });

            describe('first argument is truthy or no arguments are passed', function() {
                it('should render the load mask if no arguments are passed', function() {
                    c.setLoading();
                    expect(loadMask.show).toHaveBeenCalled();
                });

                it('should render the load mask if true', function() {
                    c.setLoading(true);
                    expect(loadMask.show).toHaveBeenCalled();
                });

                it('should render the load mask if a zero-length string', function() {
                    c.setLoading('');
                    expect(loadMask.show).toHaveBeenCalled();
                });

                it('should render the load mask if a non-zero-length string', function() {
                    c.setLoading('Motley');
                    expect(loadMask.show).toHaveBeenCalled();
                });

                it('should render the load mask if a config object', function() {
                    c.setLoading({ target: c });
                    expect(loadMask.show).toHaveBeenCalled();
                });

                // EXTJS-21006
                it("should be enabled when using load mask hide and disable on single panel", function() {

                    c.disable();
                    loadMask.show();
                    loadMask.hide();
                    c.enable();

                    expect(c.isMasked()).toBeFalsy();
                    expect(c.el.dom.firstChild).toBe(null);
               });
            });
        });

        describe("ARIA", function() {
            beforeEach(function() {
                makeComponent({
                    height: 100,
                    width: 100,
                    renderTo: Ext.getBody(),
                    style: 'background-color:red',
                    ariaRole: 'button',
                    focusable: true,
                    tabIndex: 0
                });
            });

            describe("masking", function() {
                beforeEach(function() {
                    c.setLoading(true);
                });

                it("should set aria-describedby", function() {
                    expect(c).toHaveAttr('aria-describedby', c.loadMask.id);
                });

                it("should set aria-busy", function() {
                    expect(c).toHaveAttr('aria-busy', 'true');
                });
            });

            describe("unmasking", function() {
                it("should remove aria-describedby", function() {
                    c.setLoading(true);
                    c.setLoading(false);

                    expect(c).not.toHaveAttr('aria-describedby');
                });

                it("should preserve aria-describedby value if it was set", function() {
                    c.ariaEl.dom.setAttribute('aria-describedby', 'foo');
                    c.setLoading(true);
                    c.setLoading(false);

                    expect(c).toHaveAttr('aria-describedby', 'foo');
                });

                it("should remove aria-busy", function() {
                    c.setLoading(true);
                    c.setLoading(false);

                    expect(c).not.toHaveAttr('aria-busy');
                });
            });
        });
    });

    describe("size constraints", function() {
        it("should constrain if width is greater than maxWidth", function() {
            makeComponent({
                renderTo: document.body,
                width: 300,
                maxWidth: 200
            });

            expect(c.getWidth()).toBe(200);
        });

        it("should not constrain if width is less than maxWidth", function() {
            makeComponent({
                renderTo: document.body,
                width: 200,
                maxWidth: 300
            });

            expect(c.getWidth()).toBe(200);
        });

        it("should constrain if width is less than minWidth", function() {
            makeComponent({
                renderTo: document.body,
                width: 200,
                minWidth: 300
            });

            expect(c.getWidth()).toBe(300);
        });

        it("should not constrain if width is greater than minWidth", function() {
            makeComponent({
                renderTo: document.body,
                width: 300,
                minWidth: 200
            });

            expect(c.getWidth()).toBe(300);
        });

        it("should constrain if height is greater than maxHeight", function() {
            makeComponent({
                renderTo: document.body,
                height: 300,
                maxHeight: 200
            });

            expect(c.getHeight()).toBe(200);
        });

        it("should not constrain if height is less than maxHeight", function() {
            makeComponent({
                renderTo: document.body,
                height: 200,
                maxHeight: 300
            });

            expect(c.getHeight()).toBe(200);
        });

        it("should constrain if height is less than minHeight", function() {
            makeComponent({
                renderTo: document.body,
                height: 200,
                minHeight: 300
            });

            expect(c.getHeight()).toBe(300);
        });

        it("should not constrain if height is greater than minHeight", function() {
            makeComponent({
                renderTo: document.body,
                height: 300,
                minHeight: 200
            });

            expect(c.getHeight()).toBe(300);
        });

        describe("after initial render", function() {
            it("should constrain if width is greater than maxWidth", function() {
                makeComponent({
                    renderTo: document.body,
                    width: 300
                });

                c.setMaxWidth(200);

                expect(c.getWidth()).toBe(200);
            });

            it("should not constrain if width is less than maxWidth", function() {
                makeComponent({
                    renderTo: document.body,
                    width: 200
                });

                c.setMaxWidth(300);

                expect(c.getWidth()).toBe(200);
            });

            it("should constrain if width is less than minWidth", function() {
                makeComponent({
                    renderTo: document.body,
                    width: 200
                });

                c.setMinWidth(300);

                expect(c.getWidth()).toBe(300);
            });

            it("should not constrain if width is greater than minWidth", function() {
                makeComponent({
                    renderTo: document.body,
                    width: 300
                });

                c.setMinWidth(200);

                expect(c.getWidth()).toBe(300);
            });

            it("should constrain if height is greater than maxHeight", function() {
                makeComponent({
                    renderTo: document.body,
                    height: 300
                });

                c.setMaxHeight(200);

                expect(c.getHeight()).toBe(200);
            });

            it("should not constrain if height is less than maxHeight", function() {
                makeComponent({
                    renderTo: document.body,
                    height: 200
                });

                c.setMaxHeight(300);

                expect(c.getHeight()).toBe(200);
            });

            it("should constrain if height is less than minHeight", function() {
                makeComponent({
                    renderTo: document.body,
                    height: 200
                });

                c.setMinHeight(300);

                expect(c.getHeight()).toBe(300);
            });

            it("should not constrain if height is greater than minHeight", function() {
                makeComponent({
                    renderTo: document.body,
                    height: 300
                });

                c.setMinHeight(200);

                expect(c.getHeight()).toBe(300);
            });
        });
    });

    describe("liquidLayout", function() {
        it("should set minWidth as an inline style", function() {
            makeComponent({
                renderTo: document.body,
                liquidLayout: true,
                minWidth: 50
            });

            expect(c.el.isStyle('min-width', '50px')).toBe(true);
        });

        it("should set maxWidth as an inline style", function() {
            makeComponent({
                renderTo: document.body,
                liquidLayout: true,
                maxWidth: 50
            });

            expect(c.el.isStyle('max-width', '50px')).toBe(true);
        });

        it("should set minHeight as an inline style", function() {
            makeComponent({
                renderTo: document.body,
                liquidLayout: true,
                minHeight: 50
            });

            expect(c.el.isStyle('min-height', '50px')).toBe(true);
        });

        it("should set maxHeight as an inline style", function() {
            makeComponent({
                renderTo: document.body,
                liquidLayout: true,
                maxHeight: 50
            });

            expect(c.el.isStyle('max-height', '50px')).toBe(true);
        });

        describe("before render", function() {
            it("should set minWidth as an inline style", function() {
                makeComponent({
                    liquidLayout: true
                });

                c.setMinWidth(50);
                c.render(Ext.getBody());

                expect(c.el.isStyle('min-width', '50px')).toBe(true);
            });

            it("should set maxWidth as an inline style", function() {
                makeComponent({
                    liquidLayout: true
                });

                c.setMaxWidth(50);
                c.render(Ext.getBody());

                expect(c.el.isStyle('max-width', '50px')).toBe(true);
            });

            it("should set minHeight as an inline style", function() {
                makeComponent({
                    liquidLayout: true
                });

                c.setMinHeight(50);
                c.render(Ext.getBody());

                expect(c.el.isStyle('min-height', '50px')).toBe(true);
            });

            it("should set maxHeight as an inline style", function() {
                makeComponent({
                    liquidLayout: true
                });

                c.setMaxHeight(50);
                c.render(Ext.getBody());

                expect(c.el.isStyle('max-height', '50px')).toBe(true);
            });

            it("should remove minWidth", function() {
                makeComponent({
                    liquidLayout: true,
                    minWidth: 50
                });

                c.setMinWidth(null);
                c.render(Ext.getBody());

                expect(c.el.dom.style.minWidth).toBe('');
            });

            it("should remove maxWidth", function() {
                makeComponent({
                    liquidLayout: true,
                    maxWidth: 50
                });

                c.setMaxWidth(null);
                c.render(Ext.getBody());

                expect(c.el.dom.style.maxWidth).toBe('');
            });

            it("should remove minHeight", function() {
                makeComponent({
                    liquidLayout: true,
                    minHeight: 50
                });

                c.setMinHeight(null);
                c.render(Ext.getBody());

                expect(c.el.dom.style.minHeight).toBe('');
            });

            it("should remove maxHeight", function() {
                makeComponent({
                    liquidLayout: true,
                    maxHeight: 50
                });

                c.setMaxHeight(null);
                c.render(Ext.getBody());

                expect(c.el.dom.style.maxHeight).toBe('');
            });
        });

        describe("after initial render", function() {
            it("should set minWidth as an inline style", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true
                });

                c.setMinWidth(50);

                expect(c.el.isStyle('min-width', '50px')).toBe(true);
            });

            it("should set maxWidth as an inline style", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true
                });

                c.setMaxWidth(50);

                expect(c.el.isStyle('max-width', '50px')).toBe(true);
            });

            it("should set minHeight as an inline style", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true
                });

                c.setMinHeight(50);

                expect(c.el.isStyle('min-height', '50px')).toBe(true);
            });

            it("should set maxHeight as an inline style", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true
                });

                c.setMaxHeight(50);

                expect(c.el.isStyle('max-height', '50px')).toBe(true);
            });

            it("should remove minWidth", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true,
                    minWidth: 50
                });

                c.setMinWidth(null);

                expect(c.el.dom.style.minWidth).toBe('');
            });

            it("should remove maxWidth", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true,
                    maxWidth: 50
                });

                c.setMaxWidth(null);

                expect(c.el.dom.style.maxWidth).toBe('');
            });

            it("should remove minHeight", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true,
                    minHeight: 50
                });

                c.setMinHeight(null);

                expect(c.el.dom.style.minHeight).toBe('');
            });

            it("should remove maxHeight", function() {
                makeComponent({
                    renderTo: document.body,
                    liquidLayout: true,
                    maxHeight: 50
                });

                c.setMaxHeight(null);

                expect(c.el.dom.style.maxHeight).toBe('');
            });
        });
    });

    describe("listeners", function() {
        var Foo, Bar, foo, bar, destroyer1, destroyer2,
            elHandler, childElHandler, barHandler;

        beforeEach(function() {
            elHandler = jasmine.createSpy();
            childElHandler = jasmine.createSpy();
            barHandler = jasmine.createSpy();

            Foo = Ext.define(null, {
                extend: 'Ext.Component',
                renderTo: document.body,
                childEls: ['childEl'],
                renderTpl: '<div id="{id}-childEl" data-ref="childEl"></div>'
            });

            Bar = Ext.define(null, {
                extend: 'Ext.util.Observable'
            });

            foo = new Foo();
            bar = foo.bar = new Bar();

            destroyer1 = foo.on({
                element: 'el',
                click: elHandler,
                destroyable: true
            });

            destroyer2 = foo.on({
                childEl: {
                    click: childElHandler
                },
                bar: {
                    baz: barHandler
                },
                destroyable: true
            });
        });

        afterEach(function() {
            foo.destroy();
        });

        it("should add an element listener", function() {
            jasmine.fireMouseEvent(foo.getEl(), 'click');

            // Ensure handler only got called once
            // https://sencha.jira.com/browse/EXTJS-13997
            expect(elHandler.callCount).toBe(1);
        });

        it("should add an element listener defined using element name as a property of the options object", function() {
            jasmine.fireMouseEvent(foo.childEl, 'click');
            expect(childElHandler.callCount).toBe(1);
        });

        it("should add a listener to an arbitrary observable as a property of the options object", function() {
            // this one is really creepy... supports syntax like:
            //
            // grid.on({
            //     store: {
            //         load: fn
            //     }
            // });

            bar.fireEvent('baz');
            expect(barHandler.callCount).toBe(1);
        });

        it("should remove an element listener", function() {
            foo.el.un({
                click: elHandler
            });

            jasmine.fireMouseEvent(foo.getEl(), 'click');
            expect(elHandler.callCount).toBe(0);
        });

        // TODO: make this work
        xdescribe("removal using a destroyer", function() {
            it("should remove an element listener", function() {
                destroyer1.destroy();
                jasmine.fireMouseEvent(foo.getEl(), 'click');
                expect(elHandler.callCount).toBe(0);
            });

            it("should remove an element listener defined using element name as a property of the options object", function() {
                destroyer2.destroy();
                jasmine.fireMouseEvent(foo.childEl, 'click');
                expect(childElHandler.callCount).toBe(0);
            });

            it("should remove a listener from an arbitrary observable as a property of the options object", function() {
                destroyer2.destroy();
                bar.fireEvent('baz');
                expect(barHandler.callCount).toBe(0);
            });
        });

        (Ext.isIE9m ? xdescribe : describe)("element event options", function() {
            it("should add capture and non-delegated element listeners", function() {
                // The purpose of this this spec is to ensure that we pass the proper
                // event options along to the element when we attach an event listener using
                // the element event option.
                var result = [];

                foo.on({
                    element: 'el',
                    mousedown: function() {
                        result.push('p');
                    }
                });

                foo.on({
                    element: 'el',
                    mousedown: function() {
                        result.push('pc');
                    },
                    capture: true
                });

                foo.on({
                    element: 'el',
                    mousedown: function() {
                        result.push('pd');
                    },
                    delegated: false
                });

                foo.on({
                    element: 'el',
                    mousedown: function() {
                        result.push('pdc');
                    },
                    delegated: false,
                    capture: true
                });

                foo.on({
                    element: 'childEl',
                    mousedown: function() {
                        result.push('c');
                    }
                });

                foo.on({
                    element: 'childEl',
                    mousedown: function() {
                        result.push('cc');
                    },
                    capture: true
                });

                foo.on({
                    element: 'childEl',
                    mousedown: function() {
                        result.push('cdc');
                    },
                    delegated: false,
                    capture: true
                });

                foo.on({
                    element: 'childEl',
                    mousedown: function() {
                        result.push('cd');
                    },
                    delegated: false
                });

                jasmine.fireMouseEvent(foo.childEl, 'mousedown');

                expect(result).toEqual(['pdc', 'cdc', 'cd', 'pd', 'pc', 'cc', 'c', 'p']);

                // Finish off active gestures
                jasmine.fireMouseEvent(foo.childEl, 'mouseup');
            });

            it("should allow element options to be used as event names", function() {
                var translateFn = jasmine.createSpy(),
                    captureFn = jasmine.createSpy(),
                    delegatedFn = jasmine.createSpy(),
                    stopEventFn = jasmine.createSpy(),
                    preventDefaultFn = jasmine.createSpy(),
                    stopPropagationFn = jasmine.createSpy();

                c = new Ext.Component();

                c.on({
                    translate: translateFn,
                    capture: captureFn,
                    delegated: delegatedFn,
                    stopEvent: stopEventFn,
                    preventDefault: preventDefaultFn,
                    stopPropagation: stopPropagationFn
                });

                c.fireEvent('translate');
                c.fireEvent('capture');
                c.fireEvent('delegated');
                c.fireEvent('stopEvent');
                c.fireEvent('preventDefault');
                c.fireEvent('stopPropagation');

                expect(translateFn).toHaveBeenCalled();
                expect(captureFn).toHaveBeenCalled();
                expect(delegatedFn).toHaveBeenCalled();
                expect(stopEventFn).toHaveBeenCalled();
                expect(preventDefaultFn).toHaveBeenCalled();
                expect(stopPropagationFn).toHaveBeenCalled();
            });
        });

        describe("the delegate event option", function() {
            var handler, container, result;

            beforeEach(function() {
                handler = jasmine.createSpy().andCallFake(function(cmp) {
                    result.push(cmp.id);
                });
                result = [];
                container = Ext.create({
                    xtype: 'container',
                    id: 'parentContainer',
                    items: [{
                        xtype: 'button',
                        cls: 'btn',
                        id: 'foo'
                    }, {
                        xtype: 'container',
                        cls: 'cont',
                        id: 'bar',
                        items: [{
                            xtype: 'button',
                            cls: 'btn',
                            id: 'myBtn'
                        }, {
                            xtype: 'component',
                            cls: 'comp',
                            id: 'myCmp'
                        }, {
                            xtype: 'textfield',
                            cls: 'field',
                            id: 'myField'
                        }]
                    }, {
                        xtype: 'textfield',
                        cls: 'field',
                        id: 'baz'
                    }, {
                        xtype: 'widget',
                        cls: 'widget',
                        id: 'myWidget'
                    }]
                });
            });

            afterEach(function() {
                container = Ext.destroy(container);
            });

            it("should not be case sensitive", function() {
                container.on({
                    rEnDeR: handler,
                    delegate: '> button'
                });

                container.render(document.body);

                expect(result).toEqual(['foo']);
            });

            it("should listen on direct children by xtype", function() {
                container.on({
                    render: handler,
                    delegate: '> button'
                });

                container.render(document.body);

                expect(result).toEqual(['foo']);
            });

            it("should listen on descendants by xtype", function() {
                container.on({
                    render: handler,
                    delegate: 'button'
                });

                container.render(document.body);

                expect(result).toEqual(['foo', 'myBtn']);
            });

            it("should listen on a direct child by id", function() {
                container.on({
                    render: handler,
                    delegate: '> #baz'
                });

                container.render(document.body);

                expect(result).toEqual(['baz']);
            });

            it("should listen on a descendant by id", function() {
                container.on({
                    render: handler,
                    delegate: '#myCmp'
                });

                container.render(document.body);

                expect(result).toEqual(['myCmp']);
            });

            it("should listen on direct children by attribute value", function() {
                container.on({
                    render: handler,
                    delegate: '> [cls="field"]'
                });

                container.render(document.body);

                expect(result).toEqual(['baz']);
            });

            it("should listen on a descendant by attribute value", function() {
                container.on({
                    render: handler,
                    delegate: '[cls="field"]'
                });

                container.render(document.body);

                expect(result).toEqual(['myField', 'baz']);
            });

            it("should listen on descendant widgets", function() {
                container.on({
                    fubar: handler,
                    delegate: 'widget'
                });

                Ext.getCmp('myWidget').fireEvent('fubar');
                expect(handler.callCount).toBe(1);
                Ext.getCmp('myCmp').fireEvent('fubar');
                expect(handler.callCount).toBe(1);
            });

            it("should increment Ext.Component class-level hasListeners", function() {
                expect(Ext.Component.hasListeners.derp).toBeFalsy();

                container.on({
                    derp: handler,
                    delegate: 'merp'
                });

                expect(Ext.Component.hasListeners.derp).toBe(1);
            });

            it("should call delegate listeners in bottom up hierarchy order", function() {
                function handler(cmp) {
                    result.push(this.id + ' ' + cmp.id);
                }

                container.on({
                    render: handler,
                    delegate: '#myCmp'
                });

                container.items.getAt(1).on({
                    render: handler,
                    delegate: '#myCmp'
                });

                container.render(document.body);

                expect(result).toEqual(['bar myCmp', 'parentContainer myCmp']);
            });

            it("should be able to have a delegated listener when the container has an itemId", function() {
                var spy = jasmine.createSpy();

                container.destroy();
                container = new Ext.container.Container({
                    itemId: 'foo',
                    items: {
                        xtype: 'component',
                        itemId: 'aChild'
                    },
                    listeners: {
                        someevent: spy,
                        delegate: '#aChild'
                    }
                });

                container.items.first().fireEvent('someevent');
                expect(spy.callCount).toBe(1);
            });

            describe("removal", function() {
                it("should remove a delegated listener", function() {
                    var handler = jasmine.createSpy(),
                        cmp = Ext.getCmp('myCmp');

                    container.on({
                        derp: handler,
                        delegate: '#myCmp'
                    });

                    cmp.fireEvent('derp');

                    expect(handler.callCount).toBe(1);

                    container.un('derp', handler);
                    cmp.fireEvent('derp');

                    expect(handler.callCount).toBe(1);
                });

                it("should remove all delegated listeners using clearListeners", function() {
                    var derpHandler = jasmine.createSpy(),
                        merpHandler = jasmine.createSpy(),
                        cmp = Ext.getCmp('myCmp');

                    container.on({
                        derp: derpHandler,
                        merp: merpHandler,
                        delegate: '#myCmp'
                    });

                    cmp.fireEvent('derp');
                    cmp.fireEvent('merp');

                    expect(derpHandler.callCount).toBe(1);
                    expect(merpHandler.callCount).toBe(1);

                    container.clearListeners();
                    cmp.fireEvent('derp');
                    cmp.fireEvent('merp');

                    expect(derpHandler.callCount).toBe(1);
                    expect(merpHandler.callCount).toBe(1);
                });

                it("should decrement class-level hasListeners", function() {
                    container.on({
                        derp: handler,
                        delegate: 'merp'
                    });

                    expect(Ext.Component.hasListeners.derp).toBe(1);

                    container.un('derp', handler);

                    expect(Ext.Component.hasListeners.derp).toBeFalsy();
                });

                it("should decrement class-level hasListeners when clearListeners is called", function() {
                    var derpHandler = jasmine.createSpy(),
                        merpHandler = jasmine.createSpy();

                    container.on({
                        derp: derpHandler,
                        merp: merpHandler,
                        delegate: '#myCmp'
                    });

                    // also add the same listeners to another container to make sure
                    // we are correctly decrementing (vs resetting) hasListeners
                    container.items.getAt(1).on({
                        derp: derpHandler,
                        merp: merpHandler,
                        delegate: '#myCmp'
                    });

                    expect(Ext.Component.hasListeners.derp).toBe(2);
                    expect(Ext.Component.hasListeners.merp).toBe(2);

                    container.clearListeners();

                    expect(Ext.Component.hasListeners.derp).toBe(1);
                    expect(Ext.Component.hasListeners.merp).toBe(1);
                });

                it("should remove a delegated listener using destroyable", function() {
                    var handler = jasmine.createSpy(),
                        cmp = Ext.getCmp('myCmp'),
                        destroyable =   container.on({
                            derp: handler,
                            delegate: '#myCmp',
                            destroyable: true
                        });

                    cmp.fireEvent('derp', cmp);

                    expect(handler.callCount).toBe(1);

                    destroyable.destroy();
                    cmp.fireEvent('derp');

                    expect(handler.callCount).toBe(1);
                });
            });

            describe("with other event options", function() {
                it("should attach a delegate listener with the scope option", function() {
                    var myScope = {};

                    container.on({
                        render: handler,
                        delegate: '#baz',
                        scope: myScope
                    });

                    container.render(document.body);

                    expect(handler.mostRecentCall.object).toBe(myScope);
                });

                it("should attach a delegate listener with the delay option", function() {
                    container.on({
                        render: handler,
                        delegate: '#baz',
                        delay: 20
                    });
                    var startTime = Ext.now();

                    container.render(document.body);

                    expect(handler).not.toHaveBeenCalled();

                    waitsFor(function() {
                        return handler.wasCalled;
                    });

                    runs(function() {
                        var elapsedTime = Ext.now() - startTime;

                        expect(elapsedTime >= 20).toBe(true);
                    });
                });

                it("should attach a delegate listener with the single option", function() {
                    container.on({
                        foo: handler,
                        delegate: '#baz',
                        single: true
                    });

                    expect(handler.callCount).toBe(0);
                    Ext.getCmp('baz').fireEvent('foo');
                    expect(handler.callCount).toBe(1);
                    Ext.getCmp('baz').fireEvent('foo');
                    expect(handler.callCount).toBe(1);
                });

                it("should attach a delegate listener with the buffer option", function() {
                    container.on({
                        foo: handler,
                        delegate: '#baz',
                        buffer: 20
                    });

                    expect(handler.callCount).toBe(0);
                    Ext.getCmp('baz').fireEvent('foo');
                    Ext.getCmp('baz').fireEvent('foo');

                    waitsFor(function() {
                        return handler.wasCalled;
                    });

                    runs(function() {
                        expect(handler.callCount).toBe(1);
                    });
                });

                it("should attach a delegate listener with the args option", function() {
                    var opts = {
                        foo: handler,
                        delegate: '#baz',
                        args: ['a', 'b']
                    };

                    container.on(opts);

                    Ext.getCmp('baz').fireEvent('foo', 'c');

                    expect(handler.mostRecentCall.args).toEqual(['a', 'b', 'c', opts]);
                });

                it("should attach delegate listeners with the priority option", function() {
                    var result = [];

                    container.on({
                        foo: function() {
                            result.push(5);
                        },
                        delegate: '#baz',
                        priority: 5
                    });

                    container.on({
                        foo: function() {
                            result.push(1);
                        },
                        delegate: '#baz',
                        priority: 1
                    });

                    container.on({
                        foo: function() {
                            result.push(10);
                        },
                        delegate: '#baz',
                        priority: 10
                    });

                    Ext.getCmp('baz').fireEvent('foo');

                    expect(result).toEqual([10, 5, 1]);
                });

                it("should attach delegate listeners with the order option", function() {
                    var result = [];

                    container.on({
                        foo: function() {
                            result.push('current');
                        },
                        delegate: '#baz',
                        order: 'current'
                    });

                    container.on({
                        foo: function() {
                            result.push('before');
                        },
                        delegate: '#baz',
                        order: 'before'
                    });

                    container.on({
                        foo: function() {
                            result.push('after');
                        },
                        delegate: '#baz',
                        order: 'after'
                    });

                    Ext.getCmp('baz').fireEvent('foo');

                    expect(result).toEqual(['before', 'current', 'after']);
                });

                it("should throw an error with the target option", function() {
                    expect(function() {
                        container.on({
                            foo: handler,
                            delegate: 'bar',
                            target: Ext.getCmp('baz')
                        });
                    }).toThrow("Cannot add 'foo' listener to component: 'parentContainer' - 'delegate' and 'target' event options are incompatible.");
                });

                it("should attach an element listener with the delegate option", function() {
                    container.on({
                        click: handler,
                        element: 'el',
                        delegate: '.comp'
                    });

                    container.render(document.body);

                    jasmine.fireMouseEvent(Ext.get('myBtn'), 'click');
                    expect(handler).not.toHaveBeenCalled();

                    jasmine.fireMouseEvent(Ext.get('myCmp'), 'click');
                    expect(handler).toHaveBeenCalled();
                });
            });
        });
    });

    describe("hideMode", function() {
        it("should default to 'display'", function() {
            makeComponent({
                renderTo: document.body
            });

            expect(c.hideMode).toBe('display');
            expect(c.getEl().getVisibilityMode()).toBe(Ext.Element.DISPLAY);
        });

        it("should set a visibility mode of 'DISPLAY' on the element", function() {
            makeComponent({
                renderTo: document.body,
                hideMode: 'display'
            });

            expect(c.getEl().getVisibilityMode()).toBe(Ext.Element.DISPLAY);
        });

        it("should set a visibility mode of 'VISIBILITY' on the element", function() {
            makeComponent({
                renderTo: document.body,
                hideMode: 'visibility'
            });

            expect(c.getEl().getVisibilityMode()).toBe(Ext.Element.VISIBILITY);
        });

        it("should set a visibility mode of 'OFFSETS' on the element", function() {
            makeComponent({
                renderTo: document.body,
                hideMode: 'offsets'
            });

            expect(c.getEl().getVisibilityMode()).toBe(Ext.Element.OFFSETS);
        });
    });

    describe('from', function() {
        var span;

        beforeEach(function() {
            span = document.createElement('span');
        });

        afterEach(function() {
            span = null;
        });

        it('should return null when a component cannot be found', function() {
            makeComponent({
                el: span,
                renderTo: document.body
            });

            expect(Component.from(span)).toBe(null);
        });

        it('should return the owner component when found', function() {
            makeComponent({
                autoEl: {
                    tag: 'blockquote',
                    html: 'A good idea is a good idea forever.'
                },
                renderTo: document.body
            });

            expect(Component.from(c.el.dom)).toBe(c);
        });

        describe('when the el is configured', function() {
            it('should work', function() {
                makeComponent({
                    el: span,
                    renderTo: document.body
                }, true);

                expect(Component.from(span)).toBe(c);
            });

            it('should find the component when the el is the document.body', function() {
                makeComponent({
                    plugins: 'viewport'
                });

                expect(Component.from(document.body)).toBe(c);
            });
        });
    });

    describe("ARIA attributes", function() {
        describe("static roles", function() {
            function createSuite(role) {
                describe(role, function() {
                    beforeEach(function() {
                        makeComponent({
                            ariaRole: role,
                            renderTo: Ext.getBody()
                        });
                    });

                    describe('aria-hidden', function() {
                        it("should not be present after render", function() {
                            expect(c).not.toHaveAttr('aria-hidden');
                        });

                        it("should not be present after hiding", function() {
                            c.hide();

                            expect(c).not.toHaveAttr('aria-hidden');
                        });

                        it("should not be present after showing", function() {
                            c.hide();
                            c.show();

                            expect(c).not.toHaveAttr('aria-hidden');
                        });
                    });

                    describe('aria-disabled', function() {
                        it("should not be present after render", function() {
                            expect(c).not.toHaveAttr('aria-disabled');
                        });

                        it("should not be present after disabling", function() {
                            c.disable();

                            expect(c).not.toHaveAttr('aria-disabled');
                        });

                        it("should not be present after enabling", function() {
                            c.disable();
                            c.enable();

                            expect(c).not.toHaveAttr('aria-disabled');
                        });
                    });
                });
            }

            createSuite(undefined);
            createSuite('presentation');
            createSuite('document');
        });

        describe("widget roles", function() {
            beforeEach(function() {
                makeComponent({
                    ariaRole: 'widget',
                    renderTo: Ext.getBody()
                });
            });

            describe("aria-hidden", function() {
                it("should be false after render", function() {
                    expect(c).toHaveAttr('aria-hidden', 'false');
                });

                it("should be true after hiding", function() {
                    c.hide();

                    expect(c).toHaveAttr('aria-hidden', 'true');
                });

                it("should be false again after showing", function() {
                    c.hide();
                    c.show();

                    expect(c).toHaveAttr('aria-hidden', 'false');
                });
            });

            describe("aria-disabled", function() {
                it("should be false after render", function() {
                    expect(c).toHaveAttr('aria-disabled', 'false');
                });

                it("should be true after disabling", function() {
                    c.disable();

                    expect(c).toHaveAttr('aria-disabled', 'true');
                });

                it("should be false again after enabling", function() {
                    c.disable();
                    c.enable();

                    expect(c).toHaveAttr('aria-disabled', 'false');
                });
            });
        });
    });

    (Ext.supports.TouchAction === 15 ? describe : xdescribe)("touchAction", function() {
        var Cmp, cmp;

        function makeCmpWithTouchAction(touchAction) {
            Cmp = Ext.define(null, {
                extend: 'Ext.Component',
                childEls: [ 'child' ],
                renderTpl: '<div id="{id}-child" data-ref="child">{%this.renderContent(out,values)%}</div>'
            });
            cmp = new Cmp({
                renderTo: Ext.getBody(),
                touchAction: touchAction
            });
        }

        function expectTouchAction(el, value) {
            // touch actions read from the dom are not always returned in the same order
            // as they were set, so we have to parse theme out.
            var expectedTouchAction = el.getStyle('touch-action').split(' '),
                actualTouchAction = value.split(' ');

            expect(actualTouchAction.length).toBe(expectedTouchAction.length);

            Ext.each(expectedTouchAction, function(item) {
                expect(Ext.Array.contains(actualTouchAction, item)).toBe(true);
            });
        }

        afterEach(function() {
            if (cmp) {
                cmp.destroy();
                cmp = null;
            }
        });

        it("should default to auto", function() {
            makeCmpWithTouchAction(null);

            expectTouchAction(cmp.el, 'auto');
            expectTouchAction(cmp.child, 'auto');
        });

        it("should disable panX", function() {
            makeCmpWithTouchAction({
                panX: false
            });

            expectTouchAction(cmp.el, 'pan-y pinch-zoom double-tap-zoom');
        });

        it("should disable panY", function() {
            makeCmpWithTouchAction({
                panY: false
            });

            expectTouchAction(cmp.el, 'pan-x pinch-zoom double-tap-zoom');
        });

        it("should disable panX and panY", function() {
            makeCmpWithTouchAction({
                panX: false,
                panY: false
            });

            expectTouchAction(cmp.el, 'pinch-zoom double-tap-zoom');
        });

        it("should disable pinchZoom", function() {
            makeCmpWithTouchAction({
                pinchZoom: false
            });

            expectTouchAction(cmp.el, 'pan-x pan-y double-tap-zoom');
        });

        it("should disable panX and pinchZoom", function() {
            makeCmpWithTouchAction({
                panX: false,
                pinchZoom: false
            });

            expectTouchAction(cmp.el, 'pan-y double-tap-zoom');
        });

        it("should disable panY and pinchZoom", function() {
            makeCmpWithTouchAction({
                panY: false,
                pinchZoom: false
            });

            expectTouchAction(cmp.el, 'pan-x double-tap-zoom');
        });

        it("should disable panX, panY, and PinchZoom", function() {
            makeCmpWithTouchAction({
                panX: false,
                panY: false,
                pinchZoom: false
            });

            expectTouchAction(cmp.el, 'double-tap-zoom');
        });

        it("should disable doubleTapZoom", function() {
            makeCmpWithTouchAction({
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pan-x pan-y pinch-zoom');
        });

        it("should disable panX and doubleTapZoom", function() {
            makeCmpWithTouchAction({
                panX: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pan-y pinch-zoom');
        });

        it("should disable panY and doubleTapZoom", function() {
            makeCmpWithTouchAction({
                panY: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pan-x pinch-zoom');
        });

        it("should disable panX, panY, and doubleTapZoom", function() {
            makeCmpWithTouchAction({
                panX: false,
                panY: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pinch-zoom');
        });

        it("should disable pinchZoom and doubleTapZoom", function() {
            makeCmpWithTouchAction({
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pan-x pan-y');
        });

        it("should disable panX, pinchZoom, and doubleTapZoom", function() {
            makeCmpWithTouchAction({
                panX: false,
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pan-y');
        });

        it("should disable panY, pinchZoom, and doubleTapZoom", function() {
            makeCmpWithTouchAction({
                panY: false,
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'pan-x');
        });

        it("should disable all touch actions", function() {
            makeCmpWithTouchAction({
                panX: false,
                panY: false,
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(cmp.el, 'none');
        });

        it("should set touch action on a child element", function() {
            makeCmpWithTouchAction({
                panX: false,
                child: {
                    panY: false,
                    pinchZoom: false
                }
            });

            expectTouchAction(cmp.el, 'pan-y pinch-zoom double-tap-zoom');
            expectTouchAction(cmp.child, 'pan-x double-tap-zoom');
        });
    });
});
