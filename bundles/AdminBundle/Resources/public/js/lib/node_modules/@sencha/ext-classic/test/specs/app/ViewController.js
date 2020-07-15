topSuite("Ext.app.ViewController", ['Ext.app.ViewModel', 'Ext.Button', 'Ext.Container'], function() {
    var ct, controller, doInit, doBeforeInit;

    function makeContainer(cfg) {
        cfg = cfg || {};
        Ext.applyIf(cfg, {
            controller: 'test1'
        });
        ct = new Ext.container.Container(cfg);
        controller = ct.getController();
    }

    beforeEach(function() {
        Ext.define('spec.TestController1', {
            extend: 'Ext.app.ViewController',
            alias: 'controller.test1',

            beforeInit: function(view) {
                if (doBeforeInit) {
                    doBeforeInit(this);
                }
            },

            init: function(view) {
                if (doInit) {
                    doInit(this);
                }
            },

            method1: function() {},

            method2: function() {}
        });

        Ext.define('spec.TestController2', {
            extend: 'Ext.app.ViewController',
            alias: 'controller.test2',

            init: function() {},

            method1: function() {},

            method2: function() {}
        });

        Ext.define('spec.TestController3', {
            extend: 'Ext.app.ViewController',
            alias: 'controller.test3',

            method1: function() {},

            method2: function() {}
        });
    });

    afterEach(function() {
        Ext.destroy(ct);
        Ext.undefine('spec.TestController1');
        Ext.undefine('spec.TestController2');
        Ext.undefine('spec.TestController3');
        doBeforeInit = doInit = controller = ct = null;
    });

    describe("initializing", function() {
        it("should set the view on the controller", function() {
            makeContainer();
            expect(controller.getView()).toBe(ct);
        });

        it("should should call init once the container has initialized its items", function() {
            var count = 0;

            doInit = function(ctrl) {
                count = ctrl.getView().items.getCount();
            };

            makeContainer({
                items: {
                    xtype: 'component'
                }
            });
            expect(count).toBe(1);
        });

        it("should call the init method and pass the component", function() {
            var ctrl = new spec.TestController2();

            spyOn(ctrl, 'init');
            var c = new Ext.Component({
                controller: ctrl
            });

            expect(ctrl.init).toHaveBeenCalledWith(c);
            Ext.destroy(c);
        });

        it("should call the beforeInit method before initComponent of the component", function() {
            var called = false,
                wasCalled;

            var C = Ext.define(null, {
                extend: 'Ext.Component',

                initComponent: function() {
                    called = true;
                    this.callParent();
                }
            });

            doBeforeInit = function(ctrl) {
                wasCalled = !!called;
            };

            var c = new C({
                controller: {
                    type: 'test1'
                }
            });

            c.destroy();
        });

        it("should call the beforeInit method and pass the component", function() {
            var ctrl = new spec.TestController2();

            spyOn(ctrl, 'beforeInit');
            var c = new Ext.Component({
                controller: ctrl
            });

            expect(ctrl.beforeInit).toHaveBeenCalledWith(c);
            c.destroy();
        });
    });

    describe("template methods", function() {
        describe("initViewModel", function() {
            it("should not get called if there is no viewModel", function() {
                var ctrl = new spec.TestController1(),
                    spy = spyOn(ctrl, 'initViewModel');

                makeContainer({
                    controller: ctrl
                });
                // Force VM creation
                ct.getViewModel();
                expect(spy).not.toHaveBeenCalled();
            });

            it("should get called with the view model as an argument", function() {
                var ctrl = new spec.TestController1(),
                    vm = new Ext.app.ViewModel(),
                    spy = spyOn(ctrl, 'initViewModel');

                makeContainer({
                    controller: ctrl,
                    viewModel: vm
                });
                // Force VM creation
                ct.getViewModel();
                expect(spy).toHaveBeenCalledWith(vm);
            });

            it("should be able to call getViewModel", function() {
                var ctrl = new spec.TestController1(),
                    vm = new Ext.app.ViewModel(),
                    spy = spyOn(ctrl, 'initViewModel').andCallFake(function() {
                        result = this.getViewModel();
                    }),
                    result;

                makeContainer({
                    controller: ctrl,
                    viewModel: vm
                });

                // Force VM creation
                ct.getViewModel();
                expect(result).toBe(vm);
            });
        });
    });

    describe("bindings", function() {
        function defineBindController(bindings) {
            Ext.define('spec.TestController4', {
                extend: 'Ext.app.ViewController',
                alias: 'controller.test4',

                bindings: bindings,

                method1: Ext.emptyFn,
                method2: Ext.emptyFn
            });
        }

        afterEach(function() {
            Ext.undefine('spec.TestController4');
        });

        it("should bind to a viewmodel directly on the view", function() {
            defineBindController({
                method1: '{x}'
            });

            var ctrl = new spec.TestController4(),
                vm = new Ext.app.ViewModel();

            spyOn(ctrl, 'method1');

            makeContainer({
                renderTo: Ext.getBody(),
                controller: ctrl,
                viewModel: vm
            });

            expect(ctrl.method1).not.toHaveBeenCalled();
            vm.set('x', 100);
            vm.notify();
            expect(ctrl.method1.callCount).toBe(1);
        });

        it("should bind to a viewmodel above the view", function() {
            defineBindController({
                method1: '{x}'
            });

            var ctrl = new spec.TestController4(),
                vm = new Ext.app.ViewModel();

            spyOn(ctrl, 'method1');

            makeContainer({
                renderTo: Ext.getBody(),
                viewModel: vm,
                items: {
                    xtype: 'container',
                    controller: ctrl
                }
            });

            vm.set('x', 200);
            vm.notify();
            expect(ctrl.method1.callCount).toBe(1);
        });

        it("should bind to an object bind", function() {
            defineBindController({
                method1: {
                    x: '{x}',
                    y: '{y}'
                }
            });

            var ctrl = new spec.TestController4(),
                vm = new Ext.app.ViewModel();

            spyOn(ctrl, 'method1');

            makeContainer({
                renderTo: Ext.getBody(),
                viewModel: vm,
                items: {
                    xtype: 'container',
                    controller: ctrl
                }
            });

            vm.set('x', 200);
            vm.set('y', 300);
            vm.notify();
            expect(ctrl.method1.callCount).toBe(1);
        });

        it("should be able to have multiple bindings", function() {
            defineBindController({
                method1: '{x}',
                method2: '{y}'
            });

            var ctrl = new spec.TestController4(),
                vm = new Ext.app.ViewModel();

            spyOn(ctrl, 'method1');
            spyOn(ctrl, 'method2');

            makeContainer({
                renderTo: Ext.getBody(),
                controller: ctrl,
                viewModel: vm
            });

            vm.set('x', 200);
            vm.set('y', 300);
            vm.notify();
            expect(ctrl.method1.callCount).toBe(1);
            expect(ctrl.method2.callCount).toBe(1);
        });

        it("should destroy bindings along with the controller", function() {
            defineBindController({
                method1: '{x}'
            });

            var ctrl = new spec.TestController4(),
                vm = new Ext.app.ViewModel();

            spyOn(ctrl, 'method1');

            makeContainer({
                renderTo: Ext.getBody(),
                viewModel: vm,
                items: {
                    xtype: 'container',
                    controller: ctrl
                }
            });

            vm.set('x', 1);
            vm.notify();
            expect(ctrl.method1.callCount).toBe(1);
            ctrl.method1.reset();

            ct.items.first().destroy();

            vm.set('x', 1);
            vm.notify();
            expect(ctrl.method1).not.toHaveBeenCalled();
        });
    });

    describe("references", function() {
        it("should get the same reference as the view", function() {
            makeContainer({
                items: {
                    xtype: 'component',
                    itemId: 'compA',
                    reference: 'a'
                }
            });
            var c = controller.lookupReference('a');

            expect(c).toBe(ct.down('#compA'));
        });
    });

    describe("accessing via the view", function() {
        it("should return null when the view has no view controller", function() {
            makeContainer();
            expect(controller.getViewModel()).toBeNull();
        });

        it("should return the view model of the view directly", function() {
            var vm = new Ext.app.ViewModel();

            makeContainer({
                viewModel: vm
            });
            expect(controller.getViewModel()).toBe(vm);
        });

        it("should return an inherited view model if not specified on the view", function() {
            var vm = new Ext.app.ViewModel();

            makeContainer({
                viewModel: vm,
                items: [{
                    xtype: 'container',
                    controller: 'test2'
                }]
            });
            expect(ct.items.first().getController().getViewModel()).toBe(vm);
        });
    });

    describe("getStore", function() {
        it("should return null when no named store exists on the view model", function() {
            makeContainer({
                viewModel: true,
                renderTo: Ext.getBody(),
                items: [{
                    xtype: 'container',
                    controller: 'test2'
                }]
            });
            expect(controller.getStore('users')).toBe(null);
        });

        it("should return null if there is no viewModel attached to the view", function() {
            makeContainer({
                renderTo: Ext.getBody(),
                items: [{
                    xtype: 'container',
                    controller: 'test2'
                }]
            });
            expect(ct.getViewModel()).toBeNull();
            expect(controller.getStore('users')).toBeNull();
        });

        it("should return the named store from the view model", function() {
            var vm = new Ext.app.ViewModel({
                stores: {
                    users: {
                        fields: ['name']
                    }
                }
            });

            makeContainer({
                renderTo: Ext.getBody(),
                viewModel: vm
            });
            expect(controller.getStore('users')).toBe(vm.getStore('users'));
        });
    });

    describe("getSession", function() {
        it("should return a session attached the view", function() {
            var session = new Ext.data.Session();

            makeContainer({
                renderTo: Ext.getBody(),
                session: session
            });
            expect(controller.getSession()).toBe(session);
        });

        it("should find a session higher in the hierarchy", function() {
            var controller = new Ext.app.ViewController(),
                session = new Ext.data.Session();

            makeContainer({
                controller: null,
                session: session,
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                controller: controller
                            }
                        }
                    }
                }
            });
            expect(controller.getSession()).toBe(session);
        });

        it("should return the closest session in the hierarchy", function() {
            var controller = new Ext.app.ViewController(),
                session1 = new Ext.data.Session(),
                session2 = new Ext.data.Session();

            makeContainer({
                controller: null,
                session: session1,
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        session: session2,
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                controller: controller
                            }
                        }
                    }
                }
            });
            expect(controller.getSession()).toBe(session2);
        });

        it("should return null when no session is attached to the view", function() {
            makeContainer({
                renderTo: Ext.getBody()
            });
            expect(controller.getSession()).toBeNull();
        });

        it("should return null when no session exists in the hierarchy", function() {
            var controller = new Ext.app.ViewController();

            makeContainer({
                controller: null,
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                controller: controller
                            }
                        }
                    }
                }
            });
            expect(controller.getSession()).toBeNull();
        });
    });

    describe("getViewModel", function() {
        it("should return a viewModel attached the view", function() {
            var vm = new Ext.app.ViewModel();

            makeContainer({
                renderTo: Ext.getBody(),
                viewModel: vm
            });
            expect(controller.getViewModel()).toBe(vm);
        });

        it("should find a view model higher in the hierarchy", function() {
            var controller = new Ext.app.ViewController(),
                vm = new Ext.app.ViewModel();

            makeContainer({
                controller: null,
                viewModel: vm,
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                controller: controller
                            }
                        }
                    }
                }
            });
            expect(controller.getViewModel()).toBe(vm);
        });

        it("should return the closest viewModel in the hierarchy", function() {
            var controller = new Ext.app.ViewController(),
                vm1 = new Ext.app.ViewModel(),
                vm2 = new Ext.app.ViewModel();

            makeContainer({
                controller: null,
                viewModel: vm1,
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        viewModel: vm2,
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                controller: controller
                            }
                        }
                    }
                }
            });
            expect(controller.getViewModel()).toBe(vm2);
        });

        it("should return null when no viewModel is attached to the view", function() {
            makeContainer({
                renderTo: Ext.getBody()
            });
            expect(controller.getViewModel()).toBeNull();
        });

        it("should return null when no viewModel exists in the hierarchy", function() {
            var controller = new Ext.app.ViewController();

            makeContainer({
                controller: null,
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                controller: controller
                            }
                        }
                    }
                }
            });
            expect(controller.getViewModel()).toBeNull();
        });
    });

    describe("listen", function() {
        it("should ensure any control listeners get scoped to the controller", function() {
            makeContainer({
                controller: {
                    type: 'test1',
                    listen: {
                        component: {
                            container: {
                                custom: 'method1'
                            }
                        }
                    }
                },
                items: {
                    xtype: 'container'
                }
            });

            spyOn(controller, 'method1');
            var other = new Ext.container.Container();

            other.fireEvent('custom');
            expect(controller.method1).not.toHaveBeenCalled();
            ct.items.first().fireEvent('custom');
            expect(controller.method1).toHaveBeenCalled();

            Ext.destroy(other);
        });
    });

    describe("listeners", function() {
        describe("direct events", function() {
            it("should call a method on the controller", function() {
                makeContainer({
                    items: [{
                        xtype: 'container',
                        listeners: {
                            custom: 'method1'
                        }
                    }]
                });
                spyOn(controller, 'method1');
                ct.items.first().fireEvent('custom');
                expect(controller.method1).toHaveBeenCalled();
            });

            it("should not call a method if events are suspended", function() {
                makeContainer({
                    items: [{
                        xtype: 'container',
                        listeners: {
                            custom: 'method1'
                        }
                    }]
                });
                spyOn(controller, 'method1');
                var c = ct.items.first();

                c.suspendEvents();
                c.fireEvent('custom');
                expect(controller.method1).not.toHaveBeenCalled();
            });

            it("should encapsulate events", function() {
                makeContainer({
                    renderTo: Ext.getBody(),
                    items: [{
                        xtype: 'container',
                        controller: 'test2',
                        items: [{
                            xtype: 'component',
                            listeners: {
                                custom: 'method1'
                            }
                        }]
                    }]
                });
                var child = ct.items.first().getController();

                spyOn(controller, 'method1');
                spyOn(child, 'method1');

                child.getView().items.first().fireEvent('custom');
                expect(child.method1).toHaveBeenCalled();
                expect(controller.method1).not.toHaveBeenCalled();
            });
        });

        describe("on the event bus", function() {
            describe("widgets", function() {
                beforeEach(function() {
                    Ext.define('spec.Foo', {
                        extend: 'Ext.Widget',
                        xtype: 'specfoo'
                    });
                });

                afterEach(function() {
                    Ext.undefine('spec.Foo');
                });

                it("should react to widgets", function() {
                    ct = new spec.Foo({
                        controller: {
                            type: 'test1',
                            control: {
                                specfoo: {
                                    custom: 'method1'
                                }
                            }
                        }
                    });
                    controller = ct.getController();
                    spyOn(controller, 'method1');
                    ct.fireEvent('custom');
                    expect(controller.method1).toHaveBeenCalled();
                });
            });

            it("should react to matching selectors", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    },
                    items: [{
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            itemId: 'a'
                        }
                    }]
                });

                var c = ct.down('#a');

                spyOn(controller, 'method1');
                c.fireEvent('custom');
                expect(controller.method1).toHaveBeenCalled();
            });

            it("should not react to non matching selectors", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    },
                    items: [{
                        xtype: 'container',
                        items: {
                            xtype: 'button',
                            itemId: 'a'
                        }
                    }]
                });

                var c = ct.down('#a');

                spyOn(controller, 'method1');
                c.fireEvent('custom');
                expect(controller.method1).not.toHaveBeenCalled();
            });

            it("should react to events on itself", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    }
                });
                spyOn(controller, 'method1');
                ct.fireEvent('custom');
                expect(controller.method1).toHaveBeenCalled();
            });

            it("should not react to events outside the hierarchy", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    }
                });
                spyOn(controller, 'method1');
                var other = new Ext.container.Container();

                other.fireEvent('custom');
                expect(controller.method1).not.toHaveBeenCalled();
                other.destroy();
            });

            it("should remove listeners when the controller is destroyed", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    },
                    items: {
                        xtype: 'container'
                    }
                });
                spyOn(controller, 'method1');
                controller.destroy();
                ct.items.first().fireEvent('custom');
                expect(controller.method1).not.toHaveBeenCalled();
            });

            it("should use the '#' selector to match the reference holder", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            '#': {
                                custom: 'method1'
                            }
                        }
                    },
                    items: {
                        xtype: 'component',
                        itemId: 'compA'
                    }
                });
                spyOn(controller, 'method1');
                ct.items.first().fireEvent('custom');
                expect(controller.method1).not.toHaveBeenCalled();
                ct.fireEvent('custom');
                expect(controller.method1).toHaveBeenCalled();
            });

            it("should not react if the controller gets destroyed during event firing", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            '#': {
                                custom: 'method1'
                            }
                        }
                    }
                });
                ct.on('custom', function() {
                    ct.destroy();
                });
                spyOn(controller, 'method1');
                ct.fireEvent('custom');
                expect(controller.method1).not.toHaveBeenCalled();
            });

            describe("hierarchy", function() {
                var makeController = function(i, control) {
                    return {
                        type: 'test' + i,
                        control: control || {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    };
                };

                it("should fire matched events up the hierarchy", function() {
                    makeContainer({
                        controller: makeController(1),
                        items: {
                            xtype: 'container',
                            controller: makeController(2),
                            items: {
                                xtype: 'container',
                                controller: makeController(3),
                                items: {
                                    xtype: 'container',
                                    itemId: 'compA'
                                }
                            }
                        }
                    });

                    var inner = ct.down('#compA'),
                        ctrl3 = inner.up().getController(),
                        ctrl2 = inner.up().up().getController(),
                        ctrl1 = inner.up().up().up().getController(),
                        values = [],
                        push = function() {
                            values.push(this.type);
                        };

                    spyOn(ctrl1, 'method1').andCallFake(push);
                    spyOn(ctrl2, 'method1').andCallFake(push);
                    spyOn(ctrl3, 'method1').andCallFake(push);
                    inner.fireEvent('custom');
                    expect(values).toEqual(['test3', 'test2', 'test1']);
                });

                it("should fire parents even if the deepest child doesn't match", function() {
                    makeContainer({
                        controller: makeController(1),
                        items: {
                            xtype: 'container',
                            controller: makeController(2),
                            items: {
                                xtype: 'container',
                                controller: makeController(3, {}),
                                items: {
                                    xtype: 'container',
                                    itemId: 'compA'
                                }
                            }
                        }
                    });
                    var inner = ct.down('#compA'),
                        ctrl3 = inner.up().getController(),
                        ctrl2 = inner.up().up().getController(),
                        ctrl1 = inner.up().up().up().getController();

                    spyOn(ctrl1, 'method1');
                    spyOn(ctrl2, 'method1');
                    spyOn(ctrl3, 'method1');
                    inner.fireEvent('custom');
                    expect(ctrl1.method1).toHaveBeenCalled();
                    expect(ctrl2.method1).toHaveBeenCalled();
                    expect(ctrl3.method1).not.toHaveBeenCalled();
                });

                it("should be able to continue up when a controller in the hierarchy doesn't match", function() {
                    makeContainer({
                        controller: makeController(1),
                        items: {
                            xtype: 'container',
                            controller: makeController(2, {}),
                            items: {
                                xtype: 'container',
                                controller: makeController(3),
                                items: {
                                    xtype: 'container',
                                    itemId: 'compA'
                                }
                            }
                        }
                    });
                    var inner = ct.down('#compA'),
                        ctrl3 = inner.up().getController(),
                        ctrl2 = inner.up().up().getController(),
                        ctrl1 = inner.up().up().up().getController();

                    spyOn(ctrl1, 'method1');
                    spyOn(ctrl2, 'method1');
                    spyOn(ctrl3, 'method1');
                    inner.fireEvent('custom');
                    expect(ctrl1.method1).toHaveBeenCalled();
                    expect(ctrl2.method1).not.toHaveBeenCalled();
                    expect(ctrl3.method1).toHaveBeenCalled();
                });

                it("should not fire parent events if a lower event returns false", function() {
                    makeContainer({
                        controller: makeController(1),
                        items: {
                            xtype: 'container',
                            controller: makeController(2),
                            items: {
                                xtype: 'container',
                                controller: makeController(3),
                                items: {
                                    xtype: 'container',
                                    itemId: 'compA'
                                }
                            }
                        }
                    });
                    var inner = ct.down('#compA'),
                        ctrl3 = inner.up().getController(),
                        ctrl2 = inner.up().up().getController(),
                        ctrl1 = inner.up().up().up().getController();

                    spyOn(ctrl1, 'method1');
                    spyOn(ctrl2, 'method1');
                    spyOn(ctrl3, 'method1').andReturn(false);
                    inner.fireEvent('custom');
                    expect(ctrl1.method1).not.toHaveBeenCalled();
                    expect(ctrl2.method1).not.toHaveBeenCalled();
                    expect(ctrl3.method1).toHaveBeenCalled();
                });
            });
        });

        describe("mixture of both", function() {
            it("should fire direct events first", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    },
                    items: {
                        xtype: 'container',
                        listeners: {
                           custom: 'method2'
                        }
                    }
                });

                var c = ct.items.first(),
                    ctrl = ct.getController(),
                    values = [];

                spyOn(ctrl, 'method1').andCallFake(function() {
                    values.push(1);
                });
                spyOn(ctrl, 'method2').andCallFake(function() {
                    values.push(2);
                });

                c.fireEvent('custom');
                expect(values).toEqual([2, 1]);
            });

            it("should not fire bus events if direct handlers return false", function() {
                makeContainer({
                    controller: {
                        type: 'test1',
                        control: {
                            'container': {
                                custom: 'method1'
                            }
                        }
                    },
                    items: {
                        xtype: 'container',
                        listeners: {
                           custom: 'method2'
                        }
                    }
                });

                var c = ct.items.first(),
                    ctrl = ct.getController();

                spyOn(ctrl, 'method1');
                spyOn(ctrl, 'method2').andReturn(false);

                c.fireEvent('custom');
                expect(ctrl.method1).not.toHaveBeenCalled();
            });
        });
    });

    describe("fireViewEvent", function() {
        it("view should be first argument", function() {
            makeContainer({
                controller: {
                    type: 'test1',
                    control: {
                        '#': {
                            custom: 'method1'
                        }
                    }
                }
            });

            spyOn(controller, 'method1');

            controller.fireViewEvent('custom', 'foo');

            expect(controller.method1).toHaveBeenCalled();
            expect(controller.method1.mostRecentCall.args[0]).toEqual(ct);
        });

        it("view should not add view as first argument", function() {
            makeContainer({
                controller: {
                    type: 'test1',
                    control: {
                        '#': {
                            custom: 'method1'
                        }
                    }
                }
            });

            spyOn(controller, 'method1');

            controller.fireViewEvent('custom', ct, 'foo');

            expect(controller.method1).toHaveBeenCalled();
            expect(controller.method1.mostRecentCall.args[0]).toEqual(ct);
        });
    });
});
