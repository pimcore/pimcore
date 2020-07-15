topSuite("Ext.grid.plugin.RowEditing",
    ['Ext.grid.Panel', 'Ext.grid.column.Widget', 'Ext.form.field.*',
     'Ext.grid.selection.SpreadsheetModel', 'Ext.grid.feature.GroupingSummary'],
function() {
    var store, plugin, grid, view, column,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function makeGrid(pluginCfg, gridCfg, storeCfg) {
        var gridPlugins = gridCfg && gridCfg.plugins,
            plugins;

        store = new Ext.data.Store(Ext.apply({
            fields: ['name', 'email', 'phone'],
            data: [
                { 'name': 'Lisa', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224' },
                { 'name': 'Bart', 'email': 'bart@simpsons.com', 'phone': '555-222-1234' },
                { 'name': 'Homer', 'email': 'homer@simpsons.com', 'phone': '555-222-1244' },
                { 'name': 'Marge', 'email': 'marge@simpsons.com', 'phone': '555-222-1254' }
            ],
            autoDestroy: true
        }, storeCfg));

        plugin = new Ext.grid.plugin.RowEditing(pluginCfg);

        if (gridPlugins) {
            plugins = [].concat(plugin, gridPlugins);
            delete gridCfg.plugins;
        }

        grid = new Ext.grid.Panel(Ext.apply({
            columns: [
                { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                { header: 'Email', dataIndex: 'email',
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                { header: 'Phone', dataIndex: 'phone' }
            ],
            store: store,
            plugins: plugins || [plugin],
            width: 400,
            height: 400,
            renderTo: document.body
        }, gridCfg));

        view = grid.view;
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;
        store = plugin = grid = view = column = Ext.destroy(grid);
    });

    describe('Widget column', function() {
        it('should work', function() {
            makeGrid({
                clicksToEdit: 2
            }, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                    { header: 'Email', dataIndex: 'email',
                        editor: {
                            xtype: 'textfield',
                            allowBlank: false
                        }
                    },
                    { header: 'Phone', dataIndex: 'phone' },
                    {
                        xtype: 'widgetcolumn',
                        widget: {
                            xtype: 'button',
                            text: 'Delete',
                            handler: onDeleteClick
                        }
                    }
                ]
            });

            var storeCount = store.getCount(),
                editPos = new Ext.grid.CellContext(view).setPosition(0, 3),
                cell = editPos.getCell(true);

            function onDeleteClick(btn) {
                var rec = btn.getWidgetRecord();

                store.remove(rec);
            }

            // Programatically focus because simulated mousedown event does not focus, so
            // The tabIndex will NOT be -1, so it will process as if mousedowning on an active widget.
            view.getNavigationModel().setPosition(editPos);

            // First click should delete the record.
            // Second click - the dblclick - should not edit being on a focusable widget
            jasmine.fireMouseEvent(cell.firstChild.firstChild, 'dblclick');

            // Some browsers process the first and second click separately and will delete two rows.
            // So just check that the store size has been reduced.
            expect(store.getCount()).toBeLessThan(storeCount);

            // Editing should never start; flag should be undefined/falsy
            expect(plugin.editing).not.toBe(true);
        });
    });

    describe('should work', function() {
        var node;

        afterEach(function() {
            node = null;
        });

        it('should display the row editor for the grid in editing mode', function() {
            makeGrid();

            node = grid.view.getNode(0);

            jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

            expect(plugin.editor).toBeDefined();
            expect(plugin.editing).toBe(true);
        });

        it('should work with spreadsheet selection', function() {
            var selModel = selModel = new Ext.grid.selection.SpreadsheetModel({
                dragSelect: true,
                cellSelect: true,
                columnSelect: true,
                rowSelect: true,
                checkboxSelect: false
            }),
            record, items;

            makeGrid(undefined, {
                selModel: selModel
            });

            record = grid.store.getAt(0);
            column = grid.columns[0];
            expect(function() {
                plugin.startEdit(record, column);
            }).not.toThrow();

            items = plugin.editor.items;
            expect(items.getAt(1).getValue()).toBe('Lisa');
        });
    });

    describe('renderers', function() {
        it('should be called with the correct scope for the defaultRenderer (column)', function() {
            // See EXTJS-15047.
            var record, scope;

            makeGrid(null, {
                columns: [
                    { text: 'Foo', width: 50,
                        defaultRenderer: function() {
                            scope = this;

                            return 'some text';
                        }
                    },
                    { header: 'Name',  dataIndex: 'name', editor: 'textfield' },
                    { header: 'Email', dataIndex: 'email',
                        editor: {
                            xtype: 'textfield',
                            allowBlank: false
                        }
                    },
                    { header: 'Phone', dataIndex: 'phone' }
                ]
            });

            record = grid.store.getAt(0);
            column = grid.columns[0];
            plugin.startEdit(record, column);

            expect(scope === grid.columns[0]).toBe(true);
        });
    });

    describe('starting the edit', function() {
        var combo, textfield, record, items;

        describe('should work', function() {
            beforeEach(function() {
                combo = new Ext.form.field.ComboBox({
                    queryMode: 'local',
                    valueField: 'name',
                    displayField: 'name',
                    store: {
                        fields: ['name'],
                        data: [
                            { name: 'Lisa' },
                            { name: 'Bart' },
                            { name: 'Homer' },
                            { name: 'Marge' }
                        ]
                    }
                });

                textfield = new Ext.form.field.Text();

                makeGrid(null, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', editor: combo },
                        { header: 'Email', dataIndex: 'email',
                            editor: {
                                xtype: 'textfield',
                                allowBlank: false
                            }
                        },
                        { header: 'Phone', dataIndex: 'phone', editor: textfield }
                    ]
                });

                record = grid.store.getAt(0);
                column = grid.columns[0];

                plugin.startEdit(record, column);

                waitsForFocus(plugin.getEditor(), null, 10000);
            });

            afterEach(function() {
                record = items = null;
            });

            describe('initial values', function() {
                it('should give each editor a dataIndex property', function() {
                    items = plugin.editor.items;

                    expect(items.getAt(0).dataIndex).toBe('name');
                    expect(items.getAt(1).dataIndex).toBe('email');
                    expect(items.getAt(2).dataIndex).toBe('phone');
                });

                it('should start the editor with values taken from the model', function() {
                    items = plugin.editor.items;

                    expect(items.getAt(0).getValue()).toBe('Lisa');
                    expect(items.getAt(1).getValue()).toBe('lisa@simpsons.com');
                    expect(items.getAt(2).getValue()).toBe('555-111-1224');
                });
            });

            describe('using an existing component as an editor', function() {
                it('should be able to lookup its value from the corresponding model field', function() {
                    items = plugin.editor.items;

                    // The combo editor is an existing component.
                    expect(items.getAt(0).getValue()).toBe('Lisa');

                    // The textfield editor is an existing component.
                    expect(items.getAt(2).getValue()).toBe('555-111-1224');
                });
            });
        });

        describe('calling startEdit with different columnHeader values', function() {
            it('should allow columnHeader to be a Number', function() {
                makeGrid();

                record = grid.store.getAt(0);

                // Will return `true` if the edit was successfully started.
                expect(plugin.startEdit(record, 0)).toBe(true);
            });

            it('should allow columnHeader to be a Column instance', function() {
                makeGrid();

                record = grid.store.getAt(0);
                column = grid.columns[0];

                // Will return `true` if the edit was successfully started.
                expect(plugin.startEdit(record, column)).toBe(true);
            });

            it('should default to the first visible column if unspecified', function() {
                makeGrid();

                record = grid.store.getAt(0);

                // Will return `true` if the edit was successfully started.
                expect(plugin.startEdit(record)).toBe(true);
            });
        });

        describe('adding new rows to the view', function() {
            var viewEl, count, record, editor;

            function addRecord(index) {
                var el;

                plugin.cancelEdit();
                store.insert(index, { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' });
                record = store.getAt(index ? index - 1 : 0);
                plugin.startEdit(record, 0);
                editor = plugin.editor;

                el = Ext.fly(view.getNode(record));

                return new Ext.util.Point(el.getX(), el.getY());
            }

            afterEach(function() {
                count = viewEl = record = editor = null;
            });

            it('should be contained by and visible in the view', function() {
                makeGrid(null, {
                    height: 100
                });

                count = store.getCount();
                viewEl = view.getEl();

                // Add to the beginning.
                expect(addRecord(0).isContainedBy(viewEl)).toBe(true);
                expect(addRecord(0).isContainedBy(viewEl)).toBe(true);
                expect(addRecord(0).isContainedBy(viewEl)).toBe(true);
                expect(addRecord(0).isContainedBy(viewEl)).toBe(true);

                // Add to the end.
                expect(addRecord(count).isContainedBy(viewEl)).toBe(true);
                expect(addRecord(count).isContainedBy(viewEl)).toBe(true);
                expect(addRecord(count).isContainedBy(viewEl)).toBe(true);
                expect(addRecord(count).isContainedBy(viewEl)).toBe(true);
            });

            describe('scrolling into view', function() {
                function buffered(buffered) {
                    describe('buffered renderer = ' + buffered, function() {
                        beforeEach(function() {
                            makeGrid(null, {
                                buffered: buffered,
                                height: 100
                            });

                            count = store.getCount();
                            viewEl = view.getEl();
                        });

                        it('should scroll when adding to the beginning', function() {
                            addRecord(0);
                            expect(editor.isVisible()).toBe(true);
                            expect(editor.context.record).toBe(record);
                        });

                        it('should scroll when adding to the end', function() {
                            addRecord(store.getCount());
                            expect(editor.isVisible()).toBe(true);
                            expect(editor.context.record).toBe(record);
                        });
                    });
                }

                buffered(false);
                buffered(true);
            });
        });
    });

    describe('completing the edit', function() {
        var combo, record, items;

        beforeEach(function() {
            combo = new Ext.form.field.ComboBox({
                queryMode: 'local',
                valueField: 'name',
                displayField: 'name',
                store: {
                    fields: ['name'],
                    data: [
                        { name: 'Lisa' },
                        { name: 'Bart' },
                        { name: 'Homer' },
                        { name: 'Marge' }
                    ]
                }
            });

            makeGrid(null, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', editor: combo },
                    { header: 'Email', dataIndex: 'email',
                        editor: {
                            xtype: 'textfield',
                            allowBlank: false
                        }
                    }
                ]
            });

            record = grid.store.getAt(0);
            column = grid.columns[0];

            plugin.startEdit(record, column);
        });

        afterEach(function() {
            combo = record = items = null;
        });

        describe('using an existing component as an editor', function() {
            it('should update the underlying cell and the record', function() {
                column.getEditor().setValue('utley');
                plugin.editor.completeEdit();

                expect(Ext.fly(grid.view.getNode(record)).down('.x-grid-cell-inner', true).innerHTML).toBe('utley');
                expect(store.getAt(0).get('name')).toBe('utley');
            });
        });
    });

    describe('canceledit', function() {
        var editorContext = {},
            record;

        beforeEach(function() {
            makeGrid({
                listeners: {
                    canceledit: function(editor, context) {
                        editorContext = context;
                    }
                }
            });

            record = grid.store.getAt(0);
            column = grid.columns[0];

            plugin.startEdit(record, column);
        });

        afterEach(function() {
            editorContext = record = null;
        });

        it('should be able to get the original value when canceling the edit', function() {
            column.getEditor().setValue('baz');
            plugin.cancelEdit();

            expect(editorContext.originalValues.name).toBe('Lisa');
        });

        it('should be able to get the edited value when canceling the edit', function() {
            column.getEditor().setValue('foo');
            plugin.cancelEdit();

            expect(editorContext.newValues.name).toBe('foo');
        });

        it('should have different values for edited value and original value when canceling', function() {
            column.getEditor().setValue('foo');
            plugin.cancelEdit();

            expect(editorContext.newValues.name).not.toBe(editorContext.originalValues.name);
        });

        it('should be able to capture falsey values when canceled', function() {
            column.getEditor().setValue('');
            plugin.cancelEdit();

            expect(editorContext.newValues.name).toBe('');
        });
    });

    describe('locked grid', function() {
        var suiteCfg = {
            columns: [
                { header: 'Name',  dataIndex: 'name', width: 100, locked: true, editor: true },
                { header: 'Email', dataIndex: 'email', width: 100, editor: true },
                { header: 'Phone', dataIndex: 'phone', width: 100, editor: true }
            ],
            plugins: {
                ptype: 'rowediting'
            }
        },
        node;

        beforeEach(function() {
            makeGrid(null, suiteCfg);
        });

        afterEach(function() {
            node = null;
        });

        it('should display the row editor for the locked grid in editing mode', function() {
            node = grid.lockedGrid.view.getNode(0);
            jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

            plugin = grid.findPlugin('rowediting');

            expect(plugin.editor !== null).toBe(true);
            expect(plugin.editing).toBe(true);
        });

        it('should display the row editor for the normal grid in editing mode', function() {
            node = grid.normalGrid.view.getNode(0);

            jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

            plugin = grid.findPlugin('rowediting');

            expect(plugin.editor !== null).toBe(true);
            expect(plugin.editing).toBe(true);
        });

        describe('locking and unlocking columns', function() {
            it("should move the editor from the locked to the normal side after unlocking a column", function() {
                node = grid.lockedGrid.view.getNode(0);
                jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');
                plugin = grid.findPlugin('rowediting');

                expect(grid.columns[0].getEditor().ownerCt).toBe(plugin.editor.lockedColumnContainer);
                plugin.cancelEdit();

                grid.unlock(grid.columns[0], 0);
                node = grid.normalGrid.view.getNode(0);
                jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

                expect(grid.columns[0].getEditor().ownerCt).toBe(plugin.editor.normalColumnContainer);
            });

            it("should move the editor from the normal to the locked side after locking a column", function() {
                node = grid.normalGrid.view.getNode(0);
                jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');
                plugin = grid.findPlugin('rowediting');

                expect(grid.columns[1].getEditor().ownerCt).toBe(plugin.editor.normalColumnContainer);
                plugin.cancelEdit();

                grid.lock(grid.columns[1], 0);
                node = grid.lockedGrid.view.getNode(0);
                jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

                expect(grid.columns[0].getEditor().ownerCt).toBe(plugin.editor.lockedColumnContainer);
            });
        });

        describe('with grouping feature', function() {
            describe('when the activeRecord of the activeEditor has been filtered', function() {
                // These specs simulate the filtering of the data store when the row editing plugin is active
                // and over a record that has been filtered after the row editor was activated/started editing.
                // The bug appeared in KS when the row editor was open and the dataset was filtered by the grid
                // filter feature. Since the locking partners share the same store, the normal grid can't look
                // up the record in its store if the locked grid has already filtered the store, which is the
                // case here. Note that the bug only occurred when the editor was started from the normalGrid,
                // NOT the lockedGrid (since the store hadn't been filtered yet).
                //
                // To simulate, simply filter the store after the plugin has been activated. During the filter
                // operation, it will try to lookup the row record by its internal id in the GroupStore, but it
                // will fail because the dataset has been filtered and the GroupStore#getByInternalId method will
                // lookup the record in the data store. The fix is to lookup the record in the snapshot collection,
                // if it exists. This mimics the solution implemented by v5 which solves this by maintaining another
                // unfiltered collection, Ext.util.CollectionKey. So, because we can get the record shows that the
                // bug has been fixed, since the record is being found (regardless of filtering).
                // See EXTJS-13374.
                //
                // Note these specs must use the bufferedrenderer plugin.
                var normalView, lockedView, record;

                beforeEach(function() {
                    grid.destroy();

                    makeGrid(null, Ext.applyIf({
                        features: {
                            ftype: 'groupingsummary',
                            groupHeaderTpl: '{name}'
                        },
                        plugins: ['bufferedrenderer'],
                        lockedGridConfig: null,
                        normalGridConfig: null
                    }, suiteCfg), {
                        groupField: 'name'
                    });

                    normalView = grid.normalGrid.view;
                    lockedView = grid.lockedGrid.view;
                });

                afterEach(function() {
                    normalView = lockedView = record = null;
                });

                describe('activating the editor from the normal view', function() {
                    beforeEach(function() {
                        node = normalView.getNode(0);

                        jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

                        // Now filter the store.  Make sure that the row that's clicked on has been filtered
                        // and is no longer in the filtered data collection. This is what triggered the bug
                        // because the GroupStore is trying to look up the record in the filtered collection.
                        store.filter('email', /home/);
                        record = normalView.getRecord(node);
                    });

                    it('should still be able to lookup the record in the datastore when filtered', function() {
                        expect(record).toBeDefined();
                        expect(record.get('email')).toBe('bart@simpsons.com');
                    });

                    it('should close the editor', function() {
                        expect(plugin.editing).toBe(false);
                    });
                });

                describe('activating the editor from the locked view', function() {
                    beforeEach(function() {
                        node = lockedView.getNode(0);

                        jasmine.fireMouseEvent(Ext.fly(node).down('.x-grid-cell-inner', true), 'dblclick');

                        store.filter('email', /home/);
                        record = lockedView.getRecord(node);
                    });

                    it('should still be able to lookup the record in the datastore when filtered', function() {
                        expect(record).toBeDefined();
                        expect(record.get('email')).toBe('bart@simpsons.com');
                    });

                    it('should close the editor', function() {
                        expect(plugin.editing).toBe(false);
                    });
                });
            });
        });
    });

    describe('clicksToEdit', function() {
        var node, record;

        afterEach(function() {
            node = record = null;
        });

        describe('2 clicks', function() {
            beforeEach(function() {
                makeGrid();
            });

            it('should default to 2', function() {
                expect(plugin.clicksToEdit).toBe(2);
            });

            it('should begin editing when double-clicked', function() {
                record = grid.store.getAt(0);
                node = grid.view.getNodeByRecord(record);
                jasmine.fireMouseEvent(node.querySelector('.x-grid-cell'), 'dblclick');

                expect(plugin.editor).not.toBeFalsy();
            });

            it('should not begin editing when single-clicked', function() {
                record = grid.store.getAt(0);
                node = grid.view.getNodeByRecord(record);
                jasmine.fireMouseEvent(node.querySelector('.x-grid-cell'), 'click');

                expect(plugin.editor).toBeFalsy();
            });
        });

        describe('1 click', function() {
            beforeEach(function() {
                makeGrid({
                    clicksToEdit: 1
                });
            });

            it('should honor a different number than the default', function() {
                expect(plugin.clicksToEdit).toBe(1);
            });

            it('should begin editing when single-clicked', function() {
                record = grid.store.getAt(0);
                node = grid.view.getNodeByRecord(record);
                jasmine.fireMouseEvent(node.querySelector('.x-grid-cell'), 'click');

                expect(plugin.editor).not.toBeFalsy();
            });

            it('should not begin editing when double-clicked', function() {
                record = grid.store.getAt(0);
                node = grid.view.getNodeByRecord(record);
                jasmine.fireMouseEvent(node.querySelector('.x-grid-cell'), 'dblclick');

                expect(plugin.editor).not.toBeFalsy();
            });
        });
    });

    describe('the RowEditor', function() {
        var field;

        afterEach(function() {
            field = null;
        });

        describe('as textfield', function() {
            beforeEach(function() {
                makeGrid();

                column = grid.columns[0];
                plugin.startEdit(store.getAt(0), column);
                field = column.field;
            });

            it('should start the edit when ENTER is pressed', function() {
                var node;

                // First complete the edit (we start an edit in the top-level beforeEach).
                plugin.completeEdit();
                // Let's just do a sanity to make sure we're really not currently editing.
                expect(plugin.editing).toBe(false);

                node = view.body.query('td', true)[0];
                jasmine.fireKeyEvent(node, 'keydown', 13);

                waitsFor(function() {
                    return plugin.editing;
                });

                runs(function() {
                    expect(plugin.editing).toBe(true);
                });
            });

            describe('when currently editing', function() {
                it('should complete the edit when ENTER is pressed', function() {
                    var str = 'Utley is Top Dog',
                        model = store.getAt(0);

                    expect(model.get('name')).toBe('Lisa');
                    field.setValue(str);

                    jasmine.fireKeyEvent(field.inputEl, 'keydown', 13);

                    waitsFor(function() {
                        return model.get('name') === str;
                    });

                    runs(function() {
                        expect(model.get('name')).toBe(str);
                    });
                });

                it('should cancel the edit when ESCAPE is pressed', function() {
                    spyOn(plugin, 'cancelEdit');

                    jasmine.fireKeyEvent(field.inputEl, 'keydown', 27);

                    expect(plugin.cancelEdit).toHaveBeenCalled();
                });
            });
        });

        describe('as textarea', function() {
            beforeEach(function() {
                makeGrid();

                column = grid.columns[1];
                plugin.startEdit(store.getAt(0), column);
                field = column.field;
            });

            it('should start the edit when ENTER is pressed', function() {
                var node;

                // First complete the edit (we start an edit in the top-level beforeEach).
                plugin.completeEdit();
                // Let's just do a sanity to make sure we're really not currently editing.
                expect(plugin.editing).toBe(false);

                node = view.body.query('td', true)[1];
                jasmine.fireKeyEvent(node, 'keydown', 13);

                expect(plugin.editing).toBe(true);
            });

            describe('when currently editing', function() {
                it('should complete the edit when ENTER is pressed', function() {
                    spyOn(plugin, 'completeEdit');

                    jasmine.fireKeyEvent(field.inputEl, 'keydown', 13);

                    expect(plugin.completeEdit).toHaveBeenCalled();
                });

                it('should not cancel the edit when ENTER is pressed', function() {
                    spyOn(plugin, 'cancelEdit');

                    jasmine.fireKeyEvent(field.inputEl, 'keydown', 13);

                    expect(plugin.cancelEdit).not.toHaveBeenCalled();
                });

                it('should cancel the edit when ESCAPE is pressed', function() {
                    spyOn(plugin, 'cancelEdit');

                    jasmine.fireKeyEvent(field.inputEl, 'keydown', 27);

                    expect(plugin.cancelEdit).toHaveBeenCalled();
                });
            });
        });
    });

    describe("button position", function() {
        describe("not enough space to fit the editor", function() {
            beforeEach(function() {
                makeGrid({
                    clicksToEdit: 1
                }, {
                    height: undefined
                }, {
                    data: [
                        { 'name': 'Lisa', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224' },
                        { 'name': 'Bart', 'email': 'bart@simpsons.com', 'phone': '555-222-1234' }
                    ]
                });
            });

            it("should position buttons at the bottom when editing first row", function() {
                plugin.startEdit(store.getAt(0), grid.columns[0]);

                expect(plugin.editor.floatingButtons.el.hasCls('x-grid-row-editor-buttons-bottom')).toBe(true);
            });

            it("should position buttons at the top when editing last row", function() {
                plugin.startEdit(store.getAt(1), grid.columns[0]);

                expect(plugin.editor.floatingButtons.el.hasCls('x-grid-row-editor-buttons-top')).toBe(true);
            });
        });
    });
});
