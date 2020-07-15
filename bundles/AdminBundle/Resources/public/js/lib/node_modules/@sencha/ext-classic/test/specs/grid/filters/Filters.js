// TODO: Add specs for locked grid and removing stores from other parts of the app.
// TODO: Add specs for making sure that new filters replace existing filters with same dataIndex.
// TODO: Add specs for addFilter(), making sure that only one filter store is ever created per dataIndex.
topSuite("Ext.grid.filters.Filters",
    ['Ext.grid.Panel', 'Ext.tree.Panel'],
function() {
    var synchronousLoad = false,
        grid, tree, store, filtersPlugin, filter, data;

    function completeWithData(theData) {
        Ext.Ajax.mockComplete({
            status: 200,
            responseText: Ext.encode(theData || data)
        });
    }

    function getFilters() {
        return Ext.Ajax.mockGetRequestXHR().options.operation.getFilters();
    }

    function createGrid(storeCfg, gridCfg) {
        // For the duration of this function, we do NOT want automatic flushing of loads.
        synchronousLoad = false;

        // We want the store to behave with remote semantics, ie: flush loads on a timer.
        store = new Ext.data.Store(Ext.apply({
            asynchronousLoad: true,
            autoDestroy: true,
            fields: ['name', 'email', 'phone', 'age', 'dob'],
            data: data
        }, storeCfg));

        Ext.override(store, {
            load: function() {
                this.callParent(arguments);

                if (synchronousLoad) {
                    this.flushLoad.apply(this, arguments);
                }

                return this;
            },

            flushLoad: function() {
                if (!this.destroyed) {
                    this.flushCallCount = (this.flushCallCount || 0) + 1;
                    this.callParent();
                }
            }
        });

        // Note: lower the updateBuffer (defaults to 500ms) which is what determines the delay between onStateChange
        // being called and reload, which removes/adds store filters and sends a request for remote filtering.
        filtersPlugin = new Ext.grid.filters.Filters({
            updateBuffer: 0
        });

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', width: 100 },
                { header: 'Phone', dataIndex: 'phone', width: 100 },
                { header: 'Age', dataIndex: 'age', width: 100 },
                { header: 'DOB', dataIndex: 'dob', width: 100, type: 'date', dateFormat: 'm/d/Y' }
            ],
            autoLoad: true,
            plugins: filtersPlugin,
            deferRowRender: false,
            // We need programmatic mouseover events to be handled inline so we can test effects.
            viewConfig: {
                mouseOverOutBuffer: false,
                deferHighlight: false
            },
            height: 200,
            width: 500,
            renderTo: Ext.getBody()
        }, gridCfg));

        synchronousLoad = true;

        if (store.hasPendingLoad()) {
            store.flushLoad();
        }
    }

    function createTree(storeCfg, treeCfg) {
        store = new Ext.data.TreeStore(Ext.apply({
            root: {
                name: 'root',
                descr: 'root',
                expanded: true,
                children: [{
                    name: 'Test 1',
                    description: 'My first text',
                    leaf: true
                }, {
                    name: 'Test 2',
                    description: 'The second text',
                    leaf: true
                }, {
                    name: 'Test 3',
                    description: 'The third text',
                    leaf: true
                }]
            }
        }, storeCfg));

        Ext.override(store, {
            load: function() {
                this.callParent(arguments);

                if (synchronousLoad) {
                    this.flushLoad.apply(this, arguments);
                }

                return this;
            },

            flushLoad: function() {
                if (!this.destroyed) {
                    this.flushCallCount = (this.flushCallCount || 0) + 1;
                    this.callParent();
                }
            }
        });

        tree = new Ext.tree.Panel(Ext.apply({
            columns: [{
                header: 'Name',
                dataIndex: 'name',
                filter: {
                    type: 'string'
                }
            }, {
                header: 'Description',
                dataIndex: 'description',
                filter: {
                    type: 'string'
                }
            }],
            store: store,
            plugins: 'gridfilters',
            rootVisible: false,
            renderTo: Ext.getBody()
        }, treeCfg));
    }

    beforeEach(function() {
        MockAjaxManager.addMethods();
        data = [
            { name: 'Jimmy Page', email: 'jimmy@page.com', phone: '555-111-1224', age: 69, dob: new Date('1/22/1944') },
            { name: 'Stevie Ray Vaughan', email: 'stevieray@vaughan.com', phone: '555-222-1234', age: 35, dob: new Date('1/22/1955') },
            { name: 'John Scofield', email: 'john@scofield.com', phone: '555-222-1234', age: 59, dob: new Date('1/22/1954') },
            { name: 'Robben Ford', email: 'robben@ford.com', phone: '555-222-1244', age: 60, dob: new Date('1/22/1953') },
            { name: 'Wes Montgomery', email: 'wes@montgomery.com', phone: '555-222-1244', age: 45, dob: new Date('1/22/1923') },
            { name: 'Jimmy Herring', email: 'jimmy@herring.com', phone: '555-222-1254', age: 50, dob: new Date('1/22/1962') },
            { name: 'Alex Lifeson', email: 'alex@lifeson.com', phone: '555-222-1254', age: 60, dob: new Date('1/22/1953') },
            { name: 'Kenny Burrell', email: 'kenny@burrell.com', phone: '555-222-1254', age: 82, dob: new Date('1/22/1930') }
        ];
    });

    afterEach(function() {
        MockAjaxManager.removeMethods();
        grid = tree = filtersPlugin = filter = Ext.destroy(grid, tree);
        store = Ext.destroy(store);
    });

    describe("initializing", function() {
        it("should set 'local' to be true", function() {
            createGrid();
            expect(filtersPlugin.local).toBe(true);
        });

        it("should create a filter when the data index does not map to an actual column", function() {
            createGrid({}, {
                columns: [{
                    dataIndex: 'bogus',
                    filter: true
                }]
            });

            expect(grid.columnManager.getHeaderByDataIndex('bogus').filter).toBeDefined();
        });

        describe("the store", function() {
            beforeEach(function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100, filter: true },
                        { header: 'Email',  dataIndex: 'email', width: 100,
                            filter: {
                                value: 'stevie'
                            }
                        }
                    ]
                });
            });

            it("should bind the store to the feature", function() {
                expect(filtersPlugin.store).toBeDefined();
                expect(filtersPlugin.store).toBe(store);
            });

            it("should be a pointer to the grid store", function() {
                expect(filtersPlugin.store).toBe(filtersPlugin.grid.store);
            });

            it("should create a store filter on creation for each active filter (has a 'value' property)", function() {
                // There are two column filters and one store filter was created.
                expect(store.getFilters().getCount()).toBe(1);
            });

            it("should create a store filter id for each active filter", function() {
                expect(store.getFilters().getAt(0).getId()).toBe('x-gridfilter-email');
            });
        });

        describe("filter creation", function() {
            it("should be the type it was configured with", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'list'
                            }
                        },
                        { header: 'DOB', dataIndex: 'dob', width: 100,
                            filter: {
                                type: 'date'
                            }
                        }
                    ]
                });

                expect(grid.columnManager.getHeaderByDataIndex('dob').filter.type).toBe('date');
            });

            it("should be inactive if not filtered (no 'value' property)", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100, filter: true }
                    ]
                });

                expect(grid.columnManager.getHeaderByDataIndex('name').filter.active).toBe(false);
            });

            it("should be active if filtered (has a 'value' property)", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                value: 'kenny'
                            }
                        }
                    ]
                });

                expect(grid.columnManager.getHeaderByDataIndex('name').filter.active).toBe(true);
            });

            describe("when filter = true", function() {
                var colMgr;

                beforeEach(function() {
                    createGrid({}, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', filter: true },
                            { header: 'DOB',  dataIndex: 'dob', filter: true }
                        ]
                    });

                    colMgr = grid.columnManager;
                });

                afterEach(function() {
                    colMgr = null;
                });

                it("should create an inactive filter", function() {
                    expect(colMgr.getHeaderByDataIndex('dob').filter.active).toBe(false);
                });

                // TODO: Update the specs to show that a filter type can be gleaned from the data field.
                it("should create a default String filter type", function() {
                    expect(colMgr.getHeaderByDataIndex('dob').filter.type).toBe('string');
                });
            });
        });
    });

    describe("events", function() {
        var activateSpy, deactivateSpy;

        beforeEach(function() {
            activateSpy = jasmine.createSpy('filteractivate');
            deactivateSpy = jasmine.createSpy('filterdeactivate');

            createGrid(null, {
                columns: [{
                    dataIndex: 'name',
                    filter: {
                        type: 'string'
                    }
                }],
                listeners: {
                    filteractivate: activateSpy,
                    filterdeactivate: deactivateSpy
                }
            });

            filter = grid.columnManager.getHeaderByDataIndex('name').filter;
        });

        afterEach(function() {
            activateSpy = deactivateSpy = null;
        });

        describe("activate", function() {
            beforeEach(function() {
                filter.setValue('Jimmy');
            });

            it("should fire when filter is activated programmatically", function() {
                expect(activateSpy).toHaveBeenCalled();
            });

            it("should pass filter and column", function() {
                var args = Ext.Array.slice(activateSpy.mostRecentCall.args, 0, 2);

                expect(args).toEqual([filter, filter.column]);
            });

            it("should not fire deactivate event", function() {
                expect(deactivateSpy).not.toHaveBeenCalled();
            });
        });

        describe("deactivate", function() {
            beforeEach(function() {
                filter.setValue('Jimmy');
                grid.clearFilters();
            });

            it("should fire when filter is cleared programmatically", function() {
                expect(deactivateSpy).toHaveBeenCalled();
            });

            it("should pass filter and column", function() {
                var args = Ext.Array.slice(deactivateSpy.mostRecentCall.args, 0, 2);

                expect(args).toEqual([filter, filter.column]);
            });
        });
    });

    describe("column menu influence", function() {
        var cols;

        afterEach(function() {
            cols = null;
        });

        it("should set requiresMenu: true on column when column is not configured with menuDisabled: true", function() {
            createGrid(null, {
                columns: [{
                    dataIndex: 'dob',
                    menuDisabled: true,
                    filter: {
                        type: 'date'
                    }
                }, {
                    dataIndex: 'phone',
                    menuDisabled: false,
                    filter: {
                        type: 'string'
                    }
                }]
            });

            cols = grid.getColumnManager().getColumns();

            expect(cols[0].requiresMenu).toBeFalsy();
            expect(cols[1].requiresMenu).toBe(true);
        });
    });

    describe("column cls decoration", function() {
        var filterCls = Ext.grid.filters.Filters.prototype.filterCls,
            cols;

        afterEach(function() {
            cols = null;
        });

        describe("works for both non-nested and nested columns", function() {
            it("should add the cls for columns configured with a value", function() {
                createGrid(null, {
                    columns: [{
                        dataIndex: 'name',
                        filter: {
                            value: 'Ford'
                        }
                    }, {
                        columns: [{
                            dataIndex: 'age',
                            filter: {
                                type: 'number',
                                value: {
                                    lt: 80
                                }
                            }
                        }]
                    }, {
                        dataIndex: 'dob',
                        filter: {
                            type: 'date'
                        }
                    }, {
                        dataIndex: 'phone',
                        filter: {
                            type: 'string'
                        }
                    }]
                });

                cols = grid.getColumnManager().getColumns();

                expect(cols[0].getEl()).toHaveCls(filterCls);
                expect(cols[1].getEl()).toHaveCls(filterCls);
                expect(cols[2].getEl()).not.toHaveCls(filterCls);
                expect(cols[3].getEl()).not.toHaveCls(filterCls);
            });

            it("should add the cls for columns when setting a value", function() {
                createGrid(null, {
                    columns: [{
                        dataIndex: 'name',
                        filter: true
                     }, {
                        columns: [{
                            dataIndex: 'age',
                            filter: {
                                type: 'number'
                            }
                        }]
                    }]
                });

                cols = grid.getColumnManager().getColumns();

                expect(cols[0].getEl()).not.toHaveCls(filterCls);
                cols[0].filter.setValue('Foo');
                expect(cols[0].getEl()).toHaveCls(filterCls);

                expect(cols[1].getEl()).not.toHaveCls(filterCls);
                cols[1].filter.setValue({ eq: 43 });
                expect(cols[1].getEl()).toHaveCls(filterCls);
            });

            it("should add the cls for columns with a value restored from state", function() {
                Ext.state.Manager.getProvider().clear();
                createGrid({
                    saveStatefulFilters: true
                }, {
                    stateful: true,
                    stateId: 'filtersCls',
                    columns: [{
                        dataIndex: 'name',
                        filter: {
                            type: 'string'
                        }
                    }, {
                        columns: [{
                            dataIndex: 'email',
                            filter: true
                        }]
                    }]
                });

                cols = grid.getColumnManager().getColumns();

                cols[0].filter.setValue('stevie ray');
                cols[1].filter.setValue('stevieray@vaughan.com');

                grid.saveState();
                grid.destroy();

                createGrid({
                    saveStatefulFilters: true
                }, {
                    stateful: true,
                    stateId: 'filtersCls',
                    columns: [{
                        dataIndex: 'name',
                        filter: {
                            type: 'string'
                        }
                    }, {
                        columns: [{
                            dataIndex: 'email',
                            filter: true
                        }]
                    }]
                });

                cols = grid.getColumnManager().getColumns();

                expect(cols[0].getEl()).toHaveCls(filterCls);
                expect(cols[1].getEl()).toHaveCls(filterCls);
            });

            it("should remove the cls for columns when clearing a value", function() {
                createGrid(null, {
                    columns: [{
                        dataIndex: 'name',
                        filter: {
                            value: 'x'
                        }
                    }, {
                        columns: [{
                            dataIndex: 'age',
                            filter: {
                                type: 'number',
                                value: {
                                    eq: 43
                                }
                            }
                        }]
                    }]
                });

                cols = grid.getColumnManager().getColumns();

                expect(cols[0].getEl()).toHaveCls(filterCls);
                cols[0].filter.setActive(false);
                expect(cols[0].getEl()).not.toHaveCls(filterCls);

                expect(cols[1].getEl()).toHaveCls(filterCls);
                cols[1].filter.setActive(false);
                expect(cols[1].getEl()).not.toHaveCls(filterCls);
            });
        });
    });

    describe("store filtering", function() {
        var columnFilter;

        afterEach(function() {
            columnFilter = null;
        });

        it("should not clear any filters added directly by the store when removing a feature filter", function() {
            var re = /scofield/,
                filters;

            createGrid({}, {
                columns: [{
                    dataIndex: 'name',
                    filter: {
                        value: 'lifeson'
                    }
                }]
            });

            filters = store.getFilters();
            columnFilter = grid.columnManager.getHeaderByDataIndex('name').filter;

            expect(filters.getCount()).toBe(1);
            expect(columnFilter.filter.getValue()).toBe('lifeson');

            // Now add a store filter that has the same property/dataIndex.
            store.addFilter({ property: 'name', value: re });

            expect(filters.getCount()).toBe(2);

            columnFilter.setActive(false);

            // Show that the filter on the store is still there.
            expect(filters.getCount()).toBe(1);
            expect(filters.getAt(0).getValue()).toBe(re);
        });

        describe("filtering the store", function() {
            function makeStoreFilterGrid(withFilter) {
                createGrid({}, {
                    columns: [{
                        dataIndex: 'name',
                        filter: withFilter ? { value: 'jimmy' } : undefined
                    }]
                });
            }

            it("should not throw an error when removing a non-header filter", function() {
                makeStoreFilterGrid(true);

                var f = new Ext.util.Filter({
                    property: 'age',
                    value: 60
                });

                var current = store.getCount();

                store.getFilters().add(f);
                expect(store.getCount()).toBe(0);

                expect(function() {
                    store.getFilters().remove(f);
                }).not.toThrow();
                expect(store.getCount()).toBe(current);
            });

            it("should not throw an error when removing a filter for a grid column that does not have a filter UI", function() {
                makeStoreFilterGrid(false);

                var f = new Ext.util.Filter({
                    property: 'name',
                    value: 'invalid'
                });

                var current = store.getCount();

                store.getFilters().add(f);
                expect(store.getCount()).toBe(0);

                expect(function() {
                    store.getFilters().remove(f);
                }).not.toThrow();
                expect(store.getCount()).toBe(current);
            });
        });
    });

    describe("autoLoad on gridpanel (defaults to true)", function() {
        describe("local filtering", function() {
            describe("initializing", function() {
                it("should keep local as `true`", function() {
                    createGrid({
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/autoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });
                    completeWithData();

                    expect(filtersPlugin.local).toBe(true);
                });
            });

            describe("if true", function() {
                it("should not make more than one request when filtering on an autoLoad store and autoLoad gridpanel", function() {
                    // Note that this is verifying that an old bug that sent out multiple requests isn't recurring.
                    // Configuring a filter with a value property will make a network request unless suppressed.
                    // Also, note that it ignores the store config in favor of the default panel config.
                    createGrid({
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/autoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    completeWithData();
                    expect(store.flushCallCount).toBe(1);
                });

                it("should not send filter data in the params for any active filter", function() {
                    createGrid({
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/autoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'robben'
                                }
                            },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    var filter = getFilters();

                    completeWithData();
                    expect(filter).not.toBeDefined();
                });

                it("should not send filter data in the params of any inactive filter", function() {
                    createGrid({
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/autoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    var filter = getFilters();

                    completeWithData();
                    expect(filter).not.toBeDefined();
                });
            });

            describe("if false on the grid store", function() {
                it("should still make a request if any filter has a 'value' property", function() {
                    // Note that this is verifying that the store config is in favor of the default panel config.
                    createGrid({
                        autoLoad: false,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/autoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'kenny'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    completeWithData();
                    expect(store.flushCallCount).toBe(1);
                });
            });
        });
    });

    describe("remote filtering", function() {
        describe("initializing", function() {
            it("should set 'local' to `false`", function() {
                createGrid({
                    remoteFilter: true
                }, {}, {
                    filters: [{
                        dataIndex: 'name'
                    }]
                });
                expect(filtersPlugin.local).toBe(false);
            });
        });

        describe("autoLoad", function() {
            describe("if true", function() {
                it("should not make more than one request when filtering on an autoLoad store and autoLoad gridpanel", function() {
                    // Note that it ignores the store config in favor of the default panel config.
                    createGrid({
                        remoteFilter: true,
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    waitsFor(function() {
                        return store.flushCallCount > 0;
                    });

                    runs(function() {
                        // Wait for autoLoad to trigger
                        completeWithData();
                        expect(store.flushCallCount).toBe(1);
                    });
                });

                it("should not load the store again when expanding the headerCt menu", function() {
                    var spy = jasmine.createSpy(),
                        col, menu;

                    createGrid({
                        remoteFilter: true,
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'list',
                                    options: [
                                        ['Jimmy Page', 'John Scofield', 'Robben Ford', 'Alex Lifeson']
                                    ],
                                    value: 'Robben Ford'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100
                            }
                        ]
                    });
                    completeWithData();
                    col = grid.columnManager.getColumns()[0];

                    store.on('load', spy);

                    Ext.testHelper.showHeaderMenu(col);

                    runs(function() {
                        completeWithData();
                        expect(spy.callCount).toBe(0);
                        expect(store.filters.length).toBe(1);
                    });
                });

                it("should send filter data in the params for any active filter", function() {
                    // Note that it ignores the store config in favor of the default panel config.
                    createGrid({
                        remoteFilter: true,
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'robben'
                                }
                            },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    waitsFor(function() {
                        return store.flushCallCount > 0;
                    });

                    runs(function() {
                        var filters = getFilters();

                        expect(filters.length).toBe(2);
                        expect(filters[0].getProperty()).toBe('name');
                        expect(filters[1].getProperty()).toBe('email');
                    });
                });

                it("should not send filter data in the params for any inactive filter", function() {
                    // Note that it ignores the store config in favor of the default panel config.
                    createGrid({
                        remoteFilter: true,
                        autoLoad: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    waitsFor(function() {
                        return store.flushCallCount > 0;
                    });

                    runs(function() {
                        expect(getFilters()).not.toBeDefined();
                    });
                });
            });

            describe("applying state, normal grid", function() {
                beforeEach(function() {
                    new Ext.state.Provider();

                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });
                });

                it("should not make more than one request when applying state", function() {
                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });

                    completeWithData();
                    expect(store.flushCallCount).toBe(1);
                });
            });

            describe("locked grid", function() {
                beforeEach(function() {
                    new Ext.state.Provider();

                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', locked: true, width: 100 },
                            { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });
                });

                it("should not make more than one request when applying state", function() {
                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', locked: true, width: 100 },
                            { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });

                    completeWithData();
                    expect(store.flushCallCount).toBe(1);
                });

                it("should include all filters from locking partners in the request", function() {
                    var filters = getFilters();

                    expect(filters.length).toBe(2);
                    completeWithData();
                });
            });
        });

        describe("no autoLoad", function() {
            // See EXTJS-15348.
            it("should not cause the store to load", function() {
                var proto = Ext.data.ProxyStore.prototype;

                spyOn(proto, 'flushLoad').andCallThrough();

                createGrid({
                    remoteFilter: true,
                    autoLoad: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/remoteFiltering'
                    }
                }, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'stevie ray'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', width: 100,
                            filter: {
                                type: 'string'
                            }
                        }
                    ]
                });

                // Store must now have a pending load. It's going
                // to load at the next tick. The autoLoad, and the addition
                // of the filter both required a load be scheduled.
                expect(store.hasPendingLoad()).toBe(true);

                // The createGrid function explicitly flushes an loads.
                expect(proto.flushLoad.callCount).toBe(1);
            });
            // Note that for all specs it ignores the store config in favor of the default panel config.
            it("should not send multiple requests", function() {
                createGrid({
                    remoteFilter: true,
                    autoLoad: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/remoteFiltering'
                    }
                }, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'stevie ray'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', width: 100,
                            filter: {
                                type: 'string'
                            }
                        }
                    ]
                });

                waitsFor(function() {
                    return store.flushCallCount > 0;
                });
                runs(function() {
                    expect(store.flushCallCount).toBe(1);
                });
            });

            describe("applying state, normal grid", function() {
                beforeEach(function() {
                    new Ext.state.Provider();

                    createGrid({
                        remoteFilter: true,
                        autoLoad: false,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });
                });

                it("should not make more than one request when applying state", function() {
                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        remoteFilter: true,
                        autoLoad: false,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100,
                                filter: {
                                    type: 'string'
                                }
                            }
                        ]
                    });

                    waitsFor(function() {
                        return store.flushCallCount > 0;
                    });
                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });

            describe("locked grid", function() {
                beforeEach(function() {
                    new Ext.state.Provider();

                    createGrid({
                        remoteFilter: true,
                        autoLoad: false,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', locked: true, width: 100 },
                            { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });
                });

                it("should not make more than one request when applying state", function() {
                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        remoteFilter: true,
                        autoLoad: false,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        stateful: true,
                        stateId: 'yobe',
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    type: 'string',
                                    value: 'stevie ray'
                                }
                            },
                            { header: 'Email', dataIndex: 'email', locked: true, width: 100 },
                            { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                                filter: {
                                    type: 'string',
                                    value: '555'
                                }
                            }
                        ]
                    });

                    waitsFor(function() {
                        return store.flushCallCount > 0;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });
        });
    });

    describe("adding filters", function() {
        var column, columnFilter, columnName, columnValue, filters;

        afterEach(function() {
            column = columnFilter = columnName = columnValue = filters = null;
        });

        describe("addFilter - single", function() {
            it("should add a single filter", function() {
                columnName = 'name';
                createGrid();
                column = grid.columnManager.getHeaderByDataIndex(columnName);

                expect(column.filter).toBeUndefined();
                filtersPlugin.addFilter({ dataIndex: columnName });
                expect(column.filter.isGridFilter).toBe(true);
            });

            it("should turn the filter config into a filter instance", function() {
                columnName = 'dob';
                createGrid();

                filtersPlugin.addFilter({ dataIndex: columnName, type: 'date' });
                expect(grid.columnManager.getHeaderByDataIndex(columnName).filter.isGridFilter).toBe(true);
            });

            it("should not add if it does not map to an exiting column (filter config)", function() {
                createGrid();

                filtersPlugin.addFilter({ dataIndex: 'vanhalen', value: 'jimmy' });

                expect(store.getFilters().getCount()).toBe(0);
            });

            it("should not add if it does not map to an exiting column (filter instance)", function() {
                createGrid();
                filters = grid.getStore().getFilters();

                expect(filters.getCount()).toBe(0);
                filtersPlugin.addFilter({ dataIndex: 'vanhalen', value: 'jimmy' });
                expect(filters.getCount()).toBe(0);
            });

            describe("replacing a filter", function() {
                beforeEach(function() {
                    createGrid(null, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', filter: { value: 'jimmy' }, width: 100 }
                        ]
                    });
                });

                it("should work, replacing once", function() {
                    var oldFilter, newFilter;

                    filters = grid.getStore().getFilters();
                    oldFilter = filters.getAt(0);

                    expect(filters.getCount()).toBe(1);
                    expect(oldFilter.getValue()).toBe('jimmy');

                    // Now add the new filter which should replace the existing one.
                    filtersPlugin.addFilter({ dataIndex: 'name', value: 'alex' });
                    newFilter = filters.getAt(0);

                    expect(filters.getCount()).toBe(1);
                    expect(newFilter.getValue()).toBe('alex');

                    expect(newFilter).not.toBe(oldFilter);
                });

                it("should work, replacing more than once", function() {
                    // This fixes a bug where the store filter wasn't being destroyed
                    // when the column filter was replaced more than once when .addFilter
                    // was called programatically. See EXTJS-13741.
                    var oldFilter, newFilter;

                    filters = grid.getStore().getFilters();
                    oldFilter = filters.getAt(0);

                    expect(filters.getCount()).toBe(1);
                    expect(oldFilter.getValue()).toBe('jimmy');

                    // Now add the new filter which should replace the existing one.
                    filtersPlugin.addFilter({ dataIndex: 'name', value: 'alex' });
                    newFilter = filters.getAt(0);

                    expect(filters.getCount()).toBe(1);
                    expect(newFilter.getValue()).toBe('alex');

                    expect(newFilter).not.toBe(oldFilter);

                    // Swap for the next test...
                    oldFilter = newFilter;

                    // ...and do it all again.
                    filtersPlugin.addFilter({ dataIndex: 'name', value: 'kenny' });
                    newFilter = filters.getAt(0);

                    expect(filters.getCount()).toBe(1);
                    expect(newFilter.getValue()).toBe('kenny');

                    expect(newFilter).not.toBe(oldFilter);
                });

                it("should remove the reference to the old menu on the Filters menuItem", function() {
                    // See EXTJS-13717.
                    var column = grid.columnManager.getColumns()[0],
                        menu;

                    Ext.testHelper.showHeaderMenu(column);

                    runs(function() {
                        menu = column.activeMenu;
                        // Showing the menu will have the filters plugin create the column filter menu.
                        expect(menu.items.getByKey('filters').menu).toBeDefined();

                        grid.headerCt.menu.hide();

                        // Replacing the existing filter will destroy the old filter and should remove
                        // all references bound to it, and it's ownerCmp (the 'filters' menuItem) should
                        // null out its reference to the column filter menu.
                        filtersPlugin.addFilter({ dataIndex: 'name', value: 'alex' });

                        expect(menu.items.getByKey('filters').menu).toBeNull();
                    });
                });

                it("should replace the reference to the old menu with the new menu", function() {
                    // See EXTJS-13717.
                    var column = grid.columnManager.getColumns()[0],
                        menu, menuItem, oldMenu, newMenu;

                    Ext.testHelper.showHeaderMenu(column);

                    runs(function() {
                        menu = column.activeMenu;
                        menuItem = menu.items.getByKey('filters');
                        oldMenu = menuItem.menu;

                        grid.headerCt.menu.hide();

                        // Replace...
                        filtersPlugin.addFilter({ dataIndex: 'name', value: 'alex' });
                    });

                    // ...and show to trigger the plugin to create the new column filter menu.
                    Ext.testHelper.showHeaderMenu(column);

                    runs(function() {
                        newMenu = menuItem.menu;

                        expect(newMenu).not.toBe(oldMenu);
                        expect(newMenu).toBe(column.filter.menu);
                    });
                });
            });

            describe("remote filtering", function() {
                beforeEach(function() {
                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/addingFilters'
                        }
                    }, {
                        filters: [{
                            dataIndex: 'name',
                            value: 'alex'
                        }]
                    });
                });

                it("should send a network request when adding an active filter config", function() {
                    filtersPlugin.addFilter({ dataIndex: 'email', value: 'albuquerque@newmexico.com' });

                    waitsFor(function() {
                        return store.flushCallCount === 2;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(2);
                    });
                });

                it("should not send a network request when adding an inactive filter", function() {
                    filtersPlugin.addFilter({ dataIndex: 'email' });

                    // Need to waits() because we're checking something doesn't happen
                    waits(10);

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });

                it("should not send a network request when adding an inactive filter instance", function() {
                    filtersPlugin.addFilter(Ext.grid.filters.filter.String({ dataIndex: 'email' }));

                    // Need to waits() because we're checking something doesn't happen
                    waits(10);

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });
        });

        describe("addFilters - batch", function() {
            var columnManager, col1, col2, col3;

            afterEach(function() {
                columnManager = col1 = col2 = col3 = null;
            });

            it("should add a multiple filters configs", function() {
                createGrid();

                columnManager = grid.columnManager;
                col1 = columnManager.getHeaderByDataIndex('name');
                col2 = columnManager.getHeaderByDataIndex('email');
                col3 = columnManager.getHeaderByDataIndex('phone');

                expect(col1.filter).toBeUndefined();
                expect(col2.filter).toBeUndefined();
                expect(col3.filter).toBeUndefined();

                filtersPlugin.addFilters([{ dataIndex: 'name' }, { dataIndex: 'email' }, { dataIndex: 'phone' }]);

                expect(col1.filter.isGridFilter).toBe(true);
                expect(col2.filter.isGridFilter).toBe(true);
                expect(col3.filter.isGridFilter).toBe(true);
            });

            it("should not add duplicate filters configs to store filters collection", function() {
                columnName = 'email';
                createGrid();

                column = grid.columnManager.getHeaderByDataIndex(columnName);
                filters = grid.getStore().getFilters();

                expect(filters.getCount()).toBe(0);

                filtersPlugin.addFilters([
                    { dataIndex: 'email', value: 'ben@sencha.com' },
                    { dataIndex: 'email', value: 'toll@sencha.com' }
                ]);

                expect(filters.getCount()).toBe(1);
                expect(column.filter.value).toBe('toll@sencha.com');
            });

            it("should not add column filters that do not map to a column", function() {
                columnName = 'foo';
                createGrid();
                column = grid.columnManager.getHeaderByDataIndex(columnName);

                expect(column).toBeNull();
                filtersPlugin.addFilters([{ dataIndex: columnName }]);
                expect(column).toBeNull();
            });

            it("should not add store filters when data index does not map to a column", function() {
                columnName = 'foo';
                createGrid();
                filters = grid.store.filters;

                expect(filters.getCount()).toBe(0);
                filtersPlugin.addFilters([{ dataIndex: columnName, value: 'bar' }]);
                expect(filters.getCount()).toBe(0);
            });

            it("should not add column filters that do not map to a column (mixed with legitimate data indices)", function() {
                columnName = 'foo';
                createGrid();
                column = grid.columnManager.getHeaderByDataIndex(columnName);

                expect(column).toBeNull();
                filtersPlugin.addFilters([{ dataIndex: columnName }, { dataIndex: 'phone' }]);
                expect(column).toBeNull();
            });

            it("should add column filters that do map to a column (mixed with illegitimate data indices)", function() {
                columnName = 'phone';
                createGrid();
                column = grid.columnManager.getHeaderByDataIndex(columnName);

                expect(column).toBeDefined();
                filtersPlugin.addFilters([{ dataIndex: 'foo' }, { dataIndex: columnName }]);
                expect(column.filter.isGridFilter).toBe(true);
            });

            it("should not add store filters that do not map to a column (mixed with legitimate data indices)", function() {
                columnValue = '717-737-8879';
                createGrid();
                filters = grid.getStore().getFilters();

                expect(filters.getCount()).toBe(0);
                filtersPlugin.addFilters([{ dataIndex: 'foo', value: 'bar' }, { dataIndex: 'phone', value: columnValue }]);
                expect(filters.getCount()).toBe(1);
                expect(filters.getAt(0).getValue()).toBe(columnValue);
            });

            it("should turn the filter config into a filter instance", function() {
                columnName = 'age';
                createGrid();
                column = grid.columnManager.getHeaderByDataIndex('age');

                expect(column.filter).toBeUndefined();
                filtersPlugin.addFilters([{ dataIndex: 'age', type: 'numeric' }]);
                expect(column.filter.isGridFilter).toBe(true);
            });

            it("should replace existing filters", function() {
                var oldFilter, oldFilter2, newFilter, newFilter2;

                createGrid(null, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', filter: { value: 'jimmy' }, width: 100 },
                        { header: 'Email',  dataIndex: 'email', filter: { value: 'jimmy@' }, width: 100 }
                    ]
                });

                filters = grid.getStore().getFilters();
                oldFilter = filters.getAt(0);
                oldFilter2 = filters.getAt(1);

                expect(filters.getCount()).toBe(2);
                expect(oldFilter.getValue()).toBe('jimmy');
                expect(oldFilter2.getValue()).toBe('jimmy@');

                // Now add the new filter which should replace the existing one.
                filtersPlugin.addFilters([
                    { dataIndex: 'name', value: 'Stevie Ray' },
                    { dataIndex: 'email', value: 'vaughan.com' }
                ]);
                newFilter = filters.getAt(0);
                newFilter2 = filters.getAt(1);

                expect(filters.getCount()).toBe(2);
                expect(newFilter.getValue()).toBe('Stevie Ray');
                expect(newFilter2.getValue()).toBe('vaughan.com');

                expect(newFilter).not.toBe(oldFilter);
                expect(newFilter2).not.toBe(oldFilter2);
            });

            it("should call the addFilter() implementation", function() {
                createGrid();
                spyOn(filtersPlugin, 'addFilter');
                filtersPlugin.addFilters([{ dataIndex: 'name' }, { dataIndex: 'email' }, { dataIndex: 'phone' }]);

                expect(filtersPlugin.addFilter).toHaveBeenCalled();
            });

            describe("remote filtering", function() {
                beforeEach(function() {
                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/addingFilters'
                        }
                    });
                });

                it("should send a network request when adding at least one active filter config", function() {
                    filtersPlugin.addFilters([{ dataIndex: 'name' }, { dataIndex: 'email', value: 'jack' }, { dataIndex: 'phone' }]);

                    waitsFor(function() {
                        return store.flushCallCount === 2;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(2);
                    });
                });

                it("should send only one network request no matter how many active filters configs are added", function() {
                    filtersPlugin.addFilters([{ dataIndex: 'name', value: 'ginger' }, { dataIndex: 'email', value: 'suzy' }, { dataIndex: 'phone', value: '717' }]);

                    waitsFor(function() {
                        return store.flushCallCount === 2;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(2);
                    });
                });

                it("should not send a network request when not adding an active filter config", function() {
                    filtersPlugin.addFilters([{ dataIndex: 'name' }, { dataIndex: 'email' }, { dataIndex: 'phone' }]);

                    // Need to waits because we're checking something doesn't happen
                    waits(10);

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });
        });
    });

    describe("adding to headerCt", function() {
        var column, columnFilter, columnName, columnValue, filters;

        afterEach(function() {
            column = columnFilter = columnName = columnValue = filters = null;
        });

        describe("normal grid", function() {
            beforeEach(function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'jimmy'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', width: 100,
                            filter: {
                                type: 'string',
                                value: 'ben@sencha.com'
                            }
                        },
                        { header: 'Phone', dataIndex: 'phone', width: 100,
                            filter: {
                                type: 'string',
                                value: '717-555-1212'
                            }
                        }
                    ]
                });
            });

            it("should create a column filter instance with a default `String` type when no type is given", function() {
                columnName = 'dob';

                grid.headerCt.add({
                    dataIndex: columnName,
                    text: 'DOB',
                    filter: {
                        value: {
                            eq: new Date('8/8/1992')
                        }
                    }
                });

                expect(grid.columnManager.getHeaderByDataIndex(columnName).filter.type).toBe('string');
            });

            it("should create a column filter instance with the specified filter type when a type is given", function() {
                columnName = 'dob';

                grid.headerCt.add({
                    dataIndex: columnName,
                    text: 'DOB',
                    filter: {
                        type: 'date',
                        value: {
                            eq: new Date('8/8/1992')
                        }
                    }
                });

                expect(grid.columnManager.getHeaderByDataIndex(columnName).filter.type).toBe('date');
            });

            it("should create a column filter instance when adding a new column with a 'filter' config", function() {
                columnName = 'dob';

                grid.headerCt.add({
                    dataIndex: columnName,
                    text: 'DOB',
                    filter: {
                        value: {
                            eq: new Date('8/8/1992')
                        }
                    }
                });

                expect(grid.columnManager.getHeaderByDataIndex(columnName).filter.isGridFilter).toBe(true);
            });

            it("should not create a column filter instance when adding a new column without a 'filter' config", function() {
                columnName = 'dob';

                grid.headerCt.add({
                    dataIndex: columnName,
                    text: 'DOB'
                });

                expect(grid.columnManager.getHeaderByDataIndex(columnName).filter).toBeUndefined();
            });
        });

        describe("remote filtering", function() {
            beforeEach(function() {
                createGrid({
                    remoteFilter: true,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/remoteFiltering'
                    }
                }, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100 },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', width: 100 }
                    ]
                });
                completeWithData();
            });

            it("should make a request that includes the new filter when adding a column with an active filter", function() {
                grid.headerCt.add({
                    dataIndex: 'age',
                    text: 'Age',
                    filter: {
                        type: 'numeric',
                        value: {
                            gt: 5
                        }
                    }
                });

                var filters = getFilters();

                expect(filters.length).toBe(1);
                expect(filters[0].getProperty()).toBe('age');
            });
        });

        describe("locked grid", function() {
            describe("local filtering", function() {
                beforeEach(function() {
                    createGrid({}, {
                        columns: [
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', locked: true, width: 100 }
                        ]
                    });
                });

                it("should add a new store filter when called on a locking partner (lockedGrid)", function() {
                    var filters = grid.getStore().getFilters();

                    expect(filters.getCount()).toBe(0);
                    filtersPlugin.grid.lockedGrid.headerCt.add({
                        dataIndex: 'age',
                        text: 'Age',
                        locked: true,
                        filter: {
                            type: 'numeric',
                            value: {
                                eq: 10
                            }
                        }
                    });
                    expect(filters.getCount()).toBe(1);
                });

                it("should add a new store filter when called on a locking partner (normalGrid)", function() {
                    var filters = grid.getStore().getFilters();

                    expect(filters.getCount()).toBe(0);
                    filtersPlugin.grid.normalGrid.headerCt.add({
                        dataIndex: 'age',
                        text: 'Age',
                        locked: true,
                        filter: {
                            type: 'numeric',
                            value: {
                                eq: 10
                            }
                        }
                    });
                    expect(filters.getCount()).toBe(1);
                });

                it("should filter if the filter config contains a 'value' property", function() {
                    var filters = grid.getStore().getFilters();

                    grid.lockedGrid.headerCt.add({
                        dataIndex: 'age',
                        text: 'Age',
                        locked: true,
                        filter: {
                            type: 'numeric',
                            value: {
                                eq: 10
                            }
                        }
                    });

                    expect(filters.getCount()).toBe(1);

                    grid.normalGrid.headerCt.add({
                        dataIndex: 'dob',
                        text: 'DOB',
                        filter: {
                            type: 'numeric',
                            value: {
                                lt: new Date('9/26/2009')
                            }
                        }
                    });

                    expect(filters.getCount()).toBe(2);

                    grid.lockedGrid.headerCt.add({
                        dataIndex: 'name',
                        text: 'Name',
                        locked: true,
                        filter: {
                            value: 'motley'
                        }
                    });

                    expect(filters.getCount()).toBe(3);
                });
            });

            describe("remote filtering", function() {
                beforeEach(function() {
                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/remoteFiltering'
                        }
                    }, {
                        columns: [
                            { header: 'Name', dataIndex: 'name', locked: true, width: 100 },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100 }
                        ]
                    });
                });

                describe("normalGrid", function() {
                    it("should not make a request when adding a column with an inactive filter", function() {
                        var initialFlushCallCount = store.flushCallCount;

                        filtersPlugin.grid.normalGrid.headerCt.add({
                            dataIndex: 'age',
                            text: 'Age',
                            filter: {
                                type: 'numeric'
                            }
                        });

                        expect(store.flushCallCount).toBe(initialFlushCallCount);
                    });

                    it("should make a request that includes the new filter when adding a column with an active filter", function() {
                        var initialFlushCallCount = store.flushCallCount;

                        filtersPlugin.grid.normalGrid.headerCt.add({
                            dataIndex: 'age',
                            text: 'Age',
                            filter: {
                                type: 'numeric',
                                value: {
                                    gt: 5
                                }
                            }
                        });

                        expect(store.flushCallCount).toBe(initialFlushCallCount + 1);
                    });
                });

                describe("lockedGrid", function() {
                    it("should not make a request when adding a column with an inactive filter", function() {
                        var initialFlushCallCount = store.flushCallCount;

                        filtersPlugin.grid.lockedGrid.headerCt.add({
                            dataIndex: 'age',
                            text: 'Age',
                            locked: true,
                            filter: {
                                type: 'numeric'
                            }
                        });

                        expect(store.flushCallCount).toBe(initialFlushCallCount);
                    });

                    it("should make a request that includes the new filter when adding a column with an active filter", function() {
                        filtersPlugin.grid.lockedGrid.headerCt.add({
                            dataIndex: 'age',
                            text: 'Age',
                            locked: true,
                            filter: {
                                type: 'numeric',
                                value: {
                                    gt: 5
                                }
                            }
                        });

                        expect(store.flushCallCount).toBe(2);
                    });
                });
            });
        });

        // TODO
        describe("stateful", function() {
        });
    });

    // The intent of this describe block is primarily to demonstrate what happens when setActive() is
    // called for both local and remote filtering. In order to do this, we must toggle setActive() to
    // achieve our goals.
    describe("setActive", function() {
        var storeFilters, columnFilter;

        afterEach(function() {
            storeFilters = columnFilter = null;
        });

        describe("local filtering", function() {
            beforeEach(function() {
                createGrid({
                    remoteFilter: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/setActive'
                    }
                }, {
                    columns: [{
                        dataIndex: 'name',
                        filter: {
                            value: 'alex'
                        }
                    }, {
                        dataIndex: 'age'
                    }]
                });
            });

            describe("when setting active to `false`", function() {
                it("should filter the store", function() {
                    storeFilters = store.getFilters();

                    // We're just demonstrating here that the store has one filter.
                    expect(storeFilters.getCount()).toBe(1);

                    // Now we'll set inactive, which is the point of this spec.
                    grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);

                    completeWithData();

                    expect(storeFilters.getCount()).toBe(0);
                });

                it("should not send a network request", function() {
                    grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);
                    completeWithData();
                    // Note that the load count would be 2 if setActive(false) had initiated another request.
                    expect(store.flushCallCount).toBe(1);
                });
            });

            describe("when setting active to `true`", function() {
                it("should filter the store", function() {
                    columnFilter = grid.columnManager.getHeaderByDataIndex('name').filter;

                    // Start out with it filtered and toggle.
                    storeFilters = store.getFilters();

                    // We're just demonstrating here that the store has one filter.
                    expect(storeFilters.getCount()).toBe(1);

                    // Toggle.
                    columnFilter.setActive(false);

                    expect(storeFilters.getCount()).toBe(0);

                    // Now we'll set to active, which is the point of this spec.
                    columnFilter.setActive(true);

                    expect(storeFilters.getCount()).toBe(1);
                });

                it("should not send a network request", function() {
                    filtersPlugin.addFilter({ dataIndex: 'age', type: 'numeric' });
                    grid.columnManager.getHeaderByDataIndex('age').filter.setActive(true);

                    waitsFor(function() {
                        return store.flushCallCount === 1;
                    });

                    runs(function() {
                        // Note that the load count would be 2 if the newly-added filter would have made a request.
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });
        });

        describe("remote filtering", function() {
            var columnFilter;

            beforeEach(function() {
                createGrid({
                    remoteFilter: true,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/setActive'
                    }
                }, {
                    columns: [
                        { header: 'Name', dataIndex: 'name', locked: true,
                            filter: {
                                value: 'alex'
                            },
                        width: 100 },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', width: 100 },
                        { header: 'Age', dataIndex: 'age', width: 100 }
                    ]
                });
                completeWithData();
            });

            describe("when setting active to `false`", function() {
                it("should not send the filter data in the request", function() {
                    grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);
                    expect(getFilters()).toBeUndefined();
                });

                it("should filter the store", function() {
                    var filters = store.getFilters();

                    expect(filters.getCount()).toBe(1);

                    grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);

                    expect(filters.getCount()).toBe(0);
                });
            });

            describe("when setting active to `true`", function() {
                it("should send the filter data in the request", function() {
                    filtersPlugin.addFilter({ dataIndex: 'age', type: 'numeric' });

                    columnFilter = grid.columnManager.getHeaderByDataIndex('age').filter;
                    columnFilter.createMenu();

                    // Creating a store filter will activate the column filter.
                    columnFilter.setValue({ eq: 42 });

                    // Expect 2 b/c the feature was configured with an active filter.
                    expect(getFilters().length).toBe(2);
                });

                it("should filter the store", function() {
                    var filters = store.getFilters();

                    expect(filters.getCount()).toBe(1);

                    filtersPlugin.addFilter({ dataIndex: 'age', type: 'numeric' });

                    columnFilter = grid.columnManager.getHeaderByDataIndex('age').filter;
                    columnFilter.createMenu();

                    // Creating a store filter will activate the column filter.
                    columnFilter.setValue({ eq: 42 });

                    expect(getFilters().length).toBe(2);
                });
            });
        });
    });

    describe("getting the column filter", function() {
        it("should get the specified filter", function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', filter: true, width: 100 },
                    { header: 'Email', dataIndex: 'email', width: 100,
                        filter: {
                            type: 'string',
                            value: 'ben@sencha.com'
                        }
                    },
                    { header: 'Phone', dataIndex: 'phone', width: 100,
                        filter: {
                            type: 'string',
                            value: '717-555-1212'
                        }
                    }
                ]
            });

            expect(grid.columnManager.getHeaderByDataIndex('name').filter).toBeDefined();
        });

        describe("locked grid", function() {
            beforeEach(function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'ben@sencha.com'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: 'ben@sencha.com'
                            }
                        },
                        { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: '717-555-1212'
                            }
                        }
                    ]
                });
            });

            it("should get the specified filter", function() {
                expect(grid.columnManager.getHeaderByDataIndex('phone').filter.isGridFilter).toBe(true);
            });
        });
    });

    describe("locked grid", function() {
        var column, columnFilter, filters;

        afterEach(function() {
            column = columnFilter = filters = null;
        });

        describe("initialization", function() {
            it("should create an 'isLocked' property", function() {
                createGrid({}, {
                    columns: [{ header: 'Name',  dataIndex: 'name', locked: true, width: 100 }]
                });

                expect(filtersPlugin.isLocked).toBe(true);
            });
        });

        describe("the store", function() {
            it("should bind the grid store to the feature", function() {
                createGrid({}, {
                    columns: [{ header: 'Name',  dataIndex: 'name', locked: true, width: 100 }]
                });

                expect(filtersPlugin.store).toBe(store);
            });

            it("should add each filter to the store", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'stevie ray'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: 'ben@sencha.com'
                            }
                        },
                        { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: '717-555-1212'
                            }
                        }
                    ]
                });

                expect(store.getFilters().getCount()).toBe(3);
            });
        });

        describe("adding filters", function() {
            beforeEach(function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', locked: true, width: 100 },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', width: 100 }
                    ]
                }, {});
            });

            describe("addFilter - single", function() {
                it("should work", function() {
                    column = grid.columnManager.getHeaderByDataIndex('name');

                    expect(column.filter).toBeUndefined();
                    filtersPlugin.addFilter({ dataIndex: 'name' });
                    expect(column.filter).toBeDefined();
                });

                it("should not add a new filter to the store if not configured with a 'value' property", function() {
                    filtersPlugin.addFilter({ dataIndex: 'name' });

                    expect(store.getFilters().getCount()).toBe(0);
                });

                it("should add the filter to the store if config has a 'value' property", function() {
                    filtersPlugin.addFilter({ dataIndex: 'name', value: 'jimmy' });

                    expect(store.getFilters().getCount()).toBe(1);
                });

                it("should not add if it does not map to an exiting column (filter config)", function() {
                    filtersPlugin.addFilter({ dataIndex: 'vanhalen', value: 'jimmy', locked: true });

                    expect(store.getFilters().getCount()).toBe(0);
                });
            });

            describe("addFilters - batch", function() {
                it("should not add the store filters to the store (no 'value' property)", function() {
                    filtersPlugin.addFilters([{ dataIndex: 'name' }, { dataIndex: 'email' }, { dataIndex: 'phone' }]);

                    expect(filtersPlugin.store.getFilters().getCount()).toBe(0);
                });

                it("should add the filters to their store if configured with a 'value' property", function() {
                    filtersPlugin.addFilters([{ dataIndex: 'name', value: 'john' }, { dataIndex: 'email', value: 'utley' }, { dataIndex: 'phone', value: '717-555-1212' }]);

                    expect(filtersPlugin.store.getFilters().getCount()).toBe(3);
                });

                it("should not add any filters to their store that do not map to a column", function() {
                    filtersPlugin.addFilters([{ dataIndex: 'ledzeppelin', value: 'john' }, { dataIndex: 'rush', value: 'utley' }, { dataIndex: 'phone', value: '717-555-1212' }]);

                    expect(filtersPlugin.store.getFilters().getCount()).toBe(1);
                });
            });
        });

        describe("setActive", function() {
            var storeFilters;

            afterEach(function() {
                storeFilters = null;
            });

            describe("local filtering", function() {
                describe("when setting active to `false`", function() {
                    it("should filter the store, locked grid", function() {
                        createGrid({}, {
                            columns: [{ header: 'Name', filter: { value: 'ford' }, dataIndex: 'name', locked: true, width: 100 }]
                        });

                        storeFilters = store.getFilters();

                        // We're just demonstrating here that the store has one filter.
                        expect(storeFilters.getCount()).toBe(1);

                        // Now we'll set inactive, which is the point of this spec.
                        grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);

                        expect(storeFilters.getCount()).toBe(0);
                    });
                });

                describe("when setting active to `true`", function() {
                    it("should filter the store", function() {
                        // Start out with it filtered and toggle.
                        createGrid({}, {
                            columns: [
                                { header: 'Name',  dataIndex: 'name', filter: { value: 'sco' }, locked: true, width: 100 },
                                { header: 'Email', dataIndex: 'email', width: 100 },
                                { header: 'Phone', dataIndex: 'phone', width: 100 }
                            ]
                        });

                        columnFilter = grid.columnManager.getHeaderByDataIndex('name').filter;
                        storeFilters = store.getFilters();

                        // We're just demonstrating here that the store has one filter.
                        expect(storeFilters.getCount()).toBe(1);

                        // Toggle.
                        columnFilter.setActive(false);

                        expect(storeFilters.getCount()).toBe(0);

                        // Now we'll set to active, which is the point of this spec.
                        columnFilter.setActive(true);

                        expect(storeFilters.getCount()).toBe(1);
                    });
                });
            });

            describe("remote filtering", function() {
                beforeEach(function() {
                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: 'fake'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', locked: true,
                                filter: {
                                    value: 'john'
                                },
                            width: 100 },
                            { header: 'Email', dataIndex: 'email', width: 100 },
                            { header: 'Phone', dataIndex: 'phone', width: 100 }
                        ]
                    });
                    completeWithData();
                });

                describe("when setting active to `false`", function() {
                    it("should not send the filter data in the request", function() {
                        grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);

                        expect(getFilters()).toBeUndefined();
                    });
                });

                describe("when setting active to `true`", function() {
                    it("should send the filter data in the request", function() {
                        filtersPlugin.addFilter({ dataIndex: 'email', value: 'ben' });

                        expect(getFilters().length).toBe(2);
                    });
                });
            });
        });

        describe("no autoLoad", function() {
            it("should not send multiple requests", function() {
                createGrid({
                    remoteFilter: true,
                    autoLoad: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/noAutoLoad'
                    }
                }, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'stevie ray'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: '717-555-8879'
                            }
                        }
                    ]
                });

                // Need to use waits, checking something doesn't run
                waits(10);

                runs(function() {
                    expect(store.flushCallCount).toBe(1);
                });
            });

            it("should include all the store filters from both locking partners in the request", function() {
                createGrid({
                    remoteFilter: true,
                    autoLoad: false,
                    data: null,
                    proxy: {
                        type: 'ajax',
                        url: '/grid/filters/Feature/noAutoLoad'
                    }
                }, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                type: 'string',
                                value: 'stevie ray'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: '717-555-8879'
                            }
                        }
                    ]
                }, {
                    filters: [{
                        dataIndex: 'name',
                        value: 'herring'
                    }]
                });

                waitsFor(function() {
                    return store.flushCallCount === 1;
                });

                runs(function() {
                    expect(getFilters().length).toBe(2);
                    expect(store.flushCallCount).toBe(1);
                });
            });
        });
    });

    describe("stateful", function() {
        var columns, columnFilter;

        afterEach(function() {
            Ext.state.Manager.set(grid.getStateId(), null);
            columns = columnFilter = null;
        });

        describe("remoteFilter", function() {
            describe("if `true`", function() {
                it("should still make a network request if it has state information and the grid store autoLoad = false", function() {
                    // Note that the store config is ignored in favor of the panel config.
                    createGrid({
                        autoLoad: false,
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-true-1'
                    });

                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        autoLoad: false,
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-true-1'
                    });

                    waitsFor(function() {
                        return store.flushCallCount === 1;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });

                it("should not make more than one network request if it has state information", function() {
                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-true-2'
                    });

                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        remoteFilter: true,
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: '/grid/filters/Feature/noAutoLoad'
                        }
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-true-2'
                    });

                    waitsFor(function() {
                        return store.flushCallCount === 1;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });

            describe("if `false`", function() {
                it("should not make a network request if it has state information and autoLoad = false on the grid store", function() {
                    // Note that the store config is ignored in favor of the panel config.
                    createGrid({
                        autoLoad: false,
                        remoteFilter: false
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-false-1'
                    });

                    grid.columnManager.getHeaderByDataIndex('name').filter.setValue('pagey');

                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        autoLoad: false,
                        remoteFilter: false
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-false-1'
                    });

                    waitsFor(function() {
                        return store.flushCallCount === 1;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });

                it("should not make more than one network request if it has state information and autoLoad = true on the grid store", function() {
                    // Note that the store config is ignored in favor of the panel config.
                    createGrid({
                        autoLoad: true,
                        remoteFilter: false,
                        asynchronousLoad: true
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-false-2'
                    });

                    grid.columnManager.getHeaderByDataIndex('name').filter.setValue('pagey');

                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({
                        autoLoad: true,
                        remoteFilter: false
                    }, {
                        columns: [
                            { header: 'Name',  dataIndex: 'name', width: 100,
                                filter: {
                                    value: 'jimmy'
                                }
                            }
                        ],
                        stateful: true,
                        stateId: 'remote-filter-false-2'
                    });

                    waitsFor(function() {
                        return store.flushCallCount === 1;
                    });

                    runs(function() {
                        expect(store.flushCallCount).toBe(1);
                    });
                });
            });
        });

        describe("initialization", function() {
            it("should not save state information for any initialized active filters", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                value: 'jimmy'
                            }
                        },
                        { header: 'Email', dataIndex: 'email', width: 100 },
                        { header: 'Phone', dataIndex: 'phone', width: 100,
                            filter: {
                                type: 'string'
                            }
                        }
                    ],
                    stateful: true,
                    stateId: 'foo'
                });

                grid.saveState();

                expect(grid.getState().storeFilters).toBeUndefined();
            });

            it("should replace any existing values when setting value", function() {
                var columns = [
                        { header: 'Name',  dataIndex: 'name', width: 100,
                            filter: {
                                value: 'lifeson'
                            }
                        }
                    ];

                createGrid({}, {
                    columns: columns,
                    stateful: true,
                    stateId: 'foo'
                });

                // Save state or filter state will be null.
                grid.saveState();

                columnFilter = grid.columnManager.getHeaderByDataIndex('name').filter;

                // Initial value.
                expect(columnFilter.filter.getValue()).toBe('lifeson');

                columnFilter.setValue('page');

                waits(1);

                runs(function() {
                    grid.saveState();
                    Ext.destroy(grid, store);

                    createGrid({}, {
                        columns: columns,
                        stateful: true,
                        stateId: 'foo'
                    });

                    // Updated value.
                    columnFilter = grid.columnManager.getHeaderByDataIndex('name').filter;
                    expect(columnFilter.filter.getValue()).toBe('page');
                });
            });
        });

        describe("changing filter values", function() {
            it("should retain proper filtering when setting active", function() {
                var columns = [
                    { header: 'Name',  dataIndex: 'name', filter: true, width: 100 },
                    { header: 'Email', dataIndex: 'email', width: 100 },
                    { header: 'Phone', dataIndex: 'phone', width: 100 }
                ];

                createGrid({}, {
                    columns: columns,
                    stateful: true,
                    stateId: 'foo'
                });

                // Before filtering.
                expect(grid.store.getCount()).toBe(data.length);

                var filter = grid.columnManager.getHeaderByDataIndex('name').filter;

                // Update state information.
                filter.setActive(true);
                filter.setValue('jimmy');

                waits(1);

                runs(function() {
                    // Before page refresh.
                    expect(grid.store.getCount()).toBe(2);
                });

                waits(1);

                runs(function() {
                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({}, {
                        columns: columns,
                        stateful: true,
                        stateId: 'foo'
                    });

                    // After page refresh.
                    expect(grid.store.getCount()).toBe(2);
                });
            });

            it("should update state information when setting active", function() {
                var columns = [
                    { header: 'Name',  dataIndex: 'name', filter: true, width: 100 },
                    { header: 'Email', dataIndex: 'email', width: 100 },
                    { header: 'Phone', dataIndex: 'phone', width: 100 }
                ];

                createGrid({}, {
                    columns: columns,
                    stateful: true,
                    stateId: 'foo'
                });

                var filter = grid.columnManager.getHeaderByDataIndex('name').filter;

                // Update state information.
                filter.setActive(true);
                filter.setValue('jimmy');

                waits(1);

                runs(function() {
                    // Before page refresh.
                    expect(grid.store.getCount()).toBe(2);
                });

                waits(1);

                runs(function() {
                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({}, {
                        columns: columns,
                        stateful: true,
                        stateId: 'foo'
                    });

                    // After page refresh.
                    filter = grid.columnManager.getHeaderByDataIndex('name').filter;
                    expect(grid.getState().storeState.filters[0].value).toBe('jimmy');
                    expect(filter.filter.getValue()).toBe('jimmy');
                });
            });

            it("should retain proper filtering when setting inactive", function() {
                var columns = [
                    { header: 'Name',  dataIndex: 'name', width: 100,
                        filter: {
                            value: 'jimmy'
                        }
                    },
                    { header: 'Email', dataIndex: 'email', width: 100 },
                    { header: 'Phone', dataIndex: 'phone', width: 100 }
                ];

                createGrid({}, {
                    columns: columns,
                    stateful: true,
                    stateId: 'foo'
                });

                // Before filtering.
                expect(store.getCount()).toBe(2);

                // Update state information.
                grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);

                // After filter.
                waits(1);

                runs(function() {
                    expect(store.getCount()).toBe(data.length);

                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({}, {
                        columns: columns,
                        stateful: true,
                        stateId: 'foo'
                    });

                    // After page refresh.
                    expect(store.getCount()).toBe(data.length);
                });
            });

            it("should update state information when setting inactive", function() {
                var columns = [
                    { header: 'Name',  dataIndex: 'name', width: 100,
                        filter: {
                            value: 'herring',
                            type: 'string'
                        }
                    },
                    { header: 'Email', dataIndex: 'email', width: 100 },
                    { header: 'Phone', dataIndex: 'phone', width: 100 }
                ];

                createGrid({}, {
                    columns: columns,
                    stateful: true,
                    stateId: 'foo'
                });

                grid.saveState();

                // Update state information.
                grid.columnManager.getHeaderByDataIndex('name').filter.setActive(false);

                // After filter.
                waits(1);

                runs(function() {
                    expect(grid.store.getCount()).toBe(data.length);

                    grid.saveState();

                    Ext.destroy(grid, store);

                    createGrid({}, {
                        columns: columns,
                        stateful: true,
                        stateId: 'foo'
                    });

                    // After page refresh.
                    expect(grid.getState().storeState.filters.length).toBe(0);
                });
            });

            it("should keep track of state information when changing values", function() {
                var columns = [
                    { header: 'Name',  dataIndex: 'name', filter: true, width: 100 },
                    { header: 'DOB', dataIndex: 'dob', width: 100,
                        filter: {
                            type: 'date',
                            value: {
                                lt: new Date('8/8/1992')
                            }
                        }
                    }
                ],
                date = new Date('1/22/1962');

                createGrid({}, {
                    columns: columns,
                    stateful: true,
                    stateId: 'foo'
                });

                var filter = grid.columnManager.getHeaderByDataIndex('dob').filter;

                filter.createMenu();
                filter.setValue({ eq: date });

                // Update state information.
                grid.saveState();

                waits(1);

                runs(function() {
                    grid.saveState();
                    Ext.destroy(grid, store);

                    createGrid({}, {
                        columns: columns,
                        stateful: true,
                        stateId: 'foo'
                    });

                    // After page refresh.
                    expect(grid.getState().storeState.filters[0].value).toEqual(date);
                    expect(grid.columnManager.getHeaderByDataIndex('dob').filter.filter.eq.getValue()).toEqual(date);
                });
            });
        });

        // TODO
        describe("locked grid", function() {
        });
    });

    describe("showing the headerCt menu", function() {
        beforeEach(function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100,
                        filter: {
                            type: 'string',
                            value: 'ben'
                        }
                    },
                    { header: 'Email',  dataIndex: 'email', width: 100 }
                ]
            });
        });

        it("should create the 'Filters' menuItem", function() {
            var column = grid.columnManager.getColumns()[0];

            Ext.testHelper.showHeaderMenu(column);

            runs(function() {
                expect(column.getRootHeaderCt().getMenu().items.getByKey('filters')).toBeDefined();
            });
        });

        it("should create the column filter menu", function() {
            var column = grid.columnManager.getColumns()[0],
                menu;

            Ext.testHelper.showHeaderMenu(column);

            waitsFor(function() {
                menu = column.activeMenu;

                return menu && menu.isVisible();
            });
            runs(function() {
                expect(grid.headerCt.menu.items.getByKey('filters').menu).toBeDefined();
            });
        });
    });

    describe("headerCt menu separator", function() {
        it("should add menu separator if other menu items exist", function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100,
                        filter: {
                            type: 'string',
                            value: 'ben'
                        }
                    },
                    { header: 'Email',  dataIndex: 'email', width: 100 }
                ]
            });
            var column = grid.columnManager.getColumns()[0];

            Ext.testHelper.showHeaderMenu(column);

            runs(function() {
                expect(filtersPlugin.sep).toBeDefined();
                // next to last item should be a menu separator, and it should be filters.sep
                expect(grid.headerCt.menu.items.getAt(4).id).toEqual(filtersPlugin.sep.id);
            });
        });

        it("should not add menu separator if no other menu items exist", function() {
            createGrid({}, {
                enableColumnHide: false,
                sortableColumns: false,
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100,
                        filter: {
                            type: 'string'
                        }
                    }
                ]
            });
            var column = grid.columnManager.getColumns()[0];

            Ext.testHelper.showHeaderMenu(column);

            runs(function() {
                expect(filtersPlugin.sep).not.toBeDefined();
                // first item should be the filters item
                expect(grid.headerCt.menu.items.getAt(0).itemId).toBe('filters');
            });
        });
    });

    describe("the Filters menu item", function() {
        afterEach(function() {
            MockAjaxManager.removeMethods();
            grid = filtersPlugin = filter = Ext.destroy(grid);
            store = Ext.destroy(store);
            data = null;
        });

        it("should be present in grid header menu after reordering columns and refreshing", function() {
            // Pass a reference to the cmp not an index!
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

            var columns = [{
                text: 'Name',
                dataIndex: 'name'
            }, {
                text: 'Contact',
                columns: [{
                    text: 'E-Mail',
                    dataIndex: 'email',
                    filter: 'string'
                }, {
                    text: 'Phone',
                    dataIndex: 'phone',
                    filter: 'string'
                }]
            }];

            createGrid({
                statefulFilters: true
            }, {
                stateful: true,
                stateId: 'gridSave',
                columns: columns
            });

            var visibleColumns = grid.visibleColumnManager.getColumns(),
                column, menu;

            // moving column index 2 to 1
            dragColumn(visibleColumns[2], visibleColumns[1]);

            grid.saveState();
            Ext.destroy(grid, store);

            createGrid({
                statefulFilters: true
            }, {
                stateful: true,
                stateId: 'gridSave',
                columns: columns
            });

            column = grid.getColumns()[1];
            Ext.testHelper.showHeaderMenu(column);

            waitsFor(function() {
                menu = column.activeMenu;

                return menu && menu.isVisible();
            });

            runs(function() {
                expect(grid.headerCt.menu.items.getByKey('filters')).toBeDefined();
            });
        });
    });

    // TODO: this should be in TriFilter specs.
    xdescribe("hasActiveFilter", function() {
        it("should return false if there are no active filters", function() {
            createGrid();

            expect(filtersPlugin.hasActiveFilter()).toBe(false);
        });

        it("should return true if there are active filters", function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100,
                        filter: {
                            type: 'string',
                            value: 'ben'
                        }
                    },
                    { header: 'Email',  dataIndex: 'email', width: 100 }
                ]
            });

            expect(filtersPlugin.hasActiveFilter()).toBe(true);
        });

        describe("locked grid", function() {
            it("should return false if there are no active filters", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', locked: true, width: 100 },
                        { header: 'Email',  dataIndex: 'email', width: 100 }
                    ]
                });

                expect(filtersPlugin.hasActiveFilter()).toBe(false);
            });

            it("should return true if there are active filters", function() {
                createGrid({}, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', locked: true, width: 100,
                            filter: {
                                type: 'string',
                                value: 'ben'
                            }
                        },
                        { header: 'Email',  dataIndex: 'email', width: 100 }
                    ]
                });

                expect(filtersPlugin.hasActiveFilter()).toBe(true);
            });
        });
    });

    // TODO
    describe("buffered store", function() {
    });

    describe("reconfigure", function() {
        var newStore, column, menu;

        beforeEach(function() {
            newStore = new Ext.data.Store({
                autoDestroy: true,
                fields: ['name'],
                data: [{
                    name: 'Foo'
                }, {
                    name: 'Bar'
                }, {
                    name: 'Baz'
                }]
            });
        });

        afterEach(function() {
            newStore = column = Ext.destroy(newStore);
        });

        describe("should work", function() {
            describe("the Filters menu item", function() {
                describe("removing the reference to the old menu on the Filters menu item", function() {
                    it("should work for normal grids", function() {
                        createGrid(null, {
                            columns: [{
                                dataIndex: 'name',
                                filter: true
                            }]
                        });
                        column = grid.columnManager.getColumns()[0];

                        Ext.testHelper.showHeaderMenu(column);
                        runs(function() {
                            // Showing the menu will have the filters plugin create the column filter menu.
                            expect(grid.headerCt.menu.items.getByKey('filters').menu).toBeDefined();

                            // Now, let's reconfigure.
                            grid.reconfigure(null, []);

                            expect(grid.headerCt.menu.items.getByKey('filters').menu).toBe(null);
                        });
                    });

                    it("should work for locking grids", function() {
                        var lockedGrid, lockedHeader, normalGrid, normalHeader, filterMenuItem,
                            lockedHeaderMenu, normalHeaderMenu, column;

                        createGrid(null, {
                            columns: [{
                                dataIndex: 'name',
                                filter: true,
                                locked: true
                            }, {
                                dataIndex: 'email',
                                filter: true
                            }]
                        });

                        lockedGrid = grid.lockedGrid;
                        lockedHeader = lockedGrid.headerCt;
                        normalGrid = grid.normalGrid;
                        normalHeader = normalGrid.headerCt;

                        // Show the menu for each headerCt.
                        column = lockedGrid.columnManager.getColumns()[0];
                        Ext.testHelper.showHeaderMenu(column);

                        runs(function() {
                            column = normalGrid.columnManager.getColumns()[0];
                            Ext.testHelper.showHeaderMenu(column);
                        });

                        runs(function() {
                            filterMenuItem = filtersPlugin.filterMenuItem;
                            lockedHeaderMenu = lockedHeader.menu;
                            normalHeaderMenu = normalHeader.menu;

                            // Showing the menu will have the filters plugin create the column filter menu.
                            // The Filters plugin should now have a reference to each Filters menu item.
                            expect(filterMenuItem[lockedGrid.id].menu).toBe(lockedHeaderMenu.down('#filters').menu);
                            expect(filterMenuItem[normalGrid.id].menu).toBe(normalHeaderMenu.down('#filters').menu);

                            // Now, let's reconfigure.
                            grid.reconfigure(null, []);

                            expect(lockedHeaderMenu.items.getByKey('filters').menu).toBe(null);
                            expect(normalHeaderMenu.items.getByKey('filters').menu).toBe(null);
                        });
                    });

                    it("should work with nested columns", function() {
                        var columns = [{
                            text: 'Name',
                            dataIndex: 'name'
                        }, {
                            text: 'Contact',
                            columns: [{
                                text: 'E-Mail',
                                dataIndex: 'email',
                                filter: 'string'
                            }, {
                                text: 'Phone',
                                dataIndex: 'phone',
                                filter: 'string'
                            }]
                        }];

                        createGrid(null, {
                            columns: columns
                        });

                        grid.reconfigure(store, columns);

                        column = grid.getColumnManager().getColumns()[1];
                        Ext.testHelper.showHeaderMenu(column);
                        runs(function() {
                            expect(filtersPlugin.filterMenuItem[grid.id].menu).toBeDefined();
                        });
                    });
                });
            });
        });

        describe("stores", function() {
            it("should bind the new store to the plugin", function() {
                createGrid(null, {
                    columns: [{
                        dataIndex: 'name',
                        filter: true
                    }]
                });

                expect(filtersPlugin.store).toBe(store);

                grid.reconfigure(newStore);

                expect(filtersPlugin.store).toBe(newStore);
            });

            describe("store only", function() {
                it("should have filters react when the store is changed", function() {
                    createGrid(null, {
                        columns: [{
                            dataIndex: 'name',
                            filter: true
                        }]
                    });

                    grid.reconfigure(newStore);

                    expect(newStore.getCount()).toBe(3);
                    grid.columnManager.getHeaderByDataIndex('name').filter.setValue('B');
                    expect(newStore.getCount()).toBe(2);
                });

                it("should remove any active grid filters from the old store", function() {
                    createGrid(null, {
                        columns: [{
                            dataIndex: 'string1',
                            itemId: 'string1',
                            filter: {
                                type: 'string'
                            }
                        }, {
                            dataIndex: 'name2',
                            filter: {
                                type: 'string'
                            }
                        }, {
                            dataIndex: 'number1',
                            itemId: 'number1',
                            filter: {
                                type: 'number'
                            }
                        }, {
                            dataIndex: 'string2',
                            filter: {
                                type: 'number'
                            }
                        }]
                    });

                    store.getFilters().add({
                        property: 'xxx',
                        value: 100
                    });

                    grid.down('#string1').filter.setValue('foo');
                    grid.down('#number1').filter.setValue({
                        eq: 1
                    });

                    store.setAutoDestroy(false);

                    expect(store.getFilters().getCount()).toBe(3);

                    grid.reconfigure(newStore);

                    expect(store.getFilters().getCount()).toBe(1);
                    expect(store.getFilters().getAt(0).getProperty()).toBe('xxx');
                });

                it("should add any active filters to the new store", function() {
                    createGrid(null, {
                        columns: [{
                            dataIndex: 'string1',
                            itemId: 'string1',
                            filter: {
                                type: 'string'
                            }
                        }, {
                            dataIndex: 'name2',
                            filter: {
                                type: 'string'
                            }
                        }, {
                            dataIndex: 'number1',
                            itemId: 'number1',
                            filter: {
                                type: 'number'
                            }
                        }, {
                            dataIndex: 'string2',
                            filter: {
                                type: 'number'
                            }
                        }]
                    });

                    grid.down('#string1').filter.setValue('foo');
                    grid.down('#number1').filter.setValue({
                        eq: 1
                    });

                    grid.reconfigure(newStore);

                    expect(newStore.getFilters().getCount()).toBe(2);
                    expect(newStore.getFilters().getAt(0).getProperty()).toBe('string1');
                    expect(newStore.getFilters().getAt(1).getProperty()).toBe('number1');
                });
            });
        });

        describe("columns", function() {
            function runSpecs(locked) {
                describe(locked ? "locking grid" : "non-locking grid", function() {
                    describe("with a store", function() {
                        it("should filter the store if configured with a filter.value", function() {
                            createGrid(null, {
                                columns: [{
                                    dataIndex: 'name',
                                    locked: locked,
                                    filter: true
                                }]
                            });

                            expect(store.getCount()).toBe(data.length);
                            expect(store.isFiltered()).toBe(false);

                            grid.reconfigure(newStore, [
                                { header: 'Name', dataIndex: 'name',
                                    locked: locked,
                                    filter: {
                                        type: 'string',
                                        value: 'Baz'
                                    }
                                }
                            ]);

                            expect(newStore.getCount()).toBe(1);
                            expect(newStore.isFiltered()).toBe(true);
                        });
                    });

                    describe("null store", function() {
                        it("should filter the store if configured with a filter.value", function() {
                            createGrid(null, {
                                columns: [{
                                    dataIndex: 'name',
                                    locked: locked,
                                    filter: true
                                }]
                            });

                            expect(store.getCount()).toBe(data.length);
                            expect(store.isFiltered()).toBe(false);

                            grid.reconfigure(null, [
                                { header: 'Name', dataIndex: 'name',
                                    locked: locked,
                                    filter: {
                                        type: 'string',
                                        value: 'Jimmy'
                                    }
                                },
                                { header: 'Email', dataIndex: 'email', width: 100,
                                    filter: {
                                        type: 'string',
                                        value: 'jimmy@page.com'
                                    }
                                }
                            ]);

                            expect(store.getCount()).toBe(1);
                            expect(store.isFiltered()).toBe(true);
                        });

                        it("should not react", function() {
                            var counter;

                            createGrid(null, {
                                columns: [{
                                    dataIndex: 'name',
                                    locked: locked,
                                    filter: true
                                }]
                            });

                            grid.columnManager.getHeaderByDataIndex('name').filter.setValue('Jimmy');
                            counter = store.getCount();
                            grid.reconfigure(null, [{ header: 'Name', dataIndex: 'name', locked: locked, filter: true }]);

                            expect(store.getCount()).toBe(counter);
                        });
                    });
                });
            }

            runSpecs(true);
            runSpecs(false);
        });
    });

    describe("destroy", function() {
        it("should not destroy the store when the plugin is destroyed with autoDestroy: false", function() {
            createGrid({
                autoDestroy: false
            });
            spyOn(store, 'destroy');
            grid.destroy();
            expect(store.destroy).not.toHaveBeenCalled();
        });
    });

    describe("treepanel", function() {
        function showMenu() {
            var headerCt = tree.headerCt,
                header = tree.getColumnManager().getLast();

            // Show the grid menu.
            headerCt.showMenuBy(null, header.triggerEl.dom, header);
        }

        it("should not throw when showing the header menu", function() {
            // See EXTJS-14812.
            createTree();

            expect(function() {
                showMenu();
            }).not.toThrow();
        });
    });

    describe("onCheckChange", function() {
        var header;

        function showMenu(header) {
            // Show the grid menu.
            header.ownerCt.showMenuBy(null, header.triggerEl.dom, header);
        }

        afterEach(function() {
            header = null;
        });

        describe("looking up headerCt", function() {
            describe("grids", function() {
                function lockGrid(locked) {
                    it("should not throw, locking = " + locked, function() {
                        createGrid(null, {
                            columns: [
                                { header: 'Name',  dataIndex: 'name', locked: locked, filter: true, width: 100 },
                                { header: 'Email', dataIndex: 'email', filter: true, width: 100 }
                            ]
                        });

                        header = grid.headerCt.columnManager.getHeaderByDataIndex('name');
                        showMenu(header);

                        expect(function() {
                            header.filter.setActive(true);
                        }).not.toThrow();
                    });
                }

                lockGrid(true);
                lockGrid(false);
            });

            describe("trees", function() {
                function lockTree(locked) {
                    it("should not throw, locking = " + locked, function() {
                        createTree(null, {
                            columns: [{
                                header: 'Name',
                                dataIndex: 'name',
                                filter: {
                                    type: 'string'
                                }
                            }, {
                                header: 'Description',
                                dataIndex: 'description',
                                locked: locked,
                                filter: {
                                    type: 'string'
                                }
                            }]
                        });

                        header = tree.headerCt.columnManager.getHeaderByDataIndex('description');
                        showMenu(header);

                        expect(function() {
                            header.filter.setActive(true);
                        }).not.toThrow();
                    });
                }

                lockTree(true);
                lockTree(false);
            });
        });
    });
});
