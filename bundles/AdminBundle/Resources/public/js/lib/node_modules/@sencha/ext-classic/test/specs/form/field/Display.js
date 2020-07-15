topSuite("Ext.form.field.Display", ['Ext.app.ViewController'], function() {
    var component;

    function makeComponent(config) {
        config = Ext.apply({
            name: 'fieldname',
            renderTo: Ext.getBody()
        }, config);

        component = new Ext.form.field.Display(config);
    }

    afterEach(function() {
        component = Ext.destroy(component);
    });

    describe("alternate class name", function() {
        it("should have Ext.form.DisplayField as the alternate class name", function() {
            expect(Ext.form.field.Display.prototype.alternateClassName).toEqual(["Ext.form.DisplayField", "Ext.form.Display"]);
        });

        it("should allow the use of Ext.form.DisplayField", function() {
            expect(Ext.form.DisplayField).toBeDefined();
        });
    });

    it("should be registered as xtype 'displayfield'", function() {
        component = Ext.create("Ext.form.field.Display", { name: 'test' });
        expect(component instanceof Ext.form.field.Display).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("displayfield");
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should have a fieldCls of 'x-form-display-field'", function() {
            expect(component.fieldCls).toEqual('x-form-display-field');
        });

        it("should have htmlEncode set to false", function() {
            expect(component.htmlEncode).toBeFalsy();
        });
    });

    describe("rendering", function() {
        // NOTE this doesn't test the label, error icon, etc. just the parts specific to Display.

        beforeEach(function() {
            makeComponent({ value: 'foo' });
        });

        describe("bodyEl", function() {
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

        describe("inputEl", function() {
            it("should exist", function() {
                expect(component.inputEl).toBeDefined();
            });

            it("should be a child of the bodyEl", function() {
                expect(component.inputEl.dom.parentNode).toBe(component.bodyEl.dom);
            });

            it("should be a div", function() {
                expect(component.inputEl.dom.tagName.toLowerCase()).toEqual('div');
            });

            it("should have the 'fieldCls' config as a class", function() {
                expect(component.inputEl.hasCls(component.fieldCls)).toBe(true);
            });

            it("should have the field value as its innerHTML", function() {
                expect(component.inputEl.dom).hasHTML(component.value);
            });
        });
    });

    describe("validation", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should always return true from the validate method", function() {
            expect(component.validate()).toBe(true);
        });

        it("should always return true from the isValid method", function() {
            expect(component.isValid()).toBe(true);
        });
    });

    describe("value getters", function() {
        describe("getValue", function() {
            it("should return the field's value", function() {
                makeComponent({ value: 'the field value' });
                expect(component.getValue()).toEqual('the field value');
            });

            it("should return the same value when htmlEncode is true", function() {
                makeComponent({ value: '<p>the field value</p>', htmlEncode: true });
                expect(component.getValue()).toEqual('<p>the field value</p>');
            });

            it("should keep an array value", function() {
                var arr = [];

                makeComponent({ value: arr });
                expect(component.getValue()).toBe(arr);
            });

            it("should keep an object value", function() {
                var o = {};

                makeComponent({ value: o });
                expect(component.getValue()).toBe(o);
            });

            it("should keep a numeric value", function() {
                makeComponent({ value: 50 });
                expect(component.getValue()).toBe(50);
            });

            it("should keep a boolean value", function() {
                makeComponent({ value: true });
                expect(component.getValue()).toBe(true);
            });

            it("should keep false", function() {
                makeComponent({ value: false });
                expect(component.getValue()).toBe(false);
            });

            it("should keep 0", function() {
                makeComponent({ value: 0 });
                expect(component.getValue()).toBe(0);
            });
        });
        describe("getRawValue", function() {
            it("should return the field's value", function() {
                makeComponent({ value: 'the field value' });
                expect(component.getRawValue()).toEqual('the field value');
            });

            it("should return the same value when htmlEncode is true", function() {
                makeComponent({ value: '<p>the field value</p>', htmlEncode: true });
                expect(component.getRawValue()).toEqual('<p>the field value</p>');
            });
        });
        describe("getSubmitData", function() {
            it("should return null", function() {
                makeComponent({ value: 'the field value' });
                expect(component.getSubmitData()).toBeNull();
            });
        });
        describe("getModelData", function() {
            it("should return the value", function() {
                makeComponent({ value: 'the field value', name: 'myfield' });
                expect(component.getModelData()).toEqual({ myfield: 'the field value' });
            });
        });
    });

    describe("setting value", function() {
        describe("setRawValue", function() {
            it("should set the inputEl's innerHTML to the specified value", function() {
                makeComponent({ value: 'the field value' });
                component.setRawValue('the new value');
                expect(component.inputEl.dom).hasHTML('the new value');
            });

            it("should not html-encode the value by default", function() {
                makeComponent({ value: 'the field value' });
                component.setRawValue('<p>the new value</p>');
                expect(component.inputEl.dom).hasHTML('<p>the new value</p>');
            });

            it("should html-encode the value when htmlEncode config is true", function() {
                makeComponent({ value: 'the field value', htmlEncode: true });
                component.setRawValue('<p>the new value</p>');
                expect(component.inputEl.dom).hasHTML('&lt;p&gt;the new value&lt;/p&gt;');
            });
        });

        describe("setValue", function() {
            it("should set the inputEl's innerHTML to the specified value", function() {
                makeComponent({ value: 'the field value' });
                component.setValue('the new value');
                expect(component.inputEl.dom).hasHTML('the new value');
            });

            it("should not html-encode the value by default", function() {
                makeComponent({ value: 'the field value' });
                component.setValue('<p>the new value</p>');
                expect(component.inputEl.dom).hasHTML('<p>the new value</p>');
            });

            it("should html-encode the value when htmlEncode config is true", function() {
                makeComponent({ value: 'the field value', htmlEncode: true });
                component.setValue('<p>the new value</p>');
                expect(component.inputEl.dom).hasHTML('&lt;p&gt;the new value&lt;/p&gt;');
            });

            it("should accept 0", function() {
                makeComponent({
                    value: 0
                });
                expect(component.inputEl.dom).hasHTML('0');
            });

            it("should accept false", function() {
                makeComponent({
                    value: false
                });
                expect(component.inputEl.dom).hasHTML('false');
            });

            it("should accept setting an array value", function() {
                makeComponent({
                    value: [1, 2, 3, 4],
                    renderer: function(v) {
                        return v.join(',');
                    }
                });
                expect(component.inputEl.dom).hasHTML('1,2,3,4');
            });

            it("should accept setting an object value", function() {
                makeComponent({
                    value: {
                        foo: true,
                        bar: true,
                        baz: true
                    },
                    renderer: function(v) {
                        return Ext.Object.getKeys(v).join(',');
                    }
                });
                expect(component.inputEl.dom).hasHTML('foo,bar,baz');
            });
        });

    });

    describe("renderer", function() {
        it("should set the innerHTML to the value specified by the renderer", function() {
            makeComponent({
                value: 'foo',
                renderer: function(v) {
                    return v + 'bar';
                }
            });
            expect(component.inputEl.dom).hasHTML('foobar');
        });

        it("should not change the raw value", function() {
            makeComponent({
                value: 'foo',
                renderer: function(v) {
                    return v + 'bar';
                }
            });
            expect(component.rawValue).toBe('foo');
        });

        it("should default the scope to the field", function() {
            var scope;

            makeComponent({
                value: 'foo',
                renderer: function(v) {
                    scope = this;
                }
            });
            expect(scope).toBe(component);
        });

        it("should use the passed scope", function() {
            var o = {},
                scope;

            makeComponent({
                value: 'foo',
                scope: o,
                renderer: function(v) {
                    scope = this;
                }
            });
            expect(scope).toBe(o);
        });

        it("should pass the raw value and the field to the renderer", function() {
            var arg1,
                arg2;

            makeComponent({
                value: 'foo',
                renderer: function(a, b) {
                    arg1 = a;
                    arg2 = b;
                }
            });

            expect(arg1).toBe('foo');
            expect(arg2).toBe(component);
        });

        it("should pass an empty string to the renderer if the value is undefined", function() {
            var arg1;

            makeComponent({
                value: undefined,
                renderer: function(a) {
                    arg1 = a;
                }
            });
            expect(arg1).toBe('');
        });

        it("should be able to resolve to a controller", function() {
            var controller = new Ext.app.ViewController();

            controller.doIt = function() {
                return 'ok';
            };

            var ct = new Ext.container.Container({
                controller: controller,
                renderTo: Ext.getBody(),
                items: {
                    xtype: 'displayfield',
                    renderer: 'doIt'
                }
            });

            expect(ct.items.first().inputEl.dom).hasHTML('ok');
            ct.destroy();
        });
    });

    describe("layout", function() {

        it("should vertically align the value to the top when the height of the field is stretched", function() {
            // https://sencha.jira.com/browse/EXTJS-13818
            makeComponent({
                height: 100,
                fieldLabel: 'foo',
                value: 'bar'
            });

            expect(component.inputEl.getY()).toBe(component.bodyEl.getY() + component.inputEl.getMargin('t'));
        });

        it("should be able to auto height with multi line text", function() {
            makeComponent({
                value: 'foo'
            });
            var height = component.getHeight();

            component.destroy();
            makeComponent({
                value: 'foo<br>bar<br>baz'
            });
            expect(component.getHeight()).toBeGreaterThan(height);
        });
    });

});
