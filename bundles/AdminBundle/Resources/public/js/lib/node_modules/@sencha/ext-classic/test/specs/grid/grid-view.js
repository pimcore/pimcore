topSuite("grid-view",
    [false, 'Ext.grid.Panel', 'Ext.grid.feature.Grouping', 'Ext.grid.plugin.DragDrop'],
function() {
    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var grid, view, navModel, locked,
                createGrid = function() {
                    grid = new Ext.grid.Panel({
                        width: 600,
                        height: 300,
                        bufferedRenderer: buffered,
                        renderTo: Ext.getBody(),
                        store: {
                            model: spec.LockedModel,
                            data: [{
                                f1: 1,
                                f2: 2,
                                f3: 3,
                                f4: 4
                            }, {
                                f1: 5,
                                f2: 6,
                                f3: 7,
                                f4: 8
                            }, {
                                f1: 9,
                                f2: 10,
                                f3: 11,
                                f4: 12
                            }, {
                                f1: 13,
                                f2: 14,
                                f3: 15,
                                f4: 16
                            }]
                        },
                        columns: [{
                            locked: locked,
                            dataIndex: 'f1'
                        }, {
                            locked: true,
                            dataIndex: 'f2'
                        }, {
                            dataIndex: 'f3'
                        }, {
                            dataIndex: 'f4'
                        }]
                    });

                view = grid.view;
                navModel = grid.getNavigationModel();
            };

            beforeEach(function() {
                Ext.define('spec.LockedModel', {
                    extend: 'Ext.data.Model',
                    fields: ['f1', 'f2', 'f3', 'f4']
                });
            });

            afterEach(function() {
                Ext.undefine('spec.LockedModel');
                Ext.data.Model.schema.clear();
                Ext.destroy(grid);
                grid = null;
                locked = false;
            });

            describe("locked view", function() {
                var innerSelector;

                beforeEach(function() {
                    locked = true;
                    createGrid();
                    innerSelector = grid.normalGrid.getView().innerSelector;
                });

                describe("getCellInclusive", function() {
                    it("should be able to get a cell in the locked area", function() {
                        var cell = grid.getView().getCellInclusive({
                            row: 0,
                            column: 0
                        }, true);

                        expect(cell.querySelector(innerSelector).innerHTML).toBe('1');
                    });

                    it("should be able to get a cell in the unlocked area", function() {
                        var cell = grid.getView().getCellInclusive({
                            row: 3,
                            column: 3
                        }, true);

                        expect(cell.querySelector(innerSelector).innerHTML).toBe('16');
                    });

                    it("should return false if the cell doesn't exist", function() {
                        var cell = grid.getView().getCellInclusive({
                            row: 20,
                            column: 20
                        }, true);

                        expect(cell).toBe(false);
                    });

                    it("should return a dom element if the returnDom param is passed", function() {
                        var cell = grid.getView().getCellInclusive({
                            row: 1,
                            column: 1
                        }, true);

                        expect(cell.tagName).not.toBeUndefined();
                        expect(cell.querySelector(innerSelector).innerHTML).toBe('6');
                    });

                    it("should return an Element instance if returnDom param is not used", function() {
                        var cell = grid.getView().getCellInclusive({
                            row: 1,
                            column: 1
                        });

                        expect(cell instanceof Ext.dom.Element).toBe(true);
                        expect(cell.down(innerSelector, true).innerHTML).toBe('6');

                        cell.destroy();
                    });
                });

                describe('reconfigure', function() {
                    beforeEach(function() {
                        // Suppress console warnings about Store created with no model
                        spyOn(Ext.log, 'warn');
                    });

                    it('should use the new store to refresh', function() {
                        expect(grid.lockedGrid.view.all.getCount()).toBe(4);
                        expect(grid.normalGrid.view.all.getCount()).toBe(4);

                        grid.reconfigure(new Ext.data.Store(), [{ dataIndex: name, locked: true }, { dataIndex: 'name' }]);

                        // Should have refreshed both sides to have no rows.
                        expect(grid.lockedGrid.view.all.getCount()).toBe(0);
                        expect(grid.normalGrid.view.all.getCount()).toBe(0);
                    });
                });
            });

            describe('FocusEnter', function() {
                describe('after a reconfigure', function() {
                    xit('should restore focus to the closest cell by recIdx/colIdx', function() {
                        createGrid();

                        var cell_22 = new Ext.grid.CellContext(view).setPosition(2, 2);

                        navModel.setPosition(2, 2);

                        waitsFor(function() {
                            return Ext.Element.getActiveElement() === cell_22.getCell(true);
                        });
                        runs(function() {
                            grid.reconfigure(new Ext.data.Store({
                                model: spec.LockedModel,
                                data: [{
                                    f1: 1,
                                    f2: 2,
                                    f3: 3,
                                    f4: 4
                                }, {
                                    f1: 5,
                                    f2: 6,
                                    f3: 7,
                                    f4: 8
                                }, {
                                    f1: 9,
                                    f2: 10,
                                    f3: 11,
                                    f4: 12
                                }, {
                                    f1: 13,
                                    f2: 14,
                                    f3: 15,
                                    f4: 16
                                }]
                            }), [{
                                dataIndex: 'f1'
                            }, {
                                locked: true,
                                dataIndex: 'f2'
                            }, {
                                dataIndex: 'f3'
                            }, {
                                dataIndex: 'f4'
                            }]);
                            cell_22 = new Ext.grid.CellContext(view).setPosition(2, 2);
                            view.el.focus();
                        });

                        // Should focus back to the same position coordinates ehen though the record and column
                        // of the lastFocused position no longer exist. It falls back to using the rowIdx/colIdx
                        waitsFor(function() {
                            return Ext.Element.getActiveElement() === cell_22.getCell(true);
                        });
                    });
                });
            });
        });
    }

    createSuite(false);
    createSuite(true);
});
