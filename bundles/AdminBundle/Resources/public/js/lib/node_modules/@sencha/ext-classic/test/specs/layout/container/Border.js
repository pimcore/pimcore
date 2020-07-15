// TODO: splitters
// TODO: expand/collapse
// TODO: Adding regions (done partially)
// TODO: multiple regions (done partially)
// TODO: removing regions
// TODO: collapsible center
// TODO: moving regions: do splitters/placeholders work, including directionality?
// TODO: shouldn't allow 2 center regions but should allow a center to be removed and re-added
// TODO: mini
// TODO: placeholder vs header collapse

topSuite("Ext.layout.container.Border",
    ['Ext.container.Viewport', 'Ext.Panel', 'Ext.Button', 'Ext.layout.*'],
function() {
    // Assertions based on placeholders are tricky as the default placeholder size could change without that
    // necessarily counting as a failure. To handle this we capture that size in these 'constants'.
    var HORIZONTAL_PLACEHOLDER_HEIGHT = 28;

    var VERTICAL_PLACEHOLDER_WIDTH = 28;

    var ct;

    function createBorderLayout(items, cfg) {
        ct = Ext.create('Ext.container.Container', Ext.apply({}, {
            defaultType: 'component',
            height: 200,
            items: items,
            layout: 'border',
            renderTo: Ext.getBody(),
            width: 200
        }, cfg));

        return ct;
    }

    afterEach(function() {
        Ext.destroy(ct);
        ct = null;
    });

    function getLeft(ct, item) {
        if (ct.isComponent) {
            ct = ct.el;
        }

        if (item.isComponent) {
            item = item.el;
        }

        return item.getOffsetsTo(ct)[0];
    }

    function getTop(ct, item) {
        if (ct.isComponent) {
            ct = ct.el;
        }

        if (item.isComponent) {
            item = item.el;
        }

        return item.getOffsetsTo(ct)[1];
    }

    var todoIt = Ext.isIE9 ? xit : it;

    describe("removing items", function() {
        var normalize = function(style) {
                if (style === 'auto') {
                    return '';
                }
                else if (style === '0px') {
                    return '';
                }

                return style;
            },
            other;

        beforeEach(function() {
            other = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'fit',
                width: 100,
                height: 100
            });
        });

        afterEach(function() {
            Ext.destroy(other);
        });

        it("should clear the top/left on the north region when removing", function() {
            var c;

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'vbox',
                width: 200,
                height: 200,
                defaultType: 'component',
                items: [c = new Ext.Component({
                    region: 'north',
                    height: 50
                }), {
                    region: 'west',
                    width: 50
                }, {
                    region: 'south',
                    height: 50
                }, {
                    region: 'east',
                    width: 50
                }]
            });

            ct.remove(c, false);
            other.add(c);

            expect(normalize(c.getEl().getStyle('top'))).toBe('');
            expect(normalize(c.getEl().getStyle('left'))).toBe('');
        });

        it("should clear the top/left on the west region when removing", function() {
            var c;

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'vbox',
                width: 200,
                height: 200,
                defaultType: 'component',
                items: [{
                    region: 'north',
                    height: 50
                }, c = new Ext.Component({
                    region: 'west',
                    width: 50
                }), {
                    region: 'south',
                    height: 50
                }, {
                    region: 'east',
                    width: 50
                }]
            });

            ct.remove(c, false);
            other.add(c);

            expect(normalize(c.getEl().getStyle('top'))).toBe('');
            expect(normalize(c.getEl().getStyle('left'))).toBe('');
        });

        it("should clear the top/left on the south region when removing", function() {
            var c;

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'vbox',
                width: 200,
                height: 200,
                defaultType: 'component',
                items: [{
                    region: 'north',
                    height: 50
                }, {
                    region: 'west',
                    width: 50
                }, c = new Ext.Component({
                    region: 'south',
                    height: 50
                }), {
                    region: 'east',
                    width: 50
                }]
            });

            ct.remove(c, false);
            other.add(c);

            expect(normalize(c.getEl().getStyle('top'))).toBe('');
            expect(normalize(c.getEl().getStyle('left'))).toBe('');
        });

        it("should clear the top/left on the east region when removing", function() {
            var c;

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'vbox',
                width: 200,
                height: 200,
                defaultType: 'component',
                items: [{
                    region: 'north',
                    height: 50
                }, {
                    region: 'west',
                    width: 50
                }, {
                    region: 'south',
                    height: 50
                }, c = new Ext.Component({
                    region: 'east',
                    width: 50
                })]
            });

            ct.remove(c, false);
            other.add(c);

            expect(normalize(c.getEl().getStyle('top'))).toBe('');
            expect(normalize(c.getEl().getStyle('left'))).toBe('');
        });

        it("should remove an item when the item is not rendered and the item is not destroying", function() {
            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                collapsed: true,
                layout: 'border',
                width: 100,
                height: 100,
                items: {
                    region: 'center'
                }
            });

            // When adding the item to the collapsed panel, it won't render
            var c = ct.add({});

            expect(function() {
                ct.remove(c, false);
                c.destroy();
            }).not.toThrow();
        });
    });

    describe("splitters", function() {

        var createWithCenter = function(items, cfg) {
            items = items.concat({
                xtype: 'component',
                region: 'center'
            });
            createBorderLayout(items, cfg);
        };

        describe("creation", function() {
            it("should create a splitter with split: true", function() {
                var north = new Ext.Component({
                    region: 'north',
                    height: 50,
                    split: true
                });

                createWithCenter([north]);
                expect(north.nextSibling().isXType('splitter')).toBe(true);
            });

            describe("collapsible: true && collapseMode: 'mini'", function() {
                it("should create a splitter", function() {
                    var west = new Ext.panel.Panel({
                        region: 'west',
                        height: 50,
                        collapsible: true,
                        collapseMode: 'mini'
                    });

                    createWithCenter([west]);
                    expect(west.nextSibling().isXType('splitter')).toBe(true);
                });

                it("should not hide the splitter if region is collapsed", function() {
                    var west = new Ext.panel.Panel({
                        region: 'west',
                        height: 50,
                        collapsible: true,
                        collapsed: true,
                        collapseMode: 'mini'
                    });

                    createWithCenter([west]);
                    expect(west.nextSibling().isVisible()).toBe(true);
                });
            });

            describe("splitter configuration", function() {
                var east, splitter;

                beforeEach(function() {
                    east = new Ext.Component({
                        region: 'east',
                        width: 50,
                        split: {
                            collapseOnDblClick: false,
                            id: 'foosplitter'
                        }
                    });

                    createWithCenter([east]);

                    splitter = east.previousSibling();
                });

                it("should create a splitter", function() {
                    expect(splitter.isXType('splitter')).toBe(true);
                });

                it("should set custom properties passed in config", function() {
                    expect(splitter.collapseOnDblClick).toBe(false);
                });

                it("should pass on default options unless overridden", function() {
                    expect(splitter.collapseTarget).toEqual(east);
                });

                it("should allow to override default options", function() {
                    expect(splitter.id).toBe('foosplitter');
                });
            });
        });

        describe("destruction", function() {
            it("should destroy the splitter when removing it's owner", function() {
                var north = new Ext.Component({
                    region: 'north',
                    height: 50,
                    split: true
                });

                createWithCenter([north]);
                ct.remove(north);
                expect(ct.items.getCount()).toBe(1);
            });
        });

        describe("visibility", function() {
            describe("initial", function() {
                it("should show the splitter if the component is visible", function() {
                    var north = new Ext.Component({
                        region: 'north',
                        height: 50,
                        split: true
                    });

                    createWithCenter([north]);
                    expect(north.nextSibling().isVisible()).toBe(true);
                });

                it("should hide the splitter if the component is hidden", function() {
                    var north = new Ext.Component({
                        region: 'north',
                        hidden: true,
                        height: 50,
                        split: true
                    });

                    createWithCenter([north]);
                    expect(north.nextSibling().isVisible()).toBe(false);
                });

                it("should show the splitter if the component is collapsed", function() {
                    var north = new Ext.Component({
                        region: 'north',
                        height: 50,
                        split: true,
                        collapsed: true
                    });

                    createWithCenter([north]);
                    expect(north.nextSibling().isVisible()).toBe(true);
                });
            });

            describe("dynamic", function() {
                it("should hide the splitter when hiding the component", function() {
                    var north = new Ext.Component({
                        region: 'north',
                        height: 50,
                        split: true
                    });

                    createWithCenter([north]);
                    north.hide();
                    expect(north.nextSibling().isVisible()).toBe(false);
                });

                it("should show the splitter when showing the component", function() {
                    var north = new Ext.Component({
                        region: 'north',
                        height: 50,
                        split: true,
                        hidden: true
                    });

                    createWithCenter([north]);
                    north.show();
                    expect(north.nextSibling().isVisible()).toBe(true);
                });
            });

            it("should not affect other splitters", function() {
                var north = new Ext.Component({
                    region: 'north',
                    height: 50,
                    split: true
                });

                var south = new Ext.Component({
                    region: 'south',
                    height: 50,
                    split: true
                });

                createWithCenter([north, south]);
                north.hide();
                expect(north.nextSibling().isVisible()).toBe(false);
                expect(south.previousSibling().isVisible()).toBe(true);
            });
        });

    });

    // All of these tests perform simple sizing and positioning of components within a border layout. This includes:
    //
    // * Fixed sizes
    // * Flex sizes
    // * Percentage sizes
    // * Shrink-wrap
    // * Margins
    //
    // Complex aspects of border layout like splitters, collapsing and placeholders are described in other tests.
    describe('Simple sizing and positioning', function() {
        it('should support no child regions', function() {
            // extreme edge case - we don't make any assertions but we're implicitly checking it doesn't throw an error
            createBorderLayout([]);
        });

        describe('Fixed sizes', function() {
            it('should support a fixed-width west region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      | west   | center |
                //      | w:  30 | w: 170 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |        |        |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        width: 30
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(30);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getWidth()).toBe(170);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(30);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a fixed-width east region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      | center | east   |
                //      | w: 170 | w:  30 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |        |        |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'east',
                        width: 30
                    }, {
                        region: 'center'
                    }
                ]);

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(30);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(170);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(170);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a fixed-height north region', function() {
                //
                //      +-----------------+
                //      |      north      |
                //      |      w: 200     |
                //      |      h:  30     |
                //      +-----------------+
                //      |      center     |
                //      |      w: 200     |
                //      |      h: 170     |
                //      +-----------------+
                var ct = createBorderLayout([
                    {
                        height: 30,
                        region: 'north'
                    }, {
                        region: 'center'
                    }
                ]);

                var north = ct.down('[region=north]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(30);
                expect(north.getWidth()).toBe(200);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(center.getHeight()).toBe(170);
                expect(center.getWidth()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(30);
            });

            it('should support a fixed-height south region', function() {
                //
                //      +-----------------+
                //      |      center     |
                //      |      w: 200     |
                //      |      h: 170     |
                //      +-----------------+
                //      |      south      |
                //      |      w: 200     |
                //      |      h:  30     |
                //      +-----------------+
                var ct = createBorderLayout([
                    {
                        height: 30,
                        region: 'south'
                    }, {
                        region: 'center'
                    }
                ]);

                var south = ct.down('[region=south]');

                var center = ct.down('[region=center]');

                expect(south.getHeight()).toBe(30);
                expect(south.getWidth()).toBe(200);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(170);

                expect(center.getHeight()).toBe(170);
                expect(center.getWidth()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support 4 fixed-size regions', function() {
                //
                //      +-------------------+
                //      |      w: 200       |
                //      |      h:  50       |
                //      +-----+------+------+
                //      |w: 20|w: 100|w: 80 |
                //      |h: 90|h:  90|h: 90 |
                //      +-----+------+------+
                //      |      w: 200       |
                //      |      h:  60       |
                //      +-------------------+
                var ct = createBorderLayout([
                    {
                        height: 60,
                        region: 'south'
                    }, {
                        region: 'west',
                        width: 20
                    }, {
                        height: 50,
                        region: 'north'
                    }, {
                        region: 'center'
                    }, {
                        region: 'east',
                        width: 80
                    }
                ]);

                var north = ct.down('[region=north]');

                var south = ct.down('[region=south]');

                var east = ct.down('[region=east]');

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(50);
                expect(north.getWidth()).toBe(200);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(south.getHeight()).toBe(60);
                expect(south.getWidth()).toBe(200);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(140);

                expect(east.getHeight()).toBe(90);
                expect(east.getWidth()).toBe(80);
                expect(getLeft(ct, east)).toBe(120);
                expect(getTop(ct, east)).toBe(50);

                expect(west.getHeight()).toBe(90);
                expect(west.getWidth()).toBe(20);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(50);

                expect(center.getHeight()).toBe(90);
                expect(center.getWidth()).toBe(100);
                expect(getLeft(ct, center)).toBe(20);
                expect(getTop(ct, center)).toBe(50);
            });

            it('should support 4 fixed-size regions with weights', function() {
                // Same as the previous test case but with weights to change the priorities
                //
                //      +------+------+------+
                //      |      |w: 100|      |
                //      |      |h:  50|      |
                //      |      +------+      |
                //      |w:  20|w: 100|w:  80|
                //      |h: 200|h:  90|h: 200|
                //      |      +------+      |
                //      |      |w: 100|      |
                //      |      |h:  60|      |
                //      +------+------+------+
                var ct = createBorderLayout([
                    {
                        height: 60,
                        region: 'south',
                        weight: -100
                    }, {
                        region: 'west',
                        width: 20,
                        weight: 100
                    }, {
                        height: 50,
                        region: 'north',
                        weight: -100
                    }, {
                        region: 'center'
                    }, {
                        region: 'east',
                        width: 80,
                        weight: 100
                    }
                ]);

                var north = ct.down('[region=north]');

                var south = ct.down('[region=south]');

                var east = ct.down('[region=east]');

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(50);
                expect(north.getWidth()).toBe(100);
                expect(getLeft(ct, north)).toBe(20);
                expect(getTop(ct, north)).toBe(0);

                expect(south.getHeight()).toBe(60);
                expect(south.getWidth()).toBe(100);
                expect(getLeft(ct, south)).toBe(20);
                expect(getTop(ct, south)).toBe(140);

                expect(east.getHeight()).toBe(200);
                expect(east.getWidth()).toBe(80);
                expect(getLeft(ct, east)).toBe(120);
                expect(getTop(ct, east)).toBe(0);

                expect(west.getHeight()).toBe(200);
                expect(west.getWidth()).toBe(20);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getHeight()).toBe(90);
                expect(center.getWidth()).toBe(100);
                expect(getLeft(ct, center)).toBe(20);
                expect(getTop(ct, center)).toBe(50);
            });

            it('should support margin on a fixed-width west region', function() {
                //
                //      +------------+--------+
                //      |     10     |        |
                //      |  +------+  |        |
                //      |  |west  |  | center |
                //      |40|w:  30|20| w: 110 |
                //      |  |h: 160|  | h: 200 |
                //      |  +------+  |        |
                //      |     30     |        |
                //      +------------+--------+
                var ct = createBorderLayout([
                    {
                        margin: '10 20 30 40',
                        region: 'west',
                        width: 30
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(30);
                expect(west.getHeight()).toBe(160);
                expect(getLeft(ct, west)).toBe(40);
                expect(getTop(ct, west)).toBe(10);

                expect(center.getWidth()).toBe(110);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(90);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a fixed-width west region with no center region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      | west   |        |
                //      | w:  40 |        |
                //      | h: 200 |        |
                //      |        |        |
                //      |        |        |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        width: 40
                    }
                ]);

                var west = ct.down('[region=west]');

                expect(west.getWidth()).toBe(40);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);
            });
        });

        describe('Percentage sizes', function() {
            it('should support a percentage-width west region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      | west   | center |
                //      | w:  50 | w: 150 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |   25%  |   75%  |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        width: '25%'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(50);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getWidth()).toBe(150);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(50);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a percentage-width east region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      | center | east   |
                //      | w: 120 | w:  80 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |   60%  |   40%  |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'east',
                        width: '40%'
                    }, {
                        region: 'center'
                    }
                ]);

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(80);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(120);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(120);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a percentage-height north region', function() {
                //
                //      +-----------------+
                //      |    north - 10%  |
                //      |      w: 200     |
                //      |      h:  20     |
                //      +-----------------+
                //      |   center - 90%  |
                //      |      w: 200     |
                //      |      h: 180     |
                //      +-----------------+
                var ct = createBorderLayout([
                    {
                        region: 'north',
                        height: '10%'
                    }, {
                        region: 'center'
                    }
                ]);

                var north = ct.down('[region=north]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(20);
                expect(north.getWidth()).toBe(200);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(center.getHeight()).toBe(180);
                expect(center.getWidth()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(20);
            });

            it('should support a percentage-height south region', function() {
                //
                //      +-----------------+
                //      |   center - 15%  |
                //      |      w: 200     |
                //      |      h:  30     |
                //      +-----------------+
                //      |    south - 85%  |
                //      |      w: 200     |
                //      |      h: 170     |
                //      +-----------------+
                var ct = createBorderLayout([
                    {
                        region: 'south',
                        height: '85%'
                    }, {
                        region: 'center'
                    }
                ]);

                var south = ct.down('[region=south]');

                var center = ct.down('[region=center]');

                expect(south.getHeight()).toBe(170);
                expect(south.getWidth()).toBe(200);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(30);

                expect(center.getHeight()).toBe(30);
                expect(center.getWidth()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support 4 percentage-size regions', function() {
                //
                //      +------------------+
                //      |      w: 200      |
                //      |      h:  24      |
                //      +-----+------+-----+
                //      |w: 36|w: 142|w: 22|
                //      |h: 98|h:  98|h: 98|
                //      +-----+------+-----+
                //      |      w: 200      |
                //      |      h:  78      |
                //      +------------------+
                var ct = createBorderLayout([
                    {
                        height: '39%',
                        region: 'south'
                    }, {
                        region: 'west',
                        width: '18%'
                    }, {
                        height: '12%',
                        region: 'north'
                    }, {
                        region: 'center'
                    }, {
                        region: 'east',
                        width: '11%'
                    }
                ]);

                var north = ct.down('[region=north]');

                var south = ct.down('[region=south]');

                var east = ct.down('[region=east]');

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(24);
                expect(north.getWidth()).toBe(200);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(south.getHeight()).toBe(78);
                expect(south.getWidth()).toBe(200);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(122);

                expect(east.getHeight()).toBe(98);
                expect(east.getWidth()).toBe(22);
                expect(getLeft(ct, east)).toBe(178);
                expect(getTop(ct, east)).toBe(24);

                expect(west.getHeight()).toBe(98);
                expect(west.getWidth()).toBe(36);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(24);

                expect(center.getHeight()).toBe(98);
                expect(center.getWidth()).toBe(142);
                expect(getLeft(ct, center)).toBe(36);
                expect(getTop(ct, center)).toBe(24);
            });

            it('should support 4 percentage-size regions with weights', function() {
                // Similar to the previous case but with priorities shifted and 2 south regions
                //
                //      +------+------+------+
                //      |      |center|      |
                //      |      |w: 142|      |
                //      |w:  36|h:  98|w:  22|
                //      |h: 176+------+h: 176|
                //      |      |w: 142|      |
                //      |      |h:  78|      |
                //      +------+------+------+
                //      |       w: 200       |
                //      |       h:  24       |
                //      +--------------------+
                var ct = createBorderLayout([
                    {
                        height: '39%',
                        region: 'south',
                        weight: 10
                    }, {
                        region: 'west',
                        weight: 20,
                        width: '18%'
                    }, {
                        height: '12%',
                        region: 'south',
                        weight: 100
                    }, {
                        region: 'center'
                    }, {
                        region: 'east',
                        weight: 20,
                        width: '11%'
                    }
                ]);

                var south1 = ct.down('[region=south][weight=100]');

                var south2 = ct.down('[region=south][weight=10]');

                var east = ct.down('[region=east]');

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(south1.getHeight()).toBe(24);
                expect(south1.getWidth()).toBe(200);
                expect(getLeft(ct, south1)).toBe(0);
                expect(getTop(ct, south1)).toBe(176);

                expect(south2.getHeight()).toBe(78);
                expect(south2.getWidth()).toBe(142);
                expect(getLeft(ct, south2)).toBe(36);
                expect(getTop(ct, south2)).toBe(98);

                expect(east.getHeight()).toBe(176);
                expect(east.getWidth()).toBe(22);
                expect(getLeft(ct, east)).toBe(178);
                expect(getTop(ct, east)).toBe(0);

                expect(west.getHeight()).toBe(176);
                expect(west.getWidth()).toBe(36);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getHeight()).toBe(98);
                expect(center.getWidth()).toBe(142);
                expect(getLeft(ct, center)).toBe(36);
                expect(getTop(ct, center)).toBe(0);
            });
        });

        describe('Flex sizes', function() {
            it('should support a flex-width west region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      |  west  | center |
                //      | w:  40 | w: 160 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |  0.25f |   1f   |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        flex: 0.25,
                        minWidth: 10, // min-width shouldn't affect the width as it's greater than 10 anyway
                        region: 'west'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(40);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getWidth()).toBe(160);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(40);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a flex-width east region', function() {
                //
                //      +--------+--------+
                //      |        |        |
                //      | center | east   |
                //      | w:  50 | w: 150 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |   1f   |   3f   |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        flex: 3,
                        region: 'east'
                    }, {
                        region: 'center'
                    }
                ]);

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(150);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(50);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(50);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a flex-height north region', function() {
                //
                //      +-----------------+
                //      |    north - 9f   |
                //      |      w: 200     |
                //      |      h: 180     |
                //      +-----------------+
                //      |   center - 1f   |
                //      |      w: 200     |
                //      |      h:  20     |
                //      +-----------------+
                var ct = createBorderLayout([
                    {
                        flex: 9,
                        region: 'north'
                    }, {
                        region: 'center'
                    }
                ]);

                var north = ct.down('[region=north]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(180);
                expect(north.getWidth()).toBe(200);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(center.getHeight()).toBe(20);
                expect(center.getWidth()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(180);
            });

            it('should support a flex-height south region', function() {
                //
                //      +-----------------+
                //      |   center - 1f   |
                //      |      w: 200     |
                //      |      h: 125     |
                //      +-----------------+
                //      |  south - 0.6f   |
                //      |      w: 200     |
                //      |      h:  75     |
                //      +-----------------+
                var ct = createBorderLayout([
                    {
                        flex: 0.6,
                        region: 'south'
                    }, {
                        region: 'center'
                    }
                ]);

                var south = ct.down('[region=south]');

                var center = ct.down('[region=center]');

                expect(south.getHeight()).toBe(75);
                expect(south.getWidth()).toBe(200);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(125);

                expect(center.getHeight()).toBe(125);
                expect(center.getWidth()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support 4 flex-size regions', function() {
                //
                //      +------------------+
                //      |      w: 200      |
                //      |      h:  50      |
                //      +-----+------+-----+
                //      |w: 25|w: 100|w: 75|
                //      |h: 50|h:  50|h: 50|
                //      +-----+------+-----+
                //      |      w: 200      |
                //      |      h: 100      |
                //      +------------------+
                var ct = createBorderLayout([
                    {
                        flex: 2,
                        region: 'south'
                    }, {
                        flex: 0.25,
                        region: 'west'
                    }, {
                        flex: 1,
                        region: 'north'
                    }, {
                        region: 'center'
                    }, {
                        flex: 0.75,
                        region: 'east'
                    }
                ]);

                var north = ct.down('[region=north]');

                var south = ct.down('[region=south]');

                var east = ct.down('[region=east]');

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(50);
                expect(north.getWidth()).toBe(200);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(south.getHeight()).toBe(100);
                expect(south.getWidth()).toBe(200);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(100);

                expect(east.getHeight()).toBe(50);
                expect(east.getWidth()).toBe(75);
                expect(getLeft(ct, east)).toBe(125);
                expect(getTop(ct, east)).toBe(50);

                expect(west.getHeight()).toBe(50);
                expect(west.getWidth()).toBe(25);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(50);

                expect(center.getHeight()).toBe(50);
                expect(center.getWidth()).toBe(100);
                expect(getLeft(ct, center)).toBe(25);
                expect(getTop(ct, center)).toBe(50);
            });

            it('should support center flexing', function() {
                // Explicit flex on the center, plus weights to shift priorities
                //
                //      +------+------+
                //      | west |north |
                //      |      |w: 120|
                //      |      |h:  50|
                //      |w:  80+------+
                //      |h: 200|center|
                //      |      |w: 120|
                //      |      |h: 150|
                //      +------+------+
                var ct = createBorderLayout([
                    {
                        flex: 1,
                        region: 'north'
                    }, {
                        flex: 2,
                        region: 'west',
                        weight: 50
                    }, {
                        flex: 3,
                        region: 'center'
                    }
                ]);

                var north = ct.down('[region=north]');

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(north.getHeight()).toBe(50);
                expect(north.getWidth()).toBe(120);
                expect(getLeft(ct, north)).toBe(80);
                expect(getTop(ct, north)).toBe(0);

                expect(west.getHeight()).toBe(200);
                expect(west.getWidth()).toBe(80);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getHeight()).toBe(150);
                expect(center.getWidth()).toBe(120);
                expect(getLeft(ct, center)).toBe(80);
                expect(getTop(ct, center)).toBe(50);
            });

            it('should support margin on a center region with flexed south region', function() {
                // This should be the consistent with how vbox handles flex and margin
                //
                //      +------------+
                //      |     10     |
                //      |  +------+  |
                //      |  |center|  |
                //      |40|w: 140|20|
                //      |  |h:  40|  |
                //      |  +------+  |
                //      |     30     |
                //      +------------+
                //      |   south    |
                //      |   w: 200   |
                //      |   h: 120   |
                //      +------------+
                //
                var ct = createBorderLayout([
                    {
                        margin: '10 20 30 40',
                        region: 'center'
                    }, {
                        flex: 3,
                        region: 'south'
                    }
                ]);

                var south = ct.down('[region=south]');

                var center = ct.down('[region=center]');

                expect(south.getWidth()).toBe(200);
                expect(south.getHeight()).toBe(120);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(80);

                expect(center.getWidth()).toBe(140);
                expect(center.getHeight()).toBe(40);
                expect(getLeft(ct, center)).toBe(40);
                expect(getTop(ct, center)).toBe(10);
            });

            // TODO
            it('should support margin on a center and flex-width east region', function() {
                // This should be the consistent with how hbox handles flex and margin
                //
                //      +------------+------------+
                //      |     10     |     10     |
                //      |  +------+  |  +------+  |
                //      |  |center|  |  | east |  |
                //      |40|w:  40|20|40|w:  40|20|
                //      |  |h: 160|  |  |h: 160|  |
                //      |  +------+  |  +------+  |
                //      |     30     |     30     |
                //      +------------+------------+
                var ct = createBorderLayout([
                    {
                        flex: 1,
                        margin: '10 20 30 40',
                        region: 'east'
                    }, {
                        margin: '10 20 30 40',
                        region: 'center'
                    }
                ]);

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(40);
                expect(east.getHeight()).toBe(160);
                expect(getLeft(ct, east)).toBe(140);
                expect(getTop(ct, east)).toBe(10);

                expect(center.getWidth()).toBe(40);
                expect(center.getHeight()).toBe(160);
                expect(getLeft(ct, center)).toBe(40);
                expect(getTop(ct, center)).toBe(10);
            });

            it('should support margin on multiple flex-size regions', function() {
                //      +-------------------------+
                //      |            20           |
                //      |  +-------------------+  |
                //      |  |      north        |  |
                //      |12|      w: 178       |10|
                //      |  |      h: 117       |  |
                //      |  +-------------------+  |
                //      |            5            |
                //      +------------+------------+
                //      |      4     |     10     |
                //      |  +------+  |  +------+  |
                //      |  |center|  |  | east |  |
                //      |20|w:  47|10|10|w:  94|19|
                //      |  |h:  39|  |  |h:  18|  |
                //      |  +------+  |  +------+  |
                //      |     15     |     30     |
                //      +------------+------------+
                var ct = createBorderLayout([
                    {
                        flex: 2,
                        region: 'east',
                        margin: '10 19 30 10'
                    }, {
                        margin: '4 10 15 20',
                        region: 'center'
                    }, {
                        flex: 3,
                        region: 'north',
                        margin: '20 10 5 12'
                    }
                ]);

                var east = ct.down('[region=east]');

                var north = ct.down('[region=north]');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(94);
                expect(east.getHeight()).toBe(18);
                expect(getLeft(ct, east)).toBe(87);
                expect(getTop(ct, east)).toBe(152);

                expect(north.getWidth()).toBe(178);
                expect(north.getHeight()).toBe(117);
                expect(getLeft(ct, north)).toBe(12);
                expect(getTop(ct, north)).toBe(20);

                expect(center.getWidth()).toBe(47);
                expect(center.getHeight()).toBe(39);
                expect(getLeft(ct, center)).toBe(20);
                expect(getTop(ct, center)).toBe(146);
            });

            it('should support a flex-width west region with min-width', function() {
                //
                //      +--------+--------+
                //      |  west  | center |
                //      | w:  90 | w: 110 |
                //      | h: 200 | h: 200 |
                //      |        |        |
                //      |  0.25f |   1f   |
                //      +--------+--------+
                var ct = createBorderLayout([
                    {
                        flex: 0.25,
                        minWidth: 90,
                        region: 'west'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(90);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getWidth()).toBe(110);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(90);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a flex-width west region with min-width and margin', function() {
                // The key thing here is to check that minWidth doesn't include the margin in the width
                //
                //      +------------+--------+
                //      |+--------+  |        |
                //      ||  west  |  | center |
                //      || w: 120 |  | w:  30 |
                //      || h: 200 |50| h: 200 |
                //      ||        |  |        |
                //      ||   1f   |  |        |
                //      |+--------+  |   1f   |
                //      +------------+--------+
                var ct = createBorderLayout([
                    {
                        flex: 1,
                        margin: '0 50 0 0',
                        minWidth: 120,
                        region: 'west'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(120);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(center.getWidth()).toBe(30);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(170);
                expect(getTop(ct, center)).toBe(0);
            });
        });

        describe('Shrink-wrapping', function() {
            it('should support a shrink-wrapped west region', function() {
                //
                //      +-------------+--------+
                //      | west w:  50 |        |
                //      |      h: 200 |        |
                //      |      10     |        |
                //      |  +-------+  | center |
                //      |10|w:   30|10| w: 150 |
                //      |  |h:  180|  | h: 200 |
                //      |  +-------+  |        |
                //      |      10     |        |
                //      +-------------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        xtype: 'container',
                        layout: {
                            align: 'stretch',
                            type: 'hbox' // simulate fit using hbox as it supports margin properly
                        },
                        items: [{
                            margin: '10 10 10 10',
                            width: 30,
                            xtype: 'component'
                        }]
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var inner = west.down('component');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(50);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(inner.getWidth()).toBe(30);
                expect(inner.getHeight()).toBe(180);
                expect(getLeft(ct, inner)).toBe(10);
                expect(getTop(ct, inner)).toBe(10);

                expect(center.getWidth()).toBe(150);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(50);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a shrink-wrapped east region', function() {
                //
                //      +--------+-------------+
                //      |        | east w: 180 |
                //      |        |      h: 200 |
                //      |        |      20     |
                //      | center |  +-------+  |
                //      | w:  20 |50|w:  100|30|
                //      | h: 200 |  |h:  140|  |
                //      |        |  +-------+  |
                //      |        |      40     |
                //      +--------+-------------+
                var ct = createBorderLayout([
                    {
                        region: 'east',
                        xtype: 'container',
                        layout: {
                            align: 'stretch',
                            type: 'hbox' // simulate fit using hbox as it supports margin properly
                        },
                        items: [{
                            margin: '20 30 40 50',
                            width: 100,
                            xtype: 'component'
                        }]
                    }, {
                        region: 'center'
                    }
                ]);

                var east = ct.down('[region=east]');

                var inner = east.down('component');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(180);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(20);
                expect(getTop(ct, east)).toBe(0);

                expect(inner.getWidth()).toBe(100);
                expect(inner.getHeight()).toBe(140);
                expect(getLeft(ct, inner)).toBe(70);
                expect(getTop(ct, inner)).toBe(20);

                expect(center.getWidth()).toBe(20);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a shrink-wrapped north region', function() {
                //
                //      +----------------------+
                //      |      10              |
                //      |  +-------+    north  |
                //      |40|w:  140|20  w: 200 |
                //      |  |h:   30|    h:  70 |
                //      |  +-------+           |
                //      |      30              |
                //      +----------------------+
                //      |      center          |
                //      |      w: 200          |
                //      |      h: 130          |
                //      +----------------------+
                var ct = createBorderLayout([
                    {
                        region: 'north',
                        xtype: 'container',
                        layout: {
                            align: 'stretch',
                            type: 'vbox' // simulate fit using vbox as it supports margin properly
                        },
                        items: [{
                            margin: '10 20 30 40',
                            height: 30,
                            xtype: 'component'
                        }]
                    }, {
                        region: 'center'
                    }
                ]);

                var north = ct.down('[region=north]');

                var inner = north.down('component');

                var center = ct.down('[region=center]');

                expect(north.getWidth()).toBe(200);
                expect(north.getHeight()).toBe(70);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(inner.getWidth()).toBe(140);
                expect(inner.getHeight()).toBe(30);
                expect(getLeft(ct, inner)).toBe(40);
                expect(getTop(ct, inner)).toBe(10);

                expect(center.getWidth()).toBe(200);
                expect(center.getHeight()).toBe(130);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(70);
            });

            it('should support a shrink-wrapped south region', function() {
                //
                //      +----------------------+
                //      |      center          |
                //      |      w: 200          |
                //      |      h: 130          |
                //      +----------------------+
                //      |      10              |
                //      |  +-------+    south  |
                //      |40|w:  140|20  w: 200 |
                //      |  |h:   30|    h:  70 |
                //      |  +-------+           |
                //      |      30              |
                //      +----------------------+
                var ct = createBorderLayout([
                    {
                        region: 'south',
                        xtype: 'container',
                        layout: {
                            align: 'stretch',
                            type: 'vbox' // simulate fit using vbox as it supports margin properly
                        },
                        items: [{
                            margin: '10 20 30 40',
                            height: 30,
                            xtype: 'component'
                        }]
                    }, {
                        region: 'center'
                    }
                ]);

                var south = ct.down('[region=south]');

                var inner = south.down('component');

                var center = ct.down('[region=center]');

                expect(south.getWidth()).toBe(200);
                expect(south.getHeight()).toBe(70);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(130);

                expect(inner.getWidth()).toBe(140);
                expect(inner.getHeight()).toBe(30);
                expect(getLeft(ct, inner)).toBe(40);
                expect(getTop(ct, inner)).toBe(140);

                expect(center.getWidth()).toBe(200);
                expect(center.getHeight()).toBe(130);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a shrink-wrapped west region with align stretch', function() {
                // Whether this is really testing border layout or vbox is unclear but it's a tricky case. See also the
                // stretchmax version that comes next.
                //
                //      +-------------+--------+
                //      |     15      |        |
                //      |  +-------+  |        |
                //      |15| 60x60 |15|        |
                //      |  +-------+  |        |
                //      |     35      |        |
                //      |  +-------+  |        |
                //      |20| 50x30 |20| center |
                //      |  +-------+  | w: 110 |
                //      |     25      | h: 200 |
                //      |  +-------+  |        |
                //      | 5| 80x30 | 5|        |
                //      |  +-------+  |        |
                //      |      5      |        |
                //      |west: 90x200 |        |
                //      +-------------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        xtype: 'container',
                        layout: {
                            align: 'stretch',
                            type: 'vbox'
                        },
                        items: [
                            {
                                flex: 2,
                                itemId: 'cmp1',
                                margin: '15 15 15 15',
                                xtype: 'component'
                            }, {
                                flex: 1,
                                itemId: 'cmp2',
                                margin: '20 20 20 20',
                                width: 50,
                                xtype: 'component'
                            }, {
                                flex: 1,
                                itemId: 'cmp3',
                                margin: '5 5 5 5',
                                width: 60,
                                xtype: 'component'
                            }
                        ]
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var cmp1 = west.down('#cmp1');

                var cmp2 = west.down('#cmp2');

                var cmp3 = west.down('#cmp3');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(90);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(cmp1.getWidth()).toBe(60);
                expect(cmp1.getHeight()).toBe(60);
                expect(getLeft(ct, cmp1)).toBe(15);
                expect(getTop(ct, cmp1)).toBe(15);

                expect(cmp2.getWidth()).toBe(50);
                expect(cmp2.getHeight()).toBe(30);
                expect(getLeft(ct, cmp2)).toBe(20);
                expect(getTop(ct, cmp2)).toBe(110);

                expect(cmp3.getWidth()).toBe(80);
                expect(cmp3.getHeight()).toBe(30);
                expect(getLeft(ct, cmp3)).toBe(5);
                expect(getTop(ct, cmp3)).toBe(165);

                expect(center.getWidth()).toBe(110);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(90);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support a shrink-wrapped west region with align stretchmax', function() {
                // Whether this is really testing border layout or vbox is unclear but it's a tricky case. See also the
                // stretch version that came before.
                //
                //      +-------------+--------+
                //      |     15      |        |
                //      |  +-------+  |        |
                //      |15| 60x60 |15|        |
                //      |  +-------+  |        |
                //      |   35=15+20  |        |
                //      |  +-------+  |        |
                //      |20| 50x30 |20| center |
                //      |  +-------+  | w: 110 |
                //      |   25=20+5   | h: 200 |
                //      |  +-------+  |        |
                //      | 5| 80x30 | 5|        |
                //      |  +-------+  |        |
                //      |      5      |        |
                //      |west: 90x200 |        |
                //      +-------------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        xtype: 'container',
                        layout: {
                            align: 'stretchmax',
                            type: 'vbox'
                        },
                        items: [
                            {
                                flex: 2,
                                itemId: 'cmp1',
                                margin: '15 15 15 15', // w=30 h=30
                                xtype: 'component'
                            }, {
                                flex: 1,
                                itemId: 'cmp2',
                                margin: '20 20 20 20', // w=40 h=40
                                width: 50,
                                xtype: 'component'
                            }, {
                                flex: 1,
                                itemId: 'cmp3',
                                margin: '5 5 5 5',
                                width: 60,
                                xtype: 'component'
                            }
                        ]
                    }, {
                        region: 'center'
                    }
                ]);

                expect(ct).toHaveLayout({
                    el: { w: 200, h: 200 },
                    items: {
                        '[region=west]': {
                            el: { xywh: '0 0 90 200' },
                            items: {
                                cmp1: { el: { xywh: '15 15 60 60' } },
                                cmp2: { el: { xywh: '20 110 50 30' } },
                                cmp3: { el: { xywh: '5 165 80 30' } }
                            }
                        },
                        '[region=center]': {
                            el: { xywh: '90 0 110 200' }
                        }
                    }
                });
            });
        });

        describe('Mixing sizing paradigms', function() {
            it('should support mixing fixed-width and flex', function() {
                //
                //      +--------+--------+--------+
                //      |        |        |        |
                //      |  west  | center |  east  |
                //      | w:  80 | w:  30 | w:  90 |
                //      | h: 200 | h: 200 | h: 200 |
                //      |        |        |        |
                //      |        |   1f   |   3f   |
                //      +--------+--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        width: 80
                    }, {
                        flex: 3,
                        region: 'east'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(80);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(east.getWidth()).toBe(90);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(110);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(30);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(80);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support mixing fixed-width and percentage-width', function() {
                // Exactly the same as the previous test case but using percentages instead of flex
                //
                //      +--------+--------+--------+
                //      |        |        |        |
                //      |  west  | center |  east  |
                //      | w:  80 | w:  30 | w:  90 |
                //      | h: 200 | h: 200 | h: 200 |
                //      |        |        |        |
                //      |        |        |   45%  |
                //      +--------+--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        width: 80
                    }, {
                        region: 'east',
                        width: '45%'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(80);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(east.getWidth()).toBe(90);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(110);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(30);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(80);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support mixing flex-width and percentage', function() {
                // Just like the previous 2 test cases but using flex and percentage
                //
                //      +--------+--------+--------+
                //      |        |        |        |
                //      |  west  | center |  east  |
                //      | w:  80 | w:  30 | w:  90 |
                //      | h: 200 | h: 200 | h: 200 |
                //      |        |        |        |
                //      |        |   1f   |   3f   |
                //      +--------+--------+--------+
                var ct = createBorderLayout([
                    {
                        region: 'west',
                        width: '40%'
                    }, {
                        flex: 3,
                        region: 'east'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(80);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(east.getWidth()).toBe(90);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(110);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(30);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(80);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support mixing flex-width and percentage with a max-width capping the percentage', function() {
                // Just like the previous 2 test cases but using flex and percentage
                //
                //      +--------+--------+--------+
                //      |        |        |        |
                //      |  west  | center |  east  |
                //      | w:  50 | w:  50 | w: 100 |
                //      | h: 200 | h: 200 | h: 200 |
                //      |        |        |        |
                //      |        |   1f   |   3f   |
                //      +--------+--------+--------+
                var ct = createBorderLayout([
                    {
                        maxWidth: 50,
                        region: 'west',
                        width: '40%'
                    }, {
                        flex: 2,
                        region: 'east'
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(50);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(east.getWidth()).toBe(100);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(100);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(50);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(50);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should prioritize percentages over flex and flex over fixed size', function() {
                //
                //      +--------+--------+--------+
                //      |        |        |        |
                //      |  west  | center |  east  |
                //      | w:  20 | w:  60 | w: 120 |
                //      | h: 200 | h: 200 | h: 200 |
                //      |        |        |        |
                //      |   10%  |   1f   |   2f   |
                //      +--------+--------+--------+
                var ct = createBorderLayout([
                    {
                        flex: 8, // ignored
                        region: 'west',
                        width: '10%'
                    }, {
                        flex: 2,
                        height: 100, // ignored
                        region: 'east',
                        width: 100 // ignored
                    }, {
                        region: 'center'
                    }
                ]);

                var west = ct.down('[region=west]');

                var east = ct.down('[region=east]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(20);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(east.getWidth()).toBe(120);
                expect(east.getHeight()).toBe(200);
                expect(getLeft(ct, east)).toBe(80);
                expect(getTop(ct, east)).toBe(0);

                expect(center.getWidth()).toBe(60);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(20);
                expect(getTop(ct, center)).toBe(0);
            });
        });
    });

    describe('Collapsed regions and placeholders', function() {
        it("should not fire a collapse event when the panel starts collapsed", function() {
            var fired = false;

            createBorderLayout([{
                region: 'center'
            }, {
                xtype: 'panel',
                region: 'south',
                collapsible: true,
                collapsed: true,
                listeners: {
                    collapse: function() {
                        fired = true;
                    }
                }
            }]);
            expect(fired).toBe(false);
        });

        it('should support a collapsed west region', function() {
            //
            //      +------+--------+
            //      |      | center |
            //      |w: 28 | w: 172 |
            //      |      | h: 200 |
            //      | west |        |
            //      +------+--------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'west',
                    xtype: 'panel',
                    collapsible: true,
                    floatable: true // We want to test floating
                }, {
                    region: 'center'
                }
            ]),
            floated = false;

            var west = ct.down('[region=west]:not([placeholderFor])');

            var westPh = ct.down('[region=west][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(westPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(westPh.getHeight()).toBe(200);
            expect(getLeft(ct, westPh)).toBe(0);
            expect(getTop(ct, westPh)).toBe(0);

            expect(center.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(center.getHeight()).toBe(200);
            expect(getLeft(ct, center)).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, center)).toBe(0);

            west.on('float', function() {
                floated = true;
            });
            west.on('unfloat', function() {
                floated = false;
            });

            // Click the placeholder to slide out the region
            jasmine.fireMouseEvent(westPh.el, 'mouseover');

            if (document.createTouch) {
                Ext.testHelper.tap(westPh.el);
            }
            else {
                jasmine.fireMouseEvent(westPh.el, 'click');
            }

            // Wait for 1 second animation to float out the region
            waitsFor(function() {
                return floated;
            });

            runs(function() {
                expect(floated).toBe(true);

                // Mouseout of the placeholder region so that it disappears
                jasmine.fireMouseEvent(westPh.el, 'mouseout');
            });

            // Wait for region to be unfloated
            waits(function() {
                return floated === false;
            });

            runs(Ext.emptyFn);
        });

        it('should expand a collapsed west region from floated', function() {
            //
            //      +------+--------+
            //      |      | center |
            //      |w: 28 | w: 172 |
            //      |      | h: 200 |
            //      | west |        |
            //      +------+--------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'west',
                    xtype: 'panel',
                    collapsible: true,
                    floatable: true // We want to test floating
                }, {
                    region: 'center'
                }
            ]),
            floated = false,
            expanded = false,
            westBox;

            var west = ct.down('[region=west]:not([placeholderFor])');

            var westPh = ct.down('[region=west][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(westPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(westPh.getHeight()).toBe(200);
            expect(getLeft(ct, westPh)).toBe(0);
            expect(getTop(ct, westPh)).toBe(0);

            expect(center.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(center.getHeight()).toBe(200);
            expect(getLeft(ct, center)).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, center)).toBe(0);

            west.on('float', function() {
                floated = true;
            });
            west.on('unfloat', function() {
                floated = false;
            });
            west.on('expand', function() {
                expanded = true;
            });

            // Click the placeholder to slide out the region
            if (document.createTouch) {
                Ext.testHelper.tap(westPh.el);
            }
            else {
                jasmine.fireMouseEvent(westPh.el, 'click');
            }

            // Wait for 1 second animation to float out the region
            waitsFor(function() {
                return floated;
            });

            runs(function() {
                expect(floated).toBe(true);

                if (document.createTouch) {
                    Ext.testHelper.tap(westPh.expandTool.el);
                }
                else {
                    jasmine.fireMouseEvent(westPh.expandTool.el, 'click');
                }
            });

            // Wait for region to be unfloated and expanded
            waitsFor(function() {
                return (!floated) && expanded;
            });

            runs(function() {
                westBox = west.getBox();

                if (document.createTouch) {
                    Ext.testHelper.tap(center.el);
                }
                else {
                    jasmine.fireMouseEvent(center.el, 'click');
                }
            });

            // We can't wait for anything, we are expecting nothing to happen
            waits(1000);

            // Nothing should have happened
            runs(function() {
                expect(west.getBox()).toEqual(westBox);
            });
        });

        todoIt('should support a collapsed east region', function() {
            //
            //      +--------+------+
            //      | center |      |
            //      | w: 172 |w: 28 |
            //      | h: 200 |      |
            //      |        | east |
            //      +--------+------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    width: '20%', // irrelevant
                    region: 'east',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var eastPh = ct.down('[region=east][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(eastPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(eastPh.getHeight()).toBe(200);
            expect(getLeft(ct, eastPh)).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, eastPh)).toBe(0);

            expect(center.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(center.getHeight()).toBe(200);
            expect(getLeft(ct, center)).toBe(0);
            expect(getTop(ct, center)).toBe(0);
        });

        it('should support a collapsed north region', function() {
            //
            //      +--------+
            //      | north  |
            //      | w: 200 |
            //      | h:  28 |
            //      +--------+
            //      | center |
            //      | w: 200 |
            //      | h: 172 |
            //      +--------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    height: 73, // irrelevant
                    region: 'north',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var northPh = ct.down('[region=north][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(northPh.getWidth()).toBe(200);
            expect(northPh.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, northPh)).toBe(0);
            expect(getTop(ct, northPh)).toBe(0);

            expect(center.getWidth()).toBe(200);
            expect(center.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, center)).toBe(0);
            expect(getTop(ct, center)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
        });

        it('should support a collapsed south region', function() {
            //
            //      +--------+
            //      | center |
            //      | w: 200 |
            //      | h: 172 |
            //      +--------+
            //      | south  |
            //      | w: 200 |
            //      | h:  28 |
            //      +--------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'south',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var southPh = ct.down('[region=south][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(southPh.getWidth()).toBe(200);
            expect(southPh.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, southPh)).toBe(0);
            expect(getTop(ct, southPh)).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);

            expect(center.getWidth()).toBe(200);
            expect(center.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, center)).toBe(0);
            expect(getTop(ct, center)).toBe(0);
        });

        todoIt('should support collapsed north and west regions', function() {
            //
            //      +---------------+
            //      | h: 28  north  |
            //      +------+--------+
            //      |      | center |
            //      |w: 28 | w: 172 |
            //      |      | h: 172 |
            //      | west |        |
            //      +------+--------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    height: 50, // irrelevant
                    region: 'north',
                    xtype: 'panel'
                }, {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'west',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var northPh = ct.down('[region=north][placeholderFor]');

            var westPh = ct.down('[region=west][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(northPh.getWidth()).toBe(200);
            expect(northPh.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, northPh)).toBe(0);
            expect(getTop(ct, northPh)).toBe(0);

            expect(westPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(westPh.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, westPh)).toBe(0);
            expect(getTop(ct, westPh)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);

            expect(center.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(center.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, center)).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, center)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
        });

        todoIt('should support collapsed north and west regions with weights', function() {
            // Same as the previous example but with weights shifting the region priority. The key thing to check is
            // that region weights carry across to their placeholders
            //
            //      +------+--------+
            //      |      | h: 28  |
            //      |      | north  |
            //      |w: 28 |--------+
            //      |      | center |
            //      |      | w: 172 |
            //      |      | h: 172 |
            //      | west |        |
            //      +------+--------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    height: 50, // irrelevant
                    region: 'north',
                    xtype: 'panel'
                }, {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'west',
                    weight: 30,
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var northPh = ct.down('[region=north][placeholderFor]');

            var westPh = ct.down('[region=west][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(northPh.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(northPh.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, northPh)).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, northPh)).toBe(0);

            expect(westPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(westPh.getHeight()).toBe(200);
            expect(getLeft(ct, westPh)).toBe(0);
            expect(getTop(ct, westPh)).toBe(0);

            expect(center.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(center.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, center)).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, center)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
        });

        todoIt('should support 4 collapsed regions', function() {
            //
            //      +--------------------+
            //      |       w: 200       |
            //      |       h:  28       |
            //      +------+------+------+
            //      |w:  28|w: 148|w:  28|
            //      |h: 148|h: 148|h: 148|
            //      +------+------+------+
            //      |       w: 200       |
            //      |       h:  28       |
            //      +--------------------+
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    height: 50, // irrelevant
                    region: 'north',
                    xtype: 'panel'
                }, {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'west',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }, {
                    collapsed: true,
                    height: '50%', // irrelevant
                    region: 'south',
                    xtype: 'panel'
                }, {
                    collapsed: true,
                    width: 78, // irrelevant
                    region: 'east',
                    xtype: 'panel'
                }
            ]);

            var northPh = ct.down('[region=north][placeholderFor]');

            var southPh = ct.down('[region=south][placeholderFor]');

            var westPh = ct.down('[region=west][placeholderFor]');

            var eastPh = ct.down('[region=east][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(northPh.getWidth()).toBe(200);
            expect(northPh.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, northPh)).toBe(0);
            expect(getTop(ct, northPh)).toBe(0);

            expect(southPh.getWidth()).toBe(200);
            expect(southPh.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, southPh)).toBe(0);
            expect(getTop(ct, southPh)).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);

            expect(westPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(westPh.getHeight()).toBe(200 - 2 * HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, westPh)).toBe(0);
            expect(getTop(ct, westPh)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);

            expect(eastPh.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(eastPh.getHeight()).toBe(200 - 2 * HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, eastPh)).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, eastPh)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);

            expect(center.getWidth()).toBe(200 - 2 * VERTICAL_PLACEHOLDER_WIDTH);
            expect(center.getHeight()).toBe(200 - 2 * HORIZONTAL_PLACEHOLDER_HEIGHT);
            expect(getLeft(ct, center)).toBe(VERTICAL_PLACEHOLDER_WIDTH);
            expect(getTop(ct, center)).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
        });

        it('should support a fixed-size custom placeholder', function() {
            //
            //      +--------+--------+
            //      | center |  east  |
            //      | w: 130 | w:  70 |
            //      | h: 200 | h: 200 |
            //      +--------+--------+

            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'east',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        width: 70,
                        vertical: true,
                        xtype: 'toolbar'
                    })
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(placeholder.getWidth()).toBe(70);
            expect(placeholder.getHeight()).toBe(200);
            expect(getLeft(ct, placeholder)).toBe(130);
            expect(getTop(ct, placeholder)).toBe(0);

            expect(center.getWidth()).toBe(130);
            expect(center.getHeight()).toBe(200);
            expect(getLeft(ct, center)).toBe(0);
            expect(getTop(ct, center)).toBe(0);
        });

        it('should support a flex-size custom placeholder', function() {
            //
            //      +--------+--------+
            //      |  west  | center |
            //      | w:  40 | w: 160 |
            //      | h: 200 | h: 200 |
            //      |  0.25f |        |
            //      +--------+--------+

            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'west',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        flex: 0.25,
                        xtype: 'toolbar'
                    })
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=west][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(placeholder.getWidth()).toBe(40);
            expect(placeholder.getHeight()).toBe(200);
            expect(getLeft(ct, placeholder)).toBe(0);
            expect(getTop(ct, placeholder)).toBe(0);

            expect(center.getWidth()).toBe(160);
            expect(center.getHeight()).toBe(200);
            expect(getLeft(ct, center)).toBe(40);
            expect(getTop(ct, center)).toBe(0);
        });

        it('should support a percentage-size custom placeholder', function() {
            //
            //      +--------+
            //      | center |
            //      | w: 200 |
            //      | h:  50 |
            //      +--------+
            //      | south  |
            //      | w: 200 |
            //      | h: 150 |
            //      |  75%   |
            //      +--------+

            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'south',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        height: '75%',
                        vertical: true, // TODO: required?
                        xtype: 'toolbar'
                    })
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=south][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(placeholder.getWidth()).toBe(200);
            expect(placeholder.getHeight()).toBe(150);
            expect(getLeft(ct, placeholder)).toBe(0);
            expect(getTop(ct, placeholder)).toBe(50);

            expect(center.getWidth()).toBe(200);
            expect(center.getHeight()).toBe(50);
            expect(getLeft(ct, center)).toBe(0);
            expect(getTop(ct, center)).toBe(0);
        });

        it('should support an hbox custom placeholder', function() {
            //
            //      +-------+--------+
            //      | w: 90 | w: 110 |
            //      | h: 85 | h:  85 |
            //      +-------+--------+
            //      |     center     |
            //      |     w: 200     |
            //      |     h: 115     |
            //      +----------------+

            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'north',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        xtype: 'container',
                        layout: {
                            align: 'stretchmax',
                            type: 'hbox'
                        },
                        items: [
                            {
                                height: 85,
                                itemId: 'cmp1',
                                width: 90,
                                xtype: 'component'
                            }, {
                                flex: 1,
                                itemId: 'cmp2',
                                xtype: 'component'
                            }
                        ]
                    })
                }, {
                    region: 'center'
                }
            ]);

            var cmp1 = ct.down('#cmp1');

            var cmp2 = ct.down('#cmp2');

            var placeholder = ct.down('[region=north][placeholderFor]');

            var center = ct.down('[region=center]');

            expect(cmp1.getWidth()).toBe(90);
            expect(cmp1.getHeight()).toBe(85);
            expect(getLeft(ct, cmp1)).toBe(0);
            expect(getTop(ct, cmp1)).toBe(0);

            expect(cmp2.getWidth()).toBe(110);
            expect(cmp2.getHeight()).toBe(85);
            expect(getLeft(ct, cmp2)).toBe(90);
            expect(getTop(ct, cmp2)).toBe(0);

            expect(placeholder.getWidth()).toBe(200);
            expect(placeholder.getHeight()).toBe(85);
            expect(getLeft(ct, placeholder)).toBe(0);
            expect(getTop(ct, placeholder)).toBe(0);

            expect(center.getWidth()).toBe(200);
            expect(center.getHeight()).toBe(115);
            expect(getLeft(ct, center)).toBe(0);
            expect(getTop(ct, center)).toBe(85);
        });

        it('should update a rendered placeholder when setTitle is called', function() {
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var east = ct.down('[region=east][placeholder]');

            var center = ct.down('[region=center]');

            // There's no way to check the title using the public API so resort to simply checking the property
            expect(east.title).toBe('Original Title');
            expect(placeholder.getTitle().getText()).toBe('Original Title');

            east.setTitle('New Title');

            expect(east.title).toBe('New Title');
            expect(placeholder.getTitle().getText()).toBe('New Title');
        });

        it('should update an unrendered placeholder when setTitle is called', function() {
            var ct = createBorderLayout([
                {
                    animCollapse: false,
                    flex: 2, // irrelevant
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var east = ct.down('panel[region=east]');

            var center = ct.down('[region=center]');

            // There's no way to check the title using the public API so resort to simply checking the property
            expect(east.title).toBe('Original Title');

            east.setTitle('New Title');

            // The exact point that the placeholder is created is an implementation detail but currently it is not
            // created until the panel is collapsed.
            east.collapse();

            var placeholder = ct.down('[region=east][placeholderFor]');

            expect(east.title).toBe('New Title');
            expect(placeholder.getTitle().getText()).toBe('New Title');
        });

        it('should support setTitle with a custom placeholder', function() {
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        width: 70,
                        xtype: 'component'
                    })
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var east = ct.down('[region=east][placeholder]');

            var center = ct.down('[region=center]');

            expect(east.title).toBe('Original Title');

            // With the default placeholder this updates the text in the placeholder but with a custom placeholder this
            // used to cause an error to be thrown.
            east.setTitle('New Title');

            expect(east.title).toBe('New Title');
        });

        it('should update a rendered placeholder when setIconCls is called', function() {
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    iconCls: 'firstCls',
                    region: 'east',
                    title: 'Title',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var east = ct.down('[region=east][placeholder]');

            var center = ct.down('[region=center]');

            // There's no way to check the title using the public API so resort to simply checking the property
            expect(east.iconCls).toBe('firstCls');
            expect(placeholder.iconCls).toBe('firstCls');

            east.setIconCls('secondCls');

            expect(east.iconCls).toBe('secondCls');
            expect(placeholder.iconCls).toBe('secondCls');
        });

        it('should update an unrendered placeholder when setIconCls is called', function() {
            var ct = createBorderLayout([
                {
                    animCollapse: false,
                    flex: 2, // irrelevant
                    iconCls: 'firstCls',
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var east = ct.down('panel[region=east]');

            var center = ct.down('[region=center]');

            // There's no way to check the title using the public API so resort to simply checking the property
            expect(east.iconCls).toBe('firstCls');

            east.setIconCls('secondCls');

            // The exact point that the placeholder is created is an implementation detail but currently it is not
            // created until the panel is collapsed.
            east.collapse();

            var placeholder = ct.down('[region=east][placeholderFor]');

            expect(east.iconCls).toBe('secondCls');
            expect(placeholder.iconCls).toBe('secondCls');
        });

        it('should support setIconCls with a custom placeholder', function() {
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    iconCls: 'firstCls',
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        width: 70,
                        xtype: 'component'
                    })
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var east = ct.down('[region=east][placeholder]');

            var center = ct.down('[region=center]');

            expect(east.iconCls).toBe('firstCls');

            // Ensure that there isn't an exception thrown from trying to update the custom placeholder
            east.setIconCls('secondCls');

            expect(east.iconCls).toBe('secondCls');
        });

        it('should update a rendered placeholder when setIcon is called', function() {
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'east',
                    title: 'Title',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var east = ct.down('[region=east][placeholder]');

            var center = ct.down('[region=center]');

            east.setIcon(Ext.BLANK_IMAGE_URL);

            expect(east.icon).toBe(Ext.BLANK_IMAGE_URL);
            expect(placeholder.icon).toBe(Ext.BLANK_IMAGE_URL);
        });

        it('should update an unrendered placeholder when setIcon is called', function() {
            var ct = createBorderLayout([
                {
                    animCollapse: false,
                    flex: 2, // irrelevant
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel'
                }, {
                    region: 'center'
                }
            ]);

            var east = ct.down('panel[region=east]');

            var center = ct.down('[region=center]');

            east.setIcon(Ext.BLANK_IMAGE_URL);

            // The exact point that the placeholder is created is an implementation detail but currently it is not
            // created until the panel is collapsed.
            east.collapse();

            var placeholder = ct.down('[region=east][placeholderFor]');

            expect(east.icon).toBe(Ext.BLANK_IMAGE_URL);
            expect(placeholder.icon).toBe(Ext.BLANK_IMAGE_URL);
        });

        it('should support setIcon with a custom placeholder', function() {
            var ct = createBorderLayout([
                {
                    collapsed: true,
                    flex: 2, // irrelevant
                    region: 'east',
                    title: 'Original Title',
                    xtype: 'panel',
                    placeholder: Ext.widget({
                        width: 70,
                        xtype: 'component'
                    })
                }, {
                    region: 'center'
                }
            ]);

            var placeholder = ct.down('[region=east][placeholderFor]');

            var east = ct.down('[region=east][placeholder]');

            var center = ct.down('[region=center]');

            // Ensure that there isn't an exception thrown from trying to update the custom placeholder
            east.setIcon(Ext.BLANK_IMAGE_URL);

            expect(east.icon).toBe(Ext.BLANK_IMAGE_URL);
        });
    });

    describe('Changing regions', function() {
        describe('Adding regions', function() {
            it('should support adding a west region', function() {
                var ct = createBorderLayout([]);

                ct.add({
                    flex: 1.5,
                    region: 'west'
                });

                var west = ct.down('[region=west]');

                expect(west.getWidth()).toBe(120);
                expect(west.getHeight()).toBe(200);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);
            });

            it('should support adding a center region', function() {
                var ct = createBorderLayout([]);

                ct.add({
                    flex: 1.5, // irrelevant
                    region: 'center'
                });

                var center = ct.down('[region=center]');

                expect(center.getWidth()).toBe(200);
                expect(center.getHeight()).toBe(200);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(0);
            });

            it('should support adding multiple regions simultaneously', function() {
                //
                //      +-------------+
                //      |   w: 200    |
                //      |   h:  70    |
                //      +------+------+
                //      |center| east |
                //      |w:  50|w: 150|
                //      |h: 130|h: 130|
                //      +------+------+

                var ct = createBorderLayout([]);

                ct.add(
                    {
                        height: 70,
                        region: 'north'
                    }, {
                        region: 'center'
                    }, {
                        flex: 3,
                        region: 'east'
                    }
                );

                var east = ct.down('[region=east]');

                var north = ct.down('[region=north]');

                var center = ct.down('[region=center]');

                expect(east.getWidth()).toBe(150);
                expect(east.getHeight()).toBe(130);
                expect(getLeft(ct, east)).toBe(50);
                expect(getTop(ct, east)).toBe(70);

                expect(north.getWidth()).toBe(200);
                expect(north.getHeight()).toBe(70);
                expect(getLeft(ct, north)).toBe(0);
                expect(getTop(ct, north)).toBe(0);

                expect(center.getWidth()).toBe(50);
                expect(center.getHeight()).toBe(130);
                expect(getLeft(ct, center)).toBe(0);
                expect(getTop(ct, center)).toBe(70);
            });

            it("should set isViewportBorderChild flag", function() {
                var ct = createBorderLayout([], { isViewport: true });

                ct.add({
                    flex: 1.5,
                    region: 'west'
                });

                var west = ct.down('[region=west]');

                expect(west.isViewportBorderChild).toBe(true);
            });

            it('should support adding a collapsed region', function() {
                //
                //      +------+------+
                //      | west |center|
                //      |w:  30|w: 170|
                //      |h: 172|h: 172|
                //      +------+------+
                //      |   w: 200    |
                //      |   h:  28    |
                //      +-------------+

                var ct = createBorderLayout([{
                    region: 'west',
                    width: 30
                }, {
                    region: 'center'
                }]);

                ct.add({
                    collapsed: true,
                    height: 70,
                    region: 'south',
                    xtype: 'panel'
                });

                var west = ct.down('[region=west]');

                var south = ct.down('[region=south]');

                var center = ct.down('[region=center]');

                expect(west.getWidth()).toBe(30);
                expect(west.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
                expect(getLeft(ct, west)).toBe(0);
                expect(getTop(ct, west)).toBe(0);

                expect(south.getWidth()).toBe(200);
                expect(south.getHeight()).toBe(HORIZONTAL_PLACEHOLDER_HEIGHT);
                expect(getLeft(ct, south)).toBe(0);
                expect(getTop(ct, south)).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);

                expect(center.getWidth()).toBe(170);
                expect(center.getHeight()).toBe(200 - HORIZONTAL_PLACEHOLDER_HEIGHT);
                expect(getLeft(ct, center)).toBe(30);
                expect(getTop(ct, center)).toBe(0);
            });

            it("should support re-adding previously collapsed region", function() {
                var ct = createBorderLayout([{
                    xtype: 'panel',
                    region: 'west',
                    title: 'west',
                    width: 30,
                    collapsible: true,
                    collapsed: false,
                    animCollapse: false
                }, {
                    region: 'center'
                }]);

                var west = ct.down('[region=west]');

                var center = ct.down('[region=center]');

                west.collapse();
                ct.remove(west, false);

                expect(center.getWidth()).toBe(200);
                expect(center.getHeight()).toBe(200);

                ct.add(west);

                expect(west.el.isVisible()).toBe(false);
                expect(west.placeholder.el.isVisible()).toBe(true);
                expect(west.placeholder.getWidth()).toBe(VERTICAL_PLACEHOLDER_WIDTH);
                expect(center.getWidth()).toBe(200 - VERTICAL_PLACEHOLDER_WIDTH);
            });
        });
    });

    describe('Splitters', function() {
        it('should adjust for splitters in percentage size calculations', function() {
            // Splitters are 5 pixels wide, so...
            //
            //      +-------+--------+-------+
            //      | west  = center =  east |
            //      |w:  19 = w:  76 = w:  95|
            //      |h: 200 = h: 200 = h: 200|
            //      +-------+--------+-------+

            var ct = createBorderLayout([
                {
                    region: 'west',
                    split: true,
                    width: '10%'
                }, {
                    region: 'center'
                }, {
                    region: 'east',
                    split: true,
                    width: '50%'
                }
            ]);

            var west = ct.down('[region=west][split]');

            var east = ct.down('[region=east][split]');

            var center = ct.down('[region=center]');

            expect(west.getWidth()).toBe(19);
            expect(west.getHeight()).toBe(200);
            expect(getLeft(ct, west)).toBe(0);
            expect(getTop(ct, west)).toBe(0);

            expect(east.getWidth()).toBe(95);
            expect(east.getHeight()).toBe(200);
            expect(getLeft(ct, east)).toBe(105);
            expect(getTop(ct, east)).toBe(0);

            expect(center.getWidth()).toBe(76);
            expect(center.getHeight()).toBe(200);
            expect(getLeft(ct, center)).toBe(24);
            expect(getTop(ct, center)).toBe(0);
        });
    });

    describe('Interaction with other layouts', function() {
        it('should be possible to use border layout within a docked item', function() {
            // Docking uses absolute positioning, border layout uses relative... absolute should win
            //
            //      +--------+
            //      | empty  |
            //      |        |
            //      +--------+
            //      |+------+|
            //      ||center||
            //      ||w: 200||
            //      ||h:  80||
            //      |+------+|
            //      | docked |
            //      +--------+

            ct = Ext.widget({
                border: false,
                height: 200,
                renderTo: Ext.getBody(),
                width: 200,
                xtype: 'panel',
                dockedItems: [{
                    dock: 'bottom',
                    itemId: 'docked',
                    layout: 'border',
                    height: 80,
                    xtype: 'container',
                    items: [{
                        itemId: 'inner',
                        region: 'center',
                        xtype: 'component'
                    }]
                }]
            });

            var docked = ct.down('#docked');

            var inner = docked.down('#inner');

            expect(ct.getWidth()).toBe(200);
            expect(ct.getHeight()).toBe(200);

            expect(docked.getWidth()).toBe(200);
            expect(docked.getHeight()).toBe(80);
            expect(getLeft(ct, docked)).toBe(0);
            expect(getTop(ct, docked)).toBe(120);

            expect(inner.getWidth()).toBe(200);
            expect(inner.getHeight()).toBe(80);
            expect(getLeft(ct, inner)).toBe(0);
            expect(getTop(ct, inner)).toBe(120);
        });

        it('should be possible to use border layout within an absolute layout', function() {
            // Absolute layout uses absolute positioning, border layout uses relative... absolute should win
            //
            //      +------------+
            //      |     10     |
            //      |  +------+  |
            //      |10|w: 180|10|
            //      |  |h:  25|  |
            //      |  +------+  |
            //      |     10     |
            //      |  +------+  |
            //      |  |w: 180|  |
            //      |10|h:  25|10|
            //      |  +------+  |
            //      |    130     |
            //      +------------+

            ct = Ext.widget({
                defaultType: 'container',
                height: 200,
                layout: 'absolute',
                renderTo: Ext.getBody(),
                width: 200,
                xtype: 'container',
                defaults: {
                    anchor: '-10',
                    layout: 'border',
                    height: 25,
                    x: 10
                },
                items: [
                    {
                        itemId: 'border1',
                        y: 10
                    }, {
                        itemId: 'border2',
                        y: 45
                    }
                ]
            });

            var border1 = ct.down('#border1');

            var border2 = ct.down('#border2');

            expect(ct.getWidth()).toBe(200);
            expect(ct.getHeight()).toBe(200);

            expect(border1.getWidth()).toBe(180);
            expect(border1.getHeight()).toBe(25);
            expect(getLeft(ct, border1)).toBe(10);
            expect(getTop(ct, border1)).toBe(10);

            expect(border2.getWidth()).toBe(180);
            expect(border2.getHeight()).toBe(25);
            expect(getLeft(ct, border2)).toBe(10);
            expect(getTop(ct, border2)).toBe(45);
        });
    });

    // Most of the specs in this suite are temporarily disabled because they are
    // too expensive due to all the animations.  TODO: revisit this once we have
    // the ability to run specs on a nightly basis only. See EXTJSIV-6971
    describe("collapsing/expanding/floating", function() {
        // The purpose of this suite is to provide coverage for the different
        // possible combinations of collapsing, expanding, and floating of 
        // panels within a border layout to ensure that the timing of their
        // animations do not disrupt the layout.
        var positions = {
                north: { x: 0, y: 0 },
                east: { x: 200, y: 100 },
                south: { x: 0, y: 200 },
                west: { x: 0, y: 100 }
            },
            floatOffsets = {
                north: { x: 0, y: 27 },
                east: { x: -27, y: 0 },
                south: { x: 0, y: -27 },
                west: { x: 27, y: 0 }
            },
            northCollapsedPositions = Ext.clone(positions),
            panel, regions;

            northCollapsedPositions.east.y = northCollapsedPositions.west.y = 28;

        beforeEach(function() {
            panel = Ext.widget({
                renderTo: Ext.getBody(),
                xtype: 'panel',
                layout: 'border',
                height: 302,
                width: 302,
                items: [{
                    id: 'north',
                    region: 'north',
                    title: 'north',
                    collapsible: true,
                    height: 100,
                    animCollapse: 30
                }, {
                    id: 'east',
                    region: 'east',
                    title: 'east',
                    collapsible: true,
                    width: 100,
                    animCollapse: 30
                }, {
                    id: 'south',
                    region: 'south',
                    title: 'south',
                    collapsible: true,
                    height: 100,
                    animCollapse: 30
                }, {
                    id: 'west',
                    region: 'west',
                    title: 'west',
                    collapsible: true,
                    width: 100,
                    animCollapse: 30
                }, {
                    id: 'center',
                    region: 'center'
                }]
            });
            regions = {
                north: Ext.getCmp('north'),
                east: Ext.getCmp('east'),
                south: Ext.getCmp('south'),
                west: Ext.getCmp('west'),
                center: Ext.getCmp('center')
            };
        });

        afterEach(function() {
            panel.destroy();
        });

        describe("collapse collapse", function() {
            function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0;

                panel1.collapse();
                panel1.on('collapse', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.collapse();
                    panel2.on('collapse', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(false);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            todoIt("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("collapse expand", function() {
            function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel1.id === 'north' ? northCollapsedPositions : positions,
                    pos2;

                panel2.collapse(null, false);
                panel1.collapse();
                panel1.on('collapse', function() {
                    anim++;
                });
                waitsForAnimation();
                runs(function() {
                    panel2.expand();
                    panel2.on('expand', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos2[0]).toBeApprox(pos[panel2.id].x);
                    expect(pos2[1]).toBeApprox(pos[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 100, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 172);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 100, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 100);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 100);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 172, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 172);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 100, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 100, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 100);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 172, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 100);
            });
        });

        describe("collapse float", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel1.id === 'north' ? northCollapsedPositions : positions,
                    pos2;

                panel2.collapse(null, false);
                panel1.collapse();
                panel1.on('collapse', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.floatCollapsedPanel();
                    panel2.on('float', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos2[0]).toBeApprox(pos[panel2.id].x + floatOffsets[panel2.id].x);
                    expect(pos2[1]).toBeApprox(pos[panel2.id].y + floatOffsets[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("collapse slideout", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    floated = false;

                panel2.collapse(null, false);
                panel2.floatCollapsedPanel();
                panel2.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel1.collapse();
                    panel1.on('collapse', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.slideOutFloatedPanel();
                    panel2.on('unfloat', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(false);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("expand collapse", function() {
            function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel2.id === 'north' ? northCollapsedPositions : positions,
                    pos1;

                panel1.collapse(null, false);
                panel1.expand();
                panel1.on('expand', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.collapse();
                    panel2.on('collapse', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(false);
                    pos1 = panel1.getPosition(true);
                    expect(pos1[0]).toBeApprox(pos[panel1.id].x);
                    expect(pos1[1]).toBeApprox(pos[panel1.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 100);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 172);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 100);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 100, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 100, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 172, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 172);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 100);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 100);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 100, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 172, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 100, 172);
            });
        });

        describe("expand expand", function() {
            function doTest(region1, region2) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos1, pos2;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.expand();
                panel1.on('expand', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.expand();
                    panel2.on('expand', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos1 = panel1.getPosition(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos1[0]).toBeApprox(positions[panel1.id].x);
                    expect(pos1[1]).toBeApprox(positions[panel1.id].y);
                    expect(pos2[0]).toBeApprox(positions[panel2.id].x);
                    expect(pos2[1]).toBeApprox(positions[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(100);
                    expect(center.getHeight()).toBeApprox(100);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east');
            });

            xit("should handle north south", function() {
                doTest('north', 'south');
            });

            xit("should handle north west", function() {
                doTest('north', 'west');
            });

            xit("should handle east north", function() {
                doTest('east', 'north');
            });

            xit("should handle east south", function() {
                doTest('east', 'south');
            });

            xit("should handle east west", function() {
                doTest('east', 'west');
            });

            xit("should handle south north", function() {
                doTest('south', 'north');
            });

            xit("should handle south east", function() {
                doTest('south', 'east');
            });

            xit("should handle south west", function() {
                doTest('south', 'west');
            });

            xit("should handle west north", function() {
                doTest('west', 'north');
            });

            xit("should handle west east", function() {
                doTest('west', 'east');
            });

            xit("should handle west south", function() {
                doTest('west', 'south');
            });
        });

        describe("expand float", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel2.id === 'north' ? northCollapsedPositions : positions,
                    pos1, pos2;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.expand();
                panel1.on('expand', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.floatCollapsedPanel();
                    panel2.on('float', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos1 = panel1.getPosition(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos1[0]).toBeApprox(pos[panel1.id].x);
                    expect(pos1[1]).toBeApprox(pos[panel1.id].y);
                    expect(pos2[0]).toBeApprox(pos[panel2.id].x + floatOffsets[panel2.id].x);
                    expect(pos2[1]).toBeApprox(pos[panel2.id].y + floatOffsets[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 100);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 172);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 100);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 100, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 100, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 172, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 172);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 100);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 100);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 100, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 172, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 100, 172);
            });
        });

        describe("expand slideout", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel2.id === 'north' ? northCollapsedPositions : positions,
                    floated = false,
                    pos1;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel2.floatCollapsedPanel();
                panel2.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel1.expand();
                    panel1.on('expand', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.slideOutFloatedPanel();
                    panel2.on('unfloat', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(false);
                    pos1 = panel1.getPosition(true);
                    expect(pos1[0]).toBeApprox(pos[panel1.id].x);
                    expect(pos1[1]).toBeApprox(pos[panel1.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 100);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 172);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 100);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 100, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 100, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 172, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 172);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 100);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 100);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 100, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 172, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 100, 172);
            });
        });

        describe("float collapse", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos1;

                panel1.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel1.on('float', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.collapse();
                    panel2.on('collapse', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(false);
                    pos1 = panel1.getPosition(true);
                    expect(pos1[0]).toBeApprox(positions[panel1.id].x + floatOffsets[panel1.id].x);
                    expect(pos1[1]).toBeApprox(positions[panel1.id].y + floatOffsets[panel1.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            todoIt("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("float expand", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel2.id === 'north' ? northCollapsedPositions : positions,
                    pos1;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel1.on('float', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.expand();
                    panel2.on('expand', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos1 = panel1.getPosition(true);
                    expect(pos1[0]).toBeApprox(pos[panel1.id].x + floatOffsets[panel1.id].x);
                    expect(pos1[1]).toBeApprox(pos[panel1.id].y + floatOffsets[panel1.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 100, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 172);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 100, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 100);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 100);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 172, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 172);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 100, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 100, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 100);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 172, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 100);
            });
        });

        describe("float float", function() {
           function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = (panel1.id === 'north' || panel2.id === 'north') ? northCollapsedPositions : positions,
                    pos1, pos2;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel1.on('float', function() {
                    anim++;
                });
                waits(15);
                runs(function() {
                    panel2.floatCollapsedPanel();
                    panel2.on('float', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos1 = panel1.getPosition(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos1[0]).toBeApprox(pos[panel1.id].x + floatOffsets[panel1.id].x);
                    expect(pos1[1]).toBeApprox(pos[panel1.id].y + floatOffsets[panel1.id].y);
                    expect(pos2[0]).toBeApprox(pos[panel2.id].x + floatOffsets[panel2.id].x);
                    expect(pos2[1]).toBeApprox(pos[panel2.id].y + floatOffsets[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("float slideout", function() {
          function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel2.id === 'north' ? northCollapsedPositions : positions,
                    floated = false,
                    pos1;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel2.floatCollapsedPanel();
                panel2.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel1.floatCollapsedPanel();
                    panel1.on('float', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.slideOutFloatedPanel();
                    panel2.on('unfloat', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(true);
                    expect(panel2.el.isVisible()).toBe(false);
                    pos1 = panel1.getPosition(true);
                    expect(pos1[0]).toBeApprox(pos[panel1.id].x + floatOffsets[panel1.id].x);
                    expect(pos1[1]).toBeApprox(pos[panel1.id].y + floatOffsets[panel1.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("slideout collapse", function() {
          function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    floated = false;

                panel1.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel1.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel1.slideOutFloatedPanel();
                    panel1.on('unfloat', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.collapse();
                    panel2.on('collapse', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(false);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            todoIt("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("slideout expand", function() {
          function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel1.id === 'north' ? northCollapsedPositions : positions,
                    floated = false,
                    pos2;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel1.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel1.slideOutFloatedPanel();
                    panel1.on('unfloat', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.expand();
                    panel2.on('expand', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos2[0]).toBeApprox(pos[panel2.id].x);
                    expect(pos2[1]).toBeApprox(pos[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 100, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 172);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 100, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 100);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 100);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 172, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 172);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 100, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 100, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 100);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 172, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 100);
            });
        });

        describe("slideout float", function() {
          function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    pos = panel1.id === 'north' ? northCollapsedPositions : positions,
                    floated = false,
                    pos2;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel1.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel1.slideOutFloatedPanel();
                    panel1.on('unfloat', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.floatCollapsedPanel();
                    panel2.on('float', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(true);
                    pos2 = panel2.getPosition(true);
                    expect(pos2[0]).toBeApprox(pos[panel2.id].x + floatOffsets[panel2.id].x);
                    expect(pos2[1]).toBeApprox(pos[panel2.id].y + floatOffsets[panel2.id].y);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("slideout slideout", function() {
          function doTest(region1, region2, centerWidth, centerHeight) {
                var panel1 = regions[region1],
                    panel2 = regions[region2],
                    center = regions.center,
                    anim = 0,
                    floated = 0;

                panel1.collapse(null, false);
                panel2.collapse(null, false);
                panel1.floatCollapsedPanel();
                panel2.floatCollapsedPanel();
                panel1.on('float', function() {
                    floated++;
                });
                panel2.on('float', function() {
                    floated++;
                });
                waitsFor(function() {
                    return floated === 2;
                });
                runs(function() {
                    panel1.slideOutFloatedPanel();
                    panel1.on('unfloat', function() {
                        anim++;
                    });
                });
                waits(15);
                runs(function() {
                    panel2.slideOutFloatedPanel();
                    panel2.on('unfloat', function() {
                        anim++;
                    });
                });
                waitsFor(function() {
                    return anim === 2;
                }, "animation never completed", 500);
                runs(function() {
                    expect(panel1.el.isVisible()).toBe(false);
                    expect(panel2.el.isVisible()).toBe(false);
                    expect(center.getWidth()).toBeApprox(centerWidth);
                    expect(center.getHeight()).toBeApprox(centerHeight);
                });
            }

            it("should handle north east", function() {
                doTest('north', 'east', 172, 172);
            });

            xit("should handle north south", function() {
                doTest('north', 'south', 100, 248);
            });

            xit("should handle north west", function() {
                doTest('north', 'west', 172, 172);
            });

            xit("should handle east north", function() {
                doTest('east', 'north', 172, 172);
            });

            xit("should handle east south", function() {
                doTest('east', 'south', 172, 172);
            });

            xit("should handle east west", function() {
                doTest('east', 'west', 248, 100);
            });

            xit("should handle south north", function() {
                doTest('south', 'north', 100, 248);
            });

            xit("should handle south east", function() {
                doTest('south', 'east', 172, 172);
            });

            xit("should handle south west", function() {
                doTest('south', 'west', 172, 172);
            });

            xit("should handle west north", function() {
                doTest('west', 'north', 172, 172);
            });

            xit("should handle west east", function() {
                doTest('west', 'east', 248, 100);
            });

            xit("should handle west south", function() {
                doTest('west', 'south', 172, 172);
            });
        });

        describe("expanding a floated panel", function() {
          function doTest(region) {
                var panel = regions[region],
                    center = regions.center,
                    animDone = false,
                    floated = false;

                panel.collapse(null, false);
                panel.floatCollapsedPanel();
                panel.on('float', function() {
                    floated = true;
                });
                waitsFor(function() {
                    return floated;
                });
                runs(function() {
                    panel.expand();
                    panel.on('expand', function() {
                        animDone = true;
                    });
                });
                waitsFor(function() {
                    return animDone;
                });
                runs(function() {
                    expect(panel.el.isVisible()).toBe(true);
                    // we have to round the values because moveTo can produce
                    // fractional pixel values.  This shouldn't be needed once
                    // EXTJSIV-6954 is fixed.
                    expect(Math.round(parseFloat(panel.el.getStyle('left')))).toBeApprox(positions[panel.id].x);
                    expect(Math.round(parseFloat(panel.el.getStyle('top')))).toBeApprox(positions[panel.id].y);
                    expect(center.getWidth()).toBeApprox(100);
                    expect(center.getHeight()).toBeApprox(100);
                });
            }

            it("should handle north", function() {
                doTest('north');
            });

            xit("should handle east", function() {
                doTest('east');
            });

            xit("should handle south", function() {
                doTest('south');
            });

            xit("should handle west", function() {
                doTest('west');
            });
        });

    });

    describe("floating", function() {
        var ctSize = 400,
            region, comp, floatSpy, unfloatSpy;

        beforeEach(function() {
            floatSpy = jasmine.createSpy();
            unfloatSpy = jasmine.createSpy();
        });

        afterEach(function() {
            comp = region = floatSpy = unfloatSpy = null;
        });

        function waitForFloat(p) {
            runs(function() {
                floatSpy.reset();
                p.un('float', floatSpy);
                p.on('float', floatSpy);
                p.floatCollapsedPanel();
            });
            waitsFor(function() {
                return floatSpy.callCount > 0;
            }, "Never floated");
        }

        function waitForUnfloat(p) {
            runs(function() {
                unfloatSpy.reset();
                p.un('unfloat', unfloatSpy);
                p.on('unfloat', unfloatSpy);
                p.slideOutFloatedPanel();
            });
            waitsFor(function() {
                return unfloatSpy.callCount > 0;
            }, "Never unfloated");
        }

        function makeWithRegion(regionName, regionCfg) {
            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                border: false,
                layout: 'border',
                height: ctSize,
                width: ctSize,
                items: [Ext.apply({
                    xtype: 'panel',
                    itemId: regionName,
                    region: regionName,
                    collapsible: true,
                    border: false,
                    bodyBorder: false,
                    animCollapse: 1,
                    layout: 'fit',
                    items: {
                        xtype: 'component',
                        itemId: 'comp'
                    }
                }, regionCfg), {
                    region: 'center'
                }]
            });
            region = ct.down('#' + regionName);
            comp = ct.down('#comp');
        }

        describe("positioning", function() {
            describe("north", function() {
                var regionHeight = 150;

                beforeEach(function() {
                    makeWithRegion('north', {
                        height: regionHeight
                    });
                });

                it("should position the floater below the placeholder", function() {
                    region.collapse(null, false);
                    var placeHeight = region.placeholder.getHeight();

                    waitForFloat(region);
                    runs(function() {
                        var box = region.getBox();

                        expect(box.left).toBe(0);
                        expect(box.right).toBe(ctSize);
                        expect(box.top).toBe(placeHeight);
                        expect(box.bottom).toBe(placeHeight + regionHeight);
                    });
                });

                it("should reset the position if the container resizes", function() {
                    region.collapse(null, false);
                    var placeHeight = region.placeholder.getHeight();

                    waitForFloat(region);
                    runs(function() {
                        ct.setSize(ctSize - 100, ctSize - 100);

                        var box = region.getBox();

                        expect(box.left).toBe(0);
                        expect(box.right).toBe(ctSize - 100);
                        expect(box.top).toBe(placeHeight);
                        expect(box.bottom).toBe(placeHeight + regionHeight);
                    });
                });
            });

            describe("south", function() {
                var regionHeight = 150;

                beforeEach(function() {
                    makeWithRegion('south', {
                        height: regionHeight
                    });
                });

                it("should position the floater above the placeholder", function() {
                    region.collapse(null, false);
                    var placeHeight = region.placeholder.getHeight();

                    waitForFloat(region);
                    runs(function() {
                        var box = region.getBox();

                        expect(box.left).toBe(0);
                        expect(box.right).toBe(ctSize);
                        expect(box.top).toBe(ctSize - placeHeight - regionHeight);
                        expect(box.bottom).toBe(ctSize - placeHeight);
                    });
                });

                it("should reset the position if the container resizes", function() {
                    region.collapse(null, false);
                    var placeHeight = region.placeholder.getHeight();

                    waitForFloat(region);
                    runs(function() {
                        ct.setSize(ctSize - 100, ctSize - 100);

                        var box = region.getBox();

                        expect(box.left).toBe(0);
                        expect(box.right).toBe(ctSize - 100);
                        expect(box.top).toBe(ctSize - placeHeight - regionHeight - 100);
                        expect(box.bottom).toBe(ctSize - placeHeight - 100);
                    });
                });
            });

            describe("west", function() {
                var regionWidth = 150;

                beforeEach(function() {
                    makeWithRegion('west', {
                        width: regionWidth
                    });
                });

                it("should position the floater to the right of the placeholder", function() {
                    region.collapse(null, false);
                    var placeWidth = region.placeholder.getWidth();

                    waitForFloat(region);
                    runs(function() {
                        var box = region.getBox();

                        expect(box.left).toBe(placeWidth);
                        expect(box.right).toBe(placeWidth + regionWidth);
                        expect(box.top).toBe(0);
                        expect(box.bottom).toBe(ctSize);
                    });
                });

                it("should reset the position if the container resizes", function() {
                    region.collapse(null, false);
                    var placeWidth = region.placeholder.getWidth();

                    waitForFloat(region);
                    runs(function() {
                        ct.setSize(ctSize - 100, ctSize - 100);

                        var box = region.getBox();

                        expect(box.left).toBe(placeWidth);
                        expect(box.right).toBe(placeWidth + regionWidth);
                        expect(box.top).toBe(0);
                        expect(box.bottom).toBe(ctSize - 100);
                    });
                });
            });

            describe("east", function() {
                var regionWidth = 150;

                beforeEach(function() {
                    makeWithRegion('east', {
                        width: regionWidth
                    });
                });

                it("should position the floater to the left of the placeholder", function() {
                    region.collapse(null, false);
                    var placeWidth = region.placeholder.getWidth();

                    waitForFloat(region);
                    runs(function() {
                        var box = region.getBox();

                        expect(box.left).toBe(ctSize - placeWidth - regionWidth);
                        expect(box.right).toBe(ctSize - placeWidth);
                        expect(box.top).toBe(0);
                        expect(box.bottom).toBe(ctSize);
                    });
                });

                it("should reset the position if the container resizes", function() {
                    region.collapse(null, false);
                    var placeWidth = region.placeholder.getWidth();

                    waitForFloat(region);
                    runs(function() {
                        ct.setSize(ctSize - 100, ctSize - 100);

                        var box = region.getBox();

                        expect(box.left).toBe(ctSize - placeWidth - regionWidth - 100);
                        expect(box.right).toBe(ctSize - placeWidth - 100);
                        expect(box.top).toBe(0);
                        expect(box.bottom).toBe(ctSize - 100);
                    });
                });
            });
        });

        describe("sizing", function() {
            describe("north", function() {
                beforeEach(function() {
                    makeWithRegion('north', {
                        height: 100
                    });
                });

                it("should layout the size correctly when layout updates while floating", function() {
                    var width = region.getWidth();

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        comp.setHtml('Foo');
                        expect(comp.getWidth()).toBe(width);
                    });

                });

                it("should update the size of the floater if floated if the ct size changes", function() {
                    var width = region.getWidth() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        ct.setWidth(width);
                        // Subtract 100 because we started at 400
                        expect(comp.getWidth()).toBe(width);
                    });
                });

                it("should update the size of the floater if not floated if the ct size changes", function() {
                    var width = region.getWidth() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    waitForUnfloat(region);
                    runs(function() {
                        ct.setWidth(width);
                    });
                    waitForFloat(region);
                    runs(function() {
                        // Subtract 100 because we started at 400
                        expect(comp.getWidth()).toBe(width);
                    });
                });
            });

            describe("south", function() {
                beforeEach(function() {
                    makeWithRegion('south', {
                        height: 100
                    });
                });

                it("should layout the size correctly when layout updates while floating", function() {
                    var width = region.getWidth();

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        comp.setHtml('Foo');
                        expect(comp.getWidth()).toBe(width);
                    });

                });

                it("should update the size of the floater if floated if the ct size changes", function() {
                    var width = region.getWidth() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        ct.setWidth(width);
                        // Subtract 100 because we started at 400
                        expect(comp.getWidth()).toBe(width);
                    });
                });

                it("should update the size of the floater if not floated if the ct size changes", function() {
                    var width = region.getWidth() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    waitForUnfloat(region);
                    runs(function() {
                        ct.setWidth(width);
                    });
                    waitForFloat(region);
                    runs(function() {
                        // Subtract 100 because we started at 400
                        expect(comp.getWidth()).toBe(width);
                    });
                });
            });

            describe("west", function() {
                beforeEach(function() {
                    makeWithRegion('west', {
                        width: 100
                    });
                });

                it("should layout the size correctly when layout updates while floating", function() {
                    var height = region.getHeight();

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        comp.setHtml('Foo');
                        expect(comp.getHeight()).toBe(height - region.header.getHeight());
                    });

                });

                it("should update the size of the floater if floated if the ct size changes", function() {
                    var height = region.getHeight() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        ct.setHeight(height);
                        // Subtract 100 because we started at 400
                        expect(comp.getHeight()).toBe(height - region.header.getHeight());
                    });
                });

                it("should update the size of the floater if not floated if the ct size changes", function() {
                    var height = region.getHeight() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    waitForUnfloat(region);
                    runs(function() {
                        ct.setHeight(height);
                    });
                    waitForFloat(region);
                    runs(function() {
                        // Subtract 100 because we started at 400
                        expect(comp.getHeight()).toBe(height - region.header.getHeight());
                    });
                });
            });

            describe("east", function() {
                beforeEach(function() {
                    makeWithRegion('east', {
                        width: 100
                    });
                });

                it("should layout the size correctly when layout updates while floating", function() {
                    var height = region.getHeight();

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        comp.setHtml('Foo');
                        expect(comp.getHeight()).toBe(height - region.header.getHeight());
                    });

                });

                it("should update the size of the floater if floated if the ct size changes", function() {
                    var height = region.getHeight() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    runs(function() {
                        ct.setHeight(height);
                        // Subtract 100 because we started at 400
                        expect(comp.getHeight()).toBe(height - region.header.getHeight());
                    });
                });

                it("should update the size of the floater if not floated if the ct size changes", function() {
                    var height = region.getHeight() - 100;

                    region.collapse(null, false);
                    waitForFloat(region);
                    waitForUnfloat(region);
                    runs(function() {
                        ct.setHeight(height);
                    });
                    waitForFloat(region);
                    runs(function() {
                        // Subtract 100 because we started at 400
                        expect(comp.getHeight()).toBe(height - region.header.getHeight());
                    });
                });
            });
        });
    });

    describe("adding items dynamically", function() {
        it("should be able to add a collapsed region", function() {
            createBorderLayout([{
                region: 'north',
                title: 'North'
            }, {
                region: 'center'
            }]);

            var added = ct.add({
                xtype: 'panel',
                title: 'South',
                region: 'south',
                collapsible: true,
                collapsed: true,
                height: 100,
                animCollapse: false
            });

            added.expand();
            expect(added.getHeight()).toBe(100);
        });
    });

    describe("focus management", function() {
        var asyncPressKey = jasmine.asyncPressKey,
            focusAndWait = jasmine.focusAndWait,
            expectFocused = jasmine.expectFocused,
            regions = ['north', 'east', 'south', 'west'],
            i, len, region;

        function makeRegionSuite(region, animate) {
            describe(region + " animCollapse: " + !!animate, function() {
                var panel, ph, collapseTool, expandTool, btn,
                    collapseSpy, expandSpy;

                beforeEach(function() {
                    collapseSpy = jasmine.createSpy('collapse');
                    expandSpy   = jasmine.createSpy('expand');

                    createBorderLayout([{
                        xtype: 'panel',
                        title: 'foo',
                        region: region,
                        collapsible: true,
                        testRegion: true,
                        animCollapse: animate,
                        listeners: {
                            collapse: collapseSpy,
                            expand: expandSpy
                        }
                    }, {
                        xtype: 'panel',
                        region: 'center',
                        items: [{
                            xtype: 'button',
                            text: 'bar'
                        }]
                    }]);

                    panel = ct.down('panel[testRegion]');
                    btn   = ct.down('button');

                    collapseTool = panel.collapseTool;
                });

                afterEach(function() {
                    panel = ph = collapseTool = expandTool = btn = null;
                    collapseSpy = expandSpy = null;
                });

                describe("tools when expanded", function() {
                    it("should have a collapse tool", function() {
                        expect(collapseTool.type).toMatch(/^collapse-/);
                    });

                    // Panel header is a FocusableContainer, and tools are managed by it
                    it("tool should be focusable", function() {
                        expect(collapseTool.el.isFocusable()).toBe(true);
                    });
                });

                describe("tools when collapsed", function() {
                    beforeEach(function() {
                        runs(function() {
                            panel.collapse();
                        });

                        waitForSpy(collapseSpy, 'collapse', 1000);

                        runs(function() {
                            ph = ct.down('[placeholderFor]');
                            expandTool = ph.expandTool;
                        });
                    });

                    it("should have an expand tool", function() {
                        expect(expandTool.type).toMatch(/^expand-/);
                    });

                    it("should be tabbable", function() {
                        expect(expandTool.el.isFocusable()).toBe(true);
                    });
                });

                describe("pointer interaction", function() {
                    describe("collapsing", function() {
                        beforeEach(function() {
                            focusAndWait(btn);

                            jasmine.fireMouseEvent(collapseTool.el, 'click');

                            waitForSpy(collapseSpy, 'collapse', 1000);

                            runs(function() {
                                ph = ct.down('[placeholderFor]');
                                expandTool = ph.expandTool;
                            });
                        });

                        it("should collapse", function() {
                            expect(!!panel.collapsed).toBe(true);
                        });

                        it("should not steal focus from button", function() {
                            expectFocused(btn);
                        });

                        describe("expanding", function() {
                            it("should expand", function() {
                                jasmine.fireMouseEvent(expandTool.el, 'click');

                                waitForSpy(expandSpy, 'expand', 1000);

                                runs(function() {
                                    expect(!!panel.collapsed).toBe(false);
                                });
                            });

                            it("should not steal focus from button", function() {
                                expectFocused(btn);
                            });
                        });
                    });
                });

                describe("keyboard interaction", function() {
                    function makeKeySuite(key) {
                        describe("by " + key + " key", function() {
                            describe("collapsing", function() {
                                beforeEach(function() {
                                    asyncPressKey(collapseTool, key);

                                    waitForSpy(collapseSpy, 'collapse', 1000);

                                    runs(function() {
                                        ph = ct.down('[placeholderFor]');
                                        expandTool = ph.expandTool;
                                    });
                                });

                                it("should collapse", function() {
                                    expect(!!panel.collapsed).toBe(true);
                                });

                                it("should place focus on expand tool", function() {
                                    expectFocused(expandTool);
                                });

                                describe("expanding", function() {
                                    beforeEach(function() {
                                        asyncPressKey(expandTool, key);

                                        waitForSpy(expandSpy, 'expand', 1000);
                                    });

                                    it("should expand", function() {
                                        expect(!!panel.collapsed).toBe(false);
                                    });

                                    it("should place focus on collapse tool", function() {
                                        expectFocused(collapseTool);
                                    });
                                });
                            });
                        });
                    }

                    makeKeySuite('space');
                    makeKeySuite('enter');
                });
            });
        }

        for (i = 0, len = regions.length; i < len; i++) {
            region = regions[i];

            makeRegionSuite(region, 100);
            makeRegionSuite(region, false);
        }
    });
});
