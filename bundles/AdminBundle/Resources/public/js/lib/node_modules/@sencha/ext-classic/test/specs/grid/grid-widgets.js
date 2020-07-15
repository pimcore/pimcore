topSuite("grid-widgets",
    [false, 'Ext.grid.Panel', 'Ext.grid.column.Widget', 'Ext.ProgressBarWidget',
     'Ext.slider.Widget', 'Ext.sparkline.*', 'Ext.Button'],
function() {
    var testIt = Ext.isWebKit ? it : xit,
        itNotIE8 = !Ext.isIE8 ? it : xit,
        grid, view, store,
        GridModel = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: [
               { name: 'name' },
               { name: 'progress', type: 'float' },
               'sequence1',
               'sequence2',
               'sequence3',
               'sequence4',
               'sequence5',
               'sequence6',
               'sequence7'
            ]
        });

    function getRowContextCount() {
        return Ext.Object.getSize(grid.liveRowContexts);
    }

    function generateData(recordCount) {
        var result = [],
            i,
            generateSequence = function(count, min, max) {
                var j,
                    sequence = [];

                if (count == null) {
                    count = 20;
                }

                if (min == null) {
                    min = -10;
                }

                if (max == null) {
                    max = 10;
                }

                for (j = 0; j < count; j++) {
                    sequence.push(Ext.Number.randomInt(min, max));
                }

                return sequence;
            };

        for (i = 0; i < (recordCount || 20); i++) {
            result.push(['Record ' + (i + 1), Ext.Number.randomInt(0, 100) / 100, generateSequence(), generateSequence(), generateSequence(), generateSequence(20, 1, 10), generateSequence(4, 10, 20), generateSequence(), generateSequence(20, -1, 1)]);
        }

        return result;
    }

    function spyOnEvent(object, eventName, fn) {
        var obj = {
                fn: fn || Ext.emptyFn
            },
            spy = spyOn(obj, "fn");

        object.addListener(eventName, obj.fn);

        return (object[eventName] = spy);
    }

    function makeGrid(recordCount, cfg, columns, useLocking) {
        if (!columns) {
            columns = [{
                text: 'Button',
                width: 105,
                xtype: 'widgetcolumn',
                dataIndex: 'progress',
                widget: {
                    width: 90,
                    xtype: 'button'
                }
            }, {
                text: 'Progress',
                xtype: 'widgetcolumn',
                width: 120,
                dataIndex: 'progress',
                widget: {
                    xtype: 'progressbarwidget',
                    textTpl: [
                        '{percent:number("0")}% done'
                    ]
                }
            }, {
                text: 'Slider',
                xtype: 'widgetcolumn',
                width: 120,
                dataIndex: 'progress',
                widget: {
                    xtype: 'sliderwidget',
                    minValue: 0,
                    maxValue: 1,
                    decimalPrecision: 2
                }
            }, {
                text: 'Line',
                width: 100,
                dataIndex: 'sequence1',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklineline',
                    tipTpl: 'Value: {y:number("0.00")}'
                }
            }, {
                text: 'Bar',
                width: 100,
                dataIndex: 'sequence2',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklinebar'
                }
            }, {
                text: 'Discrete',
                width: 100,
                dataIndex: 'sequence3',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklinediscrete'
                }
            }, {
                text: 'Bullet',
                width: 100,
                dataIndex: 'sequence4',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklinebullet'
                }
            }, {
                text: 'Pie',
                width: 60,
                dataIndex: 'sequence5',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklinepie'
                }
            }, {
                text: 'Box',
                width: 100,
                dataIndex: 'sequence6',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklinebox'
                }
            }, {
                text: 'TriState',
                width: 100,
                dataIndex: 'sequence7',
                xtype: 'widgetcolumn',
                widget: {
                    xtype: 'sparklinetristate'
                }
            }];
        }

        if (useLocking) {
            columns[0].locked = columns[1].locked = columns[2].locked = true;
        }

        store = new Ext.data.ArrayStore({
            model: GridModel,
            data: generateData(recordCount)
        });

        grid = new Ext.grid.Panel(Ext.apply({
            columns: columns,
            store: store,
            deferRowRender: false,
            lockedGridConfig: {
                deferRowRender: false
            },
            normalGridConfig: {
                deferRowRender: false
            },
            width: 1000,
            height: 300,
            viewConfig: {
                mouseOverOutBuffer: 0
            },
            renderTo: Ext.getBody()
        }, cfg));

        view = useLocking ? grid.normalGrid.getView() : grid.getView();
    }

    afterEach(function() {
        Ext.destroy(grid, store);
        grid = store = null;
        Ext.data.Model.schema.clear();
    });

    describe("Refreshing", function() {
        it("should not create more widgets", function() {
            makeGrid();
            var widgetCount = Ext.Object.getSize(Ext.ComponentMgr.all);

            view.refreshView();

            // No widgets should have been created on refresh
            expect(Ext.Object.getSize(Ext.ComponentMgr.all)).toBe(widgetCount);
        });
    });

    describe("Remove and add", function() {
        it("should not create more widgets", function() {
            makeGrid();
            var resultSet = store.proxy.getReader().readRecords(generateData(1)),
                newRecord = resultSet.records[0],
                widgetCount = Ext.Object.getSize(Ext.ComponentMgr.all);

            store.removeAt(0);
            store.add(newRecord);

            // No widgets should have been created when the new record was added
            expect(Ext.Object.getSize(Ext.ComponentMgr.all)).toBe(widgetCount);
        });
    });

    describe("buffered rendering", function() {
        itNotIE8('should not create new widgets when scrolling', function() {
            var widgetCount,
                normalView,
                lastRow,
                timer,
                readyToScroll = true;

            makeGrid(500, null, null, true);

            // Lots of widgets to render;
            waits(50);

            runs(function() {
                widgetCount = Ext.Object.getSize(Ext.ComponentMgr.all);
                normalView = grid.normalGrid.getView();
                lastRow = normalView.bufferedRenderer.getLastVisibleRowIndex();
            });

            jasmine.waitsForScroll(grid.getScrollable(), function scrollIt(scroller, x, y) {
                if (view.all.endIndex >= 100) {
                    Ext.undefer(timer);

                    return true;
                }

                // Only scroll again when both have caught up with rendering..
                if (readyToScroll) {
                    readyToScroll = 0;
                    timer = Ext.defer(function() {
                        normalView.bufferedRenderer.scrollTo(lastRow + 3, false, function(success, record, item) {
                            scroller.ensureVisible(item);
                            readyToScroll = true;
                            lastRow = normalView.bufferedRenderer.getLastVisibleRowIndex();
                            scrollIt(scroller);
                        });
                    }, 50);
                }
            }, 'scroll to complete', 20000);
            runs(function() {
                expect(Ext.Object.getSize(Ext.ComponentMgr.all)).toBe(widgetCount);

                // Only need the requisite number of contexts to map the rendered size
                expect(getRowContextCount()).toBe(view.bufferedRenderer.viewSize);
            });
        });
    });

    describe("CQ on widgets", function() {
        it("should return just the rendered widgets", function() {
            makeGrid(10, null, null, true);

            // Return one progressbarwidget for each row
            expect(grid.query('progressbarwidget').length).toBe(10);
        });
    });

    describe('widget focus', function() {
        it('should not focus the cell on mousedown', function() {
            var view, pos, btn;

            makeGrid(5, {}, [{
                width: 200,
                xtype: 'widgetcolumn',
                dataIndex: 'name',
                cellFocusable: false,
                widget: {
                    xtype: 'container',
                    layout: 'vbox',
                    items: [{
                        html: '',
                        height: 200
                    }, {
                        xtype: 'button'
                    }]
                }
            }]);

            view = grid.getView();

            // scroll the first row partially out of view
            view.scrollTo(0, 100);
            pos = view.getScrollY();

            btn = Ext.fly(grid.getView().getRow(0)).down('.x-btn');
            jasmine.fireMouseEvent(btn.dom, 'mousedown', null, null, true);

            expect(pos).toEqual(view.getScrollY());
        });
    });
});
