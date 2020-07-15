topSuite("Ext.selection.CellModel",
    ['Ext.grid.Panel', 'Ext.app.ViewModel', 'Ext.grid.plugin.DragDrop'],
function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        grid, view, store, selModel, colRef;

    function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
        var target = findCell(rowIdx, cellIdx);

        jasmine.fireMouseEvent(target, type, x, y, button);
    }

    function triggerCellContextMenu(rowIdx, cellIdx) {
        var target = findCell(rowIdx, cellIdx);

        jasmine.fireMouseEvent(target, 'mousedown', 0, 0, 2);
        jasmine.doFireMouseEvent(target, 'contextmenu');
    }

    function findCell(rowIdx, cellIdx) {
        return grid.getView().getCellInclusive({
            row: rowIdx,
            column: cellIdx
        }, true);
    }

    function makeGrid(columns, cfg, selModelCfg) {
        Ext.define('spec.CellModel', {
            extend: 'Ext.data.Model',
            fields: [
                'field1',
                'field2',
                'field3'
            ]
        });

        selModel = new Ext.selection.CellModel(selModelCfg || {});

        var data = [],
            defaultCols = [],
            i;

        if (!columns) {
            for (i = 1; i <= 5; ++i) {
                defaultCols.push({
                    name: 'F' + i,
                    dataIndex: 'field' + i
                });
            }
        }

        for (i = 1; i <= 10; ++i) {
            data.push({
                field1: i + '.' + 1,
                field2: i + '.' + 2,
                field3: i + '.' + 3,
                field4: i + '.' + 4,
                field5: i + '.' + 5
            });
        }

        store = new Ext.data.Store({
            model: spec.CellModel,
            data: data
        });

        grid = new Ext.grid.Panel(Ext.apply({
            columns: columns || defaultCols,
            store: store,
            selModel: selModel,
            width: 1000,
            height: 500,
            renderTo: Ext.getBody()
        }, cfg));
        view = grid.getView();
        selModel = grid.getSelectionModel();
        colRef = grid.getColumnManager().getColumns();
    }

    afterEach(function() {
        Ext.destroy(grid, store);
        selModel = grid = store = view = null;
        Ext.undefine('spec.CellModel');
        Ext.data.Model.schema.clear();
    });

    itNotTouch('should select when right-clicking', function() {
        makeGrid();
        triggerCellContextMenu(0, 0);

        expect(selModel.getSelection().length).toBe(1);
    });

    describe("deselectOnContainerClick", function() {
        it("should default to false", function() {
            makeGrid();
            expect(selModel.deselectOnContainerClick).toBe(false);
        });

        describe("deselectOnContainerClick: false", function() {
            it("should not deselect when clicking the container", function() {
                makeGrid(null, null, {
                    deselectOnContainerClick: false
                });
                selModel.selectByPosition({
                    row: 0,
                    column: 0
                });
                jasmine.fireMouseEvent(view.getEl(), 'click', 800, 200);
                var pos = selModel.getPosition();

                expect(pos.record).toBe(store.getAt(0));
                expect(pos.column).toBe(colRef[0]);
            });
        });

        describe("deselectOnContainerClick: true", function() {
            it("should deselect when clicking the container", function() {
                makeGrid(null, null, {
                    deselectOnContainerClick: true
                });
                selModel.selectByPosition({
                    row: 0,
                    column: 0
                });
                jasmine.fireMouseEvent(view.getEl(), 'click', 800, 200);
                expect(selModel.getPosition()).toBeNull();
            });
        });
    });

    describe("hidden columns", function() {
        it("should take a hidden column into account on click", function() {
            makeGrid([{
                dataIndex: 'field1'
            }, {
                dataIndex: 'field2',
                hidden: true
            }, {
                dataIndex: 'field3'
            }]);
            triggerCellMouseEvent('click', 0, 2);
            var pos = selModel.getPosition();

            expect(pos.column).toBe(colRef[2]);
            expect(pos.record).toBe(grid.getStore().getAt(0));
        });
    });

    describe("store actions", function() {
        it("should have no selection when clearing the store", function() {
            makeGrid();
            selModel.selectByPosition({
                row: 1,
                column: 0
            });
            store.removeAll();
            expect(selModel.getPosition()).toBeNull();
        });

        it("should update the position when removing records", function() {
            makeGrid();
            var rec = store.getAt(8);

            selModel.selectByPosition({
                column: 1,
                row: 8
            });
            store.removeAt(0);
            store.removeAt(0);
            store.removeAt(0);
            store.removeAt(0);

            var pos = selModel.getPosition();

            expect(pos.column).toBe(colRef[1]);
            expect(pos.record).toBe(rec);
        });

        it("should update the position on inserting records", function() {
            makeGrid();
            var rec = store.getAt(1);

            selModel.selectByPosition({
                column: 2,
                row: 1
            });
            store.insert(0, {});
            store.insert(0, {});
            store.insert(0, {});
            store.insert(0, {});

            var pos = selModel.getPosition();

            expect(pos.column).toBe(colRef[2]);
            expect(pos.record).toBe(rec);
        });

        it("should update the position on moving records", function() {
            makeGrid();
            var rec = store.getAt(0);

            selModel.selectByPosition({
                column: 2,
                row: 0
            });

            // Move record 0 to be record 9
            store.add(rec);

            // Cell selectino should still be consistent
            var pos = selModel.getPosition();

            expect(pos.column).toBe(colRef[2]);
            expect(pos.record).toBe(rec);
            expect(pos.rowIdx).toBe(9);
        });
    });

    it("should render cells with the x-grid-cell-selected cls (EXTJSIV-11254)", function() {
        makeGrid();
        selModel.select(0);

        grid.getStore().sort([{
            property: 'name',
            direction: 'DESC'
        }]);

        var col = grid.getColumnManager().getHeaderAtIndex(0);

        expect(grid.getView().getCell(0, col)).toHaveCls('x-grid-cell-selected');
    });

    describe("column move", function() {
        it("should have the correct position after moving column", function() {
            makeGrid();
            triggerCellMouseEvent('click', 0, 0);
            grid.headerCt.move(0, 3);
            var pos = selModel.getCurrentPosition();

            expect(pos.column).toBe(3);
            expect(pos.row).toBe(0);
            expect(pos.record).toBe(grid.getStore().getAt(0));
            expect(pos.columnHeader).toBe(colRef[0]);
        });

        it("should not fire a change event", function() {
            makeGrid();
            triggerCellMouseEvent('click', 0, 0);
            var spy = jasmine.createSpy();

            selModel.on('selectionchange', spy);
            grid.headerCt.move(0, 3);
            expect(spy).not.toHaveBeenCalled();
        });
    });

    describe('with DD', function() {
        it('should start dragging', function() {
            makeGrid(null, {
                viewConfig: {
                    plugins: {
                        ptype: 'gridviewdragdrop',
                        dragText: 'Drag and drop to reorganize'
                    }
                }
            });
            var plugin = grid.view.findPlugin('gridviewdragdrop');

            runs(function() {
                triggerCellMouseEvent('mousedown', 0, 0, null, 10, 30);
            });

            // Longpress to drag on touch
            if (jasmine.supportsTouch) {
                waits(1500);
            }

            runs(function() {
                jasmine.fireMouseEvent(document.body, 'mousemove', 20, 20);
            });

            waitsFor(function() {
                return plugin.dragZone.proxy.el.isVisible();
            });

            runs(function() {
                var proxyInner;

                // The proxy should contain the configured dragText
                proxyInner = plugin.dragZone.proxy.el.down('.' + Ext.baseCSSPrefix + 'grid-dd-wrap', true);
                expect(proxyInner).not.toBeFalsy();

                // Destroying the grid during a drag should throw no errors.
                grid.destroy();
                jasmine.fireMouseEvent(document.body, 'mousemove', 40, 40);
                // Clean up
                jasmine.fireMouseEvent(document.body, 'mouseup');
            });
        });
    });

    describe("view model selection", function() {
        var viewModel, spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
            viewModel = new Ext.app.ViewModel();
        });

        afterEach(function() {
            spy = selModel = viewModel = null;
        });

        function selectNotify(rec) {
            selModel.select(rec);
            viewModel.notify();
        }

        function byName(name) {
            var index = store.findExact('name', name);

            return store.getAt(index);
        }

        describe("reference", function() {
            beforeEach(function() {
                makeGrid(null, {
                    reference: 'userList',
                    viewModel: viewModel
                });
                viewModel.bind('{userList.selection}', spy);
                viewModel.notify();
            });

            it("should publish null by default", function() {
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBeNull();
                expect(args[1]).toBeUndefined();
            });

            it("should publish the value when selected", function() {
                var rec = store.getAt(0);

                selectNotify(rec);
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(rec);
                expect(args[1]).toBeNull();
            });

            it("should publish when the selection is changed", function() {
                var rec1 = store.getAt(0),
                    rec2 = store.getAt(1);

                selectNotify(rec1);
                spy.reset();
                selectNotify(rec2);
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(rec2);
                expect(args[1]).toBe(rec1);
            });

            it("should publish when an item is deselected", function() {
                var rec = store.getAt(0);

                selectNotify(rec);
                spy.reset();
                selModel.deselect(rec);
                viewModel.notify();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBeNull();
                expect(args[1]).toBe(rec);
            });
        });

        describe("two way binding", function() {
            beforeEach(function() {
                makeGrid(null, {
                    viewModel: viewModel,
                    bind: {
                        selection: '{foo}'
                    }
                });
                viewModel.bind('{foo}', spy);
                viewModel.notify();
            });

            describe("changing the selection", function() {
                it("should trigger the binding when adding a selection", function() {
                    var rec = store.getAt(0);

                    selectNotify(rec);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(rec);
                    expect(args[1]).toBeUndefined();
                });

                it("should trigger the binding when changing the selection", function() {
                    var rec1 = store.getAt(0),
                        rec2 = store.getAt(1);

                    selectNotify(rec1);
                    spy.reset();
                    selectNotify(rec2);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(rec2);
                    expect(args[1]).toBe(rec1);
                });

                it("should trigger the binding when an item is deselected", function() {
                    var rec = store.getAt(0);

                    selectNotify(rec);
                    spy.reset();
                    selModel.deselect(rec);
                    viewModel.notify();
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBeNull();
                    expect(args[1]).toBe(rec);
                });
            });

            describe("changing the viewmodel value", function() {
                it("should select the record when setting the value", function() {
                    var rec = store.getAt(0);

                    viewModel.set('foo', rec);
                    viewModel.notify();
                    expect(selModel.isSelected(rec)).toBe(true);
                });

                it("should select the record when updating the value", function() {
                    var rec1 = store.getAt(0),
                        rec2 = store.getAt(1);

                    viewModel.set('foo', rec1);
                    viewModel.notify();
                    viewModel.set('foo', rec2);
                    viewModel.notify();
                    expect(selModel.isSelected(rec1)).toBe(false);
                    expect(selModel.isSelected(rec2)).toBe(true);
                });

                it("should deselect when clearing the value", function() {
                    var rec = store.getAt(0);

                    viewModel.set('foo', rec);
                    viewModel.notify();
                    viewModel.set('foo', null);
                    viewModel.notify();
                    expect(selModel.isSelected(rec)).toBe(false);
                });
            });
        });
    });
});
