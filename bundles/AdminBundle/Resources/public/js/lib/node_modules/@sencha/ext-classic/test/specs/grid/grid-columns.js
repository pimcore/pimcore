topSuite("grid-columns",
    [false, 'Ext.grid.Panel', 'Ext.grid.column.*', 'Ext.data.ArrayStore',
     'Ext.grid.filters.*', 'Ext.form.field.Text'],
function() {
    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var defaultColNum = 4,
                totalWidth = 1000,
                grid, view, colRef, store, column;

            function spyOnEvent(object, eventName, fn) {
                var obj = {
                        fn: fn || Ext.emptyFn
                    },
                    spy = spyOn(obj, "fn");

                object.addListener(eventName, obj.fn);

                return spy;
            }

            function makeGrid(numCols, gridCfg, hiddenFn, lockedFn) {
                var cols, col, i;

                gridCfg = gridCfg || {};

                colRef = [];

                if (!numCols || typeof numCols === 'number') {
                    cols = [];
                    numCols = numCols || defaultColNum;

                    for (i = 0; i < numCols; ++i) {
                        col = {
                            itemId: 'col' + i,
                            text: 'Col' + i,
                            dataIndex: 'field' + i
                        };

                        if (hiddenFn && hiddenFn(i)) {
                            col.hidden = true;
                        }

                        if (lockedFn && lockedFn(i)) {
                            col.locked = true;
                        }

                        col = new Ext.grid.column.Column(col);
                        cols.push(col);
                    }
                }
                else {
                    cols = numCols;
                }

                store = new Ext.data.Store({
                    model: spec.TestModel,
                    data: [{
                        field0: 'val1',
                        field1: 'val2',
                        field2: 'val3',
                        field3: 'val4',
                        field4: 'val5'
                    }]
                });

                grid = new Ext.grid.Panel(Ext.apply({
                    renderTo: Ext.getBody(),
                    columns: cols,
                    width: totalWidth,
                    height: 500,
                    border: false,
                    store: store,
                    bufferedRenderer: buffered,
                    viewConfig: {
                        mouseOverOutBuffer: 0
                    }
                }, gridCfg));

                view = grid.view;
                colRef = grid.getColumnManager().getColumns();
            }

            function getCell(rowIdx, colIdx, asDom) {
                var cell = grid.getView().getCellInclusive({
                    row: rowIdx,
                    column: colIdx
                }, true);

                return asDom ? cell : Ext.fly(cell);
            }

            function getCellInner(rowIdx, colIdx) {
                var cell = getCell(rowIdx, colIdx, true);

                return cell.querySelector(grid.getView().innerSelector);
            }

            function getCellText(rowIdx, colIdx) {
                return getCellInner(rowIdx, colIdx).innerHTML;
            }

            function hasCls(el, cls) {
                return Ext.fly(el).hasCls(cls);
            }

            function clickHeader(col) {
                // Offset so we're not on the edges to trigger a drag
                jasmine.fireMouseEvent(col.titleEl, 'click', 10);
            }

            function resizeColumn(column, by) {
                var colBox = column.el.getBox(),
                    fromMx = colBox.x + colBox.width - 2,
                    fromMy = colBox.y + colBox.height / 2,
                    dragThresh = by > 0 ? Ext.dd.DragDropManager.clickPixelThresh + 1 : -Ext.dd.DragDropManager.clickPixelThresh - 1;

                // Mousedown on the header to drag
                jasmine.fireMouseEvent(column.el.dom, 'mouseover', fromMx, fromMy);
                jasmine.fireMouseEvent(column.el.dom, 'mousemove', fromMx, fromMy);
                jasmine.fireMouseEvent(column.el.dom, 'mousedown', fromMx, fromMy);

                // The initial move which tiggers the start of the drag
                jasmine.fireMouseEvent(column.el.dom, 'mousemove', fromMx + dragThresh, fromMy);

                // Move to resize
                jasmine.fireMouseEvent(column.el.dom, 'mousemove', fromMx + by + 2, fromMy);
                jasmine.fireMouseEvent(column.el.dom, 'mouseup', fromMx + by + 2, fromMy);
            }

            function setup() {
                Ext.define('spec.TestModel', {
                    extend: 'Ext.data.Model',
                    fields: ['field0', 'field1', 'field2', 'field3', 'field4']
                });
            }

            function tearDown() {
                Ext.destroy(grid, store, column);
                grid = view = store = colRef = column = null;
                Ext.undefine('spec.TestModel');
                Ext.data.Model.schema.clear();
            }

            beforeEach(setup);

            afterEach(tearDown);

            // https://sencha.jira.com/browse/EXTJS-19950
            describe('force fit columns, shrinking width to where flexes tend to zero', function() {
                it('should work', function() {
                    makeGrid([{
                        text: 'Col1',
                        dataIndex: 'foo',
                        flex: 1
                    },  {
                        text: 'Col2',
                        columns: [{
                            text: 'Col21',
                            dataIndex: 'foo2',
                             width: 140
                        }, {
                            text: 'Col22',
                            dataIndex: 'foo4',
                            width: 160
                        }, {
                            text: 'Col23',
                            dataIndex: 'foo4',
                            width: 100
                        }, {
                            text: 'Col34',
                            dataIndex: 'foo4',
                            width: 85
                        }]
                    }, {
                        text: 'Col3',
                        dataIndex: 'foo3',
                        width: 110
                    }, {
                        text: 'Col4',
                        columns: [ {
                            text: 'Col41',
                            dataIndex: 'foo2',
                                flex: 1
                        }, {
                            text: 'Col42',
                            dataIndex: 'foo4',
                            width: 120
                        }]
                    }], {
                        autoScroll: true,
                        forceFit: true,
                        width: 1800
                    });

                    expect(function() {
                        grid.setWidth(700);
                    }).not.toThrow();
                });
            });

            describe('as containers', function() {
                var leafCls = 'x-leaf-column-header',
                    col;

                afterEach(function() {
                    col = null;
                });

                describe('group headers', function() {
                    beforeEach(function() {
                        makeGrid([{
                            itemId: 'main1',
                            text: 'Group Header',
                            flex: 1,
                            // Allow for the full 600 width so that flexes work out to integers.
                            border: false,

                            columns: [{
                                itemId: 'child1',
                                text: 'Subcol 1',
                                flex: 1
                            }, {
                                itemId: 'child2',
                                text: 'Subcol 2',
                                flex: 2
                            }, {
                                itemId: 'child3',
                                text: 'Subcol 3',
                                flex: 3
                            }]
                        }], {
                            width: 600,

                            // No scrollbar, so we have one calculation pass with
                            // 600 pixels available.
                            scroll: false
                        });

                        col = grid.down('#main1');
                    });

                    it('should be stamped as a container', function() {
                        expect(col.isContainer).toBe(true);
                    });

                    it('should not give the titleEl the leaf column class', function() {
                        expect(col.titleEl.hasCls(leafCls)).toBe(false);
                    });

                    it('should honour flex settings in group headers and their children', function() {
                        // Owner is calculated widthModel
                        expect(colRef[0].lastBox.width).toBe(100);
                        expect(colRef[1].lastBox.width).toBe(200);
                        expect(colRef[2].lastBox.width).toBe(300);

                        // When all children are fixed width, it should revert to shrink wrapping
                        colRef[0].flex = colRef[1].flex = colRef[2].flex = null;
                        colRef[0].setWidth(100);
                        colRef[1].setWidth(100);
                        colRef[2].setWidth(100);
                        expect(colRef[0].ownerCt.lastBox.width).toBe(300);

                        // Now when owner is configured widthModel with 
                        // flexed children
                        colRef[0].ownerCt.flex = null;
                        colRef[0].flex = 1;
                        colRef[1].flex = 2;
                        colRef[2].flex = 3;
                        colRef[0].ownerCt.setWidth(600);

                        expect(colRef[0].lastBox.width).toBe(100);
                        expect(colRef[1].lastBox.width).toBe(200);
                        expect(colRef[2].lastBox.width).toBe(300);

                        // Fall back to shrinkWrapping column widths
                        colRef[0].ownerCt.setWidth(null);
                        colRef[0].setWidth(100);
                        colRef[1].setWidth(100);
                        colRef[2].setWidth(100);
                        expect(colRef[0].ownerCt.lastBox.width).toBe(300);
                    });
                });

                describe('contains child items', function() {
                    beforeEach(function() {
                        makeGrid([{
                            text: 'Foo',
                            dataIndex: 'field0',
                            flex: 1,
                            items: [{
                                xtype: 'textfield',
                                itemId: 'foo'
                            }]
                        }]);

                        col = grid.visibleColumnManager.getHeaderByDataIndex('field0');
                    });

                    it('should be stamped as a container', function() {
                        expect(col.isContainer).toBe(true);
                    });

                    it('should not give the titleEl the leaf column class', function() {
                        expect(col.titleEl.hasCls(leafCls)).toBe(false);
                    });

                    it("should retain a flex value", function() {
                        expect(col.getWidth()).toBe(grid.getWidth());
                    });

                    describe('focusing', function() {
                        // See EXTJS-15757.
                        it('should not throw when focusing', function() {
                            expect(function() {
                                grid.down('#foo').onFocus();
                            }).not.toThrow();
                        });

                        it('should return the items collection', function() {
                            var col = grid.visibleColumnManager.getHeaderByDataIndex('field0');

                            expect(col.getFocusables()).toBe(col.items.items);
                        });
                    });
                });
            });

            describe("cell sizing", function() {
                it("should size the cells to match fixed header sizes", function() {
                    makeGrid([{
                        width: 200
                    }, {
                        width: 500
                    }]);
                    expect(getCell(0, 0).getWidth()).toBe(200);
                    expect(getCell(0, 1).getWidth()).toBe(500);
                });

                it("should size the cells to match flex header sizes", function() {
                    makeGrid([{
                        flex: 8
                    }, {
                        flex: 2
                    }]);
                    expect(getCell(0, 0).getWidth()).toBe(800);
                    expect(getCell(0, 1).getWidth()).toBe(200);
                });

                it("should size the cells to match an the text size in the header", function() {
                    makeGrid([{
                        width: null,
                        text: '<div style="width: 25px;"></div>'
                    }, {
                        width: null,
                        text: '<div style="width: 75px;"></div>'
                    }]);

                    expect(getCell(0, 0).getWidth()).toBe(colRef[0].titleEl.getWidth() + colRef[0].el.getBorderWidth('lr'));
                    expect(getCell(0, 1).getWidth()).toBe(colRef[1].titleEl.getWidth() + colRef[1].el.getBorderWidth('lr'));
                });
            });

            describe("initializing", function() {
                describe("normal", function() {
                    it("should accept a column array", function() {
                        makeGrid([{
                            text: 'Foo',
                            dataIndex: 'field0'
                        }]);
                        expect(grid.getColumnManager().getHeaderAtIndex(0).text).toBe('Foo');
                    });

                    it("should accept a header config", function() {
                        makeGrid({
                            margin: 5,
                            items: [{
                                text: 'Foo',
                                dataIndex: 'field0'
                            }]
                        });
                        expect(grid.getColumnManager().getHeaderAtIndex(0).text).toBe('Foo');
                        expect(grid.headerCt.margin).toBe(5);
                    });
                });

                describe("locking", function() {
                    it("should accept a column array, enabling locking if a column is configured with locked: true", function() {
                        makeGrid([{
                            text: 'Foo',
                            dataIndex: 'field0',
                            locked: true
                        }, {
                            text: 'Bar',
                            dataIndex: 'field1'
                        }]);
                        expect(grid.lockable).toBe(true);
                    });

                    it("should accept a header config, enabling locking if any column is configured with locked: true", function() {
                        makeGrid({
                            items: [{
                                text: 'Foo',
                                dataIndex: 'field0',
                                locked: true
                            }, {
                                text: 'Bar',
                                dataIndex: 'field1'
                            }]
                        });
                        expect(grid.lockable).toBe(true);

                        // Top level grid should return columns from both sides
                        expect(grid.getVisibleColumns().length).toBe(2);
                        expect(grid.getColumns().length).toBe(2);
                    });
                });
            });

            describe("column manager", function() {
                // Get all columns from the grid ref
                function ga() {
                    return grid.getColumnManager().getColumns();
                }

                // Get all manager
                function gam() {
                    return grid.getColumnManager();
                }

                // Get all visible columns from the grid ref
                function gv() {
                    return grid.getVisibleColumnManager().getColumns();
                }

                // Get visible manager
                function gvm() {
                    return grid.getVisibleColumnManager();
                }

                it("should provide a getColumnManager method", function() {
                    makeGrid();
                    expect(gam().$className).toBe('Ext.grid.ColumnManager');
                });

                it("should provide a getVisibleColumnManager method", function() {
                    makeGrid();
                    expect(gvm().$className).toBe('Ext.grid.ColumnManager');
                });

                describe("simple grid", function() {
                    beforeEach(function() {
                        makeGrid();
                    });

                    it("should return all leaf columns", function() {
                        expect(gv().length).toBe(defaultColNum);
                    });

                    it("should have the correct column order", function() {
                        var cols = gv(),
                            i = 0,
                            len = cols.length;

                        for (; i < len; ++i) {
                            expect(cols[i]).toBe(colRef[i]);
                        }
                    });

                    it("should update the order when moving columns", function() {
                        grid.headerCt.move(3, 1);
                        var cols = gv();

                        expect(cols[0]).toBe(colRef[0]);
                        expect(cols[1]).toBe(colRef[3]);
                        expect(cols[2]).toBe(colRef[1]);
                        expect(cols[3]).toBe(colRef[2]);
                    });

                    it("should update the columns when removing a column", function() {
                        grid.headerCt.remove(1);
                        var cols = gv();

                        expect(cols[0]).toBe(colRef[0]);
                        expect(cols[1]).toBe(colRef[2]);
                        expect(cols[2]).toBe(colRef[3]);
                    });

                    it("should update the columns when adding a column", function() {
                        grid.headerCt.add({
                            text: 'Col4'
                        });
                        expect(gv()[4].text).toBe('Col4');
                    });

                    describe("functions", function() {
                        describe("getHeaderIndex", function() {
                            it("should return the correct index for the header", function() {
                                expect(gam().getHeaderIndex(colRef[3])).toBe(3);
                            });

                            it("should return -1 if the column doesn't exist", function() {
                                column = new Ext.grid.column.Column();

                                expect(gam().getHeaderIndex(column)).toBe(-1);
                            });
                        });

                        describe("getHeaderAtIndex", function() {
                            it("should return the column reference", function() {
                                expect(gam().getHeaderAtIndex(2)).toBe(colRef[2]);
                            });

                            it("should return null if the index is out of bounds", function() {
                                expect(gam().getHeaderAtIndex(10)).toBeNull();
                            });
                        });

                        describe("getHeaderById", function() {
                            it("should return the column reference by id", function() {
                                expect(gam().getHeaderById('col1')).toBe(colRef[1]);
                            });

                            it("should return null if the id doesn't exist", function() {
                                expect(gam().getHeaderById('foo')).toBeNull();
                            });
                        });

                        it("should return the first item", function() {
                            expect(gam().getFirst()).toBe(colRef[0]);
                        });

                        it("should return the last item", function() {
                            expect(gam().getLast()).toBe(colRef[3]);
                        });

                        describe("getNextSibling", function() {
                            it("should return the next sibling", function() {
                                expect(gam().getNextSibling(colRef[1])).toBe(colRef[2]);
                            });

                            it("should return the null if the next sibling doesn't exist", function() {
                                expect(gam().getNextSibling(colRef[3])).toBeNull();
                            });
                        });

                        describe("getPreviousSibling", function() {
                            it("should return the previous sibling", function() {
                                expect(gam().getPreviousSibling(colRef[2])).toBe(colRef[1]);
                            });

                            it("should return the null if the previous sibling doesn't exist", function() {
                                expect(gam().getPreviousSibling(colRef[0])).toBeNull();
                            });
                        });
                    });
                });

                describe('getHeaderIndex', function() {
                    var index, headerCtItems;

                    beforeEach(function() {
                        makeGrid([{
                            text: 'Name',
                            width: 100,
                            dataIndex: 'name',
                            hidden: true
                        }, {
                            text: 'Email',
                            width: 100,
                            dataIndex: 'email'
                        }, {
                            text: 'Stock Price',
                            columns: [{
                                text: 'Price',
                                width: 75,
                                dataIndex: 'price'
                            }, {
                                text: 'Phone',
                                width: 80,
                                dataIndex: 'phone',
                                hidden: true
                            }, {
                                text: '% Change',
                                width: 40,
                                dataIndex: 'pctChange'
                            }]
                        }, {
                            text: 'Foo',
                            columns: [{
                                text: 'Foo Price',
                                width: 75,
                                dataIndex: 'price',
                                hidden: true
                            }, {
                                text: 'Foo Phone',
                                width: 80,
                                dataIndex: 'phone'
                            }, {
                                text: 'Foo % Change',
                                width: 40,
                                dataIndex: 'pctChange'
                            }]
                        }]);

                        headerCtItems = grid.headerCt.items;
                    });

                    afterEach(function() {
                        index = headerCtItems = null;
                    });

                    describe('all columns', function() {
                        describe('when argument is a column', function() {
                            it('should return a valid index', function() {
                                index = gam().getHeaderIndex(headerCtItems.items[0]);

                                expect(index).not.toBe(-1);
                                expect(index).toBe(0);
                            });

                            it('should return the header regardless of visibility', function() {
                                var header;

                                header = headerCtItems.items[0];
                                index = gam().getHeaderIndex(header);

                                expect(header.hidden).toBe(true);
                                expect(index).toBe(0);
                            });

                            it('should return the index of the header in its owner stack - rootHeader', function() {
                                index = gam().getHeaderIndex(headerCtItems.items[3].items.items[0]);

                                expect(index).toBe(5);
                            });

                            it('should return the index of the header in its owner stack - groupHeader', function() {
                                // Note that this spec is using the same header as the previous spec to demonstrate the difference.
                                var groupHeader = headerCtItems.items[3];

                                index = groupHeader.columnManager.getHeaderIndex(groupHeader.items.items[0]);

                                expect(index).toBe(0);
                            });
                        });

                        describe('when argument is a group header', function() {
                            it('should return a valid index', function() {
                                index = gam().getHeaderIndex(headerCtItems.items[2]);

                                expect(index).not.toBe(-1);
                                expect(index).toBe(2);
                            });

                            it('should return an index of the first leaf of group header', function() {
                                var colMgrHeader;

                                // First, get the index from the column mgr.  It will retrieve it from the group header's column mgr.
                                index = gam().getHeaderIndex(headerCtItems.items[2]);
                                // Next, get a reference to the actual header (top-level col mgr will have a ref to all sub-level headers).
                                colMgrHeader = gam().getHeaderAtIndex(index);

                                // Remember, this is the index of the root header's visible col mgr.
                                expect(index).toBe(2);
                                expect(colMgrHeader.hidden).toBe(false);
                                expect(colMgrHeader.dataIndex).toBe('price');
                            });

                            it("should be a reference to the first leaf header in the grouped header's columnn manager", function() {
                                var groupedHeader, colMgrHeader, groupHeaderFirstHeader;

                                groupedHeader = headerCtItems.items[2];
                                groupHeaderFirstHeader = groupedHeader.columnManager.getHeaderAtIndex(0);

                                // First, get the index from the column mgr.  It will retrieve it from the group header's column mgr.
                                index = gam().getHeaderIndex(groupedHeader);
                                // Next, get a reference to the actual header (top-level col mgr will have a ref to all sub-level headers).
                                colMgrHeader = gam().getHeaderAtIndex(index);

                                expect(colMgrHeader).toBe(groupHeaderFirstHeader);
                                expect(colMgrHeader.hidden).toBe(groupHeaderFirstHeader.hidden);
                                expect(colMgrHeader.dataIndex).toBe(groupHeaderFirstHeader.dataIndex);
                            });

                            it('should return first sub-header regardless of visibility', function() {
                                var groupedHeader, colMgrHeader, groupHeaderFirstHeader;

                                groupedHeader = headerCtItems.items[3];
                                groupHeaderFirstHeader = groupedHeader.columnManager.getHeaderAtIndex(0);

                                // First, get the index from the column mgr.  It will retrieve it from the group header's column mgr.
                                index = gam().getHeaderIndex(groupedHeader);
                                // Next, get a reference to the actual header (top-level col mgr will have a ref to all sub-level headers).
                                colMgrHeader = gam().getHeaderAtIndex(index);

                                expect(colMgrHeader).toBe(groupHeaderFirstHeader);
                                expect(colMgrHeader.hidden).toBe(true);
                                expect(colMgrHeader.text).toBe('Foo Price');
                            });
                        });
                    });

                    describe('visible only', function() {
                        describe('when argument is a column', function() {
                            it('should return the correct index for the header', function() {
                                expect(gvm().getHeaderIndex(headerCtItems.items[1])).toBe(0);
                            });

                            it("should return -1 if the column doesn't exist", function() {
                                column = new Ext.grid.column.Column();

                                expect(gvm().getHeaderIndex(column)).toBe(-1);
                            });

                            it('should not return a hidden sub-header', function() {
                                var header;

                                header = headerCtItems.items[0];
                                index = gvm().getHeaderIndex(header);

                                expect(header.hidden).toBe(true);
                                expect(index).toBe(-1);
                            });

                            it('should return a valid index', function() {
                                index = gvm().getHeaderIndex(headerCtItems.items[1]);

                                expect(index).not.toBe(-1);
                                // Will filter out the first hidden column in the stack.
                                expect(index).toBe(0);
                            });

                            it('should return the index of the header in its owner stack - rootHeader', function() {
                                index = gvm().getHeaderIndex(headerCtItems.items[3].items.items[2]);

                                expect(index).toBe(4);
                            });

                            it('should return the index of the header in its owner stack - groupHeader', function() {
                                // Note that this spec is using the same header as the previous spec to demonstrate the difference.
                                var groupHeader = headerCtItems.items[3];

                                index = groupHeader.visibleColumnManager.getHeaderIndex(groupHeader.items.items[2]);

                                expect(index).toBe(1);
                            });
                        });

                        describe('when argument is a group header', function() {
                            it('should return a valid index', function() {
                                index = gvm().getHeaderIndex(headerCtItems.items[2]);

                                expect(index).not.toBe(-1);
                                // Will filter out the second hidden column in the stack.
                                expect(index).toBe(1);
                            });

                            it('should return an index of the first leaf of group header', function() {
                                var colMgrHeader;

                                // First, get the index from the column mgr.  It will retrieve it from the group header's column mgr.
                                index = gvm().getHeaderIndex(headerCtItems.items[2]);
                                // Next, get a reference to the actual header (top-level col mgr will have a ref to all sub-level headers).
                                colMgrHeader = gvm().getHeaderAtIndex(index);

                                // Remember, this is the index of the root header's visible col mgr.
                                expect(index).toBe(1);
                                expect(colMgrHeader.hidden).toBe(false);
                                expect(colMgrHeader.dataIndex).toBe('price');
                            });

                            it("should be a reference to the first leaf header in the grouped header's columnn manager", function() {
                                var groupedHeader, colMgrHeader, groupHeaderFirstHeader;

                                groupedHeader = headerCtItems.items[2];
                                groupHeaderFirstHeader = headerCtItems.items[2].visibleColumnManager.getHeaderAtIndex(0);

                                // First, get the index from the column mgr.  It will retrieve it from the group header's column mgr.
                                index = gvm().getHeaderIndex(groupedHeader);
                                // Next, get a reference to the actual header (top-level col mgr will have a ref to all sub-level headers).
                                colMgrHeader = gvm().getHeaderAtIndex(index);

                                expect(colMgrHeader).toBe(groupHeaderFirstHeader);
                                expect(colMgrHeader.hidden).toBe(groupHeaderFirstHeader.hidden);
                                expect(colMgrHeader.dataIndex).toBe(groupHeaderFirstHeader.dataIndex);
                            });

                            it('should not return a hidden sub-header', function() {
                                var groupedHeader, colMgrHeader, groupHeaderFirstHeader;

                                groupedHeader = headerCtItems.items[3];
                                groupHeaderFirstHeader = groupedHeader.visibleColumnManager.getHeaderAtIndex(0);

                                // First, get the index from the column mgr.  It will retrieve it from the group header's column mgr.
                                index = gvm().getHeaderIndex(groupedHeader);
                                // Next, get a reference to the actual header (top-level col mgr will have a ref to all sub-level headers).
                                colMgrHeader = gvm().getHeaderAtIndex(index);

                                expect(colMgrHeader).toBe(groupHeaderFirstHeader);
                                expect(colMgrHeader.hidden).toBe(false);
                                expect(colMgrHeader.text).toBe('Foo Phone');
                            });
                        });
                    });
                });

                describe('getHeaderAtIndex', function() {
                    var header, headerCtItems;

                    beforeEach(function() {
                        makeGrid([{
                            text: 'Name',
                            width: 100,
                            dataIndex: 'name',
                            hidden: true
                        }, {
                            text: 'Email',
                            width: 100,
                            dataIndex: 'email'
                        }, {
                            text: 'Stock Price',
                            columns: [{
                                text: 'Price',
                                width: 75,
                                dataIndex: 'price'
                            }, {
                                text: 'Phone',
                                width: 80,
                                dataIndex: 'phone',
                                hidden: true
                            }, {
                                text: '% Change',
                                width: 40,
                                dataIndex: 'pctChange'
                            }]
                        }, {
                            text: 'Foo',
                            columns: [{
                                text: 'Foo Price',
                                width: 75,
                                dataIndex: 'price',
                                hidden: true
                            }, {
                                text: 'Foo Phone',
                                width: 80,
                                dataIndex: 'phone'
                            }, {
                                text: 'Foo % Change',
                                width: 40,
                                dataIndex: 'pctChange'
                            }]
                        }]);

                        headerCtItems = grid.headerCt.items;
                    });

                    afterEach(function() {
                        header = headerCtItems = null;
                    });

                    describe('all columns', function() {
                        it('should return a valid header', function() {
                            header = gam().getHeaderAtIndex(0);

                            expect(header).not.toBe(null);
                            expect(header.dataIndex).toBe('name');
                        });

                        it('should return the correct header from the index', function() {
                            expect(gam().getHeaderAtIndex(0).dataIndex).toBe('name');
                        });

                        it("should return null if the column doesn't exist", function() {
                            expect(gam().getHeaderAtIndex(50)).toBe(null);
                        });

                        it('should return the header regardless of visibility', function() {
                            var header2;

                            header = gam().getHeaderAtIndex(0);
                            header2 = gam().getHeaderAtIndex(1);

                            expect(header).not.toBe(null);
                            expect(header.hidden).toBe(true);

                            expect(header2).not.toBe(null);
                            expect(header2.hidden).toBe(false);
                        });

                        it('should return the header in its owner stack - rootHeader', function() {
                            header = gam().getHeaderAtIndex(0);

                            expect(header.text).toBe('Name');
                        });

                        it('should return the index of the header in its owner stack - groupHeader', function() {
                            // Note that this spec is using the index as the previous spec to demonstrate the difference.
                            header = headerCtItems.items[3].columnManager.getHeaderAtIndex(0);

                            expect(header.text).toBe('Foo Price');
                        });
                    });

                    describe('visible only', function() {
                        it('should return the correct header from the index', function() {
                            expect(gvm().getHeaderAtIndex(0).dataIndex).toBe('email');
                        });

                        it("should return null if the column doesn't exist", function() {
                            expect(gvm().getHeaderAtIndex(50)).toBe(null);
                        });

                        it('should not return a hidden sub-header', function() {
                            header = gvm().getHeaderAtIndex(2);

                            expect(header.hidden).toBe(false);
                            expect(header.dataIndex).toBe('pctChange');
                        });

                        it('should return a valid header', function() {
                            header = gvm().getHeaderAtIndex(0);

                            expect(header).not.toBe(null);
                            expect(header.dataIndex).toBe('email');
                        });

                        it('should return the header in its owner stack - rootHeader', function() {
                            header = gvm().getHeaderAtIndex(0);
                            expect(header.text).toBe('Email');
                        });

                        it('should return the index of the header in its owner stack - groupHeader', function() {
                            // Note that this spec is using the same header as the previous spec to demonstrate the difference.
                            var groupHeader = headerCtItems.items[3];

                            header = headerCtItems.items[3].visibleColumnManager.getHeaderAtIndex(0);

                            expect(header.text).toBe('Foo Phone');
                        });
                    });
                });

                describe('getHeaderByDataIndex', function() {
                    beforeEach(function() {
                        makeGrid([{
                            text: 'Name',
                            width: 100,
                            dataIndex: 'name',
                            hidden: true
                        }, {
                            text: 'Email',
                            width: 100,
                            dataIndex: 'email'
                        }, {
                            xtype: 'templatecolumn',
                            text: 'Name & Email',
                            width: 100,
                            tpl: '{name} & {email}'
                        }]);
                    });

                    it("should return the correct header for dataIndex", function() {
                        expect(gam().getHeaderByDataIndex('email').dataIndex).toBe('email');
                    });

                    it("should return null if column doesn't exist", function() {
                        expect(gam().getHeaderByDataIndex('foo')).toBe(null);
                    });

                    it("should return null if invalid dataIndex is passed", function() {
                        expect(gam().getHeaderByDataIndex(null)).toBe(null);
                        expect(gam().getHeaderByDataIndex('')).toBe(null);
                        expect(gam().getHeaderByDataIndex(undefined)).toBe(null);
                    });
                });

                describe('hidden columns', function() {
                    // Hidden at index 3/6
                    beforeEach(function() {
                        makeGrid(8, null, function(i) {
                            return i > 0 && i % 3 === 0;
                        });
                    });

                    it("should return all columns when using getColumnManager", function() {
                        expect(ga().length).toBe(8);
                    });

                    it("should return only visible columns when using getVisibleColumnManager", function() {
                        expect(gv().length).toBe(6);
                    });

                    it("should update the collection when hiding a column", function() {
                        colRef[0].hide();
                        expect(gv().length).toBe(5);
                    });

                    it("should update the collection when showing a column", function() {
                        colRef[3].show();
                        expect(gv().length).toBe(7);
                    });

                    describe("getHeaderAtIndex", function() {
                        it("should return the column reference", function() {
                            expect(gvm().getHeaderAtIndex(3)).toBe(colRef[4]);
                        });

                        it("should return null if the index is out of bounds", function() {
                            expect(gvm().getHeaderAtIndex(7)).toBeNull();
                        });
                    });

                    describe("getHeaderById", function() {
                        it("should return the column reference by id", function() {
                            expect(gvm().getHeaderById('col1')).toBe(colRef[1]);
                        });

                        it("should return null if the id doesn't exist", function() {
                            expect(gvm().getHeaderById('col3')).toBeNull();
                        });
                    });

                    it("should return the first item", function() {
                        expect(gvm().getFirst()).toBe(colRef[0]);
                    });

                    it("should return the last item", function() {
                        expect(gvm().getLast()).toBe(colRef[7]);
                    });

                    describe("getNextSibling", function() {
                        it("should return the next sibling", function() {
                            expect(gvm().getNextSibling(colRef[2])).toBe(colRef[4]);
                        });

                        it("should return the null if the next sibling doesn't exist", function() {
                            expect(gvm().getNextSibling(colRef[3])).toBeNull();
                        });
                    });

                    describe("getPreviousSibling", function() {
                        it("should return the previous sibling", function() {
                            expect(gvm().getPreviousSibling(colRef[7])).toBe(colRef[5]);
                        });

                        it("should return the null if the previous sibling doesn't exist", function() {
                            expect(gvm().getPreviousSibling(colRef[6])).toBeNull();
                        });
                    });
                });

                describe("locking", function() {
                    // first 4 locked
                    beforeEach(function() {
                        makeGrid(10, null, null, function(i) {
                            return i <= 3;
                        });
                    });

                    describe("global manager", function() {
                        it("should return both sets of columns", function() {
                            expect(ga().length).toBe(10);
                        });

                        it("should update the collection when adding to the locked side", function() {
                            grid.lockedGrid.headerCt.add({
                                text: 'Foo'
                            });
                            expect(ga().length).toBe(11);
                        });

                        it("should update the collection when adding to the unlocked side", function() {
                            grid.normalGrid.headerCt.add({
                                text: 'Foo'
                            });
                            expect(ga().length).toBe(11);
                        });

                        it("should update the collection when removing from the locked side", function() {
                            grid.lockedGrid.headerCt.remove(0);
                            expect(ga().length).toBe(9);
                        });

                        it("should update the collection when removing from the unlocked side", function() {
                            grid.normalGrid.headerCt.remove(0);
                            expect(ga().length).toBe(9);
                        });

                        it("should maintain the same size when locking an item", function() {
                            grid.lock(colRef[4]);
                            expect(ga().length).toBe(10);
                        });

                        it("should maintain the same size when unlocking an item", function() {
                            grid.unlock(colRef[0]);
                            expect(ga().length).toBe(10);
                        });
                    });

                    describe("locked side", function() {
                        var glm = function() {
                            return grid.lockedGrid.getColumnManager();
                        };

                        it("should only return the columns for this side", function() {
                            expect(glm().getColumns().length).toBe(4);
                        });

                        it("should update the collection when adding an item to this side", function() {
                            grid.lock(colRef[9]);
                            expect(glm().getColumns().length).toBe(5);
                        });

                        it("should update the collection when removing an item from this side", function() {
                            grid.unlock(colRef[0]);
                            expect(glm().getColumns().length).toBe(3);
                        });

                        describe("function", function() {
                            describe("getHeaderIndex", function() {
                                it("should return the correct index for the header", function() {
                                    expect(glm().getHeaderIndex(colRef[2])).toBe(2);
                                });

                                it("should return -1 if the column doesn't exist", function() {
                                    expect(glm().getHeaderIndex(colRef[5])).toBe(-1);
                                });
                            });

                            describe("getHeaderAtIndex", function() {
                                it("should return the column reference", function() {
                                    expect(glm().getHeaderAtIndex(3)).toBe(colRef[3]);
                                });

                                it("should return null if the index is out of bounds", function() {
                                    expect(glm().getHeaderAtIndex(6)).toBeNull();
                                });
                            });

                            describe("getHeaderById", function() {
                                it("should return the column reference by id", function() {
                                    expect(glm().getHeaderById('col1')).toBe(colRef[1]);
                                });

                                it("should return null if the id doesn't exist", function() {
                                    expect(glm().getHeaderById('col5')).toBeNull();
                                });
                            });
                        });
                    });

                    describe("unlocked side", function() {
                        var gum = function() {
                            return grid.normalGrid.getColumnManager();
                        };

                        it("should only return the columns for this side", function() {
                            expect(gum().getColumns().length).toBe(6);
                        });

                        it("should update the collection when adding an item to this side", function() {
                            grid.unlock(colRef[1]);
                            expect(gum().getColumns().length).toBe(7);
                        });

                        it("should update the collection when removing an item from this side", function() {
                            grid.lock(colRef[7]);
                            expect(gum().getColumns().length).toBe(5);
                        });

                        describe("function", function() {
                            var offset = 4;

                            describe("getHeaderIndex", function() {
                                it("should return the correct index for the header", function() {
                                    expect(gum().getHeaderIndex(colRef[offset + 2])).toBe(2);
                                });

                                it("should return -1 if the column doesn't exist", function() {
                                    expect(gum().getHeaderIndex(colRef[0])).toBe(-1);
                                });
                            });

                            describe("getHeaderAtIndex", function() {
                                it("should return the column reference", function() {
                                    expect(gum().getHeaderAtIndex(3)).toBe(colRef[3 + offset]);
                                });

                                it("should return null if the index is out of bounds", function() {
                                    expect(gum().getHeaderAtIndex(6)).toBeNull();
                                });
                            });

                            describe("getHeaderById", function() {
                                it("should return the column reference by id", function() {
                                    expect(gum().getHeaderById('col6')).toBe(colRef[6]);
                                });

                                it("should return null if the id doesn't exist", function() {
                                    expect(gum().getHeaderById('col2')).toBeNull();
                                });
                            });
                        });
                    });
                });
            });

            describe("menu", function() {
                it("should not allow menu to be shown when menuDisabled: true", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        width: 200,
                        filter: 'string',
                        menuDisabled: true
                    }], {
                        plugins: 'gridfilters'
                    });

                    // menuDisabled=true, shouldn't have a trigger
                    expect(colRef[0].triggerEl).toBeNull();
                });

                it("should not allow menu to be shown when grid is configured with enableColumnHide: false and sortableColumns: false", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        width: 200
                    }], {
                        enableColumnHide: false,
                        sortableColumns: false
                    });

                    expect(colRef[0].triggerEl).toBeNull();
                });

                it("should allow menu to be shown when requiresMenu: true (from plugin) and grid is configured with enableColumnHide: false and sortableColumns: false", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        width: 200,
                        filter: 'string'
                    }], {
                        enableColumnHide: false,
                        sortableColumns: false,
                        plugins: 'gridfilters'
                    });

                    Ext.testHelper.showHeaderMenu(colRef[0]);
                });
            });

            describe("sorting", function() {
                it("should sort by dataIndex when clicking on the header with sortable: true", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        sortable: true
                    }]);
                    clickHeader(colRef[0]);
                    var sorters = store.getSorters();

                    expect(sorters.getCount()).toBe(1);
                    expect(sorters.first().getProperty()).toBe('field0');
                    expect(sorters.first().getDirection()).toBe('ASC');
                });

                it("should invert the sort order when clicking on a sorted column", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        sortable: true
                    }]);
                    clickHeader(colRef[0]);
                    var sorters = store.getSorters();

                    clickHeader(colRef[0]);
                    expect(sorters.getCount()).toBe(1);
                    expect(sorters.first().getProperty()).toBe('field0');
                    expect(sorters.first().getDirection()).toBe('DESC');
                    clickHeader(colRef[0]);
                    expect(sorters.getCount()).toBe(1);
                    expect(sorters.first().getProperty()).toBe('field0');
                    expect(sorters.first().getDirection()).toBe('ASC');
                });

                it("should be able to initally sort a custom sorter with direction DESC", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        sorter: {
                            sorterFn: function(a, b) {
                                a = a.get("field0");
                                b = b.get("field0");

                                return a > b ? 1 : (a === b) ? 0 : -1;

                            },
                            direction: "ASC"
                        }
                    }]);

                    colRef[0].sort('DESC');

                    expect(colRef[0].getSorter().getDirection()).toBe('DESC');
                });

                it("should be able sort to any direction when switching sorters", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        sorter: {
                            sorterFn: function(a, b) {
                                a = a.get("field0");
                                b = b.get("field0");

                                return a > b ? 1 : (a === b) ? 0 : -1;

                            },
                            direction: "ASC"
                        }
                    }, {
                        dataIndex: 'field1'
                    }]);

                    colRef[0].sort('DESC');
                    expect(colRef[0].getSorter().getDirection()).toBe('DESC');

                    colRef[1].sort('ASC');

                    colRef[0].sort('ASC');
                    expect(colRef[0].getSorter().getDirection()).toBe('ASC');
                });

                it("should not lose track of direction when sorting via header and menu with a custom sorter", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        sorter: {
                            sorterFn: function(a, b) {
                                a = a.get("field0");
                                b = b.get("field0");

                                return a > b ? 1 : (a === b) ? 0 : -1;

                            },
                            direction: "ASC"
                        }
                    }]);
                    clickHeader(colRef[0]);
                    var sorters = store.getSorters();

                    expect(sorters.getCount()).toBe(1);
                    expect(sorters.first().getDirection()).toBe('ASC');

                    Ext.testHelper.showHeaderMenu(colRef[0]);

                    runs(function() {
                        var menu = colRef[0].activeMenu;

                        jasmine.fireMouseEvent(menu.items.getByKey('ascItem').el, 'click');
                        expect(sorters.first().getDirection()).toBe('ASC');
                    });
                });

                it("should not sort when configured with sortable false", function() {
                    makeGrid([{
                        dataIndex: 'field0',
                        sortable: false
                    }]);
                    clickHeader(colRef[0]);
                    expect(store.getSorters().getCount()).toBe(0);
                });

                it("should not sort when the grid is configured with sortableColumns: false", function() {
                    makeGrid([{
                        dataIndex: 'field0'
                    }], {
                        sortableColumns: false
                    });
                    clickHeader(colRef[0]);
                    expect(store.getSorters().getCount()).toBe(0);
                });
            });

            describe("grouped columns", function() {
                var baseCols;

                function createGrid(cols, stateful) {
                    if (grid) {
                        grid.destroy();
                        grid = null;
                    }

                    makeGrid(cols, {
                        renderTo: null,
                        stateful: stateful,
                        stateId: 'foo'
                    });
                }

                function getCol(id) {
                    return grid.down('#' + id);
                }

                describe('when stateful', function() {
                    var col;

                    beforeEach(function() {
                        new Ext.state.Provider();

                        makeGrid([{
                            itemId: 'main1',
                            columns: [{
                                itemId: 'child1'
                            }, {
                                itemId: 'child2'
                            }, {
                                itemId: 'child3'
                            }]
                        }, {
                            itemId: 'main2',
                            columns: [{
                                itemId: 'child4'
                            }, {
                                itemId: 'child5'
                            }, {
                                itemId: 'child6'
                            }]
                        }], {
                            stateful: true,
                            stateId: 'foo'
                        });
                    });

                    afterEach(function() {
                        Ext.state.Manager.getProvider().clear();
                        col = null;
                    });

                    it('should work when toggling visibility on the groups', function() {
                        // See EXTJS-11661.
                        col = grid.down('#main2');
                        col.hide();
                        // Trigger the bug.
                        grid.saveState();
                        col.show();

                        // Now, select one of the col's children and query its hidden state.
                        // Really, we can check anything here, b/c if the bug wasn't fixed then
                        // a TypeError would be thrown in Ext.view.TableLayout#setColumnWidths.
                        expect(grid.down('#child6').hidden).toBe(false);
                    });

                    it('should not show a previously hidden subheader when the visibility of its group header is toggled', function() {
                        var subheader = grid.down('#child4');

                        subheader.hide();
                        col = grid.down('#main2');
                        col.hide();
                        col.show();

                        expect(subheader.hidden).toBe(true);
                    });
                });

                describe("column visibility", function() {
                    var cells;

                    afterEach(function() {
                        cells = null;
                    });

                    describe("hiding/show during construction", function() {
                        it("should be able to show a column during construction", function() {
                            expect(function() {
                                makeGrid([{
                                    dataIndex: 'field1',
                                    hidden: true,
                                    listeners: {
                                        added: function(c) {
                                            c.show();
                                        }
                                    }
                                }]);
                            }).not.toThrow();
                            expect(grid.getVisibleColumnManager().getColumns()[0]).toBe(colRef[0]);
                        });

                        it("should be able to hide a column during construction", function() {
                            expect(function() {
                                makeGrid([{
                                    dataIndex: 'field1',
                                    listeners: {
                                        added: function(c) {
                                            c.hide();
                                        }
                                    }
                                }]);
                            }).not.toThrow();
                            expect(grid.getVisibleColumnManager().getColumns().length).toBe(0);
                        });
                    });

                    describe('when groupheader parent is hidden', function() {
                        describe('hidden at config time', function() {
                            beforeEach(function() {
                                makeGrid([{
                                    itemId: 'main1'
                                }, {
                                    itemId: 'main2',
                                    hidden: true,
                                    columns: [{
                                        itemId: 'child1'
                                    }, {
                                        itemId: 'child2'
                                    }]
                                }]);

                                cells = grid.view.body.query('.x-grid-row td');
                            });

                            it('should hide child columns at config time if the parent is hidden', function() {
                                expect(grid.down('#child1').getInherited().hidden).toBe(true);
                                expect(grid.down('#child2').getInherited().hidden).toBe(true);
                                // Check the view.
                                expect(cells.length).toBe(1);
                            });

                            it('should not explicitly hide any child columns (they will be hierarchically hidden)', function() {
                                expect(grid.down('#child1').hidden).toBe(false);
                                expect(grid.down('#child2').hidden).toBe(false);
                                // Check the view.
                                expect(cells.length).toBe(1);
                            });
                        });

                        describe('hidden at run time', function() {
                            beforeEach(function() {
                                makeGrid([{
                                    itemId: 'main1'
                                }, {
                                    itemId: 'main2',
                                    columns: [{
                                        itemId: 'child1'
                                    }, {
                                        itemId: 'child2'
                                    }]
                                }]);

                                grid.down('#main2').hide();
                                cells = grid.view.body.query('.x-grid-row td');
                            });

                            it('should hide child columns at runtime if the parent is hidden', function() {
                                expect(grid.down('#child1').getInherited().hidden).toBe(true);
                                expect(grid.down('#child2').getInherited().hidden).toBe(true);
                                // Check the view.
                                expect(cells.length).toBe(1);
                            });

                            it('should not explicitly hide any child columns (they will be hierarchically hidden)', function() {
                                expect(grid.down('#child1').hidden).toBe(false);
                                expect(grid.down('#child2').hidden).toBe(false);
                                // Check the view.
                                expect(cells.length).toBe(1);
                            });
                        });
                    });

                    describe('when groupheader parent is shown', function() {
                        describe('shown at config time', function() {
                            beforeEach(function() {
                                makeGrid([{
                                    itemId: 'main1'
                                }, {
                                    itemId: 'main2',
                                    columns: [{
                                        itemId: 'child1'
                                    }, {
                                        itemId: 'child2'
                                    }]
                                }]);

                                cells = grid.view.body.query('.x-grid-row td');
                            });

                            it('should not hide child columns at config time if the parent is shown', function() {
                                expect(grid.down('#child1').getInherited().hidden).not.toBeDefined();
                                expect(grid.down('#child2').getInherited().hidden).not.toBeDefined();
                                // Check the view.
                                expect(cells.length).toBe(3);
                            });

                            it('should not explicitly hide any child columns (they will be hierarchically shown)', function() {
                                expect(grid.down('#child1').hidden).toBe(false);
                                expect(grid.down('#child2').hidden).toBe(false);
                                // Check the view.
                                expect(cells.length).toBe(3);
                            });
                        });

                        describe('shown at run time', function() {
                            beforeEach(function() {
                                makeGrid([{
                                    itemId: 'main1'
                                }, {
                                    itemId: 'main2',
                                    hidden: true,
                                    columns: [{
                                        itemId: 'child1'
                                    }, {
                                        itemId: 'child2'
                                    }]
                                }]);

                                grid.down('#main2').show();
                                cells = grid.view.body.query('.x-grid-row td');
                            });

                            it('should show child columns at runtime if the parent is shown', function() {
                                expect(grid.down('#child1').getInherited().hidden).not.toBeDefined();
                                expect(grid.down('#child2').getInherited().hidden).not.toBeDefined();
                                // Check the view.
                                expect(cells.length).toBe(3);
                            });

                            it('should not explicitly hide any child columns (they will be hierarchically shown)', function() {
                                expect(grid.down('#child1').hidden).toBe(false);
                                expect(grid.down('#child2').hidden).toBe(false);
                                // Check the view.
                                expect(cells.length).toBe(3);
                            });
                        });
                    });

                    describe("hiding/showing children", function() {
                        beforeEach(function() {
                            baseCols = [{
                                itemId: 'col1',
                                columns: [{
                                    itemId: 'col11'
                                }, {
                                    itemId: 'col12'
                                }, {
                                    itemId: 'col13'
                                }]
                            }, {
                                itemId: 'col2',
                                columns: [{
                                    itemId: 'col21'
                                }, {
                                    itemId: 'col22'
                                }, {
                                    itemId: 'col23'
                                }]
                            }];
                        });

                        it('should not show a previously hidden subheader when the visibility of its group header is toggled', function() {
                            var subheader, col;

                            makeGrid([{
                                itemId: 'main1'
                            }, {
                                itemId: 'main2',
                                columns: [{
                                    itemId: 'child1'
                                }, {
                                    itemId: 'child2'
                                }]
                            }]);

                            subheader = grid.down('#child1');

                            subheader.hide();
                            col = grid.down('#main2');
                            col.hide();
                            col.show();

                            expect(subheader.hidden).toBe(true);
                        });

                        it('should allow any subheader to be reshown when all subheaders are currently hidden', function() {
                            // There was a bug where a subheader could not be reshown when itself and all of its fellows were curently hidden.
                            // See EXTJS-18515.
                            var subheader;

                            makeGrid([{
                                itemId: 'main1'
                            }, {
                                itemId: 'main2',
                                columns: [{
                                    itemId: 'child1'
                                }, {
                                    itemId: 'child2'
                                }, {
                                    itemId: 'child3'
                                }]
                            }]);

                            grid.down('#child1').hide();
                            grid.down('#child2').hide();
                            subheader = grid.down('#child3');

                            // Toggling would reveal the bug.
                            subheader.hide();
                            expect(subheader.hidden).toBe(true);
                            subheader.show();

                            expect(subheader.hidden).toBe(false);
                        });

                        it('should show the last hidden subheader if all subheaders are currently hidden when the group is reshown', function() {
                            var groupheader, subheader1, subheader2, subheader3;

                            makeGrid([{
                                itemId: 'main1'
                            }, {
                                itemId: 'main2',
                                columns: [{
                                    itemId: 'child1'
                                }, {
                                    itemId: 'child2'
                                }, {
                                    itemId: 'child3'
                                }]
                            }]);

                            groupheader = grid.down('#main2');
                            subheader1 = grid.down('#child1').hide();
                            subheader3 = grid.down('#child3').hide();
                            subheader2 = grid.down('#child2');
                            subheader2.hide();

                            expect(subheader2.hidden).toBe(true);

                            groupheader.show();

                            // The last hidden subheader should now be shown.
                            expect(subheader2.hidden).toBe(false);

                            // Let's also demonstrate that the others are still hidden.
                            expect(subheader1.hidden).toBe(true);
                            expect(subheader3.hidden).toBe(true);
                        });

                        describe("initial configuration", function() {
                            it("should not hide the parent by default", function() {
                                createGrid(baseCols);
                                expect(getCol('col1').hidden).toBe(false);
                            });

                            it("should not hide the parent if not all children are hidden", function() {
                                baseCols[1].columns[2].hidden = baseCols[1].columns[0].hidden = true;
                                createGrid(baseCols);
                                expect(getCol('col2').hidden).toBe(false);
                            });

                            it("should hide the parent if all children are hidden", function() {
                                baseCols[1].columns[2].hidden = baseCols[1].columns[1].hidden = baseCols[1].columns[0].hidden = true;
                                createGrid(baseCols);
                                expect(getCol('col2').hidden).toBe(true);
                            });
                        });

                        describe("before render", function() {
                            it("should hide the parent when hiding all children", function() {
                                createGrid(baseCols);
                                getCol('col21').hide();
                                getCol('col22').hide();
                                getCol('col23').hide();
                                grid.render(Ext.getBody());
                                expect(getCol('col2').hidden).toBe(true);
                            });

                            it("should show the parent when showing a hidden child", function() {
                                baseCols[1].columns[2].hidden = baseCols[1].columns[1].hidden = baseCols[1].columns[0].hidden = true;
                                createGrid(baseCols);
                                getCol('col22').show();
                                grid.render(Ext.getBody());
                                expect(getCol('col2').hidden).toBe(false);
                            });
                        });

                        describe("after render", function() {
                            it("should hide the parent when hiding all children", function() {
                                createGrid(baseCols);
                                grid.render(Ext.getBody());
                                getCol('col21').hide();
                                getCol('col22').hide();
                                getCol('col23').hide();
                                expect(getCol('col2').hidden).toBe(true);
                            });

                            it("should show the parent when showing a hidden child", function() {
                                baseCols[1].columns[2].hidden = baseCols[1].columns[1].hidden = baseCols[1].columns[0].hidden = true;
                                createGrid(baseCols);
                                grid.render(Ext.getBody());
                                getCol('col22').show();
                                expect(getCol('col2').hidden).toBe(false);
                            });

                            it("should only trigger a single layout when hiding the last leaf in a group", function() {
                                baseCols[0].columns.splice(1, 2);
                                createGrid(baseCols);
                                grid.render(Ext.getBody());
                                var count = grid.componentLayoutCounter;

                                getCol('col11').hide();
                                expect(grid.componentLayoutCounter).toBe(count + 1);
                            });

                            it("should only trigger a single refresh when hiding the last leaf in a group", function() {
                                baseCols[0].columns.splice(1, 2);
                                createGrid(baseCols);
                                grid.render(Ext.getBody());
                                var view = grid.getView(),
                                    count = view.refreshCounter;

                                getCol('col11').hide();
                                expect(view.refreshCounter).toBe(count + 1);
                            });
                        });

                        describe('nested stacked columns', function() {
                            // Test stacked group headers where the only child is the next group header in the hierarchy.
                            // The last (lowest in the stack) group header will contain multiple child items.
                            // For example:
                            //
                            //           +-----------------------------------+
                            //           |               col1                |
                            //           |-----------------------------------|
                            //           |               col2                |
                            //   other   |-----------------------------------|   other
                            //  headers  |               col3                |  headers
                            //           |-----------------------------------|
                            //           |               col4                |
                            //           |-----------------------------------|
                            //           | Field1 | Field2 | Field3 | Field4 |
                            //           |===================================|
                            //           |               view                |
                            //           +-----------------------------------+
                            //
                            function assertHiddenState(n, hiddenState) {
                                while (n) {
                                    expect(getCol('col' + n).hidden).toBe(hiddenState);
                                    --n;
                                }
                            }

                            describe('on hide', function() {
                                beforeEach(function() {
                                    baseCols = [{
                                        itemId: 'col1',
                                        columns: [{
                                            itemId: 'col2',
                                            columns: [{
                                                itemId: 'col3',
                                                columns: [{
                                                    itemId: 'col4',
                                                    columns: [{
                                                        itemId: 'col41'
                                                    }, {
                                                        itemId: 'col42'
                                                    }, {
                                                        itemId: 'col43'
                                                    }, {
                                                        itemId: 'col44'
                                                    }]
                                                }]
                                            }]
                                        }]
                                    }, {
                                        itemId: 'col5'
                                    }];
                                });

                                it('should hide every group header above the target group header', function() {
                                    createGrid(baseCols);
                                    getCol('col4').hide();
                                    assertHiddenState(4, true);
                                    tearDown();

                                    setup();
                                    createGrid(baseCols);
                                    getCol('col3').hide();
                                    assertHiddenState(3, true);
                                    tearDown();

                                    setup();
                                    createGrid(baseCols);
                                    getCol('col2').hide();
                                    assertHiddenState(2, true);
                                });

                                it('should reshow every group header above the target group header when toggled', function() {
                                    createGrid(baseCols);
                                    getCol('col4').hide();
                                    assertHiddenState(4, true);
                                    getCol('col4').show();
                                    assertHiddenState(4, false);
                                    tearDown();

                                    setup();
                                    createGrid(baseCols);
                                    getCol('col3').hide();
                                    assertHiddenState(3, true);
                                    getCol('col3').show();
                                    assertHiddenState(3, false);
                                    tearDown();

                                    setup();
                                    createGrid(baseCols);
                                    getCol('col2').hide();
                                    assertHiddenState(2, true);
                                    getCol('col2').show();
                                    assertHiddenState(2, false);
                                });

                                describe('subheaders', function() {
                                    it('should hide all ancestor group headers when hiding all subheaders in lowest group header', function() {
                                        createGrid(baseCols);
                                        getCol('col41').hide();
                                        getCol('col42').hide();
                                        getCol('col43').hide();
                                        getCol('col44').hide();
                                        assertHiddenState(4, true);
                                    });
                                });
                            });

                            describe('on show', function() {
                                beforeEach(function() {
                                    baseCols = [{
                                        itemId: 'col1',
                                        hidden: true,
                                        columns: [{
                                            itemId: 'col2',
                                            hidden: true,
                                            columns: [{
                                                itemId: 'col3',
                                                hidden: true,
                                                columns: [{
                                                    itemId: 'col4',
                                                    hidden: true,
                                                    columns: [{
                                                        itemId: 'col41'
                                                    }, {
                                                        itemId: 'col42'
                                                    }, {
                                                        itemId: 'col43'
                                                    }, {
                                                        itemId: 'col44'
                                                    }]
                                                }]
                                            }]
                                        }]
                                    }, {
                                        itemId: 'col5'
                                    }];
                                });

                                it('should show every group header above the target group header', function() {
                                    // Here we're showing that a header that is explicitly shown will have every header
                                    // above it shown as well.
                                    createGrid(baseCols);
                                    getCol('col4').show();
                                    assertHiddenState(4, false);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col3').show();
                                    assertHiddenState(3, false);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col2').show();
                                    assertHiddenState(2, false);
                                });

                                it('should show every group header in the chain no matter which group header is checked', function() {
                                    // Here we're showing that a header that is explicitly shown will have every header
                                    // in the chain shown, no matter which group header was clicked.
                                    //
                                    // Group headers are special in that they are auto-hidden when their subheaders are all
                                    // hidden and auto-shown when the first subheader is reshown. They are the only headers
                                    // that should now be auto-shown or -hidden.
                                    //
                                    // It follows that since group headers are dictated by some automation depending upon the
                                    // state of their child items that all group headers should be shown if anyone in the
                                    // hierarchy is shown since these special group headers only contain one child, which is
                                    // the next group header in the stack.
                                    createGrid(baseCols);
                                    getCol('col4').show();
                                    assertHiddenState(4, false);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col3').show();
                                    assertHiddenState(4, false);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col2').show();
                                    assertHiddenState(4, false);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col1').show();
                                    assertHiddenState(4, false);
                                });

                                it('should rehide every group header above the target group header when toggled', function() {
                                    createGrid(baseCols);
                                    getCol('col4').show();
                                    assertHiddenState(4, false);
                                    getCol('col4').hide();
                                    assertHiddenState(4, true);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col3').show();
                                    assertHiddenState(3, false);
                                    getCol('col3').hide();
                                    assertHiddenState(3, true);

                                    tearDown();
                                    setup();
                                    createGrid(baseCols);
                                    getCol('col2').show();
                                    assertHiddenState(2, false);
                                    getCol('col2').hide();
                                    assertHiddenState(2, true);
                                });

                                describe('subheaders', function() {
                                    it('should not show any ancestor group headers when hiding all subheaders in lowest group header', function() {
                                        createGrid(baseCols);
                                        getCol('col41').hide();
                                        getCol('col42').hide();
                                        getCol('col43').hide();
                                        getCol('col44').hide();
                                        assertHiddenState(4, true);
                                    });

                                    it('should show all ancestor group headers when hiding all subheaders in lowest group header and then showing one', function() {
                                        createGrid(baseCols);
                                        getCol('col41').hide();
                                        getCol('col42').hide();
                                        getCol('col43').hide();
                                        getCol('col44').hide();
                                        assertHiddenState(4, true);
                                        getCol('col42').show();
                                        assertHiddenState(4, false);
                                    });

                                    it('should remember which subheader was last checked and restore its state when its group header is rechecked', function() {
                                        var col, subheader, headerCt;

                                        // Let's hide the 3rd menu item.
                                        makeGrid(baseCols);
                                        col = getCol('col4');
                                        subheader = getCol('col43');
                                        headerCt = grid.headerCt;

                                        getCol('col41').hide();
                                        getCol('col42').hide();
                                        getCol('col44').hide();
                                        subheader.hide();

                                        expect(col.hidden).toBe(true);
                                        // Get the menu item.
                                        headerCt.getMenuItemForHeader(headerCt.menu, col).setChecked(true);
                                        expect(subheader.hidden).toBe(false);

                                        // Now let's hide the 2nd menu item.
                                        tearDown();
                                        setup();
                                        makeGrid(baseCols);
                                        col = getCol('col4');
                                        subheader = getCol('col42');
                                        headerCt = grid.headerCt;

                                        getCol('col41').hide();
                                        getCol('col43').hide();
                                        getCol('col44').hide();
                                        subheader.hide();

                                        expect(col.hidden).toBe(true);
                                        // Get the menu item.
                                        headerCt.getMenuItemForHeader(headerCt.menu, col).setChecked(true);
                                        expect(subheader.hidden).toBe(false);
                                    });

                                    it('should only show visible subheaders when all group headers are shown', function() {
                                        var col;

                                        createGrid(baseCols);
                                        col = getCol('col4');

                                        // All subheaders are visible.
                                        col.show();
                                        expect(col.visibleColumnManager.getColumns().length).toBe(4);

                                        // Hide the group header and hide two subheaders.
                                        col.hide();
                                        getCol('col42').hide();
                                        getCol('col43').hide();

                                        // Only two subheaders should now be visible.
                                        col.show();
                                        expect(col.visibleColumnManager.getColumns().length).toBe(2);
                                    });
                                });
                            });
                        });
                    });

                    describe("adding/removing children", function() {
                        beforeEach(function() {
                            baseCols = [{
                                itemId: 'col1',
                                columns: [{
                                    itemId: 'col11'
                                }, {
                                    itemId: 'col12'
                                }, {
                                    itemId: 'col13'
                                }]
                            }, {
                                itemId: 'col2',
                                columns: [{
                                    itemId: 'col21'
                                }, {
                                    itemId: 'col22'
                                }, {
                                    itemId: 'col23'
                                }]
                            }];
                        });

                        describe("before render", function() {
                            it("should hide the parent if removing the last hidden item", function() {
                                baseCols[0].columns[0].hidden = baseCols[0].columns[1].hidden = true;
                                createGrid(baseCols);
                                getCol('col13').destroy();
                                grid.render(Ext.getBody());
                                expect(getCol('col1').hidden).toBe(true);
                            });

                            it("should show the parent if adding a visible item and all items are hidden", function() {
                                baseCols[0].columns[0].hidden = baseCols[0].columns[1].hidden = baseCols[0].columns[2].hidden = true;
                                createGrid(baseCols);
                                getCol('col1').add({
                                    itemId: 'col14'
                                });
                                grid.render(Ext.getBody());
                                expect(getCol('col1').hidden).toBe(false);
                            });
                        });

                        describe("after render", function() {
                            it("should hide the parent if removing the last hidden item", function() {
                                baseCols[0].columns[0].hidden = baseCols[0].columns[1].hidden = true;
                                createGrid(baseCols);
                                grid.render(Ext.getBody());
                                getCol('col13').destroy();
                                expect(getCol('col1').hidden).toBe(true);
                            });

                            it("should show the parent if adding a visible item and all items are hidden", function() {
                                baseCols[0].columns[0].hidden = baseCols[0].columns[1].hidden = baseCols[0].columns[2].hidden = true;
                                createGrid(baseCols);
                                grid.render(Ext.getBody());
                                getCol('col1').add({
                                    itemId: 'col14'
                                });
                                expect(getCol('col1').hidden).toBe(false);
                            });
                        });
                    });
                });

                describe("removing columns from group", function() {
                    beforeEach(function() {
                        baseCols = [{
                            itemId: 'col1',
                            columns: [{
                                itemId: 'col11'
                            }, {
                                itemId: 'col12'
                            }, {
                                itemId: 'col13'
                            }]
                        }, {
                            itemId: 'col2',
                            columns: [{
                                itemId: 'col21'
                            }, {
                                itemId: 'col22'
                            }, {
                                itemId: 'col23'
                            }]
                        }];

                        createGrid(baseCols);
                    });

                    describe("before render", function() {
                        it("should destroy the group header when removing all columns", function() {
                            var headerCt = grid.headerCt,
                                col2 = getCol('col2');

                            expect(headerCt.items.indexOf(col2)).toBe(1);
                            getCol('col21').destroy();
                            getCol('col22').destroy();
                            getCol('col23').destroy();
                            expect(col2.destroyed).toBe(true);
                            expect(headerCt.items.indexOf(col2)).toBe(-1);
                        });
                    });

                    describe("after render", function() {
                        it("should destroy the group header when removing all columns", function() {
                            createGrid(baseCols);
                            grid.render(Ext.getBody());

                            var headerCt = grid.headerCt,
                                col2 = getCol('col2');

                            expect(headerCt.items.indexOf(col2)).toBe(1);
                            getCol('col21').destroy();
                            getCol('col22').destroy();
                            getCol('col23').destroy();
                            expect(col2.destroyed).toBe(true);
                            expect(headerCt.items.indexOf(col2)).toBe(-1);
                        });
                    });
                });
            });

            describe("column operations & the view", function() {
                describe('', function() {
                    beforeEach(function() {
                        makeGrid();
                    });

                    it("should update the view when adding a new header", function() {
                        grid.headerCt.insert(0, {
                            dataIndex: 'field4'
                        });
                        expect(getCellText(0, 0)).toBe('val5');
                    });

                    it("should update the view when moving an existing header", function() {
                        grid.headerCt.insert(0, colRef[1]);
                        expect(getCellText(0, 0)).toBe('val2');
                    });

                    it("should update the view when removing a header", function() {
                        grid.headerCt.remove(1);
                        expect(getCellText(0, 1)).toBe('val3');
                    });

                    it("should not refresh the view when doing a drag/drop move", function() {
                        var called = false,
                            header;

                        grid.getView().on('refresh', function() {
                            called = true;
                        });

                        // Simulate a DD here
                        header = colRef[0];
                        grid.headerCt.move(0, 3);
                        expect(getCellText(0, 3)).toBe('val1');
                        expect(called).toBe(false);
                    });
                });

                describe('toggling column visibility', function() {
                    var refreshCounter;

                    beforeEach(function() {
                        makeGrid();
                        refreshCounter = view.refreshCounter;
                    });

                    afterEach(function() {
                        refreshCounter = null;
                    });

                    describe('hiding', function() {
                        it('should update the view', function() {
                            colRef[0].hide();

                            expect(view.refreshCounter).toBe(refreshCounter + 1);
                        });
                    });

                    describe('showing', function() {
                        it('should update the view', function() {
                            colRef[0].hide();
                            refreshCounter = view.refreshCounter;
                            colRef[0].show();

                            expect(view.refreshCounter).toBe(refreshCounter + 1);
                        });
                    });
                });
            });

            describe("locked/normal grid visibility", function() {
                function expectVisible(locked, normal) {
                    expect(grid.lockedGrid.isVisible()).toBe(locked);
                    expect(grid.normalGrid.isVisible()).toBe(normal);
                }

                var failCount;

                beforeEach(function() {
                    failCount = Ext.failedLayouts;
                });

                afterEach(function() {
                    expect(failCount).toBe(Ext.failedLayouts);
                    failCount = null;
                });

                describe("initial", function() {
                    it("should have both sides visible", function() {
                        makeGrid([{ locked: true }, {}], {
                            syncTaskDelay: 0
                        });
                        expectVisible(true, true);
                    });

                    it("should have only the normal side visible if there are no locked columns", function() {
                        makeGrid([{}, {}], {
                            enableLocking: true,
                            syncTaskDelay: 0
                        });
                        expectVisible(false, true);
                    });

                    it("should have only the locked side visible if there are no normal columns", function() {
                        makeGrid([{ locked: true }, { locked: true }], {
                            syncTaskDelay: 0
                        });
                        expectVisible(true, false);
                    });
                });

                describe("dynamic", function() {
                    beforeEach(function() {
                        makeGrid([{
                            locked: true,
                            itemId: 'col0'
                        }, {
                            locked: true,
                            itemId: 'col1'
                        }, {
                            itemId: 'col2'
                        }, {
                            itemId: 'col3'
                        }], {
                            syncTaskDelay: 0
                        });
                    });

                    describe("normal side", function() {
                        it("should not hide when removing a column but there are other normal columns", function() {
                            grid.normalGrid.headerCt.remove('col2');
                            expectVisible(true, true);
                        });

                        it("should hide when removing the last normal column", function() {
                            grid.normalGrid.headerCt.remove('col2');
                            grid.normalGrid.headerCt.remove('col3');
                            expectVisible(true, false);
                        });

                        it("should not hide when hiding a column but there are other visible normal columns", function() {
                            colRef[2].hide();
                            expectVisible(true, true);
                        });

                        it("should hide when hiding the last normal column", function() {
                            colRef[2].hide();
                            colRef[3].hide();
                            expectVisible(true, false);
                        });
                    });

                    describe("locked side", function() {
                        it("should not hide when removing a column but there are other locked columns", function() {
                            grid.lockedGrid.headerCt.remove('col0');
                            expectVisible(true, true);
                        });

                        it("should hide when removing the last locked column", function() {
                            grid.lockedGrid.headerCt.remove('col0');
                            grid.lockedGrid.headerCt.remove('col1');
                            expectVisible(false, true);
                        });

                        it("should not hide when hiding a column but there are other visible locked columns", function() {
                            colRef[0].hide();
                            expectVisible(true, true);
                        });

                        it("should hide when hiding the last locked column", function() {
                            colRef[0].hide();
                            colRef[1].hide();
                            expectVisible(false, true);
                        });
                    });
                });
            });

            describe("rendering", function() {
                beforeEach(function() {
                    makeGrid();
                });

                describe("first/last", function() {
                    it("should stamp x-grid-cell-first on the first column cell", function() {
                        var cls = grid.getView().firstCls;

                        expect(hasCls(getCell(0, 0), cls)).toBe(true);
                        expect(hasCls(getCell(0, 1), cls)).toBe(false);
                        expect(hasCls(getCell(0, 2), cls)).toBe(false);
                        expect(hasCls(getCell(0, 3), cls)).toBe(false);
                    });

                    it("should stamp x-grid-cell-last on the last column cell", function() {
                        var cls = grid.getView().lastCls;

                        expect(hasCls(getCell(0, 0), cls)).toBe(false);
                        expect(hasCls(getCell(0, 1), cls)).toBe(false);
                        expect(hasCls(getCell(0, 2), cls)).toBe(false);
                        expect(hasCls(getCell(0, 3), cls)).toBe(true);
                    });

                    it("should update the first class when moving the first column", function() {
                        grid.headerCt.insert(0, colRef[1]);

                        var cell = getCell(0, 0),
                            view = grid.getView(),
                            cls = view.firstCls;

                        expect(getCellText(0, 0)).toBe('val2');
                        expect(hasCls(cell, cls)).toBe(true);
                        expect(hasCls(getCell(0, 1), cls)).toBe(false);
                    });

                    it("should update the last class when moving the last column", function() {
                        // Suppress console warning about reusing existing id
                        spyOn(Ext.log, 'warn');

                        grid.headerCt.add(colRef[1]);

                        var cell = getCell(0, 3),
                            view = grid.getView(),
                            cls = view.lastCls;

                        expect(getCellText(0, 3)).toBe('val2');
                        expect(hasCls(cell, cls)).toBe(true);
                        expect(hasCls(getCell(0, 2), cls)).toBe(false);
                    });
                });

                describe("id", function() {
                    it("should stamp the id of the column in the cell", function() {
                        expect(hasCls(getCell(0, 0), 'x-grid-cell-col0')).toBe(true);
                        expect(hasCls(getCell(0, 1), 'x-grid-cell-col1')).toBe(true);
                        expect(hasCls(getCell(0, 2), 'x-grid-cell-col2')).toBe(true);
                        expect(hasCls(getCell(0, 3), 'x-grid-cell-col3')).toBe(true);
                    });
                });
            });

            describe("hiddenHeaders", function() {
                it("should lay out the hidden items so cells obtain correct width", function() {
                    makeGrid([{
                        width: 100
                    }, {
                        flex: 1
                    }, {
                        width: 200
                    }], {
                        hiddenHeaders: true
                    });

                    expect(getCell(0, 0).getWidth()).toBe(100);
                    expect(getCell(0, 1).getWidth()).toBe(totalWidth - 200 - 100);
                    expect(getCell(0, 2).getWidth()).toBe(200);
                });

                it("should lay out grouped column headers", function() {
                    makeGrid([{
                        width: 100
                    }, {
                        columns: [{
                            width: 200
                        }, {
                            width: 400
                        }, {
                            width: 100
                        }]
                    }, {
                        width: 200
                    }], {
                        hiddenHeaders: true
                    });
                    expect(getCell(0, 0).getWidth()).toBe(100);
                    expect(getCell(0, 1).getWidth()).toBe(200);
                    expect(getCell(0, 2).getWidth()).toBe(400);
                    expect(getCell(0, 3).getWidth()).toBe(100);
                    expect(getCell(0, 4).getWidth()).toBe(200);
                });
            });

            describe("emptyCellText config", function() {
                function expectEmptyText(column, rowIdx, colIdx) {
                    var cell = getCellInner(rowIdx, colIdx),
                        el = document.createElement('div');

                    // We're doing this because ' ' !== '&#160;'. By letting the browser decode the entity, we
                    // can then do a comparison.
                    el.innerHTML = column.emptyCellText;

                    expect(cell.textContent || cell.innerText).toBe(el.textContent || el.innerText);
                }

                describe("rendering", function() {
                    beforeEach(function() {
                        makeGrid([{
                            width: 100
                        }, {
                            emptyCellText: 'derp',
                            width: 200
                        }]);
                    });

                    it("should use the default html entity for when there is no emptyCellText given", function() {
                        expectEmptyText(colRef[0], 0, 0);
                    });

                    it("should use the value of emptyCellText when configured", function() {
                        expectEmptyText(colRef[1], 0, 1);
                    });
                });

                describe("empty values", function() {
                    function makeEmptySuite(val, label) {
                        it("should render " + label + " as empty", function() {
                            makeGrid(null, {
                                renderTo: null
                            });
                            store.getAt(0).set('field0', val);
                            grid.render(Ext.getBody());
                            expectEmptyText(colRef[0], 0, 0);
                        });
                    }

                    makeEmptySuite(undefined, 'undefined');
                    makeEmptySuite(null, 'null');
                    makeEmptySuite('', 'empty string');
                    makeEmptySuite([], 'empty array');
                });

                describe("column update", function() {
                    describe("full row update", function() {
                        it("should use the empty text on update", function() {
                            makeGrid([{
                                width: 100,
                                dataIndex: 'field0',
                                renderer: function(v, meta, rec) {
                                    return v;
                                }
                            }]);
                            // Renderer with >1 arg requires a full row redraw
                            store.getAt(0).set('field0', '');
                            expectEmptyText(colRef[0], 0, 0);
                        });
                    });

                    describe("cell update only", function() {
                        describe("producesHTML: true", function() {
                            it("should use the empty text on update", function() {
                                makeGrid([{
                                    width: 100,
                                    producesHTML: true,
                                    dataIndex: 'field0'
                                }]);
                                store.getAt(0).set('field0', '');
                                expectEmptyText(colRef[0], 0, 0);
                            });

                            it("should use the empty text on update with a simple renderer", function() {
                                makeGrid([{
                                    width: 100,
                                    producesHTML: true,
                                    dataIndex: 'field0',
                                    renderer: Ext.identityFn
                                }]);
                                store.getAt(0).set('field0', '');
                                expectEmptyText(colRef[0], 0, 0);
                            });

                            it("should not merge classes when changing tdStyle", function() {
                                var testEl = Ext.getBody().createChild({
                                        style: 'text-decoration: underline'
                                    }),

                                    // We need implementation-specific style string
                                    underlineStyle = Ext.fly(testEl).getStyle('text-decoration'),
                                    cell;

                                testEl.destroy();

                                makeGrid([{
                                    width: 100,
                                    dataIndex: 'field0',
                                    renderer: function(value, metaData, record) {
                                        if (!record.get('foo')) {
                                            metaData.tdStyle = 'background-color: red;';
                                        }
                                        else {
                                            metaData.tdStyle = 'text-decoration: underline;';
                                        }

                                        return value;
                                    }
                                }]);

                                cell = Ext.fly(grid.view.body.dom.querySelector('.x-grid-cell'));

                                // Edge returns rgb(255, 0, 0) here :(
                                var style = cell.getStyle('background-color');

                                if (style === 'rgb(255, 0, 0)') {
                                    style = 'red';
                                }

                                expect(style).toBe('red');

                                store.getAt(0).set('foo', true);

                                style = cell.getStyle('background-color');

                                if (style === 'rgb(255, 0, 0)') {
                                    style = 'red';
                                }

                                expect(style).not.toBe('red');
                                expect(cell.getStyle('text-decoration')).toBe(underlineStyle);
                            });
                        });

                        describe("producesHTML: false", function() {
                            it("should use the empty text on update", function() {
                                makeGrid([{
                                    width: 100,
                                    producesHTML: false,
                                    dataIndex: 'field0'
                                }]);
                                store.getAt(0).set('field0', '');
                                expectEmptyText(colRef[0], 0, 0);
                            });

                            it("should use the empty text on update with a simple renderer", function() {
                                makeGrid([{
                                    width: 100,
                                    producesHTML: false,
                                    dataIndex: 'field0',
                                    renderer: Ext.identityFn
                                }]);
                                store.getAt(0).set('field0', '');
                                expectEmptyText(colRef[0], 0, 0);
                            });
                        });
                    });
                });
            });

            describe("non-column items in the header", function() {
                it("should show non-columns as children", function() {
                    makeGrid([{
                        width: 100,
                        items: {
                            xtype: 'textfield',
                            itemId: 'foo'
                        }
                    }]);
                    expect(grid.down('#foo').isVisible(true)).toBe(true);
                });

                it("should have the hidden item as visible after showing an initially hidden column", function() {
                    makeGrid([{
                        width: 100,
                        items: {
                            xtype: 'textfield'
                        }
                    }, {
                        width: 100,
                        hidden: true,
                        items: {
                            xtype: 'textfield',
                            itemId: 'foo'
                        }
                    }]);
                    var field = grid.down('#foo');

                    expect(field.isVisible(true)).toBe(false);
                    field.ownerCt.show();
                    expect(field.isVisible(true)).toBe(true);
                });
            });

            describe("reconfiguring", function() {
                it("should destroy any old columns", function() {
                    var o = {};

                    makeGrid(4);
                    Ext.Array.forEach(colRef, function(col) {
                        col.on('destroy', function(c) {
                            o[col.getItemId()] = true;
                        });
                    });
                    grid.reconfigure(null, []);
                    expect(o).toEqual({
                        col0: true,
                        col1: true,
                        col2: true,
                        col3: true
                    });
                });

                describe("with locking", function() {
                    it("should resize the locked part to match the grid size", function() {
                        makeGrid(4, null, null, function(i) {
                            return i === 0;
                        });

                        // Default column width
                        expect(grid.lockedGrid.getWidth()).toBe(100 + grid.lockedGrid.gridPanelBorderWidth);
                        grid.reconfigure(null, [{
                            locked: true,
                            width: 120
                        }, {
                            locked: true,
                            width: 170
                        }, {}, {}]);
                        expect(grid.lockedGrid.getWidth()).toBe(120 + 170 + grid.lockedGrid.gridPanelBorderWidth);
                    });
                });
            });

            describe('column header borders', function() {
                it('should show header borders by default, and turn them off dynamically', function() {
                    makeGrid();
                    expect(colRef[0].el.getBorderWidth('r')).toBe(1);
                    expect(colRef[1].el.getBorderWidth('r')).toBe(1);
                    expect(colRef[2].el.getBorderWidth('r')).toBe(1);
                    grid.setHeaderBorders(false);
                    expect(colRef[0].el.getBorderWidth('r')).toBe(0);
                    expect(colRef[1].el.getBorderWidth('r')).toBe(0);
                    expect(colRef[2].el.getBorderWidth('r')).toBe(0);
                });
                it('should have no borders if configured false, and should show them dynamically', function() {
                    makeGrid(null, {
                        headerBorders: false
                    });
                    expect(colRef[0].el.getBorderWidth('r')).toBe(0);
                    expect(colRef[1].el.getBorderWidth('r')).toBe(0);
                    expect(colRef[2].el.getBorderWidth('r')).toBe(0);
                    grid.setHeaderBorders(true);
                    expect(colRef[0].el.getBorderWidth('r')).toBe(1);
                    expect(colRef[1].el.getBorderWidth('r')).toBe(1);
                    expect(colRef[2].el.getBorderWidth('r')).toBe(1);
                });
            });

            describe('column resize', function() {
                it('should not fire drag events on headercontainer during resize', function() {
                    makeGrid();
                    var colWidth = colRef[0].getWidth(),
                        dragSpy = spyOnEvent(grid.headerCt.el, 'drag');

                    resizeColumn(colRef[0], 10);
                    expect(colRef[0].getWidth()).toBe(colWidth + 10);
                    expect(dragSpy).not.toHaveBeenCalled();
                });
            });

            describe("auto hiding headers", function() {
                // Unit test setup defines hideHeaders as false in setup
                // because many tests lazily use empty headers and 
                // contain layout measurement which assumes visible headers.
                // Undo that interference for this test case.
                beforeEach(function() {
                    delete Ext.grid.Panel.prototype.config.hideHeaders;
                });
                afterEach(function() {
                    Ext.grid.Panel.prototype.config.hideHeaders = false;
                });

                function isHidden(theGrid) {
                    theGrid = theGrid || grid;

                    return theGrid.hasCls(theGrid.hiddenHeaderCls);
                }

                describe("not locked grid", function() {
                    describe("with a configured value", function() {
                        describe("at construction time", function() {
                            it("should hide the columns even if the columns have text", function() {
                                makeGrid([{
                                    text: 'Foo'
                                }], {
                                    hideHeaders: true
                                });
                                expect(isHidden()).toBe(true);
                            });

                            it("should not hide the columns even if the columns have no text", function() {
                                makeGrid([{
                                    text: ''
                                }], {
                                    hideHeaders: false
                                });
                                expect(isHidden()).toBe(false);
                            });
                        });

                        describe("dynamic", function() {
                            it("should be able to toggle from hidden to visible", function() {
                                makeGrid([{
                                    text: 'Foo'
                                }], {
                                    hideHeaders: true
                                });
                                grid.setHideHeaders(false);
                                expect(isHidden()).toBe(false);
                            });

                            it("should be able to toggle from visible to hidden", function() {
                                makeGrid([{
                                    text: ''
                                }], {
                                    hideHeaders: false
                                });
                                grid.setHideHeaders(true);
                                expect(isHidden()).toBe(true);
                            });

                            it("should compute to hidden", function() {
                                makeGrid([{
                                    text: ''
                                }], {
                                    hideHeaders: false
                                });
                                grid.setHideHeaders(null);
                                expect(isHidden()).toBe(true);
                            });

                            it("should compute to visible", function() {
                                makeGrid([{
                                    text: 'Foo'
                                }], {
                                    hideHeaders: true
                                });
                                grid.setHideHeaders(null);
                                expect(isHidden()).toBe(false);
                            });
                        });
                    });

                    describe("with no configured value", function() {
                        describe("at construction time", function() {
                            it("should hide headers when none of the headers have text", function() {
                                makeGrid([{}, {}, {}, {}]);
                                expect(isHidden()).toBe(true);
                            });

                            it("should not hide headers if any header has text", function() {
                                makeGrid([{}, {}, {
                                    text: 'Foo'
                                }, {}]);
                                expect(isHidden()).toBe(false);
                            });

                            it("should not hide an empty header when it's the only sibling when it's not a top level header", function() {
                                makeGrid([{
                                    text: 'main'
                                }, {
                                    text: 'group',
                                    columns: [{
                                        itemId: 'empty-header'
                                    }]
                                }]);

                                var h = grid.down('#empty-header');

                                // Text should be non-empty, so same line-height
                                expect(h.textContainerEl.dom.offsetHeight).toBe(h.ownerCt.textContainerEl.dom.offsetHeight);
                            });
                        });

                        describe("dynamic", function() {
                            it("should be able to hide headers", function() {
                                makeGrid([{}, {}, {
                                    text: 'Foo'
                                }, {}]);
                                grid.setHideHeaders(true);
                                expect(isHidden()).toBe(true);
                            });

                            it("should be able to show headers", function() {
                                makeGrid([{}, {}, {}, {}]);
                                grid.setHideHeaders(true);
                                expect(isHidden()).toBe(true);
                            });
                        });
                    });

                    describe("setting column text", function() {
                        it("should compute to hidden", function() {
                            makeGrid([{
                                text: 'Foo'
                            }, {
                                text: ''
                            }]);
                            colRef[0].setText('');
                            expect(isHidden()).toBe(true);
                        });

                        it("should compute to visible", function() {
                            makeGrid([{
                                text: ''
                            }, {
                                text: ''
                            }]);
                            colRef[0].setText('Foo');
                            expect(isHidden()).toBe(false);
                        });
                    });
                });

                describe("locked grid", function() {
                    describe("with a configured value", function() {
                        describe("at construction time", function() {
                            it("should hide the columns even if the columns have text", function() {
                                makeGrid([{
                                    text: 'Foo',
                                    locked: true
                                }, {
                                    text: 'Bar'
                                }], {
                                    hideHeaders: true
                                });
                                expect(isHidden(grid.lockedGrid)).toBe(true);
                                expect(isHidden(grid.normalGrid)).toBe(true);
                            });

                            it("should not hide the columns even if the columns have no text", function() {
                                makeGrid([{
                                    text: '',
                                    locked: true
                                }, {
                                    text: ''
                                }], {
                                    hideHeaders: false
                                });
                                expect(isHidden()).toBe(false);
                            });
                        });

                        describe("dynamic", function() {
                            it("should be able to toggle from hidden to visible", function() {
                                makeGrid([{
                                    text: 'Foo',
                                    locked: true
                                }, {
                                    text: 'Bar'
                                }], {
                                    hideHeaders: true
                                });
                                grid.setHideHeaders(false);
                                expect(isHidden(grid.lockedGrid)).toBe(false);
                                expect(isHidden(grid.normalGrid)).toBe(false);
                            });

                            it("should be able to toggle from visible to hidden", function() {
                                makeGrid([{
                                    text: '',
                                    locked: true
                                }, {
                                    text: ''
                                }], {
                                    hideHeaders: false
                                });
                                grid.setHideHeaders(true);
                                expect(isHidden(grid.lockedGrid)).toBe(true);
                                expect(isHidden(grid.normalGrid)).toBe(true);
                            });

                            it("should compute to hidden", function() {
                                makeGrid([{
                                    locked: true,
                                    text: ''
                                }, {
                                    text: ''
                                }], {
                                    hideHeaders: false
                                });
                                grid.setHideHeaders(null);
                                expect(isHidden(grid.lockedGrid)).toBe(true);
                                expect(isHidden(grid.normalGrid)).toBe(true);
                            });

                            it("should compute to visible", function() {
                                makeGrid([{
                                    locked: true,
                                    text: 'Foo'
                                }, {
                                    text: 'Bar'
                                }], {
                                    hideHeaders: true
                                });
                                grid.setHideHeaders(null);
                                expect(isHidden(grid.lockedGrid)).toBe(false);
                                expect(isHidden(grid.normalGrid)).toBe(false);
                            });
                        });
                    });

                    describe("with no configured value", function() {
                        describe("at construction time", function() {
                            it("should hide the headers when no columns have text", function() {
                                makeGrid([{
                                    locked: true
                                }, {}]);
                                expect(isHidden(grid.lockedGrid)).toBe(true);
                                expect(isHidden(grid.normalGrid)).toBe(true);
                            });

                            it("should not hide headers when the locked side has header text", function() {
                                makeGrid([{
                                    text: 'Foo',
                                    locked: true
                                }, {}]);
                                expect(isHidden(grid.lockedGrid)).toBe(false);
                                expect(isHidden(grid.normalGrid)).toBe(false);
                            });

                            it("should not hide headers when the normal side has header text", function() {
                                makeGrid([{
                                    locked: true
                                }, {
                                    text: 'Foo'
                                }]);
                                expect(isHidden(grid.lockedGrid)).toBe(false);
                                expect(isHidden(grid.normalGrid)).toBe(false);
                            });
                        });

                        describe("dynamic", function() {
                            it("should be able to hide headers", function() {
                                makeGrid([{
                                    locked: true,
                                    text: 'Foo'
                                }, {
                                    text: 'Bar'
                                }]);
                                grid.setHideHeaders(true);
                                expect(isHidden(grid.lockedGrid)).toBe(true);
                                expect(isHidden(grid.normalGrid)).toBe(true);
                            });

                            it("should be able to show headers", function() {
                                makeGrid([{
                                    locked: true,
                                    text: ''
                                }, {
                                    text: ''
                                }]);
                                grid.setHideHeaders(false);
                                expect(isHidden(grid.lockedGrid)).toBe(false);
                                expect(isHidden(grid.normalGrid)).toBe(false);
                            });
                        });
                    });

                    describe("setting column text", function() {
                        it("should compute to hidden when setting the locked column", function() {
                            makeGrid([{
                                text: 'Foo',
                                locked: true
                            }, {
                                text: ''
                            }]);
                            colRef[0].setText('');
                            expect(isHidden(grid.lockedGrid)).toBe(true);
                            expect(isHidden(grid.normalGrid)).toBe(true);
                        });

                        it("should compute to hidden when setting the unlocked column", function() {
                            makeGrid([{
                                text: '',
                                locked: true
                            }, {
                                text: 'Foo'
                            }]);
                            colRef[1].setText('');
                            expect(isHidden(grid.lockedGrid)).toBe(true);
                            expect(isHidden(grid.normalGrid)).toBe(true);
                        });

                        it("should compute to visible when setting the locked column", function() {
                            makeGrid([{
                                text: '',
                                locked: true
                            }, {
                                text: ''
                            }]);
                            colRef[0].setText('Foo');
                            expect(isHidden(grid.lockedGrid)).toBe(false);
                            expect(isHidden(grid.normalGrid)).toBe(false);
                        });

                        it("should compute to visible when setting the unlocked column", function() {
                            makeGrid([{
                                text: '',
                                locked: true
                            }, {
                                text: ''
                            }]);
                            colRef[1].setText('Foo');
                            expect(isHidden(grid.lockedGrid)).toBe(false);
                            expect(isHidden(grid.normalGrid)).toBe(false);
                        });
                    });
                });
            });
        });
    }

    createSuite(false);
    createSuite(true);
});
