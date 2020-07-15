topSuite("Ext.ElementLoader", function() {
    var getAjaxOptions, loadAndComplete, loadAndFail, mockComplete, makeLoader, loader, el;

    beforeEach(function() {
        // add global variable in whitelist
        MockAjaxManager.addMethods();

        el = Ext.getBody().createChild({
            id: 'elementloader'
        });

        makeLoader = function(cfg) {
            cfg = cfg || {};
            Ext.applyIf(cfg, {
                url: 'url',
                target: el
            });
            loader = new Ext.ElementLoader(cfg);
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

        if (el) {
            el.remove();
        }

        if (loader) {
            loader.destroy();
        }

        getAjaxOptions = loadAndFail = loadAndComplete = mockComplete = makeLoader = loader = el = null;
    });

    describe("defaults", function() {
        beforeEach(function() {
            loader = new Ext.ElementLoader();
        });

        it("should have a null url", function() {
            expect(loader.url).toBeNull();
        });

        it("should have null params", function() {
            expect(loader.params).toBeNull();
        });

        it("should have null baseParams", function() {
            expect(loader.baseParams).toBeNull();
        });

        it("should have autoLoad set as false", function() {
            expect(loader.autoLoad).toBeFalsy();
        });

        it("should have a null target", function() {
            expect(loader.target).toBeNull();
        });

        it("should set loadMask to false", function() {
            expect(loader.loadMask).toBeFalsy();
        });

        it("should have null ajax options", function() {
            expect(loader.ajaxOptions).toBeNull();
        });

        it("should not have a callback function", function() {
            expect(loader.callback).toBeUndefined();
        });

        it("should not have a success function", function() {
            expect(loader.success).toBeUndefined();
        });

        it("should not have a failure function", function() {
            expect(loader.failure).toBeUndefined();
        });

        it("should not have a scope", function() {
            expect(loader.scope).toBeUndefined();
        });

        it("should default scripts to false", function() {
            expect(loader.scripts).toBeFalsy();
        });
    });

    describe("masking", function() {
        it("should not mask by default", function() {
            makeLoader();
            loader.load();
            expect(el.isMasked()).toBe(false);
        });

        xit("should unmask after the request completes", function() {
            makeLoader({
                loadMask: true
            });
            loader.load();
            expect(el.isMasked()).toBe(true);
            mockComplete();
            expect(el.isMasked()).toBe(false);
        });

        it("should accept a masking message", function() {
            makeLoader({
                loadMask: 'Waiting'
            });
            loader.load();
            expect(el.down('.x-mask-msg', true).firstChild.firstChild).hasHTML('Waiting');
            mockComplete();
        });

        xit("should use the masking load option", function() {
            makeLoader();
            loader.load({
                loadMask: true
            });
            expect(el.isMasked()).toBe(true);
            mockComplete();
        });

        it("should give precedence to the load option", function() {
            makeLoader({
                loadMask: 'Waiting'
            });
            loader.load({
                loadMask: 'Other'
            });
            expect(el.down('.x-mask-msg', true).firstChild.firstChild).hasHTML('Other');
            mockComplete();
        });
    });

    describe("url", function() {
        it("should throw an exception if there's no url", function() {
            loader = new Ext.ElementLoader({
                target: el
            });
            expect(function() {
                loader.load();
            }).toThrow('You must specify the URL from which content should be loaded');
        });

        it("should use the url in the config", function() {
            makeLoader();
            loader.load();
            expect(getAjaxOptions().url).toEqual('url');
        });

        it("should use the url in the load options", function() {
            loader = new Ext.ElementLoader({
                target: el
            });
            loader.load({
                url: 'other'
            });
            expect(getAjaxOptions().url).toEqual('other');
        });

        it("should give precedence to the url in the load options", function() {
            makeLoader();
            loader.load({
                url: 'other'
            });
            expect(getAjaxOptions().url).toEqual('other');
        });
    });

    describe("params/baseParams", function() {
        var loadAndCheck;

        beforeEach(function() {
            loadAndCheck = function(result, config, loadOptions) {
                makeLoader(config || {});
                loader.load(loadOptions || {});
                expect(getAjaxOptions().params).toEqual(result);
            };
        });

        afterEach(function() {
            loadAndCheck = false;
        });

        it("should send no params by default", function() {
            loadAndCheck({});
        });

        it("should send along baseParams", function() {
            loadAndCheck({
                p1: 1,
                p2: 'param2'
            }, {
                baseParams: {
                    p1: 1,
                    p2: 'param2'
                }
            });
        });

        it("should send along params", function() {
            loadAndCheck({
                p1: 2,
                p2: 'param1'
            }, {
                params: {
                    p1: 2,
                    p2: 'param1'
                }
            });
        });

        it("should combine params and baseParams", function() {
            loadAndCheck({
                p1: 1,
                p2: 2
            }, {
                baseParams: {
                    p2: 2
                },
                params: {
                    p1: 1
                }
            });
        });

        it("should favour baseParams over params", function() {
            loadAndCheck({
                p1: 1
            }, {
                baseParams: {
                    p1: 1
                },
                params: {
                    p1: 2
                }
            });
        });

        it("should use params specified in the options", function() {
            loadAndCheck({
                p1: 1
            }, null, {
                params: {
                    p1: 1
                }
            });
        });

        it("should combine baseParams with load params", function() {
            loadAndCheck({
                p1: 'some',
                p2: 'param'
            }, {
                baseParams: {
                    p1: 'some'
                }
            }, {
                params: {
                    p2: 'param'
                }
            });
        });

        it("should combine config params with load params", function() {
            loadAndCheck({
                p1: 'some',
                p2: 'param'
            }, {
                params: {
                    p1: 'some'
                }
            }, {
                params: {
                    p2: 'param'
                }
            });
        });

        it("should favour the load params over the config params", function() {
            loadAndCheck({
                p1: 'param'
            }, {
                params: {
                    p1: 'some'
                }
            }, {
                params: {
                    p1: 'param'
                }
            });
        });

        it("should prefer baseParams over load params", function() {
            loadAndCheck({
                p1: 'favoured'
            }, {
                baseParams: {
                    p1: 'favoured'
                }
            }, {
                params: {
                    p1: 'other'
                }
            });
        });

        it("should combine all 3 together", function() {
            loadAndCheck({
                p1: 1,
                p2: 2,
                p3: 3
            }, {
                baseParams: {
                    p1: 1
                },
                params: {
                    p2: 2
                }
            }, {
                params: {
                    p3: 3
                }
            });
        });
    });

    describe("autoLoad", function() {
        it("should automatically load when autoLoad is set", function() {
            makeLoader({
                autoLoad: true
            });
            mockComplete();
            expect(el.dom).hasHTML('response');
        });

        it("should accept options for the request", function() {
            makeLoader({
                autoLoad: {
                    params: {
                        p1: 1
                    }
                }
            });
            expect(getAjaxOptions().params).toEqual({
                p1: 1
            });
        });
    });

    describe("ajaxOptions", function() {
        it("should pass no options by default", function() {
            makeLoader();
            loader.load();
            expect(getAjaxOptions().timeout).toBeUndefined();
        });

        it("should include any default options", function() {
            makeLoader({
                ajaxOptions: {
                    timeout: 10000
                }
            });
            loader.load();
            expect(getAjaxOptions().timeout).toEqual(10000);
        });

        it("should include any options specified in the load", function() {
            makeLoader();
            loader.load({
                ajaxOptions: {
                    timeout: 10000
                }
            });
            expect(getAjaxOptions().timeout).toEqual(10000);
        });

        it("should combine options from the config and on the load", function() {
            makeLoader({
                ajaxOptions: {
                    username: 'user'
                }
            });
            loader.load({
                ajaxOptions: {
                    timeout: 10000
                }
            });
            expect(getAjaxOptions().timeout).toEqual(10000);
            expect(getAjaxOptions().username).toEqual('user');
        });

        it("should give precedence to ajax options on the load", function() {
            makeLoader({
                ajaxOptions: {
                    timeout: 10000
                }
            });
            loader.load({
                ajaxOptions: {
                    timeout: 5000
                }
            });
            expect(getAjaxOptions().timeout).toEqual(5000);
        });
    });

    describe("target", function() {
        var E;

        beforeEach(function() {
            E = Ext.ElementLoader;
        });

        afterEach(function() {
            E = null;
        });

        it("should take the target from the config object", function() {
            makeLoader();
            expect(loader.getTarget()).toEqual(el);
        });

        it("should take a string config", function() {
            loader = new E({
                target: 'elementloader'
            });
            expect(loader.getTarget()).toEqual(el);
        });

        it("should take a dom object config", function() {
            loader = new E({
                target: el.dom
            });
            expect(loader.getTarget()).toEqual(el);
        });

        it("should assign the target", function() {
            loader = new E();
            loader.setTarget(el);
            expect(loader.getTarget()).toEqual(el);
        });

        it("should assign a new target", function() {
            var other = Ext.getBody().createChild();

            makeLoader();
            loader.setTarget(other);
            expect(loader.getTarget()).toEqual(other);
            other.remove();
        });

        it("should assign a new target via id", function() {
            loader = new E();
            loader.setTarget('elementloader');
            expect(loader.getTarget()).toEqual(el);
        });

        it("should assign a new target via DOM element", function() {
            loader = new E();
            loader.setTarget(el.dom);
            expect(loader.getTarget()).toEqual(el);
        });

        it("should return null if there is no target", function() {
            loader = new E();
            expect(loader.getTarget()).toBeNull();
        });

        it("should abort any active request if the target changes", function() {
            var other = Ext.getBody().createChild(),
                o = {
                    fn: function() {}
                };

            spyOn(o, 'fn').andCallThrough();
            makeLoader();
            loader.load({
                success: o.fn
            });
            loader.setTarget(other);
            expect(o.fn).not.toHaveBeenCalled();
            other.remove();
        });

        it("should throw an exception if no target is specified", function() {
            loader = new E({
                url: 'url'
            });
            expect(function() {
                loader.load();
            }).toThrow('A valid target is required when loading content');
        });
    });

    describe("renderers", function() {

        it("should update the target with the response text", function() {
            makeLoader();
            loadAndComplete('New content');
            expect(el.dom).hasHTML('New content');
        });

        describe("scripts", function() {
            afterEach(function() {
                try {
                    delete window.ElementLoaderTest;
                }
                catch (e) {
                    window.ElementLoaderTest = undefined;
                }
            });
            it("should process inline scripts", function() {
                makeLoader({
                    scripts: true
                });

                runs(function() {
                    loadAndComplete('<script type="text/javascript">window.ElementLoaderTest = true;</script>');
                });

                waitsFor(function() {
                    return window.ElementLoaderTest === true;
                }, "Script never executed");
            });

            it("should process external scripts", function() {
                makeLoader({
                    scripts: true
                });

                runs(function() {
                    loadAndComplete('<script type="text/javascript" src="../resources/ExternalScript.js"></script>');
                });

                waitsFor(function() {
                    return window.ElementLoaderTest === true;
                }, "Script never executed");
            });

            it("should use the scripts load option and give it precedence", function() {
                makeLoader();

                runs(function() {
                    loadAndComplete('<script type="text/javascript">window.ElementLoaderTest = true;</script>', {
                        scripts: true
                    });
                });

                waitsFor(function() {
                    return window.ElementLoaderTest === true;
                }, "Script never executed");
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
                makeLoader({
                    renderer: o.fn
                });
                loadAndComplete('response');
                expect(o.fn).toHaveBeenCalled();
                expect(el.dom).hasHTML('This is the response');
            });

            it("should fail if the renderer returns false", function() {
                var result;

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
                expect(el.dom).hasHTML('');
            });
        });

        describe("scope", function() {
            var spy;

            beforeEach(function() {
                spy = jasmine.createSpy();
            });

            it("should default the scope to the loader", function() {
                makeLoader({
                    renderer: spy
                });
                loadAndComplete();
                expect(spy.mostRecentCall.object).toBe(loader);
            });

            it("should use a passed scope", function() {
                var o = {};

                makeLoader({
                    renderer: spy,
                    rendererScope: o
                });
                loadAndComplete();
                expect(spy.mostRecentCall.object).toBe(o);
            });

            it("should favour the load scope over a config scope", function() {
                var o1 = {},
                    o2 = {};

                makeLoader({
                    renderer: spy,
                    rendererScope: o1
                });
                loadAndComplete('', {
                    rendererScope: o2
                });
                expect(spy.mostRecentCall.object).toBe(o2);
            });
        });
    });

    describe("events", function() {
        var o;

        beforeEach(function() {
            o = {
                trueFn: function(loader) {},

                falseFn: function() {}
            };
            spyOn(o, 'trueFn');
            spyOn(o, 'falseFn').andReturn(false);
        });

        afterEach(function() {
            o = null;
        });

        describe("beforeload", function() {
            it("should fire the beforeload event", function() {
                makeLoader({
                    listeners: {
                        beforeload: o.trueFn
                    }
                });
                loader.load();
                expect(o.trueFn).toHaveBeenCalled();
            });

            it("should cancel the load if beforeload returns false", function() {
                makeLoader({
                    listeners: {
                        beforeload: o.falseFn
                    }
                });
                loader.load();
                expect(o.falseFn).toHaveBeenCalled();
                expect(el.dom).hasHTML('');
            });
        });

        describe("load", function() {
            it("should fire the load event", function() {
                makeLoader({
                    listeners: {
                        load: o.trueFn
                    }
                });
                loadAndComplete();
                expect(o.trueFn).toHaveBeenCalled();
            });

            it("should not fire if beforeload returns false", function() {
                makeLoader({
                    listeners: {
                        beforeload: o.falseFn,
                        load: o.trueFn
                    }
                });
                loader.load();
                expect(o.trueFn).not.toHaveBeenCalled();
            });

            it("should not fire if the ajax request fails", function() {
                makeLoader({
                    listeners: {
                        load: o.trueFn
                    }
                });
                loadAndFail();
                expect(o.trueFn).not.toHaveBeenCalled();
            });

            it("should not fire if the renderer returns false", function() {
                makeLoader({
                    renderer: o.falseFn,
                    listeners: {
                        load: o.trueFn
                    }
                });
                loadAndComplete();
                expect(o.trueFn).not.toHaveBeenCalled();
            });
        });

        describe("exception", function() {
            it("should fire the exception event", function() {
                makeLoader({
                    listeners: {
                        exception: o.trueFn
                    }
                });
                loadAndFail();
                expect(o.trueFn).toHaveBeenCalled();
            });

            it("should not fire if beforeload returns false", function() {
                makeLoader({
                    listeners: {
                        beforeload: o.falseFn,
                        exception: o.trueFn
                    }
                });
                loader.load();
                expect(o.trueFn).not.toHaveBeenCalled();
            });

            it("should not fire if the ajax request is successful", function() {
                makeLoader({
                    listeners: {
                        exception: o.trueFn
                    }
                });
                loadAndComplete();
                expect(o.trueFn).not.toHaveBeenCalled();
            });

            it("should fire if the renderer returns false", function() {
                makeLoader({
                    renderer: o.falseFn,
                    listeners: {
                        exception: o.trueFn
                    }
                });
                loadAndComplete();
                expect(o.trueFn).toHaveBeenCalled();
            });
        });
    });

    describe("callbacks", function() {
        var me,
            o;

        beforeEach(function() {
            o = {
                callback: function() {
                    me = this;
                },

                success: function() {
                    me = this;
                },

                failure: function() {
                    me = this;
                },

                other: function() {

                }
            };
            spyOn(o, 'callback').andCallThrough();
            spyOn(o, 'success').andCallThrough();
            spyOn(o, 'failure').andCallThrough();
            spyOn(o, 'other').andCallThrough();
        });

        afterEach(function() {
            me = o = null;
        });

        describe("scope", function() {

            it("should default to the loader instance", function() {
                makeLoader({
                    callback: o.callback
                });
                loadAndComplete();
                expect(me).toEqual(loader);
            });

            it("should use the scope specified on the instance", function() {
                var scope = {};

                makeLoader({
                    callback: o.callback,
                    scope: scope
                });
                loadAndComplete();
                expect(me).toEqual(scope);
            });

            it("should use the scope specified in the load options", function() {
                var scope = {};

                makeLoader({
                    callback: o.callback
                });
                loadAndComplete('', {
                    scope: scope
                });
                expect(me).toEqual(scope);
            });

            it("should give precedence to the scope in the options", function() {
                var scope1 = {},
                    scope2 = {};

                makeLoader({
                    scope: scope1,
                    callback: o.callback
                });
                loadAndComplete('', {
                    scope: scope2
                });
                expect(me).toEqual(scope2);
            });
        });

        describe("success", function() {
            it("should get called with a scope", function() {
                var scope = {};

                makeLoader({
                    success: o.success,
                    scope: scope
                });
                loadAndComplete();
                expect(me).toEqual(scope);
            });

            it("should use the function specified in the class config", function() {
                makeLoader({
                    success: o.success
                });
                loadAndComplete();
                expect(o.success).toHaveBeenCalled();
            });

            it("should use the function specified in the load options", function() {
                makeLoader();
                loadAndComplete('', {
                    success: o.success
                });
                expect(o.success).toHaveBeenCalled();
            });

            it("should give precedence to the function specified in the options", function() {
                makeLoader({
                    success: o.other
                });
                loadAndComplete('', {
                    success: o.success
                });
                expect(o.success).toHaveBeenCalled();
                expect(o.other).not.toHaveBeenCalled();
            });

            it("should not fire success is the request fails", function() {
                makeLoader({
                    success: o.success
                });
                loadAndFail();
                expect(o.success).not.toHaveBeenCalled();
            });

            it("should not fire success if the renderer returns false", function() {
                makeLoader({
                    success: o.success,
                    renderer: function() {
                        return false;
                    }
                });
                loadAndComplete();
                expect(o.success).not.toHaveBeenCalled();
            });

            it("should never fire in conjunction with failure", function() {
                makeLoader({
                    success: o.success,
                    failure: o.failure
                });
                loadAndComplete();
                expect(o.success).toHaveBeenCalled();
                expect(o.failure).not.toHaveBeenCalled();
            });
        });

        describe("failure", function() {
            it("should get called with a scope", function() {
                var scope = {};

                makeLoader({
                    failure: o.failure,
                    scope: scope
                });
                loadAndFail();
                expect(me).toEqual(scope);
            });

            it("should use the function specified in the class config", function() {
                makeLoader({
                    failure: o.failure
                });
                loadAndFail();
                expect(o.failure).toHaveBeenCalled();
            });

            it("should use the function specified in the load options", function() {
                makeLoader();
                loadAndFail('', {
                    failure: o.failure
                });
                expect(o.failure).toHaveBeenCalled();
            });

            it("should give precedence to the function specified in the options", function() {
                makeLoader({
                    failure: o.other
                });
                loadAndFail('', {
                    failure: o.failure
                });
                expect(o.failure).toHaveBeenCalled();
                expect(o.other).not.toHaveBeenCalled();
            });

            it("should not fire failure is the request succeeds", function() {
                makeLoader({
                    failure: o.failure
                });
                loadAndComplete();
                expect(o.failure).not.toHaveBeenCalled();
            });

            it("should fire failure if the renderer returns false", function() {
                makeLoader({
                    failure: o.failure,
                    renderer: function() {
                        return false;
                    }
                });
                loadAndComplete();
                expect(o.failure).toHaveBeenCalled();
            });

            it("should never fire in conjunction with success", function() {
                makeLoader({
                    success: o.success,
                    failure: o.failure
                });
                loadAndFail();
                expect(o.failure).toHaveBeenCalled();
                expect(o.success).not.toHaveBeenCalled();
            });
        });

        describe("callback", function() {
            it("should get called with a scope", function() {
                var scope = {};

                makeLoader({
                    callback: o.callback,
                    scope: scope
                });
                loadAndComplete();
                expect(me).toEqual(scope);
            });

            it("should use the function specified in the class config", function() {
                makeLoader({
                    callback: o.callback
                });
                loadAndComplete();
                expect(o.callback).toHaveBeenCalled();
            });

            it("should use the function specified in the load options", function() {
                makeLoader();
                loadAndComplete('', {
                    callback: o.callback
                });
                expect(o.callback).toHaveBeenCalled();
            });

            it("should give precedence to the function specified in the options", function() {
                makeLoader({
                    callback: o.other
                });
                loadAndComplete('', {
                    callback: o.callback
                });
                expect(o.callback).toHaveBeenCalled();
                expect(o.other).not.toHaveBeenCalled();
            });

            it("should fire whenever success is fired", function() {
                makeLoader({
                    success: o.success,
                    callback: o.callback
                });
                loadAndComplete();
                expect(o.success).toHaveBeenCalled();
                expect(o.callback).toHaveBeenCalled();
            });

            it("should fire whenever failure is fired", function() {
                makeLoader({
                    failure: o.failure,
                    callback: o.callback
                });
                loadAndFail();
                expect(o.failure).toHaveBeenCalled();
                expect(o.callback).toHaveBeenCalled();
            });
        });
    });

    describe("auto refresh", function() {

        var removeSpy;

        beforeEach(function() {
            removeSpy = function(spy) {
                spy.baseObj[spy.methodName] = spy.originalValue;
            };
        });

        afterEach(function() {
            removeSpy = null;
        });

        it("should pass the options to the load method", function() {
            makeLoader({
                url: 'url'
            });

            var spy = spyOn(loader, 'load').andCallFake(function(options) {
                    removeSpy(spy);
                    loader.load(options);
                    isLoaded = true;
                }),
                isLoaded;

            loader.startAutoRefresh(50, {
                url: 'other'
            });

            waitsFor(function() {
                return isLoaded;
            });

            runs(function() {
                expect(getAjaxOptions().url).toBe('other');
            });
        });

        it("should return false when not auto refreshing", function() {
            makeLoader();
            expect(loader.isAutoRefreshing()).toBe(false);
        });

        it("should return true when auto refreshing", function() {
            makeLoader();
            loader.startAutoRefresh(50);
            expect(loader.isAutoRefreshing()).toBe(true);
        });

        it("should stop auto refreshing when destroyed", function() {
            makeLoader();
            loader.startAutoRefresh(50);
            loader.destroy();
            expect(loader.autoRefresh).toBe(null);
        });

        it("should stop refreshing when stopAutoRefresh is called", function() {
            makeLoader();
            loader.startAutoRefresh(50);
            loader.stopAutoRefresh();
            expect(loader.isAutoRefreshing()).toBe(false);
        });
    });
});
