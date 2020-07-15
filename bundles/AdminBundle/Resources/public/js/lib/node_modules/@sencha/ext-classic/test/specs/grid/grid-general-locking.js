topSuite("grid-general-locking",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.layout.container.Border',
     'Ext.grid.plugin.CellEditing', 'Ext.form.field.Text'],
function() {
    var grid, view, store, colRef, navModel,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function spyOnEvent(object, eventName, fn) {
        var obj = {
                fn: fn || Ext.emptyFn
            },
            spy = spyOn(obj, "fn");

        object.addListener(eventName, obj.fn);

        return spy;
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid = store = Ext.destroy(grid, store);
    });

    function createGrid(cfg) {
        grid = new Ext.grid.Panel(Ext.apply({
            title: 'Test',
            height: 300,
            width: 400,
            renderTo: document.body,
            store: store,
            columns: [{
                text: 'Row',
                dataIndex: 'row',
                locked: true,
                width: 50
            }, {
                text: 'Lorem',
                dataIndex: 'lorem'
            }]
        }, cfg));
        navModel = grid.getNavigationModel();
    }

    describe("Locking configuration", function() {
        beforeEach(function() {
            store = new Ext.data.ArrayStore({
                data: [
                    [ 1, 'Lorem'],
                    [ 2, 'Ipsum'],
                    [ 3, 'Dolor']
                ],
                fields: ['row', 'lorem']
            });
        });

        describe("on init", function() {
            beforeEach(function() {
                createGrid({
                    enableColumnHide: true,
                    rowLines: true,
                    enableColumnMove: false,
                    normalGridConfig: {
                        enableColumnHide: false
                    },
                    lockedGridConfig: {
                        rowLines: false
                    }
                });
            });

            it("should pass down configs to normalGrid", function() {
                expect(grid.enableColumnMove).toBe(false);
                expect(grid.normalGrid.enableColumnMove).toBe(false);
            });

            it("should pass down configs to lockedGrid", function() {
                expect(grid.enableColumnMove).toBe(false);
                expect(grid.lockedGrid.enableColumnMove).toBe(false);
            });

            it("should not pass down configs specified in normalGridConfig", function() {
                expect(grid.enableColumnHide).toBe(true);
                expect(grid.normalGrid.enableColumnHide).toBe(false);
            });

            it("should not pass down configs specified in lockedGridConfig", function() {
                expect(grid.rowLines).toBe(true);
                expect(grid.lockedGrid.rowLines).toBe(false);
            });

            it("should set both sides with xtype gridpanel when creating form extended classes", function() {
                grid.destroy();

                Ext.define('BaseGrid', {
                    extend: 'Ext.grid.Panel',
                    xtype: 'base-grid',
                    title: 'foo'
                });

                Ext.define('MyGrid', {
                    extend: 'BaseGrid',
                    xtype: 'mygrid',
                    columns: [{
                        dataIndex: 'foo',
                        locked: true
                    }, {
                        dataIndex: 'bar'
                    }]
                });

                grid = Ext.create('MyGrid', {
                    renderTo: document.body,
                    store: {
                        data: {
                            foo: 1,
                            bar: 2
                        }
                    }
                });

                expect(grid.lockedGrid.isXType('base-grid')).not.toBe(true);
                expect(grid.lockedGrid.isXType('gridpanel')).toBe(true);

                Ext.undefine('BaseGrid');
                Ext.undefine('MyGrid');
            });
        });

        describe("when stateful", function() {
            afterEach(function() {
                Ext.state.Manager.set(grid.getStateId(), null);
            });

            describe("retaining state across page loads", function() {
                function makeGrid(stateId) {
                    createGrid({
                        columns: [{
                            text: 'Row',
                            dataIndex: 'row',
                            locked: true,
                            width: 50
                        }, {
                            text: 'Lorem',
                            stateId: stateId || null,
                            dataIndex: 'lorem'
                        }],
                        stateful: true,
                        stateId: 'foo'
                    });
                    view = grid.getView();
                    colRef = grid.getColumnManager().getColumns();

                }

                function saveAndRecreate(stateId) {
                    grid.saveState();
                    Ext.destroy(grid);

                    // After page refresh.
                    makeGrid(stateId);
                }

                function testStateId(stateId) {
                    var maybe = !!stateId ? '' : 'not';

                    describe("when columns are " + maybe + ' configured with a stateId', function() {
                        function testLockingPartner(which) {
                            describe(which + ' locking partner', function() {
                                var partner = which + 'Grid';

                                beforeEach(function() {
                                    makeGrid(stateId);
                                });

                                it("should retain column width", function() {
                                    var columnManager = grid[partner].columnManager;

                                    waitsFor(function() {
                                        return columnManager.getColumns()[0];
                                    });
                                    runs(function() {
                                        columnManager.getColumns()[0].setWidth(250);
                                        saveAndRecreate(stateId);
                                        columnManager = grid[partner].columnManager;
                                    });

                                    waitsFor(function() {
                                        return columnManager.getColumns()[0];
                                    });

                                    runs(function() {
                                        expect(columnManager.getColumns()[0].getWidth()).toBe(250);
                                    });

                                });

                                it("should retain column visibility", function() {
                                    var columnManager = grid[partner].columnManager;

                                    waitsFor(function() {
                                        return columnManager.getColumns()[0];
                                    });

                                    runs(function() {
                                        columnManager.getColumns()[0].hide();
                                        saveAndRecreate(stateId);
                                        columnManager = grid[partner].columnManager;
                                    });

                                    waitsFor(function() {
                                        return columnManager.getColumns()[0];
                                    });

                                    runs(function() {
                                        expect(columnManager.getColumns()[0].hidden).toBe(true);
                                    });
                                });

                                it("should retain the column sort", function() {
                                    var columnManager = grid[partner].columnManager,
                                        column;

                                    waitsFor(function() {
                                        return columnManager.getColumns()[0];
                                    });

                                    runs(function() {
                                        column = columnManager.getColumns()[0];
                                        column.sort();
                                    });

                                    waitsFor(function() {
                                        return column.sortState;
                                    });

                                    runs(function() {
                                        expect(column.sortState).toBe('ASC');
                                        // Let's sort again.
                                        column.sort();
                                        saveAndRecreate(stateId);
                                        columnManager = grid[partner].columnManager;
                                    });

                                    waitsFor(function() {
                                        return columnManager.getColumns()[0] && columnManager.getColumns()[0].sortState;
                                    });

                                    runs(function() {
                                        expect(columnManager.getColumns()[0].sortState).toBe('DESC');
                                    });
                                });

                                it("should restore state when columns are moved between sides", function() {
                                    grid.unlock(colRef[0], 0);
                                    colRef[0].sort();
                                    colRef[0].setWidth(100);
                                    colRef[1].setWidth(200);

                                    saveAndRecreate(stateId);

                                    expect(colRef[0].dataIndex).toBe('row');
                                    expect(colRef[0].getWidth()).toBe(100);
                                    expect(colRef[0].sortState).toBe('ASC');
                                    expect(colRef[1].dataIndex).toBe('lorem');
                                    expect(colRef[1].getWidth()).toBe(200);
                                });
                            });
                        }

                        testLockingPartner('locked');
                        testLockingPartner('normal');
                    });
                }

                testStateId('theOwlHouse');
                testStateId(null);
            });
        });

        describe('border layout locking', function() {
            var GridEventModel = Ext.define(null, {
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
            }),
            lockedGrid, lockedView,
            normalGrid, normalView;

            function makeGrid(lockedColumnCount, cfg, lockedGridConfig, normalGridConfig) {
                var data = [],
                    defaultCols = [],
                    i;

                for (i = 1; i <= 10; ++i) {
                    defaultCols.push({
                        text: 'F' + i,
                        dataIndex: 'field' + i,
                        locked: (i <= lockedColumnCount)
                    });
                }

                for (i = 1; i <= 500; ++i) {
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

                grid = new Ext.grid.Panel(Ext.apply({
                    columns: defaultCols,
                    store: store,
                    width: 1000,
                    height: 500,
                    viewConfig: {
                        mouseOverOutBuffer: 0
                    },
                    layout: 'border',
                    lockedGridConfig: Ext.apply({
                        collapsible: true,
                        split: true
                    }, lockedGridConfig),
                    normalGridConfig: normalGridConfig,
                    renderTo: Ext.getBody()
                }, cfg));
                view = grid.getView();
                lockedGrid = grid.lockedGrid;
                lockedView = lockedGrid.getView();
                normalGrid = grid.normalGrid;
                normalView = normalGrid.getView();
            }

            it('should be able to lock columns', function() {
                makeGrid(0, {
                    enableLocking: true
                });

                expect(grid.lockedGrid.isVisible()).toBe(false);

                // Because the locked side is collapsible, it gets a header with the collapse tool
                expect(grid.normalGrid.header).not.toBeUndefined();

                grid.lock(grid.columns[0]);

                // Because the locked side is collapsible, it gets a header with the collapse tool
                expect(grid.lockedGrid.header).not.toBeUndefined();

                // Width should exactly shrinkwrap the columns
                expect(grid.lockedGrid.getWidth()).toBe(grid.lockedGrid.headerCt.getTableWidth() + grid.lockedGrid.gridPanelBorderWidth);

                grid.lockedGrid.collapse();

                waitsForSpy(spyOnEvent(lockedGrid, 'collapse'));

                runs(function() {
                    grid.lockedGrid.expand();
                });

                waitsForSpy(spyOnEvent(lockedGrid, 'expand'));

                runs(function() {
                    grid.lock(grid.columns[1]);
                    // Width should exactly shrinkwrap the columns
                    expect(grid.lockedGrid.getWidth()).toBe(grid.lockedGrid.headerCt.getTableWidth() + grid.lockedGrid.gridPanelBorderWidth);

                    grid.unlock(grid.columns[1]);

                    // Width should exactly shrinkwrap the columns
                    expect(grid.lockedGrid.getWidth()).toBe(grid.lockedGrid.headerCt.getTableWidth() + grid.lockedGrid.gridPanelBorderWidth);

                    // Now test column moving in locked side when floated
                    grid.lockedGrid.headerCt.moveBefore(grid.columns[1], grid.columns[0]);

                    // Width should exactly shrinkwrap the columns
                    expect(grid.lockedGrid.getWidth()).toBe(grid.lockedGrid.headerCt.getTableWidth() + grid.lockedGrid.gridPanelBorderWidth);

                    grid.lockedGrid.headerCt.moveBefore(grid.columns[0], grid.columns[1]);

                    // Width should exactly shrinkwrap the columns
                    expect(grid.lockedGrid.getWidth()).toBe(grid.lockedGrid.headerCt.getTableWidth() + grid.lockedGrid.gridPanelBorderWidth);
                });
            });

            (Ext.getScrollbarSize().height ? describe : xdescribe)("collpasing and expanding", function() {
                it("should display the scroller if needed", function() {
                    var spy = jasmine.createSpy();

                    makeGrid(2, null, {
                        width: 100,
                        collapsible: true,
                        listeners: {
                            expand: spy,
                            collapse: spy
                        }
                    });

                    grid.lockedGrid.collapse();

                    waitsFor(function() {
                        return spy.callCount === 1;
                    });

                    runs(function() {
                        grid.lockedGrid.expand();
                    });

                    waitsFor(function() {
                        return spy.callCount === 2;
                    });

                    runs(function() {
                        expect(grid.lockedScrollbarScroller.getElement().getWidth()).toBe(grid.lockedGrid.getWidth());
                        expect(grid.lockedScrollbarScroller.getElement().isScrollable()).toBe(true);
                        // overflow of the locked side should be handled by the lockedScrollbarScroller, not the view's body
                        expect(grid.lockedGrid.body.dom.style.overflowX).toBe('');
                    });
                });

                it("should display the scroller if need and the normal side continued to be scrollable during expand/collapse", function() {
                    var spy = jasmine.createSpy();

                    makeGrid(2, {
                        width: 400
                    }, {
                        width: 100,
                        collapsible: true,
                        listeners: {
                            expand: spy,
                            collapse: spy
                        }
                    });

                    grid.lockedGrid.collapse();

                    waitsFor(function() {
                        return spy.callCount === 1;
                    });

                    runs(function() {
                        grid.lockedGrid.expand();
                    });

                    waitsFor(function() {
                        return spy.callCount === 2;
                    });

                    runs(function() {
                        expect(grid.lockedScrollbarScroller.getElement().getWidth()).toBe(grid.lockedGrid.getWidth());
                        expect(grid.lockedScrollbarScroller.getElement().isScrollable()).toBe(true);
                        // overflow of the locked side should be handled by the lockedScrollbarScroller, not the view's body
                        expect(grid.lockedGrid.body.dom.style.overflowX).toBe('');
                    });
                });
            });
        });
    });

    describe('tabbing between sides', function() {
        it('should move to the same row on the other side', function() {
            store = new Ext.data.ArrayStore({
                data: [
                    [ 1, 'Lorem'],
                    [ 2, 'Ipsum'],
                    [ 3, 'Dolor']
                ],
                fields: ['row', 'lorem']
            });

            createGrid();

            grid.lockedGrid.view.focus();

            waitsFor(function() {
                return grid.lockedGrid.view.containsFocus;
            });
            runs(function() {
                jasmine.simulateTabKey(Ext.Element.getActiveElement(), true);
            });
            waitsFor(function() {
                return grid.normalGrid.view.containsFocus;
            });
            runs(function() {
                var p = navModel.getPosition();

                // Tabbed across the boundary
                expect(p.view === grid.normalGrid.view);
                expect(p.rowIdx).toBe(0);
                expect(p.colIdx).toBe(0);
            });
        });
    });

    describe('Focusing the view el, not a cell', function() {
        (Ext.isIE8 ? xit : it)('should move to the same row on the other side', function() {
            var errorSpy = jasmine.createSpy('error handler'),
                old = window.onError;

            store = new Ext.data.ArrayStore({
                data: [
                    [ 1, 'Lorem'],
                    [ 2, 'Ipsum'],
                    [ 3, 'Dolor']
                ],
                fields: ['row', 'lorem']
            });

            window.onerror = errorSpy.andCallFake(function() {
                if (old) {
                    old();
                }
            });

            createGrid();

            runs(function() {
                jasmine.fireMouseEvent(grid.normalGrid.view.el, 'click', 200, 200);
                expect(errorSpy).not.toHaveBeenCalled();
            });

            waitsFor(function() {
                return grid.normalGrid.containsFocus;
            });

            runs(function() {
                jasmine.fireMouseEvent(grid.lockedGrid.view.el, 'click', 25, 200);
                expect(errorSpy).not.toHaveBeenCalled();
            });

            waitsFor(function() {
                return grid.lockedGrid.containsFocus;
            });

            runs(function() {
                jasmine.fireMouseEvent(grid.normalGrid.view.el, 'click', 200, 200);
                expect(errorSpy).not.toHaveBeenCalled();
            });

            waitsFor(function() {
                return grid.normalGrid.containsFocus;
            });

            runs(function() {
                jasmine.fireMouseEvent(grid.lockedGrid.view.el, 'click', 25, 200);
                expect(errorSpy).not.toHaveBeenCalled();
            });

            waitsFor(function() {
                return grid.lockedGrid.containsFocus;
            });

            runs(function() {
                expect(errorSpy).not.toHaveBeenCalled();
                window.onerror = old;
            });
        });
    });

    describe("enable/disable", function() {
        it("should be able to enable a grid that was initially disabled", function() {
            createGrid({
                disabled: true
            });

            grid.enable();

            expect(grid.el.down('.x-mask').isVisible(true)).toBeFalsy();
        });
    });

    describe("scrolling", function() {
        beforeEach(function() {
            store = new Ext.data.Store({
                fields: ['name', 'email', 'phone'],
                data: [
                { name: 'Lisa',  email: 'lisa@simpsons.com',  phone: '555-111-1224' },
                { name: 'Bart',  email: 'bart@simpsons.com',  phone: '555-222-1234' },
                { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' },
                { name: 'Lisa',  email: 'lisa@simpsons.com',  phone: '555-111-1224' },
                { name: 'Bart',  email: 'bart@simpsons.com',  phone: '555-222-1234' },
                { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' },
                { name: 'Lisa',  email: 'lisa@simpsons.com',  phone: '555-111-1224' },
                { name: 'Bart',  email: 'bart@simpsons.com',  phone: '555-222-1234' },
                { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' },
                { name: 'Lisa',  email: 'lisa@simpsons.com',  phone: '555-111-1224' },
                { name: 'Bart',  email: 'bart@simpsons.com',  phone: '555-222-1234' },
                { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
                ]
            });
        });

        it("should not scroll back to top when selecting records", function() {
            var scroller,
                cell;

            createGrid({
                columns: [{
                    text: 'Name',
                    dataIndex: 'name',
                    locked: true
                }, {
                    text: 'Email',
                    dataIndex: 'email',
                    width: 300
                }, {
                    text: 'Phone',
                    dataIndex: 'phone',
                    width: 300
                }],
                height: 200,
                width: 400
            });

            scroller = grid.getScrollable();
            scroller.scrollTo(null, 100);
            scroller.scrollTo(100, null);

            waitsFor(function() {
                return scroller.position.y === scroller.position.x && scroller.position.y === 100;
            });

            runs(function() {
                cell = grid.normalGrid.view.getCell(7, 0);
                jasmine.fireMouseEvent(cell, 'mousedown');
            });

            // Need waits here because we are waitign for the scroller not to move
            waits(100);

            runs(function() {
                expect(scroller.getPosition().y).toBe(100);
                // finish the click to avoid even publisher leaks
                jasmine.fireMouseEvent(cell, 'mouseup');
            });
        });

        it("should not change scroll position when bufferedRenderer is false", function() {
            var scroller,
                cell;

             createGrid({
                bufferedRenderer: false,
                columns: [{
                    text: 'Name',
                    dataIndex: 'name',
                    locked: true
                }, {
                    text: 'Email',
                    dataIndex: 'email',
                    width: 300
                }, {
                    text: 'Phone',
                    dataIndex: 'phone',
                    width: 300
                }],
                height: 200,
                width: 400
            });

            scroller = grid.getScrollable();
            scroller.scrollTo(null, 100);
            scroller.scrollTo(100, null);

             waitsFor(function() {
                return scroller.position.y === scroller.position.x && scroller.position.y === 100;
            });

             runs(function() {
                cell = grid.normalGrid.view.getCell(7, 0);
                jasmine.fireMouseEvent(cell, 'mousedown');
            });

             grid.updateLayout();

             // Need waits here because we are waitign for the scroller not to move
            waits(100);

             runs(function() {
                expect(scroller.getPosition().y).toBe(100);
                // finish the click to avoid even publisher leaks
                jasmine.fireMouseEvent(cell, 'mouseup');
            });
        });
    });

    describe('View focus from cell editor', function() {
        it('should set position to the closest cell', function() {
            var rowIdx = 0,
                colIdx = 2,
                editor, editorActive, position,
                record, cellEl, cellRegion, viewRegion,
                x, y;

            store = new Ext.data.ArrayStore({
                data: [
                    [ 1, 'Lorem'],
                    [ 2, 'Ipsum'],
                    [ 3, 'Dolor']
                ],
                fields: ['row', 'lorem']
            });

            createGrid({
                plugins: [{
                    ptype: 'cellediting',
                    listeners: {
                        beforeedit: function() {
                            editorActive = true;
                        }
                    }
                }],
                columns: [{
                    text: 'Row',
                    dataIndex: 'row',
                    locked: true,
                    width: 50
                }, {
                    text: 'Lorem',
                    dataIndex: 'lorem'
                }, {
                    text: 'Lorem (editor)',
                    dataIndex: 'lorem',
                    editor: 'textfield'
                }]
            });

            view = grid.normalGrid.view;
            editor = grid.findPlugin('cellediting');
            navModel = grid.normalGrid.getNavigationModel();
            record = store.getAt(0);

            editor.startEditByPosition({ row: rowIdx, column: colIdx });

            waitFor(function() {
                return editorActive;
            });

            runs(function() {
                cellEl = view.getCell(record, colIdx - 1, true);
                cellRegion = cellEl.getRegion();
                viewRegion = view.getRegion();

                // get the XY position in the middle between the grid cell and the
                // bottom of the view
                x = (cellRegion.left + cellRegion.right) / 2;
                y = (cellRegion.bottom + viewRegion.bottom) / 2;

                // mousedown in the view container below the cell being edited
                jasmine.fireMouseEvent(view, 'mousedown', x, y);
                position = navModel.getPosition();
                jasmine.fireMouseEvent(view, 'mouseup', x, y);

                // position should remain on the same cell
                expect({
                    rowIdx: position.rowIdx,
                    colIdx: position.colIdx })
                .toEqual({
                    rowIdx: rowIdx,
                    colIdx: --colIdx
                });

                // mousedown below the cell to the left
                jasmine.fireMouseEvent(view.el, 'mousedown', x - cellRegion.width, y);
                position = navModel.getPosition();
                jasmine.fireMouseEvent(view.el, 'mouseup', x - cellRegion.width, y);

                // position should be moved to the cell to the left
                expect({
                    rowIdx: position.rowIdx,
                    colIdx: position.colIdx })
                .toEqual({
                    rowIdx: rowIdx,
                    colIdx: --colIdx
                });
            });
        });
    });
});
