topSuite("grid-general",
    [false, 'Ext.grid.Panel', 'Ext.grid.column.*', 'Ext.grid.plugin.*', 'Ext.data.ArrayStore',
            'Ext.app.ViewController', 'Ext.mixin.Watchable'],
function() {
    var grid, store,
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

    var scrollbarWidth = Ext.getScrollbarSize().width,
        transformStyleName = 'webkitTransform' in document.documentElement.style ? 'webkitTransform' : 'transform',
        scrollbarsTakeSpace = !!scrollbarWidth,
        // Some tests should only be run if the UI shows space-taking scrollbars.
        // Specifically, those tests which test that the presence or not of a scrollbar in one dimension
        // affects the presence of a scrollbar in the other dimension.
        visibleScrollbarsIt = scrollbarsTakeSpace ? it : xit;

        function getViewTop(el) {
            var dom = Ext.getDom(el),
                transform;

            if (Ext.supports.CssTransforms && !Ext.isIE9m) {
                transform = dom.style[transformStyleName];

                return transform ? parseInt(transform.split(',')[1], 10) : 0;
            }
            else {
                return parseInt(dom.style.top || '0', 10);
            }
        }

    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var GridModel, view, colRef;

            beforeAll(function() {
               GridModel = Ext.define(null, {
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
                });
            });

            function makeStore(data) {
                if (!data && data !== null) {
                    data = [{
                        field1: 1,
                        field2: 2,
                        field3: 3,
                        field4: 4,
                        field5: 5,
                        field6: 6,
                        field7: 7,
                        field8: 8,
                        field9: 9,
                        field10: 10
                    }];
                }

                store = new Ext.data.Store({
                    model: GridModel,
                    data: data
                });

                return store;
            }

            function makeData(rows, columns) {
                var data = [],
                    dataRow,
                    i, j;

                for (i = 0; i < rows; i++) {
                    dataRow = {
                        id: 'rec' + i
                    };

                    for (j = 0; j < columns.length; j++) {
                        dataRow[columns[j].dataIndex] = (i + 1) + ', ' + (j + 1);
                    }

                    data.push(dataRow);
                }

                return data;
            }

            function makeGrid(columns, data, cfg, options, locked) {
                options = options || {};
                cfg = cfg || {};

                var i;

                if (!options.preventColumnCreate && !columns) {
                    columns = [];

                    for (i = 1; i < 11; i++) {
                        columns.push({
                            dataIndex: 'field' + i,
                            text: 'Field ' + i,
                            width: 90,
                            // First column gets locked if we are doing locking tests.
                            locked: locked && i === 1
                        });
                    }
                }

                // Could pass number of required records
                if (typeof data === 'number') {
                    data = makeData(data, columns);
                }

                if (!options.preventStoreCreate) {
                    makeStore(data);
                }

                grid = new Ext.grid.Panel(Ext.apply({
                    columns: columns,
                    store: store,
                    trailingBufferZone: 1000,
                    leadingBufferZone: 1000,
                    width: 1000,
                    height: 500,
                    border: false,
                    bodyStyle: !cfg.border ? 'border: 0' : '',
                    bufferedRenderer: buffered,
                    viewConfig: Ext.apply({
                        mouseOverOutBuffer: 0
                    }, cfg.viewConfig)
                }, cfg));

                // Don't use renderTo since that may throw and we won't set "grid"
                // and will then leak the component
                if (cfg.renderTo === undefined) {
                    grid.render(Ext.getBody());
                }

                view = grid.getView();
                colRef = grid.getColumnManager().getColumns();
            }

            afterEach(function() {
                view = colRef = null;
                Ext.data.Model.schema.clear();
            });

            function getCellText(row, col) {
                var cell = view.getCell(store.getAt(row), colRef[col]),
                    selectorView = grid.lockedGrid ? grid.lockedGrid.getView() : view;

                return cell.querySelector(selectorView.innerSelector).innerHTML;
            }

            describe("misc tests", function() {
                // EXTJS-16436
                it("should not throw an exception when scrollable:false", function() {
                    // Spec will fail if an error is thrown
                    makeGrid(null, undefined, {
                        scrollable: false
                    });
                });

                it("should sync the view scrollable with the onwergrid scrollable", function() {
                    makeGrid(undefined, 50, {
                        scrollable: true
                    }, undefined, false);

                    expect(grid.getScrollable()).not.toBe(true);
                });

                it("should keep scroll position when adding a scrolled grid to a container", function() {
                    var scrollable;

                    var win = Ext.create('Ext.window.Window', {
                        width: 500,
                        height: 500,
                        title: 'Foo',
                        layout: 'fit',
                        x: 0,
                        y: 0
                    }).show();

                    makeGrid(undefined, 100, {
                        scrollable: true
                    }, undefined, false);

                    scrollable = grid.getScrollable();
                    scrollable.scrollTo(null, 1000);

                    waitsFor(function() {
                        return scrollable.getPosition().y === 1000;
                    });

                    runs(function() {
                        win.add(grid);
                        expect(scrollable.getPosition().y).toBe(1000);
                        win.destroy();
                    });
                });

                // EXTJS-14858
                it("should not throw an exception when hiding a column in a locked grid during initComponent", function() {
                    var Plug = Ext.define(null, {
                        extend: 'Ext.AbstractPlugin',

                        init: function(cmp) {
                            cmp.hide();
                        }
                    });

                    makeGrid([{
                        plugins: [new Plug()],
                        locked: true
                    }, {

                    }]);
                });

                // https://sencha.jira.com/browse/EXTJS-14879
                it("should invalidate cached element data when grid DOM is updated", function() {
                    makeGrid();
                    grid.columns[0].hasCustomRenderer = true;
                    Ext.fly(grid.view.all.item(0, true)).addCls('foo-bar');
                    expect(Ext.fly(grid.view.all.item(0, true)).hasCls('foo-bar')).toBe(true);
                    store.getAt(0).set('field1', 'CHANGED');

                    // After the update, the new state has to be synched.
                    expect(Ext.fly(grid.view.all.item(0, true)).hasCls('foo-bar')).toBe(false);
                });

                it("should not throw an error when the store is loaded in the afterrender of an earlier sibling", function() {
                    makeGrid(null, undefined, {
                        renderTo: null
                    });

                    var ct;

                    expect(function() {
                        ct = new Ext.container.Container({
                            renderTo: Ext.getBody(),
                            items: [{
                                xtype: 'component',
                                html: 'Foo',
                                listeners: {
                                    afterrender: function() {
                                        var proxy = new Ext.data.proxy.Ajax({
                                            url: 'foo'
                                        });

                                        spyOn(proxy, 'read').andReturn();
                                        store.setProxy(proxy);
                                        store.load();

                                    }
                                }
                            }, grid]
                        });
                    }).not.toThrow();
                    Ext.destroy(ct);
                });

                it("should not throw an error when the store is loaded in the afterrender event", function() {
                    makeGrid(null, [], {
                        listeners: {
                            afterrender: function() {
                                var proxy = new Ext.data.proxy.Ajax({
                                        url: 'foo'
                                    });

                                spyOn(proxy, 'read').andReturn();
                                store.setProxy(proxy);
                                expect(function() {
                                    store.load();
                                }).not.toThrow();
                            }
                        }
                    });
                });

                // EXTJS-20159
                describe("destroy", function() {
                    it("should destroy lockable", function() {
                        makeGrid([{
                            locked: true
                        }]);

                        grid.destroy();
                        expect(grid.destroyed).toBe(true);
                    });

                    it("should destroy lockable with a ViewModel", function() {
                        makeGrid([{
                            locked: true,
                            dataIndex: 'f1'
                        }, {
                            dataIndex: 'f2'
                        }], null, {
                            bind: '{test}',
                            viewModel: {
                                stores: {
                                    test: {
                                        data: [{
                                            f1: 'a',
                                            f2: 'b'
                                        }]
                                    }
                                }
                            }
                        }, {
                            preventStoreCreate: true
                        });

                        grid.getViewModel().notify();
                        grid.destroy();
                        expect(grid.destroyed).toBe(true);
                    });
                });

                describe("updates while not visible", function() {
                    describe("ancestor is not visible", function() {
                        var ct;

                        afterEach(function() {
                            ct = Ext.destroy(ct);
                        });

                        it("should update after being hidden", function() {
                            makeGrid(undefined, 5, {
                                renderTo: null
                            });

                            ct = new Ext.container.Container({
                                renderTo: Ext.getBody(),
                                items: grid
                            });

                            ct.hide();

                            store.loadData([{
                                field1: 'foo'
                            }]);
                            ct.show();
                            expect(getCellText(0, 0)).toBe('foo');
                        });

                        describe("collapsed", function() {
                            describe("without animation", function() {
                                it("should update after being collapsed", function() {
                                    makeGrid(undefined, 5, {
                                        renderTo: null
                                    });

                                    ct = new Ext.panel.Panel({
                                        collapsible: true,
                                        animCollapse: false,
                                        renderTo: Ext.getBody(),
                                        items: grid
                                    });

                                    ct.collapse();
                                    store.loadData([{
                                        field1: 'foo'
                                    }]);
                                    ct.expand();
                                    expect(getCellText(0, 0)).toBe('foo');
                                });
                            });

                            describe("with animation", function() {
                                it("should update after being collapsed", function() {
                                    makeGrid(undefined, 5, {
                                        renderTo: null
                                    });

                                    var collapseSpy = jasmine.createSpy(),
                                        expandSpy = jasmine.createSpy();

                                    ct = new Ext.panel.Panel({
                                        collapsible: true,
                                        animCollapse: true,
                                        renderTo: Ext.getBody(),
                                        items: grid,
                                        listeners: {
                                            collapse: collapseSpy,
                                            expand: expandSpy
                                        }
                                    });

                                    ct.collapse();
                                    waitsFor(function() {
                                        return collapseSpy.callCount > 0;
                                    });
                                    runs(function() {
                                        store.loadData([{
                                            field1: 'foo'
                                        }]);
                                        ct.expand();
                                    });
                                    waitsFor(function() {
                                        return expandSpy.callCount > 0;
                                    });
                                    runs(function() {
                                        expect(getCellText(0, 0)).toBe('foo');
                                    });
                                });
                            });
                        });
                    });

                    describe("grid is not visible directly", function() {
                        it("should update after being hidden", function() {
                            makeGrid();
                            grid.hide();
                            store.loadData([{
                                field1: 'foo'
                            }]);
                            grid.show();
                            expect(getCellText(0, 0)).toBe('foo');
                        });

                        describe("collapsed", function() {
                            describe("without animation", function() {
                                it("should update after being collapsed", function() {
                                    makeGrid(undefined, 5, {
                                        collapsible: true,
                                        animCollapse: false
                                    });
                                    grid.collapse();
                                    store.loadData([{
                                        field1: 'foo'
                                    }]);
                                    grid.expand();
                                    expect(getCellText(0, 0)).toBe('foo');
                                });
                            });

                            describe("with animation", function() {
                                it("should update after being collapsed", function() {
                                    var collapseSpy = jasmine.createSpy(),
                                        expandSpy = jasmine.createSpy();

                                    makeGrid(undefined, 5, {
                                        collapsible: true,
                                        animCollapse: false,
                                        listeners: {
                                            collapse: collapseSpy,
                                            expand: expandSpy
                                        }
                                    });

                                    grid.collapse();
                                    waitsFor(function() {
                                        return collapseSpy.callCount > 0;
                                    });
                                    runs(function() {
                                        store.loadData([{
                                            field1: 'foo'
                                        }]);
                                        grid.expand();
                                    });
                                    waitsFor(function() {
                                        return expandSpy.callCount > 0;
                                    });
                                    runs(function() {
                                        expect(getCellText(0, 0)).toBe('foo');
                                    });
                                });
                            });
                        });
                    });
                });
            });

            describe("focus behaviour", function() {
                describe("focusing from another grid", function() {
                    var ct;

                    afterEach(function() {
                        ct = Ext.destroy(ct);
                    });

                    it("should be able to be focus by clicking the container when another grid is focused", function() {
                        makeGrid(null, 1, {
                            renderTo: null
                        });

                        var other = grid,
                            otherCols = colRef,
                            otherView = other.view,
                            otherStore = other.getStore();

                        makeGrid(null, 1, {
                            renderTo: null
                        });

                        ct = new Ext.container.Container({
                            renderTo: Ext.getBody(),
                            layout: 'hbox',
                            items: [other, grid]
                        });

                        jasmine.focusAndWait(otherView.getCell(otherStore.getAt(0), otherCols[0]));

                        runs(function() {
                            // Trigger a click in the grid, not on a cell
                            jasmine.fireMouseEvent(view.el, 'click', view.el.getX() + 50, 100);
                        });

                        expectFocused(view.getCell(store.getAt(0), colRef[0]));
                    });
                });

                describe("after reconfigure", function() {
                    it("should be able to refocus the same row after reconfiguring with new columns/new records", function() {
                        makeGrid([{
                            text: 'Foo',
                            dataIndex: 'field1'
                        }], 100);

                        var view = grid.getView(),
                            rec = store.getAt(10);

                        view.focusRow(rec);
                        expectFocused(view.getCell(rec, colRef[0]));
                        runs(function() {
                            var data = [],
                                newStore, i;

                            for (i = 0; i < 100; ++i) {
                                data.push({
                                    id: 'rec' + i
                                });
                            }

                            newStore = makeStore(data);
                            grid.reconfigure(newStore, [{
                                text: 'Bar',
                                dataIndex: 'field2'
                            }]);

                            rec = store.getAt(10);
                            colRef = grid.getColumnManager().getColumns();

                            view.focusRow(rec);
                            expectFocused(view.getCell(rec, colRef[0]));
                        });
                    });
                });
            });

            describe("autoSizeColumn", function() {
                function getPadding() {
                    var cell = Ext.fly(grid.getView().getEl().down(colRef[0].getCellInnerSelector(), true)),
                        right = Ext.supports.ScrollWidthInlinePaddingBug ? parseInt(cell.getStyle('padding-right'), 10) : 0;

                    return parseInt(cell.getStyle('padding-left'), 10) + right;
                }

                it("should size the column when passed a header", function() {
                    makeGrid(null, [{
                        field1: '<div style="width: 125px;>a</div>'
                    }, {
                        field1: '<div style="width: 450px;>b</div>'
                    }, {
                        field1: '<div style="width: 375px;>c</div>'
                    }]);
                    grid.getView().autoSizeColumn(colRef[0]);
                    expect(colRef[0].getWidth()).toBe(451 + getPadding());
                });

                it("should size the column when passed a header index", function() {
                    makeGrid(null, [{
                        field1: '<div style="width: 125px;>a</div>'
                    }, {
                        field1: '<div style="width: 450px;>b</div>'
                    }, {
                        field1: '<div style="width: 375px;>c</div>'
                    }]);
                    grid.getView().autoSizeColumn(0);
                    expect(colRef[0].getWidth()).toBe(451 + getPadding());
                });
            });

            describe("sizing", function() {
                it("should allow for a minHeight on the view with a shrink wrapped grid", function() {
                    makeGrid(null, undefined, {
                        height: undefined,
                        viewConfig: {
                            minHeight: 100
                        }
                    });
                    expect(view.getHeight()).toBe(100);
                    expect(grid.getHeight()).toBe(100 + grid.headerCt.getHeight());
                });
            });

            describe("getRowClass", function() {
                var spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                    makeGrid(null, 3, {
                        viewConfig: {
                            getRowClass: spy.andCallFake(function(rec) {
                                return 'customCls' + store.indexOf(rec) + ' testCls';
                            })
                        }
                    });
                });

                afterEach(function() {
                    spy = null;
                });

                function getRow(index) {
                    var node = view.getNode(index);

                    return Ext.fly(Ext.fly(node).down(view.rowSelector, true));
                }

                it("should be called for each rendered row", function() {
                    expect(spy.callCount).toBe(3);
                    expect(spy.calls[0].args[0]).toBe(store.getAt(0));
                    expect(spy.calls[0].args[1]).toBe(0);
                    expect(spy.calls[1].args[0]).toBe(store.getAt(1));
                    expect(spy.calls[1].args[1]).toBe(1);
                    expect(spy.calls[2].args[0]).toBe(store.getAt(2));
                    expect(spy.calls[2].args[1]).toBe(2);
                });

                it("should be called when refreshing the view", function() {
                    spy.reset();
                    view.refresh();
                    expect(spy.calls[0].args[0]).toBe(store.getAt(0));
                    expect(spy.calls[0].args[1]).toBe(0);
                    expect(spy.calls[1].args[0]).toBe(store.getAt(1));
                    expect(spy.calls[1].args[1]).toBe(1);
                    expect(spy.calls[2].args[0]).toBe(store.getAt(2));
                    expect(spy.calls[2].args[1]).toBe(2);
                });

                it("should be called when adding a new record", function() {
                    spy.reset();
                    store.add({});
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store.getAt(3));
                    expect(spy.mostRecentCall.args[1]).toBe(3);
                });

                it("should be called when inserting a new record", function() {
                    spy.reset();
                    store.insert(0, {});
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store.getAt(0));
                    expect(spy.mostRecentCall.args[1]).toBe(0);
                });

                it("should be called when the row is updating", function() {
                    spy.reset();
                    store.getAt(0).set('field1', 'new value');
                    expect(spy.callCount).toBe(1);
                    expect(spy.mostRecentCall.args[0]).toBe(store.getAt(0));
                    expect(spy.mostRecentCall.args[1]).toBe(0);
                });

                it("should add the class to the row element", function() {
                    expect(getRow(0).hasCls('customCls0')).toBe(true);
                    expect(getRow(1).hasCls('customCls1')).toBe(true);
                    expect(getRow(2).hasCls('customCls2')).toBe(true);
                });

                it("should be able to add multiple class names", function() {
                    expect(getRow(0).hasCls('customCls0')).toBe(true);
                    expect(getRow(0).hasCls('testCls')).toBe(true);
                    expect(getRow(1).hasCls('customCls1')).toBe(true);
                    expect(getRow(1).hasCls('testCls')).toBe(true);
                    expect(getRow(2).hasCls('customCls2')).toBe(true);
                    expect(getRow(2).hasCls('testCls')).toBe(true);
                });
            });

            describe("emptyText", function() {

                function getEmpty() {
                    return Ext.fly(grid.getEl().down('.' + grid.emptyCls, true)) || null;
                }

                describe("when to display", function() {
                    it("should display on first refresh with deferEmptyText: false", function() {
                        makeGrid(null, null, {
                            viewConfig: {
                                emptyText: 'Foo',
                                deferEmptyText: false
                            }
                        });
                        expect(getEmpty()).not.toBeNull();
                    });

                    it("should display on first refresh with deferEmptyText: false and not columns defined", function() {
                        makeGrid([], null, {
                            viewConfig: {
                                emptyText: 'Foo',
                                deferEmptyText: false
                            }
                        });
                        expect(getEmpty()).not.toBeNull();
                    });

                    it("should not display on first refresh with deferEmptyText: true", function() {
                        makeGrid(null, null, {
                            viewConfig: {
                                emptyText: 'Foo',
                                deferEmptyText: true
                            }
                        });
                        expect(getEmpty()).toBeNull();
                    });

                    it("should display on subsequent refreshes with deferEmptyText: true", function() {
                        makeGrid(null, null, {
                            viewConfig: {
                                emptyText: 'Foo',
                                deferEmptyText: true
                            }
                        });
                        grid.getView().refresh();
                        expect(getEmpty()).not.toBeNull();
                    });

                    it("should display when removing the last record", function() {
                        makeGrid(null, 1, {
                            viewConfig: {
                                emptyText: 'Foo',
                                deferEmptyText: true
                            }
                        });
                        store.removeAt(0);
                        expect(getEmpty()).not.toBeNull();
                    });

                    it("should display when removing all records", function() {
                        makeGrid(null, 5, {
                            viewConfig: {
                                emptyText: 'Foo',
                                deferEmptyText: true
                            }
                        });
                        store.removeAll();
                        expect(getEmpty()).not.toBeNull();
                    });
                });

                describe("config", function() {
                    describe("emptyCls", function() {
                        it("should use the passed emptyCls", function() {
                            makeGrid(null, null, {
                                emptyCls: 'foo',
                                viewConfig: {
                                    emptyText: 'Foo',
                                    deferEmptyText: false
                                }
                            });
                            expect(getEmpty().hasCls('foo')).toBe(true);
                        });
                    });

                    describe("emptyText", function() {
                        it("should use the passed emptyText", function() {
                            makeGrid(null, null, {
                                viewConfig: {
                                    emptyText: 'Foo',
                                    deferEmptyText: false
                                }
                            });
                            expect(getEmpty().dom).hasHTML('Foo');
                        });
                    });
                });

                describe("size", function() {
                    it("should set the grid height correctly based on the emptyText when auto heighting", function() {
                        makeGrid(null, null, {
                            height: null,
                            hideHeaders: true,
                            viewConfig: {
                                emptyText: '<div style="width: 50px; height: 100px;">a</div>',
                                deferEmptyText: false
                            }
                        });
                        var otherParts = grid.body.getBorderWidth('tb') + Ext.fly(grid.el.down(view.bodySelector, true)).getHeight();

                        expect(grid.getHeight()).toBe(100 + getEmpty().getPadding('tb') + otherParts);
                    });
                });

                describe("scrolling", function() {
                    it("should keep a horizontal scrollbar if columns are larger than the grid width", function() {
                        makeGrid([{
                            width: 800
                        }, {
                            width: 800
                        }], 0, {
                            viewConfig: {
                                emptyText: '<div style="width: 50px; height: 100px;">a</div>',
                                deferEmptyText: false
                            }
                        });
                        expect(view.getEl().dom.scrollWidth).toBe(1600);
                    });
                });
            });

            describe("stripeRows", function() {
                var stripeCls = Ext.view.Table.prototype.altRowCls;

                describe("with stripeRows: false", function() {
                    function expectNotStriped() {
                        Ext.Array.forEach(grid.getView().getNodes(), function(node) {
                            expect(Ext.fly(node).hasCls(stripeCls)).toBe(false);
                        });
                    }

                    beforeEach(function() {
                        makeGrid(null, 11, {
                            viewConfig: {
                                stripeRows: false
                            }
                        });
                    });

                    it("should not stripe rows on initial render", function() {
                        expectNotStriped();
                    });

                    it("should not stripe rows when adding records", function() {
                        store.add([{}, {}, {}, {}, {}]);
                        expectNotStriped();
                    });

                    it("should not stripe rows when removing records", function() {
                        store.removeAt(0);
                        expectNotStriped();
                    });

                    it("should not stripe rows when updating records", function() {
                        store.getAt(0).set('field1', 'foo');
                        expectNotStriped();
                    });

                    it("should not stripe rows on refresh", function() {
                        grid.getView().refresh();
                        expectNotStriped();
                    });
                });

                describe("with stripeRows: true", function() {
                    beforeEach(function() {
                        makeGrid(null, 11, {
                            viewConfig: {
                                stripeRows: true
                            }
                        });
                    });

                    function expectStriped() {
                        Ext.Array.forEach(grid.getView().getNodes(), function(node, index) {
                            if (index % 2 === 1) {
                                expect(Ext.fly(node).hasCls(stripeCls)).toBe(true);
                            }
                            else {
                                expect(Ext.fly(node).hasCls(stripeCls)).toBe(false);
                            }
                        });
                    }

                    it("should stripe rows on initial render", function() {
                        expectStriped();
                    });

                    it("should stripe rows when appending records", function() {
                        store.add([{}, {}, {}, {}, {}]);
                        expectStriped();
                    });

                    it("should stripe rows when inserting records", function() {
                        store.insert(0, {});
                        expectStriped();
                    });

                    it("should stripe when removing records", function() {
                        store.removeAt(0);
                        expectStriped();
                    });

                    it("should retain the stripe class when updating records", function() {
                        store.getAt(1).set('field1', 'foo');
                        expectStriped();
                    });

                    it("should stripe when a record update causes the position to change", function() {
                        store.sort('field1');
                        store.getAt(0).set('field1', '999999999');
                        expectStriped();
                    });

                    it("should stripe on refresh", function() {
                        store.suspendEvents();
                        var numbers = [70, 72, 75, 27, 82, 70, 53, 42, 87, 19, 23];

                        store.each(function(rec, index) {
                            rec.set('field1', numbers[index]);
                        });
                        store.sort('field1');
                        store.resumeEvents();
                        grid.getView().refresh();
                        expectStriped();
                    });
                });
            });

            describe("forceFit", function() {
                var data;

                beforeEach(function() {
                    data = [];

                    for (var i = 0; i < 50; i++) {
                        data.push({
                            field1: (i + 1) + ', ' + 1,
                            field2: (i + 1) + ', ' + 2,
                            field3: (i + 1) + ', ' + 3,
                            field4: (i + 1) + ', ' + 4,
                            field5: (i + 1) + ', ' + 5,
                            field6: (i + 1) + ', ' + 6,
                            field7: (i + 1) + ', ' + 7,
                            field8: (i + 1) + ', ' + 8,
                            field9: (i + 1) + ', ' + 9,
                            field10: (i + 1) + ', ' + 10
                        });
                    }
                });

                afterEach(function() {
                    data = null;
                });

                describe("starting with no overflow", function() {
                    it("should size the columns to fit within the grid body", function() {
                        makeGrid(null, undefined, {
                            forceFit: true,
                            width: 400,
                            height: 200
                        });
                        var emptyStore = new Ext.data.Store({
                            autoDestroy: false,
                            model: GridModel,
                            data: []
                        });

                        expect(grid.headerCt.getTableWidth()).toBe(grid.body.getWidth() - grid.body.getBorderWidth('lr'));

                        // Cause overflow by adding 50 new rows
                        store.add(data);

                        // Now should fit within the scrollbar
                        expect(grid.headerCt.getTableWidth()).toBe(grid.body.getWidth() - grid.body.getBorderWidth('lr') - Ext.getScrollbarSize().width);

                        // Avoid destruction when we unbind
                        store.autoDestroy = false;

                        // Reconfigure with an empty store
                        grid.reconfigure(emptyStore);

                        // Reconfigure back to full store
                        grid.reconfigure(store);

                        // Now should fit within the scrollbar
                        expect(grid.headerCt.getTableWidth()).toBe(grid.body.getWidth() - grid.body.getBorderWidth('lr') - Ext.getScrollbarSize().width);
                        Ext.destroy(emptyStore);
                    });
                });

                describe("hidden columns", function() {
                    it("should be able to display a column that was initially hidden", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            flex: 1
                        }, {
                            dataIndex: 'field2',
                            hidden: true
                        }, {
                            dataIndex: 'field3',
                            flex: 1
                        }], data, {
                            forceFit: true,
                            width: 400,
                            height: 200
                        });

                        expect(function() {
                            colRef[1].show();
                        }).not.toThrow();

                        expect(grid.getVisibleColumnManager().getColumns().length).toBe(3);
                    });

                    it("should be able to display a column that was initially hidden configure with flex", function() {
                        var visibleColumns;

                        makeGrid([{
                            dataIndex: 'field1',
                            flex: 1
                        }, {
                            dataIndex: 'field2',
                            hidden: true,
                            flex: 1
                        }, {
                            dataIndex: 'field3',
                            flex: 1
                        }], data, {
                            forceFit: true,
                            width: 400,
                            height: 200
                        });

                        expect(function() {
                            colRef[1].show();
                        }).not.toThrow();

                        visibleColumns = grid.getVisibleColumnManager().getColumns();
                        expect(visibleColumns.length).toBe(3);

                        for (var i = 0; i < 3; i++) {
                            expect(visibleColumns[i].flex).not.toBeNaN();
                        }
                    });

                    it("should be able to display a column that was initially hidden with a fixed width", function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            flex: 1
                        }, {
                            dataIndex: 'field2',
                            hidden: true,
                            width: 100
                        }, {
                            dataIndex: 'field3',
                            flex: 1
                        }], data, {
                            forceFit: true,
                            width: 400,
                            height: 200
                        });

                        expect(function() {
                            colRef[1].show();
                        }).not.toThrow();

                        expect(grid.getVisibleColumnManager().getColumns().length).toBe(3);
                    });
                });

                describe("with overflow", function() {
                    it("should size the columns to fit within the grid body, inside the scrollbar", function() {
                        makeGrid(null, data, {
                            forceFit: true,
                            width: 400,
                            height: 200
                        });
                        expect(grid.headerCt.getTableWidth()).toBe(grid.body.getWidth() - grid.body.getBorderWidth('lr') - Ext.getScrollbarSize().width);
                    });

                    it("should not change the column sizes after updateLayout when width is not specified", function() {
                        var w1 = [],
                            w2 = [],
                            i;

                        makeGrid([{
                            dataIndex: 'field1',
                            text: 'col1'
                        }, {
                            dataIndex: 'field2',
                            text: 'col2'
                        }, {
                            dataIndex: 'field3',
                            text: 'col3'
                        }], 10, {
                            fullScreen: true,
                            forceFit: true,
                            width: '100%'
                        });

                        colRef[0].autoSize();

                        for (i = 0; i < colRef.length; i++) {
                            w1.push(colRef[i].getWidth());
                        }

                        grid.updateLayout();

                        for (i = 0; i < colRef.length; i++) {
                            w2.push(colRef[i].getWidth());
                        }

                        expect(w1).toEqual(w2);
                    });
                });
            });

            describe("basic settings", function() {
                describe("columns", function() {
                    describe("without locking", function() {
                        it("should be able to configure without columns", function() {
                            expect(function() {
                                makeGrid(null, undefined, null, { preventColumnCreate: true });
                            }).not.toThrow();
                        });
                    });

                    describe("with locking", function() {
                        it("should be able to configure without columns", function() {
                            expect(function() {
                                makeGrid(null, undefined, { enableLocking: true }, { preventColumnCreate: true });
                            }).not.toThrow();
                        });
                    });
                });

                // Note: Arguably, these specs should not be in grid since itemSelector is a view config, but the bug only
                // occurred in a locking grid. See EXTJS-15563.
                describe("itemSelector", function() {
                    var itemSelector = Ext.view.Table.prototype.itemSelector;

                    describe("without locking", function() {
                        it("should be able to lookup the itemSelector on the view", function() {
                            makeGrid();
                            expect(view.itemSelector).toBe(itemSelector);
                        });
                    });

                    describe("with locking", function() {
                        it("should be able to lookup the itemSelector on the LockingView", function() {
                            makeGrid(null, undefined, null, null, true);
                            expect(view.itemSelector).toBe(itemSelector);
                        });
                    });
                });

                describe("css classes", function() {
                    it("should add the x-grid cls", function() {
                        makeGrid();
                        expect(grid.el.hasCls('x-grid')).toBe(true);
                    });

                    it("should add the x-grid cls when specifying a custom cls", function() {
                        makeGrid(undefined, undefined, {
                            cls: 'foo'
                        });
                        expect(grid.el.hasCls('x-grid')).toBe(true);
                        expect(grid.el.hasCls('foo')).toBe(true);
                    });
                });

                describe("markDirty", function() {
                    var dirtyCls;

                    function makeDirtyGrid(markDirty, preventRender, columns) {
                        makeGrid(columns, undefined, {
                            renderTo: preventRender ? null : Ext.getBody(),
                            viewConfig: {
                                markDirty: markDirty,
                                mouseOverOutBuffer: 0
                            }
                        });
                        dirtyCls = grid.getView().dirtyCls;
                    }

                    afterEach(function() {
                        dirtyCls = null;
                    });

                    function getCell(record, column) {
                        return Ext.fly(grid.getView().getCell(record, column));
                    }

                    describe("with markDirty: false", function() {
                        it("should not render a cell with the dirtyCls initially", function() {
                            makeDirtyGrid(false, true);
                            store.first().set('field1', 'bleh');
                            grid.render(Ext.getBody());

                            expect(grid.getEl().select('.' + dirtyCls).getCount()).toBe(0);
                        });

                        it("should not render a cell with the dirtyText description initially", function() {
                            makeDirtyGrid(false, true);
                            store.first().set('field1', 'bleh');
                            grid.render(Ext.getBody());

                            expect(grid.getEl().select('[aria-describedby]').getCount()).toBe(0);
                        });

                        it("should not add the dirtyCls when updated with a simple cell updater", function() {
                            makeDirtyGrid(false);
                            store.first().set('field1', 'bleh');
                            expect(grid.getEl().select('.' + dirtyCls).getCount()).toBe(0);
                        });

                        it("should not add the dirtyText description when updated with a simple cell updater", function() {
                            makeDirtyGrid(false);
                            store.first().set('field1', 'bleh');

                            expect(grid.getEl().select('[aria-describedby]').getCount()).toBe(0);
                        });

                        it("should not add the dirtyCls when updated with a renderer", function() {
                            makeDirtyGrid(false, false, [{
                                dataIndex: '',
                                renderer: function(v, meta, rec) {
                                    return rec.get('field1') + rec.get('field2');
                                }
                            }]);
                            store.first().set('field1', 'bleh');
                            expect(grid.getEl().select('.' + dirtyCls).getCount()).toBe(0);
                        });

                        it("should not add the dirtyText description when updated with a renderer", function() {
                            makeDirtyGrid(false, false, [{
                                dataIndex: '',
                                renderer: function(v, meta, rec) {
                                    return rec.get('field1') + rec.get('field2');
                                }
                            }]);
                            store.first().set('field1', 'bleh');

                            expect(grid.getEl().select('[aria-describedby]').getCount()).toBe(0);
                        });
                    });

                    describe("with markDirty: true", function() {
                        it("should not render a cell with the dirtyCls initially if not dirty", function() {
                            makeDirtyGrid(true, true);
                            grid.render(Ext.getBody());

                            var rec = store.first();

                            for (var i = 0; i < colRef.length; ++i) {
                                expect(getCell(rec, colRef[i])).not.toHaveCls(dirtyCls);
                            }
                        });

                        it("should not render a cell with the dirtyText description initially if not dirty", function() {
                            makeDirtyGrid(true, true);
                            grid.render(Ext.getBody());

                            var rec = store.first();

                            for (var i = 0; i < colRef.length; ++i) {
                                expect(getCell(rec, colRef[i])).not.toHaveAttr('aria-describedby');
                            }
                        });

                        it("should render a cell with the dirtyCls initially if the cell is dirty", function() {
                            makeDirtyGrid(true, true);
                            store.first().set('field1', 'bleh');
                            grid.render(Ext.getBody());

                            var rec = store.first();

                            expect(getCell(rec, colRef[0])).toHaveCls(dirtyCls);

                            for (var i = 1; i < colRef.length; ++i) {
                                expect(getCell(rec, colRef[i])).not.toHaveCls(dirtyCls);
                            }
                        });

                        it("should render a cell with the dirtyText description initially if the cell is dirty", function() {
                            makeDirtyGrid(true, true);
                            store.first().set('field1', 'bleh');
                            grid.render(Ext.getBody());

                            var rec = store.first();

                            expect(getCell(rec, colRef[0])).toHaveAttr('aria-describedby', colRef[0].dirtyTextElementId);

                            for (var i = 1; i < colRef.length; ++i) {
                                expect(getCell(rec, colRef[i])).not.toHaveAttr('aria-describedby');
                            }
                        });

                        it("should add the dirtyCls to updated cells", function() {
                            makeDirtyGrid(true);
                            var rec = store.first();

                            rec.set('field1', 'bleh');
                            expect(getCell(rec, colRef[0])).toHaveCls(dirtyCls);
                            rec.set('field4', 'qwerty');
                            expect(getCell(rec, colRef[3])).toHaveCls(dirtyCls);
                        });

                        it("should add the dirtyText description to updated cells", function() {
                            makeDirtyGrid(true);
                            var rec = store.first();

                            rec.set('field1', 'bleh');
                            expect(getCell(rec, colRef[0])).toHaveAttr('aria-describedby', colRef[0].dirtyTextElementId);

                            rec.set('field4', 'qwerty');
                            expect(getCell(rec, colRef[3])).toHaveAttr('aria-describedby', colRef[3].dirtyTextElementId);
                        });

                        it("should remove the dirtyCls when the cell is no longer dirty", function() {
                            makeDirtyGrid(true);
                            var rec = store.first(),
                                val = rec.get('field1');

                            rec.set('field1', 'asdf');
                            expect(getCell(rec, colRef[0])).toHaveCls(dirtyCls);
                            rec.set('field1', val);
                            expect(getCell(rec, colRef[0])).not.toHaveCls(dirtyCls);
                        });

                        it("should remove the dirtyText description when the cell is no longer dirty", function() {
                            makeDirtyGrid(true);
                            var rec = store.first(),
                                val = rec.get('field1');

                            rec.set('field1', 'asdf');
                            expect(getCell(rec, colRef[0])).toHaveAttr('aria-describedby', colRef[0].dirtyTextElementId);

                            rec.set('field1', val);
                            expect(getCell(rec, colRef[0])).not.toHaveAttr('aria-describedby');
                        });

                        it("should remove the dirtyCls on commit", function() {
                            makeDirtyGrid(true);
                            var rec = store.first();

                            rec.set('field1', 'foo');
                            rec.set('field2', 'bar');
                            expect(getCell(rec, colRef[0])).toHaveCls(dirtyCls);
                            expect(getCell(rec, colRef[1])).toHaveCls(dirtyCls);

                            rec.set('field3', 'baz');
                            expect(getCell(rec, colRef[2])).toHaveCls(dirtyCls);

                            rec.commit();

                            expect(getCell(rec, colRef[0])).not.toHaveCls(dirtyCls);
                            expect(getCell(rec, colRef[1])).not.toHaveCls(dirtyCls);
                            expect(getCell(rec, colRef[2])).not.toHaveCls(dirtyCls);
                        });

                        it("should remove the dirtyText description on commit", function() {
                            makeDirtyGrid(true);
                            var rec = store.first();

                            rec.set('field1', 'foo');
                            rec.set('field2', 'bar');
                            expect(getCell(rec, colRef[0])).toHaveAttr('aria-describedby', colRef[0].dirtyTextElementId);
                            expect(getCell(rec, colRef[1])).toHaveAttr('aria-describedby', colRef[1].dirtyTextElementId);

                            rec.set('field3', 'baz');
                            expect(getCell(rec, colRef[2])).toHaveAttr('aria-describedby', colRef[2].dirtyTextElementId);

                            rec.commit();

                            expect(getCell(rec, colRef[0])).not.toHaveAttr('aria-describedby');
                            expect(getCell(rec, colRef[1])).not.toHaveAttr('aria-describedby');
                            expect(getCell(rec, colRef[2])).not.toHaveAttr('aria-describedby');
                        });

                        it("should remove the dirtyCls on reject", function() {
                            makeDirtyGrid(true);
                            var rec = store.first();

                            rec.set('field1', 'foo');
                            rec.set('field2', 'bar');
                            expect(getCell(rec, colRef[0])).toHaveCls(dirtyCls);
                            expect(getCell(rec, colRef[1])).toHaveCls(dirtyCls);

                            rec.set('field3', 'baz');
                            expect(getCell(rec, colRef[2])).toHaveCls(dirtyCls);

                            rec.reject();

                            expect(getCell(rec, colRef[0])).not.toHaveCls(dirtyCls);
                            expect(getCell(rec, colRef[1])).not.toHaveCls(dirtyCls);
                            expect(getCell(rec, colRef[2])).not.toHaveCls(dirtyCls);
                        });

                        it("should remove the dirtyText description on reject", function() {
                            makeDirtyGrid(true);
                            var rec = store.first();

                            rec.set('field1', 'foo');
                            rec.set('field2', 'bar');
                            expect(getCell(rec, colRef[0])).toHaveAttr('aria-describedby', colRef[0].dirtyTextElementId);
                            expect(getCell(rec, colRef[1])).toHaveAttr('aria-describedby', colRef[1].dirtyTextElementId);

                            rec.set('field3', 'baz');
                            expect(getCell(rec, colRef[2])).toHaveAttr('aria-describedby', colRef[2].dirtyTextElementId);

                            rec.reject();

                            expect(getCell(rec, colRef[0])).not.toHaveAttr('aria-describedby');
                            expect(getCell(rec, colRef[1])).not.toHaveAttr('aria-describedby');
                            expect(getCell(rec, colRef[2])).not.toHaveAttr('aria-describedby');
                        });
                    });
                });
            });

            describe("selection", function() {
                function describeSelectionSuite(withLocking) {
                    describe(withLocking ? "with locking" : "without locking", function() {
                        var sm;

                        beforeEach(function() {
                            sm = new Ext.selection.RowModel();
                            makeGrid([{
                                dataIndex: 'field1',
                                locked: withLocking
                            }, {
                                dataIndex: 'field2'
                            }], undefined, {
                                selModel: sm
                            });
                        });

                        afterEach(function() {
                            sm = null;
                        });

                        it("should bind the store to the selection model", function() {
                            expect(sm.getStore()).toBe(grid.getStore());
                        });

                        it("should add the selectedItemCls when selected", function() {
                            var cls;

                            sm.select(0);

                            if (withLocking) {
                                view = grid.normalGrid.getView();
                                cls = view.selectedItemCls;

                                expect(grid.lockedGrid.getView().getNode(0)).toHaveCls(cls);
                                expect(view.getNode(0)).toHaveCls(cls);
                            }
                            else {
                                view = grid.getView();
                                cls = view.selectedItemCls;

                                expect(view.getNode(0)).toHaveCls(cls);
                            }
                        });

                        it("should remove the selectedItemCls when deselected", function() {
                            var cls;

                            sm.select(0);
                            sm.deselect(0);

                            if (withLocking) {
                                view = grid.normalGrid.getView();
                                cls = view.selectedItemCls;

                                expect(grid.lockedGrid.getView().getNode(0)).not.toHaveCls(cls);
                                expect(view.getNode(0)).not.toHaveCls(cls);
                            }
                            else {
                                view = grid.getView();
                                cls = view.selectedItemCls;

                                expect(view.getNode(0)).not.toHaveCls(cls);
                            }
                        });

                        it("should retain the selectedItemCls when updating a row", function() {
                            var cls;

                            sm.select(0);
                            store.first().commit();

                            if (withLocking) {
                                view = grid.normalGrid.getView();
                                cls = view.selectedItemCls;

                                expect(grid.lockedGrid.getView().getNode(0)).toHaveCls(cls);
                                expect(view.getNode(0)).toHaveCls(cls);
                            }
                            else {
                                view = grid.getView();
                                cls = view.selectedItemCls;

                                expect(view.getNode(0)).toHaveCls(cls);
                            }
                        });
                    });
                }

                describeSelectionSuite(false);
                describeSelectionSuite(true);
            });

            describe("renderers", function() {
                describe("scope", function() {
                    it("should use the grid as the default scope", function() {
                        var scope;

                        makeGrid([{
                            dataIndex: 'field1',
                            text: 'Field1',
                            renderer: function() {
                                scope = this;
                            }
                        }]);
                        expect(scope).toBe(grid);
                    });

                    it("should use the passed scope", function() {
                        var o = {},
                            scope;

                        makeGrid([{
                            dataIndex: 'field1',
                            text: 'Field1',
                            scope: o,
                            renderer: function() {
                                scope = this;
                            }
                        }]);
                        expect(scope).toBe(o);
                    });
                });

                describe("meta param", function() {
                    var backgroundColor = 'yellow';

                    beforeEach(function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            renderer: function(value, meta) {
                                meta.tdStyle = 'background-color:' + backgroundColor;

                                return '<B>' + value.toUpperCase() + '</B>';
                            }
                        }], 1);
                    });

                    it("should pass the value as the first param", function() {
                        expect(view.getCell(0, 0).style.backgroundColor).toBe(backgroundColor);
                        expect(view.getCell(0, 0).querySelector(view.innerSelector).innerHTML.toUpperCase()).toBe('<B>' + store.getAt(0).get('field1').toUpperCase() + '</B>');
                    });

                    it("should pass a meta object as the second param when refreshNode is called", function() {
                        expect(view.getCell(0, 0).style.backgroundColor).toBe(backgroundColor);

                        backgroundColor = 'red';
                        expect(function() {
                            view.refreshNode(store.getAt(0));
                        }).not.toThrow();

                        // Must have updated the background color
                        expect(view.getCell(0, 0).style.backgroundColor).toBe(backgroundColor);
                    });
                });

                describe("params", function() {
                    var args;

                    beforeEach(function() {
                        makeGrid([{
                            dataIndex: 'field1',
                            renderer: function() {
                                args = Array.prototype.slice.call(arguments, 0, arguments.length);
                            }
                        }]);
                    });

                    it("should pass the value as the first param", function() {
                        expect(args[0]).toBe(1);
                    });

                    it("should pass a meta object as the second param", function() {
                        expect(Ext.isObject(args[1])).toBe(true);
                    });

                    it("should pass the record as the third param", function() {
                        expect(args[2]).toBe(store.getAt(0));
                    });

                    it("should pass the recordIndex as the fourth param", function() {
                        expect(args[3]).toBe(0);
                    });

                    it("should pass the cellIndex as the fifth param", function() {
                        expect(args[4]).toBe(0);
                    });

                    it("should pass the store as the sixth param", function() {
                        expect(args[5]).toBe(store);
                    });

                    it("should pass the view as the seventh param", function() {
                        expect(args[6]).toBe(grid.getView());
                    });
                });

                describe("cellIndex", function() {
                    it("should pass the local index when dealing with locked columns", function() {
                        var indexes = [],
                            // We are testing passed cellIndex which is only calculated if there
                            // is a recieving argument in the arg list, so declare them all.
                            fn = function(value, cellValues, record, recordIndex, fullIndex, store, view) {
                                indexes.push(arguments[4]);
                            };

                        makeGrid([{
                            locked: true,
                            dataIndex: 'field1',
                            renderer: fn
                        }, {
                            locked: true,
                            dataIndex: 'field2',
                            renderer: fn
                        }, {
                            dataIndex: 'field3',
                            renderer: fn
                        }, {
                            dataIndex: 'field4',
                            renderer: fn
                        }]);
                        expect(indexes).toEqual([0, 1, 0, 1]);
                    });

                    it("should take into account hidden columns when passing cellIdx", function() {
                        var values = [],
                            indexes = [],
                            // We are testing passed cellIndex which is only calculated if there
                            // is a recieving argument in the arg list, so declare them all.
                             fn = function(value, cellValues, record, recordIndex, fullIndex, store, view) {
                                values.push(value);
                                indexes.push(arguments[4]);
                            };

                        makeGrid([{
                            dataIndex: 'field1',
                            renderer: fn
                        }, {
                            hidden: true,
                            dataIndex: 'field2',
                            renderer: fn
                        }, {
                            dataIndex: 'field3',
                            renderer: fn
                        }, {
                            hidden: true,
                            dataIndex: 'field4',
                            renderer: fn
                        }, {
                            dataIndex: 'field5',
                            renderer: fn
                        }]);
                        expect(indexes).toEqual([0, 2, 4]);
                        expect(values).toEqual([1, 3, 5]);
                    });
                });

                it("should accept a string formatter that maps to Ext.util.Format", function() {
                    var oldFormat = Ext.util.Format.capitalize,
                        called;

                    Ext.util.Format.capitalize = function() {
                        called = true;
                    };

                    makeGrid([{
                        dataIndex: 'field1',
                        formatter: 'capitalize'
                    }]);

                    expect(called).toBe(true);

                    Ext.util.Format.capitalize = oldFormat;
                });

                it("should accept chained formatters that map to Ext.util.Format", function() {
                    var oldFormat = Ext.util.Format.capitalize,
                        oldFormat2 = Ext.util.Format.lowercase,
                        called, called2;

                    Ext.util.Format.capitalize = function() {
                        called = true;
                    };

                    Ext.util.Format.lowercase = function() {
                        called2 = true;
                    };

                    makeGrid([{
                        dataIndex: 'field1',
                        formatter: 'capitalize:lowercase'
                    }]);

                    expect(called).toBe(true);
                    expect(called2).toBe(true);

                    Ext.util.Format.capitalize = oldFormat;
                    Ext.util.Format.lowercase = oldFormat2;
                });

                it("should accept a scoped formatter", function() {
                    var called = 0;

                    var formatter = function(v, a1) {
                        called = v + a1;
                    };

                    makeGrid([{
                        dataIndex: 'field1',
                        formatter: 'this.foo(2)'
                    }], undefined, {
                        defaultListenerScope: true,
                        foo: formatter
                    });

                    expect(called).toBe(3);
                });

                it("should accept chained and scoped formatters", function() {
                    var called = 0;

                    var formatter = function(v, a1) {
                        return v * a1; // 1*2
                    };

                    var formatter2 = function(v, a1) {
                        called = v / a1; // 2/4
                    };

                    makeGrid([{
                        dataIndex: 'field1',
                        formatter: 'this.foo(2):this.bar(4)'
                    }], undefined, {
                        defaultListenerScope: true,
                        foo: formatter,
                        bar: formatter2
                    });

                    expect(called).toBe(0.5);
                });

                it("should treat dynamic renderers as needed to run in all cases", function() {
                    var x = 0;

                    var VC = Ext.define(null, {
                        extend: 'Ext.app.ViewController',
                        doRender: function() {
                            return 'x' + (++x);
                        }
                    });

                    makeGrid([{
                        dataIndex: 'field1'
                    }, {
                        renderer: 'doRender'
                    }],
                    1, // ONE row of data, so that the first call of the custom renderer
                       // increments x to one, and the second call renders 2.
                    {
                        controller: new VC()
                    });

                    var rec = store.getAt(0);

                    expect(getCellText(0, 1)).toBe('x1');
                    rec.set('field1', 'foo');
                    expect(getCellText(0, 1)).toBe('x2');
                });
            });

            describe("model operations", function() {
                describe("destroy", function() {
                    it("should remove the model when destroy is called", function() {
                        makeGrid([{
                            dataIndex: 'field1'
                        }], [{
                            field1: 'foo'
                        }, {
                            field1: 'bar'
                        }, {
                            field1: 'baz'
                        }]);
                        store.first().erase();
                        expect(grid.getView().getNodes().length).toBe(2);
                    });
                });
            });

            describe("row binding", function() {
                // While these tests will use widgetcolumn/rowwidget, they are really testing the functionality
                // shared in the grid/row context

                function makeBindSuite(o) {
                    var widgets, wCount;

                    function notify() {
                        var vm = grid.lookupViewModel();

                        if (vm) {
                            vm.notify();
                        }
                        else {
                            Ext.Array.forEach(widgets, function(widget) {
                                var vm = widget.lookupViewModel();

                                if (vm) {
                                    if (vm.getParent()) {
                                        vm = vm.getParent();
                                    }

                                    vm.notify();
                                }
                            });
                        }
                    }

                    beforeEach(function() {
                        wCount = 0;
                        widgets = [];

                        Ext.define('spec.RowVM', {
                            extend: 'Ext.app.ViewModel',
                            alias: 'viewmodel.specgridrow'
                        });

                        Ext.define('spec.WidgetVM', {
                            extend: 'Ext.app.ViewModel',
                            alias: 'viewmodel.specwidgetvm'
                        });

                        Ext.define('spec.GridWidget', {
                            extend: 'Ext.container.Container',
                            xtype: 'gridwidget',

                            defaultBindProperty: 'a',

                            config: {
                                a: ''
                            },

                            constructor: function(config) {
                                ++wCount;
                                this.callParent([config]);

                                if (!o.ignoreFirst || wCount > 1) {
                                    widgets.push(this);
                                }
                            },

                            fireIt: function() {
                                this.fireEvent('widgetevent', this);
                            }
                        });

                        Ext.define('spec.GridWidgetChild', {
                            extend: 'Ext.Component',
                            xtype: 'gridwidgetchild',

                            defaultBindProperty: 'z',

                            config: {
                                z: ''
                            },

                            fireIt: function() {
                                this.fireEvent('childevent', this);
                            }
                        });
                    });

                    afterEach(function() {
                        wCount = 0;
                        widgets = null;
                        Ext.undefine('spec.WidgetVM');
                        Ext.undefine('spec.RowVM');
                        Ext.undefine('spec.GridWidget');
                        Ext.undefine('spec.GridWidgetChild');
                    });

                    function getWidget(index) {
                        o.activateWidget(index);

                        return widgets[index];
                    }

                    function create(widgetCfg, data, gridCfg) {
                        o.createFn(widgetCfg, data, gridCfg);
                    }

                    describe("default viewModel creation", function() {
                        it("should not create a viewmodel by default", function() {
                            create({
                                xtype: 'gridwidget'
                            }, 1);

                            var w = getWidget(0);

                            expect(w.getViewModel()).toBeNull();
                        });

                        it("should create a viewmodel if the widget has a bind statement", function() {
                            create({
                                xtype: 'gridwidget',
                                bind: '{record.id}'
                            }, 1);

                            var w = getWidget(0);

                            expect(w.lookupViewModel().$className).toBe('Ext.app.ViewModel');
                            notify();
                            expect(w.getA()).toBe('rec0');
                        });

                        it("should create a viewmodel if a rowViewModel if specified", function() {
                            create({
                                xtype: 'gridwidget'
                            }, 1, {
                                rowViewModel: {}
                            });

                            var w = getWidget(0);

                            expect(w.lookupViewModel().$className).toBe('Ext.app.ViewModel');
                        });

                        it("should use the viewModel type as a string", function() {
                            create({
                                xtype: 'gridwidget'
                            }, 1, {
                                rowViewModel: 'specgridrow'
                            });

                            var w = getWidget(0);

                            expect(w.lookupViewModel().$className).toBe('spec.RowVM');
                        });

                        it("should use the viewModel type as an object", function() {
                            create({
                                xtype: 'gridwidget'
                            }, 1, {
                                rowViewModel: {
                                    type: 'specgridrow'
                                }
                            });

                            var w = getWidget(0);

                            expect(w.lookupViewModel().$className).toBe('spec.RowVM');
                        });
                    });

                    describe("inheriting viewmodels", function() {
                        describe("viewmodel above grid", function() {
                            var ct, vm;

                            beforeEach(function() {
                                vm = new Ext.app.ViewModel({
                                    data: {
                                        foo: 'xxx'
                                    }
                                });
                            });

                            afterEach(function() {
                                vm = ct = Ext.destroy(ct);
                            });

                            describe("without a rowviewmodel", function() {
                                describe("without widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}'
                                            }
                                        }, 1, {
                                            renderTo: null
                                        });

                                        ct = new Ext.container.Container({
                                            viewModel: vm,
                                            items: grid,
                                            renderTo: Ext.getBody()
                                        });

                                        var w = getWidget(0);

                                        notify();
                                        expect(w.lookupViewModel()).toBe(vm);
                                        expect(w.items.first().getZ()).toBe('xxx');
                                    });
                                });

                                describe("with widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}'
                                            },
                                            viewModel: {
                                                type: 'specwidgetvm',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        }, 1, {
                                            renderTo: null
                                        });

                                        ct = new Ext.container.Container({
                                            viewModel: vm,
                                            items: grid,
                                            renderTo: Ext.getBody()
                                        });

                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.WidgetVM');
                                        expect(wVM.getParent()).toBe(vm);
                                        expect(w.items.first().getZ()).toBe('xxxyyy');
                                    });
                                });
                            });

                            describe("with a rowviewmodel", function() {
                                describe("without widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            bind: '{foo}{bar}',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}'
                                            }
                                        }, 1, {
                                            renderTo: null,
                                            rowViewModel: {
                                                type: 'specgridrow',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        });

                                        ct = new Ext.container.Container({
                                            viewModel: vm,
                                            items: grid,
                                            renderTo: Ext.getBody()
                                        });

                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.RowVM');
                                        expect(wVM.getParent()).toBe(vm);
                                        expect(w.getA()).toBe('xxxyyy');
                                        expect(w.items.first().getZ()).toBe('xxxyyy');
                                    });
                                });

                                describe("with widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            bind: '{foo}{bar}{baz}',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}{baz}'
                                            },
                                            viewModel: {
                                                type: 'specwidgetvm',
                                                data: {
                                                    baz: 'zzz'
                                                }
                                            }
                                        }, 1, {
                                            renderTo: null,
                                            rowViewModel: {
                                                type: 'specgridrow',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        });

                                        ct = new Ext.container.Container({
                                            viewModel: vm,
                                            items: grid,
                                            renderTo: Ext.getBody()
                                        });

                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.WidgetVM');
                                        expect(wVM.getParent().$className).toBe('spec.RowVM');
                                        expect(wVM.getParent().getParent()).toBe(vm);
                                        expect(w.getA()).toBe('xxxyyyzzz');
                                        expect(w.items.first().getZ()).toBe('xxxyyyzzz');
                                    });
                                });
                            });
                        });

                        describe("viewmodel on grid", function() {
                            var vm;

                            beforeEach(function() {
                                vm = new Ext.app.ViewModel({
                                    data: {
                                        foo: 'xxx'
                                    }
                                });
                            });

                            afterEach(function() {
                                vm = null;
                            });

                            describe("without a rowviewmodel", function() {
                                describe("without widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}'
                                            }
                                        }, 1, {
                                            viewModel: vm
                                        });

                                        var w = getWidget(0);

                                        notify();
                                        expect(w.lookupViewModel()).toBe(vm);
                                        expect(w.items.first().getZ()).toBe('xxx');
                                    });
                                });

                                describe("with widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}'
                                            },
                                            viewModel: {
                                                type: 'specwidgetvm',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        }, 1, {
                                            viewModel: vm
                                        });

                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.WidgetVM');
                                        expect(wVM.getParent()).toBe(vm);
                                        expect(w.items.first().getZ()).toBe('xxxyyy');
                                    });
                                });
                            });

                            describe("with a rowviewmodel", function() {
                                describe("without widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            bind: '{foo}{bar}',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}'
                                            }
                                        }, 1, {
                                            viewModel: vm,
                                            rowViewModel: {
                                                type: 'specgridrow',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        });

                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.RowVM');
                                        expect(wVM.getParent()).toBe(vm);
                                        expect(w.getA()).toBe('xxxyyy');
                                        expect(w.items.first().getZ()).toBe('xxxyyy');
                                    });
                                });

                                describe("with widget viewmodel", function() {
                                    it("should hook up rows to the parent vm", function() {
                                        create({
                                            xtype: 'gridwidget',
                                            bind: '{foo}{bar}{baz}',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}{baz}'
                                            },
                                            viewModel: {
                                                type: 'specwidgetvm',
                                                data: {
                                                    baz: 'zzz'
                                                }
                                            }
                                        }, 1, {
                                            viewModel: vm,
                                            rowViewModel: {
                                                type: 'specgridrow',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        });

                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.WidgetVM');
                                        expect(wVM.getParent().$className).toBe('spec.RowVM');
                                        expect(wVM.getParent().getParent()).toBe(vm);
                                        expect(w.getA()).toBe('xxxyyyzzz');
                                        expect(w.items.first().getZ()).toBe('xxxyyyzzz');
                                    });
                                });
                            });
                        });

                        describe("no viewmodel on grid", function() {
                            describe("without a rowviewmodel", function() {
                                describe("without widget viewmodel", function() {
                                    it("should not throw an error", function() {
                                        // We can't bind here, but it should still render
                                        create({
                                            xtype: 'gridwidget',
                                            a: 1,
                                            items: {
                                                xtype: 'gridwidgetchild'
                                            }
                                        }, 1);

                                        var w = getWidget(0);

                                        expect(w.getA()).toBe(1);
                                    });
                                });

                                describe("with widget viewmodel", function() {
                                    beforeEach(function() {
                                        create({
                                            xtype: 'gridwidget',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}'
                                            },
                                            viewModel: {
                                                type: 'specwidgetvm',
                                                data: {
                                                    foo: 'xxx'
                                                }
                                            }
                                        }, 5);
                                    });

                                    it("should hook up rows to the parent vm", function() {
                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.WidgetVM');
                                        expect(w.items.first().getZ()).toBe('xxx');
                                    });

                                    it("should share a scheduler for all widgets", function() {
                                        var scheduler = getWidget(0).getViewModel().getScheduler();

                                        expect(getWidget(1).getViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(2).getViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(3).getViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(4).getViewModel().getScheduler()).toBe(scheduler);
                                    });
                                });
                            });

                            describe("with a rowviewmodel", function() {
                                describe("without widget viewmodel", function() {
                                    beforeEach(function() {
                                        create({
                                            xtype: 'gridwidget',
                                            bind: '{foo}',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}'
                                            }
                                        }, 5, {
                                            rowViewModel: {
                                                type: 'specgridrow',
                                                data: {
                                                    foo: 'xxx'
                                                }
                                            }
                                        });
                                    });

                                    it("should hook up rows to the parent vm", function() {
                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.RowVM');
                                        expect(w.getA()).toBe('xxx');
                                        expect(w.items.first().getZ()).toBe('xxx');
                                    });

                                    it("should share a scheduler for all widgets", function() {
                                        var scheduler = getWidget(0).lookupViewModel().getScheduler();

                                        expect(getWidget(1).lookupViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(2).lookupViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(3).lookupViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(4).lookupViewModel().getScheduler()).toBe(scheduler);
                                    });
                                });

                                describe("with widget viewmodel", function() {
                                    beforeEach(function() {
                                        create({
                                            xtype: 'gridwidget',
                                            bind: '{foo}{bar}',
                                            items: {
                                                xtype: 'gridwidgetchild',
                                                bind: '{foo}{bar}'
                                            },
                                            viewModel: {
                                                type: 'specwidgetvm',
                                                data: {
                                                    bar: 'yyy'
                                                }
                                            }
                                        }, 5, {
                                            rowViewModel: {
                                                type: 'specgridrow',
                                                data: {
                                                    foo: 'xxx'
                                                }
                                            }
                                        });
                                    });

                                    it("should hook up rows to the parent vm", function() {
                                        var w = getWidget(0),
                                            wVM = w.lookupViewModel();

                                        notify();
                                        expect(wVM.$className).toBe('spec.WidgetVM');
                                        expect(wVM.getParent().$className).toBe('spec.RowVM');
                                        expect(w.getA()).toBe('xxxyyy');
                                        expect(w.items.first().getZ()).toBe('xxxyyy');
                                    });

                                    it("should share a scheduler for all widgets", function() {
                                        var scheduler = getWidget(0).getViewModel().getScheduler();

                                        expect(getWidget(1).getViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(2).getViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(3).getViewModel().getScheduler()).toBe(scheduler);
                                        expect(getWidget(4).getViewModel().getScheduler()).toBe(scheduler);
                                    });
                                });
                            });
                        });
                    });

                    describe("data", function() {
                        it("should put the record and recordIndex in the vm", function() {
                            create({
                                xtype: 'gridwidget',
                                bind: '{record.id} - {recordIndex}'
                            }, [{ id: 101 }, { id: 102 }, { id: 103 }, { id: 104 }]);

                            // Force widget creation in expander mode
                            getWidget(0);
                            getWidget(1);
                            getWidget(2);
                            getWidget(3);

                            notify();

                            expect(getWidget(0).getA()).toBe('101 - 0');
                            expect(getWidget(1).getA()).toBe('102 - 1');
                            expect(getWidget(2).getA()).toBe('103 - 2');
                            expect(getWidget(3).getA()).toBe('104 - 3');
                        });
                    });

                    describe("resolving listeners", function() {
                        beforeEach(function() {
                            Ext.define('spec.GridController', {
                                extend: 'Ext.app.ViewController',
                                alias: 'controller.specgrid',

                                onFoo: function() {},
                                onBar: function() {}
                            });
                        });

                        afterEach(function() {
                            Ext.undefine('spec.GridController');
                        });

                        describe("controller above the grid", function() {
                            var ct;

                            afterEach(function() {
                                ct = Ext.destroy(ct);
                            });

                            it("should resolve to the controller", function() {
                                create({
                                    xtype: 'gridwidget',
                                    listeners: {
                                        widgetevent: 'onFoo'
                                    },
                                    items: {
                                        xtype: 'gridwidgetchild',
                                        listeners: {
                                            childevent: 'onBar'
                                        }
                                    }
                                }, 1, {
                                    renderTo: null
                                });

                                var controller = new spec.GridController();

                                spyOn(controller, 'onFoo');
                                spyOn(controller, 'onBar');

                                ct = new Ext.container.Container({
                                    controller: controller,
                                    items: grid,
                                    renderTo: Ext.getBody()
                                });

                                var w = getWidget(0);

                                w.fireIt();
                                expect(controller.onFoo.callCount).toBe(1);

                                w.items.first().fireIt();
                                expect(controller.onBar.callCount).toBe(1);
                            });
                        });

                        describe("controller on the grid", function() {
                            it("should resolve to the controller", function() {
                                var controller = new spec.GridController();

                                spyOn(controller, 'onFoo');
                                spyOn(controller, 'onBar');

                                create({
                                    xtype: 'gridwidget',
                                    listeners: {
                                        widgetevent: 'onFoo'
                                    },
                                    items: {
                                        xtype: 'gridwidgetchild',
                                        listeners: {
                                            childevent: 'onBar'
                                        }
                                    }
                                }, 1, {
                                    controller: controller
                                });

                                var w = getWidget(0);

                                w.fireIt();
                                expect(controller.onFoo.callCount).toBe(1);

                                w.items.first().fireIt();
                                expect(controller.onBar.callCount).toBe(1);
                            });
                        });

                        describe("method on the widget view", function() {
                            beforeEach(function() {
                                Ext.define('spec.SelfWidget', {
                                    extend: 'Ext.Component',
                                    xtype: 'selfwidget',

                                    constructor: function(config) {
                                        ++wCount;
                                        this.callParent([config]);

                                        if (!o.ignoreFirst || wCount > 1) {
                                            widgets.push(this);
                                        }
                                    },

                                    fireIt: function() {
                                        this.fireEvent('selfevent', this);
                                    },

                                    onSelfEvent: function() {},

                                    defaultListenerScope: true,
                                    listeners: {
                                        selfEvent: 'onSelfEvent'
                                    }
                                });
                            });

                            afterEach(function() {
                                Ext.undefine('spec.SelfWidget');
                            });

                            it("should resolve to the widget method", function() {
                                create({
                                    xtype: 'selfwidget'
                                }, 1);

                                var w = getWidget(0);

                                spyOn(w, 'onSelfEvent');

                                w.fireIt();
                                expect(w.onSelfEvent.callCount).toBe(1);
                            });
                        });

                        describe("method on the widget controller", function() {
                            beforeEach(function() {
                                Ext.define('spec.ControllerWidget', {
                                    extend: 'Ext.Component',
                                    xtype: 'controllerwidget',

                                    controller: 'specgrid',

                                    constructor: function(config) {
                                        ++wCount;
                                        this.callParent([config]);

                                        if (!o.ignoreFirst || wCount > 1) {
                                            widgets.push(this);
                                        }
                                    },

                                    fireIt: function() {
                                        this.fireEvent('controllerevent', this);
                                    },

                                    listeners: {
                                        controllerevent: 'onFoo'
                                    }
                                });
                            });

                            afterEach(function() {
                                Ext.undefine('spec.ControllerWidget');
                            });

                            it("should resolve to the controller method", function() {
                                create({
                                    xtype: 'controllerwidget'
                                }, 1);

                                var w = getWidget(0),
                                    controller = w.getController();

                                spyOn(controller, 'onFoo');

                                w.fireIt();
                                expect(controller.onFoo.callCount).toBe(1);
                            });
                        });
                    });

                    describe("bindings", function() {
                        describe("hidden", function() {
                            beforeEach(function() {
                                create({
                                    xtype: 'gridwidget',
                                    bind: {
                                        hidden: '{record.field1}'
                                    }
                                }, [{
                                    field1: false
                                }, {
                                    field1: true
                                }]);
                                getWidget(0);
                                getWidget(1);
                                notify();
                            });

                            it("should bind to the hidden state", function() {
                                expect(getWidget(0).isVisible()).toBe(true);
                                expect(getWidget(1).isVisible()).toBe(false);
                            });

                            it("should be able to bind to hidden state after reloading data", function() {
                                store.loadData([{
                                    field1: true
                                }, {
                                    field1: false
                                }]);
                                getWidget(0);
                                getWidget(1);
                                notify();
                                expect(getWidget(0).isVisible()).toBe(false);
                                expect(getWidget(1).isVisible()).toBe(true);
                            });
                        });
                    });
                }

                describe("widget column", function() {
                    makeBindSuite({
                        ignoreFirst: true,
                        createFn: function(widgetCfg, data, gridCfg) {
                            makeGrid([{
                                xtype: 'widgetcolumn',
                                widget: widgetCfg
                            }], data, gridCfg);
                        },
                        activateWidget: Ext.emptyFn
                    });
                });

                describe("row widget", function() {
                    makeBindSuite({
                        ignoreFirst: false,
                        createFn: function(widgetCfg, data, gridCfg) {
                            gridCfg = gridCfg || {};
                            gridCfg.plugins = [{
                                ptype: 'rowwidget',
                                widget: widgetCfg
                            }];
                            makeGrid([{
                                dataIndex: 'field1'
                            }], data, gridCfg);
                        },
                        activateWidget: function(index) {
                            var expander = grid.plugins[0];

                            expander.toggleRow(index, store.getAt(index));
                        }
                    });
                });
            });

            describe("reconfigure", function() {
                describe("setStore", function() {
                    var storeChangeSpy;

                    describe("without locking", function() {
                        function expectNodeLength(n) {
                            expect(view.getNodes().length).toBe(n);
                        }

                        it("should be able to clear the store", function() {
                            makeGrid(null, undefined, null, null);
                            storeChangeSpy = spyOnEvent(grid, 'storechange');
                            grid.setStore(null);
                            expectNodeLength(0);
                            expect(storeChangeSpy.callCount).toBe(1);
                        });
                    });

                    describe("with locking", function() {
                        function expectNodeLength(n) {
                            expect(grid.lockedGrid.getView().getNodes().length).toBe(n);
                            expect(grid.normalGrid.getView().getNodes().length).toBe(n);
                        }

                        it("should be able to clear the store", function() {
                            makeGrid(null, undefined, null, null, true);
                            storeChangeSpy = spyOnEvent(grid, 'storechange');
                            grid.setStore(null);
                            expectNodeLength(0);
                            expect(storeChangeSpy.callCount).toBe(1);
                        });

                        it("should not throw errors if the grid is hidden", function() {
                            var p = new Ext.panel.Panel({
                                renderTo: Ext.getBody(),
                                collapsed: true,
                                items: {
                                    xtype: 'gridpanel',
                                    enableLocking: true
                                }
                            });

                            expect(function() {
                                p.items.first().reconfigure(null, [{ dataIndex: 'name' }]);
                            }).not.toThrow();

                            p.destroy();
                        });
                    });
                });

                describe("scroller", function() {
                    it("should scroll to top", function() {
                        var columns = [{
                            dataIndex: 'field1',
                            text: 'Field 1',
                            width: 90
                        }];

                        makeGrid(columns, 50);

                        grid.getView().getScrollable().scrollTo(null, 300, false);
                        grid.reconfigure(makeStore(makeData(50, columns)));

                        expect(grid.getView().getScrollable().position.y).toBe(0);
                    });

                    it("should reconfigure the scroller if needed", function() {
                        var columnsA = [{
                            width: 100,
                            dataIndex: 'field1'
                        }],
                        columnsB = [{
                            width: 300,
                            dataIndex: 'field1'
                        }, {
                            width: 300,
                            dataIndex: 'field2'
                        }];

                        makeGrid(columnsA, 5, {
                            width: 400,
                            height: 400
                        });

                        // Safari and Android are always scrollable
                        if (!Ext.isSafari && !Ext.isAndroid) {
                            expect(grid.getView().getScrollable().getX()).toBe(false);
                        }

                        grid.reconfigure(null, columnsB);

                        expect(grid.getView().getScrollable().getX()).toBe(true);
                    });
                });

                function makeReconfigureSuite(beforeRender) {
                    var storeChangeSpy;

                    function makeReconfigureGrid(columns, data, cfg, options, locked) {
                        cfg = cfg || {};

                        if (beforeRender) {
                            cfg.renderTo = null;
                        }

                        makeGrid(columns, data, cfg, options, locked);
                        storeChangeSpy = spyOnEvent(grid, 'storechange');
                    }

                    function reconfigure(store, columns, allowUnbind) {
                        var oldStore = grid.store,
                            storeChangeCount = storeChangeSpy.callCount;

                        grid.reconfigure.apply(grid, arguments);

                        if (beforeRender) {
                            grid.render(Ext.getBody());
                        }

                        // Passing store as null only unbinds if allowUnbind is passed truthy
                        if (store !== oldStore && (store || allowUnbind)) {
                            expect(storeChangeSpy.callCount).toBe(storeChangeCount + 1);
                        }
                    }

                    describe(beforeRender ? "before render" : "after render", function() {
                        describe("store only", function() {
                            describe("without locking", function() {
                                function expectNodeLength(n) {
                                    expect(view.getNodes().length).toBe(n);
                                }

                                describe("with no store", function() {
                                    beforeEach(function() {
                                        store = null;

                                        makeReconfigureGrid([{
                                            dataIndex: 'field1'
                                        }], undefined, null, { preventStoreCreate: true });
                                        reconfigure(makeStore());
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });
                                });

                                describe("with an existing store", function() {
                                    var oldStore;

                                    beforeEach(function() {
                                        makeReconfigureGrid([{
                                            dataIndex: 'field1'
                                        }], 20);
                                        oldStore = store;
                                        reconfigure(makeStore());
                                    });

                                    afterEach(function() {
                                        oldStore = Ext.destroy(oldStore);
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind the new store to the loadMask", function() {
                                        expect(grid.getView().loadMask.store).toBe(store);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should not react to the old store", function() {
                                        oldStore.add([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                        oldStore.removeAt(0);
                                        expectNodeLength(1);
                                        oldStore.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('1');
                                        oldStore.loadData([{}, {}, {}, {}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                    });
                                });
                            });

                            describe("with locking", function() {
                                function expectNodeLength(n) {
                                    expect(grid.lockedGrid.getView().getNodes().length).toBe(n);
                                    expect(grid.normalGrid.getView().getNodes().length).toBe(n);
                                }

                                describe("with no store", function() {
                                    beforeEach(function() {
                                        store = null;

                                        makeReconfigureGrid([{
                                            locked: true,
                                            dataIndex: 'field1'
                                        }, {
                                            dataIndex: 'field2'
                                        }], undefined, null, { preventStoreCreate: true });
                                        reconfigure(makeStore());
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });
                                });

                                describe("with an existing store", function() {
                                    var oldStore;

                                    beforeEach(function() {
                                        makeReconfigureGrid([{
                                            locked: true,
                                            dataIndex: 'field1'
                                        }, {
                                            dataIndex: 'field2'
                                        }], 20);
                                        oldStore = store;
                                        reconfigure(makeStore());
                                    });

                                    afterEach(function() {
                                        oldStore = Ext.destroy(oldStore);
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind the new store to the loadMask", function() {
                                        expect(grid.getView().loadMask.store).toBe(store);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should not react to the old store", function() {
                                        oldStore.add([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                        oldStore.removeAt(0);
                                        expectNodeLength(1);
                                        oldStore.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('1');
                                        oldStore.loadData([{}, {}, {}, {}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                    });
                                });
                            });
                        });

                        describe("columns only", function() {
                            var oldCols;

                            afterEach(function() {
                                oldCols = null;
                            });

                            describe("without locking", function() {
                                describe("with no columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid(null, undefined, null, { preventColumnCreate: true });
                                        oldCols = colRef;
                                        reconfigure(null, [{
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });
                                });

                                describe("with existing columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid([{
                                            dataIndex: 'field1'
                                        }]);
                                        oldCols = colRef;
                                        reconfigure(null, [{
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should destroy old columns", function() {
                                        expect(oldCols[0].destroyed).toBe(true);
                                    });
                                });

                                it("should only refresh after the columns have been rendered", function() {
                                    var spy = jasmine.createSpy(),
                                        renderCount, refreshCounter, view;

                                    makeReconfigureGrid();
                                    view = grid.getView();
                                    refreshCounter = view.refreshCounter || 0;
                                    view.on('refresh', function() {
                                        renderCount = spy.callCount;
                                    });

                                    reconfigure(null, [{
                                        dataIndex: 'field2',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }, {
                                        dataIndex: 'field7',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }]);
                                    expect(renderCount).toBe(2);
                                    expect(view.refreshCounter).toBe(refreshCounter + 1);
                                });
                            });

                            describe("with locking", function() {
                                describe("with no columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid(null, undefined, { enableLocking: true }, { preventColumnCreate: true });
                                        oldCols = colRef;
                                        reconfigure(null, [{
                                            locked: true,
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should process locked/unlocked columns", function() {
                                        expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                        expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                    });
                                });

                                describe("with existing columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid([{
                                            locked: true,
                                            dataIndex: 'field1'
                                        }, {
                                            dataIndex: 'field3'
                                        }]);
                                        oldCols = colRef;
                                        reconfigure(null, [{
                                            locked: true,
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should process locked/unlocked columns", function() {
                                        expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                        expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                    });

                                    it("should destroy old columns", function() {
                                        expect(oldCols[0].destroyed).toBe(true);
                                        expect(oldCols[1].destroyed).toBe(true);
                                    });
                                });

                                it("should only refresh after the columns have been rendered", function() {
                                    var spy = jasmine.createSpy(),
                                        renderCount, refreshCounter, view;

                                    makeReconfigureGrid([{
                                        locked: true,
                                        dataIndex: 'field1'
                                    }, {
                                        dataIndex: 'field3'
                                    }]);

                                    view = grid.lockedGrid.getView();
                                    refreshCounter = view.refreshCounter || 0;

                                    view.on('refresh', function() {
                                        renderCount = spy.callCount;
                                    });

                                    reconfigure(null, [{
                                        locked: true,
                                        dataIndex: 'field2',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }, {
                                        dataIndex: 'field7',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }]);
                                    expect(renderCount).toBe(2);
                                    expect(view.refreshCounter).toBe(refreshCounter + 1);
                                });
                            });
                        });

                        describe("store & columns", function() {
                            var oldCols, oldStore;

                            afterEach(function() {
                                Ext.destroy(oldStore);
                                oldStore = oldCols = null;
                            });

                            describe("without locking", function() {
                                function expectNodeLength(n) {
                                    expect(view.getNodes().length).toBe(n);
                                }

                                describe("with no store and no columns", function() {
                                    beforeEach(function() {
                                        store = null;

                                        makeReconfigureGrid(null,  undefined, null, { preventStoreCreate: true, preventColumnCreate: true });
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });
                                });

                                describe("with an existing store and no columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid(null,  undefined, null, { preventColumnCreate: true });
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should not react to the old store", function() {
                                        oldStore.add([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                        oldStore.removeAt(0);
                                        expectNodeLength(1);
                                        oldStore.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('2');
                                        oldStore.loadData([{}, {}, {}, {}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });
                                });

                                describe("with no store and existing columns", function() {
                                    beforeEach(function() {
                                        store = null;

                                        makeReconfigureGrid([{
                                            dataIndex: 'field1'
                                        }],  undefined, null, { preventStoreCreate: true });
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should destroy old columns", function() {
                                        expect(oldCols[0].destroyed).toBe(true);
                                    });
                                });

                                describe("with an existing store and existing columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid([{
                                            dataIndex: 'field1'
                                        }]);
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should not react to the old store", function() {
                                        oldStore.add([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                        oldStore.removeAt(0);
                                        expectNodeLength(1);
                                        oldStore.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('2');
                                        oldStore.loadData([{}, {}, {}, {}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should destroy old columns", function() {
                                        expect(oldCols[0].destroyed).toBe(true);
                                    });
                                });

                                it("should only refresh after the columns have been rendered", function() {
                                    var spy = jasmine.createSpy(),
                                        renderCount, refreshCounter, view;

                                    makeReconfigureGrid();

                                    oldStore = store;
                                    view = grid.getView();
                                    refreshCounter = view.refreshCounter || 0;

                                    view.on('refresh', function() {
                                        renderCount = spy.callCount;
                                    });

                                    reconfigure(store, [{
                                        dataIndex: 'field2',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }, {
                                        dataIndex: 'field7',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }]);
                                    expect(renderCount).toBe(2);
                                    expect(view.refreshCounter).toBe(refreshCounter + 1);
                                });
                            });

                            describe("with locking", function() {
                                function expectNodeLength(n) {
                                    expect(grid.lockedGrid.getView().getNodes().length).toBe(n);
                                    expect(grid.normalGrid.getView().getNodes().length).toBe(n);
                                }

                                describe("with no store and no columns", function() {
                                    beforeEach(function() {
                                        store = null;

                                        makeReconfigureGrid(null,  undefined, { enableLocking: true }, { preventStoreCreate: true, preventColumnCreate: true });
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            locked: true,
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should process locked/unlocked columns", function() {
                                        expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                        expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                    });
                                });

                                describe("with an existing store and no columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid(null,  undefined, { enableLocking: true }, { preventColumnCreate: true });
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            locked: true,
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should not react to the old store", function() {
                                        oldStore.add([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                        oldStore.removeAt(0);
                                        expectNodeLength(1);
                                        oldStore.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('2');
                                        oldStore.loadData([{}, {}, {}, {}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should process locked/unlocked columns", function() {
                                        expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                        expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                    });
                                });

                                describe("with no store and existing columns", function() {
                                    beforeEach(function() {
                                        store = null;

                                        makeReconfigureGrid([{
                                            locked: true,
                                            dataIndex: 'field1'
                                        }, {
                                            dataIndex: 'field3'
                                        }],  undefined, null, { preventStoreCreate: true });
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            locked: true,
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should process locked/unlocked columns", function() {
                                        expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                        expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                    });

                                    it("should destroy old columns", function() {
                                        expect(oldCols[0].destroyed).toBe(true);
                                        expect(oldCols[1].destroyed).toBe(true);
                                    });
                                });

                                describe("with an existing store and existing columns", function() {
                                    beforeEach(function() {
                                        makeReconfigureGrid([{
                                            locked: true,
                                            dataIndex: 'field1'
                                        }, {
                                            dataIndex: 'field3'
                                        }]);
                                        oldCols = colRef;
                                        oldStore = store;
                                        reconfigure(makeStore(), [{
                                            locked: true,
                                            dataIndex: 'field2'
                                        }, {
                                            dataIndex: 'field7'
                                        }]);
                                        colRef = grid.getVisibleColumnManager().getColumns();
                                    });

                                    it("should render data from the new store", function() {
                                        expectNodeLength(1);
                                    });

                                    it("should react to store", function() {
                                        store.add({});
                                        expectNodeLength(2);
                                    });

                                    it("should react to store remove", function() {
                                        store.removeAt(0);
                                        expectNodeLength(0);
                                    });

                                    it("should react to store update", function() {
                                        store.getAt(0).set('field2', 'Foo');
                                        expect(getCellText(0, 0)).toBe('Foo');
                                    });

                                    it("should react to store load", function() {
                                        store.loadData([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(6);
                                    });

                                    it("should bind to the selection model", function() {
                                        expect(grid.getView().getSelectionModel().getStore()).toBe(store);
                                    });

                                    it("should not react to the old store", function() {
                                        oldStore.add([{}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                        oldStore.removeAt(0);
                                        expectNodeLength(1);
                                        oldStore.getAt(0).set('field1', 'Foo');
                                        expect(getCellText(0, 0)).toBe('2');
                                        oldStore.loadData([{}, {}, {}, {}, {}, {}, {}, {}, {}]);
                                        expectNodeLength(1);
                                    });

                                    it("should add the new columns and render the contents", function() {
                                        expect(colRef.length).toBe(2);
                                        expect(getCellText(0, 0)).toBe('2');
                                        expect(getCellText(0, 1)).toBe('7');
                                    });

                                    it("should process locked/unlocked columns", function() {
                                        expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                        expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(1);
                                    });

                                    it("should destroy old columns", function() {
                                        expect(oldCols[0].destroyed).toBe(true);
                                        expect(oldCols[1].destroyed).toBe(true);
                                    });
                                });

                                it("should only refresh after the columns have been rendered", function() {
                                    var spy = jasmine.createSpy(),
                                        renderCount, refreshCounter, view;

                                    makeReconfigureGrid([{
                                        locked: true,
                                        dataIndex: 'field1'
                                    }, {
                                        dataIndex: 'field3'
                                    }]);
                                    oldStore = store;

                                    view = grid.lockedGrid.getView();
                                    refreshCounter = view.refreshCounter || 0;

                                    view.on('refresh', function() {
                                        renderCount = spy.callCount;
                                    });

                                    reconfigure(makeStore(), [{
                                        locked: true,
                                        dataIndex: 'field2',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }, {
                                        dataIndex: 'field7',
                                        listeners: {
                                            afterrender: spy
                                        }
                                    }]);
                                    expect(renderCount).toBe(2);
                                    expect(view.refreshCounter).toBe(refreshCounter + 1);
                                });
                            });
                        });
                    });
                }

                makeReconfigureSuite(false);
                makeReconfigureSuite(true);

                it("should only refresh the view once", function() {
                    var count = 0;

                    makeGrid([{
                        dataIndex: 'field1'
                    }]);
                    grid.getView().on('refresh', function() {
                        ++count;
                    });
                    grid.reconfigure(null, [{
                        dataIndex: 'field2'
                    }, {
                        dataIndex: 'field3'
                    }, {
                        dataIndex: 'field4'
                    }, {
                        dataIndex: 'field5'
                    }, {
                        dataIndex: 'field6'
                    }, {
                        dataIndex: 'field7'
                    }]);
                    expect(count).toBe(1);
                });
            });

            if (buffered) {
                describe("buffered row rendering", function() {
                    var view,
                        rows,
                        bufferedRenderer,
                        data,
                        scrollTop,
                        viewSize,
                        rowHeight,
                        sStyle;

                    beforeEach(function() {

                        // Important
                        // The scrollHeight matching must allow a 1px deviation.
                        // ScrollHeight is sometimes reported as 1px more than the actual content height.
                        var i, j,
                            row;

                        data = [];

                        for (i = 0; i < 100; i++) {
                            row = {};

                            for (j = 1; j < 11; j++) {
                                row['field' + j] = 'r' + i + ',f' + j;
                            }

                            data.push(row);
                        }

                        // Grid and data must be exactly the right shape for the tests to perform as expected!
                        // Enforce buffer zone defaults so that the rendered viewSize is reasonable.
                        // makeGrid function defeats buffered rendering by imposing large buffer zones.
                        makeGrid(null, data, {
                            width: 1000,
                            height: 500,
                            border: false,
                            trailingBufferZone: Ext.grid.plugin.BufferedRenderer.prototype.trailingBufferZone,
                            leadingBufferZone: Ext.grid.plugin.BufferedRenderer.prototype.leadingBufferZone
                        });
                        view = grid.getView();
                        rows = view.all;
                        bufferedRenderer = view.bufferedRenderer;
                        sStyle = view.getScrollable().getSpacer().dom.style;

                        // Get as close to 15 visible rows as possible
                        grid.setHeight(bufferedRenderer.rowHeight * 15 + grid.headerCt.getHeight());
                        viewSize = bufferedRenderer.viewSize;

                        // For Safari, we have to force a synchronous layout for scroll values to be updated in this event thread
                        store.on({
                            datachanged: function() {
                                var st = view.el.dom.scrollTop;

                                // This flip-flops between 0px and 1px
                                sStyle.lineHeight = Number(!parseInt(sStyle.lineHeight)) + 'px';

                                // force a layout. Safari does not update the scrollHeight without this
                                // and so tests which test the scrollHeight fail.
                                view.el.dom.scrollTop += 1;
                                // eslint-disable-next-line no-unused-expressions
                                view.el.dom.scrollHeight;
                                view.el.dom.scrollTop = st;
                            }
                        });
                    });

                    it("should handle removing range above the rendered view", function() {
                        var oldRowStartIndex;

                        rowHeight = bufferedRenderer.rowHeight;

                        expect(rows.getCount()).toBe(viewSize);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.getScrollY();
                        oldRowStartIndex = rows.startIndex;

                        store.removeAt(0, 10);

                        // Rendered block has chunked upwards by 10
                        expect(rows.startIndex).toBe(oldRowStartIndex - 10);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBe(rowHeight * store.getCount());

                        // Operation should bump us up the scroll range
                        expect(view.getScrollY()).toBeLessThan(scrollTop);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(rows.startIndex * rowHeight - view.body.getBorderWidth('t'));
                    });

                    it("should handle removing range which intersects the top of the rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.getScrollY();

                        store.removeAt(rows.startIndex - 10, 20);

                        // Safari now appears to need a forced synchronous layout in order to produce
                        // the up to date scrollHeight;
                        // eslint-disable-next-line no-unused-expressions
                        view.body.dom.offsetHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        // Operation should bump us up the scroll range
                        expect(view.getScrollY()).toBeLessThan(scrollTop);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(rows.startIndex * rowHeight - view.body.getBorderWidth('t'));
                    });

                    it("should handle removing range wholly within the rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.getScrollY();

                        store.removeAt(52, 10);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBe(rowHeight * store.getCount());

                        // Operation should not affect scrollTop
                        expect(view.getScrollY()).toBe(scrollTop);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(rows.startIndex * rowHeight - view.body.getBorderWidth('t'));
                    });

                    it("should handle removing range which intersects the bottom of the rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.getScrollY();

                        store.removeAt(rows.endIndex - 10, 20);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        // Operation should not affect scrollTop
                        expect(view.getScrollY()).toBe(scrollTop);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(rows.startIndex * rowHeight - view.body.getBorderWidth('t'));
                    });

                    it("should handle removing range which removes everything from halfway down rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.getScrollY();

                        // RemoveAt's second input, the number to remove is sanity checked.
                        // This will remove everything from the middle of the view
                        store.removeAt(Math.floor((rows.endIndex - rows.startIndex) / 2), 1000);

                        // Constant row height, so we know the scroll range.
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        // Fewer rows than viewSize now left in store
                        expect(rows.getCount()).toBe(store.getCount());

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(0);
                    });

                    it("should handle removing range which removes from top to halfway down rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.getScrollY();

                        // RemoveAt's second input, the number to remove is sanity checked.
                        // This will remove everything from the middle of the view
                        store.removeAt(0, Math.floor(rows.startIndex + viewSize / 2));

                        // Constant row height, so we know the scroll range.
                        // Use toBeWithin matcher here.
                        // When there's no need for a stretcher, and the table creates the scroll range
                        // it appears to be 1 greater than the table's offsetHeight.
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        // Fewer rows than viewSize now left in store
                        expect(rows.getCount()).toBe(store.getCount());

                        // Operation should bump us to the top
                        expect(view.getScrollY()).toBe(0);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(0);
                    });

                    it("should handle removing range below the rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64); // was 50
                        scrollTop = view.getScrollY();

                        store.removeAt(rows.endIndex + 10, 10);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBe(rowHeight * store.getCount());

                        // Operation should not affect scrollTop
                        expect(view.getScrollY()).toBe(scrollTop);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(rows.startIndex * rowHeight - view.body.getBorderWidth('t'));
                    });

                    it("should handle removing range which encompasses the rendered view and leaves less than view size rows remaining", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(44); // was 30
                        scrollTop = view.getScrollY();

                        store.removeAt(5, 90);

                        // No scrolling now with only 10 rows left!
                        expect(view.el.dom.scrollHeight).toBeWithin(1, view.el.dom.clientHeight);

                        // Obviously... no scrolling with only 10 rows left!
                        expect(view.getScrollY()).toBe(0);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(10);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(0);
                    });

                    it("should handle removing range which encompasses the rendered view", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(44);
                        scrollTop = view.getScrollY();

                        store.removeAt(rows.startIndex - 1, viewSize + 2);

                        // Constant row height, so we know the scroll range
                        expect(bufferedRenderer.bodyTop + view.body.dom.offsetHeight - 1).toBe(rowHeight * store.getCount());

                        // We lose scroll range
                        expect(view.el.dom.scrollTop).toBeLessThan(scrollTop);

                        // We hit the end, so there's a bit lacking at the top
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(Math.max(rows.startIndex * rowHeight, 0));
                    });

                    it("should handle removing range which leaves less than the viewSize rows in store", function() {
                        rowHeight = bufferedRenderer.rowHeight;

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(34);
                        scrollTop = view.getScrollY();

                        store.removeAt(0, store.getCount() - 10);

                        // No scrolling now with only 10 rows left!
                        expect(view.el.dom.scrollHeight).toBe(view.el.dom.clientHeight);

                        // Obviously... no scrolling with only 10 rows left!
                        expect(view.getScrollY()).toBe(0);

                        // View can only contain 10 rows
                        expect(rows.getCount()).toBe(10);

                        // The table should be at top
                        expect(getViewTop(view.body)).toBe(0);
                    });

                    it("Should prepend to the rendered block when inserting at position 0", function() {
                        var cell00;

                        scrollTop = view.getScrollY();

                        store.insert(0, {
                            field1: 'Test'
                        });

                        cell00 = view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true);

                        // Operation should bump us down the scroll range
                        expect(view.getScrollY()).toBeGreaterThan(scrollTop);

                        // View item must be added
                        expect(Ext.String.trim(cell00.textContent || cell00.innerText)).toBe('Test');

                        // The table should be at top
                        expect(getViewTop(view.body)).toBe(0);
                    });

                    it("should handle adding range above the rendered view", function() {
                        var oldRowStartIndex;

                        rowHeight = bufferedRenderer.rowHeight;

                        expect(rows.getCount()).toBe(viewSize);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBeWithin(1, rowHeight * store.getCount());

                        bufferedRenderer.scrollTo(64);
                        scrollTop = view.el.dom.scrollTop;
                        oldRowStartIndex = rows.startIndex;

                        // Insert 10 rows above the rendered block
                        store.insert(0, [
                            {}, {}, {}, {}, {}, {}, {}, {}, {}, {}
                        ]);

                        // Rendered block has chunked upwards by 10
                        expect(rows.startIndex).toBe(oldRowStartIndex + 10);

                        // Constant row height, so we know the scroll range
                        expect(view.el.dom.scrollHeight).toBe(rowHeight * store.getCount());

                        // Operation should bump us down the scroll range
                        expect(view.el.dom.scrollTop).toBeGreaterThan(scrollTop);

                        // Operation should not affect the rendered row count
                        expect(rows.getCount()).toBe(viewSize);

                        // The table should be positioned according to the start index of the rendered view
                        expect(getViewTop(view.body)).toBe(rows.startIndex * rowHeight - view.body.getBorderWidth('t'));
                    });

                    it("should render rows when buffered block is less than the view size", function() {
                        var v = viewSize,
                            targetViewSize = v - 10;

                        // We need less than viewSize rows to be there
                        store.removeAt(targetViewSize, store.getCount() - targetViewSize);

                        // Now there are the number of rows we require (ten less than  viewSize for the grid height)
                        // And we're at the top
                        expect(rows.getCount()).toBe(targetViewSize);
                        expect(bufferedRenderer.scrollTop).toBe(0);
                        expect(bufferedRenderer.position).toBe(0);

                        store.add({});

                        // Must has added a row
                        expect(rows.getCount()).toBe(targetViewSize + 1);
                        expect(bufferedRenderer.scrollTop).toBe(0);
                        expect(bufferedRenderer.position).toBe(0);
                    });
                });

                describe("initial correction of viewSize", function() {
                    describe("when rowHeight is smaller than expected and viewSize has to grow", function() {
                        var i,
                            data,
                            columns,
                            empty = ' '; // Empty string with a space so that renderer does not insert &nbsp;

                        beforeEach(function() {
                            data = [];

                            // We need a lot of rows so that we get to add more than the original default viewSize
                            for (i = 0; i < 200; i++) {
                                data.push({
                                    field0: 'r' + i + ',f0'
                                });
                            }

                            columns = [{
                                dataIndex: 'field0',
                                text: 'Field 0',
                                width: 90,

                                // Empty cell. Should only be paddingTop+paddingBottom high
                                renderer: function() {
                                    return empty;
                                }
                            }];
                        });

                        it("should append new rows to the view", function() {
                            // Make like mobile dev, and only overhang 1 row each side
                            // Make it tall enough so that the default viewSize of 100 will leave
                            // the view short, and BufferedRenderer will have to add rows.
                            makeGrid(columns, data, {
                                height: 900,
                                border: false,

                                // We want to get spies in before render
                                renderTo: null,
                                trailingBufferZone: 1,
                                leadingBufferZone: 1
                            });
                            var getRangeSpy = spyOn(store, 'getRange').andCallThrough();

                            grid.render(document.body);

                            // Only one refresh. The initial refresh which inserts the default viewSize.
                            // The way we have it set up, this will be a shortfall, and rows will have to be appended.
                            expect(view.refreshCounter).toBe(1);

                            // The rendered row count should have been bumped up to the calculated viewSize
                            expect(view.all.getCount()).toBe(view.bufferedRenderer.viewSize);

                            // Two calls to Store#getRange should have been made...
                            expect(getRangeSpy.callCount).toBe(2);

                            // First call gets the default view size
                            expect(getRangeSpy.calls[0].args).toEqual([0, Ext.grid.plugin.BufferedRenderer.prototype.viewSize - 1]);

                            // Then we top up the view with the missing rows.
                            // Second getRange will have been called with options, so slice to just test start and end indices
                            expect(Ext.Array.slice(getRangeSpy.calls[1].args, 0, 2)).toEqual([0, view.bufferedRenderer.viewSize - 1]);
                        });
                    });

                    describe("when rowHeight is larger than expected and viewSize has to shrink", function() {
                        var i,
                            data,
                            columns;

                        beforeEach(function() {
                            data = [];

                            // We need a lot of rows so that we get to add more than the original default viewSize
                            for (i = 0; i < 200; i++) {
                                data.push({
                                    field0: 'r' + i + ',f0'
                                });
                            }

                            columns = [{
                                dataIndex: 'field0',
                                text: 'Field 0',
                                width: 90,

                                // Extra tall cell to make the initially rendered view too large.
                                renderer: function() {
                                    return '<div style="height:30px"></div>';
                                }
                            }];
                        });

                        it("should remove rows from the view", function() {
                            // Make like mobile dev, and only overhang 1 row each side
                            // Make it tall enough so that the default viewSize of 100 will leave
                            // the view short, and BufferedRenderer will have to add rows.
                            makeGrid(columns, data, {
                                height: 200,
                                border: false,

                                // We want to get spies in before render
                                renderTo: null,
                                trailingBufferZone: 1,
                                leadingBufferZone: 1
                            });
                            var getRangeSpy = spyOn(store, 'getRange').andCallThrough(),
                                clipSpy = spyOn(view.all, 'clip').andCallThrough();

                            grid.render(document.body);

                            // Only one refresh. The initial refresh which inserts the default viewSize.
                            // The way we have it set up, this will be a to many, and rows will have to be removed.
                            expect(view.refreshCounter).toBe(1);

                            // The rendered row count should have been shrunk to the calculated viewSize
                            expect(view.all.getCount()).toBe(view.bufferedRenderer.viewSize);

                            // Two calls to Store#getRange should have been made.
                            // One for the refresh, and another for the firing of the itemremove
                            expect(getRangeSpy.callCount).toBe(2);

                            // The first refresh uses gets the default view size
                            expect(getRangeSpy.calls[0].args).toEqual([0, Ext.grid.plugin.BufferedRenderer.prototype.viewSize - 1]);

                            // Shrinking will have been done removing the trailing unwanted elements.
                            expect(clipSpy.calls[0].args).toEqual([-1, Ext.grid.plugin.BufferedRenderer.prototype.viewSize - view.bufferedRenderer.viewSize]);
                        });
                    });
                });
            }

            describe("shrinkwrap height", function() {
                var data, columns;

                beforeEach(function() {
                    var i, j,
                        row;

                    data = [];

                    for (i = 0; i < 100; i++) {
                        row = {};

                        for (j = 1; j < 11; j++) {
                            row['field' + j] = 'r' + i + ',f' + j;
                        }

                        data.push(row);
                    }

                    columns = [];

                    for (i = 1; i < 11; i++) {
                        columns.push({
                            dataIndex: 'field' + i,
                            text: 'Field ' + i,
                            width: 90
                        });
                    }

                    columns[0].flex = 1;
                });

                it("should allow unconstrained height grids to expand to accommodate content", function() {
                    makeGrid(columns, data, {
                        height: undefined,
                        border: false
                    });

                    // View and Grid should be stretched vertically to accommodate content.
                    // Flexed column should mean that the horizontal content fits exactly.
                    // All widths should be equal since we configured NO BORDERS.
                    expect(view.getWidth()).toBe(grid.getWidth());
                    expect(view.getHeight()).toBe(grid.getHeight() - grid.body.getBorderWidth('tb') - grid.headerCt.getHeight());
                    expect(view.el.dom.clientWidth).toBe(grid.getWidth());
                    expect(view.el.dom.clientHeight).toBe(view.getHeight());
                });

                it("should reduce height if shrinkwrap height violates height constraint", function() {
                    makeGrid(columns, data, {
                        maxHeight: 100,
                        border: false
                    });

                    // The width of the View still fits because of the flexed column
                    expect(view.getWidth()).toBe(grid.getWidth());

                    // If this system displays scrollbars...
                    // The presence of the vertical scrollbar should mean clientWidth is less than View's full width
                    if (Ext.getScrollbarSize().width) {
                        expect(view.el.dom.clientWidth).toBeLessThan(grid.getWidth());
                    }

                    // The height constraint means that the clientHeight is less than the view's scrollable height
                    expect(view.el.dom.clientHeight).toBeLessThan(view.el.dom.scrollHeight);
                });
            });

            describe("locking columns", function() {
                describe("basic rendering", function() {
                    describe("locked scroller top", function() {
                        it("should set the top of the scroller to include the view region", function() {
                            makeGrid([{
                                locked: true,
                                dataIndex: 'field1',
                                text: 'Foo'
                            }, {
                                dataIndex: 'field2',
                                text: 'Bar'
                            }], 1);

                            var top = grid.scrollContainer.getTop();

                            expect(top).toBe(grid.lockedGrid.headerCt.getHeight() + 1);
                        });

                        it("should set the top of the scroller to include the view region with only locked columns", function() {
                            makeGrid([{
                                locked: true,
                                dataIndex: 'field1',
                                text: 'Foo'
                            }], 1);

                            var top = grid.scrollContainer.getTop();

                            expect(top).toBe(grid.lockedGrid.headerCt.getHeight() + 1);
                        });

                        it("should set the top of the scroller to include the view region with only unlocked columns", function() {
                            makeGrid([{
                                dataIndex: 'field1',
                                text: 'Foo'
                            }], 1, {
                                enableLocking: true
                            });

                            var top = grid.scrollContainer.getTop();

                            expect(top).toBe(grid.normalGrid.headerCt.getHeight() + 1);
                        });
                    });
                });

                it("should synchronize horizontal scrollbar presence between locked and normal side.", function() {
                    makeGrid([{
                        locked: true,
                        dataIndex: 'field1'
                    }, {
                        locked: true,
                        dataIndex: 'field2'
                    }, {
                        dataIndex: 'field3'
                    }, {
                        dataIndex: 'field4'
                    }], undefined, {
                        width: 600,
                        height: 200
                    });

                    // The client height should be the same - scrollbars at the bottom should be synched. Either both there or none there.
                    // In this case, there should be none there.
                    expect(grid.lockedGrid.getView().getTargetEl().dom.clientHeight).toEqual(grid.normalGrid.getView().getTargetEl().dom.clientHeight);

                    // View's client height is the same as the offset height because there is no horizontal scrollbar    
                    expect(grid.lockedGrid.getView().getTargetEl().dom.clientHeight).toEqual(grid.lockedGrid.getView().getTargetEl().dom.offsetHeight);

                    // Create horizontal overflow
                    grid.getColumnManager().getColumns()[3].setWidth(500);

                    // The client height should be the same - scrollbars at the bottom should be synched. Either both there or none there.
                    // In this case, both should have scrollbars.
                    expect(grid.lockedGrid.getView().getTargetEl().dom.clientHeight - Ext.getScrollbarSize().height).toEqual(grid.normalGrid.getView().getTargetEl().dom.clientHeight);

                    // View's client height has been reduced by horizontal scrollbar appearing due to overflow in normal side (if scrollbars take up space)
                    if (scrollbarWidth) {
                        expect(grid.lockedGrid.getView().getTargetEl().dom.clientHeight).toBe(grid.lockedGrid.getView().getTargetEl().dom.offsetHeight);
                        expect(grid.normalGrid.getView().getTargetEl().dom.clientHeight).toBeLessThan(grid.normalGrid.getView().getTargetEl().dom.offsetHeight);
                    }
                });

                it("should create a y-scrolling wrapper, and the views should not scroll in the Y axis", function() {
                    makeGrid([{
                        locked: true,
                        dataIndex: 'field1',
                        width: 120
                    }, {
                        locked: true,
                        dataIndex: 'field2',
                        flex: 1
                    }, {
                        dataIndex: 'field3',
                        flex: 1
                    }, {
                        dataIndex: 'field4',
                        width: 120
                    }], undefined, {
                        width: 600,
                        height: 200,
                        lockedGridConfig: {
                            width: 200
                        }
                    });

                    expect(grid.lockedGrid.getView().getTargetEl().getStyle('overflow-y')).toBe('hidden');
                    expect(grid.normalGrid.getView().getTargetEl().getStyle('overflow-y')).toBe('hidden');
                    expect(grid.scrollBody.getStyle('overflow-y')).toBe('auto');
                });

                it("should unbind locking view from its store", function() {
                    makeGrid([{
                        locked: true,
                        dataIndex: 'field1'
                    }, {
                        locked: true,
                        dataIndex: 'field2'
                    }, {
                        dataIndex: 'field3'
                    }, {
                        dataIndex: 'field4'
                    }], undefined, {
                        width: 600,
                        height: 200
                    });

                    expect(store.hasListeners.refresh).toBeDefined();

                    grid.destroy();

                    // After destroy, there are no listeners
                    expect(store.hasListeners.refresh).toBeUndefined();
                });

                describe("loadMask", function() {
                    function returnSucessFalse() {
                        while (Ext.Ajax.mockGetAllRequests().length) {
                            Ext.Ajax.mockComplete({
                                status: 200,
                                responseText: Ext.encode({
                                    success: false
                                })
                            });
                        }
                    }

                    describe("Proxy throws exception during load", function() {
                        beforeEach(function() {
                            MockAjaxManager.addMethods();
                        });

                        afterEach(function() {
                            MockAjaxManager.removeMethods();
                        });

                        it("should hide the load mask if the load fails with an exception", function() {
                            makeGrid(null, undefined, {
                                store: {
                                    proxy: {
                                        type: 'ajax',
                                        url: 'fakeUrl',
                                        reader: {
                                            type: 'json'
                                        }
                                    }
                                }
                            }, {
                                preventStoreCreate: true
                            });
                            grid.store.loadPage(1);

                            // While waiting, the mask should be visible
                            expect(grid.view.loadMask.isVisible()).toBe(true);

                            returnSucessFalse();

                            // The success: false should
                            // have thrown an exception which should hide the mask
                            expect(grid.view.loadMask.isVisible()).toBe(false);
                        });
                    });

                    it("should raise a load mask by default (no loadMask config specified)", function() {
                        makeGrid([{
                            locked: true,
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            dataIndex: 'field2'
                        }, {
                            dataIndex: 'field3'
                        }, {
                            dataIndex: 'field4'
                        }], undefined, {
                            width: 600,
                            height: 200
                        });

                        expect(grid.view.loadMask instanceof Ext.LoadMask).toBe(true);
                    });

                    it("should not raise a load mask when set as false in viewConfig", function() {
                        makeGrid([{
                            locked: true,
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            dataIndex: 'field2'
                        }, {
                            dataIndex: 'field3'
                        }, {
                            dataIndex: 'field4'
                        }], undefined, {
                            viewConfig: {
                                loadMask: false
                            }
                        });

                        expect(grid.view.loadMask).toBe(false);
                    });

                    it("should raise a load mask when set on the grid", function() {
                        makeGrid([{
                            locked: true,
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            dataIndex: 'field2'
                        }, {
                            dataIndex: 'field3'
                        }, {
                            dataIndex: 'field4'
                        }], undefined, {
                            loadMask: true
                        });

                        expect(grid.loadMask).toBe(true);
                        expect(grid.view.loadMask instanceof Ext.LoadMask).toBe(true);
                    });

                    it("should respect the viewConfig definition as final (loadMask == true)", function() {
                        makeGrid([{
                            locked: true,
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            dataIndex: 'field2'
                        }, {
                            dataIndex: 'field3'
                        }, {
                            dataIndex: 'field4'
                        }], undefined, {
                            loadMask: false,
                            viewConfig: {
                                loadMask: true
                            }
                        });

                        expect(grid.loadMask).toBe(false);
                        expect(grid.view.loadMask instanceof Ext.LoadMask).toBe(true);
                    });

                    it("should respect the viewConfig definition as final (loadMask == false)", function() {
                        makeGrid([{
                            locked: true,
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            dataIndex: 'field2'
                        }, {
                            dataIndex: 'field3'
                        }, {
                            dataIndex: 'field4'
                        }], undefined, {
                            loadMask: true,
                            viewConfig: {
                                loadMask: false
                            }
                        });

                        expect(grid.loadMask).toBe(true);
                        expect(grid.view.loadMask).toBe(false);
                    });
                });

                describe("reconfiguring", function() {
                    describe("with CheckboxModel", function() {
                        it("should invalidate the lockedGrid.width so it shrinkwraps", function() {
                            // See EXTJS-13408.
                            var activeHeader;

                            this.addMatchers({
                                toBeAtLeast: function(expected) {
                                    return expected <= this.actual;
                                }
                            });

                            // Let's make it simpler to measure the width by not having any locked columns.
                            // EnableLocking will have the checkbox column added to the lockedGrid partner.
                            makeGrid(null, undefined, {
                                enableLocking: true,
                                selModel: new Ext.selection.CheckboxModel()
                            });

                            // No locked columns, so locked grid hidden.
                            expect(grid.lockedGrid.isVisible()).toBe(false);

                            grid.reconfigure(store, [{
                                dataIndex: 'field1',
                                locked: true,
                                width: 100
                            }, {
                                dataIndex: 'field2'
                            }, {
                                dataIndex: 'field3'
                            }]);

                            var borderWidth = grid.lockedGrid.gridPanelBorderWidth;

                            // First, verify that the width of the lockedGrid is the width of the checkbox
                            // column after reconfigure.
                            expect(grid.lockedGrid.width).toBe(Ext.selection.CheckboxModel.prototype.headerWidth + 100 + borderWidth);

                            activeHeader = grid.normalGrid.columnManager.getLast();

                            // Call lock and pass in the active header.
                            grid.lock(activeHeader);

                            // We now expect the locked grid to be at least the width of the checkbox column
                            // plus the newly-locked column.
                            expect(grid.lockedGrid.width).toBe(Ext.selection.CheckboxModel.prototype.headerWidth + 100 + activeHeader.width + borderWidth);
                        });
                    });
                });

                describe("variable row height", function() {
                    var normalRows, lockedRows, i;

                    it("should match the row heights between locked sides", function() {
                        makeGrid([{
                            dataIndex: 'name',
                            locked: true,
                            variableRowHeight: true
                        }, {
                            dataIndex: 'email',
                            width: 200
                        }, {
                            dataIndex: 'phone',
                            width: 200
                        }], [{
                            field1: '1<br>'
                        }, {
                            field1: '2<br>1'
                        }, {
                            field1: '3<br>1'
                        }, {
                            field1: '4<br>1'
                        }]);

                        normalRows = grid.normalGrid.getView().all;
                        lockedRows = grid.lockedGrid.getView().all;

                        // Row heights must be synched between the sides.
                        // Check that they are all equal.
                        for (i = normalRows.startIndex; i <= normalRows.endIndex; i++) {
                            expect(normalRows.item(i, true).offsetHeight).toEqual(lockedRows.item(i, true).offsetHeight);
                        }

                        // Move the variableRowHeight "Name" column into the normal grid
                        grid.unlock(grid.getColumnManager().getHeaderAtIndex(0));

                        if (buffered) {
                            expect(grid.normalGrid.view.bufferedRenderer.variableRowHeight).toBe(true);

                            // Hide the variableRowHeight "Name" column
                            grid.normalGrid.getVisibleColumnManager().getColumns()[0].hide();

                            // Now that the only variableRowHeight column is hidden, the buffered renderer should know about that.
                            expect(grid.normalGrid.view.bufferedRenderer.variableRowHeight).toBe(false);
                        }
                    });
                });

                describe("locked side sizing", function() {
                    beforeEach(function() {
                        makeGrid([{
                            locked: true,
                            width: 110
                        }, {
                            locked: true,
                            width: 160
                        }, {
                            flex: 1
                        }]);
                    });

                    it("should wrap around locked column sizing", function() {
                        var locked = grid.lockedGrid;

                        expect(locked.getWidth()).toBe(270 + locked.gridPanelBorderWidth);
                    });

                    it("should be able to change to a fixed size", function() {
                        var locked = grid.lockedGrid;

                        locked.setWidth(400);

                        expect(locked.getWidth()).toBe(400);
                    });

                    it("should be able to change to wrapping", function() {
                        var locked = grid.lockedGrid;

                        locked.setWidth(400);

                        expect(locked.getWidth()).toBe(400);

                        locked.setWidth(null);

                        expect(locked.getWidth()).toBe(270 + locked.gridPanelBorderWidth);
                    });
                });
            });

            describe("ensureVisible", function() {
                var success,
                    record,
                    htmlEl,
                    detectedScope,
                    rec100,
                    rec400;

                // Each test uses a grid with 500 rows
                beforeEach(function() {
                    success = false;
                    makeGrid(null, 500);
                    rec100 = store.getAt(100);
                    rec400 = store.getAt(400);
                });
                it("should scroll into view when using record ID", function() {
                    grid.ensureVisible('rec400', {
                        callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                            success = passedSuccess;
                            record = passedRecord;
                            htmlEl = passedHtmlEl;
                            detectedScope = this;
                        },
                        select: true
                    });
                    waitsFor(function() {
                        return success;
                    }, 'rec 400 to scroll into view');

                    runs(function() {
                        // Default scope is the grid
                        expect(detectedScope).toBe(grid);

                        expect(record).toBe(rec400);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec400));

                        // The select option was passed
                        expect(grid.getSelectionModel().getSelection()[0]).toBe(rec400);

                        // The bottom of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);

                        success = false;
                        grid.ensureVisible('rec100', {
                            callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                                success = passedSuccess;
                                record = passedRecord;
                                htmlEl = passedHtmlEl;
                                detectedScope = this;
                            }
                        });
                    });

                    waitsFor(function() {
                        return success;
                    }, 'rec 100 to scroll into view');

                    runs(function() {
                        // Default scope is the grid
                        expect(detectedScope).toBe(grid);

                        expect(record).toBe(rec100);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec100));

                        // The top of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().top).toBeGreaterThanOrEqual(view.getBox().top);
                    });
                });

                it("should scroll into view when using record", function() {
                    var o = {};

                    grid.ensureVisible(rec400, {
                        callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                            success = passedSuccess;
                            record = passedRecord;
                            htmlEl = passedHtmlEl;
                            detectedScope = this;
                        },
                        scope: o
                    });

                    waitsFor(function() {
                        return success;
                    }, 'rec 400 to scroll into view');

                    runs(function() {
                        // Use passed scope
                        expect(detectedScope).toBe(o);

                        expect(record).toBe(rec400);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec400));

                        // The bottom of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);

                        success = false;
                        grid.ensureVisible(rec100, {
                            callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                                success = passedSuccess;
                                record = passedRecord;
                                htmlEl = passedHtmlEl;
                                detectedScope = this;
                            },
                            scope: o
                        });
                    });

                    waitsFor(function() {
                        return success;
                    }, 'rec 100 to scroll into view');

                    runs(function() {
                        // Use passed scope
                        expect(detectedScope).toBe(o);

                        expect(record).toBe(rec100);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec100));

                        // The top of the row should be within view
                        expect(Ext.fly(view.getNode(rec100)).getBox().top).toBeGreaterThanOrEqual(view.getBox().top);
                    });
                });

                it("should scroll into view when using record index", function() {
                    grid.ensureVisible(400, {
                        callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                            success = passedSuccess;
                            record = passedRecord;
                            htmlEl = passedHtmlEl;
                        }
                    });

                    waitsFor(function() {
                        return success;
                    }, 'rec 400 to scroll into view');

                    runs(function() {
                        expect(record).toBe(rec400);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec400));

                        // The bottom of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);

                        success = false;
                        grid.ensureVisible(100, {
                            callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                                success = passedSuccess;
                                record = passedRecord;
                                htmlEl = passedHtmlEl;
                                detectedScope = this;
                            }
                        });
                    });

                    waitsFor(function() {
                        return success;
                    }, 'rec 100 to scroll into view');

                    runs(function() {
                        expect(record).toBe(rec100);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec100));

                        // The top of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().top).toBeGreaterThanOrEqual(view.getBox().top);
                    });
                });
            });

            describe("ensureVisible with locking", function() {
                var success,
                    record,
                    htmlEl,
                    detectedScope,
                    rec100,
                    rec400;

                // Each test uses a grid with 500 rows
                beforeEach(function() {
                    success = false;
                    makeGrid(null, 500, null, null, true);
                    rec100 = store.getAt(100);
                    rec400 = store.getAt(400);

                    // We'll be defaulting to the locked grid
                    view = grid.lockedGrid.getView();
                });

                it("should scroll into view when using record ID", function() {
                    grid.ensureVisible('rec400', {
                        callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                            success = passedSuccess;
                            record = passedRecord;
                            htmlEl = passedHtmlEl;
                            detectedScope = this;
                        },
                        select: true,
                        focus: true,
                        animate: true
                    });

                    // We are animating, so it must not have performed any of the actions yet.
                    // No scrolling
                    expect(grid.getScrollable().getPosition().y).toBe(0);
                    // No selection
                    expect(grid.getSelectionModel().getSelection().length).toBe(0);
                    // No focus
                    expect(grid.view.el.contains(Ext.Element.getActiveElement())).toBe(false);

                    waitsFor(function() {
                        return success;
                    });

                    runs(function() {
                        // Default scope is the grid
                        expect(detectedScope).toBe(grid.lockedGrid);

                        expect(record).toBe(rec400);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec400));

                        // The select option was passed
                        expect(grid.getSelectionModel().getSelection()[0]).toBe(rec400);

                        // Navigation position must be set as the focus options was passed.
                        expect(grid.getNavigationModel().getPosition().isEqual(new Ext.grid.CellContext(grid.view).setPosition(rec400, 0))).toBe(true);

                        // The bottom of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);

                        success = false;
                        grid.ensureVisible('rec100', {
                            callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                                success = passedSuccess;
                                record = passedRecord;
                                htmlEl = passedHtmlEl;
                                detectedScope = this;
                            }
                        });
                    });

                    waitsFor(function() {
                        return success;
                    });

                    runs(function() {
                        // Default scope is the grid
                        expect(detectedScope).toBe(grid.lockedGrid);

                        expect(record).toBe(rec100);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec100));

                        // The top of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().top).toBeGreaterThanOrEqual(view.getBox().top);
                    });
                });

                it("should scroll into view when using record", function() {
                    var o = {};

                    grid.ensureVisible(rec400, {
                        callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                            success = passedSuccess;
                            record = passedRecord;
                            htmlEl = passedHtmlEl;
                            detectedScope = this;
                        },
                        scope: o
                    });

                    waitsFor(function() {
                        return success;
                    });

                    runs(function() {
                        // Use passed scope
                        expect(detectedScope).toBe(o);

                        expect(record).toBe(rec400);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec400));

                        // The bottom of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);

                        success = false;
                        grid.ensureVisible(rec100, {
                            callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                                success = passedSuccess;
                                record = passedRecord;
                                htmlEl = passedHtmlEl;
                                detectedScope = this;
                            },
                            scope: o
                        });
                    });

                    waitsFor(function() {
                        return success;
                    });

                    runs(function() {
                        // Use passed scope
                        expect(detectedScope).toBe(o);

                        expect(record).toBe(rec100);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec100));

                        // The top of the row should be within view
                        expect(Ext.fly(view.getNode(rec100)).getBox().top).toBeGreaterThanOrEqual(view.getBox().top);
                    });
                });

                it("should scroll into view when using record index", function() {
                    grid.ensureVisible(400, {
                        callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                            success = passedSuccess;
                            record = passedRecord;
                            htmlEl = passedHtmlEl;
                        }
                    });
                    waitsFor(function() {
                        return success;
                    });
                    runs(function() {
                        expect(record).toBe(rec400);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec400));

                        // The bottom of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().bottom).toBeLessThanOrEqual(view.getBox().bottom);

                        success = false;
                        grid.ensureVisible(100, {
                            callback: function(passedSuccess, passedRecord, passedHtmlEl) {
                                success = passedSuccess;
                                record = passedRecord;
                                htmlEl = passedHtmlEl;
                                detectedScope = this;
                            }
                        });
                    });

                    waitsFor(function() {
                        return success;
                    });

                    runs(function() {
                        expect(record).toBe(rec100);

                        // Table row scrolled to must be correct
                        expect(htmlEl).toBe(view.getNode(rec100));

                        // The top of the row should be within view
                        expect(Ext.fly(htmlEl).getBox().top).toBeGreaterThanOrEqual(view.getBox().top);
                    });
                });
            });

            describe("scrollbars", function() {
                var gridRef,
                    colRef,
                    lockedIsVariable,
                    originalScrollBarSize,
                    headerCtHeight,
                    singleRowHeight,
                    gridHeight,
                    maxRowsBeforeScroll,
                    maxRowsBeforeScrollWithHorizontalScrollBar,
                    scrollRowSize,
                    lockingExtraWidth,
                    measureView,
                    viewHeight,
                    gridExtraHeight,
                    measureNode;

                beforeAll(function() {
                    // Do some measuring first
                    // First up, a locking grid, so we can get the locked->normal border width.
                    makeGrid([{
                        dataIndex: 'field1',
                        text: 'Field1',
                        width: 100,
                        locked: true
                    }, {
                        dataIndex: 'field2',
                        text: 'Field2',
                        width: 100
                    }], 10);

                    lockingExtraWidth = grid.lockedGrid.gridPanelBorderWidth;
                    grid.destroy();
                    store.destroy();

                    // Now we measure row heights and exactly how many rows cause scrolling
                    makeGrid([{
                        dataIndex: 'field1',
                        text: 'Field1',
                        width: 100
                    }], 10);

                    measureView = grid.getView();
                    viewHeight = measureView.getHeight();
                    gridExtraHeight = grid.getHeight() - viewHeight;
                    measureNode = Ext.fly(measureView.getNode(0));

                    headerCtHeight = grid.headerCt.getHeight();

                    singleRowHeight = measureNode.getHeight();

                    // In IE8 we're adding bottom border on all the rows to work around
                    // the lack of :last-child selector, and we compensate that by setting
                    // a negative top margin that equals the border width, so that top and
                    // bottom borders overlap on adjacent rows. Negative margin does not
                    // affect the row's reported height though so we have to compensate
                    // for that effectively invisible additional border width here.
                    // Note that this code mostly duplicates the actual row height
                    // calculation performed in BufferedRenderer.getScrollHeight(),
                    // and the same compensation is applied there as well.
                    if (Ext.isIE8) {
                        singleRowHeight -= measureNode.getBorderWidth('b');
                    }

                    // Then calculate the view height to hold 21 rows but to overflow
                    // as soon as a horizontal scrollbar appears.
                    // Account for non space taking scrollbars
                    viewHeight = singleRowHeight * 21 + (Ext.getScrollbarSize().height || (singleRowHeight - 5));

                    // Calculate a grid height in which the view encompasses 21 rows and a horizontal scrollbar.
                    // We need to be able to definitely know whether we are going to trigger
                    // scrollbars or not so we can test for expected results.
                    gridHeight = viewHeight + gridExtraHeight;

                    maxRowsBeforeScroll = Math.floor(viewHeight / singleRowHeight);
                    maxRowsBeforeScrollWithHorizontalScrollBar = maxRowsBeforeScroll - (Ext.getScrollbarSize().height ? 1 : 0);
                    scrollRowSize = maxRowsBeforeScroll + 100;

                    grid.destroy();
                    store.destroy();
                    gridRef = colRef = null;
                });

                beforeEach(function() {
                    lockedIsVariable = false;
                    originalScrollBarSize = Ext.grid.ColumnLayout.prototype.scrollbarWidth;
                    // Create nice round numbers for calculations so that we can hardcode expected flexed widths
                    Ext.grid.ColumnLayout.prototype.scrollbarWidth = scrollbarsTakeSpace ? 20 : 0;
                });

                afterEach(function() {
                    Ext.grid.ColumnLayout.prototype.scrollbarWidth = originalScrollBarSize;
                    gridRef = colRef = null;
                    lockedIsVariable = false;
                });

                function makeScrollSuite(withLocking) {
                    describe(withLocking ? "with locking" : "without locking", function() {
                        function makeScrollGrid(columns, data, cfg) {
                            cfg = cfg || {};
                            var lockedColWidth = 100;

                            if (Ext.isNumber(data)) {
                                data = makeRows(data);
                            }

                            Ext.Array.forEach(columns, function(column, i) {
                                if (!column.dataIndex) {
                                    column.dataIndex = 'field' + (i + 1);
                                }
                            });

                            if (withLocking) {
                                columns.unshift({
                                    width: lockedColWidth,
                                    dataIndex: 'field10',
                                    locked: true,
                                    variableRowHeight: lockedIsVariable
                                });
                                cfg.width = 1000 + lockingExtraWidth + lockedColWidth;
                            }

                            cfg.height = gridHeight;

                            // If they want hideHeaders, ensure the viewHeight is still as required
                            if (cfg.hideHeaders) {
                                cfg.height -= headerCtHeight;
                            }
                            else {
                                cfg.hideHeaders = false;
                            }

                            makeGrid(columns, data, cfg);
                            gridRef = withLocking ? grid.normalGrid : grid;
                            colRef = gridRef.getColumnManager().getColumns();
                        }

                        function expectScroll(vertical, horizontal) {
                            var scrollView = gridRef.getView(),
                                dom = scrollView.getEl().dom,
                                clientHeight = withLocking ? grid.scrollBody.el.dom.clientHeight : dom.clientHeight,
                                sStyle = scrollView.getScrollable().getSpacer().dom.style;

                            // For Safari, we have to force a synchronous layout for scroll values to be updated in this event thread
                            // This flip-flops between 0px and 1px
                            sStyle.lineHeight = Number(!parseInt(sStyle.lineHeight)) + 'px';

                            // IE now seems to need this in scrollbar tests where scrollbars depend on scrollbar presence
                            if (Ext.isIE) {
                                grid.updateLayout();
                            }

                            // In Mac OS X, scrollbars can be invisible until user hovers mouse cursor
                            // over the scrolled area. This is hard to test so we just assume that
                            // in Mac browsers scrollbars can have 0 width.
                            if (vertical !== undefined) {
                                if (vertical) {
                                    expect(dom.scrollHeight).toBeGreaterThanOrEqual(clientHeight);
                                }
                                else {
                                    expect(dom.scrollHeight).toBeLessThanOrEqual(clientHeight);
                                }
                            }

                            if (horizontal !== undefined) {
                                if (horizontal) {
                                    expect(dom.scrollWidth).toBeGreaterThan(dom.clientWidth);
                                }
                                else {
                                    expect(dom.scrollWidth).toBeLessThanOrEqual(dom.clientWidth);
                                }
                            }
                        }

                        function expectColumnWidths(sizes, manager) {
                            manager = manager || gridRef.getVisibleColumnManager();
                            var columns = manager.getColumns(),
                                len = columns.length,
                                i;

                            for (i = 0; i < len; ++i) {
                                expect(columns[i].getWidth()).toBeApprox(sizes[i], 1);
                            }

                        }

                        function makeRows(n) {
                            var data = [],
                                i;

                            for (i = 1; i <= n; ++i) {
                                data.push({
                                    field1: i + '.1',
                                    field2: i + '.2',
                                    field3: i + '.3',
                                    field4: i + '.4',
                                    field5: i + '.5',
                                    field6: i + '.6',
                                    field7: i + '.7',
                                    field8: i + '.8',
                                    field9: i + '.9',
                                    field10: i + '.10'

                                });
                            }

                            return data;
                        }

                        function makeRowDiv(times) {
                            return '<div style="height:' + (singleRowHeight * times) + 'px;">x</div>';
                        }

                        describe("basic initial rendering functionality", function() {
                            describe("fixed width columns", function() {
                                it("should not show scrollbars if neither dimensions overflows", function() {
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        width: 300
                                    }], 1);
                                    expectScroll(false, false);
                                    expectColumnWidths([100, 300]);
                                });

                                it("should respect the scrollable config as an object", function() {
                                    makeGrid(null, 50, {
                                        scrollable: {
                                            y: true,
                                            x: false
                                        },
                                        width: 300,
                                        height: 400
                                    });

                                    expect(view.el.getStyle('overflow-x')).toBe('hidden');
                                    expect(view.getScrollable().getX()).toBe(false);
                                });

                                it("should show a vertical scrollbar if y overflows", function() {
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        width: 300
                                    }], scrollRowSize);
                                    expectScroll(true, false);
                                    expectColumnWidths([100, 300]);
                                });

                                it("should show a horizontal scrollbar if x overflows", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], 1);
                                    expectScroll(false, true);
                                    expectColumnWidths([600, 600]);
                                });

                                it("should show both scrollbars if x & y overflow", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], scrollRowSize);
                                    expectScroll(true, true);
                                    expectColumnWidths([600, 600]);
                                });

                                visibleScrollbarsIt("should show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], maxRowsBeforeScroll);
                                    expectScroll(true, true);
                                    expectColumnWidths([600, 600]);
                                });

                                visibleScrollbarsIt("should show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                    makeScrollGrid([{
                                        width: 499
                                    }, {
                                        width: 499
                                    }], scrollRowSize);
                                    expectScroll(true, true);
                                    expectColumnWidths([499, 499]);
                                });
                            });

                            describe("flexed columns", function() {
                                it("should not show scrollbars if neither dimension overflows", function() {
                                    makeScrollGrid([{
                                        flex: 1
                                    }, {
                                        flex: 1
                                    }], 1);
                                    expectScroll(false, false);
                                    expectColumnWidths([500, 500]);
                                });

                                it("should not show scrollbars if neither dimension overflows with hideHeaders: true and border: true", function() {
                                    makeScrollGrid([{
                                        flex: 1
                                    }, {
                                        flex: 1
                                    }], 1, {
                                        hideHeaders: true,
                                        border: true
                                    });
                                    expectScroll(false, false);

                                    // Even though headerCt is squeezed out of visibilty, it must have measurable left/right border widths
                                    expectColumnWidths([499, 499]);
                                });

                                it("should show a vertical scrollbar if y overflows", function() {
                                    makeScrollGrid([{
                                        flex: 1
                                    }, {
                                        flex: 1
                                    }], scrollRowSize);
                                    expectScroll(true, false);
                                    expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                });

                                it("should show a vertical scrollbar if y overflows with hideHeaders: true and border: true", function() {
                                    makeScrollGrid([{
                                        flex: 1
                                    }, {
                                        flex: 1
                                    }], scrollRowSize, {
                                        hideHeaders: true,
                                        border: true
                                    });
                                    expectScroll(true, false);
                                    expectColumnWidths(scrollbarsTakeSpace ? [489, 489] : [499, 499]);
                                });

                                describe("min width constraint", function() {
                                    // This is essentially the same as combined width + flex, since hitting
                                    // a min width is essentially the same as setting a fixed size, but
                                    // may hit different parts of the code.

                                    it("should show a horizontal scrollbar if x overflows", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            minWidth: 600
                                        }, {
                                            flex: 1,
                                            minWidth: 600
                                        }]);
                                        expectScroll(false, true);
                                        expectColumnWidths([600, 600]);
                                    });

                                    it("should show both scrollbars if x & y overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            minWidth: 600
                                        }, {
                                            flex: 1,
                                            minWidth: 600
                                        }], scrollRowSize);
                                        expectScroll(true, true);
                                        expectColumnWidths([600, 600]);
                                    });

                                    visibleScrollbarsIt("should show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            minWidth: 600
                                        }, {
                                            flex: 1,
                                            minWidth: 600
                                        }], maxRowsBeforeScroll);
                                        expectScroll(true, true);
                                        expectColumnWidths([600, 600]);
                                    });

                                    visibleScrollbarsIt("should show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            minWidth: 499
                                        }, {
                                            flex: 1,
                                            minWidth: 499
                                        }], scrollRowSize);
                                        expectScroll(true, true);
                                        expectColumnWidths([499, 499]);
                                    });
                                });
                            });

                            describe("fixed width + flexed columns", function() {
                                it("should not show scrollbars if neither dimensions overflows", function() {
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        flex: 1
                                    }], 1);
                                    expectScroll(false, false);
                                    expectColumnWidths([100, 900]);
                                });

                                it("should show a vertical scrollbar if y overflows", function() {
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        flex: 1
                                    }], scrollRowSize);
                                    expectScroll(true, false);
                                    expectColumnWidths(scrollbarsTakeSpace ? [100, 880] : [100, 900]);
                                });

                                it("should adjust the width of all flex columns if a scrollbar shows", function() {
                                    makeScrollGrid([{
                                        width: 200
                                    }, {
                                        flex: 1
                                    }, {
                                        flex: 1
                                    }, {
                                        flex: 1
                                    }], scrollRowSize);
                                    expectScroll(true, false);
                                    expectColumnWidths(scrollbarsTakeSpace ? [200, 260, 260, 260] : [200, 266, 267, 267]);
                                });
                            });
                        });

                        describe("resizing the grid", function() {
                            function changeHeight(numRows) {
                                grid.setHeight(grid.getHeight() + numRows * singleRowHeight);
                            }

                            function changeWidth(width) {
                                grid.setWidth(grid.getWidth() + width);
                            }

                            describe("fixed width columns", function() {
                                describe("vertically", function() {
                                    it("should not show a vertical scrollbar if resize does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        changeHeight(-10);
                                        expectScroll(false, false);
                                    });

                                    it("should retain a vertical scrollbar if resize does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        changeHeight(-10);
                                        expectScroll(true, false);
                                    });

                                    it("should show a vertical scrollbar if y overflows", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        changeHeight(-10);
                                        expectScroll(true, false);
                                    });

                                    it("should not show a vertical scrollbar if y underflows", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll + 5);
                                        expectScroll(true, false);
                                        changeHeight(10);
                                        expectScroll(false, false);
                                    });

                                    visibleScrollbarsIt("should show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        changeHeight(-1);
                                        expectScroll(true, true);
                                    });

                                    // // EXTJS-15789
                                    xit("should not show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], maxRowsBeforeScroll);
                                        changeHeight(-1);
                                        expectScroll(true, true);
                                        changeHeight(1);
                                        expectScroll(false, false);
                                    });
                                });

                                describe("horizontally", function() {
                                    it("should not show a horizontal scrollbar if resize does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        changeWidth(200);
                                        expectScroll(false, false);
                                    });

                                    it("should retain a horizontal scrollbar if resize does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        changeWidth(-100);
                                        expectScroll(false, true);
                                    });

                                    it("should show a horizontal scrollbar if x overflows", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            width: 400
                                        }], 1);
                                        expectScroll(false, false);
                                        changeWidth(-400);
                                        expectScroll(false, true);
                                    });

                                    it("should not show a horizontal scrollbar if x underflows", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        changeWidth(400);
                                        expectScroll(false, false);
                                    });

                                    visibleScrollbarsIt("should show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        changeWidth(-50);
                                        expectScroll(true, true);
                                    });

                                    visibleScrollbarsIt("should not show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                        // C reate a grid with horizontal scrollbar which has no vertical scrollbar.
                                        makeScrollGrid([{
                                            width: 505
                                        }, {
                                            width: 505
                                        }], maxRowsBeforeScroll, {
                                            height: null
                                        });

                                        // Introduce the vertical scrollbar but only by 5px so that eliminating the vertical scrollbar
                                        // will eliminate the need for it.
                                        grid.setHeight(grid.getHeight() - 5);
                                        expectScroll(true, true);

                                        // Release the width constraint so that the horizontal scrollbar disappears.
                                        // contentHeight now fits
                                        changeWidth(50);
                                        waits(1);

                                        // Allow the reflow before showing and then measuring.
                                        runs(function() {
                                            expectScroll(false, false);
                                        });
                                    });
                                });
                            });

                            describe("flexed columns", function() {
                                describe("vertically", function() {
                                    it("should not show a vertical scrollbar if resize does not cause overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        changeHeight(-10);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                    });

                                    it("should retain a vertical scrollbar if resize does not cause underflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        changeHeight(-10);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should show a vertical scrollbar if y overflows", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        changeHeight(-10);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should not show a vertical scrollbar if y underflows", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll + 5);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        changeHeight(10);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                    });
                                });
                                // Horizontally omitted
                            });

                            describe("fixed width + flexed columns", function() {
                                describe("vertically", function() {
                                    it("should not show a vertical scrollbar if resize does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        changeHeight(-10);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });

                                    it("should retain a vertical scrollbar if resize does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        changeHeight(-10);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });

                                    it("should show a vertical scrollbar if y overflows", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        changeHeight(-10);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });

                                    it("should not show a vertical scrollbar if y underflows", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll + 5);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        changeHeight(10);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });
                                });
                                // Horizontally omitted
                            });
                        });

                        describe("column operations", function() {
                            var minColWidth;

                            beforeAll(function() {
                                minColWidth = Ext.grid.plugin.HeaderResizer.prototype.minColWidth;
                            });

                            describe("resizing", function() {
                                describe("fixed width columns", function() {
                                    it("should show a horizontal scrollbar if resizing causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        colRef[0].setWidth(800);
                                        expectScroll(false, true);
                                    });

                                    it("should not show a horizontal scrollbar if resizing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        colRef[0].setWidth(300);
                                        expectScroll(false, false);
                                    });

                                    it("should show a vertical scrollbar if resizing causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        colRef[0].setWidth(800);
                                        expectScroll(scrollbarsTakeSpace, true);
                                    });

                                    it("should not show a vertical scrollbar if resizing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], maxRowsBeforeScroll);
                                        expectScroll(scrollbarsTakeSpace, true);
                                        colRef[0].setWidth(300);
                                        expectScroll(false, false);
                                    });
                                });

                                // Intentionally leaving out flex only

                                describe("fixed width + flexed columns", function() {
                                    it("should show a horizontal scrollbar if resizing causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        colRef[0].setWidth(1200);
                                        expectScroll(false, true);
                                        expectColumnWidths([1200, minColWidth]);
                                    });

                                    it("should not show a horizontal scrollbar if resizing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, true);
                                        expectColumnWidths([600, 600, minColWidth]);
                                        colRef[0].setWidth(200);
                                        expectScroll(false, false);
                                        expectColumnWidths([200, 600, 200]);
                                    });

                                    it("should show a vertical scrollbar if resizing causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        colRef[0].setWidth(1200);
                                        expectScroll(scrollbarsTakeSpace, true);
                                        expectColumnWidths([1200, minColWidth]);
                                    });

                                    it("should not show a vertical scrollbar if resizing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll, {
                                            height: null
                                        });
                                        grid.setHeight(grid.getHeight() - 5);
                                        expectScroll(true, true);
                                        expectColumnWidths([600, 600, minColWidth]);
                                        colRef[0].setWidth(200);
                                        waits(1);

                                        // Allow the reflow before showing and then measuring.
                                        runs(function() {
                                            expectScroll(!scrollbarsTakeSpace, false);
                                            expectColumnWidths([200, 600, 200]);
                                        });
                                    });
                                });
                            });

                            describe("adding", function() {
                                function addCol(col) {
                                    gridRef.headerCt.add(col);
                                }

                                describe("fixed width columns", function() {
                                    it("should show a horizontal scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        addCol({
                                            width: 700
                                        });
                                        expectScroll(false, true);
                                    });

                                    it("should not show a horizontal scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        addCol({
                                            width: 300
                                        });
                                        expectScroll(false, false);
                                    });

                                    it("should show a vertical scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        addCol({
                                            width: 200
                                        });
                                        expectScroll(scrollbarsTakeSpace, true);
                                    });

                                    describe("with variableRowHeight", function() {
                                        visibleScrollbarsIt("should show a vertical scrollbar if adding causes overflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 100
                                            }, {
                                                width: 300
                                            }], data);
                                            expectScroll(false, false);
                                            addCol({
                                                width: 200,
                                                variableRowHeight: true,
                                                dataIndex: 'field3'
                                            });
                                            expectScroll(true, false);
                                        });

                                        visibleScrollbarsIt("should show a horizontal scrollbar if adding triggered a vertical scrollbar", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 330
                                            }, {
                                                width: 330
                                            }], data);
                                            expectScroll(false, false);
                                            addCol({
                                                width: 330,
                                                variableRowHeight: true,
                                                dataIndex: 'field3'
                                            });
                                            expectScroll(true, true);
                                        });
                                    });
                                });

                                // Intentionally leaving out flex only

                                describe("fixed width + flexed columns", function() {
                                    it("should show a horizontal scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        addCol({
                                            width: 800
                                        });
                                        expectScroll(false, true);
                                        expectColumnWidths([300, minColWidth, 800]);
                                    });

                                    it("should not show a horizontal scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        addCol({
                                            width: 300
                                        });
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 400, 300]);
                                    });

                                    it("should show a vertical scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        addCol({
                                            width: 800
                                        });
                                        expectScroll(scrollbarsTakeSpace, true);
                                        expectColumnWidths([300, minColWidth, 800]);
                                    });

                                    describe("with variableRowHeight", function() {
                                        it("should show a vertical scrollbar if adding causes overflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 300
                                            }, {
                                                flex: 1
                                            }], data);
                                            expectScroll(false, false);
                                            expectColumnWidths([300, 700]);
                                            addCol({
                                                width: 200,
                                                variableRowHeight: true,
                                                dataIndex: 'field3'
                                            });
                                            expectScroll(true, false);
                                            expectColumnWidths([300, scrollbarsTakeSpace ? 480 : 500, 200]);
                                        });
                                    });
                                });
                            });

                            describe("showing", function() {
                                describe("fixed width columns", function() {
                                    it("should show a horizontal scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 700,
                                            hidden: true
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        colRef[1].show();
                                        expectScroll(false, true);
                                    });

                                    it("should not show a horizontal scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300,
                                            hidden: true
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        colRef[1].show();
                                        expectScroll(false, false);
                                    });

                                    it("should show a vertical scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 200,
                                            hidden: true
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        colRef[1].show();
                                        expectScroll(scrollbarsTakeSpace, true);
                                    });

                                    describe("with variableRowHeight", function() {
                                        visibleScrollbarsIt("should show a vertical scrollbar if showing causes overflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 100
                                            }, {
                                                width: 300
                                            }, {
                                                width: 200,
                                                variableRowHeight: true,
                                                hidden: true
                                            }], data);
                                            expectScroll(false, false);
                                            colRef[2].show();
                                            expectScroll(true, false);
                                        });

                                        visibleScrollbarsIt("should show a horizontal scrollbar if showing triggered a vertical scrollbar", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 330
                                            }, {
                                                width: 330
                                            }, {
                                                width: 330,
                                                variableRowHeight: true,
                                                hidden: true
                                            }], data);
                                            expectScroll(false, false);
                                            colRef[2].show();
                                            expectScroll(true, true);
                                        });
                                    });
                                });

                                // Intentionally leaving out flex only

                                describe("fixed width + flexed columns", function() {
                                    it("should show a horizontal scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            width: 800,
                                            hidden: true
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        colRef[1].show();
                                        expectScroll(false, true);
                                        expectColumnWidths([300, 800, minColWidth]);
                                    });

                                    it("should not show a horizontal scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            width: 300,
                                            hidden: true
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        colRef[1].show();
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 300, 400]);
                                    });

                                    it("should show a vertical scrollbar if adding causes overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            width: 800,
                                            hidden: true
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        colRef[1].show();
                                        expectScroll(scrollbarsTakeSpace, true);
                                        expectColumnWidths([300, 800, minColWidth]);
                                    });

                                    describe("with variableRowHeight", function() {
                                        it("should show a vertical scrollbar if showing causes overflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 300
                                            }, {
                                                flex: 1
                                            }, {
                                                width: 200,
                                                variableRowHeight: true,
                                                hidden: true
                                            }], data);
                                            expectScroll(false, false);
                                            expectColumnWidths([300, 700]);
                                            colRef[2].show();
                                            expectScroll(true, false);
                                            expectColumnWidths([300, scrollbarsTakeSpace ? 480 : 500, 200]);
                                        });
                                    });
                                });
                            });

                            describe("removing", function() {
                                describe("fixed width columns", function() {
                                    it("should not show a horizontal scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }, {
                                            width: 700
                                        }], 1);
                                        expectScroll(false, true);
                                        colRef[2].destroy();
                                        expectScroll(false, false);
                                    });

                                    it("should retain a horizontal scrollbar if removing does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        colRef[2].destroy();
                                        expectScroll(false, true);
                                    });

                                    it("should not show a vertical scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 300
                                        }, {
                                            width: 200
                                        }], maxRowsBeforeScroll);
                                        expectScroll(scrollbarsTakeSpace, true);
                                        colRef[2].destroy();
                                        expectScroll(false, false);
                                    });

                                    describe("with variableRowHeight", function() {
                                        it("should not show a vertical scrollbar if removing causes underflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 100
                                            }, {
                                                width: 300
                                            }, {
                                                width: 200,
                                                variableRowHeight: true
                                            }], data);
                                            expectScroll(true, false);
                                            colRef[2].destroy();
                                            expectScroll(false, false);
                                        });

                                        visibleScrollbarsIt("should not show a horizontal scrollbar if removing triggered a vertical scrollbar removal", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 330
                                            }, {
                                                width: 330
                                            }, {
                                                width: 330,
                                                variableRowHeight: true
                                            }], data);
                                            expectScroll(true, true);
                                            colRef[2].destroy();
                                            expectScroll(false, false);
                                        });
                                    });
                                });

                                // Intentionally leaving out flex only

                                describe("fixed width + flexed columns", function() {
                                    it("should not show a horizontal scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }, {
                                            width: 800
                                        }], 1);
                                        expectScroll(false, true);
                                        expectColumnWidths([300, minColWidth, 800]);
                                        colRef[2].destroy();
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });

                                    it("should show a horizontal scrollbar if removing does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }, {
                                            flex: 1
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        expectColumnWidths([600, 600, minColWidth, 600]);
                                        colRef[3].destroy();
                                        expectScroll(false, true);
                                        expectColumnWidths([600, 600, minColWidth]);
                                    });

                                    it("should not show a vertical scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }, {
                                            width: 800
                                        }], maxRowsBeforeScroll);
                                        expectScroll(scrollbarsTakeSpace, true);
                                        expectColumnWidths([300, minColWidth, 800]);
                                        colRef[2].destroy();
                                        waits(1);

                                        // Allow the reflow before showing and then measuring.
                                        runs(function() {
                                            expectScroll(false, false);
                                            expectColumnWidths([300, 700]);
                                        });
                                    });

                                    describe("with variableRowHeight", function() {
                                        it("should not show a vertical scrollbar if removing causes underflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 300
                                            }, {
                                                flex: 1
                                            }, {
                                                width: 200,
                                                variableRowHeight: true
                                            }], data);
                                            expectScroll(true, false);
                                            expectColumnWidths([300, scrollbarsTakeSpace ? 480 : 500, 200]);
                                            colRef[2].destroy();
                                            expectScroll(false, false);
                                            expectColumnWidths([300, 700]);
                                        });
                                    });
                                });
                            });

                            describe("hiding", function() {
                                describe("fixed width columns", function() {
                                    it("should not show a horizontal scrollbar if hiding causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }, {
                                            width: 700
                                        }], 1);
                                        expectScroll(false, true);
                                        colRef[2].hide();
                                        expectScroll(false, false);
                                    });

                                    it("should retain a horizontal scrollbar if hiding does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        colRef[2].hide();
                                        expectScroll(false, true);
                                    });

                                    it("should not show a vertical scrollbar if hiding causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 300
                                        }, {
                                            width: 200
                                        }], maxRowsBeforeScroll);
                                        expectScroll(scrollbarsTakeSpace, true);
                                        colRef[2].hide();
                                        waits(1);

                                        // Allow the reflow before showing and then measuring.
                                        runs(function() {
                                            expectScroll(false, false);
                                        });
                                    });

                                    describe("with variableRowHeight", function() {
                                        it("should not show a vertical scrollbar if hiding causes underflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 100
                                            }, {
                                                width: 300
                                            }, {
                                                width: 200,
                                                variableRowHeight: true
                                            }], data);
                                            expectScroll(true, false);
                                            colRef[2].hide();
                                            waits(1);

                                            // Allow the reflow before showing and then measuring.
                                            runs(function() {
                                                expectScroll(false, false);
                                            });
                                        });

                                        visibleScrollbarsIt("should not show a horizontal scrollbar if hiding triggered a vertical scrollbar removal", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 330
                                            }, {
                                                width: 330
                                            }, {
                                                width: 330,
                                                variableRowHeight: true
                                            }], data);
                                            expectScroll(true, true);
                                            colRef[2].hide();
                                            expectScroll(false, false);
                                        });
                                    });
                                });

                                // Intentionally leaving out flex only

                                describe("fixed width + flexed columns", function() {
                                    it("should not show a horizontal scrollbar if hiding causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }, {
                                            width: 800
                                        }], 1);
                                        expectScroll(false, true);
                                        expectColumnWidths([300, minColWidth, 800]);
                                        colRef[2].hide();
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });

                                    it("should show a horizontal scrollbar if hiding does not cause underflow", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }, {
                                            flex: 1
                                        }, {
                                            width: 600
                                        }], 1);
                                        expectScroll(false, true);
                                        expectColumnWidths([600, 600, minColWidth, 600]);
                                        colRef[3].hide();
                                        expectScroll(false, true);
                                        expectColumnWidths([600, 600, minColWidth]);
                                    });

                                    it("should not show a vertical scrollbar if hiding causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }, {
                                            width: 800
                                        }], maxRowsBeforeScroll);
                                        expectScroll(scrollbarsTakeSpace, true);
                                        expectColumnWidths([300, minColWidth, 800]);
                                        colRef[2].hide();
                                        waits(1);

                                        // Allow the reflow before showing and then measuring.
                                        runs(function() {
                                            expectScroll(false, false);
                                            expectColumnWidths([300, 700]);
                                        });
                                    });

                                    describe("with variableRowHeight", function() {
                                        it("should not show a vertical scrollbar if hiding causes underflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field3 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 300
                                            }, {
                                                flex: 1
                                            }, {
                                                width: 200,
                                                variableRowHeight: true
                                            }], data);
                                            expectScroll(true, false);
                                            expectColumnWidths([300, scrollbarsTakeSpace ? 480 : 500, 200]);
                                            colRef[2].hide();
                                            waits(1);

                                            // Allow the reflow before showing and then measuring.
                                            runs(function() {
                                                expectScroll(false, false);
                                                expectColumnWidths([300, 700]);
                                            });
                                        });
                                    });
                                });
                            });
                        });

                        describe("store operations", function() {
                            describe("adding records", function() {
                                describe("fixed width columns", function() {
                                    it("should not show a vertical scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        store.add({});
                                        expectScroll(false, false);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        store.add({});
                                        expectScroll(true, false);
                                    });

                                    it("should show a vertical scrollbar if adding causes an overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        store.add({});
                                        expectScroll(true, false);
                                    });

                                    visibleScrollbarsIt("should show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], maxRowsBeforeScrollWithHorizontalScrollBar, {
                                            height: null
                                        });
                                        expectScroll(false, true);
                                        store.add({});
                                        expectScroll(true, true);
                                    });

                                    visibleScrollbarsIt("should show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        store.add({});
                                        expectScroll(true, true);
                                    });
                                });

                                describe("flexed columns", function() {
                                    it("should not show a vertical scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.add({});
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.add({});
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should show a vertical scrollbar if adding causes an overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.add({});
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });
                                });

                                describe("fixed width + flexed columns", function() {
                                    it("should not show a vertical scrollbar if adding does not cause overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        store.add({});
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        store.add({});
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });

                                    it("should show a vertical scrollbar if adding causes an overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        store.add({});
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });
                                });
                            });

                            describe("removing records", function() {
                                describe("fixed width columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 2);
                                        expectScroll(false, false);
                                        store.removeAt(0);
                                        expectScroll(false, false);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        store.removeAt(0);
                                        expectScroll(true, false);
                                    });

                                    it("should not show a vertical scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], maxRowsBeforeScroll + 1);
                                        expectScroll(true, false);
                                        store.removeAt(0);
                                        expectScroll(false, false);
                                    });

                                    visibleScrollbarsIt("should not show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], maxRowsBeforeScrollWithHorizontalScrollBar + 1, {
                                            height: null
                                        });
                                        grid.setHeight(grid.getHeight() - 5);
                                        expectScroll(true, true);
                                        store.removeAt(0);
                                        waits(1);

                                        // Allow the reflow before showing and then measuring.
                                        runs(function() {
                                            expectScroll(false, true);
                                        });
                                    });

                                    // EXTJS-1578
                                    xit("should not show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], maxRowsBeforeScroll + 1);
                                        expectScroll(true, true);
                                        store.removeAt(0);
                                        expectScroll(false, false);
                                    });
                                });

                                describe("flexed columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], 2);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.removeAt(0);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.removeAt(0);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should not show a vertical scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll + 1, {
                                            height: null
                                        });
                                        // Make the grid just height enough so that there is a vertical scrollbar
                                        grid.setHeight(grid.getHeight() - 5);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.removeAt(0);

                                        // Layout resulting from remove which recalculates column widths runs on idle, so allow that to happen
                                        waits(1);
                                        runs(function() {
                                            expectScroll(false, false);
                                            expectColumnWidths([500, 500]);
                                        });
                                    });
                                });

                                describe("fixed width + flexed columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            flex: 1
                                        }], 2);
                                        expectScroll(false, false);
                                        expectColumnWidths([400, 600]);
                                        store.removeAt(0);
                                        expectScroll(false, false);
                                        expectColumnWidths([400, 600]);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [400, 580] : [400, 600]);
                                        store.removeAt(0);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [400, 580] : [400, 600]);
                                    });

                                    it("should not show a vertical scrollbar if removing causes underflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll + 1, {
                                            height: null
                                        });
                                        // Make the grid just height enough so that there is a vertical scrollbar
                                        grid.setHeight(grid.getHeight() - 5);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        store.removeAt(0);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });
                                });
                            });

                            describe("updating records with variableRowHeight", function() {
                                describe("fixed width columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            width: 100,
                                            variableRowHeight: true
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(false, false);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 100,
                                            variableRowHeight: true
                                        }, {
                                            width: 300
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                    });

                                    describe("making content larger", function() {
                                        it("should show a vertical scrollbar if the update causes an overflow", function() {
                                            makeScrollGrid([{
                                                width: 100,
                                                variableRowHeight: true
                                            }, {
                                                width: 300
                                            }], maxRowsBeforeScroll);
                                            expectScroll(false, false);
                                            store.first().set('field1', makeRowDiv(2));
                                            expectScroll(true, false);
                                        });

                                        visibleScrollbarsIt("should show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                            makeScrollGrid([{
                                                width: 600,
                                                variableRowHeight: true
                                            }, {
                                                width: 600
                                            }], maxRowsBeforeScrollWithHorizontalScrollBar, {
                                                height: null
                                            });
                                            // Make the grid just height enough so that there's a horizontal scrollbar, but no vertical
                                            grid.setHeight(grid.getHeight() + 5);
                                            expectScroll(false, true);
                                            store.first().set('field1', makeRowDiv(2));
                                            expectScroll(true, true);
                                        });

                                        visibleScrollbarsIt("should show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                            makeScrollGrid([{
                                                width: 495,
                                                variableRowHeight: true
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll);
                                            expectScroll(false, false);
                                            store.first().set('field1', makeRowDiv(2));
                                            expectScroll(true, true);
                                        });
                                    });

                                    describe("making content smaller", function() {
                                        it("should not show a vertical scrollbar if the update causes an underflow", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field1 = makeRowDiv(3);
                                            makeScrollGrid([{
                                                width: 100,
                                                variableRowHeight: true
                                            }, {
                                                width: 300
                                            }], data);
                                            expectScroll(true, false);
                                            store.first().set('field1', '1.1');
                                            expectScroll(false, false);
                                        });

                                        visibleScrollbarsIt("should not show a vertical scrollbar if triggered by a horizontal scrollbar", function() {
                                            makeScrollGrid([{
                                                width: 600,
                                                variableRowHeight: true
                                            }, {
                                                width: 600
                                            }], maxRowsBeforeScroll, {
                                                height: null
                                            });
                                            grid.setHeight(grid.getHeight() + 2);
                                            expectScroll(false, true);
                                            store.first().set('field1', makeRowDiv(3));
                                            expectScroll(true, true);
                                            store.first().set('field1', '1.1');
                                            expectScroll(false, true);
                                        });

                                        visibleScrollbarsIt("should not show a horizontal scrollbar if triggered by a vertical scrollbar", function() {
                                            var data = makeRows(maxRowsBeforeScrollWithHorizontalScrollBar);

                                            data[0].field1 = makeRowDiv(3);
                                            makeScrollGrid([{
                                                width: 495,
                                                variableRowHeight: true
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll, {
                                                height: null
                                            });
                                            grid.setHeight(grid.getHeight() + 5);
                                            store.first().set('field1', makeRowDiv(3));
                                            expectScroll(true, true);
                                            store.first().set('field1', '1.1');
                                            waits(1);

                                            // Allow the reflow before showing and then measuring.
                                            runs(function() {
                                                expectScroll(false, false);
                                            });
                                        });
                                    });
                                });

                                describe("flexed columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(false, false);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should show a vertical scrollbar if the update causes an overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll, {
                                            height: null
                                        });
                                        grid.setHeight(grid.getHeight() + 5);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should not show a vertical scrollbar if the update causes an underflow", function() {
                                        makeScrollGrid([{
                                            flex: 1,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll, {
                                            height: null
                                        });
                                        grid.setHeight(grid.getHeight() + 5);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.first().set('field1', '1.1');
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                    });
                                });

                                describe("fixed width + flexed columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            width: 300,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(false, false);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 300,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });

                                    it("should show a vertical scrollbar if the update causes an overflow", function() {
                                        makeScrollGrid([{
                                            width: 300,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll, {
                                            height: null
                                        });
                                        grid.setHeight(grid.getHeight() + 5);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });

                                    it("should not show a vertical scrollbar if the update causes an underflow", function() {
                                        makeScrollGrid([{
                                            width: 300,
                                            variableRowHeight: true
                                        }, {
                                            flex: 1
                                        }], maxRowsBeforeScroll, {
                                            height: null
                                        });
                                        grid.setHeight(grid.getHeight() + 5);
                                        store.first().set('field1', makeRowDiv(2));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        store.first().set('field1', '1.1');
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });
                                });
                            });

                            describe("loading new content", function() {
                                describe("fixed width columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        store.loadData(makeRows(2));
                                        expect(false, false);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        store.loadData(makeRows(scrollRowSize + 20));
                                        expect(true, false);
                                    });

                                    it("should not show a vertical scrollbar if the new content does not require it", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        store.loadData(makeRows(1));
                                        expectScroll(false, false);
                                    });

                                    it("should not show a horizontal and vertical scrollbar if the new content does not require it", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], maxRowsBeforeScroll - 1);
                                        expectScroll(false, false);
                                        store.loadData(makeRows(1));
                                        expectScroll(false, false);
                                    });

                                    it("should show a vertical scrollbar if the old content did not require it", function() {
                                        makeScrollGrid([{
                                            width: 100
                                        }, {
                                            width: 300
                                        }], 1);
                                        expectScroll(false, false);
                                        store.loadData(makeRows(scrollRowSize));
                                        expectScroll(true, false);
                                    });

                                    it("should show a horizontal and vertical scrollbar if the old content did not require it", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], 1);
                                        expectScroll(false, false);
                                        store.loadData(makeRows(scrollRowSize + 1));
                                        // Horizontal overflow does not happen unless the vertical scrollbar took up the remaining 10px
                                        expectScroll(true, scrollbarsTakeSpace);
                                    });
                                });

                                describe("flexed columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.loadData(makeRows(2));
                                        expect(false, false);
                                        expectColumnWidths([500, 500]);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.loadData(makeRows(scrollRowSize + 20));
                                        expect(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });

                                    it("should not show a vertical scrollbar if the new content does not require it", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                        store.loadData(makeRows(1));
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                    });

                                    it("should show a vertical scrollbar if the old content did not require it", function() {
                                        makeScrollGrid([{
                                            flex: 1
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([500, 500]);
                                        store.loadData(makeRows(scrollRowSize));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [490, 490] : [500, 500]);
                                    });
                                });

                                describe("fixed width + flexed columns", function() {
                                    it("should not show a vertical scrollbar if there is no overflow", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        store.loadData(makeRows(2));
                                        expect(false, false);
                                        expectColumnWidths([300, 700]);
                                    });

                                    it("should retain a vertical scrollbar if overflow exists", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        store.loadData(makeRows(scrollRowSize + 20));
                                        expect(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });

                                    it("should not show a vertical scrollbar if the new content does not require it", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], scrollRowSize);
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                        store.loadData(makeRows(1));
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                    });

                                    it("should show a vertical scrollbar if the old content did not require it", function() {
                                        makeScrollGrid([{
                                            width: 300
                                        }, {
                                            flex: 1
                                        }], 1);
                                        expectScroll(false, false);
                                        expectColumnWidths([300, 700]);
                                        store.loadData(makeRows(scrollRowSize));
                                        expectScroll(true, false);
                                        expectColumnWidths(scrollbarsTakeSpace ? [300, 680] : [300, 700]);
                                    });
                                });
                            });
                        });

                        describe("header sizing", function() {
                            function expectHeaderWidth(width) {
                                expect(gridRef.headerCt.getLayout().innerCt.getWidth()).toBe(width);
                            }

                            describe("with no vertical and no horizontal scroll", function() {
                                visibleScrollbarsIt("should stretch to the grid size", function() {
                                    makeScrollGrid([{
                                        width: 300
                                    }], 1);
                                    expectHeaderWidth(1000);
                                });
                            });

                            describe("with no vertical and horizontal scroll", function() {
                                visibleScrollbarsIt("should stretch to the full column size", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], 1);
                                    expectHeaderWidth(1200);
                                });
                            });

                            describe("with vertical and no horizontal scroll", function() {
                                visibleScrollbarsIt("should stretch to the grid size", function() {
                                    makeScrollGrid([{
                                        width: 300
                                    }], 100);
                                    expectHeaderWidth(1000);
                                });
                            });

                            describe("with vertical and horizontal scroll", function() {
                                visibleScrollbarsIt("should account for the vertical scrollbar", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], 100);
                                    expectHeaderWidth(1220);
                                });

                                // EXTJS-15789
                                xdescribe("when the vertical scroll is caused by a horizontal scrollbar", function() {
                                    visibleScrollbarsIt("should account for the vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 600
                                        }, {
                                            width: 600
                                        }], maxRowsBeforeScroll);
                                        expectHeaderWidth(1220);
                                    });
                                });

                                describe("when the horizontal scroll is caused by a vertical scrollbar", function() {
                                    visibleScrollbarsIt("should account for the vertical scrollbar", function() {
                                        makeScrollGrid([{
                                            width: 495
                                        }, {
                                            width: 495
                                        }], scrollRowSize);
                                        expectHeaderWidth(1010);
                                    });
                                });
                            });
                        });

                        if (withLocking) {
                            describe("locked side horizontal scroll place holder", function() {
                                function expectLockedScroll(scroll) {
                                    var overflowX = grid.lockedGrid.getView().getScrollable().getX();

                                    expect(overflowX).toBe(true);

                                    if (scrollbarsTakeSpace) {
                                        expect(grid.lockedScrollbar.el.isVisible()).toBe(scroll);
                                    }
                                }

                                it("should show the placeholder when the normal side overflows", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], 1);
                                    // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                    expectLockedScroll(true);
                                });

                                it("should not show the placeholder when the normal side does not overflow", function() {
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        width: 300
                                    }], 1);
                                    expectLockedScroll(false);
                                });

                                it("should show the placeholder when a resize causes an overflow", function() {
                                    makeScrollGrid([{
                                        width: 400
                                    }, {
                                        width: 400
                                    }], 1);
                                    expectLockedScroll(false);
                                    grid.setWidth(grid.getWidth() - 400);
                                    // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                    expectLockedScroll(true);
                                });

                                it("should not show the placeholder when a resize causes an underflow", function() {
                                    makeScrollGrid([{
                                        width: 600
                                    }, {
                                        width: 600
                                    }], 1);
                                    // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                    expectLockedScroll(true);
                                    grid.setWidth(grid.getWidth() + 400);
                                    expectLockedScroll(false);
                                });

                                describe("column operations", function() {
                                    it("should show the placeholder when an add causes an overflow", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            width: 400
                                        }], 1);
                                        expectLockedScroll(false);
                                        grid.normalGrid.headerCt.add({
                                            width: 400,
                                            dataIndex: 'field3'
                                        });
                                        // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                        expectLockedScroll(true);
                                    });

                                    it("should show the placeholder when a show causes an overflow", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            width: 400
                                        }, {
                                            width: 400,
                                            hidden: true
                                        }], 1);
                                        expectLockedScroll(false);
                                        colRef[2].show();
                                        // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                        expectLockedScroll(true);
                                    });

                                    it("should not show the placeholder when a remove causes an underflow", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            width: 400
                                        }, {
                                            width: 400
                                        }], 1);
                                        // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                        expectLockedScroll(true);
                                        colRef[2].destroy();
                                        expectLockedScroll(false);
                                    });

                                    it("should show the placeholder when a hide causes an underflow", function() {
                                        makeScrollGrid([{
                                            width: 400
                                        }, {
                                            width: 400
                                        }, {
                                            width: 400
                                        }], 1);
                                        // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                        expectLockedScroll(true);
                                        colRef[2].hide();
                                        expectLockedScroll(false);
                                    });
                                });

                                describe("store operation", function() {
                                    describe("adding", function() {
                                        it("should not show the placeholder when the add does not trigger an overflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], 1);
                                            expectLockedScroll(false);
                                            store.add({});
                                            expectLockedScroll(false);
                                        });

                                        it("should retain the placeholder when the add does not trigger an underflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], scrollRowSize);
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.add({});
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                        });

                                        it("should show the placeholder when an add causes an overflow via a vertical scrollbar", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll);
                                            expectLockedScroll(false);
                                            store.add({});
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                        });
                                    });

                                    describe("removing", function() {
                                        it("should not show the placeholder when there is no overflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll);
                                            expectLockedScroll(false);
                                            store.removeAt(0);
                                            expectLockedScroll(false);
                                        });

                                        it("should retain the placeholder when the remove does not trigger an underflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], scrollRowSize);
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.removeAt(0);
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                        });

                                        it("should not show the placeholder when a remove causes an underflow via a vertical scrollbar", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll + 1);
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.removeAt(0);
                                            expectLockedScroll(false);
                                        });
                                    });

                                    describe("update via variableRowHeight", function() {
                                        it("should not show the placeholder when there is no overflow", function() {
                                            makeScrollGrid([{
                                                width: 495,
                                                variableRowHeight: true
                                            }, {
                                                width: 495
                                            }], 1);
                                            expectLockedScroll(scrollbarsTakeSpace ? false : true);
                                            store.first().set('field1', makeRowDiv(2));
                                            expectLockedScroll(scrollbarsTakeSpace ? false : true);
                                        });

                                        it("should retain the placeholder when the update does not cause an overflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll, {
                                                height: null,
                                                viewConfig: {
                                                    variableRowHeight: true
                                                }
                                            });
                                            grid.setHeight(grid.getHeight() + 5);
                                            store.first().set('field1', makeRowDiv(2));
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.first().set('field1', '1.1');
                                            expectLockedScroll(false);
                                        });

                                        it("should show the placeholder when an update causes an overflow via a vertical scrollbar", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll, {
                                                height: null,
                                                viewConfig: {
                                                    variableRowHeight: true
                                                }
                                            });
                                            grid.setHeight(grid.getHeight() + 5);
                                            expectLockedScroll(false);
                                            store.first().set('field1', makeRowDiv(2));
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                        });

                                        it("should not show the placeholder when an update causes an underflow via a vertical scrollbar", function() {
                                            var data = makeRows(maxRowsBeforeScroll);

                                            data[0].field1 = makeRowDiv(2);
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], maxRowsBeforeScroll, {
                                                height: null,
                                                viewConfig: {
                                                    variableRowHeight: true
                                                }
                                            });
                                            grid.setHeight(grid.getHeight() + 5);
                                            store.first().set('field1', makeRowDiv(2));
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.first().set('field1', '1.1');
                                            expectLockedScroll(false);
                                        });
                                    });

                                    describe("loading new content", function() {
                                        it("should not show the placeholder if there is no overflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], 1);
                                            expectLockedScroll(scrollbarsTakeSpace ? false : true);
                                            store.loadData(makeRows(2));
                                            expectLockedScroll(scrollbarsTakeSpace ? false : true);
                                        });

                                        it("should retain the placeholder if there is no underflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], scrollRowSize);
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.loadData(makeRows(scrollRowSize + 10));
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                        });

                                        it("should not show the placeholder when load causes an underflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], scrollRowSize);
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                            store.loadData(makeRows(1));
                                            expectLockedScroll(false);
                                        });

                                        it("should show the placeholder when load causes an overflow", function() {
                                            makeScrollGrid([{
                                                width: 495
                                            }, {
                                                width: 495
                                            }], 1);
                                            expectLockedScroll(false);
                                            store.loadData(makeRows(scrollRowSize));
                                            // Will only turn the locked side's overflowX to 'scroll' if it has to match a space-taking scrollbar on the normal side
                                            expectLockedScroll(true);
                                        });
                                    });
                                });
                            });

                            describe("scrollbars caused by height synchronization", function() {
                                beforeEach(function() {
                                    lockedIsVariable = true;
                                });

                                it("should show a vertical scrollbar if the locked content cause overflow", function() {
                                    var data = makeRows(maxRowsBeforeScroll);

                                    data[0].field10 = makeRowDiv(2);
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        width: 300
                                    }], data);
                                    expectScroll(true, false);
                                });

                                it("should show a vertical scrollbar if adding causes overflow", function() {
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        width: 300
                                    }], maxRowsBeforeScroll - 1);
                                    expectScroll(false, false);
                                    store.add({
                                        field10: makeRowDiv(2)
                                    });
                                    expectScroll(true, false);
                                });

                                it("should not show a vertical scrollbar if remving causes underflow", function() {
                                    var data = makeRows(maxRowsBeforeScroll);

                                    data[0].field10 = makeRowDiv(2);
                                    makeScrollGrid([{
                                        width: 100
                                    }, {
                                        width: 300
                                    }], data);
                                    expectScroll(true, false);
                                    store.removeAt(0);
                                    expectScroll(false, false);
                                });

                                it("should show a vertical scrollbar if update causes an overflow", function() {
                                    makeScrollGrid([{
                                        width: 100,
                                        variableRowHeight: true
                                    }, {
                                        width: 300
                                    }], maxRowsBeforeScroll);
                                    expectScroll(false, false);
                                    store.first().set('field10', makeRowDiv(2));
                                    expectScroll(true, false);
                                });

                                it("should not show a vertical scrollbar if update causes an underflow", function() {
                                    var data = makeRows(maxRowsBeforeScroll);

                                    data[0].field10 = makeRowDiv(2);
                                    makeScrollGrid([{
                                        width: 100,
                                        variableRowHeight: true
                                    }, {
                                        width: 300
                                    }], data);
                                    expectScroll(true, false);
                                    store.first().set('field10', '1.1');
                                    expectScroll(false, false);
                                });
                            });
                        }
                    });
                }

                makeScrollSuite(false);
                makeScrollSuite(true);
            });

            describe("disable/enable grids", function() {
                it("should disable single grids", function() {
                    makeGrid();
                    grid.disable();

                    expect(grid.isMasked()).toBe(true);
                    expect(grid.headerCt.disabled).toBe(true);
                    expect(grid.headerCt.isMasked()).toBeFalsy();
                    expect(grid.headerCt.isFocusableContainerActive()).toBeFalsy();

                    grid.enable();
                    expect(grid.isMasked()).toBeFalsy();
                    expect(grid.headerCt.disabled).toBe(false);
                    expect(grid.headerCt.isMasked()).toBeFalsy();
                    expect(grid.headerCt.isFocusableContainerActive()).toBeTruthy();
                });

                it("should disable locking grids", function() {
                    makeGrid(null, undefined, null, null, true);
                    grid.disable();

                    // Outermost grid should be masked
                    expect(grid.isMasked()).toBe(true);

                    // Locked side
                    expect(grid.lockedGrid.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.headerCt.disabled).toBe(true);
                    expect(grid.lockedGrid.headerCt.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.headerCt.isFocusableContainerActive()).toBeFalsy();

                    // Normal side
                    expect(grid.normalGrid.isMasked()).toBeFalsy();
                    expect(grid.normalGrid.headerCt.disabled).toBe(true);
                    expect(grid.normalGrid.headerCt.isMasked()).toBeFalsy();
                    expect(grid.normalGrid.headerCt.isFocusableContainerActive()).toBeFalsy();

                    grid.enable();

                    // Outermost grid should not be masked
                    expect(grid.isMasked()).toBeFalsy();

                    // Locked side
                    expect(grid.lockedGrid.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.headerCt.disabled).toBe(false);
                    expect(grid.lockedGrid.headerCt.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.headerCt.isFocusableContainerActive()).toBeTruthy();

                    // Normal side
                    expect(grid.normalGrid.isMasked()).toBeFalsy();
                    expect(grid.normalGrid.headerCt.disabled).toBe(false);
                    expect(grid.normalGrid.headerCt.isMasked()).toBeFalsy();
                    expect(grid.normalGrid.headerCt.isFocusableContainerActive()).toBeTruthy();
                });
            });

            describe("disable/enable grid views", function() {
                it("should only disable the view when view.disable is called", function() {
                    makeGrid();
                    grid.view.disable();

                    expect(grid.disabled).toBe(false);
                    expect(grid.isMasked()).toBeFalsy();
                    expect(grid.view.disabled).toBe(true);
                    expect(grid.view.isMasked()).toBe(true);

                    grid.view.enable();

                    expect(grid.disabled).toBe(false);
                    expect(grid.isMasked()).toBeFalsy();
                    expect(grid.view.disabled).toBe(false);
                    expect(grid.view.isMasked()).toBeFalsy();
                });

                it("should disable both views in a locking grid when view.disable is alled on a locking grid", function() {
                    makeGrid(null, undefined, null, null, true);
                    grid.view.disable();

                    expect(grid.disabled).toBe(false);
                    expect(grid.isMasked()).toBeFalsy();

                    expect(grid.normalGrid.disabled).toBe(false);
                    expect(grid.normalGrid.isMasked()).toBeFalsy();
                    expect(grid.normalGrid.headerCt.disabled).toBe(false);
                    expect(grid.normalGrid.headerCt.isMasked()).toBeFalsy();

                    expect(grid.lockedGrid.disabled).toBe(false);
                    expect(grid.lockedGrid.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.headerCt.disabled).toBe(false);
                    expect(grid.lockedGrid.headerCt.isMasked()).toBeFalsy();

                    expect(grid.normalGrid.view.disabled).toBe(true);
                    expect(grid.normalGrid.view.isMasked()).toBeTruthy();
                    expect(grid.lockedGrid.view.disabled).toBe(true);
                    expect(grid.lockedGrid.view.isMasked()).toBeTruthy();

                    grid.view.enable();

                    expect(grid.disabled).toBe(false);
                    expect(grid.isMasked()).toBeFalsy();

                    expect(grid.normalGrid.disabled).toBe(false);
                    expect(grid.normalGrid.isMasked()).toBeFalsy();
                    expect(grid.normalGrid.headerCt.disabled).toBe(false);
                    expect(grid.normalGrid.headerCt.isMasked()).toBeFalsy();

                    expect(grid.lockedGrid.disabled).toBe(false);
                    expect(grid.lockedGrid.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.headerCt.disabled).toBe(false);
                    expect(grid.lockedGrid.headerCt.isMasked()).toBeFalsy();

                    expect(grid.normalGrid.view.disabled).toBe(false);
                    expect(grid.normalGrid.view.isMasked()).toBeFalsy();
                    expect(grid.lockedGrid.view.disabled).toBe(false);
                    expect(grid.lockedGrid.view.isMasked()).toBeFalsy();
                });
            });
        });

        if (buffered) {
            describe("cellWrap: true column width changing halfway down buffer rendered large dataset", function() {
                var i,
                    data = [],
                    lorem = "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.",
                    loremLen = lorem.length,
                    minLen = loremLen - 100,
                    store,
                    grid,
                    view,
                    bufferedRenderer,
                    rrSpy;

                for (i = 0; i < 500; i++) {
                    data.push(['Row ' + (i + 1), lorem.substr(0, Ext.Number.randomInt(minLen, loremLen))]);
                }

                afterEach(function() {
                    Ext.destroy(store, grid);
                });

                it("should adjust view body position if column width change causes body to move out of view", function() {
                    store = new Ext.data.ArrayStore({
                        data: data,
                        fields: ['row', 'lorem']
                    });

                    grid = new Ext.grid.Panel({
                        height: 300,
                        width: 400,
                        title: 'Test',
                        store: store,
                        buffered: true,
                        columns: [{
                            text: 'Row',
                            dataIndex: 'row',
                            width: 50
                        }, {
                            text: 'Lorem',
                            dataIndex: 'lorem',
                            flex: 1,
                            cellWrap: true
                        }],
                        renderTo: document.body
                    });
                    view = grid.view;
                    bufferedRenderer = view.bufferedRenderer;
                    bufferedRenderer.scrollTo(100);

                    rrSpy = spyOn(bufferedRenderer, "renderRange").andCallThrough();

                    // Widen and then shrink that first column
                    grid.getVisibleColumnManager().getColumns()[0].setWidth(150);
                    grid.getVisibleColumnManager().getColumns()[0].setWidth(50);

                    // BufferedRenderer#setViewSize just adds/remove records
                    expect(rrSpy.callCount).toBe(0);

                    // Body top must be above the top of the viewport
                    expect(bufferedRenderer.bodyTop).toBeLessThan(bufferedRenderer.scrollTop);

                    // The body bottom must be below the bottom of the viewport
                    expect(bufferedRenderer.bodyTop + view.body.dom.offsetHeight).toBeGreaterThan(bufferedRenderer.scrollTop + view.el.dom.clientHeight);

                    // Remove all but the last record
                    store.removeAt(0, 498);

                    // BufferedRenderer#setViewSize just adds/remove records
                    expect(rrSpy.callCount).toBe(0);

                    // Should move the rendered range to top if the viewSize >= storeCount
                    expect(bufferedRenderer.bodyTop).toBe(0);
                });

                it("should not refresh if the rendered view is positioned at the start", function() {
                    store = new Ext.data.ArrayStore({
                        data: data,
                        fields: ['row', 'lorem']
                    });

                    grid = new Ext.grid.Panel({
                        height: 300,
                        width: 400,
                        title: 'Test',
                        store: store,
                        buffered: true,
                        columns: [{
                            text: 'Row',
                            dataIndex: 'row',
                            width: 50
                        }, {
                            text: 'Lorem',
                            dataIndex: 'lorem',
                            flex: 1,
                            cellWrap: true
                        }],
                        renderTo: document.body
                    });
                    view = grid.view;
                    bufferedRenderer = view.bufferedRenderer;
                    rrSpy = spyOn(bufferedRenderer, "renderRange").andCallThrough();

                    // Widen and then shrink that first column
                    grid.getVisibleColumnManager().getColumns()[0].setWidth(150);
                    grid.getVisibleColumnManager().getColumns()[0].setWidth(50);

                    // Nothing happened
                    expect(bufferedRenderer.bodyTop).toBe(0);
                    expect(rrSpy.callCount).toBe(0);
                });
            });
        }
    }

    createSuite(false);
    createSuite(true);

    describe("Focus column header after deleting only row", function() {
        it("should focus the column header after activating an action item which deletes the last row", function() {
            grid = new Ext.grid.Panel({
                renderTo: document.body,
                height: 400,
                width: 600,
                columns: [{
                    xtype: 'widgetcolumn',
                    text: 'Button',
                    widget: {
                        xtype: 'button',
                        text: 'Edit',
                        handler: function() {
                            grid.store.removeAt(0);
                        }
                    }
                }],
                store: [{

                }]
            });

            var col, cell00, widget00;

            col = grid.getVisibleColumnManager().getColumns()[0];
            cell00 = new Ext.grid.CellContext(grid.view).setPosition(0, 0);
            widget00 = col.getWidget(grid.store.getAt(0));

            // First jump into the grid. Get tabIndex values correct as in real world grid use.
            grid.getNavigationModel().setPosition(0, 0);

            waitsForFocus(cell00.getCell(true), 'Cell 0,0 to gain focus');

            runs(function() {
                grid.setActionableMode(true, cell00);
            });

            // Widget in cell 0,0 must have focus
            waitsForFocus(widget00);

            runs(function() {
                jasmine.fireKeyEvent(document.activeElement, 'keydown', Ext.event.Event.SPACE);
            });

            // When the record is deleted from actioning the focused button, focus must
            // move into the column header
            waitsForFocus(col.el, 'Column header 0 to gain focus');
        });

        describe("direct focus of internal tabbable element", function() {
            it("should enter actionable mode", function() {
                grid = new Ext.grid.Panel({
                    renderTo: document.body,
                    height: 400,
                    width: 600,
                    columns: [{
                        xtype: 'actioncolumn'
                    }],
                    store: [{

                    }]
                });
                var col = grid.getVisibleColumnManager().getColumns()[0],
                    cell00 = new Ext.grid.CellContext(grid.view).setPosition(0, 0),
                    icon = cell00.getCell(true).querySelector('.' + col.actionIconCls);

                // Focus directly inside the cell.
                // This should result in actionable mode.
                focusAndWait(icon, null, 'action icon to focus');

                runs(function() {
                    expect(grid.actionableMode).toBe(true);
                });
            });
        });

        describe('SHIFT+TAB back into grid', function() {
            it("should enter navigable mode on the last cell", function() {
                grid = new Ext.grid.Panel({
                    renderTo: document.body,
                    height: 400,
                    width: 600,
                    columns: [{
                        xtype: 'actioncolumn'
                    }],
                    store: [{

                    }, {

                    }]
                });
                var cell01 = new Ext.grid.CellContext(grid.view).setPosition(1, 0);

                // Focus the tabGuard directly to imitate SHIFT+TAB from a following element.
                grid.view.tabGuardEl.focus();

                waitsForFocus(grid.view, 'view to gain focus');

                runs(function() {
                    expect(grid.getNavigationModel().getPosition().isEqual(cell01)).toBe(true);
                    expect(Ext.Element.getActiveElement()).toBe(cell01.getCell(true));
                    expect(grid.actionableMode).not.toBe(true);
                });
            });
        });

        describe('Changing locked column width with no vertical overflow with syncRowHeight: false', function() {
            it('should not throw a layout failure', function() {
                grid = new Ext.grid.Panel({
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 300,
                    split: true,
                    syncRowHeight: false,
                    store: new Ext.data.Store({
                        fields: ['id', 'name', 'name1'],
                        data: [
                            { id: 1, name: '1', name1: 'one' }
                        ]
                    }),
                    columns: [
                        { text: 'Id', dataIndex: 'id', locked: true, width: 100 },
                        { text: 'Name', dataIndex: 'name', locked: true, width: 100 },
                        { text: 'Name1', dataIndex: 'name1', width: 100 }
                    ]
                });

                grid.columns[0].setWidth(150);

                // Must have successfully layed out the table with the new first cell width
                expect(Ext.fly(grid.lockedGrid.getView().getCell(0, 0)).getWidth()).toBe(150);
            });
        });
    });
});
