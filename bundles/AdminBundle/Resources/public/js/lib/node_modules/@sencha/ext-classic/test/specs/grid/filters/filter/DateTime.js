xtopSuite("Ext.grid.filters.filter.DateTime", function() {
    var viewReady = false,
        grid, plugin, store;

    function createGrid(pluginCfg, gridCfg, storeCfg) {
        store = new Ext.data.Store(Ext.apply({
            fields: ['name', 'email', 'phone', { name: 'dob', type: 'date' }],
            data: [
                { 'name': 'evan',  'email': 'evan@example.com',  'phone': '555-111-1224', 'dob': '12/12/1992 01:00' },
                { 'name': 'nige',  'email': 'nige@example.com',  'phone': '555-222-1234', 'dob': '12/12/1992 02:00' },
                { 'name': 'phil', 'email': 'phil@example.com', 'phone': '555-222-1244', 'dob': '12/12/1992 03:15' },
                { 'name': 'don', 'email': 'don@example.com', 'phone': '555-222-1254', 'dob': '12/12/1992 04:30' },
                { 'name': 'alex', 'email': 'alex@example.com', 'phone': '555-222-1254', 'dob': '12/12/1992 13:00' },
                { 'name': 'ben', 'email': 'ben@example.com', 'phone': '555-222-1264', 'dob': '12/12/1992 22:45' }
            ],
            autoDestroy: true
        }, storeCfg));

        plugin = new Ext.grid.filters.Filters(Ext.apply({
            updateBuffer: 0
        }, pluginCfg || {}));

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', width: 100 },
                { header: 'Phone', dataIndex: 'phone', width: 100 },
                { header: 'DOB', dataIndex: 'dob', xtype: 'datecolumn', format: 'd/m/Y G:i', width: 100,
                    filter: {
                        type: 'datetime',
                        time: {
                            format: 'G:i'
                        },
                        dock: {
                            buttonText: 'Filter',
                            dock: 'bottom'
                        }
                    }
                }
            ],
            plugins: plugin,
            height: 200,
            width: 400,
            listeners: {
                viewready: function() {
                    viewReady = true;
                }
            },
            renderTo: Ext.getBody()
        }, gridCfg));
    }

    afterEach(function() {
        Ext.destroy(store, grid);
        grid = plugin = store = null;
        viewReady = false;
    });

    describe("setValue", function() {
        var parse = Ext.Date.parse,
            columnFilter;

        afterEach(function() {
            columnFilter = null;
        });

        it("should update the value of the date whenever called", function() {
            // See EXTJSIV-11532.
            createGrid();

            waitsFor(function() {
                return viewReady;
            });

            runs(function() {
                columnFilter = grid.columnManager.getHeaderByDataIndex('dob').filter;
                columnFilter.createMenu();

                columnFilter.setValue({ eq: parse('08/08/1992', 'd/m/Y') });
                columnFilter.setValue({ eq: parse('26/09/2009', 'd/m/Y') });

                expect(columnFilter.filter.eq.getValue()).toBe(parse('26/09/2009', 'd/m/Y').getTime());
            });
        });
    });

    describe("onMenuSelect handler and setFieldValue", function() {
        var columnFilter, headerCt, header, filtersCheckItem, beforeCheckItem, datepicker, timepicker, btn;

        afterEach(function() {
            columnFilter = headerCt = header = filtersCheckItem = beforeCheckItem = datepicker = timepicker = btn = null;
        });

        it("should correctly filter based upon picker selections", function() {
            createGrid();

            waitsFor(function() {
                return viewReady;
            });

            runs(function() {
                columnFilter = grid.columnManager.getHeaderByDataIndex('dob').filter;
                headerCt = grid.headerCt;
                header = grid.getColumnManager().getLast();

                // Show the grid menu.
                headerCt.showMenuBy(null, header.triggerEl.dom, header);

                // Show the filter menu.
                filtersCheckItem = headerCt.menu.items.last();
                filtersCheckItem.activated = true;
                filtersCheckItem.expandMenu(null, 0);

                // Show the DateTime container.
                beforeCheckItem = filtersCheckItem.menu.items.first();
                beforeCheckItem.activated = true;
                beforeCheckItem.expandMenu(null, 0);

                // Finally, get the refs to the components we need to test.
                datepicker = beforeCheckItem.menu.down('datepicker');
                timepicker = beforeCheckItem.menu.down('timepicker');
                btn = beforeCheckItem.menu.down('button[text="Filter"]');

                datepicker.setValue(new Date('12/12/1992'));
                timepicker.select(timepicker.store.getAt(5));
                btn.el.dom.click();

                // The filtering is async.
                waits(1);

                runs(function() {
                    expect(store.getCount()).toBe(1);

                    timepicker.select(timepicker.store.getAt(9));
                    btn.el.dom.click();
                });

                waits(1);

                runs(function() {
                    expect(store.getCount()).toBe(2);
                });
            });
        });
    });
});
