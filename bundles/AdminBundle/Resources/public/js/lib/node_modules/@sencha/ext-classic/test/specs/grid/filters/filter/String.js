topSuite("Ext.grid.filters.filter.String",
    ['Ext.grid.Panel', 'Ext.grid.filters.Filters'],
function() {
    var grid, store, plugin, columnFilter, menu,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function createGrid(listCfg, storeCfg, gridCfg) {
        synchronousLoad = false;
        store = new Ext.data.Store(Ext.apply({
            fields: ['name', 'email', 'phone'],
            data: [
                { name: 'Lisa',  email: 'lisa@simpsons.com',  phone: '555-111-1224' },
                { name: 'Bart',  email: 'bart@simpsons.com',  phone: '555-222-1234' },
                { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
                { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
            ]
        }, storeCfg));

        grid = new Ext.grid.Panel(Ext.apply({
            title: 'Simpsons',
            store: store,
            autoLoad: true,
            columns: [{
                dataIndex: 'name',
                filter: Ext.apply({
                    type: 'string'
                }, listCfg),
                width: 100
            }, {
                dataIndex: 'email',
                width: 100
            }, {
                dataIndex: 'phone',
                width: 100,
                hidden: true
            }],

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

        plugin = grid.filters;
        columnFilter = grid.headerCt.columnManager.getHeaderByDataIndex('name').filter;
        synchronousLoad = true;
        store.flushLoad();
    }

    function showFilterMenu() {
        var headerCt = grid.headerCt,
            filtersCheckItem,
            header = grid.columnManager.getFirst();

        // Show the grid menu.
        headerCt.showMenuBy(null, header.triggerEl.dom, header);

        // Show the filter menu.
        filtersCheckItem = headerCt.menu.items.last();
        filtersCheckItem.activated = true;
        filtersCheckItem.expandMenu(null, 0);

        menu = filtersCheckItem.menu;
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    function tearDown() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        Ext.destroy(store, grid);
        grid = store = plugin = columnFilter = menu = null;
    }

    afterEach(tearDown);

    describe("events", function() {
        describe("keyup", function() {
            it("should hide the menu", function() {
                var field;

                createGrid();
                showFilterMenu();

                field = columnFilter.inputItem;
                field.setValue('Molly');
                jasmine.fireKeyEvent(field.inputEl, 'keyup', 13);

                waitsFor(function() {
                    return menu.hidden;
                });

                runs(function() {
                    expect(menu.hidden).toBe(true);
                });
            });
        });
    });

    describe("updateBuffer", function() {
        // NOTE that teses tests were failing randomly, almost exclusively on older builds of
        // FF and older IE, with times coming in anywhere from 50 - 100 ms below the expected
        // thresholds.  Because of this, we're going to set our expectations even lower for
        // these browsers (haha i made a joke).
        var ms, startTime, endTime, field;

        function initiateFilter(ms) {
            expect(columnFilter.updateBuffer).toBe(ms);
            showFilterMenu();
        }

        beforeEach(function() {
            spyOn(Ext.grid.filters.filter.String.prototype, 'setValue').andCallFake(function() {
                endTime = new Date().getTime();
            });
        });

        afterEach(function() {
            ms = startTime = endTime = field = null;
        });

        it("should default to 500ms", function() {
            ms = 500;
            createGrid();
            initiateFilter(ms);

            field = columnFilter.inputItem;
            startTime = new Date().getTime();
            jasmine.fireKeyEvent(field.inputEl, 'keyup', 83);

            waitsFor(function() {
                return endTime;
            });

            runs(function() {
                expect(endTime - startTime).toBeAtLeast(ms - 100);
            });
        });

        it("should honor a configured updateBuffer", function() {
            // Let's choose something well below the default and then just check to make
            // sure that's it's less than the default. This is safe since we don't know
            // exactly when the callback will be fired, but it still demonstrates that
            // the updateBuffer config is variable.
            ms = 250;
            createGrid({
                updateBuffer: ms
            });

            initiateFilter(ms);

            field = columnFilter.inputItem;
            startTime = new Date().getTime();
            jasmine.fireKeyEvent(field.inputEl, 'keyup', 83);

            waitsFor(function() {
                return endTime;
            });

            runs(function() {
                var timer = (endTime - startTime);

                expect(timer).toBeAtLeast(ms - 100);
                expect(timer).toBeLE(Ext.grid.filters.filter.Base.prototype.config.updateBuffer);
            });
        });
    });

    describe("removing store filters, single filter", function() {
        // Note that it should only call the onFilterRemove handler if the gridfilters API created the store filter.
        beforeEach(function() {
            // In short: Removing a store filter on the store itself will trigger the listener bound by the gridfilters API.
            // This was throwing an exception, b/c the delegated handler in the Date filter class was expecting that the
            // menu had already been created.
            // See EXTJS-16071.
            createGrid();
            spyOn(columnFilter, 'onFilterRemove');

            // Adding a filter with the same property name as that of a column filter will setup the bug.
            store.getFilters().add({ property: 'name', value: 'Camp Hill' });
        });

        it("should not throw if removing filters directly on the bound store", function() {
            expect(function() {
                // Trigger the bug by clearing filters directly on the store.
                store.clearFilter();
            }).not.toThrow();
        });

        it("should not call through to the delegated handler if the store filter was not generated by the class", function() {
            store.clearFilter();

            expect(columnFilter.onFilterRemove).not.toHaveBeenCalled();
        });

        it("should not call through to the delegated handler when the store filter is replaced", function() {
            plugin.addFilter({
                dataIndex: 'name',
                value: 'Princeton'
            });

            store.clearFilter();

            expect(columnFilter.onFilterRemove).not.toHaveBeenCalled();
        });

        it("should call through to the delegated handler when the store filter was generated by the class (when menu has been created)", function() {
            // This should call the handler because the gridfilters API created the store filter.
            tearDown();
            createGrid({
                value: 'Homer'
            });

            showFilterMenu();

            spyOn(columnFilter, 'onFilterRemove');

            // Usually, this new filter would be added via an action triggered by a UI event.
            columnFilter.addStoreFilter(new Ext.util.Filter({
                id: 'x-gridfilter-name',
                property: 'name',
                operator: 'like',
                value: 'Lily'
            }));

            expect(columnFilter.onFilterRemove).toHaveBeenCalled();
        });
    });

    describe("adding a column filter, single filter", function() {
        describe("replacing an existing column filter", function() {
            // See EXTJS-16082.
            it("should not throw", function() {
                createGrid();

                expect(function() {
                    plugin.addFilter({
                        type: 'string',
                        value: 'ben germane'
                    });
                }).not.toThrow();
            });

            it("should replace the existing store filter", function() {
                var filters, filter, basePrefix;

                createGrid({
                    value: 'Marge'
                });

                basePrefix = columnFilter.getBaseIdPrefix();
                filters = store.getFilters();
                filter = filters.getAt(0);

                // Show that it has the configured store filter in the collection.
                expect(filters.length).toBe(1);
                expect(filter.getId()).toBe(basePrefix);
                expect(filter.getValue()).toBe('Marge');

                // Now create the new column and check again.
                plugin.addFilter({
                    type: 'string',
                    dataIndex: 'name',
                    value: 'attaboy'
                });

                filter = filters.getAt(0);

                expect(filters.length).toBe(1);
                expect(filter.getId()).toBe(basePrefix);
                expect(filter.getValue()).toBe('attaboy');
            });
        });
    });

    describe("showing the menu", function() {
        function setActive(state) {
            it("should not add a filter to the store when shown " + (state ? 'active' : 'inactive'), function() {
                createGrid({
                    active: state,
                    updateBuffer: 0,
                    value: 'Asbury Park'
                });

                spyOn(columnFilter, 'addStoreFilter');

                showFilterMenu();
                expect(columnFilter.addStoreFilter).not.toHaveBeenCalled();
            });
        }

        setActive(true);
        setActive(false);
    });
});
