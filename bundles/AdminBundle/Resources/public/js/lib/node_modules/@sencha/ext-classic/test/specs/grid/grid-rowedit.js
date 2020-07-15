topSuite("grid-rowedit",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.grid.plugin.RowEditing',
     'Ext.form.field.ComboBox', 'Ext.form.FieldContainer', 'Ext.grid.column.Action',
     'Ext.form.field.Trigger'],
function() {
    var itNotIE8 = Ext.isIE8 ? xit : it;

    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var ENTER = 13,
                ESC = 27;

            var grid, view, scroller, store, plugin, editor, colRef,
                GridEventModel = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: [
                    'field1',
                    'field2',
                    'field3',
                    'field4',
                    'field5',
                    'field6',
                    'field7',
                    'field8',
                    'field9',
                    'field10'
                ]
            });

            function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireMouseEvent(target, type, x, y, button);
            }

            function triggerCellKeyEvent(type, rowIdx, cellIdx, key) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireKeyEvent(target, type, key);
            }

            function triggerEditorKey(key) {
                var target = plugin.getEditor().items.first().inputEl.dom;

                jasmine.fireKeyEvent(target, 'keydown', key);
                jasmine.fireKeyEvent(target, 'keyup', key);
                jasmine.fireKeyEvent(target, 'keypress', key);
            }

            function getRec(index) {
                return store.getAt(index);
            }

            function findCell(rowIdx, cellIdx) {
                return grid.getView().getCellInclusive({
                    row: rowIdx,
                    column: cellIdx
                }, true);
            }

            function startEdit(rec, column) {
                if (!rec || !rec.isModel) {
                    rec = store.getAt(rec || 0);
                }

                if (column == null) {
                    column = 0;
                }

                if (typeof column === 'number') {
                    // Ensure it's an editable column so that focus happens.
                    while (colRef[column] && !(colRef[column].getEditor && colRef[column].getEditor() && colRef[column].getEditor().focusable)) {
                        ++column;
                    }

                    // Found no editable columns.
                    // Editor will still display
                    // but will not focus
                    if (column === colRef.length) {
                        column = null;
                    }
                    else {
                        column = colRef[column];
                    }
                }

                plugin.startEdit(rec, column);

                if (column) {
                    waitsForFocus(plugin.context.column.getEditor());
                }
            }

            // Prevent validity from running on a delay
            function clearFormDelay() {
                plugin.getEditor().getForm().taskDelay = 0;
            }

            function getDefaultColumns(locked, cfg, count) {
                var columns = [],
                    i, colConfig;

                for (i = 1; i <= (count || 5); ++i) {
                    colConfig = Ext.apply({
                        text: 'F' + i,
                        dataIndex: 'field' + i,
                        field: {
                            xtype: 'textfield',
                            id: 'field' + i,
                            allowBlank: i !== 1
                        }
                    }, cfg);

                    // Columns 1 and 2 are locked if the locked config is true
                    if (locked && i < 3) {
                        colConfig.locked = true;
                    }

                    columns[i - 1] = new Ext.grid.column.Column(colConfig);
                }

                return columns;
            }

            // locked param as true means that columns 1 and 2 are locked
            function makeGrid(columns, pluginCfg, locked, gridCfg) {
                var data = [],
                    defaultCols = [],
                    hasCols,
                    i;

                if (!columns) {
                    hasCols = true;
                    colRef = defaultCols = getDefaultColumns(locked);
                }

                for (i = 1; i <= 10; ++i) {
                    data.push({
                        field1: i + '.' + 1,
                        field2: i + '.' + 2,
                        field3: i + '.' + 3,
                        field4: i + '.' + 4,
                        field5: i + '.' + 5,
                        field6: i + '.' + 6,
                        field7: i + '.' + 7,
                        field8: i + '.' + 8,
                        field9: i + '.' + 9,
                        field10: i + '.' + 10
                    });
                }

                if (gridCfg && gridCfg.store && gridCfg.store.isStore) {
                    store = gridCfg.store;
                }
                else {
                    store = new Ext.data.Store({
                        model: GridEventModel,
                        data: data
                    });
                }

                plugin = new Ext.grid.plugin.RowEditing(pluginCfg);

                grid = new Ext.grid.Panel(Ext.apply({
                    columns: columns || defaultCols,
                    store: store,
                    selType: 'cellmodel',
                    plugins: [plugin],
                    width: 1000,
                    height: 500,
                    bufferedRenderer: buffered,
                    viewConfig: {
                        mouseOverOutBuffer: 0
                    },
                    renderTo: Ext.getBody()
                }, gridCfg));

                if (!hasCols) {
                    colRef = grid.getColumnManager().getColumns();
                }

                view = grid.getView();
                scroller = view.getScrollable ? view.getScrollable() : grid.normalGrid.view.getScrollable();
                editor = plugin.getEditor();
            }

            afterEach(function() {
                plugin = editor = grid = store = view = Ext.destroy(grid, store);
                Ext.data.Model.schema.clear();
            });

            describe("resolveListenerScope", function() {
                it("should resolve the scope to the grid", function() {
                    var fooScope = {
                        someFn: function() {}
                    };

                    spyOn(fooScope, 'someFn');

                    makeGrid(null, {
                        listeners: {
                            'beforeedit': 'someFn'
                        }
                    });

                    grid.resolveSatelliteListenerScope = function() {
                        return fooScope;
                    };

                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(fooScope.someFn).toHaveBeenCalled();
                });
            });

            (Ext.isIE8 ? xdescribe : describe)("Editing in a locked grid", function() {
                beforeEach(function() {
                    makeGrid(null, null, true);
                });

                it('should move an editor from one side to another when a column is locked during editing', function() {
                    grid.setActionableMode(true, new Ext.grid.CellContext(grid.normalGrid.view).setPosition(0, 0));

                    var ed = colRef[2].getEditor();

                    // normal view context 0, 0 yields colRef[2] because 1st 2 coliumns are locked
                    focusAndWait(ed, undefined, 'column 0 editor to gain focus');

                    runs(function() {
                        // The editor should be in the right container
                        expect(ed.up('container') === plugin.editor.items.items[1]).toBe(true);

                        // Locking the first normal column should not throw error.
                        grid.lock(colRef[2]);
                    });

                    // Wait for async focusing to untangle.
                    focusAndWait(ed, undefined, 'focus to return to the editor field after the column was locked');

                    runs(function() {
                        expect(plugin.editing).toBe(true);

                        // The editor should now be in the left container
                        expect(ed.up('container') === plugin.editor.items.items[0]).toBe(true);
                    });
                });
            });

            describe("basic editing", function() {
                // https://sencha.jira.com/browse/EXTJS-18773
                it('should scroll a record that is outside the rendered block into view and edit it', function() {
                    makeGrid();
                    var data = [],
                        i;

                    for (i = 11; i <= 1000; ++i) {
                        data.push({
                            field1: i + '.' + 1,
                            field2: i + '.' + 2,
                            field3: i + '.' + 3,
                            field4: i + '.' + 4,
                            field5: i + '.' + 5,
                            field6: i + '.' + 6,
                            field7: i + '.' + 7,
                            field8: i + '.' + 8,
                            field9: i + '.' + 9,
                            field10: i + '.' + 10
                        });
                    }

                    store.add(data);
                    startEdit(900);

                    waitsFor(function() {
                        return plugin.editing === true &&
                               plugin.getEditor().isVisible() === true;
                    });
                });

                it("should trigger the edit on cell interaction", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.editing).toBe(true);
                    expect(plugin.getEditor().isVisible()).toBe(true);
                });

                it("should focus the field for the clicked cell", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 3);
                    expect(plugin.editing).toBe(true);
                    expect(plugin.getEditor().isVisible()).toBe(true);
                    var toFocus = plugin.getEditor().items.getAt(3);

                    jasmine.waitForFocus(toFocus);
                    jasmine.expectFocused(toFocus);
                });

                it("should be able to be trigger by record", function() {
                    makeGrid();
                    var rec = store.first();

                    startEdit(rec);

                    runs(function() {
                        expect(plugin.editing).toBe(true);
                        expect(plugin.getEditor().getRecord() === rec).toBe(true);
                    });
                });

                it("should trigger the first time when clicking a cell without a defined editor", function() {
                    Ext.destroy(grid, store);
                    Ext.data.Model.schema.clear();
                    makeGrid([{
                        dataIndex: 'field1',
                        editor: 'textfield'
                    }, {
                        dataIndex: 'field2'
                    }]);
                    triggerCellMouseEvent('dblclick', 0, 1);
                    expect(plugin.editing).toBe(true);
                    expect(plugin.getEditor().isVisible()).toBe(true);
                });

                it("should focus the first visible column if not passed", function() {
                    makeGrid();
                    var rec = store.first();

                    startEdit(rec);

                    runs(function() {
                        var toFocus = plugin.getEditor().items.getAt(0);

                        jasmine.waitForFocus(toFocus);
                        jasmine.expectFocused(toFocus);
                    });
                });

                it("should focus the first column that isn't a displayfield", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        field: 'displayfield'
                    }, {
                        dataIndex: 'field2',
                        field: 'displayfield'
                    }, {
                        dataIndex: 'field3',
                        field: 'displayfield'
                    }, {
                        dataIndex: 'field4',
                        field: 'textfield'
                    }]);
                    triggerCellMouseEvent('dblclick', 0, 0);
                    var toFocus = plugin.getEditor().items.getAt(3);

                    jasmine.waitForFocus(toFocus);
                    jasmine.expectFocused(toFocus);
                });

                it("should focus the passed column", function() {
                    makeGrid();
                    var rec = store.first();

                    plugin.startEdit(rec, colRef[2]);

                    runs(function() {
                        expect(plugin.editing).toBe(true);
                        expect(plugin.getEditor().getRecord() === rec).toBe(true);

                        var toFocus = plugin.getEditor().items.getAt(2);

                        jasmine.waitForFocus(toFocus);
                        jasmine.expectFocused(toFocus);
                    });
                });

                it("should scroll horizontally to display the field being edited", function() {
                    makeGrid(null, null, null, {
                        width: 300
                    });
                    var rec = store.first(),
                        x;

                    // this will scroll the grid all the way to the right.
                    view.scrollBy(300, 0);

                    // Wait until it's all synced up.
                    // Cannot call getPosition immediately because this kills partner synching.
                    // TOOD: Revisit when https://sencha.jira.com/browse/EXTJS-23182 is closed.
                    waitsFor(function() {
                        var viewX = view.getScrollable().getScrollElement().dom.scrollLeft;

                        return viewX >= 200 &&
                               view.grid.headerCt.getScrollable().getScrollElement().dom.scrollLeft === viewX;
                    });

                    runs(function() {
                        x = view.getScrollX();
                        plugin.startEdit(rec, colRef[4]);
                    });

                    // We fudge the scroll because it scrolls just the required editor's
                    // element *just* into view, and then syncs the view to match that.
                    runs(function() {
                        expect(view.getScrollX()).toBeApprox(x, 3);
                        plugin.cancelEdit();
                        // expects the grid not to scroll when cancelling the edit
                        expect(view.getScrollX()).toBeApprox(x, 3);
                        plugin.startEdit(rec, colRef[0]);
                    });

                    waitsFor(function() {
                        return view.getScrollX() <= 3;
                    });
                });

                it("should not be dirty when the field has values", function() {
                    makeGrid();
                    startEdit(store.first());

                    runs(function() {
                        expect(plugin.getEditor().isDirty()).toBe(false);
                    });
                });

                it("should commit changes with autoUpdate", function() {
                    makeGrid(null, {
                        autoUpdate: true
                    });

                    startEdit(0, 0);

                    runs(function() {
                        editor.activeField.setValue('foo');
                        expect(editor.isDirty()).toBe(true);
                    });

                    runs(function() {
                        startEdit(1, 1);
                    });

                    runs(function() {
                        expect(getRec(0).get('field1')).toBe('foo');
                        expect(editor.isDirty()).toBe(false);
                    });
                });

                it("should reset changes with autoCancel", function() {
                    makeGrid();

                    startEdit(0, 0);

                    runs(function() {
                        editor.activeField.setValue('bar');
                        expect(editor.isDirty()).toBe(true);
                    });

                    runs(function() {
                        startEdit(1, 1);
                    });

                    runs(function() {
                        expect(getRec(0).get('field1')).toBe('1.1');
                        expect(editor.isDirty()).toBe(false);
                    });
                });
            });

            describe("tabbing", function() {
                beforeEach(function() {
                    makeGrid();
                });

                describe("basic tabbing", function() {
                    it("should tab from F1 to F2", function() {
                        startEdit(0, 0);

                        runs(function() {
                            pressTabKey(editor.activeField, true);
                        });

                        waitForFocus(editor.items.getAt(1));

                        runs(function() {
                            expect(editor.activeField.getValue()).toBe('1.2');
                            expect(document.activeElement).toBe(editor.activeField.inputEl.dom);
                        });
                    });

                    it("should shift-tab from F2 to F1", function() {
                        startEdit(0, 1);

                        runs(function() {
                            pressTabKey(editor.activeField, false);
                        });

                        waitForFocus(editor.items.getAt(0));

                        runs(function() {
                            expect(editor.activeField.getValue()).toBe('1.1');
                            expect(document.activeElement).toBe(editor.activeField.inputEl.dom);
                        });
                    });
                });

                describe("wrapping over edges", function() {
                    it("should tab from F5 to F1", function() {
                        startEdit(0, 4);

                        runs(function() {
                            pressTabKey(editor.activeField, true);
                        });

                        waitForFocus(editor.items.getAt(0));

                        runs(function() {
                            expect(editor.activeField.getValue()).toBe('2.1');
                            expect(document.activeElement).toBe(editor.activeField.inputEl.dom);
                        });
                    });

                    it("should shift-tab from F1 to F5", function() {
                        startEdit(1, 0);

                        runs(function() {
                            pressTabKey(editor.activeField, false);
                        });

                        waitForFocus(editor.items.getAt(4));

                        runs(function() {
                            expect(editor.activeField.getValue()).toBe('1.5');
                            expect(document.activeElement).toBe(editor.activeField.inputEl.dom);
                        });
                    });
                });

                describe("wrapping over end rows", function() {
                    it("should wrap over to the first row when editing last row", function() {
                        var firstField, lastField;

                        startEdit(store.last(), colRef[4]);
                        firstField = plugin.getEditor().items.getAt(0);
                        lastField = plugin.getEditor().items.getAt(4);

                        runs(function() {
                            pressTabKey(lastField, true);
                        });

                        waitForFocus(firstField);

                        runs(function() {
                            expect(plugin.context.record).toBe(store.first());
                        });
                    });

                    it("should wrap over to the last row when editing first row", function() {
                        var firstField, lastField;

                        startEdit(store.first(), colRef[0]);
                        firstField = plugin.getEditor().items.getAt(0);
                        lastField = plugin.getEditor().items.getAt(4);

                        runs(function() {
                            pressTabKey(firstField, false);
                        });

                        waitForFocus(lastField);

                        runs(function() {
                            expect(plugin.context.record).toBe(store.last());
                        });
                    });
                });

                describe("with dirty values", function() {
                    describe("autoUpdate == false", function() {
                        it("should tab to Update button from last field", function() {
                            startEdit(0, 4);

                            runs(function() {
                                editor.activeField.setValue('blerg');
                                pressTabKey(editor.activeField, true);
                            });

                            waitsForFocus(editor.down('#update'));
                        });

                        it("should shift-tab to Update button from first field", function() {
                            startEdit(0, 0);

                            runs(function() {
                                editor.activeField.setValue('throbbe');
                                pressTabKey(editor.activeField, false);
                            });

                            waitsForFocus(editor.down('#update'));
                        });
                    });

                    describe("autoUpdate == true", function() {
                        it("should tab to the next row from the last field", function() {
                            startEdit(0, 4);

                            runs(function() {
                                editor.autoUpdate = true;
                                editor.autoCancel = false;
                                editor.activeField.setValue('zumbo');
                                pressTabKey(editor.activeField, true);
                            });

                            waitsForFocus(editor.items.getAt(0));
                        });

                        it("should shift-tab to the previous row from the first field", function() {
                            startEdit(1, 0);

                            runs(function() {
                                editor.autoUpdate = true;
                                editor.autoCancel = false;
                                editor.activeField.setValue('ghurl');
                                pressTabKey(editor.activeField, false);
                            });

                            expectFocused(editor.items.getAt(4));
                        });
                    });
                });
            });

            describe("field styling", function() {
                it("should apply field styles", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        field: {
                            xtype: 'textfield',
                            fieldStyle: 'text-transform: uppercase;'
                        }
                    }]);
                    startEdit(store.first());

                    runs(function() {
                        var field = plugin.getEditor().items.getAt(0);

                        expect(field.inputEl.getStyle('text-transform')).toBe('uppercase');
                    });
                });

                describe("with align: right", function() {
                    describe("with no field style", function() {
                        it("should align the field right", function() {
                            makeGrid([{
                                dataIndex: 'field1',
                                align: 'right',
                                field: 'textfield'
                            }]);
                            startEdit(store.first());

                            runs(function() {
                                var field = plugin.getEditor().items.getAt(0);

                                expect(field.inputEl.getStyle('text-align')).toBe('right');
                            });
                        });
                    });

                    describe("with a field style", function() {
                        describe("as a string", function() {
                            describe("with an existing value for text-align", function() {
                                it("should respect a configured value and keep other styles", function() {
                                    makeGrid([{
                                        dataIndex: 'field1',
                                        align: 'right',
                                        field: {
                                            xtype: 'textfield',
                                            fieldStyle: 'text-transform: uppercase; text-align: left;'
                                        }
                                    }]);
                                    startEdit(store.first());

                                    runs(function() {
                                        var field = plugin.getEditor().items.getAt(0);

                                        expect(field.inputEl.getStyle('text-align')).toBe('left');
                                        expect(field.inputEl.getStyle('text-transform')).toBe('uppercase');
                                    });
                                });
                            });

                            describe("with no value for text-align", function() {
                                it("should align the field right and keep other styles", function() {
                                    makeGrid([{
                                        dataIndex: 'field1',
                                        align: 'right',
                                        field: {
                                            xtype: 'textfield',
                                            fieldStyle: 'text-transform: uppercase'
                                        }
                                    }]);
                                    startEdit(store.first());

                                    runs(function() {
                                        var field = plugin.getEditor().items.getAt(0);

                                        expect(field.inputEl.getStyle('text-align')).toBe('right');
                                        expect(field.inputEl.getStyle('text-transform')).toBe('uppercase');
                                    });
                                });
                            });
                        });

                        describe("as an object", function() {
                            describe("with an existing value for text-align", function() {
                                it("should respect a configured hyphenated value and keep other styles", function() {
                                    makeGrid([{
                                        dataIndex: 'field1',
                                        align: 'right',
                                        field: {
                                            xtype: 'textfield',
                                            fieldStyle: {
                                                textTransform: 'uppercase',
                                                'text-align': 'left'
                                            }
                                        }
                                    }]);
                                    startEdit(store.first());

                                    runs(function() {
                                        var field = plugin.getEditor().items.getAt(0);

                                        expect(field.inputEl.getStyle('text-align')).toBe('left');
                                        expect(field.inputEl.getStyle('text-transform')).toBe('uppercase');
                                    });
                                });

                                it("should respect a configured camel cased value and keep other styles", function() {
                                    makeGrid([{
                                        dataIndex: 'field1',
                                        align: 'right',
                                        field: {
                                            xtype: 'textfield',
                                            fieldStyle: {
                                                textTransform: 'uppercase',
                                                textAlign: 'left'
                                            }
                                        }
                                    }]);
                                    startEdit(store.first());

                                    runs(function() {
                                        var field = plugin.getEditor().items.getAt(0);

                                        expect(field.inputEl.getStyle('text-align')).toBe('left');
                                        expect(field.inputEl.getStyle('text-transform')).toBe('uppercase');
                                    });
                                });
                            });

                            describe("with no value for text-align", function() {
                                it("should align the field right and keep other styles", function() {
                                    makeGrid([{
                                        dataIndex: 'field1',
                                        align: 'right',
                                        field: {
                                            xtype: 'textfield',
                                            fieldStyle: {
                                                textTransform: 'uppercase'
                                            }
                                        }
                                    }]);
                                    startEdit(store.first());

                                    runs(function() {
                                        var field = plugin.getEditor().items.getAt(0);

                                        expect(field.inputEl.getStyle('text-align')).toBe('right');
                                        expect(field.inputEl.getStyle('text-transform')).toBe('uppercase');
                                    });
                                });
                            });
                        });
                    });
                });
            });

           describe("positioning", function() {
                // For ticket 19330-5
                it("should position buttons correctly for the first row when content does not overflow", function() {
                    makeGrid();
                    var records = store.getRange();

                    records.shift();
                    store.remove(records);
                    // Only 1 record, not scrolling
                    startEdit();

                    runs(function() {
                        expect(plugin.getEditor()._buttonsOnTop).toBe(false);
                    });
                });

                it("should position buttons correctly for the first row when content does overflow", function() {
                    makeGrid();
                    startEdit();
                    runs(function() {
                        expect(plugin.getEditor()._buttonsOnTop).toBe(false);
                    });
                });
            });

            describe("scrolling while editing", function() {
                beforeEach(function() {
                    var data = [];

                   makeGrid([{
                        dataIndex: 'field1',
                        field: 'displayfield'
                    }, {
                        dataIndex: 'field2',
                        field: 'displayfield'
                    }, {
                        dataIndex: 'field3',
                        field: 'displayfield'
                    }, {
                        dataIndex: 'field4',
                        field: 'textfield',
                        sortable: true
                    }], {
                        clicksToMoveEditor: 1,
                        autoCancel: false
                    }, null, {
                        trailingBufferZone: 10,
                        leadingBufferZone: 10
                    });

                    for (var i = 11; i <= 100; ++i) {
                        data.push({
                            field1: i + '.' + 1,
                            field2: i + '.' + 2,
                            field3: i + '.' + 3,
                            field4: i + '.' + 4,
                            field5: i + '.' + 5,
                            field6: i + '.' + 6,
                            field7: i + '.' + 7,
                            field8: i + '.' + 8,
                            field9: i + '.' + 9,
                            field10: i + '.' + 10
                        });
                    }

                    store.insert(10, data);
                });

                it('should keep the editor active if scrolling out of view', function() {
                    startEdit();

                    waitsFor(function() {
                        return plugin.editing && plugin.getEditor().containsFocus;
                    });

                    // this will scroll the grid view down
                    // to a point where rows get de-rendered
                    // if the grid has a bufferedRenderer plugin
                    jasmine.waitsForScroll(view.getScrollable(), function(scroller, x, y) {
                        // Wait until a record begin edit is cached
                        // or verified if it is not a grid with bufferedRenderer
                        if (plugin.editor._cachedNode || !grid.bufferedRenderer) {
                            return true;
                        }

                        scroller.scrollBy(0, 100);
                    }, 'scroll to the bottom', 10000);

                    runs(function() {
                        // if this is a grid with bufferedRenderer
                        // the record editor should be hidden at Y = -400;
                        if (grid.bufferedRenderer) {
                            expect(plugin.editor.getLocalY()).not.toBe(0);
                        }
                    });

                    jasmine.waitsForScroll(view.getScrollable(), function(scroller, x, y) {
                        if (y === 0 && plugin.editor.getLocalY() === 0) {
                            return true;
                        }

                        scroller.scrollBy(0, -100);
                    }, 'view to scroll to top and RowEditor to reappear', 10000, 50);

                    runs(function() {
                        // the cached record should have been erased
                        // or it should never existed if this is not a grid with bufferedRenderer
                        // the editor also should not be hidden anymore
                        // and the editor editing status should still be true.
                        expect(plugin.editor._editedNode).toBeFalsy();
                        expect(plugin.editor.getLocalY()).toBe(0);
                        expect(plugin.editing).toBe(true);
                    });
                });

                it('should scroll to edited item if it is out of view and the column is sorted', function() {
                    var columns = grid.getColumns();

                    columns[3].sort();

                    startEdit();

                    runs(function() {
                        plugin.getEditor().items.items[3].setValue(99999999);
                        plugin.completeEdit();
                        expect(grid.getSelectionModel().getSelection()[0]).toEqual(plugin.context.record);
                    });
                });
            });

            // https://sencha.jira.com/browse/EXTJSIV-11364
            describe("destroying a grid before editing starts", function() {
                beforeEach(function() {
                    makeGrid([{
                        name: 'F0',
                        dataIndex: 'field0',
                        field: {
                            xtype: 'combobox',
                            id: 'field0',
                            initComponent: function() {
                                // The column will be removed at this point, and column.up will return undefined.
                                this.store = this.column.up('tablepanel').store.collect(this.column.dataIndex, false, true);
                                Ext.form.field.ComboBox.prototype.initComponent.apply(this, arguments);
                            }
                        }
                    }]);
                });

                it("should not throw an error", function() {
                    expect(function() {
                        grid.destroy();
                    }).not.toThrow();
                });
            });

            describe("setting widths", function() {
                function expectWidth(field, width) {
                    width -= field._marginWidth;
                    expect(field.getWidth()).toBe(width);
                }

                function expectWidths() {
                    runs(function() {
                        var items = plugin.getEditor().items;

                        items.each(function(item, index) {
                            expectWidth(item, colRef[index].getWidth());
                        });
                    });
                }

                it("should set fixed column widths", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        width: 100,
                        field: 'textfield'
                    }, {
                        dataIndex: 'field2',
                        width: 200,
                        field: 'textfield'
                    }]);
                    startEdit();
                    expectWidths();
                });

                it("should set flex widths", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        flex: 1,
                        field: 'textfield'
                    }, {
                        dataIndex: 'field2',
                        flex: 1,
                        field: 'textfield'
                    }]);
                    startEdit();
                    expectWidths();
                });

                it("should set width with a mix of flex/configured", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        flex: 1,
                        field: 'textfield'
                    }, {
                        dataIndex: 'field2',
                        width: 300,
                        field: 'textfield'
                    }, {
                        dataIndex: 'field3',
                        width: 400,
                        field: 'textfield'
                    }]);
                    startEdit();
                    expectWidths();
                });

                describe("reconfigure", function() {
                    it("should set the widths if the editor has not been shown", function() {
                        makeGrid();
                        grid.reconfigure(null, [{
                            dataIndex: 'field1',
                            width: 200,
                            field: 'textfield'
                        }, {
                            dataIndex: 'field2',
                            width: 300,
                            field: 'textfield'
                        }]);
                        colRef = grid.getColumnManager().getColumns();
                        startEdit();
                        expectWidths();
                    });

                    it("should set the widths after the editor has already been shown", function() {
                        makeGrid();
                        plugin.startEdit(store.first());

                        runs(function() {
                            plugin.cancelEdit();
                            grid.reconfigure(null, [{
                                dataIndex: 'field1',
                                width: 200,
                                field: 'textfield'
                            }, {
                                dataIndex: 'field2',
                                width: 300,
                                field: 'textfield'
                            }]);
                            colRef = grid.getColumnManager().getColumns();
                        });

                        runs(function() {
                            startEdit();
                            expectWidths();
                        });
                    });
                });
            });

            describe("events", function() {
                beforeEach(function() {
                    makeGrid();
                });
                describe("beforeedit", function() {
                    it("should fire the event", function() {
                        var called = false;

                        plugin.on('beforeedit', function() {
                            called = true;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(called).toBe(true);
                    });

                    it("should fire the event with the plugin & an event context", function() {
                        var p, context;

                        plugin.on('beforeedit', function(a1, a2) {
                            p = a1;
                            context = a2;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(p === plugin).toBe(true);
                        expect(context.colIdx).toBe(0);
                        expect(context.column === colRef[0]).toBe(true);
                        expect(context.grid === grid).toBe(true);
                        expect(context.record === getRec(0)).toBe(true);
                        expect(context.row === view.getRow(view.all.first())).toBe(true);
                        expect(context.rowIdx).toBe(0);
                        expect(context.store === store).toBe(true);
                    });

                    it("should prevent editing if false is returned", function() {
                        plugin.on('beforeedit', function(a1, a2) {
                            return false;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(plugin.editing).toBeFalsy();
                    });

                    it("should prevent editing if context.cancel is set", function() {
                        plugin.on('beforeedit', function(p, context) {
                            context.cancel = true;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(plugin.editing).toBeFalsy();
                    });
                });

                describe("canceledit", function() {
                    it("should fire the event when editing is cancelled", function() {
                        var called = false;

                        plugin.on('canceledit', function(p, context) {
                            called = true;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.cancelEdit();
                        expect(called).toBe(true);
                        expect(plugin.editing).toBe(false);
                    });

                    it("should pass the plugin and the context", function() {
                        var p, context;

                        plugin.on('canceledit', function(a1, a2) {
                            p = a1;
                            context = a2;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.cancelEdit();
                        expect(p === plugin).toBe(true);
                        expect(context.colIdx).toBe(0);
                        expect(context.column === colRef[0]).toBe(true);
                        expect(context.grid === grid).toBe(true);
                        expect(context.record === getRec(0)).toBe(true);
                        expect(context.row === view.getRow(view.all.first())).toBe(true);
                        expect(context.rowIdx).toBe(0);
                        expect(context.store === store).toBe(true);
                    });
                });

                describe("validateedit", function() {
                    it("should fire the validateedit event before edit", function() {
                        var calledFirst = false,
                            called = false;

                        plugin.on('validateedit', function() {
                            calledFirst = !called;
                        });
                        plugin.on('edit', function(p, context) {
                            calledFirst = !called;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.completeEdit();
                        expect(calledFirst).toBe(true);
                    });

                    it("should pass the plugin and the context", function() {
                        var p, context;

                        plugin.on('validateedit', function(a1, a2) {
                            p = a1;
                            context = a2;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.completeEdit();
                        expect(p === plugin).toBe(true);
                        expect(context.colIdx).toBe(0);
                        expect(context.column === colRef[0]).toBe(true);
                        expect(context.grid === grid).toBe(true);
                        expect(context.record === getRec(0)).toBe(true);
                        expect(context.row === view.getRow(view.all.first())).toBe(true);
                        expect(context.rowIdx).toBe(0);
                        expect(context.store === store).toBe(true);
                    });

                    it("should veto the completeEdit if we return false", function() {
                        var called = false;

                        plugin.on('validateedit', function() {
                            return false;
                        });
                        plugin.on('edit', function(p, context) {
                            called = true;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.completeEdit();
                        expect(plugin.editing).toBe(true);
                        expect(called).toBe(false);
                    });

                    it("should veto the completeEdit if we set context.cancel", function() {
                        var called = false;

                        plugin.on('validateedit', function(p, context) {
                            context.cancel = true;
                        });
                        plugin.on('edit', function(p, context) {
                            called = true;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.completeEdit();
                        expect(plugin.editing).toBe(true);
                        expect(called).toBe(false);
                    });
                });

                describe("edit", function() {
                    it("should fire the edit event", function() {
                        var called = false;

                        plugin.on('edit', function(p, context) {
                            called = true;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.completeEdit();
                        expect(plugin.editing).toBe(false);
                        expect(called).toBe(true);
                    });

                    it("should pass the plugin and the context", function() {
                        var p, context;

                        plugin.on('edit', function(a1, a2) {
                            p = a1;
                            context = a2;
                        });
                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.completeEdit();
                        expect(p === plugin).toBe(true);
                        expect(context.colIdx).toBe(0);
                        expect(context.column === colRef[0]).toBe(true);
                        expect(context.grid === grid).toBe(true);
                        expect(context.record === getRec(0)).toBe(true);
                        expect(context.row === view.getRow(view.all.first())).toBe(true);
                        expect(context.rowIdx).toBe(0);
                        expect(context.store === store).toBe(true);
                    });

                    it("should update the value in the model", function() {

                        triggerCellMouseEvent('dblclick', 0, 0);
                        plugin.getEditor().items.first().setValue('foo');
                        plugin.completeEdit();
                        expect(getRec(0).get('field1')).toBe('foo');
                    });
                });
            });

            describe("dynamic editors", function() {
                beforeEach(function() {
                    // Suppress console warning about Trigger field being deprecated
                    spyOn(Ext.log, 'warn');
                });

                it("should allow the editor to change dynamically", function() {
                    var field = new Ext.form.field.Trigger();

                    makeGrid();
                    colRef[0].setEditor(field);
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first() === field).toBe(true);
                });

                it("should allow the editor to change in the beforeedit event", function() {
                    var field = new Ext.form.field.Trigger();

                    makeGrid();
                    plugin.on('beforeedit', function() {
                        colRef[0].setEditor(field);
                    });
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first() === field).toBe(true);
                });

                it("should correct the width when setting the editor during beforeedit after rendering", function() {
                    var field = new Ext.form.field.Trigger();

                    makeGrid([{
                        width: 500,
                        field: 'textfield'
                    }]);
                    triggerCellMouseEvent('dblclick', 0, 0);
                    plugin.cancelEdit();
                    plugin.on('beforeedit', function() {
                        colRef[0].setEditor(field);
                    });
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first().getWidth()).toBe(500);
                });

                it("should allow us to set an editor if one wasn't there before", function() {
                    var field = new Ext.form.field.Text();

                    makeGrid([{
                        dataIndex: 'field1'
                    }, {
                        dataIndex: 'field2',
                        field: 'textfield'
                    }]);
                    colRef = grid.getColumnManager().getColumns();
                    colRef[0].setEditor(field);
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first() === field).toBe(true);
                });

                it("should allow us to clear out an editor", function() {
                    makeGrid();
                    colRef[0].setEditor(null);
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first().getXType()).toBe('displayfield');
                });

                it("should destroy the old field", function() {
                    var field = new Ext.form.field.Text();

                    makeGrid([{
                        dataIndex: 'field1',
                        field: field
                    }]);
                    colRef = grid.getColumnManager().getColumns();
                    colRef[0].setEditor(new Ext.form.field.Text());
                    expect(field.destroyed).toBe(true);
                });
            });

            describe("hidden columns", function() {
                beforeEach(function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        hidden: true,
                        field: 'textfield'
                    }, {
                        dataIndex: 'field2',
                        field: 'textfield'
                    }, {
                        dataIndex: 'field3',
                        field: 'textfield'
                    }, {
                        dataIndex: 'field4',
                        hidden: true,
                        field: 'textfield'
                    }, {
                        dataIndex: 'field5',
                        field: 'textfield'
                    }, {
                        dataIndex: 'field6',
                        field: 'textfield'
                    }, {
                        dataIndex: 'field7',
                        field: 'textfield'
                    }]);
                    colRef = grid.getColumnManager().getColumns();
                });

                it("should not show editors for hidden columns", function() {
                    triggerCellMouseEvent('dblclick', 0, 1);
                    expect(plugin.getEditor().items.getAt(0).isVisible()).toBe(false);
                    expect(plugin.getEditor().items.getAt(3).isVisible()).toBe(false);
                });

                it("should focus the first visible field", function() {
                    startEdit();

                    runs(function() {
                        var toFocus = plugin.getEditor().items.getAt(1);

                        jasmine.waitForFocus(toFocus);
                        jasmine.expectFocused(toFocus);
                    });
                });

                it("should show the editor when the column is shown", function() {
                    startEdit();

                    runs(function() {
                        colRef[0].show();
                        expect(plugin.getEditor().items.getAt(0).isVisible()).toBe(true);
                    });
                });

                it("should hide the editor when the column is hidden", function() {
                    startEdit();

                    runs(function() {
                        colRef[6].show();
                        expect(plugin.getEditor().items.getAt(6).isVisible()).toBe(true);
                    });
                });
            });

            describe("reconfigure", function() {
                var old;

                describe("original editor not rendered", function() {
                    beforeEach(function() {
                        makeGrid();
                        old = [];
                        Ext.Array.forEach(grid.getColumnManager().getColumns(), function(col) {
                            old.push(col.getEditor());
                        });
                        grid.reconfigure(null, [{
                            dataIndex: 'field1',
                            field: {
                                id: 'newEd'
                            }
                        }, {
                            dataIndex: 'field2'
                        }]);
                        colRef = grid.getColumnManager().getColumns();
                    });

                    it("should destroy old editors", function() {
                        Ext.Array.forEach(old, function(item) {
                            expect(item.destroyed).toBe(true);
                        });
                    });

                    it("should update columns with no editors", function() {
                        triggerCellMouseEvent('dblclick', 0, 1);
                        expect(plugin.getEditor().items.getAt(1).getXType()).toBe('displayfield');
                    });

                    it("should use new editors", function() {
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(plugin.getEditor().items.first().getItemId()).toBe('newEd');
                    });
                });

                describe("original rendered", function() {
                    it("should cancel editing on reconfigure if visible", function() {
                        makeGrid();
                        triggerCellMouseEvent('dblclick', 0, 0);
                        grid.reconfigure(null, [{
                            dataIndex: 'field1'
                        }]);
                        expect(plugin.editing).toBe(false);
                        expect(plugin.getEditor().isVisible()).toBe(false);
                    });
                });
            });

            describe("values/validity", function() {
                it("should set the values on each field", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 0);
                    var items = plugin.getEditor().items;

                    expect(items.getAt(0).getValue()).toBe('1.1');
                    expect(items.getAt(1).getValue()).toBe('1.2');
                    expect(items.getAt(2).getValue()).toBe('1.3');
                    expect(items.getAt(3).getValue()).toBe('1.4');
                    expect(items.getAt(4).getValue()).toBe('1.5');
                });

                it("should set the correct value if the field name config is specified", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        field: {
                            xtype: 'textfield',
                            name: 'field2'
                        }
                    }]);

                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first().getValue()).toBe('1.2');
                });

                it("should not retain the value from previous edits if the model value is not defined", function() {
                    makeGrid();
                    startEdit(0);
                    var field = plugin.getEditor().items.getAt(0);

                    runs(function() {
                        expect(field.getValue()).toBe('1.1');
                        plugin.completeEdit();
                        store.insert(0, {});
                    });

                    runs(function() {
                        startEdit(0);
                        expect(field.getValue()).toBe('');
                    });
                });

                describe("buttons", function() {
                    beforeEach(function() {
                        makeGrid();
                    });

                    it("should disable the buttons when starting editing in an invalid state", function() {
                        store.first().set('field1', '');
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(plugin.getEditor().down('#update').disabled).toBe(true);
                    });

                    it("should disable the buttons when a value changes the form to a invalid state", function() {
                        triggerCellMouseEvent('dblclick', 0, 0);
                        clearFormDelay();
                        plugin.getEditor().items.first().setValue('');
                        expect(plugin.getEditor().down('#update').disabled).toBe(true);
                    });

                    it("should enable the buttons when a value changes the form to a valid state", function() {
                        store.first().set('field1', '');
                        triggerCellMouseEvent('dblclick', 0, 0);
                        clearFormDelay();
                        plugin.getEditor().items.first().setValue('Foo');
                        expect(plugin.getEditor().down('#update').disabled).toBe(false);
                    });

                    it("should update the state correctly after loading multiple records", function() {
                        store.removeAll();
                        store.insert(0, {});
                        startEdit(0);

                        runs(function() {
                            clearFormDelay();
                            plugin.getEditor().items.first().setValue('Foo');
                            plugin.completeEdit();
                            store.insert(0, {});
                        });

                        runs(function() {
                            startEdit(0);
                            plugin.getEditor().items.first().setValue('Foo');
                            expect(plugin.getEditor().down('#update').disabled).toBe(false);
                        });
                    });
               });

               describe("tooltip", function() {
                   beforeEach(function() {
                        makeGrid();
                    });

                    it("should show the tip when starting editing in an invalid state", function() {
                        store.first().set('field1', '');
                        triggerCellMouseEvent('dblclick', 0, 0);
                        expect(plugin.getEditor().tooltip.isVisible()).toBe(true);
                    });

                    it("should show the tip when a value changes the form to a invalid state", function() {
                        triggerCellMouseEvent('dblclick', 0, 0);
                        clearFormDelay();
                        var editor = plugin.getEditor();

                        editor.items.first().setValue('');
                        expect(editor.tooltip.isVisible()).toBe(true);
                    });

                    it("should hide the tip when a value changes the form to a valid state", function() {
                        store.first().set('field1', '');
                        triggerCellMouseEvent('dblclick', 0, 0);
                        clearFormDelay();
                        var editor = plugin.getEditor();

                        editor.items.first().setValue('Foo');
                        expect(editor.tooltip.isVisible()).toBe(false);
                    });

                    describe("tip content", function() {
                        function expectErrors(errors) {
                            // Parse out the markup here
                            var editor = plugin.getEditor(),
                                tipErrors = editor.tooltip.getEl().query('.' + editor.errorCls),
                                len = tipErrors.length,
                                i, html, error;

                            expect(len).toBe(errors.length);

                            for (i = 0; i < len; ++i) {
                                html = tipErrors[i].innerHTML;
                                error = errors[i];
                                expect(html).toBe(error);
                            }
                        }

                        it("should update the tip content with errors for each field", function() {
                            store.first().set('field2', '');
                            var editor = plugin.getEditor(),
                                f1 = editor.items.getAt(0),
                                f2 = editor.items.getAt(1),
                                f3 = editor.items.getAt(2);

                            f1.minLength = 100;
                            f2.allowBlank = false;
                            f3.maxLength = 1;

                            triggerCellMouseEvent('dblclick', 0, 0);
                            clearFormDelay();
                            expectErrors([
                                'F1: ' + f1.getErrors()[0],
                                'F2: ' + f2.getErrors()[0],
                                'F3: ' + f3.getErrors()[0]
                            ]);
                        });

                        it("should add a new field if it becomes invalid", function() {
                            var editor = plugin.getEditor(),
                                f1 = editor.items.getAt(0),
                                f2 = editor.items.getAt(1),
                                f3 = editor.items.getAt(2);

                            f1.minLength = 100;
                            f2.allowBlank = false;
                            f3.maxLength = 1;

                            triggerCellMouseEvent('dblclick', 0, 0);
                            clearFormDelay();
                            expectErrors([
                                'F1: ' + f1.getErrors()[0],
                                'F3: ' + f3.getErrors()[0]
                            ]);
                            f2.setValue('');
                            expectErrors([
                                'F1: ' + f1.getErrors()[0],
                                'F2: ' + f2.getErrors()[0],
                                'F3: ' + f3.getErrors()[0]
                            ]);
                        });

                        it("should remove a existing field if it becomes valid", function() {
                            store.first().set('field2', '');
                            var editor = plugin.getEditor(),
                                f1 = editor.items.getAt(0),
                                f2 = editor.items.getAt(1),
                                f3 = editor.items.getAt(2);

                            f1.minLength = 100;
                            f2.allowBlank = false;
                            f3.maxLength = 1;

                            triggerCellMouseEvent('dblclick', 0, 0);
                            clearFormDelay();
                            expectErrors([
                                'F1: ' + f1.getErrors()[0],
                                'F2: ' + f2.getErrors()[0],
                                'F3: ' + f3.getErrors()[0]
                            ]);
                            f2.setValue('Foo');
                            expectErrors([
                                'F1: ' + f1.getErrors()[0],
                                'F3: ' + f3.getErrors()[0]
                            ]);
                        });

                        it("should only render the first error from the field", function() {
                            var editor = plugin.getEditor(),
                                f1 = editor.items.getAt(0);

                            f1.getErrors = function() {
                                return ['Foo', 'Bar'];
                            };

                            triggerCellMouseEvent('dblclick', 0, 0);
                            clearFormDelay();
                            expectErrors(['F1: Foo']);
                        });

                        it("should not prefix the error with the column name if the text is empty", function() {
                            var editor = plugin.getEditor(),
                                f1 = editor.items.getAt(0),
                                f2 = editor.items.getAt(1);

                            f1.getErrors = function() {
                                return ['Foo'];
                            };

                            f2.getErrors = function() {
                                return ['Bar'];
                            };

                            colRef[0].text = '';
                            triggerCellMouseEvent('dblclick', 0, 0);
                            clearFormDelay();
                            expectErrors(['Foo', 'F2: Bar']);
                        });
                    });
               });
            });

            describe("field types", function() {
                it("should set values with a fieldcontainer", function() {
                    makeGrid([{
                        dataIndex: 'field1',
                        field: {
                            xtype: 'fieldcontainer',
                            items: {
                                name: 'field1',
                                xtype: 'textfield'
                            }
                        }
                    }]);
                    triggerCellMouseEvent('dblclick', 0, 0);
                    expect(plugin.getEditor().items.first().items.first().getValue()).toBe('1.1');
                });
            });

            describe("key events", function() {
                it("should cancel editing on ESC", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 0);
                    triggerEditorKey(ESC);
                    expect(plugin.editing).toBe(false);
                });

                it("should complete on edit if the form is valid", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 0);
                    plugin.getEditor().items.first().setValue('Foo');
                    triggerEditorKey(ENTER);
                    expect(plugin.editing).toBe(false);
                    expect(store.first().get('field1')).toBe('Foo');
                });

                it("should not finish editing if enter is pressed and the form is invalid", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 0);
                    // First doesn't allow blank
                    plugin.getEditor().items.first().setValue('');
                    triggerEditorKey(ENTER);
                    expect(plugin.editing).toBe(true);
                });

                it("should be able to cancel after pressing enter and the form is invalid", function() {
                    makeGrid();
                    triggerCellMouseEvent('dblclick', 0, 0);
                    // First doesn't allow blank
                    plugin.getEditor().items.first().setValue('');
                    triggerEditorKey(ENTER);
                    triggerEditorKey(ESC);
                    expect(plugin.editing).toBe(false);
                    expect(store.first().get('field1')).toBe('1.1');
                });
            });

            describe("adding/removing/moving columns", function() {
                function dragColumn(from, to, onRight) {
                    var fromBox = from.titleEl.getBox(),
                        fromMx = fromBox.x + fromBox.width / 2,
                        fromMy = fromBox.y + fromBox.height / 2,
                        toBox = to.titleEl.getBox(),
                        toMx = onRight ? toBox.right - 10 : toBox.left + 10,
                        toMy = toBox.y + toBox.height / 2,
                        dragThresh = onRight ? Ext.dd.DragDropManager.clickPixelThresh + 1 : -Ext.dd.DragDropManager.clickPixelThresh - 1;

                    // Mousedown on the header to drag
                    jasmine.fireMouseEvent(from.el.dom, 'mouseover', fromMx, fromMy);
                    jasmine.fireMouseEvent(from.titleEl.dom, 'mousedown', fromMx, fromMy);

                    // The initial move which tiggers the start of the drag
                    jasmine.fireMouseEvent(from.el.dom, 'mousemove', fromMx + dragThresh, fromMy);

                    // The move to left of the centre of the target element
                    jasmine.fireMouseEvent(to.el.dom, 'mousemove', toMx, toMy);

                    // Drop to left of centre of target element
                    jasmine.fireMouseEvent(to.el.dom, 'mouseup', toMx, toMy);
                }

                describe("modification while visible with the grid pending layouts", function() {
                    it("should update the layout appropriately", function() {
                        makeGrid();
                        startEdit();

                        runs(function() {
                            var editor = plugin.getEditor(),
                                count = editor.componentLayoutCounter;

                            dragColumn(colRef[3], colRef[0], false);
                            expect(editor.componentLayoutCounter).toBe(count + 1);
                        });
                    });
                });

                function createLockingSuite(beforeFirstShow) {
                    describe(beforeFirstShow ? "before first show" : "after first show", function() {
                        describe("basic operations", function() {
                            beforeEach(function() {
                                makeGrid();
                            });

                            it("should add a new field", function() {
                                if (!beforeFirstShow) {
                                    startEdit();
                                }

                                runs(function() {
                                    grid.headerCt.add({
                                        dataIndex: 'field6',
                                        field: {
                                            xtype: 'textfield',
                                            id: 'field6'
                                        }
                                    });

                                    if (beforeFirstShow) {
                                        startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().getComponent('field6').getValue()).toBe('1.6');
                                });
                            });

                            it("should remove an existing field & destroy it", function() {
                                if (!beforeFirstShow) {
                                    startEdit();
                                }

                                runs(function() {
                                    grid.headerCt.remove(1);

                                    if (beforeFirstShow) {
                                        startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().getComponent('field2')).toBeFalsy();
                                    expect(Ext.getCmp('field2')).toBeFalsy();
                                });
                            });

                            it("should move the field in the editor", function() {
                                if (!beforeFirstShow) {
                                    startEdit();
                                }

                                runs(function() {
                                    grid.headerCt.move(0, 5);

                                    if (beforeFirstShow) {
                                        startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().items.last().getItemId()).toBe('field1');
                                });
                            });

                            it("should remove all fields", function() {
                                if (!beforeFirstShow) {
                                    startEdit();
                                }

                                runs(function() {
                                    grid.headerCt.removeAll();

                                    if (beforeFirstShow) {
                                        startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().items.getCount()).toBe(0);
                                });
                            });
                        });

                        describe("moving with hidden columns", function() {
                            // The purpose here is to simulate user drag drops
                            function makeColumns(columns) {
                                var len = columns.length,
                                    out = [],
                                    i;

                                for (i = 1; i <= len; ++i) {
                                    out.push({
                                        name: 'F' + i,
                                        dataIndex: 'field' + i,
                                        hidden: columns[i],
                                        field: {
                                            xtype: 'textfield',
                                            id: 'field' + i
                                        }
                                    });
                                }

                                return out;
                            }

                            it("should insert after the first hidden column", function() {
                                makeGrid(makeColumns([true, false, false, false, false]));

                                if (!beforeFirstShow) {
                                   startEdit();
                                }

                                runs(function() {
                                    // insert before the first visible column
                                    grid.headerCt.move(4, 1);

                                    if (beforeFirstShow) {
                                       startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().items.getAt(1).getItemId()).toBe('field5');
                                });
                            });

                            it("should insert before the last hidden column", function() {
                                makeGrid(makeColumns([false, false, false, false, true]));

                                if (!beforeFirstShow) {
                                   startEdit();
                                }

                                runs(function() {
                                    // insert after the last visible column
                                    grid.headerCt.move(0, 3);

                                    if (beforeFirstShow) {
                                       startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().items.getAt(3).getItemId()).toBe('field1');
                                });
                            });

                            it("should position properly with hidden columns in the middle", function() {
                                makeGrid(makeColumns([false, false, true, false, true, true, false]));

                                if (!beforeFirstShow) {
                                   startEdit();
                                }

                                runs(function() {
                                    grid.headerCt.move(0, 3);
                                    grid.headerCt.insert(1, colRef[6]);

                                    if (beforeFirstShow) {
                                       startEdit();
                                    }
                                });

                                runs(function() {
                                    expect(plugin.getEditor().items.getAt(1).getItemId()).toBe('field7');
                                    expect(plugin.getEditor().items.getAt(4).getItemId()).toBe('field1');
                                });
                            });
                        });

                        describe("moving grouped columns", function() {
                            beforeEach(function() {
                                makeGrid([{
                                    text: 'Locked',
                                    dataIndex: 'field10',
                                    locked: true
                                }, {
                                    columns: [{
                                        dataIndex: 'field1',
                                        field: { id: 'field1' }
                                    }, {
                                        dataIndex: 'field2',
                                        field: { id: 'field2' }
                                    }, {
                                        dataIndex: 'field3',
                                        field: { id: 'field3' }
                                    }]
                                }, {
                                    columns: [{
                                        dataIndex: 'field4',
                                        field: { id: 'field4' }
                                    }, {
                                        dataIndex: 'field5',
                                        field: { id: 'field5' }
                                    }, {
                                        dataIndex: 'field6',
                                        field: { id: 'field6' }
                                    }]
                                }, {
                                    columns: [{
                                        dataIndex: 'field7',
                                        field: { id: 'field7' }
                                    }, {
                                        dataIndex: 'field8',
                                        field: { id: 'field8' }
                                    }, {
                                        dataIndex: 'field9',
                                        field: { id: 'field9' }
                                    }]
                                }]);
                            });

                            function expectOrder(order) {
                                // Extract the items collection of the normal (unlocked) side of the editor.
                                var items = plugin.getEditor().items.items[1].items,
                                    len = order.length,
                                    i;

                                for (i = 0; i < len; ++i) {
                                    expect(items.getAt(i).getItemId()).toBe(order[i]);
                                }
                            }

                            it("should move all leaf columns to the left", function() {
                                if (beforeFirstShow) {
                                    grid.normalGrid.headerCt.move(1, 0);
                                }

                                startEdit();

                                if (!beforeFirstShow) {
                                    runs(function() {
                                         grid.normalGrid.headerCt.move(1, 0);
                                    });
                                }

                                runs(function() {
                                    expectOrder(['field4', 'field5', 'field6', 'field1', 'field2', 'field3', 'field7', 'field8', 'field9']);
                                });
                            });

                            it("should move all leaf columns to the right", function() {
                                if (beforeFirstShow) {
                                    grid.normalGrid.headerCt.move(1, 2);
                                }

                                startEdit();

                                if (!beforeFirstShow) {
                                    runs(function() {
                                         grid.normalGrid.headerCt.move(1, 2);
                                    });
                                }

                                runs(function() {
                                    expectOrder(['field1', 'field2', 'field3', 'field7', 'field8', 'field9', 'field4', 'field5', 'field6']);
                                });
                            });
                        });
                    });
                }

                createLockingSuite(true);
                createLockingSuite(false);
            });

            describe('using a textarea as an editor', function() {
                itNotIE8('should align to the bottom of the editor when at the end', function() {
                    store = Ext.create('Ext.data.Store', {
                        storeId: 'simpsonsStore',
                        fields: [ 'name', 'email', 'phone'],
                        data: [
                            { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                            { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                            { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                            { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                            { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                            { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                            { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                            { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                            { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                            { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                            { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                            { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                            { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                            { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                            { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                            { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                            { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                            { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
                        ]
                    });

                    makeGrid([
                            { header: 'Name', dataIndex: 'name', editor: 'textfield' },
                            { header: 'Email', dataIndex: 'email', flex: 1,
                             editor: {
                                 xtype: 'textarea',
                                 allowBlank: false
                             }
                            },
                            { header: 'Phone', dataIndex: 'phone', width: 140 }
                        ], { clicksToEdit: 1 }, false, {
                            xtype: 'grid',
                            title: 'Simpsons',
                            store: Ext.data.StoreManager.lookup('simpsonsStore'),
                            selModel: 'rowmodel',
                            height: 400,
                            width: 600,
                            renderTo: document.body
                    });

                    view = grid.view;
                    plugin = grid.findPlugin('rowediting');

                    startEdit(store.last(), 1);

                    runs(function() {
                        var viewYScroll = view.getScrollY(),

                            // Return the scrollTo position required to being the activeField fully into view
                            scrollPos = plugin.editor.activeField.el.getScrollIntoViewXY(view.el, view.getScrollX(), viewYScroll);

                        // The field being edited must already be fully scrolled into view by the editor positioning.
                        expect(scrollPos.y).toBe(viewYScroll);
                    });
                });
            });

            describe('resizing columns', function() {
                it('should keep x scroll synced', function() {
                    makeGrid(getDefaultColumns(false, {
                        width: 200
                    }, 10));

                    scroller.scrollBy(300);

                    waitsForEvent(scroller, 'scrollend', 'view to scroll');

                    runs(function() {
                        startEdit(0, 3);
                    });

                    waitsFor(function() {
                        return plugin.context && plugin.context.column.getEditor().el.contains(document.activeElement) &&
                               plugin.editor.getScrollable().getPosition().x === scroller.getPosition().x;
                    }, 'field to focus and scroll into view along with the view', 5000, 100);

                    runs(function() {
                        colRef[5].setWidth(colRef[5].getWidth() - 50);
                    });

                    waitsFor(function() {
                        // X positions must be synced
                        return plugin.editor.getScrollable().getPosition().x === scroller.getPosition().x;
                    }, 'scroll positions to sync', 5000, 100);
                });
            });

            describe('showing after the normal side has already been scrolled horizontally', function() {
                it('should align itself to the existing horizontal scroll position on show', function() {
                    makeGrid(null, null, true, {
                        width: 400, height: 200
                    });

                    // Scroll normal grid rightwards
                    grid.normalGrid.getView().scrollBy(1000, 0);

                    // Start editing in the locked grid.
                    startEdit(0, 0);

                    // The normal grid has been scrolled.
                    // The RowEditor should sync with it on show.
                    runs(function() {
                        expect(plugin.editor.normalColumnContainer.getScrollX()).toBe(grid.normalGrid.getView().getScrollable().getPosition().x);
                    });
                });
            });

            describe('removeUnmodified', function() {
                it('should remove an unmodified phantom record on cancel', function() {
                    makeGrid(null, {
                        removeUnmodified: true
                    }, true, {
                        width: 400, height: 200
                    });
                    var storeCount = store.getCount();

                    // Begin editing a new record
                    store.insert(0, new GridEventModel());
                    expect(store.getCount()).toBe(storeCount + 1);
                    startEdit(0, 0);

                    runs(function() {
                        // Cancel without modifying the new record, the record should be removed
                        plugin.cancelEdit();
                        expect(store.getCount()).toBe(storeCount);
                    });
                });
            });

            describe('with record delete action column', function() {
                var oldOnError = window.onerror;

                function triggerAction(type, row, colIdx) {
                    var cell = findCell(row || 0, colIdx || 0);

                    jasmine.fireMouseEvent(cell.querySelector('.' + Ext.grid.column.Action.prototype.actionIconCls), type || 'click');

                    return cell;
                }

                afterEach(function() {
                    window.onerror = oldOnError;
                });

                it('should not throw', function() {
                    var columns = getDefaultColumns();

                    // Insert actino column
                    columns.unshift({
                        xtype: 'actioncolumn',
                        sortable: false,
                        width: 50,
                        items: [{
                            icon: 'resources/images/delete_task.png',
                            tooltip: 'Delete user',
                            handler: function(view, rowIndex, colIndex, item, event, record) {
                                store.remove(record);
                            }
                        }]
                    });
                    makeGrid(columns, {
                        clicksToEdit: 1
                    });

                    // We can't catch any exceptions thrown by synthetic events,
                    // so a standard toThrow() or even try/catch won't do the job
                    // here. They will hit onerror though, so use that.
                    var errorSpy = jasmine.createSpy();

                    window.onerror = errorSpy.andCallFake(function() {
                        if (oldOnError) {
                            oldOnError();
                        }
                    });

                    // Click on the delete action column.
                    // The Editor's click handler will be passed a
                    // context which is stale. It should handle it.
                    triggerAction('click', 0, 0);

                    expect(errorSpy.callCount).toBe(0);
                });
            });

            describe("ARIA", function() {
                describe("with visible headers", function() {
                    beforeEach(function() {
                        makeGrid();

                        startEdit(0, 0);
                    });

                    it("should have form role on the editor body", function() {
                        expect(editor.body).toHaveAttr('role', 'form');
                    });

                    it("should have aria-label on the editor body", function() {
                        expect(editor.body).toHaveAttr('aria-label', 'Editing row 2');
                    });

                    it("should have aria-owns on the editor body", function() {
                        expect(editor.body).toHaveAttr('aria-owns', editor.floatingButtons.id);
                    });

                    it("should have toolbar role on the floating buttons", function() {
                        expect(editor.floatingButtons).toHaveAttr('role', 'toolbar');
                    });

                    it("should have aria-labelledby on the fields' inputEls", function() {
                        expect(editor.items.getAt(0).inputEl).toHaveAttr('aria-labelledby', colRef[0].id);
                        expect(editor.items.getAt(1).inputEl).toHaveAttr('aria-labelledby', colRef[1].id);
                        expect(editor.items.getAt(2).inputEl).toHaveAttr('aria-labelledby', colRef[2].id);
                        expect(editor.items.getAt(3).inputEl).toHaveAttr('aria-labelledby', colRef[3].id);
                        expect(editor.items.getAt(4).inputEl).toHaveAttr('aria-labelledby', colRef[4].id);
                    });
                });

                describe("with hidden headers", function() {
                    beforeEach(function() {
                        makeGrid(null, null, null, {
                            hideHeaders: true
                        });

                        startEdit(0, 1);
                    });

                    it("should have aria-labels on the fields' inputEls", function() {
                        expect(editor.items.getAt(0).inputEl).toHaveAttr('aria-label', 'F1');
                        expect(editor.items.getAt(1).inputEl).toHaveAttr('aria-label', 'F2');
                        expect(editor.items.getAt(2).inputEl).toHaveAttr('aria-label', 'F3');
                        expect(editor.items.getAt(3).inputEl).toHaveAttr('aria-label', 'F4');
                        expect(editor.items.getAt(4).inputEl).toHaveAttr('aria-label', 'F5');
                    });
                });
            });
        });
    }

    createSuite(false);
    createSuite(true);
});
