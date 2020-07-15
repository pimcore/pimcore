/* eslint-disable one-var, vars-on-top, max-len */

topSuite("grid-grouping", [
    false, 'Ext.grid.Panel', 'Ext.grid.feature.GroupingSummary', 'Ext.data.BufferedStore'
], function() {
    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var grid, view, store, dataSource, grouping, colRef,
                GridGroupModel = Ext.define(null, {
                    extend: 'Ext.data.Model',
                    fields: [
                        'name',
                        'type'
                    ]
                });

            function spyOnEvent(object, eventName, fn) {
                var obj = {
                        fn: fn || Ext.emptyFn
                    },
                    spy = spyOn(obj, "fn");

                object.addListener(eventName, obj.fn);

                return spy;
            }

            function getCellText(row, col) {
                var cell = view.getCell(store.getAt(row), colRef[col]),
                    selectorView = grid.lockedGrid ? grid.lockedGrid.getView() : view;

                return cell.querySelector(selectorView.innerSelector).innerHTML;
            }

            function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireMouseEvent(target, type, x, y, button);
            }

            function triggerCellKeyEvent(rowIdx, cellIdx, type, key, altKey) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireKeyEvent(target, type, key, null, null, altKey);
            }

            function triggerHeaderClick(key, theView) {
                var target = findHeader(key, theView);

                jasmine.fireMouseEvent(target, 'click');
            }

            function findHeader(key, theView) {
                theView = theView || view;

                var feature = (theView.normalView || theView).features[0];

                return feature.getHeaderNode(key);
            }

            function findCell(rowIdx, cellIdx) {
                return grid.getView().getCellInclusive({
                    row: rowIdx,
                    column: cellIdx
                }, true);
            }

            function getRec(index) {
                return store.getAt(index);
            }

            function manyGroups() {
                var data = [],
                    i, n;

                for (i = 0; i < 300; ++i) {
                    n = i + 1;
                    data.push({
                        id: n,
                        name: 'Item' + n,
                        type: 'group' + Ext.String.leftPad(Math.ceil(n / 3), 3, '0')
                    });
                }

                return data;
            }

            function makeGrid(noGroup, gridCfg, numRows, withSummary, groupCfg) {
                var i = 0,
                    data = [],
                    storeConfig;

                if (Ext.isArray(numRows)) {
                    data = numRows;
                }
                else {
                    if (typeof numRows !== 'number') {
                        numRows = 100;
                    }

                    for (; i < numRows; i++) {
                        data.push({
                            id: i + 1,
                            type: 't' + (Math.floor(i / 25) + 1),
                            name: 'Item ' + (i + 1)
                        });
                    }
                }

                storeConfig = {
                    model: GridGroupModel,
                    data: data
                };

                if (!noGroup) {
                    storeConfig.groupField = 'type';
                }

                store = new Ext.data.Store(storeConfig);

                grouping = withSummary ? new Ext.grid.feature.GroupingSummary(groupCfg) : new Ext.grid.feature.Grouping(groupCfg);
                grid = new Ext.grid.Panel(Ext.apply({
                    columns: [{
                        dataIndex: 'name'
                    }],
                    trailingBufferZone: 1000,
                    leadingBufferZone: 1000,
                    store: store,
                    features: [grouping],
                    width: 1000,
                    height: 500,
                    bufferedRenderer: buffered,
                    viewConfig: {
                        mouseOverOutBuffer: false,
                        deferHighlight: false
                    },
                    renderTo: Ext.getBody()
                }, gridCfg));

                view = grid.getView();
                dataSource = view.dataSource;
                colRef = grid.getColumnManager().getColumns();
            }

            function makeBufferedStoreGrid(storeCfg, gridCfg, numRows) {
                var i = 0,
                    data = [],
                    storeConfig;

                if (typeof numRows !== 'number') {
                    numRows = 100;
                }

                for (; i < numRows; i++) {
                    data.push({
                        id: i + 1,
                        type: 't' + (Math.floor(i / 25) + 1),
                        name: 'Item ' + (i + 1)
                    });
                }

                storeConfig = Ext.apply({
                    model: GridGroupModel,
                    groupField: 'type',
                    proxy: {
                        type: 'memory',
                        data: data
                    },
                    autoLoad: true
                }, storeCfg);
                store = new Ext.data.BufferedStore(storeConfig);

                grouping = new Ext.grid.feature.Grouping();

                grid = new Ext.grid.Panel(Ext.apply({
                    columns: [{
                        dataIndex: 'name'
                    }],
                    store: store,
                    features: [grouping],
                    width: 1000,
                    height: 500,
                    viewConfig: {
                        mouseOverOutBuffer: false,
                        deferHighlight: false
                    },
                    renderTo: Ext.getBody()
                }, gridCfg));
                view = grid.getView();
                colRef = grid.getColumnManager().getColumns();
            }

            afterEach(function() {
                colRef = grouping = grid = store = view = Ext.destroy(grid, store);
            });

            describe("basic functionality", function() {
                beforeEach(function() {
                    makeGrid();
                });

                it("should start with all groups expanded", function() {
                    expect(view.all.getCount()).toBe(store.getCount());

                    // Collapse all four groups
                    triggerHeaderClick('t1');
                    triggerHeaderClick('t2');
                    triggerHeaderClick('t3');
                    triggerHeaderClick('t4');

                    // There will only be 4 items, one for each collapsed group placeholder
                    expect(view.all.getCount()).toBe(grid.store.getGroups().length);
                });

                it("should collapse the header on clicking", function() {
                    triggerHeaderClick('t1');
                    expect(grouping.isExpanded('t1')).toBe(false);
                });

                it("should NOT fire itemupdate for records in collapsed groups", function() {
                    var spy = spyOnEvent(grid.view, "itemupdate").andCallThrough();

                    triggerHeaderClick('t1');
                    grid.view.refreshNode(1);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire itemupdate event with recordIndex", function() {
                    var spy = spyOnEvent(view, "itemupdate").andCallThrough(),
                        rec25 = view.store.getAt(25),
                        groupStoreIndex = view.dataSource.indexOf(rec25);

                    grid.view.refreshNode(25);
                    expect(spy).toHaveBeenCalledWith(rec25, 25, view.all.item(groupStoreIndex).dom, view);
                });

                it("should fire itemupdate event with recordIndex when group above is collapsed", function() {
                    var spy = spyOnEvent(view, "itemupdate").andCallThrough(),
                        rec25 = view.store.getAt(25),
                        groupStoreIndex;

                    triggerHeaderClick('t1');
                    groupStoreIndex = view.dataSource.indexOf(rec25);
                    grid.view.refreshNode(25);
                    expect(spy).toHaveBeenCalledWith(rec25, 25, view.all.item(groupStoreIndex).dom, view);
                });

                it("should expand a collapsed header on clicking", function() {
                    triggerHeaderClick('t1');
                    triggerHeaderClick('t1');
                    expect(grouping.isExpanded('t1')).toBe(true);
                });

                it("should allow selection after collapsing", function() {
                    triggerHeaderClick('t1');
                    triggerCellMouseEvent('click', 1, 0);
                    expect(grid.getSelectionModel().isSelected(getRec(25))).toBe(true);
                });

                it("should restore selection after collapsing and expanding", function() {
                    var row;

                    // Select record 0
                    triggerCellMouseEvent('click', 0, 0);
                    triggerHeaderClick('t1');

                    // First record is not in the view due to collapse of first group
                    expect(view.getNode(getRec(0)) == null).toBe(true);

                    // Expand first group again and record 0 must still be selected
                    triggerHeaderClick('t1');
                    expect(grid.getSelectionModel().isSelected(getRec(0))).toBe(true);

                    // Get outer, wrapping row. It must carry the selected class
                    row = view.getNode(0);
                    expect(Ext.fly(row).hasCls(view.selectedItemCls)).toBe(true);

                    // Get inner, data row. It must NOT carry the selected class
                    row = view.getRow(0);
                    expect(Ext.fly(row).hasCls(view.selectedItemCls)).toBe(false);
                });

                describe("events", function() {
                    // Don't test this for buffered rendering, it makes decisions on what to render
                    // (or replace the whole) in a manner that's difficult to predict via testing
                    if (!buffered) {
                        it("should fire itemremove for each item on collapse", function() {
                            var spy = jasmine.createSpy(),
                                view = grid.getView(),
                                items, i, records;

                            view.on('itemremove', spy);

                            items = view.getNodes(0, 24);
                            records = store.getRange(0, 24);

                            triggerHeaderClick('t1');

                            expect(spy.callCount).toBe(1);

                            for (i = 0; i < 25; i++) {
                                expect(spy.calls[0].args[0][i]).toBe(records[i]);
                                expect(spy.calls[0].args[1]).toBe(0);
                                expect(spy.calls[0].args[2][i]).toBe(items[i]);
                                expect(spy.calls[0].args[3]).toBe(view);
                            }

                            triggerHeaderClick('t1');
                            spy.reset();

                            items = view.getNodes(75, 99);
                            records = store.getRange(75, 99);

                            triggerHeaderClick('t4');

                            expect(spy.callCount).toBe(1);

                            for (i = 0; i < 25; i++) {
                                expect(spy.calls[0].args[0][i]).toBe(records[i]);
                                expect(spy.calls[0].args[1]).toBe(75);
                                expect(spy.calls[0].args[2][i]).toBe(items[i]);
                                expect(spy.calls[0].args[3]).toBe(view);
                            }
                        });
                    }
                });

                describe("store events", function() {
                    it("should fire the store remove event on remove", function() {
                        var spy = jasmine.createSpy();

                        store.on('remove', spy);
                        store.removeAt(0);
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire the store add event on add", function() {
                        var spy = jasmine.createSpy();

                        store.on('add', spy);
                        store.add({
                            group: 't1',
                            name: 'Foo'
                        });
                        expect(spy.callCount).toBe(1);
                    });
                });
            });

            describe("groups with html characters", function() {
                function runSuite(g1, g2) {
                    makeGrid(false, null, [{
                        id: 1,
                        name: 'Item1',
                        type: g1
                    }, {
                        id: 2,
                        name: 'Item2',
                        type: g1
                    }, {
                        id: 3,
                        name: 'Item3',
                        type: g2
                    }, {
                        id: 4,
                        name: 'Item4',
                        type: g2
                    }]);

                    grouping.collapse(g1);
                    grouping.collapse(g2);

                    expect(grouping.isExpanded(g1)).toBe(false);
                    expect(grouping.isExpanded(g2)).toBe(false);

                    expect(view.el.select('.x-grid-group-hd').getCount()).toBe(2);
                    expect(view.getNodes().length).toBe(2);

                    grouping.expand(g1);
                    grouping.expand(g2);

                    expect(grouping.isExpanded(g1)).toBe(true);
                    expect(grouping.isExpanded(g2)).toBe(true);

                    expect(view.el.select('.x-grid-group-hd').getCount()).toBe(2);
                    expect(view.getNodes().length).toBe(4);

                    triggerHeaderClick(g1);
                    triggerHeaderClick(g2);

                    expect(grouping.isExpanded(g1)).toBe(false);
                    expect(grouping.isExpanded(g2)).toBe(false);

                    expect(view.el.select('.x-grid-group-hd').getCount()).toBe(2);
                    expect(view.getNodes().length).toBe(2);

                    triggerHeaderClick(g1);
                    triggerHeaderClick(g2);

                    expect(grouping.isExpanded(g1)).toBe(true);
                    expect(grouping.isExpanded(g2)).toBe(true);

                    expect(view.el.select('.x-grid-group-hd').getCount()).toBe(2);
                    expect(view.getNodes().length).toBe(4);
                }

                it("should be able to expand/collapse a group with html characters", function() {
                    runSuite('Bar&Name', 'Foo&Name');
                });

                it("should be able to expand/collapse a group with already encoded html characters", function() {
                    runSuite('Bar&amp;Name', 'Foo&amp;Name');
                });
            });

            describe("expand/collapse", function() {
                describe("expand", function() {
                    describe("basic functionality", function() {
                        it("should work when scrollable is false", function() {
                            makeGrid(null, {
                                height: 400,
                                scrollable: false
                            }, manyGroups(), false, {
                                startCollapsed: true
                            });

                            expect(function() {
                                grouping.expand('group001');
                            }).not.toThrow();
                        });
                    });

                    describe("with focus: true", function() {
                        describe("with the group in view", function() {
                            it("should expand the group", function() {
                                makeGrid(null, {
                                    height: 400
                                }, manyGroups(), false, {
                                    startCollapsed: true
                                });
                                waitsFor(function(done) {
                                    grouping.expand('group001', {
                                        callback: done
                                    });
                                    expect(grouping.isExpanded('group001')).toBe(true);
                                });
                                runs(function() {
                                    expect(grid.getView().getScrollable().getPosition()).toEqual({
                                        x: 0,
                                        y: 0
                                    });
                                });
                            });
                        });

                        describe("with the group not in the view", function() {
                            it("should expand the group & scroll to the first record", function() {
                                makeGrid(null, {
                                    height: 400,
                                    trailingBufferZone: 20,
                                    leadingBufferZone: 20
                                }, manyGroups(), false, {
                                    startCollapsed: true
                                });
                                waitsFor(function(done) {
                                    grouping.expand('group100', {
                                        callback: done
                                    });
                                    expect(grouping.isExpanded('group100')).toBe(true);
                                });
                                runs(function() {
                                    // Although already implied by the wait above, let's just be explicit
                                    // anyway, seems weird to have the test end on a waitsFor
                                    var node = view.getNodeByRecord(store.getById(298));

                                    expect(view.getScrollable().isInView(node).y).toBe(true);
                                });
                            });
                        });
                    });
                });

                describe("collapse", function() {
                    describe("basic functionality", function() {
                        it("should work when scrollable is false", function() {
                            makeGrid(null, {
                                height: 400,
                                scrollable: false
                            }, manyGroups());

                            expect(function() {
                                grouping.collapse('group001');
                            }).not.toThrow();
                        });
                    });

                    describe("with focus: true", function() {
                        describe("with the group in view", function() {
                            it("should collapse the group", function() {
                                makeGrid(null, {
                                    height: 400
                                }, manyGroups());
                                waitsFor(function(done) {
                                    grouping.collapse('group001', {
                                        callback: done
                                    });
                                    expect(grouping.isExpanded('group001')).toBe(false);
                                });
                                runs(function() {
                                    expect(grid.getView().getScrollable().getPosition()).toEqual({
                                        x: 0,
                                        y: 0
                                    });
                                });
                            });
                        });

                        describe("with the group not in the view", function() {
                            it("should collapse the group & scroll to the placeholder", function() {
                                makeGrid(null, {
                                    height: 400,
                                    trailingBufferZone: 20,
                                    leadingBufferZone: 20
                                }, manyGroups());
                                waitsFor(function(done) {
                                    grouping.collapse('group100', {
                                        callback: done
                                    });
                                    expect(grouping.isExpanded('group100')).toBe(false);
                                });
                                runs(function() {
                                    // Although already implied by the wait above, let's just be explicit
                                    // anyway, seems weird to have the test end on a waitsFor
                                    var node = grouping.getHeaderNode('group100');

                                    expect(view.getScrollable().isInView(node).y).toBe(true);
                                });
                            });
                        });
                    });
                });
            });

            describe("moving columns", function() {
                function dragColumn(from, to, onRight, locked) {
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

                    if (locked) {
                        // Locked grids need an additional mousemove because the drop won't be valid if the target headerCt isn't the same as
                        // the target headerCt of the last mousemove event. So, we need to hack around this by firing an additional event so
                        // the two mouseevents can be seen as having the same target headerCt.
                        jasmine.fireMouseEvent(to.el.dom, 'mousemove', (onRight ? toMx + 1 : toMx - 1), toMy);
                    }

                    // The move to left of the centre of the target element
                    jasmine.fireMouseEvent(to.el.dom, 'mousemove', toMx, toMy);

                    // Drop to left of centre of target element
                    jasmine.fireMouseEvent(to.el.dom, 'mouseup', toMx, toMy);
                }

                // https://sencha.jira.com/browse/EXTJS-18781
                // Cells with preventUpdate caused an error.
                describe('with collapsed group', function() {
                    it('should move columns with a collapsed group without throwing an error', function() {
                        makeGrid(null, {
                            columns: [{
                                text: 'A',
                                dataIndex: 'name',
                                preventUpdate: true
                            }, {
                                text: 'B',
                                dataIndex: 'name'
                            }, {
                                text: 'C',
                                dataIndex: 'name'
                            }],
                            height: 400,
                            trailingBufferZone: 20,
                            leadingBufferZone: 20
                        }, manyGroups(), true);
                        var columns = grid.getColumnManager().getColumns();

                        grouping.collapse('group001', true);

                        // This should just run with no errors.
                        dragColumn(columns[0], columns[2], true);
                        dragColumn(columns[1], columns[0]);
                    });
                });

                it("should not fire an update event on the store", function() {
                    makeGrid(false, {
                        columns: [{
                            text: 'A',
                            dataIndex: 'name'
                        }, {
                            text: 'B',
                            dataIndex: 'name'
                        }, {
                            text: 'C',
                            dataIndex: 'name'
                        }]
                    });
                    var columns = grid.getColumnManager().getColumns(),
                        spy = spyOnEvent(store, 'update');

                    dragColumn(columns[2], columns[0], false);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should react to events when collapsed", function() {
                    makeGrid(false, {
                        columns: [{
                            text: 'A',
                            dataIndex: 'name'
                        }, {
                            text: 'B',
                            dataIndex: 'name'
                        }, {
                            text: 'C',
                            dataIndex: 'name'
                        }]
                    });
                    var columns = grid.getColumnManager().getColumns();

                    grouping.collapse('t1');
                    dragColumn(columns[2], columns[0], false);

                    triggerHeaderClick('t1');
                    expect(grouping.isExpanded('t1')).toBe(true);
                });

                it("should update the summary rows", function() {
                    function expectSummaryText(row, values) {
                        row = view.getNode(row).querySelector(grouping.summaryRowSelector);

                        Ext.Array.forEach(grid.getColumnManager().getColumns(), function(col, index) {
                            var text = row.querySelector(col.getCellInnerSelector()).innerHTML;

                            expect(text).toBe(values[index]);
                        });
                    }

                    makeGrid(false, {
                        columns: [{
                            text: 'A',
                            dataIndex: 'name',
                            summaryRenderer: function(val, summaryData, dataIndex, meta) {
                                return 'A' + meta.record.ownerGroup;
                            }
                        }, {
                            text: 'B',
                            dataIndex: 'name',
                            summaryRenderer: function(val, summaryData, dataIndex, meta) {
                                return 'B' + meta.record.ownerGroup;
                            }
                        }, {
                            text: 'C',
                            dataIndex: 'name',
                            summaryRenderer: function(val, summaryData, dataIndex, meta) {
                                return 'C' + meta.record.ownerGroup;
                            }
                        }]
                    }, null, true);
                    expectSummaryText(24, ['At1', 'Bt1', 'Ct1']);
                    expectSummaryText(49, ['At2', 'Bt2', 'Ct2']);
                    expectSummaryText(74, ['At3', 'Bt3', 'Ct3']);
                    expectSummaryText(99, ['At4', 'Bt4', 'Ct4']);

                    var columns = grid.getColumnManager().getColumns();

                    dragColumn(columns[2], columns[0], false);

                    expectSummaryText(24, ['Ct1', 'At1', 'Bt1']);
                    expectSummaryText(49, ['Ct2', 'At2', 'Bt2']);
                    expectSummaryText(74, ['Ct3', 'At3', 'Bt3']);
                    expectSummaryText(99, ['Ct4', 'At4', 'Bt4']);
                });
            });

            describe("enable/disable", function() {
                describe("enable", function() {
                    describe("when grouped", function() {
                        it("should show group headers", function() {
                            makeGrid();
                            grouping.disable();
                            grouping.enable();
                            expect(view.el.select('.x-grid-group-hd').getCount()).toBe(4);
                        });
                    });

                    describe("when not grouped", function() {
                        it("should not show group headers", function() {
                            makeGrid(true);
                            grouping.disable();
                            grouping.enable();
                            expect(view.el.select('.x-grid-group-hd').getCount()).toBe(0);
                        });
                    });
                });

                describe("disable", function() {
                    describe("when grouped", function() {
                        it("should not show group headers", function() {
                            makeGrid();
                            grouping.disable();
                            expect(view.el.select('.x-grid-group-hd').getCount()).toBe(0);
                        });

                        it("should show all rows when collapsed", function() {
                            makeGrid();
                            grouping.collapseAll();
                            grouping.disable();
                            expect(view.el.select('.x-grid-group-hd').getCount()).toBe(0);
                            expect(view.all.getCount()).toBe(100);
                        });
                    });

                    describe("when not grouped", function() {
                        it("should not show group headers", function() {
                            makeGrid(true);
                            grouping.disable();
                            expect(view.el.select('.x-grid-group-hd').getCount()).toBe(0);
                            expect(view.all.getCount()).toBe(100);
                        });
                    });
                });
            });

            describe("markup generation", function() {
                it("should not cause an error when generating markup with group names with special characters", function() {
                    expect(function() {
                        makeGrid(false, null, [{
                            type: 'Foo Bar *&#^$%',
                            name: 'Group 1'
                        }]);
                    }).not.toThrow();
                });
            });

            describe("column visibility", function() {
                it("should not throw an exception when hiding all columns", function() {
                    makeGrid(false, {
                        columns: [{
                            dataIndex: 'name',
                            itemId: 'col1'
                        }, {
                            dataIndex: 'name',
                            itemId: 'col2'
                        }, {
                            dataIndex: 'name',
                            itemId: 'col3'
                        }, {
                            dataIndex: 'name',
                            itemId: 'col4'
                        }, {
                            dataIndex: 'name',
                            itemId: 'col5'
                        }]
                    });
                    expect(function() {
                        grid.down('#col1').hide();
                        grid.down('#col2').hide();
                        grid.down('#col3').hide();
                        grid.down('#col4').hide();
                        grid.down('#col5').hide();
                    }).not.toThrow();
                });
            });

            if (buffered) {
                describe("basic functionality with buffered renderer", function() {
                    describe('collapseAll with locked columns', function() {
                        beforeEach(function() {
                            makeGrid(null, {
                                columns: [{
                                    dataIndex: 'type',
                                    width: 100,
                                    locked: true
                                }, {
                                    dataIndex: 'name',
                                    flex: 1
                                }]
                            });
                        });
                        it('should collapseAll with no errors', function() {

                            // Every record should be prepresented in the GroupStore
                            expect(dataSource.getCount()).toBe(store.getCount());
                            grouping.collapseAll();

                            // After collapse, just four group placeholder records in te GroupStore
                            expect(dataSource.getCount()).toBe(4);
                        });
                    });

                    describe('expanding/collapsing, selecting and scrolling', function() {
                        beforeEach(function() {
                            makeGrid(null, {
                            });
                        });

                        it("should start with all groups expanded - buffered rendering", function() {
                            var selectedRecord,
                                viewIndex,
                                selItem,
                                expanded,
                                storeCount = store.getCount(),
                                spy = jasmine.createSpy();

                            // viewSize might be larger than available records if buffer zones have been extended,
                            // so minimize the test value with the storeCount
                            expect(grid.view.all.getCount()).toBe(Math.min(grid.view.bufferedRenderer.viewSize, storeCount));
                            expanded = view.el.dom.scrollHeight;

                            // After collapsing all, the scroll range should be smaller than when all were expanded
                            grouping.collapseAll();
                            expect(view.el.dom.scrollHeight).toBeLessThan(expanded);

                            // After expanding all, the scroll range should be back *CLOSE TO* where it was before. The calculation
                            // Is based upon measured mean row height, so will not be exactly the same.
                            grouping.expandAll();
                            expect(view.el.dom.scrollHeight).toBeWithin(50, expanded);

                            // Collapse all four groups using DOM events
                            triggerHeaderClick('t1');
                            triggerHeaderClick('t2');
                            triggerHeaderClick('t3');
                            triggerHeaderClick('t4');

                            // There will only be 4 items, one for each collapsed group placeholder
                            expect(grid.view.all.getCount()).toBe(grid.store.getGroups().length);

                            view.bufferedRenderer.scrollTo(75, {
                                select: true,
                                focus: true,
                                callback: spy
                            });

                            waitsForSpy(spy, 'scroller promise to resolve');

                            runs(function() {
                                selectedRecord = grid.selModel.getSelection()[0];
                                viewIndex = view.indexOf(selectedRecord);
                                selItem = view.all.item(viewIndex);
                                // The outer <table> carries the selected class.
                                expect(selItem).toHaveCls(view.selectedItemCls);

                                // The cell carries the focus class.
                                expect(view.getCell(selItem, view.getHeaderAtIndex(0))).toHaveCls(view.focusedItemCls);
                            });

                        });

                        it("should collapse the header on clicking", function() {
                            triggerHeaderClick('t1');
                            expect(grouping.isExpanded('t1')).toBe(false);
                        });

                        it("should expand a collapsed header on clicking", function() {
                            triggerHeaderClick('t1');
                            triggerHeaderClick('t1');
                            expect(grouping.isExpanded('t1')).toBe(true);
                        });

                        it("should allow selection after collapsing", function() {
                            triggerHeaderClick('t1');
                            triggerCellMouseEvent('click', 1, 0);
                            expect(grid.getSelectionModel().isSelected(getRec(25))).toBe(true);
                        });

                        it('should scroll to the passed row', function() {
                            grouping.collapseAll();

                            // It's out of range and in a collapsed group - not there
                            var r75 = grid.view.getNode(grid.store.getAt(75));

                            expect(r75 == null).toBe(true);

                            // Tell the buffered renderer to scroll to it though
                            grid.view.bufferedRenderer.scrollTo(75, true);

                            // And we should find it.
                            r75 = grid.view.getNode(grid.store.getAt(75));
                            expect(r75 != null).toBe(true);
                        });
                    });

                    describe('grouping', function() {
                        var bufferedRenderer,
                            storeClearSpy;

                        beforeEach(function() {
                            makeGrid(true, {
                            }, 5000);

                            bufferedRenderer = grid.view.bufferedRenderer;

                            storeClearSpy = spyOnEvent(store, 'clear');

                            bufferedRenderer.scrollTo(5000);
                            grid.store.group('type');
                        });

                        afterEach(function() {
                            bufferedRenderer = null;
                        });

                        it('should scroll to the top', function() {
                            var scrollTop = grid.view.el.dom.scrollTop;

                            expect(scrollTop).toBeWithin(1, scrollTop);
                        });

                        it('should reset object properties dealing with scrolling', function() {
                            var scrollTop = bufferedRenderer.scrollTop,
                                position = bufferedRenderer.position;

                            waitsFor(function() {
                                return bufferedRenderer.bodyTop === 62517;
                            });
                            runs(function() {
                                expect(scrollTop).toBeWithin(1, scrollTop);
                                expect(position).toBeWithin(1, position);
                            });
                        });

                        it('should not clear the store on data refresh', function() {
                            expect(storeClearSpy).not.toHaveBeenCalled();
                        });
                    });
                });
            }

            describe("without grouping", function() {
                it("should render no headers", function() {
                    makeGrid(true);
                    expect(view.el.select('.x-grid-group-hd').getCount()).toBe(0);
                });

                it("should allow selection", function() {
                    makeGrid(true);
                    triggerCellMouseEvent('click', 0, 0);
                    expect(grid.getSelectionModel().isSelected(getRec(0))).toBe(true);
                });

                it("should render group when 'group by this field' is clicked and not configured with grouping", function() {
                    makeGrid(true, {
                        columns: [{
                            dataIndex: 'name'
                        }, {
                            dataIndex: 'type',
                            itemId: 'type'
                        }]
                    });
                    var column = grid.down('#type');

                    expect(view.el.select('.x-grid-group-hd').getCount()).toBe(0);

                    Ext.testHelper.showHeaderMenu(column);

                    runs(function() {
                        jasmine.fireMouseEvent(grid.headerCt.getMenu().down('#groupMenuItem').getEl(), 'click');
                        expect(view.el.select('.x-grid-group-hd').getCount()).toBe(4);
                    });
                });
            });

            describe("selection/focus", function() {
                describe("when the grid is not focused", function() {
                    it("should not focus/select when collapsing a group", function() {
                        makeGrid();
                        var view = grid.getView(),
                            t2head = view.getEl().select('.x-grid-group-hd').item(1);

                        grid.getView().scrollElIntoView(t2head);
                        waitsFor(function() {
                            return view.getEl().dom.scrollTop > 0;
                        }, "Never scrolled");
                        runs(function() {
                            triggerHeaderClick('t2');
                            expect(grid.getSelectionModel().getCount()).toBe(0);
                            expect(grid.getNavigationModel().getPosition()).toBeNull();
                        });
                    });

                    it("should not focus/select when expanding a group", function() {
                        makeGrid();
                        grouping.collapse('t2');

                        var view = grid.getView(),
                            t2head = view.getEl().select('.x-grid-group-hd').item(1);

                        grid.getView().scrollElIntoView(t2head);
                        waitsFor(function() {
                            return view.getEl().dom.scrollTop > 0;
                        }, "Never scrolled");
                        runs(function() {
                            triggerHeaderClick('t2');
                            expect(grid.getSelectionModel().getCount()).toBe(0);
                            expect(grid.getNavigationModel().getPosition()).toBeNull();
                        });
                    });
                });
            });

            describe("scrolling", function() {
                it("should not focus the selected row when collapsing a group", function() {
                    makeGrid();
                    // scroll to the bottom. Record 0 must not be focused, otherwise it will
                    // scroll into view upon a refresh caused by the collapse;
                    grid.getSelectionModel().select(0);
                    grid.scrollByDeltaY(2000);

                    waitsFor(function() {
                        return !buffered || grid.bufferedRenderer.scrollTop;
                    });

                    runs(function() {
                        var el;

                        grouping.collapse('t4');
                        el = grid.getView().el.dom;

                        expect(el.scrollTop).toBe(el.scrollHeight - el.clientHeight);
                    });
                });

                it("should not focus the selected row when expanding a group", function() {
                    makeGrid();
                    // scroll to the bottom
                    grid.getSelectionModel().select(0);
                    grid.scrollByDeltaY(2000);
                    grouping.collapse('t4');

                    var el = grid.getView().el.dom,
                        top = el.scrollTop;

                    grouping.expand('t4');

                    expect(el.scrollTop).toBe(top);
                });

                it("should scroll to the group when expanding and it is out of the viewport and not expand other groups", function() {
                    var data = [],
                        i;

                    // 200 groups, 2 per group
                    for (i = 0; i < 400; ++i) {
                        data.push({
                            id: i + 1,
                            type: Math.floor(i / 2) + 1,
                            name: 'Item ' + (i + 1)
                        });
                    }

                    makeGrid(false, {
                        height: 400,
                        trailingBufferZone: 5,
                        leadingBufferZone: 5
                    }, data, false, {
                        startCollapsed: true
                    });

                    grouping.expand('200', true);

                    for (i = 1; i < 200; ++i) {
                        expect(grouping.isExpanded('' + i)).toBe(false);
                    }

                    expect(grouping.isExpanded('200')).toBe(true);

                    waitsFor(function() {
                        return view.el.dom.scrollTop > 3000;
                    });

                    runs(function() {
                        var header = grouping.getHeaderNode('200'),
                            headerRec = Ext.fly(header).getRegion(),
                            clientRec = view.el.getRegion(),
                            overlap = clientRec.intersect(headerRec);

                        expect(overlap).toBeTruthy();
                        expect(overlap.height).toBeGreaterThan(0);
                    });
                });
            });

            describe("adding", function() {
                it("should be able to add a record to an empty grid", function() {
                    makeGrid(false, {}, 0);
                    store.add({
                        name: 'Foo',
                        type: 'test'
                    });
                    expect(view.getNode(0).getAttribute('data-recordindex')).toBe('0');
                });
            });

            describe("updating", function() {
                describe("the id", function() {
                    it("should be able to modify the id of the first item in a group", function() {
                        makeGrid();
                        store.first().set('id', 1000);
                        triggerHeaderClick('t1');
                        expect(grouping.isExpanded('t1')).toBe(false);
                        triggerHeaderClick('t1');
                        expect(grouping.isExpanded('t1')).toBe(true);
                    });
                });

                it("should not cause an error when updating the first row of a group", function() {
                    makeGrid();
                    expect(function() {
                        store.first().set('name', 'foo');
                    }).not.toThrow();
                });

                it("should not cause an error when updating the first row of a group that has a column with preventUpdate: true", function() {
                    makeGrid(false, {
                        columns: [{
                            dataIndex: 'name',
                            preventUpdate: true
                        }]
                    });
                    expect(function() {
                        store.first().set('name', 'foo');
                    }).not.toThrow();
                });

                it("should be able to update the group field and then remove/add to the store", function() {
                    makeGrid(false, [{
                        columns: [{
                            dataIndex: 'name'
                        }]
                    }], [{
                        name: 'Item 1',
                        type: 'C'
                    }, {
                        name: 'Item 2',
                        type: 'C'
                    }, {
                        name: 'Item 3',
                        type: 'D'
                    }, {
                        name: 'Item 4',
                        type: 'D'
                    }, {
                        name: 'Item 5',
                        type: 'E'
                    }]);

                    var rec = store.getAt(0);

                    rec.set('type', 'A');
                    expect(getCellText(0, 0)).toBe('Item 1');
                    store.remove(rec);
                    store.add(rec);
                    expect(getCellText(0, 0)).toBe('Item 1');
                });
            });

            describe("locking", function() {
                it("should not cause an exception when collapsing/expanding after unlocking the only locked column", function() {
                    makeGrid(false, {
                        columns: [{
                            locked: true,
                            itemId: 'locked',
                            dataIndex: 'name'
                        }, {
                            dataIndex: 'name'
                        }]
                    });
                    grid.unlock(grid.down('#locked'));
                    expect(function() {
                        triggerHeaderClick('t1');
                        triggerHeaderClick('t1');
                    }).not.toThrow();
                });

                it("should be able to collapse and expand from the normal side", function() {
                    makeGrid(false, {
                        columns: [{
                            locked: true,
                            itemId: 'locked',
                            dataIndex: 'name'
                        }, {
                            dataIndex: 'name'
                        }]
                    });

                    triggerHeaderClick('t2');
                    expect(grouping.isExpanded('t2')).toBe(false);
                    triggerHeaderClick('t2');
                    expect(grouping.isExpanded('t2')).toBe(true);
                });

                // https://sencha.jira.com/browse/EXTJS-18047
                it('should not throw an exception when focusing a view in which all groups are collapsed', function() {
                    makeGrid(false, {
                        columns: [{
                            locked: true,
                            itemId: 'locked',
                            dataIndex: 'name'
                        }, {
                            dataIndex: 'name'
                        }]
                    }, null, null, {
                        startCollapsed: true
                    });
                    var normalView = grid.normalGrid.getView();

                    normalView.focus();
                    expect(Ext.Element.getActiveElement()).toBe(normalView.el.dom);
                });
            });

            describe("loading new data", function() {
                it("should be able to collapse and expand groups after loading new data", function() {
                    makeGrid();
                    store.loadData([{
                        id: 1001,
                        type: 't1'
                    }, {
                        id: 1002,
                        type: 't1'
                    }, {
                        id: 1003,
                        type: 't2'
                    }, {
                        id: 1004,
                        type: 't2'
                    }]);

                    grouping.expand('t1');
                    grouping.expand('t2');

                    expect(grouping.isExpanded('t1')).toBe(true);
                    expect(grouping.isExpanded('t2')).toBe(true);

                    grouping.collapse('t1');
                    grouping.collapse('t2');

                    expect(grouping.isExpanded('t1')).toBe(false);
                    expect(grouping.isExpanded('t2')).toBe(false);
                });

                it("should be able to collapse and expand groups after loading new data with startCollapsed: true", function() {
                    makeGrid(null, null, null, null, {
                        startCollapsed: true
                    });

                    grouping.expand('t1');

                    store.loadData([{
                        id: 1001,
                        type: 't1'
                    }, {
                        id: 1002,
                        type: 't1'
                    }, {
                        id: 1003,
                        type: 't2'
                    }, {
                        id: 1004,
                        type: 't2'
                    }]);

                    grouping.collapse('t1');
                    expect(grouping.isExpanded('t1')).toBe(false);

                    grouping.expand('t1');
                    expect(grouping.isExpanded('t1')).toBe(true);
                });
            });

            describe("reconfiguring", function() {
                function createReconfigureSuite(withLocking) {
                    describe(withLocking ? "with locking" : "without locking", function() {
                        var cols;

                        beforeEach(function() {
                            grouping = new Ext.grid.feature.Grouping();
                            // Start empty
                            makeGrid(null, {
                                enableLocking: withLocking,
                                columns: [],
                                store: null
                            });

                            cols = [{
                                dataIndex: 'name',
                                locked: withLocking
                            }, {
                                dataIndex: 'name'
                            }];

                            store = makeStore();
                        });

                        function getView() {
                            return withLocking ? grid.normalGrid.getView() : grid.getView();
                        }

                        function makeStore(cfg) {
                            return new Ext.data.Store(Ext.apply({
                                model: GridGroupModel,
                                groupField: 'type',
                                data: [{
                                    type: 't1',
                                    name: 'A'
                                }, {
                                    type: 't1',
                                    name: 'B'
                                }, {
                                    type: 't2',
                                    name: 'C'
                                }, {
                                    type: 't2',
                                    name: 'D'
                                }]
                            }, cfg));
                        }

                        it("should render the rows", function() {
                            grid.reconfigure(store, cols);
                            waitsFor(function() {
                                return getView().getNodes().length > 0;
                            }, "Rows never rendered");
                            runs(function() {
                                expect(getView().getNodes().length).toBe(4);
                            });
                        });

                        it("should render the group headers", function() {
                            grid.reconfigure(store, cols);
                            waitsFor(function() {
                                return getView().getNodes().length > 0;
                            }, "Rows never rendered");

                            runs(function() {
                                var t1 = findHeader('t1', getView()),
                                    t2 = findHeader('t2', getView());

                                expect(t1).not.toBeNull();
                                expect(t2).not.toBeNull();
                            });
                        });

                        it("should react to click events", function() {
                            grid.reconfigure(store, cols);
                            waitsFor(function() {
                                return getView().getNodes().length > 0;
                            }, "Rows never rendered");
                            runs(function() {
                                triggerHeaderClick('t1', getView());
                                // 2 from the second group + 1 placeholder
                                expect(getView().getNodes().length).toBe(3);
                            });
                        });

                        it("should not cause an error when using an autoDestroy store", function() {
                            store.autoDestroy = true;
                            expect(function() {
                                grid.reconfigure(store, cols);
                            }).not.toThrow();
                        });

                        it("should be able to reconfigure from grouped store -> not grouped store", function() {
                            grid.reconfigure(store, cols);
                            expect(function() {
                                grid.reconfigure(makeStore({
                                    groupField: ''
                                }));
                            }).not.toThrow();
                        });

                        it("should be able to reconfigure from not grouped store -> grouped store", function() {
                            store.destroy();
                            store = makeStore({
                                groupField: ''
                            });
                            grid.reconfigure(store, cols);
                            expect(function() {
                                grid.reconfigure(makeStore());
                            }).not.toThrow();
                        });
                    });
                }

                createReconfigureSuite(false);
                createReconfigureSuite(true);
            });

            if (buffered) {
                describe('Selection', function() {
                    // selection via Ext.view.Table#ensureVisible is async via a Promise
                    // so we need to wait for a change in selection prior to evaluating
                    // the expectation
                    var selectionChange = (function() {
                        var lastSelection;

                        return function() {
                            var selection = grid.selModel.getSelection()[0],
                                isChanged = selection !== lastSelection;

                            lastSelection = selection;

                            return isChanged;
                        };

                    }());

                    it('should skip collapsed groups', function() {
                        makeGrid();

                        runs(function() {
                            triggerHeaderClick('t1');
                            triggerHeaderClick('t4');

                            // Rows for all the child records of the uncollapsed groups, t2 and t3. Plus two collapsed placeholders.
                            expect(grid.view.all.getCount()).toBe(grouping.getGroup('t2').getRange().length + grouping.getGroup('t3').getRange().length + 2);

                            // Focus and select the first record of group t2
                            triggerCellMouseEvent('click', 1, 0);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            // ALT+End - Ask to go to end.
                            // With buffered rendering, that will expand the group that the target
                            // is in, so should select record 99
                            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.END, true);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            expect(grid.selModel.getSelection()[0] === grid.store.getAt(99)).toBe(true);

                            // ALT+Home - Ask to go to top.
                            // With buffered rendering, that will expand the group that the target
                            // is in, so should select record 0
                            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.HOME, true);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            expect(grid.selModel.getSelection()[0] === grid.store.getAt(0)).toBe(true);

                            triggerHeaderClick('t2'); // Collapse
                            triggerHeaderClick('t3'); // Collapse

                            // Rows for all the child records of the uncollapsed groups, t1 and t4 Plus two collapsed placeholders.
                            expect(grid.view.all.getCount()).toBe(grouping.getGroup('t1').getRange().length + grouping.getGroup('t4').getRange().length + 2);

                            // Focus and select the first record of group t1
                            triggerCellMouseEvent('click', 0, 0);

                            // ALT/END - Ask to go to end. Should skip the two collapsed groups and select record 99
                            triggerCellKeyEvent(1, 0, 'keydown', Ext.event.Event.END, true);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            expect(grid.selModel.getSelection()[0] === grid.store.getAt(99)).toBe(true);

                            // Focus and select the last record of group t1
                            triggerCellMouseEvent('click', 24, 0);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            // Ask to go to down 1. Should skip the two collapsed groups and select record 75
                            triggerCellKeyEvent(24, 0, 'keydown', Ext.event.Event.DOWN);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            expect(grid.selModel.getSelection()[0] === grid.store.getAt(75)).toBe(true);

                            // Ask to go to up 1. Should skip the two collapsed groups and select record 24
                            triggerCellKeyEvent(27, 0, 'keydown', Ext.event.Event.UP);
                        });

                        waitsFor(selectionChange);
                        runs(function() {
                            expect(grid.selModel.getSelection()[0] === grid.store.getAt(24)).toBe(true);
                        });
                    });
                });

                describe('grouping buffered stores', function() {
                    it('should work', function() {
                        var isLoaded;

                        makeBufferedStoreGrid({
                            listeners: {
                                load: function() {
                                    isLoaded = true;
                                }
                            }
                        });

                        waitsFor(function() {
                            return isLoaded;
                        }, 'for initial load');
                        runs(function() {
                            view.bufferedRenderer.scrollTo(25);

                            // We should scroll with no error, and the following should run with no error.
                            // *item* 25 should be a wrapping row. The first of group "t2". It should contain *row* 25 but deeply wrapped, not as the first child of the tbody
                            expect(view.all.item(25).contains(view.getRow(25))).toBe(true);
                            expect(view.all.item(25, true).firstChild.firstChild === view.getRow(35)).not.toBe(true);

                            // Should bring row index 99 into view (There are 100 rows, 0-99)
                            view.bufferedRenderer.scrollTo(100);

                            // We should scroll with no error, and the following should run with no error.
                            // *item* 75 should be a wrapping row. The first of group "t4". It should contain *row* 75 but deeply wrapped, not as the first <tr> of the tbody
                            expect(view.all.item(75).contains(view.getRow(75))).toBe(true);
                            expect(view.all.item(75).down('tbody', true).firstChild === view.getRow(75)).not.toBe(true);

                            // BufferedStore should reload upon group clear
                            isLoaded = false;
                            grouping.disable();
                        });
                        waitsFor(function() {
                            return isLoaded;
                        }, 'second load after group clear');
                        runs(function() {
                            // No opening group header. First item is the first row., NOT a wrapper.
                            expect(view.all.item(0, true).firstChild.firstChild === view.getRow(0)).toBe(true);
                        });
                    });
                });

                describe('Expand *and scrollTo* unrendered group', function() {
                    it('should use the buffered renderer to scroll to unrendered group headers', function() {
                        makeGrid(null, {
                            leadingBufferZone: Ext.grid.plugin.BufferedRenderer.prototype.leadingBufferZone,
                            trailingBufferZone: Ext.grid.plugin.BufferedRenderer.prototype.trailingBufferZone
                        }, 500);

                        grouping.collapse(store.getGroups().last().getGroupKey(), true);
                    });
                });
            }
        });
    }

    createSuite(false);
    createSuite(true);
});
