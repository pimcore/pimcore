topSuite("Ext.form.FieldContainer", ['Ext.form.field.*', 'Ext.form.Panel'], function() {
    var component,
        makeComponent = function(config) {
            config = config || {};
            component = new Ext.form.FieldContainer(config);
        };

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = null;
    });

    describe("FieldAncestor", function() {
        it("should fire an event whenever validitychange fires on a child item", function() {
            var called;

            makeComponent({
                items: [{
                    xtype: 'textfield',
                    allowBlank: false
                }]
            });
            component.on('fieldvaliditychange', function() {
                called = true;
            });
            component.items.first().setValue('Foo');
            expect(called).toBe(true);
        });

        it("should fire an event whenever errorchange fires on a child item", function() {
            var called;

            makeComponent({
                items: [{
                    xtype: 'textfield',
                    allowBlank: false
                }]
            });
            component.on('fielderrorchange', function() {
                called = true;
            });
            component.items.first().markInvalid('Foo');
            expect(called).toBe(true);
        });
    });

    describe("enable/disable", function() {
        var form;

        beforeEach(function() {
            makeComponent({ items: [{ xtype: 'textfield' }] });

            form = new Ext.form.Panel({
                renderTo: document.body,
                width: 100,
                items: [component]
            });
        });

        afterEach(function() {
            form.destroy();
        });

        it("should be disabled when disabling the form panel", function() {
            form.disable();

            expect(component.isDisabled()).toBe(true);
        });

        it("should be enabled when enabling the form panel", function() {
            form.disable();
            form.enable();

            expect(component.isDisabled()).toBe(false);
        });
    });

    describe("label", function() {
        it("should not hide child labels when the field container label is not visible", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                items: [{
                    xtype: 'textfield',
                    fieldLabel: 'SomeLabel'
                }]
            });
            expect(component.items.first().labelEl.isVisible()).toBe(true);
        });
    });

    describe("using box layout", function() {
        it("should add its layout's targetCls to its containerEl", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                layout: 'hbox',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: 'SomeLabel'
                }]
            });
            expect(component.containerEl.hasCls(component.layout.targetCls)).toBe(true);
        });

        it("should wrap it's items width", function() {
            var panel, textfield, displayfield;

            makeComponent({
                renderTo: null,
                layout: 'hbox',
                items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Email'
                }, {
                    xtype: 'displayfield',
                    value: 'foo'
                }]
            });

            panel = Ext.widget('panel', {
                renderTo: document.body,
                layout: 'vbox',
                items: [component]
            });

            textfield = panel.down('textfield');
            displayfield = panel.down('displayfield');

            expect(component.getWidth()).toBe(textfield.getWidth() + displayfield.getWidth());
            expect(component.getWidth()).toBeGreaterThan(0);
            panel.destroy();
        });
    });

    describe('combineLabels', function() {
        it("should combine the labels of its sub-fields", function() {
            makeComponent({
                defaultType: 'textfield',
                combineLabels: true,
                items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
            });
            expect(component.getFieldLabel()).toEqual('One, Two');
        });

        it("should use the labelConnector to combine the labels", function() {
            makeComponent({
                defaultType: 'textfield',
                combineLabels: true,
                labelConnector: ' - ',
                items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
            });
            expect(component.getFieldLabel()).toEqual('One - Two');
        });

        it("should update the combined label when a field is added to the tree", function() {
            makeComponent({
                defaultType: 'textfield',
                combineLabels: true,
                items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
            });
            component.add({ fieldLabel: 'Three' });
            expect(component.getFieldLabel()).toEqual('One, Two, Three');
        });

        it("should update the combined label when a field is removed from the tree", function() {
            makeComponent({
                defaultType: 'textfield',
                combineLabels: true,
                items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }, { fieldLabel: 'Three' }]
            });
            component.remove(component.items.getAt(1));
            expect(component.getFieldLabel()).toEqual('One, Three');
        });

        it("should use the fieldLabel config rather than combining", function() {
            makeComponent({
                defaultType: 'textfield',
                combineLabels: true,
                fieldLabel: 'Main Label',
                items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
            });
            expect(component.getFieldLabel()).toEqual('Main Label');
        });

        it("should not combine labels if combineLabels is false", function() {
            makeComponent({
                defaultType: 'textfield',
                combineLabels: false,
                items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
            });
            expect(component.getFieldLabel()).toEqual('');
        });
    });

    xdescribe("combineErrors", function() {
        it("should display no error when there are no sub-field errors", function() {
            runs(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    combineErrors: true,
                    defaultType: 'textfield',
                    items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
                });
            });
            waits(20);
            runs(function() {
                expect(component.errorEl.dom).hasHTML('');
            });
        });

        it("should display a combined error when there are sub-field errors", function() {
            runs(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    combineErrors: true,
                    defaultType: 'textfield',
                    items: [{ fieldLabel: 'One', allowBlank: false }, { fieldLabel: 'Two', allowBlank: false }]
                });
                component.items.getAt(0).validate();
                component.items.getAt(1).validate();
            });
            waitsFor(function() {
                return component.getActiveError().length > 0;
            }, 'population of errorEl');
            runs(function() {
                expect(component.getActiveError()).toEqual('<ul><li>One: This field is required</li><li class="last">Two: This field is required</li></ul>');
            });
        });

        it("should remove the combined error when sub-field errors are removed", function() {
            runs(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    combineErrors: true,
                    defaultType: 'textfield',
                    items: [{ fieldLabel: 'One', allowBlank: false }, { fieldLabel: 'Two', allowBlank: false }]
                });
                component.items.getAt(0).validate();
                component.items.getAt(1).validate();
            });
            waitsFor(function() {
                return component.getActiveError().length > 0;
            }, 'population of errorEl');
            runs(function() {
                component.items.getAt(0).setValue('a');
                component.items.getAt(1).setValue('b');
            });
            waitsFor(function() {
                return component.getActiveError().length === 0;
            }, 'clearing of errorEl');
        });

        it("should not combine errors when combineErrors is false", function() {
            runs(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    combineErrors: true,
                    defaultType: 'textfield',
                    items: [{ fieldLabel: 'One' }, { fieldLabel: 'Two' }]
                });
                component.items.getAt(0).validate();
                component.items.getAt(1).validate();
            });
            waits(20);
            runs(function() {
                expect(component.errorEl.dom).hasHTML('');
            });
        });
    });

});
