topSuite("Ext.layout.container.Dashboard", ['Ext.Panel', 'Ext.layout.container.Fit', 'Ext.dashboard.Dashboard', 'Ext.button.Button'], function() {
    var panel;

    function makeItem(itemConfig) {
        return Ext.apply({
            xtype: 'component',
            style: 'margin: 4px;'
        }, itemConfig);
    }

    function makePanel(parentConfig, childConfig) {
        var items = [];

        if (!Ext.isArray(childConfig)) {
            childConfig = [childConfig];
        }

        Ext.each(childConfig, function(config) {
            items.push(makeItem(config));
        });

        panel = Ext.widget(Ext.apply({
            renderTo: document.body,
            xtype: 'panel',
            layout: 'dashboard',
            border: 0,
            bodyPadding: '6',
            maxColumns: 10,
            items: items,
            getMaxColumns: function() {
                return this.maxColumns;
            }
        }, parentConfig));
    }

    afterEach(function() {
        panel = Ext.destroy(panel);
    });

    describe("splitters", function() {
        var parentConfig = {
            height: 100,
            width: 1000
        };

        it("should put splitters between each", function() {
            makePanel(parentConfig, [
                { height: 80, columnWidth: 0.25 },
                { height: 80, columnWidth: 0.25 },
                { height: 80, columnWidth: 0.5 }
            ]);

            var items = panel.items.items;

            expect(items.length).toBe(5);

            expect(! items[0].isSplitter).toBe(true);
            expect(items[1].isSplitter).toBe(true);
            expect(! items[2].isSplitter).toBe(true);
            expect(items[3].isSplitter).toBe(true);
            expect(! items[4].isSplitter).toBe(true);
        });

        it("should hide orphan splitter", function() {
            makePanel(parentConfig, [
                { height: 80, columnWidth: 0.25 },
                { height: 80, columnWidth: 0.50 },
                { height: 80, columnWidth: 0.50 },
                { height: 80, columnWidth: 0.50 }
            ]);

            var items = panel.items.items;

            expect(items.length).toBe(7);

            expect(! items[0].isSplitter).toBe(true);
            expect(items[1].isSplitter).toBe(true);
            expect(! items[2].isSplitter).toBe(true);

            expect(items[3].isSplitter).toBe(true);
            expect(items[3].el.getHeight()).toBe(0); // orphaned so hidden w/height=0

            expect(! items[4].isSplitter).toBe(true);
            expect(items[5].isSplitter).toBe(true);
            expect(! items[6].isSplitter).toBe(true);
        });

        it("should update splitters on add to three columns", function() {
            makePanel(parentConfig, [
                { height: 80, columnWidth: 0.25 },
                // splitter
                { height: 80, columnWidth: 0.50 },
                // splitter
                { height: 80, columnWidth: 0.25 }
            ]);

            panel.insert(2, makeItem({ height: 80, columnWidth: 0.50 }));

            var items = panel.items.items;

            expect(items.length).toBe(7);

            expect(! items[0].isSplitter).toBe(true);
            expect(items[1].isSplitter).toBe(true);
            expect(! items[2].isSplitter).toBe(true);

            expect(items[3].isSplitter).toBe(true);
            expect(items[3].el.getHeight()).toBe(0); // orphaned so hidden w/height=0

            expect(! items[4].isSplitter).toBe(true);
            expect(items[5].isSplitter).toBe(true);
            expect(! items[6].isSplitter).toBe(true);
        });

        it("should update splitters on add to four columns", function() {
            makePanel(parentConfig, [
                { height: 80, columnWidth: 0.25 },
                // splitter
                { height: 80, columnWidth: 0.50 },
                // splitter
                { height: 80, columnWidth: 0.50 },
                // splitter
                { height: 80, columnWidth: 0.50 }
            ]);

            panel.remove(2);

            var items = panel.items.items;

            expect(items.length).toBe(5);

            expect(! items[0].isSplitter).toBe(true);
            expect(items[1].isSplitter).toBe(true);
            expect(! items[2].isSplitter).toBe(true);

            expect(items[3].isSplitter).toBe(true);
            expect(items[3].el.getHeight()).toBe(0); // orphaned so hidden w/height=0

            expect(! items[4].isSplitter).toBe(true);
        });
    });

    describe("dashboard", function() {
        var dashboardCt,
        dashboard,
        currentNoPanel,
        outerCt,
        column,
        panels;

        afterEach(function() {
            dashboardCt.destroy();
        });

        function makePanel(maxColumns, columnWidths, defaultContent) {
            dashboardCt = Ext.create({
                xtype: 'panel',
                id: 'mainpanel',
                noMainPanel: 2,
                width: 900,
                height: 1000,
                layout: 'fit',
                items: [{
                    xtype: 'dashboard',
                    maxColumns: maxColumns,
                    columnWidths: columnWidths,
                    parts: {
                        part1: {
                            viewTemplate: {
                                title: '{title}',
                                layout: 'fit',
                                items: [{
                                    xtype: 'panel',
                                    html: '{html}'
                                }]
                            }
                        }
                    },
                    defaultContent: defaultContent
                }],
                renderTo: Ext.getBody()
            });
        }

        function getDashboard() {
            dashboard = Ext.ComponentQuery.query('dashboard')[0];
            outerCt = dashboardCt.items;
            column = outerCt.items[0].items;
            panels = column.items;
        }

        function addDashboardColumn(columnIndex) {
            dashboard.addView({
                type: 'part1',
                title: 'Test',
                html: 'Test html',
                height: 200
            }, columnIndex);
        }

        it("should layout both items with equal widths", function() {
            var columnWidths = [0.5, 0.5],
            maxColumns = columnWidths.length,
            columnIndex = columnWidths.length - 1;

            makePanel(maxColumns, columnWidths, [{
                type: 'part1',
                title: 'Test 1',
                html: 'Test 1 html',
                columnIndex: 0,
                height: 200
            }]);

            // get dashboard data
            getDashboard();

            // add view to last column
            addDashboardColumn(columnIndex);

            // both the panels should be provided equal space on dashboard
            expect(panels[0].width).toEqual(panels[2].width);
        });

        it("should layout all four items with equal widths", function() {
            var columnWidths = [0.25, 0.25, 0.25, 0.25],
            maxColumns = columnWidths.length,
            columnIndex = columnWidths.length - 1;

            makePanel(maxColumns, columnWidths, [{
                type: 'part1',
                title: 'Test 1',
                html: 'Test 1 html',
                columnIndex: 0,
                height: 200
            }, {
                type: 'part1',
                title: 'Test 1',
                html: 'Test 1 html',
                columnIndex: 1,
                height: 200
            }, {
                type: 'part1',
                title: 'Test 1',
                html: 'Test 1 html',
                columnIndex: 2,
                height: 200
            }]);

             // get dashboard data
             getDashboard();

             // add view to last column
             addDashboardColumn(columnIndex);

            // both the extreme panels should be provided equal space on dashboard
            expect(panels[0].width).toEqual(panels[6].width);
            // both the middle panels should be provided equal space on dashboard
            expect(panels[2].width).toEqual(panels[4].width);
        });

        it("should layout all items should set width as per defined columnWidths", function() {
            var columnWidths = [0.25, 0.75],
                maxColumns = columnWidths.length,
                columnIndex = columnWidths.length - 1,
                splitterWidth, availableWidth, dashBodyEl;

            makePanel(maxColumns, columnWidths, [{
                type: 'part1',
                title: 'Test 1',
                html: 'Test 1 html',
                columnIndex: 0,
                height: 200
            }]);

            // get dashboard data
            getDashboard();

            // add view to last column
            addDashboardColumn(columnIndex);

            dashBodyEl = dashboard.body.el;
            availableWidth = dashBodyEl.getWidth() - dashBodyEl.getPadding('lr') - dashBodyEl.getBorderWidth('lr');
            splitterWidth = panels[1].width;

            // Each column will give half space for splitter
            expect(panels[0].width).toEqual(Math.floor(availableWidth * columnWidths[0]) - Math.ceil(splitterWidth / 2));
            expect(panels[2].width).toEqual(Math.floor(availableWidth * columnWidths[1]) - Math.ceil(splitterWidth / 2));
        });

        it("should layout all items without any errors", function() {
            dashboardCt = Ext.create({
                xtype: "container",
                renderTo: document.body,
                width: 600,
                height: 300,
                layout: 'column',
                items: [{
                    xtype: 'panel',
                    title: 'Foo',
                    height: 200,
                    width: 100

                }, {
                    id: 'theMiddle',
                    xtype: 'panel',
                    title: 'Bar',
                    height: 200,
                    columnWidth: 1
                }, {
                    xtype: 'panel',
                    title: 'Baz',
                    height: 200,
                    width: 100
                }]
            });

            var elements = dashboardCt.items.items;

            // all items listed should get rendered
            for (var i = 0; i < elements.length; i++) {
                expect(elements[i].rendered).toBe(true);
            }
        });

    });

});
