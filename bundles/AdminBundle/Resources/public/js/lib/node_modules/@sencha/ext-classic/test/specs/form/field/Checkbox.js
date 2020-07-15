topSuite("Ext.form.field.Checkbox", ['Ext.app.ViewController'], function() {
    var component;

    function makeComponent(config) {
        config = Ext.apply({
            name: 'test',
            renderTo: Ext.getBody()
        }, config);

        component = new Ext.form.field.Checkbox(config);
    }

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = null;
    });

    it("should be registered with the 'checkboxfield' xtype", function() {
        component = Ext.create("Ext.form.field.Checkbox", { name: 'test' });
        expect(component instanceof Ext.form.field.Checkbox).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("checkboxfield");
    });

    describe("configuring", function() {
        it("should accept a value config", function() {
            makeComponent({
                value: true
            });
            expect(component.checked).toBe(true);
        });
    });

    describe("rendering", function() {
        // NOTE this doesn't test the main label, error icon, etc. just the parts specific to Checkbox.

        describe("bodyEl", function() {
            beforeEach(function() {
                makeComponent({ value: 'foo' });
            });

            it("should exist", function() {
                expect(component.bodyEl).toBeDefined();
            });

            it("should have the class 'x-form-item-body'", function() {
                expect(component.bodyEl.hasCls('x-form-item-body')).toBe(true);
            });

            it("should have the id '[id]-bodyEl'", function() {
                expect(component.bodyEl.dom.id).toEqual(component.id + '-bodyEl');
            });
        });

        describe("inputEl (checkbox element)", function() {
            beforeEach(function() {
                makeComponent({ value: 'foo' });
            });

            it("should exist", function() {
                expect(component.inputEl).toBeDefined();
            });

            it("should be a child of the displayEl", function() {
                expect(component.inputEl.dom.parentNode).toBe(component.displayEl.dom);
            });

            it("should be an ancestor of the bodyEl", function() {
                expect(component.bodyEl.contains(component.inputEl)).toBe(true);
            });

            it("should be an input element", function() {
                expect(component.inputEl.dom.tagName.toLowerCase()).toBe('input');
            });

            it("should have type='checkbox'", function() {
                expect(component.inputEl.dom.getAttribute('type').toLowerCase()).toBe('checkbox');
            });

            it("should have the component's inputId as its id", function() {
                expect(component.inputEl.dom.id).toEqual(component.inputId);
            });

            it("should have the 'fieldCls' config as a class", function() {
                expect(component.displayEl.hasCls(component.fieldCls)).toBe(true);
            });

            describe("ARIA attributes", function() {
                it("should have aria-hidden", function() {
                    expect(component.inputEl).toHaveAttr('aria-hidden', 'false');
                });

                it("should have aria-disabled", function() {
                    expect(component.inputEl).toHaveAttr('aria-disabled', 'false');
                });

                it("should have aria-invalid", function() {
                    expect(component.inputEl).toHaveAttr('aria-invalid', 'false');
                });
            });
        });

        describe("box label", function() {
            it("should not be created by default", function() {
                makeComponent({});
                expect(component.bodyEl.child('label')).toBeNull();
            });

            it("should be created if the boxLabel config is defined", function() {
                makeComponent({ boxLabel: 'the box label' });
                expect(component.bodyEl.down('label')).not.toBeNull();
            });

            it("should be stored as a 'boxLabelEl' reference", function() {
                makeComponent({ boxLabel: 'the box label' });
                expect(component.bodyEl.down('label').dom).toBe(component.boxLabelEl.dom);
            });

            it("should have the class 'x-form-cb-label' by default", function() {
                makeComponent({ boxLabel: 'the box label' });
                expect(component.boxLabelEl.hasCls('x-form-cb-label')).toBe(true);
            });

            it("should be given the configured boxLabelCls", function() {
                makeComponent({ boxLabel: 'the box label', boxLabelCls: 'my-custom-boxLabelCls' });
                expect(component.boxLabelEl.hasCls('my-custom-boxLabelCls')).toBe(true);
            });

            it("should have a 'for' attribute set to the inputId", function() {
                makeComponent({ boxLabel: 'the box label' });
                expect(component.boxLabelEl.getAttribute('for')).toEqual(component.inputId);
            });

            it("should contain the boxLabel as its inner text node", function() {
                makeComponent({ boxLabel: 'the box label' });
                expect(component.boxLabelEl.dom).hasHTML('the box label');
            });

            describe('boxLabelAlign', function() {
                it("should render the label after the checkbox by default", function() {
                    makeComponent({ boxLabel: 'the box label' });
                    expect(component.boxLabelEl.prev()).toBe(component.displayEl);
                });

                it("should render the label after the checkbox when boxLabelAlign='after'", function() {
                    makeComponent({ boxLabel: 'the box label', boxLabelAlign: 'after' });
                    expect(component.boxLabelEl.prev()).toBe(component.displayEl);
                });

                it("should give the 'after' label a class of {boxLabelCls}-after", function() {
                    makeComponent({ boxLabel: 'the box label', boxLabelAlign: 'after' });
                    expect(component.boxLabelEl.hasCls(component.boxLabelCls + '-after')).toBe(true);
                });

                it("should render the label before the checkbox when boxLabelAlign='before'", function() {
                    makeComponent({ boxLabel: 'the box label', boxLabelAlign: 'before' });
                    expect(component.boxLabelEl.next()).toBe(component.displayEl);
                });

                it("should give the 'before' label a class of {boxLabelCls}-before", function() {
                    makeComponent({ boxLabel: 'the box label', boxLabelAlign: 'before' });
                    expect(component.boxLabelEl.hasCls(component.boxLabelCls + '-before')).toBe(true);
                });
            });

            describe("noBoxLabelCls", function() {
                it("should add the class when there is no boxLabel", function() {
                    makeComponent();
                    expect(component.el.down('.' + component.noBoxLabelCls, true)).not.toBeNull();
                });

                it("should not add the class when there is a boxLabel", function() {
                    makeComponent({
                        boxLabel: 'Foo'
                    });
                    expect(component.el.down('.' + component.noBoxLabelCls, true)).toBeNull();
                });
            });
        });
    });

    describe("setting value", function() {
        describe("via config", function() {
            describe("checked == null", function() {
                beforeEach(function() {
                    makeComponent();
                });

                it("should return falsy value", function() {
                    expect(component.getValue()).toBe(false);
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(false);
                });

                it("should not set checked attribute in the DOM", function() {
                    expect(component.inputEl).not.toHaveAttr('checked');
                });
            });

            describe("checked: true", function() {
                beforeEach(function() {
                    makeComponent({ checked: true });
                });

                it("should return truthy value", function() {
                    expect(component.getValue()).toBe(true);
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });

                it("should set checked attribute in the DOM", function() {
                    expect(component.inputEl).toHaveAttr('checked', 'checked');
                });
            });

            describe("checked: false", function() {
                beforeEach(function() {
                    makeComponent({ checked: false });
                });

                it("should return falsy value", function() {
                    expect(component.getValue()).toBe(false);
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(false);
                });

                it("should not set checked attribute in the DOM", function() {
                    expect(component.inputEl).not.toHaveAttr('checked');
                });
            });
        });

        describe("not rendered", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: null
                });
            });

            it("should return falsy value by default", function() {
                expect(component.getValue()).toBe(false);
            });

            it("should allow the value to be set", function() {
                component.setValue(true);
                expect(component.getValue()).toBe(true);
            });

            describe("before rendering", function() {
                beforeEach(function() {
                    component.setValue(true);
                    component.render(Ext.getBody());
                });

                it("should set checked property in the DOM after rendering", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });

                it("should set checked attribute in the DOM", function() {
                    expect(component.inputEl).toHaveAttr('checked');
                });
            });

            describe("after rendering", function() {
                beforeEach(function() {
                    component.render(Ext.getBody());
                    component.setValue(true);
                });

                it("should set checked property in the DOM after rendering", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });

                it("should not set checked attribute in the DOM", function() {
                    expect(component.inputEl).not.toHaveAttr('checked');
                });
            });
        });

        describe("setValue method", function() {
            beforeEach(function() {
                makeComponent();
            });

            describe("input value: boolean true", function() {
                beforeEach(function() {
                    component.setValue(true);
                });

                it("should return truthy value", function() {
                    expect(component.getValue()).toBeTruthy();
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });
            });

            describe("input value: string 'true'", function() {
                beforeEach(function() {
                    component.setValue('true');
                });

                it("should return truthy value", function() {
                    expect(component.getValue()).toBeTruthy();
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });
            });

            describe("input value: string '1'", function() {
                beforeEach(function() {
                    component.setValue('1');
                });

                it("should return truthy value", function() {
                    expect(component.getValue()).toBeTruthy();
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });
            });

            describe("input value: string 'on'", function() {
                beforeEach(function() {
                    component.setValue('on');
                });

                it("should return truthy value", function() {
                    expect(component.getValue()).toBeTruthy();
                });

                it("should set checked property in the DOM", function() {
                    expect(component.inputEl.dom.checked).toBe(true);
                });
            });

            describe("inputValue config", function() {
                beforeEach(function() {
                    component.inputValue = 'foo';
                });

                describe("input === inputValue", function() {
                    beforeEach(function() {
                        component.setValue('foo');
                    });

                    it("should return truthy value", function() {
                        expect(component.getValue()).toBeTruthy();
                    });

                    it("should set checked property in the DOM", function() {
                        expect(component.inputEl.dom.checked).toBe(true);
                    });
                });

                describe("input !== inputValue", function() {
                    beforeEach(function() {
                        component.setValue('bar');
                    });

                    it("should return falsy value", function() {
                        expect(component.getValue()).toBeFalsy();
                    });

                    it("should not set checked property in the DOM", function() {
                        expect(component.inputEl.dom.checked).toBe(false);
                    });
                });
            });
        });

        describe("handler", function() {
            var spy, scope;

            beforeEach(function() {
                scope = {};
                spy = jasmine.createSpy('handler');

                makeComponent({
                    handler: spy,
                    scope: scope
                });
            });

            describe("value changed", function() {
                beforeEach(function() {
                    component.setValue(true);
                });

                it("should fire the handler", function() {
                    expect(spy).toHaveBeenCalled();
                });

                it("should fire the handler with scope", function() {
                    expect(spy.mostRecentCall.scope).toBe(scope);
                });

                it("should fire the handler with arguments", function() {
                    expect(spy).toHaveBeenCalledWith(component, true);
                });
            });

            it("should not fire the handler if the value doesn't change", function() {
                component.setValue(false);
                expect(component.handler).not.toHaveBeenCalled();
            });

            it("should allow the handler to route to a view controller", function() {
                var ctrl = new Ext.app.ViewController();

                ctrl.someMethod = function() {};

                spyOn(ctrl, 'someMethod');

                var ct = new Ext.container.Container({
                    controller: ctrl,
                    renderTo: Ext.getBody(),
                    items: {
                        xtype: 'checkbox',
                        handler: 'someMethod'
                    }
                });

                ct.items.first().setValue(true);
                expect(ctrl.someMethod).toHaveBeenCalled();
                ct.destroy();
            });
        });
    });

    describe('readOnly', function() {
        it("should set the checkbox to disabled=true", function() {
            makeComponent({
                readOnly: true
            });

            expect(component.inputEl.dom.disabled).toBe(true);
        });

        describe('setReadOnly method', function() {
            it("should set disabled=true when the arg is true", function() {
                makeComponent({
                    readOnly: false
                });

                component.setReadOnly(true);
                expect(component.inputEl.dom.disabled).toBe(true);
            });

            it("should set disabled=false when the arg is false", function() {
                makeComponent({
                    readOnly: true
                });

                component.setReadOnly(false);
                expect(component.inputEl.dom.disabled).toBe(false);
            });

            it("should set disabled=true when the arg is false but the component is disabled", function() {
                makeComponent({
                    readOnly: true,
                    disabled: true
                });

                component.setReadOnly(false);
                expect(component.inputEl.dom.disabled).toBe(true);
            });
        });
    });

    describe('submit value', function() {
        it("should submit the inputValue when checked", function() {
            makeComponent({
                name: 'cb-name',
                inputValue: 'the-input-value',
                checked: true
            });
            expect(component.getSubmitData()).toEqual({ 'cb-name': 'the-input-value' });
        });

        it("should submit nothing when unchecked", function() {
            makeComponent({
                name: 'cb-name',
                inputValue: 'the-input-value',
                checked: false
            });
            expect(component.getSubmitData()).toBeNull();
        });

        it("should submit the uncheckedValue when unchecked, if defined", function() {
            makeComponent({
                name: 'cb-name',
                inputValue: 'the-input-value',
                uncheckedValue: 'the-unchecked-value',
                checked: false
            });
            expect(component.getSubmitData()).toEqual({ 'cb-name': 'the-unchecked-value' });
        });
    });

    describe('getModelData', function() {
        describe("without custom modelValues", function() {
            function makeBox(checked) {
                makeComponent({
                    checked: checked,
                    name: 'foo'
                });
            }

            it("should return false when not checked", function() {
                makeBox(false);
                expect(component.getModelData().foo).toBe(false);
            });

            it("should return true when checked", function() {
                makeBox(true);
                expect(component.getModelData().foo).toBe(true);
            });
        });

        describe("with custom modelValues", function() {
            function makeBox(checked) {
                makeComponent({
                    checked: checked,
                    name: 'foo',
                    modelValue: 'yes',
                    modelValueUnchecked: 'no'
                });
            }

            it("should return the unchecked value when not checked", function() {
                makeBox(false);
                expect(component.getModelData().foo).toBe('no');
            });

            it("should return the checked value when checked", function() {
                makeBox(true);
                expect(component.getModelData().foo).toBe('yes');
            });
        });
    });

    describe("setRawValue", function() {
        // Synthetic click events do not cause default action in IE8/9 :(
        (Ext.isIE9m ? xit : it)("should be able to fire the change event when checking after calling setRawValue", function() {
            var val;

            makeComponent();
            component.setRawValue(true);
            component.on('change', function(arg1, arg2) {
                val = arg2;
            });
            jasmine.fireMouseEvent(component.inputEl.dom, 'click');
            expect(val).toBe(false);
        });

        it("should be dirty after calling setRawValue", function() {
            makeComponent();
            component.setRawValue(true);
            expect(component.isDirty()).toBe(true);
        });

        describe("values", function() {
            describe("with an inputValue", function() {
                beforeEach(function() {
                    makeComponent({
                        inputValue: '2'
                    });
                });

                it("should check when the value is true", function() {
                    component.setRawValue(true);
                    expect(component.checked).toBe(true);
                });

                it("should check when the value is 'true'", function() {
                    component.setRawValue('true');
                    expect(component.checked).toBe(true);
                });

                it("should check when the value matches the inputValue", function() {
                    component.setRawValue('2');
                    expect(component.checked).toBe(true);
                });

                it("should check when the value == the inputValue", function() {
                    component.setRawValue(2);
                    expect(component.checked).toBe(true);
                });

                it("should not check when the value is 1", function() {
                    component.setRawValue(1);
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value is '1'", function() {
                    component.setRawValue('1');
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value is 'on'", function() {
                    component.setRawValue('on');
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value is false", function() {
                    component.setRawValue(false);
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value is 'false'", function() {
                    component.setRawValue('false');
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value doesn't match the inputValue", function() {
                    component.setRawValue('5');
                    expect(component.checked).toBe(false);
                });
            });

            describe("without an inputValue", function() {
                beforeEach(function() {
                    makeComponent();
                });

                it("should check when the value is true", function() {
                    component.setRawValue(true);
                    expect(component.checked).toBe(true);
                });

                it("should check when the value is 'true'", function() {
                    component.setRawValue('true');
                    expect(component.checked).toBe(true);
                });

                it("should check when the value is 1", function() {
                    component.setRawValue(1);
                    expect(component.checked).toBe(true);
                });

                it("should check when the value is '1'", function() {
                    component.setRawValue('1');
                    expect(component.checked).toBe(true);
                });

                it("should check when the value is 'on'", function() {
                    component.setRawValue('on');
                    expect(component.checked).toBe(true);
                });

                it("should not check when the value is false", function() {
                    component.setRawValue(false);
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value is 'false'", function() {
                    component.setRawValue('false');
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value is a number", function() {
                    component.setRawValue(5);
                    expect(component.checked).toBe(false);
                });

                it("should not check when the value contains 'on'", function() {
                    component.setRawValue('tone');
                    expect(component.checked).toBe(false);
                });
            });
        });
    });

    describe("setBoxLabel", function() {

        var boxOnlyWidth = 0,
            withLabelWidth = 0,
            label = '<div style="width: 100px;">a</div>';

        beforeEach(function() {
            var temp;

            if (boxOnlyWidth === 0) {
                temp = new Ext.form.field.Checkbox({
                    renderTo: Ext.getBody()
                });
                boxOnlyWidth = temp.getWidth();
                temp.destroy();

                temp = new Ext.form.field.Checkbox({
                    renderTo: Ext.getBody(),
                    boxLabel: label
                });
                withLabelWidth = temp.getWidth();
                temp.destroy();
            }
        });

        describe("before render", function() {
            describe("with an existing label", function() {
                it("should clear the label when passing an empty string", function() {
                    makeComponent({
                        boxLabel: 'Foo',
                        renderTo: null
                    });
                    component.setBoxLabel('');
                    component.render(Ext.getBody());
                    expect(component.getWidth()).toBe(boxOnlyWidth);
                });

                it("should change the label when passing an empty string", function() {
                    makeComponent({
                        boxLabel: 'Foo',
                        renderTo: null
                    });
                    component.setBoxLabel('');
                    component.render(Ext.getBody());
                    expect(component.getWidth()).toBe(boxOnlyWidth);
                });
            });

            describe("with no label configured", function() {
                it("should show the label", function() {
                    makeComponent({
                        renderTo: null
                    });
                    component.setBoxLabel(label);
                    component.render(Ext.getBody());
                    expect(component.getWidth()).toBe(withLabelWidth);
                });
            });
        });

        describe("after render", function() {
            describe("with an existing label", function() {
                it("should clear the label when passing an empty string", function() {
                    makeComponent({
                        boxLabel: 'Foo',
                        liquidLayout: false // Use false so layouts run
                    });
                    var count = component.componentLayoutCounter;

                    component.setBoxLabel('');
                    expect(component.getWidth()).toBe(boxOnlyWidth);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                });

                it("should change the label when passing an empty string", function() {
                    makeComponent({
                        boxLabel: 'Foo',
                        liquidLayout: false // Use false so layouts run
                    });
                    var count = component.componentLayoutCounter;

                    component.setBoxLabel(label);
                    expect(component.getWidth()).toBe(withLabelWidth);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                });
            });

            describe("with no label configured", function() {
                it("should show the label", function() {
                    makeComponent({
                        liquidLayout: false // Use false so layouts run
                    });
                    var count = component.componentLayoutCounter;

                    component.setBoxLabel(label);
                    expect(component.getWidth()).toBe(withLabelWidth);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                });
            });
        });
    });

    describe("css styling", function() {
        beforeEach(function() {
            makeComponent();
        });

        describe("focused", function() {
            it("should not have focused cls on displayEl when not focused", function() {
                expect(component.displayEl.hasCls('x-form-checkbox-focus')).toBe(false);
            });

            it("should have focused cls on displayEl when focused", function() {
                focusAndWait(component);

                runs(function() {
                    expect(component.displayEl.hasCls('x-form-checkbox-focus')).toBe(true);
                });
            });
        });

        describe("checked", function() {
            it("should not have checked cls on main el when not checked", function() {
                expect(component.el.hasCls('x-form-cb-checked')).toBe(false);
            });

            it("should have checked cls on main el when checked", function() {
                component.setValue(true);
                expect(component.el.hasCls('x-form-cb-checked')).toBe(true);
            });
        });
    });

    // We don't test native input elements' behavior, only the framework code
    // that reacts to it.
    describe("interaction", function() {
        beforeEach(function() {
            makeComponent({
                boxLabel: 'zingbong'
            });
        });

        // Synthetic clicks do not work with checkboxes in IE8/9 :(
        (Ext.isIE9m ? xdescribe : describe)("pointer", function() {
            describe("on inputEl", function() {
                beforeEach(function() {
                    jasmine.fireMouseEvent(component.inputEl, 'click');
                });

                it("should check the box", function() {
                    expect(component.getValue()).toBe(true);
                });

                it("should have checked cls on main el", function() {
                    expect(component.el.hasCls('x-form-cb-checked')).toBe(true);
                });

                describe("second click", function() {
                    beforeEach(function() {
                        jasmine.fireMouseEvent(component.inputEl, 'click');
                    });

                    it("should uncheck the box", function() {
                        expect(component.getValue()).toBe(false);
                    });

                    it("should reset checked cls on main el", function() {
                        expect(component.el.hasCls('x-form-cb-checked')).toBe(false);
                    });
                });
            });

            describe("on boxLabelEl", function() {
                beforeEach(function() {
                    jasmine.fireMouseEvent(component.boxLabelEl, 'click');
                });

                it("should check the box", function() {
                    expect(component.getValue()).toBe(true);
                });

                it("should have checked cls on main el", function() {
                    expect(component.el.hasCls('x-form-cb-checked')).toBe(true);
                });

                describe("second click", function() {
                    beforeEach(function() {
                        jasmine.fireMouseEvent(component.boxLabelEl, 'click');
                    });

                    it("should uncheck the box", function() {
                        expect(component.getValue()).toBe(false);
                    });

                    it("should reset checked cls on main el", function() {
                        expect(component.el.hasCls('x-form-cb-checked')).toBe(false);
                    });
                });
            });
        });

        // Synthetic keyboard events do not cause default action and thus do not fire
        // change event. Maybe some day we will have native event injection...
        xdescribe("keyboard", function() {
            beforeEach(function() {
                focusAndWait(component);
            });

            describe("space key", function() {
                beforeEach(function() {
                    pressKey(component, 'space');
                });

                it("should check the box", function() {
                    expect(component.getValue()).toBe(true);
                });

                it("should have checked cls on main el", function() {
                    expect(component.el.hasCls('x-form-cb-checked')).toBe(true);
                });

                describe("pressed twice", function() {
                    beforeEach(function() {
                        pressKey(component, 'space');
                    });

                    it("should uncheck the box", function() {
                        expect(component.getValue()).toBe(false);
                    });

                    it("should reset checked cls on main el", function() {
                        expect(component.el.hasCls('x-form-cb-checked')).toBe(false);
                    });
                });
            });
        });
    });
});
