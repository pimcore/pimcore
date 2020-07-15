topSuite("Ext.form.CheckboxGroup", ['Ext.app.ViewModel'], function() {
    var component;

    function makeComponent(config) {
        config = Ext.apply({
            renderTo: Ext.getBody()
        }, config);
        component = new Ext.form.CheckboxGroup(config);
    }

    afterEach(function() {
        Ext.destroy(component);
        component = null;
    });

    describe("default name", function() {
        it("should assign group name to child items", function() {
            makeComponent({
                name: 'zurg',
                items: [{}, {}]
            });

            expect(component.items.getAt(0)).toHaveAttr('name', 'zurg');
            expect(component.items.getAt(1)).toHaveAttr('name', 'zurg');
        });

        it("should assign its id as group name to child items", function() {
            makeComponent({
                items: [{}, {}]
            });

            expect(component.items.getAt(0)).toHaveAttr('name', component.id);
            expect(component.items.getAt(1)).toHaveAttr('name', component.id);
        });

        it("should not override child name config", function() {
            makeComponent({
                name: 'throbbe',
                items: [{ name: 'gurgle' }, {}]
            });

            expect(component.items.getAt(0)).toHaveAttr('name', 'gurgle');
            expect(component.items.getAt(1)).toHaveAttr('name', 'throbbe');
        });
    });

    describe("initial value", function() {
        it("should set its originalValue to the aggregated value of its sub-checkboxes", function() {
            makeComponent({
                items: [
                    { name: 'one', checked: true },
                    { name: 'two', checked: true, inputValue: 'two-1' },
                    { name: 'two', checked: false, inputValue: 'two-2' },
                    { name: 'two', checked: true, inputValue: 'two-3' }
                ]
            });
            expect(component.originalValue).toEqual({ one: 'on', two: ['two-1', 'two-3'] });
        });

        it("should set the values of its sub-checkboxes if the value config is specified", function() {
            makeComponent({
                items: [
                    { name: 'one', checked: true },
                    { name: 'two', checked: true, inputValue: 'two-1' },
                    { name: 'two', checked: false, inputValue: 'two-2' },
                    { name: 'two', checked: true, inputValue: 'two-3' }
                ],
                value: { two: ['two-1', 'two-2'] }
            });
            expect(component.originalValue).toEqual({ two: ['two-1', 'two-2'] });
            expect(component.items.getAt(0).getValue()).toBe(false);
            expect(component.items.getAt(1).getValue()).toBe(true);
            expect(component.items.getAt(2).getValue()).toBe(true);
            expect(component.items.getAt(3).getValue()).toBe(false);
        });
    });

    describe("sizing", function() {
        it("should respect a configured height", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                height: 100,
                width: 300,
                vertical: true,
                columns: 2,
                scrollable: 'y',
                items: (function() {
                    var checkboxes = [],
                        i;

                    for (i = 0; i < 50; ++i) {
                        checkboxes.push({
                            xtype: 'checkbox'
                        });
                    }

                    return checkboxes;
                })()
            });
            expect(component.getHeight()).toBe(100);
        });
    });

    it("should fire the change event when a sub-checkbox is changed", function() {
        makeComponent({
            items: [{ name: 'foo', checked: true }]
        });
        var spy = jasmine.createSpy();

        component.on('change', spy);

        component.items.getAt(0).setValue(false);
        expect(spy.calls[0].args).toEqual([component, {}, { foo: 'on' }]);

        component.items.getAt(0).setValue(true);
        expect(spy.calls[1].args).toEqual([component, { foo: 'on' }, {}]);
    });

    describe("getValue", function() {
        it("should return an object with keys matching the names of checked items", function() {
            makeComponent({
                items: [{ name: 'one', checked: true }, { name: 'two' }]
            });
            var val = component.getValue();

            expect(val.one).toBeDefined();
            expect(val.two).not.toBeDefined();
        });
        it("should give the inputValue of a single checked item with a given name", function() {
            makeComponent({
                items: [{ name: 'one', checked: true, inputValue: 'foo' }, { name: 'two' }]
            });
            expect(component.getValue().one).toEqual('foo');
        });
        it("should give an array of inputValues of multiple checked items with the same name", function() {
            makeComponent({
                items: [{ name: 'one', checked: true, inputValue: '1' }, { name: 'one', checked: true, inputValue: '2' }, { name: 'one' }]
            });
            expect(component.getValue().one).toEqual(['1', '2']);
        });
    });

    describe("getSubmitData", function() {
        it("should return null", function() {
            makeComponent({
                value: { foo: true },
                items: [{ name: 'foo', inputValue: 'bar' }]
            });
            expect(component.getSubmitData()).toBeNull();
        });
    });

    describe("getModelData", function() {
        it("should return null", function() {
            makeComponent({
                value: { foo: true },
                items: [{ name: 'foo', inputValue: 'bar' }]
            });
            expect(component.getModelData()).toBeNull();
        });
    });

    describe("reset", function() {
        it("should reset each checkbox to its initial checked state", function() {
            makeComponent({
                items: [{ name: 'one', checked: true }, { name: 'two' }, { name: 'three', checked: true }]
            });
            component.setValue({ one: false, two: true });
            component.reset();
            expect(component.items.getAt(0).getValue()).toBe(true);
            expect(component.items.getAt(1).getValue()).toBe(false);
            expect(component.items.getAt(2).getValue()).toBe(true);
        });
    });

    describe("allowBlank = false", function() {
        it("should return a validation error when no sub-checkboxes are checked", function() {
            makeComponent({
                allowBlank: false,
                items: [{ name: 'one' }]
            });
            expect(component.isValid()).toBe(false);
        });

        it("should not return an error when a sub-checkbox is checked", function() {
            makeComponent({
                allowBlank: false,
                items: [{ name: 'one', checked: true }]
            });
            expect(component.isValid()).toBe(true);
        });

        it("should fire the validitychange event with true when checking a box previously undefined", function() {
            makeComponent({
                allowBlank: false,
                items: [{ name: 'one' }]
            });
            var isValid;

            component.on('validitychange', function(field, validState) {
                isValid = validState;
            });
            component.setValue({
                one: true
            });
            expect(isValid).toBe(true);
        });

        it("should fire the validitychange event with true when unchecking a box", function() {
            makeComponent({
                allowBlank: false,
                items: [{ name: 'one', checked: true }]
            });
            var isValid;

            component.on('validitychange', function(field, validState) {
                isValid = validState;
            });
            component.setValue({
                one: false
            });
            expect(isValid).toBe(false);
        });
    });

    describe("setValue", function() {
        describe("with a view model", function() {
            it("should be able to set the value with inline data", function() {
                var vm = new Ext.app.ViewModel({
                    data: {
                        theValue: {
                            foo: true,
                            baz: true
                        }
                    }
                });

                makeComponent({
                    renderTo: Ext.getBody(),
                    items: [{
                        name: 'foo'
                    }, {
                        name: 'bar'
                    }, {
                        name: 'baz'
                    }],
                    viewModel: vm,
                    bind: {
                        value: '{theValue}'
                    }
                });
                vm.notify();
                expect(component.getValue()).toEqual({
                    foo: 'on',
                    baz: 'on'
                });

            });

            it("should be able to set the value with a defined viewmodel", function() {
                Ext.define('spec.Bar', {
                    extend: 'Ext.app.ViewModel',
                    alias: 'viewmodel.bar',
                    data: {
                        theValue: {
                            foo: true,
                            baz: true
                        }
                    }
                });

                makeComponent({
                    renderTo: Ext.getBody(),
                    items: [{
                        name: 'foo'
                    }, {
                        name: 'bar'
                    }, {
                        name: 'baz'
                    }],
                    viewModel: {
                        type: 'bar'
                    },
                    bind: {
                        value: '{theValue}'
                    }
                });

                component.getViewModel().notify();

                expect(component.getValue()).toEqual({
                    foo: 'on',
                    baz: 'on'
                });
                Ext.undefine('spec.Bar');
                Ext.Factory.viewModel.instance.clearCache();
            });
        });
    });

    describe("ARIA", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                items: [{
                    name: 'foo'
                }, {
                    name: 'bar'
                }, {
                    name: 'baz'
                }]
            });
        });

        describe("ariaEl", function() {
            it("should have containerEl as ariaEl", function() {
                expect(component.ariaEl).toBe(component.containerEl);
            });
        });

        describe("attributes", function() {
            it("should have group role", function() {
                expect(component).toHaveAttr('role', 'group');
            });
        });
    });
});
