topSuite("Ext.button.Segmented", ['Ext.app.ViewModel'], function() {
    var button;

    function makeButton(cfg) {
        button = Ext.create(Ext.apply({
            xtype: 'segmentedbutton',
            renderTo: document.body
        }, cfg));
    }

    function clickButton(index) {
        jasmine.fireMouseEvent(button.items.getAt(index).el, 'click');
    }

    afterEach(function() {
        button.destroy();
    });

    describe("value", function() {

        // TODO change event

        describe("allowMultiple:false", function() {
            function makeButton(cfg) {
                button = Ext.create(Ext.apply({
                    xtype: 'segmentedbutton',
                    renderTo: document.body,
                    items: [
                        { text: 'Foo', value: 'foo' },
                        { text: 'Bar' }
                    ]
                }, cfg));
            }

            it("should initialize with a null value", function() {
                makeButton();
                expect(button.getValue()).toBeNull();
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
            });

            it("should initialize with a value", function() {
                makeButton({
                    value: 'foo'
                });

                expect(button.getValue()).toBe('foo');
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
            });

            it("should initialize with an index value", function() {
                makeButton({
                    value: 1
                });

                expect(button.getValue()).toBe(1);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
            });

            it("should set a null value", function() {
                makeButton({
                    value: 'foo'
                });

                button.setValue(null);

                expect(button.getValue()).toBe(null);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
            });

            it("should set a value", function() {
                makeButton();

                button.setValue('foo');

                expect(button.getValue()).toBe('foo');
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
            });

            it("should set an index value", function() {
                makeButton();

                button.setValue(1);

                expect(button.getValue()).toBe(1);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
            });

            it("should set the value if a button is initialized with pressed:true", function() {
                makeButton({
                    items: [{
                        text: 'Foo',
                        value: 'foo',
                        pressed: true
                    }]
                });

                expect(button.getValue()).toBe('foo');
                expect(button.items.getAt(0).pressed).toBe(true);
            });

            it("should set the index value if a button with no value is initialized with pressed:true", function() {
                makeButton({
                    items: [{
                        text: 'Foo'
                    }, {
                        text: 'Bar',
                        pressed: true
                    }]
                });

                expect(button.getValue()).toBe(1);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
            });

            it("should set the value when a button is pressed by the user", function() {
                makeButton();

                clickButton(0);

                expect(button.getValue()).toBe('foo');
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);

                clickButton(1);

                expect(button.getValue()).toBe(1);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
            });

            it("should transform an index into a value if button value is available", function() {
                makeButton();

                // button at index 0 has a value of 'foo' so 0 will be transformed to 'foo'
                button.setValue(0);

                expect(button.getValue()).toBe('foo');
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
            });

            it("should throw an error if multiple values are set", function() {
                makeButton();

                expect(function() {
                    button.setValue(['foo', 1]);
                }).toThrow("Cannot set multiple values when allowMultiple is false");
            });

            it("should throw an error if no button value is matched", function() {
                makeButton({
                    id: 'my-button'
                });

                expect(function() {
                    button.setValue('blah');
                }).toThrow("Invalid value 'blah' for segmented button: 'my-button'");
            });

            it("should thow an error if index value is out of bounds", function() {
                makeButton({
                    id: 'my-button'
                });

                expect(function() {
                    button.setValue(2);
                }).toThrow("Invalid value '2' for segmented button: 'my-button'");
            });

            it("should error if multiple items have the same value", function() {
                makeButton({
                    id: 'my-button'
                });

                expect(function() {
                    button.add({
                        text: 'Foo2',
                        value: 'foo'
                    });
                }).toThrow("Segmented button 'my-button' cannot contain multiple items with value: 'foo'");

                Ext.resumeLayouts();
            });

            describe("allowDepress:true", function() {
                it("should set the value to null when a button is depressed", function() {
                    makeButton({
                        allowDepress: true,
                        items: [{
                            text: 'Foo',
                            pressed: true
                        }]
                    });

                    clickButton(0);

                    expect(button.getValue()).toBe(null);
                    expect(button.items.getAt(0).pressed).toBe(false);
                });
            });
        });

        describe("allowMultiple:true", function() {
            function makeButton(cfg) {
                button = Ext.create(Ext.apply({
                    xtype: 'segmentedbutton',
                    allowMultiple: true,
                    renderTo: document.body,
                    items: [
                        { text: 'Seg', value: 'seg' },
                        { text: 'Men' },
                        { text: 'Ted', value: 'ted' }
                    ]
                }, cfg));
            }

            it("should initialize with a null value", function() {
                makeButton();
                expect(button.getValue()).toEqual([]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should initialize with an empty array", function() {
                makeButton({
                    value: []
                });
                expect(button.getValue()).toEqual([]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should initialize with a single value", function() {
                makeButton({
                    value: ['seg']
                });

                expect(button.getValue()).toEqual(['seg']);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should initialize with a single index value", function() {
                makeButton({
                    value: [1]
                });

                expect(button.getValue()).toEqual([1]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should initialize with multiple values", function() {
                makeButton({
                    value: ['seg', 'ted']
                });

                expect(button.getValue()).toEqual(['seg', 'ted']);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(true);
            });

            it("should initialize with multiple index values", function() {
                makeButton({
                    value: [0, 1]
                });

                expect(button.getValue()).toEqual(['seg', 1]);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should set a null value", function() {
                makeButton({
                    value: ['seg', 'ted']
                });

                button.setValue(null);

                expect(button.getValue()).toEqual([]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should set the value to emtpy array", function() {
                makeButton({
                    value: ['seg', 'ted']
                });

                button.setValue([]);

                expect(button.getValue()).toEqual([]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should set a single value", function() {
                makeButton();

                button.setValue(['ted']);
                expect(button.getValue()).toEqual(['ted']);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(true);
            });

            it("should set a single index value", function() {
                makeButton();

                button.setValue([1]);

                expect(button.getValue()).toEqual([1]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should set multiple values", function() {
                makeButton();

                button.setValue(['seg', 'ted']);

                expect(button.getValue()).toEqual(['seg', 'ted']);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(true);
            });

            it("should set multiple index values", function() {
                makeButton();

                button.setValue([1, 2]);

                expect(button.getValue()).toEqual([1, 'ted']);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(true);
            });

            it("should set values for buttons that are initialized with pressed:true", function() {
                makeButton({
                    items: [{
                        text: 'Seg',
                        value: 'seg',
                        pressed: true
                    }, {
                        text: 'Men',
                        pressed: true
                    }, {
                        text: 'Ted',
                        value: 'ted'
                    }]
                });

                expect(button.getValue()).toEqual(['seg', 1]);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should set the value when a button is pressed by the user", function() {
                makeButton();

                clickButton(0);

                expect(button.getValue()).toEqual(['seg']);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);

                clickButton(1);

                expect(button.getValue()).toEqual(['seg', 1]);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(false);

                clickButton(0);

                expect(button.getValue()).toEqual([1]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(false);

                clickButton(1);

                expect(button.getValue()).toEqual([]);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should accept a non-array value", function() {
                makeButton({
                    value: 'seg'
                });

                expect(button.getValue()).toEqual(['seg']);
                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(false);
            });

            it("should accept a non-array index value", function() {
                makeButton({
                    value: 2
                });

                expect(button.getValue()).toEqual(['ted']);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(true);
            });

            it("should throw an error if no button value is matched", function() {
                makeButton({
                    id: 'my-button'
                });

                expect(function() {
                    button.setValue(['seg', 'blah']);
                }).toThrow("Invalid value 'blah' for segmented button: 'my-button'");
            });

            it("should thow an error if an index value is out of bounds", function() {
                makeButton({
                    id: 'my-button'
                });

                expect(function() {
                    button.setValue(['seg', 3, 'ted']);
                }).toThrow("Invalid value '3' for segmented button: 'my-button'");
            });

            it("should not mutate a passed value", function() {
                var arr = ['seg'];

                makeButton({
                    value: arr
                });

                clickButton(2);
                expect(arr).toEqual(['seg']);
            });

            it("should fire a change event", function() {
                var newValues = [],
                    oldValues = [];

                makeButton({
                    listeners: {
                        change: function(b, newValue, oldValue) {
                            // Do not use push because that pushes the value array *contents*, not the array itself.
                            newValues[newValues.length] = newValue;
                            oldValues[oldValues.length] = oldValue;
                        }
                    }
                });

                // Listener will fire when button is created
                oldValues.length = newValues.length = 0;

                button.setValue([1, 2]);

                expect(button.getValue()).toEqual([1, 'ted']);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
                expect(button.items.getAt(2).pressed).toBe(true);

                clickButton(1);

                expect(button.getValue()).toEqual(['ted']);
                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(false);
                expect(button.items.getAt(2).pressed).toBe(true);

                expect(newValues[0]).toEqual([1, 'ted']);
                expect(oldValues[0]).toEqual([]);
                expect(newValues[1]).toEqual(['ted']);
                expect(oldValues[1]).toEqual([1, 'ted']);

            });

            describe('forceSelection', function() {
                it("should initialize with the value of the first button if none configured pressed", function() {
                    makeButton({
                        forceSelection: true
                    });
                    expect(button.getValue()).toEqual(['seg']);
                    expect(button.items.getAt(0).pressed).toBe(true);
                    expect(button.items.getAt(1).pressed).toBe(false);
                    expect(button.items.getAt(2).pressed).toBe(false);

                    // This gesture should be vetoed because of forceSelection: true
                    clickButton(0);
                    expect(button.getValue()).toEqual(['seg']);
                    expect(button.items.getAt(0).pressed).toBe(true);
                    expect(button.items.getAt(1).pressed).toBe(false);
                    expect(button.items.getAt(2).pressed).toBe(false);
                });
            });
        });

        describe("with a viewmodel", function() {
            function makeButton(cfg) {
                button = Ext.create(Ext.apply({
                    xtype: 'segmentedbutton',
                    renderTo: document.body,
                    items: [{
                        text: 'Foo',
                        value: 'foo'
                    }, {
                        text: 'Bar',
                        value: 'bar'
                    }, {
                        text: 'Baz',
                        value: 'baz'
                    }]
                }, cfg));
            }

            it("should have the defaultBindProperty be value", function() {
                makeButton();
                expect(button.defaultBindProperty).toBe('value');
            });

            it("should be able to set an initial value from the view model", function() {
                var vm = new Ext.app.ViewModel({
                    data: {
                        value: 'baz'
                    }
                });

                makeButton({
                    viewModel: vm,
                    bind: '{value}'
                });
                vm.notify();
                expect(button.getValue()).toBe('baz');
            });

            it("should react to view model changes", function() {
                var vm = new Ext.app.ViewModel();

                makeButton({
                    viewModel: vm,
                    bind: '{value}'
                });
                vm.set('value', 'foo');
                vm.notify();
                expect(button.getValue()).toBe('foo');
            });

            it("should update the value in the view model", function() {
                var vm = new Ext.app.ViewModel();

                makeButton({
                    viewModel: vm,
                    bind: '{value}'
                });
                button.setValue('bar');
                expect(vm.get('value')).toBe('bar');
            });
        });
    });

    describe("the toggle event", function() {
        var handler;

        beforeEach(function() {
            handler = jasmine.createSpy();
            makeButton({
                allowMultiple: true,
                items: [
                    { text: 'Seg' },
                    { text: 'Men' },
                    { text: 'Ted', pressed: true }
                ],
                listeners: {
                    toggle: handler
                }
            });
        });

        it("should fire the toggle event when a child button is pressed", function() {
            var item = button.items.getAt(1);

            item.setPressed(true);

            expect(handler.callCount).toBe(1);
            expect(handler.mostRecentCall.args[0]).toBe(button);
            expect(handler.mostRecentCall.args[1]).toBe(item);
            expect(handler.mostRecentCall.args[2]).toBe(true);
        });

        it("should fire the toggle event when a child button is depressed", function() {
            var item = button.items.getAt(2);

            item.setPressed(false);

            expect(handler.callCount).toBe(1);
            expect(handler.mostRecentCall.args[0]).toBe(button);
            expect(handler.mostRecentCall.args[1]).toBe(item);
            expect(handler.mostRecentCall.args[2]).toBe(false);
        });
    });

    describe("allowToggle", function() {
        it("should allow buttons to be toggled when allowToggle is true", function() {
            makeButton({
                items: [
                    { text: 'Seg' },
                    { text: 'Men' },
                    { text: 'Ted' }
                ]
            });

            expect(button.items.getAt(0).enableToggle).toBe(true);
            expect(button.items.getAt(1).enableToggle).toBe(true);
            expect(button.items.getAt(2).enableToggle).toBe(true);

            clickButton(0);

            expect(button.items.getAt(0).pressed).toBe(true);

            clickButton(1);

            expect(button.items.getAt(0).pressed).toBe(false);
            expect(button.items.getAt(1).pressed).toBe(true);
        });

        it("should not allow toggling when allowToggle is false", function() {
            makeButton({
                allowToggle: false,
                items: [
                    { text: 'Seg' },
                    { text: 'Men' },
                    { text: 'Ted' }
                ]
            });

            expect(button.items.getAt(0).enableToggle).toBe(false);
            expect(button.items.getAt(1).enableToggle).toBe(false);
            expect(button.items.getAt(2).enableToggle).toBe(false);

            clickButton(0);

            expect(button.items.getAt(0).pressed).toBe(false);
        });
    });

    describe("allowMultiple", function() {
        describe("when false", function() {
            it("should use a toggleGroup", function() {
                makeButton({
                    items: [
                        { text: 'Seg' },
                        { text: 'Men' },
                        { text: 'Ted' }
                    ]
                });

                expect(button.items.getAt(0).toggleGroup).toBe(button.getId());
                expect(button.items.getAt(1).toggleGroup).toBe(button.getId());
                expect(button.items.getAt(2).toggleGroup).toBe(button.getId());
            });

            it("should not use a toggleGroup when allowToggle is false", function() {
                makeButton({
                    allowToggle: false,
                    items: [
                        { text: 'Seg' },
                        { text: 'Men' },
                        { text: 'Ted' }
                    ]
                });

                expect(button.items.getAt(0).toggleGroup).toBeUndefined();
                expect(button.items.getAt(1).toggleGroup).toBeUndefined();
                expect(button.items.getAt(2).toggleGroup).toBeUndefined();
            });

            it("should only allow one button to be pressed at a time", function() {
                makeButton({
                    items: [
                        { text: 'Seg' },
                        { text: 'Men' }
                    ]
                });

                clickButton(0);

                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);

                clickButton(1);

                expect(button.items.getAt(0).pressed).toBe(false);
                expect(button.items.getAt(1).pressed).toBe(true);
            });

            it("should not allow buttons to be depressed", function() {
                makeButton({
                    items: [
                        { text: 'Seg' },
                        { text: 'Men' }
                    ]
                });

                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
            });
        });

        describe("when true", function() {
            beforeEach(function() {
                makeButton({
                    allowMultiple: true,
                    items: [
                        { text: 'Seg' },
                        { text: 'Men' }
                    ]
                });
            });

            it("should not use a toggleGroup", function() {
                expect(button.items.getAt(0).toggleGroup).toBeUndefined();
                expect(button.items.getAt(1).toggleGroup).toBeUndefined();
            });

            it("should allow multiple buttons to be pressed", function() {
                clickButton(0);

                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(false);

                clickButton(1);

                expect(button.items.getAt(0).pressed).toBe(true);
                expect(button.items.getAt(1).pressed).toBe(true);
            });

            it("should allow buttons to be depressed", function() {
                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(false);
            });
        });
    });

    describe("allowDepress", function() {
        function makeButton(cfg) {
            button = Ext.create(Ext.apply({
                xtype: 'segmentedbutton',
                renderTo: document.body,
                items: [
                    { text: 'Seg' },
                    { text: 'Men' },
                    { text: 'Ted' }
                ]
            }, cfg));
        }

        describe("when true", function() {
            it("should allow buttons to be depressed", function() {
                makeButton({
                    allowDepress: true
                });

                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(false);
            });
        });

        describe("when false", function() {
            it("should not allow buttons to be depressed", function() {
                makeButton();

                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
            });

            it("should have no effect when allowMultiple is true", function() {
                makeButton({
                    allowMultiple: true,
                    allowDepress: false
                });

                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(true);
                clickButton(0);
                expect(button.items.getAt(0).pressed).toBe(false);
            });
        });
    });

    describe("disable/enable", function() {
        it("should disable the child buttons when disable() is called", function() {
            makeButton({
                items: [
                    { text: 'foo' },
                    { text: 'bar' }
                ]
            });

            expect(button.items.getAt(0).disabled).toBe(false);
            expect(button.items.getAt(1).disabled).toBe(false);

            button.disable();

            expect(button.items.getAt(0).disabled).toBe(true);
            expect(button.items.getAt(1).disabled).toBe(true);
        });

        it("should enable the child buttons when enable() is called", function() {
            makeButton({
                disabled: true,
                items: [
                    { text: 'foo' },
                    { text: 'bar' }
                ]
            });

            expect(button.items.getAt(0).disabled).toBe(true);
            expect(button.items.getAt(1).disabled).toBe(true);

            button.enable();

            expect(button.items.getAt(0).disabled).toBe(false);
            expect(button.items.getAt(1).disabled).toBe(false);
        });

        it("should not mask the element when disabled", function() {
            makeButton();
            expect(button.maskOnDisable).toBe(false);
        });
    });

    describe("defaultUI", function() {
        it("should default to 'default'", function() {
            makeButton({
                items: [{
                    text: 'Foo'
                }]
            });
            expect(button.getDefaultUI()).toBe('default');
            expect(button.items.getAt(0).ui).toBe('default-small');
        });

        it("should allow buttons to configure their own UI", function() {
            makeButton({
                items: [{
                    text: 'Foo',
                    ui: 'bar'
                }]
            });
            expect(button.getDefaultUI()).toBe('default');
            expect(button.items.getAt(0).ui).toBe('bar-small');
        });

        it("should use the defaultUI as the UI of the items", function() {
            makeButton({
                defaultUI: 'bob',
                items: [{
                    text: 'Foo'
                }]
            });
            expect(button.items.getAt(0).ui).toBe('bob-small');
        });

        it("should not use the defaultUI for items that have a ui on the item instance", function() {
            makeButton({
                defaultUI: 'bob',
                items: [{
                    text: 'Foo',
                    ui: 'hooray'
                }]
            });
            expect(button.items.getAt(0).ui).toBe('hooray-small');
        });

        it("should not use the defaultUI for items that have a ui on the item class", function() {
            Ext.define('spec.Btn', {
                extend: 'Ext.button.Button',
                ui: 'baz'
            });

            makeButton({
                defaultUI: 'bob',
                items: [{
                    xclass: 'spec.Btn',
                    text: 'Foo'
                }]
            });

            expect(button.items.getAt(0).ui).toBe('baz-small');

            Ext.undefine('spec.Btn');
        });

        it("should not use the defaultUI for items that have a ui of 'default' on the item instance", function() {
            makeButton({
                defaultUI: 'bob',
                items: [{
                    text: 'Foo',
                    ui: 'default'
                }]
            });
            expect(button.items.getAt(0).ui).toBe('default-small');
        });
    });

    describe("item classes", function() {
        var firstCls = 'x-segmented-button-first',
            middleCls = 'x-segmented-button-middle',
            lastCls = 'x-segmented-button-last';

        // expects all of the items to have correct classes
        function expectClasses(items) {
            var itemCount, el;

            items = items || button.items.items;
            itemCount = items.length;

            if (itemCount === 1) {
                el = items[0].getEl();
                expect(el.hasCls(firstCls)).toBe(false);
                expect(el.hasCls(middleCls)).toBe(false);
                expect(el.hasCls(lastCls)).toBe(false);
            }
            else {
                Ext.each(items, function(item, index) {
                    el = item.getEl();

                    if (index === 0) {
                        expect(el.hasCls(firstCls)).toBe(true);
                        expect(el.hasCls(middleCls)).toBe(false);
                        expect(el.hasCls(lastCls)).toBe(false);
                    }
                    else if (index === itemCount - 1) {
                        expect(el.hasCls(firstCls)).toBe(false);
                        expect(el.hasCls(middleCls)).toBe(false);
                        expect(el.hasCls(lastCls)).toBe(true);
                    }
                    else {
                        expect(el.hasCls(firstCls)).toBe(false);
                        expect(el.hasCls(middleCls)).toBe(true);
                        expect(el.hasCls(lastCls)).toBe(false);
                    }
                });
            }
        }

        it("should have the correct classes when there is only one item", function() {
            makeButton({
                items: [
                    { text: 'Seg' }
                ]
            });

            expectClasses();
        });

        it("should have the correct classes when there are two items", function() {
            makeButton({
                items: [
                    { text: 'Seg' },
                    { text: 'Men' }
                ]
            });

            expectClasses();
        });

        it("should have the correct classes when there are three items", function() {
            makeButton({
                items: [
                    { text: 'Seg' },
                    { text: 'Men' },
                    { text: 'Ted' }
                ]
            });

            expectClasses();
        });

        it("should have the correct classes when there are four items", function() {
            makeButton({
                items: [
                    { text: 'Seg' },
                    { text: 'Men' },
                    { text: 'Ted' },
                    { text: 'Btn' }
                ]
            });

            expectClasses();
        });

        it("should have the correct classes when items are added or removed", function() {
            makeButton({
                items: [
                    { text: 'Seg' }
                ]
            });

            // add button at the end
            button.add({ text: 'Men' });
            expectClasses();

            // insert button before first
            button.insert(0, { text: 'Ted' });
            expectClasses();

            // insert button in middle
            button.insert(1, { text: 'Btn' });
            expectClasses();

            // remove button from middle
            button.remove(2);
            expectClasses();

            // remove button from end
            button.remove(2);
            expectClasses();

            // remove first button
            button.remove(0);
            expectClasses();
        });

        it("should have the correct classes when items are shown or hidden", function() {
            makeButton({
                items: [
                    { text: 'Seg', hidden: true },
                    { text: 'Men' },
                    { text: 'Ted', hidden: true },
                    { text: 'Btn', hidden: true }
                ]
            });

            var items = button.items;

            items.getAt(3).show();
            expectClasses([
                items.getAt(1),
                items.getAt(3)
            ]);

            items.getAt(0).show();
            expectClasses([
                items.getAt(0),
                items.getAt(1),
                items.getAt(3)
            ]);

            items.getAt(2).show();
            expectClasses([
                items.getAt(0),
                items.getAt(1),
                items.getAt(2),
                items.getAt(3)
            ]);

            items.getAt(1).hide();
            expectClasses([
                items.getAt(0),
                items.getAt(2),
                items.getAt(3)
            ]);

            items.getAt(3).hide();
            expectClasses([
                items.getAt(0),
                items.getAt(2)
            ]);

            items.getAt(0).hide();
            expectClasses([
                items.getAt(2)
            ]);
        });
    });

    describe("layout", function() {
        var dimensions = {
                1: 'width',
                2: 'height',
                3: 'width and height'
            },
            sizeStyle = {
                0: '',
                1: 'width:87px;',
                2: 'height:94px;',
                3: 'width:87px;height:94px;'
            },
            sizeStyleVert = {
                0: '',
                1: 'width:86px;',
                2: 'height:95px;',
                3: 'width:86px;height:95px;'
            },
            sizeStyleFirst = {
                0: '',
                1: 'width:86px;',
                2: 'height:94px;',
                3: 'width:86px;height:94px;'
            },
            sizeStyleFirstVert = {
                0: '',
                1: 'width:86px;',
                2: 'height:94px;',
                3: 'width:86px;height:94px;'
            };

        function makeLayoutSuite(shrinkWrap) {
            function makeButton(cfg) {
                var vertical = cfg.vertical,
                    itemText = '<div style="display:inline-block;background:red;' +
                        (vertical ? sizeStyleVert : sizeStyle)[shrinkWrap] + '">&nbsp</div>',
                    itemTextFirst = '<div style="display:inline-block;background:red;' +
                        (vertical ? sizeStyleFirstVert : sizeStyleFirst)[shrinkWrap] + '">&nbsp</div>';

                button = Ext.create(Ext.apply({
                    xtype: 'segmentedbutton',
                    renderTo: document.body,
                    width: (shrinkWrap & 1) ? null : vertical ? 100 : 300,
                    height: (shrinkWrap & 2) ? null : vertical ? 300 : 100,
                    items: [
                        { text: itemTextFirst },
                        { text: itemText },
                        { text: itemText }
                    ]
                }, cfg));
            }

            describe((shrinkWrap ? ("shrink wrap " + dimensions[shrinkWrap]) : "fixed width and height"), function() {
                it("should layout horizontal", function() {
                    makeButton({});

                    expect(button).toHaveLayout({
                        el: {
                            w: 300,
                            h: 100
                        },
                        items: {
                            0: {
                                el: {
                                    x: 0,
                                    y: 0,
                                    w: 100,
                                    h: 100
                                }
                            },
                            1: {
                                el: {
                                    x: 100,
                                    y: 0,
                                    w: 100,
                                    h: 100
                                }
                            },
                            2: {
                                el: {
                                    x: 200,
                                    y: 0,
                                    w: 100,
                                    h: 100
                                }
                            }
                        }
                    });
                });

                it("should layout vertical", function() {
                    makeButton({
                        vertical: true
                    });

                    var shrinkHeight = (shrinkWrap & 2);

                    // This layout matcher contains ranges for heights and y positions because
                    // fixed height table is subject to rounding errors in non-webkit browsers
                    // when the cells have borders.
                    expect(button).toHaveLayout({
                        el: {
                            w: 100,
                            h: 300
                        },
                        items: {
                            0: {
                                el: {
                                    x: 0,
                                    y: 0,
                                    w: 100,
                                    h: shrinkHeight ? 100 : [100, 104]
                                }
                            },
                            1: {
                                el: {
                                    x: 0,
                                    y: shrinkHeight ? 100 : [100, 103],
                                    w: 100,
                                    h: shrinkHeight ? 100 : [98, 100]
                                }
                            },
                            2: {
                                el: {
                                    x: 0,
                                    y: shrinkHeight ? 200 : [200, 202],
                                    w: 100,
                                    h: shrinkHeight ? 100 : [98, 100]
                                }
                            }
                        }
                    });
                });
            });
        }

        makeLayoutSuite(0); // fixed width and height
        makeLayoutSuite(1); // shrinkWrap width
        makeLayoutSuite(2); // shrinkWrap height
        makeLayoutSuite(3); // shrinkWrap both

        describe("horizontal", function() {
            it("should divide width evenly among non-widthed items", function() {
                makeButton({
                    width: 300,
                    height: 100,
                    items: [
                        { text: 'Seg', width: 50 },
                        { text: 'Men' },
                        { text: 'Ted' }
                    ]
                });

                expect(button).toHaveLayout({
                    el: {
                        w: 300,
                        h: 100
                    },
                    items: {
                        0: {
                            el: {
                                x: 0,
                                y: 0,
                                w: 50,
                                h: 100
                            }
                        },
                        1: {
                            el: {
                                x: 50,
                                y: 0,
                                w: 125,
                                h: 100
                            }
                        },
                        2: {
                            el: {
                                x: 175,
                                y: 0,
                                w: 125,
                                h: 100
                            }
                        }
                    }
                });
            });

            it("should stretch all items to the height of the largest item", function() {
                makeButton({
                    width: 300,
                    items: [
                        { text: 'Seg', height: 100 },
                        { text: 'Men' },
                        { text: 'Ted' }
                    ]
                });

                expect(button).toHaveLayout({
                    el: {
                        w: 300,
                        h: 100
                    },
                    items: {
                        0: {
                            el: {
                                x: 0,
                                y: 0,
                                w: 100,
                                h: 100
                            }
                        },
                        1: {
                            el: {
                                x: 100,
                                y: 0,
                                w: 100,
                                h: 100
                            }
                        },
                        2: {
                            el: {
                                x: 200,
                                y: 0,
                                w: 100,
                                h: 100
                            }
                        }
                    }
                });
            });

            if (!Ext.supports.CSS3BorderRadius) {
                it("should stretch the frameBody when the width of the segmented button is stretched", function() {
                    makeButton({
                        width: 300,
                        items: [
                            { text: 'Foo' },
                            { text: 'Bar' }
                        ]
                    });

                    var btn = button.items.getAt(1);

                    expect(btn.frameBody.getWidth()).toBe(150 - btn.getFrameInfo().right);
                });
            }
        });

        describe("vertical", function() {
            it("should divide height evenly among non-heighted items", function() {
                makeButton({
                    vertical: true,
                    width: 100,
                    height: 300,
                    items: [
                        { text: 'Seg', height: 50 },
                        { text: 'Men' },
                        { text: 'Ted' }
                    ]
                });

                expect(button).toHaveLayout({
                    el: {
                        w: 100,
                        h: 300
                    },
                    items: {
                        0: {
                            el: {
                                x: 0,
                                y: 0,
                                w: 100,
                                h: Ext.isIE8 ? 51 : 50
                            }
                        },
                        1: {
                            el: {
                                x: 0,
                                y: Ext.isIE8 ? 51 : 50,
                                w: 100,
                                h: 125
                            }
                        },
                        2: {
                            el: {
                                x: 0,
                                y: 175,
                                w: 100,
                                h: 125
                            }
                        }
                    }
                });
            });

            it("should stretch all items to the width of the largest item", function() {
                makeButton({
                    vertical: true,
                    items: [
                        { text: 'Seg', width: 100 },
                        { text: 'Men' },
                        { text: 'Ted' }
                    ]
                });

                expect(button).toHaveLayout({
                    el: {
                        w: 100
                    },
                    items: {
                        0: {
                            el: {
                                x: 0,
                                y: 0,
                                w: 100,
                                h: 22
                            }
                        },
                        1: {
                            el: {
                                x: 0,
                                y: 22,
                                w: 100,
                                h: 21
                            }
                        },
                        2: {
                            el: {
                                x: 0,
                                y: 43,
                                w: 100,
                                h: 21
                            }
                        }
                    }
                });
            });
        });
    });
});
