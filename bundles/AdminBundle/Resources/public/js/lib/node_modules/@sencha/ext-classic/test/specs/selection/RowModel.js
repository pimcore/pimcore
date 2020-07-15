topSuite("Ext.selection.RowModel",
    ['Ext.grid.Panel', 'Ext.tree.Panel', 'Ext.app.ViewModel',
     'Ext.toolbar.Paging', 'Ext.Button'],
function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        grid, view, selModel, navModel, store, columns, cell, rawData,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        },
        cellSelectedCls = Ext.view.Table.prototype.selectedCellCls,
        itemSelectedCls = Ext.view.Table.prototype.selectedItemCls;

    function createStore(config) {
        return new Ext.data.Store(Ext.apply({
            fields: ['name'],
            proxy: {
                type: 'memory',
                data: rawData
            }
        }, config));
    }

    function createGrid(gridCfg, selModelCfg, storeCfg) {
        selModel = new Ext.selection.RowModel(selModelCfg || {});

        grid = new Ext.grid.Panel(Ext.apply({
            store: (gridCfg && gridCfg.store) || createStore(storeCfg),
            columns: [
                { text: 'Name',  dataIndex: 'name' }
            ],
            selModel: selModel,
            height: 200,
            width: 200,
            renderTo: Ext.getBody()
        }, gridCfg));

        store = grid.getStore();

        if (!storeCfg || storeCfg.autoLoad !== false) {
            if (store.load) { // chained stores don't have load()
                store.load();
            }
        }

        view = grid.getView();
        navModel = view.getNavigationModel();
        columns = grid.view.getVisibleColumnManager().getColumns();
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;

        rawData = [
            { id: 1, name: 'Phil' },
            { id: 2, name: 'Ben' },
            { id: 3, name: 'Evan' },
            { id: 4, name: 'Don' },
            { id: 5, name: 'Nige' },
            { id: 6, name: 'Alex' }
        ];
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        Ext.destroy(grid, selModel);
        rawData = store = grid = selModel = null;
    });

    it('should not select the row upon in-row navigation', function() {
        createGrid({
            columns: [
                { text: 'ID', dataIndex: 'id' },
                { text: 'Name',  dataIndex: 'name' }
            ]
        });

        navModel.setPosition(0, 0, null, null, true);

        // Wait for the nav model to be fully away of the focus
        waitsFor(function() {
            return !!navModel.getPosition();
        });

        runs(function() {
            jasmine.fireKeyEvent(navModel.getPosition().getCell(true), 'keydown', Ext.event.Event.RIGHT);

            // No selection should take place navigating INSIDE a row
            expect(view.selModel.getSelection().length).toBe(0);

            expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(false);

            // Navigate to the next row however, and that should selectit
            jasmine.fireKeyEvent(navModel.getPosition().getCell(true), 'keydown', Ext.event.Event.DOWN);

            expect(view.selModel.getSelection().length).toBe(1);
            expect(Ext.fly(view.getNode(1)).hasCls(view.selectedItemCls)).toBe(true);
        });
    });

    it('should render cells without the x-grid-cell-selected cls (EXTJSIV-17255)', function() {
        createGrid();

        selModel.select(0);
        grid.getStore().sort('name', 'ASC');

        expect(grid.getView().getNode(2).firstChild).not.toHaveCls(cellSelectedCls);
    });

    itNotTouch = jasmine.supportsTouch ? xit : it;

    itNotTouch('SINGLE select mode should not select on CTRL/click (EXTJS-18592)', function() {
        createGrid({}, {
            selType: 'rowmodel', // rowmodel is the default selection model
            mode: 'SINGLE',
            allowDeselect: true,
            toggleOnClick: false
        });

        // Select row 1
        cell = grid.view.getCell(0, columns[0]);
        jasmine.fireMouseEvent(cell, 'click');

        var selection = selModel.getSelection();

        // Row 0 should be selected
        expect(grid.view.all.item(0).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(0)).toBe(true);

        // CTRL/click on row 2. Should NOT select that row
        cell = grid.view.getCell(1, columns[0]);
        jasmine.fireMouseEvent(cell, 'click', null, null, null, false, true);

        selection = selModel.getSelection();

        // Row 0 should be still be selected
        expect(grid.view.all.item(0).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(0)).toBe(true);

        // Row 1 not selected
        expect(grid.view.all.item(1).hasCls(itemSelectedCls)).toBe(false);
    });

    it('SINGLE select mode should select on RIGHT/LEFT wrap to different row', function() {
        createGrid({}, {
            selType: 'rowmodel', // rowmodel is the default selection model
            mode: 'SINGLE'
        });

        // Select row 2 by clicking on its first column
        cell = grid.view.getCell(1, columns[0]);
        jasmine.fireMouseEvent(cell, 'click');

        var selection = selModel.getSelection();

        // Row 2 should be selected
        expect(grid.view.all.item(1).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(1)).toBe(true);

        // LEFT from there should select row 1
        jasmine.fireKeyEvent(navModel.getPosition().getCell(true), 'keydown', Ext.event.Event.LEFT);

        selection = selModel.getSelection();

        // Row 1 should be now be selected
        expect(grid.view.all.item(0).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(0)).toBe(true);

        // RIGHT from there should select row 2
        jasmine.fireKeyEvent(navModel.getPosition().getCell(true), 'keydown', Ext.event.Event.RIGHT);

        selection = selModel.getSelection();

        // Row 2 should be now be selected
        expect(grid.view.all.item(1).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(1)).toBe(true);

    });

    it('should not allow deselect on SPACE if configured allowDeselect:false', function() {
        createGrid({}, {
            allowDeselect: false
        });

        // Select row 1
        cell = grid.view.getCell(0, columns[0]);
        jasmine.fireMouseEvent(cell, 'click');
        var selection = selModel.getSelection();

        // Row 0 should be selected
        expect(grid.view.all.item(0).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(0)).toBe(true);

        // Press space bar on row 0
        jasmine.fireKeyEvent(cell, 'keydown', Ext.event.Event.SPACE);

        // Row 0 should still be selected
        selection = selModel.getSelection();
        expect(grid.view.all.item(0).hasCls(itemSelectedCls)).toBe(true);
        expect(selection.length).toBe(1);
        expect(selection[0] === grid.store.getAt(0)).toBe(true);

        // Press space bar on row 0 with allowDeselect:true
        selModel.allowDeselect = true;
        jasmine.fireKeyEvent(cell, 'keydown', Ext.event.Event.SPACE);

        // Row 0 should not be selected
        selection = selModel.getSelection();
        expect(grid.view.all.item(0).hasCls(itemSelectedCls)).toBe(false);
        expect(selection.length).toBe(0);
    });

    describe("deselectOnContainerClick", function() {
        it("should default to false", function() {
            createGrid();
            expect(selModel.deselectOnContainerClick).toBe(false);
        });

        describe("deselectOnContainerClick: false", function() {
            it("should not deselect when clicking the container", function() {
                createGrid(null, {
                    deselectOnContainerClick: false
                });
                selModel.select(0);
                jasmine.fireMouseEvent(view.getEl(), 'click', 180, 180);
                expect(selModel.isSelected(0)).toBe(true);
            });
        });

        describe("deselectOnContainerClick: true", function() {
            it("should deselect when clicking the container", function() {
                createGrid(null, {
                    deselectOnContainerClick: true
                });
                selModel.select(0);
                jasmine.fireMouseEvent(view.getEl(), 'click', 180, 180);
                expect(selModel.isSelected(0)).toBe(false);
            });
        });
    });

    describe("pruneRemoved", function() {
        describe('pruneRemoved: true', function() {
            it('should remove records from selection by default when removed from the store', function() {
                createGrid({
                    bbar: {
                        xtype: 'pagingtoolbar'
                    }
                }, null, {
                    autoLoad: false,
                    pageSize: 2
                });
                store.proxy.enablePaging = true;

                var tb = grid.down('pagingtoolbar'),
                    selection;

                tb.setStore(store);
                store.loadPage(1);
                selModel.select(0);
                selection = selModel.getSelection();

                // We have selected the first record
                expect(selection.length).toBe(1);
                expect(selection[0] === store.getAt(0)).toBe(true);

                // Row zero has the selected class
                expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(true);

                // Load page 2
                tb.moveNext();

                // First row in new page NOT selected
                expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(false);

                // Go back to page 1
                tb.movePrevious();
                selection = selModel.getSelection();

                // Selection has gone
                expect(selection.length).toBe(0);

                // Row zero must not be selected
                expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(false);
            });
        });

        describe('pruneRemoved: false', function() {
            it('should NOT remove records from selection if pruneRemoved:false when they are removed from the store', function() {
                createGrid({
                    bbar: {
                        xtype: 'pagingtoolbar'
                    }
                }, {
                    pruneRemoved: false
                }, {
                    autoLoad: false,
                    pageSize: 2
                });
                store.proxy.enablePaging = true;

                var tb = grid.down('pagingtoolbar'),
                    selection;

                tb.setStore(store);
                store.loadPage(1);
                selModel.select(0);
                selection = selModel.getSelection();

                // We have selected the first record
                expect(selection.length).toBe(1);
                expect(selection[0] === store.getAt(0)).toBe(true);

                // Row zero has the selected class
                expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(true);

                // Load page 2
                tb.moveNext();

                // First row in new page NOT selected
                expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(false);

                // Go back to page 1
                tb.movePrevious();
                selection = selModel.getSelection();

                // We have selected the first record
                expect(selection.length).toBe(1);
                expect(selection[0] === store.getAt(0)).toBe(true);

                // Row zero must be selected
                expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(true);
            });
        });
    });

    describe('selecting a range', function() {
        it('should allow a range to be selected after programmatically selecting the first selection', function() {
            // See EXTJSIV-10393.
            createGrid({}, {
                mode: 'MULTI'
            });
            selModel.select(0);
            selModel.selectWithEvent(grid.store.getAt(2), {
                shiftKey: true
            });
            expect(selModel.selected.length).toBe(3);
        });

        it('should allow a range to be selected when shift is held down when making first selection', function() {
            // See EXTJIV-11374.
            createGrid({}, {
                mode: 'MULTI'
            });

            selModel.selectWithEvent(grid.store.getAt(2), {
                shiftKey: true
            });

            selModel.selectWithEvent(grid.store.getAt(0), {
                shiftKey: true
            });

            expect(selModel.selected.length).toBe(3);
        });
    });

    describe('contextmenu', function() {
        beforeEach(function() {
            createGrid({
            }, {
                mode: 'MULTI'
            });
        });

        function triggerCellContextMenu(row, col) {
            var cell = new Ext.grid.CellContext(grid.view).setPosition(row, col).getCell(true);

            jasmine.fireMouseEvent(cell, 'mousedown', 0, 0, 2);
            jasmine.doFireMouseEvent(cell, 'contextmenu');
        }

        itNotTouch('should not deselect the range when right-clicking over a previously selected record', function() {
            // See EXTJSIV-11378.
            selModel.select(4);

            selModel.selectWithEvent(grid.store.getAt(0), {
                shiftKey: true
            });

            // Right-click on a row in the range.
            triggerCellContextMenu(2, 0);

            // Length should be the previously-selected rows.
            expect(selModel.selected.length).toBe(5);
        });

        itNotTouch('should deselect the range when right-clicking over a record not previously selected', function() {
            // See EXTJSIV-11378.
            selModel.select(4);

            selModel.selectWithEvent(grid.store.getAt(0), {
                shiftKey: true
            });

            // Right-click on a row not in the range.
            triggerCellContextMenu(5, 0);

            // Length should only be the row that was right-clicked.
            expect(selModel.selected.length).toBe(1);
        });
    });

    describe("selected cls", function() {
        var phil, ben, evan, don, nige, alex;

        function isSelected(record) {
            var view = grid.getView(),
                node = view.getNode(record),
                cls = view.selectedItemCls;

            return Ext.fly(node).hasCls(cls);
        }

        function expectSelected(record) {
            expect(isSelected(record)).toBe(true);
        }

        function expectNotSelected(record) {
            expect(isSelected(record)).toBe(false);
        }

        function setupRecords() {
            phil = store.getAt(0);
            ben = store.getAt(1);
            evan = store.getAt(2);
            don = store.getAt(3);
            nige = store.getAt(4);
            alex = store.getAt(5);
        }

        afterEach(function() {
            phil = ben = evan = don = nige = alex = null;
        });

        describe("before render", function() {
            beforeEach(function() {
                createGrid({
                    renderTo: null
                }, {
                    mode: 'MULTI'
                });
                setupRecords();
            });

            it("should add the selected cls to a selected record", function() {
                selModel.select(phil);
                grid.render(Ext.getBody());
                expectSelected(phil);
                expectNotSelected(ben);
                expectNotSelected(evan);
                expectNotSelected(don);
                expectNotSelected(nige);
                expectNotSelected(alex);
            });

            it("should add the selected cls to multiple selected records", function() {
                selModel.select([phil, evan, alex]);
                grid.render(Ext.getBody());
                expectSelected(phil);
                expectNotSelected(ben);
                expectSelected(evan);
                expectNotSelected(don);
                expectNotSelected(nige);
                expectSelected(alex);
            });

            it("should not add the selected cls to deselected records", function() {
                selModel.select(phil);
                selModel.deselect(phil);
                grid.render(Ext.getBody());
                expectNotSelected(phil);
            });
        });

        describe("after render", function() {
            beforeEach(function() {
                createGrid(null, {
                    mode: 'MULTI'
                });
                setupRecords();
            });

            it("should add the selected cls to a selected record", function() {
                selModel.select(phil);
                expectSelected(phil);
                expectNotSelected(ben);
                expectNotSelected(evan);
                expectNotSelected(don);
                expectNotSelected(nige);
                expectNotSelected(alex);
            });

            it("should add the selected cls to multiple selected records", function() {
                selModel.select([phil, evan, alex]);
                expectSelected(phil);
                expectNotSelected(ben);
                expectSelected(evan);
                expectNotSelected(don);
                expectNotSelected(nige);
                expectSelected(alex);
            });

            it("should not add the selected cls to deselected records", function() {
                selModel.select(phil);
                selModel.deselect(phil);
                expectNotSelected(phil);
            });
        });

        it("should maintain the selected cls after a cell update", function() {
            createGrid();
            setupRecords();
            selModel.select(phil);
            phil.set('name', 'Foo');
            expectSelected(phil);
        });

        it("should remain selected after a whole row update", function() {
            createGrid();
            setupRecords();
            selModel.select(phil);
            phil.beginEdit();
            phil.set('name', 'Foo');
            phil.endEdit(true);
            phil.commit();
            expectSelected(phil);
        });

        it("should maintain the selected cls after being sorted", function() {
            createGrid();
            setupRecords();
            selModel.select(phil);
            store.sort('name', 'ASC');
            expectSelected(phil);
        });
    });

    it("should remove selections the selection is filtered out of a tree store", function() {
        var tree = Ext.widget({
                xtype: 'treepanel',
                renderTo: document.body,
                rootVisible: false,
                root: {
                    expanded: true,
                    children: [
                        { text: 'foo', leaf: true },
                        { text: 'bar', leaf: true }
                    ]
                }
            });

        tree.selModel.select(0);
        tree.store.filter({ property: 'text', value: 'bar' });
        expect(tree.selModel.getSelection().length).toBe(0);
        tree.destroy();
    });

    describe("view model selection", function() {
        var viewModel, spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
            viewModel = new Ext.app.ViewModel();
        });

        afterEach(function() {
            spy = selModel = viewModel = null;
        });

        function selectNotify(rec) {
            selModel.select(rec);
            viewModel.notify();
        }

        function byName(name) {
            var index = store.findExact('name', name);

            return store.getAt(index);
        }

        describe("reference", function() {
            beforeEach(function() {
                createGrid({
                    reference: 'userList',
                    viewModel: viewModel
                });
                viewModel.bind('{userList.selection}', spy);
                viewModel.notify();
            });

            it("should publish null by default", function() {
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBeNull();
                expect(args[1]).toBeUndefined();
            });

            it("should publish the value when selected", function() {
                var rec = byName('Ben');

                selectNotify(rec);
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(rec);
                expect(args[1]).toBeNull();
            });

            it("should publish when the selection is changed", function() {
                var rec1 = byName('Ben'),
                    rec2 = byName('Nige');

                selectNotify(rec1);
                spy.reset();
                selectNotify(rec2);
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(rec2);
                expect(args[1]).toBe(rec1);
            });

            it("should publish when an item is deselected", function() {
                var rec = byName('Ben');

                selectNotify(rec);
                spy.reset();
                selModel.deselect(rec);
                viewModel.notify();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBeNull();
                expect(args[1]).toBe(rec);
            });
        });

        describe("two way binding", function() {
            beforeEach(function() {
                createGrid({
                    viewModel: viewModel,
                    bind: {
                        selection: '{foo}'
                    }
                });
                viewModel.bind('{foo}', spy);
                viewModel.notify();
            });

            describe("changing the selection", function() {
                it("should trigger the binding when adding a selection", function() {
                    var rec = byName('Don');

                    selectNotify(rec);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(rec);
                    expect(args[1]).toBeUndefined();
                });

                it("should trigger the binding when changing the selection", function() {
                    var rec1 = byName('Ben'),
                        rec2 = byName('Nige');

                    selectNotify(rec1);
                    spy.reset();
                    selectNotify(rec2);
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(rec2);
                    expect(args[1]).toBe(rec1);
                });

                it("should trigger the binding when an item is deselected", function() {
                    var rec = byName('Don');

                    selectNotify(rec);
                    spy.reset();
                    selModel.deselect(rec);
                    viewModel.notify();
                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBeNull();
                    expect(args[1]).toBe(rec);
                });
            });

            describe("changing the viewmodel value", function() {
                it("should select the record when setting the value", function() {
                    var rec = byName('Phil');

                    viewModel.set('foo', rec);
                    viewModel.notify();
                    expect(selModel.isSelected(rec)).toBe(true);
                });

                it("should select the record when updating the value", function() {
                    var rec1 = byName('Phil'),
                        rec2 = byName('Ben');

                    viewModel.set('foo', rec1);
                    viewModel.notify();
                    viewModel.set('foo', rec2);
                    viewModel.notify();
                    expect(selModel.isSelected(rec1)).toBe(false);
                    expect(selModel.isSelected(rec2)).toBe(true);
                });

                it("should deselect when clearing the value", function() {
                    var rec = byName('Evan');

                    viewModel.set('foo', rec);
                    viewModel.notify();
                    viewModel.set('foo', null);
                    viewModel.notify();
                    expect(selModel.isSelected(rec)).toBe(false);
                });
            });

            describe("reloading the store", function() {
                beforeEach(function() {
                    MockAjaxManager.addMethods();
                    selectNotify(byName('Phil'));
                    spy.reset();

                    store.setProxy({
                        type: 'ajax',
                        url: 'fake'
                    });
                    store.load();
                });

                afterEach(function() {
                    MockAjaxManager.removeMethods();
                });

                describe("when the selected record is in the result set", function() {
                    it("should trigger the selection binding", function() {
                        Ext.Ajax.mockComplete({
                            status: 200,
                            responseText: Ext.encode(rawData.slice(0, 4))
                        });
                        viewModel.notify();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(store.getAt(0));
                    });
                });

                describe("when the selected record is not in the result set", function() {
                    it("should trigger the selection binding", function() {
                        Ext.Ajax.mockComplete({
                            status: 200,
                            responseText: '[]'
                        });
                        viewModel.notify();
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBeNull();
                    });
                });
            });
        });
    });

    describe('chained stores', function() {
        it('should remove records from selection by default when removed from source', function() {
            // See EXTJS-16067
            createGrid({
                bbar: {
                    xtype: 'pagingtoolbar'
                },
                store: new Ext.data.ChainedStore({
                    source: createStore()
                })
            });

            var source = store.getSource();

            source.load();

            var record = store.getAt(0);

            selModel.select(0);
            var selection = selModel.getSelection();

            // We have selected the first record
            expect(selection.length).toBe(1);
            expect(selection[0] === record).toBe(true);

            // Row zero has the selected class
            expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(true);

            source.remove(record);

            // Selection has gone
            selection = selModel.getSelection();
            expect(selection.length).toBe(0);

            // Row zero must not be selected
            expect(Ext.fly(view.getNode(0)).hasCls(view.selectedItemCls)).toBe(false);
        });
    });
});
