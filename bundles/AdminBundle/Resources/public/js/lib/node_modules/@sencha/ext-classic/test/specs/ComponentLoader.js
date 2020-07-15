topSuite("Ext.ComponentLoader", 'Ext.Container', function() {
    var getAjaxOptions, loadAndComplete, loadAndFail, mockComplete, makeLoader, makeContainer, makeComponent, loader, comp;

    beforeEach(function() {
        // add global variable in whitelist
        MockAjaxManager.addMethods();

        makeComponent = function(cfg) {
            cfg = cfg || {};
            comp = new Ext.Component(cfg);
        };

        makeContainer = function(cfg) {
            cfg = cfg || {};
            comp = new Ext.container.Container(cfg);
        };

        makeLoader = function(cfg) {
            cfg = cfg || {};
            Ext.applyIf(cfg, {
                url: 'url',
                target: comp
            });
            loader = new Ext.ComponentLoader(cfg);
        };

        mockComplete = function(responseText, status) {
            Ext.Ajax.mockComplete({
                status: status || 200,
                responseText: responseText || 'response'
            });
        };

        loadAndComplete = function(responseText, options) {
            loader.load(options);
            mockComplete(responseText);
        };

        loadAndFail = function(responseText, options) {
            loader.load(options);
            mockComplete(responseText, 500);
        };

        getAjaxOptions = function() {
            return Ext.Ajax.mockGetRequestXHR().options;
        };
    });

    afterEach(function() {
        MockAjaxManager.removeMethods();

        if (comp) {
            comp.destroy();
        }

        if (loader) {
            loader.destroy();
        }

        getAjaxOptions = loadAndFail = loadAndComplete = mockComplete = makeLoader = makeContainer = makeComponent = loader = comp = null;

    });

    describe("defaults", function() {
        beforeEach(function() {
            loader = new Ext.ComponentLoader();
        });

        it("should default removeAll to false", function() {
            expect(loader.removeAll).toBeFalsy();
        });

        it("should default the renderer to html", function() {
            expect(loader.renderer).toEqual('html');
        });
    });

    describe("loadOnRender", function() {
        describe("when not rendered", function() {
            it("should load when the component renders", function() {
                makeComponent();
                makeLoader({
                    target: comp,
                    loadOnRender: true
                });
                // No load yet
                expect(Ext.Ajax.mockGetAllRequests()).toEqual([]);
                comp.render(Ext.getBody());
                mockComplete('New Content');
                expect(comp.getEl().dom).hasHTML('New Content');
            });

            it("should pass any options in loadOnRender", function() {
                makeComponent();
                makeLoader({
                    target: comp,
                    loadOnRender: {
                        url: 'bar'
                    }
                });
                comp.render(Ext.getBody());
                expect(getAjaxOptions().url).toBe('bar');
            });
        });

        describe("already rendered", function() {
            it("should load immediately", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                makeLoader({
                    target: comp,
                    loadOnRender: true
                });
                mockComplete('New Content');
                expect(comp.getEl().dom).hasHTML('New Content');
            });

            it("should pass any options in loadOnRender", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                makeLoader({
                    target: comp,
                    loadOnRender: {
                        url: 'bar'
                    }
                });
                expect(getAjaxOptions().url).toBe('bar');
            });
        });
    });

    describe("masking", function() {

        beforeEach(function() {
            makeComponent({
                renderTo: document.body
            });
        });

        afterEach(function() {
            if (Ext.WindowManager.mask) {
                Ext.WindowManager.mask.remove();
                Ext.WindowManager.mask = null;
            }
        });
        it("should not mask by default", function() {
            makeLoader();
            loader.load();
            expect(comp.loadMask).toBeFalsy();
        });

        it("should unmask after the request completes", function() {

            makeLoader({
                loadMask: true
            });
            loader.load();
            expect(comp.loadMask != null).toBe(true);
            mockComplete();
            expect(comp.loadMask.isVisible()).toBeFalsy();
        });

        it("should accept a masking config", function() {
            makeLoader({
                loadMask: {
                    msg: 'Waiting'
                }
            });
            loader.load();
            expect(comp.loadMask.msg).toEqual('Waiting');
            mockComplete();
        });

        it("should use the masking load option", function() {
            makeLoader();
            loader.load({
                loadMask: true
            });
            expect(comp.loadMask != null).toBe(true);
            mockComplete();
        });

        it("should give precedence to the load option", function() {
            makeLoader({
                loadMask: {
                    msg: 'Waiting'
                }
            });
            loader.load({
                loadMask: {
                    msg: 'Other'
                }
            });
            expect(comp.loadMask.msg).toEqual('Other');
            mockComplete();
        });
    });

    describe("target", function() {
        var C;

        beforeEach(function() {
            C = Ext.ComponentLoader;
        });

        afterEach(function() {
            C = null;
        });

        it("should take the target from the config object", function() {
            makeComponent();
            makeLoader();
            expect(loader.getTarget()).toEqual(comp);
        });

        it("should take a string config", function() {
            makeComponent({
                id: 'id'
            });
            loader = new C({
                target: 'id'
            });
            expect(loader.getTarget()).toEqual(comp);
        });

        it("should assign the target", function() {
            makeComponent();
            loader = new C();
            loader.setTarget(comp);
            expect(loader.getTarget()).toEqual(comp);
        });

        it("should assign a new target", function() {
            var other = new Ext.Component();

            makeComponent();
            makeLoader();
            loader.setTarget(other);
            expect(loader.getTarget()).toEqual(other);

            other.destroy();
        });

        it("should assign a new target via id", function() {
            makeComponent({
                id: 'id'
            });
            loader = new C();
            loader.setTarget('id');
            expect(loader.getTarget()).toEqual(comp);
        });
    });

    describe("renderers", function() {
        describe("html", function() {
            it("should use html as the default renderer", function() {
                makeComponent({
                    renderTo: document.body
                });
                makeLoader();
                loadAndComplete('New content');
                expect(comp.getEl().dom).hasHTML('New content');
            });

            it("should use html if it's specified", function() {
                makeComponent({
                    renderTo: document.body
                });
                makeLoader({
                    renderer: 'html'
                });
                loadAndComplete('New content');
                expect(comp.getEl().dom).hasHTML('New content');
            });
        });

        describe("data", function() {
            it("should work with array data - data renderer", function() {
                makeComponent({
                    renderTo: document.body,
                    tpl: '<tpl for=".">{name}</tpl>'
                });
                makeLoader({
                    renderer: 'data'
                });
                loadAndComplete('[{"name": "foo"}, {"name": "bar"}, {"name": "baz"}]');
                expect(comp.getEl().dom).hasHTML('foobarbaz');
            });

            it("should work with an object", function() {
                makeComponent({
                    renderTo: document.body,
                    tpl: '{name} - {age}'
                });
                makeLoader({
                    renderer: 'data'
                });
                loadAndComplete('{"name": "foo", "age": 21}');
                expect(comp.getEl().dom).hasHTML('foo - 21');
            });

            it("should fail if the data could not be decoded", function() {
                var o = {
                        fn: function(loader, success) {
                            result = success;
                        }
                    },
                    result;

                spyOn(o, 'fn').andCallThrough();

                makeComponent({
                    renderTo: document.body,
                    tpl: '{name}'
                });
                makeLoader({
                    renderer: 'data',
                    callback: o.fn
                });

                // avoid Ext.Error console pollution
                var global = Ext.global;

                Ext.global = {};
                loadAndComplete('not data');
                Ext.global = global;
                expect(result).toBeFalsy();
                expect(comp.getEl().dom).hasHTML('');
            });
        });

        describe("component", function() {
            beforeEach(function() {
                makeContainer({
                    renderTo: document.body
                });
                makeLoader({
                    renderer: 'component'
                });
            });

            it("should exception if using a non-container", function() {
                comp.destroy();
                makeComponent({
                    renderTo: document.body
                });
                loader.setTarget(comp);
                loader.load();
                expect(function() {
                    mockComplete('{"html": "foo"}');
                }).toThrow('Components can only be loaded into a container');
            });

            it("should add a single item", function() {
                loader.load();
                mockComplete('{"xtype": "component", "html": "new item"}');
                expect(comp.items.first().getEl().dom).hasHTML('new item');
            });

            it("should add multiple items", function() {
                loader.load();
                mockComplete('[{"xtype": "component", "html": "new item1"}, {"xtype": "component", "html": "new item2"}]');
                expect(comp.items.first().getEl().dom).hasHTML('new item1');
                expect(comp.items.last().getEl().dom).hasHTML('new item2');
            });

            it("should respect the removeAll option", function() {
                loader.removeAll = true;
                loader.load();
                comp.add({
                    xtype: "component"
                });
                mockComplete('[{"xtype": "component", "html": "new item1"}, {"xtype": "component", "html": "new item2"}]');
                expect(comp.items.getCount()).toEqual(2);
            });

            it("should give precedence to removeAll in the config options", function() {
                loader.load({
                    removeAll: true
                });
                comp.add({
                    xtype: "component"
                });
                mockComplete('[{"xtype": "component", "html": "new item1"}, {"xtype": "component", "html": "new item2"}]');
                expect(comp.items.getCount()).toEqual(2);
            });

            it("should fail if items could not be decoded", function() {
                var o = {
                        fn: function(loader, success) {
                            result = success;
                        }
                    },
                    result;

                spyOn(o, 'fn').andCallThrough();
                loader.callback = o.fn;

                // avoid Ext.Error console pollution
                var global = Ext.global;

                Ext.global = {};
                loadAndComplete('not items');
                Ext.global = global;
                expect(result).toBeFalsy();
                expect(comp.items.getCount()).toEqual(0);
            });
        });

        describe("panel", function() {
            beforeEach(function() {
                comp = new Ext.panel.Panel({
                    title: 'Panel',
                    height: 400,
                    width: 600,
                    renderTo: document.body
                });
                makeLoader({
                    renderer: 'html'
                });
            });

            it('should use the component as the scope for inline scripts', function() {
                var callbackScope = {};

                loader.load({
                    scripts: true,
                    success: function() {
                        this.foo = 'bar';
                    },
                    scope: callbackScope
                });
                mockComplete('<script>this.setTitle("New title");</script>New content');

                waitsFor(function() {
                    return comp.getTitle() === 'New title';
                }, 'the inline script to be executed');

                runs(function() {
                    // Check that content is updated.
                    expect(comp.body.dom.textContent || comp.body.dom.innerText).toBe('New content');

                    // Check that success callback had the right scope
                    expect(callbackScope.foo).toBe('bar');
                });
            });
            it('should use the rendererScope as the scope for inline scripts', function() {
                var passedRendererScope = {};

                loader.load({
                    scripts: true,
                    rendererScope: passedRendererScope
                });
                mockComplete('<script>this.foo = "bar";</script>New content');

                waitsFor(function() {
                    return passedRendererScope.foo === 'bar';
                }, 'callback to be executed with the correct scope');

                runs(function() {
                    // Check that content is updated.
                    expect(comp.body.dom.textContent || comp.body.dom.innerText).toBe('New content');
                });
            });
        });

        describe("custom renderer", function() {
            it("should use a custom renderer if one is specified", function() {
                var o = {
                    fn: function(loader, response, options) {
                        loader.getTarget().update('This is the ' + response.responseText);
                    }
                };

                spyOn(o, 'fn').andCallThrough();

                makeComponent({
                    renderTo: document.body
                });
                makeLoader({
                    renderer: o.fn
                });
                loadAndComplete('response');
                expect(o.fn).toHaveBeenCalled();
                expect(comp.getEl().dom).hasHTML('This is the response');
            });

            it("should fail if the renderer returns false", function() {
                var result;

                makeComponent({
                    renderTo: document.body
                });
                makeLoader({
                    renderer: function() {
                        return false;
                    },

                    callback: function(loader, success) {
                        result = success;
                    }
                });
                loadAndComplete();
                expect(result).toBeFalsy();
                expect(comp.getEl().dom).hasHTML('');
            });
        });
    });
});
