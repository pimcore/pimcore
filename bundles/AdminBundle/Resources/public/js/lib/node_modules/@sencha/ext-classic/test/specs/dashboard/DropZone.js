topSuite("Ext.dashboard.DropZone", ['Ext.dashboard.Dashboard', 'Ext.layout.container.Fit'],
    function() {
        var panel,
        dashboardParts,
        items, allItems,
        thirdWidget,
        targetDom,
        count = 9;

        function createParts() {
            var parts = {},
            item;

            for (var i = 1; i <= count; i++) {
                item = {
                    viewTemplate: {
                        title: 'Widget ' + i
                    }
                };
                parts['widget' + i] = item;
            }

            return parts;
        }

        beforeEach(function() {
            dashboardParts = createParts();
            panel = Ext.create('Ext.dashboard.Dashboard', Ext.apply({
                renderTo: Ext.getBody(),
                title: 'Dashboard',
                maxColumns: 7,
                header: false,
                height: 1200,
                columnWidths: [ 1, 0.25, 0.25, 0.5],
                width: 500,
                parts: dashboardParts,

                defaultContent: [{
                        type: 'widget1', // maps to the parts key
                        columnIndex: 0
                    }, {
                        type: 'widget2',
                        columnIndex: 0
                    }, {
                        type: 'widget3',
                        columnIndex: 1
                    }, {
                        type: 'widget4',
                        columnIndex: 1
                    }, {
                        type: 'widget5',
                        columnIndex: 1
                    }, {
                        type: 'widget6',
                        columnIndex: 2
                    }, {
                        type: 'widget7',
                        columnIndex: 2
                    }, {
                        type: 'widget8',
                        columnIndex: 3
                    }, {
                        type: 'widget9',
                        columnIndex: 3
                    }
                ]
            }));

            items = panel.items;
            allItems = items.items;
            thirdWidget = allItems[2].items.items[0];
            targetDom = thirdWidget.header.el.dom;
        });

        afterEach(function() {
            panel = Ext.destroy(panel);
        });

        describe('panels', function() {
            it('should split current column and increase count of splitters and items on dragging', function() {
                var countSplitters = panel.el.dom.querySelectorAll('.x-splitter').length,
                countItems = items.length;

                runs(function() {
                    // get hold of the panel and move it
                    jasmine.fireMouseEvent(targetDom, 'mousedown');
                    jasmine.fireMouseEvent(targetDom, 'mousemove', 150, 350);

                     // drop hold of panel
                    jasmine.fireMouseEvent(targetDom, 'mousemove', 250, 250);
                    jasmine.fireMouseEvent(targetDom, 'mouseup', 350, 250);

                    // one new splitter and two new columns get added
                    expect(countSplitters + 1).toEqual(panel.el.dom.querySelectorAll('.x-splitter').length);
                    expect(countItems + 2).toEqual(items.length);
                });
            });

            it('should create a placeholder when item is dragged', function() {
                runs(function() {
                    // get hold of the panel and move it
                    jasmine.fireMouseEvent(targetDom, 'mousedown');
                    jasmine.fireMouseEvent(targetDom, 'mousemove', 150, 350);

                    // a placeholder panel should be created in target area
                    expect(panel.el.dom.querySelectorAll('.x-panel-dd-spacer').length).toBe(1);

                    // drop hold of panel
                    jasmine.fireMouseEvent(targetDom, 'mousemove', 250, 250);
                    jasmine.fireMouseEvent(targetDom, 'mouseup', 350, 250);

                    // the placeholder should be removed once mouse dropped
                    expect(panel.el.dom.querySelectorAll('.x-panel-dd-spacer').length).toBe(0);
                });
            });
        });
    });
