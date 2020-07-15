topSuite("Ext.form.field.Time", function() {
    var component, makeComponent;

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            Ext.applyIf(config, {
                name: 'test',
                width: 100
            });
            component = new Ext.form.field.Time(config);
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    function clickTrigger() {
        var trigger = component.getTrigger('picker').getEl(),
            xy = trigger.getXY();

        jasmine.fireMouseEvent(trigger.dom, 'click', xy[0], xy[1]);
    }

    describe("alternate class name", function() {
        it("should have Ext.form.TimeField as the alternate class name", function() {
            expect(Ext.form.field.Time.prototype.alternateClassName).toEqual(["Ext.form.TimeField", "Ext.form.Time"]);
        });

        it("should allow the use of Ext.form.TimeField", function() {
            expect(Ext.form.TimeField).toBeDefined();
        });
    });

    it("should be registered with xtype 'timefield'", function() {
        component = Ext.create("Ext.form.field.Time", { name: 'test' });
        expect(component instanceof Ext.form.field.Time).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("timefield");
    });

    describe("initialization", function() {

        describe("initializing value as string", function() {
            it("should not mark component as dirty", function() {
                makeComponent({
                    value: '11:00 AM'
                });
                expect(component.isDirty()).toBeFalsy();
            });
        });

    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });
        it("should have triggerCls = 'x-form-time-trigger'", function() {
            expect(component.triggerCls).toEqual('x-form-time-trigger');
        });
        it("should have multiSelect = false", function() {
            expect(component.multiSelect).toBe(false);
        });
        it("should have delimiter = ', '", function() {
            expect(component.delimiter).toEqual(', ');
        });
        it("should have minValue = undefined", function() {
            expect(component.minValue).not.toBeDefined();
        });
        it("should have maxValue = undefined", function() {
            expect(component.maxValue).not.toBeDefined();
        });
        it("should have minText = 'The time in this field must be equal to or after {0}'", function() {
            expect(component.minText).toEqual('The time in this field must be equal to or after {0}');
        });
        it("should have maxText = 'The time in this field must be equal to or before {0}'", function() {
            expect(component.maxText).toEqual('The time in this field must be equal to or before {0}');
        });
        it("should have invalidText = '{0} is not a valid time'", function() {
            expect(component.invalidText).toEqual('{0} is not a valid time');
        });
        it("should have format = 'g:i A'", function() {
            expect(component.format).toEqual('g:i A');
        });
        it("should have altFormats = 'g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|ha|gA|h a|g a|g A|gi|hi|gia|hia|g|H|gi a|hi a|giA|hiA|gi A|hi A'", function() {
            expect(component.altFormats).toEqual('g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|ha|gA|h a|g a|g A|gi|hi|gia|hia|g|H|gi a|hi a|giA|hiA|gi A|hi A');
        });
        it("should have increment = 15", function() {
            expect(component.increment).toEqual(15);
        });
    });

    describe("rendering", function() {
        // Mostly handled by Trigger and Picker tests

        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
        });

        it("should give the trigger a class of 'x-form-time-trigger'", function() {
            expect(component.getTrigger('picker').el).toHaveCls('x-form-time-trigger');
        });
    });

    describe("trigger", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            clickTrigger();
        });
        it("should open a Ext.picker.Time", function() {
            expect(component.picker instanceof Ext.picker.Time).toBe(true);
            expect(component.picker.hidden).toBe(false);
        });
        it("should clear the value when text which cannot be matched to a value is entered", function() {
            waits(1);
            runs(function() {
                expect(component.picker.highlightedItem).toBeDefined();
                component.setValue('ASAP');
                expect(component.getValue()).toBeNull();
                expect(component.getRawValue()).toBe('ASAP');
            });
        });
    });

    describe("setting values", function() {
        describe("parsing", function() {
            it("should parse a string value using the format config", function() {
                makeComponent({
                    format: 'g:iA',
                    value: '8:32PM'
                });
                expect(component.getValue()).toEqualTime(20, 32);
            });

            it("should parse a string value using the altFormats config", function() {
                makeComponent({
                    format: 'g:i.A',
                    altFormats: 'g.i a',
                    value: '8.32 pm'
                });
                expect(component.getValue()).toEqualTime(20, 32);
            });

            it("should parse a string value using the format config and snap to increment", function() {
                makeComponent({
                    snapToIncrement: true,
                    format: 'g:iA',
                    value: '8:32PM'
                });
                expect(component.getValue()).toEqualTime(20, 30);
            });

            it("should parse a string value using the altFormats config and snap to increment", function() {
                makeComponent({
                    snapToIncrement: true,
                    format: 'g:i.A',
                    altFormats: 'g.i a',
                    value: '8.32 pm'
                });
                expect(component.getValue()).toEqualTime(20, 30);
            });
        });

        describe("setValue", function() {
            it("should accept a date object", function() {
                makeComponent();
                component.setValue(new Date(2010, 10, 5, 9, 46));
                expect(component.getValue()).toEqualTime(9, 46);
            });

            it("should accept an array of date objects", function() {
                makeComponent({
                    multiSelect: true
                });
                component.setValue([new Date(2008, 0, 1, 10, 30), new Date(2008, 0, 1, 23, 15)]);
                expect(component.getValue()[0]).toEqualTime(10, 30);
                expect(component.getValue()[1]).toEqualTime(23, 15);
            });

            it("should accept a string value", function() {
                makeComponent();
                component.setValue('9:46 AM');
                expect(component.getValue()).toEqualTime(9, 46);
            });

            it("should accept an array of string values", function() {
                makeComponent({
                    multiSelect: true,
                    value: ['10:30AM', '11:15PM']
                });
                expect(component.value[0]).toEqualTime(10, 30);
                expect(component.value[1]).toEqualTime(23, 15);
            });

            it("should accept a date object and snap to increment", function() {
                makeComponent({
                    snapToIncrement: true
                });
                component.setValue(new Date(2010, 10, 5, 9, 46));
                expect(component.getValue()).toEqualTime(9, 45);
            });

            it("should accept a string value and snap to increment", function() {
                makeComponent({
                    snapToIncrement: true
                });
                component.setValue('9:46 AM');
                expect(component.getValue()).toEqualTime(9, 45);
            });

            it("should accept a null value", function() {
                makeComponent();
                component.setValue(null);
                expect(component.getValue()).toBeNull();
            });

            it("should set null if an invalid time string is passed", function() {
                makeComponent();
                component.setValue('6:::37');
                expect(component.getValue()).toBeNull();
            });

            it("should ignore the date part when setting the value", function() {
                makeComponent({
                    minValue: '9:00 AM',
                    maxValue: '5:00 PM'
                });
                // The date year/month/day will be equal to whenever the spec is run
                // But the time field defaults all dates to 2008/01/01.
                var d = new Date();

                d.setHours(12, 0);
                component.setValue(d);
                expect(component.isValid()).toBe(true);
            });
            it("should update the expanded dropdown's selection - multi select", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    multiSelect: true
                });
                component.expand();
                waits(1);
                runs(function() {
                    component.setValue([new Date(2008, 0, 1, 0, 0), new Date(2008, 0, 1, 0, 15)]);
                    expect(component.picker.getSelectionModel().getSelection()[0]).toEqual(component.store.getAt(0));
                    expect(component.picker.getSelectionModel().getSelection()[1]).toEqual(component.store.getAt(1));
                });
            });

            describe("selecting a value", function() {
                it("should be able to select a value when the current value is not in the store", function() {
                    makeComponent({
                        increment: 15,
                        format: 'H:i',
                        allowBlank: false,
                        value: '15:03',
                        renderTo: document.body
                    });

                    component.expand();
                    jasmine.fireMouseEvent(component.getPicker().getNode(component.store.getAt(0)), 'click');
                    expect(component.getValue()).toEqualTime(0, 0);

                });
            });

            describe("inputEl", function() {
                it("should accept a model", function() {
                    makeComponent({
                        minValue: '6:00 AM',
                        maxValue: '8:00 PM',
                        renderTo: document.body
                    });
                    component.setValue(component.store.getAt(0));
                    expect(component.inputEl.getValue()).toBe('6:00 AM');
                });

                it("should parse a string value to lookup a record in the store", function() {
                    makeComponent({
                        minValue: '6:00 AM',
                        maxValue: '8:00 PM',
                        renderTo: document.body
                    });
                    component.setValue('15');
                    expect(component.inputEl.getValue()).toBe('3:00 PM');
                });

                it("should display same value given to setValue when no lookups in the store", function() {
                    makeComponent({
                        minValue: '6:00 AM',
                        maxValue: '8:00 PM',
                        renderTo: document.body
                    });
                    component.setValue('21');
                    expect(component.inputEl.getValue()).toBe('21');
                });

                describe("validating as you type", function() {
                    it("should validate date format", function() {
                        var raw, errors;

                        makeComponent({
                            renderTo: document.body
                        });
                        component.inputEl.dom.value = 'foo';
                        component.doRawQuery();

                        raw = component.getRawValue();
                        errors = component.getErrors();

                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('foo is not a valid time');
                    });

                    it("should validate minValue", function() {
                        var raw, errors;

                        makeComponent({
                            minValue: '8:00 AM',
                            minText: 'too early',
                            renderTo: document.body
                        });
                        component.inputEl.dom.value = 1;
                        component.doRawQuery();

                        raw = component.getRawValue();
                        errors = component.getErrors();

                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('too early');
                    });

                    it("should validate maxValue", function() {
                        var raw, errors;

                        makeComponent({
                            maxValue: '8:00 AM',
                            maxText: 'too late',
                            renderTo: document.body
                        });
                        component.inputEl.dom.value = 9;
                        component.doRawQuery();

                        raw = component.getRawValue();
                        errors = component.getErrors();

                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('too late');
                    });
                });
            });

            describe("change event", function() {
                it("should not fire the change event when the value stays the same - single value", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        renderTo: Ext.getBody(),
                        value: '10:00AM',
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue('10:00AM');
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire the change event when the value changes - single value", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        value: '10:00AM',
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue('11:15PM');
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.args[0]).toBe(component);
                    expect(spy.mostRecentCall.args[1]).toEqualTime(23, 15);
                });

                it("should not fire the change event when the value stays the same - multiple values", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        multiSelect: true,
                        valueField: 'val',
                        value: ['10:00AM', '11:15PM'],
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue(['10:00AM', '11:15PM']);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire the change event when the value changes - multiple values", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        multiSelect: true,
                        valueField: 'val',
                        value: ['10:00AM', '11:15PM'],
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue(['3:00PM', '4:30PM']);
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.args[0]).toBe(component);
                    expect(spy.mostRecentCall.args[2][0]).toEqualTime(10);
                    expect(spy.mostRecentCall.args[2][1]).toEqualTime(23, 15);
                    expect(spy.mostRecentCall.args[1][0]).toEqualTime(15);
                    expect(spy.mostRecentCall.args[1][1]).toEqualTime(16, 30);
                });
            });
        });
    });

    describe("submit value", function() {
        it("should use the format as the default", function() {
            makeComponent({
                value: new Date(2010, 0, 15, 15, 30),
                format: 'H:i'
            });
            expect(component.getSubmitValue()).toBe('15:30');
        });

        it("should give precedence to submitFormat", function() {
            makeComponent({
                value: new Date(2010, 0, 15, 15, 45),
                submitFormat: 'H:i'
            });
            expect(component.getSubmitValue()).toBe('15:45');
        });

        it("should still return null if the value isn't a valid date", function() {
            makeComponent({
                value: 'wontparse',
                submitFormat: 'H:i'
            });
            expect(component.getSubmitValue()).toBeNull();
        });
    });

    describe("getModelData", function() {
        it("should use the format as the default", function() {
            makeComponent({
                name: 'myname',
                value: new Date(2010, 0, 15, 15, 45)
            });
            var modelData = component.getModelData();

            expect(modelData.myname).toEqualTime(15, 45);
        });

        it("should return null if the value isn't a valid date", function() {
            makeComponent({
                name: 'myname',
                value: 'wontparse',
                submitFormat: 'H:i'
            });
            expect(component.getModelData()).toEqual({ myname: null });
        });
    });

    describe("minValue", function() {
        describe("minValue config", function() {
            it("should allow a string, parsed according to the format config", function() {
                makeComponent({
                    format: 'g:i.A',
                    minValue: '8:30.AM'
                });
                expect(component.minValue).toEqualTime(8, 30);
            });

            it("should allow times after it to pass validation", function() {
                makeComponent({
                    minValue: '8:45 AM',
                    value: '9:15 AM'
                });
                expect(component.getErrors().length).toEqual(0);
            });

            it("should cause times before it to fail validation", function() {
                makeComponent({
                    minValue: '10:45 AM',
                    value: '9:15 AM'
                });
                expect(component.getErrors().length).toEqual(1);
                expect(component.getErrors()[0]).toEqual('The time in this field must be equal to or after 10:45 AM');
            });

            it("should fall back to 12AM if the string cannot be parsed", function() {
                makeComponent({
                    minValue: 'foopy',
                    value: '12:00 AM'
                });
                expect(component.getErrors().length).toEqual(0);
            });

            it("should allow a Date object", function() {
                makeComponent({
                    minValue: new Date(2010, 1, 1, 8, 30)
                });
                expect(component.minValue).toEqualTime(8, 30);
            });

            it("should be passed to the time picker object", function() {
                makeComponent({
                    minValue: '8:45 AM'
                });
                component.expand();
                expect(component.getPicker().minValue).toEqualTime(8, 45);
            });
        });

        describe("setMinValue method", function() {
            it("should allow a string, parsed according to the format config", function() {
                makeComponent({
                    format: 'g:i A'
                });
                component.setMinValue('1:15 PM');
                expect(component.minValue).toEqualTime(13, 15);
            });

            it("should allow times after it to pass validation", function() {
                makeComponent({
                    value: '9:15 AM'
                });
                component.setMinValue('7:45 AM');
                expect(component.getErrors().length).toEqual(0);
            });

            it("should cause times before it to fail validation", function() {
                makeComponent({
                    value: '9:15 AM'
                });
                component.setMinValue('10:45 AM');
                expect(component.getErrors().length).toEqual(1);
                expect(component.getErrors()[0]).toEqual('The time in this field must be equal to or after 10:45 AM');
            });

            it("should fall back to 12AM if the string cannot be parsed", function() {
                makeComponent({
                    value: '12:00 AM'
                });
                component.setMinValue('foopy');
                expect(component.getErrors().length).toEqual(0);
            });

            it("should allow a Date object", function() {
                makeComponent();
                component.setMinValue(new Date(2010, 1, 1, 8, 30));
                expect(component.minValue).toEqualTime(8, 30);
            });

            it("should call the time picker's setMinValue method", function() {
                makeComponent();
                component.expand();
                var spy = spyOn(component.getPicker(), 'setMinValue');

                component.setMinValue('11:15 AM');
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toEqualTime(11, 15);
            });
        });
    });

    describe("maxValue", function() {
        describe("maxValue config", function() {
            it("should allow a string, parsed according to the format config", function() {
                makeComponent({
                    format: 'g:i.A',
                    maxValue: '8:30.PM'
                });
                expect(component.maxValue).toEqualTime(20, 30);
            });

            it("should allow times before it to pass validation", function() {
                makeComponent({
                    maxValue: '8:45 PM',
                    value: '7:15 PM'
                });
                expect(component.getErrors().length).toEqual(0);
            });

            it("should cause times after it to fail validation", function() {
                makeComponent({
                    maxValue: '8:45 PM',
                    value: '9:15 PM'
                });
                expect(component.getErrors().length).toEqual(1);
                expect(component.getErrors()[0]).toEqual('The time in this field must be equal to or before 8:45 PM');
            });

            it("should fall back to the end of the day if the string cannot be parsed", function() {
                makeComponent({
                    maxValue: 'foopy',
                    value: '11:59 PM'
                });
                expect(component.getErrors().length).toEqual(0);
            });

            it("should allow a Date object", function() {
                makeComponent({
                    maxValue: new Date(2010, 1, 1, 20, 30)
                });
                expect(component.maxValue).toEqualTime(20, 30);
            });

            it("should be passed to the time picker object", function() {
                makeComponent({
                    maxValue: '8:45 PM'
                });
                component.expand();
                expect(component.getPicker().maxValue).toEqualTime(20, 45);
            });
        });

        describe("setMaxValue method", function() {
            it("should allow a string, parsed according to the format config", function() {
                makeComponent({
                    format: 'g:i A'
                });
                component.setMaxValue('1:15 PM');
                expect(component.maxValue).toEqualTime(13, 15);
            });

            it("should allow times before it to pass validation", function() {
                makeComponent({
                    value: '5:15 PM'
                });
                component.setMaxValue('7:45 PM');
                expect(component.getErrors().length).toEqual(0);
            });

            it("should cause times after it to fail validation", function() {
                makeComponent({
                    value: '9:15 PM'
                });
                component.setMaxValue('7:45 PM');
                expect(component.getErrors().length).toEqual(1);
                expect(component.getErrors()[0]).toEqual('The time in this field must be equal to or before 7:45 PM');
            });

            it("should fall back to the end of the day if the string cannot be parsed", function() {
                makeComponent({
                    value: '11:59 PM'
                });
                component.setMaxValue('foopy');
                expect(component.getErrors().length).toEqual(0);
            });

            it("should allow a Date object", function() {
                makeComponent();
                component.setMaxValue(new Date(2010, 1, 1, 20, 30));
                expect(component.maxValue).toEqualTime(20, 30);
            });

            it("should call the time picker's setMaxValue method", function() {
                makeComponent();
                component.expand();
                var spy = spyOn(component.getPicker(), 'setMaxValue');

                component.setMaxValue('11:15 PM');
                expect(spy).toHaveBeenCalled();
                expect(spy.mostRecentCall.args[0]).toEqualTime(23, 15);
            });
        });
    });

    describe('onBlur', function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
        });

        it('should format the raw value', function() {
            jasmine.focusAndWait(component);

            runs(function() {
                component.setRawValue('123');

                // Programmatic blur fails on IEs. Focus then remove a button
                Ext.getBody().createChild({ tag: 'button' }).focus().remove();
            });

            waitsFor(function() {
                return !component.hasFocus;
            }, 'the TimeField to blur', 1000);

            runs(function() {
                expect(component.getRawValue()).toEqual('1:23 AM');
            });
        });

        it('should not reset the hours, minutes or seconds', function() {
            var parts, d;

            parts = component.initDateParts;
            d = new Date(parts[0], parts[1], parts[2], 13, 22, 42);

            jasmine.focusAndWait(component);

            runs(function() {
                component.setValue(d);
                component.blur();
            });

            waitsFor(function() {
                return !component.hasFocus;
            }, 'the TimeField to blur', 1000);

            runs(function() {
                expect(component.getValue()).toEqual(d);
            });
        });
    });

    describe("validation", function() {
        it("should return the invalidText if an invalid time string is entered via text", function() {
            makeComponent();
            component.setRawValue('01:000 AM');
            expect(component.getErrors()[0]).toBe(Ext.String.format(component.invalidText, '01:000 AM'));
        });
    });

    describe('syncSelection', function() {
        it('should call select on the selection model with the new value record if there are no valid selections and forceSelect is false', function() {
            makeComponent({
                minValue: '7:00 PM',
                maxValue: '9:15 PM'
            });

            spyOn(component.picker.selModel, 'select');
            component.setValue('1');

            expect(component.picker.selModel.select).toHaveBeenCalled();
        });
    });

    describe('forceSelection', function() {
        function doTest(value) {
            makeComponent({
                forceSelection: true,
                minValue: '7:00 PM',
                maxValue: '9:15 PM',
                value: value
            });
        }

        it('should work with a legitimate value', function() {
            var v = '9:00 PM';

            doTest(v);

            expect(component.selection.data.disp).toBe(v);
        });

        it('should work with an illegitimate value', function() {
            doTest('9:01 PM');

            expect(component.selection).toBe(null);
        });
    });
});
