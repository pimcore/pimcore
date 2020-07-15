topSuite("Ext.plugin.Abstract",
    ['Ext.Container', 'Ext.app.ViewController', 'Ext.plugin.Responsive'],
function() {
    var PTYPE = Ext.isClassic ? 'ptype' : 'type',
        Plugin, Component, component, spies;

    function defineComponent(cfg) {
        Component = Ext.define(null, Ext.apply({
            extend: 'Ext.Component',
            onFoo: spies && spies.component
        }, cfg));
    }

    afterEach(function() {
        component = Ext.destroy(component);

        Plugin = Component = null;
    });

    describe('creation', function() {
        var Plugin1, Plugin2;

        function definePlugin(cfg) {
            var className = cfg && cfg.xclass;

            if (className) {
                delete cfg.xclass;
            }

            return Plugin = Ext.define(className || 'Ext.test.plugin.Abstract', Ext.merge({
                extend: 'Ext.plugin.Abstract',
                alias: 'plugin.plugintest'
            }, cfg));
        }

        beforeEach(function() {
            Plugin1 = definePlugin();
            Plugin2 = definePlugin({
                xclass: 'Ext.test.plugin.Abstract2',
                alias: 'plugin.plg'
            });
        });

        afterEach(function() {
            Ext.undefine('Ext.test.plugin.Abstract');
            Ext.undefine('Ext.test.plugin.Abstract2');

            Plugin1 = Plugin2 = null;
        });

        describe('plugins: config object', function() {
            it('should create a plugin defined on the class', function() {
                var p2 = {};

                p2[PTYPE] = 'plugintest';

                defineComponent({
                    plugins: p2
                });

                component = new Component();

                var plugins = component.getPlugins();

                expect(plugins.length).toBe(1);
                expect(plugins[0] instanceof Plugin1).toBe(true);
            });
        });

        describe('plugins: string', function() {
            it('should create a plugin defined on the class', function() {
                defineComponent({
                    plugins: 'plugintest'
                });

                component = new Component();

                var plugin = component.findPlugin('plugintest');

                expect(plugin instanceof Plugin1).toBe(true);
            });
        });

        describe('plugins: string[]', function() {
            it('should create plugins defined on the class', function() {
                defineComponent({
                    plugins: ['plugintest', 'plg']
                });

                component = new Component();

                var plugin = component.findPlugin('plugintest');

                expect(plugin instanceof Plugin1).toBe(true);

                plugin = component.findPlugin('plg');
                expect(plugin instanceof Plugin2).toBe(true);
            });
        });

        describe('plugins: mixed[]', function() {
            it('should create plugins defined on the class', function() {
                var p2 = {};

                p2[PTYPE] = 'plg';

                defineComponent({
                    plugins: ['plugintest', p2]
                });

                component = new Component();

                var plugin = component.findPlugin('plugintest');

                expect(plugin instanceof Plugin1).toBe(true);

                plugin = component.findPlugin('plg');
                expect(plugin instanceof Plugin2).toBe(true);
            });
        });

        describe('plugins: keyed object', function() {
            it('should create plugins defined on the class', function() {
                defineComponent({
                    plugins: {
                        plugintest: true,
                        plg: {
                            id: 'foo'
                        }
                    }
                });

                component = new Component();

                var plugin = component.findPlugin('plugintest');

                expect(plugin instanceof Plugin1).toBe(true);

                plugin = component.getPlugin('plugintest');
                expect(plugin instanceof Plugin1).toBe(true);

                plugin = component.findPlugin('plg');
                expect(plugin instanceof Plugin2).toBe(true);

                plugin = component.getPlugin('foo');
                expect(plugin instanceof Plugin2).toBe(true);
            });

            it('should create and order plugins defined on the class', function() {
                defineComponent({
                    plugins: {
                        plugintest: true,
                        plg: {
                            weight: 1
                        }
                    }
                });

                component = new Component();

                var plugins = component.getPlugins();

                expect(plugins.length).toBe(2);
                expect(plugins[0] instanceof Plugin1).toBe(true);
                expect(plugins[1] instanceof Plugin2).toBe(true);
            });

            it('should create and reverse order plugins defined on the class', function() {
                defineComponent({
                    plugins: {
                        plugintest: true,
                        plg: {
                            weight: -1
                        }
                    }
                });

                component = new Component();

                var plugins = component.getPlugins();

                expect(plugins.length).toBe(2);
                expect(plugins[0] instanceof Plugin2).toBe(true);
                expect(plugins[1] instanceof Plugin1).toBe(true);
            });

            if (Ext.isModern) {
                it('should override plugins defined on the class by instance config', function() {
                    defineComponent({
                        plugins: {
                            plugintest: true,
                            plg: {
                                weight: -1
                            }
                        }
                    });

                    component = new Component({
                        plugins: {
                            plg: {
                                weight: 1
                            }
                        }
                    });

                    var plugins = component.getPlugins();

                    expect(plugins.length).toBe(2);
                    expect(plugins[0] instanceof Plugin1).toBe(true);
                    expect(plugins[1] instanceof Plugin2).toBe(true);

                    component.destroy();

                    component = new Component({
                        plugins: {
                            plugintest: null
                        }
                    });

                    plugins = component.getPlugins();

                    expect(plugins.length).toBe(1);
                    expect(plugins[0] instanceof Plugin2).toBe(true);
                });

                it('should be able to activate a plugin early', function() {
                    defineComponent({
                        config: {
                            foo: null,
                            bar: null
                        },

                        plugins: {
                            plugintest: true,
                            plg: {
                                weight: -1
                            }
                        }
                    });

                    component = new Component({
                        plugins: {
                            responsive: true,
                            plg: {
                                weight: 1
                            }
                        },

                        responsiveConfig: {
                            'true': {
                                foo: 1
                            },
                            'false': {
                                bar: 2
                            }
                        }
                    });

                    var plugins = component.getPlugins();

                    expect(component.getFoo()).toBe(1);
                    expect(component.getBar()).toBe(null);

                    expect(plugins.length).toBe(3);
                    expect(plugins[0] instanceof Ext.plugin.Responsive).toBe(true);
                    expect(plugins[1] instanceof Plugin1).toBe(true);
                    expect(plugins[2] instanceof Plugin2).toBe(true);
                });
            }
        });
    });

    describe("listener scope resolution", function() {
        var plugin, Parent, parent,
            Controller, ParentController;

        function defineParent(cfg) {
            Parent = Ext.define(null, Ext.apply({
                extend: 'Ext.Container',
                onFoo: spies.parent
            }, cfg));
        }

        function expectScope(scope) {
            var scopes = {
                    plugin: plugin,
                    component: component,
                    controller: component && component.getController(),
                    parent: parent,
                    parentController: parent && parent.getController()
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
                plugin: jasmine.createSpy(),
                component: jasmine.createSpy(),
                controller: jasmine.createSpy(),
                parent: jasmine.createSpy(),
                parentController: jasmine.createSpy()
            };

            Controller = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.controller
            });

            ParentController = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.parentController
            });
        });

        afterEach(function() {
            if (plugin) {
                plugin.destroy();
            }

            if (parent) {
                parent.destroy();
            }

            Parent = Controller = ParentController = null;
            plugin = parent = null;
        });

        describe("listener declared on class body", function() {
            function definePlugin(cfg) {
                Plugin = Ext.define(null, Ext.merge({
                    extend: 'Ext.plugin.Abstract',
                    mixins: ['Ext.mixin.Observable'],
                    constructor: function(config) {
                        this.callParent([config]);
                        this.mixins.observable.constructor.call(this);
                    },
                    listeners: {
                        foo: 'onFoo'
                    },
                    onFoo: spies.plugin
                }, cfg));
            }

            describe("with no defaultListenerScope or controller", function() {
                beforeEach(function() {
                    defineComponent();
                });

                it("should resolve to the plugin with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });

                it("should fail with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with defaultListenerScope on component", function() {
                beforeEach(function() {
                    defineComponent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the component with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller on component", function() {
                beforeEach(function() {
                    defineComponent({
                        controller: new Controller()
                    });
                });

                it("should resolve to the view controller with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the view controller with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller and defaultListenerScope on component", function() {
                beforeEach(function() {
                    defineComponent({
                        controller: new Controller(),
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the component with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('component');
                });

                it("should resolve to the view controller with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with parent and no defaultListenerScope or controller", function() {
                beforeEach(function() {
                    defineParent();
                });

                it("should resolve to the plugin with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });

                it("should fail with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the parent with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    definePlugin();
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
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
                    definePlugin();
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parent');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    definePlugin({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    plugin = new Plugin();
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with handler declared as a function reference", function() {
                var handler, scope;

                function definePlugin(cfg) {
                    Plugin = Ext.define(null, Ext.merge({
                        extend: 'Ext.plugin.Abstract',
                        mixins: ['Ext.mixin.Observable'],
                        constructor: function(config) {
                            this.callParent([config]);
                            this.mixins.observable.constructor.call(this);
                        },
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

                describe("with no defaultListenerScope or controller", function() {
                    beforeEach(function() {
                        defineComponent();
                    });

                    it("should resolve to the plugin with unspecified scope", function() {
                        definePlugin();
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });

                    it("should fail with scope:'controller'", function() {
                        definePlugin({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        expect(function() {
                            plugin.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the plugin with scope:'this'", function() {
                        definePlugin({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });
                });

                describe("with defaultListenerScope on component", function() {
                    beforeEach(function() {
                        defineComponent({
                            defaultListenerScope: true
                        });
                    });

                    it("should resolve to the plugin with unspecified scope", function() {
                        definePlugin();
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });

                    it("should fail with scope:'controller'", function() {
                        definePlugin({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        expect(function() {
                            plugin.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the plugin with scope:'this'", function() {
                        definePlugin({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });
                });

                describe("with view controller on component", function() {
                    beforeEach(function() {
                        defineComponent({
                            controller: new Controller()
                        });
                    });

                    it("should resolve to the plugin with unspecified scope", function() {
                        definePlugin();
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });

                    it("should resolve to the component view controller with scope:'controller'", function() {
                        definePlugin({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(component.getController());
                    });

                    it("should resolve to the plugin with scope:'this'", function() {
                        definePlugin({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        plugin = new Plugin();
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });
                });
            });
        });

        describe("listener declared on instance config", function() {
            function definePlugin(cfg) {
                Plugin = Ext.define(null, Ext.merge({
                    extend: 'Ext.plugin.Abstract',
                    mixins: ['Ext.mixin.Observable'],
                    constructor: function(config) {
                        this.callParent([config]);
                        this.mixins.observable.constructor.call(this);
                    },
                    onFoo: spies.plugin
                }, cfg));
            }

            describe("with no defaultListenerScope or controller", function() {
                beforeEach(function() {
                    defineComponent();
                    definePlugin();
                });

                it("should resolve to the component with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with defaultListenerScope on component", function() {
                beforeEach(function() {
                    defineComponent({
                        defaultListenerScope: true
                    });
                    definePlugin();
                });

                it("should resolve to the component with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller on component", function() {
                beforeEach(function() {
                    defineComponent({
                        controller: new Controller()
                    });
                    definePlugin();
                });

                it("should resolve to the component view controller with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the component view controller with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller and defaultListenerScope on component", function() {
                beforeEach(function() {
                    defineComponent({
                        controller: new Controller(),
                        defaultListenerScope: true
                    });
                    definePlugin();
                });

                it("should resolve to the component with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('component');
                });

                it("should resolve to the component view controller with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    component = new Component({
                        plugins: plugin
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with parent and no defaultListenerScope or controller", function() {
                beforeEach(function() {
                    defineParent();
                    definePlugin();
                    defineComponent();
                });

                it("should resolve to the component with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: (component = new Component({
                            plugins: plugin
                        }))
                    });
                    plugin.fireEvent('foo');
                    expectScope('component');
                });

                it("should fail with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                    definePlugin();
                });

                it("should resolve to the parent with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    expect(function() {
                        plugin.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                    definePlugin();
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with view controller and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController(),
                        defaultListenerScope: true
                    });
                    definePlugin();
                });

                it("should resolve to the parent with unspecified scope", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parent');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the plugin with scope:'this'", function() {
                    plugin = new Plugin({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: {
                            plugins: plugin
                        }
                    });
                    plugin.fireEvent('foo');
                    expectScope('plugin');
                });
            });

            describe("with handler declared as a function reference", function() {
                var handler, scope;

                function definePlugin(cfg) {
                    Plugin = Ext.define(null, Ext.merge({
                        extend: 'Ext.plugin.Abstract',
                        mixins: ['Ext.mixin.Observable'],
                        constructor: function(config) {
                            this.callParent([config]);
                            this.mixins.observable.constructor.call(this);
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

                describe("with no defaultListenerScope or controller", function() {
                    beforeEach(function() {
                        defineComponent();
                        definePlugin();
                    });

                    it("should resolve to the plugin with unspecified scope", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });

                    it("should fail with scope:'controller'", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        expect(function() {
                            plugin.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the plugin with scope:'this'", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });
                });

                describe("with defaultListenerScope on component", function() {
                    beforeEach(function() {
                        defineComponent({
                            defaultListenerScope: true
                        });
                        definePlugin();
                    });

                    it("should resolve to the plugin with unspecified scope", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });

                    it("should fail with scope:'controller'", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        expect(function() {
                            plugin.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the plugin with scope:'this'", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });
                });

                describe("with view controller on component", function() {
                    beforeEach(function() {
                        defineComponent({
                            controller: new Controller()
                        });
                        definePlugin();
                    });

                    it("should resolve to the plugin with unspecified scope", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });

                    it("should resolve to the controller with scope:'controller'", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(component.getController());
                    });

                    it("should resolve to the plugin with scope:'this'", function() {
                        plugin = new Plugin({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        component = new Component({
                            plugins: plugin
                        });
                        plugin.fireEvent('foo');
                        expect(scope).toBe(plugin);
                    });
                });
            });
        });
    }); // listener scope resolution
});
