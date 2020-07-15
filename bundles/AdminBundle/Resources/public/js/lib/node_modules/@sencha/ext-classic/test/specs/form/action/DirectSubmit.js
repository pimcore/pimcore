topSuite("Ext.form.action.DirectSubmit", ['Ext.direct.RemotingProvider', 'Ext.form.Basic'], function() {
    var provider, action, loadSpy, submitSpy;

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

        action = new Ext.form.action.DirectSubmit(config);

        return action;
    }

    function createActionWithCallbackArgs(config, result, trans) {
        createAction(config);

        submitSpy.andCallFake(function() {
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
                    name: 'submit',
                    formHandler: true
                }]
            }
        });

        loadSpy = makeSpy('load');
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

        loadSpy = submitSpy = action = provider = window.spec = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.form.Action.DirectSubmit as the alternate class name", function() {
            expect(Ext.form.action.DirectSubmit.prototype.alternateClassName).toEqual("Ext.form.Action.DirectSubmit");
        });

        it("should allow the use of Ext.form.Action.DirectSubmit", function() {
            expect(Ext.form.Action.DirectSubmit).toBeDefined();
        });
    });

    it("should be registered in the action manager under the alias 'formaction.directsubmit'", function() {
        var inst = Ext.ClassManager.instantiateByAlias('formaction.directsubmit', {});

        expect(inst instanceof Ext.form.action.DirectSubmit).toBeTruthy();
    });

    describe("run", function() {
        it("should not resolve 'submit' method before first invocation", function() {
            createAction();

            expect(action.form.api.submit).toBe('spec.TestDirect.submit');
        });

        it("should resolve 'submit' method on first invocation", function() {
            createAction();
            action.run();

            expect(Ext.isFunction(action.form.api.submit)).toBeTruthy();
        });

        it("should resolve prefixed 'submit' method", function() {
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

            expect(submitSpy).toHaveBeenCalled();
        });

        it("should raise an error if it cannot resolve 'submit' method", function() {
            spec = null;

            createAction();

            var ex = "Cannot resolve Direct API method 'spec.TestDirect.load' for " +
                     "load action in Ext.form.action.DirectSubmit instance with id: unknown";

            expect(function() {
                action.run();
            }).toThrow(ex);
        });

        it("should invoke the 'submit' function in the BasicForm's 'api' config", function() {
            createAction();
            action.run();
            expect(submitSpy).toHaveBeenCalled();
        });

        it("should pass a form element containing all the field values and configured base params as the first argument", function() {
            var fieldValues = { one: '1', two: '2', three: '3' },
                allParams = Ext.apply({}, fieldValues, { fromParams: '1', fromBaseParams: '1' });

            createAction({
                params: { fromParams: '1' },
                form: {
                    baseParams: { fromBaseParams: '1' },
                    getValues: function() {
                        return fieldValues;
                    }
                }
            });

            spyOn(Ext, 'removeNode');
            action.run();
            var form = Ext.removeNode.mostRecentCall.args[0];

            expect(form).toBeDefined();
            expect(form.tagName).toEqual('FORM');

            // collect the name-value pairs from the form
            var valuesFromForm = {},
                inputs = form.getElementsByTagName("*"),
                i = 0,
                len = inputs.length;

            for (; i < len; i++) {
                valuesFromForm[inputs[i].name] = inputs[i].value;
            }

            expect(valuesFromForm).toEqual(allParams);
            Ext.removeNode.andCallThrough();

            Ext.removeNode(form);
        });

        it("should pass the callback function as the second argument", function() {
            createAction();
            action.run();
            var args = submitSpy.mostRecentCall.args;

            expect(typeof args[1]).toEqual('function');
        });

        it("should pass the callback scope as the third argument", function() {
            createAction();
            action.run();
            var args = submitSpy.mostRecentCall.args;

            expect(args[2]).toBe(action);
        });

        describe("timeouts", function() {
            beforeEach(function() {
                createAction();
            });

            it("should pass default timeout", function() {
                action.run();

                var args = submitSpy.mostRecentCall.args;

                expect(args[3].timeout).toBe(30000);
            });

            it("should pass timeout parameter if it is specified in a form", function() {
                createAction({
                    form: {
                        timeout: 42
                    }
                });

                action.run();

                var args = submitSpy.mostRecentCall.args;

                expect(args[3].timeout).toBe(42000);
            });
        });

        describe("metadata", function() {
            beforeEach(function() {
                createAction({
                    form: {
                        metadata: { foo: 42, bar: false }
                    }
                });
            });

            it("should override form metadata with options values", function() {
                // Form.submit(options) will apply options via Action constructor
                Ext.apply(action, { metadata: { foo: -1, bar: true } });

                action.run();

                expect(submitSpy.mostRecentCall.args[3]).toEqual({
                    timeout: 30000,
                    metadata: { foo: -1, bar: true }
                });
            });

            it("should default to form metadata", function() {
                action.run();

                expect(submitSpy.mostRecentCall.args[3]).toEqual({
                    timeout: 30000,
                    metadata: { foo: 42, bar: false }
                });
            });
        });
    });

    describe("validation", function() {
        beforeEach(function() {
            spyOn(Ext.Ajax, 'request'); // block ajax request
        });

        it("should validate by default", function() {
            createAction();
            spyOn(action.form, 'isValid');
            action.run();
            expect(action.form.isValid).toHaveBeenCalled();
        });

        it("should not validate if the 'clientValidation' config is false", function() {
            createAction({ clientValidation: false });
            spyOn(action.form, 'isValid');
            action.run();
            expect(action.form.isValid).not.toHaveBeenCalled();
        });

        it("should set the failureType to CLIENT_INVALID if validation fails", function() {
            createAction({
                form: {
                    isValid: function() { return false; }
                }
            });
            action.run();
            expect(action.failureType).toEqual(Ext.form.action.Action.CLIENT_INVALID);
        });

        it("should call the BasicForm's afterAction method with success=false if validation fails", function() {
            createAction({
                form: {
                    isValid: function() { return false; }
                }
            });
            spyOn(action.form, 'afterAction');
            action.run();
            expect(action.form.afterAction).toHaveBeenCalledWith(action, false);
        });
    });

    describe("submit failure", function() {
        // causes
        it("should fail if the callback is passed an exception with type=Ext.direct.Manager.exceptions.SERVER", function() {
            createActionWithCallbackArgs({}, {}, { type: Ext.direct.Manager.exceptions.SERVER });
            action.run();
            expect(action.failureType).toBeDefined();
        });

        it("should fail if the result object does not have success=true", function() {
            createActionWithCallbackArgs({}, { success: false }, {});
            action.run();
            expect(action.failureType).toBeDefined();
        });

        // effects
        it("should set the Action's failureType property to SERVER_INVALID", function() {
            createActionWithCallbackArgs({}, {}, {});
            action.run();
            expect(action.failureType).toEqual(Ext.form.action.Action.SERVER_INVALID);
        });

        it("should call the BasicForm's afterAction method with a false success param", function() {
            createActionWithCallbackArgs({}, {}, {});
            spyOn(action.form, 'afterAction');
            action.run();
            expect(action.form.afterAction).toHaveBeenCalledWith(action, false);
        });

        it("should call the BasicForm's markInvalid method with any errors in the result", function() {
            createActionWithCallbackArgs({}, { success: false, errors: { foo: 'bar' } }, {});
            spyOn(action.form, 'markInvalid');
            action.run();
            expect(action.form.markInvalid).toHaveBeenCalledWith({ foo: "bar" });
        });
    });

    describe("submit success", function() {
        beforeEach(function() {
            createActionWithCallbackArgs({}, { success: true }, {});
        });

        it("should treat a result with success:true as success", function() {
            expect(action.failureType).not.toBeDefined();
        });

        it("should invoke the BasicForm's afterAction method with a true success param", function() {
            spyOn(action.form, 'afterAction');
            action.run();
            expect(action.form.afterAction).toHaveBeenCalledWith(action, true);
        });
    });
});
