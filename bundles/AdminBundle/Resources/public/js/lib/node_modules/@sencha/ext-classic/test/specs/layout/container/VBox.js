topSuite("Ext.layout.container.VBox", ['Ext.Panel', 'Ext.layout.container.Fit'], function() {
    var ct, c, makeCt;

    afterEach(function() {
        Ext.destroy(ct, c);
        ct = c = makeCt = null;
    });

    describe("defaults", function() {
        var counter = 0,
            proto = Ext.layout.container.VBox.prototype;

        beforeEach(function() {
            // We only need to create a layout instance once to wire up configs
            if (!counter) {
                ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    layout: 'vbox',
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
        it("should clear the top on an item when removing and using in another container", function() {
            c = new Ext.Component({
                height: 50
            });

            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'vbox',
                width: 100,
                height: 100,
                items: [{
                    height: 50
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

            var top = c.getEl().getStyle('top');

            // Normalize top value
            if (top === 'auto') {
                top = '';
            }
            else if (top === '0px') {
                top = '';
            }

            expect(top).toBe('');

            other.destroy();
        });

        it("should remove an item when the item is not rendered and the item is not destroying", function() {
            ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                collapsed: true,
                layout: 'vbox',
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

    describe("padding", function() {

        beforeEach(function() {
            makeCt = function(pad) {
                c = new Ext.Component({
                    flex: 1
                });
                ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    width: 80,
                    height: 100,
                    layout: {
                        type: 'vbox',
                        align: 'stretch',
                        padding: pad
                    },
                    items: c
                });
            };
        });

        it("should not add any padding by default", function() {
            makeCt(0);
            expect(c.getWidth()).toBe(80);
            expect(c.getHeight()).toBe(100);
        });

        it("should read a padding number", function() {
            makeCt(5);
            expect(c.getWidth()).toBe(70);
            expect(c.getHeight()).toBe(90);
        });

        it("should read a padding string", function() {
            makeCt('1 2 3 4');
            expect(c.getWidth()).toBe(74);
            expect(c.getHeight()).toBe(96);
        });
    });

    describe("padding and shrinkwrap", function() {

        beforeEach(function() {
            makeCt = function(childMargins) {
                ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    width: 80,
                    padding: 100, // innerCt stretches *inside* this.
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    defaults: {
                        xtype: 'component',
                        height: 50,
                        margin: childMargins
                    },
                    items: [{}, {}]
                });
            };
        });

        it("should not add any padding by default", function() {
            makeCt(0);
            expect(ct.layout.innerCt.getHeight()).toBe(100);
        });

        it("should read a padding number", function() {
            makeCt(5);
            expect(ct.layout.innerCt.getHeight()).toBe(120);
        });

        it("should read a padding string", function() {
            makeCt('1 2 3 4');
            expect(ct.layout.innerCt.getHeight()).toBe(108);
        });
    });

    it("should apply margin to components", function() {
        ct = new Ext.container.Container({
            width: 200,
            height: 200,
            renderTo: Ext.getBody(),
            defaultType: 'component',
            layout: {
                type: 'vbox',
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

        expect(ct.items.last().getY()).toBe(105);
        expect(ct.items.last().getX()).toBe(5);
    });

    describe("pack", function() {
        var getY;

        beforeEach(function() {
            makeCt = function(pack) {
                ct = new Ext.container.Container({
                    defaultType: 'component',
                    renderTo: Ext.getBody(),
                    width: 600,
                    height: 600,
                    layout: {
                        type: 'vbox',
                        pack: pack
                    },
                    items: [{
                        height: 30
                    }, {
                        height: 40
                    }, {
                        height: 20
                    }]
                });
            };

            getY = function(index) {
                return ct.items.getAt(index).el.getY();
            };
        });

        afterEach(function() {
            getY = null;
        });

        it("should pack at the top with pack: start", function() {
            makeCt('start');
            expect(getY(0)).toBe(0);
            expect(getY(1)).toBe(30);
            expect(getY(2)).toBe(70);
        });

        it("should pack in the middle with pack: center", function() {
            makeCt('center');
            expect(getY(0)).toBe(255);
            expect(getY(1)).toBe(285);
            expect(getY(2)).toBe(325);
        });

        it("should pack at the bottom with pack: cend", function() {
            makeCt('end');
            expect(getY(0)).toBe(510);
            expect(getY(1)).toBe(540);
            expect(getY(2)).toBe(580);
        });
    });

    describe("align", function() {
        var getX, getY, getWidth, getHeight;

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
                        type: 'vbox',
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
            getX = getY = getWidth = getHeight = null;
        });

        describe("left/center/right", function() {

            it("should keep items at the left when using align: left", function() {
                makeCt('left', [{
                    html: 'a'
                }, {
                    html: 'b'
                }]);
                expect(getX(0)).toBe(0);
                expect(getX(1)).toBe(0);
            });

            it("should align items in the middle when using align: center", function() {
                makeCt('center', [{
                    width: 100
                }, {
                    width: 300
                }]);
                expect(getX(0)).toBe(250);
                expect(getX(1)).toBe(150);
            });

            it("should keep items to the right when using align: right", function() {
                makeCt('right', [{
                    html: 'a'
                }, {
                    html: 'b'
                }]);
                expect(getX(0)).toBe(600 - getWidth(0));
                expect(getX(1)).toBe(600 - getWidth(1));
            });

            describe("constrainAlign", function() {
                var makeLongString = function(c, len) {
                    var out = [],
                        i = 0;

                    for (; i < len; ++i) {
                        out.push(c);
                    }

                    return out.join(' ');
                };

                it("should constrain a shrink wrapped item with align: left", function() {
                    makeCt('left', [{
                        html: makeLongString('A', 100)
                    }], {
                        constrainAlign: true
                    });
                    expect(getWidth(0)).toBe(600);
                    expect(getX(0)).toBe(0);
                });

                it("should constrain a shrink wrapped item with align: center", function() {
                    makeCt('center', [{
                        html: makeLongString('A', 100)
                    }], {
                        constrainAlign: true
                    });
                    expect(getWidth(0)).toBe(600);
                    expect(getX(0)).toBe(0);
                });

                it("should constrain a shrink wrapped item with align: right", function() {
                    makeCt('center', [{
                        html: makeLongString('A', 100)
                    }], {
                        constrainAlign: true
                    });
                    expect(getWidth(0)).toBe(600);
                    expect(getX(0)).toBe(0);
                });

                it("should not constrain a fixed width item", function() {
                    makeCt('left', [{
                        html: 'A',
                        width: 1000
                    }], {
                        constrainAlign: false
                    });
                    expect(getWidth(0)).toBe(1000);
                });

                it("should recalculate the top positions", function() {
                    makeCt('left', [{
                        html: makeLongString('A', 100)
                    }, {
                        html: 'B'
                    }], {
                        constrainAlign: true
                    });

                    expect(getY(0)).toBe(0);
                    expect(getY(1)).toBe(getHeight(0));
                });
            });
        });

        describe("stretchmax", function() {

            it("should stretch all items to the size of the largest when using align: stretchmax", function() {
                makeCt('stretchmax', [{
                    html: 'foo'
                }, {
                    html: 'foo bar baz'
                }, {
                    html: 'foo'
                }]);

                c = new Ext.Component({
                    renderTo: Ext.getBody(),
                    html: 'foo bar baz',
                    floating: true
                });

                var expected = c.getWidth();

                c.destroy();

                expect(getWidth(0)).toBe(expected);
                expect(getWidth(1)).toBe(expected);
                expect(getWidth(2)).toBe(expected);
            });

            it("should always use a stretchmax over a fixed width", function() {
                makeCt('stretchmax', [{
                    width: 30
                }, {
                    html: 'foo bar baz blah long text'
                }, {
                    html: 'foo'
                }]);

                c = new Ext.Component({
                    renderTo: Ext.getBody(),
                    html: 'foo bar baz blah long text',
                    floating: true
                });

                var expected = c.getWidth();

                c.destroy();

                expect(getWidth(0)).toBe(expected);
                expect(getWidth(1)).toBe(expected);
                expect(getWidth(2)).toBe(expected);
            });

            describe("minWidth", function() {
                it("should stretch an item with a minWidth", function() {
                    makeCt('stretchmax', [{
                        width: 30
                    }, {
                        minWidth: 5
                    }]);
                    expect(getWidth(0)).toBe(30);
                    expect(getWidth(1)).toBe(30);
                });

                it("should stretch to the item with the largest minWidth", function() {
                    makeCt('stretchmax', [{
                        minWidth: 30
                    }, {
                        minWidth: 50
                    }]);
                    expect(getWidth(0)).toBe(50);
                    expect(getWidth(1)).toBe(50);
                });

                it("should stretch a single item outside the bounds of the container", function() {
                    makeCt('stretchmax', [{
                        xtype: 'panel',
                        title: 'Title',
                        minWidth: 1000,
                        shrinkWrap: true,
                        shrinkWrapDock: true,
                        html: 'Content...'
                    }], {
                        autoScroll: true
                    });
                    expect(getWidth(0)).toBe(1000);
                });
            });

            it("should respect a maxWidth", function() {
                makeCt('stretchmax', [{
                    width: 30
                }, {
                    maxWidth: 20
                }]);
                expect(getWidth(0)).toBe(30);
                expect(getWidth(1)).toBe(20);
            });
        });

        it("should stretch all items to the container width", function() {
            makeCt('stretch', [{
             }, {
             }]);
            expect(getWidth(0)).toBe(600);
            expect(getWidth(1)).toBe(600);
        });
    });

    describe("height", function() {
        var getHeight;

        beforeEach(function() {
            makeCt = function(items) {
                ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    width: 100,
                    height: 600,
                    defaultType: 'component',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    items: items
                });
            };

            getHeight = function(index) {
                return ct.items.getAt(index).getHeight();
            };
        });

        afterEach(function() {
            getHeight = null;
        });

        describe("flex only", function() {
            it("should stretch a single flex item to the height of the container", function() {
                makeCt({
                    flex: 1
                });
                expect(getHeight(0)).toBe(600);
            });

            it("should stretch 3 equally flexed items equally", function() {
                makeCt([{
                    flex: 1
                }, {
                    flex: 1
                }, {
                    flex: 1
                }]);
                expect(getHeight(0)).toBe(200);
                expect(getHeight(1)).toBe(200);
                expect(getHeight(2)).toBe(200);
            });

            it("should flex 2 items according to ratio", function() {
                makeCt([{
                    flex: 3
                }, {
                    flex: 1
                }]);
                expect(getHeight(0)).toBe(450);
                expect(getHeight(1)).toBe(150);
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
                expect(getHeight(0)).toBe(225);
                expect(getHeight(1)).toBe(75);
                expect(getHeight(2)).toBe(225);
                expect(getHeight(3)).toBe(75);
            });

            it("should use flex as a ratio", function() {
                makeCt([{
                    flex: 5000000
                }, {
                    flex: 1000000
                }]);
                expect(getHeight(0)).toBe(500);
                expect(getHeight(1)).toBe(100);
            });
        });

        describe("fixed height only", function() {
            it("should set the height of a single item", function() {
                makeCt({
                    height: 200
                });
                expect(getHeight(0)).toBe(200);
            });

            it("should set the height of multiple items", function() {
                makeCt([{
                    height: 500
                }, {
                    height: 50
                }]);
                expect(getHeight(0)).toBe(500);
                expect(getHeight(1)).toBe(50);
            });

            it("should allow a single item to exceed the container height", function() {
                makeCt({
                    height: 900
                });
                expect(getHeight(0)).toBe(900);
            });

            it("should allow multiple items to exceed the container height", function() {
                makeCt([{
                    height: 400
                }, {
                    height: 400
                }]);
                expect(getHeight(0)).toBe(400);
                expect(getHeight(1)).toBe(400);
            });
        });

        describe("%age", function() {
            it("should be able to use %age height", function() {
                makeCt([{
                    height: '50%'
                }, {
                    height: '50%'
                }]);
                expect(getHeight(0)).toBe(300);
                expect(getHeight(1)).toBe(300);
            });

            it("should work with fixed height", function() {
                makeCt([{
                    height: 100
                }, {
                    height: '20%'
                }, {
                    height: 380
                }]);
                expect(getHeight(0)).toBe(100);
                expect(getHeight(1)).toBe(120);
                expect(getHeight(2)).toBe(380);
            });

            it("should work with flex", function() {
                makeCt([{
                    flex: 2
                }, {
                    height: '40%'
                }, {
                    flex: 1
                }]);
                expect(getHeight(0)).toBe(240);
                expect(getHeight(1)).toBe(240);
                expect(getHeight(2)).toBe(120);
            });
        });

        describe("mixed", function() {
            it("should give any remaining space to a single flexed item", function() {
                makeCt([{
                    height: 200
                }, {
                    flex: 1
                }]);
                expect(getHeight(0)).toBe(200);
                expect(getHeight(1)).toBe(400);
            });

            it("should flex a single item with 2 fixed", function() {
                makeCt([{
                    height: 100
                }, {
                    flex: 1
                }, {
                    height: 300
                }]);
                expect(getHeight(0)).toBe(100);
                expect(getHeight(1)).toBe(200);
                expect(getHeight(2)).toBe(300);
            });

            it("should flex 2 items with 1 fixed", function() {
                makeCt([{
                    flex: 2
                }, {
                    height: 300
                }, {
                    flex: 1
                }]);
                expect(getHeight(0)).toBe(200);
                expect(getHeight(1)).toBe(300);
                expect(getHeight(2)).toBe(100);
            });

            it("should give priority to flex over a fixed height", function() {
                makeCt([{
                    flex: 1,
                    height: 200
                }, {
                    flex: 1
                }]);

                expect(getHeight(0)).toBe(300);
                expect(getHeight(1)).toBe(300);
            });
        });

        describe("min/max", function() {
            it("should assign a 0 height if there is no more flex height", function() {
                makeCt([{
                    flex: 1,
                    style: 'line-height:0'
                }, {
                    height: 700
                }]);
                expect(getHeight(0)).toBe(0);
                expect(getHeight(1)).toBe(700);
            });

            it("should respect a minWidth on a flex even if there is no more flex width", function() {
                makeCt([{
                    flex: 1,
                    minHeight: 50
                }, {
                    height: 700
                }]);
                expect(getHeight(0)).toBe(50);
                expect(getHeight(1)).toBe(700);
            });

            it("should respect a minWidth on a flex even if there is no excess flex width", function() {
                makeCt([{
                    flex: 1,
                    maxHeight: 100
                }, {
                    height: 300
                }]);
                expect(getHeight(0)).toBe(100);
                expect(getHeight(1)).toBe(300);
            });

            it("should update flex values based on min constraint", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        minHeight: 500
                    }),
                    c2 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2]);
                expect(c1.getHeight()).toBe(500);
                expect(c2.getHeight()).toBe(100);
            });

            it("should handle multiple min constraints", function() {
                 var c1 = new Ext.Component({
                        flex: 1,
                        minHeight: 250
                    }),
                    c2 = new Ext.Component({
                        flex: 1,
                        minHeight: 250
                    }),
                    c3 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getHeight()).toBe(250);
                expect(c2.getHeight()).toBe(250);
                expect(c3.getHeight()).toBe(100);
            });

            it("should update flex values based on max constraint", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        maxHeight: 100
                    }),
                    c2 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2]);
                expect(c1.getHeight()).toBe(100);
                expect(c2.getHeight()).toBe(500);
            });

            it("should update flex values based on multiple max constraints", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        maxHeight: 100
                    }),
                    c2 = new Ext.Component({
                        flex: 1,
                        maxHeight: 100
                    }),
                    c3 = new Ext.Component({
                        flex: 1
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getHeight()).toBe(100);
                expect(c2.getHeight()).toBe(100);
                expect(c3.getHeight()).toBe(400);
            });

            it("should give precedence to min constraints over flex when the min is the same", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        minHeight: 200
                    }),
                    c2 = new Ext.Component({
                        flex: 3,
                        minHeight: 200
                    }),
                    c3 = new Ext.Component({
                        flex: 1,
                        minHeight: 200
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getHeight()).toBe(200);
                expect(c2.getHeight()).toBe(200);
                expect(c3.getHeight()).toBe(200);
            });

            it("should give precedence to max constraints over flex when the max is the same", function() {
                var c1 = new Ext.Component({
                        flex: 1,
                        maxHeight: 100
                    }),
                    c2 = new Ext.Component({
                        flex: 3,
                        maxHeight: 100
                    }),
                    c3 = new Ext.Component({
                        flex: 1,
                        maxHeight: 100
                    });

                makeCt([c1, c2, c3]);
                expect(c1.getHeight()).toBe(100);
                expect(c2.getHeight()).toBe(100);
                expect(c3.getHeight()).toBe(100);
            });

            describe("with %age", function() {
                it("should respect min constraints", function() {
                    document.documentElement.style.height = document.body.style.height = '100%';

                    makeCt([{
                        height: '10%',
                        minHeight: 250
                    }, {
                        flex: 1
                    }]);
                    expect(getHeight(0)).toBe(250);
                    expect(getHeight(1)).toBe(350);

                    document.documentElement.style.height = document.body.style.height = '';
                });

                it("should respect max constraints", function() {
                    document.documentElement.style.height = document.body.style.height = '100%';
                    makeCt([{
                        height: '90%',
                        maxHeight: 100
                    }, {
                        flex: 1
                    }]);
                    expect(getHeight(0)).toBe(100);
                    expect(getHeight(1)).toBe(500);
                    document.documentElement.style.height = document.body.style.height = '';
                });
            });
        });
    });

    // Taken from extjs/test/issues/issue.html?id=5497
    it("should align:center when box layouts are nested", function() {
        // create a temporary component to measure the width of the text when
        // rendered directly to the body by itself
        var txtEl = Ext.widget({
                xtype: 'component',
                autoEl: 'span',
                html: 'Some informative title',
                renderTo: document.body
            }),
            twidth = txtEl.el.getWidth(),
            // the target width for the spec below will be the measured width here
            w = Ext.isIE9 ? twidth + 1 : twidth,
            h = 20,
            y = 0,
            x = [90, 92];

        txtEl.destroy();

        ct = Ext.create('Ext.container.Container', {
            renderTo: document.body,
            style: 'background-color:yellow',
            width: 300,
            height: 200,
            layout: {
               type: 'vbox',
               align: 'stretch'
            },
            items: {
                id: 'l1',
                xtype: 'container',
                style: 'background-color:red',
                layout: {
                    type: 'vbox',
                    align: 'center'
                },
                items: {
                    id: 'l2',
                    style: 'background-color:blue;color:white',
                    xtype: 'component', html: 'Some informative title', height: 20
                }
            }
        });

        expect(ct).toHaveLayout({
           "el": {
              "xywh": "0 0 300 200"
           },
           "items": {
              "l1": {
                 "el": {
                    "xywh": "0 0 300 20"
                 },
                 "items": {
                    "l2": {
                       "el": {
                          x: x,
                          y: y,
                          w: [w - 1, w + 1],
                          h: [h - 1, h + 1]
                       }
                    }
                 }
              }
           }
        });
    });

    // Taken from extjs/test/issues/5562.html
    // Shrinkwrapping VBox should add height for any horizontal scrollbar if any of the boxes overflowed the Container width.
    // So ct1's published shrinkwrap width shoiuld be the height of the two children cmp1 and cmp2 plus scrollbart height.
    it("should include horizontal scroller in reported shrinkwrap height", function() {
        ct = Ext.create('Ext.container.Container', {
            renderTo: document.body,
            width: 200,
            style: 'border: 1px solid black',
            layout: 'hbox',
            items: [{
                itemId: 'ct1',
                defaultType: 'component',
                style: 'background-color:yellow',
                xtype: 'container',
                layout: 'vbox',
                flex: 1,
                overflowX: 'auto',
                items: [{
                    itemId: 'cmp1',
                    width: 500,
                    style: 'background-color:red',
                    html: 'child 1 content asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf asdf',
                    margin: '0 3 0 0'
                }, {
                    itemId: 'cmp2',
                    style: 'background-color:blue',
                    html: 'child 2 content',
                    margin: '0 0 0 2'
                }]
            }]
        });

        var ct1 = ct.child('#ct1'),
            cmp1 = ct1.child('#cmp1'),
            cmp2 = ct1.child('#cmp2'),
            cmp1Height = cmp1.getHeight(),
            cmp2Height = cmp2.getHeight();

        // cmp1 should have wrapped to two lines of text, cmp2 only one.
        expect(cmp1Height).toBeGreaterThanOrEqual((cmp2Height * 2) - 1);
        expect(cmp1Height).toBeLessThanOrEqual((cmp2Height * 2) + 1);

        // ct1 should contain a scrollbar as well as the two stacked up Components.
        // So the container which contains those two should be cmp1Height + cmp2Height + <scrollbarHeight> high
        expect(ct1.el.getHeight()).toEqual(cmp1Height + cmp2Height + Ext.getScrollbarSize().height);

        // ct just has border:1px solid black, so should be 2px higher
        expect(ct.el.getHeight()).toEqual(cmp1Height + cmp2Height + Ext.getScrollbarSize().height + 2);
    });

    it("should size correctly with docked items & a configured parallel size & shrinkWrap perpendicular size", function() {
        ct = new Ext.panel.Panel({
            floating: true,
            shadow: false,
            autoShow: true,
            border: false,
            layout: 'vbox',
            height: 150,
            dockedItems: [{
                dock: 'top',
                xtype: 'component',
                html: 'X'
            }],
            items: [{
                xtype: 'component',
                html: '<div style="width: 50px;"></div>'
            }]
        });
        expect(ct.getWidth()).toBe(50);
        expect(ct.getHeight()).toBe(150);
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
                    type: 'vbox'
                }, layoutOptions)
            }, cfg));
        }

        function makeShrinkWrapItem(h, w) {
            return {
                html: makeShrinkWrapHtml(h, w)
            };
        }

        function makeShrinkWrapHtml(h, w) {
            h = h || 10;
            w = w || 10;

            return Ext.String.format('<div style="height: {0}px; width: {1}px;"></div>', h, w);
        }

        function expectScroll(vertical, horizontal) {
            var el = ct.getEl(),
                dom = el.dom;

            expectScrollDimension(vertical, el, dom, el.getStyle('overflow-y'), dom.scrollHeight, dom.clientHeight);
            expectScrollDimension(horizontal, el, dom, el.getStyle('overflow-x'), dom.scrollWidth, dom.clientWidth);
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
                it("should limit the innerCt height to the container height", function() {
                    makeCt({
                        width: defaultSize,
                        height: defaultSize,
                        defaultType: 'component',
                        items: [{
                            height: 400
                        }, {
                            height: 400
                        }]
                    });
                    expectInnerCtHeight(defaultSize);
                });
            });

            describe("user scrolling disabled", function() {
                it("should limit the innerCt height to the container height", function() {
                    makeCt({
                        width: defaultSize,
                        height: defaultSize,
                        defaultType: 'component',
                        scrollable: {
                            x: false
                        },
                        items: [{
                            height: 400
                        }, {
                            height: 400
                        }]
                    });
                    expectInnerCtHeight(800);
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

            describe("vertical scrolling", function() {
                it("should not show a scrollbar when configured to not scroll vertically", function() {
                    makeFixedCt([{
                        height: 400
                    }, {
                        height: 400
                    }], {
                        y: false
                    });
                    expectScroll(false, false);
                    expectInnerCtHeight(800);
                });

                describe("with no horizontal scrollbar", function() {
                    describe("configured", function() {
                        it("should not show a scrollbar when the total height does not overflow", function() {
                            makeFixedCt([{
                                height: 100
                            }, {
                                height: 100
                            }]);
                            expectScroll(false, false);
                            expectHeights([100, 100]);
                            expectInnerCtHeight(defaultSize);
                        });

                        it("should show a scrollbar when the total height overflows", function() {
                            makeFixedCt([{
                                height: 400
                            }, {
                                height: 400
                            }]);
                            expectScroll(true, false);
                            expectHeights([400, 400]);
                            expectInnerCtHeight(800);
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
                            expectHeights([200, 400]);
                            expectInnerCtHeight(defaultSize);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    flex: 1
                                }, {
                                    flex: 1,
                                    minHeight: 300
                                }, {
                                    flex: 1
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, false);
                                expectHeights([100, 300, 100, 100]);
                                expectInnerCtHeight(defaultSize);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    flex: 1,
                                    minHeight: 400
                                }, {
                                    flex: 1,
                                    minHeight: 400
                                }]);
                                expectScroll(true, false);
                                expectHeights([400, 400]);
                                expectInnerCtHeight(800);
                            });
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should not show a scrollbar when the total height does not overflow", function() {
                            makeFixedCt([makeShrinkWrapItem(50), makeShrinkWrapItem(50)]);
                            expectScroll(false, false);
                            expectHeights([50, 50]);
                            expectInnerCtHeight(defaultSize);
                        });

                        it("should show a scrollbar when the total width overflows", function() {
                            makeFixedCt([makeShrinkWrapItem(400), makeShrinkWrapItem(400)]);
                            expectScroll(true, false);
                            expectHeights([400, 400]);
                            expectInnerCtHeight(800);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    minHeight: 200,
                                    html: makeShrinkWrapHtml(100)
                                }, {
                                    minHeight: 300,
                                    html: makeShrinkWrapHtml(50)
                                }]);
                                expectScroll(false, false);
                                expectHeights([200, 300]);
                                expectInnerCtHeight(defaultSize);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    minHeight: 400,
                                    html: makeShrinkWrapHtml(100)
                                }, {
                                    minHeight: 500,
                                    html: makeShrinkWrapHtml(50)
                                }]);
                                expectScroll(true, false);
                                expectHeights([400, 500]);
                                expectInnerCtHeight(900);
                            });
                        });
                    });

                    describe("configured + calculated", function() {
                        it("should not show a scrollbar when the configured height does not overflow", function() {
                            makeFixedCt([{
                                height: 300
                            }, {
                                flex: 1
                            }]);
                            expectScroll(false, false);
                            expectHeights([300, 300]);
                            expectInnerCtHeight(defaultSize);
                        });

                        it("should show a scrollbar when the configured height overflows", function() {
                            makeFixedCt([{
                                height: 700
                            }, {
                                flex: 1
                            }]);
                            expectScroll(true, false);
                            expectHeights([700, 0]);
                            expectInnerCtHeight(700);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    height: 300
                                }, {
                                    flex: 1,
                                    minHeight: 200
                                }]);
                                expectScroll(false, false);
                                expectHeights([300, 300]);
                                expectInnerCtHeight(defaultSize);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    height: 300
                                }, {
                                    flex: 1,
                                    minHeight: 500
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 500]);
                                expectInnerCtHeight(800);
                            });
                        });
                    });

                    describe("configured + shrinkWrap", function() {
                        it("should not show a scrollbar when the total height does not overflow", function() {
                            makeFixedCt([{
                                height: 300
                            }, makeShrinkWrapItem(200)]);
                            expectScroll(false, false);
                            expectHeights([300, 200]);
                            expectInnerCtHeight(defaultSize);
                        });

                        it("should show a scrollbar when the total height overflows", function() {
                            makeFixedCt([{
                                height: 400
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(true, false);
                            expectHeights([400, 400]);
                            expectInnerCtHeight(800);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    height: 300
                                }, {
                                    html: makeShrinkWrapHtml(100),
                                    minHeight: 200
                                }]);
                                expectScroll(false, false);
                                expectHeights([300, 200]);
                                expectInnerCtHeight(defaultSize);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    height: 300
                                }, {
                                    html: makeShrinkWrapHtml(200),
                                    minHeight: 500
                                }]);
                                expectScroll(true, false);
                                expectHeights([300, 500]);
                                expectInnerCtHeight(800);
                            });
                        });
                    });

                    describe("calculated + shrinkWrap", function() {
                        it("should not show a scrollbar when the shrinkWrap height does not overflow", function() {
                            makeFixedCt([makeShrinkWrapItem(500), {
                                flex: 1
                            }]);
                            expectScroll(false, false);
                            expectHeights([500, 100]);
                            expectInnerCtHeight(defaultSize);
                        });

                        it("should show a scrollbar when the shrinkWrap height overflows", function() {
                            makeFixedCt([makeShrinkWrapItem(700), {
                                flex: 1
                            }]);
                            expectScroll(true, false);
                            expectHeights([700, 0]);
                            expectInnerCtHeight(700);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minHeight does not cause an overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(100),
                                    minHeight: 200
                                }, {
                                    flex: 1,
                                    minHeight: 300
                                }]);
                                expectScroll(false, false);
                                expectHeights([200, 400]);
                                expectInnerCtHeight(defaultSize);
                            });

                            it("should show a scrollbar when the minHeight causes an overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(100),
                                    minHeight: 350
                                }, {
                                    flex: 1,
                                    minHeight: 350
                                }]);
                                expectScroll(true, false);
                                expectHeights([350, 350]);
                                expectInnerCtHeight(700);
                            });
                        });
                    });
                });

                describe("with a horizontal scrollbar", function() {
                    var big = 1000;

                    describe("where the horizontal scroll can be inferred before the first pass", function() {
                        describe("configured", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    height: 100,
                                    width: big
                                }, {
                                    height: 100
                                }]);
                                expectScroll(false, true);
                                expectHeights([100, 100]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    height: 400,
                                    width: big
                                }, {
                                    height: 400
                                }]);
                                expectScroll(true, true);
                                expectHeights([400, 400]);
                                expectInnerCtHeight(800);
                            });
                        });

                        describe("calculated", function() {
                            // There will never be overflow when using only calculated
                            it("should account for the horizontal scrollbar", function() {
                                makeFixedCt([{
                                    flex: 1,
                                    width: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectHeights([290, 290]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            describe("minWidth", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minHeight: 400,
                                        width: big
                                    }, {
                                        flex: 1
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([400, 180]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minHeight: 350,
                                        width: big
                                    }, {
                                        flex: 1,
                                        minHeight: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([350, 350]);
                                    expectInnerCtHeight(700);
                                });
                            });
                        });

                        describe("shrinkWrap", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(200),
                                    width: big
                                }, {
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectHeights([200, 300]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400),
                                    width: big
                                }, {
                                    html: makeShrinkWrapHtml(400)
                                }]);
                                expectScroll(true, true);
                                expectHeights([400, 400]);
                                expectInnerCtHeight(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100),
                                        minHeight: 200,
                                        width: big
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minHeight: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 300]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100),
                                        minHeight: 350,
                                        width: big
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minHeight: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([350, 350]);
                                    expectInnerCtHeight(700);
                                });
                            });
                        });

                        describe("configured + calculated", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    height: 150,
                                    width: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectHeights([150, 430]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    height: 800,
                                    width: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectHeights([800, 0]);
                                expectInnerCtHeight(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        width: big
                                    }, {
                                        flex: 1,
                                        minHeight: 200
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 380]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        width: big
                                    }, {
                                        flex: 1,
                                        minHeight: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([200, 500]);
                                    expectInnerCtHeight(700);
                                });
                            });
                        });

                        describe("configured + shrinkWrap", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    height: 150,
                                    width: big
                                }, makeShrinkWrapItem(300)]);
                                expectScroll(false, true);
                                expectHeights([150, 300]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    height: 350,
                                    width: big
                                }, makeShrinkWrapItem(350)]);
                                expectScroll(true, true);
                                expectHeights([350, 350]);
                                expectInnerCtHeight(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        width: big
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minHeight: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 300]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        width: big
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minHeight: 550
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([200, 550]);
                                    expectInnerCtHeight(750);
                                });
                            });
                        });

                        describe("calculated + shrinkWrap", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(300),
                                    width: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectHeights([300, 280]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapItem(700),
                                    width: big
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectHeights([700, 0]);
                                expectInnerCtHeight(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(200),
                                        width: big
                                    }, {
                                        flex: 1,
                                        minHeight: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 380]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400),
                                        width: big
                                    }, {
                                        flex: 1,
                                        minHeight: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([400, 500]);
                                    expectInnerCtHeight(900);
                                });
                            });
                        });
                    });

                    describe("when the horizontal scroll needs to be calculated", function() {
                        describe("configured", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    height: 100,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    height: 100
                                }]);
                                expectScroll(false, true);
                                expectHeights([100, 100]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    height: 400,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    height: 400
                                }]);
                                expectScroll(true, true);
                                expectHeights([400, 400]);
                                expectInnerCtHeight(800);
                            });
                        });

                        describe("calculated", function() {
                            // There will never be overflow when using only calculated
                            it("should account for the horizontal scrollbar", function() {
                                makeFixedCt([{
                                    flex: 1,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectHeights([290, 290]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minHeight: 400,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([400, 180]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        flex: 1,
                                        minHeight: 350,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1,
                                        minHeight: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([350, 350]);
                                    expectInnerCtHeight(700);
                                });
                            });
                        });

                        describe("shrinkWrap", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(200, big)
                                }, {
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectHeights([200, 300]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400, big)
                                }, {
                                    html: makeShrinkWrapHtml(400)
                                }]);
                                expectScroll(true, true);
                                expectHeights([400, 400]);
                                expectInnerCtHeight(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100, big),
                                        minHeight: 200
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minHeight: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 300]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(100, big),
                                        minHeight: 350
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minHeight: 350
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([350, 350]);
                                    expectInnerCtHeight(700);
                                });
                            });
                        });

                        describe("configured + calculated", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    height: 150,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectHeights([150, 430]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    height: 800,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectHeights([800, 0]);
                                expectInnerCtHeight(800);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1,
                                        minHeight: 200
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 380]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        flex: 1,
                                        minHeight: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([200, 500]);
                                    expectInnerCtHeight(700);
                                });
                            });
                        });

                        describe("configured + shrinkWrap", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    height: 150,
                                    html: makeShrinkWrapHtml(10, big)
                                }, makeShrinkWrapItem(300)]);
                                expectScroll(false, true);
                                expectHeights([150, 300]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    height: 350,
                                    html: makeShrinkWrapHtml(10, big)
                                }, makeShrinkWrapItem(350)]);
                                expectScroll(true, true);
                                expectHeights([350, 350]);
                                expectInnerCtHeight(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        html: makeShrinkWrapHtml(100),
                                        minHeight: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 300]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        height: 200,
                                        html: makeShrinkWrapHtml(10, big)
                                    }, {
                                        html: makeShrinkWrapHtml(200),
                                        minHeight: 550
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([200, 550]);
                                    expectInnerCtHeight(750);
                                });
                            });
                        });

                        describe("calculated + shrinkWrap", function() {
                            it("should account for the horizontal scrollbar when there is no overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(300, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(false, true);
                                expectHeights([300, 280]);
                                expectInnerCtHeight(defaultSize - scrollSize);
                            });

                            it("should account for the horizontal scrollbar when there is overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapItem(700, big)
                                }, {
                                    flex: 1
                                }]);
                                expectScroll(true, true);
                                expectHeights([700, 0]);
                                expectInnerCtHeight(700);
                            });

                            describe("with constraint", function() {
                                it("should account for the horizontal scrollbar when the minHeight does not cause overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(200, big)
                                    }, {
                                        flex: 1,
                                        minHeight: 300
                                    }]);
                                    expectScroll(false, true);
                                    expectHeights([200, 380]);
                                    expectInnerCtHeight(defaultSize - scrollSize);
                                });

                                it("should account for the horizontal scrollbar when the minHeight causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400, big)
                                    }, {
                                        flex: 1,
                                        minHeight: 500
                                    }]);
                                    expectScroll(true, true);
                                    expectHeights([400, 500]);
                                    expectInnerCtHeight(900);
                                });
                            });
                        });
                    });

                    describe("when the horizontal scrollbar triggers a vertical scrollbar", function() {
                        var scrollTakesSize = Ext.getScrollbarSize().height > 0;

                        describe("configured", function() {
                            it("should account for the horizontal scrollbar", function() {
                                makeFixedCt([{
                                    height: 295,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    height: 295
                                }]);
                                expectScroll(scrollTakesSize, true);
                                expectHeights([295, 295]);
                                expectInnerCtHeight(590);
                            });
                        });

                        describe("shrinkWrap", function() {
                            it("should account for the horizontal scrollbar", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(295, big)
                                }, makeShrinkWrapItem(295)]);
                                expectScroll(scrollTakesSize, true);
                                expectHeights([295, 295]);
                                expectInnerCtHeight(590);
                            });
                        });

                        describe("configured + shrinkWrap", function() {
                            it("should account for the horizontal scrollbar", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(295, big)
                                }, {
                                    height: 295
                                }]);
                                expectScroll(scrollTakesSize, true);
                                expectHeights([295, 295]);
                                expectInnerCtHeight(590);
                            });
                        });
                    });
                });
            });

            describe("horizontal scrolling", function() {
                it("should not show a scrollbar when configured to not scroll horizontally", function() {
                    makeFixedCt([{
                        height: 100,
                        width: 900
                    }, {
                        height: 100
                    }], {
                        x: false
                    });
                    expectScroll(false, false);
                });

                describe("with no vertical scrollbar", function() {
                    describe("configured width", function() {
                        it("should not show a scrollbar when the largest width does not overflow", function() {
                            makeFixedCt([{
                                height: 100,
                                width: 300
                            }, {
                                height: 100,
                                width: 400
                            }]);
                            expectScroll(false, false);
                            expectWidths([300, 400]);
                            expectInnerCtWidth(400);
                        });

                        it("should show a scrollbar when the largest width overflows", function() {
                            makeFixedCt([{
                                height: 100,
                                width: 700
                            }, {
                                height: 200,
                                width: 800
                            }]);
                            expectScroll(false, true);
                            expectWidths([700, 800]);
                            expectInnerCtWidth(800);
                        });
                    });

                    describe("align stretch", function() {
                        it("should not show a scrollbar by default", function() {
                            makeFixedCt([{
                                height: 100
                            }, {
                                height: 100
                            }], true, { align: 'stretch' });
                            expectScroll(false, false);
                            expectWidths([600, 600]);
                            expectInnerCtWidth(defaultSize);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    height: 100,
                                    minWidth: 400
                                }, {
                                    height: 100
                                }], true, { align: 'stretch' });
                                expectScroll(false, false);
                                expectWidths([600, 600]);
                                expectInnerCtWidth(defaultSize);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    height: 100,
                                    minWidth: 800
                                }, {
                                    height: 100
                                }], true, { align: 'stretch' });
                                expectScroll(false, true);
                                expectWidths([800, 600]);
                                expectInnerCtWidth(800);
                            });
                        });
                    });

                    describe("shrinkWrap width", function() {
                        it("should not show a scrollbar when the largest width does not overflow", function() {
                            makeFixedCt([makeShrinkWrapItem(10, 300), makeShrinkWrapItem(10, 200)]);
                            expectScroll(false, false);
                            expectWidths([300, 200]);
                            expectInnerCtWidth(300);
                        });

                        it("should show a scrollbar when the largest width overflows", function() {
                            makeFixedCt([makeShrinkWrapItem(10, 500), makeShrinkWrapItem(10, 750)]);
                            expectScroll(false, true);
                            expectWidths([500, 750]);
                            expectInnerCtWidth(750);
                        });

                        describe("with constraint", function() {
                            it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                makeFixedCt([{
                                    height: 100,
                                    minWidth: 400,
                                    html: makeShrinkWrapHtml(10, 10)
                                }, {
                                    height: 100,
                                    minWidth: 500,
                                    html: makeShrinkWrapHtml(10, 10)
                                }]);
                                expectScroll(false, false);
                                expectWidths([400, 500]);
                                expectInnerCtWidth(500);
                            });

                            it("should show a scrollbar when the minWidth causes an overflow", function() {
                                makeFixedCt([{
                                    height: 100,
                                    minWidth: 650,
                                    html: makeShrinkWrapHtml(10, 50)
                                }, {
                                    height: 100,
                                    minWidth: 750,
                                    html: makeShrinkWrapHtml(10, 50)
                                }]);
                                expectScroll(false, true);
                                expectWidths([650, 750]);
                                expectInnerCtWidth(750);
                            });
                        });
                    });
                });

                describe("with a vertical scrollbar", function() {
                    describe("where the vertical scroll can be inferred before the first pass", function() {
                        describe("configured width", function() {
                            it("should not show a scrollbar when the largest width does not overflow", function() {
                                makeFixedCt([{
                                    height: 400,
                                    width: 300
                                }, {
                                    height: 400,
                                    width: 400
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 400]);
                                expectInnerCtWidth(400);
                            });

                            it("should show a scrollbar when the largest width overflows", function() {
                                makeFixedCt([{
                                    height: 400,
                                    width: 700
                                }, {
                                    height: 400,
                                    width: 800
                                }]);
                                expectScroll(true, true);
                                expectWidths([700, 800]);
                                expectInnerCtWidth(800);
                            });
                        });

                        describe("align stretch", function() {
                            it("should not show a scrollbar by default", function() {
                                makeFixedCt([{
                                    height: 400
                                }, {
                                    height: 400
                                }], true, { align: 'stretch' });
                                expectScroll(true, false);
                                expectWidths([580, 580]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                    makeFixedCt([{
                                        height: 400,
                                        minWidth: 400
                                    }, {
                                        height: 400
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, false);
                                    expectWidths([580, 580]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should should a scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        height: 400,
                                        minWidth: 800
                                    }, {
                                        height: 400
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, true);
                                    expectWidths([800, 580]);
                                    expectInnerCtWidth(800);
                                });
                            });
                        });

                        describe("shrinkWrap width", function() {
                            it("should not show a scrollbar when the largest width does not overflow", function() {
                                makeFixedCt([{
                                    height: 400,
                                    html: makeShrinkWrapHtml(10, 300)
                                }, {
                                    height: 400,
                                    html: makeShrinkWrapHtml(10, 200)
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 200]);
                                expectInnerCtWidth(300);
                            });

                            it("should show a scrollbar when the largest width overflows", function() {
                                makeFixedCt([{
                                    height: 400,
                                    html: makeShrinkWrapHtml(10, 500)
                                }, {
                                    height: 400,
                                    html: makeShrinkWrapHtml(10, 750)
                                }]);
                                expectScroll(true, true);
                                expectWidths([500, 750]);
                                expectInnerCtWidth(750);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                    makeFixedCt([{
                                        height: 400,
                                        minWidth: 400,
                                        html: makeShrinkWrapHtml(10, 10)
                                    }, {
                                        height: 400,
                                        minWidth: 500,
                                        html: makeShrinkWrapHtml(10, 10)
                                    }]);
                                    expectScroll(true, false);
                                    expectWidths([400, 500]);
                                    expectInnerCtWidth(500);
                                });

                                it("should should a scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        height: 400,
                                        minWidth: 650,
                                        html: makeShrinkWrapHtml(10, 50)
                                    }, {
                                        height: 400,
                                        minWidth: 750,
                                        html: makeShrinkWrapHtml(10, 50)
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([650, 750]);
                                    expectInnerCtWidth(750);
                                });
                            });
                        });
                    });

                    describe("when the horizontal scroll needs to be calculated", function() {
                        describe("configured width", function() {
                            it("should not show a scrollbar when the largest width does not overflow", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400),
                                    width: 300
                                }, {
                                    html: makeShrinkWrapHtml(400),
                                    width: 400
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 400]);
                                expectInnerCtWidth(400);
                            });

                            it("should show a scrollbar when the largest width overflows", function() {
                                makeFixedCt([{
                                    html: makeShrinkWrapHtml(400),
                                    width: 700
                                }, {
                                    html: makeShrinkWrapHtml(400),
                                    width: 800
                                }]);
                                expectScroll(true, true);
                                expectWidths([700, 800]);
                                expectInnerCtWidth(800);
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
                                expectWidths([580, 580]);
                                expectInnerCtWidth(defaultSize - scrollSize);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400),
                                        minWidth: 400
                                    }, {
                                        html: makeShrinkWrapHtml(400)
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, false);
                                    expectWidths([580, 580]);
                                    expectInnerCtWidth(defaultSize - scrollSize);
                                });

                                it("should show a scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        html: makeShrinkWrapHtml(400),
                                        minWidth: 800
                                    }, {
                                        html: makeShrinkWrapHtml(400)
                                    }], true, { align: 'stretch' });
                                    expectScroll(true, true);
                                    expectWidths([800, 580]);
                                    expectInnerCtWidth(800);
                                });
                            });
                        });

                        describe("shrinkWrap height", function() {
                            it("should not show a scrollbar when the largest width does not overflow", function() {
                                makeFixedCt([makeShrinkWrapItem(400, 300), makeShrinkWrapItem(400, 200)]);
                                expectScroll(true, false);
                                expectWidths([300, 200]);
                                expectInnerCtWidth(300);
                            });

                            it("should show a scrollbar when the largest width overflows", function() {
                                makeFixedCt([makeShrinkWrapItem(400, 500), makeShrinkWrapItem(400, 750)]);
                                expectScroll(true, true);
                                expectWidths([500, 750]);
                                expectInnerCtWidth(750);
                            });

                            describe("with constraint", function() {
                                it("should not show a scrollbar when the minWidth does not cause an overflow", function() {
                                    makeFixedCt([{
                                        minWidth: 400,
                                        html: makeShrinkWrapHtml(400, 10)
                                    }, {
                                        minWidth: 500,
                                        html: makeShrinkWrapHtml(400, 10)
                                    }]);
                                    expectScroll(true, false);
                                    expectWidths([400, 500]);
                                    expectInnerCtWidth(500);
                                });

                                it("should show a scrollbar when the minWidth causes an overflow", function() {
                                    makeFixedCt([{
                                        minWidth: 650,
                                        html: makeShrinkWrapHtml(400, 50)
                                    }, {
                                        minWidth: 750,
                                        html: makeShrinkWrapHtml(400, 50)
                                    }]);
                                    expectScroll(true, true);
                                    expectWidths([650, 750]);
                                    expectInnerCtWidth(750);
                                });
                            });
                        });
                    });
                });
            });
        });

        describe("shrinkWrap width", function() {
            function makeShrinkWrapCt(items, scrollable, layoutOptions) {
                // Float to prevent stretching
                makeCt({
                    floating: true,
                    height: defaultSize,
                    defaultType: 'component',
                    items: items,
                    scrollable: scrollable !== undefined ? scrollable : true
                }, layoutOptions);
            }

            // Not testing vertical scroll here because it's never visible

            describe("with no vertical scrollbar", function() {
                describe("configured", function() {
                    it("should publish the largest width", function() {
                        makeShrinkWrapCt([{
                            height: 100,
                            width: 400
                        }, {
                            height: 100,
                            width: 500
                        }]);
                        expectScroll(false, false);
                        expectWidths([400, 500]);
                        expectCtWidth(500);
                    });
                });

                describe("shrinkWrap", function() {
                    it("should publish the largest width", function() {
                        makeShrinkWrapCt([{
                            height: 100,
                            html: makeShrinkWrapHtml(10, 250)
                        }, {
                            height: 100,
                            html: makeShrinkWrapHtml(10, 300)
                        }]);
                        expectScroll(false, false);
                        expectWidths([250, 300]);
                        expectCtWidth(300);
                    });

                    describe("with constraint", function() {
                        it("should publish the largest constrained width", function() {
                            makeShrinkWrapCt([{
                                height: 100,
                                html: makeShrinkWrapHtml(10, 150),
                                minWidth: 300
                            }, {
                                height: 100,
                                html: makeShrinkWrapHtml(10, 100),
                                minWidth: 350
                            }]);
                            expectScroll(false, false);
                            expectWidths([300, 350]);
                            expectCtWidth(350);
                        });
                    });
                });

                describe("align: stretch", function() {
                    it("should stretch items & publish the largest width", function() {
                        makeShrinkWrapCt([{
                            height: 100,
                            html: makeShrinkWrapHtml(10, 200)
                        }, {
                            height: 100,
                            html: makeShrinkWrapHtml(10, 300)
                        }], true, { align: 'stretch' });
                        expectScroll(false, false);
                        expectWidths([300, 300]);
                        expectCtWidth(300);
                    });

                    describe("with constraint", function() {
                        it("should stretch items and publish the largest constrained width", function() {
                            makeShrinkWrapCt([{
                                height: 100,
                                minWidth: 400
                            }, {
                                height: 100,
                                minWidth: 550
                            }], true, { align: 'stretch' });
                            expectScroll(false, false);
                            expectWidths([550, 550]);
                            expectCtWidth(550);
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
                                height: big,
                                width: 400
                            }, {
                                width: 500
                            }]);
                            expectScroll(true, false);
                            expectWidths([400, 500]);
                            expectCtWidth(520);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                height: big,
                                html: makeShrinkWrapHtml(10, 250)
                            }, {
                                html: makeShrinkWrapHtml(10, 300)
                            }]);
                            expectScroll(true, false);
                            expectWidths([250, 300]);
                            expectCtWidth(320);
                        });

                        describe("with constraint", function() {
                            it("should publish the largest constrained width", function() {
                                makeShrinkWrapCt([{
                                    height: big,
                                    html: makeShrinkWrapHtml(10, 150),
                                    minWidth: 300
                                }, {
                                    html: makeShrinkWrapHtml(10, 100),
                                    minWidth: 350
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 350]);
                                expectCtWidth(370);
                            });
                        });
                    });

                    describe("align: stretch", function() {
                        it("should stretch items & publish the largest width", function() {
                            makeShrinkWrapCt([{
                                height: big,
                                html: makeShrinkWrapHtml(10, 200)
                            }, {
                                height: 100,
                                html: makeShrinkWrapHtml(10, 300)
                            }]);
                            expectScroll(true, false);
                            expectWidths([200, 300]);
                            expectCtWidth(320);
                        });

                        describe("with constraint", function() {
                            it("should stretch items and publish the largest constrained width", function() {
                                makeShrinkWrapCt([{
                                    height: big,
                                    minWidth: 400
                                }, {
                                    height: 100,
                                    minWidth: 550
                                }]);
                                expectScroll(true, false);
                                expectWidths([400, 550]);
                                expectCtWidth(570);
                            });
                        });
                    });
                });

                describe("when the vertical scroll needs to be calculated", function() {
                    describe("configured", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(big, 400)
                            }, {
                                html: makeShrinkWrapHtml(10, 500)
                            }]);
                            expectScroll(true, false);
                            expectWidths([400, 500]);
                            expectCtWidth(520);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total width", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(big, 250)
                            }, {
                                html: makeShrinkWrapHtml(10, 300)
                            }]);
                            expectScroll(true, false);
                            expectWidths([250, 300]);
                            expectCtWidth(320);
                        });

                        describe("with constraint", function() {
                            it("should publish the largest constrained width", function() {
                                makeShrinkWrapCt([{
                                    html: makeShrinkWrapHtml(big, 150),
                                    minWidth: 300
                                }, {
                                    html: makeShrinkWrapHtml(10, 100),
                                    minWidth: 350
                                }]);
                                expectScroll(true, false);
                                expectWidths([300, 350]);
                                expectCtWidth(370);
                            });
                        });
                    });

                    describe("align: stretch", function() {
                        it("should stretch items & publish the largest width", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(big, 200)
                            }, {
                                height: 100,
                                html: makeShrinkWrapHtml(10, 300)
                            }], true, { align: 'stretch' });
                            expectScroll(true, false);
                            expectWidths([300, 300]);
                            expectCtWidth(320);
                        });

                        describe("with constraint", function() {
                            it("should stretch items and publish the largest constrained width", function() {
                                makeShrinkWrapCt([{
                                    html: makeShrinkWrapHtml(big, 10),
                                    minWidth: 400
                                }, {
                                    minWidth: 550
                                }], true, { align: 'stretch' });
                                expectScroll(true, false);
                                expectWidths([550, 550]);
                                expectCtWidth(570);
                            });
                        });
                    });
                });
            });
        });

        describe("shrinkWrap height", function() {
            function makeShrinkWrapCt(items, scrollable, layoutOptions) {
                makeCt({
                    // Float to prevent stretching
                    floating: true,
                    width: defaultSize,
                    defaultType: 'component',
                    items: items,
                    scrollable: scrollable !== undefined ? scrollable : true
                }, layoutOptions);
            }

            // Not testing vertical scroll here because it's never visible
            // Flex items become shrinkWrap when shrink wrapping the height, so we won't bother
            // with those

            describe("with no horizontal scrollbar", function() {
                describe("configured", function() {
                    it("should publish the total height", function() {
                        makeShrinkWrapCt([{
                            height: 400
                        }, {
                            height: 400
                        }]);
                        expectScroll(false, false);
                        expectHeights([400, 400]);
                        expectCtHeight(800);
                    });
                });

                describe("shrinkWrap", function() {
                    it("should publish the total height", function() {
                        makeShrinkWrapCt([makeShrinkWrapItem(400), makeShrinkWrapItem(400)]);
                        expectScroll(false, false);
                        expectHeights([400, 400]);
                        expectCtHeight(800);
                    });

                    describe("with constraint", function() {
                        it("should publish the total height", function() {
                            makeShrinkWrapCt([{
                                minHeight: 350,
                                html: makeShrinkWrapHtml(200)
                            }, {
                                minHeight: 400,
                                html: makeShrinkWrapHtml(300)
                            }]);
                            expectScroll(false, false);
                            expectHeights([350, 400]);
                            expectCtHeight(750);
                        });
                    });
                });

                describe("configured + shrinkWrap", function() {
                    it("should publish the total height", function() {
                        makeShrinkWrapCt([{
                            height: 400
                        }, makeShrinkWrapItem(400)]);
                        expectScroll(false, false);
                        expectHeights([400, 400]);
                        expectCtHeight(800);
                    });

                    describe("with constraint", function() {
                        it("should publish the total height", function() {
                            makeShrinkWrapCt([{
                                height: 350
                            }, {
                                minHeight: 400,
                                html: makeShrinkWrapHtml(300)
                            }]);
                            expectScroll(false, false);
                            expectHeights([350, 400]);
                            expectCtHeight(750);
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
                                height: 400,
                                width: big
                            }, {
                                height: 400
                            }]);
                            expectScroll(false, true);
                            expectHeights([400, 400]);
                            expectCtHeight(820);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(400),
                                width: big
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectHeights([400, 400]);
                            expectCtHeight(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total height", function() {
                                makeShrinkWrapCt([{
                                    minHeight: 350,
                                    html: makeShrinkWrapHtml(200),
                                    width: big
                                }, {
                                    minHeight: 400,
                                    html: makeShrinkWrapHtml(100)
                                }]);
                                expectScroll(false, true);
                                expectHeights([350, 400]);
                                expectCtHeight(770);
                            });
                        });
                    });

                    describe("configured + shrinkWrap", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                height: 400,
                                width: big
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectHeights([400, 400]);
                            expectCtHeight(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total height", function() {
                                makeShrinkWrapCt([{
                                    height: 350,
                                    width: big
                                }, {
                                    minHeight: 400,
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectHeights([350, 400]);
                                expectCtHeight(770);
                            });
                        });
                    });
                });

                describe("when the horizontal scroll needs to be calculated", function() {
                    describe("configured", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                height: 400,
                                html: makeShrinkWrapHtml(10, big)
                            }, {
                                height: 400
                            }]);
                            expectScroll(false, true);
                            expectHeights([400, 400]);
                            expectCtHeight(820);
                        });
                    });

                    describe("shrinkWrap", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                html: makeShrinkWrapHtml(400, big)
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectHeights([400, 400]);
                            expectCtHeight(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total height", function() {
                                makeShrinkWrapCt([{
                                    minHeight: 350,
                                    html: makeShrinkWrapHtml(200, big)
                                }, {
                                    minHeight: 400,
                                    html: makeShrinkWrapHtml(100)
                                }]);
                                expectScroll(false, true);
                                expectHeights([350, 400]);
                                expectCtHeight(770);
                            });
                        });
                    });

                    describe("configured + shrinkWrap", function() {
                        it("should account for the scrollbar in the total height", function() {
                            makeShrinkWrapCt([{
                                height: 400,
                                html: makeShrinkWrapHtml(10, big)
                            }, makeShrinkWrapItem(400)]);
                            expectScroll(false, true);
                            expectHeights([400, 400]);
                            expectCtHeight(820);
                        });

                        describe("with constraint", function() {
                            it("should account for the scrollbar in the total height", function() {
                                makeShrinkWrapCt([{
                                    height: 350,
                                    html: makeShrinkWrapHtml(10, big)
                                }, {
                                    minHeight: 400,
                                    html: makeShrinkWrapHtml(300)
                                }]);
                                expectScroll(false, true);
                                expectHeights([350, 400]);
                                expectCtHeight(770);
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
                    height: 400,
                    width: 400,
                    scrollable: true,
                    items: [{
                        height: 300,
                        width: 500
                    }, {
                        height: 300,
                        width: 500
                    }]
                });
                var scrollable = ct.getScrollable();

                scrollable.on('scrollend', endSpy);
                scrollable.scrollTo(30, 50);
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
                        x: 30,
                        y: 50
                    });
                });
            });

            it("should restore the horizontal/vertical scroll position with programmatic scrolling", function() {
                // Allows for only programmatic scrolling, but the scrollbars aren't visible
                makeCt({
                    height: 400,
                    width: 400,
                    scrollable: {
                        y: false,
                        x: false
                    },
                    items: [{
                        height: 300,
                        width: 500
                    }, {
                        height: 300,
                        width: 500
                    }]
                });
                var scrollable = ct.getScrollable();

                scrollable.on('scrollend', endSpy);
                scrollable.scrollTo(30, 50);
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
                        x: 30,
                        y: 50
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
                        layout: 'vbox',
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
        parentLayout: 'vbox'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'container',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'container',
        parentLayout: 'vbox'
    });

    createOverflowSuite({
        parentXtype: 'container',
        childXtype: 'panel',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'container',
        childXtype: 'panel',
        parentLayout: 'vbox'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'panel',
        parentLayout: 'auto'
    });

    createOverflowSuite({
        parentXtype: 'panel',
        childXtype: 'panel',
        parentLayout: 'vbox'
    });

    describe("misc overflow", function() {
        it("should layout with autoScroll + align: stretch + A shrink wrapped parallel item", function() {
            expect(function() {
                ct = new Ext.container.Container({
                    autoScroll: true,
                    layout: {
                        align: 'stretch',
                        type: 'vbox'
                    },
                    renderTo: Ext.getBody(),
                    width: 600,
                    height: 200,
                    items: [{
                        xtype: 'component',
                        height: 200,
                        html: 'Item'
                    }, {
                        xtype: 'component',
                        html: 'Component'
                    }]
                });
            }).not.toThrow();
        });
    });

});
