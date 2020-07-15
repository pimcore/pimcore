topSuite("Ext.form.action.DirectLoad", ['Ext.direct.RemotingProvider', 'Ext.form.Basic'], function() {
    var provider, action, loadSpy, loadSpy2, submitSpy;

    function makeApi(cfg) {
        cfg = Ext.apply({
            "namespace": "spec",
            type: "remoting",
            url: "fake"
        }, cfg);

        provider = Ext.direct.Manager.addProvider(cfg);
    }

    function makeSpy(name) {
        var directCfg = spec.TestDirect[name].directCfg,
            spy = spyOn(spec.TestDirect, name);

        spy.directCfg = directCfg;

        return spy;
    }

    function createAction(config) {
        config = config || {};

        if (!config.form) {
            config.form = {};
        }

        Ext.applyIf(config.form, {
            clearInvalid: Ext.emptyFn,
            markInvalid: Ext.emptyFn,
            getValues: Ext.emptyFn,
            setValues: Ext.emptyFn,
            getFields: function() {
                return Ext.create('Ext.util.MixedCollection');
            },
            afterAction: Ext.emptyFn,
            isValid: function() { return true; },
            timeout: Ext.form.Basic.prototype.timeout,
            api: {
                load: 'spec.TestDirect.load',
                submit: 'spec.TestDirect.submit'
            }
        });

        action = new Ext.form.action.DirectLoad(config);

        return action;
    }

    function createActionWithCallbackArgs(config, result, trans) {
        createAction(config);

        loadSpy.andCallFake(function() {
            var cb = arguments[1],
                scope = arguments[2];

            cb.call(scope, result, trans);
        });
    }

    beforeEach(function() {
        makeApi({
            actions: {
                TestDirect: [{
                    name: 'load',
                    len: 1
                }, {
                    name: 'load2',
                    len: 2
                }, {
                    name: 'submit',
                    formHandler: true
                }]
            }
        });

        loadSpy = makeSpy('load');
        loadSpy2 = makeSpy('load2');
        submitSpy = makeSpy('submit');
    });

    afterEach(function() {
        if (provider) {
            Ext.direct.Manager.removeProvider(provider);
            provider.destroy();
        }

        Ext.direct.Manager.clearAllMethods();

        if (action) {
            action.destroy();
        }

        loadSpy = loadSpy2 = submitSpy = action = provider = window.spec = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.form.Action.DirectLoad as the alternate class name", function() {
            expect(Ext.form.action.DirectLoad.prototype.alternateClassName).toEqual("Ext.form.Action.DirectLoad");
        });

        it("should allow the use of Ext.form.Action.DirectLoad", function() {
            expect(Ext.form.Action.DirectLoad).toBeDefined();
        });
    });

    it("should be registered in the action manager under the alias 'formaction.directload'", function() {
        var inst = Ext.ClassManager.instantiateByAlias('formaction.directload', {});

        expect(inst instanceof Ext.form.action.DirectLoad).toBeTruthy();
    });

    describe("run", function() {
        it("should not resolve 'load' method before first invocation", function() {
            createAction();

            expect(action.form.api.load).toBe('spec.TestDirect.load');
        });

        it("should resolve 'load' method on first invocation", function() {
            createAction();
            action.run();

            expect(Ext.isFunction(action.form.api.load)).toBeTruthy();
        });

        it("should resolve prefixed 'load' method", function() {
            createAction({
                form: {
                    api: {
                        prefix: 'spec.TestDirect',
                        load: 'load',
                        submit: 'submit'
                    }
                }
            });

            action.run();

            expect(loadSpy).toHaveBeenCalled();
        });

        it("should raise an error if it cannot resolve 'load' method", function() {
            createAction();
            window.spec = null;

            var ex = "Cannot resolve Direct API method 'spec.TestDirect.load' for " +
                     "load action in Ext.form.action.DirectLoad instance with id: unknown";

            expect(function() {
                action.run();
            }).toThrow(ex);
        });

        it("should invoke the 'load' function in the BasicForm's 'api' config", function() {
            createAction();
            action.run();
            expect(action.form.api.load).toHaveBeenCalled();
        });

        it("should pass the params as a single object argument if 'paramsAsHash' is true", function() {
            createAction({
                form: {
                    paramsAsHash: true
                },
                params: {
                    foo: 'bar'
                }
            });

            action.run();
            expect(action.form.api.load.mostRecentCall.args[0]).toEqual({ foo: 'bar' });
        });

        it("should pass the param values as separate arguments in the 'paramOrder' order if specified", function() {
            createAction({
                form: {
                    api: {
                        load: 'spec.TestDirect.load2'
                    },
                    paramOrder: ['one', 'two']
                },
                params: {
                    one: 'foo',
                    two: 'bar'
                }
            });

            action.run();

            var args = action.form.api.load.mostRecentCall.args;

            expect(args[0]).toEqual('foo');
            expect(args[1]).toEqual('bar');
        });

        it("should grab params from the action's 'params' config and the BasicForm's 'baseParams' config", function() {
            createAction({
                form: {
                    paramsAsHash: true,
                    baseParams: {
                        baseOne: '1',
                        baseTwo: '2'
                    }
                },
                params: {
                    one: '1',
                    two: '2'
                }
            });

            action.run();

            expect(action.form.api.load.mostRecentCall.args[0]).toEqual({
                baseOne: '1',
                baseTwo: '2',
                one: '1',
                two: '2'
            });
        });

        it("should pass the onSuccess callback function and the callback scope as the final 2 arguments", function() {
            createAction({
                form: {
                    paramsAsHash: true
                },
                params: {
                    foo: 'bar'
                }
            });

            action.run();

            var args = action.form.api.load.mostRecentCall.args;

            expect(typeof args[args.length - 3]).toEqual('function');
            expect(args[args.length - 2]).toBe(action);
        });

        describe("metadata", function() {
            beforeEach(function() {
                // Grr, this is a kludge :(
                loadSpy.directCfg.metadata = {
                    params: ['foo', 'bar']
                };

                createAction({
                    form: {
                        metadata: { foo: 42, bar: false }
                    }
                });
            });

            it("should override form metadata with options values", function() {
                // Form.load(options) will apply options via Action constructor
                Ext.apply(action, { metadata: { foo: -1, bar: true } });

                action.run();

                expect(loadSpy.mostRecentCall.args[3]).toEqual({
                    metadata: { foo: -1, bar: true },
                    timeout: 30000
                });
            });

            it("should default to form metadata", function() {
                action.run();

                expect(loadSpy.mostRecentCall.args[3]).toEqual({
                    metadata: { foo: 42, bar: false },
                    timeout: 30000
                });
            });
        });
    });

    describe("load failure", function() {
        // effects
        it("should set the Action's failureType property to LOAD_FAILURE", function() {
            createActionWithCallbackArgs({}, {}, {});
            action.run();
            expect(action.failureType).toEqual(Ext.form.action.Action.LOAD_FAILURE);
        });

        it("should call the BasicForm's afterAction method with a false success param", function() {
            createActionWithCallbackArgs({}, {}, {});
            spyOn(action.form, 'afterAction');
            action.run();
            expect(action.form.afterAction).toHaveBeenCalledWith(action, false);
        });

        // causes
        it("should fail if the callback is passed an exception with type=Ext.direct.Manager.exceptions.SERVER", function() {
            createActionWithCallbackArgs({}, {}, { type: Ext.direct.Manager.exceptions.SERVER });
            action.run();
            expect(action.failureType).toEqual(Ext.form.action.Action.LOAD_FAILURE);
        });

        it("should fail if the result object does not have success=true", function() {
            createActionWithCallbackArgs({}, { success: false, data: {} }, {});
            action.run();
            expect(action.failureType).toEqual(Ext.form.action.Action.LOAD_FAILURE);
        });

        it("should fail if the result object does not have a data member", function() {
            createActionWithCallbackArgs({}, { success: true }, {});
            action.run();
            expect(action.failureType).toEqual(Ext.form.action.Action.LOAD_FAILURE);
        });
    });

    describe("load success", function() {
        beforeEach(function() {
            createActionWithCallbackArgs({}, { success: true, data: { foo: 'bar' } }, {});
        });

        it("should call the BasicForm's clearInvalid method", function() {
            spyOn(action.form, 'clearInvalid');
            action.run();
            expect(action.form.clearInvalid).toHaveBeenCalled();
        });

        it("should call the BasicForm's setValues method with the result data object", function() {
            spyOn(action.form, 'setValues');
            action.run();
            expect(action.form.setValues).toHaveBeenCalledWith({ foo: 'bar' });
        });

        it("should invoke the BasicForm's afterAction method with a true success param", function() {
            spyOn(action.form, 'afterAction');
            action.run();
            expect(action.form.afterAction).toHaveBeenCalledWith(action, true);
        });
    });
});
