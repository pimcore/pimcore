/* global TestController */

topSuite("Ext.app.Controller", ['Ext.app.Application', 'Ext.Panel'], function() {
    var panelEventFired = false,
        customEventFired = false,
        Controller = Ext.app.Controller,
        Class, fooPanel, bazPanelId, ctrl;

    beforeEach(function() {
        this.addMatchers({
            toBeFunction: function(expected) {
                var actual = this.actual;

                /* eslint-disable multiline-ternary */
                return expected ? Ext.isFunction(actual) && actual === expected
                     :            Ext.isFunction(actual)
                     ;
                /* eslint-enable multiline-ternary */
            }
        });

        Ext.define('TestController.view.FooPanel', {
            extend: 'Ext.panel.Panel',
            xtype: 'foopanel'
        });

        Ext.define('TestController.view.BarPanel', {
            extend: 'Ext.panel.Panel',
            xtype: 'barpanel'
        });

        Ext.define('TestController.view.BazPanel', {
            extend: 'Ext.panel.Panel',
            xtype: 'bazpanel'
        });

        Ext.define('TestController.model.Test', {
            extend: 'Ext.data.Model',

            fields: []
        });

        Ext.define('TestController.store.Test', {
            extend: 'Ext.data.Store',

            model: 'TestController.model.Test'
        });

        Ext.define("TestController.controller.Events", {
            extend: 'Ext.app.Controller',

            init: function() {
                this.control({
                    'foopanel': {
                        resize: this.onPanelFooResize,
                        UPPERCUSTOM: this.onCustomEvent
                    }
                });
            },

            onPanelFooResize: function() {
                panelEventFired = true;
            },

            onCustomEvent: function() {
                customEventFired = true;
            }
        });

        fooPanel = new TestController.view.FooPanel({
            itemId: 'fooPanel',
            prop: 'foo',

            width: 100,
            height: 100,

            renderTo: document.body
        });
    });

    afterEach(function() {
        Ext.app.clearNamespaces();

        if (fooPanel) {
            fooPanel.destroy();
        }

        ctrl = null;

        if (Ext.isIE8) {
            window.TestController = undefined;
            window.NonexistingNamespace = undefined;
            window.AnotherNonexistingNamespace = undefined;
            window.YetAnotherNonexistingNamespace = undefined;
        }
        else {
            delete window.TestController;
            delete window.NonexistingNamespace;
            delete window.AnotherNonexistingNamespace;
            delete window.YetAnotherNonexistingNamespace;
        }

        Ext.undefine('TestController.view.FooPanel');
        Ext.undefine('TestController.view.BarPanel');
        Ext.undefine('TestController.view.BazPanel');
        Ext.undefine('TestController.model.Test');
        Ext.undefine('TestController.store.Test');
        Ext.undefine('TestController.controller.Events');

        Ext.data.Model.schema.clear();
    });

    describe("handles namespaces:", function() {
        beforeEach(function() {
            spyOn(Ext.log, 'warn'); // Silence console warnings, they're pointless in unit tests
        });

        it("resolves class name from Model@Name.space", function() {
            var names = Controller.getFullName('Model@Name.space.foo', 'model', 'Nonexisting');

            expect(names).toEqual({
                absoluteName: 'Name.space.foo.Model',
                shortName: 'Model'
            });
        });

        it("resolves class name from space.Model@Name", function() {
            var names = Controller.getFullName('foo.Model@Name.space', 'model', 'Nonexisting');

            expect(names).toEqual({
                absoluteName: 'Name.space.foo.Model',
                shortName: 'foo.Model'
            });
        });

        it("kinda resolves class name when it's already defined", function() {
            Ext.define('TestController.DefinedModel', {},

            function() {
                var names = Controller.getFullName('TestController.DefinedModel', 'model', 'TestController');

                expect(names).toEqual({
                    absoluteName: 'TestController.DefinedModel',
                    shortName: 'TestController.DefinedModel'
                });
            });
        });

        it("resolves non-dotted class name when namespace and kind are provided", function() {
            var names = Controller.getFullName('StoreNotLoadedYet', 'store', 'TestController');

            expect(names).toEqual({
                absoluteName: 'TestController.store.StoreNotLoadedYet',
                shortName: 'StoreNotLoadedYet'
            });
        });

        it("resolves dotted class name when namespace and kind are provided", function() {
            var names = Controller.getFullName('Dotted.Foo', 'view', 'TestController');

            expect(names).toEqual({
                absoluteName: 'TestController.view.Dotted.Foo',
                shortName: 'Dotted.Foo'
            });
        });

        it("falls back to assuming class is fully qualified when there's no other choice", function() {
            var names = Controller.getFullName('Some.bogus.Class', 'view', undefined);

            expect(names).toEqual({
                absoluteName: 'Some.bogus.Class',
                shortName: 'Some.bogus.Class'
            });
        });

        it("resolves modules when namespace is deduced from class name", function() {
            runs(function() {
                spyOn(Ext.Loader, 'require').andReturn();

                Class = Ext.define("TestController.controller.Single", {
                    extend: 'Ext.app.Controller',

                    models: 'Foo',
                    views: 'Foo',
                    stores: 'Foo',
                    controllers: 'Bar'
                });
            });

            waits(50);

            runs(function() {
                var args = Ext.Loader.require.argsForCall[0][0];

                expect(args).toEqual([
                    'TestController.model.Foo',
                    'TestController.view.Foo',
                    'TestController.store.Foo',
                    'TestController.controller.Bar'
                ]);
            });
        });

        it("creates correct getter for Model Foo", function() {
            expect(Class.prototype.getFooModel).toBeFunction();
        });

        it("creates correct getter for View Foo", function() {
            expect(Class.prototype.getFooView).toBeFunction();
        });

        it("creates correct getter for Store Foo", function() {
            expect(Class.prototype.getFooStore).toBeFunction();
        });

        it("creates correct getter for Controller Bar", function() {
            expect(Class.prototype.getBarController).toBeFunction();
        });

        it("resolves modules when namespace is set via Ext.app.addNamespaces", function() {
            runs(function() {
                Ext.app.addNamespaces('TestController');

                spyOn(Ext.Loader, 'require').andReturn();

                Class = Ext.define("TestController.Nonconforming.Class", {
                    extend: 'Ext.app.Controller',

                    models: [ 'Bar' ],
                    views: [ 'Bar' ],
                    stores: [ 'Bar' ],
                    controllers: [ 'Baz' ]
                });
            });

            waits(50);

            runs(function() {
                var args = Ext.Loader.require.argsForCall[0][0];

                expect(args).toEqual([
                    'TestController.model.Bar',
                    'TestController.view.Bar',
                    'TestController.store.Bar',
                    'TestController.controller.Baz'
                ]);

                Ext.app.clearNamespaces();
            });
        });

        it("creates correct getter for Model Bar", function() {
            expect(Class.prototype.getBarModel).toBeFunction();
        });

        it("creates correct getter for View Bar", function() {
            expect(Class.prototype.getBarView).toBeFunction();
        });

        it("creates correct getter for Store Bar", function() {
            expect(Class.prototype.getBarStore).toBeFunction();
        });

        it("creates correct getter for Controller Baz", function() {
            expect(Class.prototype.getBazController).toBeFunction();
        });

        it("resolves modules when namespace is set via Loader.setConfig/setPath", function() {
            runs(function() {
                Ext.Loader.setPath('TestController', '/testcontroller');

                spyOn(Ext.Loader, 'require').andReturn();

                Class = Ext.define("TestController.AnotherNonconforming.Class", {
                    extend: 'Ext.app.Controller',

                    models: [ 'Baz' ],
                    views: [ 'Baz' ],
                    stores: [ 'Baz' ],
                    controllers: [ 'Qux' ]
                });
            });

            waits(50);

            runs(function() {
                var args = Ext.Loader.require.argsForCall[0][0];

                expect(args).toEqual([
                    'TestController.model.Baz',
                    'TestController.view.Baz',
                    'TestController.store.Baz',
                    'TestController.controller.Qux'
                ]);
            });
        });

        it("creates correct getter for Model Baz", function() {
            expect(Class.prototype.getBazModel).toBeFunction();
        });

        it("creates correct getter for View Baz", function() {
            expect(Class.prototype.getBazView).toBeFunction();
        });

        it("creates correct getter for Store Baz", function() {
            expect(Class.prototype.getBazStore).toBeFunction();
        });

        it("creates correct getter for Controller Qux", function() {
            expect(Class.prototype.getQuxController).toBeFunction();
        });

        it("uses $namespace shortcut to resolve modules if provided", function() {
            runs(function() {
                spyOn(Ext.Loader, 'require').andReturn();

                Class = Ext.define("NonexistingNamespace.controller.Fubaru", {
                    extend: 'Ext.app.Controller',

                    '$namespace': 'Foo',

                    models: [ 'Plugh' ],
                    views: [ 'Plugh' ],
                    stores: [ 'Plugh' ],
                    controllers: [ 'Xyzzy' ]
                });
            });

            waits(50);

            runs(function() {
                var args = Ext.Loader.require.argsForCall[0][0];

                expect(args).toEqual([
                    'Foo.model.Plugh',
                    'Foo.view.Plugh',
                    'Foo.store.Plugh',
                    'Foo.controller.Xyzzy'
                ]);
            });
        });

        it("creates correct getter for Model Plugh", function() {
            expect(Class.prototype.getPlughModel).toBeFunction();
        });

        it("creates correct getter for View Plugh", function() {
            expect(Class.prototype.getPlughView).toBeFunction();
        });

        it("creates correct getter for Store Plugh", function() {
            expect(Class.prototype.getPlughStore).toBeFunction();
        });

        it("creates correct getter for Controller Xyzzy", function() {
            expect(Class.prototype.getXyzzyController).toBeFunction();
        });

        it("resolves module names using @-notation if provided", function() {
            runs(function() {
                spyOn(Ext.Loader, 'require').andReturn();

                Class = Ext.define("AnotherNonexistingNamespace.Foobaroo", {
                    extend: 'Ext.app.Controller',

                    models: [ 'Splurge@TestController.model' ],
                    views: [ 'Splurge@TestController.view' ],
                    stores: [ 'Splurge@TestController.store' ],
                    controllers: [ 'Mymse@TestController.controller' ]
                });
            });

            waits(50);

            runs(function() {
                var args = Ext.Loader.require.argsForCall[0][0];

                expect(args).toEqual([
                    'TestController.model.Splurge',
                    'TestController.view.Splurge',
                    'TestController.store.Splurge',
                    'TestController.controller.Mymse'
                ]);
            });
        });

        it("creates correct getter for Model Splurge", function() {
            expect(Class.prototype.getSplurgeModel).toBeFunction();
        });

        it("creates correct getter for View Splurge", function() {
            expect(Class.prototype.getSplurgeView).toBeFunction();
        });

        it("creates correct getter for Store Splurge", function() {
            expect(Class.prototype.getSplurgeStore).toBeFunction();
        });

        it("creates correct getter for Controller Mymse", function() {
            expect(Class.prototype.getMymseController).toBeFunction();
        });

        it("assumes fully qualified module names if there's no way know them", function() {
            runs(function() {
                spyOn(Ext.Loader, 'require').andReturn();

                Class = Ext.define("YetAnotherNonexistingNamespace.Mymse", {
                    extend: 'Ext.app.Controller',

                    models: [ 'Fully.qualified.model.Flob' ],
                    views: [ 'Fully.qualified.view.Flob' ],
                    stores: [ 'Fully.qualified.store.Flob' ],
                    controllers: [ 'Fully.qualified.controller.Flob' ]
                });
            });

            waits(50);

            runs(function() {
                var args = Ext.Loader.require.argsForCall[0][0];

                expect(args).toEqual([
                    'Fully.qualified.model.Flob',
                    'Fully.qualified.view.Flob',
                    'Fully.qualified.store.Flob',
                    'Fully.qualified.controller.Flob'
                ]);
            });
        });

        it("creates correct getter for Model Flob", function() {
            expect(Class.prototype.getFullyQualifiedModelFlobModel).toBeFunction();
        });

        it("creates correct getter for View Flob", function() {
            expect(Class.prototype.getFullyQualifiedViewFlobView).toBeFunction();
        });

        it("creates correct getter for Store Flob", function() {
            expect(Class.prototype.getFullyQualifiedStoreFlobStore).toBeFunction();
        });

        it("creates correct getter for Controller Flob", function() {
            expect(Class.prototype.getFullyQualifiedControllerFlobController).toBeFunction();
        });
    });

    describe("works with refs:", function() {
        beforeEach(function() {
            Ext.define("TestController.controller.Refs", {
                extend: 'Ext.app.Controller',

                refs: [{
                    ref: 'fooPanel',
                    selector: 'foopanel'
                }, {
                    ref: 'barPanel',
                    selector: 'barpanel',
                    xtype: 'barpanel',
                    autoCreate: true
                }, {
                    ref: 'bazPanel',
                    selector: 'bazpanel',
                    xtype: 'bazpanel',
                    forceCreate: true
                }, {
                    ref: 'quxPanel',
                    xtype: 'barpanel',
                    autoCreate: true
                }, {
                    ref: 'fredComponent',
                    autoCreate: true
                }, {
                    ref: 'destroyed',
                    xtype: 'component',
                    autoCreate: true
                }]
            });

            ctrl = new TestController.controller.Refs({
                id: 'foo'
            });
        });

        afterEach(function() {
            var refs = ctrl.refCache;

            for (var ref in refs) {
                Ext.destroy(refs[ref]);
            }

            Ext.undefine('TestController.controller.Refs');
        });

        it("should be able to instantiate", function() {
            expect(ctrl.getId()).toBe('foo');
        });

        it("creates ref getters 1", function() {
            expect(ctrl.getFooPanel).toBeFunction();
        });

        it("creates ref getters 2", function() {
            expect(ctrl.getBarPanel).toBeFunction();
        });

        it("creates ref getters 3", function() {
            expect(ctrl.getBazPanel).toBeFunction();
        });

        it("returns existing component by ref", function() {
            var p = ctrl.getFooPanel();

            expect(p).toEqual(fooPanel);
        });

        it("creates component when ref has autoCreate flag", function() {
            var p = ctrl.getBarPanel();

            expect(p.xtype).toBe('barpanel');
        });

        // https://sencha.jira.com/browse/EXTJSIV-6032
        it("doesn't require selector when ref has autoCreate flag", function() {
            var p = ctrl.getQuxPanel();

            expect(p.xtype).toBe('barpanel');
        });

        it("creates Component by default with autoCreate", function() {
            var p = ctrl.getFredComponent();

            expect(p.xtype).toBe('component');
        });

        it("should be able to recreate an autoCreate after it is destroyed", function() {
            var o1 = ctrl.getDestroyed();

            expect(o1.isXType('component')).toBe(true);
            o1.destroy();
            var o2 = ctrl.getDestroyed();

            expect(o2.isXType('component')).toBe(true);
            expect(o2).not.toBe(o1);
        });

        it("creates component when ref has forceCreate flag", function() {
            var p = ctrl.getBazPanel();

            expect(p.xtype).toBe('bazpanel');

            bazPanelId = p.getId();

            p.destroy();
        });

        it("creates component every time when ref has forceCreate flag", function() {
            var p = ctrl.getBazPanel();

            expect(p.xtype).toBe('bazpanel');
            // AND
            expect(p.getId()).not.toBe(bazPanelId);

            p.destroy();
        });
    });

    describe("handles init():", function() {
        it("should survive init() on itself", function() {
            expect(function() { new TestController.controller.Events().init(); }).not.toThrow();

            expect(TestController.controller.Events).toBeDefined();
        });

        it("should init() child Controllers", function() {
            var called1 = false,
                called2 = false,
                called3 = false;

            spyOn(Ext.Loader, 'require').andCallFake(function(requires, callback) {
                callback();
            });

            Ext.define('TestController.controller.Child3', {
                extend: 'Ext.app.Controller',

                init: function() {
                    called3 = true;
                }
            });

            Ext.define('TestController.controller.Child2', {
                extend: 'Ext.app.Controller',

                controllers: ['Child3'],

                init: function() {
                    called2 = true;
                }
            });

            Ext.define('TestController.controller.Child1', {
                extend: 'Ext.app.Controller',

                controllers: ['Child2'],

                init: function() {
                    called1 = true;
                }
            });

            Ext.define('TestController.controller.Parent', {
                extend: 'Ext.app.Controller',

                controllers: ['Child1']
            });

            Ext.define('TestController.Application', {
                extend: 'Ext.app.Application',

                name: 'TestController',

                controllers: ['Parent']
            });

            var testApp = new TestController.Application();

            expect(called1).toBeTruthy();
            // AND
            expect(called2).toBeTruthy();
            // AND
            expect(called3).toBeTruthy();

            testApp.destroy();
        });
    });

    describe("handles View events:", function() {
        beforeEach(function() {
            customEventFired = panelEventFired = false;

            ctrl = new TestController.controller.Events();
            ctrl.init();
        });

        it("should control newly created Views", function() {
            fooPanel.setSize(50, 50);

            expect(panelEventFired).toBeTruthy();
        });

        describe("should ignore case", function() {
            function ignoreCase(obj) {
                it("should accept " + obj.specName, function() {
                    fooPanel.fireEvent(obj.eventName, fooPanel);
                    expect(customEventFired).toBe(true);
                });
            }

            Ext.Array.forEach([
                { specName: 'lowercase', eventName: 'uppercustom' },
                { specName: 'uppercase', eventName: 'UPPERCUSTOM' },
                { specName: 'camelCase', eventName: 'upperCustom' },
                { specName: 'mixed case', eventName: 'UpPErCustoM' }
            ], function(obj) {
                ignoreCase(obj);
            });
        });
    });

    describe("handles getters:", function() {
        var c, s, m, v;

        beforeEach(function() {
            ctrl = new TestController.controller.Events({
                id: 'foo'
            });
            ctrl.init();
        });

        afterEach(function() {
            Ext.destroy(s, m, v, c);
            s = m = v = c = null;
        });

        it("should return self on getController(self-id)", function() {
            c = ctrl.getController('foo');

            expect(c).toEqual(ctrl);
        });

        it("should return nothing on getController(foreign-id)", function() {
            c = ctrl.getController('bar');

            expect(c).toBeFalsy();
        });

        it("should return Store on getStore()", function() {
            s = ctrl.getStore('Test');

            expect(s.isInstance).toBeTruthy();
        });

        it("should return model class on getModel()", function() {
            m = ctrl.getModel('Test');

            expect(m.$isClass).toBeTruthy();
        });

        it("should return View class on getView()", function() {
            v = ctrl.getView('FooPanel');

            expect(v.$isClass).toBeTruthy();
        });
    });

    describe("allows unit testing:", function() {
        beforeEach(function() {
            ctrl = new TestController.controller.Events({
                id: 'bar'
            });

            spyOn(ctrl, 'onPanelFooResize');
            ctrl.init();
        });

        it("should fire the spy on the instance", function() {
            fooPanel.setSize(10, 10);

            expect(ctrl.onPanelFooResize).toHaveBeenCalled();
        });
    });
});
