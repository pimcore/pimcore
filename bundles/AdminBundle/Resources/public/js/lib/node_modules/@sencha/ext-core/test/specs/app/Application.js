/* global TestApplication, AltApplication, FooBarApp, CtrlApplication */

topSuite('Ext.app.Application', function() {
    var isClassic = Ext.toolkit === 'classic',
        isModern = Ext.toolkit === 'modern',
        app, required, initCalled, launchCalled;

    function createApp(cfg, cls) {
        Ext.app.Application.instance = app = new (cls || TestApplication.Application)(cfg);
    }

    beforeEach(function() {
        spyOn(Ext.util.History, 'setHash').andCallFake(function(hash, replace) {
            var me = this,
                hashRe = me.hashRe,
                loc = me.win.location;

            // may or may not already be prefixed with # or #! already
            hash = hash.replace(hashRe, me.hashbang ? '#!' : '#');

            // need to make sure currentToken is not prefixed
            me.currentToken = hash.replace(hashRe, '');
        });

        this.addMatchers({
            toBeFunction: function(expected) {
                var actual = this.actual;

                return expected
                    ? Ext.isFunction(actual) && actual === expected
                    : Ext.isFunction(actual);
            }
        });

        Ext.app.addNamespaces('TestApplication');

        Ext.define('TestApplication.controller.Foo', {
            extend: 'Ext.app.Controller',

            id: 'Foo',

            initialized: false,
            launched: false,

            init: function() {
                this.initialized = true;
            },

            onLaunch: function() {
                this.launched = true;
            }
        });

        Ext.define('TestApplication.Application', {
            extend: 'Ext.app.Application',

            name: 'TestApplication',

            namespaces: [
                'TestApplication.Foo',
                'TestApplication.Bar'
            ],

            controllers: [
                'Foo'
            ],

            __handleRequires: function(requires, callback) {
                required = requires;
                callback();
            },

            init: function() {
                initCalled = true;
            },

            launch: function() {
                launchCalled = true;
            }
        });

        if (isModern) {
            Ext.Viewport = new Ext.viewport.Default();
        }
    });

    afterEach(function() {
        Ext.app.clearNamespaces();

        initCalled = launchCalled = required =
            app = Ext.Viewport = Ext.destroy(app, Ext.Viewport);

        Ext.undefine('TestApplication.controller.Foo');
        Ext.undefine('TestApplication.Application');

        if (Ext.isIE8) {
            Ext.global.AltApplication = Ext.global.CtrlApplication = Ext.global.TestApplication =
                Ext.global.FooBarApp = Ext.app.Application.instance = undefined;
        }
        else {
            delete Ext.global.AltApplication;
            delete Ext.global.CtrlApplication;
            delete Ext.global.TestApplication;
            delete Ext.global.FooBarApp;
            delete Ext.app.Application.instance;
        }
    });

    it('should be constructable', function() {
        createApp();

        expect(app).toBeDefined();
    });

    it('should add getApplication method', function() {
        createApp();

        expect(Ext.getApplication).toBeFunction();
        expect(app.getApplication).toBeFunction();
    });

    it('should return Application instance', function() {
        createApp();

        expect(Ext.getApplication()).toEqual(app);
        expect(app.getApplication()).toEqual(app);
    });

    it('should init itself as a Controller', function() {
        createApp();

        expect(app._initialized).toBeTruthy();
    });

    it('should init dependent Controllers and sets their id', function() {
        createApp();

        var ctrl = app.getController('Foo');

        expect(ctrl.initialized).toBeTruthy();
        expect(ctrl.getId()).toBe('Foo');
    });

    it('should call onLaunch on dependent Controllers', function() {
        createApp();

        var ctrl = app.getController('Foo');

        expect(ctrl.launched).toBeTruthy();
    });

    it('should call its init() method', function() {
        createApp();

        expect(initCalled).toBeTruthy();
    });

    it('should call its launch() method', function() {
        createApp();

        expect(launchCalled).toBeTruthy();
    });

    it('should fire launch event', function() {
        var fired = false;

        app = new TestApplication.Application({
            listeners: {
                launch: function() { fired = true; }
            }
        });

        expect(fired).toBeTruthy();
    });

    it('inits QuickTips', function() {
        if (isModern) {
            createApp();

            expect(app.getQuickTips().$className).toBe('Ext.tip.Manager');
        }
        else {
            spyOn(Ext.tip.QuickTipManager, 'init');

            createApp();

            expect(Ext.tip.QuickTipManager.init).toHaveBeenCalled();
        }
    });

    describe('init', function() {
        afterEach(function() {
            Ext.undefine('AltApplication.Application');

            if (Ext.isIE8) {
                Ext.global.AltApplication = Ext.global.FooBarApp = undefined;
            }
            else {
                delete Ext.global.AltApplication;
                delete Ext.global.FooBarApp;
            }
        });

        it('should create the namespace', function() {
            Ext.define('AltApplication.Application', {
                extend: 'Ext.app.Application',

                name: 'FooBarApp'
            });

            createApp(null, AltApplication.Application);

            expect(Ext.global.FooBarApp).not.toBeUndefined();
        });

        it('should have getApplication return app', function() {
            var test;

            Ext.define('AltApplication.Application', {
                extend: 'Ext.app.Application',

                name: 'FooBarApp',

                init: function() {
                    test = FooBarApp.getApplication();
                }
            });

            createApp(null, AltApplication.Application);

            expect(test).not.toBeUndefined();
            expect(test.$className).toBe('AltApplication.Application');
            expect(Ext.getApplication()).toEqual(test);
        });

        describe('appProperty', function() {
            it('should have application set on appProperty', function() {
                var test;

                Ext.define('AltApplication.Application', {
                    extend: 'Ext.app.Application',

                    name: 'FooBarApp',

                    init: function() {
                        test = FooBarApp.app;
                    }
                });

                createApp(null, AltApplication.Application);

                expect(test).not.toBeUndefined();
                expect(test.$className).toBe('AltApplication.Application');
            });

            it('should have application set on configured appProperty', function() {
                var test;

                Ext.define('AltApplication.Application', {
                    extend: 'Ext.app.Application',

                    appProperty: '$APPLICATION',
                    name: 'FooBarApp',

                    init: function() {
                        test = FooBarApp.$APPLICATION;
                    }
                });

                createApp(null, AltApplication.Application);

                expect(test).not.toBeUndefined();
                expect(test.$className).toBe('AltApplication.Application');
            });
        });
    });

    describe('resolves global namespaces upon class creation', function() {
        it('should have TestApplication namespace', function() {
            createApp();

            expect(Ext.app.namespaces['TestApplication']).toBeTruthy();
        });

        it('should have TestApplication.Foo namespace', function() {
            createApp();

            expect(Ext.app.namespaces['TestApplication.Foo']).toBeTruthy();
        });

        it('should have TestApplication.Bar namespace', function() {
            createApp();

            expect(Ext.app.namespaces['TestApplication.Bar']).toBeTruthy();
        });
    });

    describe('resolves class names', function() {
        describe('when appFolder is set', function() {
            beforeEach(function() {
                Ext.define('TestApplication.AbstractApplication', {
                    extend: 'Ext.app.Application',

                    appFolder: 'foo'
                });

                Ext.define('TestApplication.Application2', {
                    extend: 'TestApplication.AbstractApplication',

                    name: 'Foo',

                    __handleRequires: function(requires, callback) {
                        callback();
                    }
                });
            });

            afterEach(function() {
                if (Ext.isIE8) {
                    Ext.global.Foo = undefined;
                }
                else {
                    delete Ext.global.Foo;
                }

                Ext.undefine('TestApplication.AbstractApplication');
                Ext.undefine('TestApplication.Application2');
            });

            it('resolves Viewport path', function() {
                var path = Ext.Loader.config.paths.Foo;

                expect(path).toBe('foo');
            });
        });
    });

    describe('router/history', function() {
        var History = Ext.util.History;

        beforeEach(function() {
            History.useTopWindow = false;
        });

        afterEach(function() {
            History.useTopWindow = true;
        });

        it('should init Ext.util.History', function() {
            createApp();

            return expect(Ext.util.History.ready).toEqual(true);
        });

        describe('defaultToken', function() {
            it('should add the defaultToken', function() {
                createApp({
                    defaultToken: 'foo'
                });

                expect(History.getToken()).toEqual('foo');
            });

            it('should already have a token', function() {
                if (!History.getToken()) {
                    History.add('foo');
                }

                createApp({
                    defaultToken: 'bar'
                });

                expect(History.getToken()).toEqual('foo');
            });
        });

        describe('router', function() {
            it('should not apply config if router not present', function() {
                var spy = spyOn(Ext.route.Router, 'setConfig');

                createApp();

                expect(spy).not.toHaveBeenCalled();
            });

            it('should set config on router', function() {
                var spy = spyOn(Ext.route.Router, 'setConfig');

                createApp({
                    router: {
                        hashbang: true,
                        multipleToken: '&'
                    }
                });

                expect(spy).toHaveBeenCalledWith({
                    hashbang: true,
                    multipleToken: '&'
                });
            });
        });
    });

    describe('getController', function() {
        var ctorLog;

        beforeEach(function() {
            ctorLog = [];

            Ext.define('CtrlApplication.controller.DeclaredWithId', {
                extend: 'Ext.app.Controller',
                id: 'declaredCustomWithId',

                constructor: function(config) {
                    this.callParent([config]);
                    ctorLog.push(this.$className);
                }
            });

            Ext.define('CtrlApplication.controller.DeclaredAutoIdShort', {
                extend: 'Ext.app.Controller',

                constructor: function(config) {
                    this.callParent([config]);
                    ctorLog.push(this.$className);
                }
            });

            Ext.define('CtrlApplication.controller.DeclaredAutoIdLong', {
                extend: 'Ext.app.Controller',

                constructor: function(config) {
                    this.callParent([config]);
                    ctorLog.push(this.$className);
                }
            });

            Ext.define('CtrlApplication.controller.NotDeclared', {
                extend: 'Ext.app.Controller',

                constructor: function(config) {
                    this.callParent([config]);
                    ctorLog.push(this.$className);
                }
            });

            Ext.define('CtrlApplication.Application', {
                extend: 'Ext.app.Application',

                name: 'CtrlApplication',

                controllers: [
                    'DeclaredWithId',
                    'DeclaredAutoIdShort',
                    'CtrlApplication.controller.DeclaredAutoIdLong'
                ]
            });

            createApp(null, CtrlApplication.Application);
        });

        afterEach(function() {
            Ext.undefine('CtrlApplication.controller.DeclaredWithId');
            Ext.undefine('CtrlApplication.controller.DeclaredAutoIdShort');
            Ext.undefine('CtrlApplication.controller.DeclaredAutoIdLong');
            Ext.undefine('CtrlApplication.controller.NotDeclared');

            Ext.undefine('CtrlApplication.Application');

            if (Ext.isIE8) {
                Ext.global.CtrlApplication = undefined;
            }
            else {
                delete Ext.global.CtrlApplication;
            }

            ctorLog = null;
        });

        function times(key) {
            var count = 0;

            Ext.Array.forEach(ctorLog, function(name) {
                if (name === key) {
                    ++count;
                }
            });

            return count;
        }

        describe('in controllers collection', function() {
            it('should be able to get a controller with an explicit id by id or class name', function() {
                expect(app.getController('declaredCustomWithId').$className).toBe('CtrlApplication.controller.DeclaredWithId');
                expect(app.getController('CtrlApplication.controller.DeclaredWithId').$className).toBe('CtrlApplication.controller.DeclaredWithId');
                expect(times('CtrlApplication.controller.DeclaredWithId')).toBe(1);
            });

            it('should be able to get a controller declared with a short name by short & long name', function() {
                expect(app.getController('DeclaredAutoIdShort').$className).toBe('CtrlApplication.controller.DeclaredAutoIdShort');
                expect(app.getController('CtrlApplication.controller.DeclaredAutoIdShort').$className).toBe('CtrlApplication.controller.DeclaredAutoIdShort');
                expect(times('CtrlApplication.controller.DeclaredAutoIdShort')).toBe(1);
            });

            it('should be able to get a controller declared with a long name by short & long name', function() {
                expect(app.getController('DeclaredAutoIdLong').$className).toBe('CtrlApplication.controller.DeclaredAutoIdLong');
                expect(app.getController('CtrlApplication.controller.DeclaredAutoIdLong').$className).toBe('CtrlApplication.controller.DeclaredAutoIdLong');
                expect(times('CtrlApplication.controller.DeclaredAutoIdLong')).toBe(1);
            });

            it('should be able to get a not declared controller by short & long name', function() {
                expect(app.getController('NotDeclared').$className).toBe('CtrlApplication.controller.NotDeclared');
                expect(app.getController('CtrlApplication.controller.NotDeclared').$className).toBe('CtrlApplication.controller.NotDeclared');
                expect(times('CtrlApplication.controller.NotDeclared')).toBe(1);
            });
        });
    });

    describe('mainView', function() {
        var required;

        beforeEach(function() {
            Ext.ns('AltApplication');

            Ext.define('AltApplication.view.Viewport', {
                create: function() {}
            });

            Ext.define('AltApplication.Application', {
                extend: 'Ext.app.Application',
                name: 'AltApplication',

                mainView: 'Viewport',

                __handleRequires: function(requires, callback) {
                    required = requires;
                    callback();
                }
            });
        });

        afterEach(function() {
            required = null;

            Ext.app.clearNamespaces();

            Ext.undefine('AltApplication.view.Viewport');
            Ext.undefine('AltApplication.Application');

            if (Ext.isIE8) {
                Ext.global.AltApplication = undefined;
            }
            else {
                delete Ext.global.AltApplication;
            }
        });

        it('should init AltApplication.view.Viewport view', function() {
            spyOn(AltApplication.view.Viewport, 'create');

            createApp(null, AltApplication.Application);

            expect(AltApplication.view.Viewport.create).toHaveBeenCalled();
        });
    });

    if (isClassic) {
        describe('autoCreateViewport', function() {
            var required;

            beforeEach(function() {
                Ext.ns('AltApplication');

                Ext.define('AltApplication.view.Viewport', {
                    create: function() {}
                });

                Ext.define('AltApplication.Application', {
                    extend: 'Ext.app.Application',
                    name: 'AltApplication',

                    autoCreateViewport: true,

                    __handleRequires: function(requires, callback) {
                        required = requires;
                        callback();
                    }
                });
            });

            afterEach(function() {
                required = null;

                Ext.app.clearNamespaces();

                Ext.undefine('AltApplication.view.Viewport');
                Ext.undefine('AltApplication.Application');

                if (Ext.isIE8) {
                    Ext.global.AltApplication = undefined;
                }
                else {
                    delete Ext.global.AltApplication;
                }
            });

            it('should resolve class names', function() {
                createApp(null, AltApplication.Application);

                expect(required).toEqual([
                    'AltApplication.view.Viewport'
                ]);
            });

            it('should init AltApplication.view.Viewport view', function() {
                spyOn(AltApplication.view.Viewport, 'create');

                createApp(null, AltApplication.Application);

                expect(AltApplication.view.Viewport.create).toHaveBeenCalled();
            });
        });
    }
});
