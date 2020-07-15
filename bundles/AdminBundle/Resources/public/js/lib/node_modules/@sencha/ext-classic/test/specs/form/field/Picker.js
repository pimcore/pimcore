topSuite("Ext.form.field.Picker",
    ['Ext.grid.Panel', 'Ext.grid.plugin.CellEditing', 'Ext.data.TreeStore',
     'Ext.tree.Panel', 'Ext.button.Button', 'Ext.window.Window'],
function() {
    var itNotIE8 = Ext.isIE8 ? xit : it,
        component, makeComponent;

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            Ext.applyIf(config, {
                ariaRole: 'foo',
                name: 'test',
                width: 300,
                hideEmptyLabel: false,
                // simple implementation
                createPicker: function() {
                    return new Ext.Component({
                        id: this.id + '-picker',
                        floatParent: this,
                        hidden: true,
                        focusable: true,
                        renderTo: Ext.getBody(),
                        floating: true,
                        html: 'foo'
                    });
                }
            });
            component = new Ext.form.field.Picker(config);
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    function clickTrigger(triggerName) {
        var trigger = component.getTrigger(triggerName || 'picker').el,
            xy = trigger.getXY();

        jasmine.fireMouseEvent(trigger.dom, 'click', xy[0], xy[1]);
    }

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should have matchFieldWidth = true", function() {
            expect(component.matchFieldWidth).toBe(true);
        });

        it("should have pickerAlign = 'tl-bl?'", function() {
            expect(component.pickerAlign).toEqual('tl-bl?');
        });

        it("should have pickerOffset = undefined", function() {
            expect(component.pickerOffset).not.toBeDefined();
        });

        describe("rendered", function() {
            beforeEach(function() {
                component.render(Ext.getBody());
            });

            it("should have aria-haspopup attribute", function() {
                expect(component).toHaveAttr('aria-haspopup', 'true');
            });

            it("should have aria-expanded attribute", function() {
                expect(component).toHaveAttr('aria-expanded', 'false');
            });
        });
    });

    describe("expand", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
        });

        it("should create the picker component", function() {
            expect(component.picker).not.toBeDefined();
            component.expand();
            expect(component.picker).toBeDefined();
            expect(component.picker instanceof Ext.Component).toBe(true);
        });

        it("should show the picker component", function() {
            component.expand();
            expect(component.picker.hidden).toBe(false);
        });

        it("should align the picker to the field", function() {
            component.expand();
            expect(component.picker.el.getX()).toEqual(component.bodyEl.getX());
        });

        it("should set the picker's width to the width of the field", function() {
            component.expand();
            expect(component.picker.getWidth()).toEqual(component.bodyEl.getWidth());
        });

        it("should not set the picker's width if matchFieldWidth is false", function() {
            Ext.destroy(component);

            makeComponent({
                renderTo: Ext.getBody(),
                matchFieldWidth: false
            });

            component.expand();
            expect(component.picker.getWidth()).not.toEqual(component.inputEl.getWidth());
        });

        it("should fire the 'expand' event", function() {
            var spy = jasmine.createSpy();

            component.on('expand', spy);
            component.expand();
            expect(spy).toHaveBeenCalledWith(component);
        });

        it("should call the onExpand method", function() {
//             makeComponent({
//                 renderTo: Ext.getBody(),
//                 onExpand: jasmine.createSpy()
//             });
            spyOn(component, 'onExpand');
            component.expand();
            expect(component.onExpand).toHaveBeenCalledWith();
        });

        it("should set aria-expanded to true", function() {
            component.expand();

            expect(component).toHaveAttr('aria-expanded', 'true');
        });

        it("should set aria-owns attribute if missing", function() {
            component.expand();

            expect(component).toHaveAttr('aria-owns', component.picker.el.id);
        });

        it("should not override existing aria-owns attribute", function() {
            component.ariaEl.dom.setAttribute('aria-owns', 'blerg zurg throbbe');
            component.expand();

            expect(component).toHaveAttr('aria-owns', 'blerg zurg throbbe');
        });

        // TODO
        xit("should monitor mousedown events on the document", function() { });
        xit("should monitor mousewheel events on the document", function() { });
    });

    describe("collapse", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            component.expand();
        });

        it("should hide the picker component", function() {
            component.collapse();
            expect(component.picker.hidden).toBe(true);
        });

        it("should fire the 'collapse' event", function() {
            var spy = jasmine.createSpy();

            component.on('collapse', spy);
            component.collapse();
            expect(spy).toHaveBeenCalledWith(component);
        });

        it("should call the onCollapse method", function() {
            spyOn(component, 'onCollapse');
            component.collapse();
            expect(component.onCollapse).toHaveBeenCalledWith();
        });

        it("should set aria-expanded to false", function() {
            component.collapse();

            expect(component).toHaveAttr('aria-expanded', 'false');
        });

        it("should not remove aria-owns", function() {
            component.collapse();

            expect(component).toHaveAttr('aria-owns', component.picker.el.id);
        });

        // TODO
        xit("should stop monitoring mousedown events on the document", function() { });
        xit("should stop monitoring mousewheel events on the document", function() { });
    });

    describe("trigger clicks", function() {
        describe("single trigger", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
            });

            it("should expand the picker if not already expanded", function() {
                spyOn(component, 'expand');
                clickTrigger();
                expect(component.expand).toHaveBeenCalled();
            });

            it("should collapse the picker if already expanded", function() {
                component.expand();
                spyOn(component, 'collapse');
                clickTrigger();
                expect(component.collapse).toHaveBeenCalled();
            });

            it("should not blur the field", function() {
                focusAndWait(component);

                runs(function() {
                    clickTrigger();
                });

                // In IE focus events are async, so we have to wait to make sure the
                // component did not lose focus as a result of the trigger click
                expectFocused(component);
            });

            it("should do nothing if the field is readOnly", function() {
                component.setReadOnly(true);
                spyOn(component, 'expand');
                clickTrigger();
                expect(component.expand).not.toHaveBeenCalled();
            });

            it("should do nothing if the field is disabled", function() {
                component.setDisabled(true);
                spyOn(component, 'expand');
                clickTrigger();
                expect(component.expand).not.toHaveBeenCalled();
            });

            // Touching the trigger should not focus
            if (!jasmine.supportsTouch) {
                it("should focus the input when field is not focused", function() {
                    clickTrigger();

                    expectFocused(component);
                });
            }

            // Touching the trigger should not focus
            if (!jasmine.supportsTouch) {
                itNotIE8("should focus the input when picker is focused before collapsing", function() {
                    var picker = component.getPicker();

                    runs(function() {
                        component.expand();
                        picker.el.dom.setAttribute('tabIndex', '-1');
                        picker.el.focus();
                    });

                    waitForFocus(picker);

                    runs(function() {
                        clickTrigger();
                    });

                    runs(function() {
                        expectFocused(component, true);
                    });
                });
            }
        });

        describe("multiple triggers", function() {
            var picker;

            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    triggers: {
                        clear: {
                            handler: Ext.emptyFn
                        }
                    }
                });

                picker = component.getPicker();
                picker.el.dom.setAttribute('tabIndex', -1);
            });

            afterEach(function() {
                picker = null;
            });

            describe("when picker is expanded and focused", function() {
                beforeEach(function() {
                    component.expand();
                    picker.el.focus();

                    waitForFocus(picker);
                });

                // Touching the trigger should not focus
                if (!jasmine.supportsTouch) {
                    itNotIE8("should focus the input when clicking picker trigger", function() {
                        clickTrigger();

                        runs(function() {
                            expectFocused(component, true);
                        });
                    });
                }

                it("should not focus the input when clicking clear trigger", function() {
                    clickTrigger('clear');

                    runs(function() {
                        expectFocused(picker, true);
                    });
                });
            });
        });
    });

    describe("keyboard access", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
        });

        function fireKey(key) {
            jasmine.fireKeyEvent(component.inputEl, 'keydown', key);
            jasmine.fireKeyEvent(component.inputEl, 'keypress', key);
        }

        it("should invoke the trigger click when the down key is pressed", function() {
            spyOn(component, "onTriggerClick").andCallThrough();
            fireKey(40);
            expect(component.onTriggerClick).toHaveBeenCalled();
        });

        it("should collapse the picker when the escape key is pressed", function() {
            spyOn(component, component.keyMap.ESC[0].handler).andCallThrough();
            fireKey(27);
            expect(component[component.keyMap.ESC[0].handler]).toHaveBeenCalled();
        });
    });

    describe("focus/blur", function() {
        var blurSpy, focusLeaveSpy, validateSpy, button;

        beforeEach(function() {
            blurSpy = jasmine.createSpy('blur event');
            focusLeaveSpy = jasmine.createSpy('focusleave event');
            validateSpy = jasmine.createSpy('validitychange event');

            makeComponent({
                renderTo: Ext.getBody(),
                allowBlank: false,
                listeners: {
                    blur: blurSpy,
                    focusleave: focusLeaveSpy,
                    validitychange: validateSpy
                },
                createPicker: function() {
                    return new Ext.Container({
                        hidden: true,
                        renderTo: Ext.getBody(),
                        floating: true,
                        html: 'foo',
                        defaultFocus: 'component',
                        items: [
                            {
                                xtype: 'component',
                                autoEl: 'a',
                                focusable: true,
                                tabIndex: 0
                            }
                        ]
                    });
                }
            });
        });

        afterEach(function() {
            blurSpy = focusLeaveSpy = validateSpy = null;
        });

        describe("blur event", function() {
            beforeEach(function() {
                button = new Ext.button.Button({
                    renderTo: document.body,
                    text: 'foo'
                });

                focusAndWait(component);
            });

            afterEach(function() {
                button = Ext.destroy(button);
            });

            describe("to other component", function() {
                it("should fire the blur event", function() {
                    runs(function() {
                        expect(blurSpy).not.toHaveBeenCalled();
                    });

                    focusAndWait(button);

                    runs(function() {
                        expect(blurSpy).toHaveBeenCalled();
                    });
                });

                it("should validate by default", function() {
                    focusAndWait(button);

                    runs(function() {
                        expect(validateSpy).toHaveBeenCalled();
                    });
                });

                it("should not validate when validateOnBlur is false", function() {
                    component.validateOnBlur = false;

                    focusAndWait(button);

                    runs(function() {
                        expect(validateSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("to the picker", function() {
                it("should fire the blur event", function() {
                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(blurSpy).toHaveBeenCalled();
                    });
                });

                it("should validate by default", function() {
                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(validateSpy).toHaveBeenCalled();
                    });
                });

                it("should not validate when validateOnBlur is false", function() {
                    component.validateOnBlur = false;
                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(validateSpy).not.toHaveBeenCalled();
                    });
                });
            });
        });

        describe("focusleave event", function() {
            beforeEach(function() {
                button = new Ext.button.Button({
                    renderTo: document.body,
                    text: 'foo'
                });

                focusAndWait(component);
            });

            afterEach(function() {
                button = Ext.destroy(button);
            });

            describe("to other component", function() {
                it("should fire the focusleave event", function() {
                    runs(function() {
                        expect(blurSpy).not.toHaveBeenCalled();
                        expect(focusLeaveSpy).not.toHaveBeenCalled();
                    });

                    focusAndWait(button);

                    runs(function() {
                        expect(blurSpy).toHaveBeenCalled();
                        expect(focusLeaveSpy).toHaveBeenCalled();
                    });
                });

                it("should validate by default", function() {
                    focusAndWait(button);

                    runs(function() {
                        expect(validateSpy).toHaveBeenCalled();
                    });
                });

                it("should validate when validateOnBlur is false but validateOnFocusLeave is true", function() {
                    component.validateOnBlur = false;
                    component.validateOnFocusLeave = true;

                    focusAndWait(button);

                    runs(function() {
                        expect(validateSpy).toHaveBeenCalled();
                    });
                });

                it("should not validate when both flags are false", function() {
                    component.validateOnBlur = false;
                    component.validateOnFocusLeave = false;

                    focusAndWait(button);

                    runs(function() {
                        expect(validateSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("to the picker", function() {
                it("should fire the blur event", function() {
                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(blurSpy).toHaveBeenCalled();
                    });
                });

                it("should not fire the focusleave event", function() {
                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(focusLeaveSpy).not.toHaveBeenCalled();
                    });
                });

                it("should validate by default", function() {
                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(validateSpy).toHaveBeenCalled();
                    });
                });

                it("should not validate when validateOnBlur is false", function() {
                    component.validateOnBlur = false;
                    component.validateOnFocusLeave = true;

                    component.expand();

                    focusAndWait(component.picker);

                    runs(function() {
                        expect(validateSpy).not.toHaveBeenCalled();
                    });
                });
            });
        });

        // What is this?
        xit("should re-focus the input if focus is lost due to a mousedown on the picker", function() {
            component.focus();
            component.expand();
            jasmine.fireMouseEvent(component.picker.el.dom, 'mousedown');
            expect(component.hasFocus).toBe(true);
            expect(Ext.Element.getActiveElement()).toBe(component.inputEl.dom);
            jasmine.fireMouseEvent(component.picker.el.dom, 'mouseup');
        });
    });

    // This test is too brittle to make it pass consistently in IE9m :(
    (Ext.isIE9m ? xdescribe : describe)('Using PickerField as a cell editor where the picker dropdown is itself an editable grid!', function() {
        var testWindow,
            staticField,
            TestModel,
            MyField;

        afterEach(function() {
            testWindow.destroy();
            staticField.destroy();
        });

        function triggerCellMouseEvent(view, type, rowIdx, cellIdx, button, x, y) {
            var target = view.getCellByPosition({
                row: rowIdx,
                column: cellIdx
            }, true);

            jasmine.fireMouseEvent(target, type, x, y, button);
        }

        function triggerCellKeyEvent(view, type, rowIdx, cellIdx, key) {
            var target = view.getCellByPosition({
                row: rowIdx,
                column: cellIdx
            }, true);

            jasmine.fireKeyEvent(target, type, key);
        }

        it('should work', function() {
            TestModel = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['id', 'text', {
                    name: 'value',
                    type: 'int'
                }]
            });

            MyField = Ext.define(null, {
                extend: 'Ext.form.field.Picker',

                createPicker: function() {
                    var me = this;

                    pickerCellEditing = new Ext.grid.plugin.CellEditing();
                    pickerGrid = new Ext.grid.Panel({
                        store: new Ext.data.Store({
                            fields: [{
                                type: 'int',
                                name: 'a'
                            }, 'b'],
                            data: [
                                { a: 123, b: '123 Text' },
                                { a: 456, b: '456 Text' },
                                { a: 789, b: '789 Text' }
                            ]
                        }),
                        floating: true,
                        width: 100,
                        plugins: pickerCellEditing,
                        columns: [{
                            text: 'Value',
                            width: 70,
                            editor: {
                                xtype: 'numberfield'
                            },
                            dataIndex: 'a',
                            resizable: false
                        }, {
                            text: 'Description',
                            dataIndex: 'b',
                            resizable: false,
                            flex: 1,
                            editor: {
                                xtype: 'textfield'
                            }
                        }],
                        selType: 'checkboxmodel',
                        selModel: {
                            checkOnly: true,
                            allowDeselect: false
                        },
                        listeners: {
                            selectionchange: function(sm, selections) {
                                if (selections.length) {
                                    me.setValue(selections[0].get('a'));
                                }
                            }
                        }
                    });

                    return pickerGrid;
                },
                setValue: function(v) {
                    var picker = this.getPicker(),
                        s = picker.getStore(),
                        r = s.findRecord('a', v);

                    if (r != null) {
                        picker.getSelectionModel().select(r, false, true);
                    }

                    this.callParent(arguments);
                },
                getValue: function() {
                    return parseInt(this.callParent());
                }
            });

            var pickerGrid,
                pickerCellEditing,
                pickerCellEditor,
                pickerEditorField,
                cellEditing = new Ext.grid.plugin.CellEditing(),
                cellEditor,
                editorField,
                treePanel = Ext.create('Ext.tree.Panel', {
                rowLines: true,
                columnLines: true,
                rootVisible: false,
                plugins: cellEditing,
                store: Ext.create('Ext.data.TreeStore', {
                    autoLoad: false,
                    model: TestModel,
                    root: {
                        id: 'root',
                        children: [{
                            id: 1,
                            text: 'Node 1',
                            value: 123,
                            children: [{ id: 11, text: 'Child Of Node 1', leaf: true, value: 456 }]
                        }, {
                            id: 2,
                            text: 'Node 2',
                            value: 123,
                            children: [{ id: 22, text: 'Child Of Node 2', leaf: true, value: 456 }]
                        }, {
                            id: 3,
                            text: 'Node 3',
                            value: 123,
                            children: [{ id: 33, text: 'Child Of Node 3', leaf: true, value: 456 }]
                        }, {
                            id: 4,
                            text: 'Node 4',
                            value: 123,
                            children: [{ id: 44, text: 'Child Of Node 4', leaf: true, value: 456 }]
                        }, {
                            id: 5,
                            text: 'Node 5',
                            value: 123,
                            children: [{ id: 55, text: 'Child Of Node 5', leaf: true, value: 456 }]
                        }, {
                            id: 6,
                            text: 'Node 6',
                            value: 123,
                            children: [{ id: 66, text: 'Child Of Node 6', leaf: true, value: 456 }]
                        }, {
                            id: 7,
                            text: 'Node 7',
                            value: 123,
                            children: [{ id: 77, text: 'Child Of Node 7', leaf: true, value: 456 }]
                        }, {
                            id: 8,
                            text: 'Node 8',
                            value: 123,
                            children: [{ id: 88, text: 'Child Of Node 8', leaf: true, value: 456 }]
                        }, {
                            id: 9,
                            text: 'Node 9',
                            value: 123,
                            children: [{ id: 99, text: 'Child Of Node 9', leaf: true, value: 456 }]
                        }]
                    }

                }),
                columns: [{
                    xtype: 'treecolumn',
                    dataIndex: 'id',
                    text: 'ID',
                    width: 170

                }, {
                    text: 'Second ',
                    dataIndex: 'text'
                }, {
                    text: 'Value',
                    flex: 1,
                    dataIndex: 'value',
                    editor: new MyField()
                }, {
                    text: 'Any Column 2',
                    width: 150
                }, {
                    text: 'Any Column 3',
                    width: 50
                }],
                bbar: [{
                    xtype: 'button',
                    text: 'To TOP ???',
                    tooltip: 'Go To SELECTED Record?'

                }]
            }),
            view;

            staticField = new Ext.form.field.Text({
                renderTo: document.body,
                fieldLabel: 'Test Field'
            });

            Ext.QuickTips.init();
            testWindow = Ext.create('Ext.window.Window', {
                layout: 'fit',
                autoShow: true,
                x: 100,
                y: 100,
                items: treePanel,
                title: "List...",
                height: 300,
                width: 700,
                listeners: {
                    boxready: function() {
                        view = treePanel.getView();
                        treePanel.expandAll();
                        view.focusRow(0);
                    }
                }
            });

            // The boxready listener focuses the first row
            waitsFor(function() {
                return view && Ext.Element.getActiveElement() === view.getCellByPosition({ row: 0, column: 0 }, true);
            }, 'the cell to be focused');

            runs(function() {
                // Start editing row 0, column 2
                triggerCellMouseEvent(view, 'dblclick', 0, 2);
                cellEditor = cellEditing.getActiveEditor();

                // We have a reference to the CellEditor
                expect(cellEditor != null).toBe(true);

                editorField = cellEditor.field;

                // The CellEditor's field is focused
                expect(Ext.Element.getActiveElement() === editorField.inputEl.dom).toBe(true);

                // Focus on a cell elsewhere in the TreeGrid.
                // Synthesized mousedowns do not move focus on some browsers.
                view.getNavigationModel().setPosition(0, 3);
            });

            // Wait for the blur to result from the click to hide the editor
            waitsFor(function() {
                return cellEditor.isVisible() === false;
            }, 'the CellEditor to be visible');

            runs(function() {
                // The edit should have been canceled, and focus should move to the clicked cell
                expect(Ext.Element.getActiveElement() === view.getCellByPosition({ row: 0, column: 3 }, true)).toBe(true);

                // Start editing row 0, column 2
                triggerCellMouseEvent(view, 'dblclick', 0, 2);
                expect(cellEditor.isVisible()).toBe(true);

                staticField.inputEl.dom.focus();
            });

            // Wait for the blur to result from the focus of the other field to hide the editor
            waitsFor(function() {
                return !cellEditor.isVisible();
            }, 'the CellEditor to be hidden');

            runs(function() {
                 // The edit should have been canceled, and focus should move to the document body
                expect(Ext.Element.getActiveElement() === staticField.inputEl.dom).toBe(true);

                // Start editing row 0, column 2
                triggerCellMouseEvent(view, 'dblclick', 0, 2);
                expect(cellEditor.isVisible()).toBe(true);
            });

            waitsFor(function() {
                return editorField.hasFocus;
            }, 'the CellEditor field to be focused');

            runs(function() {
                // Down arrow to show the picker
                jasmine.fireKeyEvent(editorField.inputEl.dom, 'keydown', Ext.event.Event.DOWN);
                expect(editorField.picker.isVisible()).toBe(true);
                staticField.inputEl.dom.focus();
            });

            // Wait for the focus to move to the static field, and the resulting focusleave to complete the edit, hide the editor and obviously its picker.
            waitsFor(function() {
                return Ext.Element.getActiveElement() === staticField.inputEl.dom &&
                       !cellEditor.isVisible() && !editorField.picker.isVisible();
            }, 'the CellEditor and its picker to be hidden and the external input field to be focused');

            // Now to open the editor in the editor's picker!
            runs(function() {
                 // The edit should have been canceled, and focus should move to the document body
                expect(Ext.Element.getActiveElement() === staticField.inputEl.dom).toBe(true);

                // Start editing row 0, column 2
                triggerCellMouseEvent(view, 'dblclick', 0, 2);
                expect(cellEditor.isVisible()).toBe(true);

                // Down arrow to show the picker
                jasmine.fireKeyEvent(editorField.inputEl.dom, 'keydown', Ext.event.Event.DOWN);
                expect(editorField.picker.isVisible()).toBe(true);

                // Start editing within the picker grid at row 0, column 2
                triggerCellMouseEvent(pickerGrid.getView(), 'dblclick', 0, 2);

                pickerCellEditor = pickerCellEditing.getActiveEditor();

                // We have a reference to the CellEditor
                expect(pickerCellEditor != null).toBe(true);

                pickerEditorField = pickerCellEditor.field;

                // The CellEditor's field is focused
                expect(Ext.Element.getActiveElement() === pickerEditorField.inputEl.dom);

                staticField.inputEl.dom.focus();
            });

            // Wait for the blur to result from the focus of the other field to hide the editor and obviously its picker
            waitsFor(function() {
                return !cellEditor.isVisible() && !editorField.picker.isVisible() && !pickerCellEditor.isVisible();
            }, 'the CellEditor, and its picker, and its picker\'s CellEditor to be hidden for the first time');

            // Now to open the editor in the editor's picker, and stop the edit by clicking on row 0, column 3
            runs(function() {
                 // The edit should have been canceled, and focus should move to the document body
                expect(Ext.Element.getActiveElement() === staticField.inputEl.dom).toBe(true);

                // Start editing row 0, column 2
                triggerCellMouseEvent(view, 'dblclick', 0, 2);
                expect(cellEditor.isVisible()).toBe(true);

                // Down arrow to show the picker
                jasmine.fireKeyEvent(editorField.inputEl.dom, 'keydown', Ext.event.Event.DOWN);
                expect(editorField.picker.isVisible()).toBe(true);

                // Start editing within the picker grid at row 0, column 2
                triggerCellMouseEvent(pickerGrid.getView(), 'dblclick', 0, 2);

                pickerCellEditor = pickerCellEditing.getActiveEditor();

                // We have a reference to the CellEditor
                expect(pickerCellEditor != null).toBe(true);

                pickerEditorField = pickerCellEditor.field;

                // The CellEditor's field is focused
                expect(Ext.Element.getActiveElement() === pickerEditorField.inputEl.dom);

                // Click elsewhere in the TreeGrid
                triggerCellMouseEvent(view, 'click', 0, 3);
            });

            // Wait for the blur to result from the focus of the other field to hide the editor and obviously its picker
            waitsFor(function() {
                return !cellEditor.isVisible() && !editorField.picker.isVisible() && !pickerCellEditor.isVisible();
            }, 'the CellEditor, and its picker, and its picker\'s CellEditor to be hidden for the second time');

            runs(function() {
                // The edit should have been canceled, and focus should move to the clicked cell
                expect(Ext.Element.getActiveElement() === view.getCellByPosition({ row: 0, column: 3 }, true)).toBe(true);
            });
        });
    });
});
