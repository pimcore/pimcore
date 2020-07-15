topSuite("Ext.grid.filters.filter.Date",
    ['Ext.grid.Panel', 'Ext.grid.filters.Filters'],
function() {
    var grid, plugin, store, columnFilter, menu, activeItem, headerCt, rootMenuItem, datepicker,
        pickerEl, headerNode, selectedNode, before, after, on,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function createGrid(listCfg, gridCfg, storeCfg) {
        synchronousLoad = false;
        store = new Ext.data.Store(Ext.apply({
            fields: ['name', 'email', 'phone', { name: 'dob', type: 'date' }],
            data: [
                { name: 'evan', dob: Ext.Date.parse('1992-12-12T12:30:01', 'c') },
                { name: 'nige', dob: Ext.Date.parse('1992-12-11T11:30:01', 'c') },
                { name: 'phil', dob: Ext.Date.parse('1992-12-10T10:30:01', 'c') },
                { name: 'don',  dob: Ext.Date.parse('1992-12-09T09:30:01', 'c') },
                { name: 'alex', dob: Ext.Date.parse('1992-12-08T08:30:01', 'c') },
                { name: 'ben',  dob: Ext.Date.parse('1992-12-08T07:30:01', 'c') }
            ],
            autoDestroy: true
        }, storeCfg));

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            autoLoad: true,
            columns: [
                { dataIndex: 'name', width: 100 },
                { dataIndex: 'dob', width: 100,
                    filter: Ext.apply({
                        type: 'date',
                        updateBuffer: 0
                    }, listCfg)
                }
            ],
            plugins: 'gridfilters',
            height: 200,
            width: 400,
            renderTo: Ext.getBody()
        }, gridCfg));

        plugin = grid.filters;
        columnFilter = grid.columnManager.getHeaderByDataIndex('dob').filter;
        plugin = grid.filters;
        synchronousLoad = true;
        store.flushLoad();
    }

    function setPicker(val) {
        // Set the value and use the selected classname to find and fire the event.
        datepicker.setValue(new Date(val));
        jasmine.fireMouseEvent(datepicker.eventEl.down('.x-datepicker-selected div', true), 'click');
    }

    function showMenu() {
        var header = grid.getColumnManager().getLast();

        menu = header.getRootHeaderCt().getMenu();

        // Show the main grid menu.
        Ext.testHelper.showHeaderMenu(header);

        runs(function() {
            headerCt = grid.headerCt;

            // Show the filter menu.
            rootMenuItem = headerCt.menu.items.last();
            rootMenuItem.activated = true;
            rootMenuItem.expandMenu(null, 0);

            menu = rootMenuItem.menu;
        });
    }

    function showPicker(which) {
        showMenu();

        runs(function() {
            var items = {
                    Before: {
                        text: 'Before',
                        priority: 3
                    },
                    After: {
                        text: 'After',
                        priority: 2
                    },
                    On: {
                        text: 'On',
                        priority: 1
                    }
                },
                itemsArray = [items.Before, items.After, items.On],
                i, item;

            if (which) {
                // Sort requested "which" item to end so that
                // we can end with it visible.
                items[which].priority = 1e99;
                Ext.Array.sort(itemsArray, function(lhs, rhs) {
                    lhs = lhs.priority;
                    rhs = rhs.priority;

                    return (lhs < rhs) ? -1 : ((lhs > rhs) ? 1 : 0);
                });
            }

            activeItem = null;

            // Collect each DatePicker, the on, after and before.
            // The last one will be left visible because if they asked
            // for one, that will have been sorted to the end.
            for (i = 0; i < 3; i++) {
                if (activeItem) {
                    activeItem.menu.hide();
                    activeItem.activated = false;
                }

                item = itemsArray[i];
                activeItem = menu.down('[text="' + item.text + '"]');
                activeItem.activated = true;
                activeItem.expandMenu(null, 0);

                switch (item.text) {
                    case "On":
                        on = activeItem.menu.down('datepicker');
                        break;
                    case "After":
                        after = activeItem.menu.down('datepicker');
                        break;
                    case "Before":
                        before = activeItem.menu.down('datepicker');
                }
            }

            // Leave the last one visible and collect its details
            // if they asked for a visible one.
            if (which) {
                datepicker = activeItem.menu.down('datepicker');
                pickerEl = datepicker.el;
                headerNode = pickerEl.down('.x-datepicker-header', true);
                selectedNode = pickerEl.down('.x-datepicker-selected', true);
                waitsForFocus(datepicker);
            }
            else {
                activeItem.menu.hide();
                activeItem.activated = false;
            }
        });
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    function tearDown() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;
        Ext.destroy(store, grid);

        grid = plugin = store = columnFilter = menu = headerCt = rootMenuItem = null;
        datepicker = pickerEl = headerNode = selectedNode = before = after = on = null;
    }

    afterEach(tearDown);

    describe("init", function() {
        it("should add a menu separator to the menu", function() {
            createGrid();
            showMenu();

            runs(function() {
                expect(menu.down('menuseparator')).not.toBeNull();
            });
        });
    });

    describe("setValue", function() {
        var parse = Ext.Date.parse;

        it("should filter the store regardless of whether the menu has been created", function() {
            createGrid();

            expect(store.data.length).toBe(6);
            columnFilter.setValue({ eq: Ext.Date.parse('1992-12-08T07:30:01', 'c') });
            expect(store.data.length).toBe(2);
        });

        it("should update the value of the date whenever called", function() {
            // See EXTJS-11532.
            createGrid();

            columnFilter.createMenu();

            columnFilter.setValue({ eq: parse('08/08/1992', 'd/m/Y') });
            columnFilter.setValue({ eq: parse('26/09/2009', 'd/m/Y') });

            expect(columnFilter.filter.eq.getValue()).toEqual(parse('26/09/2009', 'd/m/Y'));
        });
    });

    describe("the filter", function() {
        it("should serialize the filter according to the dateFormat", function() {
            createGrid();
            columnFilter.setDateFormat('Y/m/d');
            columnFilter.createMenu();
            var d = new Date(2010, 0, 1);

            columnFilter.setValue({
                lt: d
            });
            expect(store.getFilters().first().serialize().value).toBe('2010/01/01');
        });

        it("should only compare the date part when using the before filter", function() {
            createGrid(null, null, {
                remoteFilter: false
            });
            columnFilter.createMenu();
            var d = new Date(1992, 11, 9);

            columnFilter.setValue({
                lt: d
            });
            expect(store.getCount()).toBe(2);
            expect(store.getAt(0).get('name')).toBe('alex');
            expect(store.getAt(1).get('name')).toBe('ben');
        });

        it("should only compare the date part when using the after filter", function() {
            createGrid(null, null, {
                remoteFilter: false
            });
            columnFilter.createMenu();
            var d = new Date(1992, 11, 9);

            columnFilter.setValue({
                gt: d
            });
            expect(store.getCount()).toBe(3);
            expect(store.getAt(0).get('name')).toBe('evan');
            expect(store.getAt(1).get('name')).toBe('nige');
            expect(store.getAt(2).get('name')).toBe('phil');
        });

        it("should only compare the date part when using the on filter", function() {
            createGrid(null, null, {
                remoteFilter: false
            });
            columnFilter.createMenu();
            var d = new Date(1992, 11, 9);

            columnFilter.setValue({
                eq: d
            });
            expect(store.getCount()).toBe(1);
            expect(store.getAt(0).get('name')).toBe('don');
        });
    });

    describe("onMenuSelect handler and setFieldValue", function() {
        it("should correctly filter based upon picker selections", function() {
            createGrid();
            showPicker('Before');

            runs(function() {
                setPicker('12/10/1992');
                expect(store.getCount()).toBe(3);

                setPicker('12/12/1992');
                expect(store.getCount()).toBe(5);
            });
        });
    });

    describe("removing store filters, tri-filter", function() {
        // Note that it should only call the onFilterRemove handler if the gridfilters API created the store filter.
        beforeEach(function() {
            // In short: Removing a store filter on the store itself will trigger the listener bound by the gridfilters API.
            // This was throwing an exception, b/c the delegated handler in the Date filter class was expecting that the
            // menu had already been created.
            // See EXTJS-16071.
            createGrid();

            // Adding a filter with the same property name as that of a column filter will setup the bug.
            store.getFilters().add({ property: 'dob', value: { eq: new Date() } });
        });

        it("should not throw if removing filters directly on the bound store", function() {
            expect(function() {
                // Trigger the bug by clearing filters directly on the store.
                store.clearFilter();
            }).not.toThrow();
        });

        it("should not call through to the delegated handler if the store filter was not generated by the class", function() {
            spyOn(columnFilter, 'onFilterRemove');
            store.clearFilter();

            expect(columnFilter.onFilterRemove).not.toHaveBeenCalled();
        });

        it("should not call through to the delegated handler when the store filter is replaced", function() {
            spyOn(columnFilter, 'onFilterRemove');
            plugin.addFilter(new Ext.util.Filter({
                type: 'date',
                dataIndex: 'dob',
                value: {
                    eq: new Date()
                }
            }));

            store.clearFilter();

            expect(columnFilter.onFilterRemove).not.toHaveBeenCalled();
        });

        it("should replace the current filter if we add a new one with same id and different value", function() {
            // This should call the handler because the gridfilters API created the store filter.
            tearDown();
            createGrid({
                value: {
                    eq: Ext.Date.parse('1992-12-12T12:30:01', 'c')
                }
            });

            showMenu();

            runs(function() {
                // Usually, this new filter would be added via an action triggered by a UI event.
                columnFilter.addStoreFilter(new Ext.util.Filter({
                    id: 'x-gridfilter-dob-eq',
                    property: 'dob',
                    operator: 'eq',
                    value: Ext.Date.parse('1972-12-12T12:30:01', 'c')
                }));

                expect(grid.store.getFilters().length).toBe(1);
                expect(grid.store.getFilters().getAt(0).getValue()).toEqual(Ext.Date.parse('1972-12-12T12:30:01', 'c'));
            });
        });
    });

    describe("adding a column filter, tri-filter", function() {
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
                var filters, filter, basePrefix,
                    date = Ext.Date.parse('1992-12-08T07:30:01', 'c'),
                    date2 = Ext.Date.parse('1992-12-10T10:30:01', 'c');

                createGrid({
                    value: {
                        eq: date
                    }
                });

                basePrefix = columnFilter.getBaseIdPrefix() + '-eq';
                filters = store.getFilters();
                filter = filters.getAt(0);

                // Show that it has the configured store filter in the collection.
                expect(filters.length).toBe(1);
                expect(filter.getId()).toBe(basePrefix);
                expect(filter.getValue()).toBe(date);

                // Now create the new column and check again.
                plugin.addFilter({
                    type: 'date',
                    dataIndex: 'dob',
                    value: {
                        eq: date2
                    }
                });

                filter = filters.getAt(0);

                expect(filters.length).toBe(1);
                expect(filter.getId()).toBe(basePrefix);
                expect(filter.getValue()).toBe(date2);
            });
        });
    });

    describe("showing the menu", function() {
        function setActive(state) {
            it("should not add a filter to the store when shown " + (state ? 'active' : 'inactive'), function() {
                createGrid({
                    active: state,
                    value: {
                        eq: new Date()
                    }
                });

                spyOn(columnFilter, 'addStoreFilter');

                showMenu();

                runs(function() {
                    expect(columnFilter.addStoreFilter).not.toHaveBeenCalled();
                });
            });
        }

        setActive(true);
        setActive(false);
    });

    describe("clearing filters", function() {
        it("should not recheck the root menu item (\"Filters\") when showing menu after clearing filters", function() {
            createGrid();
            showMenu();

            runs(function() {
                columnFilter.setValue({
                    lt: new Date()
                });

                expect(rootMenuItem.checked).toBe(true);

                // Now, let's hide the menu and clear the filters, which will deactivate all the filters.
                // Note that it's not enough to check the root menu item's checked state, we must show the menu again.
                headerCt.getMenu().hide();
                plugin.clearFilters();
            });

            showMenu();

            runs(function() {
                expect(rootMenuItem.checked).toBe(false);
            });
        });
    });

    describe("selecting using the UI", function() {
        var filters;

        afterEach(function() {
            filters = null;
        });

        describe("the After datepicker", function() {
            function afterAndBefore() {
                showPicker('Before');

                runs(function() {
                    setPicker('12/8/2014');

                    expect(after.up('menuitem').checked).toBe(true);
                    expect(before.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(2);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-gt');
                    expect(filters.getAt(1).getId()).toBe(columnFilter.getBaseIdPrefix() + '-lt');
                });
            }

            function selectOn() {
                // Now select a date that would result in an empty result set.
                showPicker('On');

                runs(function() {
                    setPicker('9/26/2009');

                    // We expect that the On selection will disable and deactivate the previous selection
                    // because we don't currently support OR operations.
                    expect(after.up('menuitem').checked).toBe(false);

                    if (before) {
                        expect(before.up('menuitem').checked).toBe(false);
                    }

                    expect(on.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(1);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-eq');
                });
            }

            beforeEach(function() {
                createGrid();

                showPicker('After');

                runs(function() {
                    setPicker('8/8/1992');
                    filters = store.getFilters();
                });
            });

            it("should enable and activate after a selection is made", function() {
                expect(after.up('menuitem').checked).toBe(true);
                expect(filters.length).toBe(1);
                expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-gt');
            });

            it("should support the enabling and activating of the Before bits if a supported Before selection is made", function() {
                afterAndBefore();
            });

            it("should disable and deactivate the After bits if an unsupported Before selection is made", function() {
                // Now select a date that would result in an empty result set.
                showPicker('Before');

                runs(function() {
                    setPicker('8/7/1992');

                    // We expect that the Before selection will disable and deactivate the previous selection
                    // because we don't currently support OR operations.
                    expect(after.up('menuitem').checked).toBe(false);
                    expect(before.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(1);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-lt');
                });
            });

            it("should disable and deactivate the After bits if an On selection is made", function() {
                selectOn();
            });

            it("should disable and deactivate the After and Before bits if an On selection is made", function() {
                afterAndBefore();
                selectOn();
            });
        });

        describe("the Before datepicker", function() {
            function afterAndBefore() {
                showPicker('After');

                runs(function() {
                    setPicker('8/8/1992');

                    expect(after.up('menuitem').checked).toBe(true);
                    expect(before.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(2);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-lt');
                    expect(filters.getAt(1).getId()).toBe(columnFilter.getBaseIdPrefix() + '-gt');
                });
            }

            function selectOn() {
                // Now select a date that would result in an empty result set.
                showPicker('On');

                runs(function() {
                    setPicker('9/26/2009');

                    // We expect that the On selection will disable and deactivate the previous selection
                    // because we don't currently support OR operations.
                    expect(before.up('menuitem').checked).toBe(false);

                    if (after) {
                        expect(after.up('menuitem').checked).toBe(false);
                    }

                    expect(on.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(1);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-eq');
                });
            }

            beforeEach(function() {
                createGrid();
                showPicker('Before');

                runs(function() {
                    setPicker('12/8/2014');
                    filters = store.getFilters();
                });
            });

            it("should enable and activate after a selection is made", function() {
                expect(before.up('menuitem').checked).toBe(true);
                expect(filters.length).toBe(1);
                expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-lt');
            });

            it("should support the enabling and activating of the Before bits if a supported Before selection is made", function() {
                afterAndBefore();
            });

            it("should disable and deactivate the Before bits if an unsupported After selection is made", function() {
                // Now select a date that would result in an empty result set.
                showPicker('After');

                runs(function() {
                    setPicker('12/9/2014');

                    // We expect that the After selection will disable and deactivate the previous selection
                    // because we don't currently support OR operations.
                    expect(before.up('menuitem').checked).toBe(false);
                    expect(after.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(1);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-gt');
                });
            });

            it("should disable and deactivate the Before bits if an On selection is made", function() {
                selectOn();
            });

            it("should disable and deactivate the After and Before bits if an On selection is made", function() {
                afterAndBefore();
                selectOn();
            });
        });

        describe("the On datepicker", function() {
            function afterOrBefore(which) {
                // Making a selection on either the After or Before datepicker should disable and deactivate the On datepicker.
                showPicker(which);

                runs(function() {
                    setPicker('8/8/1992');
                    expect(activeItem.up('menuitem').checked).toBe(true);
                    expect(on.up('menuitem').checked).toBe(false);
                    expect(filters.length).toBe(1);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + (which === 'After' ? '-gt' : '-lt'));
                });
            }

            function selectOn() {
                // Now select a date that would result in an empty result set.
                showPicker('On');

                runs(function() {
                    setPicker('9/26/2009');
                    // We expect that the On selection will disable and deactivate the previous selection
                    // because we don't currently support OR operations.
                    expect(before.up('menuitem').checked).toBe(false);

                    if (after) {
                        expect(after.up('menuitem').checked).toBe(false);
                    }

                    expect(on.up('menuitem').checked).toBe(true);
                    expect(filters.length).toBe(1);
                    expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-eq');
                });
            }

            beforeEach(function() {
                createGrid();
                on = showPicker('On');

                runs(function() {
                    setPicker('1/22/1972');
                    filters = store.getFilters();
                });
            });

            it("should enable and activate after a selection is made", function() {
                expect(on.up('menuitem').checked).toBe(true);
                expect(filters.length).toBe(1);
                expect(filters.getAt(0).getId()).toBe(columnFilter.getBaseIdPrefix() + '-eq');
            });

            it("should disable and deactivate the On bits if an After selection is made", function() {
                afterOrBefore('After');
            });

            it("should disable and deactivate the On bits if an Before selection is made", function() {
                afterOrBefore('Before');
            });

            // Note that there a specs in the After and Before suites above that test what happens when an ON
            // selection is made after either or both of the After and Before datepickers have been set so
            // I'm not duplicating them here.
        });
    });

    describe("the UI and the active state", function() {
        function setActive(active) {
            describe("when " + active, function() {
                var maybe = !active ? 'not' : '';

                it("should " + maybe + ' check the Filters menu item', function() {
                    createGrid({
                        active: active
                    });

                    showMenu();

                    runs(function() {
                        expect(rootMenuItem.checked).toBe(active);
                    });
                });

                it("should set any field values that map to a configured value, Before and After", function() {
                    createGrid({
                        active: active,
                        value: {
                            lt: Ext.Date.parse('1992-12-12T12:30:01', 'c'),
                            gt: Ext.Date.parse('1992-12-08T07:30:01', 'c')
                        }
                    });

                    showPicker('Before');

                    runs(function() {
                        expect((headerNode.textContent || headerNode.innerText).replace(/\s/g, '')).toBe('December1992');
                        expect((selectedNode.textContent || selectedNode.innerText).replace(/\s/g, '')).toBe('12');
                    });

                    showPicker('After');

                    runs(function() {
                        expect((headerNode.textContent || headerNode.innerText).replace(/\s/g, '')).toBe('December1992');
                        expect((selectedNode.textContent || selectedNode.innerText).replace(/\s/g, '')).toBe('8');
                    });
                });

                it("should set any field values that map to a configured value, On", function() {
                    createGrid({
                        active: active,
                        value: {
                            eq: Ext.Date.parse('1972-01-22T12:30:01', 'c')
                        }
                    });

                    showPicker('On');

                    runs(function() {
                        expect((headerNode.textContent || headerNode.innerText).replace(/\s/g, '')).toBe('January1972');
                        expect((selectedNode.textContent || selectedNode.innerText).replace(/\s/g, '')).toBe('22');
                    });
                });

                describe("when a store filter is created", function() {
                    it("should not update the filter collection twice", function() {
                        var called = 0;

                        createGrid({
                            active: active
                        }, {
                            listeners: {
                                filterchange: function() {
                                    ++called;
                                }
                            }
                        });

                        showPicker('On');

                        runs(function() {
                            columnFilter.setValue({
                                eq: Ext.Date.parse('1972-01-22T12:30:01', 'c')
                            });
                            expect(called).toBe(1);
                        });
                    });
                });
            });
        }

        setActive(true);
        setActive(false);

        describe("toggling active state on same filter", function() {
            // The root Filters menu item's checked state and the header filter class should all reflect the current state.
            // See EXTJS-17430.
            it("should update the UI", function() {
                var column;

                createGrid();

                showPicker('Before');

                runs(function() {
                    setPicker('12/10/1992');
                    column = columnFilter.column;

                    // Everything should be on.
                    expect(rootMenuItem.checked).toBe(true);
                    expect(column.hasCls(plugin.filterCls)).toBe(true);

                    // Everything should be off.
                    rootMenuItem.setChecked(false);

                    expect(rootMenuItem.checked).toBe(false);
                    expect(column.hasCls(plugin.filterCls)).toBe(false);

                    setPicker('12/09/1992');

                    // Everything should be on.
                    expect(rootMenuItem.checked).toBe(true);
                    expect(column.hasCls(plugin.filterCls)).toBe(true);
                });
            });
        });
    });
});

