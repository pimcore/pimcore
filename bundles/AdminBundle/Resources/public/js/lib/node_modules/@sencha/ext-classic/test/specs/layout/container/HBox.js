topSuite("Ext.layout.container.HBox", ['Ext.Panel', 'Ext.layout.container.Fit'], function() {
    var ct, c;

    afterEach(function() {
        Ext.destroy(ct, c);
        ct = c = null;
    });

    describe("defaults", function() {
        var counter = 0,
            proto = Ext.layout.container.HBox.prototype;

        beforeEach(function() {
            // We only need to create a layout instance once to wire up configs
            if (!counter) {
                ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    layout: 'hbox',
                    width: 100,
                    height: 100
                });

                counter++;
            }
        });

        it("should have align: begin", function() {
            expect(proto.align).toBe('begin');
        });

        it("should have constrainAlign: false", function() {
            expect(proto.constrainAlign).toBe(false);
        });

        it("should have enableSplitters: true", function() {
            expect(proto.enableSplitters).toBe(true);
        });

        it("should have no padding", function() {
            expect(proto.padding).toBe(0);
        });

        it("should have pack start", function() {
            expect(proto.pack).toBe('start');
        });
    });

    describe("removing items", function() {
        it("should clear the left on an item when removing and using in another container", function() {
            c = new Ext.Component({
                width: 50
            });

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'hbox',
                width: 100,
                height: 100,
                items: [{
                    width: 50
                }, c]
            });

            var other = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'fit',
                width: 100,
                height: 100
            });

            ct.remove(c, false);
            other.add(c);

            var left = c.getEl().getStyle('left');

            // Normalize left value
            if (left === 'auto') {
                left = '';
            }
            else if (left === '0px') {
                left = '';
            }

            expect(left).toBe('');

            other.destroy();
        });

        it("should remove an item when the item is not rendered and the item is not destroying", function() {
            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                collapsed: true,
                layout: 'hbox',
                width: 100,
                height: 100
            });

            // When adding the item to the collapsed panel, it won't render
            c = ct.add({});

            expect(function() {
                ct.remove(0, false);
            }).not.toThrow();
        });
    });

    describe('sizes as percentages', function() {
        it('should correctly size items using percentages', function() {
            ct = Ext.widget({
                xtype: 'container',
                layout: 'hbox',
                width: 300,
                height: 200,
                renderTo: Ext.getBody(),
                items: [{
                    xtype: 'component',
                    width: '20%',
                    height: 100
                }, {
                    xtype: 'component',
                    width: 30,
                    height: '75%'
                }, {
                    xtype: 'component',
                    flex: 1,
                    height: '100%'
                }, {
                    xtype: 'component',
                    flex: 2,
                    html: '<div style="height:50px"></div>'
                }]
            });

            expect(ct).toHaveLayout({
                el: { w: 300, h: 200 },
                items: {
                    0: { el: { xywh: '0 0 60 100' } },
                    1: { el: { xywh: '60 0 30 150' } },
                    2: { el: { xywh: '90 0 70 200' } },
                    3: { el: { xywh: '160 0 140 50' } }
                }
            });
        });
    });

    describe("nested box layouts", function() {
        var childConfig = {
            xtype: "component",
            style: 'border: 1px solid blue;',
            html: "child 1 content"
        };

        it("should handle auto width of nested boxes", function() {
            // make the child outside of any container and get its proper height:
            c = Ext.widget(childConfig);
            c.render(Ext.getBody());
            var height = c.getHeight();

            // now nest the child inside a box inside a box
            ct = Ext.widget({
                xtype: 'container',
                layout: "auto",
                style: 'padding: 10px',
                renderTo: Ext.getBody(),
                width: 500, height: 300,
                items: [{
                    xtype: 'container',
                    layout: {
                        type: "hbox",
                        align: "stretchmax"
                    },
                    style: 'border: 1px solid yellow',
                    items: [{
                        xtype: "container",
                        itemId: "column1Id",
                        layout: "vbox",
                        style: 'border: 1px solid red;',
                        flex: 1,
                        items: [Ext.apply({
                            itemId: "child1"
                        }, childConfig), {
                            xtype: "component",
                            itemId: "column1Child2Id",
                            style: 'border: 1px solid green;',
                            html: "child 2 content"
                        }]
                    }, {
                        flex: 1,
                        style: 'border: 1px solid blue;'
                    }]
                }]
            });

            // make sure we get the same height for the nested version:
            var c1 = ct.down('#child1');

            expect(c1.getHeight()).toBe(height);
        });
    });

    describe("padding", function() {
        function makeCt(pad) {
            c = new Ext.Component({
                flex: 1
            });

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                width: 100,
                height: 80,
                layout: {
                    type: 'hbox',
                    align: 'stretch',
                    padding: pad
                },
                items: c
            });
        }

        it("should not add any padding by default", function() {
            makeCt(0);
            expect(c.getWidth()).toBe(100);
            expect(c.getHeight()).toBe(80);
        });

        it("should read a padding number", function() {
            makeCt(5);
            expect(c.getWidth()).toBe(90);
            expect(c.getHeight()).toBe(70);
        });

        it("should read a padding string", function() {
            makeCt('1 2 3 4');
            expect(c.getWidth()).toBe(94);
            expect(c.getHeight()).toBe(76);
        });
    });

    it("should apply margin to components", function() {
        ct = new Ext.container.Container({
            width: 200,
            height: 200,
            renderTo: Ext.getBody(),
            defaultType: 'component',
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            defaults: {
                flex: 1,
                margin: 5
            },
            items: [{}, {}]
        });

        expect(ct.items.first().getY()).toBe(5);
        expect(ct.items.first().getX()).toBe(5);

        expect(ct.items.last().getY()).toBe(5);
        expect(ct.items.last().getX()).toBe(105);
    });

    describe("pack", function() {
        function makeCt(pack) {
            ct = new Ext.container.Container({
                defaultType: 'component',
                renderTo: Ext.getBody(),
                width: 600,
                height: 600,
                layout: {
                    type: 'hbox',
                    pack: pack
                },
                items: [{
                    width: 30
                }, {
                    width: 40
                }, {
                    width: 20
                }]
            });
        }

        function getX(index) {
            return ct.items.getAt(index).el.getX();
        }

        it("should pack at the left with pack: start", function() {
            makeCt('start');
            expect(getX(0)).toBe(0);
            expect(getX(1)).toBe(30);
            expect(getX(2)).toBe(70);
        });

        it("should pack in the middle with pack: center", function() {
            makeCt('center');
            expect(getX(0)).toBe(255);
            expect(getX(1)).toBe(285);
            expect(getX(2)).toBe(325);
        });

        it("should pack at the right with pack: cend", function() {
            makeCt('end');
            expect(getX(0)).toBe(510);
            expect(getX(1)).toBe(540);
            expect(getX(2)).toBe(580);
        });
    });

    describe("align", function() {
        var getX, getY, getWidth, getHeight, makeCt;

        beforeEach(function() {
            makeCt = function(align, items, options) {
                options = options || {};
                ct = new Ext.container.Container({
                    defaultType: 'component',
                    renderTo: Ext.getBody(),
                    width: 600,
                    height: 600,
                    autoScroll: !!options.autoScroll,
                    layout: {
                        type: 'hbox',
                        align: align,
                        constrainAlign: !!options.constrainAlign
                    },
                    items: items
                });
            };

            getX = function(index) {
                return ct.items.getAt(index).getEl().getX();
            };

            getY = function(index) {
                return ct.items.getAt(index).getEl().getY();
            };

            getWidth = function(index) {
                return ct.items.getAt(index).getWidth();
            };

            getHeight = function(index) {
                return ct.items.getAt(index).getHeight();
            };
        });

        afterEach(function() {
            makeCt = getX = getY = getWidth = getHeight = null;
        });

        describe('top/middle/bottom', function() {

            it("should keep items at the top when using align: top", function() {
                makeCt('top', [{
                    html: 'a'
                }, {
                    html: 'b'
                }]);
                expect(getY(0)).toBe(0);
                expect(getY(1)).toBe(0);
            });

            it("should align items in the middle when using align: middle", function() {
                makeCt('middle', [{
                    height: 100
                }, {
                    height: 300
                }]);
                expect(getY(0)).toBe(250);
                expect(getY(1)).toBe(150);
            });

             it("should keep items to the bottom when using align: bottom", function() {
                makeCt('bottom', [{
                    html: 'a'
                }, {
                    html: 'b'
                }]);
                expect(getY(0)).toBe(600 - getHeight(0));
                expect(getY(1)).toBe(600 - getHeight(1));
            });

            describe("constrainAlign", function() {
                var makeLongString = function(c, len) {
                    var out = [],
                        i = 0;

                    for (; i < len; ++i) {
                        out.push(c);
                    }

                    return out.join('<br />');
                };

                it("should constrain a shrink wrapped item with align: top", function() {
                    makeCt('top', [{
                        html: makeLongString('A', 100)
                    }], {
                        constrainAlign: true
                    });
                    expect(getHeight(0)).toBe(600);
                    expect(getY(0)).toBe(0);
                });

                it("should constrain a shrink wrapped item with align: middle", function() {
                    makeCt('middle', [{
                        html: makeLongString('A', 100)
                    }], {
                        constrainAlign: true
                    });
                    expect(getHeight(0)).toBe(600);
                    expect(getY(0)).toBe(0);
                });

                it("should constrain a shrink wrapped item with align: bottom", function() {
                    makeCt('bottom', [{
                        html: makeLongString('A', 100)
                    }], {
                        constrainAlign: true
                    });
                    expect(getHeight(0)).toBe(600);
                    expect(getY(0)).toBe(0);
                });

                it("should not constrain a fixed height item", function() {
                    makeCt('top', [{
                        html: 'A',
                        height: 1000
                    }], {
                        constrainAlign: true
                    });
                    expect(getHeight(0)).toBe(1000);
                });

                it("should recalculate the left positions", function() {
                    makeCt('top', [{
                        html: makeLongString('A', 100)
                    }, {
                        html: 'B'
                    }], {
                        constrainAlign: true
                    });

                    expect(getX(0)).toBe(0);
                    expect(getX(1)).toBe(getWidth(0));
                });
            });
        });

        describe("stretchmax", function() {

            it("should stretch all items to the size of the largest when using align: stretchmax", function() {
                c = new Ext.Component({
                    renderTo: Ext.getBody(),
                    html: 'a<br />b<br />c'
                });

                var expected = c.getHeight({
                    floating: true
                });

                c.destroy();

                makeCt('stretchmax', [{
                    html: 'a<br />b'
                }, {
                    html: 'a<br />b<br />c'
                }, {
                    html: 'a<br />b'
                }]);

                expect(getHeight(0)).toBe(expected);
                expect(getHeight(1)).toBe(expected);
                expect(getHeight(2)).toBe(expected);
            });

            it("should always use a stretchmax over a fixed height", function() {
                makeCt('stretchmax', [{
                    height: 30
                }, {
                    html: 'a<br />b<br />c'
                }, {
                    html: 'a<br />b'
                }]);

                c = new Ext.Component({
                    renderTo: Ext.getBody(),
                    html: 'a<br />b<br />c',
                    floating: true
                });

                var expected = c.getHeight();

                c.destroy();

                expect(getHeight(0)).toBe(expected);
                expect(getHeight(1)).toBe(expected);
                expect(getHeight(2)).toBe(expected);
            });

            describe("minHeight", function() {
                it("should stretch an item with a minHeight", function() {
                    makeCt('stretchmax', [{
                        height: 30
                    }, {
                        minHeight: 5
                    }]);
                    expect(getHeight(0)).toBe(30);
                    expect(getHeight(1)).toBe(30);
                });

                it("should stretch to the item with the largest minHeight", function() {
                    makeCt('stretchmax', [{
                        minHeight: 30
                    }, {
                        minHeight: 50
                    }]);
                    expect(getHeight(0)).toBe(50);
                    expect(getHeight(1)).toBe(50);
                });

                it("should stretch a single item outside the bounds of the container", function() {
                    makeCt('stretchmax', [{
                        xtype: 'panel',
                        title: 'Title',
                        minHeight: 1000,
                        shrinkWrap: true,
                        shrinkWrapDock: true,
                        html: 'Content...'
                    }], {
                        autoScroll: true
                    });
                    expect(getHeight(0)).toBe(1000);
                });
            });

            it("should respect a maxHeight", function() {
                makeCt('stretchmax', [{
                    height: 30
                }, {
                    maxHeight: 20
                }]);
                expect(getHeight(0)).toBe(30);
                expect(getHeight(1)).toBe(20);
            });
        });

        it("should stretch all items to the container height", function() {
            makeCt('stretch', [{
             }, {
             }]);
            expect(getHeight(0)).toBe(600);
            expect(getHeight(1)).toBe(600);
        });
    });

    describe("width", function() {
        function makeCt(items) {
            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                width: 600,
                height: 100,
                defaultType: 'component',
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                items: items
            });
        }

        function getWidth(index) {
            return ct.items.getAt(index).getWidth();
        }

        describe("flex only", function() {
            it("should stretch a single flex item to the width of the container", function() {
                makeCt({
                    flex: 1
                });
                expect(getWidth(0)).toBe(600);
            });

            it("should stretch 3 equally flexed items equally", function() {
                makeCt([{
                    flex: 1
                }, {
                    flex: 1
                }, {
                    flex: 1
                }]);
                expect(getWidth(0)).toBe(200);
                expect(getWidth(1)).toBe(200);
                expect(getWidth(2)).toBe(200);
            });

            it("should flex 2 items according to ratio", function() {
                makeCt([{
                    flex: 3
                }, {
                    flex: 1
                }]);
                expect(getWidth(0)).toBe(450);
                expect(getWidth(1)).toBe(150);
            });

            it("should flex 4 items according to ratio", function() {
                makeCt([{
                    flex: 3
                }, {
                    flex: 1
                }, {
                    flex: 3
                }, {
                    flex: 1
                }]);
                expect(getWidth(0)).toBe(225);
                expect(getWidth(1)).toBe(75);
                expect(getWidth(2)).toBe(225);
                expect(getWidth(3)).toBe(75);
            });

            it("should use flex as a ratio", function() {
                makeCt([{
                    flex: 5000000
                }, {
                    flex: 1000000
                }]);
                expect(getWidth(0)).toBe(500);
                expect(getWidth(1)).toBe(100);
            });
        });

        describe("fixed width only", function() {
            it("should set the width of a single item", function() {
                makeCt({
                    width: 200
                });
                expect(getWidth(0)).toBe(200);
            });

            it("should set the width of multiple items", function() {
                makeCt([{
                    width: 500
                }, {
                    width: 50
                }]);
                expect(getWidth(0)).toBe(500);
                expect(getWidth(1)).toBe(50);
            });

            it("should allow a single item to exceed the container width", function() {
                makeCt({
                    width: 900
                });
                expect(getWidth(0)).toBe(900);
            });

            it("should allow multiple items to exceed the container width", function() {
                makeCt([{
                    width: 400
                }, {
                    width: 400
                }]);
                expect(getWidth(0)).toBe(400);
                expect(getWidth(1)).toBe(400);
            });
        });

        describe("%age", function() {
            it("should be able to use %age width", function() {
                makeCt([{
                    width: '50%'
                }, {
                    width: '50%'
                }]);
                expect(getWidth(0)).toBe(300);
                expect(getWidth(1)).toBe(300);
            });

            it("should work with fixed width", function() {
                makeCt([{
                    width: '20%'
                }, {
                    width: 380
                }, {
                    width: 100
                }]);
                expect(getWidth(0)).toBe(120);
                expect(getWidth(1)).toBe(380);
                expect(getWidth(2)).toBe(100);
            });

            it("should work with flex", function() {
                makeCt([{
                    flex: 2
                }, {
                    width: '50%'
                }, {
                    flex: 1
                }]);
                expect(getWidth(0)).toBe(200);
                expect(getWidth(1)).toBe(300);
                expect(getWidth(2)).toBe(100);
            });
        });

        describe("mixed", function() {
            it("should give any remaining space to a single flexed item", function() {
                makeCt([{
                    width: 200
                }, {
                    flex: 1
                }]);
                expect(getWidth(0)).toBe(200);
                expect(getWidth(1)).toBe(400);
            });

            it("should flex a single item with 2 fixed", function() {
                makeCt([{
                    width: 100
                }, {
                    flex: 1
                }, {
                    width: 300
                }]);
                expect(getWidth(0)).toBe(100);
                expect(getWidth(1)).toBe(200);
                expect(getWidth(2)).toBe(300);
            });

            it("should flex 2 items with 1 fixed", function() {
                makeCt([{
                    flex: 2
                }, {
                    width: 300
                }, {
                    flex: 1
                }]);
                expect(getWidth(0)).toBe(200);
                expect(getWidth(1)).toBe(300);
                expect(getWidth(2)).toBe(100);
            });

            it("should give priority to flex over a fixed width", function() {
                makeCt([{
                    flex: 1,
                    width: 200
                }, {
                    flex: 1
                }]);

                expect(getWidth(0)).toBe(300);
                expect(getWidth(1)).toBe(300);
            });

        });

        describe("min/max", function() {
            it("should assign a 0 width if there is no more flex width", function() {
                makeCt([{
                    flex: 1
                }, {
                    width: 700
                }]);
                expect(getWidth(0)).toBe(0);
                expect(getWidth(1)).toBe(700);
            });

            it("should respect a minWidth on a flex even if there is no more flex width", function() {
                makeCt([{
                    flex: 1,
                    minWidth: 50
                }, {
                    width: 700
                }]);
                expect(getWidth(0)).toBe(50);
                expect(getWidth(1)).toBe(700);
            });

            it("should respect a minWidth on a flex even if there is no excess flex width", function() {
                makeCt([{
                    flex: 1,
                    maxWidth: 100
                }, {
                    width: 300
                }]);
                expect(getWidth(0)).toBe(100);
                expect(getWidth(1)).toBe(300);
            });

            it("should update flex values based on min constraint", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        minWidth: 500
                    }),
                    c2 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2]);
                expect(c1.getWidth()).toBe(500);
                expect(c2.getWidth()).toBe(100);
            });

            it("should handle multiple min constraints", function() {
                 var c1 = new Ext.Component({
                        flex: 1,
                        minWidth: 250
                    }),
                    c2 = new Ext.Component({
                        flex: 1,
                        minWidth: 250
                    }),
                    c3 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getWidth()).toBe(250);
                expect(c2.getWidth()).toBe(250);
                expect(c3.getWidth()).toBe(100);
            });

            it("should update flex values based on max constraint", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        maxWidth: 100
                    }),
                    c2 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2]);
                expect(c1.getWidth()).toBe(100);
                expect(c2.getWidth()).toBe(500);
            });

            it("should update flex values based on multiple max constraints", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        maxWidth: 100
                    }),
                    c2 = new Ext.Component({
                        flex: 1,
                        maxWidth: 100
                    }),
                    c3 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getWidth()).toBe(100);
                expect(c2.getWidth()).toBe(100);
                expect(c3.getWidth()).toBe(400);
            });

            it("should give precedence to min constraints over flex when the min is the same", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        minWidth: 200
                    }),
                    c2 = new Ext.Component({
                        flex: 3,
                        minWidth: 200
                    }),
                    c3 = new Ext.Component({
                        flex: 1,
                        minWidth: 200
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getWidth()).toBe(200);
                expect(c2.getWidth()).toBe(200);
                expect(c3.getWidth()).toBe(200);
            });

            it("should give precedence to max constraints over flex when the max is the same", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        maxWidth: 100
                    }),
                    c2 = new Ext.Component({
                        flex: 3,
                        maxWidth: 100
                    }),
                    c3 = new Ext.Component({
                        flex: 1,
                        maxWidth: 100
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getWidth()).toBe(100);
                expect(c2.getWidth()).toBe(100);
                expect(c3.getWidth()).toBe(100);
            });

            describe("with %age", function() {
                it("should respect min constraints", function() {
                    makeCt([{
                        width: '10%',
                        minWidth: 400
                    }, {
                        flex: 1
                    }]);
                    expect(getWidth(0)).toBe(400);
                    expect(getWidth(1)).toBe(200);
                });

                it("should respect max constraints", function() {
                    makeCt([{
                        width: '70%',
                        maxWidth: 200
                    }, {
                        flex: 1
                    }]);
                    expect(getWidth(0)).toBe(200);
                    expect(getWidth(1)).toBe(400);
                });
            });
        });
    });

    describe("shrink wrap width", function() {
        var testHtml = 'a a',
            measure;

        function makeCt(items) {
            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                floating: true,
                shadow: false,
                height: 100,
                defaultType: 'component',
                style: 'font: 50px monospace', // 30px character width
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                items: items
            });
            var c = new Ext.Component({
                style: 'font: 50px monospace',
                floating: true,
                html: testHtml,
                autoShow: true
            });

            measure = c.getWidth();
            c.destroy();
        }

        function getWidth(index) {
            return ct.items.getAt(index).getWidth();
        }

        describe("flex only", function() {
            it("should shrink wrap a single flex item", function() {
                makeCt({
                    flex: 1,
                    html: testHtml // make sure we can shrinkwrap without wrapping the text
                });
                expect(getWidth(0)).toBe(measure);
                expect(ct.getWidth()).toBe(measure);
            });

            it("should shrink wrap 3 flexed items", function() {
                makeCt([{
                    flex: 1,
                    html: testHtml // shrink wrap without wrapping the text
                }, {
                    flex: 1,
                    html: testHtml // shrink wrap without wrapping the text
                }, {
                    flex: 2,
                    html: testHtml // shrink wrap without wrapping the text
                }]);
                expect(getWidth(0)).toBe(measure);
                expect(getWidth(1)).toBe(measure);
                expect(getWidth(2)).toBe(measure);
                expect(ct.getWidth()).toBe(measure * 3);
            });
        });

        describe("fixed width only", function() {
            it("should set the width of a single item", function() {
                makeCt({
                    width: 200
                });
                expect(getWidth(0)).toBe(200);
                expect(ct.getWidth()).toBe(200);
            });

            it("should shrink wrap multiple items", function() {
                makeCt([{
                    width: 500
                }, {
                    width: 50
                }]);
                expect(getWidth(0)).toBe(500);
                expect(getWidth(1)).toBe(50);
                expect(ct.getWidth()).toBe(550);
            });
        });

        describe("mixed", function() {
            it("should shrink wrap one flexed item, one auto-width item, and one fixed width item", function() {
                makeCt([{
                    flex: 1,
                    html: testHtml // shrink wrap without wrapping the text
                }, {
                    html: testHtml // shrink wrap without wrapping the text
                }, {
                    width: 200
                }]);
                expect(getWidth(0)).toBe(measure);
                expect(getWidth(1)).toBe(measure);
                expect(getWidth(2)).toBe(200);
                expect(ct.getWidth()).toBe(measure * 2 + 200);
            });
        });
    });

    it("should size correctly with docked items & a configured parallel size & shrinkWrap perpendicular size", function() {
        ct = new Ext.panel.Panel({
            floating: true,
            shadow: false,
            autoShow: true,
            border: false,
            layout: 'hbox',
            width: 150,
            dockedItems: [{
                dock: 'left',
                xtype: 'component',
                html: 'X'
            }],
            items: [{
                xtype: 'component',
                html: '<div style="height: 50px;"></div>'
            }]
        });
        expect(ct.getWidth()).toBe(150);
        expect(ct.getHeight()).toBe(50);
    });

    describe("scrolling", function() {
        var scrollSize = 20,
            defaultSize = 600,
            origScroll;

        function makeCt(cfg, layoutOptions) {
            cfg = cfg || {};

            if (cfg.items) {
                Ext.Array.forEach(cfg.items, function(item, index) {
                    if (!item.html) {
                        item.html = index + 1;
                    }
                });
            }

            ct = new Ext.container.Container(Ext.apply({
                renderTo: Ext.getBody(),
                layout: Ext.apply({
                    type: 'hbox'
                }, layoutOptions)
            }, cfg));
        }

        function makeShrinkWrapItem(w, h) {
            return {
                html: makeShrinkWrapHtml(w, h)
            };
        }

        function makeShrinkWrapHtml(w, h) {
            w = w || 10;
            h = h || 10;

            return Ext.String.format('<div style="width: {0}px; height: {1}px;"></div>', w, h);
        }

        function expectScroll(horizontal, vertical) {
            var el = ct.getEl(),
                dom = el.dom;

            expectScrollDimension(horizontal, el, dom, el.getStyle('overflow-x'), dom.scrollWidth, dom.clientWidth);
            expectScrollDimension(vertical, el, dom, el.getStyle('overflow-y'), dom.scrollHeight, dom.clientHeight);
        }

        function expectScrollDimension(value, el, dom, style, scrollSize, clientSize) {
            if (value !== undefined) {
                if (value) {
                    expect(style).not.toBe('hidden');
                    expect(scrollSize).toBeGreaterThan(clientSize);
                }
                else {
                    if (style === 'hidden') {
                        expect(style).toBe('hidden');
                    }
                    else {
                        expect(scrollSize).toBeLessThanOrEqual(clientSize);
                    }
                }
            }
        }

        function expectWidths(widths) {
            expectSizes(widths, 'getWidth');
        }

        function expectHeights(heights) {
            expectSizes(heights, 'getHeight');
        }

        function expectSizes(list, method) {
            Ext.Array.forEach(list, function(size, index) {
                expect(ct.items.getAt(index)[method]()).toBe(size);
            });
        }

        // IE9 adds an extra space when shrink wrapping, see: 
        // http://social.msdn.microsoft.com/Forums/da-DK/iewebdevelopment/thread/47c5148f-a142-4a99-9542-5f230c78cb3b
        function expectCtWidth(width) {
            var shrinkWrap = ct.getSizeModel().width.shrinkWrap,
                el = ct.getEl(),
                dom = el.dom,
                overflowOther = el.getStyle('overflow-y') !== 'hidden' && dom.scrollHeight > dom.clientHeight;

            if (Ext.isIE9 && shrinkWrap && overflowOther) {
                width += 4;
            }

            expect(ct.getWidth()).toBe(width);
        }

        function expectInnerCtWidth(width) {
            expectInnerSize(width, 'getWidth');
        }

        // IE9 adds an extra space when shrink wrapping, see: 
        // http://social.msdn.microsoft.com/Forums/da-DK/iewebdevelopment/thread/47c5148f-a142-4a99-9542-5f230c78cb3b
        function expectCtHeight(height) {
            var shrinkWrap = ct.getSizeModel().height.shrinkWrap,
                el = ct.getEl(),
                dom = el.dom,
                overflowOther = el.getStyle('overflow-x') !== 'hidden' && dom.scrollWidth > dom.clientWidth;

            if (Ext.isIE9 && shrinkWrap && overflowOther) {
                height += 4;
            }

            expect(ct.getHeight()).toBe(height);
        }

        function expectInnerCtHeight(height) {
            expectInnerSize(height, 'getHeight');
        }

        function expectInnerSize(size, method) {
            expect(ct.getLayout().innerCt[method]()).toBe(size);
        }

        beforeEach(function() {
            origScroll = Ext.scrollbar;

            Ext.scrollbar = {
                width: function() {
                    return scrollSize;
                },
                height: function() {
                    return scrollSize;
                },
                size: function() {
                    return {
                        width: scrollSize,
                        height: scrollSize
                    };
                }
            };
        });

        afterEach(function() {
            Ext.scrollbar = origScroll;
        });

        describe("limited scrolling", function() {
            describe("with no scroller", function() {
                it("should limit the innerCt width to the container width", function() {
                    makeCt({
                        width: defaultSize,
                        height: defaultSize,
                        defaultType: 'component',
                        items: [{
                            width: 400
                        }, {
                            width: 400
                        }]
                    });
                    expectInnerCtWidth(defaultSize);
                });
            });

            describe("user scrolling disabled", function() {
                it("should limit the innerCt width to the container width", function() {
                    makeCt({
                        width: defaultSize,
                        height: defaultSize,
                        defaultType: 'component',
                        scrollable: {
                            x: false
                        },
                        items: [{
                            width: 400
                        }, {
                            width: 400
                        }]
                    });
                    expectInnerCtWidth(800);
                });
            });
        });

        describe("fixed size", function() {
            function makeFixedCt(items, scrollable, layoutOptions) {
                makeCt({
                    width: defaultSize,
                    height: defaultSize,
                    defaultType: 'component',
                    items: items,
                    scrollable: scrollable !== undefined ? scrollable : true
                }, layoutOptions);
            }

            describe("horizontal scrolling", function() {
                it("should not show a scrollbar when configured to not scroll horizontally", function() {
                    makeFixedCt([{
                        width: 400
                    }, {
                        width: 400
                    }], {
                        x: false
                    });
                    expectScroll(false, false);
                    expectInnerCtWidth(800);
                });

                describe("with no vertical scrollbar", function() {
                    describe("configured", function() {
                        it("should not show a scrollbar when the total width does not overflow", function() {
                            makeFixedCt([{
                                width: 100
                            }, {
                                width: 100
                            }]);
                            expectScroll(false, false);
                            expectWidths([100, 100]);
                            expectInnerCtWidth(defaultSize);
                        });

                        it("should show a scrollbar when the total width overflows", function() {
                            makeFixedCt([{
                                width: 400
                            }, {
                                width: 400
                            }]);
                            expectScroll(true, false);
                            expectWidths([400, 400]);
                            expectInnerCtWidth(800);
                        });
                    });

                    describe("calculated", function() {
                        it("should not show a scrollbar when using only flex", function() {
                            makeFixedCt([{
                                flex: 1
                            }, {
                                flex: 2
                            }]);
                            expectScroll(false, false);
                            expectWidths([200, 400]);
                            expectInnerCtWidth(defaultSize);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    flex: 1
                                }, {
                                    flex: 1,
                                    minWidth: 300
                                }, {
                                    flex: 1
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, false);
                                expectWidths([100, 300, 100, 100]);
                                expectInnerCtWidth(defaultSize);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    flex: 1,
                                    minWidth: 400
                                }, {
                                    flex: 1,
                                    minWidth: 400
                                }]);
                                expectScroll(true, false);
                                expectWidths([400, 400]);
                                expectInnerCtWidth(800);
                            });
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should not show a scrollbar when the total width does not overflow", function() {
                            makeFixedCt([makeShrinkWrapItem(50), makeShrinkWrapItem(50)]);
                            expectScroll(false, false);
                            expectWidths([50, 50]);
                            expectInnerCtWidth(defaultSize);
                        });

                        it("should show a scrollbar when the total width overflows", function() {
                            makeFixedCt([makeShrinkWrapItem(400), makeShrinkWrapItem(400)]);
                            expectScroll(true, false);
                            expectWidths([400, 400]);
                            expectInnerCtWidth(800);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    minWidth: 200,
                                    html: makeShrinkWrapHtml(100)
                                }, {
                                    minWidth: 300,
                                    html: makeShrinkWrapHtml(50)
                                }]);
                                expectScroll(false, false);
                                expectWidths([200, 300]);
                                expectInnerCtWidth(defaultSize);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    minWidth: 400,
                                    html: makeShrinkWrapHtml(100)
                                }, {
                                    minWidth: 500,
                                    html: makeShrinkWrapHtml(50)
                                }]);
                                expectScroll(true, false);
                                expectWidths([400, 500]);
                                expectInnerCtWidth(900);
                            });
                        });
                    });

                    describe("configured + calculated", function() {
                        it("should not show a scrollbar when the configured width does not overflow", function() {
                            makeFixedCt([{
                                width: 300
                            }, {
                                flex: 1
                            }]);
                            expectScroll(false, false);
                            expectWidths([300, 300]);
                            expectInnerCtWidth(defaultSize);
                        });

                        it("should show a scrollbar when the configured width overflows", function() {
                            makeFixedCt([{
                                width: 700
                            }, {
                                flex: 1
                            }]);
                            expectScroll(true, false);
                            expectWidths([700, 0]);
                            expectInnerCtWidth(700);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    width: 300
                                }, {
                                    flex: 1,
                                    minWidth: 200
                                }]);
                                expectScroll(false, false);
                                expectWidths([300, 300]);
                                expectInnerCtWidth(defaultSize);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    width: 300
                                }, {
                                    flex: 1,
                                    minWidth: 500
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 500]);
                                expectInnerCtWidth(800);
                            });
                        });
                    });

                    describe("configured + shrinkWrap", function() {
                        it("should not show a scrollbar when the total width does not overflow", function() {
                            makeFixedCt([{
                                width: 300
                            }, makeShrinkWrapItem(200)]);
                            expectScroll(false, false);
                            expectWidths([300, 200]);
                            expectInnerCtWidth(defaultSize);
                        });

                        it("should show a scrollbar when the total width overflows", function() {
                            makeFixedCt([{
                                width: 400
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(true, false);
                            expectWidths([400, 400]);
                            expectInnerCtWidth(800);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    width: 300
                                }, {
                                    html: makeShrinkWrapHtml(100),
                                    minWidth: 200
                                }]);
                                expectScroll(false, false);
                                expectWidths([300, 200]);
                                expectInnerCtWidth(defaultSize);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    width: 300
                                }, {
                                    html: makeShrinkWrapHtml(200),
                                    minWidth: 500
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 500]);
                                expectInnerCtWidth(800);
                            });
                        });
                    });

                    describe("calculated + shrinkWrap", function() {
                        it("should not show a scrollbar when the shrinkWrap width does not overflow", function() {
                            makeFixedCt([makeShrinkWrapItem(500), {
                                flex: 1
                            }]);
                            expectScroll(false, false);
                            expectWidths([500, 100]);
                            expectInnerCtWidth(defaultSize);
                        });

                        it("should show a scrollbar when the shrinkWrap width overflows", function() {
                            makeFixedCt([makeShrinkWrapItem(700), {
                                flex: 1
                            }]);
                            expectScroll(true, false);
                            expectWidths([700, 0]);
                            expectInnerCtWidth(700);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(100),
                                    minWidth: 200
                                }, {
                                    flex: 1,
                                    minWidth: 300
                                }]);
                                expectScroll(false, false);
                                expectWidths([200, 400]);
                                expectInnerCtWidth(defaultSize);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(100),
                                    minWidth: 350
                                }, {
                                    flex: 1,
                                    minWidth: 350
                                }]);
                                expectScroll(true, false);
                                expectWidths([350, 350]);
                                expectInnerCtWidth(700);
                            });
                        });
                    });
                });

                describe("with a vertical scrollbar", function() {
                    var big = 1000;

                    describe("where the vertical scroll can be inferred before the first pass", function() {
                        describe("configured", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    width: 100,
                                    height: big
                                }, {
                                    width: 100
                                }]);
                                expectScroll(false, true);
                                expectWidths([100, 100]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    width: 400,
                                    height: big
                                }, {
                                    width: 400
                                }]);
                                expectScroll(true, true);
                                expectWidths([400, 400]);
                                expectInnerCtWidth(800);
                            });
                        });

                        describe("calculated", function() {
                            // There will never be overflow when using only calculated
                            it("should account for the vertical scrollbar", function() {
                                makeFixedCt([{
                                    flex: 1,
                                    height: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectWidths([290, 290]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minWidth: 400,
                                        height: big
                                    }, {
                                        flex: 1
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([400, 180]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minWidth: 350,
                                        height: big
                                    }, {
                                        flex: 1,
                                        minWidth: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([350, 350]);
                                    expectInnerCtWidth(700);
                                });
                            });
                        });

                        describe("shrinkWrap", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(200),
                                    height: big
                                }, {
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectWidths([200, 300]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400),
                                    height: big
                                }, {
                                    html: makeShrinkWrapHtml(400)
                                }]);
                                expectScroll(true, true);
                                expectWidths([400, 400]);
                                expectInnerCtWidth(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100),
                                        minWidth: 200,
                                        height: big
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minWidth: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 300]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100),
                                        minWidth: 350,
                                        height: big
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minWidth: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([350, 350]);
                                    expectInnerCtWidth(700);
                                });
                            });
                        });

                        describe("configured + calculated", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    width: 150,
                                    height: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectWidths([150, 430]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    width: 800,
                                    height: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectWidths([800, 0]);
                                expectInnerCtWidth(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        height: big
                                    }, {
                                        flex: 1,
                                        minWidth: 200
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 380]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        height: big
                                    }, {
                                        flex: 1,
                                        minWidth: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([200, 500]);
                                    expectInnerCtWidth(700);
                                });
                            });
                        });

                        describe("configured + shrinkWrap", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    width: 150,
                                    height: big
                                }, makeShrinkWrapItem(300)]);
                                expectScroll(false, true);
                                expectWidths([150, 300]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    width: 350,
                                    height: big
                                }, makeShrinkWrapItem(350)]);
                                expectScroll(true, true);
                                expectWidths([350, 350]);
                                expectInnerCtWidth(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        height: big
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minWidth: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 300]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        height: big
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minWidth: 550
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([200, 550]);
                                    expectInnerCtWidth(750);
                                });
                            });
                        });

                        describe("calculated + shrinkWrap", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(300),
                                    height: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectWidths([300, 280]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapItem(700),
                                    height: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectWidths([700, 0]);
                                expectInnerCtWidth(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(200),
                                        height: big
                                    }, {
                                        flex: 1,
                                        minWidth: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 380]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400),
                                        height: big
                                    }, {
                                        flex: 1,
                                        minWidth: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([400, 500]);
                                    expectInnerCtWidth(900);
                                });
                            });
                        });
                    });

                    describe("when the vertical scroll needs to be calculated", function() {
                        describe("configured", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    width: 100,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    width: 100
                                }]);
                                expectScroll(false, true);
                                expectWidths([100, 100]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    width: 400,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    width: 400
                                }]);
                                expectScroll(true, true);
                                expectWidths([400, 400]);
                                expectInnerCtWidth(800);
                            });
                        });

                        describe("calculated", function() {
                            // There will never be overflow when using only calculated
                            it("should account for the vertical scrollbar", function() {
                                makeFixedCt([{
                                    flex: 1,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectWidths([290, 290]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            describe("minWidth", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minWidth: 400,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([400, 180]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minWidth: 350,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1,
                                        minWidth: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([350, 350]);
                                    expectInnerCtWidth(700);
                                });
                            });
                        });

                        describe("shrinkWrap", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(200, big)
                                }, {
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectWidths([200, 300]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400, big)
                                }, {
                                    html: makeShrinkWrapHtml(400)
                                }]);
                                expectScroll(true, true);
                                expectWidths([400, 400]);
                                expectInnerCtWidth(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100, big),
                                        minWidth: 200
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minWidth: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 300]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100, big),
                                        minWidth: 350
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minWidth: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([350, 350]);
                                    expectInnerCtWidth(700);
                                });
                            });
                        });

                        describe("configured + calculated", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    width: 150,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectWidths([150, 430]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    width: 800,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectWidths([800, 0]);
                                expectInnerCtWidth(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1,
                                        minWidth: 200
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 380]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1,
                                        minWidth: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([200, 500]);
                                    expectInnerCtWidth(700);
                                });
                            });
                        });

                        describe("configured + shrinkWrap", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    width: 150,
                                    html: makeShrinkWrapHtml(10, big)
                                }, makeShrinkWrapItem(300)]);
                                expectScroll(false, true);
                                expectWidths([150, 300]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    width: 350,
                                    html: makeShrinkWrapHtml(10, big)
                                }, makeShrinkWrapItem(350)]);
                                expectScroll(true, true);
                                expectWidths([350, 350]);
                                expectInnerCtWidth(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minWidth: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 300]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        width: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minWidth: 550
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([200, 550]);
                                    expectInnerCtWidth(750);
                                });
                            });
                        });

                        describe("calculated + shrinkWrap", function() {
                            it("should account for the vertical scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(300, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectWidths([300, 280]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            it("should account for the vertical scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapItem(700, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectWidths([700, 0]);
                                expectInnerCtWidth(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the vertical scrollbar when the minWidth does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(200, big)
                                    }, {
                                        flex: 1,
                                        minWidth: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectWidths([200, 380]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should account for the vertical scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400, big)
                                    }, {
                                        flex: 1,
                                        minWidth: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([400, 500]);
                                    expectInnerCtWidth(900);
                                });
                            });
                        });
                    });

                    describe("when the vertical scrollbar triggers a horizontal scrollbar", function() {
                        var scrollTakesSize = Ext.getScrollbarSize().width > 0;

                        describe("configured", function() {
                            it("should account for the vertical scrollbar", function() {
                                makeFixedCt([{
                                    width: 295,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    width: 295
                                }]);
                                expectScroll(scrollTakesSize, true);
                                expectWidths([295, 295]);
                                expectInnerCtWidth(590);
                            });
                        });

                        describe("shrinkWrap", function() {
                            it("should account for the vertical scrollbar", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(295, big)
                                }, makeShrinkWrapItem(295)]);
                                expectScroll(scrollTakesSize, true);
                                expectWidths([295, 295]);
                                expectInnerCtWidth(590);
                            });
                        });

                        describe("configured + shrinkWrap", function() {
                            it("should account for the vertical scrollbar", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(295, big)
                                }, {
                                    width: 295
                                }]);
                                expectScroll(scrollTakesSize, true);
                                expectWidths([295, 295]);
                                expectInnerCtWidth(590);
                            });
                        });
                    });
                });
            });

            describe("vertical scrolling", function() {
                it("should not show a scrollbar when configured to not scroll vertically", function() {
                    makeFixedCt([{
                        width: 100,
                        height: 900
                    }, {
                        width: 100
                    }], {
                        y: false
                    });
                    expectScroll(false, false);
                });

                describe("with no horizontal scrollbar", function() {
                    describe("configured height", function() {
                        it("should not show a scrollbar when the largest height does not overflow", function() {
                            makeFixedCt([{
                                width: 100,
                                height: 300
                            }, {
                                width: 100,
                                height: 400
                            }]);
                            expectScroll(false, false);
                            expectHeights([300, 400]);
                            expectInnerCtHeight(400);
                        });

                        it("should show a scrollbar when the largest height overflows", function() {
                            makeFixedCt([{
                                width: 100,
                                height: 700
                            }, {
                                width: 200,
                                height: 800
                            }]);
                            expectScroll(false, true);
                            expectHeights([700, 800]);
                            expectInnerCtHeight(800);
                        });
                    });

                    describe("align stretch", function() {
                        it("should not show a scrollbar by default", function() {
                            makeFixedCt([{
                                width: 100
                            }, {
                                width: 100
                            }], true, { align: 'stretch' });
                            expectScroll(false, false);
                            expectHeights([600, 600]);
                            expectInnerCtHeight(defaultSize);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    width: 100,
                                    minHeight: 400
                                }, {
                                    width: 100
                                }], true, { align: 'stretch' });
                                expectScroll(false, false);
                                expectHeights([600, 600]);
                                expectInnerCtHeight(defaultSize);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    width: 100,
                                    minHeight: 800
                                }, {
                                    width: 100
                                }], true, { align: 'stretch' });
                                expectScroll(false, true);
                                expectHeights([800, 600]);
                                expectInnerCtHeight(800);
                            });
                        });
                    });

                    describe("shrinkWrap height", function() {
                        it("should not show a scrollbar when the largest height does not overflow", function() {
                            makeFixedCt([makeShrinkWrapItem(10, 300), makeShrinkWrapItem(10, 200)]);
                            expectScroll(false, false);
                            expectHeights([300, 200]);
                            expectInnerCtHeight(300);
                        });

                        it("should show a scrollbar when the largest height overflows", function() {
                            makeFixedCt([makeShrinkWrapItem(10, 500), makeShrinkWrapItem(10, 750)]);
                            expectScroll(false, true);
                            expectHeights([500, 750]);
                            expectInnerCtHeight(750);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    width: 100,
                                    minHeight: 400,
                                    html: makeShrinkWrapHtml(10, 10)
                                }, {
                                    width: 100,
                                    minHeight: 500,
                                    html: makeShrinkWrapHtml(10, 10)
                                }]);
                                expectScroll(false, false);
                                expectHeights([400, 500]);
                                expectInnerCtHeight(500);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    width: 100,
                                    minHeight: 650,
                                    html: makeShrinkWrapHtml(10, 50)
                                }, {
                                    width: 100,
                                    minHeight: 750,
                                    html: makeShrinkWrapHtml(10, 50)
                                }]);
                                expectScroll(false, true);
                                expectHeights([650, 750]);
                                expectInnerCtHeight(750);
                            });
                        });
                    });
                });

                describe("with a horizontal scrollbar", function() {
                    describe("where the horizontal scroll can be inferred before the first pass", function() {
                        describe("configured height", function() {
                            it("should not show a scrollbar when the largest height does not overflow", function() {
                                makeFixedCt([{
                                    width: 400,
                                    height: 300
                                }, {
                                    width: 400,
                                    height: 400
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 400]);
                                expectInnerCtHeight(400);
                            });

                            it("should show a scrollbar when the largest height overflows", function() {
                                makeFixedCt([{
                                    width: 400,
                                    height: 700
                                }, {
                                    width: 400,
                                    height: 800
                                }]);
                                expectScroll(true, true);
                                expectHeights([700, 800]);
                                expectInnerCtHeight(800);
                            });
                        });

                        describe("align stretch", function() {
                            it("should not show a scrollbar by default", function() {
                                makeFixedCt([{
                                    width: 400
                                }, {
                                    width: 400
                                }], true, { align: 'stretch' });
                                expectScroll(true, false);
                                expectHeights([580, 580]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                    makeFixedCt([{
                                        width: 400,
                                        minHeight: 400
                                    }, {
                                        width: 400
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, false);
                                    expectHeights([580, 580]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should show a scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        width: 400,
                                        minHeight: 800
                                    }, {
                                        width: 400
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, true);
                                    expectHeights([800, 580]);
                                    expectInnerCtHeight(800);
                                });
                            });
                        });

                        describe("shrinkWrap height", function() {
                            it("should not show a scrollbar when the largest height does not overflow", function() {
                                makeFixedCt([{
                                    width: 400,
                                    html: makeShrinkWrapHtml(10, 300)
                                }, {
                                    width: 400,
                                    html: makeShrinkWrapHtml(10, 200)
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 200]);
                                expectInnerCtHeight(300);
                            });

                            it("should show a scrollbar when the largest height overflows", function() {
                                makeFixedCt([{
                                    width: 400,
                                    html: makeShrinkWrapHtml(10, 500)
                                }, {
                                    width: 400,
                                    html: makeShrinkWrapHtml(10, 750)
                                }]);
                                expectScroll(true, true);
                                expectHeights([500, 750]);
                                expectInnerCtHeight(750);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                    makeFixedCt([{
                                        width: 400,
                                        minHeight: 400,
                                        html: makeShrinkWrapHtml(10, 10)
                                    }, {
                                        width: 400,
                                        minHeight: 500,
                                        html: makeShrinkWrapHtml(10, 10)
                                    }]);
                                    expectScroll(true, false);
                                    expectHeights([400, 500]);
                                    expectInnerCtHeight(500);
                                });

                                it("should show a scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        width: 400,
                                        minHeight: 650,
                                        html: makeShrinkWrapHtml(10, 50)
                                    }, {
                                        width: 400,
                                        minHeight: 750,
                                        html: makeShrinkWrapHtml(10, 50)
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([650, 750]);
                                    expectInnerCtHeight(750);
                                });
                            });
                        });
                    });

                    describe("when the vertical scroll needs to be calculated", function() {
                        describe("configured height", function() {
                            it("should not show a scrollbar when the largest height does not overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400),
                                    height: 300
                                }, {
                                    html: makeShrinkWrapHtml(400),
                                    height: 400
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 400]);
                                expectInnerCtHeight(400);
                            });

                            it("should show a scrollbar when the largest height overflows", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400),
                                    height: 700
                                }, {
                                    html: makeShrinkWrapHtml(400),
                                    height: 800
                                }]);
                                expectScroll(true, true);
                                expectHeights([700, 800]);
                                expectInnerCtHeight(800);
                            });
                        });

                        describe("align stretch", function() {
                            it("should not show a scrollbar by default", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400)
                                }, {
                                    html: makeShrinkWrapHtml(400)
                                }], true, { align: 'stretch' });
                                expectScroll(true, false);
                                expectHeights([580, 580]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400),
                                        minHeight: 400
                                    }, {
                                        html: makeShrinkWrapHtml(400)
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, false);
                                    expectHeights([580, 580]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should show a scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400),
                                        minHeight: 800
                                    }, {
                                        html: makeShrinkWrapHtml(400)
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, true);
                                    expectHeights([800, 580]);
                                    expectInnerCtHeight(800);
                                });
                            });
                        });

                        describe("shrinkWrap height", function() {
                            it("should not show a scrollbar when the largest height does not overflow", function() {
                                makeFixedCt([makeShrinkWrapItem(400, 300), makeShrinkWrapItem(400, 200)]);
                                expectScroll(true, false);
                                expectHeights([300, 200]);
                                expectInnerCtHeight(300);
                            });

                            it("should show a scrollbar when the largest height overflows", function() {
                                makeFixedCt([makeShrinkWrapItem(400, 500), makeShrinkWrapItem(400, 750)]);
                                expectScroll(true, true);
                                expectHeights([500, 750]);
                                expectInnerCtHeight(750);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                    makeFixedCt([{
                                        minHeight: 400,
                                        html: makeShrinkWrapHtml(400, 10)
                                    }, {
                                        minHeight: 500,
                                        html: makeShrinkWrapHtml(400, 10)
                                    }]);
                                    expectScroll(true, false);
                                    expectHeights([400, 500]);
                                    expectInnerCtHeight(500);
                                });

                                it("should show a scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        minHeight: 650,
                                        html: makeShrinkWrapHtml(400, 50)
                                    }, {
                                        minHeight: 750,
                                        html: makeShrinkWrapHtml(400, 50)
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([650, 750]);
                                    expectInnerCtHeight(750);
                                });
                            });
                        });
                    });
                });
            });
        });

        describe("shrinkWrap height", function() {
            function makeShrinkWrapCt(items, scrollable, layoutOptions) {
                // Float to prevent stretching
                makeCt({
                    floating: true,
                    width: defaultSize,
                    defaultType: 'component',
                    items: items,
                    scrollable: scrollable !== undefined ? scrollable : true
                }, layoutOptions);
            }

            // Not testing vertical scroll here because it's never visible

            describe("with no horizontal scrollbar", function() {
                describe("configured", function() {
                    it("should publish the largest height", function() {
                        makeShrinkWrapCt([{
                            width: 100,
                            height: 400
                        }, {
                            width: 100,
                            height: 500
                        }]);
                        expectScroll(false, false);
                        expectHeights([400, 500]);
                        expectCtHeight(500);
                    });
                });

                describe("shrinkWrap", function() {
                    it("should publish the largest height", function() {
                        makeShrinkWrapCt([{
                            width: 100,
                            html: makeShrinkWrapHtml(10, 250)
                        }, {
                            width: 100,
                            html: makeShrinkWrapHtml(10, 300)
                        }]);
                        expectScroll(false, false);
                        expectHeights([250, 300]);
                        expectCtHeight(300);
                    });

                    describe("with constraint", function() {
                        it("should publish the largest constrained height", function() {
                            makeShrinkWrapCt([{
                                width: 100,
                                html: makeShrinkWrapHtml(10, 150),
                                minHeight: 300
                            }, {
                                width: 100,
                                html: makeShrinkWrapHtml(10, 100),
                                minHeight: 350
                            }]);
                            expectScroll(false, false);
                            expectHeights([300, 350]);
                            expectCtHeight(350);
                        });
                    });
                });

                describe("align: stretch", function() {
                    it("should stretch items & publish the largest height", function() {
                        makeShrinkWrapCt([{
                            width: 100,
                            html: makeShrinkWrapHtml(10, 200)
                        }, {
                            width: 100,
                            html: makeShrinkWrapHtml(10, 300)
                        }], true, { align: 'stretch' });
                        expectScroll(false, false);
                        expectHeights([300, 300]);
                        expectCtHeight(300);
                    });

                    describe("with constraint", function() {
                        it("should stretch items and publish the largest constrained height", function() {
                            makeShrinkWrapCt([{
                                width: 100,
                                minHeight: 400
                            }, {
                                width: 100,
                                minHeight: 550
                            }], true, { align: 'stretch' });
                            expectScroll(false, false);
                            expectHeights([550, 550]);
                            expectCtHeight(550);
                        });
                    });
                });
            });

            describe("with a horizontal scrollbar", function() {
                var big = 1000;

                describe("where the horizontal scroll can be inferred before the first pass", function() {
                    describe("configured", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                width: big,
                                height: 400
                            }, {
                                height: 500
                            }]);
                            expectScroll(true, false);
                            expectHeights([400, 500]);
                            expectCtHeight(520);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                width: big,
                                html: makeShrinkWrapHtml(10, 250)
                            }, {
                                html: makeShrinkWrapHtml(10, 300)
                            }]);
                            expectScroll(true, false);
                            expectHeights([250, 300]);
                            expectCtHeight(320);
                        });

                        describe("with constraint", function() {
                            it("should publish the largest constrained height", function() {
                                makeShrinkWrapCt([{
                                    width: big,
                                    html: makeShrinkWrapHtml(10, 150),
                                    minHeight: 300
                                }, {
                                    html: makeShrinkWrapHtml(10, 100),
                                    minHeight: 350
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 350]);
                                expectCtHeight(370);
                            });
                        });
                    });

                    describe("align: stretch", function() {
                        it("should stretch items & publish the largest height", function() {
                            makeShrinkWrapCt([{
                                width: big,
                                html: makeShrinkWrapHtml(10, 200)
                            }, {
                                width: 100,
                                html: makeShrinkWrapHtml(10, 300)
                            }]);
                            expectScroll(true, false);
                            expectHeights([200, 300]);
                            expectCtHeight(320);
                        });

                        describe("with constraint", function() {
                            it("should stretch items and publish the largest constrained height", function() {
                                makeShrinkWrapCt([{
                                    width: big,
                                    minHeight: 400
                                }, {
                                    width: 100,
                                    minHeight: 550
                                }]);
                                expectScroll(true, false);
                                expectHeights([400, 550]);
                                expectCtHeight(570);
                            });
                        });
                    });
                });

                describe("when the horizontal scroll needs to be calculated", function() {
                    describe("configured", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(big, 400)
                            }, {
                                html: makeShrinkWrapHtml(10, 500)
                            }]);
                            expectScroll(true, false);
                            expectHeights([400, 500]);
                            expectCtHeight(520);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(big, 250)
                            }, {
                                html: makeShrinkWrapHtml(10, 300)
                            }]);
                            expectScroll(true, false);
                            expectHeights([250, 300]);
                            expectCtHeight(320);
                        });

                        describe("with constraint", function() {
                            it("should publish the largest constrained height", function() {
                                makeShrinkWrapCt([{
                                    html: makeShrinkWrapHtml(big, 150),
                                    minHeight: 300
                                }, {
                                    html: makeShrinkWrapHtml(10, 100),
                                    minHeight: 350
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 350]);
                                expectCtHeight(370);
                            });
                        });
                    });

                    describe("align: stretch", function() {
                        it("should stretch items & publish the largest height", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(big, 200)
                            }, {
                                width: 100,
                                html: makeShrinkWrapHtml(10, 300)
                            }], true, { align: 'stretch' });
                            expectScroll(true, false);
                            expectHeights([300, 300]);
                            expectCtHeight(320);
                        });

                        describe("with constraint", function() {
                            it("should stretch items and publish the largest constrained height", function() {
                                makeShrinkWrapCt([{
                                    html: makeShrinkWrapHtml(big, 10),
                                    minHeight: 400
                                }, {
                                    minHeight: 550
                                }], true, { align: 'stretch' });
                                expectScroll(true, false);
                                expectHeights([550, 550]);
                                expectCtHeight(570);
                            });
                        });
                    });
                });
            });
        });

        describe("shrinkWrap width", function() {
            function makeShrinkWrapCt(items, scrollable, layoutOptions) {
                makeCt({
                    // Float to prevent stretching
                    floating: true,
                    height: defaultSize,
                    defaultType: 'component',
                    items: items,
                    scrollable: scrollable !== undefined ? scrollable : true
                }, layoutOptions);
            }

            // Not testing horizontal scroll here because it's never visible
            // Flex items become shrinkWrap when shrink wrapping the width, so we won't bother
            // with those

            describe("with no vertical scrollbar", function() {
                describe("configured", function() {
                    it("should publish the total width", function() {
                        makeShrinkWrapCt([{
                            width: 400
                        }, {
                            width: 400
                        }]);
                        expectScroll(false, false);
                        expectWidths([400, 400]);
                        expectCtWidth(800);
                    });
                });

                describe("shrinkWrap", function() {
                    it("should publish the total width", function() {
                        makeShrinkWrapCt([makeShrinkWrapItem(400), makeShrinkWrapItem(400)]);
                        expectScroll(false, false);
                        expectWidths([400, 400]);
                        expectCtWidth(800);
                    });

                    describe("with constraint", function() {
                        it("should publish the total width", function() {
                            makeShrinkWrapCt([{
                                minWidth: 350,
                                html: makeShrinkWrapHtml(200)
                            }, {
                                minWidth: 400,
                                html: makeShrinkWrapHtml(300)
                            }]);
                            expectScroll(false, false);
                            expectWidths([350, 400]);
                            expectCtWidth(750);
                        });
                    });
                });

                describe("configured + shrinkWrap", function() {
                    it("should publish the total width", function() {
                        makeShrinkWrapCt([{
                            width: 400
                        }, makeShrinkWrapItem(400)]);
                        expectScroll(false, false);
                        expectWidths([400, 400]);
                        expectCtWidth(800);
                    });

                    describe("with constraint", function() {
                        it("should publish the total width", function() {
                            makeShrinkWrapCt([{
                                width: 350
                            }, {
                                minWidth: 400,
                                html: makeShrinkWrapHtml(300)
                            }]);
                            expectScroll(false, false);
                            expectWidths([350, 400]);
                            expectCtWidth(750);
                        });
                    });
                });
            });

            describe("with a vertical scrollbar", function() {
                var big = 1000;

                describe("where the vertical scroll can be inferred before the first pass", function() {
                    describe("configured", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                width: 400,
                                height: big
                            }, {
                                width: 400
                            }]);
                            expectScroll(false, true);
                            expectWidths([400, 400]);
                            expectCtWidth(820);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(400),
                                height: big
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectWidths([400, 400]);
                            expectCtWidth(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total width", function() {
                                makeShrinkWrapCt([{
                                    minWidth: 350,
                                    html: makeShrinkWrapHtml(200),
                                    height: big
                                }, {
                                    minWidth: 400,
                                    html: makeShrinkWrapHtml(100)
                                }]);
                                expectScroll(false, true);
                                expectWidths([350, 400]);
                                expectCtWidth(770);
                            });
                        });
                    });

                    describe("configured + shrinkWrap", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                width: 400,
                                height: big
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectWidths([400, 400]);
                            expectCtWidth(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total width", function() {
                                makeShrinkWrapCt([{
                                    width: 350,
                                    height: big
                                }, {
                                    minWidth: 400,
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectWidths([350, 400]);
                                expectCtWidth(770);
                            });
                        });
                    });
                });

                describe("when the vertical scroll needs to be calculated", function() {
                    describe("configured", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                width: 400,
                                html: makeShrinkWrapHtml(10, big)
                            }, {
                                width: 400
                            }]);
                            expectScroll(false, true);
                            expectWidths([400, 400]);
                            expectCtWidth(820);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(400, big)
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectWidths([400, 400]);
                            expectCtWidth(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total width", function() {
                                makeShrinkWrapCt([{
                                    minWidth: 350,
                                    html: makeShrinkWrapHtml(200, big)
                                }, {
                                    minWidth: 400,
                                    html: makeShrinkWrapHtml(100)
                                }]);
                                expectScroll(false, true);
                                expectWidths([350, 400]);
                                expectCtWidth(770);
                            });
                        });
                    });

                    describe("configured + shrinkWrap", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                width: 400,
                                html: makeShrinkWrapHtml(10, big)
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectWidths([400, 400]);
                            expectCtWidth(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total width", function() {
                                makeShrinkWrapCt([{
                                    width: 350,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    minWidth: 400,
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectWidths([350, 400]);
                                expectCtWidth(770);
                            });
                        });
                    });
                });
            });
        });

        describe("preserving scroll state", function() {
            var endSpy;

            beforeEach(function() {
                endSpy = jasmine.createSpy();
            });

            afterEach(function() {
                endSpy = null;
            });

            it("should restore the horizontal/vertical scroll position with user scrolling", function() {
                makeCt({
                    width: 400,
                    height: 400,
                    scrollable: true,
                    items: [{
                        width: 300,
                        height: 500
                    }, {
                        width: 300,
                        height: 500
                    }]
                });
                var scrollable = ct.getScrollable();

                scrollable.on('scrollend', endSpy);
                scrollable.scrollTo(50, 30);
                waitsFor(function() {
                    return endSpy.callCount > 0;
                });
                runs(function() {
                    ct.setSize(401, 401);
                });
                waitsFor(function() {
                    var pos = scrollable.getPosition();

                    return pos.x > 0 && pos.y > 0;
                });
                runs(function() {
                    var pos = scrollable.getPosition();

                    expect(pos).toEqual({
                        x: 50,
                        y: 30
                    });
                });
            });

            it("should restore the horizontal/vertical scroll position with programmatic scrolling", function() {
                // Allows for only programmatic scrolling, but the scrollbars aren't visible
                makeCt({
                    width: 400,
                    height: 400,
                    scrollable: {
                        x: false,
                        y: false
                    },
                    items: [{
                        width: 300,
                        height: 500
                    }, {
                        width: 300,
                        height: 500
                    }]
                });
                var scrollable = ct.getScrollable();

                scrollable.on('scrollend', endSpy);
                scrollable.scrollTo(50, 30);
                waitsFor(function() {
                    return endSpy.callCount > 0;
                });
                runs(function() {
                    ct.setSize(401, 401);
                });
                waitsFor(function() {
                    var pos = scrollable.getPosition();

                    return pos.x > 0 && pos.y > 0;
                });
                runs(function() {
                    var pos = scrollable.getPosition();

                    expect(pos).toEqual({
                        x: 50,
                        y: 30
                    });
                });
            });
        });
    });

    function createOverflowSuite(options) {
        describe("parent type: " + options.parentXtype +
            ", child type: " + options.childXtype +
            ", parent layout: " + options.parentLayout,
            function() {

            function makeContainer(overflowDim) {
                var nonOverflowDim = overflowDim === 'width' ? 'height' : 'width',
                    scrollbarSize = Ext.getScrollbarSize(),
                    component = {
                        xtype: 'component',
                        style: 'margin: 3px; background-color: green;'
                    },
                    childCt = {
                        xtype: options.childXtype,
                        autoScroll: true,
                        layout: 'hbox',
                        items: [component]
                    },
                    parentCt = {
                        xtype: options.parentXtype,
                        floating: true,
                        shadow: false,
                        layout: options.parentLayout,
                        items: [childCt]
                    };

                component[overflowDim] = 500;
                component[nonOverflowDim] = 90 - scrollbarSize[nonOverflowDim];
                childCt[overflowDim] = 98;

                if (options.parentXtype === 'container') {
                    parentCt.style = 'border: 1px solid black';
                }

                if (options.childXtype === 'container') {
                    childCt.style = 'border: 1px solid black';
                }

                ct = Ext.widget(parentCt);

                ct.show();
            }

            describe("horizontal overflow with shrink wrap height", function() {
                beforeEach(function() {
                    makeContainer('width');
                });

                it("should include scrollbar size in the height", function() {
                    expect(ct.getHeight()).toBe(100);
                });
            });

            describe("vertical overflow with shrink wrap width", function() {
                beforeEach(function() {
                    makeContainer('height');
                });

                it("should include scrollbar size in the width", function() {
                    expect(ct.getWidth()).toBe(100);
                });
            });
        });
    }

    createOverflowSuite({
        parentXtype: 'container',
        childXtype: 'container',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'container',
        childXtype: 'container',
        parentLayout: 'hbox'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'container',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'container',
        parentLayout: 'hbox'
    });

    createOverflowSuite({
        parentXtype: 'container',
        childXtype: 'panel',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'container',
        childXtype: 'panel',
        parentLayout: 'hbox'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'panel',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'panel',
        parentLayout: 'hbox'
    });

    describe("misc overflow", function() {
        it("should layout with autoScroll + align: stretch + A shrink wrapped parallel item", function() {
            expect(function() {
                ct = new Ext.container.Container({
                    autoScroll: true,
                    layout: {
                        align: 'stretch',
                        type: 'hbox'
                    },
                    renderTo: Ext.getBody(),
                    height: 600,
                    width: 200,
                    items: [{
                        xtype: 'component',
                        width: 200,
                        html: 'Item'
                    }, {
                        xtype: 'component',
                        html: 'Component'
                    }]
                });
            }).not.toThrow();
        });
    });

    // this suite may duplicate some logic from the above suite, but it is a test
    // case from a client so it should not be removed.
    describe("Parallel overflow", function() {
        it("should expand shrinkwrap height to accommodate parallel scrollbar when parallel dimension overflows", function() {
            ct = Ext.create('Ext.container.Container', {
                defaultType: "container",
                id: 'outermost-autoheight',
                renderTo: document.body,
                width: 200,
                style: 'border: 1px solid black',
                layout: {
                    type: 'hbox',
                    align: 'stretchmax'
                },
                items: [{
                    id: 'overflowX-vbox-container-left',
                    defaultType: 'component',
                    style: 'background-color:yellow',
                    layout: 'hbox',
                    flex: 1,
                    overflowX: 'auto',
                    items: [{
                        width: 500,
                        height: 15,
                        id: 'top-box-500px-left'
                    }]
                }, {
                    xtype: 'splitter',
                    id: 'parallel-overflow-test-splitter'
                }, {
                    id: 'overflowX-vbox-container-right',
                    defaultType: 'component',
                    style: 'background-color:yellow',
                    layout: 'hbox',
                    flex: 1,
                    overflowX: 'auto',
                    items: [{
                        width: 500,
                        height: 15,
                        id: 'top-box-500px-right'
                    }]
                }]
            });
            expect(ct).toHaveLayout({
                "el": {
                    "xywh": "0 0 200 " + (15 + Ext.getScrollbarSize().height + 2)
                },
                "items": {
                    "overflowX-vbox-container-left": {
                        "el": {
                            "xywh": "1 1 97 " + (15 + Ext.getScrollbarSize().height)
                        },
                        "items": {
                            "top-box-500px-left": {
                                "el": {
                                    "xywh": "0 0 500 15"
                                }
                            }
                        }
                    },
                    "parallel-overflow-test-splitter": {
                        "el": {
                            "xywh": "98 1 5 " + (15 + Ext.getScrollbarSize().height)
                        }
                    },
                    "overflowX-vbox-container-right": {
                        "el": {
                            "xywh": "103 1 96 " + (15 + Ext.getScrollbarSize().height)
                        },
                        "items": {
                            "top-box-500px-right": {
                                "el": {
                                    "xywh": "0 0 500 15"
                                }
                            }
                        }
                    }
                }
            });
        });
    });

    describe('scroll preserving across layouts', function() {
        // Test for EXTJSIV-7103. Enable when it is fixed.
        xit('should preserve vertical scroll when an inner fieldset is collapsed', function() {
            var panel = Ext.create('Ext.panel.Panel', {
                    renderTo: document.body,
                    height: 100,
                    width: 300,
                    autoScroll: true,
                    items: [{
                        xtype: 'container',
                        layout: 'hbox',
                        items: [{
                            xtype: 'component',
                            height: 100,
                            html: 'scroll down and toggle the fieldset'
                        }]
                    }, {
                        id: 'myFieldset',
                        xtype: 'fieldset',
                        title: 'Toggle Me!',
                        collapsible: true,
                        items: [{
                            xtype: 'component',
                            width: 100,
                            height: 20
                        }]
                    }]
                }),
                fieldset = Ext.getCmp('myFieldset'),
                panelBody = panel.body.dom,
                expectedScrollTopWhenFieldsetIsCollapsed;

            fieldset.collapse();
            panelBody.scrollTop = 9999;
            expectedScrollTopWhenFieldsetIsCollapsed = panelBody.scrollTop;
            fieldset.expand();
            panelBody.scrollTop = 0;

            // sanity
            expect(expectedScrollTopWhenFieldsetIsCollapsed).not.toBe(0);
            expect(expectedScrollTopWhenFieldsetIsCollapsed).not.toBe(9999);

            panelBody.scrollTop = 9999;
            fieldset.collapse();
            expect(panelBody.scrollTop).toBe(expectedScrollTopWhenFieldsetIsCollapsed);
            panel.destroy();

        });
    });
});
