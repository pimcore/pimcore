topSuite('Ext.grid.NavigationModel',
    ['Ext.grid.Panel', 'Ext.grid.column.Widget', 'Ext.form.field.Number',
     'Ext.Button'],
function() {
    // Expect that a row and column are focused.
    // Column index is overall across a locked pair.
    function expectPosition(rowIdx, colIdx) {
        var column = grid.getVisibleColumnManager().getColumns()[colIdx];

        expect(grid.getNavigationModel().getPosition().isEqual(new Ext.grid.CellContext(column.getView()).setPosition(rowIdx, column))).toBe(true);
    }

    function findCell(rowIdx, cellIdx) {
        return grid.getView().getCellInclusive({
            row: rowIdx,
            column: cellIdx
        }, true);
    }

    function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
        var target = findCell(rowIdx, cellIdx);

        jasmine.fireMouseEvent(target, type, x, y, button);
    }

    function triggerCellKeyEvent(rowIdx, cellIdx, type, key) {
        var target = findCell(rowIdx, cellIdx);

        jasmine.fireKeyEvent(target, type, key);
    }

    var describeNotTouch = jasmine.supportsTouch ? xdescribe : describe,
        GridModel = Ext.define(null, {
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

    function makeStore(data) {
        store = new Ext.data.Store({
            model: GridModel,
            data: data || [{
                field1: 1,
                field2: 2,
                field3: 3,
                field4: 4,
                field5: 5,
                field6: 6,
                field7: 7,
                field8: 8,
                field9: 9,
                field10: 10
            }]
        });

        return store;
    }

    function makeGrid(columns, data, cfg, options, locked) {
        options = options || {};
        cfg = cfg || {};

        var i, dataCount, dataRow;

        if (!options.preventColumnCreate && (!columns || typeof columns === 'number')) {
            var colCount = typeof columns === 'number' ? columns : 10;

            columns = [];

            for (i = 1; i <= colCount; i++) {
                columns.push({
                    dataIndex: 'field' + i,
                    text: 'Field ' + i,
                    width: 90,
                    // First column gets locked if we are doing locking tests
                    locked: locked && i === 1
                });
            }
        }

        // Could pass number of required records
        if (typeof data === 'number') {
            dataCount = data;
            data = [];

            for (i = 0; i < dataCount; i++) {
                dataRow = {
                    id: 'rec' + i
                };

                for (var j = 0; j < columns.length; j++) {
                    dataRow[columns[j].dataIndex] = (i + 1) + ', ' + (j + 1);
                }

                data.push(dataRow);
            }
        }

        if (!options.preventStoreCreate) {
            makeStore(data);
        }

        grid = new Ext.grid.Panel(Ext.apply({
            columns: columns,
            store: store,
            width: 600,
            height: 400,
            border: false,
            viewConfig: Ext.apply({
                mouseOverOutBuffer: false,
                deferHighlight: false
            }, cfg.viewConfig)
        }, cfg));

        // Don't use renderTo since that may throw and we won't set "grid"
        // and will then leak the component
        if (cfg.renderTo === undefined) {
            grid.render(Ext.getBody());
        }

        view = grid.getView();
        colRef = grid.getColumnManager().getColumns();
        navModel = view.getNavigationModel();
        selModel = view.getSelectionModel();
    }

    var proto = Ext.view.Table.prototype,
        grid, colRef, store, view, selModel, navModel;

    afterEach(function() {
        Ext.destroy(grid);
        grid = null;
        view = null;
        selModel = null;
    });

    describe('Re-entering grid after sorting', function() {
        it('should scroll last focused row into view on sort', function() {
            makeGrid(null, 500);
            var startPos = new Ext.grid.CellContext(view).setPosition(9, 4);

            navModel.setPosition(9, 4);

            waitsFor(function() {
                return navModel.lastFocused  && navModel.lastFocused.isEqual(startPos);
            });

            runs(function() {
                colRef[4].el.dom.focus();

                // Sort ascending
                jasmine.fireKeyEvent(colRef[4].el, 'keydown', Ext.event.Event.SPACE);

                // View's element Region MUST contain the focused cell.
                expect(view.getEl().getRegion().contains(Ext.fly(view.getCellByPosition(navModel.lastFocused, true)).getRegion())).toBe(true);

                // Sort descending
                jasmine.fireKeyEvent(colRef[4].el, 'keydown', Ext.event.Event.SPACE);

                // View's element Region MUST still contain the focused cell.
                expect(view.getEl().getRegion().contains(Ext.fly(view.getCellByPosition(navModel.lastFocused, true)).getRegion())).toBe(true);
            });
        });
    });

    describe('reacting to programmatic focus', function() {
        it('should set the position correctly', function() {
            makeGrid(null, 500);
            var focusContext = new Ext.grid.CellContext(view).setPosition(0, 0),
                newCell;

            // Focusing the outer focusEl will delegate to cell (0,0) first time in.
            view.focus();

            // Wait until the NavigationModel has processed the onFocusEnter, and synched its position
            waitsFor(function() {
                var pos = view.getNavigationModel().getPosition();

                return pos !== null && pos.isEqual(focusContext) && Ext.Element.getActiveElement() === focusContext.getCell(true);
            }, 'for position(0,0) to be focused');

            runs(function() {
                focusContext = new Ext.grid.CellContext(view).setPosition(2, 2);
                newCell = focusContext.getCell(true);

                // Focus a different cell's DOM
                newCell.focus();
            });

            // Wait until the NavigationModel is synched up.
            waitsFor(function() {
                return view.getNavigationModel().getPosition().isEqual(focusContext) && Ext.Element.getActiveElement() === newCell;
            }, 'for cell (2,2) to be focused');
        });
    });

    describe("row removal", function() {
        var focusAndWait = jasmine.focusAndWait,
            expectFocused = jasmine.expectFocused;

        function makeRemovalSuite(buffered) {
            describe(buffered ? "buffered" : "not buffered", function() {
                describe("without locking", function() {
                    it("should retain focus", function() {
                        makeGrid(null, 10, {
                            bufferedRenderer: buffered
                        });
                        var cell = view.getCell(store.getAt(0), colRef[0]);

                        focusAndWait(cell);
                        runs(function() {
                            store.removeAt(1);
                        });
                        expectFocused(cell);
                        runs(function() {
                            var pos = new Ext.grid.CellContext(view).setPosition(0, 0);

                            expect(navModel.getPosition().isEqual(pos)).toBe(true);
                        });
                    });
                });

                describe("with locking", function() {
                    beforeEach(function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            locked: true
                        }, {
                            dataIndex: 'field2'
                        }], 10, {
                            bufferedRenderer: buffered
                        });
                    });

                    it("should retain focus on the locked part", function() {
                        var cell = view.getCell(store.getAt(0), colRef[0]);

                        focusAndWait(cell);
                        runs(function() {
                            store.removeAt(1);
                        });
                        expectFocused(cell);
                        runs(function() {
                            var pos = new Ext.grid.CellContext(view).setPosition(0, 0);

                            expect(navModel.getPosition().isEqual(pos)).toBe(true);
                        });
                    });

                    it("should retain focus on the unlocked part", function() {
                        var cell = view.getCell(store.getAt(0), colRef[1]);

                        focusAndWait(cell);
                        runs(function() {
                            store.removeAt(1);
                        });
                        expectFocused(cell);
                        runs(function() {
                            var pos = new Ext.grid.CellContext(view).setPosition(0, 1);

                            expect(navModel.getPosition().isEqual(pos)).toBe(true);
                        });
                    });
                });
            });
        }

        makeRemovalSuite(false);
        makeRemovalSuite(true);
    });

    describe("navigation with keys", function() {
        var E = Ext.event.Event;

        function fireCellKey(key) {
            jasmine.fireKeyEvent(navModel.cell, 'keydown', key);
        }

        describe('navigation in a locking grid', function() {

            it('should wrap and navigate from side to side seamlessly', function() {
                makeGrid(4, 100, null, null, true);

                navModel.setPosition(new Ext.grid.CellContext(grid.lockedGrid.view).setPosition(0, 0));
                expectPosition(0, 0);

                fireCellKey(E.RIGHT);
                expectPosition(0, 1);

                fireCellKey(E.RIGHT);
                expectPosition(0, 2);

                fireCellKey(E.RIGHT);
                expectPosition(0, 3);

                fireCellKey(E.RIGHT);
                expectPosition(1, 0);

                fireCellKey(E.RIGHT);
                expectPosition(1, 1);

                fireCellKey(E.RIGHT);
                expectPosition(1, 2);

                fireCellKey(E.RIGHT);
                expectPosition(1, 3);

                fireCellKey(E.RIGHT);
                expectPosition(2, 0);

                fireCellKey(E.RIGHT);
                expectPosition(2, 1);

                fireCellKey(E.RIGHT);
                expectPosition(2, 2);

                fireCellKey(E.RIGHT);
                expectPosition(2, 3);

                // Now do left arrow until we get back to 0, 0
                fireCellKey(E.LEFT);
                expectPosition(2, 2);

                fireCellKey(E.LEFT);
                expectPosition(2, 1);

                fireCellKey(E.LEFT);
                expectPosition(2, 0);

                fireCellKey(E.LEFT);
                expectPosition(1, 3);

                fireCellKey(E.LEFT);
                expectPosition(1, 2);

                fireCellKey(E.LEFT);
                expectPosition(1, 1);

                fireCellKey(E.LEFT);
                expectPosition(1, 0);

                fireCellKey(E.LEFT);
                expectPosition(0, 3);

                fireCellKey(E.LEFT);
                expectPosition(0, 2);

                fireCellKey(E.LEFT);
                expectPosition(0, 1);

                fireCellKey(E.LEFT);
                expectPosition(0, 0);
            });

            describe("cellFocusable", function() {
                describe("final boundary when switching view", function() {
                    it("should not navigate when crossing to the unlocked side", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            locked: true
                        }, {
                            dataIndex: 'field2',
                            cellFocusable: false
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.lockedGrid.view).setPosition(0, 0));

                        fireCellKey(E.RIGHT);
                        expectPosition(0, 0);
                    });

                    it("should not navigate when crossing to the locked side", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            locked: true,
                            cellFocusable: false
                        }, {
                            dataIndex: 'field2'
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.normalGrid.view).setPosition(0, 0));

                        fireCellKey(E.LEFT);
                        expectPosition(0, 1);
                    });
                });

                describe("boundary when switching view", function() {
                    it("should navigate to the next available column in the unlocked view", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            locked: true
                        }, {
                            dataIndex: 'field2',
                            cellFocusable: false
                        }, {
                            dataIndex: 'field3'
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.lockedGrid.view).setPosition(0, 0));

                        fireCellKey(E.RIGHT);
                        expectPosition(0, 2);
                    });

                    it("should navigate to the next available column in the locked view", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            locked: true
                        }, {
                            dataIndex: 'field2',
                            locked: true,
                            cellFocusable: false
                        }, {
                            dataIndex: 'field3'
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.normalGrid.view).setPosition(0, 0));

                        fireCellKey(E.LEFT);
                        expectPosition(0, 0);
                    });
                });
            });
        });

        describe('navigation in a non-locking grid', function() {
            it('should wrap and navigate correctly', function() {
                makeGrid(4, 100);

                navModel.setPosition(new Ext.grid.CellContext(grid.view).setPosition(0, 0));
                expectPosition(0, 0);

                fireCellKey(E.RIGHT);
                expectPosition(0, 1);

                fireCellKey(E.RIGHT);
                expectPosition(0, 2);

                fireCellKey(E.RIGHT);
                expectPosition(0, 3);

                fireCellKey(E.RIGHT);
                expectPosition(1, 0);

                fireCellKey(E.RIGHT);
                expectPosition(1, 1);

                fireCellKey(E.RIGHT);
                expectPosition(1, 2);

                fireCellKey(E.RIGHT);
                expectPosition(1, 3);

                fireCellKey(E.RIGHT);
                expectPosition(2, 0);

                fireCellKey(E.RIGHT);
                expectPosition(2, 1);

                fireCellKey(E.RIGHT);
                expectPosition(2, 2);

                fireCellKey(E.RIGHT);
                expectPosition(2, 3);

                // Now do left arrow until we get back to 0, 0
                fireCellKey(E.LEFT);
                expectPosition(2, 2);

                fireCellKey(E.LEFT);
                expectPosition(2, 1);

                fireCellKey(E.LEFT);
                expectPosition(2, 0);

                fireCellKey(E.LEFT);
                expectPosition(1, 3);

                fireCellKey(E.LEFT);
                expectPosition(1, 2);

                fireCellKey(E.LEFT);
                expectPosition(1, 1);

                fireCellKey(E.LEFT);
                expectPosition(1, 0);

                fireCellKey(E.LEFT);
                expectPosition(0, 3);

                fireCellKey(E.LEFT);
                expectPosition(0, 2);

                fireCellKey(E.LEFT);
                expectPosition(0, 1);

                fireCellKey(E.LEFT);
                expectPosition(0, 0);
            });

            describe("cellFocusable", function() {
                describe("final boundary moving", function() {
                    it("should not navigate when going forward", function() {
                        makeGrid([{
                            dataIndex: 'field1'
                        }, {
                            dataIndex: 'field2',
                            cellFocusable: false
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.view).setPosition(0, 0));

                        fireCellKey(E.RIGHT);
                        expectPosition(0, 0);
                    });

                    it("should not navigate when going backward", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            cellFocusable: false
                        }, {
                            dataIndex: 'field2'
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.view).setPosition(0, 1));

                        fireCellKey(E.LEFT);
                        expectPosition(0, 1);
                    });
                });

                describe("boundary skipping", function() {
                    it("should navigate to the next available column when moving right", function() {
                        makeGrid([{
                            dataIndex: 'field1'
                        }, {
                            dataIndex: 'field2',
                            cellFocusable: false
                        }, {
                            dataIndex: 'field3'
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.view).setPosition(0, 0));

                        fireCellKey(E.RIGHT);
                        expectPosition(0, 2);
                    });

                    it("should navigate to the next available column when moving left", function() {
                        makeGrid([{
                            dataIndex: 'field1'
                        }, {
                            dataIndex: 'field2',
                            cellFocusable: false
                        }, {
                            dataIndex: 'field3'
                        }]);

                        navModel.setPosition(new Ext.grid.CellContext(grid.view).setPosition(0, 2));

                        fireCellKey(E.LEFT);
                        expectPosition(0, 0);
                    });
                });
            });
        });
    });

    describeNotTouch('With widget column', function() {
        var People = Ext.define(null, {
                extend: 'Ext.data.Model',
                idProperty: 'peopleId',
                fields: [
                    { name: 'name', type: 'string' },
                    { name: 'age', type: 'int' },
                    { name: 'location', type: 'string' }
                ]
            }),
            store,
            grid,
            navModel,
            widgetColumn,
            pos;

        function createGrid(storeCfg, gridCfg) {
            store = Ext.create('Ext.data.Store', Ext.apply({
                model: People,
                data: [
                    { name: 'Jimmy', age: 22, location: 'United States' },
                    { name: 'Sally', age: 25, location: 'England' },
                    { name: 'Billy', age: 26, location: 'Mexico' }
                ]
            }, storeCfg));
            grid = Ext.create('Ext.grid.Panel', Ext.apply({
                itemId: 'peopleGrid',
                width: 400,
                height: 200,
                margin: 20,
                frame: true,
                title: 'People',
                renderTo: Ext.getBody(),
                store: store,
                selModel: 'cellmodel',
                columns: [{
                    header: 'Name',
                    flex: 1,
                    dataIndex: 'name'
                }, {
                    id: 'locId',
                    text: 'Location',
                    width: 160,
                    dataIndex: 'location',
                    xtype: 'widgetcolumn',
                    stopSelection: false,
                    widget: {
                        xtype: 'button',
                        itemId: 'projButton',
                        listeners: {
                            click: function(button) {
                                pos = grid.getSelectionModel().getPosition();
                            }
                        }
                    }
                }, {
                    xtype: 'widgetcolumn',
                    header: 'Age',
                    width: 80,
                    dataIndex: 'age',
                    stopSelection: true,
                    widget: {
                            xtype: 'numberfield'
                    }
                }]
            }, gridCfg));
            navModel = grid.getNavigationModel();
            widgetColumn = grid.down('widgetcolumn');
        }

        beforeEach(function() {
            createGrid();
        });
        afterEach(function() {
            grid.destroy();
        });
        it('should select when clicking a widget and stopSelection is false', function() {
            var row0Button = widgetColumn.getWidget(store.getAt(0));

            jasmine.fireMouseEvent(row0Button.el, 'click');

            // The selection must get set
            waitsFor(function() {
                pos = grid.getSelectionModel().getPosition();

                return pos && pos.rowIdx === 0 && pos.colIdx === 1;
            });
        });
    });

    describe('With non-focusable column', function() {
        it('should skip the non-focusable cells', function() {
            makeGrid(3, 500);
            colRef[0].cellFocusable = false;
            view.refreshView();

            colRef[0].focus();

            waitsFor(function() {
                return colRef[0].containsFocus;
            }, 'column header 0 to focus');

            // TAB off column header 0
            runs(function() {
                jasmine.simulateTabKey(colRef[0].el, true);
            });

            // Should skip on to column 1
            waitsFor(function() {
                return new Ext.grid.CellContext(view).setPosition(0, 1).isEqual(navModel.getPosition());
            }, 'cell 0,1 to focus');

            // RIGHT to column 2
            runs(function() {
                // Sort ascending
                jasmine.fireKeyEvent(Ext.Element.getActiveElement(), 'keydown', Ext.event.Event.RIGHT);
            });

            waitsFor(function() {
                return new Ext.grid.CellContext(view).setPosition(0, 2).isEqual(navModel.getPosition());
            }, 'cell 0,2 to focus');

            // RIGHT again should wrap
            runs(function() {
                // Sort ascending
                jasmine.fireKeyEvent(Ext.Element.getActiveElement(), 'keydown', Ext.event.Event.RIGHT);
            });

            // But skip column 0, and go to 1,1
            waitsFor(function() {
                return new Ext.grid.CellContext(view).setPosition(1, 1).isEqual(navModel.getPosition());
            }, 'cell 1,1 to focus');
        });
    });

});
