topSuite("grid-keys",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore'],
function() {
    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var grid, view, store,
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

            var TAB = 9,
                PAGE_UP = 33,
                PAGE_DOWN = 34,
                END = 35,
                HOME = 36,
                LEFT = 37,
                UP = 38,
                RIGHT = 39,
                DOWN = 40;

            function clickAndKey(rowIdx, cellIdx, key, altKey) {
                var visibleCellIdx = view.getHeaderByCell(view.getCellInclusive({ row: rowIdx, column: cellIdx }, true)).getVisibleIndex();

                view.getNavigationModel().setPosition(rowIdx, visibleCellIdx);
                triggerCellMouseEvent('click',  rowIdx, cellIdx);
                triggerCellKeyEvent('keydown',  rowIdx, cellIdx, key, altKey);
                triggerCellKeyEvent('keyup',    rowIdx, cellIdx, key, altKey);
                triggerCellKeyEvent('keypress', rowIdx, cellIdx, key, altKey);
            }

            function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireMouseEvent(target, type, x, y, button);
            }

            function triggerCellKeyEvent(type, rowIdx, cellIdx, key, altKey) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireKeyEvent(target, type, key, null, null, altKey);
            }

            function findCell(rowIdx, cellIdx) {
                return grid.getView().getCellInclusive({
                    row: rowIdx,
                    column: cellIdx
                }, true);
            }

            function makeGrid(selModel, columns, rows) {
                var data = [],
                    defaultCols = [],
                    i;

                for (i = 1; i <= 4; ++i) {
                    defaultCols.push({
                        name: 'F' + i,
                        dataIndex: 'field' + i
                    });
                }

                rows = rows || 5;

                for (i = 1; i <= rows; ++i) {
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

                store = new Ext.data.Store({
                    model: GridEventModel,
                    data: data
                });

                grid = new Ext.grid.Panel({
                    columns: columns || defaultCols,
                    store: store,
                    selType: selModel || 'rowmodel',
                    width: 1000,
                    height: 500,
                    bufferedRenderer: buffered,
                    viewConfig: {
                        mouseOverOutBuffer: 0
                    },
                    renderTo: Ext.getBody()
                });

                view = grid.getView();
            }

            afterEach(function() {
                Ext.destroy(grid, store);
                grid = store = view = null;
                Ext.data.Model.schema.clear();
            });

            describe("row model", function() {
                describe("nav keys", function() {
                    beforeEach(function() {
                        makeGrid();
                        grid.view.el.dom.focus();
                    });
                    describe("down", function() {
                        it("should move down a row when pressing the down key on the first row", function() {
                            clickAndKey(0, 0, DOWN);
                            expect(grid.getSelectionModel().getSelection()[0]).toBe(store.getAt(1));
                        });

                        it("should move down a row when pressing the down key on a middle row", function() {
                            clickAndKey(2, 0, DOWN);
                            expect(grid.getSelectionModel().getSelection()[0]).toBe(store.getAt(3));
                        });

                        it("should not move down a row when pressing the down key on the last row", function() {
                            clickAndKey(4, 0, DOWN);
                            expect(grid.getSelectionModel().getSelection()[0]).toBe(store.getAt(4));
                        });
                    });

                    describe("up", function() {
                        it("should move up a row when pressing the up key on the last row", function() {
                            clickAndKey(4, 0, UP);
                            expect(grid.getSelectionModel().getSelection()[0]).toBe(store.getAt(3));
                        });

                        it("should move up a row when pressing the up key on a middle row", function() {
                            clickAndKey(3, 0, UP);
                            expect(grid.getSelectionModel().getSelection()[0]).toBe(store.getAt(2));
                        });

                        it("should not move up a row when pressing the up key on the first row", function() {
                            clickAndKey(0, 0, UP);
                            expect(grid.getSelectionModel().getSelection()[0]).toBe(store.getAt(0));
                        });
                    });
                });

                describe("special keys", function() {
                    // Selection via Ext.view.Table#ensureVisible is async so we need to
                    // wait for a change in selection to match the desired record. This
                    // is only necessary in this suite since these special keys will
                    // trigger selection of a record not already in view, thus selection
                    // will not immediately occur.
                    var selectionChange = function(desiredRecord) {
                        return function() {
                            return grid.selModel.getSelection()[0] === desiredRecord;
                        };
                    };

                    beforeEach(function() {
                        makeGrid(null, null, 50);
                    });

                    it("should move to the end of the visible rows on page down", function() {
                        var visible = grid.getNavigationModel().getRowsVisible();

                        clickAndKey(0, 0, PAGE_DOWN);
                        waitsFor(selectionChange(store.getAt(visible)), 'last visible row to be selected');
                    });

                    it("should move to the top of the visible rows on page up", function() {
                        var visible = grid.getNavigationModel().getRowsVisible();

                        clickAndKey(49, 0, PAGE_UP);
                        waitsFor(selectionChange(store.getAt(49 - visible)), 'first visible row to be selected');
                    });

                    it("should move to the last cell on ALT+end", function() {
                        clickAndKey(0, 0, END, true);
                        waitsFor(selectionChange(store.getAt(49)), 'last cell to be selected');

                    });

                    it("should move to the first cell on ALT+home", function() {
                        clickAndKey(49, 0, HOME, true);
                        waitsFor(selectionChange(store.getAt(0)), 'first cell to be selected');
                    });
                });
            });

            describe("cell model", function() {
                function expectSelection(row, column) {
                    var pos = grid.getSelectionModel().getCurrentPosition();

                    expect(pos.row).toBe(row);
                    expect(pos.column).toBe(column);
                }

                describe("simple movement", function() {
                    beforeEach(function() {
                        makeGrid('cellmodel');
                    });

                    describe("left", function() {
                        it("should not move when at the first cell", function() {
                            clickAndKey(0, 0, LEFT);
                            expectSelection(0, 0);
                        });

                        it("should move the position one to the left", function() {
                            clickAndKey(3, 2, LEFT);
                            expectSelection(3, 1);
                        });

                        it("should maintain vertical position if not wrapping", function() {
                            clickAndKey(2, 1, LEFT);
                            expectSelection(2, 0);
                        });

                        it("should wrap to the previous row where possible", function() {
                            clickAndKey(4, 0, LEFT);
                            expectSelection(3, 3);
                        });
                    });

                    describe("up", function() {
                        it("should not move when in the first row", function() {
                            clickAndKey(0, 2, UP);
                            expectSelection(0, 2);
                        });

                        it("should move the position one up", function() {
                            clickAndKey(3, 2, UP);
                            expectSelection(2, 2);
                        });

                        it("should maintain the vertical position", function() {
                            clickAndKey(4, 1, UP);
                            expectSelection(3, 1);
                        });
                    });

                    describe("right", function() {
                        it("should not move when at the last cell", function() {
                            clickAndKey(4, 3, RIGHT);
                            expectSelection(4, 3);
                        });

                        it("should move the position one to the right", function() {
                            clickAndKey(3, 2, RIGHT);
                            expectSelection(3, 3);
                        });

                        it("should maintain vertical position if not wrapping", function() {
                            clickAndKey(2, 1, RIGHT);
                            expectSelection(2, 2);
                        });

                        it("should wrap to the next row where possible", function() {
                            clickAndKey(2, 3, RIGHT);
                            expectSelection(3, 0);
                        });
                    });

                    describe("down", function() {
                        it("should not move when in the last row", function() {
                            clickAndKey(4, 1, DOWN);
                            expectSelection(4, 1);
                        });

                        it("should move the position one down", function() {
                            clickAndKey(3, 2, DOWN);
                            expectSelection(4, 2);
                        });

                        it("should maintain the vertical position", function() {
                            clickAndKey(1, 2, DOWN);
                            expectSelection(2, 2);
                        });
                    });
                });

                describe("hidden columns", function() {
                    describe("left", function() {
                        it("should skip over a hidden first column (left key)", function() {
                            makeGrid('cellmodel', [{
                                hidden: true,
                                dataIndex: 'field1'
                            }, {
                                dataIndex: 'field2'
                            }, {
                                dataIndex: 'field3'
                            }]);
                            clickAndKey(1, 1, LEFT);
                            expectSelection(0, 2);
                        });

                        it("should skip over multiple hidden first columns (left key)", function() {
                            makeGrid('cellmodel', [{
                                hidden: true,
                                dataIndex: 'field1'
                            }, {
                                hidden: true,
                                dataIndex: 'field2'
                            }, {
                                dataIndex: 'field3'
                            }, {
                                dataIndex: 'field4'
                            }]);
                            clickAndKey(1, 2, LEFT);
                            expectSelection(0, 3);
                        });

                        it("should skip over hidden middle columns (left key)", function() {
                            makeGrid('cellmodel', [{
                                dataIndex: 'field1'
                            }, {
                                hidden: true,
                                dataIndex: 'field2'
                            }, {
                                hidden: true,
                                dataIndex: 'field3'
                            }, {
                                dataIndex: 'field4'
                            }]);
                            clickAndKey(0, 3, LEFT);
                            expectSelection(0, 0);
                        });

                        it("should skip over a hidden last column (left key)", function() {
                            makeGrid('cellmodel', [{
                                dataIndex: 'field1'
                            }, {
                                dataIndex: 'field2'
                            }, {
                                hidden: true,
                                dataIndex: 'field3'
                            }]);
                            clickAndKey(1, 0, LEFT);
                            expectSelection(0, 1);
                        });

                        it("should skip over multiple hidden last columns (left key)", function() {
                            makeGrid('cellmodel', [{
                                dataIndex: 'field1'
                            }, {
                                dataIndex: 'field2'
                            }, {
                                hidden: true,
                                dataIndex: 'field3'
                            }, {
                                hidden: true,
                                dataIndex: 'field4'
                            }]);
                            clickAndKey(1, 0, LEFT);
                            expectSelection(0, 1);
                        });
                    });

                    describe("right", function() {
                        it("should skip over a hidden first column (right key)", function() {
                            makeGrid('cellmodel', [{
                                hidden: true,
                                dataIndex: 'field1'
                            }, {
                                dataIndex: 'field2'
                            }, {
                                dataIndex: 'field3'
                            }]);
                            clickAndKey(0, 2, RIGHT);
                            expectSelection(1, 1);
                        });

                        it("should skip over multiple hidden first columns (right key)", function() {
                            makeGrid('cellmodel', [{
                                hidden: true,
                                dataIndex: 'field1'
                            }, {
                                hidden: true,
                                dataIndex: 'field2'
                            }, {
                                dataIndex: 'field3'
                            }, {
                                dataIndex: 'field4'
                            }]);
                            clickAndKey(0, 3, RIGHT);
                            expectSelection(1, 2);
                        });

                        it("should skip over hidden middle columns (right key)", function() {
                            makeGrid('cellmodel', [{
                                dataIndex: 'field1'
                            }, {
                                hidden: true,
                                dataIndex: 'field2'
                            }, {
                                hidden: true,
                                dataIndex: 'field3'
                            }, {
                                dataIndex: 'field4'
                            }]);
                            clickAndKey(0, 0, RIGHT);
                            expectSelection(0, 3);
                        });

                        it("should skip over a hidden last column (right key)", function() {
                            makeGrid('cellmodel', [{
                                dataIndex: 'field1'
                            }, {
                                dataIndex: 'field2'
                            }, {
                                hidden: true,
                                dataIndex: 'field3'
                            }]);
                            clickAndKey(0, 1, RIGHT);
                            expectSelection(1, 0);
                        });

                        it("should skip over multiple hidden last columns (right key)", function() {
                            makeGrid('cellmodel', [{
                                dataIndex: 'field1'
                            }, {
                                dataIndex: 'field2'
                            }, {
                                hidden: true,
                                dataIndex: 'field3'
                            }, {
                                hidden: true,
                                dataIndex: 'field4'
                            }]);
                            clickAndKey(0, 1, RIGHT);
                            expectSelection(1, 0);
                        });
                    });
                });
            });
        });
    }

    createSuite(false);
    createSuite(true);
});
