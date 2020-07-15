topSuite("Ext.form.action.Load", function() {
    var action;

    function createAction(config) {
        config = config || {};

        if (!config.form) {
            config.form = {};
        }

        action = new Ext.form.action.Load(config);
    }

    afterEach(function() {
        action = undefined;
    });

    describe("alternate class name", function() {
        it("should have Ext.form.Action.Load as the alternate class name", function() {
            expect(Ext.form.action.Load.prototype.alternateClassName).toEqual("Ext.form.Action.Load");
        });

        it("should allow the use of Ext.form.Action.Load", function() {
            expect(Ext.form.Action.Load).toBeDefined();
        });
    });

    it("should be registered in the action manager under the alias 'formaction.load'", function() {
        var inst = Ext.ClassManager.instantiateByAlias('formaction.load', {});

        expect(inst instanceof Ext.form.action.Load).toBeTruthy();
    });

    describe("AJAX call parameters", function() {
        var ajaxRequestCfg;

        beforeEach(function() {
            spyOn(Ext.Ajax, 'request').andCallFake(function() {
                // store what was passed to the request call for later inspection
                expect(arguments.length).toEqual(1);
                ajaxRequestCfg = arguments[0];
            });
        });

        it("should invoke Ext.Ajax.request", function() {
            createAction();
            action.run();
            expect(Ext.Ajax.request).toHaveBeenCalled();
        });

        it("should use 'POST' as the ajax call method by default", function() {
            createAction();
            action.run();
            expect(ajaxRequestCfg.method).toEqual('POST');
        });

        it("should use the BasicForm's 'method' config as the ajax call method if specified", function() {
            createAction({ form: { method: 'FORMMETHOD' } });
            action.run();
            expect(ajaxRequestCfg.method).toEqual('FORMMETHOD');
        });

        it("should use the Action's 'method' config as the ajax call method if specified", function() {
            createAction({ method: 'actionmethod' });
            action.run();
            expect(ajaxRequestCfg.method).toEqual('ACTIONMETHOD');
        });

        it("should use the BasicForm's 'url' config as the ajax call url if specified", function() {
            createAction({ form: { url: '/url-from-form' } });
            action.run();
            expect(ajaxRequestCfg.url).toEqual('/url-from-form');
        });

        it("should use the Action's 'url' config as the ajax call url if specified", function() {
            createAction({ url: '/url-from-action' });
            action.run();
            expect(ajaxRequestCfg.url).toEqual('/url-from-action');
        });

        it("should use the Action's 'headers' config as the ajax call headers if specified", function() {
            var headers = { foo: 'bar' };

            createAction({ headers: headers });
            action.run();
            expect(ajaxRequestCfg.headers).toBe(headers);
        });

        it("should default to sending no params to the ajax call", function() {
            createAction();
            action.run();
            expect(ajaxRequestCfg.params).toEqual({});
        });

        it("should add the BasicForm's 'baseParams' config to the ajax call params if specified", function() {
            var params = { one: '1', two: '2' };

            createAction({ form: { baseParams: params } });
            action.run();
            expect(ajaxRequestCfg.params).toEqual(params);
        });

        it("should use the Action's 'params' config for the ajax call params if specfied (as an Object)", function() {
            var params = { one: '1', two: '2' };

            createAction({ params: params });
            action.run();
            expect(ajaxRequestCfg.params).toEqual(params);
        });

        it("should use the Action's 'params' config for the ajax call params if specfied (as a String)", function() {
            var params = 'one=1&two=2';

            createAction({ params: params });
            action.run();
            expect(ajaxRequestCfg.params).toEqual({ one: '1', two: '2' });
        });

        it("should concatenate the Action's 'params' config (as an Object) with the BasicForm's 'baseParams' config", function() {
            createAction({ params: { one: '1', two: '2' }, form: { baseParams: { three: '3', four: '4' } } });
            action.run();
            expect(ajaxRequestCfg.params).toEqual({ one: '1', two: '2', three: '3', four: '4' });
        });

        it("should concatenate the Action's 'params' config (as a String) with the BasicForm's 'baseParams' config", function() {
            createAction({ params: 'one=1&two=2', form: { baseParams: { three: '3', four: '4' } } });
            action.run();
            expect(ajaxRequestCfg.params).toEqual({ one: '1', two: '2', three: '3', four: '4' });
        });

        it("should use the BasicForm's 'timeout' config as the ajax call timeout if specified", function() {
            createAction({ form: { timeout: 123 } });
            action.run();
            expect(ajaxRequestCfg.timeout).toEqual(123000);
        });

        it("should use the Action's 'timeout' config as the ajax call timeout if specified", function() {
            createAction({ timeout: 123 });
            action.run();
            expect(ajaxRequestCfg.timeout).toEqual(123000);
        });

        it("should use the Action instance as the ajax call 'scope' parameter", function() {
            createAction();
            action.run();
            expect(ajaxRequestCfg.scope).toBe(action);
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
                    afterAction: jasmine.createSpy('afterAction')
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

    describe("load failure", function() {

        function run(response, form) {
            spyOn(Ext.Ajax, 'request').andCallFake(function(config) {
                // manually call the configured success handler
                config.success.call(config.scope, response);
            });
            createAction({
                form: Ext.apply({
                    afterAction: jasmine.createSpy('afterAction')
                }, form)
            });
            action.run();
        }

        // effects
        it("should set the Action's failureType property to LOAD_FAILURE", function() {
            run({});
            expect(action.failureType).toEqual(Ext.form.action.Action.LOAD_FAILURE);
        });
        it("should call the BasicForm's afterAction method with a false success param", function() {
            run({});
            expect(action.form.afterAction).toHaveBeenCalledWith(action, false);
        });

        // causes
        it("should fail if either the responseText or responseXML are populated", function() {
            run({});
            expect(action.failureType).toBeDefined();
        });
        it("should require the result object to have success=true", function() {
            run({ responseText: '{"success":false, "data":{}}' });
            expect(action.failureType).toBeDefined();
        });
        it("should require the result object to have a data property", function() {
            run({ responseText: '{"success":true}' });
            expect(action.failureType).toBeDefined();
        });

        it("should not call afterAction if the form is destroying", function() {
            run({}, { destroying: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });

        it("should not call afterAction if the form is already destroyed", function() {
            run({}, { destroyed: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });
    });

    describe("load success", function() {
        function run(response, reader, form) {
            spyOn(Ext.Ajax, 'request').andCallFake(function(config) {
                // manually call the configured success handler
                config.success.call(config.scope, response);
            });
            createAction({
                form: Ext.apply({
                    reader: reader,
                    clearInvalid: jasmine.createSpy(),
                    setValues: jasmine.createSpy(),
                    afterAction: jasmine.createSpy('afterAction')
                }, form)
            });
            action.run();
        }

        it("should call the BasicForm's clearInvalid method", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' });
            expect(action.form.clearInvalid).toHaveBeenCalled();
        });

        it("should call the BasicForm's setValues method", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' });
            expect(action.form.setValues).toHaveBeenCalled();
        });

        it("should invoke the BasicForm's afterAction method with a true success param", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' });
            expect(action.form.afterAction).toHaveBeenCalledWith(action, true);
        });

        it("should parse the responseText as JSON", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' });
            expect(action.form.setValues).toHaveBeenCalledWith({ from: "responseText" });
        });

        it("should use the BasicForm's configured Reader to parse the response if present", function() {
            var response = { responseText: '{}' };

            run(response, {
                read: jasmine.createSpy().andReturn({
                    success: true,
                    records: [
                        { data: { from: 'reader' } }
                    ]
                })
            });
            expect(action.form.reader.read).toHaveBeenCalledWith(response);
            expect(action.form.setValues).toHaveBeenCalledWith({ from: "reader" });
        });

        it("should not call afterAction if the form is destroying", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' }, undefined, { destroying: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });

        it("should not call afterAction if the form is already destroyed", function() {
            run({ responseText: '{"success":true,"data":{"from":"responseText"}}' }, undefined, { destroyed: true });

            expect(action.form.afterAction).not.toHaveBeenCalled();
        });
    });
});
