/* global MockAction, BasicFormTestModel */

topSuite("Ext.form.Basic", ['Ext.Container', 'Ext.form.field.*', 'Ext.Button'], function() {
    var basicForm, container, currentActionInstance, mockActionCtorSpy;

    /**
     * Utility to add a MockField object to the container
     */
    function addField(config, ct) {
        var c;

        if (!config.isComponent) {
            Ext.apply(config, {
                isEqual: Ext.form.field.Base.prototype.isEqualAsString
            });

            c = new Ext.form.field.Base(config);
        }
        else {
            c = config;
        }

        ct = ct || container;

        return ct.add(c);
    }

    /**
     * For each test create a container and bind a BasicForm instance to it.
     */
    beforeEach(function() {
        Ext.define('MockAction', {
            alias: 'formaction.mock',
            constructor: function() {
                currentActionInstance = this;

                // allow spying on the constructor
                if (mockActionCtorSpy) {
                    mockActionCtorSpy.apply(this, arguments);
                }
            },
            run: Ext.emptyFn
        });

        container = new Ext.container.Container({});
        basicForm = new Ext.form.Basic(container);
        basicForm.initialize();
    });

    /**
     * Cleanup
     */
    afterEach(function() {
        Ext.undefine('MockAction');
        container.destroy();
        basicForm.destroy();
        basicForm = container = currentActionInstance = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.form.BasicForm as the alternate class name", function() {
            expect(Ext.form.Basic.prototype.alternateClassName).toEqual("Ext.form.BasicForm");
        });

        it("should allow the use of Ext.form.BasicForm", function() {
            expect(Ext.form.BasicForm).toBeDefined();
        });
    });

    describe("paramOrder normalization", function() {
        var paramOrderArray = ['one', 'two', 'three'];

        it("should accept paramOrder config as an array", function() {
            var form = new Ext.form.Basic(container, { paramOrder: paramOrderArray });

            expect(form.paramOrder).toEqual(paramOrderArray);
        });
        it("should accept paramOrder config as a comma-separated string and normalize it to an array", function() {
            var form = new Ext.form.Basic(container, { paramOrder: 'one,two,three' });

            expect(form.paramOrder).toEqual(paramOrderArray);
        });
        it("should accept paramOrder config as a space-separated string and normalize it to an array", function() {
            var form = new Ext.form.Basic(container, { paramOrder: 'one two three' });

            expect(form.paramOrder).toEqual(paramOrderArray);
        });
        it("should accept paramOrder config as a pipe-separated string and normalize it to an array", function() {
            var form = new Ext.form.Basic(container, { paramOrder: 'one|two|three' });

            expect(form.paramOrder).toEqual(paramOrderArray);
        });
    });

    describe("getFields", function() {
        beforeEach(function() {
            addField({ name: 'one' });
            addField({ name: 'two' });
        });

        it("should return all field objects within the owner", function() {
            var fields = basicForm.getFields();

            expect(fields.length).toEqual(2);
            expect(fields.getAt(0).name).toEqual('one');
            expect(fields.getAt(1).name).toEqual('two');
        });

        it("should cache the list of fields after first access", function() {
            var fields1 = basicForm.getFields(),
                fields2 = basicForm.getFields();

            expect(fields2).toBe(fields1);
        });

        it("should requery the list when a field is added", function() {
            var fields1 = basicForm.getFields();

            addField({ name: 'three' });
            var fields2 = basicForm.getFields();

            expect(fields2.getCount()).toEqual(3);
            expect(fields2.getAt(2).name).toEqual('three');
        });

        it("should requery the list when a field is removed", function() {
            var fields1 = basicForm.getFields();

            container.remove(container.items.getAt(0));
            var fields2 = basicForm.getFields();

            expect(fields2.getCount()).toEqual(1);
            expect(fields2.getAt(0).name).toEqual('two');
        });

        it("should requery the list when an field is added in a container", function() {
            container.add(
                new Ext.form.field.Base()
            );
            expect(basicForm.getFields().getCount()).toBe(3);
        });

        it("should requery the list when an field is removed from a container", function() {
            container.add(
                new Ext.form.field.Base()
            );
            container.removeAll();
            expect(basicForm.getFields().getCount()).toBe(0);
        });
    });

    describe("isValid method", function() {
        it("should return true if no fields are invalid", function() {
            addField({ name: 'one' });
            addField({ name: 'two' });
            expect(basicForm.isValid()).toBeTruthy();
        });

        it("should return false if any fields are invalid", function() {
            addField({ name: 'one' });
            addField({ name: 'two', isValid: function() { return false; } });
            expect(basicForm.isValid()).toBeFalsy();
        });
    });

    describe("isDirty method", function() {
        it("should return false if no fields are dirty", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            expect(basicForm.isDirty()).toBeFalsy();
        });
        it("should return true if any fields are dirty", function() {
            addField({ name: 'one' });
            var two = addField({ name: 'two', value: 'aaa' });

            two.setValue('bbb');
            expect(basicForm.isDirty()).toBeTruthy();
        });
    });

    describe("reset method", function() {
        it("should reset all fields to their initial values", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'one' });

            spyOn(one, 'reset');
            spyOn(two, 'reset');
            basicForm.reset();
            expect(one.reset).toHaveBeenCalled();
            expect(two.reset).toHaveBeenCalled();
        });

        it("should not clear any record reference by default", function() {
            var record = {
                getData: function() {
                    return {
                        one: 'value 1',
                        two: 'value 2'
                    };
                }
            };

            basicForm.loadRecord(record);
            basicForm.reset();
            expect(basicForm.getRecord()).toBe(record);
        });

        it("should clear any record reference if resetRecord is passed", function() {
            var record = {
                getData: function() {
                    return {
                        one: 'value 1',
                        two: 'value 2'
                    };
                }
            };

            basicForm.loadRecord(record);
            basicForm.reset(true);
            expect(basicForm.getRecord()).toBeUndefined();
        });
    });

    describe("findField method", function() {
        it("should find a field by id", function() {
            var one = addField({ name: 'one', id: 'oneId' }),
                result = basicForm.findField('oneId');

            expect(result).toBe(one);
        });

        it("should find a field by name", function() {
            var one = addField({ name: 'one' }),
                result = basicForm.findField('one');

            expect(result).toBe(one);
        });

        it("should return null if no matching field is found", function() {
            var one = addField({ name: 'one' }),
                result = basicForm.findField('doesnotmatch');

            expect(result).toBeNull();
        });

        it("should exclude items with the excludeForm property on the field", function() {
            addField({
                name: 'foo',
                excludeForm: true
            });

            expect(basicForm.findField('foo')).toBeNull();
        });
    });

    describe("markInvalid method", function() {
        // change to use selectors?
        it("should accept an object where the keys are field names and the values are error messages", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            spyOn(one, 'markInvalid');
            spyOn(two, 'markInvalid');
            basicForm.markInvalid({
                one: 'error one',
                two: 'error two'
            });
            expect(one.markInvalid).toHaveBeenCalledWith('error one');
            expect(two.markInvalid).toHaveBeenCalledWith('error two');
        });

        it("should accept an array of objects with 'id' and 'msg' properties", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            spyOn(one, 'markInvalid');
            spyOn(two, 'markInvalid');
            basicForm.markInvalid([
                { id: 'one', msg: 'error one' },
                { id: 'two', msg: 'error two' }
            ]);
            expect(one.markInvalid).toHaveBeenCalledWith('error one');
            expect(two.markInvalid).toHaveBeenCalledWith('error two');
        });
    });

    describe("clearInvalid method", function() {
        it("should clear the invalid state of all fields", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            spyOn(one, 'clearInvalid');
            spyOn(two, 'clearInvalid');
            basicForm.clearInvalid();
            expect(one.clearInvalid).toHaveBeenCalled();
            expect(two.clearInvalid).toHaveBeenCalled();
        });
    });

    describe("applyToFields method", function() {
        it("should call apply() on all fields with the given arguments", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            basicForm.applyToFields({ customProp: 'custom' });
            expect(one.customProp).toEqual('custom');
            expect(two.customProp).toEqual('custom');
        });
    });

    describe("applyIfToFields method", function() {
        it("should call applyIf() on all fields with the given arguments", function() {
            var one = addField({ name: 'one', customProp1: 1 }),
                two = addField({ name: 'two', customProp1: 1 });

            basicForm.applyIfToFields({ customProp1: 2, customProp2: 3 });
            expect(one.customProp1).toEqual(1);
            expect(one.customProp2).toEqual(3);
            expect(two.customProp1).toEqual(1);
            expect(two.customProp2).toEqual(3);
        });
    });

    describe("setValues method", function() {
        it("should accept an object mapping field ids to new field values", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            spyOn(one, 'setValue');
            spyOn(two, 'setValue');
            basicForm.setValues({
                one: 'value 1',
                two: 'value 2'
            });
            expect(one.setValue).toHaveBeenCalledWith('value 1');
            expect(two.setValue).toHaveBeenCalledWith('value 2');
        });

        it("should accept an array of objects with 'id' and 'value' properties", function() {
            var one = addField({ name: 'one' }),
                two = addField({ name: 'two' });

            spyOn(one, 'setValue');
            spyOn(two, 'setValue');
            basicForm.setValues([
                { id: 'one', value: 'value 1' },
                { id: 'two', value: 'value 2' }
            ]);
            expect(one.setValue).toHaveBeenCalledWith('value 1');
            expect(two.setValue).toHaveBeenCalledWith('value 2');
        });

        it("should not set the fields' originalValue property by default", function() {
            var one = addField({ name: 'one', value: 'orig value' });

            basicForm.setValues({
                one: 'new value'
            });
            expect(one.originalValue).toEqual('orig value');
        });

        it("should set the fields' originalValue property if the 'trackResetOnLoad' config is true", function() {
            var one = addField({ name: 'one', value: 'orig value' });

            basicForm.trackResetOnLoad = true;
            basicForm.setValues({
                one: 'new value'
            });
            expect(one.originalValue).toEqual('new value');
        });

        it("should only trigger a single layout", function() {
            var fields = [],
                data = {},
                i = 0,
                count = 0,
                key;

            for (; i < 5; ++i) {
                key = 'field' + i;
                addField({
                    name: key,
                    // Simulate layout update of display field
                    setRawValue: function(value) {
                        var me = this;

                        value = Ext.valueFrom(me.transformRawValue(value), '');
                        me.rawValue = value;

                        if (me.inputEl) {
                            me.inputEl.dom.value = value;
                        }

                        me.updateLayout();

                        return value;
                    }
                });
                data[key] = key;
            }

            container.render(Ext.getBody());
            container.on('afterlayout', function() {
                ++count;
            });
            basicForm.setValues(data);
            expect(count).toBe(1);
        });
    });

    describe("getValues method", function() {
        var vals;

        afterEach(function() {
            vals = null;
        });

        it("should return an object mapping field names to field values", function() {
            addField({ name: 'one', value: 'value 1' });
            addField({ name: 'two', value: 'value 2' });
            vals = basicForm.getValues();
            expect(vals).toEqual({ one: 'value 1', two: 'value 2' });
        });

        it("should populate an array of values for multiple fields with the same name", function() {
            addField({ name: 'one', value: 'value 1' });
            addField({ name: 'two', value: 'value 2' });
            addField({ name: 'two', value: 'value 3' });
            vals = basicForm.getValues();
            expect(vals).toEqual({ one: 'value 1', two: ['value 2', 'value 3'] });
        });

        it("should populate an array of values for single fields who return an array of values", function() {
            addField({ name: 'one', value: 'value 1' });
            addField({ name: 'two', value: 'value 2' });
            addField({
                name: 'two',
                getRawValue: function() {
                    return ['value 3', 'value 4'];
                }
            });

            vals = basicForm.getValues();
            expect(vals).toEqual({ one: 'value 1', two: ['value 2', 'value 3', 'value 4'] });
        });

        it("should return a url-encoded query parameter string if the 'asString' argument is true", function() {
            addField({ name: 'one', value: 'value 1' });
            addField({ name: 'two', value: 'value 2' });
            addField({ name: 'two', value: 'value 3' });
            vals = basicForm.getValues(true);
            expect(vals).toEqual('one=value%201&two=value%202&two=value%203');
        });

        it("should return only dirty fields if the 'dirtyOnly' argument is true", function() {
            addField({ name: 'one', value: 'value 1' }).setValue('dirty value');
            addField({ name: 'two', value: 'value 2' });
            vals = basicForm.getValues(false, true);
            expect(vals).toEqual({ one: 'dirty value' });
        });

        it("should return the emptyText for empty fields if the 'includeEmptyText' argument is true", function() {
            addField({ name: 'one', value: 'value 1', dirty: true, emptyText: 'empty 1' });
            addField({ name: 'two', value: '', dirty: false, emptyText: 'empty 2' });
            vals = basicForm.getValues(false, false, true);
            expect(vals).toEqual({ one: 'value 1', two: 'empty 2' });
        });

        it("should include fields whose value is empty string", function() {
            addField({ name: 'one', value: '' });
            addField({ name: 'two', value: 'value 2' });
            vals = basicForm.getValues();
            expect(vals).toEqual({ one: '', two: 'value 2' });
        });

        it("should not include fields whose getSubmitData method returns null", function() {
            addField({ name: 'one', value: 'value 1', getSubmitData: function() {
                return null;
            } });
            addField({ name: 'two', value: 'value 2' });
            vals = basicForm.getValues();
            expect(vals).toEqual({ two: 'value 2' });
        });

        it("should not include filefields (which do not submit by default)", function() {
            addField({ name: 'one', value: 'value 1' });
            addField({ name: 'two', value: 'value 2' });
            container.add({
                xtype: 'filefield',
                name: 'three'
            });
            vals = basicForm.getValues();
            expect(vals).toEqual({ one: 'value 1', two: 'value 2' });
        });
    });

    describe("doAction method", function() {
        beforeEach(function() {
            mockActionCtorSpy = jasmine.createSpy();
        });

        afterEach(function() {
            mockActionCtorSpy = undefined;
        });

        it("should accept an instance of Ext.form.action.Action for the 'action' argument", function() {
            var action = new MockAction();

            spyOn(action, 'run');
            runs(function() {
                basicForm.doAction(action);
            });
            waitsFor(function() {
                return action.run.callCount === 1;
            }, "did not call the action's run method");
        });

        it("should accept an action name for the 'action' argument", function() {
            spyOn(MockAction.prototype, 'run');
            runs(function() {
                basicForm.doAction('mock');
            });
            waitsFor(function() {
                return MockAction.prototype.run.callCount === 1;
            }, "did not call the action's run method");
        });

        it("should pass the options argument to the Action constructor", function() {
            basicForm.doAction('mock', {});
            expect(mockActionCtorSpy).toHaveBeenCalledWith({ form: basicForm });
        });

        it("should call the beforeAction method", function() {
            spyOn(basicForm, 'beforeAction');
            basicForm.doAction('mock');
            expect(basicForm.beforeAction).toHaveBeenCalledWith(currentActionInstance);
        });

        it("should fire the beforeaction event", function() {
            var spy = jasmine.createSpy();

            basicForm.on('beforeaction', spy);
            basicForm.doAction('mock');
            expect(spy).toHaveBeenCalledWith(basicForm, currentActionInstance);
        });

        it("should cancel the action if a beforeaction listener returns false", function() {
            var handler = function() {
                return false;
            };

            basicForm.on('beforeaction', handler);
            spyOn(basicForm, 'beforeAction');
            basicForm.doAction('mock');
            expect(basicForm.beforeAction).not.toHaveBeenCalled();
        });

        // Actual action behaviors are tested separately in Action.js specs
    });

    describe("beforeAction method", function() {
        it("should call syncValue on any fields with that method", function() {
            var action = new MockAction(),
                spy = jasmine.createSpy();

            addField({ name: 'one', syncValue: spy });
            basicForm.beforeAction(action);
            expect(spy).toHaveBeenCalled();
        });

        // waiting on MessageBox implementation
        xit("should display a wait message box if waitMsg is defined and waitMsgTarget is not defined", function() {});
        xit("should mask the owner's element if waitMsg is defined and waitMsgTarget is true", function() {});
        xit("should mask the waitMsgTarget element if waitMsg is defined and waitMsgTarget is an element", function() {});
    });

    describe("afterAction method", function() {
        // waiting on MessageBox implementation
        xit("should hide the wait message box if waitMsg is defined and waitMsgTarget is not defined", function() {});
        xit("should unmask the owner's element if waitMsg is defined and waitMsgTarget is true", function() {});
        xit("should unmask the waitMsgTarget element if waitMsg is defined and waitMsgTarget is an element", function() {});

        describe("success", function() {
            it("should invoke the reset method if the Action's reset option is true", function() {
                var action = new MockAction();

                action.reset = false;
                spyOn(basicForm, 'reset');
                basicForm.afterAction(action, true);
                expect(basicForm.reset).not.toHaveBeenCalled();
                action.reset = true;
                basicForm.afterAction(action, true);
                expect(basicForm.reset).toHaveBeenCalled();
            });

            it("should invoke the Action's success option as a callback with a reference to the BasicForm and the Action", function() {
                var spy = jasmine.createSpy(),
                    action = new MockAction();

                action.success = spy;
                basicForm.afterAction(action, true);
                expect(spy).toHaveBeenCalledWith(basicForm, action);
            });

            it("should fire the 'actioncomplete' event with a reference to the BasicForm and the Action", function() {
                var spy = jasmine.createSpy(),
                    action = new MockAction();

                basicForm.on('actioncomplete', spy);
                basicForm.afterAction(action, true);
                expect(spy).toHaveBeenCalledWith(basicForm, action);
            });
        });

        describe("failure", function() {
            it("should invoke the Action's failure option as a callback with a reference to the BasicForm and the Action", function() {
                var spy = jasmine.createSpy(),
                    action = new MockAction();

                action.failure = spy;
                basicForm.afterAction(action, false);
                expect(spy).toHaveBeenCalledWith(basicForm, action);
            });

            it("should fire the 'actionfailed' event with a reference to the BasicForm and the Action", function() {
                var spy = jasmine.createSpy(),
                    action = new MockAction();

                basicForm.on('actionfailed', spy);
                basicForm.afterAction(action, false);
                expect(spy).toHaveBeenCalledWith(basicForm, action);
            });
        });
    });

    describe("submit method", function() {
        it("should call doAction with 'submit' by default", function() {
            var opts = {};

            spyOn(basicForm, 'doAction');
            basicForm.submit(opts);
            expect(basicForm.doAction).toHaveBeenCalledWith('submit', opts);
        });

        it("should call doAction with 'standardsubmit' if the standardSubmit config is true", function() {
            basicForm.standardSubmit = true;
            var opts = {};

            spyOn(basicForm, 'doAction');
            basicForm.submit(opts);
            expect(basicForm.doAction).toHaveBeenCalledWith('standardsubmit', opts);
        });

        it("should call doAction with 'directsubmit' if the api config is defined", function() {
            basicForm.api = {};
            var opts = {};

            spyOn(basicForm, 'doAction');
            basicForm.submit(opts);
            expect(basicForm.doAction).toHaveBeenCalledWith('directsubmit', opts);
        });
    });

    describe("load method", function() {
        it("should call doAction with 'load' by default", function() {
            var opts = {};

            spyOn(basicForm, 'doAction');
            basicForm.load(opts);
            expect(basicForm.doAction).toHaveBeenCalledWith('load', opts);
        });

        it("should call doAction with 'directload' if the api config is defined", function() {
            basicForm.api = {};
            var opts = {};

            spyOn(basicForm, 'doAction');
            basicForm.load(opts);
            expect(basicForm.doAction).toHaveBeenCalledWith('directload', opts);
        });
    });

    describe("checkValidity method", function() {
        it("should be called when a field's 'validitychange' event is fired", function() {
            var spy = spyOn(Ext.form.Basic.prototype, 'checkValidity');

            var field = addField({ name: 'one' });

            field.fireEvent('validitychange', field, false);

            waitForSpy(spy, "checkValidity was not called", 1000);
        });

        it("should fire the 'validitychange' event if the overall validity of the form has changed", function() {
            var spy = jasmine.createSpy('validitychange handler'),
                field1 = addField({ name: 'one' }),
                field2 = addField({ name: 'two' });

            basicForm.checkValidity();
            basicForm.on('validitychange', spy);

            field1.isValid = function() { return false; };

            basicForm.checkValidity();
            expect(spy).toHaveBeenCalled();
        });

        it("should not fire the 'validitychange' event if the overally validity of the form has not changed", function() {
            var spy = jasmine.createSpy('validitychange handler'),
                field1 = addField({ name: 'one', isValid: function() { return false; } }),
                field2 = addField({ name: 'two', isValid: function() { return false; } });

            basicForm.checkValidity();
            basicForm.on('validitychange', spy);

            field1.isValid = function() { return true; };

            basicForm.checkValidity();
            expect(spy).not.toHaveBeenCalled();
        });

        describe("add/remove items", function() {
            var checkValiditySpy;

            beforeEach(function() {
                checkValiditySpy = spyOn(Ext.form.Basic.prototype, 'checkValidity');
            });

            afterEach(function() {
                checkValiditySpy = null;
            });

            it("should checkValidity when removing a field", function() {
                addField({ name: 'one' });
                addField({ name: 'two' });
                container.remove(0);

                waitForSpy(checkValiditySpy, "checkValidity was not called", 1000);
            });

            it("should checkValidity when adding a field", function() {
                addField({ name: 'one' });
                addField({ name: 'two' });

                waitForSpy(checkValiditySpy, "checkValidity was not called", 1000);
            });

            it("should checkValidity when removing a container that contains a field", function() {
                var myCt = container.add({
                    xtype: 'container'
                });

                addField({ name: 'one' }, myCt);
                container.remove(0);

                waitForSpy(checkValiditySpy, "checkValidity was not called", 1000);
            });

            it("should checkValidity when adding a container that contains a field", function() {
                var myCt = new Ext.container.Container();

                addField({ name: 'one' }, myCt);
                container.add(myCt);

                waitForSpy(checkValiditySpy, "checkValidity was not called", 1000);
            });
        });
    });

    describe("checkDirty method", function() {
        it("should be called when a field's 'dirtychange' event is fired", function() {
            runs(function() {
                spyOn(basicForm, 'checkDirty');
                // Modify the task to point to the spy
                basicForm.checkDirtyTask = new Ext.util.DelayedTask(basicForm.checkDirty, basicForm);
                var field = addField({ name: 'one' });

                field.fireEvent('dirtychange', field, false);
            });
            waitsFor(function() {
                return basicForm.checkDirty.callCount === 1;
            }, "checkDirty was not called");
        });

        it("should fire the 'dirtychange' event if the overall dirty state of the form has changed", function() {
            var spy = jasmine.createSpy('dirtychange handler'),
                field1 = addField({ name: 'one' }),
                field2 = addField({ name: 'two' });

            basicForm.checkDirty();
            basicForm.on('dirtychange', spy);

            field1.isDirty = function() { return true; };

            basicForm.checkDirty();
            expect(spy).toHaveBeenCalled();
        });

        it("should not fire the 'dirtychange' event if the overally dirty state of the form has not changed", function() {
            var spy = jasmine.createSpy('dirtychange handler'),
                field1 = addField({ name: 'one', isDirty: function() { return true; } }),
                field2 = addField({ name: 'two', isDirty: function() { return true; } });

            basicForm.checkDirty();
            basicForm.on('dirtychange', spy);

            field1.isDirty = function() { return false; };

            basicForm.checkDirty();
            expect(spy).not.toHaveBeenCalled();
        });
    });

    describe("formBind child component property", function() {
        it("should disable a child component with formBind=true when the form becomes invalid", function() {
            var field1 = addField({ name: 'one', isValid: function() { return true; } }),
                field2 = addField({ name: 'two', isValid: function() { return true; } }),
                button = new Ext.Button({ formBind: true });

            basicForm.checkValidity();

            spyOn(button, 'setDisabled');
            container.add(button);

            field1.isValid = function() { return false; };

            basicForm.checkValidity();
            expect(button.setDisabled).toHaveBeenCalledWith(true);
        });

        it("should enable a child component with formBind=true when the form becomes valid", function() {
            var field1 = addField({ name: 'one', isValid: function() { return false; } }),
                field2 = addField({ name: 'two', isValid: function() { return true; } }),
                button = new Ext.Button({ formBind: true, disabled: true });

            basicForm.checkValidity();

            spyOn(button, 'setDisabled');
            container.add(button);

            field1.isValid = function() { return true; };

            basicForm.checkValidity();
            expect(button.setDisabled).toHaveBeenCalledWith(false);
        });

        it("should not disable a child component with formBind=true when the form remains invalid", function() {
            var field1 = addField({ name: 'one', isValid: function() { return false; } }),
                field2 = addField({ name: 'two' }),
                button = new Ext.Button({ formBind: true });

            basicForm.checkValidity();

            spyOn(button, 'setDisabled');
            container.add(button);

            basicForm.checkValidity();
            expect(button.setDisabled).not.toHaveBeenCalled();
        });

        it("should not enable a child component with formBind=true when the form remains valid", function() {
            var field1 = addField({ name: 'one', isValid: function() { return true; } }),
                field2 = addField({ name: 'two', isValid: function() { return true; } }),
                button = new Ext.Button({ formBind: true, disabled: true });

            basicForm.checkValidity();

            spyOn(button, 'setDisabled');
            container.add(button);

            field1.isValid = function() { return true; };

            basicForm.checkValidity();
            expect(button.setDisabled).not.toHaveBeenCalled();
        });

        it('should update a formBind button\'s state when a field changes enabled/disabled state', function() {
            var field1 = addField({ name: 'one', isValid: function() { return true; } }),
                field2 = container.add({ xtype: 'textfield', name: 'two', allowBlank: false }),
                button = new Ext.Button({ formBind: true });

            container.add(button);
            basicForm.checkValidity();

            expect(button.disabled).toBe(true);
            field2.disable();

            // Validation state is evaluated on a delay
            waitsFor(function() {
                return button.disabled === false;
            });
        });
    });

    describe("loadRecord method", function() {
        it("should call setValues with the record's data", function() {
            var data = {
                one: 'value 1',
                 two: 'value 2'
            },
            record = {
                getData: function() {
                    return data;
                }
            };

            spyOn(basicForm, 'setValues');
            basicForm.loadRecord(record);
            expect(basicForm.setValues).toHaveBeenCalledWith(data);
        });

        it("should keep a reference to the record on the form", function() {
            var data = {
                one: 'value 1',
                two: 'value 2'
            },
            record = {
                getData: function() {
                    return data;
                }
            };

            basicForm.loadRecord(record);
            expect(basicForm.getRecord()).toBe(record);
        });
    });

    describe("updateRecord method", function() {
        var model;

        beforeEach(function() {
            Ext.define('BasicFormTestModel', {
                extend: 'Ext.data.Model',
                fields: ['one', { type: 'int', name: 'two' }, { type: 'date', name: 'three' }, { name: 'four' }]
            });
            model = new BasicFormTestModel();
        });

        afterEach(function() {
            Ext.undefine('BasicFormTestModel');
            Ext.data.Model.schema.clear();
        });

        it("should update fields on a given model to match corresponding form fields", function() {
            var date = new Date();

            addField({ name: 'one', value: 'valueone' });
            addField(new Ext.form.field.Number({ name: 'two', value: 2 }));
            addField(new Ext.form.field.Date({ name: 'three', value: date }));

            basicForm.updateRecord(model);

            expect(model.get('one')).toBe('valueone');
            expect(model.get('two')).toBe(2);

            var d1 = model.get('three'),
                d2 = date;

            expect(d1.getFullYear()).toBe(d2.getFullYear());
            expect(d1.getMonth()).toBe(d2.getMonth());
            expect(d1.getDate()).toBe(d2.getDate());
        });

        it("should use a record specified by loadRecord if one isn't provided", function() {
            basicForm.loadRecord(model);
            var date = new Date();

            addField({ name: 'one', value: 'valueone' });
            addField(new Ext.form.field.Number({ name: 'two', value: 2 }));
            addField(new Ext.form.field.Date({ name: 'three', value: date }));

            basicForm.updateRecord();

            expect(model.get('one')).toBe('valueone');
            expect(model.get('two')).toBe(2);
            var d1 = model.get('three'),
                d2 = date;

            expect(d1.getFullYear()).toBe(d2.getFullYear());
            expect(d1.getMonth()).toBe(d2.getMonth());
            expect(d1.getDate()).toBe(d2.getDate());
        });
    });
});
