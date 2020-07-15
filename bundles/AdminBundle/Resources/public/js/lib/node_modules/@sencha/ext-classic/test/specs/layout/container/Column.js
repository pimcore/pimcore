topSuite("Ext.layout.container.Column",
    ['Ext.Panel', 'Ext.Button', 'Ext.layout.container.Anchor'],
function() {
    describe('wrapping with uneven heights', function() {
        // We must ensure that each row start clears to start of row.
        // Tall items would block it as below.
        // "Item 4" requires clear:left to begin at column zero.
        // +------------------------------- +
        // |+--------+ +--------+ +--------+|
        // ||        | |        | |        ||
        // || Item 1 | | Item 2 | | Item 3 ||
        // ||        | +--------+ +--------+|
        // ||        | +--------+           |
        // |+--------+ |        |           |
        // |           | Item 4 |           |
        // |           |        |           |
        // |           +--------+           |
        // +--------------------------------+
        it('should always wrap back to position zero', function() {
            var container = new Ext.container.Container({
                renderTo: document.body,
                width: 300,
                height: 500,
                layout: {
                    type: 'column',
                    columnCount: 3
                },
                defaultType: 'component',
                defaults: {
                    columnWidth: 1 / 3
                },
                items: [{
                    // This is a little taller.
                    height: 110
                }, {
                    height: 100
                }, {
                    height: 100
                }, {
                    height: 100
                }]
            }),
            item4 = container.items.items[3];

            // Item4 must have wrapped right back to first column
            expect(item4.getX()).toBe(0);
            container.destroy();
        });
    });

    function createSuite(shrinkWrap) {
        var suiteName = 'Ext.layout.container.Column';

        if (shrinkWrap) {
            suiteName += ' (shrinkWrap:true)';
        }

        describe(suiteName, function() {
            var panel,
                scrollbarSize = Ext.getScrollbarSize(),
                scrollbarWidth = scrollbarSize.width,
                scrollbarHeight = scrollbarSize.height;

            function makePanel(parentConfig, childConfig) {
                var items = [];

                if (!Ext.isArray(childConfig)) {
                    childConfig = [childConfig];
                }

                Ext.each(childConfig, function(config) {
                    items.push(Ext.apply({
                        xtype: 'component',
                        style: 'margin: 4px;'
                    }, config));
                });

                panel = Ext.widget(Ext.apply({
                    renderTo: document.body,
                    xtype: 'panel',
                    shrinkWrap: shrinkWrap || 2,
                    layout: 'column',
                    border: 0,
                    bodyPadding: '6',
                    items: items
                }, parentConfig));
            }

            afterEach(function() {
                panel.destroy();
            });

            describe("configured width and height", function() {
                var parentConfig = {
                    height: 100,
                    width: 100
                };

                describe("child items with columnWidth", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 80, columnWidth: 0.25 },
                            { height: 80, columnWidth: 0.25 },
                            { height: 80, columnWidth: 0.5 }
                        ]);
                    });

                    it("should width the child items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(14);
                        expect(panel.items.getAt(1).getWidth()).toBe(14);
                        expect(panel.items.getAt(2).getWidth()).toBe(36);
                    });

                });

                describe("child items with a combination of width and columnWidth", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 80, columnWidth: 0.5 },
                            { height: 80, columnWidth: 0.5 },
                            { height: 80, width: 36 }
                        ]);
                    });

                    it("should width the child items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(14);
                        expect(panel.items.getAt(1).getWidth()).toBe(14);
                        expect(panel.items.getAt(2).getWidth()).toBe(36);
                    });
                });

                describe("wrapping items", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 36, columnWidth: 0.5 },
                            { height: 36, columnWidth: 0.6 },
                            { height: 36, width: 36 }
                        ]);
                    });

                    it("should width the child items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(14);
                        expect(panel.items.getAt(1).getWidth()).toBe(18);
                        expect(panel.items.getAt(2).getWidth()).toBe(36);
                    });

                    it("should wrap the last item", function() {
                        expect(panel.items.getAt(2).getY() - panel.getY()).toBe(54);
                    });
                });

                describe("overflow", function() {
                    var overflowParentConfig = Ext.apply({}, { autoScroll: true }, parentConfig);

                    describe("vertical", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, [
                                { height: 36, width: 14 },
                                { height: 180, columnWidth: 1 },
                                { height: 36, width: 14 }
                            ]);
                        });

                        it("should width the child items correctly", function() {
                            expect(panel.items.getAt(0).getWidth()).toBe(14);
                            expect(panel.items.getAt(1).getWidth()).toBe(36 - scrollbarWidth);
                            expect(panel.items.getAt(2).getWidth()).toBe(14);
                        });

                        it("should have the correct scroll height", function() {
                            expect(panel.body.dom.scrollHeight).toBe(200);
                        });

                        it("should not have horizontal overflow", function() {
                            expect(panel.body.dom.scrollWidth).toBe(panel.getWidth() - scrollbarWidth);
                        });
                    });

                    describe("vertical overflow that triggers horizontal overflow", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { width: 81 - scrollbarWidth, height: 180 });
                        });

                        it("should have the correct scroll height", function() {
                            expect(panel.body.dom.scrollHeight).toBe(200);
                        });

                        it("should have the correct scroll width", function() {
                            expect(panel.body.dom.scrollWidth).toBe(panel.getWidth() - scrollbarWidth + 1);
                        });
                    });

                    describe("horizontal", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { height: 80 - scrollbarHeight, width: 180 });
                        });

                        it("should have the correct scroll width", function() {
                            expect(panel.body.dom.scrollWidth).toBe(200);
                        });

                        it("should not have vertical overflow", function() {
                            expect(panel.body.dom.scrollHeight).toBe(panel.getHeight() - scrollbarHeight);
                        });
                    });

                    describe("horizontal overflow that triggers vertical overflow", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { height: 81 - scrollbarHeight, width: 180 });
                        });

                        it("should have the correct scroll width", function() {
                            expect(panel.body.dom.scrollWidth).toBe(200);
                        });

                        it("should have the correct scroll height", function() {
                            expect(panel.body.dom.scrollHeight).toBe(panel.getHeight() - scrollbarHeight + 1);
                        });
                    });

                    describe("vertical and horizontal", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { height: 180, width: 180 });
                        });

                        it("should have the correct scroll height", function() {
                            expect(panel.body.dom.scrollHeight).toBe(200);
                        });

                        it("should have the correct scroll width", function() {
                            expect(panel.body.dom.scrollWidth).toBe(200);
                        });
                    });

                });

                describe("autoScroll with no scrollbars", function() {
                    var overflowParentConfig = Ext.apply({}, { autoScroll: true }, parentConfig);

                    beforeEach(function() {
                        makePanel(overflowParentConfig, [
                            { height: 80, columnWidth: 1 }
                        ]);
                    });

                    it("should not reserve space for a vertical scrollbar when sizing the child", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(80);
                    });
                });
            });

            describe("configured height, shrink wrap width", function() {
                var parentConfig = {
                    height: 100,
                    shrinkWrap: 1
                };

                describe("child items with columnWidth", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 80, columnWidth: 0.2, width: 32 },
                            { height: 80, columnWidth: 0.8, html: '<div style="width:40px;"></div>' }
                        ]);
                    });

                    it("should shrink wrap to the width of the child items", function() {
                        expect(panel.getWidth()).toBe(100);
                    });

                    it("should not size the columns to their configured column widths", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(32);
                        expect(panel.items.getAt(1).getWidth()).toBe(40);
                    });

                });

                describe("child items with columnWidth and container minWidth", function() {
                    beforeEach(function() {
                        makePanel({
                            height: 100,
                            shrinkWrap: 1,
                            minWidth: 212
                        }, [
                            { height: 80, columnWidth: 0.4, width: 32 },
                            { height: 80, columnWidth: 0.6, html: '<div style="width:40px;"></div>' }
                        ]);
                    });

                    it("should size to the min width", function() {
                        expect(panel.getWidth()).toBe(212);
                    });

                    it("should size the columns to their configured column widths", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(72);
                        expect(panel.items.getAt(1).getWidth()).toBe(112);
                    });

                });
            });

            describe("configured width, shrink wrap height", function() {
                var parentConfig = {
                    width: 100
                };

                describe("child items with columnWidth", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 80, columnWidth: 0.5 },
                            { height: 80, columnWidth: 0.25 },
                            { height: 80, columnWidth: 0.25 }
                        ]);
                    });

                    it("should width the child items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(36);
                        expect(panel.items.getAt(1).getWidth()).toBe(14);
                        expect(panel.items.getAt(2).getWidth()).toBe(14);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });
                });

                describe("child items with a combination of width and columnWidth", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 80, columnWidth: 0.5 },
                            { height: 80, width: 36 },
                            { height: 80, columnWidth: 0.5 }
                        ]);
                    });

                    it("should width the child items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(14);
                        expect(panel.items.getAt(1).getWidth()).toBe(36);
                        expect(panel.items.getAt(2).getWidth()).toBe(14);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });
                });

                describe("wrapping items", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 36, columnWidth: 0.5 },
                            { height: 36, width: 36 },
                            { height: 36, columnWidth: 0.6 }
                        ]);
                    });

                    it("should width the child items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(14);
                        expect(panel.items.getAt(1).getWidth()).toBe(36);
                        expect(panel.items.getAt(2).getWidth()).toBe(18);
                    });

                    it("should wrap the last item", function() {
                        expect(panel.items.getAt(2).getY() - panel.getY()).toBe(54);
                    });

                    // TODO: https://sencha.jira.com/browse/EXTJSIV-5910
                    xit("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });
                });

                describe("overflow", function() {
                    var overflowParentConfig = Ext.apply({}, { autoScroll: true }, parentConfig);

                    describe("horizontal", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { height: 80 - scrollbarHeight, width: 180 });
                        });

                        it("should have the correct scroll width", function() {
                            expect(panel.body.dom.scrollWidth).toBe(200);
                        });

                        // TODO: https://sencha.jira.com/browse/EXTJSIV-5911
                        xit("should not have vertical overflow", function() {
                            expect(panel.body.dom.scrollHeight).toBe(panel.getHeight() - scrollbarHeight);
                        });
                    });
                });
            });

            describe("shrink wrap width and height", function() {
                var parentConfig = {
                    shrinkWrap: true
                };

                describe("child items with columnWidth", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { height: 80, columnWidth: 0.2, width: 32 },
                            { height: 80, columnWidth: 0.8, html: '<div style="width:40px;"></div>' }
                        ]);
                    });

                    it("should shrink wrap to the width of the child items", function() {
                        expect(panel.getWidth()).toBe(100);
                    });

                    it("should not size the columns to their configured column widths", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(32);
                        expect(panel.items.getAt(1).getWidth()).toBe(40);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });
                });

                describe("child items with columnWidth and container minWidth", function() {
                    beforeEach(function() {
                        makePanel({
                            height: 100,
                            shrinkWrap: 1,
                            minWidth: 212
                        }, [
                            { height: 80, columnWidth: 0.4, width: 32 },
                            { height: 80, columnWidth: 0.6, html: '<div style="width:40px;"></div>' }
                        ]);
                    });

                    it("should size to the min width", function() {
                        expect(panel.getWidth()).toBe(212);
                    });

                    it("should size the columns to their configured column widths", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(72);
                        expect(panel.items.getAt(1).getWidth()).toBe(112);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });
                });
            });

        });
    }

    createSuite();
    createSuite(true);

    describe("shrink wrap of child items", function() {
        var ct;

        afterEach(function() {
            Ext.destroy(ct);
        });

        it("should shrink wrap children side by side", function() {
            ct = new Ext.container.Container({
                width: 400,
                height: 200,
                layout: 'column',
                renderTo: Ext.getBody(),
                defaultType: 'component',
                items: [{
                    html: '<div style="width: 100px;"></div>'
                }, {
                    html: '<div style="width: 100px;"></div>'
                }]
            });
            expect(ct.items.first().getWidth()).toBe(100);
            expect(ct.items.last().getWidth()).toBe(100);
        });

        it("should should stretch items after shrink wrapping", function() {
            ct = new Ext.container.Container({
                width: 400,
                height: 200,
                layout: 'column',
                renderTo: Ext.getBody(),
                defaultType: 'component',
                items: [{
                    html: '<div style="width: 100px;"></div>'
                }, {
                    columnWidth: 1
                }]
            });
            expect(ct.items.first().getWidth()).toBe(100);
            expect(ct.items.last().getWidth()).toBe(300);
        });
    });

    describe("failure tests", function() {
        it("should work with a minHeight child", function() {
            var ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                width: 200,
                height: 100,
                layout: 'anchor',
                items: [{
                    minHeight: 70,
                    columnWidth: 1
                }]
            });

            expect(ct.items.first().getHeight()).toBe(70);
            ct.destroy();
        });
    });

    describe("liquid layout", function() {
        it("should layout with liquidLayout items", function() {
            // https://sencha.jira.com/browse/EXTJS-15192
            var ct = Ext.create({
                xtype: 'container',
                renderTo: Ext.getBody(),
                width: 200,
                height: 100,
                layout: 'column',
                defaults: {
                    xtype: 'button'
                },
                items: [
                    { columnWidth: 1 },
                    { width: 50 }
                ]
            });

            expect(ct.items.getAt(0).getWidth()).toBe(150);
            expect(ct.items.getAt(1).getWidth()).toBe(50);

            ct.destroy();
        });
    });
});
