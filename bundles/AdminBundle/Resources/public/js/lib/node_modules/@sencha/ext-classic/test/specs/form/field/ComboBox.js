topSuite("Ext.form.field.ComboBox",
    ['Ext.app.ViewModel', 'Ext.window.Window', 'Ext.form.Panel', 'Ext.grid.Panel',
     'Ext.data.ArrayStore', 'Ext.layout.container.Fit'],
function() {
    var component, store, CBTestModel,
        itNotIE = Ext.isIE ? xit : it,
        itNotIE9m = Ext.isIE9m ? xit : it,
        synchronousLoad = true,
        storeLoad = Ext.data.ProxyStore.prototype.load,
        storeFlushLoad = Ext.data.ProxyStore.prototype.flushLoad,
        loadStore = function() {
            storeLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    // There's no simple way to simulate user typing, so going
    // to reach in too far here to call this method. Not ideal, but
    // the infrastructure to get typing simulation is fairly large
    function doTyping(value, isBackspace, keepPickerVisible) {
        component.inputEl.dom.value = value;
        component.onFieldMutation({
            type: 'change',
            getKey: function() {
                return isBackspace ? Ext.event.Event.DELETE : 0;
            },
            isSpecialKey: function() {
                return !!isBackspace;
            },
            // Need these two properties so that this object quacks
            // in correct ways to onFieldMutation.
            DELETE: Ext.event.Event.DELETE,
            BACKSPACE: Ext.event.Event.BACKSPACE
        });

        if (value) {
            // Query not executed on empty
            component.doQueryTask.cancel();
            component.doRawQuery();

            if (!keepPickerVisible) {
                component.getPicker().hide();
            }
        }
    }

    function makeComponent(config, preventStore) {
        config = config || {};

        if (!config.name) {
            config.name = 'test';
        }

        if (!config.store && !preventStore) {
            config.store = store;
        }

        component = new Ext.form.field.ComboBox(config);
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        CBTestModel = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: [
                { type: 'string', name: 'text' },
                { type: 'string', name: 'val' }
            ]
        });

        Ext.define('spec.MyStore', {
            extend: 'Ext.data.Store',
            alias: 'store.foo',
            proxy: {
                type: 'memory'
            },
            model: CBTestModel,
            data: [
                { id: 1, text: 'text 1', val: 'value 1' },
                { id: 2, text: 'text 2', val: 'value 2' },
                { id: 3, text: 'text 3', val: 'value 3' },
                { id: 4, text: 'text 31', val: 'value 31' },
                { id: 5, text: 'text 32', val: 'value 32' },
                { id: 6, text: 'text 33', val: 'value 33' },
                { id: 7, text: 'text 34', val: 'value 34' },
                { id: 8, text: 'Foo', val: 'foo1' },
                { id: 9, text: 'Foo', val: 'foo2' }
            ]
        });
        store = new spec.MyStore();
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = storeLoad;

        if (component) {
            component.destroy();
        }

        if (store) {
            store.destroy();
        }

        Ext.undefine('spec.MyStore');
        component = store = null;
    });

    function clickListItem(value, theStore) {
        var found;

        theStore = theStore || store;
        theStore.each(function(rec) {
            if (rec.get(component.valueField) === value) {
                found = rec;

                return false;
            }
        });

        component.expand();
        jasmine.fireMouseEvent(component.getPicker().getNode(found), 'click');
    }

    describe("checkChangeBuffer", function() {
        function runType(value, isBackspace) {
            runs(function() {
                doTyping(value, isBackspace);
            });
        }

        it("should respect the checkChangeBuffer when typing a value", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                checkChangeBuffer: 1000,
                forceSelection: false
            });
            var spy = jasmine.createSpy('change listener');

            component.on('change', spy);
            runType('t');
            waits(100);
            runType('te');
            waits(100);
            runType('tex');
            waits(100);
            runType('text');
            waits(200);
            runs(function() {
                expect(spy).not.toHaveBeenCalled();
            });
            waitsForSpy(spy);
            runs(function() {
                expect(spy.callCount).toBe(1);
                expect(spy.mostRecentCall.args[1]).toBe('text');
                expect(spy.mostRecentCall.args[2]).toBeNull();
            });
        });

        it("should respect checkChangeBuffer when deleting values", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                checkChangeBuffer: 1000,
                forceSelection: false,
                value: 'text'
            });
            var spy = jasmine.createSpy('change listener');

            component.on('change', spy);
            runType('tex', true);
            waits(100);
            runType('te', true);
            waits(100);
            runType('t');
            waits(100);
            runType('t', true);
            waits(100);
            runType('', true);
            waits(200);
            runs(function() {
                expect(spy).not.toHaveBeenCalled();
            });
            waitsForSpy(spy);
            runs(function() {
                expect(spy.mostRecentCall.args[1]).toBeNull();
                expect(spy.mostRecentCall.args[2]).toBe('text');
            });
        });
    });

    it("should encode the input value in the template", function() {
        makeComponent({
            renderTo: Ext.getBody(),
            value: 'test "  <br/> test'
        });
        expect(component.inputEl.dom.value).toBe('test "  <br/> test');
    });

    describe("store shortcuts", function() {
        describe('with 1-dimensional array', function() {
            it("should set the valueField/displayField on an auto created store", function() {
                component = new Ext.form.field.ComboBox({
                    store: ['Item 1', 'Item 2', 'Item 3']
                });
                expect(component.valueField).toBe('field1');
                expect(component.displayField).toBe('field1');
            });

            it("should set the value & raw value correctly", function() {
                component = new Ext.form.field.ComboBox({
                    store: ['Item 1', 'Item 2', 'Item 3']
                });
                component.setValue('Item 1');
                expect(component.getValue()).toBe('Item 1');
                expect(component.getRawValue()).toBe('Item 1');
            });

            it("should not overwrite a configured displayTpl", function() {
                component = new Ext.form.field.ComboBox({
                    store: ['Item 1', 'Item 2', 'Item 3'],
                    displayTpl: '<tpl for=".">Value is {field1}</tpl>'
                });
                component.setValue('Item 1');
                expect(component.getRawValue()).toBe('Value is Item 1');
            });
        });

        describe('with 2-dimensional array', function() {
            it("should set the valueField/displayField on an auto created store", function() {
                component = new Ext.form.field.ComboBox({
                    store: [[1, 'Item 1'], [2, 'Item 2'], [3, 'Item 3']]
                });
                expect(component.valueField).toBe('field1');
                expect(component.displayField).toBe('field2');
            });

            it("should set the value & raw value correctly", function() {
                component = new Ext.form.field.ComboBox({
                    store: [[1, 'Item 1'], [2, 'Item 2'], [3, 'Item 3']]
                });
                component.setValue(2);
                expect(component.getValue()).toBe(2);
                expect(component.getRawValue()).toBe('Item 2');
            });

            it("should not overwrite a configured displayTpl", function() {
                component = new Ext.form.field.ComboBox({
                    store: [[1, 'Item 1'], [2, 'Item 2'], [3, 'Item 3']],
                    displayTpl: '<tpl for=".">Value is {field2}</tpl>'
                });
                component.setValue(1);
                expect(component.getRawValue()).toBe('Value is Item 1');
            });
        });
    });

    describe("hiddenName", function() {
        it("should create a hidden element that gets synced with the value", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                value: 'value 1',
                hiddenName: 'foo'
            });

            var el = component.getEl().down('[name=foo]', true);

            expect(el.type.toLowerCase()).toBe('hidden');
            expect(el.value).toBe('value 1');

            component.setValue('value 34');
            expect(el.value).toBe('value 34');
        });
    });

    describe("defaults", function() {
        describe("normal", function() {
            beforeEach(function() {
                makeComponent();
            });

            it("should have triggerCls = 'x-form-arrow-trigger'", function() {
                expect(component.triggerCls).toEqual('x-form-arrow-trigger');
            });
            it("should have multiSelect = false", function() {
                expect(component.multiSelect).toBe(false);
            });
            it("should have delimiter = ', '", function() {
                expect(component.delimiter).toEqual(', ');
            });
            it("should have displayField = 'text'", function() {
                expect(component.displayField).toEqual('text');
            });
            it("should have valueField = displayField", function() {
                expect(component.valueField).toEqual('text');
            });
            it("should have triggerAction = 'all'", function() {
                expect(component.triggerAction).toEqual('all');
            });
            it("should have allQuery = ''", function() {
                expect(component.allQuery).toEqual('');
            });
            it("should have queryParam = 'query'", function() {
                expect(component.queryParam).toEqual('query');
            });
            it("should have queryMode = 'remote'", function() {
                expect(component.queryMode).toEqual('remote');
            });
            it("should have queryDelay = 500", function() {
                expect(component.queryDelay).toEqual(500);
            });
            it("should have minChars = 4", function() {
                expect(component.minChars).toEqual(4);
            });
            it("should have autoSelect = true", function() {
                expect(component.autoSelect).toBe(true);
            });
            it("should have typeAhead = false", function() {
                expect(component.typeAhead).toBe(false);
            });
            it("should have typeAheadDelay = 250", function() {
                expect(component.typeAheadDelay).toEqual(250);
            });
            it("should have forceSelection = false", function() {
                expect(component.forceSelection).toBe(false);
            });
            it("should have listConfig = undefined", function() {
                expect(component.listConfig).not.toBeDefined();
            });

            describe("rendered", function() {
                beforeEach(function() {
                    component.render(Ext.getBody());
                });

                it("should have combobox role", function() {
                    expect(component).toHaveAttr('role', 'combobox');
                });

                it("should have aria-autocomplete", function() {
                    expect(component).toHaveAttr('aria-autocomplete', 'list');
                });

                it("should have aria-owns", function() {
                    var id = component.id;

                    expect(component).toHaveAttr('aria-owns', id + '-inputEl ' + id + '-picker-listEl');
                });
            });
        });

        describe("with queryMode = 'local'", function() {
            beforeEach(function() {
                makeComponent({
                    queryMode: 'local'
                });
            });
            it("should have queryDelay = 10", function() {
                expect(component.queryDelay).toEqual(10);
            });
            it("should have minChars = 0", function() {
                expect(component.minChars).toEqual(0);
            });
        });
    });

    describe("emptyText", function() {
        if (!Ext.supports.Placeholder) {
            it("should be able to set a value equal to emptyText", function() {
                makeComponent({
                    valueField: 'val',
                    displayField: 'text',
                    emptyText: 'text 1',
                    renderTo: document.body
                });
                component.setValue('value 1');
                component.focus();
                jasmine.waitForFocus(component);
                runs(function() {
                    expect(component.inputEl.dom.value).toBe('text 1');
                    expect(component.getRawValue()).toBe('text 1');
                });
            });
        }
    });

    describe("value initialization", function() {
        describe("without a value", function() {
            it("should have value = null by default", function() {
                makeComponent();
                expect(component.value).toBeNull();
            });

            it("should return null when calling getValue()", function() {
                makeComponent();
                expect(component.value).toBeNull();
            });
        });
    });

    describe("getSubmitValue", function() {
        it("should get the underlying field value", function() {
            makeComponent({
                queryMode: 'local',
                value: 'val 2'
            });
            expect(component.getSubmitValue()).toBe('val 2');
        });

        it("should return an empty string if the value is null", function() {
                makeComponent({
                queryMode: 'local'
            });
            expect(component.getSubmitValue()).toBe('');
        });
    });

    describe("getModelData", function() {
        it("should get the underlying field value", function() {
            makeComponent({
                queryMode: 'local',
                name: 'comboName',
                value: 'val 2'
            });
            expect(component.getModelData()).toEqual({ comboName: 'val 2' });
        });
    });

    describe("onExpand", function() {
        var getInnerTpl = function() {
            return 'foo';
        };

        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                displayField: 'val',
                listConfig: {
                    width: 234,
                    maxHeight: 345,
                    loadingText: 'gazingazang',
                    emptyText: 'buffoopaloo',
                    getInnerTpl: getInnerTpl
                },
                matchFieldWidth: false,
                value: 'value 2'
            });
            component.expand();
        });

        it("should create a Ext.view.BoundList as the picker", function() {
            expect(component.picker).toBeDefined();
            expect(component.picker instanceof Ext.view.BoundList).toBe(true);
        });
        it("should pass the configured store to the BoundList", function() {
            expect(component.picker.store).toBe(component.store);
        });
        it("should pass the configured displayField to the BoundList", function() {
            expect(component.picker.displayField).toEqual(component.displayField);
        });
        it("should pass the configured listConfig.width to the BoundList", function() {
            expect(component.picker.width).toEqual(234);
        });
        it("should pass the configured listConfig.maxHeight to the BoundList", function() {
            expect(component.picker.maxHeight).toEqual(345);
        });
        it("should pass the configured listConfig.loadingText to the BoundList", function() {
            expect(component.picker.loadingText).toEqual('gazingazang');
        });
        it("should pass the configured listConfig.emptyText to the BoundList", function() {
            expect(component.picker.emptyText).toEqual('buffoopaloo');
        });
        it("should pass a configured listConfig.getInnerTpl method to the BoundList config", function() {
            expect(component.picker.getInnerTpl).toBe(getInnerTpl);
        });
        it("should set the BoundList's selection to match the current value", function() {
            expect(component.picker.selModel.getSelection().length).toEqual(1);
            expect(component.picker.selModel.getSelection()[0].get('val')).toEqual(component.value);
        });
        it("should initialize a BoundListKeyNav on the BoundList", function() {
            expect(component.keyMap).toBeDefined();
            expect(component.getPicker().getNavigationModel() instanceof Ext.view.BoundListKeyNav).toBe(true);
        });
        it("should enable the BoundListKeyNav", function() {
            waitsFor(function() {
                return component.getPicker().getNavigationModel().disabled === false;
            });
        });

        it("should set aria-activedescendant", function() {
            var node = component.picker.highlightedItem;

            expect(component).toHaveAttr('aria-activedescendant', node.id);
        });
    });

    describe("onCollapse", function() {
        it("should disable the BoundListKeyNav", function() {
            runs(function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                component.expand();
            });
            waitsFor(function() {
                return component.getPicker().getNavigationModel().disabled === false;
            });
            runs(function() {
                component.collapse();
                expect(component.getPicker().getNavigationModel().disabled).toBe(true);
            });
        });
    });

    describe("setting value", function() {
        describe("value config", function() {
            it("should accept a single string", function() {
                makeComponent({
                    value: 'value 2',
                    valueField: 'val'
                });
                expect(component.value).toEqual('value 2');
            });
            it("should accept an array of string values", function() {
                makeComponent({
                    multiSelect: true,
                    value: ['value 3', 'not in store'],
                    valueField: 'val'
                });
                expect(component.value).toEqual(['value 3', 'not in store']);
            });
            it("should accept a single Ext.data.Model", function() {
                makeComponent({
                    value: store.getAt(0),
                    valueField: 'val'
                });
                expect(component.value).toEqual('value 1');
            });
            it("should accept an array of Ext.data.Model objects", function() {
                makeComponent({
                    multiSelect: true,
                    value: [store.getAt(0), store.getAt(2)],
                    valueField: 'val'
                });
                expect(component.value).toEqual(['value 1', 'value 3']);
            });
            it("should display the values separated by the configured delimiter", function() {
                makeComponent({
                    multiSelect: true,
                    value: ['value 1', 'value 2'],
                    valueField: 'val',
                    renderTo: Ext.getBody(),
                    delimiter: '|'
                });
                expect(component.inputEl.dom.value).toEqual('text 1|text 2');
            });

            it('should accept an object with display and value fields', function() {
                // See https://sencha.jira.com/browse/EXTJS-24354
                makeComponent({
                    value: { val: 'foo', disp: 'bar' },
                    valueField: 'val',
                    displayField: 'disp',
                    renderTo: Ext.getBody()
                });

                expect(component.inputEl.dom.value).toEqual('bar');
                expect(component.getRawValue()).toEqual('bar');
                expect(component.getValue()).toEqual('foo');

                component.setValue({ val: 'bar', disp: 'foo' });
                expect(component.inputEl.dom.value).toEqual('foo');
                expect(component.getRawValue()).toEqual('foo');
                expect(component.getValue()).toEqual('bar');
            });
        });

        describe("setValue method", function() {
            it("should return the combo", function() {
                makeComponent({
                    valueField: 'val'
                });
                expect(component.setValue('value 2')).toBe(component);
            });

            it("should accept a single string", function() {
                makeComponent({
                    valueField: 'val'
                });
                component.setValue('value 2');
                expect(component.value).toEqual('value 2');
            });
            it("should accept an array of string values", function() {
                makeComponent({
                    multiSelect: true,
                    valueField: 'val'
                });
                component.setValue(['value 3', 'not in store']);
                expect(component.value).toEqual(['value 3', 'not in store']);
            });
            it("should accept a single Ext.data.Model", function() {
                makeComponent({
                    valueField: 'val'
                });
                component.setValue(store.getAt(0));
                expect(component.value).toEqual('value 1');
            });
            it("should accept an array of Ext.data.Model objects", function() {
                makeComponent({
                    multiSelect: true,
                    valueField: 'val'
                });
                component.setValue([store.getAt(0), store.getAt(2)]);
                expect(component.value).toEqual(['value 1', 'value 3']);
            });
            it("should only display the first value if not multiSelect", function() {
                makeComponent({
                    valueField: 'val',
                    renderTo: Ext.getBody(),
                    delimiter: '|'
                });
                component.setValue(['value 1', 'value 2']);
                expect(component.inputEl.dom.value).toEqual('text 1');
            });
            it("should display the values separated by the configured delimiter if multiSelect", function() {
                makeComponent({
                    valueField: 'val',
                    multiSelect: true,
                    renderTo: Ext.getBody(),
                    delimiter: '|'
                });
                component.setValue(['value 1', 'value 2']);
                expect(component.inputEl.dom.value).toEqual('text 1|text 2');
            });
            it("should display the valueNotFoundText for values not in the store if multiSelect", function() {
                makeComponent({
                    valueField: 'val',
                    forceSelection: true,
                    multiSelect: true,
                    valueNotFoundText: 'oops!',
                    renderTo: Ext.getBody()
                });
                component.setValue(['value 1', 'value not in store']);
                expect(component.inputEl.dom.value).toEqual('text 1, oops!');
            });
            it("should not display the valueNotFoundText for values not in the store if not multiSelect", function() {
                makeComponent({
                    valueField: 'val',
                    forceSelection: true,
                    valueNotFoundText: 'oops!',
                    renderTo: Ext.getBody()
                });
                component.setValue(['value 1', 'value not in store']);
                expect(component.inputEl.dom.value).toEqual('text 1');
            });
            it("should display the valueNotFoundText when setting as a single value with a custom displayField", function() {
                makeComponent({
                    valueField: 'foo',
                    forceSelection: true,
                    valueNotFoundText: 'oops!',
                    displayField: 'display',
                    renderTo: Ext.getBody()
                });
                component.setValue(1234);
                expect(component.inputEl.dom.value).toEqual('oops!');
            });
            it("should update the expanded dropdown's selection - single select", function() {
                makeComponent({
                    valueField: 'val',
                    renderTo: Ext.getBody()
                });
                component.expand();

                waits(1);
                runs(function() {
                    component.setValue('value 2');
                    expect(component.picker.getSelectionModel().getSelection()).toEqual([store.getAt(1)]);
                });

            });
            it("should update the expanded dropdown's selection - multi select", function() {
                makeComponent({
                    valueField: 'val',
                    renderTo: Ext.getBody(),
                    multiSelect: true
                });
                component.expand();
                waits(1);
                runs(function() {
                    component.setValue(['value 1', 'value 3']);
                    expect(component.picker.getSelectionModel().getSelection()).toEqual([store.getAt(0), store.getAt(2)]);
                });

            });

            describe('change event', function() {
                it("should not fire the change event when the value stays the same - single value", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        valueField: 'val',
                        value: 'value1',
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue('value1');
                    expect(spy).not.toHaveBeenCalled();
                });
                it("should fire the change event when the value changes - single value", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        valueField: 'val',
                        value: 'value1',
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue('value2');
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.args[0]).toBe(component);
                    expect(spy.mostRecentCall.args[1]).toEqual('value2');
                    expect(spy.mostRecentCall.args[2]).toEqual('value1');
                });
                it("should not fire the change event when the value stays the same - multiple values", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        multiSelect: true,
                        valueField: 'val',
                        value: ['value1', 'value2'],
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue(['value1', 'value2']);
                    expect(spy).not.toHaveBeenCalled();
                });
                it("should fire the change event when the value changes - multiple values", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        multiSelect: true,
                        valueField: 'val',
                        value: ['value1', 'value2'],
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    component.setValue(['value1', 'value3']);
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.args[0]).toBe(component);
                    expect(spy.mostRecentCall.args[1]).toEqual(['value1', 'value3']);
                    expect(spy.mostRecentCall.args[2]).toEqual(['value1', 'value2']);
                });
                it("should fire the change event when the value changes back to what the last *remotely* queried value was", function() {
                    var remoteStore = new Ext.data.Store({
                        fields: ['abbr', 'name'],
                            proxy: {
                                type: 'memory',
                                data: [{
                                    "abbr": "AL",
                                    "name": "Alabama"
                                }, {
                                    "abbr": "AK",
                                    "name": "Alaska"
                                }, {
                                    "abbr": "AZ",
                                    "name": "Arizona"
                                }]
                            }
                        }),
                        spy = jasmine.createSpy();

                    makeComponent({
                        displayField: 'name',
                        valueField: 'abbr',
                        minChars: 0,
                        queryMode: 'remote',
                        store: remoteStore,
                        renderTo: Ext.getBody(),
                        listeners: {
                            change: spy
                        }
                    });
                    doTyping('a');
                    waitsForSpy(spy, 'first change event');
                    runs(function() {
                        spy.reset();
                        doTyping('', true);
                    });
                    waitsForSpy(spy, 'second change event');
                    runs(function() {
                        spy.reset();
                        doTyping('a');
                    });
                    waitsForSpy(spy, 'third change event');
                });
            });
        });
    });

    describe('getting value', function() {
        beforeEach(function() {
            makeComponent({
                valueField: 'val',
                renderTo: Ext.getBody()
            });
        });

        it("should return the raw text field value if no selection has been made", function() {
            component.inputEl.dom.value = 'not-in-store';
            expect(component.getValue()).toEqual('not-in-store');
        });

        it("should return the valueField for an item selected from the list", function() {
            component.inputEl.dom.value = 'not-in-store';
            component.expand();
            waits(1);
            runs(function() {
                component.picker.getSelectionModel().select([store.findRecord('text', 'text 2')]);
                expect(component.getValue()).toEqual('value 2');
            });
        });

        it("should return the raw text field value if it is changed after selection", function() {
            component.inputEl.dom.value = 'not-in-store';
            component.expand();
            waits(1);
            runs(function() {
                component.picker.getSelectionModel().select([store.findRecord('text', 'text 2')]);
                component.inputEl.dom.value = 'text 2a';
                expect(component.getValue()).toEqual('text 2a');
            });
        });
    });

    describe("finding records", function() {
        beforeEach(function() {
            makeComponent({
                valueField: 'val',
                displayField: 'text'
            });
        });

        describe("findRecordByValue", function() {
            it("should return the matching record", function() {
                expect(component.findRecordByValue('value 2')).toBe(store.getAt(1));
            });

            it("should return the first matching record", function() {
                var rec = store.insert(0, {
                    val: 'value 2'
                })[0];

                expect(component.findRecordByValue('value 2')).toBe(rec);
            });

            it("should return false if no record is found", function() {
                expect(component.findRecordByValue('bar')).toBe(false);
            });

            describe("store updates", function() {
                it("should react to adds", function() {
                    expect(component.findRecordByValue('bar')).toBe(false);
                    var rec = store.add({
                        val: 'bar'
                    })[0];

                    expect(component.findRecordByValue('bar')).toBe(rec);
                });

                it("should react to removes", function() {
                    expect(component.findRecordByValue('value 1')).toBe(store.getAt(0));
                    store.removeAt(0);
                    expect(component.findRecordByValue('value 1')).toBe(false);
                });

                it("should react to updates", function() {
                    expect(component.findRecordByValue('value 1')).toBe(store.getAt(0));
                    store.getAt(0).set('val', 'bar');
                    expect(component.findRecordByValue('value 1')).toBe(false);
                });
            });
        });

        describe("findRecordByDisplay", function() {
            it("should return the matching record", function() {
                expect(component.findRecordByDisplay('text 2')).toBe(store.getAt(1));
            });

            it("should return the first matching record", function() {
                var rec = store.insert(0, {
                    text: 'text 2'
                })[0];

                expect(component.findRecordByDisplay('text 2')).toBe(rec);
            });

            it("should return false if no record is found", function() {
                expect(component.findRecordByDisplay('bar')).toBe(false);
            });

            describe("store updates", function() {
                it("should react to adds", function() {
                    expect(component.findRecordByDisplay('bar')).toBe(false);
                    var rec = store.add({
                        text: 'bar'
                    })[0];

                    expect(component.findRecordByDisplay('bar')).toBe(rec);
                });

                it("should react to removes", function() {
                    expect(component.findRecordByDisplay('text 1')).toBe(store.getAt(0));
                    store.removeAt(0);
                    expect(component.findRecordByDisplay('text 1')).toBe(false);
                });

                it("should react to updates", function() {
                    expect(component.findRecordByDisplay('text 1')).toBe(store.getAt(0));
                    store.getAt(0).set('text', 'bar');
                    expect(component.findRecordByDisplay('text 1')).toBe(false);
                });
            });
        });
    });

    describe("modifications via the text input", function() {
        it("should be able to requery when typing a value, choosing from a list then retyping the same value", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                valueField: 'val',
                displayField: 'text',
                queryMode: 'local'
            });
            var filters = store.getFilters();

            doTyping('text 12');
            jasmine.fireMouseEvent(component.getTriggers().picker.el, 'click');
            clickListItem('value 1');
            expect(filters.getCount()).toBe(0);
            doTyping('text 12');
            expect(filters.first().getValue()).toBe('text 12');
        });

        describe("with queryMode: local", function() {
            it("should filter the store via the raw value", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    valueField: 'val',
                    displayField: 'text',
                    queryMode: 'local'
                });
                doTyping('text 3');
                var filters = store.getFilters();

                expect(filters.getCount()).toBe(1);
                var filter = filters.getAt(0);

                expect(filter.getProperty()).toBe('text');
                expect(filter.getValue()).toBe('text 3');
                expect(component.getValue()).toBe('text 3');
            });

            it("should clear the value & any filters when all text is removed", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    valueField: 'val',
                    displayField: 'text',
                    queryMode: 'local',
                    value: 'text 3'
                });
                doTyping('', true);
                var filters = store.getFilters();

                expect(filters.getCount()).toBe(0);
                expect(component.getValue()).toBeNull();
            });

            describe("with enableRegex", function() {
                beforeEach(function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        valueField: 'val',
                        displayField: 'text',
                        queryMode: 'local',
                        enableRegEx: true
                    });
                });

                it("should filter using the typed value", function() {
                    doTyping('te.*3');
                    expect(store.getCount()).toBe(5);
                });

                it("should ignore invalid inputs", function() {
                    expect(function() {
                        doTyping('*');
                    }).not.toThrow();
                    expect(store.getFilters().getCount()).toBe(0);
                });
            });

            describe("with stripCharsRe", function() {
                beforeEach(function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        valueField: 'val',
                        displayField: 'text',
                        queryMode: 'local',
                        stripCharsRe: new RegExp('[^0123456789]', 'gi')
                    });
                });

                it("should remove unwanted characters", function() {
                    doTyping('a');
                    waits(100);
                    runs(function() {
                        expect(component.inputEl.dom.value).toBe('');
                    });
                });

                it("should keep lastValue clear if the text was stripped", function() {
                    doTyping('a');
                    waits(100);
                    runs(function() {
                        doTyping('a');
                    });
                    waits(100);
                    runs(function() {
                        expect(component.inputEl.dom.value).toBe('');
                        expect(component.lastValue).toBe('');
                    });
                });
            });
        });

        describe("clearing the value", function() {
            it("should set the value to null", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    valueField: 'val',
                    displayField: 'text',
                    queryMode: 'local',
                    value: 'text 3'
                });
                doTyping('', true);
                expect(component.getValue()).toBeNull();
            });

            it("should be able to select after clearing the value", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    valueField: 'val',
                    displayField: 'text',
                    queryMode: 'local',
                    value: 'text 3'
                });
                doTyping('', true);
                clickListItem('value 2');
                expect(component.getValue()).toBe('value 2');
                expect(component.getRawValue()).toBe('text 2');
            });

            it("should be able to select after clearing a cached value", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    valueField: 'val',
                    displayField: 'text',
                    queryMode: 'local',
                    value: 'value 1'
                });
                doTyping('', true);
                doTyping('text 2');
                doTyping('', true);
                doTyping('text 2');
                clickListItem('value 2');
                expect(component.getValue()).toBe('value 2');
                expect(component.getRawValue()).toBe('text 2');
                expect(component.isExpanded).toBe(false);
            });

            it("should create the correct filter after clearing a cached value with a store filter", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    valueField: 'val',
                    displayField: 'text',
                    queryMode: 'local'
                });
                store.addFilter(new Ext.util.Filter({
                    property: 'val',
                    value: 'foo',
                    anyMatch: true
                }));

                doTyping('f');
                doTyping('', true);
                doTyping('v');
                doTyping('', true);
                doTyping('v');

                expect(component.isExpanded).toBe(false);
                expect(store.getCount()).toBe(0);
            });
        });
    });

    describe("growToLongestValue", function() {
        var bodyEl,
            beforeWidth,
            afterWidth,
            shortText = 'foo',
            longText = 'this text is veeeeeeeeeeeeeeeeeeeeeeeeeeeery long',
            longestText = 'this text is much, much, much, much, much, much, much, much, much much, much, much, much, much, much, much, much too long';

        describe("when true", function() {
            describe("adding a value to store", function() {
                it("should not grow when a longer record is added to store when not set to grow", function() {
                    makeComponent({
                        grow: false,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;

                    beforeWidth = bodyEl.getWidth();
                    store.add({ text: longText, val: 'value 4' });
                    afterWidth = bodyEl.getWidth();

                    expect(beforeWidth).toEqual(afterWidth);
                });

                it("should grow when a longer record is added to store", function() {
                    makeComponent({
                        grow: true,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;

                    beforeWidth = bodyEl.getWidth();
                    store.add({ text: longText, val: 'value 4' });
                    afterWidth = bodyEl.getWidth();

                    expect(afterWidth).toBeGreaterThan(beforeWidth);
                });

                it("should not grow when a shorter record is added to store", function() {
                    makeComponent({
                        grow: true,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    var inputEl = component.inputEl;

                    beforeWidth = inputEl.getWidth();
                    store.add({ text: shortText, val: 'value 4' });
                    afterWidth = inputEl.getWidth();

                    expect(beforeWidth).toEqual(afterWidth);
                });

                it("should grow when growToLongestValue is set", function() {
                    makeComponent({
                        grow: true,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;

                    beforeWidth = bodyEl.getWidth();
                    store.add({ text: longText, val: 'value 4' });
                    afterWidth = bodyEl.getWidth();

                    expect(afterWidth).toBeGreaterThan(beforeWidth);
                });

                it("should not grow when growToLongestValue isn't set", function() {
                    makeComponent({
                        grow: true,
                        growToLongestValue: false,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;

                    beforeWidth = bodyEl.getWidth();
                    store.add({ text: longText, val: 'value 4' });
                    afterWidth = bodyEl.getWidth();

                    expect(beforeWidth).toEqual(afterWidth);
                });

                it("should not grow larger than growMax when growMax is exceeded", function() {
                    makeComponent({
                        grow: true,
                        growMax: 200,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    store.add({ text: longestText, val: 'value 4' });

                    expect(component.bodyEl.getWidth()).toEqual(component.growMax);
                });
            });

            describe('removing store values', function() {
                it('should shrink when largest item is removed', function() {
                    makeComponent({
                        grow: true,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;

                    store.add({ text: longText, val: 'value 4' });
                    beforeWidth = bodyEl.getWidth();

                    store.removeAt(store.getCount() - 1);
                    afterWidth = bodyEl.getWidth();

                    expect(afterWidth).toBeLessThan(beforeWidth);
                });

                it('should not shrink when item other than largest item is removed', function() {
                    makeComponent({
                        grow: true,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;

                    store.add({ text: longText, val: 'value 4' });
                    beforeWidth = bodyEl.getWidth();

                    store.removeAt(0);
                    afterWidth = bodyEl.getWidth();

                    expect(afterWidth).toEqual(beforeWidth);
                });

                it('should not shrink below growMin width', function() {
                    makeComponent({
                        grow: true,
                        growMin: 100,
                        growToLongestValue: true,
                        renderTo: Ext.getBody()
                    });

                    bodyEl = component.bodyEl;
                    store.add({ text: longText, val: 'value 4' });

                    beforeWidth = bodyEl.getWidth();
                    store.removeAll();
                    afterWidth = bodyEl.getWidth();

                    expect(afterWidth).toEqual(component.growMin);
                });
            });
        });

        describe('when false', function() {
            beforeEach(function() {
                Ext.util.CSS.createStyleSheet(
                    // make the input el have a 9px character width
                    '.x-form-text { font:15px monospace;letter-spacing:0px; }',
                    'growStyleSheet'
                );
            });

            afterEach(function() {
                Ext.util.CSS.removeStyleSheet('growStyleSheet');
            });

            it('should start out at growMin', function() {
                makeComponent({
                    renderTo: document.body,
                    grow: true,
                    growToLongestValue: false,
                    growMin: 50
                });

                expect(component.getWidth()).toBe(50);
            });

            it('should initially render at the width of the text', function() {
                makeComponent({
                    renderTo: document.body,
                    value: 'mmmmmmmmmm',
                    grow: true,
                    growToLongestValue: false,
                    growMin: 50
                });

                expect(component.getWidth()).toBe(component.bodyEl.getWidth());
            });

            it('should initially render with a width of growMax if initial text width exceeds growMax', function() {
                makeComponent({
                    renderTo: document.body,
                    value: 'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmm',
                    grow: true,
                    growToLongestValue: false,
                    growMax: 200
                });

                expect(component.getWidth()).toBe(200);
            });

            it('should grow and shrink', function() {
                makeComponent({
                    renderTo: document.body,
                    grow: true,
                    growToLongestValue: false,
                    triggers: {
                        foo: {}
                    },
                    growMin: 100,
                    growMax: 200
                });

                expect(component.getWidth()).toBe(100);

                component.setValue('mmmmmmmmmmmmmm');

                expect(component.getWidth()).toBe(component.bodyEl.getWidth());

                component.setValue('mmmmmmmmmmmmmmmmmmmmmmmmmmmmmm');

                expect(component.getWidth()).toBe(200);

                component.setValue('mmmmmmmmmmmmmm');

                expect(component.getWidth()).toBe(component.bodyEl.getWidth());

                component.setValue('m');

                expect(component.getWidth()).toBe(100);
            });
        });
    });

    describe("doQuery method", function() {
        it("should set the lastQuery property", function() {
            makeComponent();
            component.doQuery('foobar');
            expect(component.lastQuery).toEqual('foobar');
        });

        it("should not clear remote store's filter", function() {
            makeComponent();
            spyOn(component.store, 'clearFilter');
            component.doQuery('foobar');
            expect(component.store.clearFilter).not.toHaveBeenCalled();
        });

        describe("local queryMode", function() {
            it("should auto select if the last query is the same", function() {
                makeComponent({
                    renderTo: document.body,
                    queryMode: 'local',
                    displayField: 'val',
                    lastQuery: 'value 2'
                });

                spyOn(component, 'doAutoSelect');
                component.doQuery('value 2');

                expect(component.doAutoSelect).toHaveBeenCalled();
            });

            it("should filter the store based on the displayField", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });
                var spy = jasmine.createSpy(),
                    store = component.getStore();

                store.on('filterchange', spy);
                component.doQuery('value 2');

                expect(spy.callCount).toBe(1);
                expect(store.getCount()).toBe(1);
                expect(store.getAt(0).get('val')).toBe('value 2');
            });

            it("should not filter the store if forceAll = true", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });
                component.doQuery('value 2', true);
                expect(component.getStore().getCount()).toEqual(1);
            });

            it("should add to existing filters", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });
                store.filter('val', 'value');
                component.doQuery('value 3');
                expect(store.getCount()).toBe(5);
            });

            it("should remove only the filters added by the combo", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });
                store.filter('val', 'value');
                component.doQuery('value 3');
                component.doQuery('');
                expect(store.getCount()).toBe(7);
            });

            it("should clear any active filters on destroy", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });
                store.filter('val', 'value');
                component.doQuery('value 3');
                expect(store.getCount()).toBe(5);
                component.destroy();
                expect(store.getCount()).toBe(7);
            });

            it("should return true if the query was not vetoed", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });

                var ret = component.doQuery('value 2');

                expect(ret).toBe(true);
            });
        });

        describe("remote queryMode", function() {
            it("should call the store's load method", function() {
                makeComponent({
                    queryMode: 'remote',
                    displayField: 'val'
                });
                spyOn(component.store, 'load');
                component.doQuery('foobar');
                expect(component.store.load.callCount).toEqual(1);
                expect(component.store.load.calls[0].args[0].params.query).toEqual('foobar');
            });

            it("should pass the query string using the 'queryParam' as the parameter name", function() {
                makeComponent({
                    queryMode: 'remote',
                    displayField: 'val',
                    queryParam: 'customparam'
                });
                spyOn(component.store, 'load');
                component.doQuery('foobar');
                expect(component.store.load.callCount).toEqual(1);
                expect(component.store.load.calls[0].args[0].params.customparam).toEqual('foobar');
            });

            it("should return true if the query was not vetoed", function() {
                makeComponent({
                    queryMode: 'remote',
                    displayField: 'val'
                });

                var ret = component.doQuery('blerg');

                expect(ret).toBe(true);
            });
        });

        describe("beforequery event", function() {
            it("should fire the 'beforequery' event", function() {
                makeComponent();

                var spy = jasmine.createSpy();

                component.on('beforequery', spy);
                component.doQuery('foobar', true);
                expect(spy).toHaveBeenCalledWith({
                    query: 'foobar',
                    lastQuery: '',
                    forceAll: true,
                    combo: component,
                    cancel: false
                });
                expect(component.lastQuery).toBeDefined();
            });

            it("should not query if a 'beforequery' handler returns false", function() {
                makeComponent();

                component.on('beforequery', function() {
                    return false;
                });
                expect(component.lastQuery).not.toBeDefined();
            });

            it("should not query if a 'beforequery' handler sets the query event object's cancel property to true", function() {
                makeComponent();

                component.on('beforequery', function(qe) {
                    qe.cancel = true;
                });
                expect(component.lastQuery).not.toBeDefined();
            });

            it("should return false when local query was vetoed", function() {
                makeComponent({
                    queryMode: 'local',
                    displayField: 'val'
                });

                component.on('beforequery', function() {
                    return false;
                });

                var ret = component.doQuery('bonzo');

                expect(ret).toBe(false);
            });

            it("should return false when remote query was vetoed", function() {
                makeComponent({
                    queryMode: 'remote',
                    displayField: 'val'
                });

                component.on('beforequery', function() {
                    return false;
                });

                var ret = component.doQuery('throbbe');

                expect(ret).toBe(false);
            });

            it("should start check task if remote query was vetoed", function() {
                makeComponent();

                spyOn(component, 'startCheckChangeTask');

                component.on('beforequery', function() {
                    return false;
                });

                component.doQuery('throbbe', true);

                expect(component.startCheckChangeTask).toHaveBeenCalled();
            });
        });

        describe("minChars config", function() {
            it("should not query if the number of entered chars is less than the minChars config", function() {
                makeComponent({
                    minChars: 100
                });
                component.doQuery('foobar');
                expect(component.lastQuery).not.toBeDefined();
            });
            it("should ignore the minChars if forceAll = true", function() {
                makeComponent({
                    minChars: 100
                });
                component.doQuery('foobar', true);
                expect(component.lastQuery).toBeDefined();
            });
        });

        it("should expand the dropdown", function() {
            makeComponent();
            spyOn(component, 'expand');
            component.doQuery('foobar');
            expect(component.expand).toHaveBeenCalled();
        });
    });

    describe('doAutoSelect method', function() {
        it('should highlight the selected item', function() {
            var node;

            makeComponent({
                queryMode: 'local',
                displayField: 'val',
                renderTo: Ext.getBody()
            });

            component.expand();
            component.setValue('value 32');
            node = component.picker.getNode(component.picker.selModel.lastSelected);

            spyOn(component.picker.getNavigationModel(), 'setPosition').andCallThrough();

            component.doAutoSelect();

            expect(component.picker.getNavigationModel().setPosition).toHaveBeenCalled();
            expect(Ext.fly(node).hasCls('x-boundlist-item-over')).toBe(true);
        });

        it('should scroll the selected item into view', function() {
            makeComponent({
                queryMode: 'local',
                displayField: 'val',
                renderTo: Ext.getBody()
            });

            component.expand();
            spyOn(component.picker.getScrollable(), 'ensureVisible');
            component.setValue('value 32');

            component.doAutoSelect();

            expect(component.picker.getScrollable().ensureVisible).toHaveBeenCalled();
        });

        it("should select first item when autoSelectLast == false", function() {
            makeComponent({
                autoSelectLast: false,
                queryMode: 'local',
                displayField: 'val',
                renderTo: Ext.getBody()
            });

            component.expand();
            component.setValue('value 32');

            spyOn(component.picker.getNavigationModel(), 'setPosition').andCallThrough();

            component.expand();
            component.doAutoSelect();

            expect(component.picker.getNavigationModel().setPosition).toHaveBeenCalled();

            var firstNode = component.picker.getNode(0);

            expect(Ext.fly(firstNode).hasCls('x-boundlist-item-over')).toBe(true);
        });

        it("should autoSelect if you type an blur", function() {
            makeComponent({
                autoSelectLast: false,
                queryMode: 'local',
                value: 'value 32',
                renderTo: Ext.getBody()
            });
            component.expand();
            jasmine.focusAndWait(component);

            runs(function() {
                doTyping('text 34');
            });

            jasmine.blurAndWait(component);

            waitsFor(function() {
                return !component.hasFocus;
            });

            runs(function() {
                component.expand();
                expect(component.picker.getSelectionModel().lastSelected.get('val')).toBe('value 34');
            });
        });

        it("should fire select event only once on selection and blur with forceSelection", function() {
            var spy = jasmine.createSpy(),
                triggerEl,
                x, y;

            makeComponent({
                forceSelection: true,
                renderTo: Ext.getBody(),
                valueField: 'id',
                    displayTpl: '<tpl for=".">Selected: {text} </tpl>',
                    listConfig: {
                        getInnerTpl: function() {
                            return 'Selected: {text}';
                        }
                    },
                listeners: {
                    select: spy
                }
            });

            // Expand and focus
            component.expand();
            jasmine.focusAndWait(component);

            // Tap on first item               
            runs(function() {
                triggerEl = component.getPicker().all.item(0);
                x = triggerEl.getX() + triggerEl.getWidth() / 2;
                y = triggerEl.getY() + triggerEl.getHeight() / 2;

                Ext.testHelper.tap(triggerEl, { x: x, y: y });
            });

            // Waits for blur
            jasmine.blurAndWait(component);

            waitsFor(function() {
                return !component.hasFocus;
            });

            // Check for only one Select event
            runs(function() {
                expect(spy).toHaveBeenCalled();
                expect(spy.callCount).toEqual(1);
            });
        });
    });

    describe("doRawQuery method", function() {
        it("should call the doQuery method with the contents of the field", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            spyOn(component, 'doQuery');
            component.inputEl.dom.value = 'foobar';
            component.doRawQuery();
            expect(component.doQuery).toHaveBeenCalledWith('foobar', false, true);
        });
    });

    describe('trigger click', function() {
        it("should perform an 'all' query with the allQuery config if triggerAction='all'", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                triggerAction: 'all',
                allQuery: 'the-all-query'
            });
            spyOn(component, 'doQuery');
            component.onTriggerClick();
            expect(component.doQuery).toHaveBeenCalledWith('the-all-query', true);
        });

        it("should perform a query with the current field value if triggerAction='query'", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                triggerAction: 'query',
                allQuery: 'the-all-query',
                value: 'value 2',
                valueField: 'val'
            });
            spyOn(component, 'doQuery');
            component.onTriggerClick();
            expect(component.doQuery).toHaveBeenCalledWith('text 2', false, true);
        });

        describe('emptyText list config and no store data', function() {
            var wasCalled = false,
                defaultCfg;

            beforeEach(function() {
                defaultCfg = {
                    queryMode: 'local',
                    store: new Ext.data.Store({
                        proxy: {
                            type: 'memory'
                        },
                        model: CBTestModel,
                        data: []
                    }),
                    renderTo: Ext.getBody()
                };
            });

            afterEach(function() {
                wasCalled = false;
            });

            it('should expand the bound list and display the empty text if configured', function() {
                makeComponent(Ext.apply(defaultCfg, {
                    listConfig: {
                        emptyText: 'derp'
                    }
                }));

                spyOn(component, 'expand').andCallThrough();
                component.onTriggerClick();

                expect(component.expand).toHaveBeenCalled();
                expect(component.picker.emptyText).toBe('derp');
            });

            it('should not expand the bound list and display the empty text if not configured', function() {
                makeComponent(defaultCfg);

                spyOn(component, 'expand');
                component.onTriggerClick();
                expect(component.expand).not.toHaveBeenCalled();
            });

            it('should expand the bound list and fire the `expand` event if configured', function() {
                makeComponent(Ext.apply(defaultCfg, {
                    listConfig: {
                        emptyText: 'derp'
                    },
                    listeners: {
                        expand: function() {
                            wasCalled = true;
                        }
                    }
                }));

                spyOn(component, 'expand').andCallThrough();
                component.onTriggerClick();

                expect(wasCalled).toBe(true);
            });
        });
    });

    describe("keyboard input", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                queryMode: 'local',
                valueField: 'val',
                queryDelay: 1
            });
        });

        it("should initiate a query after the queryDelay", function() {
            runs(function() {
                spyOn(component, 'doQuery');
                component.inputEl.dom.value = 'foob';
                jasmine.fireKeyEvent(component.inputEl.dom, 'keyup', 66);
            });
            waitsFor(function() {
                return component.doQuery.callCount > 0;
            }, 'query not executed');
            runs(function() {
                expect(component.doQuery.mostRecentCall.args).toEqual(['foob', false, true]);
            });
        });
        it("should not respond to special keys", function() {
            component.inputEl.dom.value = 'foob';

            // Wait for async textinput event to fire on platforms where it fires.
            // It's not universally supported so we cannot use waitsFor
            waits(100);

            runs(function() {
                spyOn(component, 'doQuery');
                jasmine.fireKeyEvent(component.inputEl.dom, 'keyup', Ext.event.Event.DOWN);
            });
            waits(10);
            runs(function() {
                expect(component.doQuery).not.toHaveBeenCalled();
            });
        });
        it("should respond to backspace", function() {
            component.inputEl.dom.value = 'foob';

            // Wait for async textinput event to fire on platforms where it fires.
            // It's not universally supported so we cannot use waitsFor
            waits(100);

            runs(function() {
                spyOn(component, 'doQuery');
                jasmine.fireKeyEvent(component.inputEl.dom, 'keyup', Ext.event.Event.BACKSPACE);
            });
            waitsFor(function() {
                return component.doQuery.callCount > 0;
            }, 'query not executed');
        });
        it("should respond to delete", function() {
            component.inputEl.dom.value = 'foob';

            // Wait for async textinput event to fire on platforms where it fires.
            // It's not universally supported so we cannot use waitsFor
            waits(100);

            runs(function() {
                spyOn(component, 'doQuery');
                jasmine.fireKeyEvent(component.inputEl.dom, 'keyup', Ext.event.Event.DELETE);
            });
            waitsFor(function() {
                return component.doQuery.callCount > 0;
            }, 'query not executed');
        });

        // Explicitl blurring doesn't work on IE, so use itNotIE
        itNotIE('should select the value upon tab', function() {
            // FIXME the component.inputEl.dom.focus(); calls should not be necessary
            // Expand the picker
            component.inputEl.dom.focus();
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.DOWN);
            var selModel = component.picker.getSelectionModel(),
                hideSpy;

            // Picker should be visible
            expect(component.picker.isVisible()).toBe(true);
            hideSpy = spyOnEvent(component.picker, 'hide');

            // But with no selection
            expect(selModel.getSelection().length).toBe(0);

            // This should select the first record, and hide the picker
            component.inputEl.dom.focus();
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.TAB);

            // We must wait until after the browser's TAB handling has blurred the field, and therefore hidden the picker
            waitsForSpy(hideSpy, 'hide', 'picker to hide');
            runs(function() {

                // First record should be selected
                expect(selModel.getSelection()[0] === store.getAt(0)).toBe(true);

                // The raw value of the input field should be the display field of the selected record
                expect(component.getRawValue()).toBe(selModel.getSelection()[0].get(component.displayField));
            });
        });

        describe("keyboard interaction", function() {
            var expandSpy, collapseSpy;

            function pressKey(key, options) {
                jasmine.asyncPressKey(component.inputEl, key, options);
            }

            function expectItem(wantText) {
                runs(function() {
                    var navModel = component.picker.getNavigationModel(),
                        rec = navModel.getRecord(),
                        haveText = rec && rec.get('text');

                    expect(haveText).toBe(wantText);
                });
            }

            beforeEach(function() {
                expandSpy = jasmine.createSpy('expand');
                collapseSpy = jasmine.createSpy('collapse');

                component.on({
                    expand: expandSpy,
                    collapse: collapseSpy
                });
            });

            afterEach(function() {
                expandSpy = collapseSpy = null;
            });

            describe("expand", function() {
                it("should expand on down arrow", function() {
                    pressKey('down');

                    waitForSpy(expandSpy, 'expand');

                    runs(function() {
                        expect(component.isExpanded).toBe(true);
                    });
                });

                it("should expand on alt-down arrow", function() {
                    pressKey('down', { alt: true });

                    waitForSpy(expandSpy, 'expand');

                    runs(function() {
                        expect(component.isExpanded).toBe(true);
                    });
                });
            });

            describe("collapse", function() {
                beforeEach(function() {
                    pressKey('down');

                    waitForSpy(expandSpy, 'expand');
                });

                it("should collapse on Esc", function() {
                    pressKey('esc');

                    waitForSpy(collapseSpy, 'collapse');

                    runs(function() {
                        expect(component.isExpanded).toBe(false);
                    });
                });

                it("should collapse on Alt-Up arrow", function() {
                    pressKey('up', { alt: true });

                    waitForSpy(collapseSpy, 'collapse');

                    runs(function() {
                        expect(component.isExpanded).toBe(false);
                    });
                });

                it("should remove aria-activedescendant", function() {
                    pressKey('esc');

                    waitForSpy(collapseSpy, 'collapse');

                    runs(function() {
                        expect(component).not.toHaveAttr('aria-activedescendant');
                    });
                });
            });

            describe("arrow keys", function() {
                describe("down arrow", function() {
                    beforeEach(function() {
                        pressKey('down');

                        waitForSpy(expandSpy, 'expand');
                    });

                    describe("initial", function() {
                        it("should select first item", function() {
                            expectItem('text 1');
                        });

                        it("should set aria-activedescendant to first item", function() {
                            var item = component.picker.getNode(0);

                            // aria-activedescendant is set on the inputEl!
                            expect(component).toHaveAttr('aria-activedescendant', item.id);
                        });
                    });

                    describe("subsequent", function() {
                        beforeEach(function() {
                            pressKey('down');

                            jasmine.waitAWhile();
                        });

                        it("should select 2nd item", function() {
                            expectItem('text 2');
                        });

                        it("should set aria-activedescendant to 2nd item", function() {
                            var item = component.picker.getNode(1);

                            expect(component).toHaveAttr('aria-activedescendant', item.id);
                        });
                    });
                });

                describe("alt-down arrow", function() {
                    beforeEach(function() {
                        pressKey('down', { alt: true });

                        waitForSpy(expandSpy, 'expand', 1000);
                    });

                    describe("initial", function() {
                        it("should not highlight items after expanding", function() {
                            expect(component.picker.highlightedItem).not.toBeDefined();
                        });
                    });

                    describe("subsequent", function() {
                        beforeEach(function() {
                            pressKey('down', { alt: true });

                            waitAWhile();
                        });

                        it("should not highlight items on subsequent alt-down arrow key", function() {
                            expect(component.picker.highlightedItem).not.toBeDefined();
                        });
                    });
                });

                describe("up arrow", function() {
                    beforeEach(function() {
                        pressKey('down');

                        waitForSpy(expandSpy, 'expand', 1000);

                        pressKey('down');
                        pressKey('down');
                        pressKey('down');
                        pressKey('up');
                    });

                    it("should select 3rd item", function() {
                        expectItem('text 3');
                    });

                    it("should set aria-activedescendant to 3rd item", function() {
                        var item = component.picker.getNode(2);

                        expect(component).toHaveAttr('aria-activedescendant', item.id);
                    });
                });

                describe("alt-up arrow", function() {
                    beforeEach(function() {
                        pressKey('down', { alt: true });

                        waitForSpy(expandSpy, 'expand', 1000);
                    });

                    describe("initial", function() {
                        beforeEach(function() {
                            pressKey('up', { alt: true });

                            waitAWhile();
                        });

                        it("should not highlight items", function() {
                            expect(component.picker.highlightedItem).not.toBeDefined();
                        });

                        // This should operate on the closed picker
                        describe("subsequent", function() {
                            beforeEach(function() {
                                pressKey('up', { alt: true });

                                waitAWhile();
                            });

                            it("should not highlight items either", function() {
                                expect(component.picker.highlightedItem).not.toBeDefined();
                            });
                        });
                    });
                });
            });
        });
    });

    describe("keyboard input with multiSelect", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                queryMode: 'local',
                valueField: 'val',
                multiSelect: true
            });
        });

        it('should select the value upon tab with multiSelect', function() {
            var sm,
                selected,
                rawVal = '',
                hideSpy;

            // Expand the picker
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.DOWN);

            // Picker should be visible
            expect(component.picker.isVisible()).toBe(true);
            hideSpy = spyOnEvent(component.picker, 'hide');
            sm = component.picker.selModel;

            // But with no selection
            expect(sm.getSelection().length).toBe(0);

            // This should select the 1st record
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.ENTER);
            selected = sm.getSelection();
            expect(selected.length).toBe(1);
            expect(selected[0] === store.getAt(0)).toBe(true);

            // This should DEselect the 1st record
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.ENTER);

            // No select 2nd and 3rd records
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.DOWN);
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.ENTER);
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.DOWN);
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.ENTER);
            selected = sm.getSelection();
            expect(selected.length).toBe(2);
            expect(selected[0] === store.getAt(1)).toBe(true);
            expect(selected[1] === store.getAt(2)).toBe(true);

            // This should select the 4th record, and hide the picker
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.DOWN);
            jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.TAB);

            // Wait for the browser's TAB handling to complete and the picker to hide
            waitsForSpy(hideSpy, 'picker to hide');
            runs(function() {
                selected = sm.getSelection();

                // 4th record should now be selected
                expect(selected.length).toBe(3);
                expect(selected[2] === store.getAt(3)).toBe(true);

                for (var i = 0, len = selected.length; i < len; i++) {
                    if (i > 0) {
                        rawVal += ', ';
                    }

                    rawVal += selected[i].get(component.displayField);
                }

                // The raw value of the input field should be the display field of the selected record
                expect(component.getRawValue()).toEqual(rawVal);
            });
        });
    });

    describe("forceSelection", function() {
        it('should not clear the raw value', function() {
            store.load();
            makeComponent({
                displayField: 'text',
                valueField: 'val',
                forceSelection: true,
                typeAhead: true,
                queryMode: 'local',
                renderTo: Ext.getBody()
            });

            var typeaheadSpy = spyOn(component, 'onTypeAhead').andCallThrough();

            component.setRawValue('t');
            component.doRawQuery();

            // EXTJS-15501 - It was the typeahead processing that broke it.
            waitsFor(function() {
                return typeaheadSpy.callCount > 0;
            });
            runs(function() {
                expect(component.inputEl.dom.value).toBe('text 1');
            });
        });

        it("should not clear the lastSelectedRecords when calling setValue", function() {
            makeComponent({
                displayField: 'text',
                valueField: 'val',
                forceSelection: true,
                queryMode: 'local',
                renderTo: Ext.getBody()
            });

            component.setValue('value 2');

            component.setValue('value 2');

            expect(component.lastSelectedRecords).not.toBe(null);
        });

        describe("setting value to a value not in the Store with forceSelection: false", function() {
            it("should set passed value", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    forceSelection: false
                });
                component.setValue("NOT IN STORE");
                expect(component.getValue()).toBe('NOT IN STORE');
            });

            it("should not collapse the list if there are items in the store", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    forceSelection: false,
                    queryMode: 'remote'
                });
                component.expand();
                component.setValue('asdf');
                expect(component.getPicker().isVisible()).toBe(true);
            });
        });

        describe("not multi", function() {
            describe("with no value", function() {
                beforeEach(function() {
                    makeComponent({
                        displayField: 'text',
                        valueField: 'val',
                        forceSelection: true,
                        queryMode: 'local',
                        renderTo: Ext.getBody()
                    });
                });

                it("should set the underlying value on blur", function() {
                    jasmine.focusAndWait(component);
                    runs(function() {
                        doTyping('text 2');
                    });
                    jasmine.blurAndWait(component);
                    runs(function() {
                        expect(component.getRawValue()).toBe('text 2');
                        expect(component.getValue()).toBe('value 2');
                    });
                });

                it("should find the first matching text value", function() {
                    jasmine.focusAndWait(component);
                    runs(function() {
                        doTyping('Foo');
                    });
                    jasmine.blurAndWait(component);
                    runs(function() {
                        expect(component.getRawValue()).toBe('Foo');
                        expect(component.getValue()).toBe('foo1');
                    });
                });

                it("should empty the value if nothing matches", function() {
                    jasmine.focusAndWait(component);
                    runs(function() {
                        doTyping('bar');
                    });
                    jasmine.blurAndWait(component);
                    runs(function() {
                        expect(component.getRawValue()).toBe('');
                        expect(component.getValue()).toBeNull();
                    });
                });

                itNotIE9m("should not clear the combobox with custom displayTpl on blur after setValue", function() {
                    component.destroy();
                    makeComponent({
                        displayField: 'text',
                        valueField: 'val',
                        forceSelection: true,
                        queryMode: 'local',
                        renderTo: Ext.getBody(),
                        displayTpl: '<tpl for=".">Id= {val} - {text}</tpl>'
                    });
                    jasmine.focusAndWait(component, null, 'component to focus for the first time');
                    runs(function() {
                        component.setValue('value 2');
                    });
                    jasmine.blurAndWait(component, null, 'component to blur for the first time');

                    jasmine.focusAndWait(component, null, 'component to focus for the second time');

                    jasmine.blurAndWait(component, null, 'component to blur for the second time');

                    runs(function() {
                        expect(component.inputEl.dom.value).not.toBe('');
                    });
                });
            });

            describe("with a current value", function() {
                describe("via configuration", function() {
                    function makeWithValue(value, cfg) {
                        makeComponent(Ext.apply({
                            displayField: 'text',
                            valueField: 'val',
                            forceSelection: true,
                            queryMode: 'local',
                            value: value,
                            renderTo: Ext.getBody()
                        }, cfg));
                    }

                    it("should set the underlying value on blur", function() {
                        makeWithValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('text 2');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 2');
                            expect(component.getValue()).toBe('value 2');
                        });
                    });

                    it("should find the first matching text value", function() {
                        makeWithValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('Foo');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo1');
                        });
                    });

                    it("should restore the previous value if nothing matches", function() {
                        makeWithValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('bar');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 31');
                            expect(component.getValue()).toBe('value 31');
                        });
                    });

                    it("should not overwrite a known value with a matching display value", function() {
                        makeWithValue('foo2');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('Foo');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo2');
                        });
                    });

                    it("should restore the value if it has been cleared", function() {
                        makeWithValue('foo2');
                        component.allowBlank = false;
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('', true);
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo2');
                        });
                    });

                    it("should not restore the value if it has been cleared and allowBlank true", function() {
                        makeWithValue('foo2');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('', true);
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('');
                            expect(component.getValue()).toBe(null);
                        });
                    });

                    itNotIE9m('should not clear the combobox custom displayTpl and calling setValue on blur', function() {

                        makeWithValue('value 1', {
                            displayTpl: '<tpl for=".">Id= {val} - {text}</tpl>'
                        });
                        jasmine.focusAndWait(component);
                        runs(function() {
                            clickListItem('value 2', component.getStore());
                            component.setValue('value 2');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.inputEl.dom.value).not.toBe('');
                        });
                    });
                });

                describe("value via selecting from the list", function() {
                    beforeEach(function() {
                        makeComponent({
                            displayField: 'text',
                            valueField: 'val',
                            forceSelection: true,
                            queryMode: 'local',
                            renderTo: Ext.getBody()
                        });
                    });

                    it("should set the underlying value on blur", function() {
                        clickListItem('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('text 2');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 2');
                            expect(component.getValue()).toBe('value 2');
                        });
                    });

                    it("should find the first matching text value", function() {
                        clickListItem('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('Foo');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo1');
                        });
                    });

                    it("should restore the previous value if nothing matches", function() {
                        clickListItem('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('bar');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 31');
                            expect(component.getValue()).toBe('value 31');
                        });
                    });

                    it("should not overwrite a known value with a matching display value", function() {
                        clickListItem('foo2');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('Foo');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo2');
                        });
                    });

                    it("should restore the value if it has been cleared", function() {
                        clickListItem('foo2');
                        component.allowBlank = false;
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('', true);
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo2');
                        });
                    });

                    it("should not restore the value if it has been cleared and allowBlank is true", function() {
                        clickListItem('foo2');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('', true);
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('');
                            expect(component.getValue()).toBe(null);
                        });
                    });
                });

                describe("value via setValue", function() {
                    beforeEach(function() {
                        makeComponent({
                            displayField: 'text',
                            valueField: 'val',
                            forceSelection: true,
                            queryMode: 'local',
                            renderTo: Ext.getBody()
                        });
                    });

                    it("should set the underlying value on blur", function() {
                        component.setValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('text 2');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 2');
                            expect(component.getValue()).toBe('value 2');
                        });
                    });

                    it("should find the first matching text value", function() {
                        component.setValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('Foo');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo1');
                        });
                    });

                    it("should restore the previous value if nothing matches", function() {
                        component.setValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('bar');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 31');
                            expect(component.getValue()).toBe('value 31');
                        });
                    });

                    it("should not overwrite a known value with a matching display value", function() {
                        component.setValue(store.last());
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('Foo');
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('Foo');
                            expect(component.getValue()).toBe('foo2');
                        });
                    });

                    it("should restore the value if it has been cleared", function() {
                        component.setValue('value 31');
                        component.allowBlank = false;
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('', true);
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('text 31');
                            expect(component.getValue()).toBe('value 31');
                        });
                    });

                    it("should not restore the value if it has been cleared and allowBlank is true", function() {
                        component.setValue('value 31');
                        jasmine.focusAndWait(component);
                        runs(function() {
                            doTyping('', true);
                        });
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getRawValue()).toBe('');
                            expect(component.getValue()).toBe(null);
                        });
                    });
                });

                describe("clearing the value", function() {
                    beforeEach(function() {
                        makeComponent({
                            displayField: 'text',
                            valueField: 'val',
                            forceSelection: true,
                            queryMode: 'local',
                            renderTo: Ext.getBody()
                        });
                    });

                    it("should not set the value after calling clearValue", function() {
                        component.setValue('value 1');
                        component.clearValue();
                        jasmine.focusAndWait(component);
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getValue()).toBeNull();
                        });
                    });

                    it("should not set the value after calling setValue(null)", function() {
                        component.setValue('value 1');
                        component.setValue(null);
                        jasmine.focusAndWait(component);
                        jasmine.blurAndWait(component);
                        runs(function() {
                            expect(component.getValue()).toBeNull();
                        });
                    });
                });
            });

            describe("with remote loading", function() {
                beforeEach(function() {
                    MockAjaxManager.addMethods();
                });

                afterEach(function() {
                    MockAjaxManager.removeMethods();
                });

                // This test seems to randomly fail on FF in the test runner.
                // The component doesn't get focused and fails out
                (Ext.isGecko ? xit : it)("should clear an unmatched value when the store loads", function() {
                    store.destroy();
                    store = new Ext.data.Store({
                        model: CBTestModel,
                        proxy: {
                            type: 'ajax',
                            url: 'foo'
                        }
                    });
                    makeComponent({
                        store: store,
                        displayField: 'text',
                        valueField: 'val',
                        forceSelection: true,
                        queryMode: 'remote',
                        renderTo: Ext.getBody()
                    });
                    jasmine.focusAndWait(component);
                    runs(function() {
                        // Simulate user typing
                        component.setRawValue('foobar');
                        component.doRawQuery();
                        // Collapse to prevent focus issues
                        component.collapse();
                    });
                    jasmine.blurAndWait(component);
                    runs(function() {
                        Ext.Ajax.mockComplete({
                            status: 200,
                            responseText: '[]'
                        });
                        expect(component.getValue()).toBeNull();
                    });
                });
            });
        });

        describe("with remote loading", function() {
            beforeEach(function() {
                MockAjaxManager.addMethods();
            });

            afterEach(function() {
                MockAjaxManager.removeMethods();
            });

            function completeWithData(data) {
                Ext.Ajax.mockComplete({
                    status: 200,
                    responseText: Ext.JSON.encode(data || [])
                });
            }

            // Blurring doesn't work in IE, so use itNotIE
            itNotIE("should clear an unmatched value when the store loads, second version!", function() {
                store.destroy();
                store = new Ext.data.Store({
                    model: CBTestModel,
                    proxy: {
                        type: 'ajax',
                        url: 'foo'
                    }
                });
                makeComponent({
                    store: store,
                    displayField: 'text',
                    valueField: 'val',
                    forceSelection: true,
                    queryMode: 'remote',
                    renderTo: Ext.getBody()
                });
                jasmine.focusAndWait(component);
                runs(function() {
                    // Simulate user typing
                    component.setRawValue('foobar');
                    component.doRawQuery();
                    component.collapse();
                });
                jasmine.blurAndWait(component);
                runs(function() {
                    completeWithData();
                    expect(component.getValue()).toBeNull();
                });
            });

            it("should not clear an unmatched value while typing and forceSelection is true", function() {
                store.destroy();
                store = new Ext.data.Store({
                    model: CBTestModel,
                    proxy: {
                        type: 'ajax',
                        url: 'foo'
                    },
                    autoLoad: true
                });
                makeComponent({
                    store: store,
                    displayField: 'text',
                    valueField: 'val',
                    forceSelection: true,
                    queryMode: 'remote',
                    renderTo: Ext.getBody()
                });

                component.setRawValue('foobar');
                component.doQuery('foobar');
                completeWithData();
                component.setRawValue('foob');
                component.doQuery('foob');
                completeWithData();
                expect(component.inputEl.dom.value).toBe('foob');
            });

            it("should not clear an unmatched value while paging and forceSelection is true", function() {
                var paging, next;

                store.destroy();
                store = new Ext.data.Store({
                    model: CBTestModel,
                    proxy: {
                        type: 'ajax',
                        url: 'foo',
                        reader: {
                            rootProperty: 'data',
                            totalProperty: 'total'
                        }
                    },
                    autoLoad: true,
                    pageSize: 2
                });

                makeComponent({
                    store: store,
                    displayField: 'text',
                    valueField: 'val',
                    forceSelection: true,
                    queryMode: 'remote',
                    pageSize: 2,
                    renderTo: Ext.getBody()
                });

                spyOn(component, 'loadPage').andCallThrough();

                paging = component.getPicker().down('pagingtoolbar');
                next = paging.down('#next');

                component.setRawValue('foobar');
                component.doQuery('foobar');

                // first page of data
                completeWithData({
                    total: 10,
                    data: [{
                        text: 'foobar1',
                        val: 'foobar1'
                    }, {
                        text: 'foobar2',
                        val: 'foobar2'
                    }]
                });

                // focus "next"; this will simulate what happens when button is clicked and hasFocus on combo is set to false
                next.focus();
                paging.moveNext();

                // second page of data
                completeWithData({
                    total: 10,
                    data: [{
                        text: 'foobar3',
                        val: 'foobar3'
                    }, {
                        text: 'foobar4',
                        val: 'foobar4'
                    }]
                });

                // combo.loadPage should be called twice, once for initial load, once for "next"
                expect(component.loadPage.callCount).toBe(2);
                // store's last options should reflect 2nd page, preserving the query param
                expect(store.lastOptions.page).toBe(2);
                expect(store.lastOptions.params.query).toBe('foobar');
                // combo should have lost focus because of interaction with toolbar
                expect(component.hasFocus).toBe(false);
                // rawValue should be perserved
                expect(component.inputEl.dom.value).toBe('foobar');
                expect(component.isPaging).toBe(false);
            });
        });
    });

    describe('Always refilter if dropdown is visible, regardless of minChars threshold', function() {
        var combo;

        beforeEach(function() {
            combo = Ext.create('Ext.form.field.ComboBox', {
                renderTo: Ext.getBody(),
                store: ['first-1', 'first-2', 'first-3', 'first-4', 'first-5', 'does not match query'],
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true,
                minChars: 7,
                beforeQuery: function() {
                    var result = Ext.form.field.ComboBox.prototype.beforeQuery.apply(this, arguments);

                    if (this.picker && this.picker.isVisible) {
                        result.cancel = false;
                    }

                    return result;
                }
            });
        });
        afterEach(function() {
            combo.destroy();
        });

        it('should refilter when querystring length < minChars if dropdown is visible', function() {
            combo.doQuery('first-1');

            // Should filter out all except the 'first-1' value
            expect(combo.store.getCount()).toEqual(1);

            combo.doQuery('first');

            // Should show all the values which match 'first' - that is 5 values
            expect(combo.store.getCount()).toEqual(5);
        });
    });

    describe('Using the "anyMatch" filter config', function() {
        var combo;

        beforeEach(function() {
            combo = Ext.create('Ext.form.field.ComboBox', {
                renderTo: Ext.getBody(),
                store: ['first-1', 'first-2', 'first-3', 'first-4', 'first-5', 'does not match query'],
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true,
                minChars: 2,
                anyMatch: true
            });
        });
        afterEach(function() {
            combo.destroy();
        });

        it('should show all values which contain the query string', function() {
            combo.doQuery('rs');

            // Should show all the values which contain "rs" - that is 5 values
            expect(combo.store.getCount()).toEqual(5);
        });
    });

    describe('Using the "caseSensitive" filter config', function() {
        var combo;

        beforeEach(function() {
            combo = Ext.create('Ext.form.field.ComboBox', {
                renderTo: Ext.getBody(),
                store: ['first-1', 'first-2', 'first-3', 'first-4', 'first-5', 'does not match query'],
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true,
                minChars: 2,
                caseSensitive: true
            });
        });
        afterEach(function() {
            combo.destroy();
        });

        it('should fail to match because caseSensitive is set', function() {
            combo.doQuery('FIRST');

            // Should do case sensitive filtering
            expect(combo.store.getCount()).toEqual(0);
        });
    });

    describe("clearValue", function() {
        function makeClearCombo(value) {
            var cfg = {
                displayField: 'text',
                valueField: 'val',
                renderTo: Ext.getBody()
            };

            if (value) {
                cfg.value = value;
            }

            makeComponent(cfg);
        }

        describe("with no value", function() {
            it("should have an empty value", function() {
                makeClearCombo();
                component.clearValue();
                expect(component.getRawValue()).toBe('');
                expect(component.getValue()).toBeNull();
            });
        });

        describe("with a current value", function() {
            describe("via configuration", function() {
                it("should have an empty value", function() {
                    makeClearCombo('value 31');
                    component.clearValue();
                    expect(component.getRawValue()).toBe('');
                    expect(component.getValue()).toBeNull();
                });
            });

            describe("value via selecting from the list", function() {
                it("should have an empty value", function() {
                    makeClearCombo();
                    clickListItem('value 31');
                    component.clearValue();
                    expect(component.getRawValue()).toBe('');
                    expect(component.getValue()).toBeNull();
                });
            });

            describe("value via setValue", function() {
                it("should have an empty value", function() {
                    makeClearCombo();
                    component.setValue('value 31');
                    component.clearValue();
                    expect(component.getRawValue()).toBe('');
                    expect(component.getValue()).toBeNull();
                });
            });
        });
    });

    describe("reset", function() {
        describe("with no configured value", function() {
            beforeEach(function() {
                makeComponent({
                    displayField: 'text',
                    valueField: 'val',
                    renderTo: Ext.getBody()
                });
            });

            it("should restore the original value", function() {
                component.reset();
                expect(component.getRawValue()).toBe('');
                expect(component.getValue()).toBeNull();
            });

            it("should restore the original value after selecting a list item", function() {
                clickListItem('value 1');
                component.reset();
                expect(component.getRawValue()).toBe('');
                expect(component.getValue()).toBeNull();
            });

            it("should restore the original value after setting the value with setValue", function() {
                component.setValue('value 1');
                component.reset();
                expect(component.getRawValue()).toBe('');
                expect(component.getValue()).toBeNull();
            });
        });

        describe("with a configured value", function() {
            beforeEach(function() {
                makeComponent({
                    displayField: 'text',
                    valueField: 'val',
                    value: 'value 31',
                    renderTo: Ext.getBody()
                });
            });

            it("should restore the original value", function() {
                component.reset();
                expect(component.getRawValue()).toBe('text 31');
                expect(component.getValue()).toBe('value 31');
            });

            it("should restore the original value after selecting a list item", function() {
                clickListItem('value 1');
                component.reset();
                expect(component.getRawValue()).toBe('text 31');
                expect(component.getValue()).toBe('value 31');
            });

            it("should restore the original value after setting the value with setValue", function() {
                component.setValue('value 1');
                component.reset();
                expect(component.getRawValue()).toBe('text 31');
                expect(component.getValue()).toBe('value 31');
            });
        });
    });

    describe("transform", function() {
        var names = 'ABC'.split(''),
            sel;

        function makeSelect(autoAppend, name, value) {
            sel = document.createElement('select');

            var i = 1;

            for (i = 1; i <= names.length; ++i) {
                sel.options[i - 1] = new Option(names[i - 1], i);
            }

            sel.id = 'mySelect';

            if (name) {
                sel.name = name;
            }

            if (value) {
                sel.value = value;
            }

            if (autoAppend) {
                Ext.getBody().appendChild(sel);
            }
        }

        describe("transform option", function() {
            it("should accept a string id and remove the select", function() {
                makeSelect(true);
                component = new Ext.form.field.ComboBox({
                    transform: 'mySelect'
                });
                expect(Ext.getDom('mySelect') == null).toBe(true);
                expect(component.rendered).toBe(true);
            });

            it("should accept a DOM element and remove the select", function() {
                makeSelect(true);
                component = new Ext.form.field.ComboBox({
                    transform: sel
                });
                expect(Ext.getDom('mySelect') == null).toBe(true);
                expect(component.rendered).toBe(true);
            });

            it("should accept an Ext.dom.Element and remove the select", function() {
                makeSelect(true);
                component = new Ext.form.field.ComboBox({
                    transform: Ext.get(sel)
                });
                expect(Ext.getDom('mySelect') == null).toBe(true);
                expect(component.rendered).toBe(true);
            });
        });

        describe("name", function() {
            it("should use the combo name over a name on the select", function() {
                makeSelect(true, 'selName');
                component = new Ext.form.field.ComboBox({
                    transform: sel,
                    name: 'comboName'
                });
                expect(component.getName()).toBe('comboName');
            });

            it("should use the select name if no name is specified on the combo", function() {
                makeSelect(true, 'selName');
                component = new Ext.form.field.ComboBox({
                    transform: sel
                });
                expect(component.getName()).toBe('selName');
            });
        });

        describe("value", function() {
            it("should use the combo value over the value on the select", function() {
                makeSelect(true, undefined, '2');
                component = new Ext.form.field.ComboBox({
                    transform: sel,
                    value: '3'
                });
                expect(component.getValue()).toBe('3');
            });

            it("should use the select value if no value is specified on the combo", function() {
                makeSelect(true, undefined, '2');
                component = new Ext.form.field.ComboBox({
                    transform: sel
                });
                expect(component.getValue()).toBe('2');
            });
        });

        it("should use the options in the select field", function() {
            makeSelect(true);
            component = new Ext.form.field.ComboBox({
                transform: 'mySelect'
            });
            var store = component.getStore();

            expect(store.getAt(0).get('field1')).toBe('1');
            expect(store.getAt(0).get('field2')).toBe('A');
            expect(store.getAt(1).get('field1')).toBe('2');
            expect(store.getAt(1).get('field2')).toBe('B');
            expect(store.getAt(2).get('field1')).toBe('3');
            expect(store.getAt(2).get('field2')).toBe('C');
        });

        describe("rendering", function() {
            it("should render in place", function() {
                var root = Ext.getBody().appendChild({
                    tag: 'div',
                    id: 'myRoot'
                });

                makeSelect(false);
                root.appendChild(sel);

                component = new Ext.form.field.ComboBox({
                    transform: 'mySelect'
                });
                expect(component.el.dom.parentNode.id).toBe('myRoot');
                component.destroy();
                root.remove();
            });

            it("should render using renderTo with transformInPlace: false", function() {
                makeSelect(true);
                var root = Ext.getBody().appendChild({
                    tag: 'div',
                    id: 'myRoot'
                });

                component = new Ext.form.field.ComboBox({
                    transform: 'mySelect',
                    transformInPlace: false,
                    renderTo: root
                });
                expect(component.el.dom.parentNode.id).toBe('myRoot');
                component.destroy();
                root.remove();
            });

            it("should render as part of a layout with transformInPlace: false", function() {
                makeSelect(true);

                var form = new Ext.form.Panel({
                        renderTo: Ext.getBody(),
                        items: {
                            itemId: 'combo',
                            xtype: 'combobox',
                            transform: 'mySelect',
                            transformInPlace: false
                        }
                    }),
                    component = form.down('#combo');

                expect(component.ownerCt).toBe(form);
                form.destroy();
            });
        });
    });

    // Focus issues in the test runner
    (Ext.isWebkit ? describe : xdescribe)("clearFilterOnBlur", function() {

        it("should clear a filter applied on blur with clearFilterOnBlur: true", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            var count = store.getCount();

            // Simulate user typing 'text 3'
            component.setRawValue('text 3');
            component.expand();
            component.doRawQuery();
            expect(store.getCount()).toBe(5);
            component.blur();
            expect(store.getCount()).toBe(count);
        });

        it("should clear a only the combo filter applied on blur with clearFilterOnBlur: true", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            store.filter({
                property: 'text',
                value: 'text'
            });
            var count = store.getCount();

            // Simulate user typing 'text 3'
            component.setRawValue('text 3');
            component.expand();
            component.doRawQuery();
            expect(store.getCount()).toBe(5);
            component.blur();
            expect(store.getCount()).toBe(count);
        });

        it("should requery the store on focus with clearFilterOnBlur: true", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            var count = store.getCount();

            // Simulate user typing 'text 3'
            component.setRawValue('text 3');
            component.expand();
            component.doRawQuery();
            expect(store.getCount()).toBe(5);
            component.blur();
            expect(store.getCount()).toBe(count);
            component.focus();
            expect(store.getCount()).toBe(5);
        });

        it("should not modify the filter with clearFilterOnBlur: false", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody(),
                clearFilterOnBlur: false
            });
            // Simulate user typing 'text 3'
            component.setRawValue('text 3');
            component.expand();
            component.doRawQuery();
            expect(store.getCount()).toBe(5);
            component.blur();
            expect(store.getCount()).toBe(5);
        });

    });

    describe('displayTpl', function() {
        describe('should create default', function() {
            beforeEach(function() {
                makeComponent();
            });

            it('displayTpl should be an XTemplate', function() {
                expect(component.displayTpl.isTemplate).toBe(true);
            });

            it('displayTpl html match', function() {
                expect(component.displayTpl.html).toBe('<tpl for=".">{[typeof values === "string" ? values : values["text"]]}<tpl if="xindex < xcount">, </tpl></tpl>');
            });
        });

        describe('should create from string', function() {
            beforeEach(function() {
                makeComponent({
                   displayTpl: '<tpl for=".">{[typeof values === "string" ? values : values["foo"]]}</tpl>'
                });
            });

            it('displayTpl should be an XTemplate', function() {
                expect(component.displayTpl.isTemplate).toBe(true);
            });

            it('displayTpl html match', function() {
                expect(component.displayTpl.html).toBe('<tpl for=".">{[typeof values === "string" ? values : values["foo"]]}</tpl>');
            });
        });

        describe('should create from array of strings', function() {
            beforeEach(function() {
                makeComponent({
                    displayTpl: [
                        '<tpl for=".">',
                            '{[typeof values === "string" ? values : values["foo"]]}',
                        '</tpl>'
                    ]
                });
            });

            it('displayTpl should be an XTemplate', function() {
                expect(component.displayTpl.isTemplate).toBe(true);
            });

            it('displayTpl html match', function() {
                expect(component.displayTpl.html).toBe('<tpl for=".">{[typeof values === "string" ? values : values["foo"]]}</tpl>');
            });
        });

        it("should have the correct display value when displayField is set in initComponent", function() {
            var Cls = Ext.define(null, {
                extend: 'Ext.form.field.ComboBox',

                initComponent: function() {
                    this.valueField = 'text';
                    this.displayField = 'val';
                    this.callParent();
                }
            });

            component = new Cls({
                renderTo: Ext.getBody(),
                store: store
            });

            component.setValue('text 31');
            expect(component.getRawValue()).toBe('value 31');
        });
    });

    describe("events", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
        });

        afterEach(function() {
            spy = null;
        });

        function makeEventCombo(cfg) {
            makeComponent(Ext.apply({
                renderTo: Ext.getBody(),
                valueField: 'val',
                displayField: 'text'
            }, cfg));
        }

        describe("specialkey", function() {
            beforeEach(function() {
                makeEventCombo({
                    listeners: {
                        specialkey: spy
                    }
                });
            });

            it("should fire specialkey when collapsed", function() {
                jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.ENTER);
                expect(spy).toHaveBeenCalled();
            });

            it("should fire specialkey when expanded", function() {
                component.expand();
                jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.ENTER);
                expect(spy).toHaveBeenCalled();
            });
        });

        describe("change", function() {
            function expectArgs(newVal, oldVal) {
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(component);
                expect(args[1]).toBe(newVal);
                expect(args[2]).toBe(oldVal);
            }

            describe("via setValue", function() {
                it("should not fire when configured with a value", function() {
                    makeEventCombo({
                        value: 'value 2',
                        listeners: {
                            change: spy
                        }
                    });
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire once when setting an initial value", function() {
                    makeEventCombo();
                    component.on('change', spy);
                    component.setValue('value 1');
                    expect(spy.callCount).toBe(1);
                    expectArgs('value 1', null);
                });

                it("should fire once when modifying an existing value", function() {
                    makeEventCombo();
                    component.setValue('value 2');
                    component.on('change', spy);
                    component.setValue('value 1');
                    expect(spy.callCount).toBe(1);
                    expectArgs('value 1', 'value 2');
                });

                it("should fire once when nulling the value", function() {
                    makeEventCombo();
                    component.setValue('value 2');
                    component.on('change', spy);
                    component.setValue(null);
                    expect(spy.callCount).toBe(1);
                    expectArgs(null, 'value 2');
                });
            });

            describe("via user interaction", function() {
                it("should fire once when selecting an initial value", function() {
                    makeEventCombo();
                    component.on('change', spy);
                    clickListItem('value 1');
                    expect(spy.callCount).toBe(1);
                    expectArgs('value 1', null);
                });

                it("should fire once when modifying an existing value", function() {
                    makeEventCombo();
                    component.setValue('value 2');
                    component.on('change', spy);
                    clickListItem('value 1');
                    expect(spy.callCount).toBe(1);
                    expectArgs('value 1', 'value 2');
                });
            });
        });

        describe("select", function() {
            describe("via setValue", function() {
                it("should not fire when configured with a value", function() {
                    makeEventCombo({
                        value: 'value 2',
                        listeners: {
                            select: spy
                        }
                    });
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire when setting an initial value", function() {
                    makeEventCombo();
                    component.on('select', spy);
                    component.setValue('value 1');
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire when modifying an existing value", function() {
                    makeEventCombo();
                    component.setValue('value 2');
                    component.on('select', spy);
                    component.setValue('value 1');
                    expect(spy).not.toHaveBeenCalled();
                });
            });

            describe("via user interaction", function() {
                it("should fire once when setting an initial value", function() {
                    makeEventCombo();
                    component.on('select', spy);
                    clickListItem('value 1');
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(component);
                    expect(spy.mostRecentCall.args[1]).toBe(store.getAt(0));
                });

                it("should fire once when modifying an existing value", function() {
                    makeEventCombo();
                    component.setValue('value 2');
                    component.on('select', spy);
                    clickListItem('value 1');
                    expect(spy.mostRecentCall.args[0]).toBe(component);
                    expect(spy.mostRecentCall.args[1]).toBe(store.getAt(0));
                });
            });

            describe("records param", function() {
                function get(index) {
                    return store.getAt(index);
                }

                it("should be a single record with multiSelect: false", function() {
                    makeEventCombo({
                        multiSelect: false
                    });
                    component.on('select', spy);
                    clickListItem('value 1');
                    clickListItem('value 2');
                    clickListItem('value 3');
                    expect(spy.calls[0].args[1]).toBe(get(0));
                    expect(spy.calls[1].args[1]).toBe(get(1));
                    expect(spy.calls[2].args[1]).toBe(get(2));
                });

                it("should be an array of records with multiSelect: true", function() {
                    makeEventCombo({
                        multiSelect: true
                    });
                    component.on('select', spy);
                    clickListItem('value 1');
                    clickListItem('value 2');
                    clickListItem('value 3');
                    expect(spy.calls[0].args[1]).toEqual([get(0)]);
                    expect(spy.calls[1].args[1]).toEqual([get(0), get(1)]);
                    expect(spy.calls[2].args[1]).toEqual([get(0), get(1), get(2)]);
                });

                it('should not deselect selections on container events', function() {
                    var view, selModel;

                    makeEventCombo({
                        multiSelect: true
                    });

                    clickListItem('value 1');
                    clickListItem('value 2');

                    view = component.picker;
                    selModel = view.selModel;

                    // Do a sanity.
                    expect(selModel.getSelected().length).toBe(2);

                    jasmine.fireMouseEvent(view.el.dom, 'click');

                    // Expect there to be the same number of selections as before.
                    expect(selModel.getSelected().length).toBe(2);
                });
            });
        });

        describe("beforeselect", function() {
            beforeEach(function() {
                makeEventCombo({
                    listeners: {
                        beforeselect: spy
                    }
                });
            });

            it("should fire when selecting", function() {
                clickListItem('value 1');

                expect(spy.callCount).toBe(1);
            });

            it("should fire after binding another store", function() {
                clickListItem('value 1');

                component.bindStore(new Ext.data.Store({
                    proxy: {
                        type: 'memory'
                    },
                    model: CBTestModel,
                    data: [
                        { id: 100, text: 'blerg', val: 'throbbe' },
                        { id: 101, text: 'zingbong', val: 'gurgle' }
                    ]
                }));

                clickListItem('gurgle', component.store);

                // TODO Find out why events are firing differently between TC and local run!
                expect(spy.callCount).toBeGreaterThanOrEqual(2);
            });
        });

        describe("beforedeselect", function() {
            beforeEach(function() {
                makeEventCombo({
                    listeners: {
                        beforedeselect: spy
                    }
                });
            });

            it("should not fire when first selecting", function() {
                clickListItem('value 2');

                expect(spy.callCount).toBe(0);
            });

            it("should fire when selecting 2nd time", function() {
                clickListItem('value 1');
                clickListItem('value 2');

                expect(spy.callCount).toBe(1);
            });

            it("should fire after binding another store", function() {
                clickListItem('value 1');

                component.bindStore(new Ext.data.Store({
                    proxy: {
                        type: 'memory'
                    },
                    model: CBTestModel,
                    data: [
                        { id: 42, text: 'mymze', val: 'knurl' },
                        { id: 43, text: 'foobaroo', val: 'yumyum' }
                    ]
                }));

                clickListItem('knurl', component.store);
                clickListItem('yumyum', component.store);

                expect(spy.callCount).toBeGreaterThanOrEqual(1);
            });
        });
    });

    describe("binding", function() {
        var viewModel, spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
            viewModel = new Ext.app.ViewModel();
        });

        afterEach(function() {
            spy = viewModel = null;
        });

        function makeViewModelCombo(cfg) {
            makeComponent(Ext.apply({
                displayField: 'text',
                valueField: 'val',
                viewModel: viewModel,
                renderTo: Ext.getBody()
            }, cfg));
        }

        describe("view model selection", function() {
            function getByVal(val) {
                var index = store.findExact('val', val);

                return store.getAt(index);
            }

            function selectNotify(rec) {
                component.expand();
                component.getPicker().getSelectionModel().select(rec);
                viewModel.notify();
                component.collapse();
            }

            describe("reference", function() {
                describe("no initial value", function() {
                    beforeEach(function() {
                        viewModel.bind('{userList.selection}', spy);
                        makeViewModelCombo({
                            reference: 'userList'
                        });
                        viewModel.notify();
                    });

                    it("should publish null by default", function() {
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBeNull();
                        expect(args[1]).toBeUndefined();
                    });

                    it("should publish the value when selected", function() {
                        var rec = getByVal('value 1');

                        selectNotify(rec);
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(rec);
                        expect(args[1]).toBeNull();
                        expect(component.getValue()).toBe('value 1');
                    });

                    it("should publish when the selection is changed", function() {
                        var rec1 = getByVal('value 1'),
                            rec2 = getByVal('value 2');

                        selectNotify(rec1);
                        spy.reset();
                        selectNotify(rec2);
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(rec2);
                        expect(args[1]).toBe(rec1);
                        expect(component.getValue()).toBe('value 2');
                    });

                    it("should publish the record when setting the value", function() {
                        component.setValue('value 1');
                        viewModel.notify();
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(getByVal('value 1'));
                        expect(args[1]).toBeNull();
                    });

                    it("should publish the record when the value is changed", function() {
                        component.setValue('value 1');
                        viewModel.notify();
                        spy.reset();
                        component.setValue('value 2');
                        viewModel.notify();
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(getByVal('value 2'));
                        expect(args[1]).toBe(getByVal('value 1'));
                    });

                    it("should publish the record when the value is cleared", function() {
                        component.setValue('value 1');
                        viewModel.notify();
                        spy.reset();
                        component.setValue(null);
                        viewModel.notify();
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBeNull();
                        expect(args[1]).toBe(getByVal('value 1'));
                    });
                });

                describe("with initial value", function() {
                    beforeEach(function() {
                        viewModel.bind('{userList.selection}', spy);
                        makeViewModelCombo({
                            reference: 'userList',
                            value: 'value 2'
                        });
                        viewModel.notify();
                    });

                    it("should publish the record", function() {
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(getByVal('value 2'));
                        expect(args[1]).toBeUndefined();
                    });
                });
            });

            describe("two way binding", function() {
                describe("no initial value", function() {
                    beforeEach(function() {
                        viewModel.bind('{foo}', spy);
                        makeViewModelCombo({
                            bind: {
                                selection: '{foo}'
                            }
                        });
                        viewModel.notify();
                    });

                    describe("changing the selection", function() {
                        it("should trigger the binding when adding a selection", function() {
                            var rec = getByVal('value 1');

                            selectNotify(rec);
                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(rec);
                            expect(args[1]).toBeUndefined();
                        });

                        it("should trigger the binding when changing the selection", function() {
                            var rec1 = getByVal('value 1'),
                                rec2 = getByVal('value 2');

                            selectNotify(rec1);
                            spy.reset();
                            selectNotify(rec2);
                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(rec2);
                            expect(args[1]).toBe(rec1);
                        });

                        it("should trigger the binding when setting the value", function() {
                            component.setValue('value 1');
                            viewModel.notify();
                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(getByVal('value 1'));
                            expect(args[1]).toBeUndefined();
                        });

                        it("should trigger the binding when the value is changed", function() {
                            component.setValue('value 1');
                            viewModel.notify();
                            spy.reset();
                            component.setValue('value 2');
                            viewModel.notify();
                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(getByVal('value 2'));
                            expect(args[1]).toBe(getByVal('value 1'));
                        });

                        it("should trigger the binding when the value is cleared", function() {
                            component.setValue('value 1');
                            viewModel.notify();
                            spy.reset();
                            component.setValue(null);
                            viewModel.notify();
                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBeNull();
                            expect(args[1]).toBe(getByVal('value 1'));
                        });
                    });

                    describe("changing the view model value", function() {
                        it("should set the value when setting the record", function() {
                            var rec = getByVal('value 1');

                            viewModel.set('foo', rec);
                            viewModel.notify();
                            expect(component.getValue()).toBe('value 1');
                        });

                        it("should set the value when updating the record", function() {
                            viewModel.set('foo', getByVal('value 1'));
                            viewModel.notify();
                            viewModel.set('foo', getByVal('value 2'));
                            viewModel.notify();
                            expect(component.getValue()).toBe('value 2');
                        });

                        it("should deselect when clearing the value", function() {
                            viewModel.set('foo', getByVal('value 1'));
                            viewModel.notify();
                            viewModel.set('foo', null);
                            viewModel.notify();
                            expect(component.getValue()).toBeNull();
                        });
                    });
                });

                // Not sure if we want to support this, leave this out for now
                xdescribe("with initial value", function() {
                    it("should trigger the binding with an initial value in the combo", function() {
                        viewModel.bind('{foo}', spy);
                        makeViewModelCombo({
                            value: 'value 2',
                            bind: {
                                selection: '{foo}'
                            }
                        });
                        viewModel.notify();
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(getByVal('value 2'));
                        expect(args[1]).toBeUndefined();
                    });
                });

                describe("reloading the store", function() {
                    beforeEach(function() {
                        MockAjaxManager.addMethods();
                        viewModel.bind('{foo}', spy);
                        makeViewModelCombo({
                            bind: {
                                selection: '{foo}'
                            }
                        });
                        viewModel.notify();

                        selectNotify(getByVal('value 1'));
                        spy.reset();

                        store.setProxy({
                            type: 'ajax',
                            url: 'fake'
                        });
                        store.load();
                    });

                    afterEach(function() {
                        MockAjaxManager.removeMethods();
                    });

                    describe("when the selected record is in the result set", function() {
                        it("should trigger the selection binding", function() {
                            Ext.Ajax.mockComplete({
                                status: 200,
                                responseText: Ext.encode([
                                    { id: 1, text: 'text 1', val: 'value 1' },
                                    { id: 2, text: 'text 2', val: 'value 2' }
                                ])
                            });

                            viewModel.notify();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBe(store.getAt(0));
                        });
                    });

                    describe("when the selected record is not in the result set", function() {
                        it("should trigger the selection binding", function() {
                            Ext.Ajax.mockComplete({
                                status: 200,
                                responseText: '[]'
                            });

                            viewModel.notify();
                            expect(spy.callCount).toBe(1);
                            expect(spy.mostRecentCall.args[0]).toBeNull();
                        });
                    });
                });
            });
        });
    });

    describe("bindStore", function() {
        var newData, newStore;

        beforeEach(function() {
            newData = [
                { text: 'text 1', val: 1 },
                { text: 'text 2', val: 2 },
                { text: 'text 3', val: 3 },
                { text: 'text 4', val: 4 },
                { text: 'text 5', val: 5 }
            ];
        });

        afterEach(function() {
            newStore = Ext.destroy(newStore);
        });

        it("should apply a filter when binding a new store", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            component.doQuery('text 3');

            newStore = new Ext.data.Store({
                model: CBTestModel,
                data: newData
            });

            component.bindStore(newStore);
            expect(newStore.getCount()).toBe(1);
        });

        it("should be able to filter the store after binding a new one", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            component.doQuery('text 3');

            newStore = new Ext.data.Store({
                model: CBTestModel,
                data: newData
            });

            component.bindStore(newStore);
            component.doQuery('text 2');
            expect(newStore.getCount()).toBe(1);
        });

        it("should not apply active filters if the param is passed", function() {
            makeComponent({
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            component.doQuery('text 3');

            newStore = new Ext.data.Store({
                model: CBTestModel,
                data: newData
            });

            component.bindStore(newStore, true);
            expect(newStore.getCount()).toBe(5);
        });

        it("should be able to select after binding a new store", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                queryMode: 'local',
                displayField: 'text',
                valueField: 'val'
            });

            newStore = new Ext.data.Store({
                model: CBTestModel,
                data: newData
            });
            component.bindStore(newStore);
            component.expand();
            clickListItem('2', newStore);
            expect(component.getValue()).toBe('2');
        });

        it("should be able to select after binding a store when one wasn't configured", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                queryMode: 'local',
                displayField: 'text',
                valueField: 'val'
            }, true);

            newStore = new Ext.data.Store({
                model: CBTestModel,
                data: newData
            });
            component.bindStore(newStore);
            component.expand();
            clickListItem('2', newStore);
            expect(component.getValue()).toBe('2');
        });
    });

    describe("setting value with different store states", function() {
        describe("with a store not bound", function() {
            it("should not display the raw value and resolve when the store is bound", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    queryMode: 'local',
                    displayField: 'text',
                    valueField: 'val'
                }, true);
                component.setValue('value 3');
                expect(component.getValue()).toBe('value 3');
                expect(component.getRawValue()).toBe('');
                component.bindStore(store);
                expect(component.getValue()).toBe('value 3');
                expect(component.getRawValue()).toBe('text 3');
            });
        });

        describe("with a store populated via adding records", function() {
            it("should resolve the display value", function() {
                store.destroy();
                store = new Ext.data.Store({
                    model: CBTestModel
                });

                store.add([
                    { text: 'text 1', val: 'value 1' },
                    { text: 'text 2', val: 'value 2' },
                    { text: 'text 3', val: 'value 3' }
                ]);

                makeComponent({
                    renderTo: Ext.getBody(),
                    queryMode: 'local',
                    displayField: 'text',
                    valueField: 'val'
                });
                component.setValue('value 2');
                expect(component.getValue()).toBe('value 2');
                expect(component.getRawValue()).toBe('text 2');
            });
        });

        describe("setting a value with a remote store", function() {
            var fakeData, remoteStore,
                ComboModel;

            function createStore(cfg) {
                remoteStore = new Ext.data.Store(Ext.apply({
                    model: ComboModel,
                    proxy: {
                        type: 'ajax',
                        url: '/fake'
                    }
                }, cfg));
            }

            function completeWithData(data) {
                Ext.Ajax.mockComplete({
                    status: 200,
                    responseText: Ext.JSON.encode(data || fakeData)
                });
            }

            beforeEach(function() {
                MockAjaxManager.addMethods();
                ComboModel = Ext.define(null, {
                    extend: 'Ext.data.Model',
                    fields: ['id', 'name']
                });

                fakeData = [{
                    id: 1,
                    name: 'Foo'
                }, {
                    id: 2,
                    name: 'Bar'
                }, {
                    id: 3,
                    name: 'Baz'
                }];
            });

            afterEach(function() {
                Ext.destroy(remoteStore);
                MockAjaxManager.removeMethods();
                ComboModel = null;
            });

            describe("while the store is loading", function() {
                function makeLoadCombo(valueIsName, cfg) {
                    makeComponent(Ext.apply({
                        displayField: 'name',
                        valueField: valueIsName ? 'name' : 'id',
                        store: remoteStore,
                        renderTo: Ext.getBody()
                    }, cfg));
                }

                beforeEach(function() {
                    createStore();
                });

                it("should not trigger a second load", function() {
                    makeLoadCombo();
                    remoteStore.load();
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                it("should not trigger a second load with autoLoadOnValue", function() {
                    makeLoadCombo({
                        autoLoadOnValue: true
                    });
                    remoteStore.load();
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                describe("display value", function() {
                    it("should not put the id as the display value while loading", function() {
                        makeLoadCombo();
                        remoteStore.load();
                        component.setValue(1);
                        expect(component.getRawValue()).toBe('');
                    });

                    it("should use the model raw value as the display value while loading if a model is passed", function() {
                        makeLoadCombo();
                        remoteStore.load();
                        component.setValue(new ComboModel({
                            id: 1,
                            name: 'Foo'
                        }));
                        expect(component.getRawValue()).toBe('Foo');
                        expect(component.getValue()).toBe(1);
                    });

                    it("should update the display value when the store loads", function() {
                        makeLoadCombo();
                        remoteStore.load();
                        component.setValue(1);
                        completeWithData();
                        expect(component.getRawValue()).toBe('Foo');
                    });

                    it("should leave the value when displayField === valueField", function() {
                        makeLoadCombo(true);
                        remoteStore.load();
                        component.setValue('foo');
                        expect(component.getRawValue()).toBe('foo');
                    });

                    it("should remove emptyCls when displayField === valueField, store is not autoloaded, and value is provided by a bind", function() {
                        var vm;

                        makeLoadCombo(true, {
                            emptyText: 'Please select a name',
                            bind: {
                                value: '{user.name}'
                            },
                            viewModel: {}
                        });

                        vm = component.getViewModel();
                        vm.set('user.name', 'foo');
                        vm.notify();

                        expect(component.getRawValue()).toBe('foo');
                        expect(component.inputEl).not.toHaveCls('x-form-empty-field');
                        expect(component.inputEl).not.toHaveCls('x-form-empty-field-default');
                    });
                });
            });

            describe("while having a pending auto load", function() {
                var flushLoadSpy;

                beforeEach(function() {
                    synchronousLoad = false;
                    var extAsap = Ext.asap;

                    flushLoadSpy = spyOn(Ext.data.Store.prototype, 'flushLoad').andCallThrough();

                    Ext.asap = function(fn, scope) {
                        return Ext.defer(fn, 100, scope);
                    };

                    createStore({
                        autoLoad: true
                    });
                    makeComponent({
                        displayField: 'name',
                        valueField: 'id',
                        store: remoteStore,
                        renderTo: Ext.getBody()
                    });
                    Ext.asap = extAsap;
                });
                afterEach(function() {
                    synchronousLoad = true;
                    Ext.data.ProxyStore.prototype.flushLoad = storeFlushLoad;
                });

                it("should not trigger a load", function() {
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                it("should not trigger a load with autoLoadOnValue", function() {
                    component.autoLoadOnValue = true;
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                it("should not put the id as the raw value while loading", function() {
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(component.getRawValue()).toBe('');
                });

                it("should update the display value when the store loads", function() {
                    component.setValue(1);
                    // Wait for autoLoad
                    waitsForSpy(flushLoadSpy);
                    runs(function() {
                        completeWithData();
                        expect(component.getRawValue()).toBe('Foo');
                    });
                });
            });

            describe("not loading & without autoLoad", function() {
                beforeEach(function() {
                    createStore();
                    makeComponent({
                        autoLoadOnValue: true,
                        displayField: 'name',
                        valueField: 'id',
                        store: remoteStore,
                        renderTo: Ext.getBody()
                    });
                });

                it("should not trigger a load with autoLoadOnValue: false", function() {
                    component.autoLoadOnValue = false;
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                it("should not trigger a load if the value is undefined", function() {
                    spyOn(remoteStore, 'load');
                    component.setValue(undefined);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                it("should not trigger a load if the value is null", function() {
                    spyOn(remoteStore, 'load');
                    component.setValue(null);
                    expect(remoteStore.load).not.toHaveBeenCalled();
                });

                it("should trigger a load", function() {
                    spyOn(remoteStore, 'load');
                    component.setValue(1);
                    expect(remoteStore.load).toHaveBeenCalled();
                });

                it("should not put the id as the raw value while loading", function() {
                    component.setValue(1);
                    expect(component.getRawValue()).toBe('');
                });

                it("should update the display value when the store loads", function() {
                    component.setValue(1);
                    completeWithData();
                    expect(component.getRawValue()).toBe('Foo');
                });

                it("should not update the display value when the store loads if the value is already set", function() {
                    component.setValue(new ComboModel({
                        id: 4,
                        name: 'Not in payload'
                    }));
                    var doSetValueSpy = spyOn(component, "setValue").andCallThrough();

                    completeWithData();

                    // The value was set from a record.
                    // So it must not be overwritten by the autoLoadOnValue handling.
                    expect(doSetValueSpy).not.toHaveBeenCalled();
                    expect(component.getRawValue()).toBe('Not in payload');
                });

                it("should not update the display value when the store loads if the value is already set, and selected value should be the newly matched record", function() {
                    var initialRec = new ComboModel({
                        id: 3,
                        name: 'Baz'
                    });

                    component.setValue(initialRec);
                    var doSetValueSpy = spyOn(component, "setValue").andCallThrough();

                    completeWithData();

                    // The value was set from a record.
                    // So it must not be overwritten by the autoLoadOnValue handling.
                    expect(doSetValueSpy).not.toHaveBeenCalled();
                    expect(component.getRawValue()).toBe('Baz');

                    // The record will be the newly loaded record because the picker's
                    // selection model will resync on load, and the valueCollection *IS*
                    // the selModel's collection.
                    expect(component.getSelectedRecord() === initialRec).toBe(false);
                });
            });

            describe("while not having a store bound", function() {
                beforeEach(function() {
                    createStore();
                    makeComponent({
                        displayField: 'name',
                        valueField: 'id',
                        renderTo: Ext.getBody()
                    }, true);
                });

                it("should not put the id as the raw value when nothing is bound", function() {
                    component.setValue(1);
                    expect(component.getRawValue()).toBe('');
                });

                it("should update the display value when a loaded store is bound", function() {
                    remoteStore.load();
                    completeWithData();
                    component.setValue(1);
                    component.bindStore(remoteStore);
                    expect(component.getRawValue()).toBe('Foo');
                });

                it("should update the display value when a loading store is bound", function() {
                    remoteStore.load();
                    component.setValue(1);
                    component.bindStore(remoteStore);
                    completeWithData();
                    expect(component.getRawValue()).toBe('Foo');
                });

                describe("with unloaded store", function() {
                    it("should not trigger a load with autoLoadOnValue: false", function() {
                        component.autoLoadOnValue = false;
                        component.setValue(1);
                        spyOn(remoteStore, 'load');
                        component.bindStore(remoteStore);
                        expect(remoteStore.load).not.toHaveBeenCalled();
                    });

                    it("should not trigger a load with autoLoadOnValue: true", function() {
                        component.autoLoadOnValue = true;
                        component.setValue(1);
                        spyOn(remoteStore, 'load');
                        component.bindStore(remoteStore);
                        expect(remoteStore.load).toHaveBeenCalled();
                    });
                });
            });

            describe("chained stores", function() {
                var chained;

                it("should not update the display value if the source is loading", function() {
                    createStore();
                    chained = new Ext.data.ChainedStore({
                        source: remoteStore
                    });
                    remoteStore.load();
                    makeComponent({
                        displayField: 'name',
                        valueField: 'id',
                        renderTo: Ext.getBody(),
                        store: chained,
                        value: 2
                    });
                    expect(component.getRawValue()).toBe('');
                    completeWithData();
                    chained.destroy();
                });

                it("should not update the display value if the source has a pending autoLoad", function() {
                    createStore({
                        autoLoad: true
                    });
                    chained = new Ext.data.ChainedStore({
                        source: remoteStore
                    });
                    makeComponent({
                        displayField: 'name',
                        valueField: 'id',
                        renderTo: Ext.getBody(),
                        store: chained,
                        value: 2
                    });
                    expect(component.getRawValue()).toBe('');
                    chained.destroy();
                });

                it("should update the display value when the source store loads", function() {
                    createStore();
                    chained = new Ext.data.ChainedStore({
                        source: remoteStore
                    });
                    remoteStore.load();
                    makeComponent({
                        displayField: 'name',
                        valueField: 'id',
                        renderTo: Ext.getBody(),
                        store: chained,
                        value: 2
                    });
                    completeWithData();
                    expect(component.getRawValue()).toBe('Bar');
                    chained.destroy();
                });
            });
        });
    });

    describe("store modifications", function() {
        describe("remove", function() {
            describe("with forceSelection: true", function() {
                it("should not change the value if the removed record is not selected", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        forceSelection: true,
                        displayField: 'text',
                        valueField: 'val',
                        value: 'value 3'
                    });
                    store.removeAt(0);
                    expect(component.getValue()).toBe('value 3');
                });

                it("should clear the value when removing the selected record", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        queryMode: 'local',
                        forceSelection: true,
                        displayField: 'text',
                        valueField: 'val',
                        value: 'value 3'
                    });
                    store.removeAt(2);
                    expect(component.getRawValue()).toBe('');
                    expect(component.getValue()).toBeNull();
                });
            });

            describe("with forceSelection: false", function() {
                it("should not clear the value when removing the selected record", function() {
                    makeComponent({
                        renderTo: Ext.getBody(),
                        forceSelection: false,
                        displayField: 'text',
                        valueField: 'val',
                        value: 'value 3'
                    });
                    store.removeAt(2);
                    expect(component.getValue()).toBe('value 3');
                });
            });
        });

        describe("update", function() {
            it("should update the raw value when the selected record text is changed", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    queryMode: 'local',
                    forceSelection: true,
                    displayField: 'text',
                    valueField: 'val',
                    value: 'value 3'
                });
                store.getAt(2).set('text', 'Foo!');
                expect(component.getRawValue()).toBe('Foo!');
                expect(component.getValue()).toBe('value 3');
            });
        });

        describe("filtering", function() {
            it("should clear the selected value when the record is filtered out", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    forceSelection: true,
                    displayField: 'text',
                    valueField: 'val',
                    value: 'value 3'
                });
                store.getFilters().add(function(rec) {
                    return rec.get('val') !== 'value 3';
                });
                expect(component.getRawValue()).toBe('');
                expect(component.getValue()).toBeNull();
            });
        });
    });

    describe("chained stores", function() {
        it("should allow a non-record value to be used with forceSelection: false", function() {
            var chained = new Ext.data.ChainedStore({
                source: store
            });

            makeComponent({
                store: chained,
                displayField: 'text',
                valueField: 'val',
                forceSelection: false
            });
            component.setValue('Foo');
            expect(component.getValue()).toBe('Foo');
        });
    });

    describe('alternate components as the picker', function() {
        // See EXTJS-13089 and EXTJS-14151.
        var c, panel, dom;

        describe('grid as picker', function() {
            beforeEach(function() {
                c = new Ext.form.field.ComboBox({
                    createPicker: function() {
                        panel = new Ext.grid.Panel({
                            id: 'foo',
                            columns: [
                                { dataIndex: 'company', text: 'Company' },
                                { dataIndex: 'price', text: 'Price' }
                            ],
                            store: new Ext.data.ArrayStore({
                                fields: [
                                    { name: 'company' },
                                    { name: 'price', type: 'float' }
                                ],
                                data: [
                                    ['3m Co', 71.72],
                                    ['Alcoa Inc', 29.01],
                                    ['Boeing Co.', 75.43]
                                ]
                            }),
                            width: 250,
                            draggable: true,
                            simpleDrag: true,
                            floating: true
                        });

                        return panel;
                    },
                    renderTo: Ext.getBody()
                });

                c.expand();
            });

            afterEach(function() {
                Ext.destroy(c);
                c = panel = dom = null;
            });

            it('should have an ownerCmp reference to the combo', function() {
                expect(panel.ownerCmp === c).toBe(true);
            });

            it('should be able to be looked up by CQ', function() {
                expect(c.owns(panel.el)).toBe(true);
            });

            it('should be able to use the ghost panel in the CQ hierarchy when dragging', function() {
                dom = c.getPicker().header.el.dom;

                jasmine.fireMouseEvent(dom, 'mousedown');
                // Moving the dom element will trigger the ghost cmp.
                jasmine.fireMouseEvent(dom, 'mousemove', 0, 1000);

                // Now that the ghost cmp has been created, let's get a ref to it.
                dom = Ext.getCmp('foo-ghost').el.dom;

                expect(c.owns(Ext.fly(dom))).toBe(true);

                jasmine.fireMouseEvent(dom, 'mouseup');
            });

            it('should inject a getRefOwner API that returns a reference to the combo', function() {
                dom = c.getPicker().header.el.dom;

                jasmine.fireMouseEvent(dom, 'mousedown');
                // Moving the dom element will trigger the ghost cmp.
                jasmine.fireMouseEvent(dom, 'mousemove', 0, 1000);

                expect(Ext.getCmp('foo-ghost').getRefOwner()).toBe(c);

                jasmine.fireMouseEvent(dom, 'mouseup');
            });

            it('should share the same reference between the picker and the ghost panel', function() {
                dom = c.getPicker().header.el.dom;

                jasmine.fireMouseEvent(dom, 'mousedown');
                // Moving the dom element will trigger the ghost cmp.
                jasmine.fireMouseEvent(dom, 'mousemove', 0, 1000);

                expect(Ext.getCmp('foo-ghost').getRefOwner()).toBe(panel.ownerCmp);

                jasmine.fireMouseEvent(dom, 'mouseup');
            });
        });
    });

    describe('EXTJS-15045', function() {
        function completeWithData(data) {
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.JSON.encode(data)
            });
        }

        beforeEach(function() {
            MockAjaxManager.addMethods();
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it('should allow mouse selection', function() {
            store = new Ext.data.Store({
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl'
                },
                model: CBTestModel
            });
            makeComponent({
                renderTo: Ext.getBody(),
                minChars: 0,
                valueField: 'val',
                queryDelay: 1
            });

            jasmine.focusAndWait(component);

            doTyping('t', false);
            completeWithData([
                { text: 'text 10', val: 'value 10' },
                { text: 'text 11', val: 'value 11' },
                { text: 'text 12', val: 'value 12' },
                { text: 'text 31', val: 'value 31' },
                { text: 'text 32', val: 'value 32' },
                { text: 'text 33', val: 'value 33' },
                { text: 'text 34', val: 'value 34' }
            ]);
            expect(component.getPicker().getNodes().length).toBe(7);

            doTyping('text 1', false);

            completeWithData([
                { text: 'text 10', val: 'value 10' },
                { text: 'text 11', val: 'value 11' },
                { text: 'text 12', val: 'value 12' }
            ]);
            expect(component.getPicker().getNodes().length).toBe(3);

            clickListItem('value 10');

            expect(component.getPicker().isVisible()).toBe(false);
            expect(component.getValue()).toBe('value 10');
        });
    });

    describe("getRecordDisplayData", function() {
        it("should call getRecordDisplayData to display the data", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                displayField: 'text',
                valueField: 'val',
                getRecordDisplayData: function(record) {
                    var data = Ext.apply({}, record.data);

                    data.text += 'foo';

                    return data;
                }
            });
            component.setValue('value 2');
            expect(component.getRawValue()).toBe('text 2foo');
            expect(store.getAt(1).get('text')).toBe('text 2');
        });
    });

    describe('readOnly', function() {
        describe('should not react to mutation events', function() {
            function runTest(expectation, method, cfg) {
                it(expectation, function() {
                    makeComponent(Ext.apply({
                        readOnly: true,
                        renderTo: Ext.getBody()
                    }, cfg));

                    spyOn(component, method);

                    // Trigger a cross-browser field mutation event.
                    jasmine.fireKeyEvent(component.inputEl.dom, 'keyup', 65);

                    // The trick here is that we need to ensure that the method isn't called for readOnly components.
                    // Since it's called on a delayed task, we'll need to use waits() here, unfortunately.
                    waits(10);

                    runs(function() {
                        expect(component[method].callCount).toBe(0);
                    });
                });
            }

            runTest('should not call checkChange', 'checkChange', {
                checkChangeBuffer: 0
            });

            runTest('should not query', 'doQuery', {
                queryDelay: 0,
                queryMode: 'local',
                value: 'Permanent Waves'
            });

            runTest('should not expand the picker', 'expand', {
                listConfig: {
                    emptyText: 'Exit... Stage Left'
                },
                queryDelay: 0,
                queryMode: 'local',
                value: 'Moving Pictures'
            });
        });
    });

    describe('checkValueOnChange triggered before store is loaded', function() {
        // EXTJS-16468
        it('should NOT clear the combobox value if setValueOnChange is triggered before the store is loaded', function() {
            var Color = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['name']
            }),
            panel = new Ext.panel.Panel({
                title: 'Combo test',
                renderTo: document.body,
                frame: true,
                height: 400,
                width: 600,
                items: [{
                    xtype: 'combobox',
                    fieldLabel: 'Chosen color',
                    queryMode: 'local',
                    forceSelection: true,
                    store: {
                        autoLoad: false,
                        model: Color,
                        proxy: {
                            type: 'memory',
                            data: [{
                                id: '0xff0000',
                                name: 'Red'
                            }, {
                                id: '0x00ff00',
                                name: 'Green'
                            }, {
                                id: '0x0000ff',
                                name: 'Blue'
                            }]
                        }
                    },
                    displayField: 'name',
                    valueField: 'name',
                    value: 'Red'
                }]
            }),
            comboBox = panel.child('combobox'),
            store = comboBox.getStore();

            // The configured value should still be there
            expect(comboBox.getValue()).toBe('Red');

            store.addFilter({
                property: 'name',
                value: 'Blue'
            });
            // Adding the filter should NOT trigger the value to be cleared due to it not being present in the store.
            // The store is not yet loaded, so it cannot do this; that must wait untilo the store is loaded.
            expect(comboBox.getValue()).toBe('Red');

            store.load();

            // AFTER the load, we are able to ascertain that the configured value is not in the store
            // (It's filtered out), so the value should be null.
            expect(comboBox.getValue()).toBe(null);

            panel.destroy();
        });
    });

    describe("complex binding", function() {
        var panel;

        afterEach(function() {
            if (panel) {
                panel.destroy();
                panel = null;
            }
        });

        var makePanel = function(autoLoad, differentFields, hasValue) {
            panel = new Ext.panel.Panel({
                renderTo: document.body,
                height: 400,
                width: 600,
                viewModel: {
                    stores: {
                        foo: {
                            autoLoad: autoLoad || false,
                            type: 'foo'
                        }
                    },
                    // ViewModel "bar" property is the selected foo *record*
                    data: {
                        bar: null
                    }
                },
                items: [{
                    xtype: 'combobox',
                    autoLoadOnValue: true,
                    queryMode: 'local',
                    forceSelection: true,
                    bind: {
                        store: '{foo}',
                        selection: '{bar}'
                    },
                    displayField: 'text',
                    valueField: differentFields ? 'val' : 'text',
                    value: hasValue ? (differentFields ? 'value 1' : 'text 1') : undefined
                }, {
                    itemId: 'target-comp',
                    xtype: 'component',
                    bind: {
                        data: '{bar}'
                    },
                    tpl: '{text}'
                }]
            });
        };

        it("should publish a selection when store provided by a bind is NOT autoloaded, value is configured and displayField === valueField", function() {
            makePanel(false, false, true);

            var targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is autoloaded, value is configured and displayField === valueField", function() {
            makePanel(true, false, true);

            var targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is NOT autoloaded, value is set post construction and displayField === valueField", function() {
            makePanel(false, false, false);

            var comboBox = panel.child('combobox'),
                targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            comboBox.setValue('text 1');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is autoloaded, value is set post construction and displayField === valueField", function() {
            makePanel(true, false, false);

            var comboBox = panel.child('combobox'),
                targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            comboBox.setValue('text 1');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is NOT autoloaded, value is configured and displayField !== valueField", function() {
            makePanel(false, true, true);

            var targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is autoloaded, value is configured and displayField !== valueField", function() {
            makePanel(true, true, true);

            var targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is NOT autoloaded, value is set post construction and displayField !== valueField", function() {
            makePanel(false, true, false);

            var comboBox = panel.child('combobox'),
                targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            comboBox.setValue('value 1');
            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should publish a selection when store provided by a bind is autoloaded, value is set post construction and displayField !== valueField", function() {
            makePanel(true, true, false);

            var comboBox = panel.child('combobox'),
                targetComp = panel.child('#target-comp'),
                vm = panel.getViewModel(),
                store = vm.get('foo');

            comboBox.setValue('value 1');

            vm.notify();

            expect(targetComp.el.dom.innerHTML).toBe('text 1');
            expect(vm.get('bar')).toBe(store.byText.get('text 1'));
        });

        it("should not collapse while typing and it matches a record", function() {
            var spy = jasmine.createSpy(),
                vm;

            makeComponent({
                queryMode: 'local',
                autoLoadOnValue: true,
                viewModel: {
                    data: {
                        name: null
                    }
                },
                bind: {
                    value: '{name}'
                },
                displayField: 'text',
                valueField: 'text',
                renderTo: Ext.getBody()
            });

            vm = component.getViewModel();
            component.on('collapse', spy);
            doTyping('Foo', false, true);
            vm.notify();
            expect(vm.get('name')).toBe('Foo');
            expect(spy).not.toHaveBeenCalled();
        });

        it("should not clear the combobox while typing and forceSelection is true", function() {
            var vm;

            makeComponent({
                queryMode: 'local',
                autoLoadOnValue: true,
                forceSelection: true,
                viewModel: {
                    data: {
                        address: 1
                    }
                },
                bind: {
                    value: '{address}'
                },
                displayField: 'text',
                valueField: 'id',
                renderTo: Ext.getBody()
            });

            vm = component.getViewModel();

            component.setValue(9);
            vm.notify();

            jasmine.focusAndWait(component);

            runs(function() {
                doTyping('t', false, true);
                vm.notify();

                expect(vm.get('address')).toBe(9);
                expect(component.inputEl.dom.value).toBe('t');
            });
        });

        it("should be able to set an Array value using binding while the field is focused", function() {
            var vm;

            makeComponent({
                queryMode: 'local',
                multiSelect: true,
                viewModel: {
                    data: {
                        foo: [1, 2]
                    }
                },
                bind: {
                    value: '{foo}'
                },
                displayField: 'text',
                valueField: 'id',
                renderTo: Ext.getBody()
            });

            vm = component.getViewModel();
            vm.notify();

            jasmine.focusAndWait(component);

            runs(function() {
                vm.set('foo', [1]);
                vm.notify();
                expect(component.getValue()).toEqual([1]);
            });
        });
    });

    describe("forceSelection: true", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: document.body,
                valueField: 'val',
                displayField: 'text',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true,
                queryCaching: false
            });
        });

        it("should not select the lastSelectedValue after a new value has been set", function() {
            jasmine.focusAndWait(component);

            runs(function() {
                // Should narrow down to 3, 31, 32 etc
                doTyping('text 3', false, true);
            });

            // Wait for the query timer to show the narrowed list
            waitsFor(function() {
                return component.picker && component.picker.isVisible() === true;
            }, 'picker to show');

            runs(function() {
                // Down to the '31' value
                jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.DOWN);

                // Select 31 and blur
                jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.TAB);
            });
            jasmine.blurAndWait(component);

            runs(function() {
                expect(component.getValue()).toBe('value 31');
                component.setValue('');
                component.inputEl.dom.focus();
            });

            jasmine.focusAndWait(component);

            runs(function() {
                // Do not select a record
                jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.TAB);
            });

            jasmine.blurAndWait(component);

            // The assertValue call on blur should NOT have imposed the last selected value
            // from the last time the field was used. The intervening setValue call clears it.
            runs(function() {
                expect(component.getValue()).toBeNull();
            });

        });
    });

    describe("destroy", function() {
        it("should not throw an exception when destroying on select", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                valueField: 'val'
            });
            component.on('select', function() {
                this.destroy();
            }, component);
            expect(function() {
                clickListItem('value 2');
            }).not.toThrow();
        });
    });

    describe("editable", function() {
        it("should expand on inputEl click when NOT editable", function() {
            makeComponent({
                renderTo: document.body,
                editable: false
            });

            // Not editable to begin with, should expand on inputEl Click
            jasmine.fireMouseEvent(component.inputEl, 'click');
            expect(component.isExpanded).toBe(true);
            component.collapse();

            component.setEditable(true);
            // Now it is editable, should NOT expand on inputEl Click
            jasmine.fireMouseEvent(component.inputEl, 'click');
            expect(component.isExpanded).toBe(false);

            component.setEditable(false);
            // Not edtable again, SHOULD expand
            jasmine.fireMouseEvent(component.inputEl, 'click');
            expect(component.isExpanded).toBe(true);
        });
    });

    describe("typeahead", function() {
        it("should not extend the raw value with typeahead on erase", function() {
            var indices;

            store.load();
            makeComponent({
                displayField: 'text',
                valueField: 'val',
                typeAhead: true,
                minChars: 2,
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            doTyping('tex');

            // The typeahead setting will extend the raw value with a text selection
            waitsFor(function() {
                return component.getRawValue() === 'text 1' && component.getTextSelection()[0] === 3;
            });

            runs(function() {
                // get initial selection indicies
                indices = component.getTextSelection();
                // The typeahead "t 1" should be selected - 3 to 6 *exclusive*
                expect(indices[0]).toBe(3);
                expect(indices[1]).toBe(6);

                // Erasing the selected typeahead chars "t 1", should not typeahead
                doTyping('tex', true);
            });

            // We are testing that nothing happens here, so we just have to wait.
            waits(100);

            // After the erasure, it must not have done typeahead.
            runs(function() {
                // Ensure no typeahead has been done
                expect(component.inputEl.dom.value.length).toBe(3);

                // Erasing the "x" chars "xt 1", should not typeahead
                doTyping('te', true);
            });

            // We are testing that nothing happens here, so we just have to wait.
            waits(100);

            // After the erasure, it must not have done typeahead.
            runs(function() {
                // Ensure no typeahead has been done
                expect(component.inputEl.dom.value.length).toBe(2);
            });
        });

        it("should clear any selected text after selection is made", function() {
            var indices;

            store.load();
            makeComponent({
                displayField: 'text',
                valueField: 'val',
                typeAhead: true,
                minChars: 2,
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
            doTyping('tex');

            // The typeahead setting will extend the raw value with a text selection
            waitsFor(function() {
                return component.getRawValue() === 'text 1' && component.getTextSelection()[0] === 3;
            });

            runs(function() {
                // get initial selection indicies
                indices = component.getTextSelection();
               // The typeahead "text 1" should be selected - 3 to 6 *exclusive*
                expect(indices[0]).toBe(3);
                expect(indices[1]).toBe(6);
                // type ahead is complete; select the correct record matched by the typeAhead
                component.picker.getSelectionModel().select([store.findRecord('text', 'text 1')]);
            });

            waitsFor(function() {
                return component.getTextSelection()[0] === 6;
            });

            runs(function() {
                // get indicies again
                indices = component.getTextSelection();
                // selection is done; check raw value
                expect(component.getRawValue()).toBe('text 1');
                // previous text selection should be cleared and cursor placed at the end of the raw value
                expect(indices[0]).toBe(6);
                expect(indices[1]).toBe(6);
            });
        });

        var testTypeAheadOnBlur = function(enableForceSelection) {
            it("should select the appropriate record on blur with forceSelection: " + enableForceSelection, function() {
                var spy = jasmine.createSpy();

                store.load();
                makeComponent({
                    displayField: 'text',
                    valueField: 'val',
                    typeAhead: true,
                    minChars: 2,
                    queryMode: 'local',
                    forceSelection: enableForceSelection,
                    renderTo: Ext.getBody()
                });

                component.on('select', spy);

                jasmine.focusAndWait(component);

                runs(function() {
                    doTyping('tex');
                });

                // The typeahead setting will extend the raw value with a text selection
                waitsFor(function() {
                    return component.getRawValue() === 'text 1';
                });

                jasmine.blurAndWait(component);

                runs(function() {
                    expect(component.getRawValue()).toBe('text 1');
                    expect(component.getValue()).toBe('value 1');
                    expect(spy.callCount).toBe(1);
                });
            });

            it("should not fire events if we did not change values (no value) with forceSelection: " + enableForceSelection, function() {
                var spy = jasmine.createSpy();

                store.load();
                makeComponent({
                    displayField: 'text',
                    valueField: 'val',
                    typeAhead: true,
                    minChars: 2,
                    queryMode: 'local',
                    forceSelection: enableForceSelection,
                    renderTo: Ext.getBody()
                });

                component.on('select', spy);

                jasmine.focusAndWait(component);

                runs(function() {
                    doTyping('tex');
                });

                // The typeahead setting will extend the raw value with a text selection
                waitsFor(function() {
                    return component.getRawValue() === 'text 1';
                });

                runs(function() {
                    doTyping('', true);
                });

                jasmine.blurAndWait(component);

                runs(function() {
                    expect(component.getValue()).toBeNull();
                    expect(spy.callCount).toBe(0);
                });
            });

            it("should not fire events if we did not change values (with value) with forceSelection: " + enableForceSelection, function() {
                var spy = jasmine.createSpy();

                store.load();
                makeComponent({
                    displayField: 'text',
                    valueField: 'val',
                    typeAhead: true,
                    minChars: 2,
                    queryMode: 'local',
                    value: 'value 1',
                    forceSelection: enableForceSelection,
                    renderTo: Ext.getBody()
                });

                component.on('select', spy);
                jasmine.focusAndWait(component);

                jasmine.blurAndWait(component);

                runs(function() {
                    expect(component.getValue()).toBe('value 1');
                    expect(spy.callCount).toBe(0);
                });
            });
        };

        testTypeAheadOnBlur(true);
        testTypeAheadOnBlur(false);
    });

    describe("collapse on scroll", function() {
        it("should collapse when the field scrolls out of view", function() {
            component = new Ext.panel.Panel({
                renderTo: document.body,
                title: 'Framed panel with normal child',
                width: 300,
                manageHeight: false,
                html: null,
                autoScroll: true,
                frame: true,
                layout: 'fit',
                items: [{
                    xtype: 'panel',
                    itemId: 'formPanel',
                    manageHeight: false,
                    height: 170,
                    autoScroll: true,
                    width: 100,
                    title: 'Non-framed child',
                    items: [{
                        xtype: 'textfield'
                    }, {
                        xtype: 'combobox',
                        itemId: 'combo1',
                        typeAhead: true,
                        triggerAction: 'all',
                        editable: true,
                        selectOnTab: true,

                        store: [
                            ['AA', 'AA'],
                            ['B Shady', 'B Shady'],
                            ['C or Shade', 'C or Shade'],
                            ['D Sunny', 'D Sunny'],
                            ['E', 'E']
                        ],
                        lazyRender: true
                    }, {
                        xtype: 'textfield'
                    }, {
                        xtype: 'textfield'
                    }, {
                        xtype: 'textfield'
                    }, {
                        xtype: 'textfield'
                    }, {
                        xtype: 'textfield'
                    }, {
                        xtype: 'combobox',
                        typeAhead: true,
                        triggerAction: 'all',
                        editable: true,
                        selectOnTab: true,
                        store: [
                            ['AA', 'AA'],
                            ['B Shady', 'B Shady'],
                            ['C or Shade', 'C or Shade'],
                            ['D Sunny', 'D Sunny'],
                            ['E', 'E']
                        ],
                        lazyRender: true
                    }]
                }]
            });
            var combo1 = component.down('#combo1'),
                formPanel = component.down('#formPanel'),
                scroller = formPanel.getScrollable();

            combo1.expand();
            scroller.scrollBy(0, 10);

            // Any scroll should cause the picker should be hidden
            waitsForEvent(combo1.getPicker(), 'hide');
        });
    });

    describe("misc tests", function() {
        beforeEach(function() {
            makeComponent({
                displayField: 'text',
                valueField: 'id',
                queryMode: 'local',
                renderTo: Ext.getBody()
            });
        });

        it("should set the value correctly when the display value contains unix line feeds", function() {
            var rec = store.first();

            rec.set('text', 'foo\nbar');
            clickListItem(1);
            expect(component.getSelection()).toBe(rec);
            expect(component.getValue()).toBe(1);
        });

        it("should set the value correctly when the display value contains windows line feeds", function() {
            var rec = store.first();

            rec.set('text', 'foo\r\nbar');
            clickListItem(1);
            expect(component.getSelection()).toBe(rec);
            expect(component.getValue()).toBe(1);
        });
    });

    // https://sencha.jira.com/browse/EXTJS-20322
    if ((Ext.supports.PointerEvents || Ext.supports.MSPointerEvents) && Ext.getScrollbarSize().width) {
        describe('Tapping on an item', function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: document.body,
                    listConfig: {
                        maxHeight: 50
                    }
                });
            });

            it("should select when tapping on an item", function() {
                var triggerEl = component.triggerEl.item(0),
                    boundList,
                    item,
                    x = triggerEl.getX() + triggerEl.getWidth() / 2,
                    y = triggerEl.getY() + triggerEl.getHeight() / 2;

                Ext.testHelper.tap(triggerEl, { x: x, y: y });

                boundList = component.getPicker();
                expect(boundList.isVisible()).toBe(true);
                component.collapse();
                expect(boundList.isVisible()).toBe(false);

                Ext.testHelper.tap(triggerEl, { x: x, y: y });
                expect(boundList.isVisible()).toBe(true);
                item = boundList.all.item(0);

                expect(boundList.getSelectionModel().getSelection().length).toBe(0);

                x = item.el.getX() + item.el.getWidth() / 2;
                y = item.el.getY() + item.el.getHeight() / 2;
                Ext.testHelper.tap(item.el, { x: x, y: y });

                expect(boundList.getSelectionModel().getSelection().length).toBe(1);
            });
        });
    }

    describe('ComboBox in a Window', function() {
        var testWin, combo;

        beforeEach(function() {
            testWin = Ext.create('Ext.window.Window', {
                title: 'combo popup still shown after resize or move',
                width: 350,
                height: 200,
                items: {
                    xtype: 'combobox',
                    padding: '10 10 10 10',
                    height: 15,
                    width: 200,
                    fieldLabel: 'Test Combo',
                    store: [1, 2, 3],
                    queryMode: 'local',
                    displayField: 'name',
                    valueField: 'abbr'
                }
            });
            testWin.show();
            combo = testWin.down('combobox');
        });
        afterEach(function() {
            testWin.destroy();
        });

        it('should hide popup when window dragged', function() {
            var offset = 5;

            combo.expand();
            expect(combo.picker.isVisible()).toBe(true);

            jasmine.fireMouseEvent(testWin.header.el, 'mouseover', offset, offset);
            testWin.el.dom.focus();
            jasmine.fireMouseEvent(testWin.header.el, 'mousedown', offset, offset);
            jasmine.fireMouseEvent(testWin.header.el, 'mousemove', 100, 0);

            waitsFor(function() {
                return combo.isExpanded === false && !combo.picker.pendingShow;
            });

            // Mouseup should not reshow
            runs(function() {
                jasmine.fireMouseEvent(testWin.header.el, 'mouseup');
                expect(combo.picker.isVisible()).toBe(false);
            });
        });
    });
});
