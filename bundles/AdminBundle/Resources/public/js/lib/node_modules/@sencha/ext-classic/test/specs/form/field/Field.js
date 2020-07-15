topSuite('Ext.form.field.Field',
    ['Ext.form.field.*', 'Ext.data.validator.*', 'Ext.form.Panel',
     'Ext.app.ViewController', 'Ext.app.ViewModel'],
function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        ajaxRequestCfg, ct, action, form;

    function makeContainer(items) {
        ct = new Ext.container.Container({
            items: items
        });
    }

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
        Ext.destroy(ct, action, form);
        ct = action = form = ajaxRequestCfg = null;
    });

    describe("quicktips/validation", function() {
        var tf, errorDom, tip;

        function createForm(required, cfg) {
            // we're creating textields for testing, but any type that supports validation will do.
            form = Ext.create('Ext.form.Panel', Ext.apply({
                renderTo: Ext.getBody(),
                width: 400,
                height: 200,
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: 'tf',
                        msgTarget: 'side',
                        allowBlank: !!!required
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'dummy'
                    }
                ]
            }, cfg || {}));

            tf = form.down('textfield');

            errorDom = tf.errorEl.dom;
        }

        afterEach(function() {
            tip = Ext.destroy(tip);
        });

        it("should create a validation error icon to the right of the field", function() {
            createForm();
            tf.validate();
            expect(tf.errorEl.dom.firstChild).toBeNull();
            tf.allowBlank = false;
            tf.validate();
            expect(tf.errorEl.dom.firstChild).not.toBeNull();
        });

        itNotTouch("should show a quicktip if mouse over the invalid icon", function() {
            createForm(true, {
                title: 'quicktip'
            });
            tf.validate();

            tip = Ext.form.Labelable.tip;
            expect(tip.hidden).toBe(true);
            jasmine.fireMouseEvent(errorDom, 'mouseover');
            waitsFor(function() {
                return tip.hidden === false;
            });
            runs(function() {
                expect(tip.hidden).toBe(false);
                tip.hide();
            });
        });
    });

    describe("data binding", function() {
        var viewModel, field;

        function makeField(cfg) {
            cfg = Ext.apply({
                viewModel: viewModel
            }, cfg);
            field = new Ext.form.field.Base(cfg);
        }

        beforeEach(function() {
            viewModel = new Ext.app.ViewModel();
        });

        afterEach(function() {
            Ext.destroy(field);
            field = null;
        });

        describe("valuePublishEvent", function() {
            it("should accept a string", function() {
                makeField({
                    valuePublishEvent: 'foo',
                    renderTo: Ext.getBody(),
                    bind: '{theValue}'
                });
                field.setValue('XXX');
                field.fireEvent('foo');
                expect(viewModel.get('theValue')).toBe('XXX');
            });

            it("should accept an array", function() {
                makeField({
                    valuePublishEvent: ['foo', 'bar'],
                    renderTo: Ext.getBody(),
                    bind: '{theValue}'
                });
                field.setValue('XXX');
                field.fireEvent('foo');
                expect(viewModel.get('theValue')).toBe('XXX');
                field.setValue('YYY');
                field.fireEvent('bar');
                expect(viewModel.get('theValue')).toBe('YYY');
                field.setValue('ZZZ');
                field.fireEvent('baz');
                expect(viewModel.get('theValue')).toBe('YYY');
            });
        });

        describe("valid values", function() {
            it("should publish a valid value", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    bind: '{theValue}'
                });

                field.getErrors = function() {
                    return [];
                };

                field.setValue('abc');
                expect(viewModel.get('theValue')).toBe('abc');
            });

            it("should not publish an invalid value", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    bind: '{theValue}'
                });

                field.getErrors = function() {
                    var v = this.getValue();

                    return v === 'abc' ? ['Invalid'] : [];
                };

                field.setValue('abc');
                expect(viewModel.get('theValue')).toBeNull();
                field.setValue('def');
                expect(viewModel.get('theValue')).toBe('def');
            });
        });

        describe("with records", function() {
            var rec, validator;

            beforeEach(function() {
                Ext.define('Ext.data.validator.Custom', {
                    extend: 'Ext.data.validator.Validator',
                    alias: 'data.validator.custom'
                });

                validator = Ext.data.validator.Validator.create({
                    type: 'custom'
                });

                Ext.define('spec.Person', {
                    extend: 'Ext.data.Model',
                    fields: ['name', 'age', 'address'],
                    validators: {
                        name: {
                            type: 'length',
                            min: 3
                        },
                        address: validator
                    }
                });

                rec = new spec.Person({
                    name: 'FooBar',
                    age: 10
                });
                viewModel.set('thePerson', rec);
            });

            afterEach(function() {
                Ext.undefine('spec.Person');
                Ext.undefine('Ext.data.validator.Custom');
                Ext.Factory.dataValidator.instance.clearCache();
                Ext.data.Model.schema.clear(true);
            });

            it("should not validate model fields without modelValidation", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    bind: '{thePerson.name}'
                });
                viewModel.notify();
                field.setValue('');
                expect(field.getErrors()).toEqual([]);
            });

            it("should not attempt to model validate when the field is not in the model", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    modelValidation: true,
                    bind: '{thePerson.something}'
                });
                expect(field.getErrors()).toEqual([]);
            });

            it("should not include results for fields that do not have validators", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    modelValidation: true,
                    bind: '{thePerson.age}'
                });
                viewModel.notify();
                expect(field.getErrors()).toEqual([]);
            });

            it("should validate using the model validator", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    modelValidation: true,
                    bind: '{thePerson.name}'
                });
                viewModel.notify();
                field.setValue('');
                expect(field.getErrors()).toEqual(['Must be present']);
            });

            it("should pass value and record to the model validator", function() {
                spyOn(validator, 'validate').andCallThrough();

                makeField({
                    renderTo: Ext.getBody(),
                    modelValidation: true,
                    bind: '{thePerson.address}'
                });
                viewModel.notify();
                field.setValue('Foo');

                expect(validator.validate.mostRecentCall.args).toEqual(['Foo', viewModel.get('thePerson')]);
            });

            it("should combine with field validations", function() {
                makeField({
                    renderTo: Ext.getBody(),
                    modelValidation: true,
                    bind: '{thePerson.name}'
                });
                viewModel.notify();
                Ext.override(field, {
                    getErrors: function() {
                        var result = this.callParent(arguments);

                        result.push('Fail');

                        return result;
                    }
                });
                field.setValue('');
                expect(field.getErrors()).toEqual(['Must be present', 'Fail']);
            });
        });
    });

    describe('getModelData', function() {
        var form;

        afterEach(function() {
            Ext.destroy(form);
            form = null;
        });

        it('should return filefield data', function() {
            var field1 = new Ext.form.field.Display({
                name: 'field1',
                value: 'foo'
            });

            var field2 = new Ext.form.field.File({
                name: 'field2',
                value: 'bar'
            });

            expect(field1.getModelData()).toEqual({ field1: 'foo' });
            expect(field2.getModelData()).toEqual({ field2: '' });

            Ext.destroy(field1, field2);
        });

        describe('in a form with jsonSubmit', function() {
            it('should return values for fields in a form regardless of submitValue (not submitting)', function() {
                makeContainer([
                    new Ext.form.field.Base({
                        name: 'field1',
                        value: 'foo',
                        submitValue: true
                    }),
                    new Ext.form.field.File({
                        name: 'field2',
                        value: 'bar'
                    }),
                    new Ext.form.field.Display({
                        name: 'field3',
                        value: 'baz',
                        submitValue: false
                    })
                ]);
                form = new Ext.form.Basic(ct, {
                    jsonSubmit: true
                });

                expect(form.getFieldValues()).toEqual({ field1: 'foo', field2: '', field3: 'baz' });
            });
        });
    });

    describe('getSubmitData', function() {
        var file;

        afterEach(function() {
            Ext.destroy(file);
            file = null;
        });

        it('should not be able to get the submit data for a filefield by default, non-submission', function() {
            file = new Ext.form.field.File({
                name: 'foo'
            });

            expect(file.getSubmitData()).toBe(null);
        });

        it('should be able to get the submit data for a filefield when configured with submitValue: true, non-submission', function() {
            file = new Ext.form.field.File({
                name: 'foo',
                submitValue: true
            });

            expect(file.getSubmitData()).toEqual({ foo: '' });
        });

        // temporarily disabled this spec because it throws errors in several browsers
        it('should not be able to get the submit data for a filefield on form submission', function() {
            makeContainer([
                new Ext.form.field.Base({
                    name: 'field1',
                    value: 'foo'
                }),
                new Ext.form.field.File({
                    name: 'field2'
                })
            ]);

            createAction({
                form: new Ext.form.Basic(ct)
            });

            expect(ct.items.getAt(0).getSubmitData()).toEqual({ field1: 'foo' });
            expect(ct.items.getAt(1).getSubmitData()).toBe(null);
        });
    });

    describe('submitValue config', function() {
        beforeEach(function() {
            spyOn(Ext.Ajax, 'request').andCallFake(function() {
                // store what was passed to the request call for later inspection
                expect(arguments.length).toEqual(1);
                ajaxRequestCfg = arguments[0];
            });
        });

        it("should add all of the BasicForm's field values marked as submitValue: true to the ajax call parameters", function() {
            makeContainer([
                new Ext.form.field.Base({
                    name: 'field1',
                    value: 'foo',
                    submitValue: true
                }),
                new Ext.form.field.Base({
                    name: 'field2',
                    value: 'bar'
                })
            ]);

            form = new Ext.form.Basic(ct, {
                jsonSubmit: true
            });

            createAction({ form: form });

            action.run();
            expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo', field2: 'bar' });
        });

        it("should not add any of the BasicForm's field values marked as submitValue: false to the ajax call parameters", function() {
            makeContainer([
                new Ext.form.field.Base({
                    name: 'field1',
                    value: 'foo',
                    submitValue: true
                }),
                new Ext.form.field.Base({
                    name: 'field2',
                    value: 'bar',
                    submitValue: false
                })
            ]);

            form = new Ext.form.Basic(ct, {
                jsonSubmit: true
            });

            createAction({ form: form });

            action.run();
            expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo' });
        });

        it('should not include any displayfields in the form submit', function() {
            makeContainer([
                new Ext.form.field.Base({
                    name: 'field1',
                    value: 'foo',
                    submitValue: true
                }),
                new Ext.form.field.Display({
                    name: 'field2',
                    value: 'bar'
                })
            ]);

            form = new Ext.form.Basic(ct, {
                jsonSubmit: true
            });

            createAction({ form: form });

            action.run();
            expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo' });
        });

        it('should submit any fields with submitValue: true in the form submit', function() {
            makeContainer([
                new Ext.form.field.Base({
                    name: 'field1',
                    value: 'foo'
                }),
                new Ext.form.field.Display({
                    name: 'field2',
                    value: 'bar'
                }),
                new Ext.form.field.Display({
                    name: 'field3',
                    value: 'baz',
                    submitValue: true
                })
            ]);

            form = new Ext.form.Basic(ct, {
                jsonSubmit: true
            });

            createAction({ form: form });

            action.run();
            expect(ajaxRequestCfg.jsonData).toEqual({ field1: 'foo', field3: 'baz' });
        });
    });
});
