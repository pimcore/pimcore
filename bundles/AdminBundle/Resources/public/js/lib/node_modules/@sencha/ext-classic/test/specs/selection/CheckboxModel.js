topSuite("Ext.selection.CheckboxModel",
    ['Ext.grid.Panel', 'Ext.grid.column.Widget', 'Ext.Button'],
function() {
    var grid, column, view, store, checkboxModel, data,
        donRec, evanRec, nigeRec,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function makeGrid(selectionCfg, cfg) {
        checkboxModel = new Ext.selection.CheckboxModel(selectionCfg);

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: [
                { text: "name", flex: 1, sortable: true, dataIndex: 'name' }
            ],
            columnLines: true,
            selModel: checkboxModel,
            width: 300,
            height: 300,
            renderTo: Ext.getBody()
        }, cfg));

        view = grid.view;
        column = checkboxModel.column;
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        Ext.define('spec.CheckboxModel', {
            extend: 'Ext.data.Model',
            fields: [{
                name: 'name'
            }]
        });

        store = Ext.create('Ext.data.Store', {
            model: 'spec.CheckboxModel',
            proxy: 'memory',
            data: data || [{
                id: 1,
                name: 'Don'
            }, {
                id: 2,
                name: 'Evan'
            }, {
                id: 3,
                name: 'Nige'
            }]
        });

        donRec = store.getById(1);
        evanRec = store.getById(2);
        nigeRec = store.getById(3);
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        donRec = evanRec = nigeRec = data = null;
        Ext.destroy(grid, checkboxModel, store);
        Ext.undefine('spec.CheckboxModel');
        Ext.data.Model.schema.clear();

        grid = column = view = store = checkboxModel = null;
    });

    function expectHeaderChecked(checked) {
        var col = checkboxModel.column;

        expect(col.hasCls(checkboxModel.checkerOnCls)).toBe(checked);

        if (col.useAriaElements) {
            expect(col).toHaveAttr('role', 'columnheader');

            if (col.headerCheckbox) {
                expect(col).toHaveAttr('aria-describedby', col.id + '-header-description' +
                                            (!checked ? '-not' : '') + '-selected');
            }
            else {
                expect(col).not.toHaveAttr('aria-describedby');
            }
        }
        else {
            expect(col).toHaveAttr('role', 'presentation');
            expect(col).not.toHaveAttr('aria-describedby');
        }
    }

    function clickOnHeaderCheckbox() {
        jasmine.fireMouseEvent(checkboxModel.column, 'click', 10, 10);
    }

    function clickCheckbox(rowIdx) {
        var cell = view.getCellByPosition({
            row: rowIdx,
            column: 0
        }, true);

        jasmine.fireMouseEvent(cell.querySelector(checkboxModel.checkSelector), 'click');
    }

    function clickCell(rowIdx, colIdx) {
        var cell = view.getCellByPosition({
            row: rowIdx,
            column: colIdx
        }, true);

        jasmine.fireMouseEvent(cell, 'click');
    }

    function keyCheckbox(rowIdx, keyCode, shiftKey, ctrlKey, altKey) {
        var cell = grid.getView().getCellByPosition({
            row: rowIdx,
            column: 0
        }, true);

        jasmine.fireKeyEvent(cell.querySelector(checkboxModel.checkSelector), 'keydown', keyCode, shiftKey, ctrlKey, altKey);
    }

    describe("grid reconfigure", function() {
        it("should be able to change the columns without hiding the checkcolumn", function() {
            var store2 = new Ext.data.Store({
                fields: ['foo'],
                data: [{
                    foo: 'bar'
                }]
            });

            makeGrid();

            grid.reconfigure(store2, [{ dataIndex: 'foo' }]);

            expect(grid.view.body.el.dom.querySelector('.x-grid-checkcolumn')).not.toBeNull();
        });
    });

    describe("column insertion", function() {
        var cols;

        afterEach(function() {
            cols = null;
        });

        it("should ignore any xtype defaults and insert a checkcolumn", function() {
            makeGrid(null, {
                columns: {
                    defaults: {
                        xtype: 'widgetcolumn',
                        widget: {
                            xtype: 'button'
                        }
                    },
                    items: [{
                        dataIndex: 'name'
                    }]
                }
            });
            var allCols = grid.getColumnManager().getColumns();

            expect(allCols[0].$className).toBe('Ext.grid.column.Check');
        });

        describe("without locking", function() {
            beforeEach(function() {
                cols = [{
                    dataIndex: 'name'
                }, {
                    dataIndex: 'name'
                }, {
                    dataIndex: 'name'
                }];
            });

            it("should insert the column at the start by default", function() {
                makeGrid(null, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[0];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(allCols.length).toBe(4);
            });

            it("should insert the column at the start with injectCheckbox: 'first'", function() {
                makeGrid({
                    injectCheckbox: 'first'
                }, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[0];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(allCols.length).toBe(4);
            });

            it("should insert the column at the end with injectCheckbox: 'last'", function() {
                makeGrid({
                    injectCheckbox: 'last'
                }, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[3];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(allCols.length).toBe(4);
            });

            it("should insert the column at the specified index", function() {
                makeGrid({
                    injectCheckbox: 1
                }, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[1];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(allCols.length).toBe(4);
            });
        });

        describe('Lockable, but starting with no locked columns', function() {
            beforeEach(function() {
                cols = [{
                    text: 'Name1',
                    dataIndex: 'name'
                }, {
                    text: 'Name2',
                    dataIndex: 'name'
                }, {
                    text: 'Name3',
                    dataIndex: 'name'
                }];
            });

            it('should be able to be locked without any other locked columns', function() {
                var checkColumn;

                makeGrid({
                    locked: true
                }, {
                    enableLocking: true,
                    column: cols
                });

                checkColumn = grid.down('[isCheckerHd]');

                expect(checkColumn.up('grid') === grid.lockedGrid).toBe(true);
            });

            it('should migrate the check column to locked when the first column is locked', function() {
                makeGrid(null, {
                    enableLocking: true,
                    columns: cols
                });
                var checkColumn = grid.down('[isCheckerHd]'),
                    name1Column = grid.down('[text=Name1]');

                // There's a locked grid but it's not visible.
                expect(grid.lockedGrid.isVisible()).toBe(false);

                grid.lock(name1Column);

                // The locked grid should now be visible
                expect(grid.lockedGrid.isVisible()).toBe(true);

                // TWO columns should now be owned by the locked grid.
                // checkColumn must have migrated.
                expect(checkColumn.up('grid') === grid.lockedGrid).toBe(true);
                expect(name1Column.up('grid') === grid.lockedGrid).toBe(true);
            });
        });

        describe("with locking", function() {
            beforeEach(function() {
                cols = [{
                    text: 'Name 1',
                    dataIndex: 'name',
                    locked: true
                }, {
                    text: 'Name 2',
                    dataIndex: 'name',
                    locked: true
                }, {
                    text: 'Name 3',
                    dataIndex: 'name',
                    locked: true
                }, {
                    text: 'Name 4',
                    dataIndex: 'name'
                }, {
                    text: 'Name 5',
                    dataIndex: 'name'
                }, {
                    text: 'Name 6',
                    dataIndex: 'name'
                }];
            });

            it("should insert the column at the start by default", function() {
                makeGrid(null, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[0];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(grid.normalGrid.query('checkcolumn').length).toBe(0);
                expect(allCols.length).toBe(7);
            });

            it("should unlock the column when all other columns are unlocked", function() {
                makeGrid(null, {
                    width: 800,
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[0];

                grid.unlock(allCols[1]);
                grid.unlock(allCols[2]);
                grid.unlock(allCols[3]);

                // Locked grid should have been hidden because unlocking the three lockeddata columns
                // should have caysed migration of the checkbox column
                expect(grid.lockedGrid.isVisible()).toBe(false);

                // Normal grid should contain hte checkbox column
                expect(grid.normalGrid.headerCt.contains(col)).toBe(true);
            });

            it("should insert the column at the start with injectCheckbox: 'first'", function() {
                makeGrid({
                    injectCheckbox: 'first'
                }, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[0];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(grid.normalGrid.query('checkcolumn').length).toBe(0);
                expect(allCols.length).toBe(7);
            });

            it("should insert the column at the end with injectCheckbox: 'last'", function() {
                makeGrid({
                    injectCheckbox: 'last'
                }, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[3];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(grid.normalGrid.query('checkcolumn').length).toBe(0);
                expect(allCols.length).toBe(7);
            });

            it("should insert the column at the specified index", function() {
                makeGrid({
                    injectCheckbox: 1
                }, {
                    columns: cols
                });

                var allCols = grid.getColumnManager().getColumns(),
                    col = allCols[1];

                expect(col.isCheckColumn).toBe(true);
                expect(grid.query('checkcolumn').length).toBe(1);
                expect(grid.normalGrid.query('checkcolumn').length).toBe(0);
                expect(allCols.length).toBe(7);
            });
        });
    });

    describe("multiple selection", function() {
        beforeEach(function() {
            makeGrid();
        });
        describe('by clicking', function() {
            it('should select unselected records on click, and deselect selected records on click', function() {
                grid.focus();

                // Wait for the asynchronous focus processing to occur for IE
                waitsForFocus(view, 'view to gain focus');

                runs(function() {
                    clickCheckbox(0);
                    clickCheckbox(1);
                    clickCheckbox(2);
                });
                waitsFor(function() {
                    return checkboxModel.getSelection().length === 3;
                }, 'all three records to be selected');
                runs(function() {
                    clickCheckbox(1);
                });
                waitsFor(function() {
                    return checkboxModel.getSelection().length === 2;
                }, 'the first record to be deselected');
            });
        });
        describe('by key navigation', function() {
            it('should select unselected records on ctrl+SPACE, and deselect selected records on ctrl+SPACE', function() {
                grid.view.getNavigationModel().setPosition(0);
                expect(checkboxModel.getSelection().length).toBe(0);
                keyCheckbox(0, Ext.event.Event.SPACE);
                expect(checkboxModel.getSelection().length).toBe(1);
                keyCheckbox(0, Ext.event.Event.DOWN, false, true);
                expect(checkboxModel.getSelection().length).toBe(1);
                keyCheckbox(1, Ext.event.Event.DOWN, false, true);
                keyCheckbox(2, Ext.event.Event.SPACE);
                expect(checkboxModel.getSelection().length).toBe(2);
                keyCheckbox(2, Ext.event.Event.UP, false, true);
                keyCheckbox(1, Ext.event.Event.UP, false, true);
                keyCheckbox(0, Ext.event.Event.SPACE);
                expect(checkboxModel.getSelection().length).toBe(1);
            });
        });
    });

    describe("header state", function() {
        beforeEach(function() {
            makeGrid();
        });

        it("should be initially unchecked", function() {
            expectHeaderChecked(false);
        });

        it("should be unchecked if there are no records", function() {
            store.removeAll();
            expectHeaderChecked(false);
        });

        it("should check header when all rows are selected", function() {
            expectHeaderChecked(false);

            checkboxModel.select(donRec, true);
            expectHeaderChecked(false);

            checkboxModel.select(evanRec, true);
            expectHeaderChecked(false);

            checkboxModel.select(nigeRec, true);
            expectHeaderChecked(true);
        });

        it("should uncheck header when any row is deselected", function() {
            checkboxModel.selectAll();
            expectHeaderChecked(true);

            checkboxModel.selectAll();
            checkboxModel.deselect(donRec);
            expectHeaderChecked(false);

            checkboxModel.selectAll();
            checkboxModel.deselect(evanRec);
            expectHeaderChecked(false);

            checkboxModel.selectAll();
            checkboxModel.deselect(nigeRec);
            expectHeaderChecked(false);
        });

        describe("loading", function() {
            it("should keep the header checked when reloaded and all items were checked", function() {
                checkboxModel.selectAll();
                expectHeaderChecked(true);
                store.load();
                expectHeaderChecked(true);
            });

            it("should keep the header checked when reloaded and loading a subset of items", function() {
                checkboxModel.selectAll();
                expectHeaderChecked(true);

                store.getProxy().setData([{
                    id: 1,
                    name: 'Don'
                }]);
                store.load();
                expectHeaderChecked(true);
            });

            it("should be unchecked when the loaded items do not match", function() {
                checkboxModel.selectAll();
                expectHeaderChecked(true);

                store.getProxy().setData([{
                    id: 4,
                    name: 'Foo'
                }]);
                store.load();
                expectHeaderChecked(false);
            });
        });

        it("should uncheck header when an unchecked record is added", function() {
            checkboxModel.selectAll();
            expectHeaderChecked(true);

            store.add({ name: 'Marcelo' });
            expectHeaderChecked(false);
        });

        it("should check header when last unchecked record is removed before rows are rendered", function() {
            checkboxModel.select(donRec, true);
            checkboxModel.select(evanRec, true);
            expectHeaderChecked(false);

            store.remove(nigeRec);
            expectHeaderChecked(true);
        });

        it("should check header when last unchecked record is removed after rows are rendered", function() {
            checkboxModel.select(donRec, true);
            checkboxModel.select(evanRec, true);
            expectHeaderChecked(false);

            store.remove(nigeRec);
            expectHeaderChecked(true);
        });

        describe("when filtered", function() {
            describe("adding records", function() {
                it("should remain checked when a record is added that does not match the filter", function() {
                    checkboxModel.select(donRec);
                    store.filter('name', 'Don');
                    expectHeaderChecked(true);

                    store.add({
                        name: 'Foo'
                    });
                    expectHeaderChecked(true);
                });

                it("should uncheck when adding a record that does match the filter", function() {
                    checkboxModel.select(donRec);
                    store.filter('name', 'Don');
                    expectHeaderChecked(true);

                    store.add({
                        name: 'Don'
                    });
                    expectHeaderChecked(false);
                });
            });

            describe("removing records", function() {
                it("should remain checked when removing an item that does not match the filter", function() {
                    checkboxModel.select(donRec, evanRec);
                    store.filter('name', 'Don');
                    expectHeaderChecked(true);

                    store.remove(evanRec);
                    expectHeaderChecked(true);
                });

                it("should remain checked when removing an item that does match the filter", function() {
                    checkboxModel.select([donRec, evanRec]);
                    store.getFilters().add({
                        filterFn: function(rec) {
                            return rec === donRec || rec === evanRec;
                        }
                    });
                    expectHeaderChecked(true);

                    store.remove(evanRec);
                    expectHeaderChecked(true);
                });

                it("should uncheck if the record being removed is the last matching the filter", function() {
                    checkboxModel.select(donRec);
                    store.filter('name', 'Don');
                    expectHeaderChecked(true);

                    store.remove(donRec);
                    expectHeaderChecked(false);
                });
            });

            describe("updating records", function() {
                it("should uncheck if an unselected record is changed to match the filter", function() {
                    checkboxModel.select(donRec);
                    store.filter('name', 'Don');
                    expectHeaderChecked(true);

                    evanRec.set('name', 'Don');
                    expectHeaderChecked(false);
                });

                it("should uncheck if the last selected item is changed to not match the filter", function() {
                    checkboxModel.select(donRec);
                    store.filter('name', 'Don');
                    expectHeaderChecked(true);

                    donRec.set('name', 'Evan');
                    expectHeaderChecked(false);
                });

                it("should check if an unselected record is changed to not match the filter", function() {
                    checkboxModel.select(donRec);
                    evanRec.set('name', 'Don');
                    store.filter('name', 'Don');
                    expectHeaderChecked(false);

                    evanRec.set('name', 'Evan');
                    expectHeaderChecked(true);
                });
            });
        });

    });

    describe("check all", function() {
        describe('mode="SINGLE"', function() {
            it('should not render the header checkbox by default', function() {
                makeGrid({
                    mode: 'SINGLE'
                });

                expect(checkboxModel.column.el.dom.querySelector(checkboxModel.checkSelector)).toBe(null);
            });

            it('should not render the header checkbox by config', function() {
                expect(function() {
                    makeGrid({
                        mode: 'SINGLE',
                        showHeaderCheckbox: true
                    });
                }).toThrow('The header checkbox is not supported for SINGLE mode selection models.');
            });
        });

        describe('mode="MULTI"', function() {
            it("should check all when no record is checked", function() {
                makeGrid();
                expectHeaderChecked(false);

                clickOnHeaderCheckbox();
                expectHeaderChecked(true);

                expect(checkboxModel.isSelected(donRec)).toBe(true);
                expect(checkboxModel.isSelected(evanRec)).toBe(true);
                expect(checkboxModel.isSelected(nigeRec)).toBe(true);
            });

            it("should check all when some records are checked", function() {
                makeGrid();
                expectHeaderChecked(false);

                checkboxModel.select(donRec, true);
                checkboxModel.select(nigeRec, true);

                clickOnHeaderCheckbox();
                expectHeaderChecked(true);

                expect(checkboxModel.isSelected(donRec)).toBe(true);
                expect(checkboxModel.isSelected(evanRec)).toBe(true);
                expect(checkboxModel.isSelected(nigeRec)).toBe(true);
            });

            it("should not do anything with showHeaderCheckbox: false", function() {
                makeGrid({
                    showHeaderCheckbox: false
                });

                clickOnHeaderCheckbox();
                expect(checkboxModel.getCount()).toBe(0);
            });

            describe("with filtering", function() {
                it("should only check items in the current view", function() {
                    makeGrid();
                    store.filter('name', 'Don');
                    clickOnHeaderCheckbox();
                    expectHeaderChecked(true);

                    store.getFilters().removeAll();

                    expectHeaderChecked(false);
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                    expect(checkboxModel.isSelected(evanRec)).toBe(false);
                    expect(checkboxModel.isSelected(nigeRec)).toBe(false);
                });
            });
        });
    });

    describe("uncheck all", function() {
        beforeEach(function() {
            makeGrid();
        });

        it("should uncheck all when all records are checked", function() {
            checkboxModel.select(donRec, true);
            checkboxModel.select(evanRec, true);
            checkboxModel.select(nigeRec, true);
            expectHeaderChecked(true);

            clickOnHeaderCheckbox();
            expectHeaderChecked(false);
            expect(checkboxModel.isSelected(donRec)).toBe(false);
            expect(checkboxModel.isSelected(evanRec)).toBe(false);
            expect(checkboxModel.isSelected(nigeRec)).toBe(false);
        });

        describe("with filtering", function() {
            it("should only uncheck items in the current view", function() {
                checkboxModel.selectAll();
                store.filter('name', 'Nige');
                clickOnHeaderCheckbox();

                expectHeaderChecked(false);
                expect(checkboxModel.isSelected(donRec)).toBe(true);
                expect(checkboxModel.isSelected(evanRec)).toBe(true);
                expect(checkboxModel.isSelected(nigeRec)).toBe(false);

                store.getFilters().removeAll();

                expectHeaderChecked(false);
                expect(checkboxModel.isSelected(donRec)).toBe(true);
                expect(checkboxModel.isSelected(evanRec)).toBe(true);
                expect(checkboxModel.isSelected(nigeRec)).toBe(false);
            });

        });

    });

    describe("checkOnly", function() {
        function byPos(row, col) {
            return grid.getView().getCellByPosition({
                row: row,
                column: col
            }, true);
        }

        function makeCheckGrid(checkOnly, mode) {
            makeGrid({
                checkOnly: checkOnly,
                mode: mode
            });
        }

        describe("mode: multi", function() {
            describe("with checkOnly: true", function() {
                beforeEach(function() {
                    makeCheckGrid(true, 'MULTI');
                });

                it("should not select when clicking on the row", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                });

                it("should not select when calling selectByPosition on a cell other than the checkbox cell", function() {
                    checkboxModel.selectByPosition({
                        row: 0,
                        column: 1
                    });
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                });

                it("should not select when navigating with keys", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    jasmine.fireKeyEvent(byPos(0, 1), 'keydown', Ext.event.Event.LEFT);
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                    jasmine.fireKeyEvent(byPos(0, 0), 'keydown', Ext.event.Event.RIGHT);
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                });

                it("should select when clicking on the checkbox", function() {
                    var checker = byPos(0, 0).querySelector(checkboxModel.checkSelector);

                    jasmine.fireMouseEvent(checker, 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });

                it("should select when pressing space with the checker focused", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    jasmine.fireKeyEvent(byPos(0, 1), 'keydown', Ext.event.Event.LEFT);
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                    jasmine.fireKeyEvent(byPos(0, 0), 'keydown', Ext.event.Event.SPACE);
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });
            });

            describe("with checkOnly: false", function() {
                beforeEach(function() {
                    makeCheckGrid(false, 'MULTI');
                });

                it("should select when clicking on the row", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });

                it("should select when calling selectByPosition on a cell other than the checkbox cell", function() {
                    checkboxModel.selectByPosition({
                        row: 0,
                        column: 1
                    });
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });

                it("should select when navigating with keys", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    jasmine.fireKeyEvent(byPos(0, 1), 'keydown', Ext.event.Event.DOWN);
                    expect(checkboxModel.isSelected(evanRec)).toBe(true);
                });

                it("should select when clicking on the checkbox", function() {
                    var checker = byPos(0, 0).querySelector(checkboxModel.checkSelector);

                    jasmine.fireMouseEvent(checker, 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });
            });
        });

        describe("mode: single", function() {
            describe("with checkOnly: true", function() {
                beforeEach(function() {
                    makeCheckGrid(true, 'SINGLE');
                });

                it("should not select when clicking on the row", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                });

                it("should not select when navigating with keys", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    jasmine.fireKeyEvent(byPos(0, 1), 'keydown', Ext.event.Event.LEFT);
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                    jasmine.fireKeyEvent(byPos(0, 0), 'keydown', Ext.event.Event.RIGHT);
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                });

                it("should select when clicking on the checkbox", function() {
                    var checker = byPos(0, 0).querySelector(checkboxModel.checkSelector);

                    jasmine.fireMouseEvent(checker, 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });

                it("should select when pressing space with the checker focused", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    jasmine.fireKeyEvent(byPos(0, 1), 'keydown', Ext.event.Event.LEFT);
                    expect(checkboxModel.isSelected(donRec)).toBe(false);
                    jasmine.fireKeyEvent(byPos(0, 0), 'keydown', Ext.event.Event.SPACE);
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });
            });

            describe("with checkOnly: false", function() {
                beforeEach(function() {
                    makeCheckGrid(false, 'SINGLE');
                });

                it("should select when clicking on the row", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });

                it("should select when navigating with keys", function() {
                    jasmine.fireMouseEvent(byPos(0, 1), 'click');
                    jasmine.fireKeyEvent(byPos(0, 1), 'keydown', Ext.event.Event.DOWN);
                    expect(checkboxModel.isSelected(evanRec)).toBe(true);
                });

                it("should select when clicking on the checkbox", function() {
                    var checker = byPos(0, 0).querySelector(checkboxModel.checkSelector);

                    jasmine.fireMouseEvent(checker, 'click');
                    expect(checkboxModel.isSelected(donRec)).toBe(true);
                });
            });
        });
    });

    describe("event selection", function() {
        var changeSpy, selectSpy, deselectSpy;

        function makeSpies() {
            changeSpy = jasmine.createSpy();
            selectSpy = jasmine.createSpy();
            deselectSpy = jasmine.createSpy();

            checkboxModel.on('selectionchange', changeSpy);
            checkboxModel.on('select', selectSpy);
            checkboxModel.on('deselect', deselectSpy);
        }

        function expectChangeSpy(records) {
            var args = changeSpy.mostRecentCall.args;

            expect(changeSpy.callCount).toBe(1);
            expect(args[0]).toBe(checkboxModel);
            expect(args[1]).toEqual(records);
        }

        function expectSelectSpy(record) {
            var args = selectSpy.mostRecentCall.args;

            expect(selectSpy.callCount).toBe(1);
            expect(args[0]).toBe(checkboxModel);
            expect(args[1]).toBe(record);
        }

        function expectDeselectSpy(record) {
            var args = deselectSpy.mostRecentCall.args;

            expect(deselectSpy.callCount).toBe(1);
            expect(args[0]).toBe(checkboxModel);
            expect(args[1]).toBe(record);
        }

        afterEach(function() {
            changeSpy = selectSpy = deselectSpy = null;
        });

        describe("multi", function() {
            describe("with checkOnly: false", function() {
                beforeEach(function() {
                    makeGrid({
                        mode: 'MULTI',
                        checkOnly: false
                    });
                });

                describe("selection when clicking on the checkbox", function() {
                    describe("on a selected record", function() {
                        it("should deselect when there are no other selections", function() {
                            checkboxModel.select(donRec);
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(false);
                            expectChangeSpy([]);
                            expectDeselectSpy(donRec);
                            expect(selectSpy).not.toHaveBeenCalled();
                        });

                        it("should deselect and keep existing selections", function() {
                            checkboxModel.selectAll();
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(false);
                            expectChangeSpy([nigeRec, evanRec]);
                            expectDeselectSpy(donRec);
                            expect(selectSpy).not.toHaveBeenCalled();
                        });
                    });

                    describe("on an unselected record", function() {
                        it("should select the record when there are no other selections", function() {
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(true);
                            expectChangeSpy([donRec]);
                            expectSelectSpy(donRec);
                            expect(deselectSpy).not.toHaveBeenCalled();
                        });

                        it("should select and keep existing selections", function() {
                            checkboxModel.select([evanRec, nigeRec]);
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(true);
                            expectChangeSpy([evanRec, nigeRec, donRec]);
                            expectSelectSpy(donRec);
                            expect(deselectSpy).not.toHaveBeenCalled();
                        });
                    });
                });

                describe("with shiftKey", function() {
                    var philRec;

                    beforeEach(function() {
                        philRec = store.add({
                            id: 4,
                            name: 'Phil'
                        })[0];
                    });

                    it("should deselect everything past & including the clicked item", function() {
                        checkboxModel.selectAll();
                        var view = grid.getView();

                        clickCell(0, 1);
                        spyOn(view, 'processUIEvent').andCallFake(function(e) {
                            if (e.type === 'click') {
                                e.shiftKey = true;
                            }

                            Ext.grid.View.prototype.processUIEvent.apply(view, arguments);
                        });

                        clickCell(2, 1);
                        clickCell(1, 1);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);
                        expect(checkboxModel.isSelected(nigeRec)).toBe(false);
                        expect(checkboxModel.isSelected(philRec)).toBe(false);
                    });
                });
            });

            describe("with checkOnly: true", function() {
                beforeEach(function() {
                    makeGrid({
                        mode: 'MULTI',
                        checkOnly: true
                    });
                });

                describe("selection when clicking on the checkbox", function() {
                    describe("on a selected record", function() {
                        it("should deselect when there are no other selections", function() {
                            checkboxModel.select(donRec);
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(false);
                            expectChangeSpy([]);
                            expectDeselectSpy(donRec);
                            expect(selectSpy).not.toHaveBeenCalled();
                        });

                        it("should deselect and keep existing selections", function() {
                            checkboxModel.selectAll();
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(false);
                            expectChangeSpy([nigeRec, evanRec]);
                            expectDeselectSpy(donRec);
                            expect(selectSpy).not.toHaveBeenCalled();
                        });
                    });

                    describe("on an unselected record", function() {
                        it("should select the record when there are no other selections", function() {
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(true);
                            expectChangeSpy([donRec]);
                            expectSelectSpy(donRec);
                            expect(deselectSpy).not.toHaveBeenCalled();
                        });

                        it("should select and keep existing selections", function() {
                            checkboxModel.select([evanRec, nigeRec]);
                            makeSpies();
                            clickCheckbox(0);
                            expect(checkboxModel.isSelected(donRec)).toBe(true);
                            expectChangeSpy([evanRec, nigeRec, donRec]);
                            expectSelectSpy(donRec);
                            expect(deselectSpy).not.toHaveBeenCalled();
                        });
                    });
                });

                describe("with shiftKey", function() {
                    var philRec;

                    beforeEach(function() {
                        philRec = store.add({
                            id: 4,
                            name: 'Phil'
                        })[0];
                    });

                    it("should deselect everything past & including the clicked item", function() {
                        checkboxModel.selectAll();
                        var view = grid.getView();

                        spyOn(view, 'processUIEvent').andCallFake(function(e) {
                            if (e.type === 'click') {
                                e.shiftKey = true;
                            }

                            Ext.grid.View.prototype.processUIEvent.apply(view, arguments);
                        });

                        clickCell(2, 0);
                        clickCell(1, 0);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);
                        expect(checkboxModel.isSelected(nigeRec)).toBe(false);
                        expect(checkboxModel.isSelected(philRec)).toBe(false);
                    });

                    it("should NOT change selection if clicked not on checkbox", function() {
                        checkboxModel.selectAll();
                        var view = grid.getView();

                        clickCell(0, 1);
                        spyOn(view, 'processUIEvent').andCallFake(function(e) {
                            if (e.type === 'click') {
                                e.shiftKey = true;
                            }

                            Ext.grid.View.prototype.processUIEvent.apply(view, arguments);
                        });

                        clickCell(2, 1);
                        clickCell(1, 1);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);
                        expect(checkboxModel.isSelected(nigeRec)).toBe(true);
                        expect(checkboxModel.isSelected(philRec)).toBe(true);
                    });
                });
            });
        });

        describe("single", function() {
            describe("with checkOnly: false", function() {
                beforeEach(function() {
                    makeGrid({
                        mode: 'SINGLE'
                    });
                });

                describe("on the checkbox", function() {
                    it("should select the record on click", function() {
                        clickCheckbox(0);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);

                        // Actionable mode MUST NOT be set
                        expect(grid.actionableMode).toBeFalsy();
                    });

                    it("should deselect any selected records", function() {
                        clickCheckbox(0);
                        clickCheckbox(1);
                        expect(checkboxModel.isSelected(donRec)).toBe(false);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);

                        // Actionable mode MUST NOT be set
                        expect(grid.actionableMode).toBeFalsy();
                    });
                });

                describe("on the row", function() {
                    it("should select the record on click", function() {
                        clickCheckbox(0);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);
                    });

                    it("should deselect any selected records", function() {
                        clickCheckbox(0);
                        clickCheckbox(1);
                        expect(checkboxModel.isSelected(donRec)).toBe(false);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);
                    });
                });
            });

            describe("with checkOnly: true", function() {
                beforeEach(function() {
                    makeGrid({
                        mode: 'SINGLE',
                        checkOnly: true
                    });
                });

                describe("on the checkbox", function() {
                    it("should select the record on click", function() {
                        clickCheckbox(0);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);

                        // Actionable mode MUST NOT be set and the checkbox cell must be focused
                        expect(grid.actionableMode).toBeFalsy();

                        var cell = view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true);

                        expectFocused(cell, true);
                    });

                    it("should deselect any selected records", function() {
                        clickCheckbox(0);
                        clickCheckbox(1);
                        expect(checkboxModel.isSelected(donRec)).toBe(false);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);

                        // Actionable mode MUST NOT be set and the checkbox cell must be focused
                        expect(grid.actionableMode).toBeFalsy();

                        var cell = view.getCellByPosition({
                            row: 1,
                            column: 0
                        }, true);

                        expectFocused(cell, true);
                    });
                });

                describe("on the row", function() {
                    it("should select the record on click", function() {
                        clickCheckbox(0);
                        expect(checkboxModel.isSelected(donRec)).toBe(true);
                    });

                    it("should deselect any selected records", function() {
                        clickCheckbox(0);
                        clickCheckbox(1);
                        expect(checkboxModel.isSelected(donRec)).toBe(false);
                        expect(checkboxModel.isSelected(evanRec)).toBe(true);
                    });
                });
            });
        });
    });

    describe("ARIA", function() {
        describe("with checkOnly: false", function() {
            describe("with showHeaderCheckbox: false", function() {
                beforeEach(function() {
                    makeGrid({
                        checkOnly: false,
                        showHeaderCheckbox: false
                    });
                });

                describe("header", function() {
                    it("should have tabIndex set to -1", function() {
                        expect(column).toHaveAttr('tabIndex');
                        expect(column.tabIndex).toBe(-1);
                    });

                    it("should have presentation role", function() {
                        expect(column).toHaveAttr('role', 'presentation');
                    });

                    it("should not have aria-label", function() {
                        expect(column).not.toHaveAttr('aria-label');
                    });

                    it("should not have aria-labelledby", function() {
                        expect(column).not.toHaveAttr('aria-labelledby');
                    });

                    it("should not have aria-describedby", function() {
                        expect(column).not.toHaveAttr('aria-describedby');
                    });
                });

                describe("cells", function() {
                    var cell;

                    beforeEach(function() {
                        cell = view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true);
                    });

                    afterEach(function() {
                        cell = null;
                    });

                    it("should have tabIndex", function() {
                        expect(cell).toHaveAttr('tabIndex');
                    });

                    it("should have gridcell role", function() {
                        expect(cell).toHaveAttr('role', 'gridcell');
                    });

                    it("should not have aria-label", function() {
                        expect(cell).not.toHaveAttr('aria-label');
                    });

                    it("should not have aria-labelledby", function() {
                        expect(cell).not.toHaveAttr('aria-labelledby');
                    });

                    it("should not have aria-describedby", function() {
                        expect(cell).not.toHaveAttr('aria-describedby');
                    });
                });
            });

            describe("with showHeaderCheckbox: true", function() {
                beforeEach(function() {
                    makeGrid({
                        checkOnly: false,
                        showHeaderCheckbox: true
                    });
                });

                describe("header", function() {
                    it("should have tabIndex set to -1", function() {
                        expect(column).toHaveAttr('tabIndex');
                        expect(column.tabIndex).toBe(-1);
                    });

                    it("should have presentation role", function() {
                        expect(column).toHaveAttr('role', 'presentation');
                    });

                    it("should not have aria-label", function() {
                        expect(column).not.toHaveAttr('aria-label');
                    });

                    it("should not have aria-labelledby", function() {
                        expect(column).not.toHaveAttr('aria-labelledby');
                    });

                    it("should not have aria-describedby", function() {
                        expect(column).not.toHaveAttr('aria-describedby');
                    });
                });

                describe("cells", function() {
                    var cell;

                    beforeEach(function() {
                        cell = view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true);
                    });

                    afterEach(function() {
                        cell = null;
                    });

                    it("should have tabIndex", function() {
                        expect(cell).toHaveAttr('tabIndex');
                    });

                    it("should have gridcell role", function() {
                        expect(cell).toHaveAttr('role', 'gridcell');
                    });

                    it("should not have aria-label", function() {
                        expect(cell).not.toHaveAttr('aria-label');
                    });

                    it("should not have aria-labelledby", function() {
                        expect(cell).not.toHaveAttr('aria-labelledby');
                    });

                    it("should not have aria-describedby", function() {
                        expect(cell).not.toHaveAttr('aria-describedby');
                    });
                });
            });
        });

        describe("with checkOnly: true", function() {
            describe("with showHeaderCheckbox: false", function() {
                beforeEach(function() {
                    makeGrid({
                        checkOnly: true,
                        showHeaderCheckbox: false
                    });
                });

                describe("header", function() {
                    it("should have tabIndex", function() {
                        expect(column).toHaveAttr('tabIndex', -1);
                    });

                    it("should have columnheader role", function() {
                        expect(column).toHaveAttr('role', 'columnheader');
                    });

                    it("should have aria-label", function() {
                        expect(column).toHaveAttr('aria-label', checkboxModel.headerAriaLabel);
                    });

                    it("should not have aria-labelledby", function() {
                        expect(column).not.toHaveAttr('aria-labelledby');
                    });

                    it("should not have aria-describedby when not all rows are selected", function() {
                        expect(column).not.toHaveAttr('aria-describedby');
                    });

                    it("should not have aria-describedby when all rows are selected", function() {
                        checkboxModel.selectAll();
                        expect(column).not.toHaveAttr('aria-describedby');
                    });
                });

                describe("cells", function() {
                    var cell;

                    beforeEach(function() {
                        cell = view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true);
                    });

                    afterEach(function() {
                        cell = null;
                    });

                    it("should have tabIndex", function() {
                        expect(cell).toHaveAttr('tabIndex', -1);
                    });

                    it("should have gridcell role", function() {
                        expect(cell).toHaveAttr('role', 'gridcell');
                    });

                    it("should not have aria-label", function() {
                        expect(cell).not.toHaveAttr('aria-label');
                    });

                    it("should not have aria-labelledby", function() {
                        expect(cell).not.toHaveAttr('aria-labelledby');
                    });

                    it("should have aria-describedby when not selected", function() {
                        expect(cell).toHaveAttr('aria-describedby', column.id + '-cell-description-not-selected');
                    });

                    it("should have aria-describedby when selected", function() {
                        checkboxModel.select(0);
                        expect(cell).toHaveAttr('aria-describedby', column.id + '-cell-description-selected');
                    });
                });
            });

            describe("with showHeaderCheckbox: true", function() {
                beforeEach(function() {
                    makeGrid({
                        checkOnly: true,
                        showHeaderCheckbox: true
                    });
                });

                describe("header", function() {
                    it("should have tabIndex", function() {
                        expect(column).toHaveAttr('tabIndex', -1);
                    });

                    it("should have columnheader role", function() {
                        expect(column).toHaveAttr('role', 'columnheader');
                    });

                    it("should have aria-label", function() {
                        expect(column).toHaveAttr('aria-label', checkboxModel.headerAriaLabel);
                    });

                    it("should not have aria-labelledby", function() {
                        expect(column).not.toHaveAttr('aria-labelledby');
                    });

                    it("should have aria-describedby when not all rows are selected", function() {
                        expect(column).toHaveAttr('aria-describedby', column.id + '-header-description-not-selected');
                    });

                    it("should have aria-describedby when all rows are selected", function() {
                        checkboxModel.selectAll();
                        expect(column).toHaveAttr('aria-describedby', column.id + '-header-description-selected');
                    });
                });

                describe("cells", function() {
                    var cell;

                    beforeEach(function() {
                        cell = view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true);
                    });

                    afterEach(function() {
                        cell = null;
                    });

                    it("should have tabIndex", function() {
                        expect(cell).toHaveAttr('tabIndex', -1);
                    });

                    it("should have gridcell role", function() {
                        expect(cell).toHaveAttr('role', 'gridcell');
                    });

                    it("should not have aria-label", function() {
                        expect(cell).not.toHaveAttr('aria-label');
                    });

                    it("should not have aria-labelledby", function() {
                        expect(cell).not.toHaveAttr('aria-labelledby');
                    });

                    it("should have aria-describedby when not selected", function() {
                        expect(cell).toHaveAttr('aria-describedby', column.id + '-cell-description-not-selected');
                    });

                    it("should have aria-describedby when selected", function() {
                        checkboxModel.select(0);
                        expect(cell).toHaveAttr('aria-describedby', column.id + '-cell-description-selected');
                    });
                });

            });
        });
    });
    describe("checkbox model", function() {
        it("should support renderer", function() {
            makeGrid(
                {
                    type: 'checkboxmodel',
                    renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                        var result = this.defaultRenderer(value);

                        if (record) {
                            return (record.get('id') === 1 || record.get('id') === 2) ? result : '';
                        }
                    },
                    showHeaderCheckbox: false
                }, {
                    allowDeselect: true,
                    scrollable: true
                }
            );

            var allCols = grid.getColumnManager().getColumns(),
            col = allCols[0];

            expect(col.isCheckColumn).toBe(true);
            expect(grid.el.select('.x-grid-checkcolumn').elements.length).toBe(2);
        });
    });
});
