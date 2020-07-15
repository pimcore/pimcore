topSuite("Ext.grid.filters.filter.List", ['Ext.grid.Panel', 'Ext.grid.filters.Filters'], function() {
    var Model = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: ['id', 'text']
        }),
        grid, store, filterCol, columnMenu, filterItem, filterMenu, listFilter,
        synchronousLoad = true,
        storeLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            storeLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function createGrid(listCfg, storeCfg, gridCfg) {
        synchronousLoad = false;
        store = new Ext.data.Store(Ext.apply({
            model: Model,
            remoteFilter: false,
            data: getData()
        }, storeCfg));

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: [{
                dataIndex: 'id'
            }, {
                dataIndex: 'text',
                itemId: 'filterCol',
                filter: Ext.apply({
                    type: 'list',
                    updateBuffer: 0
                }, listCfg)
            }],
            plugins: [{
                ptype: 'gridfilters'
            }],
            height: 200,
            width: 400,
            renderTo: Ext.getBody()
        }, gridCfg));

        synchronousLoad = true;

        if (store.hasPendingLoad()) {
            store.flushLoad();
        }

        filterCol = grid.down('#filterCol');
        listFilter = filterCol.filter;
    }

    function clickItem(index) {
        runs(function() {
            showMenu();
        });

        waitsFor(function() {
            return columnMenu.isVisible();
        });

        runs(function() {
            jasmine.fireMouseEvent(filterMenu.items.getAt(index).el, 'click');
        });
    }

    function completeRequest(data) {
        Ext.Ajax.mockComplete({
            status: 200,
            responseText: Ext.encode(data)
        });
    }

    function getData() {
        var data = [],
            i = 0,
            ii;

        for (i = 0; i < 12; ++i) {
            ii = i + 1;

            data.push({
                id: 't' + ii,
                text: 'Item ' + ii
            });
        }

        return data;
    }

    function showMenu(column) {
        column = column || filterCol;

        // Show the menu through platform-independent keystrokes.
        Ext.testHelper.showHeaderMenu(column || filterCol);

        runs(function() {
            columnMenu = column.activeMenu;
            filterItem = columnMenu.down('#filters');
            jasmine.fireKeyEvent(columnMenu.el, 'keydown', Ext.event.Event.UP);
            filterMenu = filterItem.menu;

            if (filterMenu.items.getCount()) {
                jasmine.fireKeyEvent(filterItem.el, 'keydown', Ext.event.Event.RIGHT);
                waitsForFocus(filterMenu);
            }
        });
    }

    function hideMenu() {
        columnMenu.hide();
    }

    function setup() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        MockAjaxManager.addMethods();
    }

    function tearDown() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = storeLoad;

        Ext.destroy(store, grid);
        filterCol = filterItem = listFilter = grid = store = null;
        MockAjaxManager.removeMethods();
    }

    beforeEach(setup);

    afterEach(tearDown);

    describe("init", function() {
        it("should be given a default value if no value or options are specified", function() {
            createGrid();

            expect(Ext.isArray(listFilter.filter.getValue())).toBe(true);
        });

        it("should use the value config as its value if specified", function() {
            createGrid({
                value: ['t1', 't3']
            });

            expect(listFilter.filter.getValue()).toEqual(['t1', 't3']);
        });

        it("should transform the filter value if not specified as an array", function() {
            createGrid({
                value: 'Item 2'
            });

            expect(store.getCount()).toBe(1);
            expect(store.getFilters().first().getValue()).toEqual(['Item 2']);
        });
    });

    describe("binding the grid store listeners", function() {
        var oldGridStoreListenersCfg;

        function getGridCfg(cfg) {
            var gridCfg = {
                store: null,
                viewModel: {
                    stores: {
                        quux: {
                            fields: ['id', 'text'],
                            data: getData()
                        }
                    }
                },
                bind: {
                    store: '{quux}'
                }
            };

            return Ext.apply(gridCfg, cfg);
        }

        beforeEach(function() {
            oldGridStoreListenersCfg = Ext.grid.filters.filter.List.prototype.gridStoreListenersCfg;
            Ext.grid.filters.filter.List.prototype.gridStoreListenersCfg = {
                add: 'onDataChanged',
                refresh: 'onDataChanged',
                remove: 'onDataChanged',
                update: 'onDataChanged',
                'extjs-18225': Ext.emptyFn
            };
        });

        afterEach(function() {
            Ext.grid.filters.filter.List.prototype.gridStoreListenersCfg = oldGridStoreListenersCfg;
            oldGridStoreListenersCfg = null;
        });

        describe("when inferring its list options from the grid store", function() {
            describe("on construction", function() {
                describe("should not bind", function() {
                    it("should not bind when not configured with a value", function() {
                        createGrid();

                        expect(listFilter.gridStoreListeners).toBeUndefined();
                    });

                    it("should not bind when not configured with a value even when explicitly configured as active", function() {
                        createGrid({
                            active: true
                        });

                        expect(listFilter.gridStoreListeners).toBeUndefined();
                    });

                    it("should not bind when configured as inactive (no value)", function() {
                        createGrid({
                            active: false
                        });

                        expect(listFilter.gridStoreListeners).toBeUndefined();
                    });

                    it("should not bind when configured as inactive (with a value)", function() {
                        createGrid({
                            active: false,
                            value: 'foo'
                        });

                        expect(listFilter.gridStoreListeners).toBeUndefined();
                    });

                    describe("late binding", function() {
                        function lateBinding(active, value) {
                            it("should bind once the stores comes when late-binding when active = " + active + ' and value = ' + value, function() {
                                createGrid({
                                    active: active,
                                    value: value
                                }, null, getGridCfg());

                                grid.getViewModel().notify();
                                listFilter = filterCol.filter;
                                expect(listFilter.gridStoreListeners).toBeUndefined();
                            });
                        }

                        lateBinding(false, 'Pete');
                        lateBinding(true, null);
                    });
                });

                describe("should bind", function() {
                    it("should bind when configured with a value", function() {
                        createGrid({
                            value: 'quux'
                        });

                        expect(listFilter.gridStoreListeners).toBeDefined();
                    });

                    it("should bind when late-binding the grid store", function() {
                        createGrid({
                            value: 'baz'
                        }, null, getGridCfg());
                        grid.getViewModel().notify();
                        listFilter = filterCol.filter;
                        expect(listFilter.gridStoreListeners).toBeDefined();
                    });
                });
            });

            describe("on menu show", function() {
                function onMenuShow(useVM) {
                    describe(!useVM ? 'configured store' : 'late binding', function() {
                        beforeEach(function() {
                            createGrid(null, null, useVM ? getGridCfg() : null);

                            if (useVM) {
                                grid.getViewModel().notify();
                            }

                            clickItem(1);
                        });

                        it("should not have bound the listeners to the empty store", function() {
                            expect(Ext.StoreMgr.get('ext-empty-store').events['extjs-18225']).toBeUndefined();
                        });

                        it("should have bound the listeners to the correct store", function() {
                            expect(grid.store.events['extjs-18225']).toBeDefined();
                        });
                    });
                }

                onMenuShow(false);
                onMenuShow(true);
            });
        });
    });

    describe("filter configs", function() {
        describe("idField", function() {
            it("should default to \"id\"", function() {
                createGrid();
                expect(listFilter.idField).toBe('id');
            });

            it("should honor a different value", function() {
                var id = 'hot-dog';

                createGrid({
                    idField: id
                });

                expect(listFilter.idField).toBe(id);
            });
        });

        describe("labelField", function() {
            it("should default to \"text\"", function() {
                createGrid();
                expect(listFilter.labelField).toBe('text');
            });

            it("should honor a different value", function() {
                var label = 'veggieburger';

                createGrid({
                    labelField: label
                });

                expect(listFilter.labelField).toBe(label);
            });
        });

        describe("dataIndex", function() {
            it("should default to the column dataIndex", function() {
                createGrid();
                expect(listFilter.dataIndex).toBe(filterCol.dataIndex);
            });

            it("should set the store filter property", function() {
                createGrid();
                expect(listFilter.filter.getProperty()).toBe(filterCol.dataIndex);
            });

            it("should honor a different value", function() {
                var dataIndex = 'gryphon';

                createGrid({
                    dataIndex: dataIndex
                });

                expect(listFilter.dataIndex).toBe(dataIndex);
                expect(listFilter.filter.getProperty()).toBe(dataIndex);
            });

            describe("specifying a dataIndex value", function() {
                beforeEach(function() {
                    createGrid({
                        dataIndex: 'type'
                    }, {
                        data: [
                            { id: 101, type: 't101' },
                            { id: 102, type: 't102' },
                            { id: 103, type: 't103' },
                            { id: 104, type: 't104' }
                        ]
                    });

                    showMenu();
                });

                it("should create the expected number of menu items", function() {
                    expect(filterMenu.items.length).toBe(4);
                });

                it("should not be the same value as the column dataIndex", function() {
                    expect(listFilter.dataIndex).not.toBe(filterCol.dataIndex);
                });
            });
        });

        describe("labelIndex", function() {
            it("should default to the filter dataIndex", function() {
                createGrid();
                expect(listFilter.labelIndex).toBe(listFilter.dataIndex);
            });

            it("should honor a different value", function() {
                var labelIndex = 'Worcester County';

                createGrid({
                    labelIndex: labelIndex
                });

                expect(listFilter.labelIndex).toBe(labelIndex);
            });

            describe("specifying a labelIndex value", function() {
                beforeEach(function() {
                    createGrid({
                        dataIndex: 'foo',
                        labelIndex: 'name'
                    }, {
                        data: [
                            { foo: 101, name: 'Item 101' },
                            { foo: 102, name: 'Item 102' },
                            { foo: 103, name: 'Item 103' },
                            { foo: 104, name: 'Item 104' }
                        ]
                    });

                    showMenu();
                });

                it("should create the expected number of menu items", function() {
                    expect(filterMenu.items.length).toBe(4);
                });

                it("should work", function() {
                    expect(filterMenu.items.getAt(0).text).toBe('Item 101');
                });
            });
        });
    });

    describe("list items", function() {
        var options;

        describe("passing config.value", function() {
            describe("empty array", function() {
                beforeEach(function() {
                    createGrid({
                        value: []
                    });
                });

                it("should set the List filter as active", function() {
                    expect(listFilter.active).toBe(true);
                });

                it("should check the Filters menu item", function() {
                    showMenu();

                    runs(function() {
                        expect(filterItem.checked).toBe(true);
                    });
                });

                it("should not check any option menu items", function() {
                    showMenu();

                    runs(function() {
                        expect(filterItem.query('[checked]').length).toBe(0);
                    });
                });

                it("should create a store filter", function() {
                    showMenu();

                    runs(function() {
                        expect(store.getFilters().length).toBe(1);
                    });
                });

                it("should filter the grid store", function() {
                    showMenu();

                    runs(function() {
                        expect(store.data.filtered).toBe(true);
                    });
                });

                it("should not filter if explicitly configured as not active", function() {
                    Ext.destroy(grid, store);
                    grid = store = null;

                    createGrid({
                        active: false,
                        value: []
                    });

                    expect(store.data.filtered).toBe(false);
                });
            });

            describe("non-empty array", function() {
                beforeEach(function() {
                    createGrid({
                        value: ['Item 1', 'Item 3']
                    });
                });

                it("should set the List filter as active", function() {
                    expect(listFilter.active).toBe(true);
                });

                it("should check the Filters menu item", function() {
                    showMenu();

                    runs(function() {
                        expect(filterItem.checked).toBe(true);
                    });
                });

                it("should check the option menu items specified in the config", function() {
                    var items;

                    showMenu();

                    runs(function() {
                        items = filterMenu.query('[checked]');
                        expect(items.length).toBe(2);
                        expect(items[0].getValue()).toBe('Item 1');
                        expect(items[1].getValue()).toBe('Item 3');
                    });
                });

                it("should create a store filter", function() {
                    showMenu();

                    runs(function() {
                        expect(store.getFilters().length).toBe(1);
                    });
                });

                it("should filter the grid store", function() {
                    showMenu();

                    runs(function() {
                        expect(store.data.filtered).toBe(true);
                    });
                });

                it("should not filter if explicitly configured as not active", function() {
                    Ext.destroy(grid, store);
                    grid = store = null;

                    createGrid({
                        active: false,
                        value: ['Item 1', 'Item 3']
                    });

                    expect(store.data.filtered).toBe(false);
                });
            });
        });

        describe("passing options", function() {
            describe("flat array", function() {
                var opt = ['foo', 'bar', 'baz'];

                it("should use the array element as the menu text", function() {
                    createGrid({
                        options: opt
                    });
                    showMenu();

                    runs(function() {
                        expect(filterMenu.items.getCount()).toBe(3);
                        expect(filterMenu.items.getAt(0).text).toBe('foo');
                        expect(filterMenu.items.getAt(1).text).toBe('bar');
                        expect(filterMenu.items.getAt(2).text).toBe('baz');
                    });
                });

                it("should use the array element as the filter value", function() {
                    createGrid({
                        options: opt
                    });
                    clickItem(1);

                    runs(function() {
                        var filter = store.getFilters().first();

                        expect(filter.getProperty()).toBe('text');
                        expect(filter.getOperator()).toBe('in');
                        expect(filter.getValue()).toEqual(['bar']);
                    });
                });
            });

            describe("nested array", function() {
                var opt = [['foo', 'Foo'], ['bar', 'Bar'], ['baz', 'Baz']];

                it("should use the element at index 1 as the menu text", function() {
                    createGrid({
                        options: opt
                    });
                    showMenu();

                    runs(function() {
                        expect(filterMenu.items.getCount()).toBe(3);
                        expect(filterMenu.items.getAt(0).text).toBe('Foo');
                        expect(filterMenu.items.getAt(1).text).toBe('Bar');
                        expect(filterMenu.items.getAt(2).text).toBe('Baz');
                    });
                });

                it("should use the element at index 0 as the filter value", function() {
                    createGrid({
                        options: opt
                    });
                    clickItem(1);

                    runs(function() {
                        var filter = store.getFilters().first();

                        expect(filter.getProperty()).toBe('text');
                        expect(filter.getOperator()).toBe('in');
                        expect(filter.getValue()).toEqual(['bar']);
                    });
                });
            });

            describe("array of objects", function() {
                var opt = [{ id: 'foo', text: 'Foo' }, { id: 'bar', text: 'Bar' }, { id: 'baz', text: 'Baz' }];

                it("should use the item with the labelField as the menu text", function() {
                    createGrid({
                        options: opt,
                        idField: 'id',
                        labelField: 'text'
                    });
                    showMenu();

                    runs(function() {
                        expect(filterMenu.items.getCount()).toBe(3);
                        expect(filterMenu.items.getAt(0).text).toBe('Foo');
                        expect(filterMenu.items.getAt(1).text).toBe('Bar');
                        expect(filterMenu.items.getAt(2).text).toBe('Baz');
                    });
                });

                it("should use the item with the idField as the filter value", function() {
                    createGrid({
                        options: opt,
                        idField: 'id',
                        labelField: 'text'
                    });
                    clickItem(1);

                    runs(function() {
                        var filter = store.getFilters().first();

                        expect(filter.getProperty()).toBe('text');
                        expect(filter.getOperator()).toBe('in');
                        expect(filter.getValue()).toEqual(['bar']);
                    });
                });
            });
        });

        describe("passing a store", function() {
            afterEach(function() {
                options = Ext.destroy(options);
            });

            describe("with data", function() {
                function makeStore() {
                    return new Ext.data.Store({
                        model: Model,
                        data: [{
                            id: 't1',
                            text: 'Type 1'
                        }, {
                            id: 't2',
                            text: 'Type 2'
                        }]
                    });
                }

                it("should not load the store", function() {
                    options = makeStore();

                    spyOn(options, 'load');

                    createGrid({
                        store: options
                    });
                    showMenu();

                    runs(function() {
                        expect(options.load).not.toHaveBeenCalled();
                    });
                });

                it("should use the field with the labelField as the menu text", function() {
                    options = makeStore();
                    createGrid({
                        store: options
                    });
                    showMenu();

                    runs(function() {
                        expect(filterMenu.items.getCount()).toBe(2);
                        expect(filterMenu.items.getAt(0).text).toBe('Type 1');
                        expect(filterMenu.items.getAt(1).text).toBe('Type 2');
                    });
                });

                it("should use the field with the idField as the filter value", function() {
                    options = makeStore();
                    createGrid({
                        store: options
                    });
                    clickItem(0);

                    runs(function() {
                        var filter = store.getFilters().first();

                        expect(filter.getProperty()).toBe('text');
                        expect(filter.getOperator()).toBe('in');
                        expect(filter.getValue()).toEqual(['t1']);
                    });
                });
            });

            describe("with no data", function() {
                function makeStore(cfg) {
                    return new Ext.data.Store(Ext.apply({
                        model: Model,
                        proxy: {
                            type: 'ajax',
                            url: 'foo'
                        }
                    }, cfg));
                }

                it("should not load on creation", function() {
                    options = makeStore();

                    spyOn(options, 'load');

                    createGrid({
                        store: options
                    });
                    expect(options.load).not.toHaveBeenCalled();
                });

                describe("placeholder", function() {
                    it("should show a loading placeholder on show", function() {
                        options = makeStore();
                        createGrid({
                            store: options
                        });
                        showMenu();

                        runs(function() {
                            expect(filterMenu.items.getCount()).toBe(1);
                            expect(filterMenu.items.getAt(0).text).toBe(listFilter.loadingText);
                        });
                    });

                    it("should remove the placeholder when the store loads", function() {
                        options = makeStore();
                        createGrid({
                            store: options
                        });
                        showMenu();

                        runs(function() {
                            completeRequest([{
                                id: 't1',
                                text: 'Type 1'
                            }, {
                                id: 't2',
                                text: 'Type 2'
                            }]);
                        });
                        waitsFor(function() {
                            return filterMenu.items.getCount() === 2;
                        });
                        runs(function() {
                            expect(filterMenu.items.getAt(0).text).toBe('Type 1');
                            expect(filterMenu.items.getAt(1).text).toBe('Type 2');
                        });
                    });
                });

                describe("loadOnShow", function() {
                    describe("with loadOnShow: true", function() {
                        it("should load the store on show", function() {
                            options = makeStore();

                            spyOn(options, 'load');

                            createGrid({
                                store: options,
                                loadOnShow: true
                            });
                            showMenu();

                            runs(function() {
                                expect(options.load).toHaveBeenCalled();
                            });
                        });

                        it("should not load if the store has a pending autoLoad", function() {
                            options = makeStore({
                                autoLoad: true
                            });
                            spyOn(options, 'load');
                            createGrid({
                                store: options,
                                loadOnShow: true
                            });
                            showMenu();

                            runs(function() {
                                expect(options.load).not.toHaveBeenCalled();
                            });
                        });

                        it("should not load if the store is loading", function() {
                            options = makeStore();

                            options.load();
                            spyOn(options, 'load');

                            createGrid({
                                store: options,
                                loadOnShow: true
                            });
                            showMenu();

                            runs(function() {
                                expect(options.load).not.toHaveBeenCalled();
                            });
                        });

                        it("should not load if the store has already loaded", function() {
                            options = makeStore();

                            createGrid({
                                store: options,
                                loadOnShow: true
                            });
                            showMenu();

                            runs(function() {
                                completeRequest([{
                                    id: 't1',
                                    text: 'Type 1'
                                }]);
                                filterMenu.hide();
                                spyOn(options, 'load');
                            });

                            showMenu();

                            runs(function() {
                                expect(options.load).not.toHaveBeenCalled();
                            });
                        });
                    });

                    describe("with loadOnShow: false", function() {
                        it("should not load the store on show", function() {
                            options = makeStore();
                            spyOn(options, 'load');

                            createGrid({
                                store: options,
                                loadOnShow: false
                            });
                            showMenu();

                            runs(function() {
                                expect(options.load).not.toHaveBeenCalled();
                            });
                        });
                    });
                });

                describe("after load", function() {
                    it("should use the field with the labelField as the menu text", function() {
                        options = makeStore();
                        createGrid({
                            store: options,
                            idField: 'id',
                            labelField: 'text'
                        });
                        showMenu();

                        runs(function() {
                            completeRequest([{
                                id: 't1',
                                text: 'Type 1'
                            }, {
                                id: 't2',
                                text: 'Type 2'
                            }, {
                                id: 't3',
                                text: 'Type 3'
                            }]);
                        });
                        waitsFor(function() {
                            return filterMenu.items.getCount() === 3;
                        });
                        runs(function() {
                            expect(filterMenu.items.getAt(0).text).toBe('Type 1');
                            expect(filterMenu.items.getAt(1).text).toBe('Type 2');
                            expect(filterMenu.items.getAt(2).text).toBe('Type 3');
                        });
                    });

                    it("should use the field with the idField as the filter value", function() {
                        options = makeStore();
                        createGrid({
                            store: options,
                            idField: 'id',
                            labelField: 'text'
                        });
                        showMenu();

                        runs(function() {
                            completeRequest([{
                                id: 't1',
                                text: 'Type 1'
                            }, {
                                id: 't2',
                                text: 'Type 2'
                            }, {
                                id: 't3',
                                text: 'Type 3'
                            }]);
                        });
                        waitsFor(function() {
                            return filterMenu.items.getCount() > 0;
                        });
                        runs(function() {
                            clickItem(1);

                            runs(function() {
                                var filter = store.getFilters().first();

                                expect(filter.getProperty()).toBe('text');
                                expect(filter.getOperator()).toBe('in');
                                expect(filter.getValue()).toEqual(['t2']);
                            });
                        });
                    });
                });

                describe("cleanup", function() {
                    it("should not have any listeners on the store if the store has not loaded", function() {
                        options = makeStore();
                        var load = options.hasListeners.load || 0;

                        createGrid({
                            store: options
                        });
                        grid.destroy();
                        expect(options.hasListeners.load || 0).toBe(load);
                    });

                    it("should not have any listeners on the store if the store is loading if the load returns after destroy", function() {
                        options = makeStore();
                        var load = options.hasListeners.load || 0;

                        createGrid({
                            store: options
                        });
                        showMenu();

                        runs(function() {
                            grid.destroy();
                            expect(options.hasListeners.load || 0).toBe(load);
                            completeRequest([]);
                        });
                    });

                    it("should not have any listeners on the store if the store has loaded", function() {
                        options = makeStore();
                        var load = options.hasListeners.load || 0;

                        createGrid({
                            store: options
                        });
                        showMenu();

                        runs(function() {
                            completeRequest([]);
                            grid.destroy();
                            expect(options.hasListeners.load || 0).toBe(load);
                        });
                    });
                });
            });
        });

        describe("store types", function() {
            it("should accept a store id", function() {
                options = new Ext.data.Store({
                    model: Model,
                    id: 'Foo',
                    data: [{
                        text: 'A'
                    }, {
                        text: 'B'
                    }]
                });
                createGrid({
                    store: 'Foo',
                    labelField: 'text'
                });
                showMenu();

                runs(function() {
                    expect(filterMenu.items.getCount()).toBe(2);
                    expect(filterMenu.items.getAt(0).text).toBe('A');
                    expect(filterMenu.items.getAt(1).text).toBe('B');
                    options.destroy();
                });
            });

            it("should accept a store config", function() {
                createGrid({
                    store: {
                        model: Model,
                        data: [{
                            text: 'A'
                        }, {
                            text: 'B'
                        }]
                    },
                    labelField: 'text'
                });
                showMenu();

                runs(function() {
                    expect(filterMenu.items.getCount()).toBe(2);
                    expect(filterMenu.items.getAt(0).text).toBe('A');
                    expect(filterMenu.items.getAt(1).text).toBe('B');
                });
            });
        });

        describe("cleanup", function() {
            function makeSuite(rendered) {
                describe(rendered ? "when rendered" : "before rendering", function() {
                    function makeDestroyGrid(cfg) {
                        createGrid(cfg, null, {
                            renderTo: rendered ? Ext.getBody() : null
                        });
                    }

                    describe("with autoDestroy: true", function() {
                        it("should destroy a store specified by id", function() {
                            options = new Ext.data.Store({
                                model: Model,
                                autoDestroy: true,
                                id: 'Foo',
                                data: [{
                                    text: 'A'
                                }, {
                                    text: 'B'
                                }]
                            });
                            makeDestroyGrid({
                                store: 'Foo',
                                labelField: 'text'
                            });

                            if (rendered) {
                                showMenu();
                            }

                            runs(function() {
                                grid.destroy();
                                expect(options.destroyed).toBe(true);
                            });
                        });

                        it("should destroy a store instance", function() {
                            options = new Ext.data.Store({
                                model: Model,
                                autoDestroy: true,
                                data: [{
                                    text: 'A'
                                }, {
                                    text: 'B'
                                }]
                            });
                            makeDestroyGrid({
                                store: options
                            });

                            if (rendered) {
                                showMenu();
                            }

                            runs(function() {
                                grid.destroy();
                                expect(options.destroyed).toBe(true);
                            });
                        });

                        it("should destroy a store passed as a config", function() {
                            makeDestroyGrid({
                                store: {
                                    model: Model,
                                    autoDestroy: true,
                                    id: 'x',
                                    data: [{
                                        text: 'A'
                                    }, {
                                        text: 'B'
                                    }]
                                }
                            });

                            if (rendered) {
                                showMenu();
                            }

                            runs(function() {
                                grid.destroy();
                                expect(Ext.StoreManager.lookup('x')).toBeUndefined();
                            });
                        });
                    });

                    describe("with autoDestroy: false", function() {
                        it("should not destroy a store specified by id", function() {
                            options = new Ext.data.Store({
                                model: Model,
                                autoDestroy: false,
                                id: 'Foo',
                                data: [{
                                    text: 'A'
                                }, {
                                    text: 'B'
                                }]
                            });
                            makeDestroyGrid({
                                store: 'Foo',
                                labelField: 'text'
                            });

                            if (rendered) {
                                showMenu();
                            }

                            runs(function() {
                                grid.destroy();
                                expect(options.destroyed).toBe(false);
                                options.destroy();
                            });
                        });

                        it("should not destroy a store instance", function() {
                            options = new Ext.data.Store({
                                model: Model,
                                autoDestroy: false,
                                data: [{
                                    text: 'A'
                                }, {
                                    text: 'B'
                                }]
                            });
                            makeDestroyGrid({
                                store: options,
                                labelField: 'text'
                            });

                            if (rendered) {
                                showMenu();
                            }

                            runs(function() {
                                grid.destroy();
                                expect(options.destroyed).toBe(false);
                                options.destroy();
                            });
                        });

                        it("should not destroy a store passed as a config", function() {
                            makeDestroyGrid({
                                store: {
                                    model: Model,
                                    id: 'x',
                                    autoDestroy: false,
                                    data: [{
                                        text: 'A'
                                    }, {
                                        text: 'B'
                                    }]
                                },
                                labelField: 'text'
                            });

                            if (rendered) {
                                showMenu();
                            }

                            runs(function() {
                                options = Ext.StoreManager.lookup('x');
                                grid.destroy();
                                expect(options.destroyed).toBe(false);
                                options.destroy();
                            });
                        });
                    });
                });
            }

            makeSuite(false);
            makeSuite(true);
        });

        describe("inferring from grid store", function() {
            describe("with data", function() {
                beforeEach(function() {
                    createGrid({}, {
                        data: [{
                            text: 't1'
                        }, {
                            text: 't1'
                        }, {
                            text: 't1'
                        }, {
                            text: 't2'
                        }, {
                            text: 't2'
                        }, {
                            text: 't3'
                        }, {
                            text: null
                        }, {
                            text: 't4'
                        }]
                    });
                });

                it("should not load the store", function() {
                    spyOn(store, 'load');
                    showMenu();
                    runs(function() {
                        expect(store.load).not.toHaveBeenCalled();
                    });
                });

                it("should use the unique dataIndex values as the menu text and exclude nulls", function() {
                    showMenu();

                    runs(function() {
                        expect(filterMenu.items.getCount()).toBe(4);
                        expect(filterMenu.items.getAt(0).text).toBe('t1');
                        expect(filterMenu.items.getAt(1).text).toBe('t2');
                        expect(filterMenu.items.getAt(2).text).toBe('t3');
                        expect(filterMenu.items.getAt(3).text).toBe('t4');
                    });
                });

                it("should use the dataIndex as the filter value", function() {
                    showMenu();

                    runs(function() {
                        expect(filterMenu.items.getCount()).toBeGreaterThan(0);
                        clickItem(1);

                        runs(function() {
                            var filter = store.getFilters().first();

                            expect(filter.getProperty()).toBe('text');
                            expect(filter.getOperator()).toBe('in');
                            expect(filter.getValue()).toEqual(['t2']);
                        });
                    });
                });

                describe("cleanup", function() {
                    it("should not destroy the store when destroying the grid", function() {
                        spyOn(store, 'destroy').andCallThrough();
                        grid.destroy();
                        expect(store.destroy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("with no data", function() {
                var loadData;

                beforeEach(function() {
                    loadData = [{
                        text: 't1'
                    }, {
                        text: 't1'
                    }, {
                        text: 't1'
                    }, {
                        text: 't2'
                    }, {
                        text: 't2'
                    }, {
                        text: 't3'
                    }, {
                        text: null
                    }, {
                        text: 't4'
                    }];

                    createGrid({}, {
                        data: null,
                        proxy: {
                            type: 'ajax',
                            url: 'fake'
                        }
                    });
                });

                it("should not load the store", function() {
                    spyOn(store, 'load');
                    showMenu();

                    runs(function() {
                        expect(store.load).not.toHaveBeenCalled();
                    });
                });

                it("should use the unique dataIndex values as the menu text and exclude nulls when the store loads", function() {
                    store.load();
                    showMenu();

                    runs(function() {
                        completeRequest(loadData);
                    });
                    waitsFor(function() {
                        return filterMenu.items.getCount() === 4;
                    });
                    runs(function() {
                        expect(filterMenu.items.getCount()).toBe(4);
                        expect(filterMenu.items.getAt(0).text).toBe('t1');
                        expect(filterMenu.items.getAt(1).text).toBe('t2');
                        expect(filterMenu.items.getAt(2).text).toBe('t3');
                        expect(filterMenu.items.getAt(3).text).toBe('t4');
                    });
                });

                it("should use the dataIndex as the filter value when the store loads", function() {
                    store.load();
                    showMenu();

                    runs(function() {
                        completeRequest(loadData);
                    });
                    clickItem(1);

                    runs(function() {
                        var filter = store.getFilters().first();

                        expect(filter.getProperty()).toBe('text');
                        expect(filter.getOperator()).toBe('in');
                        expect(filter.getValue()).toEqual(['t2']);
                    });
                });

                describe("cleanup", function() {
                    it("should not destroy the store when destroying the grid", function() {
                        store.load();
                        showMenu();

                        runs(function() {
                            completeRequest(loadData);
                        });

                        runs(function() {
                            spyOn(store, 'destroy').andCallThrough();
                            grid.destroy();
                            expect(store.destroy).not.toHaveBeenCalled();
                        });
                    });
                });
            });
        });

        describe("active config", function() {
            it("should be `false` by default", function() {
                createGrid();

                expect(listFilter.active).toBe(false);
            });

            describe("active === true", function() {
                describe("when no item defaults", function() {
                    describe("with a value", function() {
                        beforeEach(function() {
                            createGrid({
                                active: true,
                                value: ['Item 2', 'Item 3']
                            });
                        });

                        it("should honor the config when `true`", function() {
                            expect(listFilter.active).toBe(true);
                        });

                        it("should check the Filters menu item", function() {
                            showMenu();

                            runs(function() {
                                expect(filterItem.checked).toBe(true);
                            });
                        });

                        it("should check the option menu items specified by the value config", function() {
                            showMenu();
                            waitsFor(function() {
                                return filterMenu.items.getCount() === 12;
                            });
                            runs(function() {
                                expect(filterMenu.query('[checked]').length).toBe(2);
                                expect(filterMenu.down('[text="Item 1"]').checked).toBe(false);
                                expect(filterMenu.down('[text="Item 2"]').checked).toBe(true);
                                expect(filterMenu.down('[text="Item 3"]').checked).toBe(true);
                                expect(filterMenu.down('[text="Item 4"]').checked).toBe(false);
                            });
                        });

                        it("should create a store filter with the values in the value config", function() {
                            expect(listFilter.filter.getValue()).toEqual(['Item 2', 'Item 3']);
                        });

                        it("should add the filter to the store", function() {
                            expect(grid.store.getFilters().length).toBe(1);
                        });

                        it("should filter the grid store", function() {
                            var data = store.data;

                            expect(store.getCount()).toBe(2);
                            expect(data.getSource().length).toBe(12);
                            expect(data.filtered).toBe(true);
                        });

                        it("should not be the grid store", function() {
                            expect(listFilter.store).not.toBe(store);
                        });

                        /*
                        it("should not create a list store before list is initially shown", function() {
                            expect(listFilter.store).toBe(undefined);
                        });
                        */
                    });

                    describe("with an empty value", function() {
                        beforeEach(function() {
                            createGrid({
                                active: true,
                                value: []
                            });
                        });

                        it("should honor the config when `true`", function() {
                            expect(listFilter.active).toBe(true);
                        });

                        it("should check the Filters menu item", function() {
                            showMenu();

                            runs(function() {
                                expect(filterItem.checked).toBe(true);
                            });
                        });

                        it("should not check any of the option menu items", function() {
                            showMenu();

                            runs(function() {
                                expect(listFilter.menu.query('[checked]').length).toBe(0);
                            });
                        });

                        it("should create a store filter with no value", function() {
                            expect(listFilter.filter.getValue()).toEqual([]);
                        });

                        it("should add the filter to the store", function() {
                            expect(grid.store.getFilters().length).toBe(1);
                        });

                        it("should filter the grid store", function() {
                            var data = store.data;

                            expect(store.getCount()).toBe(0);
                            expect(data.getSource().length).toBe(12);
                            expect(data.filtered).toBe(true);
                        });

                        it("should not be the grid store", function() {
                            expect(listFilter.store).not.toBe(store);
                        });

                        /*
                        it("should not create a list store", function() {
                            expect(listFilter.store).toBe(undefined);
                        });
                        */
                    });

                    describe("without a value", function() {
                        beforeEach(function() {
                            createGrid({
                                active: true
                            });
                        });

                        it("should honor the config when `true`", function() {
                            expect(listFilter.active).toBe(true);
                        });

                        it("should check the Filters menu item", function() {
                            showMenu();

                            runs(function() {
                                expect(filterItem.checked).toBe(true);
                            });
                        });

                        it("should not check any of the option menu items", function() {
                            showMenu();

                            runs(function() {
                                expect(listFilter.menu.query('[checked]').length).toBe(0);
                            });
                        });

                        it("should create a store filter with no value", function() {
                            expect(listFilter.filter.getValue()).toEqual([]);
                        });

                        it("should add the filter to the store", function() {
                            expect(grid.store.getFilters().length).toBe(1);
                        });

                        it("should filter the grid store", function() {
                            var data = store.data;

                            expect(store.getCount()).toBe(0);
                            expect(data.getSource().length).toBe(12);
                            expect(data.filtered).toBe(true);
                        });

                        it("should not be the grid store", function() {
                            expect(listFilter.store).not.toBe(store);
                        });

                        it("should not create a list store", function() {
                            expect(listFilter.store).toBe(undefined);
                        });
                    });
                });
            });

            describe("active === false", function() {
                describe("when no item defaults", function() {
                    beforeEach(function() {
                        createGrid({
                            active: false
                        });
                    });

                    it("should honor the config when `false`", function() {
                        expect(listFilter.active).toBe(false);
                    });

                    it("should not check the Filters menu item", function() {
                        showMenu();

                        runs(function() {
                            expect(filterItem.checked).toBe(false);
                        });
                    });

                    it("should not check any of the menu items", function() {
                        showMenu();

                        runs(function() {
                            expect(listFilter.menu.query('[checked]').length).toBe(0);
                        });
                    });

                    it("should create an empty store filter", function() {
                        expect(listFilter.filter.getValue().length).toBe(0);
                    });

                    it("should not add a filter to the store", function() {
                        expect(grid.store.getFilters().length).toBe(0);
                    });

                    it("should not filter the grid store", function() {
                        expect(store.getCount()).toBe(12);
                        expect(store.data.filtered).toBe(false);
                    });
                });
            });
        });

        describe("keeping the list filter store and menu in sync with the grid store", function() {
            beforeEach(function() {
                createGrid();
                showMenu();
            });

            it("should update its list items when a model is updated", function() {
                var value = 'Pete the Dog';

                expect(listFilter.menu.items.getAt(0).getValue()).toBe('Item 1');
                store.getAt(0).set('text', value);
                expect(listFilter.menu.items.getAt(0).getValue()).toBe(value);
            });

            describe("changing the grid store dataset", function() {
                var items, i, len;

                beforeEach(function() {
                    tearDown();
                    setup();

                    createGrid({
                        dataIndex: 'type',
                        labelIndex: 'name'
                    }, {
                        data: [
                            { id: 101, name: 'Item 101', type: 't101' },
                            { id: 102, name: 'Item 102', type: 't102' },
                            { id: 103, name: 'Item 103', type: 't103' },
                            { id: 104, name: 'Item 104', type: 't104' }
                        ]
                    });

                    showMenu();

                    runs(function() {
                        items = listFilter.menu.items;

                        expect(items.length).toBe(4);

                        for (i = 0, len = items.length; i < len; i++) {
                            expect(items.getAt(i).getValue()).toBe('t' + (i + 101));
                        }
                    });
                });

                afterEach(function() {
                    items = i = len = null;
                });

                it("should update when reloaded", function() {
                    store.loadData([
                        { id: 101, name: 'Item 101', type: 't101' },
                        { id: 102, name: 'Item 102', type: 't102' },
                        { id: 103, name: 'Item 103', type: 't103' },
                        { id: 104, name: 'Item 104', type: 't104' },
                        { id: 105, name: 'Item 105', type: 't105' },
                        { id: 106, name: 'Item 106', type: 't106' }
                    ]);

                    expect(items.length).toBe(6);

                    for (i = 0, len = items.length; i < len; i++) {
                        expect(items.getAt(i).getValue()).toBe('t' + (i + 101));
                    }
                });

                it("should update when a record is added", function() {
                    store.add({
                        id: 105,
                        name: 'Item 105',
                        type: 't105'
                    });

                    expect(items.length).toBe(5);
                    expect(items.getAt(0).getValue()).toBe('t101');
                    expect(items.getAt(1).getValue()).toBe('t102');
                    expect(items.getAt(2).getValue()).toBe('t103');
                    expect(items.getAt(3).getValue()).toBe('t104');
                    expect(items.getAt(4).getValue()).toBe('t105');
                });

                it("should update when a record is removed", function() {
                    store.getAt(2).drop();

                    expect(items.length).toBe(3);
                    expect(items.getAt(0).getValue()).toBe('t101');
                    expect(items.getAt(1).getValue()).toBe('t102');
                    expect(items.getAt(2).getValue()).toBe('t104');
                });

                it("should not add to the menu items if a duplicate is added", function() {
                    store.add({
                        id: 105,
                        name: 'Item 105',
                        type: 't104'
                    });

                    expect(items.length).toBe(4);
                    expect(items.getAt(0).getValue()).toBe('t101');
                    expect(items.getAt(1).getValue()).toBe('t102');
                    expect(items.getAt(2).getValue()).toBe('t103');
                    expect(items.getAt(3).getValue()).toBe('t104');
                });
            });
        });
    });

    describe("recreating the list store and the filter menu items", function() {
        describe("with options", function() {
            it("should not react to store changes", function() {
                var items;

                createGrid({
                    options: ['Foo', 'Item 1', 'Item 2']
                });

                showMenu();

                runs(function() {
                    items = filterMenu.items;
                    expect(items.getAt(0).text).toBe('Foo');
                    expect(items.getAt(1).text).toBe('Item 1');
                    expect(items.getAt(2).text).toBe('Item 2');
                    hideMenu();

                    store.filter('id', 't1');
                });

                showMenu();

                runs(function() {
                    items = filterMenu.items;
                    expect(items.getAt(0).text).toBe('Foo');
                    expect(items.getAt(1).text).toBe('Item 1');
                    expect(items.getAt(2).text).toBe('Item 2');
                });
            });
        });

        describe("should not", function() {
            beforeEach(function() {
                createGrid();

                showMenu();

                runs(function() {
                    spyOn(listFilter, 'createListStore').andCallThrough();
                    spyOn(listFilter, 'createMenuItems').andCallThrough();
                });
            });

            it("should not recreate when sorting the grid store", function() {
                grid.store.sort();

                expect(listFilter.createListStore.callCount).toBe(0);
                expect(listFilter.createMenuItems.callCount).toBe(0);
            });

            describe("the Filters menu item", function() {
                it("should not recreate when unchecking the Filters menu item", function() {
                    jasmine.fireMouseEvent(filterItem.checkEl.dom, 'click');
                    expect(listFilter.createListStore.callCount).toBe(0);
                    expect(listFilter.createMenuItems.callCount).toBe(0);
                });

                it("should not refresh the grid view more than once per filter added", function() {
                    tearDown();
                    createGrid(null, null, {
                        columns: [{
                            dataIndex: 'id',
                            itemId: 'filterCol1',
                            filter: {
                                type: 'list'
                            }
                        }, {
                            dataIndex: 'text',
                            itemId: 'filterCol',
                            filter: {
                                type: 'list',
                                updateBuffer: 0
                            }
                        }]
                    });
                    var spy = jasmine.createSpy();

                    grid.view.on('refresh', spy);

                    showMenu(grid.down('#filterCol1'));

                    runs(function() {
                        jasmine.fireMouseEvent(filterItem.checkEl.dom, 'click');
                        filterMenu.hide();
                    });

                    showMenu(grid.down('#filterCol2'));

                    runs(function() {
                        jasmine.fireMouseEvent(filterItem.checkEl.dom, 'click');
                        expect(spy.callCount).toBe(2);
                    });
                });

                it("should not recreate when toggling the Filters menu item", function() {
                    // Uncheck.
                    jasmine.fireMouseEvent(filterItem.checkEl.dom, 'click');
                    // Check.
                    jasmine.fireMouseEvent(filterItem.checkEl.dom, 'click');
                    expect(listFilter.createListStore.callCount).toBe(0);
                    expect(listFilter.createMenuItems.callCount).toBe(0);
                });
            });

            describe("the filter menu item", function() {
                it("should not recreate when unchecking the Filters menu item", function() {
                    clickItem(2);

                    runs(function() {
                        expect(listFilter.createListStore.callCount).toBe(0);
                        expect(listFilter.createMenuItems.callCount).toBe(0);
                    });
                });

                it("should not recreate when toggling the Filters menu item", function() {
                    // Uncheck.
                    clickItem(2);
                    // Check.
                    clickItem(2);

                    runs(function() {
                        expect(listFilter.createListStore.callCount).toBe(0);
                        expect(listFilter.createMenuItems.callCount).toBe(0);
                    });
                });
            });
        });
    });

    describe("initializing the UI", function() {
        var responseData =  [{
                id: 't1',
                text: 'Item 1'
            }, {
                id: 't2',
                text: 'Item 2'
            }, {
                id: 't3',
                text: 'Item 3'
            }, {
                id: 't4',
                text: 'Item 4'
            }, {
                id: 't5',
                text: 'Item 5'
            }, {
                id: 't6',
                text: 'Item 6'
            }],
            total = responseData.length,
            options, nodeCount;

        afterEach(function() {
            nodeCount = null;
        });

        describe("when the options are inferred from the grid store", function() {
            function setActive(state) {
                describe("when active state is " + state, function() {
                    describe("when there is a value", function() {
                        beforeEach(function() {
                            createGrid({
                                active: state,
                                value: ['Item 1', 'Item 4']
                            }, {
                                data: null,
                                proxy: {
                                    type: 'ajax',
                                    url: 'fakeUrl'
                                }
                            });

                            store.load();
                            completeRequest(responseData);
                        });

                        describe("the view", function() {
                            it("should render a row for each record in the store", function() {
                                nodeCount = grid.view.all.count;
                                expect(nodeCount).toBe(store.data.length);
                                expect(nodeCount).toBe(state ? 2 : total);
                            });
                        });

                        describe("the gridfilters UI", function() {
                            it("should check the Filters menu item", function() {
                                showMenu();

                                runs(function() {
                                    expect(filterItem.checked).toBe(state);
                                });
                            });

                            it("should check each menu item in that is in the value config", function() {
                                showMenu();
                                waitsFor(function() {
                                    return filterMenu.items.getCount() === 6;
                                });
                                runs(function() {
                                    expect(filterMenu.down('[text="Item 1"]').checked).toBe(true);
                                    expect(filterMenu.down('[text="Item 2"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 3"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 4"]').checked).toBe(true);
                                    expect(filterMenu.down('[text="Item 5"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 6"]').checked).toBe(false);
                                });
                            });
                        });
                    });

                    describe("when there is an empty value", function() {
                        beforeEach(function() {
                            createGrid({
                                active: state,
                                value: []
                            }, {
                                data: null,
                                proxy: {
                                    type: 'ajax',
                                    url: 'fakeUrl'
                                }
                            });

                            store.load();
                            completeRequest(responseData);
                        });

                        describe("the view", function() {
                            it("should render a row for each record in the store", function() {
                                nodeCount = grid.view.all.count;
                                expect(nodeCount).toBe(store.data.length);
                                expect(nodeCount).toBe(state ? 0 : total);
                            });
                        });

                        describe("the gridfilters UI", function() {
                            it("should check the Filters menu item", function() {
                                showMenu();

                                runs(function() {
                                    expect(filterItem.checked).toBe(state);
                                });
                            });

                            it("should check each menu item in that is in the value config", function() {
                                showMenu();
                                waitsFor(function() {
                                    return filterMenu.items.getCount() === 6;
                                });
                                runs(function() {
                                    expect(filterMenu.query('[checked]').length).toBe(0);
                                    expect(filterMenu.down('[text="Item 1"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 2"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 3"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 4"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 5"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 6"]').checked).toBe(false);
                                });
                            });
                        });
                    });

                    describe("when there is not a value", function() {
                        beforeEach(function() {
                            createGrid({
                                active: state
                            }, {
                                data: null,
                                proxy: {
                                    type: 'ajax',
                                    url: 'fakeUrl'
                                }
                            });

                            store.load();
                            completeRequest(responseData);
                        });

                        describe("the view", function() {
                            it("should render a row for each record in the store", function() {
                                nodeCount = grid.view.all.count;
                                expect(nodeCount).toBe(store.data.length);
                                expect(nodeCount).toBe(state ? 0 : total);
                            });
                        });

                        describe("the gridfilters UI", function() {
                            it("should check the Filters menu item", function() {
                                showMenu();

                                runs(function() {
                                    expect(filterItem.checked).toBe(state);
                                });
                            });

                            it("should check each menu item in that is in the value config", function() {
                                showMenu();
                                waitsFor(function() {
                                    return filterMenu.items.getCount() === 6;
                                });
                                runs(function() {
                                    expect(filterMenu.query('[checked]').length).toBe(0);
                                    expect(filterMenu.down('[text="Item 1"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 2"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 3"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 4"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 5"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 6"]').checked).toBe(false);
                                });
                            });
                        });
                    });

                    describe("remoteFilter", function() {
                        it("should reload the store once when a list item is checked", function() {
                            createGrid({
                                active: state
                            }, {
                                remoteFilter: true,
                                data: null,
                                proxy: {
                                    type: 'ajax',
                                    url: 'fakeUrl'
                                }
                            });

                            store.load();
                            completeRequest(responseData);

                            // Trigger a filter and respond to the Ajax request  with some data
                            clickItem(1);

                            runs(function() {
                                completeRequest(responseData);

                                // Should NOT have updated the menu, which would blur and hide it.
                                // Part of https://sencha.jira.com/browse/EXTJS-19963
                                expect(filterMenu.isVisible()).toBe(true);

                                // The returning data from the filterChange should NOT have triggered
                                // another load. https://sencha.jira.com/browse/EXTJS-19963
                                expect(store.isLoading()).toBe(false);
                            });
                        });
                    });
                });
            }

            setActive(true);
            setActive(false);
        });

        describe("when the options are created from the filter store config", function() {
            function makeStore(cfg) {
                return new Ext.data.ArrayStore(Ext.apply({
                    model: Model,
                    data: [
                        ['t1', 'Item 1'],
                        ['t2', 'Item 2'],
                        ['t3', 'Item 3'],
                        ['t4', 'Item 4']
                    ]
                }, cfg));
            }

            afterEach(function() {
                options = Ext.destroy(options);
            });

            function setActive(state) {
                describe("when active state is " + state, function() {
                    describe("when there is a value", function() {
                        beforeEach(function() {
                            options = makeStore();

                            createGrid({
                                active: state,
                                store: options,
                                // Here we need to tell the filter on which field to filter...
                                dataIndex: 'id',
                                // ...and which field should display the menu item label.
                                labelIndex: 'text',
                                value: ['t3', 't4']
                            });

                            store.load();
                            completeRequest(responseData);
                        });

                        describe("the view", function() {
                            it("should render a row for each record in the store", function() {
                                nodeCount = grid.view.all.count;
                                expect(nodeCount).toBe(store.data.length);
                                expect(nodeCount).toBe(state ? 2 : total);
                            });
                        });

                        describe("the gridfilters UI", function() {
                            it("should check the Filters menu item", function() {
                                showMenu();

                                runs(function() {
                                    expect(filterItem.checked).toBe(state);
                                });
                            });

                            it("should check each menu item in the Filters menu", function() {
                                showMenu();
                                waitsFor(function() {
                                    return filterMenu.items.getCount() === 4;
                                });
                                runs(function() {
                                    expect(filterMenu.query('[checked]').length).toBe(2);
                                    expect(filterMenu.down('[text="Item 1"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 2"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 3"]').checked).toBe(true);
                                    expect(filterMenu.down('[text="Item 4"]').checked).toBe(true);
                                });
                            });
                        });
                    });

                    describe("when there is an empty value", function() {
                        beforeEach(function() {
                            options = makeStore();

                            createGrid({
                                active: state,
                                store: options,
                                value: []
                            });

                            store.load();
                            completeRequest(responseData);
                        });

                        describe("the view", function() {
                            it("should render a row for each record in the store", function() {
                                nodeCount = grid.view.all.count;
                                expect(nodeCount).toBe(store.data.length);
                                expect(nodeCount).toBe(state ? 0 : total);
                            });
                        });

                        describe("the gridfilters UI", function() {
                            it("should check the Filters menu item", function() {
                                showMenu();

                                runs(function() {
                                    expect(filterItem.checked).toBe(state);
                                });
                            });

                            it("should check each menu item in the Filters menu", function() {
                                showMenu();
                                waitsFor(function() {
                                    return filterMenu.items.getCount() === 4;
                                });
                                runs(function() {
                                    expect(filterMenu.query('[checked]').length).toBe(0);
                                    expect(filterMenu.down('[text="Item 1"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 2"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 3"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 4"]').checked).toBe(false);
                                });
                            });
                        });
                    });

                    describe("when there is not a value", function() {
                        beforeEach(function() {
                            options = makeStore();

                            createGrid({
                                active: state,
                                store: options
                            });

                            store.load();
                            completeRequest(responseData);
                        });

                        describe("the view", function() {
                            it("should render a row for each record in the store", function() {
                                nodeCount = grid.view.all.count;
                                expect(nodeCount).toBe(store.data.length);
                                expect(nodeCount).toBe(state ? 0 : total);
                            });
                        });

                        describe("the gridfilters UI", function() {
                            it("should check the Filters menu item", function() {
                                showMenu();

                                runs(function() {
                                    expect(filterItem.checked).toBe(state);
                                });
                            });

                            it("should check each menu item in the Filters menu", function() {
                                showMenu();
                                waitsFor(function() {
                                    return filterMenu.items.getCount() === 4;
                                });
                                runs(function() {
                                    expect(filterMenu.query('[checked]').length).toBe(0);
                                    expect(filterMenu.down('[text="Item 1"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 2"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 3"]').checked).toBe(false);
                                    expect(filterMenu.down('[text="Item 4"]').checked).toBe(false);
                                });
                            });
                        });
                    });
                });
            }

            setActive(true);
            setActive(false);
        });
    });

    describe("showing the menu", function() {
        describe("should work", function() {
            describe("remoteFilter", function() {
                function doTest(remoteFilter, value) {
                    it("should show regardless of `remoteFilter` value (remoteFilter = " + remoteFilter + ')', function() {
                        createGrid({
                            value: value
                        }, {
                            remoteFilter: remoteFilter
                        });

                        expect(function() {
                            showMenu();
                        }).not.toThrow();
                    });
                }

                describe("when store is not filtered", function() {
                    doTest(false, null);
                    doTest(true, null);
                });

                describe("when store is filtered", function() {
                    doTest(false, 'Item 5');
                    doTest(true, 'Item 5');
                });
            });
        });

        describe("the active state", function() {
            var len;

            afterEach(function() {
                len = null;
            });

            function setActive(state) {
                it("should not add a possible additional filter to the store when shown, active state = " + state, function() {
                    createGrid({
                        active: state,
                        value: ['Item 1', 'Item 4']
                    });

                    len = store.getFilters().getCount();
                    showMenu();

                    runs(function() {
                        expect(len).toBe(store.getFilters().getCount());
                    });
                });
            }

            setActive(true);
            setActive(false);
        });

        describe("the list store", function() {
            beforeEach(function() {
                createGrid({
                    value: ['Item 2', 'Item 3']
                });

                clickItem(1);
            });

            it("should create the list store", function() {
                expect(listFilter.store).toBeDefined();
            });

            it("should create a list store with the same number of records as there are unique elements in the grid store", function() {
                expect(listFilter.store.data.length).toEqual(store.collect('text', true, true).length);
            });
        });
    });

    describe("statefulness", function() {
        function makeUI() {
            createGrid(null, null, {
                stateful: true,
                stateId: 'pozzuoli'
            });

            showMenu();
        }

        beforeEach(function() {
            new Ext.state.Provider();
            makeUI();
        });

        afterEach(function() {
            Ext.state.Manager.getProvider().clear();
        });

        it("should retain selections", function() {
            expect(filterMenu.query('[checked]').length).toBe(0);

            clickItem(0);
            clickItem(3);

            runs(function() {
                expect(filterMenu.query('[checked]').length).toBe(2);
                expect(filterMenu.items.getAt(0).checked).toBe(true);
                expect(filterMenu.items.getAt(3).checked).toBe(true);

                grid.saveState();
                grid.destroy();

                makeUI();
            });

            runs(function() {
                expect(filterMenu.query('[checked]').length).toBe(2);
                expect(filterMenu.items.getAt(0).checked).toBe(true);
                expect(filterMenu.items.getAt(3).checked).toBe(true);
            });
        });
    });

    describe("reconfiguring", function() {
        it("should not try to bind a null value", function() {
            createGrid();

            expect(function() {
                grid.reconfigure(null, [{
                    dataIndex: 'text',
                    filter: {
                        type: 'list',
                        value: 'foo'
                    }
                }]);
            }).not.toThrow();
        });
    });

    describe("with store sorting", function() {
        it("should not clear any filters when sorting", function() {
            createGrid({
                value: ['Item 2']
            });
            clickItem(0);
            runs(function() {
                hideMenu();
            });
            waitsFor(function() {
                return !columnMenu.isVisible();
            });
            runs(function() {
                expect(store.getCount()).toBe(2);
                store.sort({
                    property: 'id',
                    direction: 'ASC'
                });
                expect(store.getCount()).toBe(2);
                store.sort({
                    property: 'id',
                    direction: 'DESC'
                });
                expect(store.getCount()).toBe(2);
            });
        });
    });
});
