topSuite("Ext.grid.filters.filter.Boolean",
    ['Ext.grid.Panel', 'Ext.grid.filters.Filters'],
function() {
    var wasCalled = false,
        menu, grid, store;

    function createGrid(storeCfg, gridCfg) {
        store = new Ext.data.Store(Ext.apply({
            storeId: 'simpsonsStore',
            fields: ['name', 'email', 'phone', 'adult'],
            data: [
                { 'name': 'Lisa', 'email': 'lisa@simpsons.com', 'phone': '555-111-1224', 'adult': false },
                { 'name': 'Bart', 'email': 'bart@simpsons.com', 'phone': '555-222-1234', 'adult': false },
                { 'name': 'Homer', 'email': 'homer@simpsons.com', 'phone': '555-222-1244', 'adult': true },
                { 'name': 'Marge', 'email': 'marge@simpsons.com', 'phone': '555-222-1254', 'adult': true }
            ]
        }, storeCfg));

        grid = new Ext.grid.Panel(Ext.apply({
            title: 'Simpsons',
            store: store,
            autoLoad: true,
            columns: [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', width: 100 },
                { header: 'Phone', dataIndex: 'phone', width: 100 },
                { header: 'Adult', dataIndex: 'adult', width: 100 }
            ],

            // We need programmatic mouseover events to be handled inline so we can test effects.
            viewConfig: {
                mouseOverOutBuffer: false,
                deferHighlight: false
            },
            plugins: [{
                ptype: 'gridfilters'
            }],
            height: 200,
            width: 400,
            renderTo: Ext.getBody()
        }, gridCfg));
    }

    function showFilterMenu() {
        var headerCt = grid.headerCt,
            filtersCheckItem,
            header = grid.columnManager.getLast();

        // Show the grid menu.
        headerCt.showMenuBy(null, header.triggerEl.dom, header);

        // Show the filter menu.
        filtersCheckItem = headerCt.menu.items.last();
        filtersCheckItem.activated = true;
        filtersCheckItem.expandMenu(null, 0);

        menu = filtersCheckItem.menu;
    }

    afterEach(function() {
        Ext.destroy(store, grid);
        grid = store = null;
        wasCalled = false;
    });

    describe("initializing", function() {
        describe("setting as active", function() {
            describe("defined value", function() {
                it("should set as active when value is `true`", function() {
                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100 },
                            { header: 'Adult', dataIndex: 'adult',
                                filter: {
                                    type: 'boolean',
                                    value: true
                                },
                            width: 100 }
                        ]
                    });

                    expect(grid.columnManager.getHeaderByDataIndex('adult').filter.active).toBe(true);
                });

                it("should not have a default value if defaultValue is null", function() {
                    var menu, items;

                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100 },
                            { header: 'Adult', dataIndex: 'adult',
                                filter: {
                                    type: 'boolean',
                                    defaultValue: null
                                },
                            width: 100 }
                        ]
                    });

                    showFilterMenu();
                    menu = grid.columnManager.getHeaderByDataIndex('adult').filter.menu;
                    items = menu.items.getRange();

                    expect(items[0].checked).toBe(false);
                    expect(items[1].checked).toBe(false);
                });

                it("should set as active when value is `false`", function() {
                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100 },
                            { header: 'Adult', dataIndex: 'adult',
                                filter: {
                                    type: 'boolean',
                                    value: false
                                },
                            width: 100 }
                        ]
                    });

                    expect(grid.columnManager.getHeaderByDataIndex('adult').filter.active).toBe(true);
                });

                it("should set as active when value is `null`", function() {
                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100 },
                            { header: 'Adult', dataIndex: 'adult',
                                filter: {
                                    type: 'boolean',
                                    value: null
                                },
                            width: 100 }
                        ]
                    });

                    expect(grid.columnManager.getHeaderByDataIndex('adult').filter.active).toBe(true);
                });
            });

            describe("undefined value", function() {
                it("should not set as active when value is omitted", function() {
                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100 },
                            { header: 'Adult', dataIndex: 'adult',
                                filter: {
                                    type: 'boolean'
                                },
                            width: 100 }
                        ]
                    });

                    expect(grid.columnManager.getHeaderByDataIndex('adult').filter.active).toBe(false);
                });

                it("should not set as active when value is `undefined`", function() {
                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100 },
                            { header: 'Adult', dataIndex: 'adult',
                                filter: {
                                    type: 'boolean',
                                    value: undefined
                                },
                            width: 100 }
                        ]
                    });

                    expect(grid.columnManager.getHeaderByDataIndex('adult').filter.active).toBe(false);
                });
            });
        });
    });

    describe("activating", function() {
        it("should filter false by default", function() {
            var filter;

            createGrid(null, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Adult', dataIndex: 'adult',
                        filter: {
                            type: 'boolean',
                            value: undefined
                        },
                    width: 100 }
                ]
            });

            filter = grid.columnManager.getHeaderByDataIndex('adult').filter;

            filter.activate();

            expect(store.getRange().length).toBe(2);
        });
    });
});
