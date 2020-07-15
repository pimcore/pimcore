topSuite("Ext.grid.column.Widget",
    ['Ext.grid.Panel', 'Ext.Button', 'Ext.app.ViewController', 'Ext.form.RadioGroup',
     'Ext.form.field.ComboBox', 'Ext.tab.Panel', 'Ext.ProgressBarWidget'],
function() {
    var webkitIt = Ext.isWebKit ? it : xit,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    var Model = Ext.define(null, {
        extend: 'Ext.data.Model',
        fields: ['a', 'b', 'c']
    });

    var grid, view, store, colRef, navModel;

    function generateData(end) {
        var data = [],
            i;

        end = end || 10;

        for (i = 1; i <= end; i++) {
            data.push({
                id: 'rec' + i,
                a: i + 'a',
                b: i + 'b',
                c: i + 'c',
                d: i / 10
            });
        }

        return data;
    }

    function getColCfg(widget) {
        return {
            text: 'Button',
            xtype: 'widgetcolumn',
            width: 200,
            dataIndex: 'a',
            widget: widget
        };
    }

    function createGrid(columns, data, cfg) {
        columns = columns || [getColCfg({
            xtype: 'button'
        })];

        if (typeof data === 'number' || data == null) {
            data = generateData(data || 4);
        }

        store = new Ext.data.Store({
            model: Model,
            data: data,
            proxy: {
                type: 'memory',
                data: data
            }
        });

        grid = new Ext.grid.Panel(Ext.apply({
            renderTo: Ext.getBody(),
            columns: columns,
            width: 1000,
            height: 500,
            border: false,
            store: store,
            viewConfig: {
                mouseOverOutBuffer: 0
            }
        }, cfg));
        view = grid.getView();
        navModel = view.getNavigationModel();
        colRef = grid.getColumnManager().getColumns();
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        Ext.destroy(grid);
        grid = store = colRef = null;
    });

    function getWidget(index, col) {
        col = col || colRef[0];

        return col.getWidget(store.getAt(index));
    }

    function getPadding() {
        var cell = grid.getView().getEl().down(colRef[0].getCellInnerSelector());

        return parseInt(cell.getStyle('padding-left'), 10) + parseInt(cell.getStyle('padding-right'), 10);
    }

    describe("refocusing after using a column widget to trigger a delete", function() {
        it("should refocus the next row upon deletion", function() {
            createGrid([{
                text: 'Button',
                xtype: 'widgetcolumn',
                width: 200,
                dataIndex: 'a',
                widget: {
                    xtype: 'button',
                    text: 'Delete row',
                    handler: function(button) {
                        var rec = button.getWidgetRecord();

                        store.remove(rec);
                    }
                }
            }]);

            var widget0 = getWidget(0),
                widget1 = getWidget(1),
                rec0 = store.getAt(0),
                rec1 = store.getAt(1),
                toDelete = widget0.getWidgetRecord(),
                newTop = getWidget(1).getWidgetRecord(),
                storeCount = store.getCount();

            expect(toDelete).toBe(rec0);
            expect(newTop).toBe(rec1);

            // Focus the button, and enter actionable mode
            grid.setActionableMode(true, new Ext.grid.CellContext(view).setPosition(0, 0));

            // Wait for the widget to be focused
            waitsForFocus(widget0);

            runs(function() {
                jasmine.fireKeyEvent(widget0.focusEl, 'keydown', Ext.event.Event.SPACE);

                // That should have deleted a record
                expect(store.getCount()).toBe(storeCount - 1);

                // The widget's record must have gone
                expect(store.contains(toDelete)).toBe(false);
            });

            // The new widget 0 must receive focus when any async focus events have run their course
            waitsForFocus(widget1);

            runs(function() {
                widget0 = getWidget(0);

                expect(widget0.el.contains(document.activeElement)).toBe(true);

                // Widget 0 record must be what we got from widget 1 initially
                expect(widget0.getWidgetRecord()).toBe(newTop);
            });
        });
    });

    describe("Widget recycling across refresh", function() {
        it("should recycle widgets", function() {
            var cfg = {
                xtype: 'button',
                cls: 'foo'
            };

            createGrid([getColCfg(cfg)]);
            var w1 = getWidget(0),
                w2 = getWidget(1),
                w3 = getWidget(2),
                w4 = getWidget(3);

            // The store is cleared on reload.
            // Widget instances MUST be collected for reuse.
            store.reload();

            // The Widgets should have been reused.
            expect(getWidget(0) === w1).toBe(true);
            expect(getWidget(1) === w2).toBe(true);
            expect(getWidget(2) === w3).toBe(true);
            expect(getWidget(3) === w4).toBe(true);
        });
    });

    describe("construction", function() {
        it("should not modify the widget config", function() {
            var cfg = {
                xtype: 'button',
                cls: 'foo'
            };

            createGrid([getColCfg(cfg)]);
            expect(cfg).toEqual({
                xtype: 'button',
                cls: 'foo'
            });
        });
    });

    describe("Focusing the widget", function() {
        beforeEach(function() {
            createGrid();
        });

        // TODO: Add tests when Widgets are genuinely focusable.
        it("should not throw an error", function() {
            expect(function() {
                getWidget(0).focus();
            }).not.toThrow();
        });
    });

    describe("widget refocus on row delete", function() {
        // Test that focus reversion upon delete of focus-containing row works.
        webkitIt("should not cause an error when deleting the focused row using an actionable widget", function() {
            createGrid([{
                itemId: 'ct',
                columns: [getColCfg({
                    xtype: 'button',
                    handler: function(btn) {
                        var rec = btn.getWidgetRecord();

                        store.remove(rec);
                    }
                })]
            }]);
            var btn = colRef[0].getWidget(store.last());

            // The mousedown part will focus the button, and flip into actionable mode.
            // The click phase will delete the row.
            // Focus should revert to the previous row
            jasmine.fireMouseEvent(btn.el.dom, 'click');

            // Should remove the last record
            expect(view.all.getCount()).toBe(3);

            // And focus should have jumped from the mousedowned button (which has gone)
            // to the button above it.
            // NavigationModel does not enter actionable mode for touches.
            if (!jasmine.supportsTouch) {
                expect(colRef[0].getWidget(store.last()).hasFocus).toBe(true);
            }
        });
    });

    describe("stopSelection", function() {
        beforeEach(function() {
            createGrid();
        });

        it("should not select the row on click of the widget with stopSelection: true", function() {
            jasmine.fireMouseEvent(getWidget(0).getEl().dom, 'click');
            expect(grid.getSelectionModel().isSelected(0)).toBe(false);
        });

        it("should select the row on click of the widget with stopSelection: false", function() {
            colRef[0].stopSelection = false;
            navModel.setPosition(new Ext.grid.CellContext(view).setPosition(0, 0));

            waitsForFocus(view);

            // Wait for focus to be in the view.
            // Because IE has async focusing.
            runs(function() {
                jasmine.fireMouseEvent(getWidget(0).getEl().dom, 'click');
                expect(grid.getSelectionModel().isSelected(0)).toBe(true);
            });
        });
    });

    describe("widget scope resolution", function() {
        it("should resolve to a view controller", function() {
            var Cls = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onButtonClick: function() {}
            });

            var ctrl = new Cls();

            createGrid([getColCfg({
                xtype: 'button',
                handler: 'onButtonClick'
            })], undefined, {
                controller: ctrl
            });

            spyOn(ctrl, 'onButtonClick');
            jasmine.fireMouseEvent(getWidget(0).getEl().dom, 'click');
            expect(ctrl.onButtonClick).toHaveBeenCalled();
        });

        it("should handle scope: 'this'", function() {
            Ext.define('spec.Button', {
                extend: 'Ext.button.Button',
                alias: 'widget.subbutton',
                onButtonClick: function() {}
            });

            createGrid([getColCfg({
                xtype: 'subbutton',
                handler: 'onButtonClick',
                scope: 'this'
            })]);

            var btn = getWidget(0);

            spyOn(btn, 'onButtonClick');

            jasmine.fireMouseEvent(getWidget(0).getEl().dom, 'click');
            expect(btn.onButtonClick).toHaveBeenCalled();

            Ext.undefine('spec.Button');
        });
    });

    function createBufferedSuite(withBuffered) {
        describe(withBuffered ? "with buffered rendering" : "without buffered rendering", function() {
            function makeGrid(columns, data, cfg) {
                cfg = cfg || {};
                cfg.bufferedRenderer = !!withBuffered;
                createGrid(columns, data, cfg);
            }

            function checkPositions(start) {
                start = start || 0;

                var col = grid.down('widgetcolumn'),
                    view = grid.getView(),
                    selector, cells, len, i;

                if (col && view.viewReady) {
                    selector = col.getCellInnerSelector();
                    cells = view.getEl().dom.querySelectorAll(selector);
                    len = cells.lengtj;

                    for (i = start; i < len; ++i) {
                        expect(getWidget(i, col).getEl().dom.parentNode).toBe(cells[i]);
                    }
                }
            }

            describe("basic functionality", function() {
                it("should render a widget for each row", function() {
                    makeGrid();
                    expect(getWidget(0).isComponent).toBe(true);
                    expect(getWidget(1).isComponent).toBe(true);
                    expect(getWidget(2).isComponent).toBe(true);
                    expect(getWidget(3).isComponent).toBe(true);
                    checkPositions();
                });

                it("should not bust the row height when showing a button", function() {
                    var columns = [getColCfg({
                            xtype: 'button'
                        }), {
                            text: 'Data',
                            dataIndex: 'b'
                        }],
                        rowHeight;

                    // Widget column initially hidden
                    columns[0].hidden = true;

                    makeGrid(columns);

                    // Capture default, text-only row height
                    rowHeight = grid.view.getNode(0).offsetHeight;

                    // Show the widget column with buttons
                   colRef[0].show();

                    // Showing the button should NOT change the row's height
                    // https://sencha.jira.com/browse/EXTJS-13766
                    expect(grid.view.getNode(0).offsetHeight).toBe(rowHeight);
                    checkPositions();
                });

                it("should render the matching xtype", function() {
                    makeGrid();
                    expect(getWidget(0).getXType()).toBe('button');
                    checkPositions();
                });

                it("should pass in other configurations", function() {
                    makeGrid([getColCfg({
                        xtype: 'button',
                        enableToggle: true,
                        pressed: true
                    })]);

                    var widget = getWidget(0);

                    expect(widget.pressed).toBe(true);
                    expect(widget.enableToggle).toBe(true);
                    checkPositions();
                });

                it("should create a new instance for each row", function() {
                    makeGrid();
                    var w1 = getWidget(0),
                        w2 = getWidget(1),
                        w3 = getWidget(2),
                        w4 = getWidget(3);

                    expect(w2).not.toBe(w1);
                    expect(w3).not.toBe(w1);
                    expect(w4).not.toBe(w1);

                    expect(w3).not.toBe(w2);
                    expect(w4).not.toBe(w2);

                    expect(w4).not.toBe(w3);

                    checkPositions();
                });

                it("should set the value of the defaultBindProperty on the widget to the dataIndex", function() {
                    makeGrid();
                    expect(getWidget(0).getText()).toBe('1a');
                    expect(getWidget(1).getText()).toBe('2a');
                    expect(getWidget(2).getText()).toBe('3a');
                    expect(getWidget(3).getText()).toBe('4a');
                    checkPositions();
                });

                it("should not modify the defaultBindProperty if there is no dataIndex", function() {
                    makeGrid([{
                        xtype: 'widgetcolumn',
                        width: 200,
                        widget: {
                            xtype: 'button',
                            text: 'Foo'
                        }
                    }]);
                    expect(getWidget(0).getText()).toBe('Foo');
                    expect(getWidget(1).getText()).toBe('Foo');
                    expect(getWidget(2).getText()).toBe('Foo');
                    expect(getWidget(3).getText()).toBe('Foo');
                    checkPositions();
                });
            });

            describe("tdCls", function() {
                it("should get the tdCls from the widget", function() {
                    makeGrid([getColCfg({
                        xtype: 'button',
                        getTdCls: function() {
                            return 'foo';
                        }
                    })]);
                    expect(view.getCellByPosition({ row: 0, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 1, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 2, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 3, column: 0 }, true)).toHaveCls('foo');
                });

                it("should combine a tdCls on the column with the tdCls on the widget", function() {
                    var cfg = getColCfg({
                        xtype: 'button',
                        getTdCls: function() {
                            return 'foo';
                        }
                    });

                    cfg.tdCls = 'bar';
                    makeGrid([cfg]);
                    expect(view.getCellByPosition({ row: 0, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 0, column: 0 }, true)).toHaveCls('bar');
                    expect(view.getCellByPosition({ row: 1, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 1, column: 0 }, true)).toHaveCls('bar');
                    expect(view.getCellByPosition({ row: 2, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 2, column: 0 }, true)).toHaveCls('bar');
                    expect(view.getCellByPosition({ row: 3, column: 0 }, true)).toHaveCls('foo');
                    expect(view.getCellByPosition({ row: 3, column: 0 }, true)).toHaveCls('bar');
                });
            });

            describe("onWidgetAttach", function() {
                var spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                });

                afterEach(function() {
                    spy = null;
                });

                it("should call the method during render", function() {
                    var cfg = getColCfg({
                        xtype: 'button'
                    });

                    cfg.onWidgetAttach = spy;
                    makeGrid([cfg]);
                    expect(spy.callCount).toBe(store.getCount());
                });

                it("should pass the column, the widget instance and the record", function() {
                    var cfg = getColCfg({
                        xtype: 'button'
                    });

                    cfg.onWidgetAttach = spy;
                    makeGrid([cfg]);

                    expect(spy.calls[0].args[0]).toBe(colRef[0]);
                    expect(spy.calls[0].args[1].isButton).toBe(true);
                    expect(spy.calls[0].args[2]).toBe(store.getAt(0));

                    expect(spy.calls[1].args[0]).toBe(colRef[0]);
                    expect(spy.calls[1].args[1].isButton).toBe(true);
                    expect(spy.calls[1].args[2]).toBe(store.getAt(1));

                    expect(spy.calls[2].args[0]).toBe(colRef[0]);
                    expect(spy.calls[2].args[1].isButton).toBe(true);
                    expect(spy.calls[2].args[2]).toBe(store.getAt(2));

                    expect(spy.calls[3].args[0]).toBe(colRef[0]);
                    expect(spy.calls[3].args[1].isButton).toBe(true);
                    expect(spy.calls[3].args[2]).toBe(store.getAt(3));
                });

                it("should get called when a new record is added", function() {
                    var cfg = getColCfg({
                        xtype: 'button'
                    });

                    cfg.onWidgetAttach = spy;
                    makeGrid([cfg]);
                    spy.reset();
                    var rec = store.insert(2, {})[0];

                    expect(spy.calls[0].args[0]).toBe(colRef[0]);
                    expect(spy.calls[0].args[1].isButton).toBe(true);
                    expect(spy.calls[0].args[2]).toBe(rec);
                });

                it("should be called after rendering the widget", function() {
                    var isAttached = false,
                        cfg = getColCfg({
                            xtype: 'button'
                        });

                    cfg.onWidgetAttach = spy;

                    makeGrid([cfg]);

                    spy.andCallFake(function(column, widget) {
                        isAttached = Ext.getBody().isAncestor(widget.el);
                    });

                    store.insert(2, {});

                    expect(isAttached).toBe(true);
                });

                if (withBuffered) {
                    describe("buffered rendering", function() {
                        var recordSize = 10000,
                            data, i;

                        beforeEach(function() {
                             var cfg = getColCfg({
                                xtype: 'button'
                            });

                            cfg.onWidgetAttach = spy;

                            data = [];

                            for (i = 1; i <= recordSize; ++i) {
                                data.push({
                                    id: 'rec' + i
                                });
                            }

                            makeGrid([cfg], data);
                        });

                        it("should only be called for records in the view", function() {
                            var view = grid.getView(),
                                nodes = view.getNodes(),
                                firstNode = nodes[0],
                                len = nodes.length;

                            expect(spy.callCount).toBeLessThan(recordSize);

                            for (i = 0; i < len; ++i) {
                                expect(spy.calls[i].args[2]).toBe(store.getAt(i));
                            }

                            checkPositions();

                            spy.reset();
                            // Force it to the end, wait for the re-render
                            grid.bufferedRenderer.scrollTo(recordSize * 100);
                            waitsFor(function() {
                                return view.getNodes()[0] !== firstNode;
                            });

                            runs(function() {
                                nodes = view.getNodes();
                                len = nodes.length;
                                var offset = recordSize - len;

                                for (i = 0; i < len; ++i) {
                                    expect(spy.calls[i].args[2]).toBe(store.getAt(i + offset));
                                }

                                checkPositions(offset);
                            });
                        });

                        // https://sencha.jira.com/browse/EXTJS-19251
                        it("should be called after reattaching the widget to DOM", function() {
                            var isAttached = false;

                            spy.andCallFake(function(column, widget) {
                                isAttached = isAttached || Ext.getBody().isAncestor(widget.el);
                            });
                            spy.reset();

                            grid.bufferedRenderer.scrollTo(recordSize * 100);

                            waitForSpy(spy, 'scrolling to occur', 1000);

                            runs(function() {
                                expect(isAttached).toBe(true);
                            });
                        });
                    });
                }

                describe("scope", function() {
                    it("should default the scope to the column", function() {
                        var cfg = getColCfg({
                            xtype: 'button'
                        });

                        cfg.onWidgetAttach = spy;
                        makeGrid([cfg]);
                        expect(spy.mostRecentCall.object).toBe(colRef[0]);
                    });

                    it("should use a passed scope", function() {
                        var cfg = getColCfg({
                                xtype: 'button'
                            }),
                            o = {};

                        cfg.onWidgetAttach = spy;
                        cfg.scope = o;
                        makeGrid([cfg]);
                        expect(spy.mostRecentCall.object).toBe(o);
                    });

                    it("should be able to resolve to a view controller method", function() {
                        var cfg = getColCfg({
                            xtype: 'button'
                        });

                        var ctrl = new Ext.app.ViewController();

                        ctrl.doSomething = spy;
                        cfg.onWidgetAttach = 'doSomething';
                        makeGrid([cfg], null, {
                            controller: ctrl
                        });
                        expect(spy.callCount).toBe(4);
                    });
                });
            });

            describe("add/remove column", function() {
                it("should render widgets when adding the column dynamically", function() {
                    makeGrid([]);
                    grid.headerCt.add(getColCfg({
                        xtype: 'button'
                    }));
                    colRef = grid.getColumnManager().getColumns();
                    expect(getWidget(0).getText()).toBe('1a');
                    expect(getWidget(1).getText()).toBe('2a');
                    expect(getWidget(2).getText()).toBe('3a');
                    expect(getWidget(3).getText()).toBe('4a');
                    checkPositions();
                });

                it("should not cause an error when removing", function() {
                    makeGrid();
                    expect(function() {
                        grid.headerCt.remove(colRef[0]);
                    }).not.toThrow();
                });

                it("should be able to re-use the column", function() {
                    makeGrid();
                    grid.headerCt.remove(colRef[0], false);
                    grid.headerCt.add(colRef[0]);
                    expect(getWidget(0).getText()).toBe('1a');
                    expect(getWidget(1).getText()).toBe('2a');
                    expect(getWidget(2).getText()).toBe('3a');
                    expect(getWidget(3).getText()).toBe('4a');
                    checkPositions();
                });

                it("should be able to move a column to the left", function() {
                    makeGrid([{}, getColCfg({
                        xtype: 'button'
                    })]);
                    grid.headerCt.moveBefore(colRef[0], colRef[1]);
                    checkPositions();
                });

                it("should be able to move a column to the right", function() {
                    makeGrid([getColCfg({
                        xtype: 'button'
                    }), {}]);
                    grid.headerCt.moveAfter(colRef[1], colRef[0]);
                    checkPositions();
                });
            });

            describe("widget sizing", function() {
                it("should not cause an error if the view is not rendered", function() {
                    makeGrid(undefined, undefined, {
                        renderTo: null
                    });
                    expect(function() {
                        colRef[0].setWidth(400);
                    }).not.toThrow();
                });

                it("should not cause an error if the store is empty", function() {
                    expect(function() {
                        makeGrid(undefined, []);
                    }).not.toThrow();
                });

                it("should not cause an error if there are no records in the view", function() {
                    makeGrid();
                    store.removeAll();
                    expect(function() {
                        colRef[0].setWidth(400);
                    }).not.toThrow();
                });

                it("should not modify the width if the widget is configured with a width", function() {
                    makeGrid([getColCfg({
                        xtype: 'button',
                        width: 50
                    })]);
                    colRef = grid.getColumnManager().getColumns();
                    expect(getWidget(0).getWidth()).toBe(50);
                    expect(getWidget(1).getWidth()).toBe(50);
                    expect(getWidget(2).getWidth()).toBe(50);
                    expect(getWidget(3).getWidth()).toBe(50);
                });

                it("should set the width to the column size minus the padding by on initial render", function() {
                    makeGrid();

                    var padding = getPadding();

                    expect(getWidget(0).getWidth()).toBe(200 - padding);
                    expect(getWidget(1).getWidth()).toBe(200 - padding);
                    expect(getWidget(2).getWidth()).toBe(200 - padding);
                    expect(getWidget(3).getWidth()).toBe(200 - padding);
                });

                it("should modify the widget size dynamically", function() {
                    makeGrid();

                    var padding = getPadding();

                    colRef[0].setWidth(400);

                    expect(getWidget(0).getWidth()).toBe(400 - padding);
                    expect(getWidget(1).getWidth()).toBe(400 - padding);
                    expect(getWidget(2).getWidth()).toBe(400 - padding);
                    expect(getWidget(3).getWidth()).toBe(400 - padding);

                });

                it("should modify the size with a flexed column", function() {
                    var col = getColCfg({
                        xtype: 'button'
                    });

                    delete col.width;
                    col.flex = 1;
                    makeGrid([col]);

                    var padding = getPadding();

                    expect(getWidget(0).getWidth()).toBe(1000 - padding);
                    expect(getWidget(1).getWidth()).toBe(1000 - padding);
                    expect(getWidget(2).getWidth()).toBe(1000 - padding);
                    expect(getWidget(3).getWidth()).toBe(1000 - padding);

                    grid.setWidth(600);

                    expect(getWidget(0).getWidth()).toBe(600 - padding);
                    expect(getWidget(1).getWidth()).toBe(600 - padding);
                    expect(getWidget(2).getWidth()).toBe(600 - padding);
                    expect(getWidget(3).getWidth()).toBe(600 - padding);
                });

                it("should run layouts on components initially and when they are sized", function() {
                    var col = getColCfg({
                            xtype: 'container',
                            layout: 'hbox',
                            defaultType: 'component',
                            items: [{
                                flex: 1,
                                html: 'A'
                            }, {
                                flex: 1,
                                html: 'B'
                            }]
                        }),
                        widget;

                    delete col.dataIndex;

                    makeGrid([col], generateData(2));

                    var padding = getPadding(),
                        availWidth = 200 - padding;

                    function expectWidthAndLayout(c, width, counter) {
                        expect(c.getWidth()).toBe(width);
                        expect(c.componentLayoutCounter).toBe(counter);
                    }

                    widget = getWidget(0);
                    expectWidthAndLayout(widget, availWidth, 1);
                    expectWidthAndLayout(widget.items.getAt(0), availWidth / 2, 1);
                    expectWidthAndLayout(widget.items.getAt(1), availWidth / 2, 1);

                    widget = getWidget(1);
                    expectWidthAndLayout(widget, availWidth, 1);
                    expectWidthAndLayout(widget.items.getAt(0), availWidth / 2, 1);
                    expectWidthAndLayout(widget.items.getAt(1), availWidth / 2, 1);

                    colRef[0].setWidth(400);

                    availWidth = 400 - padding;

                    widget = getWidget(0);
                    expectWidthAndLayout(widget, availWidth, 2);
                    expectWidthAndLayout(widget.items.getAt(0), availWidth / 2, 2);
                    expectWidthAndLayout(widget.items.getAt(1), availWidth / 2, 2);

                    widget = getWidget(1);
                    expectWidthAndLayout(widget, availWidth, 2);
                    expectWidthAndLayout(widget.items.getAt(0), availWidth / 2, 2);
                    expectWidthAndLayout(widget.items.getAt(1), availWidth / 2, 2);
                });

                it("should run layouts when the grid has a pending layout", function() {
                    var col = getColCfg({
                            xtype: 'component'
                        }),
                        widget, count;

                    makeGrid([col], generateData(2));

                    widget = getWidget(0);
                    count = widget.componentLayoutCounter;
                    Ext.suspendLayouts();
                    grid.setWidth(grid.getWidth() + 100);
                    colRef[0].setWidth(400);
                    Ext.resumeLayouts(true);
                    expect(widget.componentLayoutCounter).toBe(count + 1);
                });
            });

            describe("store modifications", function() {
                describe("before render", function() {
                    beforeEach(function() {
                        makeGrid(undefined, undefined, {
                            renderTo: null
                        });
                    });

                    it("should not cause an error when adding records", function() {
                        expect(function() {
                            store.add({});
                        }).not.toThrow();
                    });

                    it("should not cause an error when removing items", function() {
                        expect(function() {
                            store.removeAt(0);
                        }).not.toThrow();
                    });

                    it("should not cause an error when updating items", function() {
                        expect(function() {
                            store.first().set('a', 'X');
                        }).not.toThrow();
                    });

                    it("should not cause an error when clearing the store", function() {
                        expect(function() {
                            store.removeAll();
                        }).not.toThrow();
                    });
                });

                describe("after render", function() {
                    beforeEach(function() {
                        makeGrid();
                    });

                    it("should add a new widget when adding a record", function() {
                        store.add({
                            a: 'New'
                        });
                        expect(getWidget(4).getText()).toBe('New');
                        checkPositions();
                    });

                    it("should remove the widget when removing a record", function() {
                        store.removeAt(3);
                        expect(getWidget(3)).toBeNull();
                        checkPositions();
                    });

                    it("should update the defaultBindProperty when changing a value", function() {
                        store.first().set('a', 'NewValue');

                        expect(getWidget(0).getText()).toBe('NewValue');
                        checkPositions();
                    });

                    it("should add the cell dirty class when changing a value, and remove it when reverting that change", function() {
                        var oldValue = store.first().get('a');

                        store.first().set('a', 'NewValue');

                        // Dirty class should be added to the cell
                        expect(view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true)).toHaveCls(view.dirtyCls);

                        store.first().set('a', oldValue);

                        // Dirty class should be removed from the cell
                        expect(view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true)).not.toHaveCls(view.dirtyCls);
                    });

                    it("should render with a cell dirty class set if the record is already modified", function() {
                        // The beforeEach one cannot be used.
                        grid.destroy();

                        makeGrid(null, null, {
                            renderTo: null
                        });
                        store.first().set('a', 'NewValue');
                        grid.render(document.body);

                        // Dirty class should be rendered into the cell
                        expect(view.getCellByPosition({
                            row: 0,
                            column: 0
                        }, true)).toHaveCls(view.dirtyCls);
                    });

                    it("should remove all widgets when calling removeAll", function() {
                        store.removeAll();
                        expect(getWidget(0)).toBeNull();
                        checkPositions();
                    });
                });
            });

            describe("widget decoration", function() {
                it("should add a method to get the column from the widget", function() {
                    makeGrid();
                    expect(getWidget(0).getWidgetColumn()).toBe(colRef[0]);
                    expect(getWidget(1).getWidgetColumn()).toBe(colRef[0]);
                    expect(getWidget(2).getWidgetColumn()).toBe(colRef[0]);
                    expect(getWidget(3).getWidgetColumn()).toBe(colRef[0]);
                });

                it("should add a method to get the record from the widget", function() {
                    makeGrid();
                    expect(getWidget(0).getWidgetRecord()).toBe(store.getAt(0));
                    expect(getWidget(1).getWidgetRecord()).toBe(store.getAt(1));
                    expect(getWidget(2).getWidgetRecord()).toBe(store.getAt(2));
                    expect(getWidget(3).getWidgetRecord()).toBe(store.getAt(3));
                });

                it("should have the record/column available before the bind is called", function() {
                    var fooRec, barRec, col;

                    Ext.define('spec.Button', {
                        extend: 'Ext.button.Button',
                        alias: 'widget.specbutton',

                        updateText: function(text) {
                            col = this.getWidgetColumn();

                            if (text === 'foo') {
                                fooRec = this.getWidgetRecord();
                            }
                            else if (text === 'bar') {
                                barRec = this.getWidgetRecord();
                            }

                            this.callParent(arguments);
                        }
                    });

                    makeGrid([getColCfg({
                        xtype: 'specbutton'
                    })], []);

                    store.suspendEvents();
                    store.add({
                        a: 'foo'
                    });
                    store.resumeEvents();
                    grid.getView().refresh();

                    store.add({
                        a: 'bar'
                    });

                    expect(col).toBe(colRef[0]);
                    expect(fooRec).toBe(store.getAt(0));
                    expect(barRec).toBe(store.getAt(1));

                    Ext.undefine('spec.Button');
                });

                describe("getWidgetRecord", function() {
                    it("should have the correct reference when an update causes the view to change", function() {
                        makeGrid();
                        store.getSorters().add({
                            property: 'a'
                        });
                        store.first().set('a', '5a');
                        expect(getWidget(0).getWidgetRecord().getId()).toBe('rec2');
                        expect(getWidget(1).getWidgetRecord().getId()).toBe('rec3');
                        expect(getWidget(2).getWidgetRecord().getId()).toBe('rec4');
                        expect(getWidget(3).getWidgetRecord().getId()).toBe('rec1');
                    });

                    it("should have the correct references when removing records", function() {
                        makeGrid();
                        store.removeAt(1);
                        expect(getWidget(0).getWidgetRecord().getId()).toBe('rec1');
                        expect(getWidget(1).getWidgetRecord().getId()).toBe('rec3');
                        expect(getWidget(2).getWidgetRecord().getId()).toBe('rec4');
                    });

                    it("should have the correct references when adding records", function() {
                        makeGrid();
                        store.insert(1, [{}, {}, {}]);
                        expect(getWidget(0).getWidgetRecord().getId()).toBe('rec1');
                        expect(getWidget(4).getWidgetRecord().getId()).toBe('rec2');
                        expect(getWidget(5).getWidgetRecord().getId()).toBe('rec3');
                        expect(getWidget(6).getWidgetRecord().getId()).toBe('rec4');
                    });
                });
            });

            describe("reconfigure", function() {
                it("should be able to reconfigure with adding a column", function() {
                    makeGrid([]);
                    grid.reconfigure(undefined, [getColCfg({
                        xtype: 'button'
                    })]);
                    colRef = grid.getColumnManager().getColumns();
                    expect(getWidget(0).getText()).toBe('1a');
                    expect(getWidget(1).getText()).toBe('2a');
                    expect(getWidget(2).getText()).toBe('3a');
                    expect(getWidget(3).getText()).toBe('4a');
                    checkPositions();
                });

                it("should be able to reconfigure with removing a column", function() {
                    makeGrid();
                    expect(function() {
                        grid.reconfigure(undefined, [{
                            dataIndex: 'a'
                        }]);
                    }).not.toThrow();
                });
            });

            describe("destroy", function() {
                it("should destroy components", function() {
                    makeGrid();
                    var count = Ext.ComponentManager.getCount(),
                        toDestroy = 1 + store.getCount();

                    grid.headerCt.remove(colRef[0]);
                    // We're destroying the column + each widget
                    expect(Ext.ComponentManager.getCount()).toBe(count - toDestroy);
                });
            });

            describe("on refresh", function() {
                describe("beforerefresh", function() {
                    it("should recycle the widget dom tree hierarchy when refreshed", function() {
                        // See EXTJS-14874.
                        // We need the view to overflow to cause the bug in IE 8.
                        var data = generateData(100),
                            childNodes;

                        makeGrid(null, data, {
                            height: 100
                        });

                        // Pick a row that is within the buffere rendered range
                        childNodes = getWidget(1).el.dom.childNodes;

                        expect(childNodes.length).toBe(1);

                        // This will refresh the view and trigger the bug.
                        grid.store.loadData(data);
                        expect(childNodes.length).toBe(1);
                    });
                });

                it("should be rendered after calling view refreshNode", function() {
                    makeGrid(null, 1, {
                        height: 200
                    });

                    grid.getView().refreshNode(0);

                    expect(Ext.fly(grid.getView().getRow(0)).down('.x-btn')).not.toBeNull();
                });
            });

            describe("item removal", function() {
                it("should recycle dom nodes when items are removed", function() {
                    var data = generateData(100),
                        childNodes;

                    makeGrid(null, data, {
                        height: 100
                    });

                        // Pick a row that is within the buffere rendered range
                    childNodes = getWidget(1).el.dom.childNodes;

                    expect(childNodes.length).toBe(1);

                    store.removeAt(1);

                    expect(childNodes.length).toBe(1);
                });
            });

            describe("hide/show", function() {
                it("should not create widgets initially when hidden", function() {
                    var count = 0;

                    Ext.define('spec.Foo', {
                        extend: 'Ext.Component',
                        alias: 'widget.foo',

                        initComponent: function() {
                            ++count;
                            this.callParent();
                        }
                    });

                    var col = getColCfg({
                        xtype: 'foo'
                    });

                    col.hidden = true;
                    makeGrid([col]);
                    // Gets called once during construction to set the tdCls
                    expect(count).toBe(1);
                    Ext.undefine('spec.Foo');
                });

                it("should size the widgets when hidden initially and then shown", function() {
                    var col = getColCfg({
                        xtype: 'button'
                    });

                    col.hidden = true;
                    makeGrid([col]);
                    colRef[0].show();
                    var padding = getPadding();

                    expect(getWidget(0).getWidth()).toBe(200 - padding);
                    expect(getWidget(1).getWidth()).toBe(200 - padding);
                    expect(getWidget(2).getWidth()).toBe(200 - padding);
                    expect(getWidget(3).getWidth()).toBe(200 - padding);
                });

                it("should not cause an error when hiding the last leaf column in a grouped header", function() {
                    makeGrid([{
                        columns: [getColCfg({
                            xtype: 'button'
                        })]
                    }]);
                    expect(function() {
                        colRef[0].hide();
                    }).not.toThrow();
                });

                it("should not cause an error when hiding the group header that contains this widget", function() {
                    makeGrid([{
                        itemId: 'ct',
                        columns: [getColCfg({
                            xtype: 'button'
                        })]
                    }]);
                    expect(function() {
                        grid.down('#ct').hide();
                    }).not.toThrow();
                });
            });

            describe("RadioGroup as a widget", function() {
                it("should be able to update value from column's dataIndex", function() {
                    var changed = false,
                        widget;

                    createGrid([getColCfg({
                        xtype: 'radiogroup',
                        // The local config means child Radio names are scoped to this RadioGroup
                        local: true,
                        items: [{
                            name: 'value',
                            inputValue: '1'
                        }, {
                            name: 'value',
                            inputValue: '2'
                        }]
                    })], [{
                        a: {
                            value: '2'
                        }
                    }]);

                    widget = getWidget(0);
                    widget.on({
                        change: {
                            fn: function(radioGroup, newValue) {
                                radioGroup.getWidgetRecord().set('a', newValue);
                                changed = true;
                            }
                        }
                    });

                    if (Ext.isIE9m) {
                        // jasmine fireMouseEvent doesn't work properly to simulate clicks on a radion button on legacy browsers
                        widget.items.first().setValue(true);
                    }
                    else {
                        jasmine.fireMouseEvent(widget.items.first().inputEl.el, 'click');
                    }

                    waitsFor(function() {
                        return changed;
                    });

                    runs(function() {
                        expect(store.first().get('a').value).toBe('1');
                    });
                });

                it("should be able to sort a column", function() {
                    createGrid([getColCfg({
                        xtype: 'radiogroup',
                        // The local config means child Radio names are scoped to this RadioGroup
                        local: true,
                        items: [{
                            name: 'value',
                            inputValue: '1'
                        }, {
                            name: 'value',
                            inputValue: '2'
                        }],
                        listeners: {
                            change: function(radioGroup, newValue) {
                                radioGroup.getWidgetRecord().set('a', newValue);
                            }
                        }
                    }), {
                        text: 'Name',
                        dataIndex: 'b',
                        sortable: true
                    }], [{
                        a: {
                            value: '2'
                        },
                        b: 'Vince'
                    }, {
                        a: {
                            value: '1'
                        },
                        b: 'John'
                    }]);
                    store.sort('b', 'ASC');
                    expect(getWidget(0).items.first().checked).toBe(true);
                });
            });

            describe("Combobox as a widget", function() {
                it("should be able to expand and collapse the field by clicking on the trigger", function() {
                    createGrid([
                        getColCfg({
                            xtype: 'combobox',
                            queryMode: 'local',
                            displayField: 'a',
                            valueField: 'b'
                        })
                    ]);

                    getWidget(0).bindStore(store);
                    jasmine.fireMouseEvent(getWidget(0).triggers.picker.el, 'click');
                    expect(getWidget(0).isExpanded).toBe(true);
                    jasmine.fireMouseEvent(getWidget(0).triggers.picker.el, 'click');
                    expect(getWidget(0).isExpanded).toBe(false);
                });
            });

            describe("Button as a widget", function() {
                it("should be able to show and hide a button menu", function() {
                    createGrid([
                        getColCfg({
                            xtype: 'button',
                            menu: [{
                                text: 'Button1'
                            }, {
                                text: 'Button2'
                            }]
                        })
                    ]);

                    jasmine.fireMouseEvent(getWidget(0).focusEl, 'click');
                    expect(getWidget(0).menu.isVisible()).toBe(true);

                    // NavigationModel does not enter actionable mode for touches.
                    if (!jasmine.supportsTouch) {
                        expect(grid.actionableMode).toBe(true);
                    }

                    jasmine.fireMouseEvent(getWidget(0).focusEl, 'click');
                    expect(getWidget(0).menu.isVisible()).toBe(false);

                    jasmine.fireMouseEvent(view.getCellByPosition({
                        row: 0,
                        column: 0
                    }, true), 'click');

                    // Should focus the cell and exit actionable mode.
                    // Some browsers fire async focus events, so wait for it.
                    // NavigationModel does not enter actionable mode for touches.
                    if (!jasmine.supportsTouch) {
                        waitsFor(function() {
                            return grid.actionableMode === false;
                        });
                    }
                });

                describe("in a locked grid", function() {
                    var normalGrid, lockedGrid, firstNormalRow, firstLockedRow;

                    afterEach(function() {
                        normalGrid = lockedGrid = firstNormalRow = firstLockedRow = null;
                    });

                    it("should keep line heights synced after sorting", function() {

                        createGrid(null, null, {
                            columns: [Ext.apply(getColCfg({
                                xtype: 'button',
                                height: 40
                            }), {
                                locked: true
                            }), {
                                dataIndex: 'a'
                            }]
                        });

                        normalGrid = grid.normalGrid;
                        lockedGrid = grid.lockedGrid;

                        normalGrid.getColumnManager().getColumns()[0].sort();

                        firstNormalRow = normalGrid.getView().getRow(0);
                        firstLockedRow = lockedGrid.getView().getRow(0);

                        expect(Ext.fly(firstNormalRow).getHeight()).toBe(Ext.fly(firstLockedRow).getHeight());

                    });
                });
            });

            describe("in a tabpanel", function() {
                var panel;

                beforeEach(function() {
                    createGrid([{
                        xtype: 'widgetcolumn',
                        width: 200,
                        dataIndex: 'd',
                        widget: {
                            xtype: 'progressbarwidget',
                            textTpl: '{value:percent}'
                        }
                    }], null, {
                        renderTo: null
                    });

                    panel = new Ext.tab.Panel({
                        width: 800,
                        height: 300,

                        items: [
                            grid, {
                                xtype: 'panel',
                                title: 'TAB2'
                            }
                        ],
                        renderTo: document.body
                    });
                });

                afterEach(function() {
                    panel.destroy();
                    panel = null;
                });

                it("should display the widget if it's record was added while the grid was in an inactive panel", function() {
                    panel.setActiveTab(1);
                    store.add({ id: 'rec5', a: '5a',  b: '5b', c: '5c', d: 0.5 });
                    panel.setActiveTab(0);

                    expect(getWidget(4, 0).textEl.dom.innerHTML).toBe('50%');
                });
            });

            describe("with locking", function() {
                it("should be able to add a widget as part of a header group on the locked side", function() {
                    createGrid([{
                        text: 'Foo',
                        locked: true,
                        columns: [getColCfg({
                            xtype: 'button'
                        })]
                    }, {
                        text: 'Bar',
                        dataIndex: 'b'
                    }]);
                    expect(getWidget(0).getText()).toBe('1a');
                });

                it("should be able to add a widget as part of a header group on the unlocked side", function() {
                    createGrid([{
                        text: 'Foo',
                        locked: true,
                        dataIndex: 'b'
                    }, {
                        text: 'Bar',
                        columns: [getColCfg({
                            xtype: 'button'
                        })]
                    }]);
                    expect(getWidget(0, colRef[1]).getText()).toBe('1a');
                });
            });
        });
    }

    createBufferedSuite(false);
    createBufferedSuite(true);
});
