topSuite("Ext.form.field.Number", ['Ext.app.ViewModel'], function() {
    var component, makeComponent;

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            Ext.applyIf(config, {
                name: 'test'
            });
            component = new Ext.form.field.Number(config);
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.form.NumberField as the alternate class name", function() {
            expect(Ext.form.field.Number.prototype.alternateClassName).toEqual(["Ext.form.NumberField", "Ext.form.Number"]);
        });

        it("should allow the use of Ext.form.NumberField", function() {
            expect(Ext.form.NumberField).toBeDefined();
        });
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should have an inputType of 'text'", function() {
            expect(component.inputType).toEqual('text');
        });
        it("should have allowDecimals = true", function() {
            expect(component.allowDecimals).toBe(true);
        });
        it("should have decimalSeparator = '.'", function() {
            expect(component.decimalSeparator).toEqual('.');
        });
        it("should have decimalPrecision = 2", function() {
            expect(component.decimalPrecision).toEqual(2);
        });
        it("should have minValue = NEGATIVE_INFINITY", function() {
            expect(component.minValue).toEqual(Number.NEGATIVE_INFINITY);
        });
        it("should have maxValue = MAX_VALUE", function() {
            expect(component.maxValue).toEqual(Number.MAX_VALUE);
        });
        it("should have step = 1", function() {
            expect(component.step).toEqual(1);
        });
        it("should have minText = 'The minimum value for this field is {0}'", function() {
            expect(component.minText).toEqual('The minimum value for this field is {0}');
        });
        it("should have maxText = 'The maximum value for this field is {0}'", function() {
            expect(component.maxText).toEqual('The maximum value for this field is {0}');
        });
        it("should have nanText = '{0} is not a valid number'", function() {
            expect(component.nanText).toEqual('{0} is not a valid number');
        });
        it("should have negativeText = 'The value cannot be negative'", function() {
            expect(component.negativeText).toEqual('The value cannot be negative');
        });
        it("should have baseChars = '0123456789'", function() {
            expect(component.baseChars).toEqual('0123456789');
        });
        it("should have autoStripChars = false", function() {
            expect(component.autoStripChars).toBe(false);
        });

        describe("rendered", function() {
            beforeEach(function() {
                component.render(Ext.getBody());
            });

            it("should have spinbutton role", function() {
                expect(component).toHaveAttr('role', 'spinbutton');
            });

            it("should not have aria-valuemin", function() {
                expect(component).not.toHaveAttr('aria-valuemin');
            });

            it("should not have aria-valuemax", function() {
                expect(component).not.toHaveAttr('aria-valuemax');
            });

            it("should not have aria-valuenow", function() {
                expect(component).not.toHaveAttr('aria-valuenow');
            });

            it("should not have aria-valuetext", function() {
                expect(component).not.toHaveAttr('aria-valuetext');
            });
        });
    });

    describe("ARIA attributes", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                minValue: 1,
                maxValue: 100,
                value: 50
            });
        });

        it("should have aria-valuemin", function() {
            expect(component).toHaveAttr('aria-valuemin', '1');
        });

        it("should have aria-valuemax", function() {
            expect(component).toHaveAttr('aria-valuemax', '100');
        });

        it("should have aria-valuenow", function() {
            expect(component).toHaveAttr('aria-valuenow', '50');
        });
    });

    describe("setMinValue", function() {

        it("should set the minValue property to the argument", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                minValue: -10
            });
            component.setMinValue(-5);
            expect(component.minValue).toEqual(-5);
        });
        it("should default a non-numeric argument to NEGATIVE_INFINITY", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                minValue: -10
            });
            component.setMinValue('foobar');
            expect(component.minValue).toEqual(Number.NEGATIVE_INFINITY);
        });

        it("should recalculate any maskRe/stripCharsRe", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                minValue: 0,
                autoStripChars: true
            });
            var maskRe = component.maskRe,
                stripCharsRe = component.stripCharsRe;

            component.setMinValue(-1);
            expect(component.maskRe).not.toBe(maskRe);
            expect(component.stripCharsRe).not.toBe(stripCharsRe);
        });

        it("should update aria-valuemin", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                minValue: -10
            });

            component.setMinValue(-1);

            expect(component).toHaveAttr('aria-valuemin', '-1');
        });
    });

    describe("setMaxValue", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                maxValue: 10
            });
        });

        it("should set the maxValue property to the argument", function() {
            component.setMaxValue(25);
            expect(component.maxValue).toEqual(25);
        });
        it("should default a non-numeric argument to MAX_VALUE", function() {
            component.setMaxValue('foobar');
            expect(component.maxValue).toEqual(Number.MAX_VALUE);
        });

        it("should update aria-valuemax", function() {
            component.setMaxValue(25);

            expect(component).toHaveAttr('aria-valuemax', '25');
        });
    });

    describe("parsing invalid values", function() {
        it("should be null if configured with no value", function() {
            makeComponent();
            expect(component.getValue()).toBeNull();
        });

        it("should be null if configured with an invalid value", function() {
            makeComponent({
                value: "foo"
            });
            expect(component.getValue()).toBeNull();
        });

        it("should set the field value to the parsed value on blur", function() {
            makeComponent({
                inputType: 'text', // forcing to text, otherwise chrome ignores the whole value if it contains non-numeric chars
                renderTo: Ext.getBody()
            });
            jasmine.focusAndWait(component);
            runs(function() {
                component.inputEl.dom.value = '15foo';
            });
            jasmine.blurAndWait(component);
            runs(function() {
                expect(component.inputEl.dom.value).toEqual('15');
            });
        });

        it("should remove aria-valuenow", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                value: 10
            });

            component.setValue('fubar');

            expect(component).not.toHaveAttr('aria-valuenow');
        });
    });

    describe("respecting allowDecimals", function() {
        it("should round any decimals when allowDecimals is false", function() {
            makeComponent({
                allowDecimals: false
            });

            component.setValue(1.2345);
            expect(component.getValue()).toEqual(1);

            component.setValue(7.9);
            expect(component.getValue()).toEqual(8);

            component.setValue(2);
            expect(component.getValue()).toEqual(2);
        });

        it("should round any decimals when decimalPrecision is 0", function() {
            makeComponent({
                decimalPrecision: 0
            });

            component.setValue(3.14);
            expect(component.getValue()).toEqual(3);

            component.setValue(19);
            expect(component.getValue()).toEqual(19);
        });

        it("should round values correctly", function() {
            makeComponent({
                decimalPrecision: 3
            });

            component.setValue(3.14159);
            expect(component.getValue()).toEqual(3.142);

            component.decimalPrecision = 1;
            component.setValue(1.94430194859);
            expect(component.getValue()).toEqual(1.9);
        });
    });

    describe("respecting decimalSeparator", function() {
        it("should parse values containing the separator", function() {
            makeComponent({
                decimalSeparator: ",",
                decimalPrecision: 2
            });

            component.setValue("1,3");
            expect(component.getValue()).toEqual(1.3);

            component.setValue(4);
            expect(component.getValue()).toEqual(4);

            component.setValue("1,728");
            expect(component.getValue()).toEqual(1.73);
        });
    });

    describe("submitLocaleSeparator", function() {
        it("should use the locale separator by default", function() {
            makeComponent({
                decimalSeparator: ',',
                value: 0.4
            });
            expect(component.getSubmitValue()).toBe('0,4');
        });

        it("should replace the separator with the default number", function() {
            makeComponent({
                decimalSeparator: ',',
                value: 0.4,
                submitLocaleSeparator: false
            });
            expect(component.getSubmitValue()).toBe('0.4');
        });

        it("should have no effect if we specify no custom separator", function() {
            makeComponent({
                value: 0.4
            });
            expect(component.getSubmitValue()).toBe('0.4');
        });
    });

    describe("validation", function() {
        it("should have an error when the number is outside the bounds", function() {
            makeComponent({
                minValue: 5,
                maxValue: 30
            });

            expect(component.getErrors(3)).toContain("The minimum value for this field is 5");

            expect(component.getErrors(100)).toContain("The maximum value for this field is 30");

            expect(component.getErrors(7.2)).toEqual([]);
        });

        it("should have an error when the number is invalid", function() {
            makeComponent();

            expect(component.getErrors("foo")).toContain("foo is not a valid number");

            expect(component.getErrors(17).length).toEqual(0);
        });

        it("should have an error if the value is negative and minValue is 0", function() {
            makeComponent({
                minValue: 0
            });

            expect(component.getErrors(-3)).toContain("The value cannot be negative");
        });
    });

    describe("autoStripChars", function() {
        beforeEach(function() {
            makeComponent({
                autoStripChars: true,
                inputType: 'text', // forcing to text, since chrome doesn't allow setting non-numeric chars in number field
                renderTo: Ext.getBody()
            });
        });

        it("should remove non-numeric characters from the input's raw value", function() {
            component.inputEl.dom.value = '123abc45de';
            expect(component.getValue()).toEqual(12345);
        });

        it("should support scientific number notation", function() {
            jasmine.focusAndWait(component);
            runs(function() {
                component.inputEl.dom.value = '10000000000000000000000000000000000000';
            });
            // This forces input value to be checked and converted
            jasmine.blurAndWait(component);
            runs(function() {
                expect(component.getValue()).toEqual(1e+37);
            });
        });
    });

    describe("enforceMaxLength", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                maxLength: 2,
                enforceMaxLength: true
            });
        });
        it("should enforce the max length when spinning up", function() {
            component.setValue(99);
            component.spinUp();
            expect(component.getValue()).toBe(99);
        });

        it("should enforce the max length when spinning down", function() {
            component.setValue(-9);
            component.spinDown();
            expect(component.getValue()).toBe(-9);
        });
    });

    describe("spinner buttons", function() {
        describe("spin up", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    value: 5,
                    step: 2,
                    maxValue: 8
                });
            });

            it("should increment the value by the step config", function() {
                component.onSpinUp();
                expect(component.getValue()).toEqual(7);
            });

            it("should not increment past the maxValue", function() {
                component.onSpinUp();
                component.onSpinUp();
                expect(component.getValue()).toEqual(8);
                component.onSpinUp();
                expect(component.getValue()).toEqual(8);
            });

            it("should disable the up button when at the maxValue", function() {
                component.onSpinUp();
                expect(component.spinUpEnabled).toBe(true);
                component.onSpinUp();
                expect(component.spinUpEnabled).toBe(false);
            });

            it("should update aria-valuenow", function() {
                component.onSpinUp();

                expect(component).toHaveAttr('aria-valuenow', '7');
            });
        });

        describe("spin down", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    value: 5,
                    step: 2,
                    minValue: 2
                });
            });

            it("should decrement the value by the step config", function() {
                component.onSpinDown();
                expect(component.getValue()).toEqual(3);
            });

            it("should not decrement past the minValue", function() {
                component.onSpinDown();
                component.onSpinDown();
                expect(component.getValue()).toEqual(2);
                component.onSpinDown();
                expect(component.getValue()).toEqual(2);
            });

            it("should disable the down button when at the minValue", function() {
                component.onSpinDown();
                expect(component.spinDownEnabled).toBe(true);
                component.onSpinDown();
                expect(component.spinDownEnabled).toBe(false);
            });

            it("should update aria-valuenow", function() {
                component.onSpinDown();

                expect(component).toHaveAttr('aria-valuenow', '3');
            });
        });
    });

    describe('getSubmitData', function() {
        it("should return the field's numeric value", function() {
            makeComponent({ name: 'myname', value: 123 });
            expect(component.getSubmitData()).toEqual({ myname: '123' });
        });
        it("should return empty string for an empty value", function() {
            makeComponent({ name: 'myname' });
            expect(component.getSubmitData()).toEqual({ myname: '' });
        });
        it("should return empty string for a non-numeric", function() {
            makeComponent({ name: 'myname', value: 'asdf' });
            expect(component.getSubmitData()).toEqual({ myname: '' });
        });
    });

    describe('getModelData', function() {
        it("should return the field's numeric value", function() {
            makeComponent({ name: 'myname', value: 123 });
            expect(component.getModelData()).toEqual({ myname: 123 });
        });
        it("should return null for an empty value", function() {
            makeComponent({ name: 'myname', value: '' });
            expect(component.getModelData()).toEqual({ myname: null });
        });
        it("should return null for a non-numeric value", function() {
            makeComponent({ name: 'myname', value: '' });
            expect(component.getModelData()).toEqual({ myname: null });
        });
    });

    describe("blur", function() {
        it("should call rawToValue inside blur", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                rawToValue: function(rawValue) {
                    return Ext.form.field.Number.prototype.rawToValue.call(this, rawValue / 2);
                },
                valueToRaw: function(value) {
                    return Ext.form.field.Number.prototype.valueToRaw.call(this, value * 2);
                }
            });
            component.setValue(50);
            jasmine.focusAndWait(component);
            jasmine.blurAndWait(component);
            runs(function() {
                expect(component.getValue()).toBe(50);
            });
        });
    });

    describe("with binding", function() {
        it("should leave the user typed value intact", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                viewModel: {},
                decimalPrecision: 4,
                bind: '{val}'
            });

            var vm = component.getViewModel();

            jasmine.focusAndWait(component);
            runs(function() {
                // Reaching in a bit far here, simulate typing a value
                component.inputEl.dom.value = '1.23456';
                component.checkChange();
                vm.notify();
                expect(component.inputEl.dom.value).toBe('1.23456');
                expect(vm.get('val')).toBe(1.2346);
                expect(component.getValue()).toBe(1.2346);
            });
        });
    });
});
