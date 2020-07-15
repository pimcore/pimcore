topSuite("Ext.grid.Panel",
    ['Ext.data.ArrayStore', 'Ext.ux.PreviewPlugin', 'Ext.grid.feature.*', 'Ext.form.field.Text',
     'Ext.container.Viewport', 'Ext.data.BufferedStore', 'Ext.grid.filters.Filters'],
function() {
    var itShowsScrollbars = Ext.getScrollbarSize().width ? it : xit,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

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

    function getNames() {
        var result = [];

        store.each(function(rec) {
            result.push(rec.get('name'));
        });

        return result.join(',');
    }

    var createGrid = function(storeCfg, gridCfg) {
        if (!(gridCfg && gridCfg.viewModel && gridCfg.viewModel.stores)) {
            if (!(storeCfg instanceof Ext.data.Store)) {
                store = new Ext.data.Store(Ext.apply({
                storeId: 'simpsonsStore',
                    fields: ['name', 'email', 'phone'],
                    data: [
                        { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                        { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                        { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                        { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
                    ]
                }, storeCfg));
            }
            else {
                store = storeCfg;
            }
        }
        else {
            store = null;
        }

        grid = new Ext.grid.Panel(Ext.apply({
            title: 'Simpsons',
            store: store,
            columns: [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', flex: 1 },
                { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
            ],

            // We need programmatic mouseover events to be handled inline so we can test effects.
            viewConfig: {
                mouseOverOutBuffer: false,
                deferHighlight: false
            },
            height: 200,
            width: 400,
            renderTo: Ext.getBody()
        }, gridCfg));

        colRef = grid.getColumnManager().getColumns();
        view = grid.view;
        selModel = grid.selModel;
        navModel = grid.getNavigationModel();
    };

    var proto = Ext.view.Table.prototype,
        selectedItemCls = proto.selectedItemCls,
        focusedItemCls = proto.focusedItemCls,
        overItemCls = proto.overItemCls,
        grid, colRef, store, view, selModel, navModel, failedLayouts;

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        failedLayouts = Ext.failedLayouts || 0;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid = view = selModel = store = Ext.destroy(grid, store);
    });

    function expectNoFailedLayouts() {
        var failures = (Ext.failedLayouts || 0) - failedLayouts;

        expect(failures).toBe(0);
    }

    describe('scrollable: false', function() {
        var field;

        afterEach(function() {
            if (field) {
                field.destroy();
            }
        });

        it('should disable scrolling with scrollable: false', function() {
            createGrid(null, {
                scrollable: false
            });
            expect(grid.view.getScrollable()).toBe(false);
            expect(grid.view.getScrollable()).toBe(false);
        });

        it('should be able to focus for a second time without throwing an error', function() {
            createGrid(null, {
                viewConfig: {
                    scrollable: false
                }
            });
            field = new Ext.form.field.Text({
                renderTo: document.body
            });
            grid.view.focus();
            field.focus();
            grid.view.focus();
            waitsFor(function() {
                return view.cellFocused;
            }, 'non-scrollable TableView to focus for a second time');
        });
    });

    // https://sencha.jira.com/browse/EXTJS-17837
    describe('Loading the store during render', function() {
        it('should not throw an error', function() {
            var TestGrid = Ext.define(null, {
                extend: 'Ext.grid.Panel',

                // Assign to the closure grid var eaerly in the constructor
                // so that if an error DOES throw before the return and assignment
                // take place, the afterEach can still clean up.
                constructor: function() {
                    grid = this;
                    this.callParent(arguments);
                },

                afterRender: function() {
                    this.callParent(arguments);
                    this.getStore().loadPage(1);
                }
            });

            grid = new TestGrid({
                width: 700,
                height: 500,
                store: store,
                renderTo: Ext.getBody()
            });
        });
    });

    describe('Splitter in locked grid', function() {
        it('should allow configuration of the splitter', function() {
            createGrid(null, {
                height: 100,
                split: {
                    width: 30
                },
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 200, locked: true },
                    { header: 'Email', dataIndex: 'email', width: 200 },
                    { header: 'Phone', dataIndex: 'phone', width: 200 }
                ]
            });
            expect(grid.child('splitter').getWidth()).toBe(30);
        });
    });

    describe('Allowing for scrollbar in HeaderContainer', function() {
        itShowsScrollbars("should add scrollBarWidth to HeaderContainer's innerCt width", function() {
            createGrid(null, {
                height: 100,
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 200 },
                    { header: 'Email', dataIndex: 'email', width: 200 },
                    { header: 'Phone', dataIndex: 'phone', width: 200 }
                ]
            });

            // The HeaderContainer's innerCt needs to be extended to cover the view's vertical scrollbar.
            expect(grid.headerCt.layout.innerCt.dom.offsetWidth).toBe(600 + Ext.getScrollbarSize().width);

            view.setScrollX(100);
            waitsFor(function() {
                return grid.headerCt.getScrollX() === 100;
            });
            runs(function() {
                grid.columns[1].sort();

                // The HeaderContainer layout layout caused by sort should not reset the scrollX to zero.
                // (layout reset clears width which will zero the scrollX, but the scrollX should be cached at the beforeLayout stage)
                expect(grid.headerCt.getScrollX()).toBe(100);
            });
        });
    });

    describe('constructing plugins and features', function() {
        describe('defined on the panel', function() {
            beforeEach(function() {
                createGrid(null, {
                    plugins: [{
                        ptype: 'preview',
                        bodyField: 'excerpt',
                        expanded: true,
                        pluginId: 'preview'
                    }]
                });
            });

            xit('should construct plugins with feature dependencies on the grid', function() {
                expect(grid.features.length).toBe(1);
                expect(grid.plugins.length).toBe(1);
                expect(grid.view.body.query('.x-grid-rowbody').length).toBe(4);
            });

            xit('should add a reference to the feature on the view', function() {
                expect(view.features.length).toBe(1);
            });

            it('should convert the plugins config array from objects into instances', function() {
                expect(grid.plugins[0] instanceof Ext.ux.PreviewPlugin).toBe(true);
            });
        });
    });

    describe('hideHeaders', function() {
        var data = [
            [ 1, 'Hello' ],
            [ 2, 'World' ]
        ];

        it('should render columns correctly', function() {
            grid = new Ext.grid.Panel({
                columns: [{ flex: 1, dataIndex: 'field1' }, { width: 100, dataIndex: 'field2' }],
                border: false,
                width: 500,
                height: 300,
                store: data,
                hideHeaders: true,
                renderTo: Ext.getBody()
            });

            expectNoFailedLayouts();
            var columns = grid.columnManager.getColumns();

            expect(columns[0].getWidth()).toEqual(500 - 100);
            expect(columns[1].getWidth()).toEqual(100);
            expect(grid.getDockedItems()[0].getHeight()).toBe(0);
        });

        it("should accept a headerCt config object", function() {
            grid = new Ext.grid.Panel({
                columns: new Ext.grid.header.Container({
                    items: [{
                        flex: 1,
                        dataIndex: 'field1'
                    }, {
                        flex: 1,
                        dataIndex: 'field2'
                    }]
                }),
                border: false,
                width: 500,
                height: 300,
                store: data,
                hideHeaders: true,
                renderTo: Ext.getBody()
            });

            expectNoFailedLayouts();
            var columns = grid.columnManager.getColumns();

            expect(columns[0].getWidth()).toEqual(250);
            expect(columns[1].getWidth()).toEqual(250);
            expect(grid.getDockedItems()[0].getHeight()).toBe(0);
        });

        it("should not have a horizontal scrollbar when there is a flex column (EXTJSIV-7153)", function() {
            var ready = 0;

            grid = new Ext.grid.Panel({
                columns: [{ flex: 1, dataIndex: 'field1' }],
                width: 100,
                height: 100,
                store: data,
                hideHeaders: true,
                renderTo: Ext.getBody(),
                listeners: {
                    viewready: function() {
                        ++ready;
                    }
                }
            });

            waitsFor(function() {
                return ready;
            });

            runs(function() {
                // No border: false config here. width is going to be within the border box.
                expect(grid.view.el.dom.scrollWidth).toBe(100 - grid.body.getBorderWidth('lr'));
            });
        });
    });

    describe('grid layout', function() {
        store = Ext.create('Ext.data.ArrayStore', {
            fields: [
                { name: 'company' },
                { name: 'price',      type: 'float' },
                { name: 'change',     type: 'float' },
                { name: 'pctChange',  type: 'float' },
                { name: 'lastChange', type: 'date', dateFormat: 'n/j h:ia' }
            ],
            data: []
        });

        function makeGrid(locked, cfg) {
            return Ext.widget(Ext.apply({
                xtype: 'grid',
                store: store,
                columnLines: true,
                columns: [{
                    text: 'Company<br>Name', // Two line header! Test header height synchronization!
                    locked: locked,
                    width: 200,
                    sortable: false,
                    dataIndex: 'company'
                }, {
                    text: 'Price',
                    width: 125,
                    sortable: true,
                    formatter: 'usMoney',
                    dataIndex: 'price'
                }, {
                    text: 'Change',
                    width: 125,
                    sortable: true,
                    dataIndex: 'change'
                }, {
                    text: '% Change',
                    width: 125,
                    sortable: true,
                    dataIndex: 'pctChange'
                }, {
                    text: 'Last Updated',
                    width: 135,
                    sortable: true,
                    formatter: 'date("m/d/Y")',
                    dataIndex: 'lastChange'
                }],
                height: 350,
                width: 600,
                title: 'Locking Grid Column',
                renderTo: Ext.getBody()
            }, cfg));
        }

        it("should calculate the locked grid's width to encapsulate the total locked column width plus right+left borders", function() {
            grid = makeGrid(true, {
                lockedGridConfig: {
                    style: {
                        borderLeft: '5px solid red',
                        borderRight: '5px solid red'
                    }
                }
            });

            // Width must be the locked column width plus any left & right borders
            expect(grid.lockedGrid.getWidth()).toBe(200 + grid.lockedGrid.gridPanelBorderWidth);
        });

        it('should properly place table below header', function() {
            grid = makeGrid(true);
            var lockedGrid = grid.query('grid')[1];

            expect(lockedGrid.body.getLocalY()).toEqual(lockedGrid.headerCt.getHeight());

            // https://sencha.jira.com/browse/EXTJS-18183
            // Destroying a lockable grid on touch scroll platforms should run with no errors.
            grid.destroy();
        });

        // Unit test for EXTJS-5293
        it('should not show hidden flex columns', function() {
            createGrid();

            var columns = grid.headerCt.getGridColumns();

            columns[2].show();

            expect(grid).toHaveLayout({
                el: { xywh: "0 0 400 200" },
                body: { xywh: "0 50 400 150" },
                items: {
                    gridview: {
                        el: { xywh: "1 51 398 148" } // account for 1px border
                    }
                },
                dockedItems: {
                    header: {
                        el: { xywh: "0 0 400 27" },
                        items: {
                            "component": {
                                el: { xywh: "6 6 388 16" }
                            }
                        }
                    },
                    headercontainer: {
                        el: { xywh: "0 27 400 [23,24]" },
                        items: {
                            0: {
                                el: { xywh: "1 1 100 22" },
                                textEl: { xywh: "7 [4,5] [27-30] [13-15]" },
                                titleEl: { xywh: "1 1 99 22" }
                            },
                            1: {
                                el: { xywh: "101 1 149 22" },
                                textEl: { xywh: "107 [4,5] [24-28] [13-15]" },
                                titleEl: { xywh: "101 1 148 22" }
                            },
                            2: {
                                el: { xywh: "250 1 149 22" },
                                textEl: { xywh: "256 [4-5] [30-32] [13-15]" },
                                titleEl: { xywh: "250 1 148 22" }
                            }
                        }
                    }
                }
            });

            var table = grid.view.el.down('table', true),
                tbody = table.tBodies[0],
                firstRow = tbody.children[0];

            expect(firstRow.children[0].clientWidth).toEqual(100);
            expect(firstRow.children[1].clientWidth).toEqual(149);
            expect(firstRow.children[2].clientWidth).toEqual(149);
        });
    });

    describe('sorting', function() {
        it('should maintain selection across sort', function() {
            var rec, sm;

            createGrid({
                groupField: 'sex',
                fields: ['name', 'sex', 'email', 'phone'],
                data: [
                    { 'name': 'Homer', 'sex': 'Male', 'email': 'homer@simpsons.com', 'phone': '555-222-1244' },
                    { 'name': 'Bart', 'sex': 'Male', 'email': 'bart@simpsons.com', 'phone': '555-222-1234' },
                    { 'name': 'Marge', 'sex': 'Female', 'email': 'marge@simpsons.com', 'phone': '555-222-1254' },
                    { 'name': 'Lisa', 'sex': 'Female', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224' }
                ]
            }, {
                width: 600,
                height: 400,
                features: [{
                    ftype: 'grouping'
                }],
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 200, locked: true },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ],
                selModel: {
                    selType: 'cellmodel'
                }
            });

            sm = grid.getSelectionModel();
            rec = grid.store.getAt(0);

            // Select Marge's name field in the locked side
            sm.setCurrentPosition({ view: grid.lockedGrid.view, row: 0, column: 0 });
            // Sort by name
            grid.store.sort('name');

            // Selection should be preserved
            expect(grid.getSelectionModel().getSelection()[0]).toBe(rec);
        });

        describe('Custom column sorter', function() {
            var nameCol;

            afterEach(function() {
                Ext.state.Manager.set(grid.getStateId(), null);
            });

            function createCustomSortGrid() {
                createGrid(null, {
                    stateful: true,
                    stateId: 'zarquon',
                    columns: [{
                        header: 'Name',
                        dataIndex: 'name',
                        width: 200,
                        locked: true,

                        // Use a custom sorter which sorts in REVERSE order to test
                        sorter: function(rec1, rec2) {
                            var rec1Name = rec1.get('name'),
                                rec2Name = rec2.get('name');

                            if (rec1Name < rec2Name) {
                                return 1;
                            }

                            if (rec1Name > rec2Name) {
                                return -1;
                            }

                            return 0;
                        }
                    },
                    {
                        header: 'Email', dataIndex: 'email', flex: 1
                    }, {
                        header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true
                    }]
                });
                nameCol = colRef[0];
            }

            it("should sort by a column's custom sorter", function() {
                createCustomSortGrid();

                // Initial, no sort, order is as specified in data object in createGrid function
                expect(getNames()).toEqual('Lisa,Bart,Homer,Marge');

                // No column sort classes on headers initially
                expect(nameCol.hasCls(nameCol.ascSortCls)).toBe(false);
                expect(nameCol.hasCls(nameCol.descSortCls)).toBe(false);

                // Sort ascending
                colRef[0].sort();
                expect(nameCol.hasCls(nameCol.ascSortCls)).toBe(true);
                expect(nameCol.hasCls(nameCol.descSortCls)).toBe(false);

                // But data should be in descending order because of custom column sorter
                expect(getNames()).toEqual('Marge,Lisa,Homer,Bart');

                // Sort descending
                colRef[0].sort();
                expect(nameCol.hasCls(nameCol.ascSortCls)).toBe(false);
                expect(nameCol.hasCls(nameCol.descSortCls)).toBe(true);

                // But data should be in ascending order because of custom column sorter
                expect(getNames()).toEqual('Bart,Homer,Lisa,Marge');

                grid.saveState();
                grid.destroy();
                createCustomSortGrid();

                // State should have been restored in the descending order
                // But data should be in ascending order because of custom column sorter
                expect(getNames()).toEqual('Bart,Homer,Lisa,Marge');
            });
        });
    });

    describe('scrolling', function() {
        it('should scroll locked side', function() {
            var gsbw = Ext.getScrollbarSize;

            // If there is no scrollbar, then the locked side is set to scroll: 'vertical'
            Ext.getScrollbarSize = function() {
                return {
                    height: 0,
                    width: 0
                };
            };

            createGrid({
                groupField: 'sex',
                fields: ['name', 'sex', 'email', 'phone'],
                data: [
                    { 'name': 'Homer', 'sex': 'Male', 'email': 'homer@simpsons.com', 'phone': '555-222-1244' },
                    { 'name': 'Bart', 'sex': 'Male', 'email': 'bart@simpsons.com', 'phone': '555-222-1234' },
                    { 'name': 'Marge', 'sex': 'Female', 'email': 'marge@simpsons.com', 'phone': '555-222-1254' },
                    { 'name': 'Lisa', 'sex': 'Female', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224' }
                ]
            }, {
                width: 600,
                height: 400,
                features: [{
                    ftype: 'grouping'
                }],
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 200, locked: true },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ],
                selModel: {
                    selType: 'cellmodel'
                },
                lockedViewConfig: {
                    scroll: 'horizontal'
                }
            });
            Ext.getScrollbarSize = gsbw;

            var lockedScroller = grid.lockedGrid.view.getScrollable();

            // Scrollbar width zero means that we have vertical scrolling, the grid is configured with scroll: 'horizontal'.
            // The upshot should be that scrolling is auto in both dimensions
            expect(lockedScroller.getX()).toBe(true);
            expect(lockedScroller.getY()).toBe(true);
       });
    });

    describe("reconfigure", function() {
        var tds;

        it('should destroy the header column menu is the columns are not sortable', function() {
            var col,
                menu,
                columnsItem;

            grid = new Ext.grid.Panel({
                height: 300,
                width: 600,
                renderTo: Ext.getBody(),
                sortableColumns: false,
                columns: [{
                    text: 'Forename',
                    dataIndex: 'name'
                }],
                store: new Ext.data.Store({
                    fields: ['name', 'surname'],
                    data: [
                        { name: 'Tom', surname: 'Jones' },
                        { name: 'Pete', surname: 'Tong' },
                        { name: 'Brian', surname: 'May' }
                    ]
                })
            });
            col = grid.getVisibleColumnManager().getColumns()[0];

            Ext.testHelper.showHeaderMenu(col);

            runs(function() {
                menu = col.activeMenu;
                columnsItem = menu.child('[text=Columns]');

                // The single menu item should be for the "Forename" column
                expect(columnsItem.menu.items.items.length).toBe(1);
                expect(columnsItem.menu.items.items[0].text).toBe('Forename');

                menu.hide();

                // Reconfigure and check that the columns menu reflects the new column set
                grid.reconfigure(null, [{ dataIndex: 'surname', text: 'Surname' }]);

                col = grid.getVisibleColumnManager().getColumns()[0];
                Ext.testHelper.showHeaderMenu(col);
            });

            runs(function() {
                menu = col.activeMenu;
                columnsItem = menu.child('[text=Columns]');

                // The single menu item should be for the "Surname" column
                expect(columnsItem.menu.items.items.length).toBe(1);
                expect(columnsItem.menu.items.items[0].text).toBe('Surname');
            });
        });

        it("Should reconfigure the grid with no error", function() {
            grid = new Ext.grid.Panel({
                height: 300,
                width: 600,
                hideHeaders: true,
                renderTo: Ext.getBody(),
                columns: [{
                    dataIndex: 'name'
                }],
                store: new Ext.data.Store({
                    fields: ['name', 'surname'],
                    data: [
                        { name: 'Tom', surname: 'Jones' },
                        { name: 'Pete', surname: 'Tong' },
                        { name: 'Brian', surname: 'May' }
                    ]
                })
            });

            grid.reconfigure(null, [{ dataIndex: 'surname' }]);
            tds = grid.view.el.query('tbody td');
            expect(Ext.String.trim(tds[0].textContent || tds[0].innerText)).toEqual("Jones");
            expect(Ext.String.trim(tds[1].textContent || tds[1].innerText)).toEqual("Tong");
            expect(Ext.String.trim(tds[2].textContent || tds[2].innerText)).toEqual("May");
        });

        it("Should reconfigure the grid with no error when there's a buffered renderer", function() {
            grid = new Ext.grid.Panel({
                height: 300,
                width: 600,
                hideHeaders: true,
                renderTo: Ext.getBody(),
                columns: [{
                    dataIndex: 'name'
                }],
                store: new Ext.data.Store({
                    fields: ['name', 'surname'],
                    data: [
                        { name: 'Tom', surname: 'Jones' },
                        { name: 'Pete', surname: 'Tong' },
                        { name: 'Brian', surname: 'May' }
                    ]
                })
            });

            grid.reconfigure(null, [{ dataIndex: 'surname' }]);
            tds = grid.view.el.query('tbody td');
            expect(Ext.String.trim(tds[0].textContent || tds[0].innerText)).toEqual("Jones");
            expect(Ext.String.trim(tds[1].textContent || tds[1].innerText)).toEqual("Tong");
            expect(Ext.String.trim(tds[2].textContent || tds[2].innerText)).toEqual("May");
        });

        it("Should reconfigure the grid with no error when there's a buffered renderer and the grid contains focus", function() {
            grid = new Ext.grid.Panel({
                height: 300,
                width: 600,
                hideHeaders: true,
                renderTo: Ext.getBody(),
                columns: [{
                    dataIndex: 'name'
                }],
                store: new Ext.data.Store({
                    fields: ['name', 'surname'],
                    data: [
                        { name: 'Tom', surname: 'Jones' },
                        { name: 'Pete', surname: 'Tong' },
                        { name: 'Brian', surname: 'May' }
                    ]
                })
            });
            var view = grid.getView(),
                navModel = grid.getNavigationModel();

            navModel.setPosition(0, 0);

            waitsFor(function() {
                return view.containsFocus;
            });

            runs(function() {
                grid.reconfigure(new Ext.data.Store({
                    fields: ['name', 'surname'],
                    data: [
                        { name: 'Tom', surname: 'Jones' },
                        { name: 'Pete', surname: 'Tong' },
                        { name: 'Brian', surname: 'May' }
                    ]
                }), [{ dataIndex: 'surname' }]);
                tds = grid.view.el.query('tbody td');
                expect(Ext.String.trim(tds[0].textContent || tds[0].innerText)).toEqual("Jones");
                expect(Ext.String.trim(tds[1].textContent || tds[1].innerText)).toEqual("Tong");
                expect(Ext.String.trim(tds[2].textContent || tds[2].innerText)).toEqual("May");
            });

            // Same cell by row/column should be focused after the reconfigure even though the record and column are different
            waitsFor(function() {
                var position = navModel.getPosition();

                return view.containsFocus && position &&
                       position.isEqual(new Ext.grid.CellContext(view).setPosition(0, 0));
            }, 'position to match', 1000);
        });

        it("Should reconfigure the grid with no error when no columns are passed", function() {
            grid = new Ext.grid.Panel({
                height: 300,
                width: 600,
                hideHeaders: true,
                renderTo: Ext.getBody(),
                columns: [{
                    dataIndex: 'name'
                }],
                store: new Ext.data.Store({
                    fields: ['name'],
                    data: [
                        { name: 'Tom' },
                        { name: 'Pete' },
                        { name: 'Brian' }
                    ]
                })
            });
            var newStore = new Ext.data.Store({
                fields: ['name'],
                proxy: {
                    type: 'memory',
                    reader: 'json'
                },
                data: [
                    { name: 'Jones' },
                    { name: 'Tong' },
                    { name: 'May' }
                ]
            });

            grid.reconfigure(newStore);
            tds = grid.view.el.query('tbody td');
            expect(Ext.String.trim(tds[0].textContent || tds[0].innerText)).toEqual("Jones");
            expect(Ext.String.trim(tds[1].textContent || tds[1].innerText)).toEqual("Tong");
            expect(Ext.String.trim(tds[2].textContent || tds[2].innerText)).toEqual("May");
        });

        describe('viewready event', function() {
            var wasCalled = false;

            function doIt(newStore) {
                grid.reconfigure(newStore);

                expect(wasCalled).toBe(true);
            }

            beforeEach(function() {
                grid = new Ext.grid.Panel({
                    height: 300,
                    width: 600,
                    hideHeaders: true,
                    renderTo: Ext.getBody(),
                    columns: [{
                        dataIndex: 'name'
                    }],
                    store: new Ext.data.Store({
                        fields: ['name'],
                        data: [
                            { name: 'Tom' },
                            { name: 'Pete' },
                            { name: 'Brian' }
                        ]
                    }),
                    listeners: {
                        viewready: function() {
                            wasCalled = true;
                        }
                    }
                });
            });

            it('should fire when the new store does not have a data config', function() {
                doIt(new Ext.data.Store({
                    fields: ['name'],
                    proxy: {
                        type: 'memory',
                        reader: 'json'
                    }
                }));
            });

            it('should fire when the new store has no data', function() {
                doIt(new Ext.data.Store({
                    fields: ['name'],
                    proxy: {
                        type: 'memory',
                        reader: 'json'
                    },
                    data: []
                }));
            });

            it('should fire when the new store has data', function() {
                doIt(new Ext.data.Store({
                    fields: ['name'],
                    proxy: {
                        type: 'memory',
                        reader: 'json'
                    },
                    data: [
                        { name: 'Lily' },
                        { name: 'Rupert' },
                        { name: 'Utley' },
                        { name: 'Molly' },
                        { name: 'Pete' }
                    ]
                }));
            });
        });

        describe('reconfiguring with forceFit', function() {
            it('should reconfigure so each header is the same width', function() {
                var gridData = [[{
                        id: 1,
                        text: 'Item 1',
                        type: 'a'
                    }, {
                        id: 2,
                        text: 'Item 2',
                        type: 'c'
                    }, {
                        id: 3,
                        text: 'Item 3',
                        type: 'b'
                    }, {
                        id: 4,
                        text: 'Item 4',
                        type: 'b'
                    }, {
                        id: 5,
                        text: 'Item 5',
                        type: 'a'
                    }, {
                        id: 6,
                        text: 'Item 6',
                        type: 'b'
                    }, {
                        id: 7,
                        text: 'Item 7',
                        type: 'c'
                    }, {
                        id: 8,
                        text: 'Item 8',
                        type: 'a'
                    }, {
                        id: 9,
                        text: 'Item 9',
                        type: 'c'
                    }, {
                        id: 10,
                        text: 'Item 10',
                        type: 'b'
                    }
                    ], [{
                        id: 1,
                        city: 'New York',
                        country: 'U.S.A.'
                    }, {
                        id: 2,
                        city: 'London',
                        country: 'United Kingdom'
                    }, {
                        id: 3,
                        city: 'Sydney',
                        country: 'Australia'
                    }, {
                        id: 4,
                        city: 'Los Angeles',
                        country: 'U.S.A.'
                    }, {
                        id: 5,
                        city: 'Melbourne',
                        country: 'Australia'
                    }, {
                        id: 6,
                        city: 'Montreal',
                        country: 'Canada'
                    }, {
                        id: 7,
                        city: 'Paris',
                        country: 'France'
                    }, {
                        id: 8,
                        city: 'Nice',
                        country: 'France'
                    }, {
                        id: 9,
                        city: 'Rome',
                        country: 'Italy'
                    }, {
                        id: 10,
                        city: 'Liverpool',
                        country: 'United Kingdom'
                    }]],
                    fields = [[
                        'id', 'text', 'type'
                    ], [
                        'id', 'city', 'country'
                    ]],
                    sorters = [[{
                        property: 'type',
                        direction: 'ASC'
                    }],
                    [{
                        property: 'country',
                        direction: 'DESC'
                    }]],
                    // working
                    //            ,gridColumns = [[
                    //                {header:'Item', dataIndex:'text', flex:1},{header:'Type', dataIndex:'type'}
                    //            ],[
                    //                {header:'City', dataIndex:'city', flex:1},{header:'Country', dataIndex:'country'}
                    //            ]]

                    // misaligned columns and header
                    gridColumns = [[{
                        header: 'Item',
                        dataIndex: 'text'
                    }, {
                        header: 'Type',
                        dataIndex: 'type'
                    }], [{
                        header: 'City',
                        dataIndex: 'city'
                    }, {
                        header: 'Country',
                        dataIndex: 'country'
                    }]],
                    stores = [],
                    headerContainerWidth,
                    columns;

                stores[0] = new Ext.data.Store({
                    fields: fields[0],
                    data: gridData[0],
                    sorters: sorters[0]
                });

                stores[1] = new Ext.data.Store({
                    fields: fields[1],
                    data: gridData[1],
                    sorters: sorters[1]
                });

                // First, create empty grid
                grid = new Ext.grid.Panel({
                    title: 'Grid reconfigure',
                    forceFit: true,
                    renderTo: Ext.getBody(),
                    width: 300,
                    height: 400,
                    columns: [],
                    viewConfig: {
                        emptyText: 'No records found.',
                        deferEmptyText: false
                    }
                });

                expect(grid.query('gridcolumn').length).toEqual(0);

                // Reconfigure grid with new store and columns
                grid.reconfigure(stores[0], gridColumns[0]);
                headerContainerWidth = grid.child('headercontainer').el.getViewSize().width;
                columns = grid.query('gridcolumn');
                expect(columns.length).toEqual(2);
                expect(columns[0].getWidth()).toEqual(headerContainerWidth / 2);
                expect(columns[1].getWidth()).toEqual(headerContainerWidth / 2);
                expect(columns[1].el.hasCls('x-column-header-sort-ASC')).toBe(true);

                // Reconfigure grid with new store and columns
                grid.reconfigure(stores[1], gridColumns[1]);
                columns = grid.query('gridcolumn');
                expect(columns.length).toEqual(2);
                expect(columns[0].getWidth()).toEqual(headerContainerWidth / 2);
                expect(columns[1].getWidth()).toEqual(headerContainerWidth / 2);
                expect(columns[1].el.hasCls('x-column-header-sort-DESC')).toBe(true);
            });
        });
    });

    // Remove simjax when re-enabling these specs
    xdescribe('Buffered rendering', function() {
        var grid,
            view,
            grouping,
            smallData = [
                { 'name': 'Homer', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Marge', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Lisa',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Bart',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" }
            ],
            largeData = [
                { 'name': 'Homer1', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer2', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer3', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer4', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer5', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer6', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer7', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer8', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer9', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Homer10', "email": "homer@simpsons.com",  "phone": "555-222-1244", "role": "Parent" },
                { 'name': 'Marge1', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge2', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge3', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge4', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge5', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge6', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge7', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge8', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge9', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Marge10', "email": "marge@simpsons.com", "phone": "555-222-1254", "role": "Parent" },
                { 'name': 'Lisa1',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa2',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa3',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa4',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa5',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa6',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa7',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa8',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa9',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Lisa10',  "email": "lisa@simpsons.com",  "phone": "555-111-1224", "role": "Child" },
                { 'name': 'Bart1',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart2',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart3',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart4',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart5',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart6',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart7',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart8',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart9',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" },
                { 'name': 'Bart10',  "email": "bart@simpsons.com",  "phone": "555-222-1234", "role": "Child" }
            ],
            columns = [
                { header: 'Name',  dataIndex: 'name' },
                { header: 'Email', dataIndex: 'email', flex: 1 },
                { header: 'Phone', dataIndex: 'phone' }
            ],
            makeSmallGrid = function(cfg) {
                return new Ext.grid.Panel(Ext.apply({
                    renderTo: Ext.getBody(),
                    columns: columns,
                    store: {
                        fields: ['name', 'email', 'phone', 'role'],
                        data: smallData,
                        groupField: 'role'
                    }
                }, cfg));
            },
            makeLargeGrid = function(cfg) {
                return new Ext.grid.Panel(Ext.apply({
                    renderTo: Ext.getBody(),
                    columns: columns,
                    store: {
                        fields: ['name', 'email', 'phone', 'role'],
                        data: largeData,
                        groupField: 'role'
                    }
                }, cfg));
            };

        afterEach(function() {
            grid.destroy();
        });

        describe('grouping with buffered rendering', function() {
            it('should collapse correctly when all data fits inside view height', function() {
                grid = makeSmallGrid({
                    height: 400,
                    width: 600,
                    features: {
                        ftype: 'grouping',
                        startCollapsed: false
                    }
                });
                view = grid.view;
                grouping = view.findFeature('grouping');

                // Wait for initial render
                waitsFor(function() {
                    return view.all.getCount() !== 0;
                });

                runs(function() {
                    expect(view.dataSource.getCount()).toEqual(4);
                    expect(view.all.getCount()).toEqual(4);

                    // After collapsing there will be one placeholder record which is represented by a group header row,
                    // Then the two "Parent" rows, the first of which will wrap a group header and a data row.
                    grouping.collapse('Child');
                    expect(view.dataSource.getCount()).toEqual(3);
                    expect(view.all.getCount()).toEqual(3);
                });
            });

            it('should collapse correctly when all data does not fit inside view height', function() {
                grid = makeLargeGrid({
                    height: 400,
                    width: 600,
                    border: false,
                    features: {
                        ftype: 'grouping',
                        startCollapsed: false
                    },
                    trailingBufferZone: 0,
                    leadingBufferZone: 1
                });
                view = grid.view;
                grouping = view.findFeature('grouping');

                // Wait for initial render
                waitsFor(function() {
                    return view.all.getCount() !== 0;
                });

                runs(function() {
                    var row4 = view.getNode(3);

                    grid.setHeight(row4.offsetTop + row4.offsetHeight + grid.headerCt.getHeight());

                    // All records will be represented by a row.
                    // The first row of each group will be a wrap which encapsulates the header and the first child row
                    expect(view.dataSource.getCount()).toEqual(40);
                    expect(view.all.getCount()).toEqual(view.bufferedRenderer.viewSize);

                    // After collapsing there will be one placeholder record which is represented by a group header row,
                    // Then the twenty "Parent" rows, the first of which will wrap a group header and a data row.
                    grouping.collapse('Child');
                    expect(view.dataSource.getCount()).toEqual(21);
                    expect(view.all.getCount()).toEqual(view.bufferedRenderer.viewSize);
                });
            });

            it('should reduce the scrollHeight when collapsing groups and increase when expanding', function() {
                grid = makeLargeGrid({
                    height: 400,
                    width: 600,
                    border: false,
                    features: {
                        ftype: 'grouping',
                        startCollapsed: false
                    }
                });
                view = grid.view;
                grouping = view.findFeature('grouping');

                // Wait for initial render
                waitsFor(function() {
                    return view.all.getCount() !== 0;
                });

                runs(function() {
                    var scrollRange = view.el.dom.scrollHeight,
                        newScrollRange;

                    grouping.collapse('Child');

                    // When collapsing, the scroll range should be recalculated, and the stretcher div moved upwards
                    expect(newScrollRange = view.el.dom.scrollHeight).toBeLessThan(scrollRange);
                    scrollRange = newScrollRange;
                    grouping.collapse('Parent');
                    expect(newScrollRange = view.el.dom.scrollHeight).toBeLessThan(scrollRange);
                    scrollRange = newScrollRange;

                    // When expanding, the scroll range should be recalculated, and the stretcher div moved downwards
                    grouping.expand('Child');
                    expect(newScrollRange = view.el.dom.scrollHeight).toBeGreaterThan(scrollRange);
                    scrollRange = newScrollRange;
                    grouping.expand('Parent');
                    expect(newScrollRange = view.el.dom.scrollHeight).toBeGreaterThan(scrollRange);

                });
            });
        });

        it('should reload after remote filter', function() {
            function makeRows(n, total) {
                var data = [],
                    i = 1;

                for (i = 1; i <= n; ++i) {
                    data.push({
                        id: i,
                        title: 'Title' + i
                    });
                }

                return {
                    data: data,
                    totalCount: total
                };
            }

            MockAjaxManager.addMethods();
            Ext.define('ForumThread', {
                extend: 'Ext.data.Model',
                fields: ['id', 'title']
            });

            // create the Data Store
            var store = new Ext.data.BufferedStore({
                model: 'ForumThread',
                asynchronousLoad: false,
                pageSize: 350,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl',
                    reader: {
                        rootProperty: 'data',
                        totalProperty: 'totalCount'
                    }
                },
                remoteFilter: true
            });

            grid = new Ext.grid.Panel({
                width: 700,
                height: 500,
                store: store,
                columns: [{
                    text: "Topic",
                    dataIndex: 'title',
                    flex: 1
                }],
                renderTo: Ext.getBody()
            });

            store.load();

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(makeRows(350, 5000))
            });

            store.filter({
                value: 'quicktips'
            });
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(makeRows(20, 20))
            });

            expect(grid.getView().getNodes().length).toBe(20);

            MockAjaxManager.removeMethods();
            Ext.undefine('ForumThread');
        });

        it("should update the view when removing a chunk of records in the middle", function() {
           var data = [],
                i = 0;

            for (; i < 200; ++i) {
                data.push({
                    name: 'Name' + i
                });
            }

            var store = new Ext.data.Store({
                fields: ['name'],
                data: data
            });

            grid = new Ext.grid.Panel({
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                store: store,
                columns: [{
                    dataIndex: 'name',
                    text: 'Name',
                    flex: 1
                }]
            });

            store.remove(store.getRange(1, 198));
            expect(grid.getView().all.getCount()).toBe(2);
        });

        it('should render the last row selected', function() {
            // Create a data block which can be read and broken into pages by an appropriately configured MemoryProxy
            function makeRows(n) {
                var data = [],
                    i = 1;

                for (i = 1; i <= n; ++i) {
                    data.push({
                        id: i,
                        title: 'Title' + i
                    });
                }

                return {
                    success: true,
                    total: n,
                    data: data
                };
            }

            Ext.define('ForumThread', {
                extend: 'Ext.data.Model',
                fields: ['id', 'title']
            });

            var store = new Ext.data.BufferedStore({
                    model: 'ForumThread',
                    asynchronousLoad: false,
                    pageSize: 50,
                    proxy: {
                        type: 'memory',
                        enablePaging: true,
                        reader: {
                            rootProperty: 'data'
                        },
                        data: makeRows(200)
                    },
                    // Ensure that all 4 pages are read eaglerly
                    leadingBufferZone: 200
                }),
                lastRecord;

            grid = new Ext.grid.Panel({
                width: 700,
                height: 300,
                store: store,
                columns: [{
                    text: "Topic",
                    dataIndex: 'title',
                    flex: 1
                }],
                renderTo: Ext.getBody()
            });

            // Will be synchronous from the MemoryProxy
            store.load();

            // All four 50 record pages should have been loaded due to large leadingBufferZone
            expect(store.getTotalCount()).toBe(200);

            // Select last record even though it is not rendered
            lastRecord = store.getAt(199);
            grid.getSelectionModel().select(lastRecord);

            // Scroll last record into view. It should be rendered in selected rendition.
            grid.view.el.setScrollTop(4000);

            waits(10);

            runs(function() {
                var lastRow = Ext.get(grid.view.getNode(lastRecord));

                // The row corresponding to the last record should have the selected class.
                expect(lastRow.hasCls(Ext.view.Table.prototype.selectedItemCls)).toBe(true);
            });

            Ext.undefine('ForumThread');
        });

        describe('reload', function() {
            var wasCalled = false;

            function createGrid() {
                store = new Ext.data.BufferedStore({
                    model: 'Foo',
                    asynchronousLoad: false,
                    pageSize: 100,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/Panel/store/reload',
                        reader: {
                            type: 'json'
                        }
                    },
                    autoLoad: true,
                    listeners: {
                        prefetch: function(store, records) {
                            wasCalled = true;
                        }
                    }
                });

                grid = new Ext.grid.Panel({
                    width: 700,
                    height: 500,
                    store: store,
                    columns: [{
                        text: 'Topic',
                        dataIndex: 'title',
                        flex: 1
                    }],
                    renderTo: Ext.getBody()
                });

                view = grid.view;
            }

            beforeEach(function() {
                Ext.define('Foo', {
                    extend: 'Ext.data.Model',
                    fields: ['id', 'title']
                });
            });

            afterEach(function() {
                store.destroy();
                grid.destroy();
                Ext.undefine('Foo');
                wasCalled = false;
            });

            it('should reload the current view and buffers', function() {
                var start, end;

                createGrid();

                waitsFor(function() {
                    return wasCalled;
                });

                runs(function() {
                    start = store.lastRequestStart;
                    end = store.lastRequestEnd;

                    wasCalled = false;
                    store.reload();
                });

                waitsFor(function() {
                    return wasCalled;
                });

                runs(function() {
                    expect(store.lastRequestStart).toEqual(start);
                    expect(store.lastRequestEnd).toEqual(end);
                });
            });

            it('should retain the scroll position on reload', function() {
                var scrollHeight;

                createGrid();

                waitsFor(function() {
                    return wasCalled;
                });

                runs(function() {
                    wasCalled = false;
                    grid.view.bufferedRenderer.scrollTo(3000, true, function() {
                        wasCalled = true;
                    });
                });

                waitsFor(function() {
                    return wasCalled;
                });

                runs(function() {
                    wasCalled = false;
                    scrollHeight = grid.view.body.dom.scrollHeight;
                    store.reload();
                });

                waitsFor(function() {
                    return wasCalled;
                });

                runs(function() {
                    expect(grid.view.body.dom.scrollHeight).toEqual(scrollHeight);
                });
            });
        });
    });

    xdescribe("View's element cache", function() {
        /*
         * creates rows for "Lisa", "Bart", "Homer" and "Marge"
         */
        beforeEach(createGrid);

        describe('Removing single row', function() {
            it('should move subsequent rows up', function() {
                grid.view.all.removeElement(0, true);

                // First cell - name field - should now be "Bart"
                expect(grid.view.all.first().child(grid.view.getCellSelector(), true).childNodes[0].innerHTML).toEqual('Bart');
                expect(grid.view.all.getCount()).toBe(3);
            });
        });

        describe('Removing range', function() {
            it('should move subsequent rows up', function() {
                grid.view.all.removeRange(0, 1, true);

                // First cell - name field - should now be "Homer"
                expect(grid.view.all.first().child(grid.view.getCellSelector(), true).childNodes[0].innerHTML).toEqual('Homer');
                expect(grid.view.all.getCount()).toBe(2);
            });
        });

        describe('removing larger range than is rendered (buffered rendering does this)', function() {
            it('should only attempt to remove valid element indices', function() {
                var rows = grid.view.all;

                // Attempt to remove element past end
                rows.removeElement(4);

                expect(rows.getCount()).toEqual(4);
                expect(rows.startIndex).toEqual(0);
                expect(rows.endIndex).toEqual(3);
            });
        });

    });

    xdescribe('emptyText', function() {
        function setup(cfg) {
            cfg = Ext.apply({}, cfg || {}, {
                xtype: 'grid',
                columns: [
                    { dataIndex: 'field1' },
                    { dataIndex: 'field2' }
                ],
                border: false,
                width: 500,
                height: 300,
                store: [],
                hideHeaders: true,
                renderTo: Ext.getBody(),
                viewConfig: {
                    deferEmptyText: false
                }
            });

            grid = Ext.widget(cfg);
        }

        afterEach(function() {
            grid.destroy();
            grid = null;
        });

        describe('defined on grid panel', function() {
            it('should render the emptyText to the grid view (string)', function() {
                runs(function() {
                    setup({ emptyText: 'foobar' });
                });

                waitsFor(function() {
                    return grid.isVisible();
                });

                runs(function() {
                    expect(grid.view.el.dom.lastChild).hasHTML(grid.emptyText);
                });
            });

            it('should render the emptyText to the grid view (html)', function() {
                runs(function() {
                    setup({ emptyText: '<div class="baz">foobar</div>' });
                });

                waitsFor(function() {
                    return grid.isVisible();
                });

                runs(function() {
                    expect(grid.view.el.dom.lastChild).hasHTML(grid.emptyText);
                });
            });

            it('should wrap the emptyText with a div with the CSS class "x-grid-empty"', function() {
                runs(function() {
                    setup({ emptyText: 'foobar' });
                });

                waitsFor(function() {
                    return grid.isVisible();
                });

                runs(function() {
                    expect(grid.view.el.dom.lastChild.tagName).toBe('DIV');
                    expect(grid.view.el.dom.lastChild.className).toBe('x-grid-empty');
                });
            });
        });

        describe('defined in view config', function() {
            it('should render the emptyText to the grid view when defined in viewConfig (string)', function() {
                runs(function() {
                    setup({
                        viewConfig: {
                            deferEmptyText: false,
                            emptyText: 'foobar'
                        }
                    });
                });

                waitsFor(function() {
                    return grid.isVisible();
                });

                runs(function() {
                    expect(grid.view.el.dom.lastChild).hasHTML(grid.viewConfig.emptyText);
                });
            });

            it('should render the emptyText to the grid view when defined in viewConfig (html)', function() {
                runs(function() {
                    setup({
                        viewConfig: {
                            deferEmptyText: false,
                            emptyText: '<div class="baz">foobar</div>'
                        }
                    });
                });

                waitsFor(function() {
                    return grid.isVisible();
                });

                runs(function() {
                    expect(grid.view.el.dom.lastChild).hasHTML(grid.viewConfig.emptyText);
                });
            });

            it('should wrap the emptyText with a div with the CSS class "x-grid-empty" when defined in viewConfg', function() {
                runs(function() {
                    setup({
                        viewConfig: {
                            deferEmptyText: false,
                            emptyText: 'foobar'
                        }
                    });
                });

                waitsFor(function() {
                    return grid.isVisible();
                });

                runs(function() {
                    expect(grid.view.el.dom.lastChild.tagName).toBe('DIV');
                    expect(grid.view.el.dom.lastChild.className).toBe('x-grid-empty');
                });
            });
        });

        it("should size based on the emptyText when shrink wrapping height", function() {
            grid = new Ext.grid.Panel({
                columns: [
                    { dataIndex: 'field1' },
                    { dataIndex: 'field2' }
                ],
                border: false,
                width: 500,
                store: [],
                hideHeaders: true,
                renderTo: Ext.getBody(),
                bodyStyle: 'border: 0',
                emptyCls: 'foo',
                emptyText: '<div style="height: 100px;"></div>',
                viewConfig: {
                    deferEmptyText: false
                }
            });
            expect(grid.getHeight()).toBe(100);
        });
    });

    describe("changing record id", function() {
        it("should update the view when changing from phantom to not phantom", function() {
            createGrid();
            var rec = store.first(),
                oldCount = store.getCount();

            rec.setId(1);
            store.remove(rec);
            expect(grid.getView().getNodes().length).toBe(oldCount - 1);
        });

        it("should update the view when changing a non phantom id", function() {
            createGrid({
                data: [
                    { id: 1, 'name': 'Lisa',  "email": "lisa@simpsons.com",  "phone": "555-111-1224"  },
                    { 'name': 'Bart',  "email": "bart@simpsons.com",  "phone": "555-222-1234"  },
                    { 'name': 'Homer', "email": "homer@simpsons.com", "phone": "555-222-1244"  },
                    { 'name': 'Marge', "email": "marge@simpsons.com", "phone": "555-222-1254"  }
                ]
            });

            var rec = store.first(),
                view = grid.getView();

            rec.setId(2);
            rec.set('name', 'foo');

            var item = view.getNode(0);

            var cell = item.rows[0].cells[0];

            expect(Ext.fly(cell).down(view.innerSelector, true)).hasHTML('foo');
        });
    });

    describe('statefulness', function() {
        // State will use a MemoryProvider by default because we do not need run-to-run state persistence
        beforeEach(function() {
            MockAjaxManager.addMethods();
        });

        afterEach(function() {
            if (grid) {
                Ext.state.Manager.set(grid.getStateId(), null);
            }

            MockAjaxManager.removeMethods();
            Ext.state.Manager.clear('foo');
        });

        describe("store binding", function() {
            var data = [
                { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
                { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
                { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
            ];

            it("should restore sort and filters", function() {
                createGrid(null, {
                        stateful: true,
                        stateId: 'withBinding',
                        plugins: [{
                            ptype: 'gridfilters'
                        }],
                        viewModel: {
                            stores: {
                                simpsonsStore: {
                                    statefulFilters: true,
                                    saveStatefulFilters: true,
                                    fields: ['name', 'email', 'phone'],
                                    data: data
                                }
                            }
                        },
                        bind: {
                            store: '{simpsonsStore}'
                        },
                        columns: [{
                            dataIndex: 'name',
                            filter: {
                                type: 'string'
                            }
                        }, {
                            dataIndex: 'email'
                        }]
                    });
                waitsFor(function() {
                    return !grid.store.isEmptyStore;
                });

                runs(function() {
                    jasmine.fireMouseEvent(grid.headerCt.items.getAt(0).el, 'click');
                    grid.getColumns()[0].filter.setValue('ar');

                    expect(grid.getStore().isSorted()).toBe(true);
                    expect(grid.getStore().isFiltered()).toBe(true);

                    grid.saveState();
                    grid.destroy();
                    createGrid(null, {
                        stateful: true,
                        stateId: 'withBinding',
                        plugins: [{
                            ptype: 'gridfilters'
                        }],
                        viewModel: {
                            stores: {
                                simpsonsStore: {
                                    statefulFilters: true,
                                    saveStatefulFilters: true,
                                    fields: ['name', 'email', 'phone'],
                                    data: data
                                }
                            }
                        },
                        bind: {
                            store: '{simpsonsStore}'
                        },
                        columns: [{
                            dataIndex: 'name',
                            filter: {
                                type: 'string'
                            }
                        }, {
                            dataIndex: 'email'
                        }]
                    });
                });

                waitsFor(function() {
                    return !grid.store.isEmptyStore;
                });

                runs(function() {
                    expect(grid.getStore().isSorted()).toBe(true);
                    expect(grid.getStore().isFiltered()).toBe(true);
                    expect(grid.getColumns()[0].filter.active).toBe(true);
                });
            });
        });

        describe('locked column state', function() {
            // https://sencha.jira.com/browse/EXTJS-19598
            // If the only visible locked column was moved to the locked side
            // as a result of state restoration, the locked grid did not display properly.
            it('should restore a locked column and ensure the locked side is visible', function() {
                createGrid(null, {
                    stateful: true,
                    stateId: 'lockedColumnState',
                    enableLocking: true
                });

                // Locked grid is hidden because it is empty
                expect(grid.lockedGrid.isVisible()).toBe(false);

                // colRef[2] begins life unlocked and hidden
                expect(colRef[2].locked).not.toBe(true);
                expect(colRef[2].isVisible()).toBe(false);

                colRef[2].show();
                grid.lock(colRef[2]);

                // This should cause show of the locked grid.
                expect(grid.lockedGrid.isVisible()).toBe(true);

                // We now expect the locked grid to be the width of colRef[2] plus its border width
                expect(grid.lockedGrid.width).toBe(colRef[2].getWidth() + grid.lockedGrid.gridPanelBorderWidth);

                grid.saveState();
                grid.destroy();

                createGrid(null, {
                    stateful: true,
                    stateId: 'lockedColumnState',
                    enableLocking: true
                });

                // The locked side should render visible because of colRef[2] (It will be colRef[0] now)
                // being *statefully* visible and locked.
                expect(grid.lockedGrid.isVisible()).toBe(true);

                // We now expect the locked grid to be the width of colRef[0] plus its border width
                expect(grid.lockedGrid.width).toBe(colRef[0].getWidth() + grid.lockedGrid.gridPanelBorderWidth);
            });
        });

        describe('autoLoad = false', function() {
            var op = {
                    filter: {
                        property: 'name',
                        value: 'Name 1'
                    },
                    sort: {
                        property: 'name',
                        direction: 'DESC'
                    }
                },
                gridCfg, storeCfg;

            function setConfig(s, g) {
                storeCfg = Ext.apply({
                    autoLoad: false,
                    statefulFilters: true,
                    fields: ['name', 'sex', 'email', 'phone', { name: 'isSprog', type: 'boolean' }],
                    proxy: {
                        type: 'ajax',
                        url: '/fakeUrl'
                    },
                    data: null
                }, s);

                if (!g.stateId) {
                    throw 'Test requires a unique stateId';
                }

                gridCfg = Ext.apply({
                    stateful: true,
                    width: 600,
                    height: 400,
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 200 },
                        { header: 'Email', dataIndex: 'email', flex: 1 },
                        { header: 'Sprog?', dataIndex: 'isSprog', flex: 1 },
                        { header: 'Phone', dataIndex: 'phone', flex: 1 }
                    ],
                    selModel: {
                        selType: 'cellmodel'
                    },
                    // Save state in real-time.
                    saveDelay: 0
                }, g);
            }

            function makeUI(s, g) {
                setConfig(s, g);
                store = new Ext.data.Store(storeCfg);
                spyOn(store, 'flushLoad').andCallThrough();
                createGrid(store, gridCfg);
            }

            function doTest(cfg, method) {
                var gridConfig = {
                    stateId: Ext.id(null, 'stateful-filters-')
                };

                makeUI(cfg, gridConfig);

                expect(store.flushLoad.callCount).toBe(0);

                store[method](op[method]);
                grid.saveState();
                grid = store = Ext.destroy(grid, store);
                makeUI(cfg, gridConfig);

                expect(store.flushLoad.callCount).toBe(0);
            }

            function doExtendedTest(cfg, method) {
                doTest(cfg, method);

                synchronousLoad = true;
                store[method](op[method]);
                expect(store.flushLoad.callCount).toBe(1);
            }

            beforeEach(function() {
                synchronousLoad = false;
            });
            afterEach(function() {
                synchronousLoad = true;
            });

            describe('on page load', function() {
                describe('sorting', function() {
                    it('should not load when local sorting', function() {
                        doTest({
                            remoteSort: false
                        }, 'sort');
                    });

                    it('should not load when remote sorting', function() {
                        doTest({
                            remoteSort: true
                        }, 'sort');
                    });
                });

                describe('filtering', function() {
                    it('should not load when local filtering', function() {
                        doTest({
                            remoteFilter: false
                        }, 'filter');
                    });

                    it('should not load when remote filtering', function() {
                        doTest({
                            remoteFilter: true
                        }, 'filter');
                    });
                });
            });

            describe('after page load', function() {
                describe('sorting', function() {
                    it('should trigger a load when remote sorting', function() {
                        doExtendedTest({
                            remoteSort: true
                        }, 'sort');
                    });
                });

                describe('filtering', function() {
                    it('should trigger a load when remote filtering', function() {
                        doExtendedTest({
                            remoteFilter: true
                        }, 'filter');
                    });
                });
            });
        });

        // Remove simjax when re-enabling
        /*
        xit('should save and restore state', function() {
            var sp = Ext.create('Ext.state.CookieProvider'),
                statesaved = false,
                staterestored = false,
                headerCt,
                stateId = Ext.id(null, 'unitTestSimpsonsGrid-');

            // setup the state provider, all state information will be saved to a cookie
            Ext.state.Manager.setProvider(sp);

            createGrid({
                statefulFilters: true,
                groupField: 'sex',
                fields:['name', 'sex', 'email', 'phone', {name: 'isSprog', type: 'boolean'}],
                data: data
            }, {
                stateful: true,
                stateId: stateId,
                width: 600,
                height: 400,
                delay: 0,
                features: [{
                    ftype: 'grouping'
                }],
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 200, locked: true },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Sprog?', dataIndex: 'isSprog', flex: 1},
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ],
                selModel: {
                    selType: 'cellmodel'
                },

                listeners: {
                    statesave: function() {
                        statesaved = true;
                    }
                }
            });

            headerCt = grid.normalGrid.headerCt;

            grid.store.filter('isSprog', true);

            // Swap the email and sprog columns
            headerCt.move(1, 0);

            // Wait for the stateTask to tick...
            waitsFor(function() {
                return statesaved === true && grid.stateTask.taskRunCount === 1;
            });

            runs(function() {
                statesaved = false;

                // Check that the first 2 columns in the normal grid have been swapped from configured order
                expect(headerCt.items.items[0].text).toEqual("Sprog?");
                expect(headerCt.items.items[1].text).toEqual("Email");

                // Check that filtering has only left in the sprogs
                expect(grid.store.getCount()).toEqual(2);

                // Check that the grouping sort has put Lisa first, "Female" before "Male"
                expect(grid.store.getAt(0).get('name')).toEqual('Lisa');
                expect(grid.store.getAt(1).get('name')).toEqual('Bart');

                // Reverse the default grouping direction to pug Bart first
                // This should be saved in state
                grid.store.group('sex', 'DESC');

                // Wait for the stateTask to tick...
                // taskRunCount is reset each time because the task runs one time only
            });

            waitsFor(function() {
                return statesaved === true && grid.stateTask.taskRunCount === 1;
            });

            runs(function() {
                // Check that the change of grouping sort has put Bart first, "Male" before "Female"
                expect(grid.store.getAt(0).get('name')).toEqual('Bart');
                expect(grid.store.getAt(1).get('name')).toEqual('Lisa');

                grid.destroy();

                // Recreate the grid
                createGrid({
                    statefulFilters: true,
                    groupField: 'sex',
                    fields:['name', 'sex', 'email', 'phone', {name: 'isSprog', type: 'boolean'}],
                    data: {'items': [
                        { 'name': 'Homer', 'sex': 'Male',   "email": "homer@simpsons.com", "phone":"555-222-1244", "isSprog": false },
                        { 'name': 'Bart',  'sex': 'Male',   "email": "bart@simpsons.com",  "phone":"555-222-1234", "isSprog": true },
                        { 'name': 'Marge', 'sex': 'Female', "email": "marge@simpsons.com", "phone":"555-222-1254", "isSprog": false },
                        { 'name': 'Lisa',  'sex': 'Female', "email": "lisa@simpsons.com",  "phone":"555-111-1224", "isSprog": true }
                    ]}
                }, {
                    deferRowRender: false,
                    stateful: true,
                    stateId: 'unitTestSimpsonsGrid',
                    width: 600,
                    height: 400,
                    features: [{
                        ftype: 'grouping'
                    }],
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 200, locked: true },
                        { header: 'Email', dataIndex: 'email', flex: 1 },
                        { header: 'Sprog?', dataIndex: 'isSprog', flex: 1},
                        { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                    ],
                    selModel: {
                        selType: 'cellmodel'
                    },
                    listeners: {
                        staterestore: function() {
                            staterestored = true;
                        }
                    }
                });
            });

            waitsFor(function() {
                return staterestored === true && grid.stateTask.taskRunCount === 1;
            });

            runs(function() {
                headerCt = grid.normalGrid.headerCt;

                // Check that the first 2 columns in the normal grid have been swapped from configured order
                expect(headerCt.items.items[0].text).toEqual("Sprog?");
                expect(headerCt.items.items[1].text).toEqual("Email");

                // Check that filtering has only left in the sprogs
                expect(grid.store.getCount()).toEqual(2);

                // Check that the grouping sort has put Bart first, "Male" before "Female"
                // The change of grouping should have saved the grouping state.
                expect(grid.store.getAt(0).get('name')).toEqual('Bart');
                expect(grid.store.getAt(1).get('name')).toEqual('Lisa');
                sp.clear('unitTestSimpsonsGrid');
            });
        });

        xdescribe('loading/reloading', function() {
            // Note the intent of these specs is to demonstrate that a store should only have
            // its .load method called once regardless of any grid or store configs such as
            // remote sorting and grid filters.  See EXTJS-10029.
            var wasCalled = false;

            Ext.ux.ajax.SimManager.init({
                delay: 10,
                defaultSimlet: null
            }).register({
                '/grid/Panel/statefulness': {
                    data: (function() {
                        var i = 0,
                            recs = [];

                        for (; i < 50; i++) {
                            recs.push({
                                name: 'Name' + i
                            });
                        }

                        return recs;
                    }()),
                    stype: 'json'
                }
            });

            beforeEach(function() {
                Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
                // Set a fake cookie so there is a state to lookup and apply.
                Ext.state.Manager.set('unitTestSimpsonsGrid', 'BT was here');
            });

            afterEach(function() {
                Ext.state.Manager.clear('unitTestSimpsonsGrid');
                wasCalled = false;
            });

            describe('plain stateful grid', function() {
                it('should only load the store once using an autoLoad store', function() {
                    spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                    createGrid({
                        fields: ['name'],
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/Panel/statefulness'
                        },
                        listeners: {
                            load: function(store, records, successful) {
                                wasCalled = true;
                            }
                        }
                        //statefulFilters: true,
                        //fields:['name', 'sex', 'email', 'phone', {name: 'isSprog', type: 'boolean'}],
                        //data: data,
                    }, {
                        id: 'foo0',
                        stateful: true,
                        stateId: 'unitTestSimpsonsGrid',
                        columns: [
                            { header: 'Name', dataIndex: 'name', width: 100 }
                        ]
                    });

                    waitsFor(function() {
                        return wasCalled;
                    });

                    runs(function() {
                        expect(Ext.data.Store.prototype.load.callCount).toBe(1);
                    });
                });

                it('should only load the store once when manually loading the store', function() {
                    createGrid({
                        fields: ['name'],
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/Panel/statefulness'
                        },
                        listeners: {
                            load: function(store, records, successful) {
                                wasCalled = true;
                            }
                        }
                    }, {
                        id: 'foo0.5',
                        stateful: true,
                        stateId: 'unitTestSimpsonsGrid',
                        columns: [
                            { header: 'Name', dataIndex: 'name', width: 100 }
                        ]
                    });

                    spyOn(grid.store, 'load').andCallThrough();

                    grid.store.load();

                    waitsFor(function() {
                        return wasCalled;
                    });

                    runs(function() {
                        expect(grid.store.load.callCount).toBe(1);
                    });
                });
            });

            describe('with remote sorting enabled', function() {
                it('should only load the store once when sorting remotely and using an autoLoad store', function() {
                    spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                    createGrid({
                        fields: ['name'],
                        remoteSort: true,
                        sorters: [{
                            property: 'name'
                        }],
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/Panel/statefulness'
                        },
                        listeners: {
                            load: function(store, records, successful) {
                                wasCalled = true;
                            }
                        }
                    }, {
                        id: 'foo1',
                        stateful: true,
                        stateId: 'unitTestSimpsonsGrid',
                        columns: [
                            { header: 'Name', dataIndex: 'name', width: 100 }
                        ]
                    });

                    waitsFor(function() {
                        return wasCalled;
                    });

                    runs(function() {
                        expect(Ext.data.Store.prototype.load.callCount).toBe(1);
                    });
                });

                it('should only load the store once when sorting remotely and manually loading the store', function() {
                    createGrid({
                        fields: ['name'],
                        remoteSort: true,
                        sorters: [{
                            property: 'name'
                        }],
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/Panel/statefulness'
                        },
                        listeners: {
                            load: function(store, records, successful) {
                                wasCalled = true;
                            }
                        }
                    }, {
                        id: 'foo1.5',
                        stateful: true,
                        stateId: 'unitTestSimpsonsGrid',
                        columns: [
                            { header: 'Name', dataIndex: 'name', width: 100 }
                        ]
                    });

                    spyOn(grid.store, 'load').andCallThrough();

                    grid.store.load();

                    waitsFor(function() {
                        return wasCalled;
                    });

                    runs(function() {
                        expect(grid.store.load.callCount).toBe(1);
                    });
                });
            });

            describe('with remote sorting enabled and grid filters', function() {
                it('should only load the store once when sorting remotely and using an autoLoad store', function() {
                    spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                    createGrid({
                        fields: ['name'],
                        remoteSort: true,
                        sorters: [{
                            property: 'name'
                        }],
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/Panel/statefulness'
                        },
                        listeners: {
                            load: function(store, records, successful) {
                                wasCalled = true;
                            }
                        }
                    }, {
                        id: 'foo2',
                        stateful: true,
                        stateId: 'unitTestSimpsonsGrid',
                        columns: [
                            { header: 'Name', dataIndex: 'name', width: 100, filter: { type: 'string' } }
                        ],
                        features: [{
                            ftype: 'filters',
                            encode: false
                        }]
                    });

                    waitsFor(function() {
                        return wasCalled;
                    });

                    runs(function() {
                        expect(Ext.data.Store.prototype.load.callCount).toBe(1);
                    });
                });

                it('should only load the store once when sorting remotely and manually loading the store', function() {
                    createGrid({
                        fields: ['name'],
                        remoteSort: true,
                        sorters: [{
                            property: 'name'
                        }],
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/Panel/statefulness'
                        },
                        listeners: {
                            load: function(store, records, successful) {
                                wasCalled = true;
                            }
                        }
                    }, {
                        id: 'foo2.5',
                        stateful: true,
                        stateId: 'unitTestSimpsonsGrid',
                        columns: [
                            { header: 'Name', dataIndex: 'name', width: 100, filter: { type: 'string' } }
                        ],
                        features: [{
                            ftype: 'filters',
                            encode: false
                        }]
                    });

                    spyOn(grid.store, 'load').andCallThrough();

                    grid.store.load();

                    waitsFor(function() {
                        return wasCalled;
                    });

                    runs(function() {
                        expect(grid.store.load.callCount).toBe(1);
                    });
                });
            });
        });

        xdescribe('should not make more than one network request', function() {
            it('stateful, remoteSorting, grid filters', function() {
                var storeCfg = {
                    remoteSort: true,
                    sorters: [{
                        property: 'name',
                        direction: 'DESC'
                    }],
                    autoLoad: true,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/Panel/statefulness',
                        reader: {
                            type: 'json',
                            rootProperty: 'items'
                        }
                    }
                };

                Ext.state.Provider();
                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0,
                    features: [{
                        ftype: 'filters',
                        local: true,
                        filters: [{
                            dataIndex: 'name',
                            value: 'ben'
                        }]
                    }]
                });

                grid.saveState();

                Ext.destroy(grid);

                spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0,
                    features: [{
                        ftype: 'filters',
                        local: true,
                        filters: [{
                            dataIndex: 'name',
                            value: 'ben'
                        }]
                    }]
                });

                waits(10);

                runs(function() {
                    expect(store.load.callCount).toBe(1);
                });
            });

            it('stateful, remoteSorting', function() {
                var storeCfg = {
                    remoteSort: true,
                    sorters: [{
                        property: 'name',
                        direction: 'DESC'
                    }],
                    autoLoad: true,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/Panel/statefulness',
                        reader: {
                            type: 'json',
                            rootProperty: 'items'
                        }
                    }
                };

                Ext.state.Provider();

                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0
                });

                grid.saveState();

                Ext.destroy(grid);

                spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0
                });

                waits(10);

                runs(function() {
                    expect(store.load.callCount).toBe(1);
                });
            });
        });

        xdescribe('should not make a network request when autoLoad is false', function() {
            // Note that the storeCfg is the same as above except that autoLoad is explicitly set to false.
            // This demonstrates how developers can control stores even when the grid is stateful.
            it('stateful, remoteSorting, grid filters', function() {
                var storeCfg = {
                    remoteSort: true,
                    sorters: [{
                        property: 'name',
                        direction: 'DESC'
                    }],
                    autoLoad: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/Panel/statefulness',
                        reader: {
                            type: 'json',
                            rootProperty: 'items'
                        }
                    }
                };

                Ext.state.Provider();
                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0,
                    features: [{
                        ftype: 'filters',
                        local: true,
                        filters: [{
                            dataIndex: 'name',
                            value: 'ben'
                        }]
                    }]
                });

                grid.saveState();

                Ext.destroy(grid);

                spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0,
                    features: [{
                        ftype: 'filters',
                        local: true,
                        filters: [{
                            dataIndex: 'name',
                            value: 'ben'
                        }]
                    }]
                });

                waits(10);

                runs(function() {
                    expect(store.load.callCount).toBe(0);
                });
            });

            it('stateful, remoteSorting', function() {
                var storeCfg = {
                    remoteSort: true,
                    sorters: [{
                        property: 'name',
                        direction: 'DESC'
                    }],
                    autoLoad: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/Panel/statefulness',
                        reader: {
                            type: 'json',
                            rootProperty: 'items'
                        }
                    }
                };

                Ext.state.Provider();

                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0
                });

                grid.saveState();

                Ext.destroy(grid);

                spyOn(Ext.data.Store.prototype, 'load').andCallThrough();

                createGrid(storeCfg, {
                    stateful: true,
                    stateId: 'statefulness',
                    width: 600,
                    height: 400,
                    delay: 0
                });

                waits(10);

                runs(function() {
                    expect(store.load.callCount).toBe(0);
                });
            });
        });
        */
    });

    describe('updating', function() {
        var store, grid, view, layoutCounter, refreshCounter;

        beforeEach(function() {
            store = Ext.create('Ext.data.Store', {
                fields: ['name', 'email', 'phone'],
                proxy: {
                    type: 'ajax',
                    url: 'faleUrl'
                },
                autoSync: false
            });

            grid = Ext.create('Ext.grid.Panel', {
                title: 'Simpsons',
                store: store,
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    // Specify variableRowHeight so we can test batching of the layouts caused
                    // by two returned updated records triggering refreshSize calls.
                    { header: 'Email', dataIndex: 'email', flex: 1, variableRowHeight: true },
                    { header: 'Phone', dataIndex: 'phone', flex: 1 }
                ],
                height: 200,
                width: 400,
                renderTo: Ext.getBody()
            });
            view = grid.getView();
            MockAjaxManager.addMethods();
        });

        afterEach(function() {
            store.destroy();
            grid.destroy();
            MockAjaxManager.removeMethods();
        });

        it("should only update the modified field's cell", function() {
            store.load();

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([
                    { name: 'Lisa',  email: 'lisa@simpsons.com',  phone: '555-111-1224'  },
                    { name: 'Bart',  email: 'bart@simpsons.com',  phone: '555-222-1234'  },
                    { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244'  },
                    { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254'  }
                ])
            });

            var firstRow = view.getRow(0),
                nameCell = firstRow.childNodes[0].innerHTML,
                emailCell = firstRow.childNodes[1].innerHTML,
                phoneCell = firstRow.childNodes[2].innerHTML;

            store.getAt(0).set('phone', "555-111-1111");
            store.getAt(1).set('phone', "555-222-2222");

            // After that set of  the phone number ONLY the last cell, [2] must have been replaced
            expect(firstRow.childNodes[0].innerHTML === nameCell).toBe(true);
            expect(firstRow.childNodes[1].innerHTML === emailCell).toBe(true);
            expect(firstRow.childNodes[2].innerHTML === phoneCell).toBe(false);

            // cell[2] must contain the new phone number value
            expect(firstRow.childNodes[2].firstChild.firstChild.nodeValue).toBe('555-111-1111');

            nameCell = firstRow.childNodes[0].innerHTML;
            emailCell = firstRow.childNodes[1].innerHTML;
            phoneCell = firstRow.childNodes[2].innerHTML;

            // There should only be one more layout caused by the sync.
            // The two record updates in returned data should be bracketed by a suspend.
            layoutCounter = grid.layoutCounter;

            // sync should NOT fire refresh.
            refreshCounter = view.refreshCounter;

            store.sync();

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([
                    { name: 'Lisa',  email: 'lisa@simpsons.google.com',  phone: '555-111-1111'  },
                    { name: 'Bart',  email: 'bart@simpsons.google.com',  phone: '555-222-2222'  }
                ])
            });

            // Only one more layout should have been performed.
            // The two record updates in returned data should be bracketed by a suspend.
            // sync should NOT fire refresh.
            expect(grid.layoutCounter).toBe(layoutCounter + 1);

            // The postprocessing in ProxyStore#onProxyWrite and ProxyStore#onBatchComplete should NOT fire a data refresh event.
            expect(view.refreshCounter).toBe(refreshCounter);

            // After the receipt of a new email address ONLY the second cell, [1] must have been replaced
            firstRow = view.getRow(0);
            expect(firstRow.childNodes[0].innerHTML === nameCell).toBe(true);
            expect(firstRow.childNodes[1].innerHTML === emailCell).toBe(false);
            expect(firstRow.childNodes[2].innerHTML === phoneCell).toBe(true);

            // cell[1] must contain the new phone email value
            expect(firstRow.childNodes[1].firstChild.firstChild.nodeValue).toBe('lisa@simpsons.google.com');
        });
    });

    xdescribe('forceFit', function() {
        var grid, store, viewBody, beforeWidth, afterWidth;

        afterEach(function() {
            store.destroy();
            Ext.destroy(grid);
            grid = store = viewBody = beforeWidth = afterWidth = null;
        });

        it('should not allow extremely wide columns to resize the view', function() {
            store = Ext.create('Ext.data.Store', {
                storeId: 'simpsonsStore',
                fields: ['name', 'email', 'phone'],
                data: [
                    { 'name': 'Lisa asdf asdf asfd asdf asdf asfd asfd asfd asdf asfd asdf asdf asdf asdf asdf asdf asdf asdf',  "email": "lisa@simpsons.com",  "phone": "555-111-1224"  },
                    { 'name': 'Bart',  "email": "bart@simpsons.com",  "phone": "555-222-1234"  },
                    { 'name': 'Homer', "email": "homer@simpsons.com", "phone": "555-222-1244"  },
                    { 'name': 'Marge', "email": "marge@simpsons.com", "phone": "555-222-1254"  }
                ]
            });

            grid = Ext.create('Ext.grid.Panel', {
                title: 'Simpsons',
                store: store,
                forceFit: true,
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Email', dataIndex: 'email', width: 50 },
                    { header: 'Phone', dataIndex: 'phone', width: 50 }
                ],
                height: 200,
                width: 200,
                renderTo: document.body
            });

            viewBody = grid.view.body;

            beforeWidth = viewBody.getWidth();
            grid.headerCt.autoSizeColumn(grid.columns[0]);
            afterWidth = viewBody.getWidth();

            expect(afterWidth).not.toBeGreaterThan(beforeWidth);
        });

        it('should not push last column out of the view when hiding and showing', function() {
            var wasCalled = false;

            store = Ext.create('Ext.data.ArrayStore', {
                fields: [
                   { name: 'company' },
                   { name: 'price',      type: 'float', convert: null,     defaultValue: undefined },
                   { name: 'change',     type: 'float', convert: null,     defaultValue: undefined },
                   { name: 'pctChange',  type: 'float', convert: null,     defaultValue: undefined },
                   { name: 'lastChange', type: 'date',  dateFormat: 'n/j h:ia', defaultValue: undefined }
                ],
                data: [
                    ['3m Co',                               71.72, 0.02,  0.03,  '9/1 12:00am'],
                    ['Alcoa Inc',                           29.01, 0.42,  1.47,  '9/1 12:00am'],
                    ['Altria Group Inc',                    83.81, 0.28,  0.34,  '9/1 12:00am'],
                    ['American Express Company',            52.55, 0.01,  0.02,  '9/1 12:00am'],
                    ['American International Group, Inc.',  64.13, 0.31,  0.49,  '9/1 12:00am'],
                    ['AT&T Inc.',                           31.61, -0.48, -1.54, '9/1 12:00am'],
                    ['Boeing Co.',                          75.43, 0.53,  0.71,  '9/1 12:00am'],
                    ['Caterpillar Inc.',                    67.27, 0.92,  1.39,  '9/1 12:00am'],
                    ['Citigroup, Inc.',                     49.37, 0.02,  0.04,  '9/1 12:00am']
                ]
            });

            grid = Ext.create('Ext.grid.Panel', {
                store: store,
                forceFit: true,
                columns: [{
                    text: 'Company',
                    width: 200,
                    dataIndex: 'company'
                }, {
                    text: 'Price',
                    width: 75,
                    dataIndex: 'price'
                }, {
                    text: 'Change',
                    width: 75,
                    dataIndex: 'change'
                }, {
                    text: '% Change',
                    width: 75,
                    dataIndex: 'pctChange'
                }, {
                    text: 'Last Updated',
                    width: 85,
                    dataIndex: 'lastChange'
                }, {
                    menuDisabled: true,
                    sortable: false,
                    xtype: 'actioncolumn',
                    width: 50,
                    items: [{
                        icon: '../shared/icons/fam/delete.gif',  // Use a URL in the icon config
                        tooltip: 'Sell stock',
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = store.getAt(rowIndex);

                            alert("Sell " + rec.get('company'));
                        }
                    }, {
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = store.getAt(rowIndex);

                            alert((rec.get('change') < 0 ? "Hold " : "Buy ") + rec.get('company'));
                        }
                    }]
                }],
                listeners: {
                    viewready: function() {
                        wasCalled = true;
                    }
                },
                height: 400,
                width: 600,
                renderTo: document.body
            });

            viewBody = grid.view.body;

            waitsFor(function() {
                return wasCalled;
            });

            runs(function() {
                var i = 0,
                    col;

                beforeWidth = viewBody.getWidth();

                for (; i < 8; i++) {
                    if (i > 3) {
                        col = i % 4;
                        grid.columns[col].show();
                        grid.headerCt.autoSizeColumn(grid.columns[col]);
                    }
                    else {
                        grid.columns[i].hide();
                    }
                }

                afterWidth = viewBody.getWidth();

                expect(afterWidth).not.toBeGreaterThan(beforeWidth);
            });
        });
    });

    describe('column manager', function() {
        it('should create the column manager when columns is a config', function() {
            createGrid();

            expect(grid.columnManager).toBeDefined();
        });

        it('should create the column manager when columns is an instance', function() {
            createGrid({}, {
                columns: new Ext.grid.header.Container({
                    items: [
                        { header: 'Name',  dataIndex: 'name', width: 100 },
                        { header: 'Email', dataIndex: 'email', flex: 1 },
                        { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                    ]
                })
            });

            expect(grid.columnManager).toBeDefined();
        });
    });

    describe('focusing', function() {
        it("should restore focus when the view is refreshed with no buffered rendering", function() {
            var cellBeforeRefresh,
                cellAfterRefresh;

            createGrid(null, {
                bufferedRenderer: false
            });
            navModel.setPosition(1, 1);

            // Navigation conditions must be met.
            cellBeforeRefresh = view.getCellByPosition({ row: 1, column: 1 }, true);
            expect(view.el.query('.' + view.focusedItemCls).length).toBe(1);
            expect(cellBeforeRefresh).toHaveCls(view.focusedItemCls);

            store.fireEvent('refresh', store);

            // The DOM has changed, but focus conditions must be restored
            cellAfterRefresh = view.getCellByPosition({ row: 1, column: 1 }, true);
            expect(cellAfterRefresh !== cellBeforeRefresh).toBe(true);

            // Navigation conditions must be restored after the refresh.
            expect(view.el.query('.' + view.focusedItemCls).length).toBe(1);
            expect(cellAfterRefresh).toHaveCls(view.focusedItemCls);
        });

        it("should restore focus when the view is refreshed with buffered rendering", function() {
            var cellBeforeRefresh,
                cellAfterRefresh;

            createGrid();
            navModel.setPosition(1, 1);

            // Navigation conditions must be met.
            cellBeforeRefresh = view.getCellByPosition({ row: 1, column: 1 }, true);
            expect(view.el.query('.' + view.focusedItemCls).length).toBe(1);
            expect(cellBeforeRefresh).toHaveCls(view.focusedItemCls);

            store.fireEvent('refresh', store);

            // The DOM has changed, but focus conditions must be restored
            cellAfterRefresh = view.getCellByPosition({ row: 1, column: 1 }, true);
            expect(cellAfterRefresh !== cellBeforeRefresh).toBe(true);

            // Navigation conditions must be restored after the refresh.
            expect(view.el.query('.' + view.focusedItemCls).length).toBe(1);
            expect(cellAfterRefresh).toHaveCls(view.focusedItemCls);
        });

    });

    describe('buffered store, locking and sorting', function() {
        var ForumThread;

        beforeEach(function() {
            MockAjaxManager.addMethods();
            ForumThread = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['id', 'title']
            });
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        // https://sencha.jira.com/browse/EXTJS-18848
        it('should successfully sort a locked grid with a buffered store', function() {
            function makeRows(n, total) {
                var data = [],
                    i = 1;

                for (i = 1; i <= n; ++i) {
                    data.push({
                        id: i,
                        title: 'Title' + i
                    });
                }

                return {
                    data: data,
                    totalCount: total
                };
            }

            // create the Data Store
            var store = new Ext.data.Store({
                model: ForumThread,
                buffered: true,
                asynchronousLoad: false,
                pageSize: 350,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl',
                    reader: {
                        rootProperty: 'data',
                        totalProperty: 'totalCount'
                    }
                },
                remoteFilter: true
            });

            grid = new Ext.grid.Panel({
                width: 700,
                height: 500,
                store: store,
                columns: [{
                    text: 'ID',
                    dataIndex: 'id',
                    locked: true
                }, {
                    text: "Topic",
                    dataIndex: 'title',
                    flex: 1
                }],
                renderTo: Ext.getBody()
            });
            store.load();

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(makeRows(350, 5000))
            });

            // Sort by ID (data will already be in ID order)
            grid.getVisibleColumnManager().getColumns()[0].sort();

            // Passing is NOT throwing an error.
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(makeRows(350, 5000))
            });
        });
    });

    describe('buffered store, heighted by a viewport', function() {
        var ForumThread, viewport;

        beforeEach(function() {
            MockAjaxManager.addMethods();
            ForumThread = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['id', 'title']
            });
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
            Ext.destroy(viewport);
        });

        it('should successfully render the data', function() {
            // create the Data Store
            var store = new Ext.data.BufferedStore({
                model: ForumThread,
                asynchronousLoad: false,
                pageSize: 350,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl',
                    reader: {
                        rootProperty: 'data',
                        totalProperty: 'totalCount'
                    }
                },
                remoteFilter: true
            });

            expect(function() {
                viewport = new Ext.container.Viewport({
                    layout: 'fit',
                    items: grid = new Ext.grid.Panel({
                        store: store,
                        columns: [{
                            text: "Topic",
                            dataIndex: 'title',
                            flex: 1
                        }]
                    })
                });
            }).not.toThrow();
        });
    });

    describe('buffered store, dataset shrinks on reload', function() {
        var ForumThread;

        beforeEach(function() {
            MockAjaxManager.addMethods();
            ForumThread = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['id', 'title']
            });
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it('should successfully reload the smaller dataset, and render what it can', function() {
            function makeRows(n, total) {
                var data = [],
                    i = 1;

                for (i = 1; i <= n; ++i) {
                    data.push({
                        id: i,
                        title: 'Title' + i
                    });
                }

                return {
                    data: data,
                    totalCount: total
                };
            }

            // create the Data Store
            var store = new Ext.data.BufferedStore({
                model: ForumThread,
                asynchronousLoad: false,
                pageSize: 350,
                proxy: {
                    type: 'ajax',
                    url: 'fakeUrl',
                    reader: {
                        rootProperty: 'data',
                        totalProperty: 'totalCount'
                    }
                },
                remoteFilter: true
            });

            grid = new Ext.grid.Panel({
                width: 700,
                height: 500,
                store: store,
                columns: [{
                    text: "Topic",
                    dataIndex: 'title',
                    flex: 1
                }],
                renderTo: Ext.getBody()
            });
            var view = grid.getView(),
                scroller = view.getScrollable();

            store.load();

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(makeRows(350, 5000))
            });

            // Scroll until we've moved out of the initial rendered block
            jasmine.waitsForScroll(scroller, function() {
                if (view.all.startIndex > 0) {
                    return true;
                }

                scroller.scrollBy(0, 100);
            }, 'Initially rendered block to scroll out of view');

            runs(function() {

                store.reload();

                Ext.Ajax.mockComplete({
                    status: 200,
                    responseText: Ext.encode(makeRows(10, 10))
                });

                // It will have *tried* to reload the original
                expect(view.all.startIndex).toBe(0);
                expect(view.all.endIndex).toBe(9);
            });
        });
    });

    xdescribe("selected/focused/hover css classes", function() {
        beforeEach(createGrid);

        it("should add and remove the selected and before selected classes when the selection changes", function() {
            selModel.select(1);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            selModel.select(3);
            expect(view.getNode(1)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should add and remove the focused and before focused classes when the focus changes", function() {
            selModel.setLastFocused(store.getAt(1));
            expect(view.getNode(1)).toHaveCls(focusedItemCls);
            selModel.setLastFocused(store.getAt(3));
            expect(view.getNode(1)).not.toHaveCls(focusedItemCls);
            expect(view.getNode(3)).toHaveCls(focusedItemCls);

        });

        it("should add and remove the over and before over classes when the hover state changes", function() {
            view.setHighlightedItem(view.getNode(1));
            expect(view.getNode(1)).toHaveCls(overItemCls);
            view.setHighlightedItem(view.getNode(3));
            expect(view.getNode(1)).not.toHaveCls(overItemCls);
            expect(view.getNode(3)).toHaveCls(overItemCls);
        });

        it("should add and remove the before selected class to the table element when the first row is selected and unselected", function() {
            selModel.select(0);
            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            selModel.select(2);
        });

        it("should add and remove the before focused class to the table element when the first row is focused and unfocused", function() {
            selModel.setLastFocused(store.getAt(0));
            expect(view.getNode(0)).toHaveCls(focusedItemCls);
            selModel.setLastFocused(store.getAt(1));
        });

        it("should add and remove the before over class to the table element when the first row is focused and unfocused", function() {
            view.setHighlightedItem(view.getNode(0));
            expect(view.getNode(0)).toHaveCls(overItemCls);
            view.setHighlightedItem(view.getNode(1));
        });

        it("should restore selected classes when the view is refreshed", function() {
            selModel.select(1);
            view.refresh();
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
        });

        it("should restore selected classes when the view is refreshed (first row)", function() {
            selModel.select(0);
            view.refresh();
            expect(view.getNode(0)).toHaveCls(selectedItemCls);
        });

        it("should restore focused classes when the view is refreshed", function() {
            selModel.setLastFocused(store.getAt(1));
            view.refresh();
            expect(view.getNode(1)).toHaveCls(focusedItemCls);
        });

        it("should restore focused classes when the view is refreshed (first row)", function() {
            selModel.setLastFocused(store.getAt(0));
            view.refresh();
            expect(view.getNode(0)).toHaveCls(focusedItemCls);
        });

        it("should update the before selected class when a row is added before the selected row", function() {
            selModel.select(1);
            store.insert(1, { name: 'Phil', email: 'phil.guerrant@sencha.com', phone: '1-800-SENCHA' });
            expect(view.getNode(2)).toHaveCls(selectedItemCls);
        });

        it("should update the before selected class when a row is removed before the selected row", function() {
            selModel.select(2);
            store.removeAt(1);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
        });

        it("should update the before focused class when a row is added before the focused row", function() {
            selModel.setLastFocused(store.getAt(1));
            store.insert(1, { name: 'Phil', email: 'phil.guerrant@sencha.com', phone: '1-800-SENCHA' });
            expect(view.getNode(2)).toHaveCls(focusedItemCls);
        });

        it("should update the before focused class when a row is removed before the focused row", function() {
            selModel.select(2);
            store.removeAt(1);
            expect(view.getNode(1)).toHaveCls(focusedItemCls);
        });
    });

    xdescribe("selected/focused/hover css classes - through events", function() {
        beforeEach(createGrid);

        it("should add and remove the selected and before selected classes when the selection changes", function() {
            triggerCellMouseEvent('click', 1, 0);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            triggerCellMouseEvent('click', 3, 0);
            expect(view.getNode(1)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should add and remove the selected and before selected classes when the selection changes when using the contextmenu event", function() {
            triggerCellMouseEvent('contextmenu', 1, 0);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            triggerCellMouseEvent('contextmenu', 3, 0);
            expect(view.getNode(1)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should add and remove the focused and before focused classes when the focus changes", function() {
            // Move upwards from row 2 to row 1
            triggerCellKeyEvent(2, 0, 'keydown', Ext.event.Event.UP);

            expect(view.getNode(1)).toHaveCls(focusedItemCls);

            // Move downwards from row 1 to row 3
            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.DOWN);
            triggerCellKeyEvent(2, 0, 'keydown', Ext.event.Event.DOWN);

            expect(view.getNode(1)).not.toHaveCls(focusedItemCls);
            expect(view.getNode(3)).toHaveCls(focusedItemCls);
        });

        it("should add and remove the over and before over classes when the hover state changes", function() {
            triggerCellMouseEvent('mouseover', 1, 0);
            expect(view.getNode(1)).toHaveCls(overItemCls);

            triggerCellMouseEvent('mouseout', 1, 0);
            triggerCellMouseEvent('mouseover', 3, 0);
            expect(view.getNode(1)).not.toHaveCls(overItemCls);
            expect(view.getNode(3)).toHaveCls(overItemCls);

        });

        it("should add and remove the before selected class to the table element when the first row is selected and unselected", function() {
            triggerCellMouseEvent('click', 0, 0);
            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            triggerCellMouseEvent('click', 2, 0);
        });

        it("should add and remove the before focused class to the table element when the first row is focused and unfocused", function() {

            // Move upwards from row 1 to row 0
            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.UP);
            expect(view.getNode(0)).toHaveCls(focusedItemCls);

            // Move downwards from row 0 to row 1
            triggerCellKeyEvent(0, 0, 'keydown', Ext.event.Event.DOWN);
            selModel.setLastFocused(store.getAt(1));
        });

        it("should add and remove the before over class to the table element when the first row is mouseovered and mouseouted", function() {
            triggerCellMouseEvent('mouseover', 0, 0);
            expect(view.getNode(0)).toHaveCls(overItemCls);

            triggerCellMouseEvent('mouseout', 0, 0);
            triggerCellMouseEvent('mouseover', 1, 0);
            view.setHighlightedItem(view.getNode(1));
        });
    });

    xdescribe("selected/focused/hover css classes - through events. With buffered store", function() {
        beforeEach(function() {
            createGrid({
                buffered: true,
                asynchronousLoad: false,
                pageSize: 4
            });
        });

        it("should add and remove the selected and before selected classes when the selection changes", function() {
            triggerCellMouseEvent('click', 1, 0);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            triggerCellMouseEvent('click', 3, 0);
            expect(view.getNode(1)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should add and remove the focused and before focused classes when the focus changes", function() {
            // Move upwards from row 2 to row 1
            triggerCellKeyEvent(2, 0, 'keydown', Ext.event.Event.UP);

            expect(view.getNode(1)).toHaveCls(focusedItemCls);

            // Move downwards from row 1 to row 3
            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.DOWN);
            triggerCellKeyEvent(2, 0, 'keydown', Ext.event.Event.DOWN);

            expect(view.getNode(1)).not.toHaveCls(focusedItemCls);
            expect(view.getNode(3)).toHaveCls(focusedItemCls);
        });

        it("should add and remove the over and before over classes when the hover state changes", function() {
            triggerCellMouseEvent('mouseover', 1, 0);
            expect(view.getNode(1)).toHaveCls(overItemCls);

            triggerCellMouseEvent('mouseout', 1, 0);
            triggerCellMouseEvent('mouseover', 3, 0);
            expect(view.getNode(1)).not.toHaveCls(overItemCls);
            expect(view.getNode(3)).toHaveCls(overItemCls);

        });

        it("should add and remove the before selected class to the table element when the first row is selected and unselected", function() {
//             var tableEl = view.el.down('table.x-grid-table');
            triggerCellMouseEvent('click', 0, 0);
            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            triggerCellMouseEvent('click', 2, 0);
        });

        it("should add and remove the before focused class to the table element when the first row is focused and unfocused", function() {
//             var tableEl = view.el.down('table.x-grid-table');

            // Move upwards from row 1 to row 0
            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.UP);
            expect(view.getNode(0)).toHaveCls(focusedItemCls);

            // Move downwards from row 0 to row 1
            triggerCellKeyEvent(0, 0, 'keydown', Ext.event.Event.DOWN);
            selModel.setLastFocused(store.getAt(1));
        });

        it("should add and remove the before over class to the table element when the first row is mouseovered and mouseouted", function() {
//             var tableEl = view.el.down('table.x-grid-table');
            triggerCellMouseEvent('mouseover', 0, 0);
            expect(view.getNode(0)).toHaveCls(overItemCls);

            triggerCellMouseEvent('mouseout', 0, 0);
            triggerCellMouseEvent('mouseover', 1, 0);
            view.setHighlightedItem(view.getNode(1));
        });
    });

    xdescribe("selected row css classes with multi-select", function() {
        beforeEach(function() {
            createGrid(null, {
                selModel: {
                    selType: 'rowmodel',
                    mode: 'MULTI'
                }
            });
        });

        it("should update the selected classes when rows before the selections are removed", function() {
//             var tableEl = view.el.down('table.x-grid-table');

            selModel.select([store.getAt(1), store.getAt(3)]);
            store.remove([0, 2]);
            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
        });

        it("should update the selected classes when selected rows are removed", function() {
            selModel.select([store.getAt(1), store.getAt(3)]);
            store.remove([1, 3]);
            expect(view.getNode(0)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(1)).not.toHaveCls(selectedItemCls);
        });
    });

    describe("selected/focused/hover css classes with grouping", function() {
        var groupingFeature;

        function makeGrid(cfg) {
            grid = Ext.widget({
                renderTo: Ext.getBody(),
                xtype: 'grid',
                id: 'grid',
                title: 'Employees',
                margin: '0 10 0 0',
                store: Ext.create('Ext.data.Store', {
                    fields: ['name', 'seniority'],
                    groupField: 'seniority',
                    data: { 'employees': [
                        { "name": "Michael Scott",  "seniority": "7" },
                        { "name": "Dwight Schrute", "seniority": "7" },
                        { "name": "Jim Halpert",    "seniority": "3" },
                        { "name": "Kevin Malone",   "seniority": "3" },
                        { "name": "Angela Martin",  "seniority": "3" }
                    ] },
                    proxy: {
                        type: 'memory',
                        reader: {
                            type: 'json',
                            rootProperty: 'employees'
                        }
                    }
                }),
                columns: [
                    { text: 'Name',     dataIndex: 'name' },
                    { text: 'Seniority', dataIndex: 'seniority' }
                ],
                features: [{ ftype: 'grouping' }],
                selModel: {
                    selType: 'rowmodel',
                    mode: 'MULTI'
                },
                width: 400,
                height: 275
            });

            store = grid.store;
            view = grid.view;
            selModel = grid.selModel;
            groupingFeature = view.findFeature('grouping');
        }

        beforeEach(function() {
            makeGrid();
        });

        afterEach(function() {
            grid.destroy();
            grid = null;
        });

        it("should preserve the selected classes when the view is refreshed", function() {
            selModel.select([store.getAt(0), store.getAt(1), store.getAt(3)]);
            view.refresh();

            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should preserve the selected classes when grouping is disabled", function() {
            selModel.select([store.getAt(0), store.getAt(1), store.getAt(3)]);

            groupingFeature.disable();

            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should preserve the selected classes when grouping is enabled", function() {
            selModel.select([store.getAt(0), store.getAt(1), store.getAt(3)]);

            groupingFeature.disable();
            groupingFeature.enable();

            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
            expect(view.getNode(3)).toHaveCls(selectedItemCls);
        });

        it("should remove the selected classes when selected rows are removed (first in group)", function() {
            selModel.select([store.getAt(0), store.getAt(3)]);
            store.remove([0, 3]);

            expect(view.getNode(0)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(2)).not.toHaveCls(selectedItemCls);
        });

        it("should remove the selected classes when selected rows are removed (not first in group)", function() {
            selModel.select([store.getAt(1), store.getAt(4)]);
            store.remove([1, 4]);

            expect(view.getNode(1)).not.toHaveCls(selectedItemCls);
            expect(view.getNode(2)).not.toHaveCls(selectedItemCls);
        });

        it("should update the selected classes when rows before selected rows are removed", function() {
            selModel.select([store.getAt(1), store.getAt(3)]);
            store.remove([0, 2]);

            expect(view.getNode(0)).toHaveCls(selectedItemCls);
            expect(view.getNode(1)).toHaveCls(selectedItemCls);
        });

        // TODO: add tests for focus/hover classes for grouped grid.
    });

    xdescribe('RowExpander plugin', function() {
        function createRowExpanderGrid(cfg) {
            createGrid(null, Ext.apply({
                plugins: [{
                    ptype: 'rowexpander',
                    rowBodyTpl: new Ext.XTemplate(
                        'Expanded data for {name} Simpson'
                    )
                }]
            }, cfg));
        }

        it('should start collapsed, then expand to reveal expander row', function() {
            createRowExpanderGrid();

            var firstRow = grid.view.all.item(0),
                expanderTarget = Ext.fly(firstRow).down('.' + Ext.baseCSSPrefix + 'grid-row-expander', true),
                expanderRow = Ext.fly(firstRow).down(Ext.grid.plugin.RowExpander.prototype.rowBodyTrSelector, true),
                expanderData = Ext.fly(expanderRow).down('.x-grid-rowbody', true);

            // Rows begin collapsed
            expect(firstRow).toHaveCls(Ext.grid.plugin.RowExpander.prototype.rowCollapsedCls);

            // Expander row starts hidden
            expect(expanderRow.isVisible()).toBe(false);

            // "click" the expander
            jasmine.fireMouseEvent(expanderTarget, 'click');

            // Expander row should now be visible
            expect(expanderRow.isVisible()).toBe(true);

            // First row is now expanded
            expect(firstRow).not.toHaveCls(Ext.grid.plugin.RowExpander.prototype.rowCollapsedCls);

            // Check data is as expected
            expect(expanderData.firstChild.data).toBe("Expanded data for Lisa Simpson");
        });

        it('should insert checkbox at column 1 when using a CheckboxSelectionModel', function() {
            createGrid(null, {
                selModel: new Ext.selection.CheckboxModel(),
                plugins: [{
                    ptype: 'rowexpander',
                    rowBodyTpl: new Ext.XTemplate(
                        'Expanded data for {name} Simpson'
                    )
                }]
            });

            // THere chould be an extra column for the checkbox (in addition to the 2 visible and 1 hidden data columns)
            expect(grid.getVisibleColumnManager().getColumns().length).toBe(4);

            // The CheckboxSelectionModel's column should be at position 1
            expect(grid.getVisibleColumnManager().getColumns()[1].el).toHaveCls(Ext.baseCSSPrefix + 'column-header-checkbox');
        });

        it("should add/remove highlighted CSS classes to rows", function() {
            var rows;

            createRowExpanderGrid({
                viewConfig: {
                    mouseOverOutBuffer: false
                }
            });

            rows = view.all.elements;

            triggerCellMouseEvent('mouseover', 2, 1);

            expect(Ext.fly(rows[2])).toHaveCls('x-grid-item-over');

            triggerCellMouseEvent('mouseout', 2, 1);

            expect(Ext.fly(rows[2])).not.toHaveCls('x-grid-item-over');
        });
    });

    xit('should work with a grid subclass which adds the plugin at initComponent time', function() {
        var testStore = Ext.create('Ext.data.Store', {
            fields: ['name', 'email', 'phone'],
            data: [
                { 'name': 'Lisa',  "email": "lisa@simpsons.com",  "phone": "555-111-1224"  },
                { 'name': 'Bart',  "email": "bart@simpsons.com",  "phone": "555-222-1234"  },
                { 'name': 'Homer', "email": "homer@simpsons.com", "phone": "555-222-1244"  },
                { 'name': 'Marge', "email": "marge@simpsons.com", "phone": "555-222-1254"  }
            ]
        });

        Ext.define('TestRowExpanderGrid', {
            extend: 'Ext.grid.Panel',
            title: 'Simpsons',
            height: 200,
            width: 400,
            initComponent: function() {
                this.store = testStore;

                this.columns = [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', dataIndex: 'phone', flex: 1 }
                ];

                this.enableLocking = true;

                this.plugins = {
                    ptype: 'rowexpander',
                    rowBodyTpl: new Ext.XTemplate(
                        'Expanded data for {name} Simpson'
                    )
                };

                this.callParent(arguments);
            }
        });

        // eslint-disable-next-line no-undef
        grid = new TestRowExpanderGrid({
            renderTo: Ext.getBody()
        });

        waits(1);

        runs(function() {
            var allColumns = grid.getVisibleColumnManager().getColumns(),
                lockedColumns = grid.lockedGrid.getVisibleColumnManager().getColumns();

            // There will be an extra expander column
            expect(allColumns.length).toBe(4);

            // The expander column will be the only locked column
            expect(lockedColumns.length).toBe(1);

            // The innerCls for the data cell is "x-grid-cell-inner-row-expander"
            // See Ext.grid.plugins.RowExpander::getHeaderConfig
            expect(lockedColumns[0].innerCls).toBe("x-grid-cell-inner-row-expander");

            // There will be a lockedGrid with one column which is the expander column
            expect(lockedColumns[0] === allColumns[0]).toBe(true);

            Ext.undefine('TestRowExpanderGrid');
        });
    });

    // https://sencha.jira.com/browse/EXTJSIV-11863
    xdescribe('enableLocking:true with no locked columns and buffered store', function() {
        it('should hide the locked grid when there are no locked columns, and not refresh it', function() {
            createGrid({
                buffered: true,
                asynchronousLoad: false,
                pageSize: 4
            }, {
                enableLocking: true
            });

            // Locked side is hidden and does not receive a refresh
            expect(grid.lockedGrid.view.refreshCounter).toBeFalsy();

            // Normal side should have been refreshed with the initial data payload that was in the store at first layout time.
            expect(grid.normalGrid.view.refreshCounter).toBe(1);
        });
    });

    xdescribe('rowValues', function() {
        // If there are more than one component with a view rendered onto a page, the neither view's rowValues
        // values should be available in the other's tableview rowTpl. This can happen when there's a tree and a
        // grid, and the grid will defer its initial view refresh.
        //
        // In the example below, the tree view will render its last row after the grid has been initialized, but
        // because the grid view was deferred the tree view pokes its values into rowAttr which is then wrongly
        // rendered by the grid view. See EXTJSIV-9341.
        it('should not render rowAttr information from another view', function() {
            var tree = new Ext.tree.Panel({
                width: 400,
                height: 200,
                renderTo: Ext.getBody(),
                root: {
                    text: 'Foo',
                    expanded: true,
                    children: [{
                        text: 'A',
                        leaf: true
                    }, {
                        text: 'B',
                        leaf: true
                    }, {
                        text: 'C',
                        leaf: true
                    }]
                }
            });

            createGrid({}, {
            });

            // The set attributes should only be rendered to the treeview.
            tree.getRootNode().childNodes[1].set({
                qtip: 'Hello from',
                qtitle: 'Redwood City!'
            });

            waits(1);

            runs(function() {
                var node = grid.view.getNode(0);

                // The gridview should not have rendered any tree attributes in its rowTpl.
                expect(node.getAttribute('data-qtip')).toBe(null);
                expect(node.getAttribute('data-qtitle')).toBe(null);

                tree.destroy();
                tree = null;
            });
        });
    });

    describe('disableSelection', function() {
        function doDisableSelectionTest(disableSelection, createInstance) {
            var rowModel = createInstance ? new Ext.selection.Model() : 'rowmodel';

            afterEach(function() {
                rowModel = null;
            });

            describe('when disableSelection = ' + disableSelection + ', config.selModel.isSelectionModel = ' + !!createInstance, function() {
                it('should work when defined on the grid', function() {
                    createGrid(null, {
                        selModel: rowModel,
                        disableSelection: disableSelection
                    });

                    triggerCellMouseEvent('click', 0, 0);
                    expect(!!grid.selModel.getSelection().length).toBe(!disableSelection);
                });

                it('should work when defined on the view', function() {
                    createGrid(null, {
                        selModel: rowModel,
                        viewConfig: {
                            disableSelection: disableSelection
                        }
                    });

                    triggerCellMouseEvent('click', 0, 0);
                    expect(!!grid.selModel.getSelection().length).toBe(!disableSelection);
                });
            });
        }

        doDisableSelectionTest(false, false);
        doDisableSelectionTest(true, false);
        doDisableSelectionTest(true, true);
        doDisableSelectionTest(false, true);
    });

    describe('autoLoad config', function() {
        var bindStore;

        beforeEach(function() {
            createGrid(null, {
                autoLoad: true,
                bind: '{foo}',
                viewModel: {
                    stores: {
                        foo: {
                            storeId: 'Utley',
                            proxy: {
                                type: 'memory',
                                useModelWarning: false
                            }
                        }
                    }
                }
            });

            bindStore = Ext.StoreMgr.get('Utley');
        });

        it('should work with a VM store binding', function() {
            waitsFor(function() {
                return grid.store === bindStore;
            });

            runs(function() {
                expect(grid.store).toBe(bindStore);
            });
        });

        it('should load the bound store', function() {
            waitsFor(function() {
                return grid.store === bindStore;
            });

            runs(function() {
                store = grid.store;
                expect(store.loading || store.isLoaded()).toBe(true);
            });
        });
    });

    describe('grid destruction of contained grid', function() {
        it('should not throw an error', function() {
            var p = new Ext.panel.Panel({
                width: 500,
                height: 260,
                renderTo: document.body,
                layout: 'fit',
                items: [{
                    xtype: 'grid',
                    columns: [{
                        locked: true,
                        text: 'col1',
                        dataIndex: 'col1',
                        width: 150,
                        variableRowHeight: true
                    }, {
                        text: 'col2',
                        dataIndex: 'col2',
                        width: 300
                    }, {
                        text: 'col3',
                        dataIndex: 'col3',
                        width: 300
                    }],
                    store: {
                        proxy: {
                            type: 'memory'
                        },
                        fields: ['id', 'group', 'col1', 'col2', 'col3'],
                        data: [{
                            id: 1,
                            group: 'group1',
                            col1: 'fdsfds',
                            col2: 'zeeazepze',
                            col3: 'pokopkpok'
                        }, {
                            id: 2,
                            group: 'group1',
                            col1: 'fdsfds',
                            col2: 'zeeazepze',
                            col3: 'pokopkpok'
                        }, {
                            id: 3,
                            group: 'group2',
                            col1: 'fdsfds',
                            col2: 'zeeazepze',
                            col3: 'pokopkpok'
                        }, {
                            id: 4,
                            group: 'group2',
                            col1: 'fdsfds',
                            col2: 'zeeazepze',
                            col3: 'pokopkpok'
                        }, {
                            id: 5,
                            group: 'group2',
                            col1: 'fdsfds',
                            col2: 'zeeazepze',
                            col3: 'pokopkpok'
                        }],
                        grouper: {
                            property: 'group',
                            direction: 'ASC'
                        }
                    },
                    enableLocking: true,
                    syncRowHeight: true
                }]
            }),
            grid = p.down('grid'),
            store = grid.getStore();

            store.getById(3).set('col1', 'AZERTY');
            grid.destroy();
            p.destroy();
        });
    });
});

