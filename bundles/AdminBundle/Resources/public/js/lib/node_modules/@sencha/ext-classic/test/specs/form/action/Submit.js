topSuite("Ext.form.action.Submit", ['Ext.form.Basic', 'Ext.form.field.*'], function() {
    var action;

    function createAction(config) {
        config = config || {};

        if (!config.form) {
            config.form = {};
        }

        Ext.applyIf(config.form, {
            isValid: function() { return true; },
            afterAction: Ext.emptyFn,
            getValues: Ext.emptyFn,
            hasUpload: function() { return false; },
            markInvalid: Ext.emptyFn
        });
        action = new Ext.form.action.Submit(config);
    }

    afterEach(function() {
        action = undefined;
    });

    describe("alternate class name", function() {
        it("should have Ext.form.Action.Submit as the alternate class name", function() {
            expect(Ext.form.action.Submit.prototype.alternateClassName).toEqual("Ext.form.Action.Submit");
        });

        it("should allow the use of Ext.form.Action.Submit", function() {
            expect(Ext.form.Action.Submit).toBeDefined();
        });
    });

    it("should be registered in the action manager under the alias 'formaction.submit'", function() {
        var inst = Ext.ClassManager.instantiateByAlias('formaction.submit', {});

        expect(inst instanceof Ext.form.action.Submit).toBeTruthy();
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

    describe("AJAX call options", function() {
        var ajaxRequestCfg,
            formBase;

        beforeEach(function() {
            formBase = {
                getValues: function() { return { field1: 'foo', field2: 'bar' }; }
            };

            spyOn(Ext.Ajax, 'request').andCallFake(function() {
                // store what was passed to the request call for later inspection
                expect(arguments.length).toEqual(1);
                ajaxRequestCfg = arguments[0];
            });
        });

        it("should invoke Ext.Ajax.request", function() {
            createAction({ form: formBase });
            action.run();
            expect(Ext.Ajax.request).toHaveBeenCalled();
        });

        it("should use 'POST' as the ajax call method by default", function() {
            createAction({ form: formBase });
            action.run();
            expect(ajaxRequestCfg.method).toEqual('POST');
        });

        it("should use the BasicForm's 'method' config as the ajax call method if specified", function() {
            createAction({ form: Ext.apply({}, { method: 'FORMMETHOD' }, formBase) });
            action.run();
            expect(ajaxRequestCfg.method).toEqual('FORMMETHOD');
        });

        it("should use the Action's 'method' config as the ajax call method if specified", function() {
            createAction({ method: 'actionmethod', form: formBase });
            action.run();
            expect(ajaxRequestCfg.method).toEqual('ACTIONMETHOD');
        });

        it("should use the BasicForm's 'url' config as the ajax call url if specified", function() {
            createAction({ form: Ext.apply({}, { url: '/url-from-form' }, formBase) });
            action.run();
            expect(ajaxRequestCfg.url).toEqual('/url-from-form');
        });

        it("should use the Action's 'url' config as the ajax call url if specified", function() {
            createAction({ url: '/url-from-action', form: formBase });
            action.run();
            expect(ajaxRequestCfg.url).toEqual('/url-from-action');
        });

        it("should use the Action's 'headers' config as the ajax call headers if specified", function() {
            var headers = { foo: 'bar' };

            createAction({ headers: headers, form: formBase });
            action.run();
            expect(ajaxRequestCfg.headers).toBe(headers);
        });

        describe("params", function() {
            it("should add all the form's field values to the ajax call params", function() {
                createAction({ form: formBase });
                action.run();
                expect(ajaxRequestCfg.params).toEqual({ field1: 'foo', field2: 'bar' });
            });

            it("should add the BasicForm's 'baseParams' config to the ajax call params if specified", function() {
                var params = { one: '1', two: '2' };

                createAction({ form: Ext.apply({}, { baseParams: params }, formBase) });
                action.run();
                expect(ajaxRequestCfg.params).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2' });
            });

            it("should use the Action's 'params' config for the ajax call params if specfied (as an Object)", function() {
                var params = { one: '1', two: '2' };

                createAction({ params: params, form: formBase });
                action.run();
                expect(ajaxRequestCfg.params).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2' });
            });

            it("should use the Action's 'params' config for the ajax call params if specfied (as a String)", function() {
                var params = 'one=1&two=2';

                createAction({ params: params, form: formBase });
                action.run();
                expect(ajaxRequestCfg.params).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2' });
            });

            it("should concatenate the Action's 'params' config (as an Object) with the BasicForm's 'baseParams' config", function() {
                createAction({ params: { one: '1', two: '2' }, form: Ext.apply({}, { baseParams: { three: '3', four: '4' } }, formBase) });
                action.run();
                expect(ajaxRequestCfg.params).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2', three: '3', four: '4' });
            });

            it("should concatenate the Action's 'params' config (as a String) with the BasicForm's 'baseParams' config", function() {
                createAction({ params: 'one=1&two=2', form: Ext.apply({}, { baseParams: { three: '3', four: '4' } }, formBase) });
                action.run();
                expect(ajaxRequestCfg.params).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2', three: '3', four: '4' });
            });

            it("should set the jsonData if using jsonSubmit", function() {
                createAction({
                    form: formBase,
                    jsonSubmit: true
                });
                action.run();
                expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo', field2: 'bar' });
            });
        });

        it("should use the BasicForm's 'timeout' config as the ajax call timeout if specified", function() {
            createAction({ form: Ext.apply({}, { timeout: 123 }, formBase) });
            action.run();
            expect(ajaxRequestCfg.timeout).toEqual(123000);
        });

        it("should use the Action's 'timeout' config as the ajax call timeout if specified", function() {
            createAction({ timeout: 123, form: formBase });
            action.run();
            expect(ajaxRequestCfg.timeout).toEqual(123000);
        });

        it("should use the Action instance as the ajax call 'scope' parameter", function() {
            createAction({ form: formBase });
            action.run();
            expect(ajaxRequestCfg.scope).toBe(action);
        });

        describe("jsonSubmit", function() {
            it("should bind the BasicForm's field values to ajaxRequestCfg.jsonData", function() {
                createAction({ form: Ext.apply({ jsonSubmit: true }, formBase) });
                action.run();
                expect(ajaxRequestCfg.params).toBe(undefined);
                expect(ajaxRequestCfg.jsonData).not.toBe(undefined);
            });

            it("should not bind the BasicForm's field values to ajaxRequestCfg.jsonData", function() {
                createAction({ form: Ext.apply({ jsonSubmit: false }, formBase) });
                action.run();
                expect(ajaxRequestCfg.params).not.toBe(undefined);
                expect(ajaxRequestCfg.jsonData).toBe(undefined);
            });

            it("should not bind the BasicForm's field values to ajaxRequestCfg.params", function() {
                createAction({ form: Ext.apply({ jsonSubmit: true }, formBase) });
                action.run();
                expect(ajaxRequestCfg.jsonData).not.toBe(undefined);
                expect(ajaxRequestCfg.params).toBe(undefined);
            });

            it("should add all the BasicForm's field values to the ajax call parameters", function() {
                createAction({ form: Ext.apply({ jsonSubmit: true }, formBase) });
                action.run();
                expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo', field2: 'bar' });
            });

            it("should concatenate the Action's 'params' config (as an Object) with the BasicForm's 'baseParams' config", function() {
                createAction({ params: { one: '1', two: '2' }, form: Ext.apply({ jsonSubmit: true, baseParams: { three: '3', four: '4' } }, formBase) });
                action.run();
                expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2', three: '3', four: '4' });
            });

            it("should concatenate the Action's 'params' config (as a String) with the BasicForm's 'baseParams' config", function() {
                createAction({ params: 'one=1&two=2', form: Ext.apply({ jsonSubmit: true, baseParams: { three: '3', four: '4' } }, formBase) });
                action.run();
                expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo', field2: 'bar', one: '1', two: '2', three: '3', four: '4' });
            });
        });
    });

    describe("ajax request error", function() {
        var wantResponse = { responseText: '{}' };

        function run(response, form) {
            response = response || wantResponse;

            spyOn(Ext.Ajax, 'request').andCallFake(function(config) {
                // call the configured failure handler
                config.failure.call(config.scope, response);
            });
            createAction({
                form: Ext.apply({
                    afterAction: jasmine.createSpy('afterAction'),
                    getValues: function() { return ''; }
                }, form)
            });
            action.run();
        }

        it("should set the Action's failureType property to CONNECT_FAILURE", function() {
            run();

            expect(action.failureType).toEqual(Ext.form.action.Action.CONNECT_FAILURE);
        });

        it("should set the Action's response property to the ajax response", function() {
            run();

            expect(action.response).toEqual(wantResponse);
        });

        it("should call the BasicForm's afterAction method with a false success param", function() {
            run();

            expect(action.form.afterAction).toHaveBeenCalledWith(action, false);
        });

        it("should not call afterAction if the form is destroying", function() {
            run(null, { destroying: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });

        it("should not call afterAction if the form is already destroyed", function() {
            run(null, { destroyed: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });
    });

    describe("response parsing", function() {
        function run(response, reader) {
            spyOn(Ext.Ajax, 'request').andCallFake(function(config) {
                // manually call the configured success handler
                config.success.call(config.scope, response);
            });
            createAction({
                form: {
                    markInvalid: jasmine.createSpy(),
                    errorReader: reader
                }
            });
            action.run();
        }

        it("should parse the responseText as JSON if no errorReader is configured", function() {
            run({ responseText: '{"success":false,"errors":{"from":"responseText"}}' }, undefined);
            expect(action.form.markInvalid).toHaveBeenCalledWith({ from: "responseText" });
        });

        it("should use the configured errorReader to parse the response if present", function() {
            var response = { responseText: '{"success":false,"errors":[]}' };

            run(response, {
                read: jasmine.createSpy().andReturn({
                    success: false,
                    records: [
                        { data: { id: 'field1', msg: 'message 1' } },
                        { data: { id: 'field2', msg: 'message 2' } }
                    ]
                })
            });
            expect(action.form.errorReader.read).toHaveBeenCalledWith(response);
            expect(action.form.markInvalid).toHaveBeenCalledWith([{ id: 'field1', msg: 'message 1' }, { id: 'field2', msg: 'message 2' }]);
        });
    });

    describe("submit failure", function() {
        function run(response, form) {
            spyOn(Ext.Ajax, 'request').andCallFake(function(config) {
                // manually call the configured success handler
                config.success.call(config.scope, response);
            });
            createAction({
                form: Ext.apply({
                    markInvalid: jasmine.createSpy(),
                    afterAction: jasmine.createSpy('afterAction'),
                    getValues: function() { return ''; }
                }, form)
            });
            action.run();
        }

        // causes
        it("should require the result object to have success=true", function() {
            run({ responseText: '{"success":false}' });
            expect(action.failureType).toBeDefined();
        });

        // effects
        it("should set the Action's failureType property to SERVER_INVALID", function() {
            run({ responseText: '{"success":false}' });
            expect(action.failureType).toEqual(Ext.form.action.Action.SERVER_INVALID);
        });
        it("should call the BasicForm's afterAction method with a false success param", function() {
            run({ responseText: '{"success":false}' });
            expect(action.form.afterAction).toHaveBeenCalledWith(action, false);
        });
        it("should call the BasicForm's markInvalid method with any errors in the result", function() {
            run({ responseText: '{"success":false,"errors":{"foo":"bar"}}' });
            expect(action.form.markInvalid).toHaveBeenCalledWith({ foo: "bar" });
        });

        it("should not call afterAction if the form is destroying", function() {
            run({ responseText: '{"success":false}' }, { destroying: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });

        it("should not call afterAction if the form is already destroyed", function() {
            run({ responseText: '{"success":false}' }, { destroyed: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });
    });

    describe("submit success", function() {
        function run(response, form) {
            spyOn(Ext.Ajax, 'request').andCallFake(function(config) {
                // manually call the configured success handler
                config.success.call(config.scope, response);
            });
            createAction({
                form: Ext.apply({
                    afterAction: jasmine.createSpy('afterAction'),
                    getValues: function() { return ''; }
                }, form)
            });
            action.run();
        }

        it("should treat empty responseText and responseXML as success", function() {
            run({ responseText: '', responseXML: '' });
            expect(action.failureType).not.toBeDefined();
        });

        it("should treat a result with success:true as success", function() {
            run({ responseText: '{"success":true}' });
            expect(action.failureType).not.toBeDefined();
        });

        it("should invoke the BasicForm's afterAction method with a true success param", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' });
            expect(action.form.afterAction).toHaveBeenCalledWith(action, true);
        });

        it("should not call afterAction if the form is destroying", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' }, { destroying: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });

        it("should not call afterAction if the form is already destroyed", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' }, { destroyed: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });
    });

    describe('file uploads', function() {
        var ctr;

        function makeCtr(items) {
            ctr = new Ext.container.Container({
                items: items
            });
        }

        afterEach(function() {
            Ext.destroy(ctr);
            ctr = null;
        });

        describe('doSubmit method', function() {
            var ctx, ajaxRequestCfg;

            beforeEach(function() {
                spyOn(Ext.Ajax, 'request').andCallFake(function() {
                    // store what was passed to the request call for later inspection
                    expect(arguments.length).toEqual(1);

                    // Specs were failing in IE < 9 before I cloned the args. The following was failing:
                    //
                    //      expect(ajaxRequestCfg.form.childNodes.length).toBe(2);
                    //
                    // Inspecting the arguments in devtools, I could see the childNodes, but they were
                    // then gone when queried in the unit test (so, length was 0 in IE < 9 browsers).
                    // This does not occur in modern browsers and must be some weird IE bug?
                    if (Ext.isIE8) {
                        ajaxRequestCfg = Ext.clone(arguments[0]);
                    }
                    else {
                        ajaxRequestCfg = arguments[0];
                    }
                });
            });

            afterEach(function() {
                ctx = ajaxRequestCfg = null;
            });

            it('should call buildForm and through to the getParams method', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                spyOn(action, 'buildForm').andCallThrough();
                spyOn(action, 'getParams');

                ctx = action.doSubmit();

                expect(action.buildForm).toHaveBeenCalled();
                expect(action.getParams).toHaveBeenCalled();
            });

            it('should return an object that contains the form dom element', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                ctx = action.doSubmit();

                expect(ajaxRequestCfg.form).toBeDefined();
                expect(ajaxRequestCfg.form.nodeName.toLowerCase()).toBe('form');
            });

            it('should return an object with a form that contains the form elements', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                ctx = action.doSubmit();

                expect(ajaxRequestCfg.form.childNodes.length).toBe(2);
            });

            it('should add an isUpload property that is used by Connection', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                ctx = action.doSubmit();

                expect(ajaxRequestCfg.isUpload).toBe(true);
            });
        });

        describe('getParams method', function() {
            var params;

            afterEach(function() {
                params = null;
            });

            it('should not include any file fields', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                params = action.getParams();

                expect(params).toEqual({ field1: 'foo' });
            });

            it('should call getValues method on the form', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                spyOn(action.form, 'getValues');

                params = action.getParams();

                expect(action.form.getValues).toHaveBeenCalled();
            });

            it('should call through to getSubmitData on each field', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                spyOn(Ext.form.field.Base.prototype, 'getSubmitData');

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                params = action.getParams();

                expect(Ext.form.field.Base.prototype.getSubmitData).toHaveBeenCalled();
                expect(Ext.form.field.Base.prototype.getSubmitData.callCount).toBe(2);
            });

            it('should not call through to getModelData on each field', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                spyOn(Ext.form.field.Base.prototype, 'getModelData');

                createAction({
                    form: new Ext.form.Basic(ctr)
                });

                params = action.getParams();

                expect(Ext.form.field.Base.prototype.getModelData).not.toHaveBeenCalled();
                expect(Ext.form.field.Base.prototype.getModelData.callCount).toBe(0);
            });
        });

        describe('specifying a target config', function() {
            var returnVal;

            afterEach(function() {
                returnVal.formEl.parentNode.removeChild(returnVal.formEl);
                returnVal = null;
            });

            it('should honor the config', function() {
                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr),
                    target: 'foo'
                });

                returnVal = action.buildForm();

                expect(returnVal.formEl.target).toBe('foo');
            });

            it('should use the "name" property if passed a dom node', function() {
                var iframe = document.createElement('iframe');

                iframe.setAttribute('name', 'foo');

                makeCtr([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo'
                    }),
                    new Ext.form.field.File({
                        name: 'field2'
                    })
                ]);

                createAction({
                    form: new Ext.form.Basic(ctr),
                    target: iframe
                });

                returnVal = action.buildForm();

                expect(returnVal.formEl.target).toBe('foo');
            });
        });
    });
});
